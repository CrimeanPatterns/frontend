<?php

namespace AwardWallet\Engine\mileageplus\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Notifications extends \TAccountChecker
{
    public $mailFiles = "mileageplus/it-136332457.eml, mileageplus/it-136448489.eml, mileageplus/it-149532885.eml, mileageplus/it-151064661.eml, mileageplus/it-208552436.eml, mileageplus/it-909688561.eml";

    private $detectFrom = 'notifications@united.com';

    private $detectSubject = [
        ' is departing soon', //  Your flight to Orlando is departing soon
        'Check in now for your flight to ', // Check in now for your flight to New York
        'Check in now for your flight from ',
        ' is delayed', // Flight UA149 to Sao Paulo (GRU) is delayed
        'We’ve rebooked you on a new flight',
    ];

    private $subjectVal = '';
    private $emailDate;

    private $xpath = [
        'time' => '(starts-with(translate(normalize-space(),"0123456789： ","∆∆∆∆∆∆∆∆∆∆:"),"∆:∆∆") or starts-with(translate(normalize-space(),"0123456789： ","∆∆∆∆∆∆∆∆∆∆:"),"∆∆:∆∆"))',
    ];

    private $patterns = [
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->subjectVal = $parser->getSubject();

        $this->emailDate = strtotime("-2 day", strtotime($parser->getDate()));
        $this->parseEmail($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class));

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, $this->detectFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers['from'], $this->detectFrom) === false) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if (stripos(implode('', $parser->getFrom()), $this->detectFrom) === false
            && $this->http->XPath->query('//a[normalize-space()="My United"]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"United Airlines. All rights reserved") or contains(normalize-space(),"All rights reserved. United Airlines")]')->length === 0
        ) {
            return false;
        }

        return $this->findSegments1()->length > 0 || $this->findSegments2()->length > 0 || $this->findSegments3()->length > 0;
    }

    private function findSegments1(): \DOMNodeList
    {
        // examples: ???
        return $this->http->XPath->query("//img[contains(@src,'/plane-line.')]/ancestor::*[position()<7][count(*[normalize-space()])=3][*[normalize-space()][1][not(.//img)] and *[normalize-space()][2][//img[contains(@src,'/plane-line.')]] and *[normalize-space()][3][not(.//img)]]");
    }

    private function findSegments2(): \DOMNodeList
    {
        // examples: ???
        return $this->http->XPath->query("//img[contains(@src,'/plane-line.')]/ancestor::*[position()<7][count(*[normalize-space()])=2][*[normalize-space()][1][not(.//img)] and *[normalize-space()][2][not(.//img)]][contains(translate(normalize-space(),'0123456789','∆∆∆∆∆∆∆∆∆∆'),'∆∆:∆∆')]");
    }

    private function findSegments3(): \DOMNodeList
    {
        // examples: it-909688561.eml
        return $this->http->XPath->query("//*[count(*[{$this->xpath['time']}])=2]/ancestor::*[ preceding-sibling::*[normalize-space()] or following-sibling::*[normalize-space()] ][1][not(.//img)]");
    }

    private function parseEmail(Email $email): void
    {
        $f = $email->add()->flight();

        if (preg_match("/Flight (?:[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*\d+ to .{3,100} is (?<status>delayed|delayed further)(?:\s*[,.:;!?]|$)/i", $this->subjectVal, $m)
            || preg_match("/We’ve (?<status>rebooked) you on a new flight/iu", $this->subjectVal, $m)
        ) {
            $f->general()->status($m['status']);
        }

        $confirmation = $this->http->FindSingleNode("//text()[{$this->starts(['Confirmation number:', 'Confirmation:'])}]");

        if (preg_match("/^(Confirmation(?:\s+number)?)\s*:\s*([A-Z\d]{5,8})\s*$/", $confirmation, $m)) {
            $f->general()->confirmation($m[2], $m[1]);
        } elseif (empty($confirmation) && $this->http->XPath->query("//*[{$this->contains('Confirmation')}]")->length === 0) {
            $f->general()
                ->noConfirmation();
        }

        $roots = $this->findSegments1();

        foreach ($roots as $root) {
            $s = $f->addSegment();

            // Airline
            if (preg_match("/^\s*(?<al>[A-Z\d][A-Z]|[A-Z][A-Z\d])\s*(?<fn>\d{1,5})\s*(?:operating as .+? (?<oal>[A-Z\d][A-Z]|[A-Z][A-Z\d])(?<ofn>\d{1,5}))?\s*$/", $this->http->FindSingleNode("*[normalize-space()][2]", $root), $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fn']);

                if (!empty($m['oal'])) {
                    $s->airline()
                        ->carrierName($m['oal'])
                        ->carrierNumber($m['ofn']);
                }
            }
            // Departure
            $depart = $this->http->FindNodes("*[normalize-space()][1]//tr[not(.//tr)][normalize-space()]", $root);
            $s->departure()
                ->code($this->re("/\(([A-Z]{3})\)\s*$/", $depart[1] ?? ''))
                ->name($depart[1] ?? '')
                ->date($this->normalizeDate($depart[0] ?? false));

            // Arrival
            $arrival = $this->http->FindNodes("*[normalize-space()][3]//tr[not(.//tr)][normalize-space()]", $root);
            $s->arrival()
                ->code($this->re("/\(([A-Z]{3})\)\s*$/", $arrival[1] ?? ''))
                ->name($arrival[1] ?? '')
                ->date($this->normalizeDate($arrival[0] ?? false));
        }

        $roots = $this->findSegments2();

        foreach ($roots as $root) {
            $s = $f->addSegment();

            // Airline
            if (preg_match("/^\s*(?<al>[A-Z\d][A-Z]|[A-Z][A-Z\d])\s*(?<fn>\d{1,5})\s*$/", $this->http->FindSingleNode("*[normalize-space()][1]/descendant::tr[not(.//tr)][normalize-space()][1]", $root), $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fn']);
            }
            // Departure
            $depart = $this->http->FindNodes("*[normalize-space()][1]//tr[not(.//tr)][normalize-space()][position() > 1]", $root);
            $s->departure()
                ->code($this->re("/\(([A-Z]{3})\)\s*$/", $depart[1] ?? ''))
                ->name($depart[1] ?? '')
                ->date($this->normalizeDate($depart[0] ?? false));

            // Arrival
            $arrival = $this->http->FindNodes("*[normalize-space()][2]//tr[not(.//tr)][normalize-space()][position() > 1]", $root);
            $s->arrival()
                ->code($this->re("/\(([A-Z]{3})\)\s*$/", $arrival[1] ?? ''))
                ->name($arrival[1] ?? '')
                ->date($this->normalizeDate($arrival[0] ?? false));
        }

        $roots = $this->findSegments3();

        foreach ($roots as $root) {
            $s = $f->addSegment();

            $date = strtotime($this->http->FindSingleNode("preceding-sibling::*[normalize-space()][2]", $root, true, "/^[[:alpha:]]+[,.\s]*\d{1,2}[.\s]*,\s*\d{4}$/u"));

            $flight = $this->http->FindSingleNode("preceding-sibling::*[normalize-space()][1]", $root);

            if ( preg_match("/^(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<number>\d+)$/", $flight, $m) ) {
                $s->airline()->name($m['name'])->number($m['number']);
            } elseif ( preg_match("/^(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<number>\d+)\s+(?i)operated by\s+(?<operator>\S.*?\S)(?:\s+dba\b|$)/", $flight, $m) ) {
                $s->airline()->name($m['name'])->number($m['number'])->operator($m['operator']);
            }

            $xpathTimeCell = "descendant-or-self::*[count(*[{$this->xpath['time']}])=2][1]/*[{$this->xpath['time']}]";
            $timeDep = $this->http->FindSingleNode($xpathTimeCell . "[1]", $root, true, "/^{$this->patterns['time']}/");
            $timeArr = $this->http->FindSingleNode($xpathTimeCell . "[2]", $root, true, "/^{$this->patterns['time']}/");

            if ($date && $timeDep) {
                $s->departure()->date(strtotime($timeDep, $date));
            }

            if ($date && $timeArr) {
                $s->arrival()->date(strtotime($timeArr, $date));
            }

            $xpathAirports = "following-sibling::*[normalize-space()][1]/descendant-or-self::*[ *[normalize-space()][2] ][1]/*[normalize-space()]";
            $airportDep = $this->http->FindSingleNode($xpathAirports . "[1]", $root);
            $airportArr = $this->http->FindSingleNode($xpathAirports . "[position()>1][last()]", $root);

            if (preg_match('/^[A-Z]{3}$/', $airportDep)) {
                $s->departure()->code($airportDep);
            } else {
                $s->departure()->name($airportDep);
            }

            if (preg_match('/^[A-Z]{3}$/', $airportArr)) {
                $s->arrival()->code($airportArr);
            } else {
                $s->arrival()->name($airportArr);
            }

            $durationValues = array_filter($this->http->FindNodes($xpathAirports, $root, "/^(?:[,\s]*\d{1,3}\s*[hm]){1,2}$/i"));

            if (count($durationValues) === 1) {
                $duration = array_shift($durationValues);
                $s->extra()->duration($duration);
            }
        }
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'contains(normalize-space(.),"' . $s . '")';
        }, $field));
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function normalizeDate($date)
    {
        if (empty($this->emailDate) || empty($date)) {
            return null;
        }
        $in = [
            //4:20 p.m. January 2
            "/^\s*([\d:]+)\s*([ap])[. ]?m[. ]?\s+([[:alpha:]]+)\s+(\d{1,2})\s*$/i",
        ];
        $out = [
            "$1$2m $4 $3 ",
        ];
        $date = preg_replace($in, $out, $date);

//        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
//            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
//                $date = str_replace($m[1], $en, $date);
//            }
//        }

        return EmailDateHelper::parseDateRelative($date, $this->emailDate);
    }
}
