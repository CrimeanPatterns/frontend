<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\Modules\DbBuilder\User;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Google\Service\AndroidPublisher\SubscriptionPurchase;
use Google\Service\AndroidPublisher\SubscriptionPurchasesDeferRequest;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Service\Billing\GooglePlayCartPaidListener
 */
class GooglePlayCartPaidListenerCest
{
    public function testSuspend(\TestSymfonyGuy $I)
    {
        $nextYear = strtotime('+1 year', strtotime("today"));
        $suspendUntilDate = strtotime("+2 year today");
        $purchasesSubscriptions = $I->stubMake(PurchasesSubscriptions::class, [
            'get' => Stub::atLeastOnce(function () use ($nextYear) {
                $result = new SubscriptionPurchase();
                $result->autoRenewing = true;
                $result->expiryTimeMillis = $nextYear * 1000;

                return $result;
            }),
            'defer' => Stub::atLeastOnce(function ($packageName, $subscriptionId, $token, SubscriptionPurchasesDeferRequest $postBody, $optParams = []) use ($I, $suspendUntilDate) {
                $expected = date("Y-m-d", $suspendUntilDate);
                $actual = date("Y-m-d", $postBody->getDeferralInfo()->getDesiredExpiryTimeMillis() / 1000);
                $I->assertLessOrEquals($expected, $actual);
            }),
        ]);
        $I->mockService(PurchasesSubscriptions::class, $purchasesSubscriptions);

        $userId = $I->makeUser(new User(null, false, ['Subscription' => Usr::SUBSCRIPTION_MOBILE]));
        $cart = $I->addUserPayment($userId, PAYMENTTYPE_ANDROIDMARKET, new AwPlusSubscription());
        $purchaseToken = "pt_" . bin2hex(random_bytes(6));
        $cart->setPurchaseToken($purchaseToken);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);
        $em->persist($cart);

        $oldPlusExpirationDate = date("Y-m-d", strtotime($I->grabFromDatabase("Usr", "PlusExpirationDate", ["UserID" => $userId])));
        $I->assertGreaterThan(date('Y-m-d', strtotime("+1 year -5 day")), $oldPlusExpirationDate);
        $I->assertLessThan(date('Y-m-d', strtotime("+1 year +5 day")), $oldPlusExpirationDate);

        $I->assertNUll($I->grabFromDatabase("Usr", "PaypalSuspendedUntilDate", ["UserID" => $userId]));

        $nextBillingDate = $I->grabFromDatabase("Usr", "NextBillingDate", ["UserID" => $userId]);
        $I->assertGreaterThan(date('Y-m-d', strtotime("+1 year -5 day")), $nextBillingDate);
        $I->assertLessThan(date('Y-m-d', strtotime("+1 year +5 day")), $nextBillingDate);

        $I->addUserPayment($userId, PAYMENTTYPE_STRIPE_INTENT, new AwPlus1Year());

        $newPlusExpirationDate = date("Y-m-d", strtotime($I->grabFromDatabase("Usr", "PlusExpirationDate", ["UserID" => $userId])));
        $I->assertGreaterThan(date('Y-m-d', strtotime("+2 year -5 day")), $newPlusExpirationDate);
        $I->assertLessThan(date('Y-m-d', strtotime("+2 year +5 day")), $newPlusExpirationDate);
        $suspendUntilDate = $I->grabFromDatabase("Usr", "PaypalSuspendedUntilDate", ["UserID" => $userId]);
        $I->assertGreaterThan(date('Y-m-d', strtotime("+2 year -5 day")), $suspendUntilDate);
        $I->assertLessThan(date('Y-m-d', strtotime("+2 year +5 day")), $suspendUntilDate);

        $I->verifyMocks();
    }
}
