<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Worker\AsyncProcess\EmailCallbackExecutor;
use AwardWallet\MainBundle\Worker\AsyncProcess\EmailCallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;

/**
 * @group frontend-functional
 */
class EmailCallbackControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private const API_EMAIL_URL_CALLBACK = '/api/awardwallet/email';

    private $userId;
    private ?EmailCallbackTask $task;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true);

        $I->haveHttpHeader('PHP_AUTH_USER', 'awardwallet');
        $I->haveHttpHeader('PHP_AUTH_PW', $I->grabService('service_container')->getParameter('email.callback_password'));
        $I->haveHttpHeader('Content-type', 'application/json');

        $I->mockService(Process::class, $I->stubMakeEmpty(Process::class, [
            'execute' => function (Task $task) {
                if ($task instanceof EmailCallbackTask) {
                    $this->task = $task;
                }

                return new Response(Response::STATUS_READY);
            }]));
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->task = null;
    }

    public function phoneProvider(\TestSymfonyGuy $I)
    {
        $content = file_get_contents(__DIR__ . '/../Controller/../../../_data/emailCallback.json');
        $content = str_replace('{\"user\":7', '{\"user\":' . $this->userId, $content);

        $I->sendPOST(self::API_EMAIL_URL_CALLBACK, $content);
        $this->executeAsyncTask($I);
        $I->sendGET('/m/api/login_status?_switch_user=' . $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->userId]));

        $I->amOnPage('/timeline/data');
        $I->seeResponseIsJson();
        $phoneProvider = $I->grabDataFromResponseByJsonPath('$.segments[1].details.phones')[0];
        $I->assertNotEmpty($phoneProvider);
        $I->assertSame('Enterprise', $phoneProvider['account']['provider']);
    }

    public function cardPromoTest(\TestSymfonyGuy $I)
    {
        $content = file_get_contents(__DIR__ . '/../Controller/../../../_data/emailCallback.json');
        $content = str_replace('{\"user\":7', '{\"user\":' . $this->userId, $content);

        $data = json_decode($content, true);

        $providerCode = StringHandler::getRandomCode(8);
        $providerId = $I->createAwProvider(null, $providerCode);
        $creditCardId = $I->createAwCreditCard($providerId, [
            'Patterns' => 'Citi Prestige',
            'MatchingOrder' => 0,
        ]);

        $date = new \DateTime('2016-12-31 00:00:00');
        $data['providerCode'] = $providerCode;

        $data['cardPromo'] = [
            'cardName' => 'Citi Prestige\u00ae Card',
            'cardMemberSince' => $date->format('Y'),
            'offerDetails' => [
                'applicationURL' => 'https://awardwallet.com/',
            ],
        ];

        $I->sendPOST(self::API_EMAIL_URL_CALLBACK, json_encode($data));
        $this->executeAsyncTask($I);

        // test insert new card
        $I->seeInDatabase('UserCreditCard', [
            'UserId' => $this->userId,
            // 'EarliestSeenDate' => $date->format('Y-m-d H:i:s'),
            'DetectedViaEmail' => 1,
            'CreditCardID' => $creditCardId,
        ]);

        // test update EarliestSeenDate existing card
        $I->updateInDatabase(
            'UserCreditCard',
            ['EarliestSeenDate' => date('Y-m-d H:i:s')],
            ['UserId' => $this->userId]
        );
        $I->sendPOST(self::API_EMAIL_URL_CALLBACK, json_encode($data));
        $this->executeAsyncTask($I);
        $I->seeInDatabase('UserCreditCard', ['UserId' => $this->userId, 'EarliestSeenDate' => $date->format('Y-m-d H:i:s')]);

        // test don't change exists card date
        $existDate = '2010-01-01 00:00:00';
        $I->updateInDatabase(
            'UserCreditCard',
            ['EarliestSeenDate' => $existDate],
            ['UserId' => $this->userId]
        );
        $I->sendPOST(self::API_EMAIL_URL_CALLBACK, json_encode($data));
        $this->executeAsyncTask($I);
        $I->seeInDatabase('UserCreditCard', ['UserId' => $this->userId, 'EarliestSeenDate' => $existDate]);
    }

    private function executeAsyncTask(\TestSymfonyGuy $I)
    {
        /** @var EmailCallbackExecutor $executor */
        $executor = $I->grabService(EmailCallbackExecutor::class);
        $executor->execute($this->task);
    }
}
