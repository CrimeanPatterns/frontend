<?php

use AwardWallet\MainBundle\Globals\StringUtils;

/**
 * @group frontend-functional
 */
class ConnectionCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $email;
    private $login;

    public function _before(TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, [], true, true);
        $this->email = $I->grabFromDatabase("Usr", "Email", ["UserID" => $userId]);
        $this->login = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);
    }

    public function checkConnectionWithYourself(TestSymfonyGuy $I)
    {
        $I->amOnPage("/agents/add-connection?_switch_user={$this->login}");
        $I->fillField("Email", $this->email);
        $I->click("Submit");
        $I->see("You are not able to connect with yourself");
    }

    public function checkConnectionWithYourselfAsBusiness(TestSymfonyGuy $I)
    {
        $business = $I->createBusinessUserWithBookerInfo();
        $bookerId = $I->createStaffUserForBusinessUser($business);
        $email = $I->grabFromDatabase("Usr", "Email", ["UserID" => $business]);
        $login = $I->grabFromDatabase("Usr", "Login", ["UserID" => $bookerId]);

        $I->amOnPage($this->getFullRoute($I, 'business_host', 'aw_create_connection', ['_switch_user' => $login]));
        $I->fillField("Email addresses", $email);
        $I->click("Submit");
        $I->see("You are not able to connect with yourself");
    }

    public function checkNotApprovedUserWithFamilyMember(TestSymfonyGuy $I)
    {
        $router = $I->grabService('router');
        $connectionManager = $I->grabService('aw.manager.connection_manager');
        $userRepository = $I->grabService('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        $oneUserId = $I->createAwUser(null, null, ['FirstName' => ($oneFirstname = StringUtils::getRandomCode(8)), 'LastName' => ($oneLastname = StringUtils::getRandomCode(8))]);
        $twoUserId = $I->createAwUser(null, null, ['FirstName' => ($twoFirstname = StringUtils::getRandomCode(8)), 'LastName' => ($twoLastname = StringUtils::getRandomCode(8))]);

        $inviterLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $oneUserId]);
        $invitedEmail = $I->grabFromDatabase('Usr', 'Email', ['UserID' => $twoUserId]);
        $invitedLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $twoUserId]);

        $connectionManager->connectUser($userRepository->find($twoUserId), $userRepository->find($oneUserId));
        $I->seeEmailTo($invitedEmail, 'Connection invite from ' . $oneFirstname . ' ' . $oneLastname);

        $I->amOnPage($router->generate('aw_add_agent', ['_switch_user' => $inviterLogin]));
        $I->fillField(['name' => 'desktop_add_agent[firstname]'], $familyMemberFirstname = StringUtils::getRandomCode(8));
        $I->fillField(['name' => 'desktop_add_agent[lastname]'], $familyMamberLastname = StringUtils::getRandomCode(8));
        $I->click('button[type="submit"]');

        $agentId = $I->grabFromDatabase('UserAgent', 'UserAgentID', ['ClientID' => null, 'AgentID' => $oneUserId, 'FirstName' => $familyMemberFirstname]);
        $I->assertNotNull($agentId);

        $invitedUserAgentId = $I->grabFromDatabase('UserAgent', 'UserAgentID', ['ClientID' => $oneUserId, 'AgentID' => $twoUserId]);
        $I->assertNotNull($invitedUserAgentId);

        $I->amOnPage($router->generate('aw_users_logout'));
        $I->amOnPage($router->generate('aw_timeline', ['_switch_user' => $invitedLogin]));
        $I->cantSee($familyMemberFirstname . ' ' . $familyMamberLastname);

        $I->updateInDatabase('UserAgent', ['IsApproved' => 1], ['AgentID' => $oneUserId, 'ClientID' => $twoUserId]);

        $I->amOnPage($router->generate('aw_timeline'));
        $I->cantSee($familyMemberFirstname . ' ' . $familyMamberLastname);

        $I->amOnPage($router->generate('aw_users_logout'));
        $I->amOnPage($router->generate('aw_user_connections', ['_switch_user' => $inviterLogin]));
        $I->amOnPage($router->generate('aw_user_connection_edit', ['userAgentId' => $invitedUserAgentId]));

        $I->checkOption('connection_edit[sharedTimelines][' . $agentId . ']');
        $I->click('button[type="submit"]');

        $I->amOnPage($router->generate('aw_timeline'));
        $I->canSee($familyMemberFirstname . ' ' . $familyMamberLastname);
    }

    private function getFullRoute(TestSymfonyGuy $I, $hostParam, $route, $routeParams = [])
    {
        $container = $I->grabService('service_container');

        return
            $container->getParameter('requires_channel') .
            '://' .
            $container->getParameter($hostParam) .
            $container->get('router')->generate($route, $routeParams);
    }
}
