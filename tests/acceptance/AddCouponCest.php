<?php

use Codeception\Scenario;

class AddCouponCest
{
    public const FILL_SELECTORS = [
        'inputCompany' => '//input[@name="providercoupon[programname]"]',
        'selectCategory' => '//select[@name="providercoupon[kind]"]',
        'inputType' => '//input[@name="providercoupon[typeName]"]',
        'inputCard' => '//input[@name="providercoupon[cardnumber]"]',
        'inputPin' => '//input[@name="providercoupon[pin]"]',
        'inputValue' => '//input[@name="providercoupon[value]"]',
        'inputNotExpire' => '//input[@name="providercoupon[donttrackexpiration]"]',
        'textareaNote' => '//textarea[@name="providercoupon[description]"]',
    ];
    private $uniqId;

    public function login(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo('register -> fill -> check -> remove');
        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->register();

        $I->amOnPage($I->grabService('router')->generate('aw_select_provider'));
        $I->click('//a[@href="#/coupon"]');

        $this->uniqId = uniqid();
        $this->fillCoupon($I, $scenario);
    }

    private function fillCoupon(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo('Add a coupon');
        $I->fillField(self::FILL_SELECTORS['inputCompany'], 'company-' . $this->uniqId);
        $I->selectOption(self::FILL_SELECTORS['selectCategory'], array_rand(\AwardWallet\MainBundle\Entity\Provider::getKinds()));
        $I->fillField(self::FILL_SELECTORS['inputType'], 'certificate-' . $this->uniqId);
        $I->fillField(self::FILL_SELECTORS['inputCard'], 'card-' . $this->uniqId);
        $I->fillField(self::FILL_SELECTORS['inputPin'], mt_rand(1000, 9999));
        $I->fillField(self::FILL_SELECTORS['inputValue'], $this->uniqId);
        $I->checkOption(self::FILL_SELECTORS['inputNotExpire']);
        $I->fillField(self::FILL_SELECTORS['textareaNote'], 'note-' . $this->uniqId);

        $I->click(['xpath' => '//form[@action="/coupon/add"]//button[@type="submit"]']);
        $this->checkAddition($I, $scenario);
    }

    private function checkAddition(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo('Check coupon');
        $I->amOnPage('/account/list');
        $I->waitForElement('//div[contains(@class, "account-row")]');
        $I->see($this->uniqId, '//div[contains(@class, "account-row")]//p[contains(@class, "balanceRaw")]');
        $this->removeCoupon($I, $scenario);
    }

    private function removeCoupon($I, Scenario $scenario)
    {
        $id = $I->grabAttributeFrom('//p[contains(text(), "company-' . $this->uniqId . '")]//ancestor::*[@id][1]', 'id');

        if (!empty($id)) {
            $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
            $em = $I->grabService('doctrine')->getManager();
            $couponRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class);
            $coupon = $couponRep->find($id);
            $em->remove($coupon);
            $em->flush();
        }

        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->deleteIfExist(\CommonUser::$user_email);
    }
}
