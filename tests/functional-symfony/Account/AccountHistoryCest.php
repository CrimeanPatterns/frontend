<?php

namespace Account;

use AwardWallet\MainBundle\Email\Api;
use AwardWallet\MainBundle\Loyalty\EmailApiHistoryParser;
use Codeception\Util\Stub;
use Monolog\Logger;

/**
 * @group frontend-functional
 */
class AccountHistoryCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public $apiResponse;
    public $deltaId;
    public $current;
    protected $userId;
    protected $username;
    private $mileageId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true, true);
        $this->username = $I->grabFromDatabase("Usr", "Login", ["UserID" => $this->userId]);
        $this->mileageId = $I->createAwAccount($this->userId, 'mileageplus', 'account_history');
        $this->deltaId = $I->createAwAccount($this->userId, 'delta', 'account_history');

        $json = file_get_contents(__DIR__ . '/../../_data/AccountHistory/mileageplus.json');
        $data = json_decode($json, true);
        $data['userData'] = json_encode(['accountId' => $this->mileageId, 'id' => uniqid()]);
        $this->apiResponse = json_encode($data);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->accountId = null;
        $this->apiResponse = null;
        $this->userId = null;
        $this->username = null;
    }

    public function checkHistoryUpload(\TestSymfonyGuy $I)
    {
        $I->wantTo('upload valid history file');
        $this->mockServices($I, true);

        $page = $I->grabService('router')->generate('aw_account_v2_history_view', ['accountId' => $this->deltaId]);
        $I->amOnPage($page . "?_switch_user=" . $this->username);

        $I->saveCsrfToken();

        $route = $I->getContainer()->get('router')->generate('aw_account_history_upload', ['accountId' => $this->deltaId]);
        $I->sendPOST($route, [], [
            'historyFile' => [
                'name' => 'delta.pdf',
                'type' => 'application/pdf',
                'error' => UPLOAD_ERR_OK,
                'size' => filesize(__DIR__ . '/../../_data/AccountHistory/delta.pdf'),
                'tmp_name' => __DIR__ . '/../../_data/AccountHistory/delta.pdf',
            ],
        ]);

        $I->seeResponseIsJson();
        $res = $I->grabDataFromJsonResponse();
        \PHPUnit_Framework_Assert::assertTrue($res['success']);
    }

    public function checkApiCallback(\TestSymfonyGuy $I)
    {
        $I->wantTo('send valid Email API response');
        $this->mockServices($I, false, true);
        $route = $I->grabService('router')->generate('aw_account_history_callback');

        $I->haveHttpHeader("PHP_AUTH_USER", 'awardwallet');
        $I->haveHttpHeader("PHP_AUTH_PW", $I->getContainer()->getParameter('email.callback_password'));

        $I->sendPOST($route, $this->apiResponse);
        $I->seeResponseEquals('OK');

        $query = $I->query("select * from AccountHistory where AccountID = {$this->mileageId}");

        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        \PHPUnit_Framework_Assert::assertCount(13, $res);
    }

    public function checkInvalidProvider(\TestSymfonyGuy $I)
    {
        $I->wantTo('send valid Email API response with incorrect providerCode');
        $this->mockServices($I, false, true);
        $this->current = __FUNCTION__;
        $accountId = $I->createAwAccount($this->userId, 'testprovider', 'account_history');
        $payload = json_decode($this->apiResponse, true);
        $payload['userData'] = json_encode(['accountId' => $accountId, 'id' => uniqid()]);

        $route = $I->grabService('router')->generate('aw_account_history_callback');

        $I->haveHttpHeader("PHP_AUTH_USER", 'awardwallet');
        $I->haveHttpHeader("PHP_AUTH_PW", $I->getContainer()->getParameter('email.callback_password'));

        $I->sendPOST($route, json_encode($payload));
        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals('Malformed request');
    }

    public function checkInvalidActivity(\TestSymfonyGuy $I)
    {
        $I->wantTo('send valid Email API response with empty activity');
        $this->mockServices($I);
        $this->current = __FUNCTION__;
        $payload = json_decode($this->apiResponse, true);
        $payload['loyaltyAccount']['history'] = null;

        $route = $I->grabService('router')->generate('aw_account_history_callback');

        $I->haveHttpHeader("PHP_AUTH_USER", 'awardwallet');
        $I->haveHttpHeader("PHP_AUTH_PW", $I->getContainer()->getParameter('email.callback_password'));

        $I->sendPOST($route, json_encode($payload));
        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals('Malformed request');
    }

    public function checkMalformedJson(\TestSymfonyGuy $I)
    {
        $I->wantTo('send malformed Email API response');
        $this->mockServices($I, false, true);
        $this->current = __FUNCTION__;
        $payload = json_decode($this->apiResponse, true);
        unset($payload['userData']);

        $route = $I->grabService('router')->generate('aw_account_history_callback');

        $I->haveHttpHeader("PHP_AUTH_USER", 'awardwallet');
        $I->haveHttpHeader("PHP_AUTH_PW", $I->getContainer()->getParameter('email.callback_password'));

        $I->sendPOST($route, json_encode($payload));
        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals('Malformed request');
    }

    public function checkCallbackAccessDenied(\TestSymfonyGuy $I)
    {
        $this->mockServices($I);
        $this->current = __FUNCTION__;
        $route = $I->grabService('router')->generate('aw_account_history_callback');
        $I->sendPOST($route, $this->apiResponse);
        $I->seeResponseCodeIs(403);
        $I->seeResponseEquals('Access denied');
    }

    public function checkUploadInvalidFormat(\TestSymfonyGuy $I)
    {
        $I->wantTo('upload not supported file format');
        $this->mockServices($I, false, true);
        $this->current = __FUNCTION__;

        $page = $I->grabService('router')->generate('aw_account_v2_history_view', ['accountId' => $this->deltaId]);
        $I->amOnPage($page . "?_switch_user=" . $this->username);

        $I->saveCsrfToken();

        $route = $I->getContainer()->get('router')->generate('aw_account_history_upload', ['accountId' => $this->deltaId]);
        $I->sendPOST($route, [], [
            'historyFile' => [
                'name' => 'delta.jpg',
                'type' => 'application/jpg',
                'error' => UPLOAD_ERR_OK,
                'size' => filesize(__DIR__ . '/../../_data/AccountHistory/delta.pdf'),
                'tmp_name' => __DIR__ . '/../../_data/AccountHistory/delta.pdf',
            ],
        ]);

        $res = $I->grabDataFromJsonResponse();
        \PHPUnit_Framework_Assert::assertFalse($res['success']);
    }

    private function mockServices(\TestSymfonyGuy $I, bool $emailApiMockCallStub = false, bool $loggerMockLogStub = false)
    {
        $accountId = &$this->deltaId;
        $current = &$this->current;
        $emailApiMockParams = [];

        if ($emailApiMockCallStub) {
            $emailApiMockParams['call'] = Stub::exactly(1, function ($name, $isPost, $data) use (&$accountId) {
                $userData = json_decode($data['userData']);

                \PHPUnit_Framework_Assert::assertEquals(EmailApiHistoryParser::METHOD_PARSE_EMAIL, $name);
                \PHPUnit_Framework_Assert::assertEquals($accountId, $userData->accountId);

                return [
                    'status' => 'queued',
                ];
            });
        }

        /** @var Api $emailApiMock */
        $emailApiMock = $I->stubMake(Api::class, $emailApiMockParams);

        $loggerMockParams = [];

        if ($loggerMockLogStub) {
            $loggerMockParams['log'] = Stub::atLeastOnce(function ($level, $message) use (&$current) {
                $errorMessages = [
                    "checkInvalidProvider" => "Account not found or incorrect login/provider",
                    "checkUploadInvalidFormat" => "Invalid API request",
                ];

                if ($level == 'error') {
                    \PHPUnit_Framework_Assert::assertEquals($errorMessages[$current], $message);
                }

                return true;
            });
        }
        /** @var Logger $loggerMock */
        $loggerMock = $I->stubMake(Logger::class, $loggerMockParams);
        $container = $I->getContainer();
        $emailApiHistoryParser = new EmailApiHistoryParser(
            $emailApiMock,
            $container->get('aw.globals'),
            $container->get('router'),
            $loggerMock,
            $container->get('doctrine.orm.entity_manager'),
            $container->get('aw.loyalty.account_saving.history'),
            $container->get('jms_serializer'),
            $container->getParameter('email.api_http_auth')
        );

        $I->mockService('aw.loyalty_api.email_history.parser', $emailApiHistoryParser);
    }
}
