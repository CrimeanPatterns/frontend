<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\SecureLink;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class UnsubscribeCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var SecureLink
     */
    private $secureLink;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->secureLink = $I->grabService(SecureLink::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = $this->secureLink = null;
        parent::_after($I);
    }

    /**
     * @group testclosure
     */
    public function invalidLink(\TestSymfonyGuy $I)
    {
        // Test invalid link
        global $closure;
        $closure = new \stdClass();
        $closure->attr = function () use ($I) {
            $I->see("text");
        };
        $I->amOnPage($this->router->generate('aw_profile_unsubscribe', ['email' => 'fake', 'code' => 'fake']));
        $I->see('Invalid link');
    }

    public function validLink(\TestSymfonyGuy $I)
    {
        // Test invalid link
        $I->amOnPage($this->secureLink->protectUnsubscribeUrl($this->user->getEmail(), ""));
        $I->see('Notifications for ' . $this->user->getEmail());
    }

    public function md5Hash(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_profile_unsubscribe', [
            'email' => $this->user->getEmail(),
            'code' => md5($this->user->getEmail() . SECRET_KEY_EMAIL_UNSUBSCRIBE_PERSONAL),
        ]));
        $I->see('Notifications for ' . $this->user->getEmail());
    }

    public function unregisteredEmail(\TestSymfonyGuy $I)
    {
        $randomEmail = 'random-email-' . $I->grabRandomString(5) . '@mail.com';
        $I->amOnPage($this->secureLink->protectUnsubscribeUrl($randomEmail, ""));
        $I->see('Add Email to Do Not Send');
        $I->seeInField(['name' => 'form[email]'], $randomEmail);
    }

    public function loginUser(\TestSymfonyGuy $I)
    {
        global $Connection;
        $Connection->OnSome = function () {
            echo "hello";
        };
        $I->sendGET($this->router->generate('awm_new_login_status') . '?_switch_user=' . $this->user->getLogin());
        $I->amOnPage($this->secureLink->protectUnsubscribeUrl($this->user->getEmail(), ""));
        $I->see('Notifications');
    }

    public function businessUnsubscribe(\TestSymfonyGuy $I)
    {
        $container = $I->grabService('service_container');
        $schemeAndHost = $container->getParameter('requires_channel') . "://" . $container->getParameter('business_host');
        $businessId = $I->createBusinessUserWithBookerInfo('testbusiness' . $I->grabRandomString(5), [
            'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
        ]);
        $userId = $I->createStaffUserForBusinessUser($businessId);
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);
        $I->amOnPage($this->secureLink->protectUnsubscribeUrl($user->getEmail(), $schemeAndHost, true));
        $I->see('Notifications for ' . $user->getFullName());
        $I->see("Mobile");
        $I->see("Desktop");
        $I->see("Email");
    }
}
