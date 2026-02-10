<?php

namespace AwardWallet\Engine\tapportugal\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Common\Flight;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class LeftForYourTrip extends \TAccountChecker
{
    public $mailFiles = "tapportugal/it-73649513-pt.eml, tapportugal/it-73850730-pt.eml, tapportugal/it-73853884.eml, tapportugal/it-921316248.eml, tapportugal/it-921557843-pt.eml";
    public static $dictionary = [
        'en' => [
            'Booking Code' => 'Booking Code',
            'details of your booking' => ['details of your booking', 'Check everything included in your trip', 'See everything included in your trip'],
            // 'Hello,' => '',
            'Duration' => 'Duration',
            'subjectStart' => 'Booking',
        ],
        'pt' => [
            'Booking Code' => ['Código de Reserva', 'Booking Code', 'Código de reserva'],
            'details of your booking' => ['detalhes da sua reserva', 'Veja tudo o que tem incluído na sua viagem'],
            'Hello,'       => ['Olá,', 'Hello,'],
            'Duration'     => 'Duração',
            'subjectStart' => 'Reserva',
        ],
    ];

    private $detectSubject = [
        // en
        'Booking',
        // pt
        'Reserva',
    ];

    public $lang = 'en';

    private $patterns = [
        'time' => '\d{1,2}[:：h]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
        }

        $f = $email->add()->flight();

        $confirmation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking Code'), "translate(.,':','')")}]/following::text()[normalize-space()][1]", null, true, '/^[A-Z\d]{5,8}$/');

        if ($confirmation) {
            $confirmationTitle = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Booking Code'))}]", null, true, '/^(.+?)[\s:：]*$/u');
            $f->general()->confirmation($confirmation, $confirmationTitle);
        }

        $travellerName = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello,'))}]", null, true, "/{$this->preg_implode($this->t('Hello,'))}\s*({$this->patterns['travellerName']})[,.!\s]*$/u")
            ?? (preg_match("/{$this->preg_implode($this->t('subjectStart'))}(?:\s+TAP)?\s+[A-Z\d]{5,8}\s*[:]+\s*({$this->patterns['travellerName']})\s*,/u", $parser->getSubject(), $m) ? $m[1] : null);
        $f->general()->traveller($travellerName, false);

        $segments1 = $this->findSegments1();

        if ($segments1->length > 0) {
            $this->parseSegments1($f, $segments1);
        } else {
            $segments2 = $this->findSegments2();
            $this->parseSegments2($f, $segments2);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]flytap\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (is_string($dSubject) && array_key_exists('subject', $headers) && strpos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $href = ['.flytap.com/', '.mytap.pt/', 'mkt.flytap.com', 'info.mytap.pt'];

        if ($this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0) {
            return false;
        }

        return $this->assignLang() && $this->findSegments1()->length > 0
            || $this->findSegments2()->length > 0;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function findSegments1(): \DOMNodeList
    {
        return $this->http->XPath->query("//text()[{$this->eq($this->t('Duration'))}]/ancestor::tr[1]");
    }

    private function findSegments2(): \DOMNodeList
    {
        $xpathAirportCode = 'translate(normalize-space(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆")="∆∆∆"';
        return $this->http->XPath->query("//tr[ *[5] and *[normalize-space()][3] ][ *[2]/descendant::text()[{$xpathAirportCode}] or *[4]/descendant::text()[{$xpathAirportCode}] ]");
    }

    private function parseSegments1(Flight $f, \DOMNodeList $segments): void
    {
        // examples: it-73649513-pt.eml, it-73850730-pt.eml, it-73853884.eml
        $this->logger->debug(__FUNCTION__ . '()');

        foreach ($segments as $root) {
            $s = $f->addSegment();

            $date = null;

            $column = 1;

            // Airline
            $node = implode(" ", $this->http->FindNodes("./td[normalize-space()][{$column}]//text()[normalize-space()]", $root));

            if (preg_match("/^\s*(?<al>[A-Z\d][A-Z]|[A-Z][A-Z\d]) (?<fl>\d{1,5})\s+(?<date>.+)/", $node, $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fl'])
                ;
                $date = $m['date'];
            } elseif (preg_match("/^\s*(?<al>[A-Z\d][A-Z]|[A-Z][A-Z\d]) (?<fl>\d{1,5})\s*$/", $node, $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fl'])
                ;
                $column++;
                $date = $this->http->FindSingleNode("./td[normalize-space()][{$column}]/descendant::text()[normalize-space()][1]", $root);
            }
            $column++;

            // Departure
            $node = implode(" ", $this->http->FindNodes("./td[normalize-space()][{$column}]//text()[normalize-space()]", $root));

            if (preg_match("/^\s*(?<time>\d{1,2}h\d{2})\s*(?<code>[A-Z]{3})\s*$/", $node, $m)) {
                $s->departure()
                    ->code($m['code'])
                    ->date((!empty($date)) ? $this->normalizeDate($date . ', ' . $m['time']) : null);
            }
            $column++;

            // Arrival
            $node = implode(" ", $this->http->FindNodes("./td[normalize-space()][{$column}]//text()[normalize-space()]", $root));

            if (preg_match("/^\s*(?<time>\d{1,2}h\d{2})\s*(?<code>[A-Z]{3})\s*$/", $node, $m)) {
                $s->arrival()
                    ->code($m['code'])
                    ->date((!empty($date)) ? $this->normalizeDate($date . ', ' . $m['time']) : null);
            }
            $column++;

            // Extra
            $s->extra()
                ->duration($this->http->FindSingleNode("./td[normalize-space()][{$column}]/descendant::text()[normalize-space()][1]", $root));
        }
    }

    private function parseSegments2(Flight $f, \DOMNodeList $segments): void
    {
        // examples: it-921316248.eml, it-921557843-pt.eml
        $this->logger->debug(__FUNCTION__ . '()');

        $year = $this->http->FindSingleNode("//text()[starts-with(normalize-space(),'©') and contains(.,'TAP')]", null, true, "/^©\s*(\d{4})\s*TAP/");

        foreach ($segments as $root) {
            $s = $f->addSegment();

            $dateVal = $this->http->FindSingleNode("preceding::tr[normalize-space()][1]/descendant::*[not(.//tr[normalize-space()]) and normalize-space()][1]", $root);

            if (preg_match("/^.{4,}\b\d{4}$/", $dateVal)) {
                $date = strtotime($dateVal);
            } elseif ($year && preg_match("/^(?<wday>[-[:alpha:]]+)[,.\s]+(?<date>\d{1,2}[,.\s]+[[:alpha:]]+|[[:alpha:]]+[,.\s]+\d{1,2})$/u", $dateVal, $m)) {
                // Mon, 21 Sep    |    Mon, Sep 21
                $weekDateNumber = WeekTranslate::number1($m['wday']);

                if ($weekDateNumber) {
                    $date = EmailDateHelper::parseDateUsingWeekDay($m['date'] . ' ' . $year, $weekDateNumber);
                }
            } else {
                $date = null;
            }

            $timeDep = $timeArr = null;

            /*
                22h05
                JFK
            */
            $pattern = "/^(?<time>{$this->patterns['time']})\n+(?<airport>[A-Z]{3})$/";

            $departure = implode("\n", $this->http->FindNodes("*[2]/descendant::text()[normalize-space()]", $root));

            if (preg_match($pattern, $departure, $m)) {
                $timeDep = $this->normalizeTime($m['time']);
                $s->departure()->code($m['airport']);
            }

            $arrival = implode("\n", $this->http->FindNodes("*[4]/descendant::text()[normalize-space()]", $root));

            if (preg_match($pattern, $arrival, $m)) {
                $timeArr = $this->normalizeTime($m['time']);
                $s->arrival()->code($m['airport']);
            }

            if ($date && $timeDep) {
                $s->departure()->date(strtotime($timeDep, $date));
            }

            if ($date && $timeArr) {
                $s->arrival()->date(strtotime($timeArr, $date));
            }

            $durationVal = implode("\n", $this->http->FindNodes("*[5]/descendant::text()[normalize-space()]", $root));

            if (preg_match("/^((?:\s*\d{1,3}\s*(?:h|min))+)(?:\n|$)/i", $durationVal, $m)) {
                $s->extra()->duration($m[1], false, true);
            }

            $flight = $this->http->FindSingleNode("*[6]", $root);

            if ( preg_match("/^(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<number>\d+)$/", $flight, $m) ) {
                $s->airline()->name($m['name'])->number($m['number']);
            }
        }
    }

    private function assignLang(): bool
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }

        /*
            Attempt 1
        */

        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) ) {
                continue;
            }
            if (!empty($phrases['Duration']) && $this->http->XPath->query("//*[{$this->eq($phrases['Duration'])}]")->length > 0) {
                $this->lang = $lang;
                return true;
            }
        }

        /*
            Attempt 2
        */

        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) ) {
                continue;
            }
            if (!empty($phrases['details of your booking']) && $this->http->XPath->query("//*[{$this->contains($phrases['details of your booking'])}]")->length > 0) {
                $this->lang = $lang;
                return true;
            }
        }

        return false;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function normalizeDate($date)
    {
//        $this->logger->debug('$date = '.print_r( $date,true));
        $in = [
            // 13 Dec 2020, 18h30
            "/^\s*(\d{1,2})\s+([[:alpha:]]+)\s+(\d{4})\s*,\s*(\d{1,2})h(\d{2})\s*$/iu",
        ];
        $out = [
            "$1 $2 $3, $4:$5",
        ];
        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        return strtotime($date);
    }

    private function normalizeTime(?string $s): string
    {
        $s = preg_replace([
            '/(\d)[ ]*[Hh][ ]*(\d)/', // 01h55    ->    01:55
        ], [
            '$1:$2',
        ], $s);
        return $s;
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function preg_implode($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }

        return '(?:' . implode("|", array_map(function ($v) {return preg_quote($v, '/'); }, $field)) . ')';
    }
}
