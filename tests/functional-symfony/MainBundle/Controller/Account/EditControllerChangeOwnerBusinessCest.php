<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class EditControllerChangeOwnerBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $user;

    /**
     * @var Usr
     */
    private $business;

    /**
     * @var Usr
     */
    private $businessAdmin;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var int
     */
    private $userAgentId;

    /**
     * @var int
     */
    private $backConnectionId;

    /**
     * @var int
     */
    private $familyMemberId;

    public function _before(\TestSymfonyGuy $I)
    {
        $users = $I->grabService('doctrine')->getRepository(Usr::class);
        $this->user = $users->find($I->createAwUser(null, null, [], true, true));
        $this->familyMemberId = $I->createFamilyMember($this->user->getId(), "Samantha", "Fox");

        $this->business = $users->find($I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS], true));
        $this->businessAdmin = $users->find($I->createAwUser(null, null, [], true));
        $I->connectUserWithBusiness($this->businessAdmin->getId(), $this->business->getId(), ACCESS_ADMIN);

        $this->userAgentId = $I->createConnection($this->user->getId(), $this->business->getId(), null, null, ['AccessLevel' => ACCESS_WRITE]);
        $this->backConnectionId = $I->createConnection($this->business->getId(), $this->user->getId()); // approved back-connection required for account voter

        $this->router = $I->grabService('router');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->user = null;
        $this->business = null;
        $this->businessAdmin = null;
        $this->router = null;
    }

    public function moveFromConnectedToBusiness(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $I->shareAwAccountByConnection($accountId, $this->userAgentId);

        $I->amOnSubdomain("business");
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]) . "?_switch_user=" . $this->businessAdmin->getLogin());
        $I->seeResponseCodeIs(200);
        $I->see('Test Provider');
        $I->seeInField('account[owner]', $this->userAgentId);
        $I->fillField('account[owner]', "my");
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->business->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertEquals(null, $I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->dontSeeInDatabase("AccountShare", ["AccountID" => $accountId]);
    }

    public function moveFromConnectedFamilyMemberToConnected(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "balance.random", null, ['UserAgentID' => $this->familyMemberId]);
        $I->shareAwAccountByConnection($accountId, $this->userAgentId);

        $I->amOnSubdomain("business");
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]) . "?_switch_user=" . $this->businessAdmin->getLogin());
        $I->seeResponseCodeIs(200);
        $I->seeInField('account[owner]', $this->familyMemberId);
        $I->fillField('account[owner]', $this->userAgentId);
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->user->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertEquals(null, $I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $this->userAgentId]);
    }

    public function moveFromBusinessToConnected(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->business->getId(), "testprovider", "balance.random", null);

        $I->amOnSubdomain("business");
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]) . "?_switch_user=" . $this->businessAdmin->getLogin());
        $I->seeResponseCodeIs(200);
        $I->seeInField('account[owner]', "");
        $I->fillField('account[owner]', $this->userAgentId);
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->user->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertEquals(null, $I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $this->userAgentId]);
    }

    public function moveFromBusinessToConnectedFamilyMember(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->business->getId(), "testprovider", "balance.random", null);

        $I->amOnSubdomain("business");
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]) . "?_switch_user=" . $this->businessAdmin->getLogin());
        $I->seeResponseCodeIs(200);
        $I->seeInField('account[owner]', "");
        $I->fillField('account[owner]', $this->familyMemberId);
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->user->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertEquals($this->familyMemberId, $I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $this->userAgentId]);
    }

    public function moveFromArbitrary(\TestSymfonyGuy $I)
    {
        $arbitraryUserId = $I->createAwUser();
        $accountId = $I->createAwAccount($arbitraryUserId, 'testprovider', 'balance.random');
        $I->amOnSubdomain("business");
        $I->amOnPage("account/edit/$accountId?_switch_user={$this->businessAdmin->getLogin()}");
        $I->seeResponseCodeIs(403);
    }
}
