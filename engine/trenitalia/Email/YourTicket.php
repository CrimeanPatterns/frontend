<?php

namespace AwardWallet\Engine\trenitalia\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;

class YourTicket extends \TAccountChecker
{
    public $mailFiles = "trenitalia/it-898624873.eml, trenitalia/it-901694273.eml, trenitalia/it-913227279.eml, trenitalia/it-914309277.eml, trenitalia/it-915099131.eml, trenitalia/it-919212795.eml, trenitalia/it-920120678.eml, trenitalia/it-922360771.eml";

    public $lang;
    public static $dictionary = [
        'en' => [
            'Passenger'     => ['Passenger', 'Tickets purchased by:'],
            'Offer/Comfort' => 'Offer/Comfort',
            'accountDesc'   => ['CartaFreccia/X-GO', 'Loyalty card'],
            'Points earned' => ['Points earned', 'Points earned*'],
        ],
    ];

    private $detectFrom = "webmaster@trenitalia.it";
    private $detectSubject = [
        // en
        'Your Trenitalia Ticket',
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]trenitalia\.it$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], ' Trenitalia ') === false
        ) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//a/@href[{$this->contains(['.trenitalia.'])}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains(['Trenitalia'])}]")->length === 0
            && $this->http->XPath->query("//img/@alt[{$this->contains(['Trenitalia'])}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains(['www.trenitalia.com'])}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        foreach (self::$dictionary as $dict) {
            if (!empty($dict['Passenger']) && $this->http->XPath->query("//*[{$this->eq($dict['Passenger'])}]")->length > 0
                && !empty($dict['Offer/Comfort']) && $this->http->XPath->query("//*[{$this->eq($dict['Offer/Comfort'])}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }
        $this->parseEmailHtml($email);

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

    // additional methods

    protected function array_diff_recursive($array1, $array2)
    {
        $result = [];

        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (isset($array2[$key]) && is_array($array2[$key])) {
                    $recursive_diff = $this->array_diff_recursive($value, $array2[$key]);

                    if (count($recursive_diff) > 0) {
                        $result[$key] = $recursive_diff;
                    }
                } else {
                    $result[$key] = $value;
                }
            } else {
                if (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["Passenger"]) && !empty($dict["Offer/Comfort"])) {
                if ($this->http->XPath->query("//*[{$this->contains($dict['Passenger'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($dict['Offer/Comfort'])}]")->length > 0
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
        $patterns = [
            'phone'         => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52    |    (+351) 21 342 09 07    |    713.680.2992
            'travellerName' => "(?:{$this->opt(['Dr', 'Miss', 'Mrs', 'Mr', 'Ms', 'Mme', 'Mr/Mrs', 'Mrs/Mr', 'Monsieur'])}[\.\s]*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])", // Mr. Hao-Li Huang => Hao-Li Huang
        ];

        $segments = $this->http->XPath->query("//*[count(tr) = 2 and tr[1][{$this->contains('/')} and {$this->contains(':')}] and tr[2][{$this->contains($this->t('Passenger'))}] ]/tr[1]");

        foreach ($segments as $sRoot) {
            unset($t);

            // skip old reservations (pre-change trip)
            if (!empty($this->http->FindSingleNode("./preceding::text()[normalize-space()][1][{$this->eq($this->t('Pre-change trip'))}]", $sRoot))) {
                continue;
            }

            $text = implode("\n", $this->http->FindNodes("./descendant::text()[normalize-space()]", $sRoot));
            $re = "/^\s*(?<service>.+?)(?:\s+(?<number>[A-Z\d]+))?\s+of\s+(?<date>.+)\n\s*(?<dName>.+?)\s*\((?<dTime>\d{1,2}:\d{2})\)\s+-\s+(?<aName>.+?)\s*\((?<aTime>\d{1,2}:\d{2})\)\s*$/";

            if (preg_match($re, $text, $m)) {
                // determine itinerary type
                $type = 'train';

                if ($this->striposArray($m['service'], (array) $this->t('Autobus'))) {
                    // it-914309277.eml
                    $type = 'bus';
                }

                // collect PNR
                $confNumber = $this->http->FindSingleNode("./following-sibling::tr[1]/descendant::text()[{$this->eq($this->t('PNR'))}]/following::text()[normalize-space()][1]", $sRoot);

                // find existing itinerary to which this prevSegment (segmentText) belongs
                foreach ($email->getItineraries() as $it) {
                    if ($type === $it->getType()
                        && (in_array($confNumber, array_column($it->getConfirmationNumbers(), 0))
                            || (empty($confNumber) && count($it->getConfirmationNumbers()) === 0)
                        )
                    ) {
                        $t = $it;

                        break;
                    }
                }

                // if itinerary is not found, create new itinerary
                if (!isset($t)) {
                    if ($type === 'bus') {
                        $t = $email->add()->bus();
                    } else {
                        $t = $email->add()->train();
                    }
                }

                // save PNR
                if (!empty($confNumber) && !in_array($confNumber, array_column($t->getConfirmationNumbers(), 0))) {
                    $t->addConfirmationNumber($confNumber, 'PNR');
                }

                // if no PNR, set noConfirmation
                if (empty($confNumber) && count($t->getConfirmationNumbers()) === 0) {
                    $t->setNoConfirmationNumber(true);
                }

                $s = $t->addSegment();

                // collect departure and arrival info
                It6132072::assignRegion($m['dName'], $m['service']);
                $s->departure()
                    ->name($m['dName'] . (It6132072::$region == 'Italia' ? ', Italia' : ''))
                    ->geoTip(It6132072::$regionCode)
                    ->date($this->normalizeDate($m['date'] . ', ' . $m['dTime']))
                ;

                It6132072::assignRegion($m['aName'], $m['service']);
                $s->arrival()
                    ->name($m['aName'] . (It6132072::$region == 'Italia' ? ', Italia' : ''))
                    ->geoTip(It6132072::$regionCode)
                    ->date($this->normalizeDate($m['date'] . ', ' . $m['aTime']))
                ;

                // collect service name/bus type
                if ($t->getType() === 'train') {
                    $s->setServiceName($m['service']);
                } elseif ($t->getType() === 'bus') {
                    $s->setBusType($m['service']);
                }

                if (!empty($m['number'])) {
                    $s->setNumber($m['number']);
                } else {
                    $s->setNoNumber(true);
                }
            } else {
                return $email;
            }

            // collect travellers
            $travellers = $this->http->FindNodes("./following-sibling::tr[1]/descendant::td[{$this->eq($this->t('Passenger'))}]/following-sibling::td", $sRoot, "#^\s*({$patterns['travellerName']})\s*$#u");

            foreach ($travellers as $traveller) {
                if (!in_array($traveller, array_column($t->getTravellers(), 0))) {
                    $t->addTraveller($traveller);
                }
            }

            // collect tickets
            $ticketsText = $this->http->FindSingleNode("./following-sibling::tr[1]/descendant::text()[{$this->eq($this->t('Ticket Code'))}]/following::text()[normalize-space()][1]", $sRoot);

            // if more than one ticket number at td (separated by comma)
            if (preg_match_all("/\b(\d{9,11})\b/", $ticketsText, $m)) {
                $m[1] = array_diff($m[1], array_column($t->getTicketNumbers(), 0));

                foreach ($m[1] as $ticket) {
                    if (count($travellers) === 1) {
                        $t->addTicketNumber($ticket, false, $travellers[0]);
                    } else {
                        $t->addTicketNumber($ticket, false);
                    }
                }
            }

            // collect cabin
            $cabins = array_unique(array_filter($this->http->FindNodes("./following-sibling::tr[1]/descendant::td[{$this->eq($this->t('Offer/Comfort'))}]/following-sibling::td", $sRoot)));

            if (count($cabins) == 1) {
                $s->extra()
                    ->cabin($this->re("/^\s*(?:.+?\/\s*)?(.+?)\s*$/", $cabins[0]));
            }

            // collect seats
            $seats = $this->http->FindSingleNode("./following-sibling::tr[1]/descendant::td[{$this->eq($this->t('Coach/Seat'))}]/following-sibling::td", $sRoot);

            if (preg_match("/^\s*(?<carNumber>\d+)\s*\/\s*(?<seats>[A-Z\d\s\,]+)\s*$/", $seats, $m)) {
                $s->setCarNumber($m['carNumber']);

                if (preg_match_all("/\b(\d{1,2}[A-Z]?)\b/", $m['seats'], $m1)) {
                    foreach (array_map(null, $m1[1], $travellers) as [$seat, $traveller]) {
                        $s->addSeat($seat, false, false, $traveller);
                    }
                }
            }

            // collect accounts
            $account = $this->http->FindSingleNode("./following-sibling::tr[1]/descendant::td[{$this->eq($this->t('accountDesc'))}]/ancestor::tr[1]", $sRoot);

            if (preg_match("/^\s*(?<desc>{$this->opt($this->t('accountDesc'))})\s*(?<number>\d{8,10})\s*$/", $account, $m)
                && !in_array($m['number'], array_column($t->getAccountNumbers(), 0))
            ) {
                if (count($travellers) === 1) {
                    $t->addAccountNumber($m['number'], false, $travellers[0], $m['desc']);
                } else {
                    $t->addAccountNumber($m['number'], false, null, $m['desc']);
                }
            }

            // check that new segment has equal route and dates (compared to previous segments)
            // if route and dates matches - copy seats from new segment and remove new segment
            foreach ($t->getSegments() as $prevSegment) {
                if ($prevSegment->getId() !== $s->getId()
                    && $prevSegment->getDepName() === $s->getDepName() && $prevSegment->getDepDate() === $s->getDepDate()
                    && $prevSegment->getArrName() === $s->getArrName() && $prevSegment->getArrDate() === $s->getArrDate()
                ) {
                    $diffAssignedSeats = $this->array_diff_recursive($s->getAssignedSeats(), $prevSegment->getAssignedSeats());

                    foreach ($diffAssignedSeats as $diffAssignedSeat) {
                        $prevSegment->addSeat($diffAssignedSeat[0], false, false, $diffAssignedSeat[1]);
                    }

                    $t->removeSegment($s);

                    break;
                }
            }
        }

        $itineraries = $email->getItineraries();

        // collect price info
        $totals = $this->http->FindNodes("//td[{$this->eq($this->t('Amount'))}]/following-sibling::td");
        $total = null;

        foreach ($totals as $totalText) {
            if (preg_match("#^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\.\,\' ]*)\s*$#", $totalText, $m)
                || preg_match("#^\s*(?<amount>\d[\d\.\,\' ]*)\s*(?<currency>[^\d\s]{1,5})\s*$#", $totalText, $m)
            ) {
                $currency = $this->currency($m['currency']);
                $total += PriceHelper::parse($m['amount'], $currency);
            }
        }

        if (count($itineraries) === 1) {
            $it = end($itineraries);
        } else {
            $it = $email;
        }

        if ($total !== null) {
            $it->price()
                ->total($total)
                ->currency($currency);
        }

        // collect earned points
        $earnedPoints = $this->http->FindNodes("//td[{$this->eq($this->t('Points earned'))}]/following-sibling::td", null, "/^\s*([\d\.\,\']+)\s*$/");
        $pointsSum = null;

        foreach ($earnedPoints as $points) {
            $pointsSum += PriceHelper::parse($points);
        }

        if ($pointsSum !== null) {
            $it->setEarnedAwards("$pointsSum points");
        }

        // collect provider phone
        $phone = $this->http->FindSingleNode("//text()[{$this->starts($this->t('For assistance contact our call centre, at'))}]", null, true,
            "/^{$this->opt($this->t('For assistance contact our call centre, at'))}\s+({$patterns['phone']})/");

        if (!empty($phone)) {
            foreach ($itineraries as $it) {
                $it->addProviderPhone($phone);
            }
        }

        return true;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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
        // $this->logger->debug('date begin = ' . print_r( $date, true));

        $in = [
            // 09/04/2025, 15:45
            '/^\s*(\d{1,2})\/(\d{2})\/(\d{4})\s*,\s*(\d{1,2}:\d{2})\s*$/iu',
        ];
        $out = [
            '$1.$2.$3, $4',
        ];

        $date = preg_replace($in, $out, $date);
        // if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
        //     if ($en = MonthTranslate::translate($m[1], $this->lang)) {
        //         $date = str_replace($m[1], $en, $date);
        //     }
        // }
        // $this->logger->debug('date replace = ' . print_r( $date, true));

        // $this->logger->debug('date end = ' . print_r( $date, true));

        return strtotime($date);
    }

    private function currency($s)
    {
        if (preg_match("#^\s*([A-Z]{3})\s*$#", $s, $m)) {
            return $m[1];
        }
        $sym = [
            'Eur' => 'EUR',
        ];

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return $s;
    }
}
