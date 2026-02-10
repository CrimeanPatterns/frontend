<?php

namespace AwardWallet\Engine\omnihotels\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class AccountSummary extends \TAccountChecker
{
    public $mailFiles = "omnihotels/statements/it-924304053.eml, omnihotels/statements/it-924578278.eml";
    public $lang = 'en';

    private static $dictionary = [
        'en' => [
        ],
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $patterns = [
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $st = $email->add()->statement();

        $travellerName = $this->http->FindSingleNode("//text()[{$this->contains($this->t('’s Account'))}]", null, false, "/^(?:Dr|Mr|Mrs|Ms)?\.?[ ]*({$patterns['travellerName']})[ ]*{$this->opt($this->t('’s Account'))}$/u");

        if (!empty($travellerName)) {
            $st->addProperty('Name', $travellerName);
        }

        $level = $this->http->FindSingleNode("//td[{$this->eq($this->t('TIER LEVEL:'))}]/following-sibling::td[normalize-space()][1]", null, true, "/^(\w+)$/");

        if (!empty($level)) {
            $st->addProperty('Level', $level);
        }

        $number = $this->http->FindSingleNode("//text()[{$this->eq($this->t('SELECT GUEST #'))}]/following::text()[normalize-space()][1]", null, true, "/([A-Z\d]{5,})$/");

        if (!empty($number)) {
            $st->setNumber($number);
        }

        /*$nightsNextLevel = $this->http->FindSingleNode("//tr[{$this->eq($this->t('Nights to Next Level'))}][1]/following-sibling::tr[normalize-space()][1]", null, true, "/^\d+$/");

        if ($nightsNextLevel !== null) {
            $st->addProperty('NightsNextLevel', $nightsNextLevel);
        }*/

        $balance = $this->http->FindSingleNode("//tr[{$this->eq($this->t('Award Credits Earned'))}][1]/following-sibling::tr[normalize-space()][1]", null, true, "/^\d+$/");

        if ($balance !== null) {
            $st->setBalance($balance);
        }

        $login = $this->http->FindSingleNode("//text()[{$this->starts($this->t('This email was sent to'))}][1]", null, false, "/^{$this->opt($this->t('This email was sent to'))}[ ]*(.+\@.+\.[A-z]+)$/u");

        if (!empty($login)) {
            $st->setLogin($login);
        }

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]omnihotels\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        return stripos($headers['subject'], 'Your Select Guest Member Account Summary and Offers') !== false
            || stripos($headers['subject'], 'Your Select Guest Member Account Summary') !== false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider(rtrim($parser->getHeader('from'), '> ')) !== true
            && $this->http->XPath->query('//*[contains(normalize-space(),"Omni Hotels")]')->length === 0
        ) {
            return false;
        }

        return $this->http->XPath->query("//*[{$this->contains($this->t('Award Credits Earned'))}]")->length > 0
            && $this->http->XPath->query("//*[{$this->contains($this->t('Nights to Next Level'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('TIER LEVEL'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('SELECT GUEST #'))} ]")->length > 0;
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'contains(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function starts($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field));
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }
}
