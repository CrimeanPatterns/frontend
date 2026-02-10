<?php

namespace AwardWallet\Engine\aeroplan\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Points extends \TAccountChecker
{
    public $mailFiles = "aeroplan/statements/it-902042592.eml, aeroplan/statements/it-902653044.eml, aeroplan/statements/it-902934717.eml";
    public $lang = '';

    public static $dictionary = [
        "en" => [
            'You have been sent this email because you are subscribed to receive Aeroplan' => [
                'You have been sent this email because you are subscribed to receive Aeroplan',
                'You have received this email because it is an important communication about the Aeroplan', ],
        ],
        "fr" => [
            'Hello'                                                                        => ['Bonjour'],
            'You have been sent this email because you are subscribed to receive Aeroplan' => 'Vous recevez ce courriel parce que vous êtes inscrit aux communications électroniques promotionnelles d’Aéroplan',
            'Web version'                                                                  => ['Web version', 'Version Web', 'Version web'],
            'Individual points balance as of'                                              => 'Solde de points individuel en date du',
        ],
    ];

    public $detectLang = [
        "fr" => ["Bonjour"],
        "en" => ["Hello"],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $this->assignLang();

        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Aeroplan')]")->length === 0
        && $this->http->XPath->query("//img[contains(@src, 'aeroplan')]")->length === 0) {
            return false;
        }

        if (($this->http->XPath->query("//text()[{$this->eq($this->t('Web version'))}]/following::text()[normalize-space()][1][{$this->contains($this->t('pts'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->starts($this->t('Hello'))}]/preceding::text()[normalize-space()][1][{$this->contains($this->t('pts'))}]")->length > 0)
            && $this->http->XPath->query("//text()[{$this->contains($this->t('You have been sent this email because you are subscribed to receive Aeroplan'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        if (stripos($from, '@mail.aircanada.com') !== false) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello'))}]", null, true, "/^{$this->opt($this->t('Hello'))}\s+(\D+)\,$/");

        if (!empty($name)) {
            $st->addProperty('Name', trim($name, ','));
        }

        $balance = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Web version'))}]/following::text()[normalize-space()][1][{$this->contains($this->t('pts'))}]", null, true, "/^([\d\,\s]+)\s*pts/i");

        if (empty($balance)) {
            $balance = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello'))}]/preceding::text()[normalize-space()][1][{$this->contains($this->t('pts'))}]", null, true, "/^([\d\,\s]+)\s*pts/i");
        }

        $st->setBalance(str_replace(',', '', $balance));

        $dateBalance = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Individual points balance as of'))}]", null, true, "/{$this->opt($this->t('Individual points balance as of'))}\s*(\w+.*\d{4})$/");

        if (!empty($dateBalance)) {
            $st->setBalanceDate($this->normalizeDate($dateBalance));
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
        return 0;
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), \"{$s}\")";
        }, $field));
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function assignLang()
    {
        foreach ($this->detectLang as $lang => $array) {
            if ($this->http->XPath->query("//text()[{$this->contains($array)}]")->length > 0) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function eq($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'normalize-space(.)="' . $s . '"'; }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^(\d+\s*\w+\s*\d{4})$#u", //17 octobre 2024
        ];
        $out = [
            "$1",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }
}
