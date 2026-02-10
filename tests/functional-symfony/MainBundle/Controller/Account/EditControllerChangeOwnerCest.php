<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @group frontend-functional
 */
class EditControllerChangeOwnerCest
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
        $accountId = $I->createAwAccount($this->connectedUser->getId(), 'testprovider', "balance.random");
        $I->shareAwAccountByConnection($accountId, $this->connection->getId());

        $I->amOnPage("account/edit/$accountId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('account[owner]', 'Connected Petrovich');
        $I->selectOption('account[owner]', $this->me->getId());
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->me->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertNull($I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->dontSeeInDatabase("AccountShare", ["AccountID" => $accountId]);
    }

    public function moveFromMeToConnected(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->me->getId(), 'testprovider', "balance.random");

        $I->amOnPage("account/edit/$accountId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('account[owner]', 'Tester Petrovich');
        $I->selectOption('account[owner]', $this->connectedUser->getId());
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->connectedUser->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertNull($I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->seeInDatabase("AccountShare", ["AccountID" => $accountId, "UserAgentID" => $this->connection->getId()]);
    }

    public function moveFromMeToFamilyMember(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->me->getId(), 'testprovider', "balance.random");

        $I->amOnPage("account/edit/$accountId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('account[owner]', 'Tester Petrovich');
        $I->selectOption('account[owner]', $this->me->getId() . '_' . $this->familyMember->getId());
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->me->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertEquals($this->familyMember->getId(), $I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->dontSeeInDatabase("AccountShare", ["AccountID" => $accountId]);
    }

    public function moveFromFamilyMemberToMe(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->me->getId(), 'testprovider', "balance.random", null, ['UserAgentID' => $this->familyMember->getId()]);

        $I->amOnPage("account/edit/$accountId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('account[owner]', 'Samantha Fox (Tester Petrovich)');
        $I->selectOption('account[owner]', $this->me->getId());
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->me->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertNull($I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->dontSeeInDatabase("AccountShare", ["AccountID" => $accountId]);
    }

    public function moveFromConnectedFamilyMemberToMe(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->connectedUser->getId(), 'testprovider', "balance.random", null, ['UserAgentID' => $this->connectedUserFamilyMember->getId()]);
        $I->shareAwAccountByConnection($accountId, $this->connection->getId());

        $I->amOnPage("account/edit/$accountId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('account[owner]', 'Samantha Bear (Connected Petrovich)');
        $I->selectOption('account[owner]', $this->me->getId());
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->me->getId(), $I->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
        $I->assertNull($I->grabFromDatabase("Account", "UserAgentID", ["AccountID" => $accountId]));
        $I->dontSeeInDatabase("AccountShare", ["AccountID" => $accountId]);
    }

    public function couponsOwnerChangeWithAccount(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->me->getId(), 'testprovider', "balance.random");
        $I->createAwCoupon($this->me->getId(), 'test', 100, null, ['AccountID' => $accountId]);

        $I->amOnPage("account/edit/$accountId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('account[owner]', 'Tester Petrovich');
        $I->selectOption('account[owner]', $this->connectedUser->getId());
        $I->submitForm('#account-form', []);
        $I->assertEquals($this->connectedUser->getId(), $I->grabFromDatabase("ProviderCoupon", "UserID", ["AccountID" => $accountId]));
    }

    public function moveFromArbitrary(\TestSymfonyGuy $I)
    {
        $arbitraryUserId = $I->createAwUser();
        $accountId = $I->createAwAccount($arbitraryUserId, 'testprovider', 'balance.random');
        $I->amOnPage("account/edit/$accountId?_switch_user={$this->me->getLogin()}");
        $I->seeResponseCodeIs(403);
    }
}
