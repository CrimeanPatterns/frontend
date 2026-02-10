<?php

namespace Codeception\Extension;

use AwardWallet\MainBundle\Globals\PropertyAccess\SafeCallPropertyAccessor;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Module\SymfonyTestHelper;
use Codeception\Test\Descriptor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestResult;
use Prophecy\Doubler\LazyDouble;
use Prophecy\Prophecy\ObjectProphecy;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SymfonyTestHelperExtension extends Extension
{
    public static $events = [
        Events::SUITE_AFTER => ['suiteAfter', -99999],
        Events::TEST_AFTER => ['testAfter', 99999],
    ];

    private array $unverifiedMocksMap = [];
    private SafeCallPropertyAccessor $propertyAccessor;

    public function __construct($config, $options)
    {
        parent::__construct($config, $options);

        $this->propertyAccessor = new SafeCallPropertyAccessor();
    }

    public function testAfter(TestEvent $e)
    {
        /** @var SymfonyTestHelper $module */
        $module = $this->getModule('SymfonyTestHelper');
        [$mocksVerified, $mocks] = $module->__getMocksStateInternal();
        $mocksCount = \count($mocks);
        $test = $e->getTest();

        /** @var $testResult TestResult */
        if (
            ($test instanceof \Codeception\Test\Test)
            && ($testResult = $test->getTestResultObject())
            && !($testResult->errorCount() > 0 || $testResult->failureCount() > 0)
            && !$mocksVerified
            && $mocksCount
        ) {
            $testName = codecept_relative_path(Descriptor::getTestFullName($test));
            $this->unverifiedMocksMap[$testName] =
                it($mocks)
                ->map(function ($mock) {
                    if ($mock instanceof MockObject) {
                        return $this->propertyAccessor->getValue($mock, '__mocked?') ?: 'unknown_mock_type';
                    }

                    if ($mock instanceof \Prophecy\Prophecy\ProphecySubjectInterface) {
                        $mock = $mock->getProphecy();
                    }

                    if ($mock instanceof ObjectProphecy) {
                        $refl = new \ReflectionProperty($mock, 'lazyDouble');
                        $refl->setAccessible(true);
                        /** @var LazyDouble $double */
                        $double = $refl->getValue($mock);
                        $refl->setAccessible(false);

                        $refl = new \ReflectionProperty($double, 'class');
                        $refl->setAccessible(true);
                        /** @var \ReflectionClass $class */
                        $class = $refl->getValue($double);
                        $refl->setAccessible(false);

                        return $class->getName();
                    }

                    return 'unknown_mock_type';
                })
                ->stat()
                ->toArrayWithKeys();
        }
    }

    public function suiteAfter(SuiteEvent $e)
    {
        if (!$this->unverifiedMocksMap) {
            return;
        }

        $map = $this->unverifiedMocksMap;
        $this->unverifiedMocksMap = [];
        $this->output->writeln("
<error>The following test(s) has unverified mock(s):
" . it($map)
    ->mapIndexed(function (array $mocks, string $testName) {
        $mocksCount = \count($mocks);

        return
            "\t{$testName} has {$mocksCount} unverified mock(s): {\n"
            . it($mocks)
                ->mapIndexed(fn (int $count, string $class) => "\t\t{$class}: {$count}\n")
                ->joinToString(', ')
            . "\t}";
    })
    ->joinToString("\n") . "
</error>");

        throw new \RuntimeException('Unverified mock(s) found in test(s), please look above for details.');
    }
}
