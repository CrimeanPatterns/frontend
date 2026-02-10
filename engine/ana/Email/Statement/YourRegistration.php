<?php

namespace AwardWallet\Engine\ana\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class YourRegistration extends \TAccountChecker
{
	public $mailFiles = "ana/statements/it-908974375.eml";

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]ana\.co\.jp/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (self::detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        return stripos($headers['subject'], 'Your Registration Confirmation from ANA Mileage Club') !== false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if (self::detectEmailFromProvider($parser->getHeader('from')) !== true
            && $this->http->XPath->query('//*[contains(normalize-space(),".ana.co.jp/") or contains(normalize-space(),"www.ana.co.jp") or contains(normalize-space(),"amc.ana.co.jp")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"ANA Mileage Club")]')->length === 0
        ) {
            return false;
        }

        return true;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $text = $parser->getBody();

        $st = $email->add()->statement();

        $name = $this->re("/{$this->opt($this->t('Dear'))}[ ]*([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])[ ]*[\,\.\!\?]*\n/u", $text);
        $st->addProperty('Name', $name);

        $number = $this->re("/{$this->opt($this->t('ANA Number'))}[ ]*\:[ ]*([0-9]+)[ ]*\n/u", $text);
        $st->setNumber($number);

        $st->setNoBalance(true);

        return $email;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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
                return str_replace(' ', '\s+', preg_quote($s));
            }, $field)) . ')';
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
}
