<?php

namespace AwardWallet\Engine\eurostar\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;
use function GuzzleHttp\Psr7\str;

class YourTicketPDF extends \TAccountChecker
{
    public $mailFiles = "eurostar/it-12629878.eml, eurostar/it-904617825.eml, eurostar/it-914016076.eml";
    public $lang = 'en';
    public $pdfNamePattern = ".*pdf";
    
    public static $dictionary = [
        "en" => [
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

            if (stripos($text, 'Your Eurostar ticket') === false) {
                return false;
            }

            if (strpos($text, "TRAVEL DATE") !== false
                && (strpos($text, 'FROM') !== false)
                && (strpos($text, 'DEPARTING') !== false)
                && (strpos($text, 'TRAIN NUMBER') !== false)
                && (strpos($text, 'CARRIER') !== false)
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]vacv\.com$/', $from) > 0;
    }

    public function ParseTrainPDF(Email $email, $text)
    {
        $segmentsText = $this->splitText($text, "/^\s*(Your Eurostar ticket.*)/m", true);

        foreach ($segmentsText as $segText) {
            unset($t);

            $conf = null;
            $traveller = null;

            if (preg_match("/PASSENGER\s+BOOKING REFERENCE \/ PNR\n+\s*(?<traveller>[[:alpha:]][-.\''[:alpha:] ]*[[:alpha:]])[ ]{5,}(?<confNumber>[A-Z\d]{6})\n/", $segText, $m)) {
                $conf = $m['confNumber'];
                $traveller = $m['traveller'];
            }

            foreach ($email->getItineraries() as $it) {
                if ($it->getType() == 'train' && in_array($conf, array_column($it->getConfirmationNumbers(), 0))) {
                    $t = $it;

                    if (!in_array($traveller, array_column($t->getTravellers(), 0))) {
                        $t->general()
                            ->traveller($traveller, true);
                    }

                    break;
                }
            }

            if (!isset($t)) {
                $t = $email->add()->train();

                $t->general()
                    ->confirmation($conf)
                    ->traveller($traveller, true);

            }

            $travelDate = $this->re("/TRAVEL DATE.+\n(?:.+\n)?\s*(\w+\,\s.+\d{4})/", $segText);

            $stationText = $this->re("/FROM.+\n+((?:.+\n){1,5})\s*TRAIN NUMBER/", $segText);

            $stationTable = $this->splitCols($stationText);

            if ($stationText === null){ // it-12629878.eml
                $stationText = $this->re("/TO\n+((?:.+\n){1,5})\s*TRAIN NUMBER/", $segText);

                $stationTable = $this->splitCols($stationText, [0, 50]);
            }

            $depName = '';
            $depDate = '';

            if (preg_match("/^\s*(?<depName>(?:.+\n*){1,3})\s+DEPARTING\s+(?<depTime>[\d\:]+\s*A?P?M?)(?:\s*local time)?$/", $stationTable[0], $m)) {
                $depName = preg_replace("/(\s{2,})/u", " ", $m['depName']);
                $depDate = strtotime($travelDate . ', ' . $m['depTime']);
            } else {
                $this->logger->debug($stationTable[0]);
            }

            $s = $t->addSegment();

            $s->departure()
                ->name($depName)
                ->date($depDate);

            if (preg_match("/^\s*(?<arrName>.+)\s+ARRIVING\s+(?<arrTime>[\d\:]+\s*A?P?M?)(?:\s*local time)?$/", $stationTable[1], $m)) {
                $s->arrival()
                    ->name($m['arrName'])
                    ->date(strtotime($travelDate . ', ' . $m['arrTime']));
            }

            if (preg_match("/TRAIN NUMBER\s+COACH\s+SEAT\n+\s*(?<trainNumber>\d{2,4})\s+(?<coach>\d+)\s*(?<seat>\S+)?\n/", $segText, $m)) {
                $s->setNumber($m['trainNumber'])
                    ->setCarNumber($m['coach'])
                    ->addSeat($m['seat']);
            }

            if (preg_match("/CARRIERS?\s+ISSUER\s+TICKET NUMBER\n\s*(?<service>.+\b)[ ]{15,}(\S.+\b)[ ]{15,}(?<ticket>\d{8,})/", $segText, $m)) {
                $s->setServiceName($m['service']);
                $t->addTicketNumber($m['ticket'], false, $traveller);
            }

            foreach ($t->getSegments() as $key => $seg) {
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
                        $t->removeSegment($s);

                        break;
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

            if (stripos($text, 'Your Eurostar ticket') === false) {
                continue;
            }

            $this->ParseTrainPDF($email, $text);
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
            return str_replace(' ', '\s+', preg_quote($s));
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

    private function splitText(?string $textSource, string $pattern, bool $saveDelimiter = false): array
    {
        $result = [];

        if ($saveDelimiter) {
            $textFragments = preg_split($pattern, $textSource, -1, PREG_SPLIT_DELIM_CAPTURE);
            array_shift($textFragments);

            for ($i = 0; $i < count($textFragments) - 1; $i += 2) {
                $result[] = $textFragments[$i] . $textFragments[$i + 1];
            }
        } else {
            $result = preg_split($pattern, $textSource);
            array_shift($result);
        }

        return $result;
    }

    private function rowColsPos(?string $row): array
    {
        $pos = [];
        $head = preg_split('/\s{2,}/', $row, -1, PREG_SPLIT_NO_EMPTY);
        $lastpos = 0;

        foreach ($head as $word) {
            $pos[] = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $lastpos = mb_strpos($row, $word, $lastpos, 'UTF-8') + mb_strlen($word, 'UTF-8');
        }

        return $pos;
    }

    private function splitCols(?string $text, ?array $pos = null): array
    {
        $cols = [];

        if ($text === null) {
            return $cols;
        }
        $rows = explode("\n", $text);

        if ($pos === null || count($pos) === 0) {
            $pos = $this->rowColsPos($rows[0]);
        }
        arsort($pos);

        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $cols[$k][] = rtrim(mb_substr($row, $p, null, 'UTF-8'));
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);

        foreach ($cols as &$col) {
            $col = implode("\n", $col);
        }

        return $cols;
    }
}
