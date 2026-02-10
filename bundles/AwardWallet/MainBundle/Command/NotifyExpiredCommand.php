<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountExpiredEvent;
use AwardWallet\MainBundle\Event\PassportExpiredEvent;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\AbstractAccountExpirationTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\BalanceExpiration;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\PassportExpiration;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Manager\Ad\Options;
use AwardWallet\MainBundle\Repository\ProvidercouponRepository;
use AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount\Property;
use AwardWallet\MainBundle\Service\ElasticSearch\Client;
use AwardWallet\MainBundle\Service\ExpirationDate\ExpirationDate;
use AwardWallet\MainBundle\Service\ExpirationDate\Expire;
use AwardWallet\MainBundle\Service\LogProcessor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtObj;

class NotifyExpiredCommand extends Command
{
    protected static $defaultName = 'aw:email:expired';

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private Mailer $mailer;

    private AdManager $adManager;

    private EventDispatcherInterface $eventDispatcher;

    private ExpirationDate $expDate;

    private UsrRepository $userRep;

    private ProvidercouponRepository $couponRep;

    private UseragentRepository $uaRep;

    private ?string $testEmail;

    private bool $testMode = false;

    private int $processed = 0;

    private int $emails = 0;

    private Client $elasticClient;

    private array $excludeUsers = [];
    private Connection $connection;

    private ?\DateTime $now = null;

    public function __construct(
        LoggerInterface $statLogger,
        EntityManagerInterface $em,
        Mailer $mailer,
        AdManager $adManager,
        EventDispatcherInterface $eventDispatcher,
        ExpirationDate $expDate,
        Client $elasticClient,
        Connection $connection
    ) {
        $this->logger = new Logger('notify_expired_command', [new PsrHandler($statLogger)], [
            new LogProcessor('notify_expired_command', [], [], ['account', 'period', 'target', 'user', 'email']),
        ]);
        $this->em = $em;
        $this->mailer = $mailer;
        $this->adManager = $adManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->expDate = $expDate;

        $this->userRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->couponRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class);
        $this->uaRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        parent::__construct();
        $this->elasticClient = $elasticClient;
        $this->connection = $connection;
    }

    public function onEach(Expire $expire): void
    {
        $this->log('processing', $expire);
        $this->processed++;
        $this->sendEvents($expire);
    }

    public function filter(Expire $expire): bool
    {
        if ($this->shouldSkipNotification($expire)) {
            return false;
        }

        if ('U' === $expire->TargetKind) {
            if (EMAIL_NDR === $expire->EmailVerified) {
                $this->log('email ndr, skipping', $expire);

                return false;
            }

            if (
                !is_null($expire->Owner) && (
                    (
                        $expire->EmailFamilyMemberAlert !== 1
                        && (
                            $expire->uaSendEmails === 1 && !is_null($expire->uaEmail)
                        )
                    ) || (
                        $expire->uaSendEmails === 1 && !is_null($expire->uaEmail) && $expire->uaEmail === $expire->Email
                    )
                )
            ) {
                $this->log('email to user agent, skipping', $expire);

                return false;
            }
        }

        if ($expire->ProviderID === 128 && $expire->Days > 8) {
            $this->log('emiles, skipping, refs #6297', $expire);

            return false;
        }

        if ($this->testMode) {
            $this->log('test mode, skip sending', $expire);

            return false;
        }

        if ($expire->isExpiredPassport()) {
            $this->log('send expired passport', $expire);
            $this->sendExpiredPassportEmail($expire);

            return false;
        }

        if (in_array($expire->UserID, $this->excludeUsers)) {
            $this->log('user already received an email/push, skipping', $expire);

            return false;
        }

        return true;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Notifications of expiration of balances/passports')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by userId')
            ->addOption('accountId', 'a', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by accountId (no provider coupons)')
            ->addOption('date', 'b', InputOption::VALUE_REQUIRED, '<YYYY-MM-DD>, use this date instead of today')
            ->addOption('startUser', 'x', InputOption::VALUE_REQUIRED, 'starting from this user')
            ->addOption('endUser', 's', InputOption::VALUE_REQUIRED, 'ending on this user')
            ->addOption('allowTestProvider', 'r', InputOption::VALUE_NONE, 'allow test provider')
            ->addOption('testEmail', 'm', InputOption::VALUE_REQUIRED, 'send mail to this email instead of real address')
            ->addOption('testMode', 't', InputOption::VALUE_NONE, 'test mode, do not send anything, just log')
            ->addOption('exclude-sent-in-day', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD, exclude users who have already received an email/push at that day')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->logger;

        // filter by user
        if ($usersIds = $input->getOption('userId')) {
            $usersIds = array_map('intval', $usersIds);
            $this->expDate->setUsersIds($usersIds);
            $logger->info(sprintf('filter by userId: [%s]', implode(', ', $usersIds)));
        }

        // filter by accounts
        if ($accountIds = $input->getOption('accountId')) {
            $accountIds = array_map('intval', $accountIds);
            $this->expDate->setAccountIds($accountIds);
            $logger->info(sprintf('filter by accountId: [%s]', implode(', ', $accountIds)));
        }

        // start date
        if (!empty($date = $input->getOption('date'))) {
            $startDate = date_create($date);
            $logger->info(sprintf('start date: %s', $startDate->format('Y-m-d')));
        } else {
            $startDate = date_create();
            $logger->info('start date: now()');
            $logger->info('real date: ' . $this->expDate->getStartDate()->format("Y-m-d"));
        }

        $this->expDate->setStartDate($startDate);
        $this->now = $startDate;

        // start user
        if (!empty($startUser = $input->getOption('startUser'))) {
            $startUser = intval($startUser);
            $this->expDate->setStartUser($startUser);
            $logger->info(sprintf('start user: %d', $startUser));
        }

        // end user
        if (!empty($endUser = $input->getOption('endUser'))) {
            $endUser = intval($endUser);
            $this->expDate->setEndUser($endUser);
            $logger->info(sprintf('end user: %d', $endUser));
        }

        // allow test provider
        $allowTestProvider = $input->getOption('allowTestProvider');
        $this->expDate->setAllowTestProvider($allowTestProvider ? true : false);
        $logger->info(sprintf('allow test provider: %s', $allowTestProvider ? 'true' : 'false'));

        // test email
        if (!empty($testEmail = $input->getOption('testEmail'))) {
            $this->testEmail = $testEmail;
            $logger->info(sprintf('test email: %s', $this->testEmail));
        }

        // test mode
        $this->testMode = $input->getOption('testMode');
        $logger->info(sprintf('test mode: %s', $this->testMode ? 'true' : 'false'));

        if ($input->getOption('exclude-sent-in-day')) {
            foreach (explode(",", $input->getOption('exclude-sent-in-day')) as $day) {
                $this->excludeUsers = array_merge($this->excludeUsers, $this->loadExcludeUsersFromElastic($day));
            }
            $this->excludeUsers = array_unique($this->excludeUsers);
            $this->logger->info("excluding " . count($this->excludeUsers) . " users in total");
        }

        $this->reset();
        $this->start();

        $logger->info(sprintf('processed %d accounts, sent %d emails total', $this->processed, $this->emails));
        $output->writeln('done.');

        return 0;
    }

    private function start(): void
    {
        $stmt = $this->expDate->getStmt(ExpirationDate::MODE_EMAIL);

        foreach (
            stmtObj($stmt, Expire::class)
            ->onNth(100, function () {
                $this->em->clear();
                $this->logger->info("processed {$this->processed} accounts, mem: " . Helper::formatMemory(memory_get_usage(true)));
            })
            ->onEach([$this, 'onEach'])
            ->filter([$this, 'filter'])
            ->map(\Closure::fromCallable([$this->expDate, 'prepareExpire']))
            ->flatten(1) as $program
        ) {
            $this->sendExpiredBalancesEmail([$program]);
        }
    }

    private function sendExpiredPassportEmail(Expire $expire): void
    {
        $template = new PassportExpiration();
        $template->expiresInMonths = $expire->Months;
        $template->passport = $this->couponRep->find($expire->ID);
        $this->sendEmail($template, $expire);
    }

    private function sendExpiredBalancesEmail(array $programs): void
    {
        $providers = [];

        /** @var Property[] $ep */
        foreach ($programs as $ep) {
            if (
                isset($ep['ProviderID']) && !empty($ep['ProviderID']->getValue())
                && isset($ep['ProviderKind']) && !empty($ep['ProviderKind']->getValue())
                && isset($ep['ChangeCount']) && !empty($ep['ChangeCount']->getValue())
            ) {
                $providerID = $ep['ProviderID']->getValue();
                $kind = $ep['ProviderKind']->getValue();
                $changeCount = $ep['ChangeCount']->getValue();

                if (!isset($providers[$providerID])) {
                    $providers[$providerID] = [
                        'Kind' => $kind,
                        'ChangeCount' => $changeCount,
                    ];
                } elseif ($providers[$providerID]['ChangeCount'] < $changeCount) {
                    $providers[$providerID]['ChangeCount'] = $changeCount;
                }
            }
        }

        /** @var Property[] $firstProgram */
        $firstProgram = reset($programs);
        /** @var Expire $expire */
        $expire = $firstProgram['Expire']->getValue();

        $template = new BalanceExpiration();
        $template->accounts = $programs;
        $template->now = $this->now;
        // ads
        $opt = new Options(
            ADKIND_EMAIL,
            $this->userRep->find($expire->UserID),
            BalanceExpiration::getEmailKind()
        );
        $opt->flatData = $providers;
        $template->advt = $this->adManager->getAdvt($opt);
        $this->sendEmail($template, $expire);
    }

    private function sendEmail(AbstractAccountExpirationTemplate $template, Expire $expire): void
    {
        if (!$this->prepareEmailTemplate($template, $expire)) {
            return;
        }

        $message = $this->getEmailMessageByTemplate($template);
        $this->log(sprintf('mailing to %s', key($message->getTo())), $expire);
        $this->mailer->send($message);
        $this->emails++;
    }

    private function prepareEmailTemplate(AbstractAccountExpirationTemplate $template, Expire $expire): bool
    {
        $template->setDebug(!empty($this->testEmail));

        if (!empty($expire->UserAgentID)) {
            /** @var Useragent $ua */
            $ua = $this->uaRep->find($expire->UserAgentID);

            if ($ua) {
                $template->toFamilyMember($ua);

                return true;
            } else {
                $this->log('user agent not found', $expire);
            }
        } else {
            /** @var Usr $user */
            $user = $this->userRep->find($expire->UserID);

            if ($user) {
                if ($expire->AccountLevel === ACCOUNT_LEVEL_BUSINESS) {
                    /** @var Usr $admin */
                    $admin = $this->userRep->findOneByEmail($expire->Email);

                    if ($admin) {
                        $template->toUser($admin, true);
                        $template->businessRecipient = $user;

                        return true;
                    } else {
                        $this->log('admin not found', $expire);
                    }
                } else {
                    $template->toUser($user, false);

                    return true;
                }
            } else {
                $this->log('user not found', $expire);
            }
        }

        return false;
    }

    private function getEmailMessageByTemplate(AbstractAccountExpirationTemplate $template): \Swift_Message
    {
        $message = $this->mailer->getMessageByTemplate($template);

        if (!empty($this->testEmail)) {
            $message->setTo($this->testEmail);
            $message->setCc([]);
            $message->setBcc([]);
        }

        return $message;
    }

    private function shouldSkipNotification(Expire $expire): bool
    {
        if (
            $expire->Kind === 'S'
            && $expire->ProviderID == 84
            && isset($expire->Notes)
            && stripos($expire->Notes, 'Digital Entertainment Credit') !== false
            && $expire->Balance != 20
        ) {
            $this->log('skip Digital Entertainment Credit, balance != 20', $expire);

            return true;
        }

        return false;
    }

    private function sendEvents(Expire $expire): void
    {
        if ($this->shouldSkipNotification($expire)) {
            return;
        }

        if ('U' === $expire->TargetKind) {
            switch ($expire->Kind) {
                case 'A':
                    $this->eventDispatcher->dispatch(
                        new AccountExpiredEvent([Account::class, $expire->ID], $expire->UserID),
                        'aw.account.expire'
                    );

                    break;

                case 'C': case 'T': case 'P':
                    if ($expire->isExpiredPassport()) {
                        $this->eventDispatcher->dispatch(
                            new PassportExpiredEvent(
                                $expire->UserID,
                                $expire->ID,
                                $expire->Months,
                                $expire->UserName
                            ),
                            'aw.passport.expire'
                        );
                    } else {
                        $this->eventDispatcher->dispatch(
                            new AccountExpiredEvent([Providercoupon::class, $expire->ID], $expire->UserID),
                            'aw.account.expire'
                        );
                    }

                    break;

                case 'S': case 'D':
                    $this->eventDispatcher->dispatch(
                        new AccountExpiredEvent([Subaccount::class, $expire->ID], $expire->UserID),
                        'aw.account.expire'
                    );

                    break;
            }
        }
    }

    private function log(string $string, Expire $expire): void
    {
        $isPassport = $expire->isExpiredPassport();

        $this->logger->info($string, [
            'account' => sprintf('%s%s', $expire->Kind, $expire->ID),
            'period' => sprintf('%s %s', $isPassport ? $expire->Months : $expire->Days, $isPassport ? 'months' : 'days'),
            'target' => $expire->TargetKind,
            'user' => $expire->UserName,
            'email' => $expire->Email,
        ]);
    }

    private function reset()
    {
        $this->processed = 0;
        $this->emails = 0;
    }

    private function loadExcludeUsersFromElastic(string $date): array
    {
        $this->logger->info("loading excluded users from elastic for day: $date");

        $sentEmails = it($this->elasticClient->query(
            'message: "email sent" AND context.template: balance_expiration',
            new \DateTime($date),
            (new \DateTime($date))->modify("+1 day"),
            1000
        ))
            ->map(fn (array $record) => $record['context']['UserID'])
            ->toArray();
        $this->logger->info("excluding " . count($sentEmails) . " users who already received an email");

        $sentPushes = it($this->elasticClient->query(
            'message: "publishing push to device" AND context.serialized: *account_expiration*',
            new \DateTime($date),
            (new \DateTime($date))->modify("+1 day"),
            1000
        ))
            ->map(fn (array $record) => $this->connection->fetchOne("select UserID from MobileDevice where MobileDeviceID = ?", [$record['context']['MobileDeviceID']]))
            ->toArray();
        $this->logger->info("excluding " . count($sentPushes) . " users who already received a push");

        $result = array_unique(array_merge($sentEmails, $sentPushes));
        $this->logger->info("excluding " . count($result) . " users for the day");

        return $result;
    }
}
