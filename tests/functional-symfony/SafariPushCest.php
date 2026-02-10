<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\Usr;

/**
 * @group frontend-functional
 */
class SafariPushCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testPackageHook(\TestSymfonyGuy $I)
    {
        $route = $I->grabService("router")->generate("aw_push_safari_package", ["webPushId" => $I->getContainer()->getParameter("webpush.id")]);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST($route, json_encode(["token" => $this->getUserToken($I)]));
        $I->seeResponseCodeIs(200);
    }

    public function testRegisterHook(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "ApplePushNotifications " . $this->getUserToken($I));
        $route = $I->grabService("router")->generate("aw_push_safari_register", ["deviceToken" => "0B127B9330D29D8769511D205B5DDD8AE82235F1D595123458D58B8FF69092FF", "webPushId" => $I->getContainer()->getParameter("webpush.id")]);
        $I->sendPOST($route);
        $I->seeResponseCodeIs(200);
    }

    public function testDeleteHook(\TestSymfonyGuy $I)
    {
        $route = $I->grabService("router")->generate("aw_push_safari_register", ["deviceToken" => "0B127B9330D29D8769511D205B5DDD8AE82235F1D595123458D58B8FF69092FF", "webPushId" => $I->getContainer()->getParameter("webpush.id")]);
        $I->haveHttpHeader("Authorization", "ApplePushNotifications " . $this->getUserToken($I));
        $I->sendDELETE($route);
        $I->seeResponseCodeIs(200);
    }

    public function testSaveDeviceTokenAuth(\TestSymfonyGuy $I)
    {
        $this->getUserToken($I); // authorize
        $router = $I->grabService("router");
        $route = $router->generate("aw_push_safari_save_token", ["deviceToken" => "0B127B9330D29D8769511D205B5DDD8AE82235F1D595123458D58B8FF69092FF"]);
        $I->sendPOST($route);
        $I->seeResponseCodeIs(200);
    }

    public function testAnonymous(\TestSymfonyGuy $I)
    {
        $token = $this->getUserToken($I, true);

        $I->wantTo("get safari push package");
        $route = $I->grabService("router")->generate("aw_push_safari_package", ["webPushId" => $I->getContainer()->getParameter("webpush.id")]);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST($route, json_encode(["token" => $token]));
        $I->seeResponseCodeIs(200);

        $I->wantTo("save registration");
        $I->haveHttpHeader("Authorization", "ApplePushNotifications " . $token);
        $route = $I->grabService("router")->generate("aw_push_safari_register", ["deviceToken" => "0B127B9330D29D8769511D205B5DDD8AE82235F1D595123458D58B8FF69092FF", "webPushId" => $I->getContainer()->getParameter("webpush.id")]);
        $I->sendPOST($route);
        $I->seeResponseCodeIs(200);

        $route = $I->grabService("router")->generate("aw_push_safari_save_token", ["deviceToken" => "0B127B9330D29D8769511D205B5DDD8AE82235F1D595123458D58B8FF69092FF"]);
        $I->sendPOST($route);
        $I->seeResponseCodeIs(200);
    }

    private function getUserToken(\TestSymfonyGuy $I, $anonymous = false)
    {
        if ($anonymous) {
            $params = [];
        } else {
            /** @var Usr $user */
            $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
            $params = ["_switch_user" => $user->getLogin()];
        }
        $I->sendGET($I->grabService("router")->generate("aw_push_safari_get_user_token", $params));
        $I->seeResponseCodeIs(200);

        return $I->grabDataFromJsonResponse();
    }
}
