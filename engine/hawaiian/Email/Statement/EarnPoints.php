<?php

namespace AwardWallet\Engine\hawaiian\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class EarnPoints extends \TAccountChecker
{
	public $mailFiles = "hawaiian/statements/it-912069070.eml, hawaiian/statements/it-913443105.eml, hawaiian/statements/it-913650373.eml";
	private $lang = '';
    private $reFrom = ['emails.barclaysus.com'];
    private $reProvider = ['HawaiianMiles'];
    private $reSubject = [
        'Hit your spend goal. Earn more HawaiianMiles.',
        'Earn bonus HawaiianMiles for your purchases',
        'your purchases can earn you HawaiianMiles',
        'Enroll today: Earn bonus HawaiianMiles',
        'How many HawaiianMiles have you earned?',
        'Use your card and earn more HawaiianMiles',
    ];


    private static $dictionary = [
        'en' => [
            'Account ending:' => ['Account ending:'],
            'detectBody' => ['Earn more HawaiianMiles', 'EARN AN ADDITIONAL', 'Get started and get rewarded', 'Earn an additional', 'Earn HawaiianMiles on your purchases'],

        ],
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();
        $this->logger->notice("Lang: {$this->lang}");

        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Account ending:'))}]/preceding::text()[normalize-space()][1]", null, false, "/^([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])$/u");
        $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Account ending:'))}]", null, false, "/^{$this->opt($this->t('Account ending:'))}[ ]*([\d\-]+)$/u");

        $st->setNumber($number)->masked('left')
            ->setLogin($number)->masked('left')
            ->addProperty("Name", $name)
            ->setNoBalance(true);

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
        $this->assignLang();

        if ($this->http->XPath->query("//text()[{$this->contains($this->reProvider)}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//*[{$this->contains($this->t('Account ending:'))}]")->length > 0
            && $this->http->XPath->query("//*[{$this->contains($this->t('detectBody'))}]")->length > 0){
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
        foreach (self::$dictionary as $lang => $values) {
            if (isset($values['Account ending:'], $values['detectBody'])) {
                if ($this->http->XPath->query("//*[{$this->contains($values['Account ending:'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($values['detectBody'])}]")->length > 0) {
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
