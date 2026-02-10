<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use Doctrine\ORM\EntityManagerInterface;
use Google\Authenticator\GoogleAuthenticator;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group xxx
 */
class TwoFactorAuthCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $user;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @group locks
     */
    public function setup(\TestSymfonyGuy $I)
    {
        $page = $this->router->generate('aw_profile_2factor');
        $I->amOnPage($page . "?_switch_user=" . $this->user->getLogin());

        $I->mockService(ReauthenticatorWrapper::class, $I->stubMake(ReauthenticatorWrapper::class, [
            'isReauthenticated' => true,
            'reset' => true,
        ]));
        $I->see("Two-factor authentication setup");
        $I->see("scan the following QR code");
        $I->fillField("Code", "12345");
        $I->click("Next");
        $I->see("Incorrect code");

        $auth = new GoogleAuthenticator();
        $secret = $I->grabValueFrom(["name" => "two_fact[secret]"]);
        $I->assertNotEmpty($secret);
        $code = $auth->getCode($secret);
        $I->fillField("Code", $code);
        $I->click("Next");
        $I->see("Setup almost complete");
        $I->click("Complete Setup");
        $I->see("authentication has been successfully enabled");

        $I->amOnPage($page);
        $I->see("Would you like to cancel two-factor authentication?");
        $I->click("Yes");
        $I->see("authentication has been disabled");
    }

    public function _before(\CodeGuy $I)
    {
        $this->em = $I->grabService('doctrine')->getManager();
        $login = 'test2fact' . $I->grabRandomString(5);
        $password = 'tup' . $I->grabRandomString();
        $this->user = $this->em->getRepository(Usr::class)->find($I->createAwUser($login, $password, [
            'FirstName' => 'First',
            'LastName' => 'Last',
        ]));
        $this->router = $I->grabService('router');
    }

    public function _after(\CodeGuy $I)
    {
        $this->user = null;
        $this->router = null;
        $this->em = null;
    }

    public function userWithoutPasswordShouldNotBeAbleSetup2Fact(\TestSymfonyGuy $I)
    {
        $this->user->setPass(null);
        $this->em->flush();

        $I->amOnPage($this->router->generate('aw_profile_2factor') . "?_switch_user=" . $this->user->getLogin());
        $I->see('Please create an AwardWallet password and start using your username and password');
    }

    public function userWithPasswordShouldBeAbleSetup2Fact(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_profile_2factor') . "?_switch_user=" . $this->user->getLogin());
        $I->see('Two-factor authentication');
    }
}
