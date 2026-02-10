<?php

namespace AwardWallet\Engine\saudisrabianairlin\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Discount extends \TAccountChecker
{
    public $mailFiles = "saudisrabianairlin/statements/it-541480045.eml, saudisrabianairlin/statements/it-919418907.eml, saudisrabianairlin/statements/it-920974725.eml, saudisrabianairlin/statements/it-921565609.eml";
    public $subjects = [
        '% off with',
        'HH invite confirmation',
        'SAUDIA mega sale: up to',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'Membership Number:' => 'Membership Number:',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'saudia.com') !== false) {
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
        if ($this->http->XPath->query("//*[{$this->contains(['AlFursan', 'Saudia Group', 'ALFURSAN'])}]")->length > 0
            || $this->http->XPath->query("//a[contains(@href, '.saudia.com')]")->length > 0) {
            foreach (self::$dictionary as $lang => $dict) {
                if (!empty($dict['Membership Number:']) && $this->http->XPath->query("//text()[{$this->contains($dict['Membership Number:'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]saudia\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByBody($parser) == true) {
            $st = $email->add()->statement();

            $number = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Membership Number:'))}]/ancestor::tr[normalize-space()][1]", null, false, "/^{$this->opt($this->t('Membership Number:'))}\s*([0-9\-]+)$/u");

            $st->setLogin($number)
                ->setNumber($number)
                ->setNoBalance(true);

            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]/ancestor::*[normalize-space()][1]", null, false, "/^{$this->opt($this->t('Dear'))}[\,\s]+([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])(?:[\.\,\?\!\s]|$)/u");

            if ($name !== null){
                $st->addProperty('Name', $name);
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

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), '{$s}')";
        }, $field));
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return "normalize-space(.)='" . $s . "'"; }, $field)) . ')';
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "contains(normalize-space(.), '{$s}')";
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
