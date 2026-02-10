<?php

namespace AwardWallet\MainBundle\Globals\Localizer;

use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;

class TwigExtension extends \Twig_Extension
{
    private $localizer;

    /**
     * @var DateTimeIntervalFormatter
     */
    private $intervalFormatter;

    private $defaultTimezone;

    public function __construct(LocalizeService $localizer, DateTimeIntervalFormatter $intervalFormatter)
    {
        $this->localizer = $localizer;
        $this->intervalFormatter = $intervalFormatter;
        $this->defaultTimezone = new \DateTimeZone(date_default_timezone_get());
    }

    public function getFilters()
    {
        return [
            'formatDatetime' => new \Twig_SimpleFilter('formatDatetime', [$this, 'formatDatetimeFilter']),
            'patternDatetime' => new \Twig_SimpleFilter('patternDatetime', [$this, 'patternDateTimeFilter']),
            'formatCurrency' => new \Twig_SimpleFilter('formatCurrency', [$this, 'formatCurrencyFilter']),
            'formatNumber' => new \Twig_SimpleFilter('formatNumber', [$this, 'formatNumberFilter']),
            'leadTZ' => new \Twig_SimpleFilter('leadTZ', [$this, 'leadTZFilter']),
            'formatTimeAgoAngularTag' => new \Twig_SimpleFilter('formatTimeAgoAngularTag', [$this, 'formatTimeAgoAngularTagFilter'], ['is_safe' => ['html']]),
            'formatTimeAgoTag' => new \Twig_SimpleFilter('formatTimeAgoTag', [$this, 'formatTimeAgoTagFilter'], ['is_safe' => ['html']]),
            'formatTimeAgo' => new \Twig_SimpleFilter('formatTimeAgo', [$this, 'formatTimeAgoFilter']),
            'formatRelativeTime' => new \Twig_SimpleFilter('formatRelativeTime', [$this, 'formatRelativeTime']),
            'weekdayShort' => new \Twig_SimpleFilter('weekdayShort', [$this, 'weekdayShortFilter']),
            'localizedCountryById' => new \Twig_SimpleFilter('localizedCountryById', [$this, 'localizedCountryById']),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('localizerLocale', function () {
                return $this->localizer->getLocale();
            }),
        ];
    }

    public function formatDatetimeFilter($arg, $datetype = 'short', $timetype = 'short', $locale = null)
    {
        return $this->localizer->formatDatetime($arg, $datetype, $timetype, $locale);
    }

    public function patternDateTimeFilter($arg, $pattern, $locale = null)
    {
        return $this->localizer->patternDateTime($arg, $pattern, $locale);
    }

    public function formatCurrencyFilter($arg, $currency = null, $round = true, $locale = null)
    {
        return $this->localizer->formatCurrency($arg, $currency, $round, $locale);
    }

    public function formatNumberFilter($arg, $fraction = null, $locale = null)
    {
        return $this->localizer->formatNumber($arg, $fraction, $locale);
    }

    public function leadTZFilter($arg, $user = null)
    {
        return $this->localizer->correctDateTime($arg, $user);
    }

    public function formatTimeAgoAngularTagFilter($datetime, $title, $onlyDate = false, $shortFormat = false, $monthDecimal = false, $locale = null)
    {
        $timeAgo = $this->formatTimeAgo(new \DateTime(), $datetime, $onlyDate, $shortFormat, $monthDecimal, $locale);

        return <<<HTML
<abbr class="timeago" data-role="tooltip" ng-attr-title="{$title}">{$timeAgo}</abbr>
HTML;
    }

    public function formatTimeAgoTagFilter($datetime, $title, $onlyDate = false, $shortFormat = false, $monthDecimal = false, $locale = null)
    {
        $timeAgo = $this->formatTimeAgo(new \DateTime(), $datetime, $onlyDate, $shortFormat, $monthDecimal, $locale);

        return <<<HTML
<abbr class="timeago" data-role="tooltip" title="{$title}">{$timeAgo}</abbr>
HTML;
    }

    public function formatTimeAgoFilter($datetime, $onlyDate = false, $shortFormat = false, $monthDecimal = false, $locale = null)
    {
        return $this->formatTimeAgo(new \DateTime(), $datetime, $onlyDate, $shortFormat, $monthDecimal, $locale);
    }

    public function formatRelativeTime(
        \DateTime $fromDate,
        \DateTime $toDate,
        bool $onlyDate = false,
        bool $shortFormat = false,
        bool $monthDecimal = false,
        ?string $locale = null
    ): string {
        return $this->formatTimeAgo($fromDate, $toDate, $onlyDate, $shortFormat, $monthDecimal, $locale);
    }

    public function weekdayShortFilter($date)
    {
        $shortDate = $this->localizer->formatDatetime($date, 'short', 'none');
        $weekday = $this->localizer->getWeekday($date);

        return "$weekday ($shortDate)";
    }

    public function localizedCountryById($id, $locale): ?string
    {
        $countries = $this->localizer->getLocalizedCountries($locale);

        return $countries[$id] ?? null;
    }

    public function getName()
    {
        return 'Twig filters for localizer';
    }

    private function formatTimeAgo($fromDate, $toDate, $onlyDate = false, $shortFormat = false, $monthDecimal = false, $locale = null)
    {
        if (!$fromDate instanceof \DateTime) {
            $d = new \DateTime();
            $d->setTimestamp(strtotime($fromDate));
            $d->setTimezone($this->defaultTimezone);
            $fromDate = $d;
        }

        if (!$toDate instanceof \DateTime) {
            $d = new \DateTime();
            $d->setTimestamp(strtotime($toDate));
            $d->setTimezone($this->defaultTimezone);
            $toDate = $d;
        }

        $fromToday = $fromDate->format('Y-m-d') === date('Y-m-d');

        if ($shortFormat) {
            if ($onlyDate) {
                return $this->intervalFormatter->shortFormatViaDates(
                    $fromDate,
                    $toDate,
                    true,
                    $fromToday,
                    $locale
                );
            } else {
                return $this->intervalFormatter->shortFormatViaDateTimes(
                    $fromDate,
                    $toDate,
                    true,
                    $fromToday,
                    $locale
                );
            }
        } else {
            if ($onlyDate) {
                return $this->intervalFormatter->longFormatViaDates(
                    $fromDate,
                    $toDate,
                    true,
                    $fromToday,
                    $locale
                );
            } else {
                return $this->intervalFormatter->longFormatViaDateTimes(
                    $fromDate,
                    $toDate,
                    true,
                    $fromToday,
                    $locale
                );
            }
        }
    }
}
