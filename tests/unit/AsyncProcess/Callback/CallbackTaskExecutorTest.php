<?php

namespace AwardWallet\Tests\Unit\AsyncProcess\Callback;

use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Autowire;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTaskExecutor;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Parameter;
use AwardWallet\Tests\Unit\AsyncProcess\Callback\Fixtures\Dependency1;
use AwardWallet\Tests\Unit\AsyncProcess\Callback\Fixtures\Dependency2;
use AwardWallet\Tests\Unit\AsyncProcess\Callback\Fixtures\Dependency3;
use AwardWallet\Tests\Unit\BaseContainerTest;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @group frontend-unit
 */
class CallbackTaskExecutorTest extends BaseContainerTest
{
    public function expBackoffDelayTaskDataProvider()
    {
        return [
            [null, 1000, 2, 4],
            [1000, 2000, 2, 4],
            [2000, 4000, 2, 4],
            [4000, 8000, 2, 4],
            [8000, 16000, 2, 4],
            [null, 1000, 10, 5],
        ];
    }

    /**
     * @dataProvider expBackoffDelayTaskDataProvider
     */
    public function testExpBackoffDelayTask($delay, $newDelay, $expBase, $maxExp, $willThrow = null)
    {
        $task = new CallbackTask(function (CallbackTaskExecutor $executor) use ($maxExp, $expBase) {
            $executor->expBackoffDelayTask($maxExp, null, $expBase);
        });

        $executor = $this->defineByConstructor(
            $this->container,
            $this->prophesize(ProducerInterface::class)->publish(
                Argument::that(function ($arg) {
                    return unserialize($arg) instanceof CallbackTask;
                }),
                '',
                Argument::withEntry('application_headers', ['x-delay' => ['I', $newDelay + 1]])
            )
            ->willReturn()
            ->getObjectProphecy()
            ->reveal()
        );

        $executor->execute(\unserialize(\serialize($task)), $delay);
    }

    public function testExpBackoffExceededRetries()
    {
        $this->expectException(\AwardWallet\MainBundle\Worker\AsyncProcess\Callback\TaskRetriesExceededException::class);
        $task = new CallbackTask(function (CallbackTaskExecutor $executor) {
            $executor->expBackoffDelayTask(5);
        });
        $executor = $this->defineByConstructor($this->container);
        $this->mockService(CallbackTaskExecutor::class, $executor);
        $executor->execute(\unserialize(\serialize($task)), 32000);
    }

    public function testAutowiredArguments()
    {
        $task = new CallbackTask(
            function (
                Dependency1 $dep1Autowire,
                Dependency2 $dep2Autowire,
                Dependency3 $dep3OldStyle,
                /** @Parameter("kernel.project_dir") */
                string $someParamFromContainer
            ) {
                return [
                    $dep1Autowire->testSomeVar($someParamFromContainer . '/bundles'),
                    $dep2Autowire->testSomeVar($someParamFromContainer . '/app'),
                    $dep3OldStyle->testSomeVar($someParamFromContainer . '/tests'),
                ];
            },
        );

        $executor = $this->defineByConstructor($this->container);
        $response = $executor->execute(\unserialize(\serialize($task)));
        $this->assertEquals(
            [
                '/www/awardwallet/bundles',
                '/www/awardwallet/app',
                '/www/awardwallet/tests',
            ],
            $response->data
        );
    }

    public function testAutowiredArgumentsByDefault()
    {
        $task = new CallbackTask(
            function (
                Dependency1 $dep1Autowire,
                Dependency2 $dep2Autowire,
                Dependency3 $dep3OldStyle
            ) {
                return 'callback_result';
            }
        );

        $executor = $this->defineByConstructor($this->container);
        $result = $executor->execute(\unserialize(\serialize($task)));
        $this->assertEquals('callback_result', $result->data);
    }

    public function testAutowireAnnotations()
    {
        $callable1 = /** @Autowire */ function (
            Dependency1 $dep1Autowire,
            Dependency2 $dep2Autowire,
            Dependency3 $dep3OldStyle
        ) {
            return 'callable_result_1';
        };

        $callable2 = /** @Autowire */ function (
            Dependency3 $dep3OldStyle,
            Dependency2 $dep2Autowire,
            Dependency1 $dep1Autowire
        ) {
            return 'callable_result_2';
        };

        foreach ([[$callable1, 'callable_result_1'], [$callable2, 'callable_result_2']] as [$callable, $expectedResult]) {
            $task = new CallbackTask($callable);

            $executor = $this->defineByConstructor($this->container);
            $result = $executor->execute(\unserialize(\serialize($task)));
            $this->assertEquals($expectedResult, $result->data);
        }
    }

    public function testAutowiredAndAnnotation()
    {
        $task = new CallbackTask(
            /** @Autowired */ function (
                Dependency1 $dep1Autowire,
                Dependency2 $dep2Autowire,
                Dependency3 $dep3OldStyle
            ) {
                return 'callback_result';
            }
        );

        $executor = $this->defineByConstructor($this->container);
        $result = $executor->execute(\unserialize(\serialize($task)));
        $this->assertEquals('callback_result', $result->data);
    }

    protected function defineByConstructor(
        ?ContainerInterface $container = null,
        ?ProducerInterface $delayedProducer = null,
        ?LoggerInterface $logger = null,
        $secretKey = null
    ) {
        return new CallbackTaskExecutor(
            $container ?: $this->prophesize(ContainerInterface::class)->reveal(),
            $delayedProducer ?: $this->prophesize(ProducerInterface::class)->reveal(),
            $logger ?: $this->prophesize(LoggerInterface::class)->reveal(),
            $secretKey ?? 'someSecretKey'
        );
    }
}
