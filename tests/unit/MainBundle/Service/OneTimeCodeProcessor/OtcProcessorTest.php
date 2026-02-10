<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Event\LoyaltyPrepareAccountRequestEvent;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\Answer;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\AccountTracker;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\OtcCache;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\OTCProcessor;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\WaitTracker;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Clock\ClockNative;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @group frontend-unit
 */
class OtcProcessorTest extends BaseContainerTest
{
    /** @var Account */
    private $account;

    public function _before()
    {
        parent::_before();
        $userId = $this->aw->createAwUser(null, null, ['ValidMailboxesCount' => 1]);
        $id = $this->aw->createAwAccount($userId, 'testprovider', 'login', '', ['ErrorCode' => 10, 'Question' => 'Enter code that was sent to email test@test.test']);
        $this->db->haveInDatabase('Answer', ['AccountID' => $id, 'Question' => 'Enter code that was sent to email test@test.test', 'Answer' => 'old answer', 'CreateDate' => date('Y-m-d H:i:s', strtotime('-10 min'))]);
        $this->account = $this->em->getRepository(Account::class)->find($id);
    }

    public function testProcessor()
    {
        $cache = $this->getMockBuilder(OtcCache::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUpdate', 'getCheck', 'getAutoCheck', 'getProviderOtc', 'hasCodeCollision', 'setAutoCheck', 'dropProviderOtc'])
            ->getMock();
        $cache->expects($this->exactly(1))
            ->method('getUpdate')
            ->with($this->account->getId())
            ->willReturn(strtotime('-5 min'));
        $cache->expects($this->once())
            ->method('getAutoCheck')
            ->with($this->account->getId())
            ->willReturn(null);
        $cache->expects($this->once())
            ->method('getCheck')
            ->with($this->account->getId())
            ->willReturn(null);
        $cache->expects($this->once())
            ->method('getProviderOtc')
            ->with($this->account->getUser()->getId(), $this->account->getProviderid()->getCode())
            ->willReturn('correct answer');

        $cache->expects($this->once())
            ->method('hasCodeCollision')
            ->with($this->account->getUser()->getId(), $this->account->getProviderid()->getCode())
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('setAutoCheck')
            ->with($this->account->getId());
        $cache->expects($this->once())
            ->method('dropProviderOtc')
            ->with($this->account->getUser()->getId(), $this->account->getProviderid()->getCode());

        $converter = $this->getMockBuilder(Converter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepareCheckAccountRequest'])
            ->getMock();
        $request = new CheckAccountRequest();
        $converter->expects($this->once())
            ->method('prepareCheckAccountRequest')
            ->with($this->callback(function (Account $acc) {
                return count($acc->getAnswers()) === 1
                    && strcmp($acc->getAnswers()[0]->getQuestion(), 'Enter code that was sent to email test@test.test') === 0
                    && strcmp($acc->getAnswers()[0]->getAnswer(), 'correct answer') === 0;
            }), $this->anything(), Converter::USER_CHECK_REQUEST_PRIORITY)
            ->willReturn($request);

        $communicator = $this->getMockBuilder(ApiCommunicator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['CheckAccount'])
            ->getMock();
        $communicator->expects($this->once())
            ->method('CheckAccount')
            ->with($request);

        (new OTCProcessor(
            $cache,
            $converter,
            $communicator,
            $this->em,
            new NullLogger(),
            new EventDispatcher(),
            new ClockNative()
        ))->process($this->account);
    }

    public function testLocalPassword()
    {
        return;
        $isPasswordRequired = $this->account->getProviderid()->getPasswordrequired();
        $pass = $this->account->getPass();
        $this->account
            ->setSavepassword(SAVE_PASSWORD_LOCALLY)
            ->setPass('localPassword')
            ->getProviderid()->setPasswordrequired(true);

        $cache = $this->createMock(OtcCache::class);
        $cache->expects($this->once())
            ->method('getUpdateCache')
            ->with($this->account->getId())
            ->willReturn(strtotime('-5 min'));
        $cache->expects($this->once())
            ->method('getAnswersCache')
            ->with($this->account->getId())
            ->willReturn(['time' => strtotime('-10 min'), 'priority' => 5, 'answers' => []]);
        $cache->expects($this->once())
            ->method('isChecked')
            ->with($this->account->getId())
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('setCheckCache')
            ->with($this->account->getId());

        $cache->expects($this->once())->method('setTempLocalPassword');
        $cache->expects($this->once())->method('getTempLocalPassword')->willReturn('localPassword');
        $this->mockService(OtcCache::class, $cache);

        $converter = $this->getMockBuilder(Converter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepareCheckAccountRequest'])
            ->getMock();

        $request = new CheckAccountRequest();
        $converter->expects($this->once())
            ->method('prepareCheckAccountRequest')
            ->with($this->callback(function (Account $acc) {
                return count($acc->getAnswers()) === 1
                    && strcmp($acc->getAnswers()[0]->getQuestion(),
                        'Enter code that was sent to email test@test.test') === 0
                    && strcmp($acc->getAnswers()[0]->getAnswer(), 'correct answer') === 0;
            }), $this->anything(), Converter::USER_CHECK_REQUEST_PRIORITY)
            ->willReturn($request);

        $communicator = $this->getMockBuilder(ApiCommunicator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['CheckAccount'])
            ->getMock();
        $communicator->expects($this->once())
            ->method('CheckAccount')
            ->with($request);

        $waitTracker = $this->getMockBuilder(WaitTracker::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isWaitingOtc'])
            ->getMock();
        $waitTracker->expects($this->once())
            ->method('isWaitingOtc')
            ->with($this->account, OTCProcessor::MAX_WAIT_TIME)
            ->willReturn(true);

        $processor = new OTCProcessor(
            $cache,
            $converter,
            $communicator,
            $this->em,
            new NullLogger(),
            $waitTracker,
            new EventDispatcher()
        );
        $processor->process($this->account);

        $request->setAnswers([new Answer('question', 'answer')]);
        $request->setUserdata(
            (new UserData($this->account->getId()))
                ->setPriority(3)
                ->setSource(UpdaterEngineInterface::SOURCE_DESKTOP)
                ->setCheckIts(true)
        );
        $request->setPriority(3);

        $tracker = new AccountTracker($cache, new NullLogger(), $processor);
        $tracker->onLoyaltyPrepareAccountRequest(new LoyaltyPrepareAccountRequestEvent($this->account, $request));

        $this->account
            ->setPass($pass)
            ->getProviderid()->setPasswordrequired($isPasswordRequired);
    }
}
