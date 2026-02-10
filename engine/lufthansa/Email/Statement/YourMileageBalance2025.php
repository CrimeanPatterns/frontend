<?php

namespace AwardWallet\Engine\lufthansa\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class YourMileageBalance2025 extends \TAccountChecker
{
	public $mailFiles = "lufthansa/statements/it-904688615.eml, lufthansa/statements/it-904903048.eml";
    public $lang = '';

    public $subjects = [
        // en
        'Your current mileage balance in',
    ];
    public static $dictionary = [
        'en' => [
            'travellerTitle'        => ['Mr.', 'Ms.', 'Dr.', 'Prof.'],
            'Your current account balance is:'       => 'Your current account balance is:',
            'Account balance from:' => 'Account balance from:',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@mailing.milesandmore.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (is_string($phrase) && stripos($headers['subject'], $phrase) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getHeader('from')) !== true
            && $this->http->XPath->query('//a[contains(@href,".miles-and-more.com/") or contains(@href,".miles-and-more.com%2F")]')->length === 0
            && $this->http->XPath->query('//img[contains(@src,".miles-and-more.com/") or contains(@src,".miles-and-more.com%2F")]')->length === 0
        ) {
            return false;
        }

        return $this->assignLang();
    }

    public function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $phrases) {
            if (empty($phrases['Your current account balance is:'])) {
                continue;
            }

            if ($this->http->XPath->query("//*[{$this->contains($phrases['Your current account balance is:'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $email->setType('YourMileageBalance2025' . ucfirst($this->lang));


        $st = $email->add()->statement();

        $patterns['travellerName'] = '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]';

        if (preg_match("/, {$this->opt($this->t('travellerTitle'))}\s*(?<name>\b{$patterns['travellerName']})\s*$/u", $parser->getSubject(), $m)) {
            $st->addProperty('Name', $m['name']);
        }

        $date = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Account balance from:'))}]", null, true, "/{$this->opt($this->t('Account balance from:'))}\s*(.*)$/u");

        if ($date !== null) {
            $st->setBalanceDate($this->normalizeDate($date));
        }

        $balance = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Your current account balance is:'))}])[1]",null, true, "/^{$this->opt($this->t('Your current account balance is:'))}\s*(\-?[ ]*\d[,.\d ]*)\s*{$this->opt($this->t('miles'))}$/");
        $st->setBalance(preg_replace("/([\,])/","", $balance));

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

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    private function normalizeDate($date)
    {
        $in = [
            // 14.03.24
            "/^\s*(\d{1,2})\.(\d{2})\.(\d{2})\s*$/",
            // 3/14/24
            "/^\s*(\d{1,2})\/(\d{1,2})\/(\d{2})\s*$/",
        ];
        $out = [
            "$1.$2.20$3",
            "20$3-$1-$2",
        ];
        $date = preg_replace($in, $out, $date);

        return strtotime($date);
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

                return 'contains(normalize-space(' . $node . '),' . $s . ')';
            }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

                return 'normalize-space(' . $node . ')=' . $s;
            }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
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
