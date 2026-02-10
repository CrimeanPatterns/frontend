<?php

namespace AwardWallet\Engine\etihad\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class MonthStatement extends \TAccountChecker
{
    public $mailFiles = "etihad/statements/it-63689391.eml, etihad/statements/it-884041959.eml, etihad/statements/it-889988888.eml, etihad/statements/it-895415186.eml, etihad/statements/it-897403389.eml, etihad/statements/it-897941575.eml";
    public $reSubjects = [
        'en' => [
            '/^\s*(?:\[.+?\]\s*)?([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\,\s+here\’s your Etihad Guest \w+ e\-Statement\s*$/',
        ],
    ];
    public $lang = 'en';
    public $name = null;
    private static $dictionary = [
        'en' => [
            'As of'                => ['As of', 'as of'],
            'Guest Miles Balance:' => ['Guest Miles Balance:', 'Etihad Guest Miles', 'Miles balance:'],
            'Guest No:'            => ['Guest No:', 'Etihad Guest Number:'],
            'TierLevel'            => ['Bronze', 'Silver', 'Gold', 'Platinum', 'Emerald'],
            'Tier miles'           => ['Tier miles', 'Tier Miles'],
            'Tier segments'        => ['Tier segments', 'Tier Segments'],
            'View account details' => [
                'View account details',
                'View Account Details',
                'Your account details',
            ],
            'Your monthly mileage statement' => [
                'Your monthly mileage statement',
                'Your Monthly Mileage Statement',
                'Your monthly mileage statement period',
            ],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]choose\.etihadguest\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers['from'], 'etihadguest.com') === false) {
            return false;
        }

        // detect Format
        foreach ($this->reSubjects as $reSubjects) {
            foreach ($reSubjects as $reSubject) {
                if (preg_match($reSubject, $headers['subject'], $m)) {
                    $this->name = $m[1];

                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Etihad'))}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains('etihadguest.com')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('etihadguest.com')}]")->length === 0) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Your monthly mileage statement'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Guest Miles Balance:'))}]")->length > 0
            && $this->http->XPath->query("//a[{$this->eq($this->t('View account details'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        if (!empty($this->name)) {
            $st->addProperty('Name', $this->name);
        }

        // collect account info for it-63689391.eml, it-895415186.eml
        // it-63689391.eml
        $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Guest No:'))}]", null, true, "/^{$this->opt($this->t('Guest No:'))}\s*(\d+)$/")
            // and it-895415186.eml
            ?? $this->http->FindSingleNode("//text()[{$this->eq($this->t('Guest No:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d+)\s*$/");

        if (!empty($number)) {
            $st->setLogin($number)
                ->setNumber($number);
        }

        // it-63689391.eml
        $level = $this->http->FindSingleNode("//td/@background[{$this->contains($this->t('TierLevel'))}]", null, true, "/({$this->opt($this->t('TierLevel'))})/")
            ?? $this->http->FindSingleNode("//td/@style[{$this->contains($this->t('TierLevel'))}]", null, true, "/({$this->opt($this->t('TierLevel'))})/");

        if (empty($level)) {
            $nextLevel = $this->http->FindSingleNode("//text()[{$this->starts($this->t('On your way to'))}]", null, true, "/^\s*{$this->opt($this->t('On your way to'))}\s*(\w+)\s*{$this->opt($this->t('Status'))}\s*$/");
            $levelPos = array_search($nextLevel, $this->t('TierLevel')) - 1;

            if (array_key_exists($levelPos, $this->t('TierLevel'))) {
                $level = $this->t('TierLevel')[$levelPos];
            }
        }

        if (!empty($level)) {
            $st->addProperty('TierLevel', $level);
        }

        // collect account info for it-897941575.eml
        $xpathMainLogo = "{$this->eq($this->t('Etihad Guest'), '@alt')}";
        $accountText = implode("\n", $this->http->FindNodes("(//img[{$xpathMainLogo}])[1]/following::table[contains(translate(normalize-space(.),'0123456789','dddddddddd--'),'ddddddddd')][1]/descendant::text()[normalize-space()]"));

        /*
            Bronze
            500006153964
        */
        $accountPattern = "/^"
            . "\s*(?<level>\w+)[ ]*\n"
            . "[ ]*(?<number>\d{5,})\s*"
            . "$/u";

        if (preg_match($accountPattern, $accountText, $m)) {
            $st->setLogin($m['number'])
                ->setNumber($m['number'])
                ->addProperty('TierLevel', $m['level']);
        }

        // collect balance info
        // it-63689391.eml
        $balance = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Guest Miles Balance:'))}]/ancestor::td[1]", null, true, "/{$this->opt($this->t('Guest Miles Balance:'))}\s*(\d+)/")
            // it-897941575.eml, it-895415186.eml
            ?? $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Guest Miles Balance:'))}])[1]/following::text()[normalize-space()][1]", null, true, "/^\s*([\d\.\,\']+)\s*$/");

        if ($balance !== null) {
            $st->setBalance($this->normalizeAmount($balance));
        }

        $dateOfBalance = $this->http->FindSingleNode("//text()[{$this->starts($this->t('As of'))}]/ancestor::td[1]", null, true, "/{$this->opt($this->t('As of'))}\s*(\d+\s+\w+\s+\d{4})/");

        if (!empty($dateOfBalance)) {
            $st->setBalanceDate(strtotime($dateOfBalance));
        }

        // collect membership since for it-889988888.eml, it-895415186.eml
        $membershipDateEnd = strtotime($this->http->FindSingleNode("(//a[{$this->starts($this->t('View account details'))}]/following::text()[{$this->eq($this->t('Membership year end'))}])[1]/preceding::text()[normalize-space()][1]", null, true, "/^\s*(\d+\s+[^\d\s]+\s+\d{4})\s*$/"))
            ?? strtotime($this->http->FindSingleNode("(//a[{$this->starts($this->t('View account details'))}]/following::text()[{$this->eq($this->t('Days'))}])[1]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d+\s+[^\d\s]+\s+\d{4})\s*$/"));

        if (!empty($membershipDateEnd)) {
            $st->addProperty('Since', strtotime('-1 year', $membershipDateEnd));
        }

        // collect miles and segments to next level for it-63689391.eml, it-895415186.eml
        $tierMilesNeed = $this->http->FindSingleNode("(//a[{$this->starts($this->t('View account details'))}]/following::text()[{$this->eq($this->t('Tier miles'))}])[1]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d+)\s*$/");

        if (!empty($tierMilesNeed)) {
            $st->addProperty('TierMilesNeed', $this->normalizeAmount($tierMilesNeed));
        }

        $tierSegmentsNeed = $this->http->FindSingleNode("(//a[{$this->starts($this->t('View account details'))}]/following::text()[{$this->eq($this->t('Tier segments'))}])[1]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d+)\s*$/");

        if (!empty($tierSegmentsNeed)) {
            $st->addProperty('TierSegmentsNeed', $tierSegmentsNeed);
        }

        // collect expire info
        // it-63689391.eml
        $expireInfo = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Miles expiring on'))}])[1]/ancestor::tr[1]");

        if (preg_match("/^\s*{$this->opt($this->t('Miles expiring on'))}[\s\:]+(?<expirationDate>\d+\s+[^\d\s]+\s+\d{4})[\s\:]+(?<milesToExpire>[\d\.\,\']+)\s*$/", $expireInfo, $m)) {
            $st->addProperty('MilesToExpire', $this->normalizeAmount($m['milesToExpire']))
                ->parseExpirationDate($m['expirationDate']);
        }

        // it-897941575.eml
        $milesToExpire = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Miles expiring on'))}])[1]/ancestor::tr[1]/following-sibling::tr[normalize-space()][1]", null, true, "/^\s*([\d\.\,\']+)\s*$/");

        if ($milesToExpire !== null) {
            $st->addProperty('MilesToExpire', $this->normalizeAmount($milesToExpire));
        }

        $expirationDate = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Miles expiring on'))}])[1]/ancestor::tr[1]", null, true, "/^\s*{$this->opt($this->t('Miles expiring on'))}\s*(\d+\s+[^\d\s]+\s+\d{4}).*$/");

        if (!empty($expirationDate)) {
            $st->parseExpirationDate($expirationDate);
        }
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
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

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'contains(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
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
}
