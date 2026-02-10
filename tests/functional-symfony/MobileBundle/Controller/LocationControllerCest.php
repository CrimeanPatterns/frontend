<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use AwardWallet\Tests\Modules\Access\AccountLocationAccessScenario;
use AwardWallet\Tests\Modules\Access\Action;
use AwardWallet\Tests\Modules\Access\CouponLocationAccessScenario;
use Codeception\Example;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;

/**
 * @group loyalty-location
 * @group mobile
 * @group frontend-functional
 */
class LocationControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;
    use JsonHeaders;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoyaltyLocation
     */
    private $loyaltyLocation;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService("router");
        $this->em = $I->grabService("doctrine")->getManager();
        $this->loyaltyLocation = $I->grabService(LoyaltyLocation::class);
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.29.0+b100500');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        $this->loyaltyLocation = null;
        $this->em = null;
        $this->router = null;
    }

    public function addAccountLocation(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate("aw_mobile_account_location_add", ["accountId" => 123]) . "?_switch_user=_exit");
        $I->seeResponseCodeIs(403);
        $this->loginUser($I, $this->user);
        $I->sendGET($this->router->generate("aw_mobile_account_location_add", ["accountId" => 0]));
        $I->seeResponseCodeIs(404);
        $I->sendPOST($this->router->generate("aw_mobile_account_location_add", ["accountId" => 123]));
        $I->seeResponseCodeIs(405);

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)
            ->find($I->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "balance.random"));
        /** @var Provider $provider */
        $provider = $account->getProviderid();
        $I->sendGET($route = $this->router->generate("aw_mobile_account_location_add", ["accountId" => $accountId = $account->getAccountid()]));
        $I->seeResponseContainsJson(array_merge([
            "formData" => ["submitLabel" => "Add"],
        ], $this->getLocationForm($provider->getDisplayname(), $provider->getKind())));

        $this->checkValidation($I, $route);

        $this->addLocation($I, $route, $this->getLocationRequest("Location #1"), [
            "AccountID" => $accountId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $this->addLocation($I, $route, $this->getLocationRequest("Location #2"), [
            "AccountID" => $accountId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $this->addLocation($I, $route, $this->getLocationRequest("Location #3"), [
            "AccountID" => $accountId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 0]);

        $I->assertEquals(3, $this->em->getRepository(\AwardWallet\MainBundle\Entity\Location::class)->getCountTotal($this->user));
        $I->assertEquals(3, $this->loyaltyLocation->getCountTotal($this->user));
        $I->sendGET($route);
        $I->sendPUT($route, array_merge($this->getLocationRequest("Location #4"), [
            '_token' => $this->grabFormCsrfToken($I),
        ]));
        $I->seeResponseContainsJson(["error" => "You can't add more than 3 locations."]);
    }

    public function addSubAccountLocation(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate("aw_mobile_subaccount_location_add", ["accountId" => 123, "subAccountId" => 123]) . "?_switch_user=_exit");
        $I->seeResponseCodeIs(403);
        $this->loginUser($I, $this->user);
        $I->sendGET($this->router->generate("aw_mobile_subaccount_location_add", ["accountId" => 0, "subAccountId" => 0]));
        $I->seeResponseCodeIs(404);
        $I->sendPOST($this->router->generate("aw_mobile_subaccount_location_add", ["accountId" => 123, "subAccountId" => 123]));
        $I->seeResponseCodeIs(405);

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)
            ->find($I->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "balance.random", null, ["SubAccounts" => 1]));
        $subaccount = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class)
            ->find($I->createAwSubAccount($account->getAccountid(), [
                "Code" => "#1",
                "DisplayName" => "Test Subaccount",
            ]));
        /** @var Provider $provider */
        $provider = $account->getProviderid();

        $I->sendGET($route = $this->router->generate("aw_mobile_subaccount_location_add", [
            "accountId" => $account->getAccountid(),
            "subAccountId" => $subAccountId = $subaccount->getSubaccountid(),
        ]));
        $I->seeResponseContainsJson(array_merge([
            "formData" => ["submitLabel" => "Add"],
        ], $this->getLocationForm($provider->getDisplayname(), $provider->getKind())));

        $this->checkValidation($I, $route);

        $this->addLocation($I, $route, $this->getLocationRequest("Location #1"), [
            "AccountID" => null,
            "SubAccountID" => $subAccountId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $this->addLocation($I, $route, $this->getLocationRequest("Location #2"), [
            "AccountID" => null,
            "SubAccountID" => $subAccountId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $this->addLocation($I, $route, $this->getLocationRequest("Location #3"), [
            "AccountID" => null,
            "SubAccountID" => $subAccountId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 0]);

        $I->assertEquals(3, $this->em->getRepository(\AwardWallet\MainBundle\Entity\Location::class)->getCountTotal($this->user));
        $I->assertEquals(3, $this->loyaltyLocation->getCountTotal($this->user));
        $I->sendGET($route);
        $I->sendPUT($route, array_merge($this->getLocationRequest("Location #4"), [
            '_token' => $this->grabFormCsrfToken($I),
        ]));
        $I->seeResponseContainsJson(["error" => "You can't add more than 3 locations."]);
    }

    public function addCouponLocation(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate("aw_mobile_coupon_location_add", ["couponId" => 123]) . "?_switch_user=_exit");
        $I->seeResponseCodeIs(403);
        $this->loginUser($I, $this->user);
        $I->sendGET($this->router->generate("aw_mobile_coupon_location_add", ["couponId" => 0]));
        $I->seeResponseCodeIs(404);
        $I->sendPOST($this->router->generate("aw_mobile_coupon_location_add", ["couponId" => 123]));
        $I->seeResponseCodeIs(405);

        /** @var Providercoupon $coupon */
        $coupon = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)
            ->find($I->createAwCoupon($this->user->getUserid(), "Test Coupon", 12345));

        $I->sendGET($route = $this->router->generate("aw_mobile_coupon_location_add", [
            "couponId" => $couponId = $coupon->getProvidercouponid(),
        ]));
        $I->seeResponseContainsJson(array_merge([
            "formData" => ["submitLabel" => "Add"],
        ], $this->getLocationForm($coupon->getProgramname(), $coupon->getKind())));

        $this->checkValidation($I, $route);

        $this->addLocation($I, $route, $this->getLocationRequest("Location #1"), [
            "AccountID" => null,
            "ProviderCouponID" => $couponId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $this->addLocation($I, $route, $this->getLocationRequest("Location #2"), [
            "AccountID" => null,
            "ProviderCouponID" => $couponId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $this->addLocation($I, $route, $this->getLocationRequest("Location #3"), [
            "AccountID" => null,
            "ProviderCouponID" => $couponId,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 0]);

        $I->assertEquals(3, $this->em->getRepository(\AwardWallet\MainBundle\Entity\Location::class)->getCountTotal($this->user));
        $I->assertEquals(3, $this->loyaltyLocation->getCountTotal($this->user));
        $I->sendGET($route);
        $I->sendPUT($route, array_merge($this->getLocationRequest("Location #4"), [
            '_token' => $this->grabFormCsrfToken($I),
        ]));
        $I->seeResponseContainsJson(["error" => "You can't add more than 3 locations."]);
    }

    public function editLocation(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate("aw_mobile_location_edit", ["locationId" => 123]) . "?_switch_user=_exit");
        $I->seeResponseCodeIs(403);
        $this->loginUser($I, $this->user);
        $I->sendGET($this->router->generate("aw_mobile_location_edit", ["locationId" => 0]));
        $I->seeResponseCodeIs(404);
        $I->sendPUT($this->router->generate("aw_mobile_location_edit", ["locationId" => 123]));
        $I->seeResponseCodeIs(405);

        /** @var Providercoupon $coupon */
        $coupon = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)
            ->find($I->createAwCoupon($this->user->getUserid(), "Test Coupon", 12345, null, ["Kind" => PROVIDER_KIND_CAR_RENTAL]));
        $addRoute = $this->router->generate("aw_mobile_coupon_location_add", [
            "couponId" => $couponId = $coupon->getProvidercouponid(),
        ]);
        $locationId = $this->addLocation($I, $addRoute, $editRequest = $this->getLocationRequest("Location #1"), [
            "ProviderCouponID" => $coupon->getProvidercouponid(),
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $I->sendGET($route = $this->router->generate("aw_mobile_location_edit", ["locationId" => $locationId]));
        $I->seeResponseContainsJson(array_merge([
            "formData" => ["submitLabel" => "Change"],
        ], $this->getLocationForm($coupon->getProgramname(), $coupon->getKind(), true)));
        $editRequest['name'] = "Xoxo";
        $I->sendPOST($route, array_merge($editRequest, [
            '_token' => $this->grabFormCsrfToken($I),
        ]));
        $I->seeResponseContainsJson(["success" => true]);
        $I->seeInDatabase("Location", [
            "LocationID" => $locationId,
            "Name" => $editRequest['name'],
        ]);

        /** @var Usr $user */
        $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)
            ->find($I->createAwUser());
        $coupon->setUserid($user);
        $this->em->flush();

        $I->sendGET($route = $this->router->generate("aw_mobile_location_edit", ["locationId" => $locationId]));
        $I->seeResponseCodeIs(403);
    }

    /**
     * @dataProvider editAccountLocationAccessDataProvider
     */
    public function editAccountLocationAccess(\TestSymfonyGuy $I, Example $example)
    {
        $I->amOnRoute("awm_new_login_status", ["_switch_user" => "_exit"]);
        $I->seeResponseCodeIs(200);
        /** @var AccountLocationAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $routes = [
            $this->router->generate("aw_mobile_location_edit", ["locationId" => $scenario->accountLocationId]),
            $this->router->generate("aw_mobile_location_edit", ["locationId" => $scenario->subAccountLocationId]),
        ];

        foreach ($routes as $route) {
            $I->sendPUT($route);
            $I->seeResponseCodeIs(405);
            $I->sendDELETE($route);
            $I->seeResponseCodeIs(405);

            foreach (["get", "post"] as $action) {
                $I->saveCsrfToken();
                call_user_func_array([$I, "send" . strtoupper($action)], [$route]);

                switch ($scenario->expectedAction) {
                    case Action::ALLOWED:
                        $I->seeResponseCodeIs(200);

                        break;

                    default:
                        $I->seeResponseCodeIs(403);
                }
            }
        }
    }

    public static function editAccountLocationAccessDataProvider()
    {
        return AccountLocationAccessScenario::editDataProvider();
    }

    /**
     * @dataProvider editCouponLocationAccessDataProvider
     */
    public function editCouponLocationAccess(\TestSymfonyGuy $I, Example $example)
    {
        $I->amOnRoute("awm_new_login_status", ["_switch_user" => "_exit"]);
        $I->seeResponseCodeIs(200);
        /** @var CouponLocationAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $routes = [
            $this->router->generate("aw_mobile_location_edit", ["locationId" => $scenario->locationId]),
        ];

        foreach ($routes as $route) {
            $I->sendPUT($route);
            $I->seeResponseCodeIs(405);
            $I->sendDELETE($route);
            $I->seeResponseCodeIs(405);

            foreach (["get", "post"] as $action) {
                $I->saveCsrfToken();
                call_user_func_array([$I, "send" . strtoupper($action)], [$route]);

                switch ($scenario->expectedAction) {
                    case Action::ALLOWED:
                        $I->seeResponseCodeIs(200);

                        break;

                    default:
                        $I->seeResponseCodeIs(403);
                }
            }
        }
    }

    public static function editCouponLocationAccessDataProvider()
    {
        return CouponLocationAccessScenario::editDataProvider();
    }

    public function deleteLocation(\TestSymfonyGuy $I)
    {
        $I->sendDELETE($this->router->generate("aw_mobile_location_delete", ["locationId" => 123]) . "?_switch_user=_exit");
        $I->seeResponseCodeIs(405);
        $this->loginUser($I, $this->user);
        $I->saveCsrfToken();
        $I->sendDELETE($this->router->generate("aw_mobile_location_delete", ["locationId" => 0]));
        $I->seeResponseCodeIs(404);
        $I->sendPUT($this->router->generate("aw_mobile_location_delete", ["locationId" => 123]));
        $I->seeResponseCodeIs(405);

        /** @var Providercoupon $coupon */
        $coupon = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)
            ->find($I->createAwCoupon($this->user->getUserid(), "Test Coupon", 12345, null, ["Kind" => PROVIDER_KIND_CAR_RENTAL]));
        $addRoute = $this->router->generate("aw_mobile_coupon_location_add", [
            "couponId" => $couponId = $coupon->getProvidercouponid(),
        ]);
        $locationId = $this->addLocation($I, $addRoute, $editRequest = $this->getLocationRequest("Location #1"), [
            "ProviderCouponID" => $coupon->getProvidercouponid(),
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $route = $this->router->generate("aw_mobile_location_delete", [
            "locationId" => $locationId,
        ]);
        $I->sendDELETE($route);
        $I->seeResponseContainsJson(["success" => true]);
        $I->dontSeeInDatabase("Location", [
            "LocationID" => $locationId,
        ]);
    }

    /**
     * @dataProvider deleteAccountLocationAccessDataProvider
     */
    public function deleteAccountLocationAccess(\TestSymfonyGuy $I, Example $example)
    {
        $I->amOnRoute("awm_new_login_status", ["_switch_user" => "_exit"]);
        $I->seeResponseCodeIs(200);
        /** @var AccountLocationAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $routes = [
            $this->router->generate("aw_mobile_location_delete", ["locationId" => $scenario->accountLocationId]),
            $this->router->generate("aw_mobile_location_delete", ["locationId" => $scenario->subAccountLocationId]),
        ];

        foreach ($routes as $route) {
            $I->sendPUT($route);
            $I->seeResponseCodeIs(405);
            $I->sendGET($route);
            $I->seeResponseCodeIs(405);
            $I->sendPOST($route);
            $I->seeResponseCodeIs(405);

            foreach (["delete"] as $action) {
                $I->saveCsrfToken();
                call_user_func_array([$I, "send" . strtoupper($action)], [$route]);

                switch ($scenario->expectedAction) {
                    case Action::ALLOWED:
                        $I->seeResponseCodeIs(200);

                        break;

                    default:
                        $I->seeResponseCodeIs(403);
                }
            }
        }
    }

    public static function deleteAccountLocationAccessDataProvider()
    {
        return AccountLocationAccessScenario::deleteDataProvider();
    }

    /**
     * @dataProvider deleteCouponLocationAccessDataProvider
     */
    public function deleteCouponLocationAccess(\TestSymfonyGuy $I, Example $example)
    {
        $I->amOnRoute("awm_new_login_status", ["_switch_user" => "_exit"]);
        $I->seeResponseCodeIs(200);
        /** @var CouponLocationAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $routes = [
            $this->router->generate("aw_mobile_location_delete", ["locationId" => $scenario->locationId]),
        ];

        foreach ($routes as $route) {
            $I->sendPUT($route);
            $I->seeResponseCodeIs(405);
            $I->sendGET($route);
            $I->seeResponseCodeIs(405);
            $I->sendPOST($route);
            $I->seeResponseCodeIs(405);

            foreach (["delete"] as $action) {
                $I->saveCsrfToken();
                call_user_func_array([$I, "send" . strtoupper($action)], [$route]);

                switch ($scenario->expectedAction) {
                    case Action::ALLOWED:
                        $I->seeResponseCodeIs(200);

                        break;

                    default:
                        $I->seeResponseCodeIs(403);
                }
            }
        }
    }

    public static function deleteCouponLocationAccessDataProvider()
    {
        return CouponLocationAccessScenario::deleteDataProvider();
    }

    public function listLocations(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate("aw_mobile_location_list") . "?_switch_user=_exit");
        $I->seeResponseCodeIs(403);
        $this->loginUser($I, $this->user);
        $I->sendPOST($this->router->generate("aw_mobile_location_list"));
        $I->seeResponseCodeIs(405);

        $I->saveCsrfToken();
        $I->sendGET($this->router->generate("aw_mobile_location_list"));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([]);

        /** @var Providercoupon $coupon */
        $coupon = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)
            ->find($I->createAwCoupon($this->user->getUserid(), "Test Coupon", 12345, null, ["Kind" => PROVIDER_KIND_CAR_RENTAL]));
        $addRoute = $this->router->generate("aw_mobile_coupon_location_add", [
            "couponId" => $couponId = $coupon->getProvidercouponid(),
        ]);
        $locationId = $this->addLocation($I, $addRoute, $editRequest = $this->getLocationRequest("Location #1"), [
            "ProviderCouponID" => $coupon->getProvidercouponid(),
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);

        $I->sendGET($this->router->generate("aw_mobile_location_list"));
        $I->seeResponseContainsJson([
            'children' => [
                [
                    'type' => 'groupTitle',
                ],
                [
                    'name' => 'location_' . $locationId,
                    'type' => 'switcher',
                    'value' => true,
                ],
            ],
        ]);

        $I->executeQuery("UPDATE LocationSetting SET Tracked = 0 WHERE LocationID = $locationId");
        $I->sendGET($this->router->generate("aw_mobile_location_list"));
        $I->dontSeeResponseJsonMatchesXpath("//children/attr/checked");
    }

    public function customRadius(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "balance.random");
        $this->addLocation($I, $this->router->generate("aw_mobile_account_location_add", ["accountId" => $accountId]), $this->getLocationRequest("Location #1"), [
            "AccountID" => $accountId,
            "Radius" => 75,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $accountId = $I->createAwAccount($this->user->getUserid(), 'delta', "test");
        $this->addLocation($I, $this->router->generate("aw_mobile_account_location_add", ["accountId" => $accountId]), $this->getLocationRequest("Location #2"), [
            "AccountID" => $accountId,
            "Radius" => 100,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 1]);
        $accountId = $I->createAwAccount($this->user->getUserid(), 'groupon', "test");
        $this->addLocation($I, $this->router->generate("aw_mobile_account_location_add", ["accountId" => $accountId]), $this->getLocationRequest("Location #3"), [
            "AccountID" => $accountId,
            "Radius" => 50,
        ], ["UserID" => $this->user->getUserid(), "Tracked" => 0]);
    }

    private function addLocation(\TestSymfonyGuy $I, $route, array $location, array $locationWhere, array $locationSettingWhere)
    {
        $I->sendGET($route);
        $I->seeResponseCodeIs(200);
        $I->sendPUT($route, array_merge($location, [
            '_token' => $this->grabFormCsrfToken($I),
        ]));
        $I->seeResponseContainsJson(["success" => true]);
        $locationId = (int) $I->grabDataFromJsonResponse("locationId");
        $I->assertNotEmpty($locationId);
        $I->seeInDatabase("Location", array_merge([
            "LocationID" => $locationId,
        ], $locationWhere));
        $I->seeInDatabase("LocationSetting", array_merge([
            "LocationID" => $locationId,
        ], $locationSettingWhere));

        return $locationId;
    }

    private function getLocationRequest($name, $tracked = 1, $lat = 10, $lng = 20)
    {
        return [
            'name' => $name,
            'lat' => $lat,
            'lng' => $lng,
            'tracked' => $tracked,
        ];
    }

    private function checkValidation(\TestSymfonyGuy $I, $route, $methodName = "sendPUT")
    {
        $I->$methodName($route, [
            'name' => 'My First Location',
            'lat' => -91,
            'lng' => 20,
            'tracked' => "1",
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(["errors" => ['This value should be between -90 and 90.']]);
        $I->$methodName($route, [
            'name' => 'My First Location',
            'lat' => 91,
            'lng' => 20,
            'tracked' => "1",
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(["errors" => ['This value should be between -90 and 90.']]);
        $I->$methodName($route, [
            'name' => 'My First Location',
            'lat' => 0,
            'lng' => -181,
            'tracked' => "1",
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(["errors" => ['This value should be between -180 and 180.']]);
        $I->$methodName($route, [
            'name' => 'My First Location',
            'lat' => 0,
            'lng' => 181,
            'tracked' => "1",
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(["errors" => ['This value should be between -180 and 180.']]);
        $I->$methodName($route, [
            'name' => 'My First Location',
            'lat' => 0,
            'lng' => 0,
            'tracked' => "1",
            'radius' => 10000,
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(["errors" => ['This value should be between 10 and 1000.']]);
    }

    private function grabFormCsrfToken(\TestSymfonyGuy $I)
    {
        return $I->grabDataFromResponseByJsonPath('$..[?(@.name = "_token")].value')[0];
    }

    private function getLocationForm($displayName, $kind, $edit = false)
    {
        return [
            "Kind" => $kind,
            "DisplayName" => $displayName,
            "formData" => [
                "children" => [
                    [
                        "full_name" => "location[map]",
                        "type" => "storeLocation",
                        "mapped" => false,
                    ],
                    [
                        "full_name" => "location[name]",
                        "type" => "hidden",
                        "mapped" => true,
                    ],
                    [
                        "full_name" => "location[lat]",
                        "type" => "hidden",
                        "mapped" => true,
                    ],
                    [
                        "full_name" => "location[lng]",
                        "type" => "hidden",
                        "mapped" => true,
                    ],
                    [
                        "full_name" => "location[tracked]",
                        "type" => !$edit ? "hidden" : "switcher",
                        "mapped" => true,
                    ],
                ],
            ],
        ];
    }
}
