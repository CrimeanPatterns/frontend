<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\Builder;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Spatie\OpeningHours\Helpers\Arr;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230220043400 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $updater = new BatchUpdater($this->connection);
        $q = $this->connection->executeQuery("SELECT LoungeSourceID, OpeningHours FROM LoungeSource");
        $processed = stmtAssoc($q)
            ->map(function (array $row) {
                if (!StringHandler::isEmpty($row['OpeningHours'])) {
                    $openingHours = @json_decode($row['OpeningHours'], true);

                    if (
                        json_last_error() === JSON_ERROR_NONE
                        && is_array($openingHours)
                    ) {
                        if (
                            is_string($openingHours['tz'] ?? null)
                            && is_array($openingHours['hours'] ?? null)
                        ) {
                            $openingHours = [
                                'tz' => $openingHours['tz'],
                                'data' => $this->processOpeningHours($openingHours['hours']),
                            ];
                            $row['OpeningHours'] = json_encode($openingHours);
                        }
                    } else {
                        $row['OpeningHours'] = null;
                    }
                } else {
                    $row['OpeningHours'] = null;
                }

                return $row;
            })
            ->chunk(100)
            ->map(function (array $rows) use ($updater) {
                $updater->batchUpdate($rows, "UPDATE LoungeSource SET OpeningHours = :OpeningHours WHERE LoungeSourceID = :LoungeSourceID", 0);

                return count($rows);
            })
            ->sum();

        $this->connection->executeStatement("
            UPDATE Lounge AS l
            JOIN LoungeSource AS ls ON ls.LoungeID = l.LoungeID AND ls.SourceCode = 'loungebuddy'
            SET l.OpeningHours = ls.OpeningHours;
        ");

        $this->write("total lounge sources: $processed");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }

    private function processOpeningHours(array $openingHours): array
    {
        $days = [];

        foreach ($openingHours as $day) {
            if ($day[0]['open'] === '0000' && $day[0]['close'] === '0000') {
                $days[] = $this->range('00:00-23:59', $day[0], Builder::CODE_OPEN24);
            } else {
                $ranges = [];

                foreach ($day as $range) {
                    if (!StringHandler::isEmpty($range['open']) || !StringHandler::isEmpty($range['close'])) {
                        $formatted = $this->formatRange($range['open'], $range['close'], $range);

                        if (in_array($formatted['data']['code'] ?? null, Builder::DAY_CODES)) {
                            $days[] = $formatted;

                            continue 2;
                        }

                        if (!$formatted) {
                            continue;
                        }

                        $ranges[] = $formatted;
                    } elseif (!StringHandler::isEmpty($range['note'])) {
                        $range['note'] = trim($range['note']);
                        $parts = explode('-', $range['note']);

                        if (count($parts) === 2) {
                            $formatted = $this->formatRange($parts[0], $parts[1], $range);

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
                                $days[] = $this->range('00:00-00:00', $range, Builder::CODE_CLOSED, $range['note']);

                                continue 2;
                            } elseif (stripos($range['note'], 'Lounge Hours Vary') === 0) {
                                $days[] = $this->range('00:00-23:59', $range, Builder::CODE_HOURS_VARY, $range['note']);

                                continue 2;
                            }

                            $ranges[] = $this->range('00:00-00:00', $range, Builder::CODE_UNKNOWN, $range['note']);
                        }
                    }
                }

                if (count($ranges) === 0) {
                    $days[] = $this->range('00:00-00:00', $day, Builder::CODE_CLOSED);
                } else {
                    $days[] = $this->preventTimeRangeOverlaps($ranges);
                }
            }
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
            return $this->range(
                sprintf('%s-%s', $this->formatHours($start), '23:59'),
                $source,
                Builder::CODE_RANGE_UNKNOWN_END,
                $end
            );
        }

        if (!$this->isValidHours($start) && $this->isValidHours($end)) {
            return $this->range(
                sprintf('%s-%s', '00:00', $this->formatHours($end === '0000' ? '2359' : $end)),
                $source,
                Builder::CODE_RANGE_UNKNOWN_START,
                $start
            );
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
            return $this->range(
                sprintf('%s-%s', $this->formatHours($start), $this->formatHours($end === '0000' ? '2359' : $end)),
                $source
            );
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
