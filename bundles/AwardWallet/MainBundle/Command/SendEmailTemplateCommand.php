<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MailerCollection;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProviderLoader;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\Events;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\ProgressEvent;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\SendEvent;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\UsersFoundEvent;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;
use AwardWallet\MainBundle\Service\User\StateNotification;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendEmailTemplateCommand extends Command
{
    public static $defaultName = 'aw:send-email:template';

    private $showProgress = false;

    /**
     * @var ProgressBar
     */
    private $progressBar;
    /**
     * @var bool
     */
    private $countOnly;

    private $limit;
    /**
     * @var int
     */
    private $found = 0;
    /**
     * @var int
     */
    private $counter = 0;
    private bool $isDryRun = true;
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UsrRepository
     */
    private $userRep;

    /**
     * @var EmailTemplate
     */
    private $emailTemplate;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var EmailLog
     */
    private $emailLog;
    /**
     * @var AppBot
     */
    private $appBot;
    private DataProviderLoader $dataProviderLoader;
    private array $relaysParameters;
    private MailerCollection $mailerCollection;
    private StateNotification $stateNotification;
    private LocalizeService $localizeService;
    private ClockInterface $clock;

    public function __construct(
        AppBot $appBot,
        EntityManagerInterface $entityManager,
        EmailLog $emailLog,
        DataProviderLoader $dataProviderLoader,
        array $relaysParameters,
        MailerCollection $mailerCollection,
        StateNotification $stateNotification,
        LocalizeService $localizeService,
        ClockInterface $clock
    ) {
        parent::__construct();
        $this->appBot = $appBot;
        $this->em = $entityManager;
        $this->emailLog = $emailLog;
        $this->dataProviderLoader = $dataProviderLoader;
        $this->relaysParameters = $relaysParameters;
        $this->mailerCollection = $mailerCollection;
        $this->stateNotification = $stateNotification;
        $this->localizeService = $localizeService;
        $this->clock = $clock;
    }

    public function onUsersFound(UsersFoundEvent $event)
    {
        $this->found = $event->getCountUsers();

        if ($this->found == 0) {
            $this->io->error("Users have not been found");

            return;
        }
        $this->io->success(sprintf("Found %d users", $this->found));

        if ($this->countOnly) {
            exit(0);
        }

        if (!$this->isDryRun) {
            $this->stateNotification->sendState(Slack::CHANNEL_AW_ALL);
            $this->appBot->send(
                Slack::CHANNEL_AW_ALL,
                'Frontend » send-offer - (' . $this->emailTemplate->getCode() . ') started sending emails to ' . $this->localizeService->formatNumber($this->found) . ' users'
                . "\n<{$this->emailTemplate->getDashboardLink()}|Dashboard>"
            );
        }

        $this->io->text("Dashboard: {$this->emailTemplate->getDashboardLink()}");
    }

    public function onProgress(ProgressEvent $event)
    {
        if (!$this->progressBar) {
            $max = !empty($this->limit) && $this->limit < $this->found ? $this->limit : $this->found;
            $this->progressBar = $this->io->createProgressBar($max);
            $this->progressBar->setFormat(" %message% %done%\n %current%/%max% <info>[%bar%]</info> %percent:3s%% | %elapsed:6s%/%remaining% | %memory:6s%");
            $this->progressBar->setBarWidth(70);
            $this->progressBar->setMessage('', 'message');
            $this->progressBar->setMessage('', 'done');
            $this->progressBar->start();
        } else {
            gc_collect_cycles();
            $this->progressBar->advance(1);
        }
    }

    public function onPreSend(SendEvent $event)
    {
        $event->getMessage()
            ->setBcc([])
            ->setCc([]);
        $text = implode(", ", array_keys(
            $event->getMessage()->getTo())) .
            sprintf('   [%d of %d] %.2f%%',
                ++$this->counter,
                $this->found,
                round(($this->counter / $this->found) * 100, 2)
            );

        if ($this->progressBar) {
            $this->progressBar->setMessage($text, 'message');
        } else {
            $this->io->writeln($text);
        }
    }

    public function onPostSend(SendEvent $event)
    {
        if ($this->progressBar) {
            if ($event->isSuccess()) {
                $this->progressBar->setMessage('<info>ok</info>', 'done');
            } else {
                $this->progressBar->setMessage('<error>error</error>', 'done');
            }
        }

        if ($event->isSuccess() && !$this->isDryRun) {
            $to = $event->getMessage()->getTo();

            foreach (array_keys($to) as $email) {
                $userId = $this->em->getConnection()->fetchOne('select UserID from Usr where Email = ?', [$email]);

                if ($userId !== false) {
                    $this->emailLog->recordEmailToLog((int) $userId, $this->emailTemplate->getEmailTemplateID());
                }
            }
        }

        if (($this->counter > 0) && $this->counter % 1000 === 0) {
            $this->em->clear();
        }
    }

    protected function configure()
    {
        $this
            ->setDescription('Send emails from database')
            ->addArgument('code', InputArgument::REQUIRED, 'code from "EmailTemplate" table')
            ->addOption('userId', 'u', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'filter by userId')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'max email addresses')
            ->addOption('progress', 'p', InputOption::VALUE_NONE, 'show progress bar')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'dry run mode, will not send emails and modify database')
            ->addOption('count', 'c', InputOption::VALUE_NONE, 'count only')
            ->addOption('relay', null, InputOption::VALUE_REQUIRED, 'mail relay, see relays in parameters.yml')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->counter = 0;
        $this->io = new SymfonyStyle($input, $output);
        $this->userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $templateRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\EmailTemplate::class);
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $code = $input->getArgument('code');

        if (!($this->emailTemplate = $templateRep->findOneByCode($code))) {
            $this->io->error(sprintf("Email template '%s' was not found", $code));

            return 1;
        }

        $this->countOnly = $input->getOption('count');

        if (!$this->emailTemplate->isEnabled() && !$this->countOnly) {
            $this->io->error(sprintf("Email template '%s' was not enabled", $code));

            return 2;
        }

        // Data Provider
        try {
            /** @var AbstractDataProvider $dataProvider */
            $dataProvider = $this->dataProviderLoader
                ->getDataProviderByEmailTemplate($this->emailTemplate);
        } catch (\InvalidArgumentException $e) {
            $this->io->error(sprintf("Data provider '%s' was not found", $code));

            return 4;
        }

        $this->isDryRun = $input->getOption('dry-run');

        if ($this->isDryRun) {
            $this->io->text("Operating in dry-run mode, no emails will be sent.");
        } else {
            $this->io->text("Operating in normal mode, emails will be sent.");

            if (!$input->getOption('userId')) {
                $this->io->text("You have 30 seconds to cancel the operation.");
                $timeBudget = 30;
                $step = 5;

                while ($timeBudget > 0) {
                    \sleep($step);
                    $timeBudget -= $step;

                    if ($timeBudget >= $step) {
                        $this->io->text("{$timeBudget} seconds left to cancel the operation.");
                    } else {
                        break;
                    }
                }
            }
        }

        $this->io->text("Configuring data provider...");
        // Query Options
        $queryOptions = $dataProvider->getQueryOptions();

        if ($userIds = $input->getOption('userId')) {
            $this->io->text(sprintf("filter by users: %s", implode(", ", $userIds)));
            array_walk($queryOptions, function ($option) use ($userIds) {
                /** @var Options $option */
                $option->userId = array_merge($option->userId, $userIds);
            });
        }

        [$exclusionEmails, $exclusionProviders] = DataProviderLoader::expandExclusions($this->emailTemplate);
        array_walk($queryOptions, function ($option) use ($exclusionEmails, $exclusionProviders) {
            /** @var Options $option */
            $option->exclusionDataProviders = $exclusionProviders;
            $option->hasNotEmails = $exclusionEmails;
            $option->excludedCreditCards = $this->emailTemplate->getExcludedCreditCards();
        });

        $dataProvider->setQueryOptions($queryOptions);

        if (($this->limit = $input->getOption('limit')) && is_numeric($this->limit)) {
            $this->limit = intval($this->limit);
            $this->io->text(sprintf("max email addresses: %d", $this->limit));
        }

        $this->showProgress = $input->getOption('progress');

        $dataProvider->getDispatcher()->addListener(Events::EVENT_USERS_FOUND, [$this, "onUsersFound"]);
        $dataProvider->getDispatcher()->addListener(Events::EVENT_PRE_SEND, [$this, "onPreSend"]);
        $dataProvider->getDispatcher()->addListener(Events::EVENT_POST_SEND, [$this, "onPostSend"]);

        if ($this->showProgress) {
            $dataProvider->getDispatcher()->addListener(Events::EVENT_PROGRESS, [$this, "onProgress"]);
        }
        $dataProvider->setOptions(array_merge($dataProvider->getOptions(), [Mailer::OPTION_DIRECT => false]));

        $relay = $input->getOption('relay');

        if ($relay !== null) {
            if (!isset($this->relaysParameters[$relay])) {
                $this->io->error("unknown relay: $relay, select one of: " .
                                 implode(", ", array_keys($this->relaysParameters)));

                return 3;
            }
            $dataProvider->setOptions(array_merge($dataProvider->getOptions(), [Mailer::OPTION_RELAY => $relay]));
        }

        // Mailer
        $this->mailerCollection->setDataProvider($dataProvider);

        if (!empty($this->limit)) {
            $this->mailerCollection->setLimit($this->limit);
        }

        $this->io->writeln("Searching for users...");
        $countingDuration = $this->clock->stopwatch(fn () => $dataProvider->__init(true));
        $this->io->writeln("Searching for users took: {$countingDuration->scaleToSeconds()}");
        $this->io->text("Preparing users for sending...");
        $this->mailerCollection->send($this->isDryRun, $this->isDryRun);

        if ($this->showProgress && $this->progressBar) {
            $this->progressBar->setMessage('', 'message');
            $this->progressBar->setMessage('', 'done');
            $this->progressBar->finish();
            $this->io->newLine(2);
            $this->progressBar = null;
        }
        $this->io->text("dashboard: {$this->emailTemplate->getDashboardLink()}");
        $this->io->success("done.");

        $timeStart = new \DateTime('@' . $_SERVER['REQUEST_TIME']);
        $time = $timeStart->diff(new \DateTime('@' . time()));

        if ($this->isDryRun) {
            $sparkpostSuccess = true;
            $sparkpostMessage = 'Sparkpost stats check skipped in dry-run mode';
        } else {
            [$sparkpostSuccess, $sparkpostMessage] = $this->checkSparkpostStats($relay ?? 'sparkpost');
        }

        $status = 'successfully finished sending';

        if (!$sparkpostSuccess) {
            $status = 'finished sending with errors,';
            $this->io->error($sparkpostMessage);
        }

        if ($sparkpostSuccess && $sparkpostMessage) {
            $this->io->text($sparkpostMessage);
        }

        if (!$this->isDryRun) {
            $this->appBot->send(Slack::CHANNEL_AW_ALL, 'Frontend » send-offer - (' . $this->emailTemplate->getCode() . ') ' . $status . ' ' . $this->localizeService->formatNumber($this->counter) . ' emails after '
                . ($time->h > 0 ? $time->h . ' hours ' : '')
                . ($time->i > 0 ? $time->i . ' min ' : '')
                . (0 == $time->h && 0 == $time->i ? $time->s . ' seconds ' : '')
                . "\n<{$this->emailTemplate->getDashboardLink()}|Dashboard>"
                . ($sparkpostMessage ? "\n" . $sparkpostMessage : "")
            );
        }

        return $sparkpostSuccess ? 0 : 1;
    }

    /**
     * @return [bool, string] - [bool success, string message]
     */
    private function checkSparkpostStats(string $relay): array
    {
        $apiKey = getenv('SPARKPOST_STATS_API_KEY');

        if ($apiKey === "IGNORE") {
            return [true, null];
        }

        if ($apiKey === "" || $apiKey === false) {
            return [false, "Failed to get sparkpost api key"];
        }

        if (in_array($relay, ['nontransactional_amazon']) === 0) {
            return [true, null];
        }

        if (in_array($relay, ['nontransactional_direct', 'nontransactional_sparkpost_and_ses']) === 0) { // sparkpost-and-direct, sparkpost-and-amazon
            $expectedCount = round($this->counter / 3);
        }

        $expectedCount = $this->counter;
        $this->io->text("checking sparkpost stats for {$this->emailTemplate->getCode()}-{$this->emailTemplate->getDataProvider()}, expecting $expectedCount");

        $stats = null;
        $failedToDecode = false;
        $startTime = microtime(true);

        // wait sparkpost stats
        while ((microtime(true) - $startTime) < 300) {
            $json = curlRequest(
                "https://api.sparkpost.com/api/v1/metrics/deliverability?campaigns={$this->emailTemplate->getCode()}-{$this->emailTemplate->getDataProvider()}&from={$this->emailTemplate->getCreateDate()->format("Y-m-d")}T00:00&metrics=count_sent,count_accepted,count_injected",
                60,
                [
                    CURLOPT_HTTPHEADER => [
                        "Authorization: {$apiKey}",
                        "Content-Type: application/json",
                    ],
                ],
            );

            $stats = @json_decode($json, true);

            $failedToDecode = $stats === null || !isset($stats['results'][0]['count_injected']);

            if ($failedToDecode) {
                $this->io->text("failed to decode sparkpost stats: " . $json);
            }

            if (!$failedToDecode && ($stats['results'][0]['count_injected'] >= $expectedCount)) {
                break;
            }

            sleep(10);
        }

        if ($failedToDecode) {
            return [false, "failed to decode sparkpost stats: $json"];
        }

        $this->io->text("we've sent {$this->counter} messages, expected {$expectedCount} messages sent with sparkpost");
        $this->io->text("sparkpost stats: " . json_encode($stats['results'][0]));

        // sparkpost calculate stats with delay. so we expect at least 2/3 of messages to be injected
        if ($stats['results'][0]['count_injected'] < ($expectedCount * 0.66)) {
            return [false, "sparkpost stats mismatch: injected: {$stats['results'][0]['count_injected']}, sent: {$this->counter}, expected at least: {$expectedCount}"];
        }

        $countInjected = $this->localizeService->formatNumber($stats['results'][0]['count_injected']);
        $countSent = $this->localizeService->formatNumber($stats['results'][0]['count_sent']);
        $countAccepted = $this->localizeService->formatNumber($stats['results'][0]['count_accepted']);

        return [true, "sparkpost stats: injected: {$countInjected}, sent: {$countSent}, accepted: {$countAccepted}"];
    }
}
