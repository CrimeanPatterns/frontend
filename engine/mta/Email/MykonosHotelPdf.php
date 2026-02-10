<?php

namespace AwardWallet\Engine\mta\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class MykonosHotelPdf extends \TAccountChecker
{
    public $mailFiles = "mta/it-916364904.eml";
    public $pdfNamePattern = ".*\.pdf";

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
        ],
    ];

    private $detectBody = [
        'en' => [
            'ROOM TYPE:', 'PRE-PAID:', 'BOOKING AGENT:', 'AGENT CONTACT:', 'CONDITIONS:',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]mtatravel\.com\.au$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
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

    public function detectPdf(string $text)
    {
        if (empty($text)) {
            return false;
        }

        // detect Provider
        if (stripos($text, '@mtatravel.com.au') === false) {
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
                $this->parseHotelPdf($email, $text);
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

    private function parseHotelPdf(Email $email, ?string $textPdf = null)
    {
        $h = $email->add()->hotel();

        $phonePattern = '[+(\d][-+. \d)(]{5,}[\d)]'; // +377 (93) 15 48 52    |    (+351) 21 342 09 07    |    713.680.2992

        // create table
        $reservationText = $this->re("/^(.+?{$this->opt($this->t('This is a prepaid service'))})/s", $textPdf);
        $this->logger->info($reservationText);
        $pos = $this->columnPositions($reservationText, 20);
        $table = $this->createTable($reservationText, $pos);

        $this->logger->info(var_export($table, true));

        if (count($table) !== 2) {
            $this->logger->debug("incorrect count of columns");

            return false;
        }

        $leftCol = $table[0];
        $rightCol = $table[1];

        // collect ota reservation confirmation
        if (preg_match("/(?<otaDesc>{$this->opt($this->t('ORDER NUMBER'))})[\:\# ]+(?<otaConf>\w{15,20})\b/", $leftCol, $m)) {
            $h->ota()
                ->confirmation($m['otaConf'], $m['otaDesc']);
        }

        // collect reservation confirmation
        if (preg_match("/(?<confDesc>{$this->opt($this->t('SUPPLIER REF'))})[\: ]+(?<confNumber>\d+)[ ]*$/m", $leftCol, $m)) {
            $h->general()
                ->confirmation($m['confNumber'], $m['confDesc']);
        }

        // collect traveller
        $traveller = $this->re("/^[ ]*{$this->opt($this->t('LEAD TRAVELLER'))}[\: ]+(?:{$this->opt(['Miss', 'Mrs', 'Mr', 'Ms'])}\.?\s*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])[ ]*$/m", $leftCol);

        if (!empty($traveller)) {
            $h->addTraveller($traveller);
        }

        // collect hotel name
        $hotelName = $this->re("/^[ ]*(.+?)\s+{$this->opt($this->t('ORDER NUMBER'))}/m", $leftCol);
        $h->setHotelName($hotelName);

        // collect address
        $address = $this->re("/^[ ]*{$this->opt($this->t('HOTEL ADDRESS'))}[\: ]+(.+?)[ ]*$/m", $rightCol);
        $h->setAddress($address . ', ' . $hotelName);

        // collect check-in and check-out
        $checkIn = $this->re("/^[ ]*{$this->opt($this->t('CHECK IN'))}[\: ]+(.+?)[ ]*$/m", $leftCol);
        $checkOut = $this->re("/^[ ]*{$this->opt($this->t('CHECK OUT'))}[\: ]+(.+?)[ ]*$/m", $leftCol);

        $h->booked()
            ->checkIn($this->normalizeDate($checkIn))
            ->checkOut($this->normalizeDate($checkOut));

        // collect room type
        $roomType = preg_replace("/\s+/", ' ', $this->re("/{$this->opt($this->t('ROOM TYPE'))}[\: ]+(.+?)[ ]*(?:\n{2,}|{$this->opt($this->t('NO. OF ROOMS'))})/s", $leftCol));

        if (!empty($roomType)) {
            $r = $h->addRoom();
            $r->setType($roomType);
        }

        // collect rooms count
        $roomsCount = $this->re("/^[ ]*{$this->opt($this->t('NO. OF ROOMS'))}[\: ]+(\d+)[ ]*$/m", $leftCol);
        $h->setRoomsCount($roomsCount, true, true);

        // collect adults count
        $guestCount = $this->re("/^[ ]*{$this->opt($this->t('NO.OF PEOPLE'))}[\: ]+{$this->opt($this->t('Adults'))}[\: ]+(\d+)[ ]*$/m", $leftCol);
        $h->setGuestCount($guestCount, true, true);

        // collect cancellation
        $cancellation = preg_replace(["/\s+/", "/\-\s+/"], [' ', ''], $this->re("/{$this->opt($this->t('CANCELLATION POLICY'))}[\: ]+(.+?)\s*$/s", $rightCol));
        $h->setCancellation($cancellation, true, true);

        // check NonRefundable
        $this->detectDeadLine($h);

        // collect ota phone
        if (preg_match("/^[ ]*(?<desc>{$this->opt($this->t('AGENT CONTACT'))})[\: ]+(?<phone>{$phonePattern})[ ]*$/m", $rightCol, $m)) {
            $h->ota()
                ->phone($m['phone'], $m['desc']);
        }

        return $email;
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

    private function normalizeDate(?string $date): ?int
    {
        $this->logger->debug('date begin = ' . print_r($date, true));

        if (empty($date)) {
            return null;
        }

        $in = [
            '/^\s*([^\d\s]+)\s+(\d+)\-([^\d\s]+)\-(\d{4})\s*$/', // Saturday 21-Jun-2025 -> Saturday, 21 Jun 2025
        ];
        $out = [
            '$1, $2 $3 $4',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        $this->logger->debug('date replace = ' . print_r($date, true));

        if (preg_match("/\b\d{4}\b/", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }
        $this->logger->debug('date end = ' . print_r($date, true));

        return $date;
    }

    private function detectDeadLine(\AwardWallet\Schema\Parser\Common\Hotel $h): void
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        if (preg_match("/This booking is Non.?Refundable/i", $cancellationText)) {
            $h->setNonRefundable(true);
        }
    }
}
