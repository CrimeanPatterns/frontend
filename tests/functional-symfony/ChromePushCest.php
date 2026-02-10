<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;

/**
 * @group frontend-functional
 */
class ChromePushCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected $deviceType = MobileDevice::TYPE_CHROME;
    private $route;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->route = $I->grabService("router")->generate("aw_push_chrome_subscribe");
        $I->setHeader('User-Agent', ucfirst(MobileDevice::getTypeName($this->deviceType)));
    }

    public function testSubscribeAsUser(\TestSymfonyGuy $I)
    {
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $I->sendGET("/webPush/chrome/manifest.json?_switch_user=" . $user->getLogin());

        $params = ["endpoint" => "http://someendpoint/" . StringHandler::getRandomCode(10), "key" => "somekey", "token" => "sometoken", 'vapid' => false];
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId1 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertNotEmpty($deviceId1);
        $I->assertEquals($user->getUserid(), $I->grabFromDatabase("MobileDevice", "UserID", ["MobileDeviceID" => $deviceId1]));

        $I->amGoingTo("repeat request, should not see double subscription");
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId2 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertEquals($deviceId1, $deviceId2);
    }

    public function testSubscribeAsAnonymous(\TestSymfonyGuy $I)
    {
        $params = ["endpoint" => "http://someendpoint/" . StringHandler::getRandomCode(10), "key" => "somekey", "token" => "sometoken", 'vapid' => false];
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId1 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertNotEmpty($deviceId1);
        $I->assertEmpty($I->grabFromDatabase("MobileDevice", "UserID", ["MobileDeviceID" => $deviceId1]));

        $I->amGoingTo("repeat request, should not see double subscription");
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId2 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertEquals($deviceId1, $deviceId2);
    }

    public function testAnonymousThenUser(\TestSymfonyGuy $I)
    {
        $params = ["endpoint" => "http://someendpoint/" . StringHandler::getRandomCode(10), "key" => "somekey", "token" => "sometoken", 'vapid' => false];
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId1 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertNotEmpty($deviceId1);
        $I->assertEmpty($I->grabFromDatabase("MobileDevice", "UserID", ["MobileDeviceID" => $deviceId1]));

        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $I->sendGET("/webPush/chrome/manifest.json?_switch_user=" . $user->getLogin());

        $I->amGoingTo("repeat request as user");
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId2 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertEquals($deviceId1, $deviceId2);
        $I->assertEquals($user->getUserid(), $I->grabFromDatabase("MobileDevice", "UserID", ["MobileDeviceID" => $deviceId1]));
    }

    public function testUserThenAnonymous(\TestSymfonyGuy $I)
    {
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $I->sendGET("/webPush/chrome/manifest.json?_switch_user=" . $user->getLogin());
        $params = ["endpoint" => "http://someendpoint/" . StringHandler::getRandomCode(10), "key" => "somekey", "token" => "sometoken", 'vapid' => false];

        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId1 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertEquals($user->getUserid(), $I->grabFromDatabase("MobileDevice", "UserID", ["MobileDeviceID" => $deviceId1]));

        $I->sendGET("/webPush/chrome/manifest.json?_switch_user=_exit");
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId2 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertNotEmpty($deviceId2);
        $I->assertEquals($deviceId1, $deviceId2);
        $I->assertEquals($user->getUserid(), $I->grabFromDatabase("MobileDevice", "UserID", ["MobileDeviceID" => $deviceId1]));
    }

    public function testCountryAndIp(\TestSymfonyGuy $I)
    {
        $params = ["endpoint" => "http://someendpoint/" . StringHandler::getRandomCode(10), "key" => "somekey", "token" => "sometoken", 'vapid' => false];

        $I->setServerParameter("REMOTE_ADDR", '198.199.104.203'); // USA
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId1 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertNotEmpty($deviceId1);
        $I->assertEquals('198.199.104.203', $I->grabFromDatabase("MobileDevice", "IP", ["MobileDeviceID" => $deviceId1]));
        $I->assertEquals(Country::UNITED_STATES, $I->grabFromDatabase("MobileDevice", "CountryID", ["MobileDeviceID" => $deviceId1]));

        $I->amGoingTo("repeat request with another country");
        $I->setServerParameter("REMOTE_ADDR", '212.58.246.79'); // London
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId2 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertEquals($deviceId1, $deviceId2);
        $I->assertEquals('212.58.246.79', $I->grabFromDatabase("MobileDevice", "IP", ["MobileDeviceID" => $deviceId1]));
        $I->assertEquals(Country::UK, $I->grabFromDatabase("MobileDevice", "CountryID", ["MobileDeviceID" => $deviceId1]));

        $I->amGoingTo("repeat request with bad ip");
        $I->setServerParameter("REMOTE_ADDR", '127.0.0.1');
        $I->sendPOST($this->route, $params);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains("false");
        $deviceId3 = $I->grabFromDatabase("MobileDevice", "MobileDeviceID", ["DeviceType" => $this->deviceType, "DeviceKey" => json_encode($params)]);
        $I->assertEquals($deviceId1, $deviceId3);
        $I->assertEquals('127.0.0.1', $I->grabFromDatabase("MobileDevice", "IP", ["MobileDeviceID" => $deviceId1]));
        $I->assertEquals(Country::UK, $I->grabFromDatabase("MobileDevice", "CountryID", ["MobileDeviceID" => $deviceId1]));
    }
}
