<?php

namespace AwardWallet\Tests\FunctionalSymfony\Account;

use AwardWallet\MainBundle\Globals\StringUtils;
use Codeception\Example;
use Doctrine\DBAL\Connection;

/**  @group frontend-functional */
class ExportAccountListCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $userId;

    private $login;

    private $translator;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->login = "login" . StringUtils::getRandomCode(10);
        $this->userId = $I->createAwUser($this->login, null, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS], true);
        $provider = "testprovider";
        $this->translator = $I->grabService('translator');
        $accountId = $I->createAwAccount($this->userId, $provider, "balance.random");
        $I->amOnPage('/account/list?_switch_user=' . $this->login);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->userId = null;
        $this->login = null;
    }

    /**
     * @dataProvider formatTypes
     */
    public function checkValidFormat(\TestSymfonyGuy $I, Example $example)
    {
        $I->wantTo('download excel file - account list: valid user');
        $I->sendGET($I->getContainer()->get('router')->generate('aw_account_list_export', [
            'type' => $example['type'],
        ]));
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('content-type', $example['content-type']);
    }

    public function checkInvalidFormat(\TestSymfonyGuy $I)
    {
        $I->wantTo('download excel file - account list: bad data');

        $validType = $this->formatTypes()[0]['type'];
        $url = $I->getContainer()->get('router')->generate('aw_account_list_export', ['type' => $validType]);
        $url = str_replace($validType, 'badformat', $url);

        $I->sendGET($url);
        $I->seeResponseCodeIs(404);
    }

    public function checkNeedAwPlus(\TestSymfonyGuy $I)
    {
        $I->wantTo('download excel file - account list: error, not aw plus user');
        $login = "login" . StringUtils::getRandomCode(10);
        $userId = $I->createAwUser($login, null, ['AccountLevel' => ACCOUNT_LEVEL_FREE], true);
        $I->createAwAccount($userId, 'testprovider', "balance.random");
        $I->amOnPage('/account/list?_switch_user=' . $login);

        $type = $this->formatTypes();
        $I->followRedirects(0);
        $I->sendGET($I->getContainer()->get('router')->generate('aw_account_list_export', ['type' => $type[0]['type']]));

        $I->seeResponseCodeIs(302);
    }

    /**
     * @dataProvider formatTypes
     */
    public function checkAccountIssetInFile(\TestSymfonyGuy $I, Example $example)
    {
        $I->sendGET($I->getContainer()->get('router')->generate('aw_account_list_export', [
            'type' => $example['type'],
        ]));
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('content-type', $example['content-type']);
        /** @var Connection $db */
        $db = $I->grabService('doctrine')->getManager()->getConnection();
        $fullName = implode(' ', $db->executeQuery('SELECT FirstName, MidName, LastName FROM Usr WHERE AccountLevel = ' . ACCOUNT_LEVEL_AWPLUS . ' AND UserID = ' . $this->userId . ' LIMIT 1')->fetch());
        $fullName = preg_replace('/\s+/', ' ', $fullName);
        $I->seeInHeader('content-disposition', ucwords(strtolower($fullName)));
    }

    private function formatTypes()
    {
        return [
            ['type' => 'excel', 'content-type' => 'application/vnd.ms-excel'],
            ['type' => 'pdf', 'content-type' => 'application/pdf'],
        ];
    }
}
