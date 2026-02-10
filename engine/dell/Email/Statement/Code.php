<?php

namespace AwardWallet\Engine\dell\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Code extends \TAccountChecker
{
    public $mailFiles = "dell/statements/it-688969612.eml, dell/statements/it-693991937.eml, dell/statements/it-905003019.eml";
    public $subjects = [
        'Dell One-time Password',
        'Email OTP',
        // de
        'Dell Einmal-Passcode',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'Your one-time password for completing access request is:' => [
                'Your one-time password for completing access request is:',
                'Here is the One Time Passcode (OTP) for completing your access request',
            ],
            'OTP:' => 'OTP:',
            'Hi '  => 'Hi ',
        ],
        "de" => [
            'Your one-time password for completing access request is:' => [
                'Ihr Einmalkennwort zum Abschließen der Zugriffsanforderung lautet:',
            ],
            // 'OTP:' => 'OTP:',
            'Hi ' => 'Guten Tag,',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@dell.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains(['Dell Technologies', '@dell.com'])}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains(['.dell.com'])}]")->length === 0
            && stripos($parser->getCleanFrom(), '@dell.com') === false
        ) {
            return false;
        }

        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Your one-time password for completing access request is:'])
                && $this->http->XPath->query("//text()[{$this->contains($dict['Your one-time password for completing access request is:'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]dell\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByBody($parser) === false) {
            return $email;
        }

        $code = $email->add()->oneTimeCode();
        $codeNumber = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Your one-time password for completing access request is:'))}]/following::text()[normalize-space()][1]",
            null, true, "/^\s*(?:{$this->opt($this->t('OTP:'))}\s*)?(\d{6})\s*$/");

        if (!empty($codeNumber)) {
            $code->setCode($codeNumber);

            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hi '))}]", null, true, "/^{$this->opt($this->t('Hi '))}(\D+)\,$/");

            if (!empty($name)) {
                $st = $email->add()->statement();
                $st->addProperty('Name', trim($name, ','));
                $st->setNoBalance(true);
            }
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'normalize-space(.)="' . $s . '"'; }, $field)) . ')';
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
}
