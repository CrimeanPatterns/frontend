<?php

namespace AwardWallet\Engine\bondi\Email;

use AwardWallet\Engine\bcd\Email\PDF;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BoardingPassPdf extends \TAccountChecker
{
	public $mailFiles = "bondi/it-910771710.eml";
    public $subjects = [
        'Flybondi - Your boarding pass',
    ];

    public $pdfNamePattern = ".*pdf";

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'Your check-in is confirmed.'    => 'Your check-in is confirmed.',
            "we've also attached the PDF" => "we've also attached the PDF",
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'flybondi.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectProvider($from, $subject)
    {
        if (stripos($from, 'flybondi.com') !== false) {
            return true;
        }

        if (stripos($subject, 'Flybondi') !== false) {
            return true;
        }

        if ($this->http->XPath->query("//*[{$this->contains(['The Flybondi team'])}]")->length > 0
            || $this->http->XPath->query("//*[{$this->contains(['flybondi.com'])}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->detectProvider($parser->getHeader('from'), $parser->getSubject()) !== true) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['Your check-in is confirmed.']) && $this->http->XPath->query("//text()[{$this->contains($this->t('Your check-in is confirmed.'))}]")->length > 0
                && !empty($dict["we've also attached the PDF"]) && $this->http->XPath->query("//text()[{$this->contains($this->t("we've also attached the PDF"))}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]flybondi\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        $pdfNames = '';

        foreach ($pdfs as $pdf) {
            $pdfNames .= "\n" . $this->getAttachmentName($parser, $pdf);

            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            $this->BoardingPass($email, $text, $pdfNames);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function BoardingPass(Email $email, $text, $pdfNames)
    {
        $this->logger->debug($text);
        $f = $email->add()->flight();

        $f->general()->noConfirmation();

        $searchPattern = "/\n*[ ]*(?<name>[[:alpha:]][-.\'’\/[:alpha:] ]*[[:alpha:]])[ ]*\n+[ ]*(?<conf>[A-Z\d]{5,8})\n+.+\n+[ ]*(?<number>\d{1,5})\n+.+\d+[ ]{2,}(?<seat>\d{1,}[A-Z]+)\n+[ ]*(?<depCode>[A-Z]{3})[ ]*(?<arrCode>[A-Z]{3})\n+[ ]*(?<depCity>.+)[ ]{2,}(?<arrCity>.+)\n+[ ]*(?<depDate>.+)[ ]{2,}(?<arrDate>.+)\n+[ ]*(?<depTime>[0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]*h?[ ]{2,}(?<arrTime>[0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]*h?\n+[ ]*.+[ ]{2,}(?<ticket>[\d\-]{5,})\n*/u";

        if (preg_match($searchPattern, $text, $m)){
            $f->general()
                ->traveller($m['name']);

            $f->issued()
                ->ticket($m['ticket'],false, $m['name']);

            $s = $f->addSegment();

            $s->setConfirmation($m['conf'])
                ->setFlightNumber($m['number'])
                ->setAirlineName("FO");

            $s->departure()
                ->name($m['depCity'])
                ->code($m['depCode'])
                ->date($this->normalizeDate($m['depDate'] . ', ' . $m['depTime']));

            $s->arrival()
                ->name($m['arrCity'])
                ->code($m['arrCode'])
                ->date($this->normalizeDate($m['arrDate'] . ', ' . $m['arrTime']));

            $s->extra()
                ->seat($m['seat'], false, false, $m['name']);

            $bp = $email->add()->bpass();

            $bp
                ->setRecordLocator($m['conf'])
                ->setFlightNumber($s->getAirlineName() . ' ' .$s->getFlightNumber())
                ->setAttachmentName($pdfNames)
                ->setDepCode($s->getDepCode())
                ->setDepDate($s->getDepDate())
                ->setTraveller($m['name']);
        }
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
        } else {
            $header = $parser->getAttachmentHeader($pdf, 'Content-Disposition');

            if (preg_match('/filename=[\"\']*(.+\.pdf)[\'\"]*/i', $header, $m)) {
                $this->logger->debug($m[1]);
                return $m[1];
            }
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
