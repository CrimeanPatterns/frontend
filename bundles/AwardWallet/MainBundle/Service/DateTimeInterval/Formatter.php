<?php

namespace AwardWallet\MainBundle\Service\DateTimeInterval;

use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Formatter implements TranslationContainerInterface
{
    private TranslatorInterface $translator;

    private LocalizeService $localizer;

    public function __construct(TranslatorInterface $translator, LocalizeService $localizer)
    {
        $this->translator = $translator;
        $this->localizer = $localizer;
    }

    /**
     * @param bool $onlyDate true - time is set to 00:00:00
     * @param bool $shortFormat true - fraction in years and months, only 1 unit (e.g. 1.7 years or 4.8 months or 10 hours)
     * @param bool $fromToday true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
     */
    public function formatDuration(
        \DateTime $fromDateTime,
        \DateTime $toDateTime,
        bool $onlyDate = false,
        bool $shortFormat = false,
        bool $fromToday = false,
        ?string $locale = null
    ): string {
        return $this->format(
            $fromDateTime,
            $toDateTime,
            $onlyDate,
            $shortFormat,
            false,
            $fromToday,
            true,
            $locale
        );
    }

    public function formatDurationInHours(\DateTime $fromDateTime, \DateTime $toDateTime, ?string $locale = null): string
    {
        $seconds = abs($fromDateTime->getTimestamp() - $toDateTime->getTimestamp());
        $hours = floor($seconds / (60 * 60));
        $minutes = floor(($seconds / 60) % 60);
        $units = [];

        if ($hours > 0) {
            $units['h'] = $this->translator->trans(/** @Desc("1h|%number%h") */ 'hours.short-v2', [
                '%count%' => $hours,
                '%number%' => $this->localizer->formatNumber($hours),
            ], null, $locale);
        }

        if ($minutes > 0) {
            $units['i'] = $this->translator->trans(/** @Desc("1m|%number%m") */ 'minutes.short-v2', [
                '%count%' => $minutes,
                '%number%' => $this->localizer->formatNumber($minutes),
            ], null, $locale);
        }

        if (count($units) === 0) {
            $units['s'] = $this->translator->trans('seconds', [], null, $locale);
        }

        return implode(' ', $units);
    }

    /**
     * use {@see \AwardWallet\MainBundle\Service\DateTimeInterval\Formatter::formatDuration} when possible.
     *
     * @param bool $onlyDate true - time is set to 00:00:00
     * @param bool $shortFormat true - fraction in years and months, only 1 unit (e.g. 1.7 years or 4.8 months or 10 hours)
     * @param bool $fromToday true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
     */
    public function formatDurationViaInterval(
        \DateInterval $interval,
        bool $onlyDate = false,
        bool $shortFormat = false,
        bool $fromToday = false,
        ?string $locale = null
    ): string {
        $fromDateTime = new \DateTime('1970-01-01 00:00:00');
        $toDateTime = (clone $fromDateTime)->add($interval);

        return $this->formatDuration($fromDateTime, $toDateTime, $onlyDate, $shortFormat, $fromToday, $locale);
    }

    /**
     * @param bool $suffix true - wrap "in %text%" or "%text% ago"
     * @param bool $fromToday true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
     */
    public function shortFormatViaDates(
        \DateTime $fromDateTime,
        \DateTime $toDateTime,
        bool $suffix = true,
        bool $fromToday = true,
        ?string $locale = null
    ): string {
        return $this->format(
            $fromDateTime,
            $toDateTime,
            true,
            true,
            $suffix,
            $fromToday,
            false,
            $locale
        );
    }

    /**
     * @param bool $suffix true - wrap "in %text%" or "%text% ago"
     * @param bool $fromToday true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
     */
    public function longFormatViaDates(
        \DateTime $fromDateTime,
        \DateTime $toDateTime,
        bool $suffix = true,
        bool $fromToday = true,
        ?string $locale = null
    ): string {
        return $this->format(
            $fromDateTime,
            $toDateTime,
            true,
            false,
            $suffix,
            $fromToday,
            false,
            $locale
        );
    }

    /**
     * @param bool $suffix true - wrap "in %text%" or "%text% ago"
     * @param bool $fromToday true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
     */
    public function shortFormatViaDateTimes(
        \DateTime $fromDateTime,
        \DateTime $toDateTime,
        bool $suffix = true,
        bool $fromToday = true,
        ?string $locale = null
    ): string {
        return $this->format(
            $fromDateTime,
            $toDateTime,
            false,
            true,
            $suffix,
            $fromToday,
            false,
            $locale
        );
    }

    /**
     * @param bool $suffix true - wrap "in %text%" or "%text% ago"
     * @param bool $fromToday true - from datetime = now datetime, enable "Tomorrow", "Yesterday" and "Today"
     */
    public function longFormatViaDateTimes(
        \DateTime $fromDateTime,
        \DateTime $toDateTime,
        bool $suffix = true,
        bool $fromToday = true,
        ?string $locale = null
    ): string {
        return $this->format(
            $fromDateTime,
            $toDateTime,
            false,
            false,
            $suffix,
            $fromToday,
            false,
            $locale
        );
    }

    /**
     * Get the number of nights for the hotel reservation.
     */
    public function getNightCount(\DateTime $startDate, \DateTime $endDate): string
    {
        $nights = Reservation::getNightCount($startDate, $endDate);

        return sprintf(
            '%d %s',
            $this->localizer->formatNumber($nights, 1),
            $this->translator->trans(/** @Desc("night|nights") */ 'nights', ['%count%' => $nights])
        );
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('hours-v2'))->setDesc('1 hour|%number% hours'),
            (new Message('hours.short-v2'))->setDesc('1h|%number%h'),
            (new Message('minutes-v2'))->setDesc('1 minute|%number% minutes'),
            (new Message('minutes.short-v2'))->setDesc('1m|%number%m'),
        ];
    }

    private function format(
        \DateTime $fromDateTime,
        \DateTime $toDateTime,
        bool $onlyDate,
        bool $shortFormat,
        bool $addSuffix,
        bool $fromToday,
        bool $duration,
        ?string $locale
    ): string {
        if ($onlyDate || $duration) {
            [$fromDateTime, $toDateTime] = $this->resetTime($fromDateTime, $toDateTime, !$onlyDate && $duration);
        }

        $diff = $this->getExtraDiffData($fromDateTime, $toDateTime, $shortFormat);
        $future = !$diff['invert'];
        $hasTime = $diff['h'] > 0 || $diff['i'] > 0 || $diff['s'] > 0;
        $hasOnlySeconds = $diff['h'] == 0 && $diff['i'] == 0 && $diff['s'] > 0;
        $showTime = $diff['days'] < 2 && $hasTime;
        $units = [];

        if ($diff['y'] > 0) {
            $units['y'] = sprintf(
                '%s %s',
                $this->localizer->formatNumber($diff['y'], 1, $locale),
                $this->translator->trans(/** @Desc("year|years") */ 'years', ['%count%' => ceil($diff['y'])], null, $locale)
            );
        }

        if ($diff['m'] > 0) {
            $units['m'] = sprintf(
                '%s %s',
                $this->localizer->formatNumber($diff['m'], 1, $locale),
                $this->translator->trans(/** @Desc("month|months") */ 'months', ['%count%' => ceil($diff['m'])], null, $locale)
            );
        }

        if (
            $diff['d'] > 1
            || (
                $diff['d'] == 1 && (
                    count($units) > 0 || ($showTime && !$hasOnlySeconds) || !$fromToday
                )
            )
        ) {
            $units['d'] = sprintf(
                '%d %s',
                $this->localizer->formatNumber($diff['d'], 1, $locale),
                $this->translator->trans(/** @Desc("day|days") */ 'days', ['%count%' => $diff['d']], null, $locale)
            );
        } elseif ($diff['d'] == 1 && (!$showTime || $hasOnlySeconds) && $fromToday) {
            $addSuffix = false;

            if ($future) {
                $units['d'] = mb_strtolower($this->translator->trans(/** @Desc("Tomorrow") */ 'tomorrow', [], null, $locale));
            } else {
                $units['d'] = mb_strtolower($this->translator->trans(/** @Desc("Yesterday") */ 'yesterday', [], null, $locale));
            }
        }

        if ($showTime) {
            $shortyTime = ($diff['h'] > 0 && $diff['i'] > 0) && !$shortFormat;

            if ($diff['h'] > 0) {
                if ($shortyTime) {
                    $units['h'] = $this->translator->trans('hours.short-v2', [
                        '%count%' => $diff['h'],
                        '%number%' => $this->localizer->formatNumber($diff['h'], 1, $locale),
                    ], null, $locale);
                } else {
                    $units['h'] = $this->translator->trans(/** @Desc("1 hour|%number% hours") */ 'hours-v2', [
                        '%count%' => $diff['h'],
                        '%number%' => $this->localizer->formatNumber($diff['h'], 1, $locale),
                    ], null, $locale);
                }
            }

            if ($diff['i'] > 0) {
                if ($shortyTime) {
                    $units['i'] = $this->translator->trans('minutes.short-v2', [
                        '%count%' => $diff['i'],
                        '%number%' => $this->localizer->formatNumber($diff['i'], 1, $locale),
                    ], null, $locale);
                } else {
                    $units['i'] = $this->translator->trans(/** @Desc("1 minute|%number% minutes") */ 'minutes-v2', [
                        '%count%' => $diff['i'],
                        '%number%' => $this->localizer->formatNumber($diff['i'], 1, $locale),
                    ], null, $locale);
                }
            }

            if ($hasOnlySeconds && count($units) === 0) {
                $units['s'] = $this->translator->trans(/** @Desc("a few seconds") */ 'seconds', [], null, $locale);
            }
        }

        if (count($units) === 0) {
            if ($fromToday && $onlyDate) {
                $addSuffix = false;
                $units['s'] = mb_strtolower($this->translator->trans(/** @Desc("Today") */ 'today', [], null, $locale));
            } else {
                $units['s'] = $this->translator->trans(/** @Desc("a few seconds") */ 'seconds', [], null, $locale);
            }
        }

        if ($shortFormat) {
            $formatted = array_shift($units);
        } else {
            $formatted = [];
            $started = false;

            foreach (['y', 'm', 'd', 'h', 'i', 's'] as $var) {
                if (isset($units[$var])) {
                    $started = true;
                    $formatted[] = $units[$var];
                } elseif ($started) {
                    break;
                }
            }
            $formatted = implode(' ', array_slice($formatted, 0, 2));
        }

        if ($addSuffix) {
            return $this->addSuffix($future, $formatted, $locale);
        }

        return $formatted;
    }

    private function addSuffix(bool $future, string $text, ?string $locale): string
    {
        if ($future) {
            return $this->translator->trans(/** @Desc("in %text%") */ 'relative_date.future', ['%text%' => $text], null, $locale);
        }

        return $this->translator->trans(/** @Desc("%text% ago") */ 'relative_date.past', ['%text%' => $text], null, $locale);
    }

    private function getExtraDiffData(\DateTime $fromDateTime, \DateTime $toDateTime, bool $short): array
    {
        $interval = date_diff($fromDateTime, $toDateTime);
        $ts1 = $fromDateTime->getTimestamp();
        $ts2 = $toDateTime->getTimestamp();
        $extraDiff = [
            'invert' => $interval->invert || $ts1 === $ts2,
            'days' => $interval->days,
        ];

        foreach (['y', 'm', 'd', 'h', 'i', 's'] as $var) {
            $extraDiff[$var] = 0;
        }

        // years
        if ($interval->y > 0) {
            $ts1 = strtotime(sprintf($interval->invert ? '-%d year' : '+%d year', $interval->y), $ts1);
        }
        $sec = abs($ts1 - $ts2);
        $daysInYear = (bool) date('L', $ts1) ? 366 : 365;
        $fract = ($sec / 60 / 60 / 24) / $daysInYear;
        $extraDiff['y'] = $interval->y;

        if ($fract > 0.9) {
            ++$extraDiff['y'];

            return $extraDiff;
        } elseif ($extraDiff['y'] > 0 && $short) {
            $extraDiff['y'] += round($fract, 1);

            return $extraDiff;
        }

        // months
        if ($interval->m > 0) {
            $ts1 = strtotime(sprintf($interval->invert ? '-%d month' : '+%d month', $interval->m), $ts1);
        }
        $sec = abs($ts1 - $ts2);
        $fract = ($sec / 60 / 60 / 24) / date('t', $ts1);
        $extraDiff['m'] = $interval->m;

        if ($fract > 0.9) {
            ++$extraDiff['m'];

            return $extraDiff;
        } elseif ($extraDiff['m'] > 0 && $short) {
            $extraDiff['m'] += round($fract, 1);

            return $extraDiff;
        }

        // days
        if ($interval->d > 0) {
            $ts1 = strtotime(sprintf($interval->invert ? '-%d day' : '+%d day', $interval->d), $ts1);
        }
        $sec = abs($ts1 - $ts2);
        $fract = ($sec / 60 / 60) / 24;
        $extraDiff['d'] = $interval->d;

        if ($fract > 0.9) {
            ++$extraDiff['d'];

            return $extraDiff;
        } elseif ($extraDiff['d'] >= 2 || ($extraDiff['d'] > 0 && $fract < 0.1)) {
            return $extraDiff;
        }

        // hours
        if ($interval->h > 0) {
            $ts1 = strtotime(sprintf($interval->invert ? '-%d hour' : '+%d hour', $interval->h), $ts1);
        }
        $sec = abs($ts1 - $ts2);
        $fract = ($sec / 60) / 60;
        $extraDiff['h'] = $interval->h;

        if ($fract > 0.9) {
            ++$extraDiff['h'];

            return $extraDiff;
        }

        // minutes
        if ($interval->i > 0) {
            $ts1 = strtotime(sprintf($interval->invert ? '-%d minute' : '+%d minute', $interval->i), $ts1);
        }
        $sec = abs($ts1 - $ts2);
        $fract = $sec / 60;
        $extraDiff['i'] = $interval->i;

        if ($fract > 0.9) {
            ++$extraDiff['i'];

            return $extraDiff;
        }

        // seconds
        if ($interval->s > 0) {
            $extraDiff['s'] = $interval->s;
        }

        return $extraDiff;
    }

    private function resetTime(\DateTime $fromDateTime, \DateTime $toDateTime, bool $onlySeconds = false): array
    {
        $fromDateTime = clone $fromDateTime;
        $toDateTime = clone $toDateTime;

        if ($onlySeconds) {
            $fromDateTime->setTime($fromDateTime->format('H'), $fromDateTime->format('i'), 00);
            $toDateTime->setTime($toDateTime->format('H'), $toDateTime->format('i'), 00);
        } else {
            $fromDateTime->setTime(00, 00, 00);
            $toDateTime->setTime(00, 00, 00);
        }

        return [$fromDateTime, $toDateTime];
    }
}
