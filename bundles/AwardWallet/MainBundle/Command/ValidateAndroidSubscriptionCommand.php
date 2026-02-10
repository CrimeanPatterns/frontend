<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\QuietGoogleException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\QuietVerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateAndroidSubscriptionCommand extends Command
{
    protected static $defaultName = 'aw:iap:android:validate-subscription';
    private LoggerInterface $logger;
    private Provider $provider;
    private Billing $billing;

    public function __construct(
        LoggerInterface $logger,
        Provider $provider,
        Billing $billing
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->provider = $provider;
        $this->billing = $billing;
    }

    protected function configure()
    {
        $this
            ->setDescription('Validate android subscription, upgrade aw users, refunds')
            ->addArgument('productId', InputArgument::REQUIRED, 'platform product id (constants "PRODUCT_*" from \\AwardWallet\\MainBundle\\Service\\InAppPurchase\\GooglePlay\\Provider)')
            ->addArgument('purchaseToken', InputArgument::REQUIRED, 'token')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->logger;
        $provider = $this->provider;
        $billing = $this->billing;

        $logger->info('check android subscription');

        try {
            $productId = $input->getArgument('productId');
            $token = $input->getArgument('purchaseToken');
            $subscription = $provider->getGooglePlaySubscription($productId, $token);

            if (empty($subscription->getStartTimeMillis()) || empty($subscription->getExpiryTimeMillis())) {
                throw new \Exception("google subscription was not found");
            }

            $logger->info(sprintf('google subscription: %s', var_export($subscription, true)));

            $purchases = $provider->validatePurchaseData([
                'packageName' => Provider::BUNDLE_ID,
                'productId' => $productId,
                'purchaseState' => Provider::PURCHASE_STATE_PURCHASED,
                'purchaseTime' => $subscription->getStartTimeMillis(),
                'purchaseToken' => $token,
                'orderId' => $subscription->getOrderId(),
                'developerPayload' => $subscription->getDeveloperPayload(),
            ]);

            foreach ($purchases as $purchase) {
                $billing->processing($purchase);
            }
        } catch (QuietGoogleException $e) {
            $logger->warning(sprintf("Google exception: %s", $e->getMessage()));
        } catch (QuietVerificationException $e) {
            $logger->warning(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
        } catch (VerificationException $e) {
            $logger->critical(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
        } catch (\Google_Exception $e) {
            $logger->critical(sprintf("Google exception: %s", $e->getMessage()));
        } catch (\Exception $e) {
            $logger->critical(sprintf("Exception: %s", $e->getMessage()));
        }

        $logger->info('done.');

        return 0;
    }
}
