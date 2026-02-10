<?php

namespace AwardWallet\Engine\cvs\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class NameOnly extends \TAccountChecker
{
    public $mailFiles = "cvs/statements/it-71088741.eml, cvs/statements/it-901621887.eml";
    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'My Account' => ['My Account', 'My account'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('CVS Pharmacy, Inc.'))}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains('cvs.com')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('cvs.com')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('My Account'))}]")->length > 0
            && $this->http->XPath->query("//*[{$this->starts($this->t('ExtraCare® Member'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Today You Saved'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Year to Date Savings'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Total ExtraCare savings this year'))}]")->length === 0
        ) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]cvs\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('ExtraCare'))}]/following::text()[{$this->starts($this->t('Member'))}])[1]", null, true, "/^\s*{$this->opt($this->t('Member'))}\:?\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/u");

        if (!empty($name)) {
            $st->addProperty('Name', trim($name, ','));
            $st->setNoBalance(true);
        } elseif (!empty($this->http->FindSingleNode("//text()[{$this->starts($this->t('Member'))}]/ancestor::*[{$this->starts($this->t('ExtraCare'))}][1]", null, true, "/^\s*ExtraCare\s*\S\s*{$this->opt($this->t('Member'))}/u"))) {
            $st->setMembership(true);
        }

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

        return implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), \"{$s}\")";
        }, $field));
    }

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
}
