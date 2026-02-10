<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\StripePaymentMethodHelper;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StripeCardDetailsUpdateCommand extends Command
{
    private const USER_BATCH_SIZE = 1000;

    public static $defaultName = 'aw:stripe:update-card-details';

    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private StripeClient $stripeClient;
    private StripePaymentMethodHelper $paymentMethodHelper;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        Connection $unbufConnection,
        Connection $connection,
        StripeClient $stripeClient,
        StripePaymentMethodHelper $paymentMethodHelper,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->unbufConnection = $unbufConnection;
        $this->connection = $connection;
        $this->stripeClient = $stripeClient;
        $this->paymentMethodHelper = $paymentMethodHelper;
        $this->logger = $logger;
        parent::__construct();
    }

    public function configure()
    {
        $this->setDescription('Starts updating the name and number of credit cards');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query = $this->getQuery();
        $query->execute();
        $i = 0;

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $parts = explode('_', $row['BillingTransactionID']);

            try {
                switch (reset($parts)) {
                    case 'pi':
                        $payment = $this->stripeClient->paymentIntents->retrieve($row['BillingTransactionID']);

                        break;

                    case 'seti':
                        $payment = $this->stripeClient->setupIntents->retrieve($row['BillingTransactionID']);

                        break;

                    default:
                        continue 2;
                }

                if ($payment->payment_method !== null) {
                    $cart = $this->entityManager->getRepository(Cart::class)->find($row['CartID']);
                    $this->paymentMethodHelper->updateCreditCardDetails($payment->payment_method, $cart);
                }
            } catch (InvalidRequestException $exception) {
                $format = 'error when receiving a payment intent object, payment intent id: %s, message: %s';
                $this->logger->error(sprintf($format, $row['BillingTransactionID'], $exception->getMessage()));
            }

            ++$i;

            if (($i % self::USER_BATCH_SIZE) === 0) {
                $this->entityManager->clear();
            }

            if (($i % 100) === 0) {
                $output->writeln('Processed records: ' . $i);
            }
        }

        $output->writeln('Done. Total processed records: ' . $i);

        return 0;
    }

    private function getQuery()
    {
        return $this->unbufConnection->executeQuery('
            SELECT `Usr`.`UserID`, `Cart`.`CartID`, `Cart`.`BillingTransactionID`
            FROM `Usr`
            LEFT JOIN `CartItem` ON `Usr`.`LastSubscriptionCartItemID` = `CartItem`.`CartItemID`
            LEFT JOIN `Cart` ON `CartItem`.`CartID` = `Cart`.`CartID`
            WHERE
                `Usr`.`Subscription` = :subscription
                AND `Cart`.`PaymentType` IN (:type_stripe, :type_stripe_intent)
                AND `Cart`.`BillingTransactionID` IS NOT NULL
                AND `Cart`.`CreditCardNumber` IS NULL
            ORDER BY `PayDate` ASC',
            [
                'subscription' => Usr::SUBSCRIPTION_STRIPE,
                'type_stripe' => Cart::PAYMENTTYPE_STRIPE,
                'type_stripe_intent' => Cart::PAYMENTTYPE_STRIPE_INTENT,
            ]
        );
    }
}
