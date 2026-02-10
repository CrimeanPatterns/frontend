<?php

namespace AwardWallet\Engine\airchina\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class AccountStatement extends \TAccountChecker
{
    public $mailFiles = "airchina/statements/it-910651419.eml";
    public $subjects = [
        'Your PhoenixMiles Account Statement',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'ffpbill@enewsletter.airchina.com.cn') !== false) {
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
        return $this->http->XPath->query("//text()[contains(normalize-space(), 'PhoenixMiles')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Air China Group'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('In order to credit the mileages to your account automatically'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/ffpbill[@]enewsletter\.airchina\.com\.cn$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $number = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'No.')]", null, true, "/{$this->opt($this->t('No.'))}\s*(CA\d{8,})$/");

        if (!empty($number)) {
            $st = $email->add()->statement();
            $st->setNumber($number)
                ->setNoBalance(true);
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }
}
