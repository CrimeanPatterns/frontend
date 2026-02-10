<?php

namespace AwardWallet\Engine\virgin\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Code extends \TAccountChecker
{
    public $mailFiles = "virgin/statements/it-2.eml, virgin/statements/it-900502776.eml";
    public $subjects = [
        "Virgin Atlantic account email verification code",
        "Your verification code is",
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'Thanks for verifying your' => ['Thanks for verifying your', 'Here\'s the code to set up your Flying Club account'],
            'Your code is:'             => ['Your code is:', 'Here\'s the code to set up your Flying Club account:'], // set up your Flying Club account - is not error
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'flyingclub@service.virginatlantic.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Virgin Atlantic'))}]")->length > 0) {
            return $this->http->XPath->query("//text()[{$this->contains($this->t('Thanks for verifying your'))}]")->length > 0;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]virginatlantic\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $otc = $email->add()->oneTimeCode();
        $code = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your code is:'))}]", null, true, "/^{$this->opt($this->t('Your code is:'))}\s*(\d+)$/");

        if (empty($code)) {
            $code = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Your code is:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d+)\s*$/");
        }
        $otc->setCode($code);

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

        return '(' . implode(" or ", array_map(function ($s) {
            return "normalize-space(.)=\"{$s}\"";
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
