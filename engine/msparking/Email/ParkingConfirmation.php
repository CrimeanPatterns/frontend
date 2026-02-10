<?php

namespace AwardWallet\Engine\msparking\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ParkingConfirmation extends \TAccountChecker
{
    public $mailFiles = "msparking/it-901080828.eml";

    public $lang = 'en';

    public $detectSubjects = [
        'en' => [
            'Minneapolis Airport Parking Confirmation',
        ],
    ];

    public static $dictionary = [
        'en' => [
            'nameStart' => 'Your parking reservation at',
            'nameEnd'   => 'is confirmed',
            'phoneDesc' => 'If you have questions',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]parking\.mspairport\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from']) || stripos($headers['from'], 'parking.mspairport.com') === false)
            && stripos($headers['subject'], 'Minneapolis') === false
        ) {
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

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('MSP Airport'))}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains($this->t('mspairport.com'))}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Tax invoice/Booking ID:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Entry Date:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Booking Date:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Status:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Getting to the Parking Ramp'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseParking(Email $email)
    {
        $p = $email->add()->parking();

        // collect reservation confirmation
        $confDesc = trim($this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking ID:'))}]"), ': ');
        $confNumber = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking ID:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([\w\-]+\-\d+)\s*$/");

        if (!empty($confNumber)) {
            $p->general()
                ->confirmation($confNumber, $confDesc);
        }

        $bookingDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking Date:'))}]/following::text()[normalize-space()][1]"));

        if (!empty($bookingDate)) {
            $p->general()
                ->date($bookingDate);
        }

        $status = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Status:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\w+)\s*$/");

        if (!empty($status)) {
            $p->general()
                ->status($status);
        }

        // collect traveller
        $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Customer:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/");

        if (!empty($traveller)) {
            $p->general()
                ->traveller($traveller);
        }

        // collect address and location
        $address = $this->http->FindSingleNode("//text()[{$this->starts($this->t('The parking ramp address is'))}]", null, true, "/^\s*{$this->opt($this->t('The parking ramp address is'))}\s+(.+?)\s*$/");
        $location = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Parking Ramp:'))}]/following::text()[normalize-space()][1]");
        $p->place()
            ->address($address)
            ->location($location);

        // collect rate type
        $rateType = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Product Name:'))}]/following::text()[normalize-space()][1]");

        if (!empty($rateType)) {
            $p->booked()
                ->rate($rateType);
        }

        // collect start and end dates
        $startDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('Entry Date:'))}]/following::text()[normalize-space()][1]"));
        $endDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('Exit Date:'))}]/following::text()[normalize-space()][1]"));
        $p->booked()
            ->start($startDate)
            ->end($endDate);

        // collect phone
        $phoneText = $this->http->FindSingleNode("//text()[{$this->contains($this->t('phoneDesc'))}]");

        if (preg_match("/(?<desc>{$this->opt($this->t('phoneDesc'))}).+?{$this->opt($this->t('or'))}\s*(?<phone>[\+\-\(\)\d ]+?)[\.\s]*$/", $phoneText, $m)) {
            $p->program()
                ->phone(preg_replace("/[\+\-\(\)\s]+/", '', $m['phone']), $m['desc']);
        }

        // collect pricing details
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Total:'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/u", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $p->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);
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
            "/^\s*({$timePattern})\s*(\d+)\/(\d+)\/(\d{4})\s*$/", // 06:28 PM 04/21/2025 => 21.04.2025, 06:28 PM
        ];
        $out = [
            '$3.$2.$4, $1',
        ];

        return strtotime(preg_replace($in, $out, $date));
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
