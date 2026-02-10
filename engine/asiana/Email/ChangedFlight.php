<?php

namespace AwardWallet\Engine\asiana\Email;

// TODO: delete what not use
use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class ChangedFlight extends \TAccountChecker
{
    public $mailFiles = "asiana/it-890240433.eml, asiana/it-891342153.eml, asiana/it-895622609.eml";

    public $lang;
    public static $dictionary = [
        'ko' => [
            'Reservation Number :'            => '예약번호 :',
            'Reason :'                        => '변경사유 :',
            'Departure Time:'                 => '출발시간 :',
            'Arrival Time:'                   => '도착시간 :',
            'Details :'                       => '변경내용 :',
            'only departure time has changed' => '편 출발시간이 변경되어 안내드립니다',
        ],
    ];

    private $detectFrom = "asianaairlines@flyasiana.com";
    private $detectSubject = [
        // en
        '[Asiana Airlines] 운항 변경 사항 안내드립니다.(Flight Status Change Alert)',
        '[Asiana Airlines] 아시아나항공에서 알려드립니다 (Notify)',
    ];
    private $detectBody = [
        'ko' => [
            'Flight Status Change Alert',
            '아시아나항공에서 알려드립니다 (Notify)',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]flyasiana\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], '[Asiana Airlines]') === false
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

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//a/@href[{$this->contains(['flyasiana.com'])}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['[Asiana Airlines]', 'Asiana Airlines Reservation / Contacts'])}]")->length === 0
        ) {
            return false;
        }
        // detect Format
        // case 1: use only $this->detectBody
        foreach ($this->detectBody as $detectBody) {
            if ($this->http->XPath->query("//*[{$this->contains($detectBody)}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }
        $this->parseEmailHtml($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["Reservation Number :"]) && !empty($dict["Reason :"])) {
                if ($this->http->XPath->query("//*[{$this->contains($dict['Reservation Number :'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($dict['Reason :'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function parseEmailHtml(Email $email)
    {
        $f = $email->add()->flight();

        // General
        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->contains($this->t('Reservation Number :'))}]", null, true,
                "/{$this->opt($this->t('Reservation Number :'))}\s*[\d\-]+\s*\/\s*([A-Z\d]{5,7})\s*$/"));

        $s = $f->addSegment();

        $flightDate = null;
        $routeText = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Reservation Number :'))}]/preceding::text()[normalize-space()][1]");

        if ($this->lang === 'ko' && (
            preg_match("/^\s*(?<date>.+?)\s+(?<dCode>[A-Z]{3})\s*\((?<dName>.+?)\)\s*[-→]\s*(?<aCode>[A-Z]{3})\s*\((?<aName>.+?)\)\s+(?<al>[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(?<fn>\d{1,4})편 .*변경되어 안내드립니다\./u", $routeText, $m)
            || preg_match("/^\s*(?<date>.+?)\s+(?<dCode>[A-Z]{3})\s*\((?<dName>.+?)\)\s*[-→]\s*(?<aCode>[A-Z]{3})\s*\((?<aName>.+?)\)\s*예약 관련 변경된 편명 및 출발시간 안내드립니다\./u", $routeText, $m)
        )) {
            if (!empty($m['al']) && !empty($m['fn'])) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fn']);
            }

            $date = $this->normalizeDate($m['date']);
            $s->departure()
                ->code($m['dCode'])
                ->name($m['dName']);

            $s->arrival()
                ->code($m['aCode'])
                ->name($m['aName']);
        }

        $dDate = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Departure Time:'))}]", null, true,
            "/{$this->opt($this->t('Departure Time:'))}\s*.+?→\s*(.+)$/");

        if (!empty($dDate)) {
            $s->departure()
                ->date($this->normalizeDate($dDate, $date));
        }
        $aDate = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Arrival Time:'))}]", null, true,
            "/{$this->opt($this->t('Arrival Time:'))}\s*.+?→\s*(.+)$/");

        if (!empty($aDate)) {
            $s->arrival()
                ->date($this->normalizeDate($aDate, $date));
        }

        $details = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Details :'))}]", null, true,
        "/{$this->opt($this->t('Details :'))}\s*.+?→\s*(.+)$/");

        if (!empty($details)) {
            if (preg_match("/^\s*(?<al>[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(?<fn>\d{1,4})편\s+(?<date>.+)/", $details, $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fn']);

                $s->departure()
                    ->date($this->normalizeDate($m['date'], $date));
                $s->arrival()
                    ->noDate();
            } elseif ($this->http->XPath->query("//node()[{$this->contains($this->t('only departure time has changed'))}]")->length > 0) {
                $s->departure()
                    ->date($this->normalizeDate($details, $date));
                $s->arrival()
                    ->noDate();
            }
        }

        return true;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function normalizeDate(?string $dateStr, $relativeDate = null)
    {
        // $this->logger->debug('$dateStr In = '.print_r( $dateStr,true));

        $in = [
            // 2025년 06월 30일
            "/^\s*(\d{4})\s*년\s*(\d{1,2})\s*월\s*(\d{1,2})\s*일\s*$/iu",
            // 07월 01일
            "/^\s*(\d{1,2})\s*월\s*(\d{1,2})\s*일\s*(\d{1,2}:\d{2})\s*$/iu",
        ];

        // $year - for date without year and with week
        // %year% - for date without year and without week

        $out = [
            "$1-$2-$3",
            "%year%-$1-$2, $3",
        ];

        $dateStr = preg_replace($in, $out, trim($dateStr));

        if (preg_match("/^(\D*\d+\s+)([[:alpha:]]+)(\s+(?:%year%|\d{4}))/u", $dateStr, $m)) {
            if ($en = MonthTranslate::translate($m[2], $this->lang)) {
                $dateStr = $m[1] . $en . $m[3];
            }
        }
        // $this->logger->debug('$dateStr replace = '.print_r( $dateStr,true));

        if (strpos($dateStr, '%year%') !== false && !empty($relativeDate) && $relativeDate > strtotime('01.01.2010')
        ) {
            $relativeDate = $relativeDate - 60 * 60 * 24;

            if (preg_match('/^\s*%year%-(?<date>\d+\W\w+)(?:\s*,\s(?<time>\d{1,2}:\d{1,2}.*))?$/u', $dateStr, $m)) {
                $date = EmailDateHelper::parseDateRelative($m['date'], $relativeDate, true, "%Y%-%D%");

                if (!empty($date) && !empty($m['time'])) {
                    return strtotime($m['time'], $date);
                }

                return $date;
            }
        } elseif (preg_match('/^\s*\d{4}-\d{1,2}-\d{2}(?:\s*,\s(?<time>\d{1,2}:\d{1,2}.*))?$/u', $dateStr, $m)) {
            return strtotime($dateStr);
        }

        return null;
    }

    private function opt($field, $delimiter = '/')
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
            return preg_quote($s, $delimiter);
        }, $field)) . ')';
    }
}
