<?php

namespace AwardWallet\Engine\ana\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class YourMileageBalance2025 extends \TAccountChecker
{
	public $mailFiles = "ana/statements/it-907981973.eml";

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]ana\.co\.jp/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (self::detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        return stripos($headers['subject'], 'Your mileage balance') !== false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if (self::detectEmailFromProvider($parser->getHeader('from')) !== true
            && $this->http->XPath->query('//a[contains(@href,".ana.co.jp/") or contains(@href,"www.ana.co.jp") or contains(@href,"amc.ana.co.jp")]')->length === 0
            && $this->http->XPath->query('//img[contains(normalize-space(@alt),"ANA. All Rights Reserved")]')->length === 0
        ) {
            return false;
        }

        return $this->http->XPath->query('//img[contains(normalize-space(@alt),"Mileage Updates") or contains(normalize-space(@alt),"ANA Mileage Information")]')->length > 0;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode('//tr[not(.//tr) and starts-with(normalize-space(),"Dear ")]', null, true, '/^Dear \s*([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])(?:\s*[,;!?]|$)/iu');
        $st->addProperty('Name', $name);

        $miles = $this->http->FindSingleNode("//td[{$this->eq($this->t('Mileage Balance'))}]/following-sibling::td[./following-sibling::td[{$this->eq($this->t('miles'))}]][1]", null, true, '/^\d[,.\'\d ]*$/');
        $st->setBalance($this->normalizeAmount($miles));

        $points = $this->http->FindSingleNode("//td[{$this->eq($this->t('Premium Points'))}]/following-sibling::td[./following-sibling::td[{$this->eq($this->t('points'))}]][1]", null, true, '/^\d[,.\'\d ]*$/');
        $st->addProperty('TotalPremiumPoints', $points);

        $balanceDate = $this->http->FindSingleNode("//td[{$this->starts($this->t('As of'))}][1]", null, false, "/^{$this->opt($this->t('As of'))}[ ]*(\w+[ ]*[0-9]{1,2}\,[ ]*\d{4})[ ]*\(/u");

        if ($balanceDate !== null){
            $st->setBalanceDate(strtotime($balanceDate));
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

    /**
     * Formatting over 3 steps:
     * 11 507.00  ->  11507.00
     * 2,790      ->  2790    |    4.100,00  ->  4100,00    |    1'619.40  ->  1619.40
     * 18800,00   ->  18800.00  |  2777,0    ->  2777.0.
     *
     * @param string|null $s Unformatted string with amount
     * @param string|null $decimals Symbols floating-point when non-standard decimals (example: 1,258.943)
     */
    private function normalizeAmount(?string $s, ?string $decimals = null): ?float
    {
        if (!empty($decimals) && preg_match_all('/(?:\d+|' . preg_quote($decimals, '/') . ')/', $s, $m)) {
            $s = implode('', $m[0]);

            if ($decimals !== '.') {
                $s = str_replace($decimals, '.', $s);
            }
        } else {
            $s = preg_replace('/\s+/', '', $s);
            $s = preg_replace('/[,.\'](\d{3})/', '$1', $s);
            $s = preg_replace('/,(\d{1,2})$/', '.$1', $s);
        }

        return is_numeric($s) ? (float) $s : null;
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
