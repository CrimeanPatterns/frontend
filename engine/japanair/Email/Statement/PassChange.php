<?php

namespace AwardWallet\Engine\japanair\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class PassChange extends \TAccountChecker
{
	public $mailFiles = "japanair/statements/it-912240835.eml, japanair/statements/it-912303976.eml";

    public $detectFrom = ["/jmb\_confirmation\@jal\.com/u", "/.+\@jal\.com/u"];
    public $detectSubject = [
        // en
        'Notification of completion of JAL Mileage Bank password registration/change procedures',
        // ja
        '【JALファミリークラブ】お申込ありがとうございました',
    ];

    public $lang;
    public static $dictionary = [
        'en' => [
            'Your password has been changed.' => ['Your password has been changed.', 'Thank you for joining the JAL Family Club.'],
            'JAL' => ['JAL Group', 'JAL Mileage Bank', 'Japan Airlines'],
        ],
        'ja' => [
            'Your password has been changed.' => ['JALファミリークラブへのお申し込みありがとうございました。'],
            'JAL' => ['JALファミリークラブ'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        foreach ($this->detectFrom as $mail){
            if (preg_match($mail, $from)) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//*[{$this->contains(['JAL Group', 'JAL Mileage Bank', 'Japan Airlines', 'JAL FAMILY CLUB'])}]")->length === 0
        ) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['JAL'])
                && !empty($dict['Your password has been changed.'])
                && $this->http->XPath->query("//*[{$this->contains($dict['JAL'])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->contains($dict['Your password has been changed.'])}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Your password has been changed.'])
                && $this->http->XPath->query("//*[{$this->contains($dict['Your password has been changed.'])}]")->length > 0
            ) {
                $this->lang = $lang;

                break;
            }
        }

        if ($this->detectEmailByBody($parser) === true){
            $st = $email->add()->statement();

            $st->setMembership(true)
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
                return preg_quote($s, '/');
            }, $field)) . ')';
    }
}
