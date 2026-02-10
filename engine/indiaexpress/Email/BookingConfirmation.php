<?php

namespace AwardWallet\Engine\indiaexpress\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class BookingConfirmation extends \TAccountChecker
{
    public $mailFiles = "indiaexpress/it-900652511.eml, indiaexpress/it-901091915.eml, indiaexpress/it-903867078.eml, indiaexpress/it-904649314.eml, indiaexpress/it-905237394.eml";

    public $lang = 'en';

    public $year = null;

    public $date;

    public $detectSubjects = [
        'en' => [
            'Here’s your itinerary, Happy Skytrippin',
        ],
    ];

    public static $dictionary = [
        'en' => [
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]transaction\.airindiaexpress\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers["from"], '@transaction.airindiaexpress.com') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Air India Express'))}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains('airindiaexpress.com')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('airindiaexpress.com')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->starts($this->t('Itinerary generated on'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('PNR:'))}]")->length > 0
            && $this->http->XPath->query("//a[{$this->eq($this->t('Check-in'))}]")->length > 0
            && $this->http->XPath->query("//a[{$this->eq($this->t('View Payment Details'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseFlight(Email $email)
    {
        $f = $email->add()->flight();

        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:\s*[AaPp](?:\.\s*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm  |  00:00
        ];

        $nonEmptyXpath = "string-length(normalize-space()) > 0";

        // collect reservation confirmation
        $confDesc = trim($this->http->FindSingleNode("(//text()[{$this->eq($this->t('PNR:'))}])[1]"), ':');
        $confNumber = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('PNR:'))}])[1]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{5,7})\s*$/");

        if (!empty($confNumber)) {
            $f->general()
                ->confirmation($confNumber, $confDesc);
        }

        $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your booking is'))}]", null, true, "/\s*{$this->opt($this->t('Your booking is'))}\s*(\w+)[!.]\s*$/");

        if (!empty($status)) {
            $f->general()
                ->status($status);
        }

        $bookingDate = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Booked on'))}]", null, true, "/\s*{$this->opt($this->t('Booked on'))}\s*(.+?)\s*$/");

        if (!empty($bookingDate)) {
            $f->general()
                ->date($this->normalizeDate($bookingDate));
        }

        // collect segments
        $segmentNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('PNR:'))}]/ancestor::*[{$this->contains($this->t('Baggage'))}][1]");

        $travellers = [];
        $infants = [];

        foreach ($segmentNodes as $root) {
            $s = $f->addSegment();

            // collect airline info
            $airlineInfo = $this->http->FindSingleNode("./descendant::text()[{$this->eq($this->t('PNR:'))}]/preceding::text()[normalize-space()][2]", $root);

            if (preg_match("/^\s*(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{1,4})\s*$/u", $airlineInfo, $m)) {
                $s->airline()
                    ->name($m['aName'])
                    ->number($m['fNumber']);
            }

            // collect operator
            $operator = $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Operated by'))}]", $root, true, "/^\s*{$this->opt($this->t('Operated by'))}\s*(.+?)\s*$/");

            if (!empty($operator)) {
                $s->airline()
                    ->operator($operator);
            }

            // find node with departure and arrival info
            $depArrNode = $this->http->XPath->query("./descendant::*[ count(*[{$nonEmptyXpath}])=3 and *[normalize-space()][1][contains(translate(.,'0123456789', 'dddddddddd'), 'dd:dd')] and *[normalize-space()][3][contains(translate(.,'0123456789', 'dddddddddd'), 'dd:dd')] ]", $root)[0] ?? null;

            // collect departure info
            $depNode = $this->http->XPath->query("./*[{$nonEmptyXpath}][1]", $depArrNode)[0] ?? null;

            if (empty($depArrNode) || empty($depNode)) {
                return;
            }

            // collect depDate
            $depTime = $this->http->FindSingleNode("./*[normalize-space()][1]", $depNode, true, "/^\s*({$patterns['time']})\s*$/");
            $depDay = $this->normalizeDate($this->http->FindSingleNode("./*[normalize-space()][2]", $depNode));

            $s->departure()
                ->date(strtotime($depTime, $depDay));

            // collect depCode
            $depCode = $this->http->FindSingleNode("./*[normalize-space()][3]", $depNode, true, "/^\s*([A-Z]{3})\,.+$/");

            if (!empty($depCode)) {
                $s->departure()
                    ->code($depCode);
            }

            // collect depTerminal
            $depTerminal = $this->http->FindSingleNode("./*[normalize-space()][4]", $depNode, true, "/([tT]\d+)/");

            if (!empty($depTerminal)) {
                $s->departure()
                    ->terminal($depTerminal);
            }

            // collect duration
            $duration = $this->http->FindSingleNode("./*[normalize-space()][2]", $depArrNode, true, "/^\s*((?:\d+[hH])?\s*(?:\d+[mM])?)\s*$/");

            if (!empty($duration)) {
                $s->extra()
                    ->duration($duration);
            }

            // collect arrival info
            $arrNode = $this->http->XPath->query("./*[{$nonEmptyXpath}][3]", $depArrNode)[0] ?? null;

            if (empty($arrNode)) {
                return;
            }

            // collect arrDate
            $arrTime = $this->http->FindSingleNode("./*[normalize-space()][1]", $arrNode, true, "/^\s*({$patterns['time']})\s*$/");
            $arrDay = $this->normalizeDate($this->http->FindSingleNode("./*[normalize-space()][2]", $arrNode));

            $s->arrival()
                ->date(strtotime($arrTime, $arrDay));

            // collect arrCode and arrName
            $arrCode = $this->http->FindSingleNode("./*[normalize-space()][3]", $arrNode, true, "/^\s*([A-Z]{3})\,.+$/");

            if (!empty($arrCode)) {
                $s->arrival()
                    ->code($arrCode);
            }

            // collect arrTerminal
            $arrTerminal = $this->http->FindSingleNode("./*[normalize-space()][4]", $arrNode, true, "/([tT]\d+)/");

            if (!empty($arrTerminal)) {
                $s->arrival()
                    ->terminal($arrTerminal);
            }

            // collect travellers info
            $travellerNodes = $this->http->XPath->query("./descendant::*[ count(*)=3 and *[2][contains(normalize-space(), 'Baggage')] ]/ancestor::*[1]", $root);
            $meals = [];

            foreach ($travellerNodes as $travellerNode) {
                // collect infants and travellers
                $travellerText = $this->http->FindSingleNode("./*[1]", $travellerNode);

                $traveller = $this->re("/^\s*(?:{$this->opt(['Miss', 'Mrs', 'Mstr', 'Mr', 'Ms'])}\.?\s*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\s*(?!\({$this->opt($this->t('Infant'))}\))/", $travellerText);

                // junk name
                if (stripos($traveller, 'Loyalty Customer') !== false) {
                    $traveller = null;
                }

                $infant = $this->re("/(?:^|\+)\s*(?:{$this->opt(['Miss', 'Mrs', 'Mstr', 'Mr', 'Ms'])}\.?\s*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\s*\({$this->opt($this->t('Infant'))}\)\s*$/", $travellerText);

                // junk name
                if (stripos($infant, 'Loyal Child') !== false) {
                    $infant = null;
                }

                $travellers[] = $traveller;
                $infants[] = $infant;

                // collect seats
                $seat = $this->http->FindSingleNode("./*[2]/*[1]", $travellerNode, true, "/^\s*(\d+[A-Z])\s*\(.+?\)\s*$/");

                if (!empty($seat)) {
                    $s->addSeat($seat, false, true, $traveller);
                }

                // collect meals
                $meals[] = $this->http->FindSingleNode("./*[2]/*[3]/descendant::text()[normalize-space()][1]", $travellerNode, true, "/^\s*(.+?)(?:\s*\([A-Z]{3}[\s\-]+[A-Z]{3}\))?\s*$/");
            }

            $meals = array_unique(array_filter($meals));

            // remove services and save only meals
            foreach ($meals as $key => $meal) {
                if ($this->striposArray($meal, ['Xpress Ahead', 'Baggage']) === true) {
                    unset($meals[$key]);
                }
            }

            if (!empty($meals)) {
                $s->addMeal(implode('; ', $meals));
            }
        }

        // filter and save travellers and infants
        $travellers = array_unique(array_filter($travellers));
        $infants = array_unique(array_filter($infants));

        if (!empty($travellers)) {
            $f->setTravellers($travellers);
        }

        if (!empty($infants)) {
            $f->setInfants($infants);
        }

        // collect pricing details
        $totalText = $this->http->FindSingleNode("//a[{$this->eq($this->t('View Payment Details'))}]/preceding-sibling::*[normalize-space()][1]");

        if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/u", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $total = PriceHelper::parse($m['amount'], $currency);

            if ($total !== null) {
                $f->price()
                    ->total(PriceHelper::parse($m['amount'], $currency))
                    ->currency($currency);
            }
        }
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->date = strtotime($parser->getHeader('date'));
        $this->parseFlight($email);

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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
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
            return str_replace(' ', '\s+', preg_quote($s, $delimiter));
        }, $field)) . ')';
    }

    private function striposArray($haystack, $arrayNeedle)
    {
        $arrayNeedle = (array) $arrayNeedle;

        foreach ($arrayNeedle as $needle) {
            if (stripos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCurrency($s)
    {
        $sym = [
            '€'          => 'EUR',
            'US dollars' => 'USD',
            '£'          => 'GBP',
            '₹'          => 'INR',
            'CA$'        => 'CAD',
            'R$'         => 'BRL',
            '$'          => '$',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3}\D)(?:$|\s)#", $s)) {
            return $code;
        }

        $s = $this->re("#([^\d\,\.]+)#", $s);

        foreach ($sym as $f => $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return $s;
    }

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date) || empty($this->date)) {
            return null;
        }

        $timePattern = '\d{1,2}(?:[:：]\d{2})?(?:\s*[AaPp](?:\.\s*)?[Mm]\.?)?'; // 4:19PM  |  2:00 p. m.  |  3pm  |  00:00

        $year = date('Y', $this->date);

        $in = [
            "/^\s*(\w+)\s+(\d+)\s+(\d{4})\s*$/u", // Dec 22 2024 => 22 Dec 2024
            "/^\s*\w+\,\s+(\w+)\s+(\d+)\s+(\d{4})\,\s+({$timePattern})\s*$/u", // Sat, Oct 12 2024, 16:30
        ];
        $out = [
            "$2 $1 $3",
            "$2 $1 $3, $4",
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        if (preg_match("/^(?<week>\w+), (?<date>\d+ \w+ .+)/u", $date, $m)) {
            $weeknum = WeekTranslate::number1($m['week']);
            $date = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/\b\d{4}\b/", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }

        return $date;
    }
}
