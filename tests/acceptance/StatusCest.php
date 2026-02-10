<?php

use Codeception\Scenario;

class StatusCest
{
    public const TEST_PROVIDER_ID = 26;
    public const TEST_PROVIDER_NAME = 'Mileage Plus';

    // const TEST_PROVIDER_NAME = 'Test Provider (Test)';

    public function start(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo('check status page');
        $this->deleteUser($I, $scenario);
        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->register();

        /** @var \Doctrine\ORM\EntityManager $em */
        // $em = $I->grabService('doctrine')->getManager();
        // $user = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy(['login' => \CommonUser::$user_username]);

        // for Test Provider
        // $em->getConnection()->executeQuery('INSERT INTO GroupUserLink (SiteGroupID, UserID) VALUES(3, ' . $user->getUserid() . ')');
        // $em->getConnection()->executeQuery('INSERT INTO GroupUserLink (SiteGroupID, UserID) VALUES(37, ' . $user->getUserid() . ')');

        $this->addProvider($I, $scenario);
    }

    private function addProvider(WebGuy $I, Scenario $scenario)
    {
        $I->amOnPage($I->grabService('router')->generate('aw_select_provider'));

        $I->waitForElement(AddAccountPage::$selector_searchResultsRows);
        $I->fillField(AddAccountPage::$selector_searchProviderInput, self::TEST_PROVIDER_NAME);
        $I->wait(1);
        $I->see(self::TEST_PROVIDER_NAME, AddAccountPage::$selector_searchResultsRows);
        $I->click(['xpath' => '//a//span[contains(text(), "' . self::TEST_PROVIDER_NAME . '")]/ancestor::a']);

        $I->waitForElement('//input[@id="account_notrelated"]');
        $I->fillField('//input[@id="account_login"]', 'test');
        $I->fillField('//input[@id="account_pass"]', 'test');
        // $I->selectOption('//select[@id="account_login"]', 'unknown.error');
        $I->checkOption('//input[@id="account_notrelated"]');
        $I->click(['xpath' => '//form[@id="account-form"]//button[@type="submit"]']);

        $this->checkingMarkConsidering($I, $scenario);
    }

    private function checkingMarkConsidering(WebGuy $I, Scenario $scenario)
    {
        $I->amOnPage($I->grabService('router')->generate('aw_status_index'));
        $type = 'consideringAdd';
        $firstVote = '//table[@id="t_' . $type . '"]//tr[1]//a[contains(@class, "js-vote")]';
        $I->waitForElement($firstVote);
        $I->click(['xpath' => '//a[@href="#' . $type . '"]']);
        $I->wait(2);
        $I->click(['xpath' => $firstVote]);
        $I->waitForElementVisible('//span[contains(text(), "Please Confirm")]');
        $I->click(['xpath' => '//div[contains(@class, "ui-dialog")]//span[contains(text(), "Yes")]/ancestor::button']);
        $I->waitForElement('//table[@id="t_' . $type . '"]//tr[1]//td[contains(@class, "btn")]/i');

        $this->checkingMarkWorking($I, $scenario);
    }

    private function checkingMarkWorking(WebGuy $I, Scenario $scenario)
    {
        $I->amOnPage($I->grabService('router')->generate('aw_status_index'));

        $type = 'working';
        $container = '//table[@id="t_' . $type . '"]';

        $I->waitForElement($container);
        $I->click('//a[@href="#' . $type . '"]');
        $I->wait(1);

        $I->click(['xpath' => $container . '//tr[1]//a[contains(@class, "js-vote")]']);
        $I->waitForElementVisible('//div[contains(@class, "ui-dialog-content")][contains(text(), "We did not detect")]', 25);
        $I->click(['xpath' => '//div[contains(@class, "ui-dialog-content")][contains(text(), "We did not detect")]/ancestor::div[contains(@class, "ui-dialog")]//button[contains(@class,"ui-dialog-titlebar-close")]']);
        $I->wait(1);

        // $vote = '//table[@id="t_working"]//strong[contains(text(), "United")]/following-sibling::*[contains(text(), "Mileage Plus")]/ancestor::tr//a[contains(@class, "js-vote")]';
        $vote = '//a[@href="#' . self::TEST_PROVIDER_ID . '"]';
        $errorText = 'test error message';

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $I->grabService('doctrine')->getManager();
        $user = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy(['login' => \CommonUser::$user_username]);
        $em->getConnection()->executeQuery("UPDATE Account SET ErrorMessage = '" . $errorText . "' WHERE UserID = " . $user->getUserid() . ' AND ProviderID = ' . self::TEST_PROVIDER_ID . ' LIMIT 1'); // emulate error

        $I->click($container . $vote);
        $I->waitForElementVisible('//textarea[@id="statusProviderComment"]', 15);

        $I->waitForElement('//td[contains(text(), "' . $errorText . '")]');
        $I->waitForElement('//textarea[@id="statusProviderComment"]');
        $I->fillField('//textarea[@id="statusProviderComment"]', "user comment\ntest message");
        $I->click(['xpath' => '//div[contains(@class, "ui-dialog")]//span[contains(text(), "Yes")]/ancestor::button']);
        $I->waitForElement('//table[@id="t_' . $type . '"]//strong[contains(text(), "United")]/following-sibling::*[contains(text(), "Mileage Plus")]/ancestor::tr/td[contains(@class, "btn")]/i');

        $this->deleteUser($I, $scenario);
    }

    private function deleteUser(WebGuy $I, Scenario $scenario)
    {
        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->deleteIfExist(\CommonUser::$user_email);
    }
}
