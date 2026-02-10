<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use Doctrine\Common\Annotations\Reader;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsReq;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FillPaypalNextBillingDateCommand extends Command
{
    protected static $defaultName = 'aw:billing:fill-paypal-next-billing-date';

    private LoggerInterface $logger;
    private PayPalAPIInterfaceServiceService $paypal;
    private Connection $connection;
    private UsrRepository $usrRepository;

    private array $errors = [];
    private EntityManagerInterface $entityManager;

    public function __construct(
        PaypalSoapApi $paypalSoapApi,
        LoggerInterface $logger,
        Connection $connection,
        Reader $reader,
        UsrRepository $usrRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->paypal = $paypalSoapApi->getPaypalService();
        $this->connection = $connection;
        $this->reader = $reader;
        $this->usrRepository = $usrRepository;
        $this->entityManager = $entityManager;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updateAll($input);

        if (count($this->errors) > 0) {
            $this->logger->error("we got " . count($this->errors) . " errors");

            foreach ($this->errors as $error) {
                $this->logger->info($error);
            }
        } else {
            $this->logger->info("success");
        }

        return count($this->errors) ? 1 : 0;
    }

    protected function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('start-from', null, InputOption::VALUE_REQUIRED)
            ->addOption('before', null, InputOption::VALUE_REQUIRED)
            ->addOption('fix-missing', null, InputOption::VALUE_NONE)
        ;
    }

    private function updateAll(InputInterface $input)
    {
        $this->logger->info("loading users");
        $sql = "
            select 
                UserID, 
                PaypalRecurringProfileID
            from
                Usr 
            where
                Subscription = " . Usr::SUBSCRIPTION_PAYPAL . "
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

        if ($input->getOption('fix-missing')) {
            $sql .= " and NextBillingDate is null";
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users");

        $corrected = 0;
        $row = 0;

        foreach ($users as $user) {
            if ($this->processUser($user, $input)) {
                $corrected++;
            }

            $row++;

            if (($row % 1000) === 0) {
                $this->entityManager->clear();
            }
        }

        $this->logger->info("processed " . count($users) . " users, corrected: {$corrected}");
    }

    private function processUser(array $user, InputInterface $input): bool
    {
        $nextBillingDate = $this->readNextBillingDate($user['PaypalRecurringProfileID']);
        $userEntity = $this->usrRepository->find($user['UserID']);

        if ($userEntity->getNextBillingDate() !== null && $userEntity->getNextBillingDate()->format("Y-m-d") === $nextBillingDate->format("Y-m-d")) {
            $this->logger->info("user {$user["UserID"]} already has correct next billing date");

            return false;
        }
        $userEntity->setNextBillingDate($nextBillingDate);
        $this->logger->info("user {$user["UserID"]}, next billing date: " . $nextBillingDate->format('Y-m-d'));
        $this->entityManager->flush();

        return true;
    }

    private function readNextBillingDate(string $profileId): \DateTime
    {
        $req = new GetRecurringPaymentsProfileDetailsReq();
        $req->GetRecurringPaymentsProfileDetailsRequest = new GetRecurringPaymentsProfileDetailsRequestType();
        $req->GetRecurringPaymentsProfileDetailsRequest->ProfileID = $profileId;
        $response = $this->paypal->GetRecurringPaymentsProfileDetails($req);

        return new \DateTime($response->GetRecurringPaymentsProfileDetailsResponseDetails->RecurringPaymentsSummary->NextBillingDate);
    }

    private function addError(string $error)
    {
        $this->logger->error($error);
        $this->errors[] = $error;
    }
}
