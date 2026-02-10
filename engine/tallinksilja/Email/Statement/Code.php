<?php

namespace AwardWallet\Engine\tallinksilja\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Code extends \TAccountChecker
{
    public $mailFiles = "tallinksilja/statements/it-900956747.eml";
    public $subjects = [
        'Your verification code:',
        'Copy your verification code',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@tallink.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Tallink Silja Line'))}]")->length > 0) {
            return $this->http->XPath->query("//text()[{$this->contains($this->t('verification code is'))}]")->length > 0
                 && $this->http->XPath->query("//text()[{$this->contains($this->t('Copy and paste the code to complete your sign-in'))}]")->length > 0;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]info\.cinemark\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hi '))}]", null, true, "/^{$this->opt($this->t('Hi '))}(\D+)\,/");

        if (!empty($name)) {
            $st->addProperty('Name', trim($name, ','));
            $st->setNoBalance(true);
        }

        $code = $email->add()->oneTimeCode();
        $code->setCode($this->http->FindSingleNode("//text()[contains(normalize-space(), 'verification code is')]", null, true, "/{$this->opt($this->t('verification code is'))}\s+(\d+)\./"));

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
