<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use Doctrine\Common\Annotations\Reader;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\UpdateRecurringPaymentsProfileRequestDetailsType;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsReq;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsRequestType;
use PayPal\PayPalAPI\UpdateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\UpdateRecurringPaymentsProfileRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangePaypalAmountCommand extends Command
{
    protected static $defaultName = 'aw:billing:change-paypal-amount';

    private LoggerInterface $logger;
    private PayPalAPIInterfaceServiceService $paypal;
    private Connection $connection;
    private CartRepository $cartRepository;
    private UsrRepository $usrRepository;

    private array $errors = [];
    private EntityManagerInterface $entityManager;

    public function __construct(
        PaypalSoapApi $paypalSoapApi,
        LoggerInterface $logger,
        Connection $connection,
        Reader $reader,
        CartRepository $cartRepository,
        UsrRepository $usrRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->paypal = $paypalSoapApi->getPaypalService();
        $this->connection = $connection;
        $this->reader = $reader;
        $this->cartRepository = $cartRepository;
        $this->usrRepository = $usrRepository;
        $this->entityManager = $entityManager;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('profile-id')) {
            $this->updateSingleProfile($input);
        } else {
            $this->updateAll($input);
        }

        foreach ($this->errors as $error) {
            $this->logger->error($error);
        }

        if (count($this->errors) > 0) {
            $this->logger->error("we got " . count($this->errors) . " errors");
        } else {
            $this->logger->info("success");
        }

        return count($this->errors) ? 1 : 0;
    }

    protected function configure()
    {
        $this
            ->addOption('profile-id', null, InputOption::VALUE_REQUIRED, 'Paypal profile id')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, 'New amount')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('start-from', null, InputOption::VALUE_REQUIRED)
            ->addOption('before', null, InputOption::VALUE_REQUIRED)
            ->addOption('apply', null, InputOption::VALUE_NONE)
            ->addOption('fill-database', null, InputOption::VALUE_NONE, 'fill Usr.SubscriptionPrice, Usr.SubscriptionPeriod with parameters from paypal')
            ->addOption('fix-missing', null, InputOption::VALUE_NONE)
            ->addOption('where', null, InputOption::VALUE_REQUIRED)
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
        ;
    }

    private function readCurrentAmountAndPeriod(string $profileId): array
    {
        $req = new GetRecurringPaymentsProfileDetailsReq();
        $req->GetRecurringPaymentsProfileDetailsRequest = new GetRecurringPaymentsProfileDetailsRequestType();
        $req->GetRecurringPaymentsProfileDetailsRequest->ProfileID = $profileId;
        $response = $this->paypal->GetRecurringPaymentsProfileDetails($req);
        $amount = $response->GetRecurringPaymentsProfileDetailsResponseDetails->RegularRecurringPaymentsPeriod->Amount->value;
        $period = $response->GetRecurringPaymentsProfileDetailsResponseDetails->RegularRecurringPaymentsPeriod->BillingFrequency . $response->GetRecurringPaymentsProfileDetailsResponseDetails->RegularRecurringPaymentsPeriod->BillingPeriod;

        return [$amount, $period];
    }

    private function updateAmount(string $profileId, float $amount): void
    {
        $this->logger->info("updating profile $profileId to $amount");

        require_once __DIR__ . '/../../../../../vendor/paypal/merchant-sdk-php/lib/PayPal/EBLBaseComponents/BillingPeriodDetailsType_Update.php';

        $req = new UpdateRecurringPaymentsProfileReq();
        $req->UpdateRecurringPaymentsProfileRequest = new UpdateRecurringPaymentsProfileRequestType();
        $req->UpdateRecurringPaymentsProfileRequest->UpdateRecurringPaymentsProfileRequestDetails = new UpdateRecurringPaymentsProfileRequestDetailsType();
        $req->UpdateRecurringPaymentsProfileRequest->UpdateRecurringPaymentsProfileRequestDetails->ProfileID = $profileId;
        $req->UpdateRecurringPaymentsProfileRequest->UpdateRecurringPaymentsProfileRequestDetails->PaymentPeriod = new \PayPal\EBLBaseComponents\BillingPeriodDetailsType_Update();
        $req->UpdateRecurringPaymentsProfileRequest->UpdateRecurringPaymentsProfileRequestDetails->PaymentPeriod->Amount = new BasicAmountType('USD', round($amount, 2));
        $response = $this->paypal->UpdateRecurringPaymentsProfile($req);

        if ($response->Ack !== 'Success') {
            throw new \Exception("failed to update profile $profileId: " . $response->Errors[0]->LongMessage);
        }
    }

    private function updateSingleProfile(InputInterface $input)
    {
        [$amount, $period] = $this->readCurrentAmountAndPeriod($input->getOption('profile-id'));
        $this->logger->info("{$input->getOption('profile-id')}: current amount: $amount in $period");

        if ($input->getOption('amount')) {
            $this->updateAmount($input->getOption('profile-id'), $input->getOption('amount'));
            [$amount, $period] = $this->readCurrentAmountAndPeriod($input->getOption('profile-id'));
            $this->logger->info("{$input->getOption('profile-id')}: new amount: $amount in $period");
        }
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
            $sql .= " and SubscriptionPeriod is null";
        }

        if ($input->getOption('where')) {
            $sql .= " and " . $input->getOption('where');
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users");

        $corrected = 0;

        foreach ($users as $user) {
            if ($this->processUser($user, $input)) {
                $corrected++;
            }
        }

        $this->logger->info("processed " . count($users) . " users, corrected: {$corrected}");
    }

    private function processUser(array $user, InputInterface $input): bool
    {
        [$amount, $period] = $this->readCurrentAmountAndPeriod($user['PaypalRecurringProfileID']);
        $userEntity = $this->usrRepository->find($user['UserID']);
        $subscription = $this->cartRepository->getActiveAwSubscription($userEntity);
        $mappedDuration = $this->mapPayPalPeriodToDuration($period);
        $newPrice = SubscriptionPrice::getPrice($userEntity->getSubscriptionType(), $mappedDuration);
        $this->logger->info("user {$user["UserID"]}, {$user["PaypalRecurringProfileID"]}, s type {$userEntity->getSubscriptionType()}, paypal: $amount in $period ($mappedDuration), cart: {$subscription->getPlusItem()->getPrice()} in {$subscription->getPlusItem()->getDuration()}, new price: $newPrice");

        if ($subscription === null) {
            $this->addError("Subscription cart not found for user {$user["UserID"]}");

            return false;
        }

        if ($mappedDuration === null) {
            $this->addError("could not map period $period for user {$user["UserID"]}");

            return false;
        }

        if ($newPrice === null) {
            $this->addError("could not get new price for user {$user["UserID"]}, subscription: {$userEntity->getSubscription()}, duration: {$mappedDuration}");

            return false;
        }

        $days = SubscriptionPeriod::DURATION_TO_DAYS[$mappedDuration];

        if ($input->getOption('fill-database')) {
            if (
                (abs($userEntity->getSubscriptionPrice() - $amount) >= 0.01)
                || ($userEntity->getSubscriptionPeriod() != $days)
            ) {
                $this->logger->info("want to correct user {$user["UserID"]} from days: {$userEntity->getSubscriptionPeriod()}, price {$userEntity->getSubscriptionPrice()} to days: $days, price: $amount");

                if ($input->getOption('dry-run')) {
                    $this->logger->info("dry-run");

                    return true;
                }

                $userEntity->setSubscriptionPrice($amount);
                $userEntity->setSubscriptionPeriod($days);
                $this->entityManager->flush();
                $this->logger->info("user {$user["UserID"]} saved to db: days: $days, price: $amount");

                return true;
            } else {
                return false;
            }
        }

        if ($days !== $userEntity->getSubscriptionPeriod()) {
            $this->addError("user {$userEntity->getId()}: invalid period in database ({$userEntity->getSubscriptionPeriod()}), paypal has {$days}");

            return false;
        }

        $corrected = $this->correctPrice($userEntity, $amount, $newPrice, $input->getOption('apply'));

        $this->logger->info("user {$user["UserID"]}, corrected: " . json_encode($corrected));

        return $corrected;
    }

    private function addError(string $error)
    {
        $this->logger->error($error);
        $this->errors[] = $error;
    }

    private function mapPayPalPeriodToDuration(string $period): ?string
    {
        if ($period === '12Month') {
            return SubscriptionPeriod::DURATION_1_YEAR;
        }

        if ($period === '6Month') {
            return SubscriptionPeriod::DURATION_6_MONTHS;
        }

        return null;
    }

    private function correctPrice(Usr $userEntity, float $amount, float $newPrice, bool $apply)
    {
        if (abs($amount - $newPrice) < 0.001) {
            return false;
        }

        if (!$apply) {
            return true;
        }

        $this->updateAmount($userEntity->getPaypalrecurringprofileid(), $newPrice);
        [$amount, $period] = $this->readCurrentAmountAndPeriod($userEntity->getPaypalrecurringprofileid());

        if (abs($amount - $newPrice) > 0.001) {
            $this->addError("can't change price for user {$userEntity->getUserid()} from $amount to $newPrice");
        }

        $userEntity->setSubscriptionPrice($newPrice);
        $this->entityManager->flush();

        return true;
    }
}
