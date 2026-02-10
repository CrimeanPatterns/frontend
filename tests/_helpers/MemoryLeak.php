<?php

namespace Codeception\Extension;

use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\TestInterface;

class MemoryLeak extends Extension
{
    public static $events = [
        Events::MODULE_INIT => 'moduleInit',
        Events::TEST_START => 'testStart',
        Events::TEST_END => 'testEnd',
        Events::RESULT_PRINT_AFTER => 'printResults',
    ];
    private $memoryUsage = [];
    private $testNumber = 0;

    // we are listening for events
    /**
     * @var string
     */
    private $basePath;

    public function moduleInit(SuiteEvent $e)
    {
        $this->basePath = realpath(__DIR__ . '/..') . "/";
    }

    public function testStart(TestEvent $e)
    {
        $this->testNumber++;
        $testName = $this->getTestName($e);
        $this->memoryUsage[$testName] = ['before' => memory_get_usage(true), 'testNumber' => $this->testNumber, 'testName' => $testName];
    }

    public function testEnd(TestEvent $e)
    {
        $testName = $this->getTestName($e);
        $this->memoryUsage[$testName]['after'] = memory_get_usage(true);
        $this->memoryUsage[$testName]['delta'] = $this->memoryUsage[$testName]['after'] - $this->memoryUsage[$testName]['before'];
    }

    public function printResults()
    {
        usort($this->memoryUsage, function (array $a, array $b) {
            return $b['delta'] - $a['delta'];
        });

        $this->memoryUsage = array_filter($this->memoryUsage, function (array $test) {
            return $test['delta'] > 512;
        });

        if (count($this->memoryUsage) > 100) {
            $this->output->writeln("--- TOP MEMORY LEAKS ---");

            foreach (array_slice($this->memoryUsage, 0, 5) as $test) {
                $this->output->writeln("{$test['testName']} (#{$test['testNumber']}): " . number_format(round($test['delta'] / 1024), 0, ".", " ") . " kb");
            }
        }
    }

    private function getTestName(TestEvent $e)
    {
        if ($e->getTest() instanceof TestInterface) {
            return substr($e->getTest()->getMetadata()->getFilename(),
                strlen($this->basePath)) . ":" . $e->getTest()->getMetadata()->getName();
        }

        return get_class($e->getTest());
    }
}
