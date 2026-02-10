<?php

namespace AwardWallet\Engine\reserva\Email;

// TODO: delete what not use
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class BilhetePdf extends \TAccountChecker
{
    public $mailFiles = "reserva/it-894051887.eml, reserva/it-897792000.eml";

    public $pdfNamePattern = ".*\.pdf";

    public $lang;
    public static $dictionary = [
        'pt' => [
            'Nome do Passageiro' => 'Nome do Passageiro',
            'Voo'                => 'Voo',
            'Classe'             => 'Classe',
            'Destino(s)'         => 'Destino(s)',
            'Valor Tarifas'      => 'Valor Tarifas',
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]reservafacil\./", $from) > 0;
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
        // detect Format
        foreach (self::$dictionary as $lang => $dict) {
            if (
                !empty($dict['Nome do Passageiro']) && !empty($dict['Voo'])
                && !empty($dict['Classe']) && !empty($dict['Destino(s)'])
                && !empty($dict['Valor Tarifas'])
                && $this->containsText($text, $dict['Nome do Passageiro']) === true
                && $this->containsText($text, $dict['Voo']) === true
                && $this->containsText($text, $dict['Classe']) === true
                && $this->containsText($text, $dict['Destino(s)']) === true
                && $this->containsText($text, $dict['Valor Tarifas']) === true
                && preg_match("/\n *{$this->opt($dict['Nome do Passageiro'])} {2,}[\s\S]+\n *{$this->opt($dict['Voo'])} {2,}{$this->opt($dict['Classe'])} {2,}.+ {2,}{$this->opt($dict['Destino(s)'])} {2,}[\s\S]+\n *{$this->opt($dict['Valor Tarifas'])} {2,}/u", $text)
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
            // if ($this->detectPdf($text) == true) {
            $this->parseEmailPdf($email, $text);
            // }
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
        $f = $email->add()->flight();

        // General
        $f->general()
            ->confirmation($this->re("/\n *{$this->opt($this->t('LOC (Localizador da reserva)'))} +([A-Z\d]{5,7})\n/", $textPdf))
        ;
        $traveller = $this->re("/\n *{$this->opt($this->t('Nome do Passageiro'))} +(.+)\n/", $textPdf);
        $f->general()
            ->traveller($traveller, true)
        ;

        $f->issued()
            ->ticket($this->re("/\n *{$this->opt($this->t('Número do bilhete'))} +(.+)\n/", $textPdf), false, $traveller);

        $ffAccount = $this->re("/\n *{$this->opt($this->t('Cartão de Fidelidade'))} +(.+)\n/", $textPdf);

        if (!empty($ffAccount)) {
            $f->program()
                ->account($ffAccount, false, $traveller);
        }

        $segmentHeaderText = $this->re("/\n( *{$this->opt($this->t('Voo'))} {2,}{$this->opt($this->t('Classe'))} {2,}.+)\n/", $textPdf);
        $segmentHeaderTable = $this->createTable($segmentHeaderText);
        $poses = [
            'flight'   => 20,
            'class'    => 21,
            'from'     => 22,
            'to'       => 23,
            'fromDate' => 24,
            'toDate'   => 25,
            'seat'     => 26,
            'conf'     => 27,
        ];

        foreach ($segmentHeaderTable as $i => $hname) {
            switch ($hname) {
                case preg_match("/^\s*{$this->opt($this->t('Voo'))}\s*$/", $hname) > 0:
                    $poses['flight'] = $i;

                    break;

                case preg_match("/^\s*{$this->opt($this->t('Classe'))}\s*$/", $hname) > 0:
                    $poses['class'] = $i;

                    break;

                case preg_match("/^\s*{$this->opt($this->t('Origem'))}\s*$/", $hname) > 0:
                    $poses['from'] = $i;

                    break;

                case preg_match("/^\s*{$this->opt($this->t('Destino(s)'))}\s*$/", $hname) > 0:
                    $poses['to'] = $i;

                    break;

                case preg_match("/^\s*{$this->opt($this->t('Saída'))}\s*$/", $hname) > 0:
                    $poses['fromDate'] = $i;

                    break;

                case preg_match("/^\s*{$this->opt($this->t('Chegada'))}\s*$/", $hname) > 0:
                    $poses['toDate'] = $i;

                    break;

                case preg_match("/^\s*{$this->opt($this->t('LOC Cia'))}\s*$/", $hname) > 0:
                    $poses['conf'] = $i;

                    break;

                case preg_match("/^\s*{$this->opt($this->t('Assento'))}\s*$/", $hname) > 0:
                    $poses['seat'] = $i;

                    break;
            }
        }

        $segmentText = $this->re("/\n *{$this->opt($this->t('Voo'))} {2,}{$this->opt($this->t('Classe'))} {2,}.+\n+([\s\S]+?)"
            . "\n *{$this->opt($this->t('Valor Tarifas'))}/", $textPdf);
        $segmentsRows = array_filter(explode("\n", $segmentText));
        $segments = [];
        $seg = '';

        foreach ($segmentsRows as $row) {
            if (preg_match_all("/ {2,}[A-Z]{3} - \S/", $seg . $row, $m)
                && count($m[0]) > 2
            ) {
                $segments[] = $seg;
                $seg = $row;
            } else {
                $seg .= "\n" . $row;
            }
        }
        $segments[] = $seg;
        // $this->logger->debug('$segments = ' . print_r($segments, true));

        foreach ($segments as $sText) {
            $s = $f->addSegment();

            $table = $this->createTable($sText, $this->rowColumnPositions($this->inOneRow($sText)));
            // $this->logger->debug('$table = ' . print_r($table, true));

            // Airline
            if (preg_match("/^\s*(?<al>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<fn>\d{1,4})(?:\s*\({$this->opt($this->t('operado'))}\s+(?<operator>[A-Z\d]{2})\))?\s*$/", $table[$poses['flight']] ?? '', $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fn']);

                if (!empty($m['operator'])) {
                    $s->airline()
                        ->operator($m['operator']);
                }
            }

            if (preg_match("/^\s*([A-Z\d]{5,7})\s*$/", $table[$poses['conf']] ?? '', $m)
                && !in_array($m[1], array_column($f->getConfirmationNumbers(), 0))
            ) {
                $s->airline()
                    ->confirmation($m[1]);
            }

            // Departure
            if (preg_match("/^\s*(?<code>[A-Z]{3})\s*-\s*(?<name>[\s\S]+)\s*$/", $table[$poses['from']] ?? '', $m)) {
                $s->departure()
                    ->code($m['code'])
                    ->name(preg_replace('/\s+/', ' ', trim($m['name'])));
            }
            $s->departure()
                ->date($this->normalizeDate($table[$poses['fromDate']] ?? ''));

            // Arrival
            if (preg_match("/^\s*(?<code>[A-Z]{3})\s*-\s*(?<name>[\s\S]+)\s*$/", $table[$poses['to']] ?? '', $m)) {
                $s->arrival()
                    ->code($m['code'])
                    ->name(preg_replace('/\s+/', ' ', trim($m['name'])));
            }
            $s->arrival()
                ->date($this->normalizeDate($table[$poses['toDate']] ?? ''));

            // Extra
            if (preg_match("/^\s*([A-Z]{1,2})\s*$/", $table[$poses['class']] ?? '', $m)) {
                $s->extra()
                    ->bookingCode($m[1]);
            }

            if (preg_match("/^\s*(\d{1,3}[A-Z])\s*$/", $table[$poses['seat']] ?? '', $m)
            ) {
                $s->extra()
                    ->seat($m[1], true, true, $traveller);
            }
        }

        // Price
        $total = $this->re("/[\s\S]+\n *Total {3,}(.+)/", $textPdf);

        if (preg_match("#^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$#", $total, $m)
            || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$#", $total, $m)
        ) {
            $currency = $this->currency($m['currency']);
            $f->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency)
            ;
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

    private function inOneRow($text)
    {
        $textRows = array_filter(explode("\n", $text));
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
            // 30/03/2025 04:20
            '/^\s*(\d{1,2})\/(\d{1,2})\/(\d{4})\s*(\d{1,2})\s*:\s*(\d{2})\s*$/iu',
        ];
        $out = [
            '$1.$2.$3, $4:$5',
        ];

        $date = preg_replace($in, $out, $date);
        // if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
        //     if ($en = MonthTranslate::translate($m[1], $this->lang)) {
        //         $date = str_replace($m[1], $en, $date);
        //     }
        // }
//        $this->logger->debug('date replace = ' . print_r( $date, true));

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
            return preg_quote($s, $delimiter);
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

    private function currency($s)
    {
        if ($code = $this->re("#^\s*([A-Z]{3})\s*$#", $s)) {
            return $code;
        }
        $sym = [
            'R$' => 'BRL',
        ];

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return $s;
    }
}
