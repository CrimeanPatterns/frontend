<?php

namespace AwardWallet\Tests\Unit\AsyncProcess;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\SqlTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Storage;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use AwardWallet\MainBundle\Worker\AsyncProcess\Worker;
use AwardWallet\MainBundle\Worker\ProcessControlWrapper;
use Codeception\Util\Stub;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @group frontend-unit
 */
class WorkerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Worker
     */
    private $worker;

    public function _before(\CodeGuy $I)
    {
        $this->container = new Container();

        $storage = $I->stubMakeEmpty(Storage::class, [
            "setResponse" => function (Task $task, Response $response) {
            },
            'getResponse' => Stub::atLeastOnce(function () {
                $response = new Response();
                $response->status = Response::STATUS_QUEUED;

                return $response;
            }),
        ]);

        $this->worker = new Worker(
            $this->container,
            $I->stubMakeEmpty(Process::class),
            $I->stubMakeEmpty(Logger::class),
            $storage,
            $I->stubMakeEmpty(AppProcessor::class),
            $I->stubMakeEmpty(ProcessControlWrapper::class),
            $I->stubMakeEmpty(EntityManagerInterface::class),
            $I->stubMakeEmpty(Connection::class)
        );
    }

    public function _after(\CodeGuy $I)
    {
        $this->container = null;
        $this->worker = null;
    }

    public function testExecutorTask(\CodeGuy $I)
    {
        $executorMock = $I->stubMakeEmpty(ExecutorInterface::class, [
            "execute" => Stub::once(function (SqlTask $task, $delay = null) use ($I) {
                $I->assertInstanceOf(SqlTask::class, $task);
                $I->assertEquals("some sql", $task->sql);

                return new Response();
            }),
        ]);
        $this->container->set("aw.async.executor.sql", $executorMock);

        $task = new SqlTask("some sql", [], "someId");
        $this->worker->execute(new AMQPMessage(serialize($task)));
        $executorMock->__phpunit_getInvocationHandler()->verify();
    }

    public function testServiceTask(\CodeGuy $I)
    {
        $serviceMock = $I->stubMakeEmpty(LoggerInterface::class /* no matter what, but it does not support ExecutorInterface */ , [
            "info" => Stub::once(function ($message, array $context = []) use ($I) {
                $I->assertEquals("message1", $message);
                $I->assertEquals(["context1", "context2"], $context);

                return new Response();
            }),
        ]);
        $this->container->set("aw.test_service", $serviceMock);

        $task = new Task("aw.test_service", "someId", "info", ["message1", ["context1", "context2"]]);
        $this->worker->execute(new AMQPMessage(serialize($task)));
        $serviceMock->__phpunit_getInvocationHandler()->verify();
    }
}
