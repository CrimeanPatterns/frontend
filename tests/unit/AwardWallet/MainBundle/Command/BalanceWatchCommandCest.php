<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Command;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchCommand;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchExecutor;
use AwardWallet\MainBundle\Service\BalanceWatch\Model\BalanceWatchTask;
use AwardWallet\MainBundle\Service\BalanceWatch\Stopper;
use AwardWallet\MainBundle\Service\BalanceWatch\Timeout;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Storage;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use AwardWallet\MainBundle\Worker\AsyncProcess\Worker;
use AwardWallet\MainBundle\Worker\ProcessControlWrapper;
use Codeception\Example;
use Codeception\Util\Stub;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * @group frontend-unit
 */
class BalanceWatchCommandCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider updateDataProvider
     */
    public function sendToUpdate(\TestSymfonyGuy $I, Example $data)
    {
        $userId = $I->createAwUser(null, null, [], true);

        $accountId = $I->createAwAccount($userId, "testprovider", "balance.random", null, [
            "BalanceWatchStartDate" => date("Y-m-d H:i:s", strtotime($data["BalanceWatchStartDate"])),
            "UpdateDate" => date("Y-m-d H:i:s", strtotime($data["UpdateDate"])),
            "Disabled" => $data["Disabled"],
        ]);

        $apiCommunicator = $I->stubMakeEmpty(ApiCommunicator::class, [
            'CheckAccount' => $data['ExpectedUpdate'] ? Stub::once() : Stub::never(),
        ]);

        $bwTimeout = $I->stubMakeEmpty(Timeout::class, [
            'getTimeoutSeconds' => function () {
                return 86400 * 3;
            },
        ]);

        $stopper = $I->stubMakeEmpty(Stopper::class, [
            'stopBalanceWatch' => function () use ($data, $I) {
                $data['ExpectedStop'] ? Stub::once() : Stub::never();

                return $I->getContainer()->get(Stopper::class);
            },
        ]);

        $command = new BalanceWatchCommand(
            $I->stubMakeEmpty(LoggerInterface::class),
            $I->stubMakeEmpty(LoggerInterface::class),
            $I->grabService("doctrine.orm.default_entity_manager"),
            $apiCommunicator,
            $I->grabService(Converter::class),
            $bwTimeout,
            $I->grabService(Process::class),
            $stopper
        );

        $command->run($I->stubMakeEmpty(InputInterface::class, ['getOption' => Stub::atLeastOnce(function () use ($accountId) { return $accountId; })]), $I->stubMakeEmpty(OutputInterface::class));

        $sentToUpdateDate = $I->grabFromDatabase("Account", "SentToUpdateDate", ["AccountID" => $accountId]);

        if ($data['ExpectedUpdate']) {
            $I->assertNotEmpty($sentToUpdateDate);
        } else {
            $I->assertEmpty($sentToUpdateDate);
        }
    }

    public function balanceWatchTask(\TestSymfonyGuy $I)
    {
        $container = new Container();
        $storage = $I->stubMakeEmpty(Storage::class, [
            'setResponse' => function (Task $task, Response $response) {
            },
            'getResponse' => function () {
                $response = new Response();
                $response->status = Response::STATUS_QUEUED;

                return $response;
            },
        ]);
        $worker = new Worker(
            $container,
            $I->stubMakeEmpty(Process::class),
            $I->stubMakeEmpty(Logger::class),
            $storage,
            $I->stubMakeEmpty(AppProcessor::class),
            $I->stubMakeEmpty(ProcessControlWrapper::class),
            $I->stubMakeEmpty(EntityManagerInterface::class),
            $I->stubMakeEmpty(Connection::class)
        );

        $userId = $I->createAwUser(null, null, [], true);
        $accountId = $I->createAwAccount($userId, 'testprovider', 'balance.random', null, [
            'BalanceWatchStartDate' => date('Y-m-d H:i:s', time() + 10 * 60),
            'UpdateDate' => date('Y-m-d H:i:s', time()),
        ]);

        $executorMock = $I->stubMakeEmpty(ExecutorInterface::class, [
            'execute' => Stub::once(function (BalanceWatchTask $task, $delay = null) use ($I, $accountId) {
                $I->assertInstanceOf(BalanceWatchTask::class, $task);
                $I->assertEquals($accountId, $task->accountId);

                return new Response();
            }),
        ]);
        $container->set(BalanceWatchExecutor::class, $executorMock);

        $task = new BalanceWatchTask($accountId, StringUtils::getRandomCode(20));
        $worker->execute(new AMQPMessage(serialize($task)));

        $executorMock->__phpunit_getInvocationHandler()->verify();

        $container = null;
        $worker = null;
    }

    private function updateDataProvider()
    {
        return [
            [
                'UpdateDate' => '2001-01-01',
                'BalanceWatchStartDate' => "-12 hour",
                'Disabled' => 0,
                'ExpectedUpdate' => true,
                'ExpectedStop' => false,
            ],
            [
                'UpdateDate' => '2001-01-01',
                'BalanceWatchStartDate' => "-169 hour",
                'Disabled' => 0,
                'ExpectedUpdate' => false,
                'ExpectedStop' => true,
            ],
            [
                'UpdateDate' => '2001-01-01',
                'BalanceWatchStartDate' => "-1 hour",
                'Disabled' => 1,
                'ExpectedUpdate' => false,
                'ExpectedStop' => true,
            ],
            [
                'UpdateDate' => '2001-01-01',
                'BalanceWatchStartDate' => "+5 minute",
                'Disabled' => 0,
                'ExpectedUpdate' => true,
                'ExpectedStop' => false,
            ],
            [
                'UpdateDate' => date("Y-m-d H:i:s"),
                'BalanceWatchStartDate' => "-12 hour",
                'Disabled' => 0,
                'ExpectedUpdate' => true,
                'ExpectedStop' => false,
            ],
            [
                'UpdateDate' => date("Y-m-d H:i:s", strtotime("-6 hour")),
                'BalanceWatchStartDate' => "-12 hour",
                'Disabled' => 0,
                'ExpectedUpdate' => true,
                'ExpectedStop' => false,
            ],
            [
                'UpdateDate' => date("Y-m-d H:i:s", strtotime("-10 minute")),
                'BalanceWatchStartDate' => "-12 hour",
                'Disabled' => 0,
                'ExpectedUpdate' => true,
                'ExpectedStop' => false,
            ],
            [
                'UpdateDate' => date("Y-m-d H:i:s"),
                'BalanceWatchStartDate' => "-12 hour",
                'Disabled' => 0,
                'ExpectedUpdate' => true,
                'ExpectedStop' => false,
            ],
        ];
    }
}
