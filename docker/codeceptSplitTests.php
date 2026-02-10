#!/usr/bin/php
<?php

use Codeception\Lib\GroupManager;
use Codeception\Test\Descriptor;
use Codeception\Test\Interfaces\Descriptive;
use Codeception\Test\Interfaces\Plain;
use Symfony\Component\Finder\Finder;

include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../vendor/codeception/codeception/autoload.php';

interface GroupProviderInterface
{
    public function getGroups() : \Iterator;
}

abstract class BaseProvider implements GroupProviderInterface
{
    /**
     * @var GroupManager
     */
    protected $groupManager;
    /**
     * @var array
     */
    protected $suites;
    /**
     * @var array
     */
    protected $allowedGroups;
    /**
     * @var int
     */
    protected $groupsCount;

    public function __construct(array $suites, array $allowedGroups, int $groupsCount)
    {
        $this->suites = $suites;
        $this->allowedGroups = $allowedGroups;
        $this->groupsCount = $groupsCount;
        $this->groupManager = new GroupManager([]);
    }

    protected function getTests() : \Iterator
    {
        foreach ($this->suites as $suite) {
            $testLoader = new \Codeception\Test\Loader(['path' => "tests/{$suite}"]);
            $testLoader->loadTests("tests/{$suite}");
            $tests = $testLoader->getTests();
            $i = 0;

            foreach ($tests as $testCase) {
                if ($testCase instanceof PHPUnit_Framework_TestSuite_DataProvider) {
                    $testCase = current($testCase->tests());
                }

                $fileName = null;
                $testName = null;

                if ($testCase instanceof Plain) {
                    $fileName = Descriptor::getTestFileName($testCase);
                } else
                if ($testCase instanceof Descriptive) {
                    $signature = $testCase->getSignature(); // cut everything before ":" from signature
                    $fileName = Descriptor::getTestFileName($testCase);
                    $testName = preg_replace('~^(.*?):~', '', $signature);
                } else
                if ($testCase instanceof \PHPUnit_Framework_TestCase) {
                    $fileName = Descriptor::getTestFileName($testCase);
                    $testName = $testCase->getName(false);
                } else {
                    $fileName = Descriptor::getTestFileName($testCase);
                    $testName = $testCase->toString();
                }

                if (false === strpos($fileName, "tests/{$suite}/")) {
                    continue;
                }

                if (realpath($fileName) === $fileName) {
                    $fileName = substr($fileName, strpos($fileName, "tests/{$suite}/"));
                }

                if (!file_exists($fileName)) {
                    continue;
                }

                $testGroups = $this->groupManager->groupsForTest($testCase);
                $groupFound = false;

                foreach ($testGroups as $testGroup) {
                    if (!$this->allowedGroups || in_array($testGroup, $this->allowedGroups)) {
                        $groupFound = true;
                        break;
                    }
                }

                if (!$groupFound) {
                    continue;
                }

                yield [
                    $suite,
                    $fileName,
                    $testName,
                    $testCase,
                ];
            }
        }
    }

    protected function getFullTestName(string $fileName, ?string $testName = null) : string
    {
        return $fileName . (isset($testName) ? ":{$testName}" : '');
    }

    abstract public function getGroups() : \Iterator;
}

class TimeDependantProvider extends BaseProvider
{
    public function getGroups() : \Iterator
    {
        $timings = $this->loadTimings();
        $groups = array_fill(1, $this->groupsCount, []);
        $groupsTimings = array_fill(1, $this->groupsCount, 0);

        $tests = [];

        foreach ($this->getTests() as [$suite, $fileName, $testName, $_]) {
            $tests[$this->getFullTestName(realpath($fileName), $testName)] = [$suite, $fileName, $testName];
        }

        foreach ($timings as $fullTestName => $timing) {
            if (isset($tests[$fullTestName])) {
                [$_, $fileName, $testName] = $tests[$fullTestName];
                $minGroupIndex = $this->getMinGroup($groupsTimings);
                $groups[$minGroupIndex][] = $this->getFullTestName($fileName, $testName);
                $groupsTimings[$minGroupIndex] += $timing;
                unset($tests[$fullTestName]);
            }
        }

        echo "****************************\n".
            "Time distribution:\n" .
            "****************************\n";

        foreach ($groupsTimings as $groupId => $groupTiming) {
            echo "Group {$groupId}: " . round($groupTiming, 2) . "s\n";
        }


        $i = 0;

        foreach ($tests as $testData) {
            [$_, $fileName, $testName] = $testData;
            $groups[($i++ % $this->groupsCount) + 1][] = $this->getFullTestName($fileName, $testName);
        }

        yield from $groups;
    }

    protected function getMinGroup(array $groups) : int
    {
        $minIndex = key($groups);

        foreach (array_keys($groups) as $index) {
            if ($groups[$minIndex] > $groups[$index]) {
                $minIndex = $index;
            }
        }

        return $minIndex;
    }

    protected function loadTimings()
    {
        $files = Finder::create()
            ->name('*.xml')
            ->in(__DIR__ . '/../tests/_data/paracept');

        $tests = [];
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            try {
                $dom = new DOMDocument();
                $dom->loadXML($file->getContents());
                $domXpath = new DOMXPath($dom);

                /** @var DOMElement $testCase */
                foreach ($domXpath->query('//testcase') as $testCase) {
                    $tests[realpath($testCase->getAttribute('file')) . ':' . $testCase->getAttribute('name')] = (float)$testCase->getAttribute('time');
                }
            } catch (\Throwable $_) {}
        }

        asort($tests, SORT_NUMERIC);
        $tests = array_reverse($tests, true);

        return $tests;
    }
}

class ShuffledProvider extends BaseProvider
{
    public function getGroups() : \Iterator
    {
        $tests = [];

        foreach ($this->getTests() as [$suite, $fileName, $testName, $_]) {
            $tests[] = $this->getFullTestName($fileName, $testName);
        }

        shuffle($tests);
        $groups = [];

        foreach ($tests as $i => $test) {
            $groups[($i % $this->groupsCount) + 1][] = $test;
        }

        yield from $groups;
    }
}

chdir(__DIR__ . '/../');

if (!file_exists($paraceptDir = __DIR__ . '/../tests/_data/paracept/')) {
    mkdir($paraceptDir);
}

`rm -rf {$paraceptDir}/group_*`;

$groupsCount = $argv[1] ?? 3;
$suites = ['unit', 'functional-symfony', 'functional', 'acceptance'];
$allowedGroups = ['frontend-unit', 'frontend-acceptance', 'frontend-functional'];
$provider = new TimeDependantProvider($suites, $allowedGroups, $groupsCount);


// saving group files
foreach ($provider->getGroups() as $i => $tests) {
    $filename =  $paraceptDir . 'group_' . $i;
    file_put_contents($filename, implode("\n", $tests));
}

