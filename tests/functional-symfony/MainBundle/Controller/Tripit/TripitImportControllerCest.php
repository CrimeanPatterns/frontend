<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Tripit;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Tripit\TripitHelper;
use AwardWallet\MainBundle\Service\Tripit\TripitImportResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class TripitImportControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $userId;
    private $username;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    private ?string $requestToken = null;
    private ?string $requestSecret = null;
    private ?string $accessToken = null;
    private ?string $accessSecret = null;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true);
        $this->username = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->userId]);
        $this->router = $I->grabService('router');
        $this->entityManager = $I->grabService('doctrine')->getManager();

        $this->requestToken = StringHandler::getRandomCode(32);
        $this->requestSecret = StringHandler::getRandomCode(32);
        $this->accessToken = StringHandler::getRandomCode(32);
        $this->accessSecret = StringHandler::getRandomCode(32);

        $I->mockService(TripitHelper::class, $I->stubMakeEmpty(TripitHelper::class, [
            'getRequestToken' => function ($user) {
                return http_build_query(['oauth_token' => $this->requestToken, 'oauth_token_secret' => $this->requestSecret]);
            },
            'getAccessToken' => function ($user) {
                return http_build_query(['oauth_token' => $this->accessToken, 'oauth_token_secret' => $this->accessSecret]);
            },
            'subscribe' => function ($user) {
                return json_encode(['timestamp' => time(), 'num_bytes' => 80]);
            },
            'unsubscribe' => function ($user) {
                return json_encode(['timestamp' => time(), 'num_bytes' => 80]);
            },
        ]));
    }

    public function _after(\TestSymfonyGuy $I)
    {
        unset($this->userId, $this->username);
        $this->requestToken = null;
        $this->requestSecret = null;
        $this->accessToken = null;
        $this->accessSecret = null;
    }

    /**
     * Проверяет получение временного токена запроса.
     */
    public function checkGetRequestToken(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_timeline', ['_switch_user' => $this->username]));

        /** @var Usr $user */
        $user = $this->entityManager->getRepository(Usr::class)->find($this->userId);
        $I->assertEquals(null, $user->getTripitOauthToken());

        $I->followRedirects(false);
        $I->sendGet('/tripit/authorize');

        $url = TripitHelper::SIGN_IN_URL . '?' . http_build_query([
            'oauth_token' => $this->requestToken,
            'oauth_callback' => $this->router->generate('aw_timeline_tripit_access_token', [], RouterInterface::ABSOLUTE_URL),
            'is_sign_in' => 1,
        ]);
        $I->seeRedirectTo($url);

        $this->entityManager->refresh($user);
        $I->assertEquals([
            'oauth_request_token' => $this->requestToken,
            'oauth_request_secret' => $this->requestSecret,
            'oauth_access_token' => null,
            'oauth_access_secret' => null,
        ], $user->getTripitOauthToken());
    }

    /**
     * Проверяет получение постоянного токена доступа.
     */
    public function checkGetAccessToken(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_timeline', ['_switch_user' => $this->username]));

        /** @var Usr $user */
        $user = $this->entityManager->getRepository(Usr::class)->find($this->userId);
        $user->setTripitOauthToken([
            'oauth_request_token' => $this->requestToken,
            'oauth_request_secret' => $this->requestSecret,
            'oauth_access_token' => null,
            'oauth_access_secret' => null,
        ]);

        $I->followRedirects(false);
        $I->sendGet('/tripit/access-token');

        $I->seeRedirectToPath('/tripit/import');

        $this->entityManager->refresh($user);
        $I->assertEquals([
            'oauth_request_token' => null,
            'oauth_request_secret' => null,
            'oauth_access_token' => $this->accessToken,
            'oauth_access_secret' => $this->accessSecret,
        ], $user->getTripitOauthToken());
    }

    /**
     * Проверяет получение новых резерваций.
     */
    public function checkImport(\TestSymfonyGuy $I)
    {
        $I->mockService(TripitHelper::class, $I->stubMakeEmpty(TripitHelper::class, [
            'list' => function ($user) {
                return new TripitImportResult(true, ['T.10570000']);
            },
        ]));

        $I->amOnPage($this->router->generate('aw_timeline', ['_switch_user' => $this->username]));

        $I->followRedirects(false);
        $I->sendGet('/tripit/import');

        $I->seeRedirectToPath('/timeline/itineraries/T.10570000');
    }

    /**
     * Проверяет получение новых резерваций, в случае, если предстоящие события отсутствуют.
     */
    public function checkImportEmptyReservations(\TestSymfonyGuy $I)
    {
        $I->mockService(TripitHelper::class, $I->stubMakeEmpty(TripitHelper::class, [
            'list' => function ($user) {
                return new TripitImportResult(true, []);
            },
        ]));

        $I->amOnPage($this->router->generate('aw_timeline', ['_switch_user' => $this->username]));

        $I->followRedirects(false);
        $I->sendGet('/tripit/import');

        $I->seeRedirectToPath('/timeline/');
    }

    /**
     * Проверяет получение новых резерваций, в случае, если токен пользователя недействителен.
     */
    public function checkImportUnauthorized(\TestSymfonyGuy $I)
    {
        $I->mockService(TripitHelper::class, $I->stubMakeEmpty(TripitHelper::class, [
            'list' => function ($user) {
                return new TripitImportResult(false);
            },
        ]));

        $I->amOnPage($this->router->generate('aw_timeline', ['_switch_user' => $this->username]));

        $I->followRedirects(false);
        $I->sendGet('/tripit/import');

        $I->seeRedirectToPath('/tripit/authorize');
    }

    /**
     * Проверяет отключение привязанной учётной записи.
     */
    public function checkRemoveTokens(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_profile_overview', ['_switch_user' => $this->username]));

        /** @var Usr $user */
        $user = $this->entityManager->getRepository(Usr::class)->find($this->userId);
        $user->setTripitOauthToken([
            'oauth_request_token' => null,
            'oauth_request_secret' => null,
            'oauth_access_token' => $this->accessToken,
            'oauth_access_secret' => $this->accessSecret,
        ]);

        $I->followRedirects(false);
        $I->sendGet('/tripit/disconnect');

        $I->seeRedirectToPath('/user/profile');

        $this->entityManager->refresh($user);
        $I->assertEquals([
            'oauth_request_token' => null,
            'oauth_request_secret' => null,
            'oauth_access_token' => null,
            'oauth_access_secret' => null,
        ], $user->getTripitOauthToken());
    }
}
