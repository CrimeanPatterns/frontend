<?php

namespace AwardWallet\Tests\FunctionalSymfony\Account;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class SearchHintsUpdateCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private int $userId;
    private $username;
    private ?RouterInterface $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true);
        $this->username = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->userId]);
        $this->router = $I->grabService('router');
    }

    /**
     * Проверяет обновление поля "search_hints" у пользователя.
     */
    public function checkUpdateHintsForUser(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_list', ['_switch_user' => $this->username]));
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');

        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($this->userId);
        $I->assertEquals(null, $user->getSearchHints());

        $I->saveCsrfToken();
        $I->sendPost('/account/search-hints', ['value' => 'Aeromexico']);
        $I->canSeeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($this->userId);
        $I->assertEquals(['Aeromexico'], $user->getSearchHints());
    }
}
