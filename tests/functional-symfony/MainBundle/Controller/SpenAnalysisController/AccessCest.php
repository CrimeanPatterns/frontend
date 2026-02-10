<?php
/**
 * Created by PhpStorm.
 * User: puzakov
 * Date: 13/03/2018
 * Time: 10:26.
 */

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\SpenAnalysisController;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use AwardWallet\Tests\Modules\Access\AccountAccessScenario;
use AwardWallet\Tests\Modules\Access\Action;
use Codeception\Example;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

/**
 * @group frontend-functional
 */
class AccessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const ANALYSIS_DATA_URL = "/spend-analysis/merchants/data";

    private $userId;
    private $username;
    private $accountId;
    private $subAccId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true, true);
        $this->username = $I->grabFromDatabase("Usr", "Login", ["UserID" => $this->userId]);
        $this->accountId = $I->createAwAccount($this->userId, 'chase', 'testchase');
        $this->subAccId = $I->createAwSubAccount($this->accountId);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->userId = null;
        $this->username = null;
        $this->accountId = null;
        $this->subAccId = null;
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDefaultAccess(\TestSymfonyGuy $I, Example $example)
    {
        /** @var AccountAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $I->sendPOST(self::ANALYSIS_DATA_URL, $this->getRequestParams());

        switch ($scenario->expectedAction) {
            case Action::REDIRECT_TO_LOGIN:
                $I->seeInCurrentUrl("/login?BackTo=");
                $I->dontSeeInSource($scenario->login);

                break;

            default:
                $I->seeResponseCodeIs(200);
                $I->dontSeeInSource($scenario->login);
        }
    }

    public function testNoAwPlus(\TestSymfonyGuy $I)
    {
        $page = $I->getContainer()->get('router')->generate('aw_account_list');
        $I->amOnPage($page . "?_switch_user=" . $this->username);
        $I->sendPOST(self::ANALYSIS_DATA_URL, $this->getRequestParams());
        $I->seeResponseCodeIs(200);
    }

    public function testAwPlus(\TestSymfonyGuy $I)
    {
        $page = $I->getContainer()->get('router')->generate('aw_account_list');
        $I->addUserPayment($this->userId, \PAYMENTTYPE_CREDITCARD, new AwPlusSubscription());
        $I->amOnPage($page . "?_switch_user=" . $this->username);
        $I->sendPOST(self::ANALYSIS_DATA_URL, $this->getRequestParams());
        $I->seeResponseCodeIs(200);
    }

    public function testAnalysisDataSuccess(\TestSymfonyGuy $I)
    {
        $page = $I->getContainer()->get('router')->generate('aw_account_list');
        $I->addUserPayment($this->userId, \PAYMENTTYPE_CREDITCARD, new AwPlusSubscription());
        $I->amOnPage($page . "?_switch_user=" . $this->username);

        $merchants = [];

        foreach (["TestMerchant #1", "TestMerchant #2", "TestMerchant #3"] as $merchantName) {
            $I->haveInDatabase('Merchant', ["Name" => $merchantName]);
            $id = $I->grabFromDatabase("Merchant", "MerchantID", ["Name" => $merchantName]);
            $merchants[$id] = $merchantName;
        }

        //        StringHandler::uuid();
        //        $data = [
        //            [1209586, '2018-01-26 05:00:00', 'TANCZOS BEVERAGES BETHLEH', 78.91, NULL, 129, '1b2a11d0-d83e-453c-8c25-d033e361b239', 0, NULL, 2008160, 78.91, NULL, NULL, 3, NULL, 252161, 1.0, NULL],
        //            [1209586, '2017-12-22 05:00:00', 'TANCZOS BEVERAGES BETHLEH', 206.69, NULL, 246, '31dddc23-dbc2-4fa5-9f0d-6aa63e95f5c9', 0, NULL, 2008160, 206.69, NULL, NULL, 3, NULL, 252161, 1.0, NULL],
        //            [1209586, '2018-02-16 05:00:00', 'TANCZOS BEVERAGES BETHLEH', 102.8, NULL, 51, '36fd107e-f771-4850-b9a1-3efa22ea503a', 0, NULL, 2008160, 102.80, NULL, NULL, 3, NULL, 252161, 1.0, NULL],
        //            [1209586, '2018-02-09 05:00:00', 'TANCZOS BEVERAGES BETHLEH', 105.98, NULL, 71, 'c7a01b90-f5ef-4868-9e3c-bfc98514ffa2', 0, NULL, 2008160, 105.98, NULL, NULL, 3, NULL, 252161, 1.0, NULL],
        //            [1209586, '2018-02-08 05:00:00', 'THE HOME DEPOT 4105', 27.54, NULL, 77, '014e0315-8310-4e89-92c2-679e2c3356c8', 0, NULL, 2008160, 27.54, NULL, NULL, 3, NULL, 31, 1.0, NULL],
        //            [1209586, '2018-02-23 05:00:00', 'THE HOME DEPOT 4105', 13.51, NULL, 29, 'b87d8b11-5d0d-48f2-bdcd-1a7e670f7959', 0, NULL, 2008160, 13.51, NULL, NULL, 3, NULL, 31, 1.0, NULL],
        //            [1209586, '2018-02-03 05:00:00', 'THE HOME DEPOT 4105', 13.5, NULL, 101, 'db6324e2-b30c-4e97-a07a-bb7eb47bd0f5', 0, NULL, 2008160, 13.50, NULL, NULL, 3, NULL, 31, 1.0, NULL],
        //            [1209586, '2018-02-23 05:00:00', 'TURKEY HILL #298', 49.06, NULL, 32, '89e7ac54-fceb-408b-9cf1-63b1d70d5506', 0, NULL, 2008160, 49.06, NULL, NULL, 3, NULL, 13528, 1.0, NULL],
        //            [1209586, '2018-01-10 05:00:00', 'TURKEY HILL #298', 39.54, NULL, 175, 'fd35e418-cfc3-468f-b67c-6850c1b6b13a', 0, NULL, 2008160, 39.54, NULL, NULL, 3, NULL, 13528, 1.0, NULL],
        //            [1209586, '2018-02-04 05:00:00', 'VALERO GAS STATION', 51.61, NULL, 96, 'f8ba2b18-f874-4e61-bdd6-54cd02913d8e', 0, NULL, 2008160, 51.61, NULL, NULL, 3, NULL, 634107, 1.0, NULL],
        //            [1209586, '2018-01-28 05:00:00', 'WEGMANS #97', 26.02, NULL, 121, '020dd9a9-3423-49d3-b072-7bbb560e5a23', 0, NULL, 2008160, 26.02, NULL, NULL, 3, NULL, 4588, 1.0, NULL],
        //            [1209586, '2017-12-30 05:00:00', 'WEGMANS #97', 25.66, NULL, 215, '02724922-d6eb-4d5d-a192-e37954670e9a', 0, NULL, 2008160, 25.66, NULL, NULL, 3, NULL, 4588, 1.0, NULL],
        //            [1209586, '2018-01-31 05:00:00', 'WEGMANS #97', 14.38, NULL, 111, '08dfce49-d83a-425c-b681-151dd10eb2f7', 0, NULL, 2008160, 14.38, NULL, NULL, 3, NULL, 4588, 1.0, NULL],
        //            [1209586, '2018-01-20 05:00:00', 'WEGMANS #97', 19.99, NULL, 155, '0b47c213-201d-4f5c-aa35-0da2ff73018f', 0, NULL, 2008160, 19.99, NULL, NULL, 3, NULL, 4588, 1.0, NULL],
        //        ];
        //        $I->haveInDatabase()

        $I->sendPOST(self::ANALYSIS_DATA_URL, $this->getRequestParams());
        $I->seeResponseCodeIs(200);
    }

    /**
     * @dataProvider connectedCheckData
     */
    public function testConnectedOwners(\TestSymfonyGuy $I, Example $example)
    {
        $userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        $agentRepository = $I->grabService('doctrine')->getManager()->getRepository(Useragent::class);
        $connectionManager = $I->grabService('aw.manager.connection_manager');

        [$userId, $accountId, $creditCardId, $merchantId, $subAccountId] = $this->createBaseData($I);
        [$userId2, $accountId2, $creditCardId2, $merchantId2, $subAccountId2] = $this->createBaseData($I);

        $connectionId = $I->createConnection($userId2, $userId);
        $connectionManager->connectUser($userRepository->find($userId), $userRepository->find($userId2));

        $agent = $agentRepository->find($connectionId);
        $client = $agentRepository->findOneBy(['clientid' => $agent->getAgentid()->getUserid()]);
        $connectionManager->approveConnection($client, $userRepository->find($userId2));

        /** @var Connection $connection */
        $connection = $I->grabService('doctrine')->getConnection();

        $I->haveInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $connectionId]);
        $I->haveInDatabase('AccountShare', ['AccountID' => $accountId2, 'UserAgentID' => $connectionId]);

        $I->switchToUser($userId);
        $bank = $I->grabService(BankTransactionsAnalyser::class);

        $connection->update('UserAgent', ['AccessLevel' => $example['level']], ['UserAgentID' => $agent->getId()]);
        $initial = $bank->getSpentAnalysisInitialByUserAgent($agent);

        $I->assertEquals($example['availableOwnerUsers'], \count($initial['ownersList']));
    }

    private function getRequestParams()
    {
        return [
            "ids" => [$this->subAccId],
            "range" => 1,
        ];
    }

    private function dataProvider()
    {
        return AccountAccessScenario::dataProvider();
    }

    private function createBaseData(\TestSymfonyGuy $I): array
    {
        $userId = $I->createAwUser();
        $accountId = $I->createAwAccount($userId, Provider::CHASE_ID, "test");
        $creditCardId = $I->createAwCreditCard(Provider::CHASE_ID);
        $merchantId = $I->createAwMerchant();
        $subAccountId = $I->createAwSubAccount($accountId, [
            'CreditCardID' => $creditCardId,
            'DisplayName' => sprintf('SubAccount %d', $creditCardId),
        ]);

        $date = time();

        for ($n = 0; $n <= SpentAnalysisService::MIN_MULTIPLIER_TRANSACTIONS; $n++) {
            $uuid = Uuid::uuid4()->toString();
            $I->haveInDatabase("AccountHistory", [
                "UUID" => $uuid,
                "AccountID" => $accountId,
                "SubAccountID" => $subAccountId,
                "MerchantID" => $merchantId,
                "PostingDate" => date("Y", $date) . "-01-01 00:00:00",
                "Description" => "Existing tx",
                "Miles" => \rand(100, 10000),
                "Position" => 1,
                'Amount' => \rand(1, 100),
            ]);
        }

        return [$userId, $accountId, $creditCardId, $merchantId, $subAccountId];
    }

    private function connectedCheckData()
    {
        return [
            ['level' => Useragent::ACCESS_READ_NUMBER, 'availableOwnerUsers' => 1],
            ['level' => Useragent::ACCESS_READ_BALANCE_AND_STATUS, 'availableOwnerUsers' => 1],
            ['level' => Useragent::ACCESS_WRITE, 'availableOwnerUsers' => 2],
            ['level' => Useragent::ACCESS_ADMIN, 'availableOwnerUsers' => 2],
        ];
    }
}
