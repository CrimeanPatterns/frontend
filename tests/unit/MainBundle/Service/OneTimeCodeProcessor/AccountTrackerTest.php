<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Event\LoyaltyPrepareAccountRequestEvent;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\Resources\Answer;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\AccountTracker;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\OtcCache;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\OTCProcessor;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class AccountTrackerTest extends BaseContainerTest
{
    /** @var Account */
    private $account;

    public function _before()
    {
        parent::_before();
        $userId = $this->aw->createAwUser();
        $id = $this->aw->createAwAccount($userId, 'testprovider', 'login');
        $this->account = $this->em->getRepository(Account::class)->find($id);
    }

    public function _after()
    {
        parent::_after();
        $this->account = null;
    }

    public function testSetCache()
    {
        $cache = $this->getMockBuilder(OtcCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())
            ->method('setCheck')
            ->with($this->account->getId());
        $cache->expects($this->once())
            ->method('clearStop')
            ->with($this->account->getUser()->getId(), $this->account->getProviderid()->getCode());
        $processor = $this->getMockBuilder(OTCProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tracker = new AccountTracker(
            $cache,
            new NullLogger(),
            $processor
        );
        $request = new CheckAccountRequest();
        $request->setAnswers([new Answer('question', 'answer')]);
        $request->setUserdata(
            (new UserData($this->account->getId()))
                ->setPriority(3)
                ->setSource(UpdaterEngineInterface::SOURCE_DESKTOP)
                ->setCheckIts(true)
        );
        $request->setPriority(3);
        $tracker->onLoyaltyPrepareAccountRequest(new LoyaltyPrepareAccountRequestEvent($this->account, $request));
    }

    public function testOnUpdate()
    {
        $cache = $this->getMockBuilder(OtcCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())
            ->method('setUpdate')
            ->with($this->account->getId());
        $processor = $this->getMockBuilder(OTCProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processor->expects($this->once())
            ->method('process')
            ->with($this->account);
        $tracker = new AccountTracker(
            $cache,
            new NullLogger(),
            $processor
        );
        $request = new CheckAccountRequest();
        $request->setAnswers([new Answer('question', 'answer')]);
        $request->setUserdata(
            (new UserData($this->account->getId()))
                ->setPriority(3)
                ->setSource(UpdaterEngineInterface::SOURCE_DESKTOP)
                ->setCheckIts(true)
        );
        $request->setPriority(3);
        $tracker->onAccountUpdated(new AccountUpdatedEvent($this->account, new CheckAccountResponse(), new ProcessingReport(), 1));
    }
}
