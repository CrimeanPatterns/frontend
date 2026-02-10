<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Command\ValidateAndroidSubscriptionCommand;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Decoder;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider;
use AwardWallet\Tests\Unit\CommandTester;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 * @group billing
 * @group mobile
 * @group mobile/billing
 */
class ValidateAndroidSubscriptionCommandTest extends CommandTester
{
    public function _before()
    {
        parent::_before();

        $this->db->executeQuery("delete from GroupUserLink where UserID = {$this->user->getUserid()}");
        $this->em->refresh($this->user);
    }

    public function _after()
    {
        $this->cleanCommand();
        parent::_after();
    }

    public function testUpgradeUser()
    {
        $ts = time();
        $startTs = $ts * 1000;
        $expiryTs = strtotime("+1 year", $ts) * 1000;
        $orderId = 'GPA.1234-1234-1234-01234';
        $provider = $this->mockGoogleProvider($this->getGoogleSubscription($startTs, $expiryTs, $orderId));
        $this->initCommand(new ValidateAndroidSubscriptionCommand(
            $this->prophesize(LoggerInterface::class)->reveal(),
            $provider,
            $this->container->get(Billing::class)
        ));
        $this->db->dontSeeInDatabase('Cart', [
            'UserID' => $this->user->getUserid(),
        ]);
        $this->executeCommand([
            'productId' => Provider::PRODUCT_AWPLUS_SUBSCR,
            'purchaseToken' => 'xxx',
        ]);

        $this->db->seeInDatabase('Cart', [
            'UserID' => $this->user->getUserid(),
            'BillingTransactionID' => $orderId,
        ]);
    }

    protected function mockGoogleProvider(\Google_Service_AndroidPublisher_SubscriptionPurchase $subscription): Provider
    {
        /** @var Provider $provider */
        $provider = $this->construct(Provider::class, [
            $this->container->get(LoggerInterface::class),
            $this->container->get('translator'),
            $this->em,
            $this->container->get('aw.api.versioning'),
            false,
            $this->makeEmpty(Decoder::class),
            '',
            $this->createMock(PurchasesSubscriptions::class),
            $this->container->get(CartRepository::class),
            $this->container->get(LocalizeService::class),
        ], [
            'getGooglePlaySubscription' => $subscription,
        ]);

        return $provider;
    }

    protected function getGoogleSubscription($startTime, $expiryTime, $orderId)
    {
        $googleSubscr = new \Google_Service_AndroidPublisher_SubscriptionPurchase();
        $googleSubscr->setAutoRenewing(true);
        $googleSubscr->setPaymentState(Provider::PAYMENT_STATE_RECEIVED);
        $googleSubscr->setStartTimeMillis($startTime);
        $googleSubscr->setExpiryTimeMillis($expiryTime);
        $googleSubscr->setOrderId($orderId);
        $googleSubscr->setDeveloperPayload(json_encode(['UserID' => $this->user->getUserid()]));

        return $googleSubscr;
    }
}
