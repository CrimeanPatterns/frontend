<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveRefundableSubscriptionsCommand extends Command
{
    protected static $defaultName = 'aw:remove-refundable-subscriptions';

    private EntityManagerInterface $entityManager;
    private Manager $cartManager;
    private ExpirationCalculator $expirationCalculator;
    private $payPalParameters;

    public function __construct(
        EntityManagerInterface $entityManager,
        Manager $cartManager,
        ExpirationCalculator $expirationCalculator,
        $payPalParameters
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->cartManager = $cartManager;
        $this->expirationCalculator = $expirationCalculator;
        $this->payPalParameters = $payPalParameters;
    }

    public function configure()
    {
        $this
            ->setDescription('Remove paypal subscriptions eligible for refund')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry-run, do not do anything')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'limit processed carts', 0);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;
        $cartManager = $this->cartManager;
        /** @var UsrRepository $userRep */
        $userRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $expirationCalculator = $this->expirationCalculator;
        $cartRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $conn = $em->getConnection();
        $dryRun = !empty($input->getOption('dry-run'));

        if ($dryRun) {
            $output->writeln("dry run");
        }

        $stmt = $conn->executeQuery("
            select ci.CartID, u.UserID, u.FirstName, u.LastName, u.Email from CartItem ci
                join Usr u on ci.ID = u.UserID 
    	        where 
    		      Name = 'AwardWallet Plus subscription renewal (12 months)'
    		        and ci.ID in (
    			      select ID 
    			        from CartItem ci
    			        join Cart c on ci.CartID = c.CartID 
    			          where ci.Name = 'AwardWallet Plus yearly subscription' 
    			            and Price = 30 
    			            and c.Paydate is not null
    			            and c.BillingTransactionID is not null
    		        )
        ");

        $found = 0;
        $processed = 0;
        $errors = 0;
        $limit = (int) $input->getOption('limit');

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $found++;
            /** @var Cart $cart */
            $cart = $cartRep->find($row['CartID']);

            $currentUserId = $row['UserID'];
            $expDate = $expirationCalculator->getAccountExpiration($currentUserId);
            $output->writeln("");
            $output->writeln(sprintf("processing userId %d (%s), expire %s", $currentUserId, "{$row['FirstName']} {$row['LastName']} {$row['Email']}", date("Y-m-d H:i:s", $expDate['date'])));
            $output->writeln(sprintf("CartID: %d, PayDate: %s, BillingTransactionID: %s",
                $cart->getCartid(), $cart->getPaydate()->format("Y-m-d H:i:s"), $cart->getBillingtransactionid()));

            if (!$dryRun && $limit) {
                $processed++;
                $limit--;

                $output->writeln("sending refund request");
                $service = $this->getPaypalService();

                $refundRequestType = new \RefundTransactionRequestType();
                $refundRequestType->TransactionID = $cart->getBillingtransactionid();
                $refundRequest = new \RefundTransactionReq();
                $refundRequest->RefundTransactionRequest = $refundRequestType;

                $response = $service->RefundTransaction($refundRequest);

                if (isset($response->Errors)) {
                    $errors++;
                    $output->writeln("Paypal returned an error: " . $this->ppErrorsToString($response->Errors));
                } else {
                    $output->writeln("Paypal response: " . $response->Ack);

                    $recurringRequestType = new \GetRecurringPaymentsProfileDetailsRequestType();
                    $recurringRequestType->ProfileID = $cart->getUser()->getPaypalrecurringprofileid();

                    $recurringRequest = new \GetRecurringPaymentsProfileDetailsReq();
                    $recurringRequest->GetRecurringPaymentsProfileDetailsRequest = $recurringRequestType;

                    /** @var \GetRecurringPaymentsProfileDetailsResponseType $response */
                    $response = $service->GetRecurringPaymentsProfileDetails($recurringRequest);

                    $details = $response->GetRecurringPaymentsProfileDetailsResponseDetails;

                    $output->writeln(sprintf("subscription status: %s", $details->ProfileStatus));
                }

                $output->writeln("deleting subscription renewal payment");
                $cartManager->refund($cart);
                $expDate = $expirationCalculator->getAccountExpiration($currentUserId);
                $output->writeln("new expiration date: " . date("Y-m-d H:i:s", $expDate['date']));

                if (!$limit) {
                    $output->writeln("Refund limit reached, exiting");

                    break;
                }
            }
        }

        $output->writeln(sprintf("total accounts found: %s, processed: %s, failed refunds: %s", $found, $processed, $errors));

        return 0;
    }

    protected function getPaypalService()
    {
        $paypalParams = $this->payPalParameters;
        $config = $paypalParams['profiles']['live'];

        if (isset($paypalParams['globals'])) {
            $config = array_merge($config, $paypalParams['globals']);
        }

        $config['log.LogEnabled'] = '1';
        $config['log.LogLevel'] = 'FINE';
        $config['log.FileName'] = "/var/log/www/awardwallet/refund.log";

        $service = new \PayPalAPIInterfaceServiceService($config);

        return $service;
    }

    protected function ppErrorsToString($errors)
    {
        if (!is_array($errors)) {
            $errors = [$errors];
        }
        $err = [];

        foreach ($errors as $error) {
            /** @var \ErrorType $error */
            $err[] = $error->LongMessage . " ({$error->ErrorCode})";
        }

        return implode($err, ", ");
    }
}
