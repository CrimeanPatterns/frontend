<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 25.03.16
 * Time: 15:40.
 */

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use JMS\Serializer\Serializer;

/**
 * @group frontend-functional
 */
class LoyaltyApiCallbackCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const AUTH = 'awardwallet:awdeveloper';
    public const TEST_ACCOUNT_ID = 2899937;

    public function testFailGet(\TestSymfonyGuy $I)
    {
        $I->sendGET("/api/awardwallet/loyalty/callback-v2/account");
        $I->seeResponseCodeIs(405);
        $I->sendGET("/api/awardwallet/loyalty/callback-v2/confirmation");
        $I->seeResponseCodeIs(405);
    }

    public function testFailAuth(\TestSymfonyGuy $I)
    {
        $I->sendPOST("/api/awardwallet/loyalty/callback-v2/account");
        $I->seeResponseCodeIs(403);
        $I->sendPOST("/api/awardwallet/loyalty/callback-v2/confirmation");
        $I->seeResponseCodeIs(403);
    }

    // needs to remake
    //    public function testCallback(\TestSymfonyGuy $I)
    //    {
    //        $I->haveHttpHeader('Content-Type', 'application/json');
    //        $I->haveHttpHeader('Authorization', 'Basic '.base64_encode(self::AUTH));
    //        /** @var Serializer $serializer */
    //        $serializer = $I->grabService('jms_serializer');
    //        $response = (new CheckAccountResponse())
    //                    ->setUserdata(self::TEST_ACCOUNT_ID)
    //                    ->setBalance(999.8)
    //                    ->setState(1)
    //                    ->setCheckdate(new \DateTime())
    //                    ->setRequestdate(new \DateTime())
    //                    ->setDebuginfo(null);
    //        $callback = (new CheckAccountCallback())->setResponse($response)->setMethod('account');
    //
    //        $I->sendPOST("/api/awardwallet/loyalty/callback/account", $serializer->serialize($callback, 'json'));
    //        $I->seeResponseCodeIs(200);
    //    }
}
