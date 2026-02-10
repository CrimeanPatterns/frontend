<?php

namespace AwardWallet\Engine\delta\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class EarnedMedallionStatus extends \TAccountChecker
{
    public $mailFiles = "delta/statements/it-69942210.eml, delta/statements/it-916826999.eml, delta/statements/it-918698613.eml, delta/statements/it-919431704.eml";
    public $subjects = [
        '/Congratulations\! You\'ve Earned \w+ Medallion Status$/',
        '/Welcome to \w+ Medallion® Status$/',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'It\'s time to celebrate! Welcome to' => [
                'It\'s time to celebrate! Welcome to',
                'It’s time to celebrate! Welcome to',
                'It’s time to celebrate! Welcome back to',
            ],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@t.delta.com') !== false) {
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
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Delta Air Lines')]")->length === 0) {
            return false;
        }

        //it-69942210.eml
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Congratulations on successfully completing the SkyMiles Medallion'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Medallion Status will continue until'))}]")->length > 0) {
            return true;
        }

        //it-919431704.eml
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('It\'s time to celebrate! Welcome to'))}]")->length > 0
            && ($this->http->XPath->query("//text()[{$this->contains($this->t('EXPLORE MY BENEFITS'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t('LEARN MORE'))}]")->length > 0)) {
            return true;
        }

        //it-918698613.eml
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Congratulations on Earning'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('You successfully completed the SkyMiles Medallion Status'))}]")->length > 0) {
            return true;
        }

        //it-916826999.eml
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('We know choosing an airline is personal and we are excited to see you\'re interested in Delta'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('This means you can experience the Medallion difference until'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]t\.delta\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $node = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Congratulations on successfully')]/preceding::text()[contains(normalize-space(), 'Medallion')][1]/ancestor::tr[1]");

        if (empty($node)) {
            $node = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), '#')]/ancestor::td[1][contains(normalize-space(), 'Medallion')]");
        }

        if (preg_match('/[#]\s*(\d+)\s+\|\s*(\w+\s+Medallion)\s*®?\s*\|?/isu', $node, $m)) {
            $st->setNumber($m[1]);
            $st->addProperty('Level', $m[2]); // Status
        }

        $st->setNoBalance(true);

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
}
