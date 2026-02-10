<?php

namespace AwardWallet\Engine\sundiogroup\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class AutohuurvoucherPdf extends \TAccountChecker
{
    public $mailFiles = "sundiogroup/it-7584818.eml, sundiogroup/it-894526062.eml, sundiogroup/it-899144313.eml";

    public $lang = 'nl';

    public $pdfNamePattern = ".*pdf";

    public $detectSubjects = [
        'nl' => [
            'Voucher voor reservering',
        ],
    ];

    public static $dictionary = [
        'nl' => [
            'startMainPart'             => ['LOKALE AUTOLEVERANCIER', 'LOKALE VERHUURPARTNER'],
            'endPickUpAndDropOffPart'   => ['HANDIGE LINKS', 'WAT IS INCLUSIEF'],
            'Bestuurdersnaam'           => ['Bestuurdersnaam', 'Klant (Naam hoofdbestuurder)'],
            'Openingstijden'            => ['Openingstijden', 'Openingstijden verhuurstation'],
            'Categorie autoleverancier' => ['Categorie autoleverancier', 'Cat. lokale verhuurpartner'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]sunnycars\.nl$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers['from'], 'sunnycars.nl') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
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
        if ($this->striposArray($text, $this->t('Sunny Cars reserveringsnummer')) === false
            && $this->striposArray($text, $this->t('Categorie Sunny Cars')) === false
        ) {
            return false;
        }

        // detect Format
        if ($this->striposArray($text, $this->t('startMainPart')) !== false
            && $this->striposArray($text, $this->t('HUURGEGEVENS')) !== false
            && $this->striposArray($text, $this->t('OPHAALGEGEVENS')) !== false
            && $this->striposArray($text, $this->t('INLEVERGEGEVENS')) !== false
            && $this->striposArray($text, $this->t('Huurperiode')) !== false
        ) {
            return true;
        }

        return false;
    }

    public function parseRental(Email $email, $text)
    {
        $r = $email->add()->rental();

        $patterns = [
            'time'  => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.
            'phone' => '[\+\-\/\(\)\s\d]+',
        ];

        // extract text part with confirmation, traveller and car type/model (common part)
        $commonPart = $this->re("/\n([ ]*(?:{$this->opt($this->t('startMainPart'))}[ ]*)?{$this->opt($this->t('HUURGEGEVENS'))}.+?)\n\s*{$this->opt($this->t('OPHAALGEGEVENS'))}/msu", $text);
        // get columns positions for $commonPart
        $pos = $this->columnPositions($commonPart, 40);
        // get table with columns
        $commonTable = $this->createTable($commonPart, $pos);

        $commonInfo = null;

        foreach ($commonTable as $column) {
            if ($this->striposArray($column, $this->t('HUURGEGEVENS')) !== false) {
                $commonInfo = $column;

                break;
            }
        }

        if ($commonInfo === null) {
            $this->logger->error("incorrect commonTable parse");

            return;
        }

        // collect reservation confirmation
        if (preg_match("/^\s*(?<desc>{$this->opt($this->t('Sunny Cars reserveringsnummer'))})[\s\:]+(?<number>\d+)\s*$/mu", $commonInfo, $m)) {
            $r->general()
                ->confirmation($m['number'], $m['desc']);
        }

        // collect traveller
        $traveller = $this->re("/^\s*?{$this->opt($this->t('Bestuurdersnaam'))}[\s\:]+([[:alpha:]][-.,\/\'’[:alpha:] ]*[[:alpha:]])\s*$/mu", $commonInfo);

        if (!empty($traveller)) {
            $r->general()
                ->traveller($traveller);
        }

        // collect car info
        if (preg_match("/{$this->opt($this->t('Categorie Sunny Cars'))}[\s\:]+(?<type>\S+?)[\s\-]+(?<model>.+?)\s*{$this->opt($this->t('Categorie autoleverancier'))}/su", $commonInfo, $m)) {
            $r->car()
                ->type($m['type'])
                ->model(preg_replace("/\s+/", " ", $m['model']));
        }

        // extract text part with pick-up and drop-off details
        $pickUpAndDropOffPart = $this->re("/({$this->opt($this->t('OPHAALGEGEVENS'))}\s*{$this->opt($this->t('INLEVERGEGEVENS'))}.+?)\n\s*{$this->opt($this->t('endPickUpAndDropOffPart'))}/su", $text);
        // extract header rows for columns split
        $headerRows = $this->re("/^(.+?{$this->opt($this->t('Bestemming:'))}.+?)\n/su", $pickUpAndDropOffPart);
        // get columns positions for $pickUpAndDropOffPart
        $pos = $this->columnPositions($headerRows, 25);
        // get table with columns
        $pickUpAndDropOffTable = $this->createTable($pickUpAndDropOffPart, $pos);

        if (count($pickUpAndDropOffTable) !== 2) {
            $this->logger->error("incorrect pickUpAndDropOffTable parse");

            return;
        }

        // let's use more convenient names
        $pickUpInfo = $pickUpAndDropOffTable[0];
        $dropOffInfo = $pickUpAndDropOffTable[1];

        // collect pick-up address and datetime
        $pickUpLocation = preg_replace("/\,?[ ]*\n+[ ]*/", ", ", $this->re("/{$this->opt($this->t('Adres:'))}\s*(.+?)\s*{$this->opt($this->t('Tel.'))}/sui", $pickUpInfo));
        $pickUpDateTime = $this->re("/{$this->opt($this->t('Datum/tijdstip:'))}\s*(.+?)\s*$/mu", $pickUpInfo);

        $r->pickup()
            ->location($pickUpLocation)
            ->date($this->normalizeDate($pickUpDateTime));

        // collect pick-up opening hours
        $pickUpOpeningHours = $this->re("/{$this->opt($this->t('Openingstijden'))}[\s\:]+(.+?)\s*(?:{$this->opt($this->t('Ophaal-'))}|$)/su", $pickUpInfo);
        $pickUpOpeningHours = preg_replace("/{$this->opt($this->t('verhuurstation:'))}/", "", $pickUpOpeningHours);
        $pickUpOpeningHours = preg_replace("/\s+/", " ", $pickUpOpeningHours);

        if (preg_match_all("/[\s\,]*(.+?\:\s*{$patterns['time']}\s*\-\s*{$patterns['time']})/u", $pickUpOpeningHours, $m, PREG_PATTERN_ORDER)) {
            $r->setPickUpOpeningHours($m[1]);
        }

        // collect pick-up phone
        $pickUpPhone = $this->re("/{$this->opt($this->t('Tel'))}[\s\.\:]+({$patterns['phone']})[ ]*(?:\n|$)/sui", $pickUpInfo);

        if (!empty($pickUpPhone)) {
            $r->pickup()
                ->phone($pickUpPhone);
        }

        // collect drop-off address and datetime
        $dropOffLocation = preg_replace("/\,?[ ]*\n+[ ]*/", ", ", $this->re("/{$this->opt($this->t('Adres:'))}\s*(.+?)\s*{$this->opt($this->t('Tel.'))}/sui", $dropOffInfo));
        $dropOffDateTime = $this->re("/{$this->opt($this->t('Datum/tijdstip:'))}\s*(.+?)\s*$/mu", $dropOffInfo);

        $r->dropoff()
            ->location($dropOffLocation)
            ->date($this->normalizeDate($dropOffDateTime));

        // collect drop-off opening hours
        $dropOffOpeningHours = $this->re("/{$this->opt($this->t('Openingstijden'))}[\s\:]+(.+?)\s*(?:{$this->opt($this->t('Ophaal-'))}|$)/su", $dropOffInfo);
        $dropOffOpeningHours = preg_replace("/{$this->opt($this->t('verhuurstation:'))}/", "", $dropOffOpeningHours);
        $dropOffOpeningHours = preg_replace("/\s+/", " ", $dropOffOpeningHours);

        if (preg_match_all("/[\s\,]*(.+?\:\s*{$patterns['time']}\s*\-\s*{$patterns['time']})/u", $dropOffOpeningHours, $m, PREG_PATTERN_ORDER)) {
            $r->setDropOffOpeningHours($m[1]);
        }

        // collect drop-off phone
        $dropOffPhone = $this->re("/{$this->opt($this->t('Tel'))}[\s\.\:]+({$patterns['phone']})[ ]*(?:\n|$)/sui", $dropOffInfo);

        if (!empty($dropOffPhone)) {
            $r->dropoff()
                ->phone($dropOffPhone);
        }

        // collect pick-up notes and drop-off notes
        $pickUpNotes = $this->re("/{$this->opt($this->t('Ophaal-'))}\s*(.+?)\s*(?:{$this->opt($this->t('Adres'))}|$)/su", $pickUpInfo);
        $pickUpNotes = trim(preg_replace("/{$this->opt($this->t('instructies:'))}/su", "", $pickUpNotes));

        if (!empty($pickUpNotes)) {
            $pickUpNotes = "Ophaal-instructies: " . preg_replace("/[ ]*\n+[ ]*/su", " ", $pickUpNotes);
        } else {
            $pickUpNotes = null;
        }

        $dropOffNotes = $this->re("/{$this->opt($this->t('Inlever-'))}\s*(.+?)\s*(?:{$this->opt($this->t('Adres'))}|$)/su", $dropOffInfo);
        $dropOffNotes = trim(preg_replace("/{$this->opt($this->t('gegevens:'))}/su", "", $dropOffNotes));

        if (!empty($dropOffNotes)) {
            $dropOffNotes = "Inlever-gegevens: " . preg_replace("/[ ]*\n+[ ]*/su", ". ", $dropOffNotes);
        } else {
            $dropOffNotes = null;
        }

        $notes = $pickUpNotes . "\n" . $dropOffNotes;

        if (!empty(trim($notes))) {
            $r->general()->notes($notes);
        }

        // collect provider phone
        if (preg_match("/(?<desc>{$this->opt($this->t('24/7 NOODNUMMER'))}).+?[ ]*(?<phone>{$patterns['phone']})[ ]*\n/sui", $text, $m)) {
            $r->program()
                ->phone($m['phone'], $m['desc']);
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));
            $this->parseRental($email, $text);
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

    private function normalizeDate($str)
    {
        $in = [
            "#^[^\d\s]+\s+(\d+\s+[^\d\s]+\s+\d{4}),\s+(\d+:\d+)$#", //za 03 jun 2017, 09:35
        ];
        $out = [
            "$1, $2",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);

                return strtotime($str);
            }
        }

        return $str;
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

    private function columnPositions($table, $correct = 2)
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
}
