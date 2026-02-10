<?php

namespace AwardWallet\Engine\flyone\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;

class FlightConfirmation extends \TAccountChecker
{
    public $mailFiles = "flyone/it-895659088.eml, flyone/it-895905358.eml";

    public static $dictionary = [
        "en" => [
            'Flight ticket' => ['Flight ticket', 'Mediterranean'],
        ],
    ];

    private $detectFrom = "flyone.eu";

    private $detectSubjects = [
        'en' => [
            'FLYONE - Booking Confirmation',
        ],
    ];

    private $lang = 'en';

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]flyone\.eu$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from'])
            || stripos($headers["from"], $this->detectFrom) === false)
            && stripos($headers["subject"], 'FLYONE') === false
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

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//text()[{$this->contains(['FLYONE'])}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains($this->detectFrom)}]")->length === 0
            && $this->http->XPath->query("//a/@img[{$this->contains($this->detectFrom)}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Ticket code:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Status:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Time:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Passenger'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Receipt:'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->parseFlight($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class));

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

    private function parseFlight(Email $email)
    {
        $patterns = [
            'date'          => '[^\d\s]+\,[ ]*(\d+\s*[^\d\s]+\s*\d{2,4})', // Mon, 12 Aug 2024
            'time'          => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $f = $email->add()->flight();

        // collect reservation confirmation
        $confNumber = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Ticket code:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\w{5,7})\s*$/");

        if (!empty($confNumber)) {
            $f->general()->confirmation($confNumber, 'Booking Confirmation'); // description "Booking Confirmation" from subject
        }

        $status = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Status:'))}]/following::text()[normalize-space()][1]");

        if (!empty($status)) {
            $f->general()->status($status);
        }

        // collect segments
        $segments = $this->http->XPath->query("//*[ *[1][{$this->contains($this->t('Depart:'))}] and *[2][{$this->contains($this->t('Arrival:'))}] ]");
        $travellers = [];

        foreach ($segments as $root) {
            $s = $f->addSegment();

            // collect department place
            $depPlace = $this->http->FindSingleNode(".//text()[{$this->eq($this->t('Depart:'))}]/following::text()[normalize-space()][1]", $root);

            if (preg_match("/^\s*(?<depName>.+?)\s*\((?<depCode>[A-Z]{3})\)\s*$/", $depPlace, $m)) {
                $s->departure()
                    ->name($m['depName'])
                    ->code($m['depCode']);
            }

            // collect department datetime
            $detDate = $this->http->FindSingleNode("(.//text()[{$this->eq($this->t('Date:'))}])[1]/following::text()[normalize-space()][1]", $root, true, "/^\s*{$patterns['date']}\s*$/");
            $depTime = $this->http->FindSingleNode("(.//text()[{$this->eq($this->t('Time:'))}])[1]/following::text()[normalize-space()][1]", $root, true, "/^\s*({$patterns['time']})\s*$/");

            if (!empty($detDate) && !empty($depTime)) {
                $s->departure()
                    ->date(strtotime($depTime, strtotime($detDate)));
            }

            // collect arrival place
            $arrPlace = $this->http->FindSingleNode(".//text()[{$this->eq($this->t('Arrival:'))}]/following::text()[normalize-space()][1]", $root);

            if (preg_match("/^\s*(?<arrName>.+?)\s*\((?<arrCode>[A-Z]{3})\)\s*$/", $arrPlace, $m)) {
                $s->arrival()
                    ->name($m['arrName'])
                    ->code($m['arrCode']);
            }

            // collect arrival datetime
            $arrDate = $this->http->FindSingleNode("(.//text()[{$this->eq($this->t('Date:'))}])[last()]/following::text()[normalize-space()][1]", $root, true, "/^\s*{$patterns['date']}\s*$/");
            $arrTime = $this->http->FindSingleNode("(.//text()[{$this->eq($this->t('Time:'))}])[last()]/following::text()[normalize-space()][1]", $root, true, "/^\s*({$patterns['time']})\s*$/");

            if (!empty($arrDate) && !empty($arrTime)) {
                $s->arrival()
                    ->date(strtotime($arrTime, strtotime($arrDate)));
            }

            // build route (string like "depName - arrName") to match it with route from Passenger block
            $route = $s->getDepName() . ' - ' . $s->getArrName();

            // find routeNode from Passenger block relative to which we are searching airline, flight number, traveller and seats
            $routeNode = $this->http->XPath->query("(//text()[{$this->eq($route)}])[1]");

            if ($routeNode->length === 0) {
                continue;
            }

            // collect airline and flight number
            $airlineAndFlightNumber = $this->http->FindSingleNode("./following::text()[{$this->eq($this->t('Flight number:'))}]/following::text()[normalize-space()][1]", $routeNode[0]);

            if (preg_match("/^\s*(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{1,4})\s*$/", $airlineAndFlightNumber, $m)) {
                $s->airline()
                    ->name($m['aName'])
                    ->number($m['fNumber']);
            }

            // collect traveller
            $traveller = $this->http->FindSingleNode("./preceding::img[{$this->contains('email/img', '@src')}][1]/following::text()[normalize-space()][1]", $routeNode[0], true, "/^\s*({$patterns['travellerName']})\s*$/");
            $travellers[] = $traveller;

            // collect seat
            $seat = $this->http->FindSingleNode("./following::text()[{$this->eq($this->t('Seat'))}][1]/following::text()[normalize-space()][1]", $routeNode[0], true, "/^\s*(\d+[A-Z])\s*$/");

            if (!empty($seat) && !empty($traveller)) {
                $s->extra()->seat($seat, false, false, $traveller);
            }
        }

        $travellers = array_filter($travellers);

        if (!empty($travellers)) {
            $f->general()
                ->travellers(array_unique($travellers));
        }

        // collect pricing details
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Total paid'))}]/following::text()[{$this->eq($this->t('Credit/Debit Card'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $f->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);
        }

        $cost = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Flight ticket'))}]/following::text()[normalize-space()][1]", null, true, "/\D*([\d\.\,\']+)$/");

        if (!empty($currency) && $cost !== null) {
            $f->price()
                ->cost(PriceHelper::parse($cost, $currency));
        }

        if (!empty($currency)) {
            $feeNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Receipt:'))}]/following::tr[not({$this->contains($this->t('Flight ticket'))})][following-sibling::tr[{$this->eq($this->t('Total paid'))}]]");

            foreach ($feeNodes as $feeRoot) {
                $feeName = trim($this->http->FindSingleNode("./descendant::td[string-length()>1][1]", $feeRoot));
                $feeSumm = $this->http->FindSingleNode("./descendant::td[string-length()>1][2]", $feeRoot, true, "/\D*([\d\.\,\']+)$/");

                if (!empty($feeName) && $feeSumm !== null) {
                    $f->price()
                        ->fee($feeName, PriceHelper::parse($feeSumm, $currency));
                }
            }
        }
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function contains($field, $node = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'contains(' . $node . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

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
