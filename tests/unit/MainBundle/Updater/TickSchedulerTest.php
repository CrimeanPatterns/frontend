<?php

namespace AwardWallet\Tests\Unit\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\RequestSerializer;
use AwardWallet\MainBundle\Updater\TickScheduler;
use AwardWallet\MainBundle\Updater\UpdaterSessionManager;
use AwardWallet\MainBundle\Updater\UserMessagesHandlerResult;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterSessionTickTask;
use AwardWallet\Tests\Unit\BaseTest;
use Duration\Duration;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\seconds;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Updater\TickScheduler
 */
class TickSchedulerTest extends BaseTest
{
    public function deadlineTicksCoalescingDataProvider(): array
    {
        return [
            [[
                'providers' => [
                    ['providerid' => 1, 'timeout' => seconds(100)],
                    ['providerid' => 2, 'timeout' => seconds(101)],
                    ['providerid' => 3, 'timeout' => seconds(105)],
                    ['providerid' => 4, 'timeout' => seconds(110)],
                    ['providerid' => 4, 'timeout' => seconds(110)],
                ],
                'accounts' => [
                    ['providerid' => 1],
                    ['providerid' => 1],
                    ['providerid' => 3],
                    ['providerid' => 4],
                    ['providerid' => 2],
                ],
                'ticks_delays' => [
                    seconds(100),
                    seconds(105),
                    seconds(110),
                ],
            ]],
        ];
    }

    /**
     * @dataProvider deadlineTicksCoalescingDataProvider
     * @covers ::scheduleDeadlineTick
     */
    public function testDeadlineTicksCoalescing(array $coalesceData): void
    {
        $providersMap =
            it($coalesceData['providers'])
            ->reindexByColumn('providerid')
            ->column('providerid')
            ->map(fn (int $providerId) => $this->createProvider($providerId))
            ->toArrayWithKeys();

        $providerToTimeoutMap =
            it($coalesceData['providers'])
            ->reindexByColumn('providerid')
            ->column('timeout')
            ->toArrayWithKeys();

        $timeoutProvider = fn (Provider $provider): Duration => $providerToTimeoutMap[$provider->getProviderid()];

        $accountStates =
            it($coalesceData['accounts'])
            ->map(fn ($data) => new AccountState(
                (new Account())->setProviderid($providersMap[$data['providerid']])
            ))
            ->toArray();

        $asyncProcessMock = $this->prophesize(Process::class);
        $sessionKey = 'abcd';

        foreach ($coalesceData['ticks_delays'] as $tickDelay) {
            $asyncProcessMock
                ->execute(
                    Argument::that(fn (UpdaterSessionTickTask $task) => $task->sessionKey === $sessionKey),
                    Argument::that(fn (int $delay) => seconds($delay)->equals($tickDelay)),
                    Argument::cetera()
                )
                ->shouldBeCalledOnce();
        }

        $tickScheduler = new TickScheduler(
            $this->prophesize(ProviderRepository::class)->reveal(),
            $asyncProcessMock->reveal(),
            $this->prophesize(RequestSerializer::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $tickScheduler->scheduleDeadlineTick($sessionKey, $accountStates, $timeoutProvider);
    }

    /**
     * @covers ::scheduleTickByHttpRequest
     */
    public function testSchedulingTickByRequest(): void
    {
        $sessionKey = 'abcdef';
        $asyncProcessMock = $this->prophesize(Process::class);
        $request = new Request();
        $serializedRequest = 'SERIALIZED_REQUEST';
        $asyncProcessMock
            ->execute(
                Argument::that(
                    function (UpdaterSessionTickTask $task) use ($serializedRequest, $sessionKey) {
                        return
                            ($sessionKey === $task->sessionKey)
                            && (\count($task->userMessagesHandlerResult->addAccounts) === UpdaterSessionManager::MAX_ADDED_ACCOUNTS_COUNT)
                            && ($task->serializedHttpRequest === $serializedRequest);
                    }
                ),
                Argument::cetera()
            )
            ->shouldBeCalledOnce();

        $requestSerializer = $this->prophesize(RequestSerializer::class);
        $requestSerializer
            ->serializeRequest(Argument::exact($request))
            ->willReturn($serializedRequest)
            ->shouldBeCalledOnce();

        $tickScheduler = new TickScheduler(
            $this->prophesize(ProviderRepository::class)->reveal(),
            $asyncProcessMock->reveal(),
            $requestSerializer->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );
        $messRes = new UserMessagesHandlerResult();
        $messRes->addAccounts = \range(1, 5000);
        $tickScheduler->scheduleTickByHttpRequest(
            $sessionKey,
            $request,
            $messRes
        );
    }

    protected function createProvider(int $id): Provider
    {
        $provider = $this->prophesize(Provider::class);
        $provider->getProviderid()->willReturn($id);

        return $provider->reveal();
    }
}
