<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthResponse;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class ReauthenticateControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;

    /**
     * @var Usr
     */
    protected $user;

    /**
     * @var string
     */
    private $pass = 'xxxyyy';

    /**
     * @var Router
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, $this->pass));
        $this->router = $I->getContainer()->get('router');
        $this->loginUser($I, $this->user);
    }

    public function startActionNoAuth(\TestSymfonyGuy $I)
    {
        $this->logoutUser($I);
        $this->startReauth($I, [], 200);
        $I->amOnPage($this->router->generate('aw_home'));
    }

    public function startActionAuth(\TestSymfonyGuy $I)
    {
        $this->startReauth($I, [], 200);
    }

    public function startActionNoCsrf(\TestSymfonyGuy $I)
    {
        $this->sendStartReauth($I, ['action' => Action::getChangeEmailAction()]);
        $I->seeResponseCodeIs(403);
    }

    public function startActionBadRequest(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate('aw_reauth_start'), [
            'action' => Action::getChangeEmailAction(),
        ]);
        $I->seeResponseCodeIs(405);
    }

    public function startActionBadAction(\TestSymfonyGuy $I)
    {
        $this->startReauth($I, ['action' => null], 400);
        $this->startReauth($I, ['action' => 'xxxxx'], 400);
    }

    public function startActionAsking(\TestSymfonyGuy $I)
    {
        $this->startReauth($I, [], 200, [
            'action' => ReauthResponse::ACTION_ASK,
            'context' => 'password',
        ]);
    }

    public function startActionAuthorized(\TestSymfonyGuy $I)
    {
        $this->verifyReauth($I);
        $this->startReauth($I, [], 200, ['action' => ReauthResponse::ACTION_AUTHORIZED]);
    }

    public function verifyActionNoAuth(\TestSymfonyGuy $I)
    {
        $this->logoutUser($I);
        $this->verifyReauth($I, [], 200);
        $I->amOnPage($this->router->generate('aw_home'));
    }

    public function verifyActionAuth(\TestSymfonyGuy $I)
    {
        $this->verifyReauth($I, [], 200);
    }

    public function verifyActionNoCsrf(\TestSymfonyGuy $I)
    {
        $this->sendVerifyReauth($I, ['action' => Action::getChangeEmailAction()]);
        $I->seeResponseCodeIs(403);
    }

    public function verifyActionBadRequest(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate('aw_reauth_verify'), [
            'action' => Action::getChangeEmailAction(),
            'context' => 'password',
            'input' => $this->pass,
        ]);
        $I->seeResponseCodeIs(405);
    }

    public function verifyActionBadAction(\TestSymfonyGuy $I)
    {
        $this->verifyReauth($I, ['action' => null], 400);
        $this->verifyReauth($I, ['action' => 'xxxxx'], 400);
    }

    public function verifyActionBadContext(\TestSymfonyGuy $I)
    {
        $this->verifyReauth($I, ['context' => null], 400);
        $this->verifyReauth($I, ['context' => 'xxxxx'], 400);
    }

    public function verifyActionInvalidPassword(\TestSymfonyGuy $I)
    {
        $this->verifyReauth($I, ['input' => 'zzzz'], 200, [
            'success' => false,
        ]);
    }

    public function verifyActionCorrectPassword(\TestSymfonyGuy $I)
    {
        $this->verifyReauth($I, [], 200, [
            'success' => true,
        ]);
    }

    public function verifyActionWithoutPassword(\TestSymfonyGuy $I)
    {
        $this->logoutUser($I);
        $this->user->setPass(null);
        $I->updateInDatabase('Usr', ['Pass' => null], ['UserID' => $this->user->getUserid()]);
        $this->loginUser($I, $this->user);
        $this->verifyReauth($I, ['input' => null], 400);
    }

    private function startReauth(\TestSymfonyGuy $I, array $data = [], int $responseCode = 200, ?array $responseJson = null)
    {
        $I->saveCsrfToken();
        $this->sendStartReauth($I, array_merge(['action' => Action::getChangeEmailAction()], $data));
        $I->seeResponseCodeIs($responseCode);

        if ($responseJson) {
            $I->seeResponseContainsJson($responseJson);
        }
    }

    private function sendStartReauth(\TestSymfonyGuy $I, array $data)
    {
        $I->sendPOST($this->router->generate('aw_reauth_start'), $data);
    }

    private function verifyReauth(\TestSymfonyGuy $I, array $data = [], int $responseCode = 200, ?array $responseJson = null)
    {
        $I->saveCsrfToken();
        $this->sendVerifyReauth($I, array_merge([
            'action' => Action::getChangeEmailAction(),
            'context' => 'password',
            'input' => $this->pass,
        ], $data));
        $I->seeResponseCodeIs($responseCode);

        if ($responseJson) {
            $I->seeResponseContainsJson($responseJson);
        }
    }

    private function sendVerifyReauth(\TestSymfonyGuy $I, array $data)
    {
        $I->sendPOST($this->router->generate('aw_reauth_verify'), $data);
    }
}
