<?php

namespace AwardWallet\Engine\etihad\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class NumberMilesOnly extends \TAccountChecker
{
    public $mailFiles = "etihad/statements/it-63711011.eml, etihad/statements/it-897897661.eml, etihad/statements/it-897937370.eml";
    public $lang = 'en';
    private static $dictionary = [
        'en' => [
            'Hi'                             => ['Hi', 'Hello'],
            'View account details'           => ['View account details', 'View Account Details'],
            'name'                           => ['your', 'did you'],
            'Miles balance:'                 => ['Miles balance:', 'Etihad Guest Miles'],
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
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Your monthly mileage statement'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Miles balance:'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $patterns['travellerName'] = '[[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]]';
        $xpathMainLogo = "{$this->eq($this->t('Etihad Guest'), '@alt')}";

        // collect name
        // it-63711011.eml
        $name = $this->re("/^({$patterns['travellerName']})\,\s*{$this->opt($this->t('name'))}/", $parser->getSubject())
            // it-897897661.eml
            ?? $this->http->FindSingleNode("(//img[{$xpathMainLogo}])[1]/following::text()[{$this->starts($this->t('Hi'))}]", null, true, "/^\s*{$this->opt($this->t('Hi'))}\s*({$patterns['travellerName']})[\s\,]*$/");

        if (!empty($name)) {
            $st->addProperty('Name', $name);
        }

        // collect number
        // it-63711011.eml
        $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Etihad Guest No:'))}]", null, true, "/^{$this->opt($this->t('Etihad Guest No:'))}\s*(\d+)$/")
            // it-897937370.eml
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('Etihad Guest No:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d+)\s*$/");

        if (!empty($number)) {
            $st->setNumber($number)
                ->setLogin($number);
        }

        // collect account info for it-897897661.eml
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

        // collect $balance
        // it-63711011.eml
        $balance = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Miles balance:'))}]", null, true, "/^{$this->opt($this->t('Miles balance:'))}\s*([\d\.\,\']+)/")
            // it-897897661.eml
            ?? $this->http->FindSingleNode("//text()[{$this->eq($this->t('Miles balance:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([\d\.\,\']+)\s*$/");

        $st->setBalance($this->normalizeAmount($balance));
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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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
