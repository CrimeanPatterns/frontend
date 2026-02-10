<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals\Utils\BinaryLogger;

use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use Cocur\Slugify\SlugifyInterface;
use Codeception\Test\Unit;
use Monolog\Logger;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class BinaryLoggerFactoryTest extends Unit
{
    use ConditionDataProviderTrait;
    use ProphecyTrait;

    /**
     * @dataProvider conditionDataProvider
     */
    public function testSettingLogLevel(bool $condition): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(Argument::exact(Logger::ALERT), Argument::cetera())
            ->shouldBeCalled();
        $loggerProphecy = $loggerProphecy->reveal();

        $producer = new BinaryLoggerFactory(
            $loggerProphecy,
            $this->prophesize(SlugifyInterface::class)->reveal()
        );
        $producer->to(Logger::ALERT);
        $producer('')->is('')->on($condition);
    }

    /**
     * @dataProvider conditionDataProvider
     */
    public function testDefaultDebugLogLevel(bool $condition): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(Argument::exact(Logger::DEBUG), Argument::cetera())
            ->shouldBeCalled();
        $loggerProphecy = $loggerProphecy->reveal();
        $producer = new BinaryLoggerFactory(
            $loggerProphecy,
            $this->prophesize(SlugifyInterface::class)->reveal()
        );
        $producer('')->is('')->on($condition);
    }

    public function toLogLevelMethodsDataProvider(): array
    {
        return
            it([
                [fn (BinaryLoggerFactory $producer) => $producer->toDebug(), Logger::DEBUG],
                [fn (BinaryLoggerFactory $producer) => $producer->toInfo(), Logger::INFO],
                [fn (BinaryLoggerFactory $producer) => $producer->toNotice(), Logger::NOTICE],
                [fn (BinaryLoggerFactory $producer) => $producer->toWarning(), Logger::WARNING],
                [fn (BinaryLoggerFactory $producer) => $producer->toError(), Logger::ERROR],
                [fn (BinaryLoggerFactory $producer) => $producer->toCritical(), Logger::CRITICAL],
                [fn (BinaryLoggerFactory $producer) => $producer->toAlert(), Logger::ALERT],
                [fn (BinaryLoggerFactory $producer) => $producer->toEmergency(), Logger::EMERGENCY],
            ])
            ->product([true, false])
            ->flatMap(fn (array $pair) => yield Logger::getLevelName($pair[0][1]) . ' condition is ' . ($pair[1] ? 'true' : 'false') => [$pair[0][0], $pair[0][1], $pair[1]]
            )
            ->toArrayWithKeys();
    }

    /**
     * @dataProvider toLogLevelMethodsDataProvider
     */
    public function testToLogLevelMethods(callable $methodCaller, int $logLevel, bool $condition): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(Argument::exact($logLevel), Argument::cetera())
            ->shouldBeCalled();
        $loggerProphecy = $loggerProphecy->reveal();

        $producer = new BinaryLoggerFactory(
            $loggerProphecy,
            $this->prophesize(SlugifyInterface::class)->reveal()
        );
        $methodCaller($producer);
        $producer('')->is('')->on($condition);
    }

    /**
     * @dataProvider conditionDataProvider
     */
    public function testDefaultPrefix(bool $condition): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(
                Argument::any(),
                Argument::that(
                    fn (string $message) => \strpos($message, 'some is') === 0),
                Argument::any()
            )
            ->shouldBeCalled();
        $loggerProphecy = $loggerProphecy->reveal();

        $producer = new BinaryLoggerFactory(
            $loggerProphecy,
            $this->prophesize(SlugifyInterface::class)->reveal()
        );
        $producer('some')->is('something')->on($condition);
    }

    /**
     * @dataProvider conditionDataProvider
     */
    public function testSetPrefix(bool $condition): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(
                Argument::any(),
                Argument::that(
                    fn (string $message) => \strpos($message, 'prefixprefix: some is') === 0),
                Argument::any()
            )
            ->shouldBeCalled();
        $loggerProphecy = $loggerProphecy->reveal();

        $producer = new BinaryLoggerFactory(
            $loggerProphecy,
            $this->prophesize(SlugifyInterface::class)->reveal()
        );
        $producer->setPrefix('prefixprefix: ');
        $producer->that('some')->is('something')->on($condition);
    }

    public function conditionPassthroughDataProvider(): array
    {
        return [
            'condition is true' => [true, 'some is something'],
            'condition is false' => [false, 'some is not something'],
            'condition is object' => [new \stdClass(), 'some is something'],
            'condition is null' => [null, 'some is not something'],
            'condition is int' => [1, 'some is something'],
            'condition is array' => [[], 'some is not something'],
            'condition is float' => [0.0, 'some is not something'],
            'condition is string' => ['some', 'some is something'],
        ];
    }

    /**
     * @dataProvider conditionPassthroughDataProvider
     */
    public function testConditionPassthroughInSecondArgument($condition, string $logMessage): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(Argument::any(), $logMessage, Argument::cetera())
            ->shouldBeCalled();
        $loggerProphecy = $loggerProphecy->reveal();

        $producer = new BinaryLoggerFactory(
            $loggerProphecy,
            $this->prophesize(SlugifyInterface::class)->reveal()
        );
        $passthrough = $producer('some *is* something', $condition);
        $this->assertSame($condition, $passthrough);
    }
}
