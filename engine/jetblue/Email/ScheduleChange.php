<?php

namespace AwardWallet\Engine\jetblue\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class ScheduleChange extends \TAccountChecker
{
    public $mailFiles = "jetblue/it-902925314.eml, jetblue/it-903278594.eml";

    private $detectFrom = "info@change.jetblue.com";
    private $detectSubject = [
        'JetBlue flight schedule change - No action needed',
        'ACTION NEEDED: JetBlue flight schedule change',
    ];
    private $detectBody = [
        'en' => [
            "We've made some adjustments to our schedule",
            "We've made some changes to our schedule which will impact your upcoming trip",
        ],
    ];

    private $dateRelative;

    private $lang = 'en';
    private static $dict = [
        'en' => [
            'Hi'                        => ['Hi', 'Hi,'],
            'Your New Flight Itinerary' => ['Your New Flight Itinerary', 'Your New Flight Itinerary:'],
            'on confirmation code:'     => ['on confirmation code:', 'upcoming trip with us on confirmation code'],
            'Flights'                   => 'Flights',
            'Departure'                 => 'Departure',
        ],
    ];

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
//        foreach ($this->detectBody as $lang => $detectBody) {
//            if ($this->http->XPath->query("//*[".$this->contains($detectBody)."]")->length > 0) {
//                $this->lang = $lang;
//                break;
//            }
//        }
        $this->dateRelative = EmailDateHelper::getEmailDate($this, $parser);

        if (!empty($this->dateRelative)) {
            $this->dateRelative = strtotime("- 5 days", $this->dateRelative);
        }

        $this->parseEmail($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//a[contains(@href, '.jetblue.com')]")->length == 0
            && $this->http->XPath->query("//*[contains(., 'JetBlue Airways Corporation')]")->length == 0
        ) {
            return false;
        }

        foreach ($this->detectBody as $detectBody) {
            if ($this->http->XPath->query("//*[" . $this->contains($detectBody) . "]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!isset($headers['from']) || !isset($headers["subject"])
            || (stripos($headers['from'], $this->detectFrom) === false)
        ) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, $this->detectFrom) !== false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dict);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dict);
    }

    private function parseEmail(Email $email)
    {
        $f = $email->add()->flight();

        // General
        $confirmation = $this->http->FindSingleNode("//text()[{$this->contains($this->t("on confirmation code:"))}]/ancestor::td[1]", null, true,
            "/{$this->opt($this->t("on confirmation code:"))}\s*([A-Z\d]{5,7})\./");

        $f->general()
            ->confirmation($confirmation);

        $traveller = $this->http->FindSingleNode("//text()[" . $this->eq($this->t("Hi")) . "][following::text()[normalize-space()][2][" . $this->eq(',') . "]]/following::text()[normalize-space()][1]",
            null, true, "/^\s*([^\d,.]+?)$/");

        if (empty($traveller)) {
            $traveller = $this->http->FindSingleNode("//text()[" . $this->eq($this->t("Hi")) . "]/following::text()[normalize-space()][1][" . $this->contains([',', '.']) . "]",
                null, true, "/^\s*([^\d,.]+?)\s*[,\.]\s*$/");
        }

        $f->general()
            ->traveller($traveller, false);

        //Segments
        $xpath = "//*[" . $this->eq($this->t("Your New Flight Itinerary")) . "]/following::tr[*[normalize-space()][2][" . $this->eq($this->t("Departure")) . "]][*[normalize-space()][1][" . $this->eq($this->t("Flights")) . "]]/following-sibling::tr";
        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            // Airline
            $text = implode("\n", $this->http->FindNodes("td[normalize-space()][1]//text()[normalize-space()]", $root));

            if (preg_match("/^\s*(\d{1,4})\s*{$this->opt($this->t('Operated by:'))}\s*(.+)/", $text, $m)) {
                $s->airline()
                    ->name($m[2])
                    ->number($m[1]);
            }

            $re = "/^\s*(?<name>.+?)\s*\((?<code>[A-Z]{3})\)\s+(?<date>.+)\s+(?<time>\d+:\d+.*)$/";
            $re2 = "/^\s*(?<name>.+)\n(?<date>.+)\s+(?<time>\d+:\d+.*)$/";
            // Departure
            $departure = implode("\n", $this->http->FindNodes("td[normalize-space()][2]//text()[normalize-space()]", $root));

            if (preg_match($re, $departure, $m)
                || preg_match($re2, $departure, $m)
            ) {
                if (!empty($m['code'])) {
                    $s->departure()
                        ->code($m['code']);
                } else {
                    $s->departure()
                        ->noCode();
                }
                $s->departure()
                    ->name($m['name'])
                    ->date($this->normalizeDate($m['date'] . ', ' . $m['time']));
            }
            // Arrival
            $arrival = implode("\n", $this->http->FindNodes("td[normalize-space()][3]//text()[normalize-space()]", $root));

            if (preg_match($re, $arrival, $m)
                || preg_match($re2, $arrival, $m)
            ) {
                if (!empty($m['code'])) {
                    $s->arrival()
                        ->code($m['code']);
                } else {
                    $s->arrival()
                        ->noCode();
                }
                $s->arrival()
                    ->name($m['name'])
                    ->date($this->normalizeDate($m['date'] . ', ' . $m['time']));
            }
        }

        return $email;
    }

    private function t($s)
    {
        if (!isset($this->lang) || !isset(self::$dict[$this->lang][$s])) {
            return $s;
        }

        return self::$dict[$this->lang][$s];
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s*', preg_quote($s));
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
            return "normalize-space(.)='{$s}'";
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), '{$s}')";
        }, $field)) . ')';
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function normalizeDate(?string $date)
    {
        // $this->logger->debug('$date IN = ' . print_r($date, true));
        // $this->logger->debug('$this->dateRelative = ' . print_r($this->dateRelative, true));

        if (empty($date)) {
            return null;
        }
        $year = date('Y', $this->dateRelative);

        $in = [
            // without year, without week
            // May 02, 10:46 AM
            '/^\s*([[:alpha:]]+)\s+(\d{1,2})\s*,\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/ui',
        ];

        // $year - for date without year and with week
        // %year% - for date without year and without week

        $out = [
            '$2 $1 %year%, $3',
        ];

        $date = preg_replace($in, $out, trim($date));

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        // $this->logger->debug('$date RE = ' . print_r($date, true));

        if (!empty($this->dateRelative) && $this->dateRelative > strtotime('01.01.2000') && strpos($date, '%year%') !== false && preg_match('/^\s*(?<date>\d+ \w+) %year%(?:\s*,\s(?<time>\d{1,2}:\d{1,2}.*))?$/', $date, $m)) {
            // $this->logger->debug('$date (no week, no year) = ' . print_r($m['date'], true));
            $date = EmailDateHelper::parseDateRelative($m['date'], $this->dateRelative);

            if (!empty($date) && !empty($m['time'])) {
                return strtotime($m['time'], $date);
            }

            return $date;
        } elseif (($year) > 2000 && preg_match("/^(?<week>\w+), (?<date>\d+ \w+ .+)/u", $date, $m)) {
            // $this->logger->debug('$date (week no year) = ' . print_r($date, true));
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));

            return EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/^\d+ [[:alpha:]]+ \d{4}(,\s*\d{1,2}:\d{2}(?: ?[ap]m)?)?$/ui", $date)) {
            // $this->logger->debug('$date (year) = ' . print_r($date, true));

            return strtotime($date);
        } else {
            return null;
        }

        return null;
    }
}
