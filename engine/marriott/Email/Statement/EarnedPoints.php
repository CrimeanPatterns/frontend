<?php

namespace AwardWallet\Engine\marriott\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class EarnedPoints extends \TAccountChecker
{
    public $mailFiles = "marriott/statements/it-905424483.eml";
    public $subjects = [
        '/^Confirmation\:\s*You Earned Points$/',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@email-marriott.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Marriott Bonvoy program'))}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//a[normalize-space()='My Account']")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('You’ve earned'))} and {$this->contains($this->t('points at'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('If you think you’ve received this email in error, please contact Marriott Bonvoy'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Marriott Bonvoy upholds high business standards and practices to secure customer information'))}]")->length > 0
            && $this->http->XPath->query("//a[normalize-space()='Log in']")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]email\-marriott\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByBody($parser) === true) {
            $st = $email->add()->statement();

            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello'))}]", null, true, "/^{$this->opt($this->t('Hello'))}\s+(\w+)\,$/");

            if (!empty($name)) {
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }
}
