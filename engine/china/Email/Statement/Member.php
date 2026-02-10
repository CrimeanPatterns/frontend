<?php

namespace AwardWallet\Engine\china\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Member extends \TAccountChecker
{
	public $mailFiles = "china/statements/it-909160661.eml";
    public $subjects = [
        // en
        "China Airlines DFP member's card number advice",
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'searchPhrase'   => 'Your DFP Membership card number is',
            'searchPhrase2'   => 'Thank you for joining Dynasty Flyer Program',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@email.china-airlines.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $this->assignLang();

        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'China Airlines') or contains(normalize-space(), '中華航空')]" )->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('searchPhrase'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('searchPhrase2'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]email\.china\-airlines\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        $st = $email->add()->statement();

        $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('searchPhrase'))}]", null, true, "/{$this->opt($this->t('searchPhrase'))}\s*([A-Z\d]+)[\.\。]/u");

        $st->setNumber($number)->setNoBalance(true);

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

    private function assignLang(): bool
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['searchPhrase']) || empty($phrases['searchPhrase2'])) {
                continue;
            }

            if ($this->http->XPath->query("//node()[{$this->contains($phrases['searchPhrase'])}]")->length > 0
                && $this->http->XPath->query("//node()[{$this->contains($phrases['searchPhrase2'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }
}
