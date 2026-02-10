<?php

namespace AwardWallet\Engine\delta\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class YourMenuFlight extends \TAccountChecker
{
    public $mailFiles = "delta/statements/it-919844984.eml, delta/statements/it-921571474.eml";
    public $subjects = [
        'Your Menu For Your Delta Flight Has Landed',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'deltaairlines@t.delta.com') !== false) {
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
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Delta Air Lines')]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Savor your time in the air by selecting your preferred entrée to enjoy on your upcoming Delta-operated flight to'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('View the menu and'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('SELECT YOUR ENTRÉE'))}]")->length > 0) {
            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Verification Update'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('We’ve verified your Government Forms'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('FLIGHT DATE'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/^deltaairlines[@]t\.delta\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByBody($parser) === true) {
            $st = $email->add()->statement();

            $number = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), '#')]", null, true, "/^[#](\d{5,})$/");

            if (!empty($number)) {
                $st->setNumber($number);
            }

            $name = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Confirmation Number'))}]/ancestor::tr[1]/descendant::text()[normalize-space()][1]/ancestor::td[1]", null, true, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/su");
            $this->logger->error($name);

            if (!empty($name)) {
                $st->addProperty('Name', trim($name, ','));
            }

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

    private function eq($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'normalize-space(.)="' . $s . '"'; }, $field)) . ')';
    }
}
