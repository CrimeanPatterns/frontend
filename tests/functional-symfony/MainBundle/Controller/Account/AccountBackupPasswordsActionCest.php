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
class AccountBackupPasswordsActionCest
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

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $this->router = $I->getContainer()->get('router');
        $this->loginUser($I, $this->user);
    }

    public function success(\TestSymfonyGuy $I)
    {
        $this->mockReauth($I);
        $I->saveCsrfToken();
        $this->send($I, ['accounts' => $accountId = 'a' . $this->createAccount($I, 'xxx')]);
        $I->seeResponseCodeIs(200);
        $this->assertResponse($I, [
            'success' => true, 'accounts' => [$accountId => true],
        ]);
    }

    public function successDownload(\TestSymfonyGuy $I)
    {
        $this->mockReauth($I, true, true);
        $I->saveCsrfToken();
        $this->send($I, ['accounts' => $accountId = 'a' . $this->createAccount($I, 'xxx'), 'download' => true]);
        $I->seeResponseCodeIs(200);
    }

    public function fail(\TestSymfonyGuy $I)
    {
        $this->mockReauth($I, false);
        $I->saveCsrfToken();
        $this->send($I, ['accounts' => $accountId = 'a' . $this->createAccount($I, 'xxx')]);
        $I->seeResponseCodeIs(200);
        $this->assertResponse($I, [
            'success' => false,
        ]);
    }

    public function successWithUnsuitableAccount(\TestSymfonyGuy $I)
    {
        $this->mockReauth($I);
        $I->saveCsrfToken();
        $this->send($I, ['accounts' => [$accountId = 'a' . $this->createAccount($I, 'xxx'), 'a123']]);
        $I->seeResponseCodeIs(200);
        $this->assertResponse($I, [
            'success' => true, 'accounts' => [$accountId => true],
        ]);
    }

    public function successWithEmptyPassword(\TestSymfonyGuy $I)
    {
        $this->mockReauth($I);
        $I->saveCsrfToken();
        $this->send($I, ['accounts' => [$accountId = 'a' . $this->createAccount($I, '')]]);
        $I->seeResponseCodeIs(200);
        $I->assertIsNotBool($I->grabDataFromJsonResponse("accounts.$accountId"));
        $this->assertResponse($I, ['success' => true]);
    }

    private function createAccount(\TestSymfonyGuy $I, string $password = '', array $fields = []): int
    {
        return $I->createAwAccount(
            $this->user->getId(),
            'testprovider',
            'some',
            $password,
            $fields
        );
    }

    private function mockReauth(\TestSymfonyGuy $I, bool $result = true, bool $resetCall = false)
    {
        $methods = [
            'isReauthenticated' => $result,
        ];

        if ($resetCall) {
            $methods['reset'] = Stub::once();
        }

        $I->mockService(ReauthenticatorWrapper::class, $I->stubMakeEmpty(ReauthenticatorWrapper::class, $methods));
    }

    private function send(\TestSymfonyGuy $I, array $data)
    {
        $I->sendPOST($this->router->generate('aw_account_backup_passwords'), $data);
    }

    private function assertResponse(\TestSymfonyGuy $I, array $result)
    {
        $I->seeResponseContainsJson($result);
    }
}
