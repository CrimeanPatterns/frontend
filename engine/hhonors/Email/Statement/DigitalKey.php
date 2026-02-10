<?php

namespace AwardWallet\Engine\hhonors\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class DigitalKey extends \TAccountChecker
{
    public $mailFiles = "hhonors/it-890981127.eml, hhonors/statements/it-895152236.eml, hhonors/statements/it-895382147.eml";
    public $subjects = [
        'You shared your Digital Key at',
        'Your Digital Key share has been Accepted',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'This email advertisement was delivered to' => ['This email advertisement was delivered to', 'This email was delivered to'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@h6.hilton.com') !== false) {
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
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Hilton Honors')]")->length === 0) {
            return false;
        }

        //it-895152236.eml
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('We are processing your request to share the Digital Key for your stay at'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Once accepted, you can revoke shared keys directly within the Hilton Honors app'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Thank you for choosing Digital Key over plastic!'))}]")->length > 0) {
            return true;
        }
        //it-895382147.eml
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your shared Digital Key has been accepted and can now be used by'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('If you did not intend to share your Digital Key'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Thank you for choosing Digital Key over plastic!'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.](?:h1|h6)\.hilton\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $login = $this->http->FindSingleNode("//img[contains(@alt, 'explore')]/@src", null, true, "/(?:customerid|mi_u)[=](\d{8,})/");

        if (!empty($login)) {
            $st = $email->add()->statement();

            $st->setLogin($login)
                ->setNumber($login)
                ->setNoBalance(true);

            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hi '))}]", null, true, "/^{$this->opt($this->t('Hi '))}\s*(\D+)\,$/");

            if (!empty($name)) {
                $st->addProperty('Name', trim($name, ','));
            }

            $email->setUserEmail($this->http->FindSingleNode("//text()[{$this->starts($this->t('This email advertisement was delivered to'))}]/following::text()[normalize-space()][1]", null, true, "/^(\S+[@]\S+)$/"));
        } else {
            //it-890981127.eml
            $link = $this->http->FindSingleNode("//img[contains(@alt, 'explore')]/@src");

            if (preg_match("/\.[a-z]{3}[?]mi_u[=][&]mi_language[=][A-Z]{2}/", $link) && $this->detectEmailByBody($parser) === true) {
                $email->setIsJunk(true);
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }
}
