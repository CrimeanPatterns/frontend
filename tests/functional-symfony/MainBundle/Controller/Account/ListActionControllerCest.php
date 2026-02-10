<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\Modules\Access\AccountAccessScenario;
use AwardWallet\Tests\Modules\Access\Action;
use AwardWallet\Tests\Modules\Access\CouponDeleteScenario;
use Codeception\Example;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class ListActionControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?RouterInterface $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDeleteAccess(\TestSymfonyGuy $I, Example $example)
    {
        /** @var CouponDeleteScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $I->followRedirects(false);

        $I->sendGET("/contact");
        $I->seeResponseCodeIs(200);
        $I->saveCsrfToken();

        $I->sendPOST($this->router->generate("aw_account_json_remove"), json_encode([
            ["isCoupon" => true, "id" => $scenario->couponId],
        ]));

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->seeResponseContainsJson(["removed" => ["c" . $scenario->couponId]]);

                break;

            case Action::REDIRECT_TO_LOGIN:
                $I->seeResponseCodeIs(302);

                break;

            default:
                $I->seeResponseContainsJson(["removed" => []]);
        }
    }

    /**
     * Проверяет перенос аккаунтов и купонов в архивные.
     */
    public function testAddArchive(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $providers = [
            'brex' => $I->createAwProvider('Brex Cash Test', StringHandler::getRandomCode(8)),
            'citibank' => $I->createAwProvider('Citibank Test', StringHandler::getRandomCode(8)),
        ];

        $I->createAwAccount($userId, $providers['brex'], 'my_account_01');
        $I->createAwAccount($userId, $providers['citibank'], 'my_account_02');
        $I->createAwCoupon($userId, 'passport', null);
        $data = [];

        $accounts = $I->query('SELECT * FROM `Account` WHERE `UserID` = ?', [$userId])->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($accounts as $account) {
            $data[] = 'a' . $account['AccountID'];
            $I->assertEquals(Account::NOT_ARCHIVED, $account['IsArchived']);
        }

        $coupon = $I->query('SELECT * FROM `ProviderCoupon` WHERE `UserID` = ?', [$userId])->fetch(\PDO::FETCH_ASSOC);
        $data[] = 'c' . $coupon['ProviderCouponID'];
        $I->assertEquals(Providercoupon::NOT_ARCHIVED, $coupon['IsArchived']);

        $I->switchToUser($userId);

        $I->saveCsrfToken();
        $I->sendAjaxPostRequest($this->router->generate('aw_acount_json_addarchiveaccount'), ['form' => ['accounts' => $data, 'isArchived' => 1]]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $accounts = $I->query('SELECT * FROM `Account` WHERE `UserID` = ?', [$userId])->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($accounts as $account) {
            $I->assertEquals(Account::ARCHIVED, $account['IsArchived']);
        }

        $coupon = $I->query('SELECT * FROM `ProviderCoupon` WHERE `UserID` = ?', [$userId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(Providercoupon::ARCHIVED, $coupon['IsArchived']);
    }

    /**
     * Проверяет доступ к переносу аккаунтов другого пользователя в архивные.
     *
     * @dataProvider dataProviderAccountAccess
     */
    public function testAddArchiveAccess(\TestSymfonyGuy $I, Example $example)
    {
        /** @var AccountAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        if ($scenario->authorized) {
            $I->saveCsrfToken();
        }
        $I->sendAjaxPostRequest($this->router->generate('aw_acount_json_addarchiveaccount'), [
            'form' => ['accounts' => ['a' . $scenario->accountId], 'isArchived' => Account::ARCHIVED],
        ]);

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->seeResponseCodeIs(200);
                $I->seeInDatabase('Account', ['AccountID' => $scenario->accountId, 'IsArchived' => Account::ARCHIVED]);

                break;

            case Action::REDIRECT_TO_LOGIN:
            case Action::FORBIDDEN:
                $I->seeResponseCodeIs(403);
                $I->seeInDatabase('Account', ['AccountID' => $scenario->accountId, 'IsArchived' => Account::NOT_ARCHIVED]);

                break;
        }
    }

    private function dataProvider()
    {
        return CouponDeleteScenario::dataProvider();
    }

    private function dataProviderAccountAccess()
    {
        return AccountAccessScenario::dataProvider();
    }
}
