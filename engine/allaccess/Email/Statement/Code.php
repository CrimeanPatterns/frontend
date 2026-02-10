<?php

namespace AwardWallet\Engine\allaccess\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Code extends \TAccountChecker
{
    public $mailFiles = "allaccess/statements/it-892248380.eml";
    public $subjects = [
        'Here\'s your security validation code',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@account.unitybyhardrock.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('HR Unity Global Services'))}]")->length > 0) {
            return $this->http->XPath->query("//text()[{$this->contains($this->t('to verify your identity, please use the following security validation code'))}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($this->t('Thanks for going through this extra step to help us keep your information secure'))}]")->length > 0;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]account\.unitybyhardrock\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $code = $this->http->FindSingleNode("//text()[{$this->contains($this->t('to verify your identity, please use the following security validation code'))}]/following::text()[string-length()>5][1]", null, true, "/^(\d{5,})$/");

        if (!empty($code)) {
            $c = $email->add()->oneTimeCode();
            $c->setCode($code);
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
}
