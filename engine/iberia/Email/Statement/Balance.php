<?php

namespace AwardWallet\Engine\iberia\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Balance extends \TAccountChecker
{
    public $mailFiles = "iberia/statements/it-904210327.eml, iberia/statements/it-904210351.eml";
    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Iberia Líneas Aéreas')]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains(['| Balance', '| Saldo', '| Stand', '| Solde'])}]/ancestor::tr[1][contains(normalize-space(), 'IB')]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/^iberiaplus[@]comunicaciones\.iberia\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $info = $this->http->FindSingleNode("//text()[{$this->contains(['| Balance', '| Saldo', '| Stand', '| Solde'])}]/ancestor::tr[1][contains(normalize-space(), 'IB')]");

        if (preg_match("/^(?<pax>[[:alpha:]][-.\'’\–[:alpha:] ]*[[:alpha:]])\s+IB\s+(?<number>\d{5,})\s*(?<level>\w+)[\s\|]+(?:Balance|Saldo|Stand|Solde)\s*(?<balance>[\d\,\.]+)\s*Avios$/u", $info, $m)) {
            $st = $email->add()->statement();

            $st->addProperty('Name', trim($m['pax'], ','));

            $st->addProperty('Level', trim($m['level'], ','));

            $st->setNumber($m['number'])
                ->setBalance($m['balance']);

            $elitePoints = $this->http->FindSingleNode("//text()[normalize-space() = 'Elite Points']/ancestor::tr[1]/descendant::td[2]", null, true, "/^([\d\,]+)$/");

            if ($elitePoints !== null) {
                $st->addProperty('ElitePoints', $elitePoints);
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
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }
}
