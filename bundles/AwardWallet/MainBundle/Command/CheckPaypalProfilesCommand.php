<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Paypal\AgreementHack;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use PayPal\Exception\PayPalConnectionException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckPaypalProfilesCommand extends Command
{
    public static $defaultName = 'aw:check-paypal-profiles';
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PaypalRestApi
     */
    private $paypal;

    public function __construct(Connection $connection, LoggerInterface $paymentLogger, PaypalRestApi $paypal)
    {
        parent::__construct();
        $this->logger = $paymentLogger;
        $this->connection = $connection;
        $this->paypal = $paypal;
    }

    public function configure()
    {
        $this
            ->addOption('free', null, InputOption::VALUE_NONE, 'check only free users')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'remove cancelled profiles')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("checking paypal profiles");
        $found = 0;
        $removed = 0;

        $sql = "select UserID, PaypalRecurringProfileID from Usr where Subscription = " . Usr::SUBSCRIPTION_PAYPAL;

        if ($input->getOption('free')) {
            $sql .= " and AccountLevel = " . ACCOUNT_LEVEL_FREE;
        }

        $apiContext = $this->paypal->getApiContext();

        $q = $this->connection->executeQuery($sql);

        foreach ($q->fetchAll(FetchMode::ASSOCIATIVE) as $row) {
            $this->logger->info("checking user {$row['UserID']}, profile: {$row['PaypalRecurringProfileID']}");

            try {
                $agreement = AgreementHack::get($row['PaypalRecurringProfileID'], $apiContext);

                if ($agreement->state !== "Active") {
                    $this->logger->info("profile is not active, user id: {$row['UserID']}, profile: {$row['PaypalRecurringProfileID']}, state: {$agreement->state}");
                    $found++;

                    if ($agreement->state === "Cancelled" && $input->getOption('remove')) {
                        $this->logger->info("removing cancelled profile, user id: {$row['UserID']}, profile: {$row['PaypalRecurringProfileID']}");
                        $this->connection->executeUpdate("update Usr set Subscription = null, PaypalRecurringProfileID = null where UserID = ?", [$row['UserID']]);
                        $removed++;
                    }
                }
            } catch (PayPalConnectionException $e) {
                $data = @json_decode($e->getData(), true);

                if (!empty($data['name']) && $data['name'] == 'INVALID_PROFILE_ID') {
                    $this->logger->info("profile not found, user: {$row['UserID']}, profile: {$row['PaypalRecurringProfileID']}");
                } else {
                    throw $e;
                }
            }
        }

        $this->logger->info("done, found: $found, removed: $removed");

        return 0;
    }
}
