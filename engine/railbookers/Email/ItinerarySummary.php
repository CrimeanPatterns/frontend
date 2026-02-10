<?php

namespace AwardWallet\Engine\railbookers\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class ItinerarySummary extends \TAccountChecker
{
    public $mailFiles = "railbookers/it-830530620.eml, railbookers/it-830619872.eml, railbookers/it-830628883.eml, railbookers/it-894177986.eml";

    public $pdfNamePattern = ".*\.pdf";

    public $date;
    public $lang;
    public static $dictionary = [
        'en' => [
            'Your Itinerary' => ['Your Itinerary', 'Itinerary Summary'],
            'Passengers:'    => ['Passengers:', 'passengers:'],
            'Day '           => 'Day ',
        ],
    ];

    public $europeStation = [
        'Milan', 'Amsterdam Central', 'Antwerp', 'Galway', 'Zurich Airport Station', 'London St Pancras', 'Genève', 'Interlaken Ost', 'Luzern', 'Spiez',
    ];

    public $europeRegion = false;

    private $patterns = [
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]railbookers\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // if (strpos($headers["subject"], 'Railbookers') !== false) {
        //     return true;
        // }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text) == true) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text) == true) {
                $this->parsePdf($email, $text);
            }
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

    private function detectPdf($text): bool
    {
        // detect provider
        if ($this->containsText($text, ['Railbookers']) === false) {
            return false;
        }

        // detect Format
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Your Itinerary'])
                && $this->containsText($text, $dict['Your Itinerary']) === true
                && !empty($dict['Day '])
                && $this->containsText($text, $dict['Day ']) === true
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function parsePdf(Email $email, ?string $textPdf = null): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        // Travel Agency
        $email->ota()
            ->confirmation($this->re("/\n\s*{$this->opt($this->t('BOOKING NUMBER:'))} *([A-Z\d]{5,})\n/", $textPdf));

        $total = $this->re("/\n\s*{$this->opt($this->t('Total Amount'))} *(.+)\n/", $textPdf);

        if (preg_match('/^\s*(?<currency>[A-Z]{3})[ ]*\$? *(?<amount>\d[,.\'\d]*)\s*$/', $total, $m)
            || preg_match('/^\s*(?<amount>\d[,.\'\d]*) *(?<currency>[A-Z]{3})\s*$/', $total, $m)
        ) {
            $email->price()
                ->currency($m['currency'])
                ->total(PriceHelper::parse($m['amount'], $m['currency']))
            ;
        }

        $isParseTrain = $isParseTransfers = false;
        $segmentsShortText = $this->re("/\n\s*{$this->opt($this->t('Your Itinerary'))}\n([\s\S]+?)\n\s*(?:{$this->opt($this->t('Detailed Itinerary'))}|{$this->opt($this->t('Pricing'))}|{$this->opt($this->t('Destination Information'))})\n/", $textPdf);

        if (preg_match_all("/\n.+\n\s*{$this->opt($this->t('Departure:'))}(.+\n.+\n.+)/", $segmentsShortText, $m)) {
            $this->logger->debug(' > TRAIN (short)');
            $segDate = $this->re("/\n(.*\b\d{4}\b.*?)( {3,}.*)?\n *{$this->opt($this->t('Day '))} ?\d+\n/", $segmentsShortText)
                ?? $this->re("/\n *{$this->opt($this->t('Day '))} ?\d+: {3,}.*\b\d{4}\b.*?)( {3,}.*)?\n/", $segmentsShortText);

            if (!empty($segDate)) {
                $this->date = $this->normalizeDate($segDate);
            }
            $re = "/^\s*(?<dName>.+?) - (?<aName>.+?)\n\s*{$this->opt($this->t('Departure:'))}(?<dDate>.+{$this->patterns['time']})\n\s*[^\d\n]+?: *(?<aDate>.+{$this->patterns['time']})\n(?:.+ - )?(?<class>.+)/u";
            $t = $email->add()->train();
            $isParseTrain = true;

            $t->general()
                ->noConfirmation();

            if (preg_match_all("/\n *{$this->opt($this->t('Passengers:'))} *(.+)/", $segmentsShortText, $mat)) {
                $travellers = array_filter(preg_split('/\s*,\s*/', implode(', ', $mat[1])));
                $t->general()
                    ->travellers(array_unique($travellers));
            }

            foreach ($m[0] as $sText) {
                if (preg_match($re, $sText, $m)) {
                    $this->logger->debug('  > TRAIN SEGMENT (short)');
                    $s = $t->addSegment();

                    // Departure
                    $s->departure()
                        ->date($this->normalizeDate($m['dDate']))
                        ->name($m['dName']);

                    if (in_array($s->getDepName(), $this->europeStation) || $this->europeRegion == true) {
                        $s->departure()
                            ->name($s->getDepName() . ', Europe')
                            ->geoTip('europe');
                    }

                    if ($s->getDepGeoTip() === 'europe') {
                        $this->europeRegion = true;
                    }

                    // Arrival
                    $s->arrival()
                        ->date($this->normalizeDate($m['aDate']))
                        ->name($m['aName']);

                    if (in_array($s->getArrName(), $this->europeStation) || $this->europeRegion == true) {
                        $s->arrival()
                            ->name($s->getArrName() . ', Europe')
                            ->geoTip('europe');
                    }

                    if ($s->getArrGeoTip() === 'europe') {
                        $this->europeRegion = true;
                    }

                    // Extra
                    $s->extra()
                        ->noNumber()
                        ->cabin($m['class']);

                    if ($s->getDepDate() === $s->getArrDate()) {
                        $t->removeSegment($s);
                    }
                }
            }
        }

        $segmentsFullText = $this->re("/\n\s*{$this->opt($this->t('Detailed Itinerary'))}(\n[\s\S]+?)\n\s*(?:{$this->opt($this->t('Destination Information'))}|{$this->opt($this->t('Pricing'))})\n/", $textPdf);
        $segmentsFull = $this->split("/\n *({$this->opt($this->t('DAY'))} ?\d+:)/", $segmentsFullText);

        foreach ($segmentsFull as $dayText) {
            $dayDate = $this->re("/^\s*{$this->opt($this->t('DAY'))} ?\d+: *(.+)/", $dayText);
            $segs = $this->split("/\n(.+\n *(?:.*\b{$this->opt($this->t('Car'))}\b.*|{$this->opt($this->t('Check-in date:'))}.*|{$this->opt($this->t('Your room booking:'))}|.*\b{$this->opt($this->t('Tour'))}\b.*\n+ *{$this->opt($this->t('Service Phone Number:'))}|.+\n+ *{$this->opt($this->t('Departure:'))}))\n/", $dayText);

            foreach ($segs as $sText) {
                if ($isParseTrain === false && preg_match("/^(.+\n+ *(?:{$this->opt($this->t('Departure:'))}))\n/", $sText)) {
                    $this->logger->debug(' > TRAIN');
                    unset($t);

                    foreach ($email->getItineraries() as $it) {
                        /** @var \AwardWallet\Schema\Parser\Common\Train $it */
                        if ($it->getType() === 'train') {
                            $t = $it;
                        }
                    }

                    if (empty($t)) {
                        $t = $email->add()->train();

                        $t->general()
                            ->noConfirmation();

                        if (preg_match("/\n *{$this->opt($this->t('Passengers:'))}\s+(.+)/", $sText, $mat)) {
                            $t->general()
                                ->travellers(preg_split('/\s*,\s*/', $mat[1]));
                        }
                    }

                    $s = $t->addSegment();

                    $date = $this->re("/{$this->opt($this->t('Departure:'))}\s*(.+)\n/", $sText);

                    if (preg_match("/^\s*\d+:\d+\D{0,5}$/", $date)) {
                        $date = $dayDate . ', ' . $date;
                    }
                    $s->departure()
                        ->date($this->normalizeDate($date));

                    if (preg_match("/^\s*(.+?) *- *(.+)\n\s*(.+)/", $sText, $m)) {
                        $s->departure()
                            ->name($m[1]);

                        if (in_array($s->getDepName(), $this->europeStation)) {
                            $s->departure()
                                ->geoTip('europe');
                        }
                        $s->arrival()
                            ->name($m[2]);

                        if (in_array($s->getArrName(), $this->europeStation)) {
                            $s->arrival()
                                ->geoTip('europe');
                        }

                        $s->extra()
                            ->noNumber()
                            ->cabin($m[3]);
                    }

                    $date = $this->re("/{$this->opt($this->t('Arrival:'))}\s*(.+)\n/", $sText);

                    if (preg_match("/^\s*\d+:\d+\D{0,5}$/", $date)) {
                        $date = $dayDate . ', ' . $date;
                    }
                    $s->arrival()
                        ->date($this->normalizeDate($date));
                }

                if (preg_match("/^(.+\n *(?:{$this->opt($this->t('Car'))}))\n/", $sText)) {
                    $this->logger->debug(' > TRANSFER');
                    unset($t);
                    $conf = $this->re("/{$this->opt($this->t('Booking reference:'))}\s+([A-Z\d\-]{5,})\n/", $sText);

                    foreach ($email->getItineraries() as $it) {
                        /** @var \AwardWallet\Schema\Parser\Common\Transfer $it */
                        if ($it->getType() === 'transfer' && in_array($conf, array_column($it->getConfirmationNumbers(), 0))) {
                            $t = $it;
                        }
                    }

                    if (empty($t)) {
                        $t = $email->add()->transfer();
                        $isParseTransfers = true;

                        $t->general()
                            ->confirmation($conf);

                        if (preg_match("/\n *{$this->opt($this->t('Passengers:'))}\s+(.+)/", $sText, $mat)) {
                            $t->general()
                                ->travellers(preg_split('/\s*,\s*/', $mat[1]));
                        }
                    }

                    $s = $t->addSegment();

                    $s->departure()
                        ->date($this->normalizeDate($this->re("/{$this->opt($this->t('Time From:'))}\s+(.+)\n/", $sText)))
                    ;
                    $address = preg_replace('/\s+/', ' ', $this->re("/{$this->opt($this->t('Pick Up Location:'))}\s+([\s\S]+?)\n\s*{$this->opt($this->t('Drop Off Location:'))}/", $sText));

                    if (preg_match("/^\s*([A-Z]{3})\s*-\s*[A-Z\d]{2} ?\d{1,4} {$this->opt($this->t('from'))} [A-Z]{3}\s*-\s*/", $address, $m)) {
                        $s->departure()
                            ->code($m[1]);
                    } elseif (preg_match("/^\s*(.+)\s*-\s*{$this->opt($this->t('Train Schedule:'))}/", $address, $m)) {
                        $s->departure()
                            ->name($m[1]);

                        if (in_array($s->getDepName(), $this->europeStation)) {
                            $s->departure()
                                ->geoTip('europe');
                        }
                    } elseif (preg_match("/{$this->opt($this->t('Hotel Address:'))}\s*(.+)/", $address, $m)) {
                        $s->departure()
                            ->address($m[1]);
                    } else {
                        $s->departure()
                            ->address($address);
                    }
                    $s->arrival()
                        ->noDate()
                    ;
                    $address = preg_replace('/\s+/', ' ', $this->re("/{$this->opt($this->t('Drop Off Location:'))}\s+([\s\S]+?)\n\s*{$this->opt($this->t('Booking reference:'))}/", $sText));

                    if (preg_match("/^\s*([A-Z]{3})\s*-\s*[A-Z\d]{2} ?\d{1,4} {$this->opt($this->t('to'))} [A-Z]{3}\s*-\s*/", $address, $m)) {
                        $s->arrival()
                            ->code($m[1]);
                    } elseif (preg_match("/^\s*(.+)\s*-\s*{$this->opt($this->t('Train Schedule:'))}/", $address, $m)) {
                        $s->arrival()
                            ->name($m[1]);

                        if (in_array($s->getArrName(), $this->europeStation)) {
                            $s->arrival()
                                ->geoTip('europe');
                        }
                    } elseif (preg_match("/{$this->opt($this->t('Hotel Address:'))}\s*(.+)/", $address, $m)) {
                        $s->arrival()
                            ->address($m[1]);
                    } else {
                        $s->arrival()
                            ->address($address);
                    }
                }

                if (preg_match("/^(.+)\n+ *{$this->opt($this->t('Check-in date:'))}/", $sText, $m)) {
                    $this->logger->debug(' > HOTEL');
                    unset($h);
                    $name = trim($m[1]);
                    $checkIn = $this->normalizeDate($this->re("/{$this->opt($this->t('Check-in date:'))}\s*(.+)\n/", $sText));
                    $checkOut = $this->normalizeDate($this->re("/{$this->opt($this->t('Check-out date:'))}\s*(.+)\n/", $sText));

                    foreach ($email->getItineraries() as $it) {
                        /** @var \AwardWallet\Schema\Parser\Common\Hotel $it */
                        if ($it->getType() === 'hotel' && $it->getHotelName() === $name && $it->getCheckInDate() === $checkIn && $it->getCheckOutDate() === $checkOut) {
                            $h = $it;
                        }
                    }

                    if (empty($h)) {
                        $h = $email->add()->hotel();

                        $h->hotel()
                            ->name($name)
                            ->address(preg_replace('/\s*\n\s*/', ' ', $this->re("/{$this->opt($this->t('Address:'))}\s+((?:.+\n){1,3})\n/", $sText)))
                            ->phone($this->re("/{$this->opt($this->t('Service Phone Number:'))}\s+(.+)\n/", $sText), true, true)
                        ;

                        $h->booked()
                            ->checkIn($checkIn)
                            ->checkOut($checkOut);
                    }

                    if (preg_match("/\n *{$this->opt($this->t('Passengers:'))}\s+(.+)/", $sText, $mat)) {
                        $travellers = array_filter(array_diff(preg_split('/\s*,\s*/', $mat[1]), array_column($h->getTravellers(), 0)));

                        if (!empty($travellers)) {
                            $h->general()
                                ->travellers($travellers, true);
                        }
                    }
                    $conf = $this->re("/{$this->opt($this->t('Booking reference:'))}\s+([A-Z\d\-]{5,})\n/", $sText);

                    if (!empty($conf) && !in_array($conf, array_column($h->getConfirmationNumbers(), 0))) {
                        $h->general()
                            ->confirmation($conf);
                    }

                    $roomType = $this->re("/{$this->opt($this->t('Your room booking:'))}\s*\n *\d+ ?x ?(.+)\n/", $sText);
                    $h->addRoom()
                        ->setType($roomType)
                        ->setConfirmation($conf, true, true);
                }
            }

            foreach ($email->getItineraries() as $it) {
                if ($it->getType() === 'hotel' && empty($h->getConfirmationNumbers())) {
                    $it->general()
                        ->noConfirmation();
                }
            }
        }

        // it-894177986.eml
        if (!$isParseTransfers && preg_match_all("/\n[ ]*(?:Private )?Transfer from[ ]+(\S.+(?:\n.+){0,2}\n+[ ]*{$this->opt($this->t('Time From:'))}.*\n.*)/", $segmentsShortText, $transferMatches)) {
            foreach ($transferMatches[1] as $sText) {
                $this->logger->debug(' > TRANSFER (short)');
                $t = $email->add()->transfer();
                $t->general()->noConfirmation();
                $s = $t->addSegment();

                if (preg_match("/^(?<dName>.+?\S)[ ]+to[ ]+(?<aName>\S.+)\n/", $sText, $m)) {
                    $s->departure()->name($m['dName']);
                    $s->arrival()->name($m['aName']);
                }

                if (preg_match("/\n[ ]*{$this->opt($this->t('Time From:'))}\s*(?<dDate>.{3,}{$this->patterns['time']})\n+[ ]*{$this->opt($this->t('Passengers:'))}/", $sText, $m)) {
                    $s->departure()->date($this->normalizeDate($m['dDate']));
                    $s->arrival()->noDate();
                }

                if (preg_match("/\n[ ]*{$this->opt($this->t('Passengers:'))}\s*(\S.+)/", $sText, $m)) {
                    $t->general()->travellers(preg_split('/(?:\s*,\s*)+/', $m[1]));
                }
            }
        }
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function containsText($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (mb_strpos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && mb_strpos($text, $needle) !== false) {
            return true;
        }

        return false;
    }

    // additional methods

    private function normalizeDate(?string $date)
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        $year = date("Y", $this->date);
        $in = [
            // ,11 Jan, 2025 - 14:44
            "/^[,\s]*(\d{1,2})[,.\s]*([[:alpha:]]+)[,.\s]*(\d{4})\s*-\s*({$this->patterns['time']})\s*$/iu",
            // 19 May, 2025
            '/^[,\s]*(\d{1,2})[,.\s]*([[:alpha:]]+)[,.\s]*(\d{4})\s*$/iu',
            // Tue, 01 Apr 11:01
            "/^[,\s]*([-[:alpha:]]+)[,.\s]*(\d{1,2})[,.\s]*([[:alpha:]]+)\s*[-+\s]+\s*({$this->patterns['time']})\s*$/iu",
        ];
        $out = [
            '$1 $2 $3, $4',
            '$1 $2 $3',
            '$1, $2 $3 ' . $year . ', $4',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        // $this->logger->debug('date end = ' . print_r( $date, true));

        if (preg_match('/\b20\d{2}\b/', $date)) {
            return strtotime($date);
        } elseif (preg_match("/^(?<week>\w+), (?<date>\d+ \w+ .+)/u", $date, $m) && !empty($this->date)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));

            return EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
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
}
