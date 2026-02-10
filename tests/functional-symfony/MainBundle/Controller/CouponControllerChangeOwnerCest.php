<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @group frontend-functional
 */
class CouponControllerChangeOwnerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $me;

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
        $myId = $I->createAwUser(null, null, ['FirstName' => 'Tester'], true, true);
        $this->me = $users->find($myId);
        $connectedUserId = $I->createAwUser(null, null, ['FirstName' => 'Connected'], true, true);
        $this->connectedUser = $users->find($connectedUserId);
        $connectionId = $I->createConnection($connectedUserId, $myId, null, null, ['AccessLevel' => Useragent::ACCESS_WRITE]);
        $this->connection = $userAgents->find($connectionId);
        $reverseConnectionId = $I->createConnection($myId, $connectedUserId);
        $this->reverseConnection = $userAgents->find($reverseConnectionId);
        $familyMemberId = $I->createFamilyMember($myId, "Samantha", "Fox");
        $this->familyMember = $userAgents->find($familyMemberId);
        $connectedUserFamilyMemberId = $I->createFamilyMember($connectedUserId, "Samantha", "Bear");
        $this->connectedUserFamilyMember = $userAgents->find($connectedUserFamilyMemberId);
    }

    public function moveFromConnectedToMe(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->connectedUser->getUserid(), 'test', 100);
        $I->shareAwCouponByConnection($couponId, $this->connection->getUseragentid());

        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('providercoupon[owner]', "Connected Petrovich");
        $I->selectOption('providercoupon[owner]', $this->me->getUserid());
        $I->submitForm('form', []);
        $I->assertEquals($this->me->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertNull($I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
        $I->dontSeeInDatabase("ProviderCouponShare", ["ProviderCouponID" => $couponId, "UserAgentID" => $this->connection->getUseragentid()]);
    }

    public function moveFromMeToConnected(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->me->getUserid(), 'test', 100);

        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('providercoupon[owner]', "Tester Petrovich");
        $I->selectOption('providercoupon[owner]', $this->connectedUser->getUserid());
        $I->submitForm('form', []);
        $I->assertEquals($this->connectedUser->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertNull($I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
        $I->seeInDatabase("ProviderCouponShare", ["ProviderCouponID" => $couponId, "UserAgentID" => $this->connection->getUseragentid()]);
    }

    public function moveFromMeToFamilyMember(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->me->getUserid(), 'test', 100, null);

        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('providercoupon[owner]', 'Tester Petrovich');
        $I->selectOption('providercoupon[owner]', "{$this->me->getUserid()}_{$this->familyMember->getUseragentid()}");
        $I->submitForm('form', []);
        $I->assertEquals($this->me->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertEquals($this->familyMember->getUseragentid(), $I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
    }

    public function moveFromFamilyMemberToMe(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->me->getUserid(), 'test', 100, null, ['UserAgentID' => $this->familyMember->getUseragentid()]);

        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('providercoupon[owner]', "Samantha Fox (Tester Petrovich)");
        $I->selectOption('providercoupon[owner]', $this->me->getUserid());
        $I->submitForm('form', []);
        $I->assertEquals($this->me->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertNull($I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
    }

    public function moveFromConnectedFamilyMemberToMe(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->connectedUser->getUserid(), 'test', 100, null, ['UserAgentID' => $this->connectedUserFamilyMember->getUseragentid()]);
        $I->shareAwCouponByConnection($couponId, $this->connection->getUseragentid());

        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('providercoupon[owner]', "Samantha Bear (Connected Petrovich)");
        $I->selectOption('providercoupon[owner]', $this->me->getUserid());
        $I->submitForm('form', []);
        $I->assertEquals($this->me->getUserid(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["ProviderCouponID" => $couponId]));
        $I->assertNull($I->grabFromDatabase("ProviderCoupon", "UserAgentID", ["ProviderCouponID" => $couponId]));
        $I->dontSeeInDatabase("ProviderCouponShare", ["ProviderCouponID" => $couponId, "UserAgentID" => $this->connection->getUseragentid()]);
    }

    public function moveFromArbitrary(\TestSymfonyGuy $I)
    {
        $arbitraryUserId = $I->createAwUser();
        $couponId = $I->createAwCoupon($arbitraryUserId, 'test', 100);
        $I->amOnPage("coupon/edit/$couponId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(403);
    }
}
