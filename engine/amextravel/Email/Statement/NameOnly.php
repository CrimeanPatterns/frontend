<?php

namespace AwardWallet\Engine\amextravel\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class NameOnly extends \TAccountChecker
{
    public $mailFiles = "amextravel/statements/it-100804696.eml, amextravel/statements/it-914304067.eml";
    public $subjects = [
        '/Your one\-time passcode to verify your identity/',
        '/Your Authentication One\-Time Password/',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'Hello,' => ['Hello,', 'Dear'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && (stripos($headers['from'], '@welcome.aexp.com') !== false || stripos($headers['from'], '@americanexpress.com') !== false)) {
            foreach ($this->subjects as $subject) {
                if (preg_match($subject, $headers['subject'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'American Express')]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your Two-Factor Authentication OTP for accessing your Online Services account is'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('and is valid for'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('For further information, please contact Customer Services'))}]")->length > 0) {
            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Security Verification'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('one-time passcode'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.](?:welcome\.aexp\.com|americanexpress\.com(?:\.[a-z]+)?)$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello,'))}]", null, true, "/^{$this->opt($this->t('Hello,'))}\s+(\D+)$/");

        if (!empty($name)) {
            $st = $email->add()->statement();
            $st->addProperty('Name', trim($name, ','));
            $st->setNoBalance(true);
        }

        $code = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Your Two-Factor Authentication OTP for accessing your Online Services account is'))}]",
            null, true, "/{$this->opt($this->t('Your Two-Factor Authentication OTP for accessing your Online Services account is'))}\s*(\d{6})\,/");

        if (empty($code)) {
            $code = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'To verify your identity, please use the below one-time passcode')]/following::text()[string-length()>5][1]",
            null, true, "/^(\d{6})$/");
        }

        if (!empty($code)) {
            $otc = $email->add()->oneTimeCode();
            $otc->setCode($code);
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
            return str_replace(' ', '\s+', preg_quote($s));
        }, $field)) . ')';
    }
}
