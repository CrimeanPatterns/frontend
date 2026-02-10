<?php

namespace AwardWallet\Tests\Unit\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\WaitTracker;
use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;
use AwardWallet\MainBundle\Updater\Plugin\ServerCheckPlugin;
use AwardWallet\MainBundle\Updater\Plugin\WaitEmailOTCPlugin;
use AwardWallet\MainBundle\Updater\TickScheduler;
use AwardWallet\MainBundle\Updater\TimeoutResolver;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterSessionTickTask;
use AwardWallet\Tests\Unit\BaseTest;
use Clock\ClockTest;
use Doctrine\ORM\EntityManagerInterface;
use Duration\Duration;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

use function Duration\seconds;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Updater\Plugin\WaitEmailOTCPlugin
 * @group frontend-unit
 */
class WaitEmailOTCPluginTest extends BaseTest
{
    public function testSkipIsWaitingOtc()
    {
        $waitTracker = $this->prophesize(WaitTracker::class)
            ->isWaitingOtc(Argument::cetera())
            ->willReturn(false)
            ->getObjectProphecy()
            ->reveal();
        $plugin = new WaitEmailOTCPlugin(
            $this->makeRefreshingEntityManager(),
            $this->makeProphesizedMuted(TickScheduler::class),
            new ClockTest(),
            $waitTracker,
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(TimeoutResolver::class)->reveal()
        );

        [$state1, $state2] = [$this->makeState(), $this->makeState()];

        foreach ([$state1, $state2] as $state) {
            $state->pushPlugin('some_plugin');
            $state->pushPlugin(WaitEmailOTCPlugin::ID);
        }

        $plugin->tick(
            $this->getDefaultMaster(),
            [$state1, $state2]
        );

        foreach ([$state1, $state2] as $state) {
            $this->assertEquals(
                ['some_plugin'],
                $state->saveState()['plugins']
            );
        }
    }

    public function testWaitingForSecondCheck()
    {
        $waitTracker = $this->prophesize(WaitTracker::class);
        $loyaltyRequestId = \bin2hex(random_bytes(16));
        $waitTracker
            ->getNextRequestId($loyaltyRequestId)
            ->willReturn(\bin2hex(random_bytes(16)));
        $waitTracker
            ->isWaitingOtc(Argument::cetera())
            ->willReturn(true);
        $waitTracker
            ->getLastOtcCheckDate(Argument::cetera())
            ->willReturn(1);
        $timeoutResolver = $this->prophesize(TimeoutResolver::class);
        $timeoutResolver
            ->resolveForProvider(Argument::that(fn (Provider $provider) => $provider->getId() === 100500))
            ->willReturn(seconds(123))
            ->shouldBeCalled();
        $tickScheduler = $this->prophesize(TickScheduler::class);
        $tickScheduler
            ->scheduleTick(
                Argument::any(),
                Argument::that(fn (Duration $time) => $time->equals(seconds(123)))
            )
            ->shouldBeCalled();
        $plugin = new WaitEmailOTCPlugin(
            $this->makeRefreshingEntityManager(),
            $tickScheduler->reveal(),
            new ClockTest(),
            $waitTracker->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $timeoutResolver->reveal()
        );

        [$state1, $state2] = [$this->makeState(), $this->makeState()];

        /** @var AccountState $state */
        foreach ([$state1, $state2] as $state) {
            $state->pushPlugin('some_plugin');
            $state->pushPlugin(WaitEmailOTCPlugin::ID);
            $state->setSharedValue(ServerCheckPlugin::SHARED_START_TIME_KEY, seconds(0));
            $state->setSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY, $loyaltyRequestId);
        }

        $master = $this->prophesize(MasterInterface::class);
        $master
            ->getKey()
            ->willReturn('updater_key');
        $master->log(Argument::cetera())->willReturn(null);
        $plugin->tick(
            $master->reveal(),
            [$state1, $state2]
        );

        foreach ([$state1, $state2] as $state) {
            $this->assertEquals(
                [
                    'some_plugin',
                    ServerCheckPlugin::ID,
                ],
                $state->saveState()['plugins']
            );
            $this->assertArrayContainsArray(
                [
                    ServerCheckPlugin::ID => [
                        'startTime' => seconds(1),
                    ],
                ],
                \unserialize($state->saveState()['context'])
            );
        }
    }

    public function testSecondCheckWillNotBeInvoked()
    {
        $waitTracker = $this->prophesize(WaitTracker::class);
        $loyaltyRequestId = \bin2hex(random_bytes(16));
        $waitTracker
            ->getNextRequestId($loyaltyRequestId)
            ->willReturn(\bin2hex(random_bytes(16)));
        $waitTracker
            ->isWaitingOtc(Argument::cetera())
            ->willReturn(true);
        $waitTracker
            ->getLastOtcCheckDate(Argument::cetera())
            ->willReturn(2);
        $tickScheduler = $this->prophesize(TickScheduler::class);
        $tickScheduler
            ->scheduleTick(Argument::cetera())
            ->shouldNotBeCalled();
        $plugin = new WaitEmailOTCPlugin(
            $this->makeRefreshingEntityManager(),
            $tickScheduler->reveal(),
            new ClockTest(seconds(10)),
            $waitTracker->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(TimeoutResolver::class)->reveal()
        );
        [$state1, $state2] = [$this->makeState(), $this->makeState()];

        /** @var AccountState $state */
        foreach ([$state1, $state2] as $state) {
            $state->pushPlugin('some_plugin');
            $state->pushPlugin(WaitEmailOTCPlugin::ID);
            $state->setSharedValue(ServerCheckPlugin::SHARED_START_TIME_KEY, seconds(5));
            $state->setSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY, $loyaltyRequestId);
        }

        $plugin->tick(
            $this->getDefaultMaster(),
            [$state1, $state2]
        );

        foreach ([$state1, $state2] as $state) {
            $this->assertEquals(
                ['some_plugin'],
                $state->saveState()['plugins']
            );
        }
    }

    public function testFirstPolling()
    {
        $waitTracker = $this->prophesize(WaitTracker::class);
        $loyaltyRequestId = \bin2hex(random_bytes(16));
        $waitTracker
            ->getNextRequestId($loyaltyRequestId)
            ->willReturn(\bin2hex(random_bytes(16)));
        $waitTracker
            ->isWaitingOtc(Argument::cetera())
            ->willReturn(true);
        $waitTracker
            ->getLastOtcCheckDate(Argument::cetera())
            ->willReturn(null);
        $tickScheduler = $this->prophesize(TickScheduler::class);
        $tickScheduler
            ->scheduleTick(
                Argument::that(fn (UpdaterSessionTickTask $task) => $task->sessionKey === 'updater_key'),
                seconds(30)
            )
            ->shouldBeCalled();
        $plugin = new WaitEmailOTCPlugin(
            $this->makeRefreshingEntityManager(),
            $tickScheduler->reveal(),
            new ClockTest(seconds(60)),
            $waitTracker->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(TimeoutResolver::class)->reveal()
        );

        [$state1, $state2] = [$this->makeState(), $this->makeState()];

        /** @var AccountState $state */
        foreach ([$state1, $state2] as $state) {
            $state->pushPlugin('some_plugin');
            $state->pushPlugin(WaitEmailOTCPlugin::ID);
            $state->setSharedValue(ServerCheckPlugin::SHARED_START_TIME_KEY, seconds(0));
            $state->setSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY, $loyaltyRequestId);
        }

        $plugin->tick(
            $this->getDefaultMaster(),
            [$state1, $state2]
        );

        foreach ([$state1, $state2] as $state) {
            $this->assertEquals(
                [
                    'some_plugin',
                    WaitEmailOTCPlugin::ID,
                ],
                $state->saveState()['plugins']
            );
            $this->assertArrayContainsArray(
                [
                    WaitEmailOTCPlugin::ID => [
                        'otc_waiting_start_mark' => seconds(60),
                    ],
                ],
                \unserialize($state->saveState()['context'])
            );
        }
    }

    public function testSubsequentPolling()
    {
        $waitTracker = $this->prophesize(WaitTracker::class);
        $loyaltyRequestId = \bin2hex(random_bytes(16));
        $waitTracker
            ->getNextRequestId($loyaltyRequestId)
            ->willReturn(\bin2hex(random_bytes(16)));
        $waitTracker
            ->isWaitingOtc(Argument::cetera())
            ->willReturn(true);
        $waitTracker
            ->getLastOtcCheckDate(Argument::cetera())
            ->willReturn(null);
        $tickScheduler = $this->prophesize(TickScheduler::class);
        $tickScheduler
            ->scheduleTick(Argument::cetera())
            ->shouldNotBeCalled();
        $plugin = new WaitEmailOTCPlugin(
            $this->makeRefreshingEntityManager(),
            $tickScheduler->reveal(),
            new ClockTest(seconds(60)),
            $waitTracker->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(TimeoutResolver::class)->reveal()
        );

        [$state1, $state2] = [$this->makeState(), $this->makeState()];

        /** @var AccountState $state */
        foreach ([$state1, $state2] as $state) {
            $state->pushPlugin('some_plugin');
            $state->pushPlugin(WaitEmailOTCPlugin::ID, [
                'otc_waiting_start_mark' => seconds(40),
            ]);
            $state->setSharedValue(ServerCheckPlugin::SHARED_START_TIME_KEY, seconds(0));
            $state->setSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY, $loyaltyRequestId);
        }

        $plugin->tick(
            $this->getDefaultMaster(),
            [$state1, $state2]
        );

        foreach ([$state1, $state2] as $state) {
            $this->assertEquals(
                [
                    'some_plugin',
                    WaitEmailOTCPlugin::ID,
                ],
                $state->saveState()['plugins']
            );
            $this->assertArrayContainsArray(
                [
                    WaitEmailOTCPlugin::ID => [
                        'otc_waiting_start_mark' => seconds(40),
                    ],
                ],
                \unserialize($state->saveState()['context'])
            );
        }
    }

    public function testTaskFreePolling()
    {
        $waitTracker = $this->prophesize(WaitTracker::class);
        $loyaltyRequestId = \bin2hex(random_bytes(16));
        $waitTracker
            ->getNextRequestId($loyaltyRequestId)
            ->willReturn(\bin2hex(random_bytes(16)));

        $waitTracker
            ->isWaitingOtc(Argument::cetera())
            ->willReturn(true);
        $waitTracker
            ->getLastOtcCheckDate(Argument::cetera())
            ->willReturn(null);
        $tickScheduler = $this->prophesize(TickScheduler::class);
        $tickScheduler
            ->scheduleTick(Argument::cetera())
            ->shouldNotBeCalled();
        $plugin = new WaitEmailOTCPlugin(
            $this->makeRefreshingEntityManager(),
            $tickScheduler->reveal(),
            new ClockTest(seconds(57)),
            $waitTracker->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(TimeoutResolver::class)->reveal()
        );

        [$state1, $state2] = [$this->makeState(), $this->makeState()];

        /** @var AccountState $state */
        foreach ([$state1, $state2] as $state) {
            $state->pushPlugin('some_plugin');
            $state->pushPlugin(WaitEmailOTCPlugin::ID, [
                'otc_waiting_start_mark' => seconds(40),
            ]);
            $state->setSharedValue(ServerCheckPlugin::SHARED_START_TIME_KEY, seconds(0));
            $state->setSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY, $loyaltyRequestId);
        }

        $plugin->tick(
            $this->getDefaultMaster(),
            [$state1, $state2]
        );

        foreach ([$state1, $state2] as $state) {
            $this->assertEquals(
                [
                    'some_plugin',
                    WaitEmailOTCPlugin::ID,
                ],
                $state->saveState()['plugins']
            );
            $this->assertArrayContainsArray(
                [
                    WaitEmailOTCPlugin::ID => [
                        'otc_waiting_start_mark' => seconds(40),
                    ],
                ],
                \unserialize($state->saveState()['context'])
            );
        }
    }

    protected function makeRefreshingEntityManager(): EntityManagerInterface
    {
        return $this->prophesize(EntityManagerInterface::class)
            ->refresh(Argument::cetera())
            ->getObjectProphecy()
            ->reveal();
    }

    protected function makeState(int $providerId = 100500, int $accountId = 100500): AccountState
    {
        $provider = $this->prophesize(Provider::class);
        $provider->getId()->willReturn($providerId);
        $provider->getCancheck()->willReturn(true);
        $provider->getCheckinbrowser()->willReturn(false);

        $account = $this->prophesize(Account::class);
        $account->getId()->willReturn($accountId);
        $account->getAccountid()->willReturn($accountId);
        $account->getProviderid()->willReturn($provider->reveal());

        return new AccountState($account->reveal());
    }

    protected function getDefaultMaster(string $key = 'updater_key'): MasterInterface
    {
        return $this->prophesize(MasterInterface::class)
            ->getKey()
            ->willReturn($key)
            ->getObjectProphecy()
            ->reveal();
    }
}
