<?php

namespace AwardWallet\Engine\turkish\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Notification extends \TAccountChecker
{
    public $mailFiles = "turkish/statements/it-919307913.eml";
    public $subjects = [
        'Cyber Security Advices',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'milesandsmiles@milesandsmiles.turkishairlines.com') !== false) {
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
        return $this->http->XPath->query("//text()[contains(normalize-space(), 'TURKISH AIRLINES HEADQUARTER')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('CYBER SECURITY MEASURES'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('We care about your your account security and privacy'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/^milesandsmiles[@]milesandsmiles\.turkishairlines\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByBody($parser) === true) {
            $st = $email->add()->statement();
            $st->setMembership(true);
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
