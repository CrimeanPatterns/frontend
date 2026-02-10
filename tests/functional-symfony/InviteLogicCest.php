<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Codeception\Module\Mail;
use Symfony\Component\Routing\Router;

/**
 * @group frontend-functional
 */
class InviteLogicCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    protected $business;

    /**
     * @var Usr
     */
    protected $businessAdmin;

    /**
     * @var Useragent
     */
    protected $familyMember;

    /** @var Usr */
    protected $invitedUser;
    /**
     * @var Usr
     */
    private $user1;

    /**
     * @var Usr
     */
    private $user2;

    public function _before(\TestSymfonyGuy $I)
    {
        $I->setSource(Mail::SOURCE_SWIFT);
    }

    public function denyAccountAccessNoConnection(\TestSymfonyGuy $I)
    {
        $I->wantTo("check denied access to account edit page if connection doesn't exist");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $accountId = $I->createAwAccount($this->user1->getUserid(), "aeroflot", "balance.random", "pass1", ["Disabled" => 0]);
        $page = $I->grabService('router')->generate('aw_account_edit', ['accountId' => $accountId]);

        $I->amOnPage($page . "?_switch_user=" . $this->user2->getLogin());
        $I->dontSeeElement('form', ['id' => 'account-form']);
    }

    public function denyAccountAccessUnapprovedConnection(\TestSymfonyGuy $I)
    {
        $I->wantTo("check denied access to account edit page if connection is unapproved");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $accountId = $I->createAwAccount($this->user1->getUserId(), "aeroflot", "balance.random", "pass1", ["Disabled" => 0]);

        $connectionId = $I->createConnection($this->user1->getUserId(), $this->user2->getUserId(), false, true, ['AccessLevel' => ACCESS_WRITE]);
        $I->haveInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $connectionId]);

        $page = $I->grabService('router')->generate('aw_account_edit', ['accountId' => $accountId]);

        $I->amOnPage($page . "?_switch_user=" . $this->user2->getLogin());
        $I->dontSee('Aeroflot Bonus');
    }

    public function denyAccountAccessReadonlyConnection(\TestSymfonyGuy $I)
    {
        $I->wantTo("check denied access to account edit page if connection is approved and ACCESS_READ_ALL given");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $accountId = $I->createAwAccount($this->user1->getUserId(), "aeroflot", "balance.random", "pass1", ["Disabled" => 0]);

        $connectionId = $I->createConnection($this->user1->getUserId(), $this->user2->getUserId(), true, true, ['AccessLevel' => ACCESS_READ_ALL]);
        $I->haveInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $connectionId]);

        $page = $I->grabService('router')->generate('aw_account_edit', ['accountId' => $accountId]);

        $I->amOnPage($page . "?_switch_user=" . $this->user2->getLogin());
        $I->dontSee('Aeroflot Bonus');
    }

    public function allowAccountAccessApprovedConnection(\TestSymfonyGuy $I)
    {
        $I->wantTo("check allowed access to account edit page if connection is approved and ACCESS_WRITE given");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $accountId = $I->createAwAccount($this->user1->getUserId(), "aeroflot", "balance.random", "pass1", ["Disabled" => 0]);

        $connectionId = $I->createConnection($this->user1->getUserId(), $this->user2->getUserId(), true, true, ['AccessLevel' => ACCESS_WRITE]);
        $I->createConnection($this->user2->getUserId(), $this->user1->getUserId(), true, true, ['AccessLevel' => ACCESS_WRITE]);
        $I->haveInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $connectionId]);

        $page = $I->grabService('router')->generate('aw_account_edit', ['accountId' => $accountId]);

        $I->amOnPage($page . "?_switch_user=" . $this->user2->getLogin());
        $I->see('Aeroflot Bonus');
    }

    public function sendInviteLetter(\TestSymfonyGuy $I)
    {
        $I->wantTo("check invite email and working invite link");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $page = $I->grabService('router')->generate('aw_create_connection');
        $I->amOnPage($page . "?_switch_user=" . $this->user1->getLogin());
        $email = $I->grabRandomString(5) . '@gmail.com';

        $I->see('Add a new connection');
        $I->fillField('Email', $email);
        $I->click('Submit');
        $I->see('We sent an email to the address you specified');

        $I->seeEmailTo($email);

        $email = $I->grabLastMailMessageBody();
        $I->assertNotEmpty(preg_match_all('/<a href="([^"]+)".+[^>]+>/i', $email, $result));
        $inviteLink = htmlspecialchars_decode($result[1][2]);

        $I->assertEquals(1,
            preg_match('/invite/i', $inviteLink)
        );
    }

    public function sendConnectLetter(\TestSymfonyGuy $I)
    {
        $I->wantTo("check connect email and denied account access if one of useragents in unapproved");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $page = $I->grabService('router')->generate('aw_create_connection');
        $I->amOnPage($page . "?_switch_user=" . $this->user1->getLogin());

        $I->see('Add a new connection');
        $I->fillField('Email', $this->user2->getEmail());
        $I->click('Submit');
        $I->see('We sent an email to the address you specified');

        $I->seeEmailTo($this->user2->getEmail());

        $email = $I->grabLastMailMessageBody();
        preg_match_all('/<a href="([^"]+)".+[^>]+>/i', $email, $result);

        $I->assertEquals(1,
            preg_match('/user\/connections/i', $result[1][2])
        );

        $I->seeInDatabase('UserAgent', [
            'AgentId' => $this->user1->getUserId(),
            'ClientId' => $this->user2->getUserId(),
            'IsApproved' => 0,
        ]);

        $I->seeInDatabase('UserAgent', [
            'AgentId' => $this->user2->getUserId(),
            'ClientId' => $this->user1->getUserId(),
            'IsApproved' => 1,
        ]);

        $userAgentId = $I->grabFromDatabase('UserAgent', 'UserAgentId', [
            'AgentId' => $this->user2->getUserId(),
            'ClientId' => $this->user1->getUserId(),
            'IsApproved' => 1,
        ]);

        $accountId = $I->createAwAccount($this->user1->getUserId(), "aeroflot", "balance.random", "pass1", ["Disabled" => 0]);
        $I->haveInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $userAgentId]);

        $page = $I->grabService('router')->generate('aw_account_edit', ['accountId' => $accountId]);

        $I->amOnPage('/' . "?_switch_user=_exit");
        $I->amOnPage($page . "?_switch_user=" . $this->user2->getLogin());
        $I->dontSee('Aeroflot Bonus');

        $connectionsPage = $I->grabService('router')->generate('aw_user_connections');
        $I->amOnPage($connectionsPage);
        $I->see('My connections');
        $I->see('Approve', 'a');

        $I->executeQuery("update UserAgent set IsApproved = 1 where AgentId = " . $this->user1->getUserId() . " and ClientId = " . $this->user2->getUserId());

        $I->amOnPage($page . "?_switch_user=" . $this->user2->getLogin());
        $I->see('Aeroflot Bonus');
    }

    public function denyTimelineAccessNoConnection(\TestSymfonyGuy $I)
    {
        $I->wantTo("check denied access to timeline if connection doesn't exist");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $I->sendGET('/m/api/data' . "?_switch_user=" . $this->user1->getLogin());

        $I->haveInDatabase("Restaurant", [
            "UserID" => $this->user1->getUserId(),
            "ConfNo" => 'Rest1',
            "Address" => "London",
            "Name" => "Birthday",
            "StartDate" => date("Y-m-d H:i:s", strtotime("tomorrow")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR * 5),
        ]);

        $I->sendGET("/timeline/data");
        $I->seeResponseIsJson();
        $timeline = $I->grabDataFromJsonResponse();
        $I->assertEquals(2, count($timeline['segments']));

        $I->amOnPage("/?_switch_user=_exit");
        $I->amOnPage('/m/api/data' . "?_switch_user=" . $this->user2->getLogin());

        $I->sendGET("/timeline/data");
        $I->seeResponseIsJson();
        $timeline = $I->grabDataFromJsonResponse();
        $I->assertEquals(0, count($timeline['segments']));
    }

    public function denyTimelineAccessUnapprovedConnection(\TestSymfonyGuy $I)
    {
        $I->wantTo("check denied access to user timeline if connection is unapproved");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $I->haveInDatabase("Restaurant", [
            "UserID" => $this->user1->getUserId(),
            "ConfNo" => 'Rest1',
            "Address" => "London",
            "Name" => "Birthday",
            "StartDate" => date("Y-m-d H:i:s", strtotime("tomorrow")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR * 5),
        ]);

        $connectionId = $I->createConnection($this->user1->getUserId(), $this->user2->getUserId(), false, true, ['AccessLevel' => ACCESS_READ_ALL]);
        $I->shareAwTimeline($this->user1->getUserId(), null, $this->user2->getUserId());

        $I->sendGET('/m/api/data' . "?_switch_user=" . $this->user2->getLogin());
        $I->sendGET('/timeline/data/' . $connectionId . '?showDeleted=0');
        $I->seeResponseCodeIs(403);
    }

    public function allowTimelineAccessApprovedConnection(\TestSymfonyGuy $I)
    {
        $I->wantTo("check allowed access to user timeline if connection is approved");
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $I->haveInDatabase("Restaurant", [
            "UserID" => $this->user1->getUserId(),
            "ConfNo" => 'Rest1',
            "Address" => "London",
            "Name" => "Bitghday",
            "StartDate" => date("Y-m-d H:i:s", strtotime("tomorrow")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR * 5),
        ]);

        $connectionId = $I->createConnection($this->user1->getUserId(), $this->user2->getUserId(), true, true, ['AccessLevel' => ACCESS_READ_ALL]);
        $I->shareAwTimeline($this->user1->getUserId(), null, $this->user2->getUserId());

        $I->sendGET('/m/api/data' . "?_switch_user=" . $this->user2->getLogin());
        $I->sendGET('/timeline/data/' . $connectionId . '?showDeleted=0');
        $I->seeResponseIsJson();
        $timeline = $I->grabDataFromJsonResponse();
        $I->assertEquals(2, count($timeline['segments']));
    }

    public function testInviteBusinessFamilyMember(\TestSymfonyGuy $I)
    {
        /** @var Router $router */
        $router = $I->grabService('router');
        $this->createBusinessWithFamilyMember($I);
        $accountId = $I->createAwAccount($this->business->getUserid(), 'testprovider', 'test', 'test', ['UserAgentID' => $this->familyMember->getUseragentId()]);

        // send invite
        $I->amOnSubdomain("business");
        $I->amOnPage($router->generate('aw_business_members') . "?_switch_user=" . $this->businessAdmin->getLogin());
        $I->seeResponseCodeIs(200);

        $I->saveCsrfToken();

        $inviteRoute = $router->generate('aw_invite_family', ['userAgentId' => $this->familyMember->getUseragentId()]);

        $I->sendAjaxPostRequest($inviteRoute, ['Email' => $this->familyMember->getEmail()]);
        $I->seeResponseContainsJson(['success' => true]);
        $recordLocator = $I->grabRandomString(20);
        $this->addTestItineraries($I, $recordLocator, $this->business, $this->familyMember);
        $this->useFamilyMemberInvite($I, $this->business, $this->familyMember);
        $I->seeInDatabase('UserAgent', ['AgentId' => $this->invitedUser->getUserid(), 'ClientId' => $this->business->getUserid(), 'AccessLevel' => ACCESS_NONE]);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($accountId);
        $I->assertSame($this->invitedUser->getUserid(), $account->getUserid()->getUserid());
        $I->assertNull($account->getUseragentid());
        $this->checkTestItineraries($I, $recordLocator, $this->invitedUser);
    }

    public function testInviteFamilyMember(\TestSymfonyGuy $I)
    {
        /** @var Router $router */
        $router = $I->grabService('router');
        $userId = $I->createAwUser();
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($userId);
        $familyMemberEmail = $I->grabRandomString(10) . '@gmail.com';
        $familyMemberId = $I->createFamilyMember($userId, 'Samantha', 'Fox', null, $familyMemberEmail);
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($familyMemberId);
        $accountId = $I->createAwAccount($userId, 'testprovider', 'test', 'test', ['UserAgentID' => $familyMemberId]);

        $I->amOnPage("/?_switch_user=" . $user->getLogin());
        $I->saveCsrfToken();
        // send invite
        $inviteRoute = $router->generate('aw_invite_family', ['userAgentId' => $familyMemberId]);

        $I->sendAjaxPostRequest($inviteRoute, ['Email' => $familyMemberEmail]);
        $I->seeResponseContainsJson(['success' => true]);
        $recordLocator = $I->grabRandomString(20);
        $this->addTestItineraries($I, $recordLocator, $user, $familyMember);
        $this->useFamilyMemberInvite($I, $user, $familyMember);
        $I->seeInDatabase('UserAgent', ['AgentId' => $this->invitedUser->getUserid(), 'ClientId' => $user->getUserid(), 'AccessLevel' => ACCESS_WRITE]);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($accountId);
        $I->assertSame($this->invitedUser->getUserid(), $account->getUserid()->getUserid());
        $I->assertNull($account->getUseragentid());
        $this->checkTestItineraries($I, $recordLocator, $this->invitedUser);
    }

    public function testInviteBusinessFamilyMemberByEmail(\TestSymfonyGuy $I)
    {
        /** @var Router $router */
        $router = $I->grabService('router');
        $this->createBusinessWithFamilyMember($I);
        $accountId = $I->createAwAccount($this->business->getUserid(), 'testprovider', 'test', 'test', ['UserAgentID' => $this->familyMember->getUseragentId()]);

        $I->amOnSubdomain("business");
        $I->amOnPage($router->generate('aw_business_create_connection') . "?_switch_user=" . $this->businessAdmin->getLogin());
        $I->seeResponseCodeIs(200);

        $I->seeElement('#form_email_addresses');
        $I->fillField('#form_email_addresses', $this->familyMember->getEmail());
        $I->submitForm('//form', []);

        $I->see('Thank you. We sent invitation emails to the addresses you specified.');
        $I->seeEmailTo($this->familyMember->getEmail());

        $recordLocator = $I->grabRandomString(20);
        $this->addTestItineraries($I, $recordLocator, $this->business, $this->familyMember);
        $this->useFamilyMemberInvite($I, $this->business, $this->familyMember);
        $I->seeInDatabase('UserAgent', ['AgentId' => $this->invitedUser->getUserid(), 'ClientId' => $this->business->getUserid(), 'AccessLevel' => ACCESS_NONE]);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($accountId);
        $I->assertSame($this->invitedUser->getUserid(), $account->getUserid()->getUserid());
        $I->assertNull($account->getUseragentid());
        $this->checkTestItineraries($I, $recordLocator, $this->invitedUser);
    }

    public function testInviteUseByExistingUser(\TestSymfonyGuy $I)
    {
        /** @var Router $router */
        $router = $I->grabService('router');
        $this->createBusinessWithFamilyMember($I);

        // send invite
        $I->amOnSubdomain("business");
        $I->amOnPage($router->generate('aw_business_members') . "?_switch_user=" . $this->businessAdmin->getLogin());
        $I->seeResponseCodeIs(200);

        $I->saveCsrfToken();

        $inviteRoute = $router->generate('aw_invite_family', ['userAgentId' => $this->familyMember->getUseragentId()]);

        $I->sendAjaxPostRequest($inviteRoute, ['Email' => $this->familyMember->getEmail()]);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeEmailTo($this->familyMember->getEmail());

        $I->amOnPage('/' . "?_switch_user=_exit");
        $I->amOnSubdomain('');

        $invite = $I->grabService('doctrine')->getRepository(Invites::class)->findOneBy([
            'inviterid' => $this->business->getUserid(),
            'familyMember' => $this->familyMember->getUseragentId(),
        ]);

        // new user
        $existingUserLogin = 'exist-' . $I->grabRandomString(5);
        $existingUserName = 'name-' . $I->grabRandomString(5);
        $I->createAwUser($existingUserLogin, null, [
            'FirstName' => $existingUserName,
        ], true);

        $useInviteRoute = $router->generate('aw_invite_confirm', ['shareCode' => $invite->getCode()]);
        $I->amOnPage($useInviteRoute . "?_switch_user={$existingUserLogin}");

        $I->see($existingUserName);

        $I->submitForm('//form', []);

        $I->see($this->business->getCompany());
    }

    public function ignoreInviteFromBusinessByFamilyMember(\TestSymfonyGuy $I)
    {
        $this->createBusinessWithFamilyMember($I);
        $accountId = $I->createAwAccount($this->business->getUserid(), 'testprovider', 'test', 'test', ['UserAgentID' => $this->familyMember->getUseragentId()]);
        $recordLocator = $I->grabRandomString(6);
        $this->addTestItineraries($I, $recordLocator, $this->business, $this->familyMember);

        $I->amOnBusiness();
        $I->amOnPage("/members?_switch_user={$this->businessAdmin->getLogin()}");
        $I->saveCsrfToken();
        $I->sendPOST("/members/invite/{$this->familyMember->getUseragentid()}", ['Email' => $this->familyMember->getEmail()]);
        $I->seeResponseContainsJson(['success' => true]);

        $newUserId = $this->signUp($I, $this->familyMember->getEmail(), $this->familyMember->getFirstname(), $this->familyMember->getLastname());
        $I->cantSeeInDatabase('UserAgent', [
            'agentId' => $this->business->getUserid(),
            'clientId' => $newUserId,
        ]);
        $I->cantSeeInDatabase('UserAgent', [
            'agentId' => $newUserId,
            'clientId' => $this->business->getUserid(),
        ]);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($accountId);
        $I->assertSame($this->business->getUserid(), $account->getUserid()->getUserid());
        $I->assertSame($this->familyMember->getUseragentid(), $account->getUseragentid()->getUseragentid());
        $this->checkTestItineraries($I, $recordLocator, $this->business, $this->familyMember);
    }

    public function ignoreInviteFromBusinessByEmail(\TestSymfonyGuy $I)
    {
        $this->createBusiness($I);
        $newUserEmail = $I->grabRandomString(10) . '@gmail.com';

        $I->amOnBusiness();
        $I->amOnPage("/agents/add-connection?_switch_user={$this->businessAdmin->getLogin()}");
        $I->seeElement('#form_email_addresses');
        $I->fillField('#form_email_addresses', $newUserEmail);
        $I->submitForm('//form', []);

        $I->seeEmailTo($newUserEmail);

        $newUserId = $this->signUp($I, $newUserEmail, 'Samantha', 'Fox');
        $I->cantSeeInDatabase('UserAgent', [
            'agentId' => $this->business->getUserid(),
            'clientId' => $newUserId,
        ]);
        $I->cantSeeInDatabase('UserAgent', [
            'agentId' => $newUserId,
            'clientId' => $this->business->getUserid(),
        ]);
    }

    public function ignoreInviteFromUserByFamilyMember(\TestSymfonyGuy $I)
    {
        /** @var UsrRepository $userRepository */
        $userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        /** @var UseragentRepository $userAgentRepository */
        $userAgentRepository = $I->grabService('doctrine')->getRepository(Useragent::class);
        /** @var Usr $host */
        $host = $userRepository->find($I->createAwUser());
        $email = $I->grabRandomString(10) . '@gmail.com';
        $familyMemberId = $I->createFamilyMember($host->getUserid(), 'Samantha', 'Fox', null, $email);
        /** @var Useragent $familyMember */
        $familyMember = $userAgentRepository->find($familyMemberId);
        $accountId = $I->createAwAccount($host->getUserid(), 'testprovider', 'test', 'test', ['UserAgentID' => $familyMemberId]);
        $recordLocator = $I->grabRandomString(6);
        $this->addTestItineraries($I, $recordLocator, $host, $familyMember);

        $I->amOnPage("/user/connections?_switch_user={$host->getLogin()}");
        $I->saveCsrfToken();
        $I->sendPOST("/members/invite/{$familyMember->getUseragentid()}", ['Email' => $familyMember->getEmail()]);
        $I->seeResponseContainsJson(['success' => true]);

        $I->seeEmailTo($familyMember->getEmail());

        $newUserId = $this->signUp($I, $familyMember->getEmail(), $familyMember->getFirstname(), $familyMember->getLastname());
        $I->cantSeeInDatabase('UserAgent', [
            'agentId' => $host->getUserid(),
            'clientId' => $newUserId,
        ]);
        $I->cantSeeInDatabase('UserAgent', [
            'agentId' => $newUserId,
            'clientId' => $host->getUserid(),
        ]);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($accountId);
        $I->assertSame($host->getUserid(), $account->getUserid()->getUserid());
        $I->assertSame($familyMember->getUseragentid(), $account->getUseragentid()->getUseragentid());
        $this->checkTestItineraries($I, $recordLocator, $host, $familyMember);
    }

    public function ignoreInviteFromUserByEmail(\TestSymfonyGuy $I)
    {
        /** @var UsrRepository $userRepository */
        $userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        /** @var UseragentRepository $userAgentRepository */
        $userAgentRepository = $I->grabService('doctrine')->getRepository(Useragent::class);
        /** @var Usr $host */
        $host = $userRepository->find($I->createAwUser());
        $newUserEmail = $I->grabRandomString(10) . '@gmail.com';

        $I->amOnPage("/agents/add-connection?_switch_user={$host->getLogin()}");
        $I->seeElement('#form_email');
        $I->fillField('#form_email', $newUserEmail);
        $I->submitForm('//form', []);

        $I->seeEmailTo($newUserEmail);

        $newUserId = $this->signUp($I, $newUserEmail, 'Samantha', 'Fox');
        $I->cantSeeInDatabase('UserAgent', [
            'agentId' => $host->getUserid(),
            'clientId' => $newUserId,
        ]);
        $I->cantSeeInDatabase('UserAgent', [
            'agentId' => $newUserId,
            'clientId' => $host->getUserid(),
        ]);
    }

    public function personalFamilyMemberInviteWithAccount(\TestSymfonyGuy $I)
    {
        $this->createTwoUsers($I);
        $I->amOnPage('/?_switch_user=' . $this->user1->getLogin());
        $I->seeResponseCodeIs(200);
        $I->saveCsrfToken();

        $familyMemberId = $I->createFamilyMember($this->user1->getUserid(), 'samantha', 'fox', 'midname', $this->user2->getEmail());
        $I->seeInDatabase('UserAgent', ['AgentID' => $this->user1->getUserid(), 'Email' => $this->user2->getEmail()]);

        /** @var Router $router */
        $router = $I->grabService('router');
        $accountId = $I->createAwAccount($this->user1->getUserid(), 155, 'test', 'test', ['UserAgentID' => $familyMemberId]);
        $I->executeQuery('UPDATE Account SET UserAgentID = ' . $familyMemberId . ' WHERE AccountID = ' . $accountId);

        $I->sendAjaxPostRequest($router->generate('aw_invite_family', ['Email' => $this->user2->getEmail(), 'userAgentId' => $familyMemberId]));
        $I->seeResponseContainsJson(['success' => true]);
        // $I->seeEmailTo($this->familyMemberEmail);
        $I->seeInDatabase('InviteCode', ['UserID' => $this->user1->getUserid(), 'Email' => $this->user2->getEmail()]);

        $I->amOnPage('/?_switch_user=_exit');
        $I->amOnPage('/?_switch_user=' . $this->user2->getLogin());
        $I->seeResponseCodeIs(200);
        $I->saveCsrfToken();
        $inviteCode = $I->grabFromDatabase('InviteCode', 'Code', ['UserID' => $this->user1->getUserid(), 'Email' => $this->user2->getEmail()]);
        $I->sendPOST($router->generate('aw_invite_confirm', ['shareCode' => $inviteCode]));

        $I->amOnPage($router->generate('aw_user_connections'));
        $I->see($this->user1->getEmail());

        $I->amOnPage($router->generate('aw_account_list'));
        $I->assertStringContainsString('S7 Priority', $I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata'));

        $I->amOnPage('/?_switch_user=_exit');
        $I->amOnPage('/?_switch_user=' . $this->user1->getLogin());
        $I->seeResponseCodeIs(200);
        $I->saveCsrfToken();
        $I->amOnPage($router->generate('aw_account_edit', ['accountId' => $accountId]));
        $I->seeResponseCodeIs(403);
    }

    public function inviteLinkByRegisteredUser(\TestSymfonyGuy $I)
    {
        $this->createTwoUsers($I);
        $I->resetLockout('forgot');

        $I->amOnRoute('aw_create_connection', [
            '_switch_user' => $this->user1->getLogin(),
        ]);
        $email = sprintf('invite-%s@invitemail.com', $I->grabRandomString(5));

        $I->see('Add a new connection');
        $I->fillField('Email', $email);
        $I->click('Submit');
        $I->see('We sent an email to the address you specified');

        $code = $I->grabFromDatabase('Invites', 'Code', [
            'InviterID' => $this->user1->getId(),
            'InviteeID' => null,
            'UserAgentID' => null,
            'Approved' => 0,
        ]);
        $I->seeInDatabase('InviteCode', [
            'UserID' => $this->user1->getId(),
            'Code' => $code,
        ]);

        $I->amOnRoute('aw_invite_confirm', [
            'shareCode' => $code,
            '_switch_user' => $this->user2->getLogin(),
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Connect with');
        $I->click('Connect with');
        $I->amOnRoute('aw_user_connections', [
            '_switch_user' => $this->user1->getLogin(),
        ]);
        $I->seeNumberOfElements(['css' => 'table.manage-users tbody tr'], 1);
        $I->dontSeeInDatabase('Invites', [
            'InviterID' => $this->user1->getId(),
            'Code' => $code,
        ]);
        $I->dontSeeInDatabase('InviteCode', [
            'UserID' => $this->user1->getId(),
            'Code' => $code,
        ]);
    }

    private function createTwoUsers(\TestSymfonyGuy $I)
    {
        $random1 = $I->grabRandomString(5);
        $random2 = $I->grabRandomString(5);
        $user1Id = $I->createAwUser(
            "user-1-$random1",
            "testpass-$random1",
            [
                'Email' => "user1-$random1@gmail.com",
            ]
        );
        $user2Id = $I->createAwUser(
            "user-2-$random2",
            $password = "testpass-$random2",
            [
                'Email' => "user2-$random2@gmail.com",
            ]
        );
        /** @var UsrRepository $userRepository */
        $userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        $this->user1 = $userRepository->find($user1Id);
        $this->user2 = $userRepository->find($user2Id);
    }

    private function createBusiness(\TestSymfonyGuy $I)
    {
        $userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        $businessName = $I->grabRandomString(10);
        $this->business = $userRepository->find($I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
            'Company' => $businessName,
        ], true));
        $this->businessAdmin = $userRepository->find($I->createAwUser(null, null, [], true));
        $I->connectUserWithBusiness($this->businessAdmin->getUserid(), $this->business->getUserid(), ACCESS_ADMIN);
    }

    private function createBusinessWithFamilyMember(\TestSymfonyGuy $I)
    {
        $this->createBusiness($I);
        $familyMemberEmail = $I->grabRandomString(10) . '@gmail.com';
        $familyMemberId = $I->createFamilyMember($this->business->getUserid(), 'Samantha', 'Fox', null, $familyMemberEmail);
        $this->familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($familyMemberId);
    }

    private function useFamilyMemberInvite(\TestSymfonyGuy $I, Usr $inviter, Useragent $invitee)
    {
        /** @var Router $router */
        $router = $I->grabService('router');
        $users = $I->grabService('doctrine')->getRepository(Usr::class);
        $I->seeEmailTo($invitee->getEmail());
        $I->seeInDatabase('UserAgent', ['UserAgentID' => $invitee->getUseragentID(), 'Email' => $invitee->getEmail()]);
        $I->seeInDatabase('Invites', ['InviterID' => $inviter->getUserid(), 'UserAgentID' => $invitee->getUseragentID()]);

        /** @var Invites $invite */
        $invite = $I->grabService('doctrine')->getRepository(Invites::class)->findOneBy([
            'inviterid' => $inviter->getUserid(),
            'familyMember' => $invitee->getUseragentID(),
        ]);
        $I->seeInDatabase('InviteCode', ['Code' => $invite->getCode()]);

        $I->amOnPage('/' . "?_switch_user=_exit");
        $I->amOnSubdomain('');

        // use invite
        $useInviteRoute = $router->generate('aw_invite_confirm', ['shareCode' => $invite->getCode()]);
        $I->amOnPage($useInviteRoute);
        $I->see('Samantha Fox');
        $I->submitForm('//form', []);

        // registration
        $I->see($I->grabService('translator')->trans('meta.title.register'));
        $I->seeInSource('Samantha');
        $I->seeInSource('Fox');
        $I->seeInSource(str_replace('@', '&#x40;', $invitee->getEmail()));

        $I->saveCsrfToken();

        $I->sendPOST($router->generate('aw_users_register'), [
            'coupon' => null,
            'user' => [
                'pass' => $I->grabRandomString(10),
                'firstname' => $I->grabRandomString(5),
                'lastname' => $I->grabRandomString(5),
                'email' => $invitee->getEmail(),
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);

        $I->amOnPage(
            $router->generate('aw_profile_overview')
        );

        $I->see($invitee->getEmail());

        $I->amOnPage(
            $router->generate('aw_user_connections')
        );

        $I->see($inviter->getFullName());

        $this->invitedUser = $users->findOneBy(['email' => $invitee->getEmail()]);
        /** @var Useragent $userAgent */
        $userAgent = $I->grabService('doctrine')->getRepository(Useragent::class)->findOneBy([
            'agentid' => $inviter,
            'clientid' => $this->invitedUser,
        ]);

        $I->amOnPage(
            $router->generate('aw_user_connection_edit', ['userAgentId' => $userAgent->getUseragentid()])
        );

        $I->seeCheckboxIsChecked('#connection_edit_accesslevel_3');
        $I->seeCheckboxIsChecked('#connection_edit_tripAccessLevel_1');
    }

    private function addTestItineraries(\TestSymfonyGuy $I, string $recordLocator, Usr $user, ?Useragent $familyMember = null)
    {
        $I->haveInDatabase('Trip', [
            'UserID' => $user->getUserid(),
            'UserAgentID' => $familyMember ? $familyMember->getUseragentid() : null,
            'RecordLocator' => $recordLocator,
        ]);
        $tripId = $I->grabFromDatabase('Trip', 'TripID', ['RecordLocator' => $recordLocator]);
        $I->createTripSegment([
            'TripID' => $tripId,
            'DepDate' => date("Y-m-d H:i:s"),
            'ScheduledDepDate' => date("Y-m-d H:i:s"),
            'ArrDate' => date("Y-m-d H:i:s"),
            'ScheduledArrDate' => date("Y-m-d H:i:s"),
            'DepName' => 'LAX',
            'ArrName' => 'LAX',
            'MarketingAirlineConfirmationNumber' => $recordLocator,
        ]);
        $I->haveInDatabase('Reservation', [
            'UserID' => $user->getUserid(),
            'UserAgentID' => $familyMember ? $familyMember->getUseragentid() : null,
            'ConfirmationNumber' => $recordLocator,
            'HotelName' => 'Some Hotel Name',
            'CheckInDate' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'CheckOutDate' => date('Y-m-d, H:i:s', strtotime('+2 days')),
            'Rooms' => "a:0:{}",
        ]);
        $I->haveInDatabase('Rental', [
            'UserID' => $user->getUserid(),
            'UserAgentID' => $familyMember ? $familyMember->getUseragentid() : null,
            'Number' => $recordLocator,
            'PickupLocation' => 'some pick-up location',
            'DropoffLocation' => 'some drop-off location',
            'PickupDatetime' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'DropoffDatetime' => date('Y-m-d, H:i:s', strtotime('+2 days')),
        ]);
        $I->haveInDatabase('Restaurant', [
            'UserID' => $user->getUserid(),
            'UserAgentID' => $familyMember ? $familyMember->getUseragentid() : null,
            'ConfNo' => $recordLocator,
            'Name' => 'Restaurant Name',
            'EventType' => Restaurant::EVENT_CONFERENCE,
            'StartDate' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);
    }

    private function checkTestItineraries(\TestSymfonyGuy $I, string $recordLocator, Usr $user, ?Useragent $familyMember = null)
    {
        $I->seeInDatabase('Trip', [
            'UserID' => $user->getUserid(),
            'UserAgentID' => $familyMember ? $familyMember->getUseragentid() : null,
            'RecordLocator' => $recordLocator,
        ]);
        $I->seeInDatabase('Reservation', [
            'UserID' => $user->getUserid(),
            'UserAgentID' => $familyMember ? $familyMember->getUseragentid() : null,
            'ConfirmationNumber' => $recordLocator,
        ]);
        $I->seeInDatabase('Rental', [
            'UserID' => $user->getUserid(),
            'UserAgentID' => $familyMember ? $familyMember->getUseragentid() : null,
            'Number' => $recordLocator,
        ]);
        $I->seeInDatabase('Restaurant', [
            'UserID' => $user->getUserid(),
            'UserAgentID' => $familyMember ? $familyMember->getUseragentid() : null,
            'ConfNo' => $recordLocator,
        ]);
    }

    private function signUp(\TestSymfonyGuy $I, string $email, string $firstName, string $lastName)
    {
        $I->amOnPage('/?_switch_user=_exit');
        $I->amOnSubdomain(null);
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
        $I->saveCsrfToken();
        $I->sendPOST('/user/register', [
            'coupon' => null,
            'user' => [
                'pass' => $I->grabRandomString(10),
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $email,
            ],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeInDatabase('Usr', ['Email' => $email]);

        return $I->grabFromDatabase('Usr', 'UserID', ['Email' => $email]);
    }
}
