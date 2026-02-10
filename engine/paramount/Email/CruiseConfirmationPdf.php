<?php

namespace AwardWallet\Engine\paramount\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class CruiseConfirmationPdf extends \TAccountChecker
{
    public $mailFiles = "paramount/it-914448584.eml, paramount/it-914450561.eml, paramount/it-914665279.eml";

    public $date = null;

    public $pdfNamePattern = ".*\.pdf";

    public $patterns;

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'reservationName' => ['Flight', 'Transfer', 'Cruise Overview'],
            'phoneDesc'       => ['need any further information regarding your reservation'],
        ],
    ];

    private $detectFrom = "@paramountcruises.com";

    private $detectSubject = [
        // en
        'Your Paramount Cruises Booking, Reference:',
    ];

    private $detectBody = [
        'en' => [
            'Cruise Overview', 'CABIN NUMBER:', 'CABIN GRADE:', 'BED CONFIGURATION:',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]paramountcruises\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], 'Paramount') === false
        ) {
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
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text) === true) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf($text)
    {
        if (empty($text)) {
            return false;
        }

        // detect Provider
        if (stripos($text, 'Paramount Cruises') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectBody as $detectBody) {
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

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->patterns = [
            'phone'         => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52    |    (+351) 21 342 09 07    |    713.680.2992
            'time'          => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.    |    3pm
            'travellerName' => "(?:{$this->opt(['Dr', 'Miss', 'Mrs', 'Mr', 'Ms', 'Mme', 'Mr/Mrs', 'Mrs/Mr'])}[\.\s]*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])", // Mr. Hao-Li Huang => Hao-Li Huang
        ];

        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text)) {
                $this->parseEmailPdf($email, $text);
            }
        }

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

    private function parseEmailPdf(Email $email, ?string $textPdf = null)
    {
        if (preg_match("/(?<otaDesc>{$this->opt($this->t('PARAMOUNT BOOKING REFERENCE'))})[\:\s]+(?<otaConf>[-A-Z\d]+)\b/", $textPdf, $m)) {
            $email->ota()
                ->confirmation($m['otaConf'], $m['otaDesc']);
        }

        $reservationText = $this->re("/({$this->opt($this->t('YOUR HOLIDAY DETAILS'))}.+?)(?:{$this->opt($this->t('YOUR HOLIDAY COSTING'))}|$)/s", $textPdf);
        $parts = $this->split("/({$this->opt($this->t('reservationName'))}[ ]*\n)/s", $reservationText);

        // collect reservations
        foreach ($parts as $part) {
            if (strpos($part, 'Cruise Overview') !== false) {
                $this->parseCruisePdf($email, $part);
            } elseif (strpos($part, 'Flight') !== false) {
                $this->parseFlightPdf($email, $part);
            } elseif (strpos($part, 'Transfer') !== false) {
                $this->parseTransferPdf($email, $part);
            }
        }

        // correct depName and arrName in transferSegments
        $this->correctTransferAddresses($email);

        // collect total
        if (preg_match("/{$this->opt($this->t('TOTAL COST'))}.+\s+(?<currency>[^\d\s]{1,3})[ ]*(?<amount>[\d\.\,\']+)[ ]{5,}/", $textPdf, $m)
            || preg_match("/{$this->opt($this->t('TOTAL COST'))}.+\s+(?<amount>[\d\.\,\']+)[ ]*(?<currency>[^\d\s]{1,3})[ ]{5,}/", $textPdf, $m)
        ) {
            $currency = $this->normalizeCurrency($m['currency']);

            $email->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);
        }

        // collect phone
        if (preg_match("/{$this->opt($this->t('call our team on'))}\s+(?<phone>{$this->patterns['phone']})\./s", $textPdf, $m)) {
            $email->ota()->phone($m['phone']);
        }

        return $email;
    }

    private function parseCruisePdf(Email $email, ?string $text = null)
    {
        if (empty($text)) {
            return false;
        }

        $cr = $email->add()->cruise();

        // create table from Cruise Overview block
        $cruiseOverviewText = $this->re("/({$this->opt($this->t('SUPPLIER REFERENCE'))}.+?)\s+{$this->opt($this->t('Passengers'))}/s", $text);
        $pos = $this->columnPositions($cruiseOverviewText, 20);
        $cruiseOverviewTable = $this->createTable($cruiseOverviewText, $pos);

        if (count($cruiseOverviewTable) !== 3) {
            return false;
        }

        // collect reservation confirmation
        if (preg_match("/(?<desc>{$this->opt($this->t('SUPPLIER REFERENCE'))})[\:\s]+(?<number>\d+)[ ]*\n/", $cruiseOverviewTable[0], $m)) {
            $cr->general()->confirmation($m['number'], $m['desc']);
        }

        // collect departure date
        $this->date = $this->normalizeDate($this->re("/{$this->opt($this->t('DEPARTURE DATE'))}[\:\s]+(\d+\/\d+\/\d{4})[ ]*(?:\n|$)/", $cruiseOverviewTable[1]));

        // collect cruise description
        $desc = $this->re("/{$this->opt($this->t('CRUISE LINE'))}[\:\s]+(.+?)[ ]*(?:\n|$)/", $cruiseOverviewTable[1]);
        $cr->setDescription($desc);

        // collect ship name
        $shipName = $this->re("/{$this->opt($this->t('CRUISE SHIP'))}[\:\s]+(.+?)[ ]*\n/", $cruiseOverviewTable[2]);
        $cr->setShip($shipName);

        // create table from Passengers and Cabin Details blocks
        $passAndCabinText = $this->re("/{$this->opt($this->t('Cabin Details'))}[ ]*\n(.+?)\s*{$this->opt($this->t('YOUR CRUISE ITINERARY'))}/s", $text);
        $pos = $this->columnPositions($passAndCabinText, 20);
        $passAndCabinTable = $this->createTable($passAndCabinText, $pos);

        if (count($passAndCabinTable) !== 3) {
            return false;
        }

        // collect travellers
        if (preg_match_all("/{$this->opt($this->t('PASSENGER:'))}\s+{$this->patterns['travellerName']}[ ]*(?:\n|$)/ui", $passAndCabinTable[0], $m)) {
            $cr->setTravellers($m[1]);
        }

        // collect class (cabin)
        $cabin = $this->re("/{$this->opt($this->t('CABIN GRADE'))}[\:\s]+(.+?)[ ]*\n/", $passAndCabinTable[1]);
        $cr->setClass($cabin);

        // collect room (cabin number)
        $room = $this->re("/{$this->opt($this->t('CABIN NUMBER'))}[\:\s]+(.+?)[ ]*\n/", $passAndCabinTable[2]);
        $cr->setRoom($room);

        // get text from YOUR CRUISE ITINERARY block (segments text)
        $segmentsText = $this->re("/{$this->opt($this->t('YOUR CRUISE ITINERARY'))}[ ]*\n(.+?)\s*$/s", $text);

        $s = null;
        $preTime = 'ashoreTime';

        foreach ($this->split("/(\d+\/\d+)/", $segmentsText) as $number=>$segment) {
            if (preg_match("/^[ ]*(?<date>\d+\/\d+)[ ]+(?<ashoreTime>{$this->patterns['time']})?[ ]+\/[ ]+(?<aboardTime>{$this->patterns['time']})?[ ]+(?<name>.+?)[ ]*$/s", $segment, $m)) {
                // if at sea
                if (empty($m['ashoreTime']) && empty($m['aboardTime'])) {
                    continue;
                }

                // if aboardTime is missing at preceding segment: set aboardTime as '16:00'
                if (!empty($s) && !empty($m['ashoreTime']) && $preTime === 'ashoreTime') {
                    $s->setAboard(strtotime('16:00', $s->getAshore()));
                    $preTime = 'aboardTime';
                }

                // if new segment
                if (empty($s) || !empty($s->getAboard())) {
                    $s = $cr->addSegment();
                    // collect port/city name
                    $s->setName(preg_replace("/\s+/", ' ', $m['name']));
                }

                // collect date
                $date = $this->normalizeDate($m['date']);

                // if cruise ends at next year
                if ($date < $this->date) {
                    $date = strtotime('+1 year', $date);
                }

                // collect ashore and aboard
                if (!empty($m['ashoreTime']) && $preTime === 'aboardTime') {
                    $s->setAshore(strtotime($m['ashoreTime'], $date));
                    $preTime = 'ashoreTime';
                }

                if (!empty($m['aboardTime']) && $preTime === 'ashoreTime') {
                    $s->setAboard(strtotime($m['aboardTime'], $date));
                    $preTime = 'aboardTime';
                }
            }
        }
    }

    private function parseFlightPdf(Email $email, ?string $text = null)
    {
        if (empty($text)) {
            return false;
        }

        $f = $email->add()->flight();

        // no confirmation
        $f->setNoConfirmationNumber(true);

        $segments = $this->split("/({$this->opt($this->t('AIRLINE:'))})/", $text);

        foreach ($segments as $segment) {
            $s = $f->addSegment();

            // collect airline name
            $airlineName = $this->re("/{$this->opt($this->t('AIRLINE'))}[\:\s]+(.+?)[ ]{5,}/", $segment);
            $s->setAirlineName($airlineName);

            // collect flight number
            $flightNumber = $this->re("/{$this->opt($this->t('FLIGHT NUMBER'))}[\:\s]+(\d{1,4})[ ]{5,}/", $segment);
            $s->setFlightNumber($flightNumber);

            // collect cabin
            $cabin = $this->re("/{$this->opt($this->t('CLASS'))}[\:\s]+(.+?)[ ]*\n/", $segment);
            $s->setCabin($cabin);

            // collect departure info
            if (preg_match("/{$this->opt($this->t('DEPARTS'))}[\:\s]+(?<depDate>\d+\/\d+\/\d{4})[\- ]+(?<depName>.+?)[ ]*\((?<depCode>[A-Z]{3})\)[ ]*(?<depTime>{$this->patterns['time']})[ ]*\n/", $segment, $m)) {
                $s->setDepName($m['depName'])
                    ->setDepCode($m['depCode'])
                    ->setDepDate(strtotime($m['depTime'], $this->normalizeDate($m['depDate'])));
            }

            // collect arrival info
            if (preg_match("/{$this->opt($this->t('ARRIVES'))}[\:\s]+(?<arrDate>\d+\/\d+\/\d{4})[\- ]+(?<arrName>.+?)[ ]*\((?<arrCode>[A-Z]{3})\)[ ]*(?<arrTime>{$this->patterns['time']})[ ]*\n/", $segment, $m)) {
                $s->setArrName($m['arrName'])
                    ->setArrCode($m['arrCode'])
                    ->setArrDate(strtotime($m['arrTime'], $this->normalizeDate($m['arrDate'])));
            }
        }
    }

    private function parseTransferPdf(Email $email, ?string $text = null)
    {
        if (empty($text)) {
            return false;
        }

        $t = $email->add()->transfer();

        // no confirmation
        $t->setNoConfirmationNumber(true);

        // create table from Transfer block
        $transferText = $this->re("/{$this->opt($this->t('Transfer'))}[ ]*\n(.+?)\s*(?:{$this->opt($this->t('Transfer times are'))}|$)/s", $text);
        $pos = $this->columnPositions($transferText, 20);
        $transferTable = $this->createTable($transferText, $pos);

        if (count($transferTable) !== 3) {
            return false;
        }

        $s = $t->addSegment();

        // collect departure date
        if (preg_match("/{$this->opt($this->t('PICKUP DATE'))}[\:\s]+(?<depDate>\d+\s+[^\d\s]+\s+\d{4})\s+(?<depTime>{$this->patterns['time']})\s*$/", $transferTable[0], $m)) {
            $s->setDepDate(strtotime($m['depTime'], $this->normalizeDate($m['depDate'])));
        }

        // no arrival date
        $s->setNoArrDate(true);

        // collect departure address
        $depAddress = $this->re("/{$this->opt($this->t('PICKUP DETAILS'))}[\:\s]+(.+?)\s*$/", $transferTable[1]);

        if ($depAddress === 'AIRPORT') {
            $s->setDepAddress('Need to set airport address');
        } elseif ($depAddress === 'PORT') {
            $s->setDepAddress('Need to set port address');
        }

        // collect arrival address
        $arrAddress = $this->re("/{$this->opt($this->t('DROPOFF DETAILS'))}[\:\s]+(.+?)\s*$/", $transferTable[2]);

        if ($arrAddress === 'AIRPORT') {
            $s->setArrAddress('Need to set airport address');
        } elseif ($arrAddress === 'PORT') {
            $s->setArrAddress('Need to set port address');
        }
    }

    private function correctTransferAddresses(Email $email)
    {
        $itineraries = $email->getItineraries();

        foreach ($itineraries as $number => $itinerary) {
            if ($itinerary->getType() === 'transfer') {
                $transferSegment = $itinerary->getSegments()[0];

                // get airport from preceding flight reservation
                if ($number > 0 && $transferSegment->getDepAddress() === 'Need to set airport address' && $itineraries[$number - 1]->getType() === 'flight') {
                    $flightSegments = $itineraries[$number - 1]->getSegments();
                    $flightSegment = $flightSegments[count($flightSegments) - 1];

                    $transferSegment->setDepAddress($flightSegment->getArrName());
                    $transferSegment->setDepCode($flightSegment->getArrCode());
                }

                // get port from preceding cruise reservation
                if ($number > 0 && $transferSegment->getDepAddress() === 'Need to set port address' && $itineraries[$number - 1]->getType() === 'cruise') {
                    $cruiseSegments = $itineraries[$number - 1]->getSegments();
                    $cruiseSegment = $cruiseSegments[count($cruiseSegments) - 1];

                    $transferSegment->setDepAddress($cruiseSegment->getName());
                }

                // get airport from following flight reservation
                if ($number < count($itineraries) && $transferSegment->getArrAddress() === 'Need to set airport address' && $itineraries[$number + 1]->getType() === 'flight') {
                    $flightSegments = $itineraries[$number + 1]->getSegments();
                    $flightSegment = $flightSegments[0];

                    $transferSegment->setArrAddress($flightSegment->getDepName());
                    $transferSegment->setArrCode($flightSegment->getDepCode());
                }

                // get airport from following cruise reservation
                if ($number < count($itineraries) && $transferSegment->getArrAddress() === 'Need to set port address' && $itineraries[$number + 1]->getType() === 'cruise') {
                    $cruiseSegments = $itineraries[$number + 1]->getSegments();
                    $cruiseSegment = $cruiseSegments[0];

                    $transferSegment->setArrAddress($cruiseSegment->getName());
                }

                // check correction
                if ($transferSegment->getDepAddress() === 'Need to set port address' || $transferSegment->getDepAddress() === 'Need to set airport address') {
                    $transferSegment->setDepAddress(null);
                }

                if ($transferSegment->getArrAddress() === 'Need to set port address' || $transferSegment->getArrAddress() === 'Need to set airport address') {
                    $transferSegment->setArrAddress(null);
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

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function columnPositions($table, $correct = 5)
    {
        $pos = [];
        $rows = explode("\n", $table);

        foreach ($rows as $row) {
            $pos = array_merge($pos, $this->rowColumnPositions($row));
        }
        $pos = array_unique($pos);
        sort($pos);
        $pos = array_merge([], $pos);

        foreach ($pos as $i => $p) {
            if (!isset($prev) || $prev < 0) {
                $prev = $i - 1;
            }

            if (isset($pos[$i], $pos[$prev])) {
                if ($pos[$i] - $pos[$prev] < $correct) {
                    unset($pos[$i]);
                } else {
                    $prev = $i;
                }
            }
        }
        sort($pos);
        $pos = array_merge([], $pos);

        return $pos;
    }

    private function createTable(?string $text, $pos = []): array
    {
        $cols = [];
        $rows = explode("\n", $text);

        if (!$pos) {
            $pos = $this->rowColumnPositions($rows[0]);
        }
        arsort($pos);

        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $cols[$k][] = trim(mb_substr($row, $p, null, 'UTF-8'));
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);

        foreach ($cols as &$col) {
            $col = implode("\n", $col);
        }

        return $cols;
    }

    private function rowColumnPositions(?string $row): array
    {
        $head = array_filter(array_map('trim', explode("|", preg_replace("/\s{2,}/", "|", $row))));
        $pos = [];
        $lastpos = 0;

        foreach ($head as $word) {
            $pos[] = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $lastpos = mb_strpos($row, $word, $lastpos, 'UTF-8') + mb_strlen($word, 'UTF-8');
        }

        return $pos;
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

    private function normalizeCurrency($s)
    {
        if (empty($s)) {
            return null;
        }

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
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }
        $year = date('Y', $this->date);

        $in = [
            '/^\s*(\d+)\/(\d+)\/(\d{4})\s*$/', // 28/11/2026 -> 28.11.2026
            '/^\s*(\d+)\/(\d+)\s*$/',          // 28/11      -> 28.11.$year
        ];
        $out = [
            '$1.$2.$3',
            '$1.$2.' . $year,
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        // $this->logger->debug('date replace = ' . print_r( $date, true));

        if (preg_match("/\b\d{4}\b/", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }
        // $this->logger->debug('date end = ' . print_r( $date, true));

        return $date;
    }
}
