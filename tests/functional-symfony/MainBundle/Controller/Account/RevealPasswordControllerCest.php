<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use Codeception\Util\Stub;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class RevealPasswordControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;

    /**
     * @var Usr
     */
    protected $user;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var int
     */
    private $accountId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, [], true));
        $this->router = $I->getContainer()->get('router');
        $this->accountId = $I->createAwAccount($this->user->getUserid(), "testprovider", "some");
        $this->loginUser($I, $this->user);
    }

    public function success(\TestSymfonyGuy $I)
    {
        $I->mockService(ReauthenticatorWrapper::class, $I->stubMakeEmpty(ReauthenticatorWrapper::class, [
            'isReauthenticated' => true,
            'reset' => Stub::once(),
        ]));
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('aw_get_pass', ['accountId' => $this->accountId]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function fail(\TestSymfonyGuy $I)
    {
        $I->mockService(ReauthenticatorWrapper::class, $I->stubMakeEmpty(ReauthenticatorWrapper::class, [
            'isReauthenticated' => false,
            'reset' => Stub::never(),
        ]));
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('aw_get_pass', ['accountId' => $this->accountId]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => false]);
    }
}
