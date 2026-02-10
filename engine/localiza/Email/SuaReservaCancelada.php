<?php

namespace AwardWallet\Engine\localiza\Email;

// TODO: delete what not use
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class SuaReservaCancelada extends \TAccountChecker
{
    public $mailFiles = "localiza/it-898524219.eml";

    public $lang;
    public static $dictionary = [
        'pt' => [
            'Dados de reserva cancelada' => ['Dados de reserva cancelada'],
            'Retirada:'                  => ['Retirada:'],
        ],
    ];

    private $detectFrom = "no-reply@e.localiza.com";
    private $detectSubject = [
        // pt
        'foi cancelada. Esperamos te ver em breve',
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
            if (!empty($dict['Dados de reserva cancelada']) && $this->http->XPath->query("//*[{$this->contains($dict['Dados de reserva cancelada'])}]")->length > 0
                && !empty($dict['Retirada:']) && $this->http->XPath->query("//*[{$this->contains($dict['Retirada:'])}]")->length > 0
            ) {
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
            if (!empty($dict['Dados de reserva cancelada']) && $this->http->XPath->query("//*[{$this->contains($dict['Dados de reserva cancelada'])}]")->length > 0
                && !empty($dict['Retirada:']) && $this->http->XPath->query("//*[{$this->contains($dict['Retirada:'])}]")->length > 0
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
            ->confirmation($this->http->FindSingleNode("//text()[{$this->starts($this->t('Sua reserva'))}][{$this->contains($this->t('foi cancelada com sucesso.'))}]",
                null, true, "/{$this->opt($this->t('Sua reserva'))}\s+([A-Z\d]{5,})\s+{$this->opt($this->t('foi cancelada com sucesso.'))}/"))
            ->traveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('Oi, '))}]",
                null, true, "/{$this->opt($this->t('Oi, '))}\s*(.+)!\s*$/"), false)
            ->cancelled()
            ->status('Cancelled')
        ;

        // Pick Up
        $pickUpText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Retirada:'))}]/ancestor::tr[1]//text()[normalize-space()]"));
        $r->pickup()
            ->date($this->normalizeDate(
                $this->re("/\n\s*{$this->opt($this->t('Data:'))}\s+(.+)/u", $pickUpText)
                . ', ' . $this->re("/\n\s*{$this->opt($this->t('Horário:'))}\s+(.+)/u", $pickUpText)
            ))
            ->location($this->re("/^\s*{$this->opt($this->t('Retirada:'))}\s+([\s\S]+)\n\s*{$this->opt($this->t('Data'))}/u", $pickUpText))
        ;

        // Car
        $r->car()
            ->type($this->http->FindSingleNode("//text()[{$this->eq($this->t('Grupo escolhido:'))}]/following::text()[normalize-space()][1]"));

        // Drop Off
        $dropOffText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Devolução:'))}]/ancestor::tr[1]//text()[normalize-space()][not({$this->eq($this->t('COMO CHEGAR'))})]"));
        $r->dropoff()
            ->date($this->normalizeDate(
                $this->re("/\n\s*{$this->opt($this->t('Data:'))}\s+(.+)/u", $dropOffText)
                . ', ' . $this->re("/\n\s*{$this->opt($this->t('Horário:'))}\s+(.+)/u", $dropOffText)
            ))
            ->location($this->re("/^\s*{$this->opt($this->t('Devolução:'))}\s+([\s\S]+)\n\s*{$this->opt($this->t('Data:'))}/u", $dropOffText))
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
            // 12/04/2025,  08:30
            '/^\s*(\d{1,2})\/(\d{1,2})\/(\d{4})\s*,\s*(\d{1,2}:\d{2})\s*$/iu',
        ];
        $out = [
            '$1.$2.$3, $4',
        ];

        $date = preg_replace($in, $out, $date);

        // if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4})#", $date, $m)) {
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
