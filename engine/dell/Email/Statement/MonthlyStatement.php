<?php

namespace AwardWallet\Engine\dell\Email\Statement;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class MonthlyStatement extends \TAccountChecker
{
    public $mailFiles = "dell/statements/it-897952145.eml, dell/statements/it-904724214.eml, dell/statements/it-908810680.eml, dell/statements/it-910303642.eml";

    public $lang = 'en';

    public $subjects = [
        'Your Dell Rewards monthly statement is here!',
        'Your Dell Rewards points expire soon!',
    ];

    public $detectBody = [
        'en' => [
            'Thank you for being such a loyal Dell Rewards member!',
            'Don\'t let your Dell Rewards expire!',
        ],
    ];

    public static $dictionary = [
        "en" => [
            'Total Rewards:'   => ['Total Rewards:', 'Total Points:'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from'])
            && (stripos($headers['from'], '@dell.com') !== false || stripos($headers['from'], '.dell.com') !== false)
        ) {
            foreach ($this->subjects as $subject) {
                if (strpos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[{$this->contains(['Dell Technologies', '@dell.com'])}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains(['.dell.com'])}]")->length === 0
            && stripos($parser->getCleanFrom(), '@dell.com') === false
        ) {
            return false;
        }

        foreach ($this->detectBody as $lang => $dBody) {
            if ($this->http->XPath->query("//text()[{$this->contains($dBody)}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]dell\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $balance = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Total Rewards:'))}])[1]/following::text()[normalize-space()][1]",
            null, true, "/^\s*[^\d\s]\s*[\d\.\,\']+\s*\(\s*([\d\.\,\']+)(?:\s+points?)?\)\s*$/i");

        if (!empty($balance)) {
            $balance = PriceHelper::parse($balance);
        }

        $st->setBalance($balance);

        $date = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Total Rewards:'))}])[1]/following::text()[normalize-space()][position() < 5][{$this->eq($this->t('Expiration date:'))}]/following::text()[normalize-space()][1]")
            ?? $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Total Rewards:'))}])[1]/preceding::text()[normalize-space()][position() < 5][{$this->eq($this->t('Expiration date:'))}]/following::text()[normalize-space()][1]");

        $st->setExpirationDate($this->normalizeDate($date));

        $expiringBalance = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Points expiring soon:'))}])[1]/following::text()[normalize-space()][1]", null, true, "/^\s*[^\d\s]\s*[\d\.\,\']+\s*\(\s*([\d\.\,\']+)(?:\s+points?)?\)\s*$/i");

        if (!empty($expiringBalance)) {
            $st->addProperty('ExpiringBalance', PriceHelper::parse($expiringBalance));
        }

        $userEmail = $this->http->FindSingleNode("//text()[{$this->starts($this->t('This email was sent to'))}]", null, true, "/^\s*{$this->opt($this->t('This email was sent to'))}\s+(\b\S+@\S+\b)\.\s+/")
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('This email was sent to'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\S+@\S+)\s*$/");

        if (!empty($userEmail)) {
            $email->setUserEmail($userEmail);
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'normalize-space(.)="' . $s . '"'; }, $field)) . ')';
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $in = [
            "#^\s*(\d+)[/-](\d+)[/-](\d{4})\s*$#", // 11/15/2024 or 11-15-2024 => 15.11.2024
        ];

        $out = [
            '$2.$1.$3',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("/\b\d{4}\b/", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }

        return $date;
    }
}
