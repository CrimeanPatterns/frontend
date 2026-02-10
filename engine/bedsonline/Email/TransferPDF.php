<?php

namespace AwardWallet\Engine\bedsonline\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class TransferPDF extends \TAccountChecker
{
    public $mailFiles = "bedsonline/it-925548603.eml";
    public $lang = 'en';
    public $pdfNamePattern = ".*pdf";

    public static $dictionary = [
        "en" => [
        ],
    ];
    public $emailsFrom = ['@bedsonline.com', '@flightcentre.com.au'];

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (strpos($text, "BEDSONLINE") !== false
                && strpos($text, "Transfer voucher") !== false
                && strpos($text, 'Lead') !== false
                && strpos($text, 'travellers') !== false
                && strpos($text, 'Pickup instructions') !== false
                && (strpos($text, 'Train details') !== false || strpos($text, 'Flight details') !== false)) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        foreach ($this->emailsFrom as $emailFrom) {
            if (preg_match("/{$this->opt($emailFrom)}$/", $from)) {
                return true;
            }
        }

        return false;
    }

    public function ParseTransferPDF(Email $email, $text)
    {
        $t = $email->add()->transfer();

        $segText = $this->re("/\-\s*(?:{$this->opt($this->t('Outbound'))}|{$this->opt($this->t('Inbound'))})\s*\n+(.+)\n+\s*{$this->opt($this->t('Pickup instructions'))}/su", $text);
        $columnFrom = strlen($this->re("/^(.+){$this->opt($this->t('From'))}/m", $segText));
        $tableSeg = $this->splitCols($segText, [0, $columnFrom]);

        $t->general()
            ->confirmation($this->re("/{$this->opt($this->t('Booking number'))}\s*\n+\s*(?<confNumber>[\d\-]{6,})\n/", $tableSeg[0]))
            ->traveller($this->re("/{$this->opt($this->t('traveller'))}\s*([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\b[ ]{10,}/", $text));

        $transferDate = $this->re("/\s*{$this->opt($this->t('Transfer date'))}\n+\s*(.+\d{4})/", $tableSeg[0]);
        $pickUpTime = $this->re("/{$this->opt($this->t('Pick up time'))}\s*\n+\s*([\d\:]+a?p?m?)h?\n/i", $tableSeg[0]);
        $transportTime = $this->re("/(?:{$this->opt($this->t('Flight details'))}|{$this->opt($this->t('Train details'))})\s*\n+(?:.+\n+)?.+\-\s*([\d\:]+a?p?m?)h?\n/", $tableSeg[0]);

        $s = $t->addSegment();

        if (preg_match("/{$this->opt($this->t('From'))}\s*(?<depName>.+)\n+{$this->t('To')}\s+(?<arrName>.+)\n+\s*{$this->opt($this->t('Lead'))}/s", $tableSeg[1], $m)) {
            $m['depName'] = preg_replace("/\s+/", " ", $m['depName']);
            $m['arrName'] = preg_replace("/{$this->opt($this->t('Image used for illustrative purposes'))}\n+/", "", $m['arrName']);
            $m['arrName'] = preg_replace("/\s+/", " ", $m['arrName']);

            $s->departure()
                ->name($m['depName']);

            $s->arrival()
                ->name($m['arrName']);
        }

        if (!empty($pickUpTime)) {
            $s->departure()
                ->date(strtotime($transferDate . ', ' . $pickUpTime));

            $s->arrival()
                ->noDate();
        } else {
            $s->departure()
                ->date(strtotime($transferDate . ', ' . $transportTime));

            $s->arrival()
                ->noDate();
        }

        $adults = $this->re("/{$this->opt($this->t('travellers'))}\s*(\d+)\s*{$this->opt($this->t('adult'))}/", $text);

        if ($adults !== null) {
            $s->setAdults($adults);
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            $this->ParseTransferPDF($email, $text);
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
