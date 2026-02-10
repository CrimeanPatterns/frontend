<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\OneTimeCode;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Event\LoyaltyPrepareAccountRequestEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\AccountTracker;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\OtcCache;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\OTCProcessor;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Serializer;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Router;
use TestSymfonyGuy;

class AccountOTCAnswerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    private $otcQuestion;
    private $otcAnswer = 12345;

    private ?Router $router;
    private ?Serializer $jms;
    private ?EntityManager $entityManager;
    private ?\Memcached $memcached;
    private ?OtcCache $otcCache;
    private ?OTCProcessor $otcProcessor;

    public function _before(\TestSymfonyGuy $I)
    {
        $loyaltyCommunicatorMock = $I->stubMake(ApiCommunicator::class, [
            'CheckAccount' => Stub::exactly(1, function ($request) {
                return null;
            }),
        ]);
        $I->mockService(ApiCommunicator::class, $loyaltyCommunicatorMock);

        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->jms = $I->grabService('jms_serializer');
        $this->entityManager = $I->grabService('doctrine')->getManager();
        $this->memcached = $I->grabService(\Memcached::class);
        $this->otcCache = $I->grabService(OtcCache::class);
        $this->otcProcessor = $I->grabService(OTCProcessor::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $I->verifyMocks();
        parent::_after($I);
    }

    public function testFullUpdate(\TestSymfonyGuy $I): void
    {
        /** @var Usr $user */
        /** @var Account $account */
        [$user, $account] = $this->prepare($I);

        $this->loyaltyCallback($I, $account);

        $I->haveInDatabase('Answer', [
            'AccountID' => $account->getId(),
            'Question' => $this->otcQuestion,
            'Answer' => $this->otcAnswer,
            'CreateDate' => date('Y-m-d H:i:s'),
            'Valid' => 1,
        ]);

        $this->otcProcessorProcess($user, $account);

        $this->emailCallback($I, $user, $account);

        $this->updateAccount($I, $account);

        $this->entityManager->refresh($account);
        $I->assertEquals(ACCOUNT_CHECKED, $account->getErrorcode());
        $I->assertEmpty($account->getErrormessage());
    }

    /*
    public function testMissingWaitOTC(TestSymfonyGuy $I): void
    {
        [$user, $account] = $this->prepare($I);

        $this->loyaltyCallback($I, $account);

        $I->haveInDatabase('Answer', [
            'AccountID' => $account->getId(),
            'Question' => $this->otcQuestion,
            'Answer' => $this->otcAnswer,
            'CreateDate' => date('Y-m-d H:i:s'),
            'Valid' => 1,
        ]);

        $this->otcProcessorProcess($user, $account);

        $this->emailCallback($I, $user, $account);
    }

    public function testTimeout(TestSymfonyGuy $I): void
    {
    }
    */

    private function prepare(\TestSymfonyGuy $I): array
    {
        $this->otcQuestion = \AwardWallet\Engine\testprovider\QuestionAnalyzer::getEmailOtcQuestion();

        /** @var Usr $user */
        $user = $this->entityManager->getRepository(Usr::class)->find(
            $I->createAwUser(null, null, [
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'ValidMailboxesCount' => 1,
            ], true)
        );
        /** @var Account $account */
        $account = $this->entityManager->getRepository(Account::class)->find(
            $I->createAwAccount($user->getId(), Provider::TEST_PROVIDER_ID, 'Checker.OtcQuestion')
        );

        $this->loginUser($I, $user);
        $I->amOnPage($this->router->generate('aw_account_list'));
        $I->saveCsrfToken();

        return [$user, $account];
    }

    private function loyaltyCallback(\TestSymfonyGuy $I, Account $account): void
    {
        $jsonAccountResponseData = json_decode(
            file_get_contents(__DIR__ . '/../../../../_data/loyaltyCheckAccountResponse.json'),
            true
        );
        $jsonAccountResponseData['state'] = ACCOUNT_QUESTION;
        $jsonAccountResponseData['question'] = $this->otcQuestion;
        $jsonAccountResponseData['userData'] = $this->jms->serialize([
            'accountId' => $account->getId(),
            'priority' => Converter::BACKGROUND_CHECK_REQUEST_PRIORITY_MEDIUM,
        ], 'json');

        /** @var CheckAccountResponse $response */
        $response = $this->jms->deserialize(
            $this->jms->serialize($jsonAccountResponseData, 'json'),
            CheckAccountResponse::class, 'json'
        );

        $I->assertInstanceOf(UserData::class, $response->getUserdata());
        $callback = (new CheckAccountCallback())->setResponse([$response]);

        /** @var Converter $converter */
        $converter = $I->grabService(Converter::class);
        $result = $converter->processCallbackPackage($callback);

        $I->assertTrue($result);
        $I->seeInDatabase('Account', [
            'AccountID' => $account->getId(),
            'ErrorCode' => ACCOUNT_QUESTION,
            'Question' => $this->otcQuestion,
        ]);
    }

    private function otcProcessorProcess(Usr $user, Account $account): void
    {
        $tracker = new AccountTracker($this->otcCache, new NullLogger(), $this->jms, $this->otcProcessor);
        $request = (new CheckAccountRequest())
            ->setPriority(Converter::BACKGROUND_CHECK_REQUEST_PRIORITY_MEDIUM)
            ->setUserdata($this->jms->serialize([
                'user' => $user->getId(),
                'email' => $user->getEmail(),
                'accountId' => $account->getId(),
                'priority' => Converter::BACKGROUND_CHECK_REQUEST_PRIORITY_MEDIUM,
            ], 'json'));

        $tracker->onLoyaltyPrepareAccountRequest(new LoyaltyPrepareAccountRequestEvent($account, $request));

        $this->entityManager->refresh($account);
        $tracker->onAccountUpdated(
            new AccountUpdatedEvent($account, new CheckAccountResponse(), new ProcessingReport(), 1)
        );
    }

    private function emailCallback(\TestSymfonyGuy $I, Usr $user, Account $account): void
    {
        $jsonEmailResponse = json_decode(
            file_get_contents(__DIR__ . '/../../../../_data/emailCallback.json'),
            true
        );
        $jsonEmailResponse['oneTimeCodes'] = [$this->otcAnswer];
        $jsonEmailResponse['userData'] = $this->jms->serialize([
            'user' => $user->getId(),
            'email' => $user->getEmail(),
            'accountId' => $account->getId(),
            'priority' => Converter::BACKGROUND_CHECK_REQUEST_PRIORITY_MEDIUM,
        ], 'json');

        $I->haveHttpHeader('PHP_AUTH_USER', 'awardwallet');
        $I->haveHttpHeader('PHP_AUTH_PW', $I->getContainer()->getParameter('email.callback_password'));
        $I->sendPOST(
            $this->router->generate('aw_emailcallback_save'),
            $this->jms->serialize($jsonEmailResponse, 'json')
        );
        $I->assertJson($I->grabResponse());
        $I->seeResponseContainsJson(['status' => 'success']);
    }

    private function updateAccount(\TestSymfonyGuy $I, Account $account): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST($this->router->generate('aw_account_updater_start'), json_encode([
            'accounts' => [$account->getId()],
            'startKey' => random_int(0, 10000000),
            'source' => 'one',
        ]));
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
    }
}
