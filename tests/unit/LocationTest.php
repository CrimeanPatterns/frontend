<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Location;
use AwardWallet\MainBundle\Entity\LocationContainerInterface;
use AwardWallet\MainBundle\Entity\LocationSetting;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\LocationRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use Codeception\Module\Aw;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @group loyalty-location
 * @group frontend-unit
 */
class LocationTest extends BaseUserTest
{
    /**
     * @var LocationRepository
     */
    private $locationRep;

    /**
     * @var LoyaltyLocation
     */
    private $loyaltyLocation;

    /**
     * @var CacheManager
     */
    private $cache;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    public function _before()
    {
        parent::_before();

        $this->locationRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Location::class);
        $this->loyaltyLocation = $this->container->get(LoyaltyLocation::class);
        $this->cache = $this->container->get(CacheManager::class);
        $this->authChecker = $this->container->get("security.authorization_checker");
    }

    public function _after()
    {
        $this->authChecker = null;
        $this->cache = null;
        $this->loyaltyLocation = null;
        $this->locationRep = null;
        parent::_after();
    }

    public function testNoLocations()
    {
        $this->assertEquals(0, $this->locationRep->getCountTotal($this->user));
        $this->assertEquals(0, $this->loyaltyLocation->getCountTotal($this->user));
        $this->assertEquals(0, $this->locationRep->getCountTracked($this->user));
        $this->assertEquals(0, $this->loyaltyLocation->getCountTracked($this->user));
    }

    public function testAccounts()
    {
        $account = $this->createAccount($this->user);
        $settings = $this->addLocation($this->user, $account, "Test Location", 10, 20, true);
        $this->db->seeInDatabase("Location", [
            "AccountID" => $account->getAccountid(),
            "Name" => $settings->getLocation()->getName(),
        ]);
        $this->db->seeInDatabase("LocationSetting", [
            "LocationID" => $settings->getLocation()->getId(),
            "Tracked" => 1,
        ]);
        $this->assertLocationsCount($this->user, 1, 1);

        $settings->setTracked(false);
        $this->em->flush();
        $this->assertLocationsCount($this->user, 0, 1);

        $account2 = $this->createAccount($this->user);
        $this->assertLocationsCount($this->user, 0, 1);
        $this->addLocation($this->user, $account, "Test Location #2", 10, 20, true);
        $this->assertLocationsCount($this->user, 1, 2);
        $this->addLocation($this->user, $account2, "Test Location #3", 10, 20, true);
        $this->assertLocationsCount($this->user, 2, 3);

        $user = $this->createUser();
        $this->assertLocationsCount($user, 0, 0);
        $uaId = $this->aw->createConnection($this->user->getUserid(), $user->getUserid(), true, null, [
            "AccessLevel" => ACCESS_WRITE,
        ]);
        $this->aw->createConnection($user->getUserid(), $this->user->getUserid(), true);
        $this->db->haveInDatabase("AccountShare", [
            "AccountID" => $account2->getAccountid(),
            "UserAgentID" => $uaId,
        ]);
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 1);
        $this->db->haveInDatabase("AccountShare", [
            "AccountID" => $account->getAccountid(),
            "UserAgentID" => $uaId,
        ]);
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 3);

        $location = $settings->getLocation();
        $settings2 = new LocationSetting($location, $user, true);
        $location->addLocationSettings($settings2);
        $this->em->flush();
        $this->assertLocationsCount($user, 1, 3);

        $this->db->executeQuery("UPDATE UserAgent SET AccessLevel = " . ACCESS_READ_NUMBER . " WHERE UserAgentID = $uaId");
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 0);
    }

    public function testSubAccounts()
    {
        $account = $this->createAccount($this->user);
        $subaccount1 = $this->createSubAccount($account, [
            "Code" => "#1",
            "DisplayName" => "Test Subaccount",
        ]);
        $subaccount12 = $this->createSubAccount($account, [
            "Code" => "#2",
            "DisplayName" => "Test Subaccount #2",
        ]);

        $settings = $this->addLocation($this->user, $subaccount12, "Test Location", 10, 20, true);
        $this->db->seeInDatabase("Location", [
            "SubAccountID" => $subaccount12->getSubaccountid(),
            "Name" => $settings->getLocation()->getName(),
        ]);
        $this->db->seeInDatabase("LocationSetting", [
            "LocationID" => $settings->getLocation()->getId(),
            "Tracked" => 1,
        ]);
        $this->assertLocationsCount($this->user, 1, 1);
        $this->addLocation($this->user, $subaccount1, "Test Location #2", 10, 20, true);
        $this->assertLocationsCount($this->user, 2, 2);

        $settings->setTracked(false);
        $this->em->flush();
        $this->assertLocationsCount($this->user, 1, 2);

        $account2 = $this->createAccount($this->user);
        $subaccount2 = $this->createSubAccount($account2, [
            "Code" => "#1",
            "DisplayName" => "Test Subaccount",
        ]);
        $this->assertLocationsCount($this->user, 1, 2);
        $this->addLocation($this->user, $subaccount2, "Test Location #2", 10, 20, true);
        $this->assertLocationsCount($this->user, 2, 3);
        $this->addLocation($this->user, $account2, "Test Location #3", 10, 20, true);
        $this->assertLocationsCount($this->user, 3, 4);

        $user = $this->createUser();
        $this->assertLocationsCount($user, 0, 0);
        $uaId = $this->aw->createConnection($this->user->getUserid(), $user->getUserid(), true, null, [
            "AccessLevel" => ACCESS_WRITE,
        ]);
        $this->aw->createConnection($user->getUserid(), $this->user->getUserid(), true);
        $this->db->haveInDatabase("AccountShare", [
            "AccountID" => $account2->getAccountid(),
            "UserAgentID" => $uaId,
        ]);
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 2);
        $this->db->haveInDatabase("AccountShare", [
            "AccountID" => $account->getAccountid(),
            "UserAgentID" => $uaId,
        ]);
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 4);

        $location = $settings->getLocation();
        $settings2 = new LocationSetting($location, $user, true);
        $location->addLocationSettings($settings2);
        $this->em->flush();
        $this->assertLocationsCount($user, 1, 4);

        $this->db->executeQuery("UPDATE UserAgent SET AccessLevel = " . ACCESS_READ_NUMBER . " WHERE UserAgentID = $uaId");
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 0);
    }

    public function testCoupons()
    {
        $coupon = $this->createCoupon($this->user, "Test Coupon", 5000);
        $settings = $this->addLocation($this->user, $coupon, "Test Location", 10, 20, true);
        $this->db->seeInDatabase("Location", [
            "ProviderCouponID" => $coupon->getProvidercouponid(),
            "Name" => $settings->getLocation()->getName(),
        ]);
        $this->db->seeInDatabase("LocationSetting", [
            "LocationID" => $settings->getLocation()->getId(),
            "Tracked" => 1,
        ]);
        $this->assertLocationsCount($this->user, 1, 1);

        $settings->setTracked(false);
        $this->em->flush();
        $this->assertLocationsCount($this->user, 0, 1);

        $coupon2 = $this->createCoupon($this->user, "Test Coupon 2", 5000);
        $this->assertLocationsCount($this->user, 0, 1);
        $this->addLocation($this->user, $coupon, "Test Location #2", 10, 20, true);
        $this->assertLocationsCount($this->user, 1, 2);
        $this->addLocation($this->user, $coupon2, "Test Location #3", 10, 20, true);
        $this->assertLocationsCount($this->user, 2, 3);

        $user = $this->createUser();
        $this->assertLocationsCount($user, 0, 0);
        $uaId = $this->aw->createConnection($this->user->getUserid(), $user->getUserid(), true, null, [
            "AccessLevel" => ACCESS_WRITE,
        ]);
        $this->aw->createConnection($user->getUserid(), $this->user->getUserid(), true);
        $this->db->haveInDatabase("ProviderCouponShare", [
            "ProviderCouponID" => $coupon2->getProvidercouponid(),
            "UserAgentID" => $uaId,
        ]);
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 1);
        $this->db->haveInDatabase("ProviderCouponShare", [
            "ProviderCouponID" => $coupon->getProvidercouponid(),
            "UserAgentID" => $uaId,
        ]);
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 3);

        $location = $settings->getLocation();
        $settings2 = new LocationSetting($location, $user, true);
        $location->addLocationSettings($settings2);
        $this->em->flush();
        $this->assertLocationsCount($user, 1, 3);

        $this->db->executeQuery("UPDATE UserAgent SET AccessLevel = " . ACCESS_READ_NUMBER . " WHERE UserAgentID = $uaId");
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        $this->assertLocationsCount($user, 0, 0);
    }

    public function testAccountVoter()
    {
        $account = $this->createAccount($this->user);
        $location = $this->addLocation($this->user, $account, "Test Location", 10, 20, true)->getLocation();
        $this->assertTrue($this->authChecker->isGranted("VIEW", $location));
        $this->assertTrue($this->authChecker->isGranted("EDIT", $location));
        $this->assertTrue($this->authChecker->isGranted("DELETE", $location));

        $otherUser = $this->createUser();
        $account->setUserid($otherUser);
        $this->em->flush();
        $this->assertFalse($this->authChecker->isGranted("VIEW", $location));
        $this->assertFalse($this->authChecker->isGranted("EDIT", $location));
        $this->assertFalse($this->authChecker->isGranted("DELETE", $location));

        // create connection
        $this->aw->createConnection($this->user->getUserid(), $otherUser->getUserid());
        /** @var Useragent $ua */
        $ua = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->find($this->aw->createConnection($otherUser->getUserid(), $this->user->getUserid()));
        $this->db->haveInDatabase("AccountShare", [
            "AccountID" => $account->getAccountid(),
            "UserAgentID" => $ua->getUseragentid(),
        ]);

        foreach ($this->votersDataProvider() as $row) {
            $ua->setAccesslevel($row[0]);
            $this->em->flush();
            $this->em->refresh($account);
            $this->assertEquals($row[1], $this->authChecker->isGranted("VIEW", $location), var_export($row, true));
            $this->assertEquals($row[2], $this->authChecker->isGranted("EDIT", $location), var_export($row, true));
            $this->assertEquals($row[3], $this->authChecker->isGranted("DELETE", $location), var_export($row, true));
        }
    }

    public function testSubAccountVoter()
    {
        $account = $this->createAccount($this->user);
        $subaccount = $this->createSubAccount($account, [
            "Code" => "#1",
            "DisplayName" => "Test Subaccount",
        ]);
        $location = $this->addLocation($this->user, $subaccount, "Test Location", 10, 20, true)->getLocation();
        $this->assertTrue($this->authChecker->isGranted("VIEW", $location));
        $this->assertTrue($this->authChecker->isGranted("EDIT", $location));
        $this->assertTrue($this->authChecker->isGranted("DELETE", $location));

        $otherUser = $this->createUser();
        $account->setUserid($otherUser);
        $this->em->flush();
        $this->assertFalse($this->authChecker->isGranted("VIEW", $location));
        $this->assertFalse($this->authChecker->isGranted("EDIT", $location));
        $this->assertFalse($this->authChecker->isGranted("DELETE", $location));

        // create connection
        $this->aw->createConnection($this->user->getUserid(), $otherUser->getUserid());
        /** @var Useragent $ua */
        $ua = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->find($this->aw->createConnection($otherUser->getUserid(), $this->user->getUserid()));
        $this->db->haveInDatabase("AccountShare", [
            "AccountID" => $account->getAccountid(),
            "UserAgentID" => $ua->getUseragentid(),
        ]);

        foreach ($this->votersDataProvider() as $row) {
            $ua->setAccesslevel($row[0]);
            $this->em->flush();
            $this->em->refresh($account);
            $this->assertEquals($row[1], $this->authChecker->isGranted("VIEW", $location), var_export($row, true));
            $this->assertEquals($row[2], $this->authChecker->isGranted("EDIT", $location), var_export($row, true));
            $this->assertEquals($row[3], $this->authChecker->isGranted("DELETE", $location), var_export($row, true));
        }
    }

    public function testCouponVoter()
    {
        $coupon = $this->createCoupon($this->user, "Test Coupon", 5000);
        $location = $this->addLocation($this->user, $coupon, "Test Location", 10, 20, true)->getLocation();
        $this->assertTrue($this->authChecker->isGranted("VIEW", $location));
        $this->assertTrue($this->authChecker->isGranted("EDIT", $location));
        $this->assertTrue($this->authChecker->isGranted("DELETE", $location));

        $otherUser = $this->createUser();
        $coupon->setUserid($otherUser);
        $this->em->flush();
        $this->assertFalse($this->authChecker->isGranted("VIEW", $location));
        $this->assertFalse($this->authChecker->isGranted("EDIT", $location));
        $this->assertFalse($this->authChecker->isGranted("DELETE", $location));

        // create connection
        $this->aw->createConnection($this->user->getUserid(), $otherUser->getUserid());
        /** @var Useragent $ua */
        $ua = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->find($this->aw->createConnection($otherUser->getUserid(), $this->user->getUserid()));
        $this->db->haveInDatabase("ProviderCouponShare", [
            "ProviderCouponID" => $coupon->getProvidercouponid(),
            "UserAgentID" => $ua->getUseragentid(),
        ]);

        foreach ($this->votersDataProvider() as $row) {
            $ua->setAccesslevel($row[0]);
            $this->em->flush();
            $this->em->refresh($coupon);
            $this->assertEquals($row[1], $this->authChecker->isGranted("VIEW", $location), var_export($row, true));
            $this->assertEquals($row[2], $this->authChecker->isGranted("EDIT", $location), var_export($row, true));
            $this->assertEquals($row[3], $this->authChecker->isGranted("DELETE", $location), var_export($row, true));
        }
    }

    public function votersDataProvider()
    {
        return [
            //   AccessLevel,                   view,  edit,  delete
            [ACCESS_READ_NUMBER,             false, false, false],
            [ACCESS_READ_BALANCE_AND_STATUS, false, false, false],
            [ACCESS_READ_ALL,                false, false, false],
            [ACCESS_WRITE,                   true,  true,  true],
        ];
    }

    private function assertLocationsCount(Usr $user, $tracked, $total)
    {
        $this->assertEquals($total, $this->locationRep->getCountTotal($user));
        $this->assertEquals($total, $this->loyaltyLocation->getCountTotal($user));
        $this->assertEquals($tracked, $this->locationRep->getCountTracked($user));
        $this->assertEquals($tracked, $this->loyaltyLocation->getCountTracked($user));
    }

    /**
     * @return Account
     */
    private function createAccount(Usr $user)
    {
        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)
            ->find($this->aw->createAwAccount($user->getUserid(), Aw::TEST_PROVIDER_ID, "balance.random"));
    }

    /**
     * @param array $fields
     * @return Subaccount
     */
    private function createSubAccount(Account $account, $fields = [])
    {
        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class)
            ->find($this->aw->createAwSubAccount($account->getAccountid(), $fields));
    }

    /**
     * @param string $name
     * @param string $value
     * @return Providercoupon
     */
    private function createCoupon(Usr $user, $name, $value)
    {
        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)
            ->find($this->aw->createAwCoupon($user->getUserid(), $name, $value));
    }

    /**
     * @return Usr
     */
    private function createUser()
    {
        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)
            ->find($this->aw->createAwUser(null, null, [], true));
    }

    /**
     * @param string $name
     * @param float $lat
     * @param float $lng
     * @param bool $tracked
     * @return LocationSetting
     */
    private function addLocation(Usr $user, LocationContainerInterface $container, $name, $lat, $lng, $tracked = false)
    {
        $location = (new Location())
            ->setContainer($container)
            ->setName($name)
            ->setLat($lat)
            ->setLng($lng);
        $settings = new LocationSetting($location, $user, $tracked);
        $location->addLocationSettings($settings);
        $this->em->persist($location);
        $this->em->flush();

        return $settings;
    }
}
