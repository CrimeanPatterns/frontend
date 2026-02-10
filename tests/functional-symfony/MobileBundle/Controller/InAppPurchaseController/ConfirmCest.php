<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\InAppPurchaseController;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use Codeception\Example;

/**
 * @group manual
 */
class ConfirmCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const APP_VERSION = '4.4.0';

    /**
     * this test actually does not test anything, used for debugging
     * need to mock (comment out) signature verification and getGooglePlaySubscription inside GooglePlay\Provider
     * to complete test.
     */
    public function testGoogle(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $json = str_ireplace('%userId%', $userId, file_get_contents(codecept_data_dir("InAppPurchase/google.json")));
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, "android-v3");
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, self::APP_VERSION);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST($I->grabService("router")->generate("aw_mobile_purchase_confirm"), $json);
        $I->seeResponseCodeIs(200);
    }

    /**
     * this test actually does not test anything, used for debugging AppleAppStore\Provider
     * uncomment $json = json_decode($data['fake_receipt']) in AppleAppStore\Provider to run test.
     *
     * @dataProvider iosReceiptsProvider
     */
    public function testIOS(\TestSymfonyGuy $I, Example $example)
    {
        $userId = $I->createAwUser();
        $cart = $I->addUserPayment($userId, PAYMENTTYPE_APPSTORE, new AwPlusSubscription());
        $txId = rand(time() - SECONDS_PER_DAY * 365, time());
        $originalTxId = $txId . "_orig";
        $I->executeQuery("update Cart set BillingTransactionID = '$originalTxId' where CartID = {$cart->getCartid()}");
        $json = str_ireplace(['%transactionId%'], [$txId], file_get_contents(codecept_data_dir("InAppPurchase/ios.json")));
        $receipt = json_decode(str_ireplace(['%userId%', '%transactionId%', '%originalTransactionId%'], [$userId, $txId, $originalTxId], file_get_contents(codecept_data_dir($example["receipt"]))), true);
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, "ios");
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, self::APP_VERSION);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $data = json_decode($json, true);
        $data['fake_receipt'] = json_encode($receipt);
        $I->sendPOST($I->grabService("router")->generate("aw_mobile_purchase_confirm"), json_encode($data));
        $I->seeResponseCodeIs(200);
    }

    public function iosReceiptsProvider()
    {
        return [
            ["receipt" => "InAppPurchase/iosReceipt7.json"],
            ["receipt" => "InAppPurchase/iosReceipt6.json"],
        ];
    }
}
