<?php

namespace AwardWallet\Engine\reserveam\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class EventReservation extends \TAccountChecker
{
    public $mailFiles = "reserveam/it-851106950.eml, reserveam/it-856061433.eml, reserveam/it-880160377.eml, reserveam/it-880160845.eml";

    public $lang = 'en';

    public $detectSubjects = [
        'en' => [
            'Your Reservation Confirmation',
            'Confirmation Letter Email',
        ],
    ];

    public $detectBody = [
        'en' => [],
    ];

    public static $dictionary = [
        'en' => [
            'cancelledStatus' => ['Cancelled', 'Canceled'],
            'Gate Hours'      => ['Main gate hours', 'Gate Hours', 'Gate open from'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]reserveamerica\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers['from'], 'reserveamerica.com') === false) {
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
        if ($this->http->XPath->query("//a/@href[{$this->contains('reserveamerica.com')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('reserveamerica.com')}]")->length === 0) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Important Billing Information:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Reservation #'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Date:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Site:'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->parseEvent($email);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function parseEvent(Email $email)
    {
        $patterns = [
            'date'          => '.{4,}\b\d{4}\b', // Fri Dec 1 2023
            'time'          => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)', // 4:19PM    |    2:00 p. m.    |    3pm
            'travellerName' => '[[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $e = $email->add()->event();
        $e->type()->event();

        // collect reservation confirmation
        $confDesc = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Reservation #'))}]");
        $confNumber = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Reservation #'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^\s*(?<confNumber>[\-\d]+)(?:\s*\((?<cancelledStatus>{$this->opt($this->t('cancelledStatus'))})\))?\s*$/", $confNumber, $m)) {
            $e->general()->confirmation($m['confNumber'], $confDesc);

            if (!empty($m['cancelledStatus'])) {
                $e->general()->status($m['cancelledStatus']);
                $e->general()->cancelled();
            }
        }

        $reservationDate = strtotime($this->http->FindSingleNode("//text()[{$this->eq($this->t('Order Date/Time:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*({$patterns['date']}\s+{$patterns['time']})(?:\s+|$)/"));

        if (!empty($reservationDate)) {
            $e->general()->date($reservationDate);
        }

        // collect traveller
        $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Primary Occupant:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*({$patterns['travellerName']})\s*$/");

        if (!empty($traveller)) {
            $e->general()->traveller($traveller);
        }

        // collect address
        $address = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Sales Location Address:'))}]/following::text()[normalize-space()][1]");
        $e->place()->address($address);

        // collect park name and event name
        $parkName = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Sales Location:'))}]/following::text()[normalize-space()][1]");
        $eventName = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Site:'))}]/following::text()[normalize-space()][1]");

        if (!empty($parkName)) {
            $e->place()->name($parkName . ', ' . $eventName);
        } else {
            $e->place()->name($eventName);
        }

        // collect check-in and check-out dates
        $dateCheckIn = strtotime($this->http->FindSingleNode("//text()[{$this->eq($this->t('Date:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*({$patterns['date']})\s*$/"));
        $timeCheckIn = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Site:'))}]/following::text()[normalize-space()][1]", null, true, "/^.*?({$patterns['time']})[\s\-]+/i")
            ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('Gate Hours'))}]", null, true, "/^.*?{$this->opt($this->t('Gate Hours'))}.+?({$patterns['time']})\s/");

        if (!empty($timeCheckIn)) {
            $e->booked()->start(strtotime($timeCheckIn, $dateCheckIn));
        }

        $e->booked()->noEnd();

        // collect payment information
        $pricePattern = "(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)"; // use with 'Unicode' regex flag

        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Total:'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^s*$pricePattern\s*$/u", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);

            $e->price()
                ->currency($currency)
                ->total(PriceHelper::parse($m['amount'], $currency));
        }

        $fees = $this->http->FindNodes("//text()[{$this->eq($this->t('# of vehicle:'))}]/following::tr[count(td)=2][following::td[{$this->eq($this->t('Total:'))}]]");

        $discountSum = null;

        foreach ($fees as $fee) {
            if (preg_match("/^\s*(?<feeName>.+?)(?<currency>[^\d\s]{1,3})\s*\((?<amount>[\d\.\,\']+)\)\s*$/u", $fee, $m)) {
                $currency = $this->normalizeCurrency($m['currency']);
                $discountSum += PriceHelper::parse($m['amount'], $currency);

                continue;
            }

            if (preg_match("/^\s*(?<feeName>.+?)$pricePattern\s*$/u", $fee, $m)) {
                $currency = $this->normalizeCurrency($m['currency']);
                $e->price()->fee($m['feeName'], PriceHelper::parse($m['amount'], $currency));
            }
        }

        if (!empty($currency) && $discountSum !== null) {
            $e->price()->discount(PriceHelper::parse($m['amount'], $currency));
        }

        $guestCount = null;
        $kidsCount = null;

        if ($e->getPrice() !== null) {
            foreach ($e->getPrice()->getFees() as $fee) {
                if (preg_match("/^\s*{$this->opt($this->t('Adult (13 and over)'))}\s*x\s*(\d+)\s*@/", $fee[0], $m)) {
                    $guestCount += $m[1];

                    continue;
                }

                if (preg_match("/^\s*{$this->opt($this->t('Child (12 and under)'))}\s*x\s*(\d+)\s*@/", $fee[0], $m)) {
                    $kidsCount += $m[1];
                }
            }
        }

        if ($guestCount !== null) {
            $e->booked()->guests($guestCount);
        }

        if ($kidsCount !== null) {
            $e->booked()->kids($kidsCount);
        }

        $phone = $this->http->FindSingleNode("//text()[{$this->starts($this->t('PARK PHONE NUMBER'))}]/following::text()[normalize-space()][1]", null, true, '/^\s*([+\-()\d\s]+?)\s*$/');

        if (!empty($phone)) {
            $e->place()->phone($phone);
        }

        // collect directions in notes
        $notes = array_filter($this->http->FindNodes("//text()[{$this->eq($this->t('PARK DESCRIPTION'))}]/following::text()[following::text()[{$this->eq($this->t('OTHER ALERTS'))}]]"));

        if (!empty($notes)) {
            $e->general()->notes(implode("\n", $notes));
        }

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

    private function t(string $phrase, string $lang = '')
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return $phrase;
        }

        if ($lang === '') {
            $lang = $this->lang;
        }

        if (empty(self::$dictionary[$lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$lang][$phrase];
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

    private function re($re, $str, $c = 1): ?string
    {
        if (preg_match($re, $str, $m) && array_key_exists($c, $m)) {
            return $m[$c];
        }

        return null;
    }

    /**
     * @param string|null $s Unformatted string with amount
     * @param string|null $c String with currency
     */
    private function normalizeCurrency($s)
    {
        $sym = [
            '€'          => 'EUR',
            'US dollars' => 'USD',
            '£'          => 'GBP',
            '₹'          => 'INR',
            'CA$'        => 'CAD',
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
