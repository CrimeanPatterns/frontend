<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\StringHandler;

/**
 * @group mobile
 * @group frontend-functional
 */
class PushNotificationCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'accounpn-', 'userpass-', [], true);
    }

    public function registration(\TestSymfonyGuy $I)
    {
        $I->sendPOST($registerUrl = '/m/api/push/register', ['id' => ($deviceKey = StringHandler::getRandomCode(20) . str_repeat('abcd', 1000)), 'type' => 'android']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $deviceId = $I->grabDataFromJsonResponse('deviceId');
        $I->seeInDatabase('MobileDevice', ['UserID' => $this->userId, 'DeviceKey' => $deviceKey, 'DeviceType' => 1]);

        $I->setHeader(MobileHeaders::MOBILE_DEVICE_ID, $deviceId);
        $I->setHeader('Accept-Language', 'ru');
        $this->accountSteps->loadData();
        $I->seeInDatabase('MobileDevice', ['UserID' => $this->userId, 'DeviceKey' => $deviceKey, 'DeviceType' => 1, 'Lang' => 'ru']);

        $I->sendPOST('/m/api/push/unregister', ['id' => $deviceKey, 'type' => 'android']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $I->dontSeeInDatabase('MobileDevice', ['UserID' => $this->userId, 'DeviceKey' => $deviceKey, 'DeviceType' => 1]);
    }

    public function removeTheSameKeyOnRegistration(\TestSymfonyGuy $I)
    {
        $userId = $this->userId;
        $I->sendPOST($registerUrl = '/m/api/push/register', ['id' => ($deviceKey = StringHandler::getRandomCode(32) . str_repeat('abcd', 1000)), 'type' => 'android']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeInDatabase('MobileDevice', ['UserID' => $userId, 'DeviceKey' => $deviceKey, 'DeviceType' => 1]);

        $this->userSteps->logout();

        $this->_before($I);
        $I->sendPOST($registerUrl, ['id' => $deviceKey, 'type' => 'android']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeInDatabase('MobileDevice', ['UserID' => $this->userId, 'DeviceKey' => $deviceKey, 'DeviceType' => 1]);
        $I->dontSeeInDatabase('MobileDevice', ['UserID' => $userId, 'DeviceKey' => $deviceKey, 'DeviceType' => 1]);
    }
}
