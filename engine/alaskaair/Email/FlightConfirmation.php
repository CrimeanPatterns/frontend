<?php

namespace AwardWallet\Engine\alaskaair\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class FlightConfirmation extends \TAccountChecker
{
    public $mailFiles = "alaskaair/it-58093277.eml, alaskaair/it-58161096.eml, alaskaair/it-889940829.eml, alaskaair/it-909049166.eml, alaskaair/it-910975505.eml, alaskaair/it-911154900.eml, alaskaair/it-911788929.eml, alaskaair/it-914225813.eml, alaskaair/it-916201158.eml";
    public $from = '@ifly.alaskaair.com';
    public $subject = ['/(?:Confirmation code|confirmation receipt)[:\s]+[A-Za-z]+\s+for\s+your\s+flight\s+on\s+\d+\/\d+\/\d{2}\b/'];
    public $body = [
        'Total charges for air travel',
        'Alaska Airlines reservations',
        'Alaska Airlines provides',
        'Summary of airfare charges',
        'Summary of additional item charges',
        "We can't wait to see you on board. Before you fly",
        'Alaska Airlines rules and fees apply for this itinerary.',
        'We understand you may have questions about your upcoming Hawaiian Airlines flight',
    ];
    public $lang = 'en';
    public $date = null;

    public static $dictionary = [
        'en' => [
            'confDesc'    => ['Confirmation code', 'Standby confirmation code'],
            'Manage trip' => ['Manage trip', 'MANAGE TRIP'],
        ],
    ];

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->http->FilterHTML = true;

        if (preg_match("#for\s+your\s+flight\s+on\s+(\d+\/\d+\/)(\d{2})\.?$#", $parser->getSubject(), $m)) {
            $this->date = strtotime($m[1] . '20' . $m[2]);
        } else {
            $this->date = strtotime($parser->getDate());
        }

        $flight = $email->add()->flight();

        // collect reservation confirmation
        $confDesc = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Traveler(s)'))}])[1]/preceding::text()[{$this->starts($this->t('confDesc'))}][1]", null, true, "/({$this->opt($this->t('confDesc'))})/");
        $confNumber = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Traveler(s)'))}])[1]/preceding::text()[{$this->starts($this->t('confDesc'))}][1]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Za-z]{6})\s*$/")
            ?? $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Traveler(s)'))}])[1]/preceding::text()[{$this->starts($this->t('confDesc'))}][1]", null, true, "/^\s*{$this->opt($this->t('confDesc'))}[\s:：]+([A-Za-z]{6})\s*$/");

        if (!empty($confNumber)) {
            $flight->general()
                ->confirmation(mb_strtoupper($confNumber), $confDesc);
        }

        if (empty($confNumber) && preg_match("/(?<desc>{$this->opt($this->t('Your confirmation receipt'))})[\s:：]+(?<number>[A-Za-z]{6})\s/", $parser->getSubject(), $m)) {
            $flight->general()
                ->confirmation(mb_strtoupper($m['number']), $m['desc']);
        }

        if (empty($confNumber) && empty($confDesc)
            && stripos($parser->getSubject(), 'Your confirmation receipt: for your flight on') !== false
        ) {
            $flight->setNoConfirmationNumber(true);
        }

        // collect user email
        $userEmail = $this->http->FindSingleNode("//text()[{$this->starts($this->t('This email was sent to'))}]", null, true, "/^\s*{$this->opt($this->t('This email was sent to'))}\s*(\S+@\S+?)[\s\.]*$/")
            ?? $this->http->FindSingleNode("//text()[{$this->eq($this->t('This email was sent to'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\S+@\S+)\s*$/");

        if (!empty($userEmail)) {
            $email->setUserEmail($userEmail);
        }

        if (empty($travellers)) {
            $travellers = $this->http->FindNodes("//text()[{$this->eq($this->t('Traveler(s)'))}]/ancestor::tr[1]/following-sibling::tr/td[normalize-space()][1]/descendant::text()[normalize-space()][1]", null, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/");
        }

        $flight->general()->travellers(array_unique(array_filter($travellers)), true);

        $ffNumbers = array_filter($this->http->FindNodes("//*[starts-with(normalize-space(),'Mileage Plan')]", null, '/^Mileage Plan.*#\s*([*]+\d{2,})$/'));

        foreach ($ffNumbers as $ffNumber) {
            $pax = $this->http->FindSingleNode("//text()[{$this->contains($ffNumber)}]/preceding::text()[normalize-space()][string-length()>2][1]");

            if (!empty($pax)) {
                $flight->addAccountNumber($ffNumber, true, $pax);
            } else {
                $flight->addAccountNumber($ffNumber, true);
            }
        }

        //Tickets
        $tickets = array_filter($this->http->FindNodes("//text()[starts-with(normalize-space(),'Ticket')]", null, '/^Ticket\s*(\d{3}[- ]*\d{5,}[- ]*\d{1,2})$/'));

        foreach ($tickets as $ticket) {
            $pax = $this->http->FindSingleNode("//text()[{$this->contains($ticket)}]/preceding::text()[normalize-space()][not(contains(normalize-space(), 'Mileage Plan'))][string-length()>2][1]");

            if (!empty($pax)) {
                $flight->addTicketNumber($ticket, false, $pax);
            } else {
                $flight->addTicketNumber($ticket, false);
            }
        }

        //Price
        $totalPrice = $this->http->FindSingleNode("//text()[starts-with(normalize-space(),'Total charges for air')]/ancestor::td[1]/following-sibling::td[1]");

        if (preg_match('/^(?<currency>[^\d)(]+?)[ ]*(?<amount>\d[,.\'\d ]*)$/', $totalPrice, $m)) {
            $flight->price()
                ->currency($this->normalizeCurrency($m['currency']))
                ->total($this->amount($m['amount']));

            $cost = null;
            $priceCosts = array_filter($this->http->FindNodes("//text()[starts-with(normalize-space(),'Base fare and surcharges')]/ancestor::td[1]/following-sibling::td[1]", null, '/^(?:' . preg_quote($m['currency'], '/') . ')?[ ]*(\d[,.\'\d ]*)$/'));

            foreach ($priceCosts as $priceCost) {
                $cost += $this->amount($priceCost);
            }
            $flight->price()->cost($cost);

            $tax = null;
            $priceTaxs = array_filter($this->http->FindNodes("//text()[starts-with(normalize-space(),'Taxes and other fees')]/ancestor::td[1]/following-sibling::td[1]", null, '/^(?:' . preg_quote($m['currency'], '/') . ')?[ ]*(\d[,.\'\d ]*)$/'));

            foreach ($priceTaxs as $priceTax) {
                $tax += $priceTax;
            }
            $flight->price()->tax($tax);
        }

        $spentAwards = $this->http->FindSingleNode("//text()[starts-with(normalize-space(),'Total charges for air')]/following::text()[normalize-space()][position() < 10][{$this->contains($this->t('miles have been redeemed from'))}]",
            null, true, "/(?:^|\.)\s*(\d+[\d,]* miles) have been redeemed from/");

        if (!empty($spentAwards)) {
            $flight->price()
                ->spentAwards($spentAwards);
        }

        //Segments
        $xpath = "//text()[starts-with(normalize-space(), 'Traveler(s)')]/ancestor::table[1]/following::table[1]";
        // $this->logger->debug($xpath);
        $segments = $this->http->XPath->query($xpath);

        foreach ($segments as $root) {
            $segment = $flight->addSegment();

            $confSegment = $this->http->FindSingleNode("./preceding::text()[normalize-space()][1][starts-with(normalize-space(), 'Confirmation Code:')]", $root, true, "/Confirmation Code\:\s*([A-Za-z\d]{6})/");

            if (!empty($confSegment)) {
                $segment->setConfirmation(mb_strtoupper($confSegment));
            }

            //Airline
            $aName = $this->http->FindSingleNode("./preceding::text()[starts-with(normalize-space(), 'Traveler(s)')][1]/preceding::text()[starts-with(normalize-space(), 'Flight')][1]/preceding::text()[normalize-space()][1]", $root);
            $fNumber = $this->http->FindSingleNode("./preceding::text()[starts-with(normalize-space(), 'Traveler(s)')][1]/preceding::text()[starts-with(normalize-space(), 'Flight')][1]", $root, true, '/^Flight\s+(\d{1,5})(?:\s*\(.+\))?$/');

            $flightInfo = $this->http->FindSingleNode("./preceding::text()[starts-with(normalize-space(), 'Traveler(s)')][1]/preceding::text()[starts-with(normalize-space(), 'Flight')][1]", $root);

            if (preg_match("/Flight\s*\d+\s*\((?<aName>\w+)\s*(?<fNumber>\d{2,4})\)/", $flightInfo, $m)) {
                $aName = $m['aName'];
                $fNumber = $m['fNumber'];
            }

            $segment->airline()
                ->name($aName)
                ->number($fNumber);

            $operator = $this->http->FindSingleNode("./preceding::tr[normalize-space()][1]//text()[contains(., 'Operated by')]", $root, true, '/Operated\s+by\s+\D+as\s+(\D+)[.]\s+/');

            if (!empty($operator)) {
                $segment->airline()
                    ->operator($operator);
            }

            //Departure
            //Arrival
            $segmentText = implode("\n", $this->http->FindNodes('descendant::tr[normalize-space()][1]/descendant::text()[normalize-space()]', $root));
            $patterns['anyTime'] = '\d{1,2}(?:[:：]\d{1,2})?(?:\s*[AaPp]\.?[Mm]\.?)?';
            $patterns['validTime'] = '\d{1,2}(?:[:：]\d{2})?(?:\s*[AaPp]\.?[Mm]\.?)?';
            $pattern = "/^(?<depDate>\w+[,]\s+\w+\s+\d+(?:\s+\d{4})?\s+{$patterns['anyTime']})\s+(?<depCode>[A-Z]{3})\s+(?<depName>\w+\D+)\s+(?<arrDate>\w+[,]\s+\w+\s+\d+(?:\s+\d{4})?\s+{$patterns['validTime']})\s+(?<arrCode>[A-Z]{3})\s*(?<arrName>\w+\D+)$/u";

            if (preg_match($pattern, $segmentText, $m)) {
                $segment->departure()
                    ->code($m['depCode'])
                    ->name(preg_replace("#\s+#", ' ', trim($m['depName'])));

                if (preg_match("/^\s*\w+[,]\s+\w+\s+\d+\s+{$patterns['validTime']}\s*$/", $m['depDate'], $o)) {
                    $segment->departure()
                        ->date($this->normalizeDate($m['depDate']));
                } else {
                    $segment->departure()
                        ->noDate();
                }

                $segment->arrival()
                    ->code($m['arrCode'])
                    ->name(preg_replace("#\s+#", ' ', trim($m['arrName'])))
                    ->date($this->normalizeDate($m['arrDate']));
            }

            $aircraft = $this->http->FindSingleNode("./preceding::text()[starts-with(normalize-space(), 'Traveler(s)')][1]/preceding::text()[normalize-space()][1]", $root);

            if (!empty($aircraft)) {
                $segment->extra()
                    ->aircraft($aircraft);
            }

            // Seats
            // BookingCode
            // Cabin
            $seats = [];
            $bookingCodes = [];
            $cabins = [];
            $seatTexts = $this->http->FindNodes("preceding::table[1]/descendant::text()[starts-with(normalize-space(),'Seat:')]/ancestor::tr[1]", $root);

            foreach ($seatTexts as $sText) {
                // Seat: 7D★, Class: R (Coach)
                if (preg_match("/Seat\s*[:]+\s*(\d+[A-Z])[\s★]*(?:,|[*]|Class|$)/", $sText, $m)) {
                    $seats[] = $m[1];
                }

                if (preg_match("/Class\s*[:]+\s*([A-Z]{1,2})\s*\(\s*(?:([^)(]+?))?\s*\)/", $sText, $m)) {
                    $bookingCodes[] = $m[1];

                    if (isset($m[2]) && !empty($m[2])) {
                        $cabins[] = $m[2];
                    }
                }
            }
            $seats = array_unique($seats);
            $bookingCodes = array_unique($bookingCodes);
            $cabins = array_unique($cabins);

            if (count($seats) > 0) {
                $segment->extra()->seats($seats);
            }

            if (count($bookingCodes) === 1) {
                $segment->extra()->bookingCode($bookingCodes[0]);
            }

            if (count($cabins) === 1) {
                $segment->extra()->cabin($cabins[0]);
            }
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, $this->from) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        foreach ($this->subject as $subject) {
            if (preg_match($subject, $headers['subject']) > 0) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//a/@href[{$this->contains('alaskaair.com')}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains('alaskaair.com')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->contains($this->body)}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Trip insurance by Allianz Global Assistance'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Traveler(s)'))}]")->length > 0
            && ($this->http->XPath->query("//a[{$this->eq($this->t('Manage trip'))}]/following::text()[normalize-space()][1][{$this->contains($this->t('confDesc'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->eq($this->t('you’re all set.'))} or {$this->starts($this->t('See you on board'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->starts($this->t('Navigating your trip on'))}]")->length > 0
            )
        ) {
            return true;
        }

        return false;
    }

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    private function normalizeDate($str)
    {
        // $this->logger->debug('date begin = ' . print_r( $str, true));
        $year = date("Y", $this->date);
        $in = [
            "#^(\w+)[,\s]+(\w+)\s+(\d+)\s+([\d\:]+\s+[AP]M)$#",         // Tue, Jun 09 08:05 AM      => Tue, 09 Jun $year, 08:05 AM
            "#^\w+[,\s]+(\w+)\s+(\d+)\s+(\d{4})\s+([\d\:]+\s+[AP]M)$#", // Tue, Jun 09 2024 08:05 AM => Tue, 09 Jun 2024, 08:05 AM
        ];
        $out = [
            "$1, $3 $2 $year, $4",
            "$2 $1 $3, $4",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        // $this->logger->debug('date replace = ' . print_r( $str, true));
        if (preg_match("#^(?<week>\w+), (?<date>\d+ \w+ \d{4})(?:\,\s+(?<time>[\d\:]+.+)?)#u", $str, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));
            $str = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);

            if (!empty($m['time'])) {
                $str = strtotime($m['time'], $str);
            }
        } elseif (preg_match("#\b\d{4}\b#", $str)) {
            $str = strtotime($str);
        } else {
            $str = null;
        }
        // $this->logger->debug('date end = ' . print_r( $str, true));

        return $str;
    }

    private function normalizeCurrency(string $string): string
    {
        $string = trim($string);
        $currences = [
            'GBP' => ['£'],
            'EUR' => ['€'],
            'USD' => ['US$', '$'],
        ];

        foreach ($currences as $currencyCode => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency) {
                    return $currencyCode;
                }
            }
        }

        return $string;
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

    private function amount($price): ?float
    {
        $price = str_replace([',', ' '], '', $price);

        if (is_numeric($price)) {
            return (float) $price;
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
}
