<?php

namespace AwardWallet\Engine\vivaaerobus\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BoardingPassPdf extends \TAccountChecker
{
    public $mailFiles = "vivaaerobus/it-865455047.eml, vivaaerobus/it-879720292.eml, vivaaerobus/it-881551810.eml, vivaaerobus/it-894145686.eml, vivaaerobus/it-897046891.eml";

    public $subjects = [
        'Tu pase de abordar a ',
    ];

    public $detectLang = [
        'es' => ['Salida'],
        'en' => ['Departs'],
    ];

    public $pdfNamePattern = "[A-Z\d]{5,7}\_[A-Z]{6}\.pdf";

    public $passNamePattern = "[A-Z\d]{5,7}\_[A-Z]{6}\.pdf";

    public $lang = '';

    public static $dictionary = [
        'es' => [
            'Boarding'           => 'Abordaje',
            'Reservation'        => 'Reserva',
            'Passenger'          => 'Pasajero',
            'segPhrase'          => 'Aviso legal: La reproducción parcial o total no autorizada del pase de abordar constituye un',
            'Airport'            => 'Aeropuerto',
            'Baggage guidelines' => 'Lineamientos de',
            'Seat'               => 'Asiento',
            'Gate'               => 'Puerta',
            'Window'             => ['Ventana', 'En medio', 'Pasillo'],
            'Zone'               => 'Zona',
        ],
        'en' => [
            'Boarding'    => 'Boarding',
            'Reservation' => 'Reservation',
            'Passenger'   => 'Passenger',
            'Window'      => ['Window', 'Aisle', 'Middle'],
            'segPhrase'   => 'Legal notice: The unauthorized reproduction, either part or all of this boarding pass, constitutes',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'vivaaerobus.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectProvider($from)
    {
        if (stripos($from, 'vivaaerobus.com') !== false
        ) {
            return true;
        }

        if ($this->http->XPath->query("//*[{$this->contains(['VivaAerobus', 'Viva'])}]")->length > 0
            || $this->http->XPath->query("//img/src[{$this->contains(['vivaaerobus.com'])}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            $this->assignLang($text);

            if ($this->detectProvider($parser->getHeader('from')) !== true) {
                continue;
            }

            foreach (self::$dictionary as $dict) {
                if (!empty($dict['Boarding']) && strpos($text, $this->t('Boarding')) !== false
                    && !empty($dict['Reservation']) && strpos($text, $this->t('Reservation')) !== false
                    && !empty($dict['Passenger']) && strpos($text, $this->t('Passenger')) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]vivaaerobus\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        $passes = $parser->searchAttachmentByName($this->passNamePattern);

        $pdfNames = '';

        foreach ($passes as $pdf) {
            $pdfNames .= "\n" . $this->getAttachmentName($parser, $pdf);
        }

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            $this->assignLang($text);

            $this->BoardingPass($email, $text, $pdfNames);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function BoardingPass(Email $email, $text, $pdfNames)
    {
        $f = $email->add()->flight();

        $f->general()->noConfirmation();

        preg_match_all("/{$this->opt($this->t('segPhrase'))}\n+[ ]+\w+\n+([ ]+.+?)(?:\n+[ ]+{$this->opt($this->t('Airport'))}\n+[ ]+{$this->opt($this->t('Baggage guidelines'))}|\n*[ ]*$)/s", $text, $segments);

        $travellersArray = [];

        foreach ($segments[1] as $segment) {
            $segment = preg_replace("/[ ]{90,100}/", '', $segment);

            unset($traveller);

            $s = $f->addSegment();

            $s->airline()
                ->confirmation($this->re('/^[ ]*([A-Z\d]{5,7})[\n ]*/u', $segment))
                ->number($airNum = $this->re('/(?:[A-Z\d][A-Z]|[A-Z][A-Z\d])[ ]*([0-9]{1,5})[\n ]*$/u', $segment))
                ->name($airName = $this->re('/([A-Z\d][A-Z]|[A-Z][A-Z\d])[ ]*[0-9]{1,5}[\n ]*$/u', $segment));

            $flightDate = $this->re("/{$this->opt($this->t('Passenger'))}\s*\n+\s*((?:[[:alpha:]]+[ ]+[0-9]{1,2}\D+?|[0-9]{1,2}[ ]+[[:alpha:]]+)\,[ ]\d{4}|[0-9]{1,2}\/[0-9]{2}\/[0-9]{4}|[[:alpha:]]+[ ]+[0-9]{1,2}(?:th|nd|rd|st)[ ]+[0-9]{4})\s*\n+\s*{$airName}[ ]*{$airNum}[\n ]*$/u", $segment);
            //01/19/2024
            if (preg_match("/^(\d+)\/(\d+)\/(\d{4})$/", $flightDate, $m) && $m[2] > 12) {
                $flightDate = $m[2] . '/' . $m[1] . '/' . $m[3];
            }
            $dateString = $this->splitCols($this->re("/{$this->opt($this->t('Seat'))}[\n ]+(.+)[\n ]+{$this->opt($this->t('Gate'))}/su", $segment));

            if (preg_match("/([0-9]{1,2}\:[0-9]{2}[ ]+A?P?M?)/u", $dateString[1], $m) && $flightDate !== null) {
                $s->departure()->date($this->normalizeDate($flightDate . ' ' . $m[1]));
            }

            $flightText = $this->re("/{$this->opt($this->t('Boarding'))}\s*\n+\s*((?:{$this->opt($this->t('Terminal'))}[ ]*[A-Z\d]+)?[ ]*(?:{$this->opt($this->t('Terminal'))}[ ]*[A-Z\d]+)?[\n ]*.+\s*\n+\s*.+)\s*\n+\s*/u", $segment);
            //$flightNodes = $this->createTable($flightText, $this->rowColumnPositions($this->inOneRow($flightText)));
            $flightText = preg_replace("/([ ]{5,})/", "                                     ", $flightText);
            $flightNodes = $this->createTable($flightText, [0, 40]);

            if (count($flightNodes) !== 2) {
                // 894145686
                $strArray = explode("\n", $flightText);

                $strLength = null;

                foreach ($strArray as $str) {
                    if ($strLength === null || strlen($this->re("/(?:^|\n)(.{15,}[ ]{2,})\b.+(?:\n|$)/", $str)) < $strLength) {
                        $strLength = strlen($this->re("/(?:^|\n)(.{15,}[ ]{2,})\b.+(?:\n|$)/", $str));
                    }
                }

                $flightNodes = $this->splitCols($flightText, [0, $strLength]);
            }

            if (count($flightNodes) === 2) {
                if (preg_match("/^{$this->opt($this->t('Terminal'))}?[ ]*(?<depTerminal>[A-Z\d]+)?[\n ]*?(?<depCode>[A-Z]{3})\s*\n+\s*(?<depCity>.+)$/u", $flightNodes[1], $m)) {
                    $s->departure()
                        ->code($depCode = $m['depCode'])
                        ->name($depCity = $m['depCity']);

                    if (!empty($m['depTerminal'])) {
                        $s->departure()
                            ->terminal($m['depTerminal']);
                    }
                }

                if (preg_match("/^{$this->opt($this->t('Terminal'))}?[ ]*(?<arrTerminal>[A-Z\d]+)?[\n ]*?(?<arrCode>[A-Z]{3})\s*\n+\s*(?<arrCity>.+)$/u", $flightNodes[0], $m)) {
                    $s->arrival()
                        ->code($arrCode = $m['arrCode'])
                        ->name($m['arrCity']);

                    if (!empty($m['arrTerminal'])) {
                        $s->arrival()
                            ->terminal($m['arrTerminal']);
                    }
                }

                $htmlDepTime = $this->http->FindSingleNode("//tr[*[3]/descendant::text()[{$this->eq($arrCode)}]]/child::*[last()][descendant::text()[{$this->eq($arrCode)}]]/descendant::text()[normalize-space()][3]");

                if ($htmlDepTime !== null && $s->getDepDate() !== null) {
                    $s->arrival()
                        ->date(strtotime($htmlDepTime, $s->getDepDate()));
                } else {
                    $s->arrival()
                        ->noDate();
                }
            }

            $passNode = $this->re("#{$depCity}\s*\n+\s*(.+)\s*\n+\s*{$this->opt($this->t('Passenger'))}#us", preg_replace("/(Green|Silver|Gold|Verde|Plata|Oro|Doters)/", "", $segment));

            if ($passNode !== null) {
                $travellersArray[] = $traveller = preg_replace("/(\n+[ ]{2,}|[ ]{2,)/u", ' ', $passNode);
            }

            $seatInfo = $this->splitCols($this->re("/{$this->opt($this->t('Window'))}\s*\n+\s*(.+)\s*\n+\s*{$this->opt($this->t('Zone'))}/u", $segment));

            if (preg_match("/^([0-9]{1,2}[A-Z])$/u", $seatInfo[1], $seat)) {
                $s->addSeat($seat[0], true, true, $traveller);
            }

            if ($s->getConfirmation() !== null && $s->getDepCode() !== null && $s->getArrCode() !== null
                && preg_match("/\s*(?<pdfName>{$s->getConfirmation()}.*\.pdf)\s*(?:\n|$)/u", $pdfNames, $b)
            ) {
                $bp = $email->add()->bpass();
                $bp
                    ->setRecordLocator($s->getConfirmation())
                    ->setFlightNumber($s->getAirlineName() . ' ' . $s->getFlightNumber())
                    ->setDepCode($s->getDepCode())
                    ->setDepDate($s->getDepDate())
                    ->setAttachmentName($b['pdfName'])
                    ->setTraveller($traveller);
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
        $f->setTravellers(array_unique($travellersArray));
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^([0-9]{1,2})[ ]+([[:alpha:]]+)\,[ ]+(\d{4})[ ]+([0-9]{1,2}\:[0-9]{2}[ ]+A?P?M?)$#u", //19 feb, 2025 20:25 PM
            "#^([0-9]{1,2})\/([0-9]{2})\/(\d{4})[ ]+([0-9]{1,2}\:[0-9]{2}[ ]+A?P?M?)$#u", //19/06/2025 20:25 PM
            "#^([[:alpha:]]+)[ ]+([0-9]{1,2})(?:th|nd|rd|st)[ ]+([0-9]{4})[ ]+([0-9]{1,2}\:[0-9]{2}[ ]+A?P?M?)$#u", //March 27th 2023 20:25 PM
        ];

        $out = [
            "$1 $2 $3 $4",
            "$1.$2.$3 $4",
            "$2 $1 $3 $4",
        ];

        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field));
    }

    private function splitCols($text, $pos = false)
    {
        $cols = [];
        $rows = explode("\n", $text);

        if (!$pos) {
            $pos = $this->rowColsPos($rows[0]);
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

    private function rowColsPos($row)
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

    private function assignLang($text)
    {
        foreach ($this->detectLang as $lang => $dBody) {
            foreach ($dBody as $word) {
                if (stripos($text, $word) !== false) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "contains(normalize-space(.), \"{$s}\")";
        }, $field));
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function getAttachmentName(PlancakeEmailParser $parser, $pdf)
    {
        $header = $parser->getAttachmentHeader($pdf, 'Content-Type');

        if (preg_match('/name=[\"\']*(.+\.pdf)[\'\"]*/i', $header, $m)) {
            return $m[1];
        }

        return null;
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
}
