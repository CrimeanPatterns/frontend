<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\Builder;
use Spatie\OpeningHours\Helpers\Arr;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @NoDI()
 */
class OpeningHoursParser
{
    private function __construct()
    {
    }

    /**
     * @param array<int, array<int, array{
     *      open: string,     // Opening time in 'HHMM' format (e.g., '0800')
     *      close: string,    // Closing time in 'HHMM' format (e.g., '1700')
     *      note?: string     // Optional note (e.g., 'Closed' or 'Lounge Hours Vary')
     *  }>> $openingHours An array of opening hours for each day of the week (0 to 6). Each day is an array of time intervals.
     * @throws \InvalidArgumentException
     */
    public static function parse(array $openingHours): array
    {
        $instance = new self();
        $days = [];

        foreach ($openingHours as $day) {
            if (!is_array($day)) {
                throw new \InvalidArgumentException('Invalid opening hours data');
            }

            if (
                \count($day) > 0
                && ($day[0]['open'] ?? null) === '0000'
                && ($day[0]['close'] ?? null) === '0000'
            ) {
                $days[] = $instance->range('00:00-23:59', $day[0], Builder::CODE_OPEN24);
            } else {
                $ranges = [];

                foreach ($day as $range) {
                    if (!is_array($range)) {
                        continue;
                    }

                    if (
                        !array_key_exists('open', $range)
                        && !array_key_exists('close', $range)
                        && !array_key_exists('note', $range)
                    ) {
                        continue;
                    }

                    if (
                        !StringHandler::isEmpty($range['open'] ?? null)
                        || !StringHandler::isEmpty($range['close'] ?? null)
                    ) {
                        $formatted = $instance->formatRange(
                            $range['open'] ?? null,
                            $range['close'] ?? null,
                            $range
                        );

                        if (in_array($formatted['data']['code'] ?? null, Builder::DAY_CODES)) {
                            $days[] = $formatted;

                            continue 2;
                        }

                        if (!$formatted) {
                            continue;
                        }

                        $ranges[] = $formatted;
                    } elseif (!StringHandler::isEmpty($range['note'] ?? null)) {
                        $range['note'] = trim($range['note']);
                        $parts = explode('-', $range['note']);

                        if (count($parts) === 2) {
                            $formatted = $instance->formatRange($parts[0], $parts[1], $range);

                            if (in_array($formatted['data']['code'] ?? null, Builder::DAY_CODES)) {
                                $days[] = $formatted;

                                continue 2;
                            }

                            if (!$formatted) {
                                continue;
                            }

                            $ranges[] = $formatted;
                        } else {
                            if (stripos($range['note'], 'Closed') === 0) {
                                $days[] = $instance->range('00:00-00:00', $range, Builder::CODE_CLOSED, $range['note']);

                                continue 2;
                            } elseif (stripos($range['note'], 'Lounge Hours Vary') === 0) {
                                $days[] = $instance->range('00:00-23:59', $range, Builder::CODE_HOURS_VARY, $range['note']);

                                continue 2;
                            }

                            $ranges[] = $instance->range('00:00-00:00', $range, Builder::CODE_UNKNOWN, $range['note']);
                        }
                    }
                }

                if (count($ranges) === 0) {
                    $days[] = $instance->range('00:00-00:00', $day, Builder::CODE_CLOSED);
                } else {
                    $days[] = $instance->preventTimeRangeOverlaps($ranges);
                }
            }
        }

        if (count($days) !== 7) {
            throw new \InvalidArgumentException('Invalid opening hours data');
        }

        $sunday = $days[0];
        $days = array_slice($days, 1);
        $days[] = $sunday;

        return array_combine(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], $days);
    }

    private function formatRange(?string $start, ?string $end, array $source): array
    {
        if ($start === '0000' && $end === '0000') {
            return $this->range('00:00-23:59', $source, Builder::CODE_OPEN24);
        }

        if (StringHandler::isEmpty($start) && StringHandler::isEmpty($end)) {
            return [];
        }

        if ($this->isValidHours($start) && !$this->isValidHours($end)) {
            try {
                return $this->range(
                    sprintf('%s-%s', $this->formatHours($start), '23:59'),
                    $source,
                    Builder::CODE_RANGE_UNKNOWN_END,
                    $end
                );
            } catch (\InvalidArgumentException $e) {
                return [];
            }
        }

        if (!$this->isValidHours($start) && $this->isValidHours($end)) {
            try {
                return $this->range(
                    sprintf('%s-%s', '00:00', $this->formatHours($end === '0000' ? '2359' : $end)),
                    $source,
                    Builder::CODE_RANGE_UNKNOWN_START,
                    $start
                );
            } catch (\InvalidArgumentException $e) {
                return [];
            }
        }

        if (!$this->isValidHours($start) && !$this->isValidHours($end)) {
            return $this->range(
                '00:00-23:59',
                $source,
                Builder::CODE_RANGE_UNKNOWN_BOTH,
                sprintf('%s|%s', $start, $end),
            );
        }

        if ($this->isValidHours($start) && $this->isValidHours($end)) {
            try {
                return $this->range(
                    sprintf('%s-%s', $this->formatHours($start), $this->formatHours($end === '0000' ? '2359' : $end)),
                    $source
                );
            } catch (\InvalidArgumentException $e) {
                return [];
            }
        }

        return [];
    }

    private function formatHours(string $hours): string
    {
        $hours = (int) $hours;
        $r = $hours % 100;
        $h = ($hours - $r) / 100 % 12;
        $time = sprintf(
            '%d:%02d%s',
            $h > 0 ? $h : 12,
            $r,
            $hours < 1200 ? 'am' : 'pm'
        );
        $date = date_create(sprintf('2000-01-01 %s', $time));

        if ($date === false) {
            throw new \InvalidArgumentException(sprintf('Invalid time: %s', $time));
        }

        return sprintf(
            '%02d:%02d',
            $date->format('H'),
            $date->format('i')
        );
    }

    private function isValidHours(string $hours): bool
    {
        return !StringHandler::isEmpty($hours) && is_numeric($hours);
    }

    private function range(string $range, array $source, ?string $code = null, ?string $msg = null): array
    {
        $data = [
            'source' => $source,
        ];

        if (isset($code)) {
            $data['code'] = $code;
        }

        if (isset($msg)) {
            $data['msg'] = $msg;
        }

        return array_merge([$range], ['data' => $data]);
    }

    private function preventTimeRangeOverlaps(array $ranges): array
    {
        $tr = it($ranges)
            ->mapIndexed(function ($source, $key) {
                return [
                    'range' => TimeRange::fromDefinition($source),
                    'key' => $key,
                ];
            })
            ->toArrayWithKeys();

        foreach (Arr::createUniquePairs($tr) as $timeRanges) {
            /** @var TimeRange $range1 */
            $range1 = $timeRanges[0]['range'];
            /** @var TimeRange $range2 */
            $range2 = $timeRanges[1]['range'];

            if ($range1->overlaps($range2)) {
                $start = $range1->start()->isBefore($range2->start()) ? $range1->start() : $range2->start();
                $range1End = $range1->end();

                if ($range1End->isSame(Time::fromString('00:00'))) {
                    $range1End = Time::fromString('23:59');
                }
                $range2End = $range2->end();

                if ($range2End->isSame(Time::fromString('00:00'))) {
                    $range2End = Time::fromString('23:59');
                }
                $end = $range1End->isAfter($range2End) ? $range1End : $range2End;
                $copy = $ranges[$timeRanges[0]['key']];
                $ranges[$timeRanges[0]['key']][0] = sprintf('%s-%s', $start, $end);
                $ranges[$timeRanges[0]['key']]['data'] = [
                    'code' => Builder::CODE_MERGED,
                ];
                $ranges[$timeRanges[0]['key']]['data']['merge'] = array_merge(
                    $ranges[$timeRanges[0]['key']]['data']['merge'] ?? [],
                    $ranges[$timeRanges[1]['key']]['data']['merge'] ?? [],
                );
                $ranges[$timeRanges[0]['key']]['data']['merge'][] = [
                    $copy ?? null,
                    $ranges[$timeRanges[1]['key']] ?? null,
                ];

                foreach ($ranges[$timeRanges[0]['key']]['data']['merge'] as &$merge) {
                    unset($merge[0]['data']['merge']);
                    unset($merge[1]['data']['merge']);
                }
                unset($ranges[$timeRanges[1]['key']]);

                return $this->preventTimeRangeOverlaps(array_values($ranges));
            }
        }

        return $ranges;
    }
}
