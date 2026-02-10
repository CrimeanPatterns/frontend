<?php

namespace AwardWallet\Engine\localiza\Email;

// TODO: delete what not use
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class SuaReserva extends \TAccountChecker
{
    public $mailFiles = "localiza/it-414125877.eml, localiza/it-896594054.eml, localiza/it-898841636.eml";

    public $lang;
    public static $dictionary = [
        'pt' => [
            'Confira todos os detalhes:' => ['Confira todos os detalhes:', 'Verifique os dados da sua reserva:', 'Confira os detalhes da sua reserva:'],
            'Localizador'                => ['Localizador', 'O código da sua reserva é:'],
            'Retirada do veículo'        => ['Retirada do veículo', 'Retirada do veículo:'],
            'Devolução do veículo'       => ['Devolução do veículo', 'Devolução do veículo:'],
            'Horario de funcionamento'   => ['Horario de funcionamento', 'Horário de funcionamento:'],
            'Diárias'                    => ['Diárias', 'Diárias:'],
        ],
    ];

    private $detectFrom = "no-reply@e.localiza.com";
    private $detectSubject = [
        // pt
        'está confirmada!',
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]localiza\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//a/@href[{$this->contains(['.localiza.com'])}]")->length === 0
            && $this->http->XPath->query("//*[{$this->eq(['localiza.com'])}]")->length === 0
        ) {
            return false;
        }
        // detect Format
        foreach (self::$dictionary as $dict) {
            if (!empty($dict['Confira todos os detalhes:']) && $this->http->XPath->query("//*[{$this->contains($dict['Confira todos os detalhes:'])}]")->length > 0
                && !empty($dict['Retirada do veículo']) && $this->http->XPath->query("//*[{$this->contains($dict['Retirada do veículo'])}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }
        $this->parseEmailHtml($email);

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

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Confira todos os detalhes:']) && $this->http->XPath->query("//*[{$this->contains($dict['Confira todos os detalhes:'])}]")->length > 0
                && !empty($dict['Retirada do veículo']) && $this->http->XPath->query("//*[{$this->contains($dict['Retirada do veículo'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function parseEmailHtml(Email $email)
    {
        $r = $email->add()->rental();

        // General
        $r->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Localizador'))}]/following::text()[normalize-space()][1]",
                null, true, "/^\s*[A-Z\d]{5,}\s*$/"));
        $traveller = $this->http->FindSingleNode("//text()[" . $this->starts($this->t('Olá,')) . "]", null, true,
            "/{$this->opt($this->t('Olá,'))}\s*(\D+)(?:\!|\,|\.)/u");

        if (!empty($traveller)) {
            $r->general()
                ->traveller($traveller, false);
        }

        // Pick Up
        $pickUpText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Retirada do veículo'))}]/ancestor::*[not(.//text()[{$this->eq($this->t('Devolução do veículo'))}])][last()]//text()[normalize-space()][not({$this->eq($this->t('COMO CHEGAR'))})]"));
        $r->pickup()
            ->date($this->normalizeDate(
                $this->re("/\n\s*{$this->opt($this->t('Data:'))}\s+(.+)/u", $pickUpText)
                . ', ' . $this->re("/\n\s*{$this->opt($this->t('Hora:'))}\s+(.+)/u", $pickUpText)
            ))
            ->location($this->re("/\n\s*{$this->opt($this->t('Agência:'))}\s+(.+)/u", $pickUpText)
                . ', ' . $this->re("/\n\s*{$this->opt($this->t('Endereço:'))}\s+([\s\S]+)\n\s*{$this->opt($this->t('Horario de funcionamento'))}/u", $pickUpText))
            ->openingHours($this->re("/\n\s*{$this->opt($this->t('Horario de funcionamento'))}\s+([\s\S]+)\s*$/u", $pickUpText))
        ;

        // Car
        $type = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Veículo similar a '))}]/preceding::text()[normalize-space()][1]");

        if (!empty($type)) {
            $r->car()
                ->type($type);
        }
        $model = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Veículo similar a '))}]", null, true,
            "/^\s*{$this->opt($this->t('Veículo similar a '))}\s*(.+)/");

        if (!empty($model)) {
            $r->car()
                ->model($model);
        }

        // Drop Off
        $dropOffText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Devolução do veículo'))}]/ancestor::*[not(.//text()[{$this->eq($this->t('Retirada do veículo'))}])][last()]//text()[normalize-space()][not({$this->eq($this->t('COMO CHEGAR'))})]"));
        $r->dropoff()
            ->date($this->normalizeDate(
                $this->re("/\n\s*{$this->opt($this->t('Data:'))}\s+(.+)/u", $dropOffText)
                . ', ' . $this->re("/\n\s*{$this->opt($this->t('Hora:'))}\s+(.+)/u", $dropOffText)
            ))
            ->location($this->re("/\n\s*{$this->opt($this->t('Agência:'))}\s+(.+)/u", $dropOffText)
                . ', ' . $this->re("/\n\s*{$this->opt($this->t('Endereço:'))}\s+([\s\S]+)\n\s*{$this->opt($this->t('Horario de funcionamento'))}/u", $dropOffText))
            ->openingHours($this->re("/\n\s*{$this->opt($this->t('Horario de funcionamento'))}\s+([\s\S]+)\s*$/u", $dropOffText))
        ;

        $priceText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Informações detalhadas'))}]/following::text()[normalize-space()][1]/ancestor::*[not(.//text()[{$this->eq($this->t('Informações detalhadas'))}])][last()]//text()[normalize-space()]"));

        if (empty($priceText)) {
            $priceText = implode("\n",
                $this->http->FindNodes("//text()[{$this->eq($this->t('Diárias:'))}]/ancestor::*[count(.//text()[normalize-space()]) > 2][1]//text()[normalize-space()]"));
            $priceText = preg_replace("/\n\s*{$this->opt($this->t('Valor total:'))}[\s\S]+/", '', $priceText);
        }

        if (preg_match("/(?:^|\n)\s*{$this->opt($this->t('Diárias'))}\n\s*(?<days>\d+) x (?<cost>\D{0,4} ?\d[,. \d]*? ?\D{0,4})\n(?<tax>[\s\S]+)/", $priceText, $mat)) {
            if (preg_match("/^(?<currency>\D{1,3})\s*(?<total>\d[\d \.\,\']+?)\s*$/u", $mat['cost'], $m)
                || preg_match("/^(?<total>\d[\d \.\,\']+?)\s+(?<currency>\D{1,3})\.?$/u", $mat['cost'], $m)) {
                $currency = $this->currency($m['currency']);
                $r->price()
                    ->cost($mat['days'] * PriceHelper::parse($m['total'], $currency))
                    ->currency($currency);
            }

            $rows = explode("\n", $mat['tax']);

            foreach ($rows as $row) {
                if (preg_match("/(?<name>.+):\s*(?<days>\d+) diárias? x \D{0,4} ?(?<amount>\d[,. \d]*?) ?\D{0,4}\s*$/", $row, $m)) {
                    $r->price()
                        ->fee($m['name'], $m['days'] * PriceHelper::parse($m['amount'], $currency));
                } elseif (preg_match("/(?<name>.+):\s*\D{0,4} ?(?<amount>\d[,. \d]*?) ?\D{0,4}\s*$/", $row, $m)) {
                    $r->price()
                        ->fee($m['name'], PriceHelper::parse($m['amount'], $currency));
                }
            }
        }

        $total = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Valor total:'))}]", null, true,
            "/{$this->opt($this->t('Valor total:'))}\s*(.+)/");

        if (empty($total)) {
            $total = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Valor total:'))}]/following::text()[normalize-space()][1]");
        }

        if (preg_match("/^(?<currency>\D{1,3})\s*(?<total>\d[\d \.\,\']+?)\s*$/u", $total, $m)
            || preg_match("/^(?<total>\d[\d \.\,\']+?)\s+(?<currency>\D{1,3})\.?$/u", $total, $m)) {
            $currency = $this->currency($m['currency']);
            $r->price()
                ->total(PriceHelper::parse($m['total'], $currency))
                ->currency($currency);
        }

        return true;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    // additional methods
    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        $in = [
            // 09, abr 2025, 16h30
            '/^\s*(\d+)[.,]?\s*([[:alpha:]]+)\s+(\d{4})\s*,\s*(\d{1,2})[h:](\d{2})\s*$/iu',
        ];
        $out = [
            '$1 $2 $3, $4:$5',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4})#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
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
            return str_replace(' ', '\s+', preg_quote($s, $delimiter));
        }, $field)) . ')';
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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
