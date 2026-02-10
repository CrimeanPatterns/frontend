<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\DateTimeInterval;

use AwardWallet\MainBundle\Controller\Test\DateTimeDiffController;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Symfony\Component\Yaml\Yaml;

/**
 * @group frontend-unit
 */
class FormatterTest extends BaseContainerTest
{
    /**
     * @var Formatter
     */
    private $formatter;

    public function _before()
    {
        parent::_before();
        $this->formatter = $this->container->get(Formatter::class);
    }

    public function _after()
    {
        $this->formatter = null;
        parent::_after();
    }

    public function testFormat()
    {
        $fixtures = DateTimeDiffController::parseData();
        $updateFixtures = false;

        foreach ($fixtures as &$data) {
            $interval = $data['interval'];

            foreach ($data['tests'] as $name => &$tests) {
                foreach ($tests as &$test) {
                    if ($name !== 'formatDurationInHours') {
                        $this->assert(
                            $test['onlyDate'] ?? in_array($name, ['shortFormatViaDates', 'longFormatViaDates']),
                            $test['shortFormat'] ?? in_array($name, ['shortFormatViaDates', 'shortFormatViaDateTimes']),
                            $name === 'formatDuration',
                            $interval,
                            $test,
                            $updateFixtures
                        );
                    } else {
                        $this->assertDurationInHours($interval, $test, $updateFixtures);
                    }
                }
            }
        }

        if ($updateFixtures) {
            static::updateFixtures($fixtures);
        }
    }

    public static function updateFixtures(array $fixtures): void
    {
        \file_put_contents(DateTimeDiffController::DATA_FILE, Yaml::dump($fixtures, 5, 4));
    }

    private function assert(bool $onlyDate, bool $shortFormat, bool $duration, string $interval, array &$data, bool $updateFixtures): void
    {
        [$from, $to] = DateTimeDiffController::getDates($interval, $data['from'] ?? null);
        $fromToday = !isset($data['from']);

        if ($duration) {
            $actual = $this->formatter->formatDuration($from, $to, $onlyDate, $shortFormat, false, 'en');
        } else {
            if ($onlyDate) {
                if ($shortFormat) {
                    $actual = $this->formatter->shortFormatViaDates($from, $to, true, $fromToday, 'en');
                } else {
                    $actual = $this->formatter->longFormatViaDates($from, $to, true, $fromToday, 'en');
                }
            } else {
                if ($shortFormat) {
                    $actual = $this->formatter->shortFormatViaDateTimes($from, $to, true, $fromToday, 'en');
                } else {
                    $actual = $this->formatter->longFormatViaDateTimes($from, $to, true, $fromToday, 'en');
                }
            }
        }

        if ($updateFixtures) {
            $data['expected'] = $actual;
        } else {
            $this->assertEquals($data['expected'], $actual, $this->log($interval, $from, $to, $onlyDate, $shortFormat, $duration));
        }
    }

    private function assertDurationInHours(string $interval, array &$data, bool $updateFixtures): void
    {
        [$from, $to] = DateTimeDiffController::getDates($interval, $data['from'] ?? null);
        $actual = $this->formatter->formatDurationInHours($from, $to, 'en');

        if ($updateFixtures) {
            $data['expected'] = $actual;
        } else {
            $this->assertEquals($data['expected'], $actual, DateTimeDiffController::log($interval, $from, $to));
        }
    }

    private function log(string $interval, \DateTime $from, \DateTime $to, bool $onlyDate, bool $shortFormat, bool $duration): string
    {
        return sprintf(
            '%s, onlyDate: %d, shortFormat: %d, duration: %d',
            DateTimeDiffController::log($interval, $from, $to),
            (int) $onlyDate,
            (int) $shortFormat,
            (int) $duration
        );
    }
}
