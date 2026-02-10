<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @group frontend-functional
 */
class CouponControllerChangeOwnerBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $admin;

    /**
     * @var Usr
     */
    private $business;

    /**
     * @var Useragent
     */
    private $familyMember;

    /**
     * @var Usr
     */
    private $connectedUser;

    /**
     * @var Useragent
     */
    private $connectedUserFamilyMember;

    /**
     * @var Useragent
     */
    private $connection;

    /**
     * @var Useragent
     */
    private $reverseConnection;

    public function _before(\TestSymfonyGuy $I)
    {
        /** @var UsrRepository $users */
        $users = $I->grabService('doctrine')->getRepository(Usr::class);
        /** @var UseragentRepository $userAgents */
        $userAgents = $I->grabService('doctrine')->getRepository(Useragent::class);
        $adminId = $I->createAwUser(null, null, ['FirstName' => 'Admin'], true, true);
        $this->admin = $users->find($adminId);
        $businessId = $I->createAwUser(null, null, ['FirstName' => 'Business', 'AccountLevel' => ACCOUNT_LEVEL_BUSINESS], true);
        $this->business = $users->find($businessId);
        $I->connectUserWithBusiness($adminId, $businessId, ACCESS_ADMIN);
        $connectedUserId = $I->createAwUser(null, null, ['FirstName' => 'Connected'], true, true);
        $this->connectedUser = $users->find($connectedUserId);
        $connectionId = $I->createConnection($connectedUserId, $businessId, null, null, ['AccessLevel' => Useragent::ACCESS_WRITE]);
        $this->connection = $userAgents->find($connectionId);
        $reverseConnectionId = $I->createConnection($businessId, $connectedUserId);
        $this->reverseConnection = $userAgents->find($reverseConnectionId);
        $familyMemberId = $I->createFamilyMember($adminId, "Samantha", "Fox");
        $this->familyMember = $userAgents->find($familyMemberId);
        $connectedUserFamilyMemberId = $I->createFamilyMember($connectedUserId, "Samantha", "Bear");
        $this->connectedUserFamilyMember = $userAgents->find($connectedUserFamilyMemberId);
    }

    public function moveFromConnectedToBusiness(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->connectedUser->getUserid(), 'test', 100);
        $I->shareAwCouponByConnection($couponId, $this->connection->getUseragentid());

        $I->amOnSubdomain("business");
        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->admin->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeInField('providercoupon[owner]', $this->connection->getUseragentid());
        $I->fillField('providercoupon[owner]', "my");
        $I->submitForm('form', []);
        $I->assertEquals($this->business->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertNull($I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
        $I->dontSeeInDatabase("ProviderCouponShare", ["ProviderCouponID" => $couponId, "UserAgentID" => $this->connection->getUseragentid()]);
    }

    public function moveFromConnectedFamilyMemberToConnected(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->connectedUser->getUserid(), 'test', 100, null, ['UserAgentID' => $this->connectedUserFamilyMember->getUseragentid()]);
        $I->shareAwCouponByConnection($couponId, $this->connection->getUseragentid());

        $I->amOnSubdomain("business");
        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->admin->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeInField('providercoupon[owner]', $this->connectedUserFamilyMember->getUseragentid());
        $I->fillField('providercoupon[owner]', $this->connection->getUseragentid());
        $I->submitForm('form', []);
        $I->assertEquals($this->connectedUser->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertNull($I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
        $I->seeInDatabase("ProviderCouponShare", ["ProviderCouponID" => $couponId, "UserAgentID" => $this->connection->getUseragentid()]);
    }

    public function moveFromBusinessToConnected(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->business->getUserid(), 'test', 100);

        $I->amOnSubdomain("business");
        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->admin->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeInField('providercoupon[owner]', '');
        $I->fillField('providercoupon[owner]', $this->connection->getUseragentid());
        $I->submitForm('form', []);
        $I->assertEquals($this->connectedUser->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertNull($I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
        $I->seeInDatabase("ProviderCouponShare", ["ProviderCouponID" => $couponId, "UserAgentID" => $this->connection->getUseragentid()]);
    }

    public function moveFromBusinessToConnectedFamilyMember(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->business->getUserid(), 'test', 100);

        $I->amOnSubdomain("business");
        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->admin->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeInField('providercoupon[owner]', '');
        $I->fillField('providercoupon[owner]', $this->connectedUserFamilyMember->getUseragentid());
        $I->submitForm('form', []);
        $I->assertEquals($this->connectedUser->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertEquals($this->connectedUserFamilyMember->getUseragentid(), $I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
        $I->seeInDatabase("ProviderCouponShare", ["ProviderCouponID" => $couponId, "UserAgentID" => $this->connection->getUseragentid()]);
    }

    public function moveFromArbitrary(\TestSymfonyGuy $I)
    {
        $arbitraryUserId = $I->createAwUser();
        $couponId = $I->createAwCoupon($arbitraryUserId, 'test', 100);
        $I->amOnSubdomain("business");
        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->admin->getLogin()}");
        $I->seeResponseCodeIs(403);
    }
}
