<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\EBLBaseComponents\ManageRecurringPaymentsProfileStatusRequestDetailsType;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsReq;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsRequestType;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusReq;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResumePaypalCommand extends Command
{
    protected static $defaultName = 'aw:billing:resume-paypal';

    private ContextAwareLoggerWrapper $logger;
    private Connection $connection;
    private EntityManagerInterface $entityManager;
    private PayPalAPIInterfaceServiceService $paypal;

    private array $errors = [];

    public function __construct(
        LoggerInterface $paymentLogger,
        Connection $connection,
        EntityManagerInterface $entityManager,
        PaypalSoapApi $paypalSoapApi
    ) {
        parent::__construct();

        $this->logger = new ContextAwareLoggerWrapper($paymentLogger);
        $this->logger->pushContext(['worker' => 'ResumePaypalCommand']);
        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->paypal = $paypalSoapApi->getPaypalService();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info("starting ResumePaypalCommand");
        $users = $this->loadUsers($input);

        foreach ($users as $user) {
            $this->processUser($user["UserID"], $input);
        }

        $this->showErrors();
        $this->logger->info("finished ResumePaypalCommand");

        return count($this->errors) ? 1 : 0;
    }

    protected function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('start-from', null, InputOption::VALUE_REQUIRED)
            ->addOption('before', null, InputOption::VALUE_REQUIRED)
        ;
    }

    private function loadUsers(InputInterface $input): array
    {
        $this->logger->info("loading users");
        $sql = "
            select 
                UserID, 
                PaypalRecurringProfileID,
                SubscriptionPeriod,
                NextBillingDate,
                PlusExpirationDate,
                PaypalSuspendedUntilDate
            from
                Usr 
            where
                Subscription = " . Usr::SUBSCRIPTION_PAYPAL . "
                and PaypalSuspendedUntilDate <= now()
        ";

        if ($input->getOption('userId')) {
            $sql .= " and UserID = " . (int) $input->getOption('userId');
        }

        if ($input->getOption('start-from')) {
            $sql .= " and UserID >= " . (int) $input->getOption('start-from');
        }

        if ($input->getOption('before')) {
            $sql .= " and UserID < " . (int) $input->getOption('before');
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users");

        return $users;
    }

    private function processUser(int $userId, InputInterface $input): void
    {
        $user = $this->entityManager->find(Usr::class, $userId);
        $this->logger->info("getting next billing date for paypal profile {$user->getPaypalrecurringprofileid()}", ["UserID" => $user->getId()]);
        $req = new GetRecurringPaymentsProfileDetailsReq();
        $req->GetRecurringPaymentsProfileDetailsRequest = new GetRecurringPaymentsProfileDetailsRequestType();
        $req->GetRecurringPaymentsProfileDetailsRequest->ProfileID = $user->getPaypalrecurringprofileid();

        $response = $this->paypal->GetRecurringPaymentsProfileDetails($req);

        if ($response->Ack !== 'Success') {
            throw new \Exception("error reading paypal profile: " . json_encode($response) . ", UserID: {$user->getId()}");
        }

        if ($response->GetRecurringPaymentsProfileDetailsResponseDetails->ProfileStatus !== "Suspended") {
            throw new \Exception("paypal profile {$user->getPaypalrecurringprofileid()} has not known state: {$response->GetRecurringPaymentsProfileDetailsResponseDetails->ProfileStatus}, UserID: {$user->getId()}");
        }

        $nextBillingDate = new \DateTime($response->GetRecurringPaymentsProfileDetailsResponseDetails->RecurringPaymentsSummary->NextBillingDate);

        if ($nextBillingDate->format("Y-m-d") !== $user->getNextBillingDate()->format("Y-m-d")) {
            throw new \Exception("next billing date for paypal profile {$user->getPaypalrecurringprofileid()} is: {$nextBillingDate->format("Y-m-d")}, does not match db: {$user->getNextBillingDate()->format("Y-m-d")}, UserID: {$user->getId()}");
        }

        $req = new ManageRecurringPaymentsProfileStatusReq();
        $req->ManageRecurringPaymentsProfileStatusRequest = new ManageRecurringPaymentsProfileStatusRequestType();
        $req->ManageRecurringPaymentsProfileStatusRequest->ManageRecurringPaymentsProfileStatusRequestDetails = new ManageRecurringPaymentsProfileStatusRequestDetailsType();
        $req->ManageRecurringPaymentsProfileStatusRequest->ManageRecurringPaymentsProfileStatusRequestDetails->ProfileID = $user->getPaypalrecurringprofileid();
        $req->ManageRecurringPaymentsProfileStatusRequest->ManageRecurringPaymentsProfileStatusRequestDetails->Action = "Reactivate";

        $response = $this->paypal->ManageRecurringPaymentsProfileStatus($req);

        if ($response->Ack !== 'Success') {
            throw new \Exception("error activating paypal profile: " . json_encode($response) . ", UserID: $userId");
        }

        $user->setPaypalSuspendedUntilDate(null);
        $this->entityManager->flush();
        $this->logger->info("paypal profile reactivated", ["UserID" => $userId]);
    }

    private function addError(string $error)
    {
        $this->logger->error($error);
        $this->errors[] = $error;
    }

    private function showErrors()
    {
        foreach ($this->errors as $error) {
            $this->logger->info($error);
        }

        if (count($this->errors) > 0) {
            $this->logger->error("we got " . count($this->errors) . " errors");
        } else {
            $this->logger->info("success");
        }
    }
}
