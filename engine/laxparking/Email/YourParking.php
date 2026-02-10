<?php

namespace AwardWallet\Engine\laxparking\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class YourParking extends \TAccountChecker
{
    public $mailFiles = "laxparking/it-226233622.eml, laxparking/it-531643823.eml, laxparking/it-531802321.eml, laxparking/it-531878285.eml, laxparking/it-532157216.eml, laxparking/it-649088641-cancelled.eml, laxparking/it-654232105-sfoparking.eml, laxparking/it-675671564-panynj.eml, laxparking/it-895911935.eml, laxparking/it-896038494.eml, laxparking/it-896241407.eml, laxparking/it-897572318.eml, laxparking/it-897885960.eml, laxparking/it-901930251.eml, laxparking/it-902103131.eml, laxparking/it-902281889.eml, laxparking/it-902517147.eml";

    public $from = [
        // houston
        '@fly2houston.com',
        // laxparking
        '@flylax.com',
        // panynj
        '@bookings.jfkairport.com',
        '@bookings.newarkairport.com',
        '@bookings.laguardiaairport.com',
        // parkbost
        '@parking.massport.com',
        // sfoparking
        '@flysfo.com',
        // noname providers
        '@parking.clevelandairport.com',
        '@orlandoairports.net',
        '@parkdia.com',
        '@phl.org',
        '@flysatparking.com',
    ];

    public $detectSubjects = [
        'en' => [
            // cleparking, houston, laxparking, orlandopark, parkdia, phlparking, satparking
            ' - Your Parking for ',
            // sfoparking
            'SFO Parking Confirmation',
            'SFO Parking Cancellation Confirmation',
            'SFO Parking Cancelation Confirmation',
            // panynj
            'Newark Airport booking confirmation',
            'JFK Airport booking confirmation',
            'JFK Airport Parking Confirmation',
            'Newark Airport Parking Confirmation',
            'LaGuardia Airport Parking Confirmation',
            // parkbost
            'Boston Logan Parking - Reservation',
        ],
    ];

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'cancelledPhrases' => [
                'Your Booked Parking Cancellation Confirmation',
                'Your Booked Parking Cancelation Confirmation',
                'Your Booking Cancellation',
                'has been cancelled', 'has been canceled',
                'summary of the cancelled reservation',
            ],
            'confDesc' => [
                'Booking Reference:', 'Booking Reference :',
                'Booking reference:', 'Booking reference :',
                'Confirmation Number:', 'Confirmation Number :',
                'Confirmation number:', 'Confirmation number :',
                'Reservation Number:', 'Reservation Number :',
                'Reservation number:', 'Reservation number :',
                'Your Reservation Confirmation',
                'Your Parking Plus Reservation Confirmation',
                'Reservation #:',
            ],
            'Directions'     => ['Directions', 'Map & Directions', 'Map', 'Information', 'Get directions'],
            'entry'          => ['Entry:', 'Entry :', 'Entry Date/Time:'],
            'exit'           => ['Exit:', 'Exit :', 'Exit Date/Time:'],
            'Hello'          => ['Hello', 'Dear'],
            'is located at'  => ['is located at', 'are located at', 'entrance located at'],
            'License plate:' => ['License plate:', 'License Plate:'],
            'nameStart'      => [
                'Thank you for reserving your Premium parking space at',
                'Thank you for booking your parking at',
                'Your booking for parking at',
                'Your parking booking at',
                'Thank you for reserving your parking at',
                'Thank you for reserving your Discount Parking space for',
                'Thank you for modifying your parking at',
            ],
            'nameEnd'         => ['A summary', 'has been', 'is coming up'],
            'parkingLocation' => [
                'Parking Product:', 'Parking product:', 'Parking Location:', 'Parking garage:', 'Parking lot:',
                'Parking garage:', 'Parking type:', 'Parking:', 'Product:',
            ],
            'phoneStart'      => ['or call', 'For shuttle-related questions, call', 'phone:', 'or by calling'],
            'phoneEnd'        => 'to speak',
            'reservationDate' => ['Reservation Made:', 'Booking date:', 'Booking Made:', 'Booking made:', 'Reservation Created:'],
            'totalPrice'      => ['Total:', 'Total :'],
        ],
    ];

    private $patterns = [
        'date' => '\b(?:\d{1,2}\/\d{1,2}\/\d{4}|[[:alpha:]]+\s+\d{1,2}[,\s]+\d{2,4})\b', // 10/15/2023  |  Oct 5, 2023
        'time' => '\b(\d{1,2}[:：]\d{2})(?:[:：]\d{2})?(?:[ ]*([AaPp](?:\.[ ]*)?[Mm]\.?))?', // 02:00 PM  |  11:30:00 PM
    ];

    private $providerCode = '';

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]flylax\.com$/', $from) > 0;
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
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('entry'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('exit'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('parkingLocation'))}]")->length > 0
            && ($this->http->XPath->query("//a[{$this->eq($this->t('Directions'))}]")->length > 0
                || $this->http->XPath->query("//img/@alt[{$this->eq($this->t('Directions'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->contains($this->t('cancelledPhrases'))}]")->length > 0)
        ) {
            return true;
        }

        return false;
    }

    public function ParseParking(Email $email): void
    {
        $p = $email->add()->parking();

        // collect reservation info
        $confirmationText = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('confDesc'))}])[last()]/ancestor::tr[1][normalize-space()]");

        if (preg_match("/^\s*(?<desc>{$this->opt($this->t('confDesc'))})\s*(?<number>[-A-Z\d]{5,})\s*$/", $confirmationText, $m)) {
            $p->general()
                ->confirmation($m['number'], trim($m['desc'], ':'));
        }

        $reservationDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('reservationDate'))}]/following::text()[normalize-space()][1]"));

        if (!empty($reservationDate)) {
            $p->general()
                ->date($reservationDate);
        }

        $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your reservation has been'))}]", null, true, "/^\s*{$this->opt($this->t('Your reservation has been'))}\s+([[:alpha:]]+)\b/");

        if (!empty($status)) {
            $p->general()
                ->status($status);
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('cancelledPhrases'))}]")->length > 0) {
            $p->general()
                ->cancelled();
        }

        // collect traveller
        $travellerNames = array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Hello'))}]", null, "/^{$this->opt($this->t('Hello'))}[,\s]+(?i)(?:Ms\/Mr\s+)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])(?:\s*[,;:!?]|$)/u"));

        if (count(array_unique($travellerNames)) === 1) {
            $p->general()
                ->traveller(array_shift($travellerNames));
        }

        // collect location
        $location = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('parkingLocation'))}])[1]/following::text()[normalize-space()][1]");
        $p->setLocation($location);

        // collect plate
        $plate = $this->http->FindSingleNode("//text()[{$this->eq($this->t('License plate:'))}]/following::text()[normalize-space()][1]")
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your license plate number'))}]", null, true, "/^\s*{$this->opt($this->t('Your license plate number'))}\s*(\w+)\s/");

        if (!empty($plate)) {
            $p->booked()
                ->plate($plate);
        }

        // collect dates
        $dateStart = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('entry'))}]/following::text()[normalize-space()][1]"));
        $dateEnd = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('exit'))}]/following::text()[normalize-space()][1]"));

        $p->booked()->start($dateStart);

        if (!empty($dateEnd)) {
            $p->booked()
                ->end($dateEnd);
        } else {
            $p->booked()
                ->noEnd();
        }

        // collect address
        $address = $this->http->FindSingleNode("//text()[{$this->contains($this->t('is located at'))}]", null, true, "/{$this->opt($this->t('is located at'))}\s+(.+?)\s*\./")
            ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('is located at'))}]/following::text()[normalize-space()][1]/ancestor::a[1]", null, true, "/^\s*(.+?)[.\s]*$/")
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('nameStart'))} and {$this->contains($this->t('nameEnd'))}]", null, true, "/^\s*{$this->opt($this->t('nameStart'))}\s+(.+?)[\.\s]+{$this->opt($this->t('nameEnd'))}/")
            // it-896038494.eml
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('nameStart'))}]", null, true, "/^\s*{$this->opt($this->t('nameStart'))}\s+(.+?)[\.\s]+$/")
            // it-899169299.eml
            ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('George Bush Intercontinental Airport'))}]", null, true, "/({$this->opt($this->t('George Bush Intercontinental Airport'))})/");

        if (empty($address) && $this->http->XPath->query("//text()[{$this->contains('ParkDIA')}]")->length > 0) {
            $address = 'Denver International Airport';
        }

        $p->place()->address($address);

        // collect phone
        $phone = $this->http->FindSingleNode("(//*[{$this->contains($this->t('phoneStart'))} and {$this->contains($this->t('phoneEnd'))}])[last()]", null, true, "/{$this->opt($this->t('phoneStart'))}\s+([\+\-\(\)\d ]+?)\s+{$this->opt($this->t('phoneEnd'))}/")
            ?? $this->http->FindSingleNode("//text()[{$this->eq($this->t('phoneStart'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([\+\-\(\)\.\d ]+?)[\.\s]*$/");

        if (!empty($phone)) {
            $p->place()
                ->phone($phone);
        }

        // collect pricing details
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('totalPrice'))}]/following::text()[normalize-space()][1]");
        // $ 162.00
        if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/u", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $p->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignProvider();
        $this->ParseParking($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));
        $email->setProviderCode($this->providerCode);

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

    public static function getEmailProviders()
    {
        return [
            'houston',
            'laxparking',
            'panynj',
            'parkbost',
            'sfoparking',
        ];
    }

    private function assignProvider(): bool
    {
        $nonameProviders = [
            'Cleveland Hopkins International Airport',
            'Greater Orlando Aviation Authority',
            'ParkDIA',
            'Philadelphia International Airport',
            'San Antonio International Airport',
        ];

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Houston Airport System'))}]")->length > 0
            || $this->http->XPath->query("//a/@href[{$this->contains($this->t('fly2houston.com'))}]")->length > 0
        ) {
            $this->providerCode = 'houston';

            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('The Port Authority of New York and New Jersey'))}]")->length > 0
            || $this->http->XPath->query("//a/@href[{$this->contains($this->t('panynj.gov'))}]")->length > 0
            || $this->http->XPath->query("//img/@src[{$this->contains($this->t('PANYNJ'))}]")->length > 0
        ) {
            $this->providerCode = 'panynj';

            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Massachusetts Port Authority'))}]")->length > 0
            || $this->http->XPath->query("//a/@href[{$this->contains($this->t('massport.com'))}]")->length > 0
        ) {
            $this->providerCode = 'parkbost';

            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('SFO'))}]")->length > 0
            || $this->http->XPath->query("//a/@href[{$this->contains($this->t('flysfo.com'))}]")->length > 0
        ) {
            $this->providerCode = 'sfoparking';

            return true;
        }

        // main provider
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('FlyLAX'))}]")->length > 0
            || $this->http->XPath->query("//a/@href[{$this->contains($this->t('flylax.com'))}]")->length > 0
            || $this->http->XPath->query("//img/@src[{$this->contains($this->t('LAX'))}]")->length > 0
        ) {
            $this->providerCode = 'laxparking';

            return true;
        }

        // noname providers always detect as laxparking
        foreach ($nonameProviders as $phrase) {
            if ($this->http->XPath->query("//text()[{$this->contains($phrase)}]")->length > 0) {
                $this->providerCode = 'laxparking';

                return true;
            }
        }

        return false;
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
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

    private function opt($field): string
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
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

    private function normalizeDate(?string $str): ?int
    {
        // $this->logger->debug('$str = ' . print_r($str, true));
        $in = [
            "/^\s*([[:alpha:]]+)\s+(\d{1,2})[,\s]+(\d{4})\s*$/u",                    // Oct 3, 2023               => 3 Oct 2023
            "/^\s*(\w+)\s*(\d+)\,\s*(\d{4})\s*at\s*{$this->patterns['time']}\s*$/u", // Oct 3, 2023 at 5:00:00 AM => 3 Oct 2023, 5:00 AM
            "/^\s*{$this->patterns['time']}\s*on\s*(\d+)\/(\d+)\/(\d{4})\s*$/u",     // 5:00 AM on 10/03/2023     => 03.10.2023, 5:00 AM
            "/^\s*(\d+)\/(\d+)\/(\d{4})\s*at\s*{$this->patterns['time']}\s*$/u",     // 10/03/2023 at 05:00 PM    => 03.10.2023, 05:00 AM
        ];
        $out = [
            '$2 $1 $3',
            "$2 $1 $3, $4 $5",
            "$4.$3.$5, $1 $2",
            "$2.$1.$3, $4 $5",
        ];
        // $this->logger->debug('$str = ' . print_r($regex, true));
        $str = preg_replace($in, $out, $str);
        // $this->logger->debug('$str = '.print_r( $str,true));

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }
}
