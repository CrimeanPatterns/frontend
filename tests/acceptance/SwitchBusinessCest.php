<?php

use Codeception\Scenario;

/**
 * Class SwitchBusinessCest.
 *
 * @group convert-to-business
 */
class SwitchBusinessCest
{
    public function testSwitchToBusiness(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("test switch to business");
        $I->amOnPage("/");
        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->login(CommonUser::$booker_login, CommonUser::$booker_password);
        $I->click("//a[@title = 'Switch to Business interface']");
        $I->click("#menu-closer");
        $I->see("BookYourAward");
        $I->seeInCurrentUrl("/awardBooking/queue");
        $I->assertStringContainsString("business.", $I->executeInSelenium(function (WebDriver $webdriver) {
            return $webdriver->getCurrentURL();
        }));
        $I->see("Booking requests");
        $I->seeElement("#logoBOOK");
        $I->clearCookiesAfterTest(false);
    }

    /**
     * @depends testSwitchToBusiness
     */
    public function testSwitchToPersonal(WebGuy $I)
    {
        $I->click("//a[@title = 'Switch to Personal interface']");
        $I->see("Steve Belkin");
        $I->assertStringNotContainsString("business.", $I->executeInSelenium(function (WebDriver $webdriver) {
            return $webdriver->getCurrentURL();
        }));
        $I->seeInCurrentUrl("/account/list.php?UserAgentID=All");
        $I->dontSeeElement("#logoBOOK");
    }
}
