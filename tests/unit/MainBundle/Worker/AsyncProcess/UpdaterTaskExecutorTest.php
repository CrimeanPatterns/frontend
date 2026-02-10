<?php

namespace AwardWallet\Tests\Unit\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\AggregateErrorReporter;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Updater\UpdaterSessionManager;
use AwardWallet\MainBundle\Updater\UpdaterSessionStorage;
use AwardWallet\MainBundle\Updater\UserMessagesHandlerResult;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\TaskNeedsRetryException;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterAccountTickTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterSessionTickTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterTaskExecutor;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\minutes;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterTaskExecutor
 */
class UpdaterTaskExecutorTest extends BaseTest
{
    public function testHandleTicksByAccount()
    {
        $accountId = 100500;
        $sessionIdsList =
            it(\range(1, 12))
            ->map(fn (int $i) => "session{$i}")
            ->toArray();
        $updaterSessionStorage = $this->prophesize(UpdaterSessionStorage::class);
        $updaterSessionStorage
            ->loadSessionsMapByAccount(1)
            ->willReturn(
                it($sessionIdsList)
                ->flatMap(fn (string $sessionId) => yield $sessionId => minutes(40))
                ->toArrayWithKeys()
            );
        $updaterSessionStorage
            ->loadSessionMapByUser(2)
            ->willReturn([]);
        $accountRepository = $this->prophesize(AccountRepository::class);
        $accountRepository
            ->find($accountId)
            ->willReturn(
                new class() extends Account {
                    public function getId()
                    {
                        return 1;
                    }

                    public function getUser(): ?Usr
                    {
                        return new class() extends Usr {
                            public function getId(): ?int
                            {
                                return 2;
                            }
                        };
                    }
                }
            );
        $process = $this->prophesize(Process::class);

        foreach ($sessionIdsList as $sessionId) {
            $process
                ->execute(
                    Argument::that(fn (UpdaterSessionTickTask $task) =>
                        ($task->sessionKey === $sessionId)
                        && ($task->retry === 0)
                    ),
                    Argument::cetera()
                )
                ->willReturn(new Response())
                ->shouldBeCalled();
        }

        $executor = new UpdaterTaskExecutor(
            $process->reveal(),
            $this->prophesize(UpdaterSessionManager::class)->reveal(),
            $updaterSessionStorage->reveal(),
            $accountRepository->reveal(),
            new SafeExecutorFactory(new AggregateErrorReporter($this->prophesize(LoggerInterface::class)->reveal()), true),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );
        $executor->execute(new UpdaterAccountTickTask($accountId));
    }

    public function testHandleTicksByAccountFailed(): void
    {
        $this->expectException(TaskNeedsRetryException::class);
        $accountId = 100500;
        $updaterSessionStorage = $this->prophesize(UpdaterSessionStorage::class);
        $updaterSessionStorage
            ->loadSessionsMapByAccount(Argument::cetera())
            ->willThrow(new \RuntimeException('Memcached failed'));
        $accountRepository = $this->prophesize(AccountRepository::class);
        $accountRepository
            ->find($accountId)
            ->willReturn(new Account());
        $safeExecutorFactory = new SafeExecutorFactory(new AggregateErrorReporter($this->prophesize(LoggerInterface::class)->reveal()), false);
        $executor = new UpdaterTaskExecutor(
            $this->prophesize(Process::class)->reveal(),
            $this->prophesize(UpdaterSessionManager::class)->reveal(),
            $updaterSessionStorage->reveal(),
            $accountRepository->reveal(),
            $safeExecutorFactory,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );
        $executor->execute(new UpdaterAccountTickTask($accountId));
    }

    /**
     * @covers ::execute
     */
    public function testSessionTick(): void
    {
        $messRes = new UserMessagesHandlerResult();
        $messRes->addAccounts = [1, 2];
        $task = new UpdaterSessionTickTask('abcdeefg', $messRes, '{}');
        $updaterSessionManagerMock = $this->prophesize(UpdaterSessionManager::class);
        $updaterSessionManagerMock
            ->synchronizedTick(
                $task->sessionKey,
                $messRes,
                $task->serializedHttpRequest
            )
            ->willReturn(true)
            ->shouldBeCalledOnce();
        /** @var UpdaterTaskExecutor $executor */
        $executor = $this->makeProphesized(UpdaterTaskExecutor::class, [
            '$updaterSessionManager' => $updaterSessionManagerMock->reveal(),
            '$safeExecutorFactory' => new SafeExecutorFactory(new AggregateErrorReporter($this->prophesize(LoggerInterface::class)->reveal()), false),
        ]);
        $executor->execute($task);
    }

    /**
     * @covers ::execute
     */
    public function testLockFailedSession(): void
    {
        $this->expectException(TaskNeedsRetryException::class);
        $messRes = new UserMessagesHandlerResult();
        $messRes->addAccounts = [1, 2];
        $task = new UpdaterSessionTickTask('abcdeefg', $messRes, '{}');
        $updaterSessionManagerMock = $this->prophesize(UpdaterSessionManager::class);
        $updaterSessionManagerMock
            ->synchronizedTick(
                $task->sessionKey,
                $messRes,
                $task->serializedHttpRequest
            )
            ->willReturn(false)
            ->shouldBeCalledOnce();
        /** @var UpdaterTaskExecutor $executor */
        $executor = $this->makeProphesized(UpdaterTaskExecutor::class, [
            '$updaterSessionManager' => $updaterSessionManagerMock->reveal(),
            '$safeExecutorFactory' => new SafeExecutorFactory(new AggregateErrorReporter($this->prophesize(LoggerInterface::class)->reveal()), false),
        ]);
        $executor->execute($task);
    }
}
