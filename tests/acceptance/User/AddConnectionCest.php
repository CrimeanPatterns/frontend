<?php

use Codeception\Scenario;

class AddConnectionCest
{
    protected $uniq_email;
    protected $exist_email;

    public function _before(WebGuy $I)
    {
        $this->uniq_email = 'xxx3xxx@awardwallet.com';
        $this->exist_email = \CommonUser::$admin_email;
    }

    public function _addFamilyMember(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("add family member");
        $I->amOnPage($I->grabService('router')->generate(AddFamilyMemberPage::$route));
        $I->fillField(AddFamilyMemberPage::$selector_fname, 'Test');
        $I->fillField(AddFamilyMemberPage::$selector_lname, 'Testovich');
        $I->click(AddFamilyMemberPage::$selector_invite);
        $I->click(AddFamilyMemberPage::$selector_button);
        $I->waitForText('Email is required to send invitation');
        $I->fillField(AddFamilyMemberPage::$selector_email, 'Test-Testovich@awardwallet.com');
        $I->click(AddFamilyMemberPage::$selector_button);
        $I->waitForElementVisible(AddAccountPage::$selector_searchProviderInput);
        $I->waitForSubject("Invitation to claim ownership", 10, '-10 seconds');
    }

    public function addUsers(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("register new user");
        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->register();
        $this->_addFamilyMember($I, $scenario);
        $userSteps->delete(\CommonUser::$user_password);
    }

    public function addConnection(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("register new user");
        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->register();

        $I->wantTo("add yourself");
        $I->amOnPage($I->grabService('router')->generate(AddConnectionPage::$route));
        $I->see('new connection');
        $I->fillField(AddConnectionPage::$selector_email, \CommonUser::$user_email);
        $I->click(AddConnectionPage::$selector_submit_email_button);
        $I->waitForText('does not appear to be registered on AwardWallet');
        $I->click(AddConnectionPage::$selector_button_search_again);

        $I->wantTo("add unregistered user");
        $I->fillField(AddConnectionPage::$selector_email, $this->uniq_email);
        $I->click(AddConnectionPage::$selector_submit_email_button);
        $I->waitForText('does not appear to be registered on AwardWallet');
        $I->click(AddConnectionPage::$selector_button_invite);
        $I->waitForElementVisible(AddConnectionPage::$selector_message_success_invite);
        $I->waitForSubject("wants to connect with you", 10, '-10 seconds');
        $message = $I->grabLastMailMessageBody();
        $I->assertEquals(1, preg_match('/' . preg_quote(\CommonUser::$user_email) . '/', $message));

        $I->wantTo("add registered user");
        $I->amOnPage($I->grabService('router')->generate(AddConnectionPage::$route));
        $I->fillField(AddConnectionPage::$selector_email, $this->exist_email);
        $I->click(AddConnectionPage::$selector_submit_email_button);
        $I->waitForText('was found in our database');
        $I->click(AddConnectionPage::$selector_button_connect);
        $I->waitForElementVisible(AddConnectionPage::$selector_message_success_invite);
        $I->waitForSubject("chose to share travel plan or award balance information with you", 10, '-10 seconds');

        $userSteps->delete(\CommonUser::$user_password);
    }
}
