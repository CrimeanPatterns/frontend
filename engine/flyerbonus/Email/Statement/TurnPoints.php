<?php

namespace AwardWallet\Engine\flyerbonus\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class TurnPoints extends \TAccountChecker
{
	public $mailFiles = "flyerbonus/statements/it-903172392.eml";
    public $subjects = [
        '/Turn Points into Memorable Journeys/',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@bangkokair.com') !== false) {
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
        return $this->http->XPath->query("//*[contains(normalize-space(), 'flyerbonus@bangkokair.com')]")->length > 0
            && $this->http->XPath->query("//img[contains(@src, 'flyerbonus.bangkokair.com')]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]bangkokair\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Hello!'))}]/following::text()[normalize-space()][1]", null, true, "/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])$/");

        if (!empty($name)) {
            $st->addProperty('Name', preg_replace("/^((?:MR|MRS|MSTR|DR)[ ]*\.)[ ]*/u", "", $name));
        }

        $number = $this->http->FindSingleNode("//text()[{$this->eq($this->t('ID:'))}]/following::text()[normalize-space()][1]", null, true, "/^(\d+)$/");

        if (!empty($number)) {
            $st->setNumber($number);
        }

        $status = $this->http->FindSingleNode("//text()[{$this->eq($this->t('ID:'))}]/preceding::text()[normalize-space()][not({$this->eq($this->t('|'))})][./preceding::text()[normalize-space()][1][{$this->eq($this->t('|'))}]][1]", null, true, "/^(\S+)$/");

        if (!empty($status)) {
            $st->addProperty('TierLevel', $status);
        }

        $quailifyingPoints = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Quailifying Points Required toward Premier Level'))}]/following::text()[normalize-space()][not({$this->eq($this->t(':'))})][1]", null, true, "/^([0-9\,\.\s]+)$/");

        if (!empty($quailifyingPoints)) {
            $st->addProperty('QualifyingPoints', $quailifyingPoints);
        }

        $balanceNode = $this->http->FindSingleNode("//td[{$this->starts($this->t('Points Balance on'))}][1]");

        if (preg_match("/^{$this->opt($this->t('Points Balance on'))}[ ]*(?<date>\d{1,2}[ ]*\w+[ ]*\d{4})[ ]*\:[ ]*(?<balance>[0-9\,\.\s]+)$/", $balanceNode, $m)){
            $st->setBalance($m['balance'])
                ->setBalanceDate(strtotime($m['date']));
        }

        $expiringNode = $this->http->FindSingleNode("//td[{$this->starts($this->t('Points Expiring on'))}][1]");

        if (preg_match("/^{$this->opt($this->t('Points Expiring on'))}[ ]*(?<date>\d{1,2}[ ]*\w+[ ]*\d{4})[ ]*\:[ ]*(?<balance>[0-9\,\.\s]+)$/", $expiringNode, $m)){
            $st->addProperty('ExpiringBalance', $m['balance'])
                ->setExpirationDate(strtotime($m['date']));
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

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field));
    }
}
