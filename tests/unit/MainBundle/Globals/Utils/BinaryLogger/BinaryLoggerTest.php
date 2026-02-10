<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals\Utils\BinaryLogger;

use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLogger;
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
class BinaryLoggerTest extends Unit
{
    use ProphecyTrait;

    private const NON_EXISTING_LOG_LEVEL = -100;

    /**
     * @dataProvider toLogLevelMethodsDataProvider
     */
    public function testToLogLevelMethods(callable $logLevelCaller, int $logLevel, bool $condition): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(
                Argument::exact($logLevel),
                Argument::that(fn (string $message) => \strpos($message, 'some is') === 0),
                Argument::any()
            )
            ->shouldBeCalled();
        $binaryLogger = new BinaryLogger(
            'some',
            self::NON_EXISTING_LOG_LEVEL,
            false,
            $loggerProphecy->reveal(),
            $this->makeDumbSlugger()
        );
        $logLevelCaller($binaryLogger);
        $binaryLogger->is('something')->on($condition);
    }

    public function toLogLevelMethodsDataProvider(): array
    {
        return self::productCallsWithConditions([
            [fn (BinaryLogger $logger) => $logger->toDebug(), Logger::DEBUG],
            [fn (BinaryLogger $logger) => $logger->toInfo(), Logger::INFO],
            [fn (BinaryLogger $logger) => $logger->toNotice(), Logger::NOTICE],
            [fn (BinaryLogger $logger) => $logger->toWarning(), Logger::WARNING],
            [fn (BinaryLogger $logger) => $logger->toError(), Logger::ERROR],
            [fn (BinaryLogger $logger) => $logger->toCritical(), Logger::CRITICAL],
            [fn (BinaryLogger $logger) => $logger->toAlert(), Logger::ALERT],
            [fn (BinaryLogger $logger) => $logger->toEmergency(), Logger::EMERGENCY],
            [fn (BinaryLogger $logger) => $logger->debug(), Logger::DEBUG],
            [fn (BinaryLogger $logger) => $logger->info(), Logger::INFO],
            [fn (BinaryLogger $logger) => $logger->notice(), Logger::NOTICE],
            [fn (BinaryLogger $logger) => $logger->warning(), Logger::WARNING],
            [fn (BinaryLogger $logger) => $logger->error(), Logger::ERROR],
            [fn (BinaryLogger $logger) => $logger->critical(), Logger::CRITICAL],
            [fn (BinaryLogger $logger) => $logger->alert(), Logger::ALERT],
            [fn (BinaryLogger $logger) => $logger->emergency(), Logger::EMERGENCY],
        ]);
    }

    /**
     * @dataProvider conditionPassthroughDataProvider
     */
    public function testConditionPassthrough($condition): void
    {
        $binaryLogger = new BinaryLogger(
            'some',
            self::NON_EXISTING_LOG_LEVEL,
            false,
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->makeDumbSlugger()
        );
        $this->assertSame($condition, $binaryLogger->is('something')->on($condition));
    }

    public function conditionPassthroughDataProvider(): array
    {
        return [
            'condition is true' => [true],
            'condition is false' => [false],
            'condition is object' => [new \stdClass()],
            'condition is null' => [null],
            'condition is int' => [1],
            'condition is array' => [[]],
            'condition is float' => [0.0],
            'condition is string' => ['some'],
        ];
    }

    /**
     * @dataProvider toLogLevelNegativePositiveDataProvider
     */
    public function testToLogLevelPositiveMethods(callable $logLevelCaller, int $logLevel, bool $condition): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(
                Argument::exact($logLevel),
                Argument::that(fn (string $message) => \strpos($message, 'some is') === 0),
                Argument::any()
            )
            ->shouldBeCalled();
        $binaryLogger = new BinaryLogger(
            'some',
            self::NON_EXISTING_LOG_LEVEL,
            false,
            $loggerProphecy->reveal(),
            $this->makeDumbSlugger()
        );
        $logLevelCaller($binaryLogger);
        $binaryLogger->is('something')->on($condition);
    }

    public function toLogLevelNegativePositiveDataProvider()
    {
        return [
            [fn (BinaryLogger $l) => $l->negativeToDebug(),  Logger::DEBUG, false],
            [fn (BinaryLogger $l) => $l->negativeToInfo(), Logger::INFO, false],
            [fn (BinaryLogger $l) => $l->negativeToNotice(), Logger::NOTICE, false],
            [fn (BinaryLogger $l) => $l->negativeToWarning(), Logger::WARNING, false],
            [fn (BinaryLogger $l) => $l->negativeToError(), Logger::ERROR, false],
            [fn (BinaryLogger $l) => $l->negativeToCritical(), Logger::CRITICAL, false],
            [fn (BinaryLogger $l) => $l->negativeToAlert(), Logger::ALERT, false],
            [fn (BinaryLogger $l) => $l->negativeToEmergency(), Logger::EMERGENCY, false],

            [fn (BinaryLogger $l) => $l->positiveToDebug(), Logger::DEBUG, true],
            [fn (BinaryLogger $l) => $l->positiveToInfo(), Logger::INFO, true],
            [fn (BinaryLogger $l) => $l->positiveToNotice(), Logger::NOTICE, true],
            [fn (BinaryLogger $l) => $l->positiveToWarning(), Logger::WARNING, true],
            [fn (BinaryLogger $l) => $l->positiveToError(), Logger::ERROR, true],
            [fn (BinaryLogger $l) => $l->positiveToCritical(), Logger::CRITICAL, true],
            [fn (BinaryLogger $l) => $l->positiveToAlert(), Logger::ALERT, true],
            [fn (BinaryLogger $l) => $l->positiveToEmergency(), Logger::EMERGENCY, true],
        ];
    }

    public function testAlreadyInitializedException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Already initialized!');

        $binaryLogger = new BinaryLogger(
            'some',
            self::NON_EXISTING_LOG_LEVEL,
            false,
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->makeDumbSlugger()
        );
        $binaryLogger->was('some')->wasNot('some');
    }

    /**
     * @dataProvider infixOnVarianceDataProvider
     */
    public function testOnMessageVariance(callable $caller, string $message, bool $condition, bool $isUpperCaseInfix)
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(
                self::NON_EXISTING_LOG_LEVEL,
                Argument::exact($message),
                Argument::any()
            )
            ->shouldBeCalled();
        $binaryLogger = new BinaryLogger(
            'some',
            self::NON_EXISTING_LOG_LEVEL,
            $isUpperCaseInfix,
            $loggerProphecy->reveal(),
            $this->makeDumbSlugger()
        );
        $caller($binaryLogger)->on($condition);
    }

    public function infixOnVarianceDataProvider(): array
    {
        return [
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->is('something'),    'some is something',     true, false],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->is('something'),    'some is not something', false, false],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->isNot('something'), 'some is not something', true, false],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->isNot('something'), 'some is something',     false, false],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->is('something'),    'some IS something',     true, true],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->is('something'),    'some IS NOT something', false, true],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->isNot('something'), 'some IS NOT something', true, true],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->isNot('something'), 'some IS something',     false, true],
        ];
    }

    /**
     * @dataProvider autoInfixOnVarianceDataProvider
     */
    public function testOnAutoMessageVariance($prefix, string $message, bool $condition, bool $isUpperCaseInfix)
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(
                self::NON_EXISTING_LOG_LEVEL,
                Argument::exact($message),
                Argument::any()
            )
            ->shouldBeCalled();
        $binaryLogger = new BinaryLogger(
            $prefix,
            self::NON_EXISTING_LOG_LEVEL,
            $isUpperCaseInfix,
            $loggerProphecy->reveal(),
            $this->makeDumbSlugger()
        );
        $binaryLogger->on($condition);
    }

    public function autoInfixOnVarianceDataProvider(): array
    {
        return [
            ['some *is* something',    'some is something',     true, false],
            ['some *is* something',    'some is not something', false, false],
            ['some *is not* something', 'some is not something', true, false],
            ['some *is not* something', 'some is something',     false, false],
            ["some *isn't* something", 'some is something',     false, false],
            ['some *is* something',    'some IS something',     true, true],
            ['some *is* something',    'some IS NOT something', false, true],
            ['some *is not* something', 'some IS NOT something', true, true],
            ['some *is not* something', 'some IS something',     false, true],
            ["some *isn't* something", 'some IS something',     false, true],

            ['some *was* something',    'some was something',     true, false],
            ['some *was* something',    'some was not something', false, false],
            ['some *was not* something', 'some was not something', true, false],
            ['some *was not* something', 'some was something',     false, false],
            ['some *was* something',    'some WAS something',     true, true],
            ['some *was* something',    'some WAS NOT something', false, true],
            ['some * was* something',    'some WAS NOT something', false, true],
            ["some * was  \n* something",    'some WAS NOT something', false, true],
            ['some *was not* something', 'some WAS NOT something', true, true],
            ['some *was not* something', 'some WAS something',     false, true],
            ['some * was not* something', 'some WAS something',     false, true],
            ["some * was  \n  not* something", 'some WAS something',     false, true],

            ['some *was something', 'some *was something', true, false],
            ['some *was something', 'not (some *was something)', false, false],
            ['some *was something', 'some *was something', true, true],
            ['some *was something', 'NOT (some *was something)', false, true],

            // unknown infixes
            ['some *shmos* something',    'some SHMOS something',     true, true],
            ['some *shmos* something',    'some NOT (SHMOS) something', false, true],

            // escaping cases
            ['some \\* was not\\* something', 'some \\* was not\\* something', true, true],
            ['some \\* was not\\* something', 'NOT (some \\* was not\\* something)',  false, true],

            ['some \\* was not\\* something', 'some \\* was not\\* something',  true, true],
            ['some \\* was not\\* something', 'NOT (some \\* was not\\* something)',  false, true],

            ['some *was not\* something', 'some *was not\* something',  true, true],
            ['some *was not\* something', 'NOT (some *was not\* something)',  false, true],

            ['some \**was not\* something', 'some \**was not\* something',  true, true],
            ['some \**was not\* something', 'NOT (some \**was not\* something)',  false, true],

            ['some \\*are\\**was*\\*have\\* something', 'some \\*are\\*WAS\\*have\\* something', true, true],
            ['some \\*are\\**was*\\*have\\* something', 'some \\*are\\*WAS NOT\\*have\\* something', false, true],
        ];
    }

    /**
     * @dataProvider infixOnCallVarianceDataProvider
     */
    public function testOnCallMessageVariance(callable $caller, string $message, bool $condition)
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->log(
                self::NON_EXISTING_LOG_LEVEL,
                Argument::exact($message),
                Argument::any()
            )
            ->shouldBeCalled();
        $binaryLogger = new BinaryLogger(
            'some',
            self::NON_EXISTING_LOG_LEVEL,
            false,
            $loggerProphecy->reveal(),
            $this->makeDumbSlugger()
        );
        $caller($binaryLogger)->onCall(fn () => $condition);
    }

    public function infixOnCallVarianceDataProvider(): array
    {
        return [
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->is('something'),    'some is something',     true],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->is('something'),    'some is not something', false],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->isNot('something'), 'some is not something', true],
            [fn (BinaryLogger $binaryLogger) => $binaryLogger->isNot('something'), 'some is something',     false],
        ];
    }

    protected static function productCallsWithConditions(array $calls): array
    {
        return
            it($calls)
            ->product([true, false])
            ->map(function (array $pair) {
                [[$caller, $logLevel], $condition] = $pair;

                return [$caller, $logLevel, $condition];
            })
            ->toArray();
    }

    protected function makeDumbSlugger(): SlugifyInterface
    {
        return $this->prophesize(SlugifyInterface::class)->reveal();
    }
}
