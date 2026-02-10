<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\Builder;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class OpeningHoursScheduleBuilder implements TranslationContainerInterface
{
    private LocalizeService $localizeService;

    private TranslatorInterface $translator;

    public function __construct(LocalizeService $localizeService, TranslatorInterface $translator)
    {
        $this->localizeService = $localizeService;
        $this->translator = $translator;
    }

    /**
     * @return OpeningHoursItemView[]|string
     */
    public function build(Builder $openingHours, string $locale)
    {
        $formatter = \IntlDateFormatter::create(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            null,
            null,
            'cccc'
        );
        $formatWeekDay = function (string $day) use ($formatter) {
            $str = $formatter->format(date_create('last ' . $day));
            $fc = mb_strtoupper(mb_substr($str, 0, 1));

            return $fc . mb_substr($str, 1);
        };
        /** @var OpeningHoursForDay[] $week */
        $week = $openingHours->getOpeningHours()->forWeek();
        $open247 = count(
            array_filter(
                $week,
                function (OpeningHoursForDay $day) {
                    return
                        (is_array($data = $day->getData()) && ($data['code'] ?? null) === Builder::CODE_OPEN24)
                        || (count($day) === 1 && (it($day->getIterator())->first()->getData()['code'] ?? null) === Builder::CODE_OPEN24);
                }
            )
        ) === 7;

        if ($open247) {
            return $this->translator->trans('lounge.open-24-7', [], 'trips');
        }

        return it($week)
            ->mapIndexed(function (OpeningHoursForDay $day, string $weekDay) use ($formatWeekDay, $locale) {
                $dayData = $day->getData();
                $dayCode = is_array($dayData) ? $dayData['code'] ?? null : null;

                if (is_null($dayCode) && count($day) === 1) {
                    /** @var TimeRange $range */
                    $range = it($day->getIterator())->first();
                    $dayCode = $range->getData()['code'] ?? null;
                }

                switch ($dayCode) {
                    case Builder::CODE_OPEN24:
                        $desc = $this->translator->trans('lounge.open-24-hours', [], 'trips');

                        break;

                    case Builder::CODE_CLOSED:
                        $desc = $this->translator->trans('lounge.closed', [], 'trips');

                        break;

                    case Builder::CODE_HOURS_VARY:
                        $desc = $this->translator->trans('lounge.working-hours-vary', [], 'trips');

                        break;
                }

                if (empty($desc)) {
                    $desc = it($day->getIterator())
                        ->map(function (TimeRange $range) use ($locale) {
                            return $this->formatTimeRange($range, $locale);
                        })
                        ->flatten()
                        ->toArray();
                }

                return new OpeningHoursItemView(
                    [$formatWeekDay($weekDay)],
                    $desc
                );
            })
            ->toArray();
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('lounge.open-24-7', 'trips'))->setDesc('Open 24 / 7'),
            (new Message('lounge.open-24-hours', 'trips'))->setDesc('Open 24 hours'),
            (new Message('lounge.opened', 'trips'))->setDesc('Opened'),
            (new Message('lounge.closed', 'trips'))->setDesc('Closed'),
            (new Message('lounge.working-hours-vary', 'trips'))->setDesc('Lounge working hours vary'),
            (new Message('lounge.first-dep', 'trips'))->setDesc('The First Departure'),
            (new Message('lounge.last-dep', 'trips'))->setDesc('The Last Departure'),
            (new Message('lounge.before-first-dep', 'trips'))->setDesc('%duration% Before the First Departure'),
            (new Message('lounge.daily', 'trips'))->setDesc('Daily'),
        ];
    }

    private function formatTimeRange(TimeRange $timeRange, string $locale, bool $recursive = true)
    {
        $rangeData = $timeRange->getData();
        $rangeCode = is_array($rangeData) ? $rangeData['code'] ?? null : null;
        $rangeMsg = is_array($rangeData) ? $rangeData['msg'] ?? '' : '';

        switch ($rangeCode) {
            case Builder::CODE_RANGE_UNKNOWN_START:
                return $this->formatRange(
                    $this->translateRange($rangeMsg),
                    $this->formatTime($this->roundTime($timeRange->end()), $locale)
                );

            case Builder::CODE_RANGE_UNKNOWN_END:
                return $this->formatRange(
                    $this->formatTime($this->roundTime($timeRange->start()), $locale),
                    $this->translateRange($rangeMsg)
                );

            case Builder::CODE_RANGE_UNKNOWN_BOTH:
                $parts = explode('|', $rangeMsg);

                return $this->formatRange(
                    $this->translateRange($parts[0] ?? ''),
                    $this->translateRange($parts[1] ?? '')
                );

            case Builder::CODE_UNKNOWN:
                //                return $rangeMsg;
                return null;

            case Builder::CODE_MERGED:
                if (!$recursive) {
                    return null;
                }

                $rangeMerge = is_array($rangeData) ? $rangeData['merge'] ?? [] : [];
                $merges = count($rangeMerge);

                if ($merges === 0) {
                    return null;
                }

                return it($rangeMerge[0])
                    ->map(fn (array $range) => $this->formatTimeRange(TimeRange::fromDefinition($range), $locale, false))
                    ->filterNotNull()
                    ->toArray();

            default:
                return $this->formatRange(
                    $this->formatTime($this->roundTime($timeRange->start()), $locale),
                    $this->formatTime($this->roundTime($timeRange->end()), $locale)
                );
        }
    }

    private function roundTime(Time $time): Time
    {
        if ($time->hours() === 23 && $time->minutes() === 59) {
            return Time::fromString('00:00');
        }

        return $time;
    }

    private function translateRange(string $msg): string
    {
        if (preg_match('/^First Departure($|,)/ims', $msg)) {
            return $this->translator->trans('lounge.first-dep', [], 'trips');
        } elseif (preg_match('/^Last Departure($|,)/ims', $msg)) {
            return $this->translator->trans('lounge.last-dep', [], 'trips');
        } elseif (preg_match('/^(?:([\d\.]+)h)?\s*(?:([\d\.]+)m)?\s+Before First Departure$/ims', $msg, $matches) && (isset($matches[1]) || isset($matches[2]))) {
            $duration = array_filter([
                !empty($matches[1]) ? $this->translator->trans('hours.short-v2', ['%count%' => $matches[1], '%number%' => $matches[1]]) : null,
                !empty($matches[2]) ? $this->translator->trans('minutes.short-v2', ['%count%' => $matches[2], '%number%' => $matches[2]]) : null,
            ]);

            return $this->translator->trans('lounge.before-first-dep', [
                '%duration%' => implode(' ', $duration),
            ], 'trips');
        }

        return $msg;
    }

    private function formatRange(string $a, string $b): RangeView
    {
        return new RangeView($a, $b);
    }

    private function formatTime(Time $time, string $locale): string
    {
        return mb_strtolower($this->localizeService->formatTime(
            date_create('1970-01-01 ' . $time),
            'short',
            $locale
        ));
    }
}
