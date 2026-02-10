<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class TextFieldsSanitizerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;

    /**
     * @var RouterInterface
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $I->amOnPage($this->router->generate('aw_coupon_add'));
        $I->see("Manually track travel voucher or gift card");
        $I->selectOption("Category", "Airlines");
        $I->selectOption("Type", "Certificate");
        $I->fillField("Cert / Card / Voucher #", "test");
        $I->fillField("PIN / Redemption Code", "1231444");
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        parent::_after($I);
    }

    /*
        public function TextTypeTest(\TestSymfonyGuy $I)
        {
            $I->fillField("Company", '"quotes" <tags>');
            $I->click('button[type=submit]');
            $I->see("Invalid symbols.");
        }

        public function TextareaTypeTest(\TestSymfonyGuy $I)
        {
            $I->fillField("Company", 'test');
            $I->fillField("Note", '<tags>');
            $I->click('button[type=submit]');
            $I->see("Invalid symbols.");
        }
    */
}
