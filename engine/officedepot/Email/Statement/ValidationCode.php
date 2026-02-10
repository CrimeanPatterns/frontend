<?php

namespace AwardWallet\Engine\officedepot\Email\Statement;

// TODO: delete what not use
use AwardWallet\Schema\Parser\Email\Email;

class ValidationCode extends \TAccountChecker
{
    public $mailFiles = "officedepot/it-903523332.eml";

    public $lang;
    public static $dictionary = [
        'en' => [
            'One-Time Validation Code' => 'One-Time Validation Code',
        ],
    ];

    private $detectFrom = "noreply@officedepot.com";
    private $detectSubject = [
        // en
        'Office Depot Validation Code',
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]officedepot\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], 'Office Depot') === false
        ) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//img/@src[{$this->contains(['officedepot'])}]")->length === 0
            && $this->detectEmailByHeaders($parser->getHeaders()) === false
        ) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['One-Time Validation Code']) && $this->http->XPath->query("//*[{$this->contains($dict['One-Time Validation Code'])}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $otc = $email->add()->oneTimeCode();

        $code = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Your one-time validation code is'))}]/following::text()[normalize-space()][1]",
            null, true, "/^\s*(\d{6})\s*$/");
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

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    // additional methods
    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function opt($field, $delimiter = '/')
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
            return str_replace(' ', '\s+', preg_quote($s, $delimiter));
        }, $field)) . ')';
    }
}
