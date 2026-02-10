<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use AwardWallet\Tests\Modules\DbBuilder\User;
use Codeception\Util\Stub;
use PayPal\EBLBaseComponents\GetRecurringPaymentsProfileDetailsResponseDetailsType;
use PayPal\EBLBaseComponents\RecurringPaymentsSummaryType;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsReq;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsResponseType;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusReq;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusResponseType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Service\Billing\PaypalCartPaidListener
 */
class PaypalCartPaidListenerCest
{
    public function testSuspend(\TestSymfonyGuy $I)
    {
        $profileId = 'PP-' . bin2hex(random_bytes(5));
        $nextYear = strtotime('+1 year');
        $paypal = $I->stubMake(PaypalSoapApi::class, [
            'getPaypalService' => Stub::atLeastOnce(function () use ($I, $profileId, $nextYear) {
                return $I->stubMake(PayPalAPIInterfaceServiceService::class, [
                    'GetRecurringPaymentsProfileDetails' => function (GetRecurringPaymentsProfileDetailsReq $req) use ($I, $profileId, $nextYear) {
                        $response = new GetRecurringPaymentsProfileDetailsResponseType();
                        $response->Ack = "Success";
                        $response->GetRecurringPaymentsProfileDetailsResponseDetails = new GetRecurringPaymentsProfileDetailsResponseDetailsType();
                        $response->GetRecurringPaymentsProfileDetailsResponseDetails->ProfileStatus = "ActiveProfile";
                        $response->GetRecurringPaymentsProfileDetailsResponseDetails->RecurringPaymentsSummary = new RecurringPaymentsSummaryType();
                        $response->GetRecurringPaymentsProfileDetailsResponseDetails->RecurringPaymentsSummary->NextBillingDate = date("Y-m-d\TH:i:s\Z", $nextYear);
                        $I->assertEquals($profileId, $req->GetRecurringPaymentsProfileDetailsRequest->ProfileID);

                        return $response;
                    },
                    'ManageRecurringPaymentsProfileStatus' => function (ManageRecurringPaymentsProfileStatusReq $req) {
                        $response = new ManageRecurringPaymentsProfileStatusResponseType();
                        $response->Ack = 'Success';

                        return $response;
                    },
                ]);
            }),
        ]);
        $I->mockService(PaypalSoapApi::class, $paypal);

        $userId = $I->makeUser(new User(null, false, ['Subscription' => Usr::SUBSCRIPTION_PAYPAL, 'PaypalRecurringProfileID' => $profileId]));
        $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, new AwPlusSubscription());

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
        $I->assertGreaterThan(date('Y-m-d', strtotime("+2 year -15 day")), $suspendUntilDate);
        $I->assertLessThan(date('Y-m-d', strtotime("+2 year -5 day")), $suspendUntilDate);

        $I->verifyMocks();
    }

    public function testNewUser(\TestSymfonyGuy $I)
    {
        $profileId = 'PP-' . bin2hex(random_bytes(5));
        $today = time();
        $paypal = $I->stubMake(PaypalSoapApi::class, [
            'getPaypalService' => Stub::atLeastOnce(function () use ($I, $profileId, $today) {
                return $I->stubMake(PayPalAPIInterfaceServiceService::class, [
                    'GetRecurringPaymentsProfileDetails' => function (GetRecurringPaymentsProfileDetailsReq $req) use ($I, $profileId, $today) {
                        $response = new GetRecurringPaymentsProfileDetailsResponseType();
                        $response->Ack = "Success";
                        $response->GetRecurringPaymentsProfileDetailsResponseDetails = new GetRecurringPaymentsProfileDetailsResponseDetailsType();
                        $response->GetRecurringPaymentsProfileDetailsResponseDetails->ProfileStatus = "ActiveProfile";
                        $response->GetRecurringPaymentsProfileDetailsResponseDetails->RecurringPaymentsSummary = new RecurringPaymentsSummaryType();
                        $response->GetRecurringPaymentsProfileDetailsResponseDetails->RecurringPaymentsSummary->NextBillingDate = date("Y-m-d\TH:i:s\Z", $today);
                        $I->assertEquals($profileId, $req->GetRecurringPaymentsProfileDetailsRequest->ProfileID);

                        return $response;
                    },
                    'ManageRecurringPaymentsProfileStatus' => Stub::never(),
                ]);
            }),
        ]);
        $I->mockService(PaypalSoapApi::class, $paypal);

        $userId = $I->makeUser(new User(null, false, ['Subscription' => Usr::SUBSCRIPTION_PAYPAL, 'PaypalRecurringProfileID' => $profileId]));

        $nextBillingDate = $I->grabFromDatabase("Usr", "NextBillingDate", ["UserID" => $userId]);
        $I->assertNull($nextBillingDate);

        $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, new AwPlus1Year());

        $suspendUntilDate = $I->grabFromDatabase("Usr", "PaypalSuspendedUntilDate", ["UserID" => $userId]);
        $I->assertNull($suspendUntilDate);

        $I->verifyMocks();
    }
}
