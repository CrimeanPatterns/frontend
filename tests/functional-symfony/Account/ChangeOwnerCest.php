<?php

namespace Account;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class ChangeOwnerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var UsrRepository
     */
    private $users;

    /**
     * @var Usr
     */
    private $user;

    /**
     * @var RouterInterface
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->users = $I->grabService('doctrine')->getRepository(Usr::class);
        $this->user = $this->users->find($I->createAwUser(null, null, ['FirstName' => 'First'], true, true));

        $this->router = $I->grabService('router');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->users = null;
        $this->user = null;
        $this->router = null;
    }

    public function moveFromConnectedToConnected(\TestSymfonyGuy $I)
    {
        $user2 = $this->users->find($I->createAwUser(null, null, ['FirstName' => 'Second'], true, true));
        $userAgentId2 = $I->createConnection($user2->getUserid(), $this->user->getUserid(), null, null, ['AccessLevel' => ACCESS_WRITE]);
        $I->createConnection($this->user->getUserid(), $user2->getUserid());

        $accountId = $I->createAwAccount($user2->getUserid(), "testprovider", "balance.random");
        $I->shareAwAccountByConnection($accountId, $userAgentId2);

        $user3 = $this->users->find($I->createAwUser(null, null, ['FirstName' => 'Third'], true, true));
        $userAgentId3 = $I->createConnection($user3->getUserid(), $this->user->getUserid(), null, null, ['AccessLevel' => ACCESS_WRITE]);
        $I->createConnection($this->user->getUserid(), $user3->getUserid());

        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]) . "?_switch_user=" . $this->user->getLogin());
        $I->seeResponseCodeIs(200);
        $I->see('Test Provider');
        $I->seeOptionIsSelected('account[owner]', 'Second Petrovich');
        $I->selectOption('account[owner]', 'Third Petrovich');
        $I->submitForm('#account-form', []);
        $I->seeResponseCodeIs(200);
        $I->assertEquals($user3->getUserid(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertEquals(null, $I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->dontSeeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $userAgentId2]);
        $I->seeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $userAgentId3]);
    }

    public function moveFromConnectedReadonlyToConnected(\TestSymfonyGuy $I)
    {
        $user2 = $this->users->find($I->createAwUser(null, null, ['FirstName' => 'Second'], true, true));
        $userAgentId2 = $I->createConnection($user2->getUserid(), $this->user->getUserid(), null, null, ['AccessLevel' => ACCESS_WRITE]);
        $I->createConnection($this->user->getUserid(), $user2->getUserid());

        $accountId = $I->createAwAccount($user2->getUserid(), "testprovider", "balance.random");
        $I->shareAwAccountByConnection($accountId, $userAgentId2);

        $user3 = $this->users->find($I->createAwUser(null, null, ['FirstName' => 'Third'], true, true));
        $userAgentId3 = $I->createConnection($user3->getUserid(), $this->user->getUserid(), null, null, ['AccessLevel' => ACCESS_WRITE]);
        $I->createConnection($this->user->getUserid(), $user3->getUserid());

        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]) . "?_switch_user=" . $this->user->getLogin());
        $I->seeResponseCodeIs(200);
        $I->see('Test Provider');
        $I->canSeeOptionIsSelected('account[owner]', 'Second Petrovich');
        $I->selectOption('account[owner]', 'Third Petrovich');

        $I->executeQuery("update UserAgent set AccessLevel = " . ACCESS_READ_ALL . " where UserAgentID = " . $userAgentId2);
        $I->submitForm('#account-form', []);
        $I->seeResponseCodeIs(403);
        $I->assertEquals($user2->getUserid(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertEquals(null, $I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->seeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $userAgentId2]);
        $I->dontSeeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $userAgentId3]);
    }

    public function moveFromConnectedToConnectedReadonly(\TestSymfonyGuy $I)
    {
        $user2 = $this->users->find($I->createAwUser(null, null, ['FirstName' => 'Second'], true, true));
        $userAgentId2 = $I->createConnection($user2->getUserid(), $this->user->getUserid(), null, null, ['AccessLevel' => ACCESS_WRITE]);
        $I->createConnection($this->user->getUserid(), $user2->getUserid());

        $accountId = $I->createAwAccount($user2->getUserid(), "testprovider", "balance.random");
        $I->shareAwAccountByConnection($accountId, $userAgentId2);

        $user3 = $this->users->find($I->createAwUser(null, null, ['FirstName' => 'Third'], true, true));
        $userAgentId3 = $I->createConnection($user3->getUserid(), $this->user->getUserid(), null, null, ['AccessLevel' => ACCESS_WRITE]);
        $I->createConnection($this->user->getUserid(), $user3->getUserid());

        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]) . "?_switch_user=" . $this->user->getLogin());
        $I->seeResponseCodeIs(200);
        $I->see('Test Provider');
        $I->canSeeOptionIsSelected('account[owner]', 'Second Petrovich');
        $I->selectOption('account[owner]', 'Third Petrovich');

        $I->executeQuery("update UserAgent set AccessLevel = " . ACCESS_READ_ALL . " where UserAgentID = " . $userAgentId3);
        $I->submitForm('#account-form', []);
        $I->seeResponseCodeIs(200);
        $I->assertEquals($user2->getUserid(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertEquals(null, $I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->seeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $userAgentId2]);
        $I->dontSeeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $userAgentId3]);
    }
}
