<?php

namespace AwardWallet\MainBundle\Globals\Localizer;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\DateTimeHandler;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use DateTime;
use Herrera\Version\Exception\InvalidStringRepresentationException;
use Herrera\Version\Parser;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalizeService implements TranslationContainerInterface
{
    /**
     * self::FORMAT_* constants correspond with \IntlDateFormatter::{FULL, LONG, MEDIUM, SHORT} constants.
     */
    public const FORMAT_FULL = 'full';
    public const FORMAT_LONG = 'long';
    public const FORMAT_MEDIUM = 'medium';
    public const FORMAT_SHORT = 'short';

    private $locale;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var Usr
     */
    private $user;

    private $formatters = [
        'date' => [],
        'currency' => [],
        'number' => [],
    ];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->translator = $container->get('translator');
        $this->container = $container;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;

        if (null !== $locale) {
            self::defineDateTimeFormat($locale);
        }
    }

    public function setTimezone(\DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function getLocale()
    {
        if ($this->locale) {
            return $this->locale;
        }

        $tokenStorage = $this->container->get('security.token_storage');

        if (
            $tokenStorage->getToken() !== null
            && $this->container->get('security.authorization_checker')->isGranted('ROLE_USER')
        ) {
            $this->setUser($tokenStorage->getToken()->getUser());
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if (!$request) {
            return 'en';
        }

        $locale = $request->getLocale();

        if ($this->user) {
            $locale = $this->user->getLocale();
        }

        // TODO: remove after regional settings implementation on mobile
        if (
            (new RequestMatcher('/m/api/'))->matches($request)
            && !StringUtils::isEmpty($providedVersion = $request->headers->get(MobileHeaders::MOBILE_VERSION))
        ) {
            try {
                $version = Parser::toVersion($providedVersion);
            } catch (InvalidStringRepresentationException $e) {
                $version = null;
            }

            $apiVersioning = $this->container->get('aw.api.versioning');
            $apiVersioning->setVersion($version);
            $apiVersioning->setVersionsProvider(new MobileVersions($request->headers->get(MobileHeaders::MOBILE_PLATFORM, '')));

            if (
                $apiVersioning->supports(MobileVersions::TIMELINE_BLOCKS_V2)
                && !$apiVersioning->supports(MobileVersions::REGIONAL_SETTINGS)
                && !StringUtils::isEmpty($mobileLocale = $request->getLocale())
            ) {
                $locale = $mobileLocale;
            }
        }

        $this->setLocale($locale);

        return $this->locale;
    }

    public function setUser(?Usr $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param self::FORMAT_* $datetype
     * @param self::FORMAT_* $timetype
     */
    public function formatDateTime($param, $datetype = self::FORMAT_SHORT, $timetype = self::FORMAT_SHORT, $locale = null)
    {
        $timezone = (is_object($param) && $param instanceof \DateTime) ? $this->getTimezoneName($param->getTimezone()) : null;
        $formatter = $this->getFormatter($datetype, $timetype, $locale, $timezone);

        return $formatter->format($param);
    }

    /**
     * @param self::FORMAT_* $datetype
     */
    public function formatDate($param, $datetype = self::FORMAT_SHORT, $locale = null)
    {
        return $this->formatDateTime($param, $datetype, null, $locale);
    }

    /**
     * @param self::FORMAT_* $timetype
     */
    public function formatTime($param, $timetype = self::FORMAT_SHORT, $locale = null)
    {
        return $this->formatDateTime($param, null, $timetype, $locale);
    }

    /**
     * @param self::FORMAT_* $type
     * @param null $locale
     * @return \DateTime
     */
    public function parseTime(string $time, $type = self::FORMAT_SHORT, $locale = null)
    {
        $formatter = $this->getFormatter(null, $type, $locale);
        $timestamp = $formatter->parse($time);

        if (false === $timestamp) {
            throw new \RuntimeException("Failed to parse time string '$time'");
        }
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($timestamp);

        return $dateTime;
    }

    public function patternDateTime($param, $pattern, $locale = null)
    {
        $timezone = (is_object($param) && $param instanceof \DateTime) ? $this->getTimezoneName($param->getTimezone()) : null;
        $locale = $locale ?? $this->getLocale();
        $pattern = (new \IntlDateTimePatternGenerator($locale))->findBestPattern($pattern);
        $formatter = \IntlDateFormatter::create(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            $timezone,
            null,
            $pattern
        );

        if (!$formatter) {
            // TODO: hotfix!!!
            $formatter = \IntlDateFormatter::create(
                $locale,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                "UTC",
                null,
                $pattern
            );
        }

        return $formatter->format($param);
    }

    public function formatCurrency($number, $currency = null, $round = true, $locale = null)
    {
        $locale = $locale ?? $this->getLocale();
        $key = $locale;

        if (isset($this->formatters['currency'][$key])) {
            $formatter = $this->formatters['currency'][$key];
        } else {
            $formatter = \NumberFormatter::create($locale, \NumberFormatter::CURRENCY);
            $this->formatters['currency'][$key] = $formatter;
        }
        $fraction = $formatter->getAttribute(\NumberFormatter::MAX_FRACTION_DIGITS);

        // refs #23183, logs
        if (!method_exists($formatter, 'getTextAttribute')) {
            throw new \RuntimeException(sprintf('Method getTextAttribute() not found in %s, number: %s, currency: %s, locale: %s', get_class($formatter), $number, $currency ?? 'null', $locale ?? 'null'));
        }

        $curCode = $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE);
        $pattern = $formatter->getPattern();

        if (isset($currency)) {
            $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency);

            try {
                $symbol = Currencies::getSymbol($currency);
            } catch (MissingResourceException $e) {
                $symbol = null;
            }

            if (mb_strlen($symbol) > 1) {
                $formatter->setPattern(
                    str_replace('¤', '¤ ', $formatter->getPattern())
                );
            }
        }

        if ($round && $number == intval($number)) {
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 0);
        }
        $result = $formatter->format($number);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $fraction);
        $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $curCode);
        $formatter->setPattern($pattern);

        return $result;
    }

    public function formatCurrencyShort($number, $currency = null, $ranges = ['th' => 1000, 'h' => 100], $locale = null)
    {
        if (!is_numeric($number)) {
            return $this->formatCurrency($number, $currency, true, $locale);
        }

        if ($number >= ($ranges['th'] ?? 1000)) {
            $number = (int) ($number / 1000);
            $formatted = $this->formatCurrency($number, $currency, true, $locale);

            return trim($formatted, '0') . 'K';
        }

        if ($number > ($ranges['h'] ?? 100)) {
            return $this->formatCurrency((int) $number, $currency, true, $locale);
        }

        return $this->formatCurrency($number, $currency, true, $locale);
    }

    public function formatNumber($number, $fraction = null, $locale = null)
    {
        $formatter = \NumberFormatter::create($locale ?? $this->getLocale(), \NumberFormatter::DECIMAL);

        if (isset($fraction)) {
            if ($number != intval($number)) {
                $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $fraction);
            }
        }

        return $formatter->format($number);
    }

    public function formatNumberWithFraction($number, $fraction = 2, $locale = null)
    {
        $formatter = \NumberFormatter::create($locale ?? $this->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $fraction);

        return $formatter->format($number);
    }

    /**
     * @deprecated use formatLargeNumber
     */
    public function formatNumberShort($number, $fraction = 3): string
    {
        $base = 1000;

        if ($base > $number) {
            return $number;
        }

        $abbrs = ['', 'K', 'M', 'B', 'T', 'Qa', 'Qi'];
        $divisors = [];

        foreach ($abbrs as $key => $abbr) {
            $divisors[pow(1000, $key)] = $abbr;
        }

        foreach ($divisors as $divisor => $shorthand) {
            if (abs($number) < ($divisor * 1000)) {
                return number_format($number / $divisor, $fraction) . $shorthand;
            }
        }

        return $number;
    }

    public function formatLargeNumber($number, $fraction = null, $locale = null)
    {
        $base = 1000;

        if ($base > $number) {
            return $this->formatNumber($number, $fraction, $locale);
        }

        $abbrs = ['', 'K', 'M', 'B', 'T', 'Qa', 'Qi'];
        $divisors = [];

        foreach ($abbrs as $key => $abbr) {
            $divisors[pow(1000, $key)] = $abbr;
        }

        foreach ($divisors as $divisor => $shorthand) {
            if (abs($number) < ($divisor * 1000)) {
                if (round($number / $divisor, $fraction) == round($number / $divisor)) {
                    $fraction = 0;
                }

                return $this->formatNumber($number / $divisor, $fraction, $locale) . $shorthand;
            }
        }

        return $this->formatNumber($number, $fraction, $locale);
    }

    /**
     * @param \DateTime|string $datetime
     * @param Usr|null $user
     */
    public function correctDateTime($datetime, $user = null)
    {
        $user = ($user) ? $user : $this->getUser();
        $datetime = $this->getDateTime($datetime);
        $datetimeClone = clone $datetime;
        $tz = $this->timezone ?: $this->getUserDateTimeZone($user);

        return $datetimeClone->setTimezone($tz);
    }

    public function getUserDateTimeZone($user = null, $default = 'Etc/GMT')
    {
        $user = ($user) ? $user : $this->getUser();

        if (is_object($user) && $user instanceof Usr) {
            return $user->getDateTimeZone($default);
        }

        return new \DateTimeZone($default);
    }

    public function getWeekday($date)
    {
        return $this->patternDateTime($date, 'EEEE');
    }

    public function getThousandsSeparator($locale = null)
    {
        return \NumberFormatter::create($locale ?? $this->getLocale(), \NumberFormatter::PATTERN_DECIMAL)->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    public function getDecimalPoint($locale = null)
    {
        return \NumberFormatter::create($locale ?? $this->getLocale(), \NumberFormatter::PATTERN_DECIMAL)->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    public function getLocalizedCountries(?string $locale = null): array
    {
        $cacheManager = $this->container->get(CacheManager::class);
        $cacheKey = 'listLocalizedCountriesById_';

        if (!$locale) {
            $locale = $this->getUser() instanceof Usr ? $this->getUser()->getLanguage() : $this->getLocale();
        }

        $cacheKey .= $locale;
        $cacheRef = new CacheItemReference(
            $cacheKey,
            [],
            function () use ($locale) {
                $regions = [];
                $allLocales = Locales::getNames($locale);

                foreach ($allLocales as $localeCode => $localeName) {
                    $countryName = \Locale::getDisplayRegion($localeCode, $locale);
                    $localeData = \Locale::parseLocale($localeCode);

                    if (isset($localeData['region']) && !array_search($countryName, $regions)) {
                        $regions[$localeData['region']] = $countryName;
                    }
                }

                $result = [];
                $countries = $this->container->get('doctrine.orm.entity_manager')->getRepository(\AwardWallet\MainBundle\Entity\Country::class)->getCountriesByCode();

                foreach ($countries as $code => $row) {
                    $result[$row['CountryID']] = array_key_exists($code, $regions) ? $regions[$code] : $row['Name'];
                }
                asort($result);

                return $result;
            }
        );
        $cacheRef->setExpiration(60 * 60);

        return $cacheManager->load($cacheRef);
    }

    public function isFormattedNumber($value): bool
    {
        if (is_int($value)
            || is_float($value)
            || ((string) (float) $value) === (string) $value) {
            return false;
        }

        if (false !== strpos($value, ',')
            || false !== strpos($value, '.')
            || false !== strpos($value, ' ')) {
            return true;
        }

        return false;
    }

    public function setRegionalSettings(): void
    {
        self::defineDateTimeFormat($this->getLocale());
    }

    public static function defineDateTimeFormat(?string $locale = null): void
    {
        $dateFormat = DateTimeHandler::DATEFORMAT_US;

        if (null !== $locale && !str_starts_with($locale, 'en')) {
            $dateFormat = DateTimeHandler::DATEFORMAT_EU;
        }

        $dateTime = new DateTimeHandler();
        $dateFormats = $dateTime->getDateFormats($dateFormat);

        if (!defined('DATE_TIME_FORMAT')) { // ???
            define("DATE_TIME_FORMAT", $dateFormats['datetime']);
        }

        if (!defined('DATE_FORMAT')) { // 4, mobile; 300, old
            define("DATE_FORMAT", $dateFormats['date']);
        }

        if (!defined('TIME_FORMAT')) { // ???
            define("TIME_FORMAT", $dateFormats['time']);
        }

        if (!defined('MONTH_DAY_FORMAT')) { // 1, old trips
            define("MONTH_DAY_FORMAT", $dateFormats['monthday']);
        }

        if (!defined('DATE_LONG_FORMAT')) { // 1, mobile; old site
            define("DATE_LONG_FORMAT", $dateFormats['datelong']);
        }

        if (!defined('DATE_SHORT_FORMAT')) { // 1, old site
            define("DATE_SHORT_FORMAT", $dateFormats['dateshort']);
        }

        if (!defined('TIME_LONG_FORMAT')) { // 20+, old trips
            define("TIME_LONG_FORMAT", $dateFormats['timelong']);
        }

        if (!defined('WEEK_DATE_FORMAT')) { // 1, mobile timeline
            define("WEEK_DATE_FORMAT", $dateFormats['weekdatetime']);
        }

        if (!defined('TIME_WOZ_FORMAT')) { // 4, mobile timeline
            define("TIME_WOZ_FORMAT", $dateFormats['timewithoutzero']);
        }
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('interval_short.years'))->setDesc('1 year|%count% years'),
            (new Message('interval_short.months'))->setDesc('1 month|%count% months'),
            (new Message('interval_short.days'))->setDesc('1 day|%count% days'),
            (new Message('interval_short.hours'))->setDesc('1h|%count%h'),
            (new Message('interval_short.minutes'))->setDesc('1m|%count%m'),
        ];
    }

    /**
     * @param self::FORMAT_* $dateType
     * @param self::FORMAT_* $timeType
     */
    private function getFormatter($dateType = self::FORMAT_SHORT, $timeType = self::FORMAT_SHORT, $locale = null, $timeZone = null)
    {
        $locale = $locale ?? $this->getLocale();
        $key = implode('-', [$locale, $timeZone, $dateType, $timeType]);

        if (isset($this->formatters['date'][$key])) {
            $formatter = $this->formatters['date'][$key];
        } else {
            $formatter = \IntlDateFormatter::create(
                $locale,
                $this->getType($dateType),
                $this->getType($timeType),
                $timeZone
            );

            if (!$formatter) {
                // TODO: hotfix!!!
                $formatter = \IntlDateFormatter::create(
                    $locale,
                    $this->getType($dateType),
                    $this->getType($timeType),
                    'UTC'
                );
            }
            $this->formatters['date'][$key] = $formatter;
        }

        return $formatter;
    }

    private function getDateTime($string)
    {
        if (is_string($string) || is_numeric($string)) {
            if (is_numeric($string)) {
                $string = "@" . $string;
            }
            $string = new \DateTime($string);
        }

        return $string;
    }

    /**
     * @param self::FORMAT_* $type
     */
    private function getType($type)
    {
        switch ($type) {
            case self::FORMAT_FULL:
                $result = \IntlDateFormatter::FULL;

                break;

            case self::FORMAT_SHORT:
                $result = \IntlDateFormatter::SHORT;

                break;

            case self::FORMAT_MEDIUM:
                $result = \IntlDateFormatter::MEDIUM;

                break;

            case self::FORMAT_LONG:
                $result = \IntlDateFormatter::LONG;

                break;

            default:
                $result = \IntlDateFormatter::NONE;
        }

        return $result;
    }

    private function getDateTimeFormat($type)
    {
        //        if (!defined('DATE_TIME_FORMAT'))
        //            define( "DATE_TIME_FORMAT", $dateFormats['datetime'] );
        //        if (!defined('DATE_FORMAT'))
        //            define( "DATE_FORMAT", $dateFormats['date'] );
        //        if (!defined('TIME_FORMAT'))
        //            define( "TIME_FORMAT", $dateFormats['time'] );
        //        if (!defined('MONTH_DAY_FORMAT'))
        //            define( "MONTH_DAY_FORMAT", $dateFormats['monthday'] );
        //        if (!defined('DATE_LONG_FORMAT'))
        //            define( "DATE_LONG_FORMAT", $dateFormats['datelong'] );
        //        if (!defined('DATE_SHORT_FORMAT'))
        //            define( "DATE_SHORT_FORMAT", $dateFormats['dateshort'] );
        //        if (!defined('TIME_LONG_FORMAT'))
        //            define( "TIME_LONG_FORMAT", $dateFormats['timelong'] );
        //        if (!defined('WEEK_DATE_FORMAT'))
        //            define( "WEEK_DATE_FORMAT", $dateFormats['weekdatetime'] );
        //        if (!defined('TIME_WOZ_FORMAT'))
        //            define( "TIME_WOZ_FORMAT", $dateFormats['timewithoutzero'] );
        //  EEEE, MMMM d, y 'at' h:mm:ss a zzzz | MMMM d, y 'at' h:mm:ss a z | MMM d, y, h:mm:ss a | M/d/yy, h:mm a |
        //  Saturday, January 31, 2015 at 2:30:00 PM GMT | January 31, 2015 at 2:30:00 PM GMT | Jan 31, 2015, 2:30:00 PM | 1/31/15, 2:30 PM |
        //  EEEE? d MMMM? y h:mm:ss a zzzz | d MMMM? y h:mm:ss a z | dd?/MM?/y h:mm:ss a | d?/M?/y h:mm a |
        //                  'datetime' => "F d, Y H:i:s", January 01, 2100 10:10:10
        //					'date' => "m/d/Y", 01/10/2100
        //					'dateshort' => "m/d/y", 01/10/00
        //					'time' => "h:ia", 01:10am
        //					'timewithoutzero' => "g:i A", 1:10 AM
        //					'datelong' => "F j, Y", January 1, 2100
        //					'monthday' => "F j", January 1
        //					'timelong' => "g:i A", 1:10 AM
        //					'datetimelong' => "F j, Y g:i A",
        //					'weekdatetime' => "D m/d" Mon 01/10
        switch ($type) {
            case 'datetime':
                $result = [self::FORMAT_MEDIUM, self::FORMAT_MEDIUM]; // d > dd, h > hh, H > HH, k > kk, K > KK

                break;

            case 'date':
                $result = [self::FORMAT_SHORT, null]; // yy > y, M > MM

                break;

            case 'dateshort':
                $result = [self::FORMAT_SHORT, null]; // M > MM

                break;

            case 'time':
                $result = [null, self::FORMAT_SHORT]; // trim spaces, strtolower, h > hh, H > HH, k > kk, K > KK

                break;

            case 'timewithoutzero':
                $result = [null, self::FORMAT_SHORT];

                break;

            case 'datelong':
                $result = [self::FORMAT_MEDIUM, null];

                break;

            case 'monthday':
                $result = [self::FORMAT_MEDIUM, null]; // trim y, trim delimeter

                break;

            case 'timelong':
                $result = [null, self::FORMAT_SHORT];

                break;

            case 'datetimelong':
                $result = [self::FORMAT_MEDIUM, self::FORMAT_SHORT];

                break;

            case 'weekdatetime':
                $result = [self::FORMAT_SHORT, null]; // add E, trim y, trim delimeter

                break;

            default:
                $result = \IntlDateFormatter::NONE;
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getTimezoneName(\DateTimeZone $timezone)
    {
        $name = $timezone->getName();

        if (
            ('+' === $name[0])
            || ('-' === $name[0])
        ) {
            $name = "GMT{$name}";
        } elseif (is_numeric($name[0])) {
            $name = "GMT+{$name}";
        }

        return $name;
    }
}
