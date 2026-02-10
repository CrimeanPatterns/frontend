<?php

namespace AwardWallet\Engine\delta\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Code extends \TAccountChecker
{
    public $mailFiles = "delta/statements/it-921900476.eml";

    public $subjects = [
        'Your SkyMiles® Account Verification Code',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@delta.com') !== false) {
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
        return $this->http->XPath->query("//text()[contains(normalize-space(), 'Delta Air Lines')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t("Use this code to verify your SkyMiles account"))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Thank you for being a loyal SkyMiles Member'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]@delta\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $code = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'Use this code to verify your SkyMiles account')]/preceding::text()[normalize-space()][1]", null, true, "/^(\d{6})$/");

        if (!empty($code)) {
            $oc = $email->add()->oneTimeCode();
            $oc->setCode($code);
        }

        $name = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Hello')]", null, true, "/Hello\s*(.+)/");

        if (!empty($name)) {
            $st = $email->add()->statement();
            $st->addProperty('Name', trim(preg_replace("/^(?:Mr\.|Mrs\.|Ms\.|MRS|MR|MS)/", "", $name), ','));
            $st->setNoBalance(true);
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
