<?php

namespace AwardWallet\Engine\hengine\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class ItineraryDetails extends \TAccountChecker
{
    public $mailFiles = "hengine/it-892594438.eml";

    public $pdfNamePattern = ".*\.pdf";

    public $lang;
    public static $dictionary = [
        'en' => [
            'Itinerary details'  => 'Itinerary details',
            'Summary of charges' => 'Summary of charges',
            'Engine #:'          => ['Engine #:', 'Hotel Confirmation #:'],
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]hotelengine\.com$/", $from) > 0;
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

            if ($this->detectPdf($text) == true) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf($text)
    {
        // detect provider
        if ($this->containsText($text, ['your Engine itinerary', 'Engine #:', 'inclusive of Engine overhead']) === false) {
            return false;
        }

        // detect Format
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Itinerary details'])
                && $this->containsText($text, $dict['Itinerary details']) === true
                && !empty($dict['Summary of charges'])
                && $this->containsText($text, $dict['Summary of charges']) === true
            ) {
                $this->lang = $lang;

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
                $this->parseEmailPdf($email, $text);
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

    private function parseEmailPdf(Email $email, ?string $textPdf = null)
    {
        $h = $email->add()->hotel();

        // General
        $confs = preg_split('/\s*,\s*/', $this->re("/\n *{$this->opt($this->t('Engine #:'))} *([\dA-Z\- ,]+)\n/", $textPdf));

        foreach ($confs as $conf) {
            $h->general()
                ->confirmation($conf);
        }
        $h->general()
            ->travellers($this->res("/\n *{$this->opt($this->t('Primary guest'))} {2,}(\S.+?)\n/", $textPdf))
        ;

        // Hotel
        $hotelsInfo = $this->re("/\n *{$this->opt($this->t('Hotel information'))}\n\s*([\s\S]+?)\s*\n *{$this->opt($this->t('Trip details'))}\n/", $textPdf);

        if (preg_match("/^(?<name>.+)\n(?<address>.+)\n(?<phone>[\d\W]{5,})\s*$/", $hotelsInfo, $m)) {
            $h->hotel()
                ->name($m['name'])
                ->address($m['address'])
                ->phone($m['phone'])
            ;
        }

        // Booked
        $tableText = $this->re("/\n *{$this->opt($this->t('Trip details'))}\n([\s\S]+?)\n {0,10}{$this->opt($this->t('Rooms'))} +\d/", $textPdf);
        $tableInfo = $this->createTable($tableText, $this->rowColumnPositions($this->inOneRow($tableText)));

        if (count($tableInfo) === 3) {
            $h->booked()
                ->checkIn($this->normalizeDate($this->re("/^\s*.+\n\s*([\s\S]+)/", $tableInfo[0])))
                ->checkOut($this->normalizeDate($this->re("/^\s*.+\n\s*([\s\S]+)/", $tableInfo[2])));
        }
        $h->booked()
            ->rooms($this->re("/\n *{$this->opt($this->t('Rooms'))} *(\d+)\n/", $textPdf))
            ->guests($this->re("/\n *{$this->opt($this->t('Guests'))} *(\d+)\n/", $textPdf))
        ;

        // Rooms
        $types = $this->res("/\n *{$this->opt($this->t('Room type'))} {2,}(\S.+?)\n/", $textPdf);

        foreach ($types as $type) {
            $room = $h->addRoom();

            $room->setType($type);
        }

        $totalText = $this->re("/\n\s*{$this->opt($this->t('Summary of charges'))}\n[\s\S]*\n *{$this->opt($this->t('Total charges'))} {2,}(.+)/", $textPdf);

        if ((preg_match("/^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$/", $totalText, $m)
            || preg_match("/^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$/", $totalText, $m))
        ) {
            $h->price()
                ->total(PriceHelper::parse($m['amount'], $m['currency']))
                ->currency($m['currency'])
            ;
        } else {
            $h->price()
                ->total(null);
        }
        $costText = $this->re("/\n\s*{$this->opt($this->t('Summary of charges'))}(?:\n[\s\S]*)?\n *\d+ ?{$this->opt($this->t('Room'))}.*, *\d+ ?{$this->opt($this->t('Night'))}.* {2,}(.+)/", $textPdf);

        if ((preg_match("/^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$/", $costText, $m)
            || preg_match("/^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$/", $costText, $m))
        ) {
            $h->price()
                ->cost(PriceHelper::parse($m['amount'], $m['currency']));
        }
        $taxText = $this->re("/\n\s*{$this->opt($this->t('Summary of charges'))}\n[\s\S]*\n *{$this->opt($this->t('Taxes and fees'))}[\*]? {2,}(.+)/", $textPdf);

        if ((preg_match("/^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$/", $taxText, $m)
            || preg_match("/^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$/", $taxText, $m))
        ) {
            $h->price()
                ->tax(PriceHelper::parse($m['amount'], $m['currency']));
        }

        return $email;
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

    private function res($re, $str, $c = 1)
    {
        preg_match_all($re, $str, $m);

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

    private function inOneRow($text)
    {
        $textRows = array_filter(explode("\n", $text));

        if (empty($textRows)) {
            return '';
        }
        $pos = [];
        $length = [];

        foreach ($textRows as $key => $row) {
            $length[] = mb_strlen($row);
        }
        $length = max($length);
        $oneRow = '';

        for ($l = 0; $l < $length; $l++) {
            $notspace = false;

            foreach ($textRows as $key => $row) {
                $sym = mb_substr($row, $l, 1);

                if ($sym !== false && trim($sym) !== '') {
                    $notspace = true;
                    $oneRow[$l] = 'a';
                }
            }

            if ($notspace == false) {
                $oneRow[$l] = ' ';
            }
        }

        return $oneRow;
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

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));

        $in = [
            // Saturday, 29 Mar, 2025     11:00 AM CDT
            '/^\s*[[:alpha:]]+,\s*(\d{1,2})\s+([[:alpha:]]+)\s*,\s*(\d{4})\s+(\d{1,2}:\d{2}(?:\s*[ap]m)?)(?:\s*[A-Z]{3,4})?\s*$/iu',
        ];
        $out = [
            '$1 $2 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        // $this->logger->debug('date replace = ' . print_r( $date, true));

        // $this->logger->debug('date end = ' . print_r( $date, true));

        return strtotime($date);
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
