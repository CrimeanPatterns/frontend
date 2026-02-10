<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\RecurringPaymentFailed;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Globals\Cart\UpgradeCodeGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

class MailAt201Command extends Command
{
    public static $defaultName = 'aw:billing:mail-at-201';
    private Connection $connection;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private Mailer $mailer;
    private UpgradeCodeGenerator $upgradeCodeGenerator;

    public function __construct(
        Connection $connection,
        LoggerInterface $paymentLogger,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        Mailer $mailer,
        UpgradeCodeGenerator $upgradeCodeGenerator
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->logger = $paymentLogger;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->mailer = $mailer;
        $this->upgradeCodeGenerator = $upgradeCodeGenerator;
    }

    public function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('apply', null, InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("loading users");
        $sql = "
        select 
            u.UserID, 
            u.Email, 
            u.FirstName, 
            u.LastName, 
            Subscription, 
            SubscriptionType, 
            u.DefaultTab as CustomerId, 
            u.PayPalRecurringProfileID as PaymentMethodId,
            At201ExpirationDate,
            not isnull(gul.SiteGroupID) as InGroup 
        from 
            Usr u 
            left join GroupUserLink gul on u.UserID = gul.UserID and gul.SiteGroupID = 75
        where 
            u.Email in ('desmarteau@gmail.com', 'legwork-coyness-0n@icloud.com', 'moreillyvt@gmail.com', 'betsypv@aol.com', 'rpeetz@hotmail.com', 'tlamm49@gmail.com', 'lmaxsc@yahoo.com', 'kayhooshmand@gmail.com', 'james.buzek@gmail.com', 'bear@cybervalkyrie.com', 'george.d.peterson@gmail.com', 'j.mleczko2@gmail.com', 'jessicalovesnoonday@gmail.com', 'aguilarcoupons2011@gmail.com', 'jon@pickleballtrips.com', 'stlh173@aol.com', 'ludeans@yahoo.com', 'turquoiseandtangerine@yahoo.com', 'jflores008@gmail.com', 'stevenkeane.lv@gmail.com', 'adam.jorde@gmail.com', 'mcgeeh@gmail.com', 'elite758@yahoo.com', 'george@peden.ws', 'ccarte45@gmail.com', 'macksperling@gmail.com', 'strattondo@gmail.com', 'kenh412@hotmail.com', 'Robin.Milonas@comcast.net', 'zwojtowi@gmail.com', 'jessicabsimon12@gmail.com', 'mtmtshultz@sbcglobal.net', 'lauriesbridges@yahoo.com', 'omalley.christine@gmail.com', 'jessicapeters@outlook.com', 'slamcdade@me.com', 'Charisse.loder@gmail.com', 'travelwidstom@gmail.com', 'brigid.hallinan@gmail.com', 'tiffanyatran5@gmail.com', 'davidj988@gmail.com', 'olgaflomin@yahoo.com', 'shieccamadzima@yahoo.com', 'aaronandamy2015@gmail.com', 'chris@chrishutchins.com', 'drsrinivasu@outlook.com', 'flashstash@gmail.com', 'tylerbc24@sbcglobal.net', 'tarikmiranda@hotmail.com', 'nagendra.mogasala@gmail.com', 'ikiefer@comcast.net', 'sijaz68@gmail.com', 'ujjwaltravelplans@outlook.com', 'rjccn5785@gmail.com', 'dixonge@gmail.com', 'patti.marcum@gmail.com', 'rickysfun@yahoo.com', 'skekbote@hotmail.com', 'mei.hqiang@gmail.com', 'desmarteau@gmail.com', 'carlandtheresa@gmail.com', 'bernicejbruno@gmail.com', 'spcoomer@gmail.com', 'schillertimothy@gmail.com', 'mbcarter74@gmail.com') 
            and (SubscriptionType is null)
        ";

        if ($input->getOption('userId')) {
            $sql .= "and u.UserID = " . (int) $input->getOption('userId');
        }

        if ($input->getOption('limit')) {
            $sql .= "limit " . (int) $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("loaded " . count($users) . " users");

        foreach ($users as $user) {
            $this->processUser($user);
        }

        $this->logger->info("done");

        return 0;
    }

    private function processUser(array $user): void
    {
        $this->logger->info("processing {$user['UserID']}, {$user['Email']}, {$user['FirstName']} {$user['LastName']}, at 201 exp date: {$user['At201ExpirationDate']}");
        $userEntity = $this->entityManager->find(Usr::class, $user['UserID']);
        // we repeat last subscription, no matter is it now active or not
        $subscription = $this->entityManager->getRepository(Cart::class)->getActiveAwSubscription($userEntity, false);

        if (is_null($subscription->getAT201Item())) {
            throw new \Exception("last subscription is not at 201");
        }

        $template = new RecurringPaymentFailed($userEntity);
        $template->paymentSource = RecurringPaymentFailed::PAYMENT_SOURCE_CC;
        $template->ccNumber = "xxxx";
        $template->amount = SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AT201, $subscription->getAT201Item()::DURATION);
        $template->throughDate = new \DateTime('@' . strtotime($subscription->getAT201Item()::DURATION, max(time(), strtotime($subscription->getAT201Item()::DURATION, $subscription->getPaydate()->getTimestamp()))));
        $template->paymentLink = $this->router->generate('aw_cart_change_payment_method_email', ['userId' => $user['UserID'], "hash" => $this->upgradeCodeGenerator->generateCode($userEntity)]);
        $template->subscriptionType = Usr::SUBSCRIPTION_TYPE_AT201;
        $template->subscriptionPeriod = $subscription->getAT201Item()::DURATION;
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message, [Mailer::OPTION_SKIP_DONOTSEND => true]);
    }
}
