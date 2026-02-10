<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\FunctionalSymfony\Security\LoginTrait;

/**
 * @group frontend-functional
 */
class CustomCouponCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    private $router;
    private $user;

    public function _before(\TestSymfonyGuy $I): void
    {
        $this->router = $I->grabService('router');
        $userRepository = $I->grabService('doctrine')->getRepository(Usr::class);

        $userId = $I->createAwUser();
        $this->user = $userRepository->find($userId);
    }

    public function addVaccineCard(\TestSymfonyGuy $I): void
    {
        $I->amOnPage($this->router->generate('aw_document_add', [
            'type' => Providercoupon::KEY_TYPE_VACCINE_CARD,
            '_switch_user' => $this->user->getLogin(),
        ]));

        $disease = 'Covid19';
        $I->fillField('document_form[disease]', $disease);
        $I->fillField('document_form[firstDoseDate]', '2021-10-10');
        $I->fillField('document_form[firstDoseVaccine]', 'vaccineBrandName');

        $I->submitForm('form', []);
        $I->seeResponseCodeIs(200);

        $I->seeInDatabase('ProviderCoupon', [
            'UserID' => $this->user->getId(),
            'TypeID' => Providercoupon::TYPE_VACCINE_CARD,
        ]);

        $json = $I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata');
        $I->assertStringContainsString('"firstDoseVaccine":"vaccineBrandName"', $json);
        $I->assertStringContainsString('"DisplayName":"' . $disease . ' Vaccine Card"', $json);
    }

    public function addInsuranceCard(\TestSymfonyGuy $I): void
    {
        $I->amOnPage($this->router->generate('aw_document_add', [
            'type' => Providercoupon::KEY_TYPE_INSURANCE_CARD,
            '_switch_user' => $this->user->getLogin(),
        ]));

        $I->selectOption('document_form[insuranceType]', Providercoupon::INSURANCE_TYPE_LIST[1]);
        $I->fillField('document_form[insuranceCompany]', 'company-name');
        $I->fillField('document_form[nameOnCard]', 'card-name');
        $I->fillField('document_form[memberNumber]', 'member-number');

        $I->submitForm('form', []);
        $I->seeResponseCodeIs(200);

        $I->seeInDatabase('ProviderCoupon', [
            'UserID' => $this->user->getId(),
            'TypeID' => Providercoupon::TYPE_INSURANCE_CARD,
        ]);

        $json = $I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata');
        $I->assertStringContainsString('"insuranceCompany":"company-name"', $json);
        $I->assertStringContainsString('"nameOnCard":"card-name"', $json);
    }

    public function addVisa(\TestSymfonyGuy $I): void
    {
        $I->amOnPage($this->router->generate('aw_document_add', [
            'type' => Providercoupon::KEY_TYPE_VISA,
            '_switch_user' => $this->user->getLogin(),
        ]));

        $I->selectOption('select[id="document_form_countryVisa"]', 230);
        $I->fillField('document_form[numberEntries]', 123);
        $I->fillField('document_form[fullName]', 'visa-user-name');

        $I->submitForm('form', []);
        $I->seeResponseCodeIs(200);

        $I->seeInDatabase('ProviderCoupon', [
            'UserID' => $this->user->getId(),
            'TypeID' => Providercoupon::TYPE_VISA,
        ]);

        $json = $I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata');
        $I->assertStringContainsString('"DisplayName":"Visa for United States"', $json);
        $I->assertStringContainsString('"fullName":"visa-user-name"', $json);
    }

    public function addDriversLicense(\TestSymfonyGuy $I): void
    {
        $I->amOnPage($this->router->generate('aw_document_add', [
            'type' => Providercoupon::KEY_TYPE_DRIVERS_LICENSE,
            '_switch_user' => $this->user->getLogin(),
        ]));

        $I->selectOption('select[id="document_form_country"]', 230);
        $I->fillField('document_form[licenseNumber]', 12345);
        $I->fillField('document_form[fullName]', 'user-name');
        $I->fillField('document_form[expirationDate]', '2021-10-10');

        $I->submitForm('form', []);
        $I->seeResponseCodeIs(200);

        $I->seeInDatabase('ProviderCoupon', [
            'UserID' => $this->user->getId(),
            'TypeID' => Providercoupon::TYPE_DRIVERS_LICENSE,
        ]);

        $json = $I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata');
        $I->assertStringContainsString('"DisplayName":"United States Driver\'s License"', $json);
        $I->assertStringContainsString('"fullName":"user-name"', $json);
    }

    public function addPriorityPass(\TestSymfonyGuy $I): void
    {
        $I->amOnPage($this->router->generate('aw_document_add', [
            'type' => Providercoupon::KEY_TYPE_PRIORITY_PASS,
            '_switch_user' => $this->user->getLogin(),
        ]));

        $I->fillField('document_form[accountNumber]', 12345);
        $I->fillField('document_form[expirationDate]', '2021-10-10');
        $option = $I->grabTextFrom('select[id="document_form_creditCardId"] option:nth-child(1)');
        $I->selectOption('select[id="document_form_creditCardId"]', $option);

        $I->submitForm('form', []);
        $I->seeResponseCodeIs(200);

        $I->seeInDatabase('ProviderCoupon', [
            'UserID' => $this->user->getId(),
            'TypeID' => Providercoupon::TYPE_PRIORITY_PASS,
        ]);
    }
}
