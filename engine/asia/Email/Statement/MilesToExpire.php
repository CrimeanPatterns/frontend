<?php

namespace AwardWallet\Engine\asia\Email\Statement;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class MilesToExpire extends \TAccountChecker
{
    public $mailFiles = "asia/statements/it-927750604.eml";
    public $detectSubjects = [
        // en
        'Your miles are set to expire on',
    ];

    public $lang = '';

    public static $dictionary = [
        'en' => [
            // 'Dear' => '',
            'Membership No :'      => 'Membership No :',
            // 'Your account balance' => ''
            // 'We’d like to remind you that' => ''
            // 'expiring on' => ''
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, 'member-noreply@e.cathaypacific.com') !== false
            || stripos($from, 'cathay-noreply@e.cathaypacific.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (self::detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        foreach ($this->detectSubjects as $detectSubject) {
            if (stripos($headers['subject'], $detectSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect provider
        if (self::detectEmailFromProvider($parser->getHeader('from')) !== true
            && $this->http->XPath->query('//a[contains(@href,".cathaypacific.com/")]')->length === 0
            && $this->http->XPath->query('//node()[contains(normalize-space(),"© Cathay Pacific") or contains(normalize-space(),"member-noreply@e.cathaypacific.com")]')->length === 0
        ) {
            return false;
        }

        // detect format
        if ($this->assignLang()
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Your account balance'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('expiring on'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $phrases) {
            if (!empty($phrases['Membership No :']) && $this->http->XPath->query("//tr[{$this->starts($phrases['Membership No :'])}]")->length > 0) {
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

        $patterns = [
            'prefixName'    => "{$this->opt(['Dr', 'Miss', 'Mrs', 'Mr', 'Ms', 'Mme', 'Mr/Mrs', 'Mrs/Mr', 'Monsieur'])}",
            'travellerName' => "[[:alpha:]][-.\'’[:alpha:] ]*?[[:alpha:]]?",
        ];

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]",
            null, true, "#^{$this->opt($this->t('Dear'))}\s*(?:{$patterns['prefixName']}[\s\.]+)?({$patterns['travellerName']})\s*[^A-Z]*$#ui");

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Membership No :'))}]/preceding::text()[normalize-space()][1]",
                null, true, "#^({$patterns['travellerName']})\s*[^A-Z]*$#ui");
        }

        $st->addProperty('Name', $name);

        $number = $this->http->FindSingleNode("//tr[{$this->starts($this->t('Membership No :'))}]", null, true, "/{$this->opt($this->t('Membership No :'))}\s+(\d{2}[-Xx \d]{4,}\d{3})\s*$/u");
        $number = str_replace(' ', '', $number);

        if (preg_match("/^(\d+)[Xx]+(\d+)$/", $number, $m)) {
            // 172XXXX649
            $numberMasked = $m[1] . '**' . $m[2];
            $st->setNumber($numberMasked)->masked('center')
                ->setLogin($numberMasked)->masked('center');
        } elseif (preg_match("/^\d+$/", $number)) {
            // 1723548649
            $st->setNumber($number)
                ->setLogin($number);
        }

        $balance = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Your account balance'))}]/following::text()[normalize-space()][1]", null, true, "/^([\d\.\,\']+)$/");

        if ($balance !== null) {
            $balance = PriceHelper::parse($balance);
            $st->setBalance($balance);
            $balanceDate = $this->http->FindSingleNode("(//*[{$this->starts($this->t('We’d like to remind you that'))}])[1]/preceding::text()[normalize-space()][1]");
            $st->setBalanceDate($this->normalizeDate($balanceDate));
        }

        $expiringBalance = $this->http->FindSingleNode("//text()[{$this->eq($this->t('expiring on'))}]/preceding::text()[normalize-space()][1]", null, true, "/^([\d\.\,\']+)$/");

        if ($expiringBalance !== null) {
            $expiringBalance = PriceHelper::parse($expiringBalance);
            $st->addProperty('MilesToExpire', $expiringBalance);

            if ($balance === $expiringBalance) {
                $expirationDate = $this->http->FindSingleNode("//text()[{$this->eq($this->t('expiring on'))}][1]/following::text()[normalize-space()][1]");
                $st->setExpirationDate($this->normalizeDate($expirationDate));
            }
        }

        return $email;
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || !isset(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
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

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'normalize-space(' . $node . ')="' . $s . '"';
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

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }

        $in = [
            '/^\s*(\d{4})년(\d+)월(\d+)일\s*$/iu', // ko: 2025년6월18일   =>   2025-6-18
            '/^\s*(\d{4})年(\d+)月(\d+)日\s*$/iu', // zh: 2020年9月16日   =>   2020-9-16
        ];
        $out = [
            '$1-$2-$3',
            '$1-$2-$3',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        // $this->logger->debug('date replace = ' . print_r( $date, true));

        if (preg_match("/\b\d{4}\b/", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }
        // $this->logger->debug('date end = ' . print_r( $date, true));

        return $date;
    }
}
