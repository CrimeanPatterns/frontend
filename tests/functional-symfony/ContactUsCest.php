<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class ContactUsCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;

    /**
     * @var RouterInterface
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        parent::_after($I);
    }

    public function authorized(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_contactus_index') . "?_switch_user=" . $this->user->getUsername());
        $I->see("Please fill out the form to contact us");
        $I->selectOption("Request Type", "General");
        $I->fillField("Message", $text = "Test message Livingsocial");
        $I->click("Send");
        $I->seeEmailTo(
            $I->grabService('aw.email.mailer')->getEmail('support'),
            'request type: "General", #',
            "Livingsocial"
        );
        $I->seeInDatabase("ContactUs", ['UserID' => $this->user->getUserid(), 'Email' => $this->user->getEmail(), 'Message' => $text]);
    }

    public function unauthorized(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_contactus_index') . "?wcaptcha=1");
        $I->see("Please fill out the form to contact us");
        $I->selectOption("Request Type", "General");
        $I->fillField("Full Name", "Billy Villy");
        $I->fillField("Email", $email = "test@mail.com");
        $I->fillField("Phone", "123-456");
        $I->fillField("Message", $text = "Test message Livingsocial");
        $I->click("Send");
        $I->seeEmailTo(
            $I->grabService('aw.email.mailer')->getEmail('support'),
            'request type: "General", #',
            "Livingsocial"
        );
        $I->seeInDatabase("ContactUs", ['UserID' => null, 'Email' => $email, 'Message' => $text]);
    }
}
