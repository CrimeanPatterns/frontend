<?php

namespace AwardWallet\Engine\hawaiian\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class PurchaseConfirmation extends \TAccountChecker
{
	public $mailFiles = "hawaiian/statements/it-914560073.eml, hawaiian/statements/it-914743921.eml";
    private $lang = '';
    private $reFrom = ['buy@buymiles.hawaiianairlines.com'];
    private $reProvider = ['HawaiianMiles'];
    private $reSubject = [
        'HawaiianMiles Purchase Confirmation',
        'Your HawaiianMiles purchase is pending',
    ];
    private $reBody = [
        'en' => [
            ['HawaiianMiles number:', 'Mahalo for buying miles', 'Your transaction is pending'],
        ],
    ];

    private static $dictionary = [
        'en' => [
        ],
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();
        $this->logger->notice("Lang: {$this->lang}");

        $st = $email->add()->statement();

        $balance = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Miles Balance'))}][1]", null, false, "/([0-9\,\.]+)[ ]*{$this->opt($this->t('Miles Balance'))}/u");
        $name = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Name:'))}]/following::text()[normalize-space()][1]");

        $st->setNumber($number = $this->http->FindSingleNode("//text()[{$this->eq($this->t('HawaiianMiles number:'))}]/following::text()[normalize-space()][1]", null, false, "/^([\d\-]+)$/u"))
            ->setLogin($number)
            ->addProperty("Name", $name)
            ->setBalance(preg_replace("/(\,)/u", "", $balance));



        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return $this->arrikey($from, $this->reFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (self::detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        return $this->arrikey($headers['subject'], $this->reSubject) !== false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[{$this->contains($this->reProvider)}]")->length === 0) {
            return false;
        }

        if ($this->assignLang()) {
            return true;
        }

        return false;
    }

    public static function getEmailLanguages()
    {
        return [];
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function assignLang()
    {
        foreach ($this->reBody as $lang => $values) {
            foreach ($values as $value) {
                if ($this->http->XPath->query("//text()[{$this->contains($value[0])}]")->length > 0
                    && ($this->http->XPath->query("//text()[{$this->contains($value[1])}]")->length > 0
                    || $this->http->XPath->query("//text()[{$this->contains($value[2])}]")->length > 0)) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
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

    private function arrikey($haystack, array $arrayNeedle)
    {
        foreach ($arrayNeedle as $key => $needles) {
            if (is_array($needles)) {
                foreach ($needles as $needle) {
                    if (stripos($haystack, $needle) !== false) {
                        return $key;
                    }
                }
            } else {
                if (stripos($haystack, $needles) !== false) {
                    return $key;
                }
            }
        }

        return false;
    }
}
