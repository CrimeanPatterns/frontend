<?php

namespace AwardWallet\Engine\netjets\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FlightJunk extends \TAccountChecker
{
    public $mailFiles = "netjets/it-858540018.eml, netjets/it-928850361.eml";

    public $detectFrom = '@netjets.com';

    public $detectSubject = [
        'NetJets itinerary: ',
    ];

    public $pdfNamePattern = ".*\.pdf";

    public $patterns = [
        'phone'     => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52    |    (+351) 21 342 09 07    |    713.680.2992
        'traveller' => '[[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]]',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'PASSENGER MANIFEST' => ['PASSENGER MANIFEST', 'PASSENGERS AND TRAVEL DOCUMENTS'],
        ],
    ];

    private $detectBody = [
        'en' => [
            'REQUEST #:',
            'DISTANCE:',
            'EST. TRAVEL:',
            'FLIGHT RULE:',
            'PASSENGER NAME:',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        // detect provider
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], 'NetJets') === false
        ) {
            return false;
        }

        // detect format
        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect provider
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'fly.netjets.com')]")->length === 0
            && stripos($parser->getSubject(), 'NetJets') === false
        ) {
            return false;
        }

        // detect format
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Reservation:')]")->length > 0
            && $this->http->XPath->query("//text()[contains(normalize-space(), 'Request:')]")->length > 0
            && $this->http->XPath->query("//text()[contains(normalize-space(), 'Please arrive') and contains(normalize-space(), 'prior to your departure')]")->length > 0
            && $this->http->XPath->query("//text()[contains(normalize-space(), 'Requested Aircraft:')]")->length > 0
            && $this->http->XPath->query("//text()[contains(normalize-space(), 'Lead Passenger:')]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]netjets\.com$/', $from) > 0;
    }

    public function detectPdf($text)
    {
        // detect provider
        if ($this->containsText($text, ['NetJets', '@netjets.com']) === false) {
            return false;
        }

        // detect format
        foreach ($this->detectBody as $detectBody) {
            // if array -  all phrase of array
            if (is_array($detectBody)) {
                foreach ($detectBody as $phrase) {
                    if (strpos($text, $phrase) === false) {
                        continue 2;
                    }
                }

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->parseEmailHtml($email);

        // collect all travellers
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text)) {
                $this->parseEmailPdf($email, $text);
            }
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function parseEmailHtml(Email $email): void
    {
        $f = $email->add()->flight();

        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Reservation:')]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Reservation:'))}\s*(\d{5,})$/"));

        if (!empty($f->getConfirmationNumbers())) {
            $f->setNonCommercial(true);
        }

        $travellers = array_unique(array_filter($this->http->FindNodes("//text()[starts-with(normalize-space(), 'Lead Passenger:')]/following::text()[normalize-space()][1]",
            null, "/^({$this->patterns['traveller']})$/")));

        if (!empty($travellers)) {
            $f->setTravellers($travellers);
        }

        $phones = array_filter($this->http->FindNodes("//text()[contains(normalize-space(), 'by contacting your team at')]/ancestor::td[1]/descendant::text()[normalize-space()][ preceding::text()[contains(normalize-space(), 'by contacting your team at')] ]",
            null, "/^({$this->patterns['phone']})$/"));

        foreach ($phones as $phone) {
            $desc = $this->http->FindSingleNode("//text()[normalize-space() = '{$phone}']/preceding::text()[normalize-space()][1]", null, true, "/^(.{5,25}?)\:?$/");
            $f->addProviderPhone($phone, $desc);
        }

        $IcaoXpath = "translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'llllllllllllllllllllllllll') = 'llll'";
        $nodes = $this->http->XPath->query("//tr[ count(td) = 3 and td[1][ .//text()[{$IcaoXpath}] ] and td[3][ .//text()[{$IcaoXpath}] ] ]/td[2]/table[1]");

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            $s->setConfirmation($this->http->FindSingleNode("(./preceding::text()[starts-with(normalize-space(), 'Request:')])[1]//ancestor::tr[1]",
                $root, true, "/{$this->opt($this->t('Request:'))}\s*(\d{5,})$/"));

            $s->airline()
                ->name('1I')
                ->noNumber();

            $depAirportNodes = $this->http->FindNodes("./preceding::table[1]/descendant::tr[normalize-space()]", $root);

            if (count($depAirportNodes) === 3) {
                if (stripos($depAirportNodes[1], $depAirportNodes[0]) === 0) {
                    $s->departure()
                        ->name($depAirportNodes[1]);
                } else {
                    $s->departure()
                        ->name($depAirportNodes[0] . ', ' . $depAirportNodes[1]);
                }

                $s->setDepCodeIcao($depAirportNodes[2]);
            }

            $arrAirportNodes = $this->http->FindNodes("./following::table[1]/descendant::tr[normalize-space()]", $root);

            if (count($arrAirportNodes) === 3) {
                if (stripos($arrAirportNodes[1], $arrAirportNodes[0]) === 0) {
                    $s->arrival()
                        ->name($arrAirportNodes[1]);
                } else {
                    $s->arrival()
                        ->name($arrAirportNodes[0] . ', ' . $arrAirportNodes[1]);
                }

                $s->setArrCodeIcao($arrAirportNodes[2]);
            }

            $depDate = $this->http->FindSingleNode("(./preceding::text()[starts-with(normalize-space(), 'Request:')])[1]/preceding::text()[string-length()>5][1]", $root, true, "/^(\w+\s*\w+\s*\d+\,\s*\d{4})$/");

            $depTime = $this->http->FindSingleNode("./ancestor::table[1]/following-sibling::table[1]/descendant::text()[contains(normalize-space(), ':')][1]", $root, true, "/^([\d\:]+\s*A?P?M)/");
            $arrTime = $this->http->FindSingleNode("./ancestor::table[1]/following-sibling::table[1]/descendant::text()[contains(normalize-space(), ':')][2]", $root, true, "/^([\d\:]+\s*A?P?M)/");

            if (!empty($depDate) && !empty($depTime) && !empty($arrTime)) {
                $s->departure()
                    ->date(strtotime($depDate . ', ' . $depTime));

                $s->arrival()
                    ->date(strtotime($depDate . ', ' . $arrTime));
            }

            $duration = $this->http->FindSingleNode("(./following::text()[normalize-space()='Travel Time:'])[1]/following::text()[normalize-space()][1]", $root, true, "/^(\d+.*(?:H|M))$/");

            if (!empty($duration)) {
                $s->setDuration($duration);
            }

            $aircraft = trim($this->http->FindSingleNode("(./following::text()[normalize-space()='Requested Aircraft:'])[1]/ancestor::tr[1]", $root, true, "/{$this->opt($this->t('Requested Aircraft:'))}\s*(.+)$/"));

            if (!empty($aircraft)) {
                $s->setAircraft($aircraft);
            }
        }
    }

    public function parseEmailPdf(Email $email, $text): void
    {
        if (count($email->getItineraries()) === 1) {
            $f = $email->getItineraries()[0];
        } else {
            return;
        }

        $segmentTexts = $this->split("/(Flight Itinerary)/", $text);

        foreach ($f->getSegments() as $segment) {
            // search for segments with same confirmation number (Request)
            foreach ($segmentTexts as $segmentText) {
                if (empty($segmentText) || empty($segment->getConfirmation())) {
                    continue;
                }

                if (strpos($segmentText, $segment->getConfirmation()[0]) !== false) {
                    $s = $segment;

                    break;
                }
            }

            if (empty($s)) {
                continue;
            }

            $travellerText = $this->re("/{$this->opt($this->t('PASSENGER MANIFEST'))}\s+(.+?)\s+{$this->opt('DEPARTURE SERVICES')}/s", $segmentText);

            if (preg_match_all("/\d{1,2}\.[ ]+({$this->patterns['traveller']})(?:[ ]{5,}|\n)/", $travellerText, $m)) {
                foreach ($m[1] as $traveller) {
                    $traveller = $this->re("/^(.+?)[ ]{5,}/", $traveller) ?? $traveller;

                    if (!in_array($traveller, array_column($f->getTravellers(), 0))) {
                        $f->addTraveller($traveller);
                    }
                }
            }
        }
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function containsText($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (mb_strpos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && mb_strpos($text, $needle) !== false) {
            return true;
        }

        return false;
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function t($s)
    {
        if (!isset($this->lang) || !isset(self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function split($re, $text)
    {
        $r = preg_split($re, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ret = [];

        if (count($r) > 1) {
            array_shift($r);

            for ($i = 0; $i < count($r) - 1; $i += 2) {
                $ret[] = $r[$i] . $r[$i + 1];
            }
        } elseif (count($r) == 1) {
            $ret[] = reset($r);
        }

        return $ret;
    }
}
