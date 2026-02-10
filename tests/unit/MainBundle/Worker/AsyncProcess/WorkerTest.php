<?php

namespace AwardWallet\Tests\Unit\MainBundle\Worker\AsyncProcess;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Storage;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use AwardWallet\MainBundle\Worker\AsyncProcess\TaskNeedsRetryException;
use AwardWallet\MainBundle\Worker\AsyncProcess\Worker;
use AwardWallet\MainBundle\Worker\ProcessControlWrapper;
use AwardWallet\Tests\Unit\BaseTest;
use AwardWallet\Tests\Unit\MainBundle\Worker\AsyncProcess\Fixtures\TaskWithOneRetry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use PhpAmqpLib\Message\AMQPMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Worker\AsyncProcess\Worker
 */
class WorkerTest extends BaseTest
{
    /**
     * @covers ::execute
     */
    public function testFailWhileExecutingTaskStopWorkerOnNextQueueMessage(): void
    {
        $services = [];

        $process = $this->prophesize(Process::class)->reveal();

        $loggerMock = $this->createLoggerMock();
        $loggerMock
            ->critical(
                Argument::containingString('RuntimeException: exception while execute'),
                Argument::cetera()
            )
            ->shouldBeCalledOnce();
        $executorMock = $this->prophesize(ExecutorInterface::class);
        $executorMock
            ->execute(Argument::cetera())
            ->will(function () {
                throw new \RuntimeException('exception while execute');
            })
            ->shouldBeCalledOnce();

        $services['my.executor.id'] = $executorMock->reveal();
        $processControlMock = $this->prophesize(ProcessControlWrapper::class);

        $task = new Task('my.executor.id', 'abc');

        $worker = new Worker($this->prepareContainer($services), $process, $loggerMock->reveal(), $this->createStorageMock(), $this->prophesize(AppProcessor::class)->reveal(), $processControlMock->reveal(), $this->prophesize(EntityManagerInterface::class)->reveal(), $this->prophesize(Connection::class)->reveal());
        $this->assertTrue($worker->execute(new AMQPMessage(@\serialize($task))));

        $processControlMock
            ->exit(1)
            ->shouldBeCalledOnce(1);

        $this->assertTrue($worker->execute(new AMQPMessage(@\serialize($task))));
    }

    /**
     * @covers ::execute
     */
    public function testRetryShouldBeTriggeredByRetryException(): void
    {
        $services = [];

        $loggerMock = $this->createLoggerMock();

        $executorMock = $this->prophesize(ExecutorInterface::class);
        $executorMock
            ->execute(Argument::cetera())
            ->will(function () use ($loggerMock) {
                $loggerMock
                    ->info(
                        Argument::containingString('retrying task')
                    )
                    ->shouldBeCalled();

                $loggerMock
                    ->info(
                        Argument::containingString('saving response'),
                        Argument::cetera()
                    )
                    ->shouldBeCalled();

                throw new TaskNeedsRetryException(60);
            })
            ->shouldBeCalledOnce();
        $services['my.executor.id'] = $executorMock->reveal();

        $task = new TaskWithOneRetry('my.executor.id', 'abc');
        $asyncProcessMock = $this->prophesize(Process::class);
        $asyncProcessMock
            ->execute(
                Argument::that(function ($actualTask) {
                    return $actualTask->retry === 1;
                }),
                Argument::is(60),
                Argument::is(true),
                Argument::cetera()
            )
            ->shouldBeCalledOnce();

        $worker = new Worker($this->prepareContainer($services), $asyncProcessMock->reveal(), $loggerMock->reveal(), $this->createStorageMock(), $this->prophesize(AppProcessor::class)->reveal(), $this->prophesize(ProcessControlWrapper::class)->reveal(), $this->prophesize(EntityManagerInterface::class)->reveal(), $this->prophesize(Connection::class)->reveal());
        $this->assertTrue($worker->execute(new AMQPMessage(@\serialize($task))));
    }

    /**
     * @covers ::execute
     */
    public function testRetryLimitsExceededByUserExceptions(): void
    {
        $services = [];

        $loggerMock = $this->createLoggerMock();

        $executorMock = $this->prophesize(ExecutorInterface::class);
        $executorMock
            ->execute(Argument::cetera())
            ->will(function () use ($loggerMock) {
                $loggerMock
                    ->error(
                        Argument::containingString('retries (max: 1) exceeded for task')
                    )
                    ->shouldBeCalledOnce();

                $loggerMock
                    ->info(
                        Argument::containingString('saving response'),
                        Argument::cetera()
                    )
                    ->shouldBeCalled();

                throw new TaskNeedsRetryException(60);
            })
            ->shouldBeCalledOnce();
        $services['my.executor.id'] = $executorMock->reveal();

        $task = new TaskWithOneRetry('my.executor.id', 'abc');
        $task->retry = 1;
        $asyncProcessMock = $this->prophesize(Process::class);
        $asyncProcessMock
            ->execute(Argument::cetera())
            ->shouldNotBeCalled();

        $worker = new Worker($this->prepareContainer($services), $asyncProcessMock->reveal(), $loggerMock->reveal(), $this->createStorageMock(), $this->prophesize(AppProcessor::class)->reveal(), $this->prophesize(ProcessControlWrapper::class)->reveal(), $this->prophesize(EntityManagerInterface::class)->reveal(), $this->prophesize(Connection::class)->reveal());
        $this->assertTrue($worker->execute(new AMQPMessage(@\serialize($task))));
    }

    /**
     * @covers ::execute
     */
    public function testRetryLimitsExceededByExecutionCount(): void
    {
        $services = [];

        $loggerMock = $this->createLoggerMock();
        $loggerMock
            ->error(
                Argument::containingString('retries (max: 0) exceeded for task')
            )
            ->shouldBeCalledOnce();

        $executorMock = $this->prophesize(ExecutorInterface::class);
        $executorMock
            ->execute(Argument::cetera())
            ->shouldNotBeCalled();
        $services['my.executor.id'] = $executorMock->reveal();

        $task = new Task('my.executor.id', 'abc');
        $task->retry = 1;
        $asyncProcessMock = $this->prophesize(Process::class);
        $asyncProcessMock
            ->execute(Argument::cetera())
            ->shouldNotBeCalled();
        $process = $asyncProcessMock->reveal();

        $response = new Response();
        $response->status = Response::STATUS_QUEUED;
        $response->executionCount = 1;

        $storage = $this->prophesize(Storage::class);
        $storage
            ->getResponse(Argument::type(Task::class))
            ->willReturn($response)
            ->shouldBeCalledOnce();

        $storage
            ->setResponse(Argument::type(Task::class), Argument::type(Response::class))
            ->shouldBeCalledOnce();

        $worker = new Worker($this->prepareContainer($services), $process, $loggerMock->reveal(), $storage->reveal(), $this->prophesize(AppProcessor::class)->reveal(), $this->prophesize(ProcessControlWrapper::class)->reveal(), $this->prophesize(EntityManagerInterface::class)->reveal(), $this->prophesize(Connection::class)->reveal());
        $this->assertTrue($worker->execute(new AMQPMessage(@\serialize($task))));
    }

    protected function prepareContainer(array $services): ServiceLocator
    {
        $container = $this->prophesize(ServiceLocator::class);

        foreach ($services as $id => $object) {
            $container->get($id)->willReturn($object);
        }

        return $container->reveal();
    }

    /**
     * @return Logger|ObjectProphecy
     */
    private function createLoggerMock()
    {
        return $this->prophesizeExtended(Logger::class)->prophesizeRemainingMethods();
    }

    private function createStorageMock(): Storage
    {
        $task = new TaskWithOneRetry('my.executor.id', 'abc');
        $task->retry = 1;
        $asyncProcessMock = $this->prophesize(Process::class);
        $asyncProcessMock
            ->execute(Argument::cetera())
            ->shouldNotBeCalled();
        $services[ProcessControlWrapper::class] = $this->prophesize(ProcessControlWrapper::class)->reveal();

        $response = new Response();
        $response->status = Response::STATUS_QUEUED;

        $storage = $this->prophesize(Storage::class);
        $storage
            ->getResponse(Argument::type(Task::class))
            ->willReturn($response)
            ->shouldBeCalled();

        $storage
            ->setResponse(Argument::type(Task::class), Argument::type(Response::class))
            ->shouldBeCalled();

        return $storage->reveal();
    }
}
