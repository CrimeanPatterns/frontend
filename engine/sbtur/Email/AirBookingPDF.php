<?php

namespace AwardWallet\Engine\sbtur\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class AirBookingPDF extends \TAccountChecker
{
    public $mailFiles = "sbtur/it-706249938.eml, sbtur/it-718177000.eml, sbtur/it-872962215.eml, sbtur/it-885661095.eml, sbtur/it-905466638.eml, sbtur/it-906799791.eml";
    public $lang = 'pt';
    public $pdfNamePattern = ".*pdf";

    public static $dictionary = [
        "pt" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (strpos($text, "superviagem.com.br") !== false
                && (strpos($text, 'Reserva') !== false)
                && (strpos($text, 'Voos') !== false)
                && (strpos($text, 'Assentos') !== false)
            ) {
                return true;
            }

            if (preg_match("/\n {0,10}Cia +Origem *\/ *Destino +Voo/", $text)) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]superviagem\.com\.br$/', $from) > 0;
    }

    public function ParseFlightPDF(Email $email, $text)
    {
        $f = $email->add()->flight();

        $year = $this->re("/[ ]{2,}\d+\/\s*\d+\/\s*(\d{4})/", $text);

        $travellers = [];
        // Travellers from block Passageiros
        $passengersText = $this->re("/\n {0,10}Passageiros\n\s*Tipo {2,}.+((?:.+\n){1,15})\n {0,10}(?:Voos)\n/u", $text);

        if (preg_match_all("/^ {0,5}\p{Lu}[\p{Ll} ]+ ([A-Z][A-Z ]+?) +(?:F ?e ?m ?i ?n ?i ?n ?o|M ?a ?s ?c ?u ?l ?i ?n ?o)/m", $passengersText, $m)) {
            $travellers = $m[1];
            $travellers = preg_replace('/\s+/', ' ', $travellers);
        } elseif (preg_match_all("/^ {0,5}\p{Lu}[\p{Ll} ]+ ([A-Z][A-Z ]+?) +\p{Lu} ?\p{Ll}[\p{Ll} ]+/m", $passengersText, $m)) {
            $travellers = $m[1];
            $travellers = preg_replace('/\s+/', ' ', $travellers);
        }
        // $this->logger->debug('$travellers from $passengersText = '.print_r( $travellers,true));

        // block Assentos
        $seatsTextAll = $this->re("/\n {0,10}Assentos\n((?:.*\n){1,18}?)\n {0,10}(?:Serviços Auxiliares|Tarifamento|Valores)/u", $text);
        // удаляем вторую и последующие строки заголовков
        $seatsText = preg_replace('/^(.+\n)(?: {15,}.*)*\n( {0,10}\S)/u', '$1$2', $seatsTextAll);

        $seatsTable = [];

        $colsCount = null;
        $seatsRows = array_filter(explode("\n", $seatsText));
        $seatsRows = array_values($seatsRows);

        foreach ($seatsRows as $i => $row) {
            if (preg_match("/^ {0,10}[A-Z]( ?\S)+$/", $row)) {
                // part of the name on another line
                foreach ($seatsTable as $j => $v) {
                    if ($j === 0) {
                        $seatsTable[0] .= $row . "\n";
                    } else {
                        $seatsTable[$j] .= "\n";
                    }
                }

                continue;
            }

            if ($i === 0) {
                $seatsRows = $this->split("/\b([A-Z\d]{2}\d{1,4})\b/", $row, false);
            } else {
                $seatsRows = preg_split("/ {2,}/", $row, false);
            }

            if ($colsCount === null || $colsCount === count($seatsRows)) {
                $colsCount = count($seatsRows);

                foreach ($seatsRows as $j => $v) {
                    $seatsTable[$j] = $seatsTable[$j] ?? '';
                    $seatsTable[$j] .= $v . "\n";
                }
            } else {
                $seatsTable = [];

                break;
            }
        }

        $travellersOrderFromSeats = [];

        if (!empty($travellers) && !empty($seatsTable[0])
            && strpos(preg_replace("/\W/", '', $seatsTable[0]), preg_replace("/\W/", '', implode('', $travellers))) !== false
        ) {
            $travellersOrderFromSeats = $travellers;
        }

        if (empty(array_filter($seatsTable))) {
            $pos = 45;

            if (preg_match_all("/^[ ]{0,40}(ADT[\s\-]+[[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\b\s+(?:[-]+|\d+[A-Z]|[^\d\s]{1,3} ?\d+)(?:\s+|\n)/m",
                $seatsText, $m)) {
                $pos = max(array_map(function ($v) {
                    return strlen($v);
                }, $m[1]));
            } elseif (preg_match_all("/^( {0,5}(?:\S ?)+)(?: {2,}|\n)/m", $seatsText, $m)) {
                $pos = max(array_map(function ($v) {
                    return strlen($v);
                }, $m[1]));
            }

            $onlySeats = $this->splitCols($seatsText, [0, $pos])[1];
            $seatsTable = $this->splitCols($onlySeats);

            foreach ($seatsTable as $i => $col) {
                if (preg_match_all("/( +[A-Z\d]{2}\d{1,4})\b/", $this->re("/^(.+)/", $col), $m, PREG_OFFSET_CAPTURE)
                    && count($m[1]) > 1
                ) {
                    $poses = [];

                    foreach ($m[1] as $v) {
                        $poses[] = $v[1];
                    }
                    rsort($poses);
                    $seatsRows = explode("\n", $col);

                    foreach ($poses as $pos) {
                        $seatsRows = array_map(function ($v) use ($pos) {
                            return substr_replace($v, ' ', $pos, 0);
                        },
                            $seatsRows);
                    }

                    $seatsRows2 = implode("\n", $seatsRows);
                    $seatsTable2 = $this->splitCols($seatsRows2);

                    $seatsTable = array_merge(
                        array_slice($seatsTable, 0, $i),
                        $seatsTable2,
                        array_slice($seatsTable, $i + 1)
                    );
                }
            }
        }
        // $this->logger->debug('$seatsTable = '.print_r( $seatsTable,true));

        if (empty($travellers) && preg_match_all("/^( {0,5}(?:\S ?)+) {2,}[\d\-]+(?: {0,5}(?:\S ?)+ $)?/m", $seatsText, $m)) {
            $travellers = preg_replace('/\s*\n\s*/', ' ', $m[1]);
        } elseif (empty($travellers) && preg_match_all("/ADT[\s\-]+([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])/", $seatsTextAll, $m)) {
            $travellers = $m[1];
        }
        $travellers = $this->niceTraveller(array_map('trim', $travellers));

        $f->general()
            ->noConfirmation()
            ->travellers($travellers);

        if (preg_match_all("/^\s*(\d{3}\-?\s*\d{8,}.+)/m", $text, $m)) {
            $ticketText = str_replace('/', '', implode("\n", $m[1]));

            foreach ($travellers as $traveller) {
                $traveller = str_replace('/', '', $traveller);
                $ticket = $this->re("#^\s*(\d{3}\-?\s*\d{8,})(?:\s*[A-Z\d]{5,})?\s.*{$this->opt($traveller)}#um", $ticketText);

                if (!empty($ticket)) {
                    $f->addTicketNumber(str_replace(' ', '', $ticket), false, $traveller);
                }
            }
        }

        $segmetsText = $this->re("#Voos\n+\s*Cia\s*Origem / Destino.+Loc Cia\n+(.+)\n*Assentos#s", $text);
        $segmentRows = $this->split("/((?:\n\n|\n.{20,} {2,}Família:.+\n)(?:.*\n){3})/u", "\n\n\n" . $segmetsText);
        $segmentRows = array_filter(preg_replace("/^\n+/", '', $segmentRows));
        // $this->logger->debug('$segmentRows = '.print_r( $segmentRows,true));

        foreach ($segmentRows as $segmentRow) {
            //bad segments
            if (preg_match("/^\s+(?:Mochila ou bolsa|ATENÇÃO:).+(?:\n.*)?\s*$/", $segmentRow)) {
                continue;
            }
            $segmentRow = preg_replace("/^\s*(?:Mochila ou bolsa|ATENÇÃO:) +.+\n\n+/", '', $segmentRow);
            $segmentRow = preg_replace("/^(\s*\S+(?:.*\n+){3,}) *(?:Mochila ou bolsa|ATENÇÃO:) +.+\s*$/", '$1', $segmentRow);

            $s = $f->addSegment();
            $segmentTable = $this->splitCols($segmentRow, $this->rowColsPos($this->inOneRow($segmentRow)));

            if (!preg_match("/\b[A-Z]{3}\s*\-\s*.+\b\d{4}\b/s", $segmentTable[1] ?? '')
                && preg_match("/^( *[A-Z]{3} *- *.+? {3,})[A-Z]{3} *- *.+?/m", $segmentTable[0], $m)
            ) {
                $table = $this->splitCols($segmentTable[0], [0, mb_strlen($m[1])]);
                $segmentTable = array_merge($table, array_slice($segmentTable, 1));
            }

            if (count($segmentTable) > 2) {
                if (preg_match("/(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{2,4})/", $segmentTable[2], $m)) {
                    $s->airline()
                        ->name($m['aName'])
                        ->number($m['fNumber']);
                }

                $re = "/^\s*(?<code>[A-Z]{3})\s*\-\s*(?<name>[\s\S]+?)\n\s*(?<day>\d+\s*\w+)(?:\s*(?<year>\d{4}))?\s*(?<time>\d+\:\d+)(\s*\/\s*Terminal: (?<terminal>\w+))?/";

                if (preg_match($re, $segmentTable[0], $m)) {
                    if (isset($m['year']) && !empty($m['year'])) {
                        $year = $m['year'];
                    }

                    $s->departure()
                        ->code($m['code'])
                        ->name(preg_replace('/\s+/', ' ', trim($m['name'])))
                        ->terminal($m['terminal'] ?? null, true, true)
                        ->date($this->normalizeDate($m['day'] . ' ' . $year . ', ' . $m['time']));
                }

                if (preg_match($re, $segmentTable[1], $m)) {
                    if (isset($m['year']) && !empty($m['year'])) {
                        $year = $m['year'];
                    }

                    $s->arrival()
                        ->code($m['code'])
                        ->name(preg_replace('/\s+/', ' ', trim($m['name'])))
                        ->terminal($m['terminal'] ?? null, true, true)
                        ->date($this->normalizeDate($m['day'] . ' ' . $year . ', ' . $m['time']))
                    ;
                }

                $s->setConfirmation($this->re("/^\s*([A-Z\d]{5,7})\s*$/", $segmentTable[count($segmentTable) - 1])
                ?? $this->re("/^.{12,}[ ]{2,}([A-Z\d]{5,7})\s*\n/m", $segmentTable[count($segmentTable) - 1]));
            }

            foreach ($seatsTable as $seatsColumn) {
                if (!empty($s->getAirlineName()) && !empty($s->getFlightNumber())
                    && preg_match("/^\s*" . $s->getAirlineName() . $s->getFlightNumber() . "\b/", $seatsColumn, $m)
                ) {
                    if (preg_match_all("/^\s*(\d+(?: ?- ?)?[A-Z])\s*$/m", $seatsColumn, $m)) {
                        $seats = $m[1];

                        foreach ($seats as $i => $seat) {
                            $traveller = null;

                            if (!empty($travellersOrderFromSeats) && count($seats) == count($travellersOrderFromSeats)) {
                                $traveller = $travellersOrderFromSeats[$i];
                            } else {
                                $row = count(explode("\n", $this->re("/^([\s\S]*)\n *{$seat}/", $seatsColumn)));

                                if (preg_match("#^(?:.*\n){" . $row . "} *([^\d\n]+?) (?:[\d\-])#u", $seatsText, $m)) {
                                    $traveller = [];

                                    foreach ($travellers as $tr) {
                                        if (mb_stripos(preg_replace("/\W/", '', $tr),
                                                preg_replace("/\W/", '', $m[1])) === 0) {
                                            $traveller[] = $tr;
                                        }
                                    }

                                    if (count($traveller) === 1) {
                                        $traveller = $traveller[0];
                                        $traveller = $this->niceTraveller($traveller);
                                    } else {
                                        $traveller = null;
                                    }
                                }
                            }
                            $seat = preg_replace('/\W/', '', $seat);
                            $s->extra()
                                ->seat($seat, false, false, $traveller);
                        }
                    }
                }
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            $this->ParseFlightPDF($email, $text);
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s*', preg_quote($s));
        }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^\w+\,\s*(\w+)\s(\d+)\,\s*(\d{4})\,\s*([\d\:]+)$#u", //Sunday, October 16, 2022, 13:15
        ];
        $out = [
            "$2 $1 $3, $4",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }

    private function normalizeCurrency($s)
    {
        $sym = [
            '€'         => 'EUR',
            'US dollars'=> 'USD',
            '£'         => 'GBP',
            '₹'         => 'INR',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3})(?:$|\s)#", $s)) {
            return $code;
        }
        $s = $this->re("#([^\d\,\.]+)#", $s);

        foreach ($sym as $f=> $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return null;
    }

    private function splitCols($text, $pos = false, $trim = true)
    {
        $cols = [];
        $rows = explode("\n", $text);

        if (!$pos) {
            $pos = $this->rowColsPos($rows[0]);
        }
        arsort($pos);

        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $cols[$k][] = mb_substr($row, $p, null, 'UTF-8');
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);

        foreach ($cols as &$col) {
            $col = implode("\n", $col);
        }

        return $cols;
    }

    private function rowColsPos($row)
    {
        $head = array_filter(array_map('trim', explode("|", preg_replace("#\s{2,}#", "|", $row))));
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

    private function split($re, $text, $deleteFirst = true)
    {
        $r = preg_split($re, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ret = [];

        if (count($r) > 1) {
            if ($deleteFirst === true) {
                array_shift($r);
            } else {
                array_unshift($r, '');
            }

            for ($i = 0; $i < count($r) - 1; $i += 2) {
                $ret[] = $r[$i] . $r[$i + 1];
            }
        } elseif (count($r) == 1) {
            $ret[] = reset($r);
        }

        return $ret;
    }

    private function niceTraveller($travellers)
    {
        return preg_replace("/\s(?:MRS|MR|MS|MISS)$/", "", $travellers);
    }
}
