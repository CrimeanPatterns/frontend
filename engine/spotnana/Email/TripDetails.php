<?php

namespace AwardWallet\Engine\spotnana\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;
use AwardWallet\Schema\Parser\Email\Email;

class TripDetails extends \TAccountChecker
{
    public $mailFiles = "spotnana/it-737484418.eml, spotnana/it-739300288.eml, spotnana/it-739513495.eml, spotnana/it-741179155.eml, spotnana/it-911257764.eml, spotnana/it-912282083.eml";

    public $detectFrom = "@spotnana.com";
    public $detectSubject = [
        // en
        'Hotel confirmation for ',
        'Hotel canceled for ',
        'Rental car confirmation for ',
        'Rental car canceled for ',
        'Rail confirmation for ',
        'Rail canceled for ',
        'Air e-Ticket confirmation for ',
        'Seat confirmed by airline',
    ];

    public $detectRentalProviders = [
        'avis' => [
            'AVIS',
        ],
        'perfectdrive' => [
            'BUDGET',
        ],
        'national' => [
            'NATIONAL',
        ],
        'sixt' => [
            'SIXT',
        ],
        'dollar' => [
            'DOLLAR',
        ],
        'europcar' => [
            'EUROPCAR',
        ],
        'rentacar' => [
            'ENTERPRISE',
        ],
        'movida' => [
            'MOVIDA',
        ],
    ];

    public $lang;
    public static $dictionary = [
        'en' => [
            'Trip Name'        => ['Trip Name', 'Trip name'],
            'Traveler Details' => ['Traveler Details', 'Traveler details'],
            'otaConf'          => ['Trip ID', 'Booking ID', 'Agency reference'],
            'Flight Details'   => ['Flight Details', 'Flight details'],
            'Payment Details'  => ['Payment Details', 'Invoice'],
            'Cost'             => ['Base rate', 'Room rate', 'Fare'],
            'Taxes'            => ['Taxes', 'Taxes and fees', 'Taxes & fees'],
            'Total charges'    => ['Total charges', 'Total', 'Grand total'],
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/@spotnana\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false) {
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
        if (
            $this->http->XPath->query("//a[{$this->contains(['.spotnana.com', '.mycwt.com'], '@href')}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['@spotnana.com'])}]")->length === 0
        ) {
            return false;
        }

        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Traveler Details']) && !empty($dict['Trip Name'])
                && $this->http->XPath->query("//*[{$this->eq($dict['Trip Name'])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->eq($dict['Traveler Details'])}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public static function getEmailProviders()
    {
        return ['wagonlit', 'brex'];
    }

    public function getProvider(\PlancakeEmailParser $parser)
    {
        if (
            stripos($parser->getCleanFrom(), 'no-reply-cwt@spotnana.com') !== false
            || $this->http->XPath->query("//a[{$this->contains(['.mycwt.com'], '@href')}]")->length > 0
            || $this->http->XPath->query("//*[{$this->contains(['no-reply-cwt@spotnana.com'])}]")->length > 0
        ) {
            return 'wagonlit';
        }

        if (
            stripos($parser->getCleanFrom(), 'no-reply@travel.brex.com') !== false
            || $this->http->XPath->query("//*[{$this->contains(['@travel.brex.com'])}]")->length > 0
        ) {
            return 'brex';
        }
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }
        $this->parseEmailHtml($email);

        $provider = $this->getProvider($parser);

        if (!empty($provider)) {
            $email->setProviderCode($provider);
        }

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
            if (!empty($dict["Traveler Details"])) {
                if ($this->http->XPath->query("//*[{$this->contains($dict['Traveler Details'])}]")->length > 0
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
        // Travel Agency
        $email->obtainTravelAgency();

        foreach ($this->t('otaConf') as $name) {
            $conf = $this->nextTd($name, "[following::text()[{$this->eq($this->t('Payment Details'))}]]", "/^\s*([\dA-Z]{5,})\s*$/");

            if (!empty($conf)) {
                $email->ota()
                    ->confirmation($conf, $this->http->FindSingleNode("//text()[{$this->eq($name)}]"));
            }
        }

        if (empty($email->getTravelAgency()->getConfirmationNumbers())) {
            $email->ota()
                ->confirmation(null);
        }

        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Hotel Details'))}]")->length > 0) {
            $this->parseHotel($email);
        }

        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Car Details'))}]")->length > 0) {
            $this->parseRental($email);
        }

        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Rail Details'))}]")->length > 0) {
            $this->parseTrain($email);
        }

        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Flight Details'))}]")->length > 0) {
            $this->parseFlight($email);
        }

        return true;
    }

    private function parseGeneral(Itinerary $it)
    {
        $traveller = $this->nextTd($this->t('Traveler'), "[following::text()[{$this->eq($this->t('Payment Details'))}]]");
        $account = $this->nextTd($this->t('Loyalty Program'), "[following::text()[{$this->eq($this->t('Payment Details'))}]]");
        $resDate = strtotime($this->nextTd($this->t('Reservation date'), "[preceding::text()[{$this->eq($this->t('Payment Details'))}]]"));
        $it->general()
            ->traveller($traveller, true);

        if (!empty($account)
            && preg_match("/^\s*(.*#)?\s*(\w+)\s*$/", $account, $m)
        ) {
            $it->program()
                ->account($m[2], false, null, $m[1]);
        }

        if (!empty($resDate)) {
            $it->general()
                ->date($resDate);
        }
    }

    private function parsePrice(Itinerary $it)
    {
        if ($it->getCancelled() === true) {
            return;
        }
        $total = $this->nextTd($this->t('Total charges'), "[preceding::text()[{$this->eq($this->t('Payment Details'))}]]");

        if (preg_match("#^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$#", $total, $m)
            || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$#", $total, $m)
        ) {
            $currencySign = $m['currency'];
            $currency = $this->currency($m['currency']);
            $m['amount'] = PriceHelper::parse($m['amount'], $currency);
            $it->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency)
            ;

            $cost = $this->nextTd($this->t('Cost'), "[preceding::text()[{$this->eq($this->t('Payment Details'))}]]");

            if (preg_match("#^\s*(?<currency>{$this->opt($currencySign)})\s*(?<amount>\d[\d\., ]*)\s*$#", $cost, $m)
                || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>{$this->opt($currencySign)})\s*$#", $cost, $m)
            ) {
                $it->price()
                    ->cost(PriceHelper::parse($m['amount'], $currency));
            }
            $tax = $this->nextTd($this->t('Taxes'), "[preceding::text()[{$this->eq($this->t('Payment Details'))}]]");

            if (preg_match("#^\s*(?<currency>{$this->opt($currencySign)})\s*(?<amount>\d[\d\., ]*)\s*$#", $tax, $m)
                || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>{$this->opt($currencySign)})\s*$#", $tax, $m)
            ) {
                $it->price()
                    ->tax(PriceHelper::parse($m['amount'], $currency));
            }
            $fees = $this->http->XPath->query("//text()[{$this->eq($this->t('Payment Details'))}]"
                . "/following::tr[count(*[normalize-space()]) = 2][preceding::text()[{$this->eq($this->t('Traveler Total'))}]][following::text()[{$this->eq($this->t('Total charges'))}]]");

            foreach ($fees as $fRoot) {
                $name = $this->http->FindSingleNode("*[1]", $fRoot);
                $value = $this->http->FindSingleNode("*[2]", $fRoot);

                if (preg_match("#^\s*(?<currency>{$this->opt($currencySign)})\s*(?<amount>\d[\d\., ]*)\s*$#", $value, $m)
                    || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>{$this->opt($currencySign)})\s*$#", $value, $m)
                ) {
                    $it->price()
                        ->fee($name, PriceHelper::parse($m['amount'], $currency));
                }
            }
        }
    }

    private function parseHotel(Email $email)
    {
        $h = $email->add()->hotel();

        // General
        $h->general()
            ->confirmation($this->nextTd($this->t('Hotel confirmation'), "[following::text()[{$this->eq($this->t('Payment Details'))}]]"))
            ->cancellation($this->nextTd($this->t('Cancellation policy')))
        ;
        $this->parseGeneral($h);

        if ($this->http->XPath->query("//*[{$this->contains($this->t('booking was cancelled.'))}]")->length > 0) {
            $h->general()
                ->status('Cancelled')
                ->cancelled();

            $cancelledNumber = $this->nextTd($this->t('Cancellation reference'));

            if (!empty($cancelledNumber)) {
                $h->general()
                    ->cancellationNumber($cancelledNumber);
            }
        }

        // Hotel
        $h->hotel()
            ->name($this->http->FindSingleNode("//text()[{$this->eq($this->t('CHECK IN'))}]/preceding::text()[normalize-space()][1]"))
            ->address($this->nextTd($this->t('Address')))
            ->phone($this->nextTd($this->t('Phone')), true, true)
            ->fax(preg_replace('/^\s*-\s*$/', '', $this->nextTd($this->t('Fax'))), true, true)
        ;

        // Booked
        $h->booked()
            ->checkIn($this->normalizeDate(implode(', ',
                $this->http->FindNodes("//*[*[1][{$this->starts($this->t('CHECK IN'))}] and *[3][{$this->starts($this->t('CHECK OUT'))}]]/*[1]/descendant::text()[normalize-space()][position() > 1]"))))
            ->checkOut($this->normalizeDate(implode(', ',
                $this->http->FindNodes("//*[*[1][{$this->starts($this->t('CHECK IN'))}] and *[3][{$this->starts($this->t('CHECK OUT'))}]]/*[3]/descendant::text()[normalize-space()][position() > 1]"))))
        ;

        // Rooms
        $h->addRoom()
            ->setType($this->nextTd($this->t('Room type')));

        // Price
        $this->parsePrice($h);

        return true;
    }

    private function parseRental(Email $email)
    {
        $r = $email->add()->rental();

        // General
        $r->general()
            ->confirmation($this->nextTd($this->t('Rental confirmation'), "[following::text()[{$this->eq($this->t('Payment Details'))}]]"))
            ->cancellation($this->nextTd($this->t('Cancellation policy')))
        ;
        $this->parseGeneral($r);

        if ($this->http->XPath->query("//*[{$this->contains($this->t('booking was cancelled.'))}]")->length > 0) {
            $r->general()
                ->status('Cancelled')
                ->cancelled();
        }

        // Pick Up
        $r->pickup()
            ->date($this->normalizeDate($this->nextTd($this->t('Pick-up date and time'))))
            ->location($this->nextTd($this->t('Address'), "[preceding::tr[normalize-space()][1][{$this->starts($this->t('Pick-up date and time'))}]]"))
        ;
        // Drop Off
        $r->dropoff()
            ->date($this->normalizeDate($this->nextTd($this->t('Drop-off date and time'))))
            ->location($this->nextTd($this->t('Address'), "[preceding::tr[normalize-space()][1][{$this->starts($this->t('Drop-off date and time'))}]]"))
        ;

        // Car
        $r->car()
            ->type($this->nextTd($this->t('Vehicle Class')) . ' (' . implode(', ',
                    $this->nextTds($this->t('Amenities'), "//text()[normalize-space()]")) . ')');

        // Extra
        $company = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Pick up'))}]/preceding::text()[normalize-space()][1]");
        $provider = $this->getRentalProviderByKeyword($company);

        if (!empty($provider)) {
            $r->setProviderCode($provider);
        } elseif (!empty($keyword)) {
            $r->extra()->company($keyword);
        }

        // Price
        $this->parsePrice($r);

        return true;
    }

    private function getRentalProviderByKeyword(string $keyword)
    {
        if (!empty($keyword)) {
            foreach ($this->detectRentalProviders as $code => $kws) {
                if (in_array($keyword, $kws)) {
                    return $code;
                } else {
                    foreach ($kws as $kw) {
                        if (strpos($keyword, $kw) !== false) {
                            return $code;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function parseTrain(Email $email)
    {
        $t = $email->add()->train();

        // General
        $confs = $this->nextTds($this->t('Carrier Confirmation'), "[following::text()[{$this->eq($this->t('Payment Details'))}]]", "/^\s*([A-Z\d]{5,})\s*$/");

        if (!empty($confs) && empty(array_filter($confs))) {
            $t->general()
                ->noConfirmation();
        } else {
            $confs = array_unique(array_filter($confs));

            foreach ($confs as $conf) {
                $t->general()
                    ->confirmation($conf);
            }
        }
        $this->parseGeneral($t);

        if ($this->http->XPath->query("//*[{$this->contains($this->t('booking was cancelled.'))}]")->length > 0) {
            $t->general()
                ->status('Cancelled')
                ->cancelled();
        }

        // Segment
        $xpath = "//text()[{$this->eq($this->t('Departure'))}]/ancestor::*[.//text()[{$this->eq($this->t('Departure date'))}]][1][count(.//text()[{$this->eq($this->t('Departure date'))}]) = 1]";
        // $this->logger->debug('$xpath = '.print_r( $xpath,true));
        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $root) {
            $s = $t->addSegment();

            $service = $this->http->FindSingleNode(".//text()[{$this->eq($this->t('Departure'))}]/preceding::text()[normalize-space()][1]", $root);
            $geotip = '';

            if (in_array($service, ['Empire Service', 'Acela'])) {
                $geotip = 'us';
            }

            if (in_array($service, ['Avanti West Coast'])) {
                $geotip = 'eu';
            }

            // Departure
            $s->departure()
                ->date($this->normalizeDate(implode(', ',
                    $this->http->FindNodes(".//*[*[1][{$this->starts($this->t('Departure'))}] and *[3][{$this->starts($this->t('Arrival'))}]]/*[1]/descendant::text()[normalize-space()][position() = 2 or position() = 3]", $root))))
                ->name($this->http->FindSingleNode(".//*[*[1][{$this->starts($this->t('Departure'))}] and *[3][{$this->starts($this->t('Arrival'))}]]/*[1]/descendant::text()[normalize-space()][4]", $root))
            ;

            if (!empty($geotip)) {
                $s->departure()
                    ->geoTip($geotip)
                ;
            }
            // Arrival
            $s->arrival()
                ->date($this->normalizeDate(implode(', ',
                    $this->http->FindNodes(".//*[*[1][{$this->starts($this->t('Departure'))}] and *[3][{$this->starts($this->t('Arrival'))}]]/*[3]/descendant::text()[normalize-space()][position() = 2 or position() = 3]", $root))))
                ->name($this->http->FindSingleNode(".//*[*[1][{$this->starts($this->t('Departure'))}] and *[3][{$this->starts($this->t('Arrival'))}]]/*[3]/descendant::text()[normalize-space()][4]", $root))
            ;

            if (!empty($geotip)) {
                $s->arrival()
                    ->geoTip($geotip)
                ;
            }

            // Extra
            $s->extra()
                ->noNumber()
                ->service($service)
                ->car($this->nextTd($this->t('Coach'), '', "/^\s*(\w+)\s*$/", $root), true, true)
                ->seat($this->nextTd($this->t('Seat'), '', "/^\s*(\w+)\s*$/", $root), true, true)
                ->duration($this->http->FindSingleNode(".//*[*[1][{$this->starts($this->t('Departure'))}] and *[3][{$this->starts($this->t('Arrival'))}]]/*[2]", $root))
            ;
        }

        // Price
        $this->parsePrice($t);

        return true;
    }

    private function parseFlight(Email $email)
    {
        $f = $email->add()->flight();

        // General
        $f->general()
            ->noConfirmation();
        $this->parseGeneral($f);

        if ($this->http->XPath->query("//*[{$this->contains($this->t('booking was cancelled.'))}]")->length > 0) {
            $f->general()
                ->status('Cancelled')
                ->cancelled();
        }

        // Issued
        $tickets = array_filter($this->nextTds($this->t('Ticket number(s)'), '', '/^[\d\- ]+$/'));

        if (!empty($tickets)) {
            $f->issued()
                ->tickets($tickets, false);
        }

        // Segment
        $xpath = "//*[count(.//text()[{$this->eq($this->t('Departure date'))}]) = 1][preceding-sibling::*[.//text()[{$this->starts($this->t('Flight time:'))}]]]";
        // $this->logger->debug('$xpath = '.print_r( $xpath,true));
        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            // Airline
            $flight = implode("\n", $this->http->FindNodes("preceding-sibling::*[2]//text()[normalize-space()]", $root));

            if (preg_match("/^.+, *(?<al>[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(?<fn>\d+)\n/", $flight, $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fn']);
            }

            if (preg_match("/{$this->opt($this->t('Operated by'))}\s*(\S.+)$/", $flight, $m)) {
                $s->airline()
                    ->operator($m[1]);
            }
            $conf = $this->nextTd($this->t('Airline confirmation'), '', "/^\s*([A-Z\d]{5,7})\s*$/", $root);

            if (!empty($conf)) {
                $s->airline()
                    ->confirmation($conf);
            }

            $date = $this->normalizeDate($this->nextTd($this->t('Departure date'), '', null, $root));
            $routeXpath = "preceding-sibling::*[1]/descendant-or-self::*[count(*[normalize-space()]) = 2][1]/";
            // Departure
            $s->departure()
                ->code($this->http->FindSingleNode($routeXpath . "*[1]/descendant::text()[normalize-space()][1]", $root))
                ->name($this->http->FindSingleNode($routeXpath . "*[1]/descendant::text()[normalize-space()][2]", $root))
                ->terminal(preg_replace("#\s*Terminal\s*#i", '', $this->http->FindSingleNode($routeXpath . "*[1]/descendant::text()[{$this->starts($this->t('Terminal:'))}]",
                    $root, true, "/^\s*{$this->opt($this->t('Terminal:'))}\s*(.+)/")), true, true)
            ;
            $time = $this->nextTd($this->t('Departure time'), '', null, $root);

            if (!empty($date) && !empty($time)) {
                $s->departure()
                    ->date(strtotime($time, $date));
            }

            // Arrival
            $s->arrival()
                ->code($this->http->FindSingleNode($routeXpath . "*[2]/descendant::text()[normalize-space()][1]", $root))
                ->name($this->http->FindSingleNode($routeXpath . "*[2]/descendant::text()[normalize-space()][2]", $root))
                ->terminal(preg_replace("#\s*Terminal\s*#i", '', $this->http->FindSingleNode($routeXpath . "*[2]/descendant::text()[{$this->starts($this->t('Terminal:'))}]",
                    $root, true, "/^\s*{$this->opt($this->t('Terminal:'))}\s*(.+)/")), true, true)
            ;
            $time = $this->nextTd($this->t('Arrival time'), '', null, $root);

            if (!empty($date) && !empty($time)) {
                if (preg_match("/^(.+?)\s*([\-+]\s*\d)\s*$/", $time, $m)) {
                    $time = $m[1];
                    $date = strtotime($m[2] . ' day', $date);
                }
                $s->arrival()
                    ->date(strtotime($time, $date));
            }

            // Extra
            $s->extra()
                ->cabin($this->nextTd($this->t('Cabin'), '', null, $root))
                ->duration($this->http->FindSingleNode("preceding-sibling::*[1]/descendant::text()[{$this->starts($this->t('Flight time:'))}]",
                    $root, true, "/^\s*{$this->opt($this->t('Flight time:'))}\s*(.+)/"))
            ;
            $seats = $this->nextTd($this->t('Seat'), '', null, $root);

            if (preg_match_all("/\b(\d{1,3}[A-Z])\s*\(([^\d\)]+)\)/", $seats, $m)) {
                foreach ($m[0] as $i => $v) {
                    $s->extra()
                        ->seat($m[1][$i], true, true, $m[2]);
                }
            }
        }

        // Price
        $this->parsePrice($f);

        return true;
    }

    private function nextTd($field, $cond = '', $regexp = null, $root = null)
    {
        return $this->http->FindSingleNode(".//*[count(*[normalize-space()]) = 2][*[normalize-space()][1][{$this->eq($field)}]]/*[normalize-space()][2]"
            . $cond, $root, true, $regexp);
    }

    private function nextTds($field, $cond = '', $regexp = null, $root = null)
    {
        return $this->http->FindNodes(".//*[count(*[normalize-space()]) = 2][*[normalize-space()][1][{$this->eq($field)}]]/*[normalize-space()][2]"
            . $cond, $root, $regexp);
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $in = [
            //            // Apr 09
            //            '/^\s*(\w+)\s+(\d+)\s*$/iu',
            //            // Sun, Apr 09
            //            '/^\s*(\w+),\s*(\w+)\s+(\d+)\s*$/iu',
            //            // Tue Jul 03, 2018 at 1:43 PM
            //            '/^\s*[\w\-]+\s+(\w+)\s+(\d+),\s*(\d{4})\s+(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/ui',
        ];
        $out = [
            //            '$2 $1 %year%',
            //            '$1, $3 $2 ' . $year,
            //            '$2 $1 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);

//        $this->logger->debug('date replace = ' . print_r( $date, true));

        // $this->logger->debug('date end = ' . print_r( $date, true));

        return strtotime($date);
    }

    private function currency($s)
    {
        if (preg_match("#^\s*([A-Z]{3})\s*$#", $s)) {
            return trim($s);
        }
        $sym = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
        ];

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return $s;
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
}
