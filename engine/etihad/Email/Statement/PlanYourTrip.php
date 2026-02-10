<?php

namespace AwardWallet\Engine\etihad\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class PlanYourTrip extends \TAccountChecker
{
	public $mailFiles = "etihad/statements/it-911878109.eml";
    public $from = '/\@choose\.etihad\.com/i';

    public $lang = 'en';
    private static $dictionary = [
        'en' => [ ],
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hi'))}]/preceding::tr[count(./child::td) = 2][./descendant::img[contains(@src, 'EtihadAirways_Logo')]][1]/child::td[2]/descendant::text()[normalize-space()][2]", null, true, "/^(\d[\d\-]+)$/");

        if (!empty($number)) {
            $st = $email->add()->statement();

            $st->setNoBalance(true);

            $st->setNumber($number)
                ->setLogin($number);

            $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hi'))}]/preceding::tr[count(./child::td) = 2][./descendant::img[contains(@src, 'EtihadAirways_Logo')]][1]/child::td[2]/descendant::text()[normalize-space()][1]", null, true, "/^([\w\s]+)$/");

            if ($status !== null){
                $st->addProperty('TierLevel', $status);
            }

            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hi'))}]", null, true, "/^{$this->opt($this->t('Hi'))}[ ]+(.+)$/u");

            if (!preg_match("/^(Guest|guest)$/u", $name)){
                $st->addProperty('Name', $name);
            }
        }

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]choose\.etihad\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return isset($headers['from']) && is_string($headers['from']) && preg_match($this->from, $headers['from']);
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        return $this->http->XPath->query("//text()[{$this->contains($this->t('Etihad Airways'))}]")->count() > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Plan your trip'))}]")->count() > 0;
    }

    public static function getEmailLanguages()
    {
        return ['en'];
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

        return implode(' or ', array_map(function ($s) {
            return 'contains(normalize-space(.),"' . $s . '")';
        }, $field));
    }

    private function eq($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                return 'normalize-space(' . $node . ")='" . $s . "'";
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
