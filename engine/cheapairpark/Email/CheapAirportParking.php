<?php

namespace AwardWallet\Engine\cheapairpark\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class CheapAirportParking extends \TAccountChecker
{
    public $mailFiles = "cheapairpark/it-895968872.eml, cheapairpark/it-897306070.eml, cheapairpark/it-902377681.eml, cheapairpark/it-904432581.eml, cheapairpark/it-912212248.eml";

    public $lang = 'en';

    public $from = [
        // cheapairpark
        '@cheapairportparking.org',
        // noname
        '@parkon.com',
    ];

    public $detectSubjects = [
        'en' => [
            'Airport Parking Confirmation',
        ],
    ];

    public static $dictionary = [
        'en' => [
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]cheapairportparking\.org$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from'])) {
            return false;
        }

        $detectProvider = false;

        foreach ($this->from as $from) {
            if (stripos($headers['from'], $from) !== false) {
                $detectProvider = true;

                break;
            }
        }

        if ($detectProvider === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubject) {
                if (stripos($headers['subject'], $dSubject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if (!$this->assignProvider()) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Important'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Confirmation Code:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Parking Lot Arrival:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Parking Total:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Next Steps'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseParking(Email $email)
    {
        $p = $email->add()->parking();

        // collect reservation confirmation
        $confDesc = trim($this->http->FindSingleNode("//text()[{$this->eq($this->t('Confirmation Code:'))}]"), ': ');
        $confNumber = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Confirmation Code:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\w+)\s*$/");

        if (!empty($confNumber)) {
            $p->general()
                ->confirmation($confNumber, $confDesc);
        }

        $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your Parking Reservation Is'))}]", null, true, "/^\s*{$this->opt($this->t('Your Parking Reservation Is'))}\s*(\w+)\s*$/");

        if (!empty($status)) {
            $p->general()
                ->status($status);
        }

        // collect traveller
        $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Dear'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/");

        if (!empty($traveller) && stripos($traveller, 'Gift card') === false) {
            $p->general()
                ->traveller($traveller);
        }

        // collect place info
        $placeText = implode("\n", $this->http->FindNodes("//text()[{$this->starts($this->t('Confirmation Code:'))}]/following::text()[normalize-space()][ following::text()[{$this->eq($this->t('Parking Lot Arrival:'))}] ][position() > 1]"));

        /*
            Airport: Los Angeles International (LAX) // optional
            QuikPark LAX - Indoor Self Parking
            9821 Vicksburg ave., Los Angeles, CA 90045
            310-645-7754
            Get Directions // optional
        */

        $placePattern = "/^\s*"
            . "(?:{$this->opt($this->t('Airport:'))}.+?\n)?"
            . "[ ]*(?<location>.+?)[ ]*\n"
            . "[ ]*(?<address>.+?)[ ]*\n"
            . "[ ]*(?<phone>[+)(\d][-.\s\d)(]{5,}[\d)(])[ ]*(?:\,|\n|$)"
            . "/u";

        if (preg_match($placePattern, $placeText, $m)) {
            $p->place()
                ->address($m['address'])
                ->location($m['location'])
                ->phone($m['phone']);
        }

        // collect start and end dates
        $startDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('Parking Lot Arrival:'))}]/following::text()[normalize-space()][1]"));
        $endDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('Parking Lot Departure:'))}]/following::text()[normalize-space()][1]"));

        $p->booked()
            ->start($startDate)
            ->end($endDate);

        // collect pricing details
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Parking Total:'))}]/ancestor::tr[1][normalize-space()]")
            // it-904432581.eml
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('Parking Total:'))}]");

        if (preg_match("/^\s*{$this->opt($this->t('Parking Total:'))}\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/u", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $p->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);
        }

        $costText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Airport Parking:'))}]/ancestor::tr[1][normalize-space()]");

        if (preg_match("/^\s*{$this->opt($this->t('Airport Parking:'))}\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/u", $costText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $p->price()
                ->cost(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);

            $feeNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Airport Parking:'))}]/ancestor::tr[1]/following-sibling::tr[following-sibling::tr[{$this->starts($this->t('Parking Total:'))}]][normalize-space()]");

            foreach ($feeNodes as $feeRoot) {
                $feeName = trim($this->http->FindSingleNode("./descendant::td[string-length()>1][1]", $feeRoot), ':');
                $feeAmount = $this->http->FindSingleNode("./descendant::td[string-length()>1][2]", $feeRoot, true, "/\D*([\d\.\,\']+)$/");

                $p->price()
                    ->fee($feeName, PriceHelper::parse($feeAmount, $currency));
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->parseParking($email);
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

    private function assignProvider(): bool
    {
        $nonameProviders = [
            'ParkOn',
        ];

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('CheapAirportParking'))}]")->length > 0
            || $this->http->XPath->query("//a/@href[{$this->contains($this->t('cheapairportparking.org'))}]")->length > 0
            || $this->http->XPath->query("//img/@src[{$this->contains($this->t('cheapairportparking.org'))}]")->length > 0
        ) {
            return true;
        }

        // noname providers always detect as main provider (cheapairpark)
        foreach ($nonameProviders as $phrase) {
            if ($this->http->XPath->query("//text()[{$this->contains($phrase)}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $timePattern = '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?'; // 4:19PM    |    2:00 p. m.

        $in = [
            "/^\s*(\d+)\/(\d+)\/(\d{4})\s*\-\s*({$timePattern})\s*$/", // 05/26/2025 - 4:00 PM => 26.05.2025, 4:00 PM
            "/^\s*(\d+)\s*([^\d\s]+)\s*(\d{4})\s*\(({$timePattern})\)\s*$/", // 02 Apr 2025 (11:00 AM) => 02 Apr 2025, 11:00 AM
        ];
        $out = [
            '$2.$1.$3, $4',
            '$1 $2 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        return strtotime($date);
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
}
