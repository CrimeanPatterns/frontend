<?php

namespace AwardWallet\Engine\qmiles\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BoardingPassPdf extends \TAccountChecker
{
    public $mailFiles = "qmiles/it-10046188.eml, qmiles/it-1895230.eml, qmiles/it-1895232.eml, qmiles/it-1898223.eml, qmiles/it-1904879.eml, qmiles/it-30464474.eml, qmiles/it-6804082.eml, qmiles/it-892382177.eml, qmiles/it-892777711.eml, qmiles/it-894600459.eml, qmiles/it-9940145.eml";

    public $reFrom = "qrwebcheckin@qatarairways.com";
    public $reSubject = [
        // en
        "Boarding pass for booking ref",
        'Travel Pass for booking ref.',
    ];
    public $emailSubject;
    public $pdfPattern = ".*(?:Boarding|Confirmation) pass for .*.pdf";

    public static $dictionary = [
        "en" => [
            'documentName' => ['BOARDING PASS', 'This is your confirmation pass.'],
        ],
    ];

    public $lang = "en";

    public function parsePdf(Email $email, $text)
    {
        $f = $email->add()->flight();

        // General
        if (preg_match("/for booking ref\. ([A-Z\d]{5,7}) for/", $this->emailSubject, $m)
            || preg_match("/予約コード： ([A-Z\d]{5,7})\s*$/u", $this->emailSubject, $m)
        ) {
            $f->general()
                ->confirmation($m[1]);
        }
        preg_match_all("#\n *(\S.+?)(?: {5,}.*)?\n+ *Flight {2,}Class Of Travel#", $text, $p);
        $f->general()
            ->travellers(array_unique($this->niceTravellers($p[1])));

        // Segments
        $segments = $this->split("/\n( *\S.+\n+ *Flight {2,}Class Of Travel(?:.*\n){2,10}.* Departure From)/", $text);
        // $this->logger->debug('$segments = '.print_r( $segments,true));

        foreach ($segments as $stext) {
            $s = $f->addSegment();

            $traveller = $this->niceTravellers($this->re("/^ *(\S.+?)(?: {5,}|\n)/", $stext));

            $stext = preg_replace("/^ *(\S.+)\n/", '', $stext);
            $stext = preg_replace("#\n *Free checked[\s\S]+#", '', $stext);
            $stext = preg_replace("#\n *Seq No\.[\s\S]+#", '', $stext);

            $tableText = $this->re("/^([\s\S]+?)\n *Departure From/", $stext);
            $tableRow1 = $this->createTable($tableText, $this->rowColumnPositions($this->inOneRow($tableText)));

            $tableText = $this->re("/\n( *Departure From[\s\S]+?)\n *(E-Ticket No:|Booking Reference)/", $stext);
            $tableRoute = $this->createTable($tableText, $this->rowColumnPositions($this->inOneRow($tableText)));

            if (count($tableRoute) !== 4) {
                $values = preg_split('/\s{2,}/', trim($tableText));

                if (count($values) == 8) {
                    $tableRoute = [$values[0] . "\n" . $values[4], $values[1] . "\n" . $values[5], $values[2] . "\n" . $values[6], $values[3] . "\n" . $values[7]];
                } elseif (count($values) < 8) {
                    $values = preg_split('/\s{2,}/', trim(preg_replace('/( Airport)(\w)/', '$1  $2', $tableText)));

                    if (count($values) == 8) {
                        $tableRoute = [$values[0] . "\n" . $values[4], $values[1] . "\n" . $values[5], $values[2] . "\n" . $values[6], $values[3] . "\n" . $values[7]];
                    }
                }
            }

            $tableText = $this->re("/\n( *(?:E-Ticket No:|Booking Reference)[\s\S]+)/", $stext);
            $additionalInfo = implode("\n###\n", $this->createTable($tableText, $this->rowColumnPositions($this->inOneRow($tableText))));

            // Airline
            if (preg_match("/^\s*Flight\n\s*(?<al>[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(?<fn>\d{1,4})\s*$/", $tableRow1[0], $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fn']);
            }

            if (preg_match("/Booking Reference\n\s*([A-Z\d]{5,7})(?:###|\n|$)/", $additionalInfo, $m)) {
                if (empty($f->getConfirmationNumbers())) {
                    $f->general()
                        ->noConfirmation();
                } elseif (!in_array($m[1], array_column($f->getConfirmationNumbers(), 0))) {
                    $s->airline()
                        ->confirmation($m[1]);
                }
            }

            // Departure
            if (preg_match("/^\s*Departure From\s+(\S[^,]+? \(([A-Z]{3})\),.+)/s", $tableRoute[0], $m)) {
                $s->departure()
                    ->code($m[2])
                    ->name(preg_replace('/\s+/', ' ', $m[1]));
            } elseif (preg_match("/^\s*Departure From\s+(\S.+)/s", $tableRoute[0], $m)) {
                $s->departure()
                    ->noCode()
                    ->name(preg_replace('/\s+/', ' ', $m[1]));
            } elseif (preg_match("/^\s*Departure From\s*$/", $tableRoute[0], $m)) {
                $s->departure()
                    ->noCode();
            }

            if (preg_match("/^\s*Departure Date\s+(\w+\W{1,3}\w+\W{1,3}\w+)\s*$/", $tableRoute[2], $mD)
                && preg_match("/^\s*Departure Time\s+(\d+:\d+)\s*$/", $tableRoute[3], $mT)
            ) {
                $s->departure()
                    ->date(strtotime($this->normalizeDate($mD[1] . ', ' . $mT[1])));
                $s->arrival()
                    ->noDate();
            }

            // Arrival
            if (preg_match("/^\s*Destination\s+(\S[^,]+? \(([A-Z]{3})\),.+)/s", $tableRoute[1], $m)) {
                $s->arrival()
                    ->code($m[2])
                    ->name(preg_replace('/\s+/', ' ', $m[1]));
            } elseif (preg_match("/^\s*Destination\s+(\S.+)/s", $tableRoute[1], $m)) {
                $s->arrival()
                    ->noCode()
                    ->name(preg_replace('/\s+/', ' ', $m[1]));
            } elseif (preg_match("/^\s*Destination\s*$/", $tableRoute[1], $m)) {
                $s->arrival()
                    ->noCode();
            }
            // Extra
            $s->extra()
                ->status($this->re("/(?:^|\n)\s*Status\s+([^#]+?)\s*(?:###|\n|$)/", $additionalInfo), true, true)
                ->cabin($this->re("/^\s*Class Of Travel\s+(.+)/", $tableRow1[1]))
                ->seat($seat = $this->re("/^\s*Seat\s+(\d{1,3}[A-Z]+)(?:\n|$)/", $tableRow1[2]), true, true, $seat ? $traveller : null)
            ;

            if (($ticket = $this->re("/(?:^|\n)\s*(?:ETicket No|E-Ticket No:)\s+(\d+)\s*(?:###|\n|$)/", $additionalInfo))
                && (!in_array($ticket, array_column($f->getTicketNumbers(), 0)))
            ) {
                $f->issued()
                    ->ticket($ticket, false, $traveller);
            }

            if (($number = $this->re("/(?:^|\n)\s*Frequent Flyer\s+([A-Z\d\-\/]+)\s*(?:###|\n|$)/", $additionalInfo))
                && (!in_array($number, array_column($f->getAccountNumbers(), 0)))
            ) {
                $f->program()
                    ->account($number, false, $traveller);
            }

            foreach ($f->getSegments() as $key => $seg) {
                if ($seg->getId() !== $s->getId()) {
                    if (serialize(array_diff_key($seg->toArray(),
                            ['seats' => [], 'assignedSeats' => []])) === serialize(array_diff_key($s->toArray(), ['seats' => [], 'assignedSeats' => []]))) {
                        if (!empty($s->getAssignedSeats())) {
                            foreach ($s->getAssignedSeats() as $seat) {
                                $seg->extra()
                                    ->seat($seat[0], false, false, $seat[1]);
                            }
                        } elseif (!empty($s->getSeats())) {
                            $seg->extra()->seats(array_unique(array_merge($seg->getSeats(),
                                $s->getSeats())));
                        }
                        $f->removeSegment($s);

                        break;
                    }
                }
            }
        }
    }

    public function detectEmailFromProvider($from)
    {
        return strpos($from, $this->reFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (strpos($headers["from"], $this->reFrom) === false) {
            return false;
        }

        foreach ($this->reSubject as $re) {
            if (stripos($headers["subject"], $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfPattern);

        foreach ($pdfs as $pdf) {
            if (($text = \PDF::convertToText($parser->getAttachmentBody($pdf))) === null) {
                return null;
            }

            if ($this->detectPdf($text, $parser) === true) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf($text, PlancakeEmailParser $parser)
    {
        if ($this->containsText($text, ['Qatar Airways', 'at Qatar\n']) === false
            && $this->http->XPath->query("//text()[normalize-space() = 'Qatar Airways']")->length === 0
            && stripos($parser->getCleanFrom(), '@qatarairways.com') === false
        ) {
            return false;
        }

        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['documentName'])
                && $this->containsText($text, $dict['documentName']) === true
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->emailSubject = $parser->getSubject();

        $pdfs = $parser->searchAttachmentByName($this->pdfPattern);

        foreach ($pdfs as $pdf) {
            if (($text = \PDF::convertToText($parser->getAttachmentBody($pdf))) === null) {
                return null;
            }

            if ($this->detectPdf($text, $parser) === true) {
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

    public function niceTravellers($name)
    {
        return preg_replace("/^\s*(Mr|Ms|Mstr|Miss|Mrs|Dr)\s+/", '', $name);
    }

    private function t($word)
    {
        // $this->http->log($word);
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function normalizeDate($str)
    {
        // $this->logger->debug('$str = '.print_r( $str,true));
        $in = [
            //26 Nov 2017, 09:45
            "/^\s*(\d{1,2})\s+([[:alpha:]]+)\s+(\d{4})\s*,\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/",
            // Dec 03 2024, 19:30
            "/^\s*([[:alpha:]]+)\s+(\d{1,2})\s+(\d{4})\s*,\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/",
        ];
        $out = [
            "$1 $2 $3, $4",
            "$2 $1 $3, $4",
        ];
        $str = preg_replace($in, $out, $str);
        // $this->logger->debug('$str 2 = '.print_r( $str,true));

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return $str;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", $field) . ')';
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

    private function containsText($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (stripos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && stripos($text, $needle) !== false) {
            return true;
        }

        return false;
    }
}
