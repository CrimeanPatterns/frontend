<?php

namespace AwardWallet\Engine\dell\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class YourOrder extends \TAccountChecker
{
    public $mailFiles = "dell/statements/it-65460240.eml, dell/statements/it-65463992.eml";
    public $subjects = [
        'Dell Order Has Been Confirmed for Order Number:',
        'Your Dell Order Has Been Confirmed|Dell Purchase ID:',
        'Confirming your order | Dell Purchase ID:',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'Order Date:'    => ['Order Date:', 'Dell Purchase ID', 'Order Number'],
            'Customer Name:' => ['Customer Name:', 'Customer:'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from'])
            && (stripos($headers['from'], '@dell.com') !== false || stripos($headers['from'], '.dell.com') !== false)
        ) {
            foreach ($this->subjects as $subject) {
                if (strpos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        return $this->http->XPath->query("//text()[contains(normalize-space(), 'Dell')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Order Date:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Customer Number:'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]dell\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $st->setNoBalance(true);

        $number = $this->http->FindSingleNode("//node()[{$this->eq($this->t('Customer Number:'))}]/following::text()[normalize-space()][1]");
        $st->setNumber($number);

        $name = $this->http->FindSingleNode("//node()[{$this->eq($this->t('Customer Name:'))}]/following::text()[normalize-space()][1]");

        if (!empty($name)) {
            $st->addProperty('Name', trim($name));
        }

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

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'normalize-space(.)="' . $s . '"'; }, $field)) . ')';
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
