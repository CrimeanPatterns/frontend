<?php

namespace AwardWallet\Engine\national\Email\Statement;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class AccountStatement extends \TAccountChecker
{
    public $mailFiles = "national/it-903008821.eml, national/it-903590327.eml, national/it-905108270.eml, national/it-905980130.eml, national/it-909905586.eml";

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'National Car Rental' => ['National Car Rental', 'NATIONAL CAR RENTAL'],
            'Member Number:'      => ['Member Number:', 'Member #:'],
            'Tier'                => ['Emerald Club', 'Executive', 'Executive Elite'],
            'NEXT FREE DAY'       => ['NEXT FREE DAY', 'Next Free Day'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]email\.emeraldclub\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains('National Car Rental')}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains('nationalcar.com')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//a/@href[{$this->eq('Reserve')}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->eq('Specials')}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->eq('Log In')}]")->length === 0
            && ($this->http->XPath->query("//text()[{$this->starts($this->t('Member Number:'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->eq($this->t('Emerald Club #:'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->eq($this->t('Tier'))}]")->length > 0)
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Member Number:'))}]", null, true, "/^\s*{$this->opt($this->t('Member Number:'))}\s*(\d{9,})\s*$/")
            ?? $this->http->FindSingleNode("//text()[{$this->eq($this->t('Emerald Club #:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d{9,})\s*$/")
            ?? $this->http->FindSingleNode("//text()[{$this->eq($this->t('Emerald Club'))}]/preceding::text()[normalize-space()][1][{$this->starts('#')}]", null, true, "/^\s*\#(\d{9,})\s*$/")
            ?? $this->http->FindSingleNode("//text()[{$this->eq($this->t('Tier'))}]/preceding::text()[normalize-space()][1][{$this->starts('#')}]", null, true, "/^\s*\#(\d{9,})\s*$/");

        if (!empty($number)) {
            $st->addProperty('Number', $number);
        }

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Member Number:'))}]/preceding::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/u")
            ?? $this->http->FindSingleNode("//text()[{$this->eq("#{$number}")}]/preceding::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/u");

        if (!empty($name)) {
            $st->addProperty('Name', $name);
        }

        $rentalsToNextStatus = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('more Rentals* until'))}])[last()]", null, true, "/^\s*(\d+)\s*{$this->opt($this->t('more Rentals* until'))}/");

        if (!empty($rentalsToNextStatus)) {
            $st->addProperty('RentalsToNextStatus', $rentalsToNextStatus);
        }

        $rentalDaysToNextStatus = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('more Rental Days* until'))}])[last()]", null, true, "/^\s*(\d+)\s*{$this->opt($this->t('more Rental Days* until'))}/");

        if (!empty($rentalDaysToNextStatus)) {
            $st->addProperty('RentalDaysToNextStatus', $rentalDaysToNextStatus);
        }

        $tier = $this->http->FindSingleNode("//text()[{$this->eq("#{$number}")}]/following::text()[normalize-space()][1][{$this->eq($this->t('Tier'))}]")
            ?? $this->http->FindSingleNode("(//text()[{$this->contains($this->t('more Rentals* to keep'))}])[1]/following::text()[normalize-space()][1][{$this->eq($this->t('Tier'))}]")
            ?? $this->http->FindSingleNode("(//text()[{$this->contains($this->t('more Rental Days* to keep'))}])[1]/following::text()[normalize-space()][1][{$this->eq($this->t('Tier'))}]");

        // determine current tier if exist next tier
        if (empty($tier) && (!empty($rentalsToNextStatus) || !empty($rentalDaysToNextStatus))) {
            $nextTier = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('more Rentals* until'))}])[last()]/following::text()[normalize-space()][1][{$this->eq($this->t('Tier'))}]") ??
                $this->http->FindSingleNode("(//text()[{$this->contains($this->t('more Rental Days* until'))}])[last()]/following::text()[normalize-space()][1][{$this->eq($this->t('Tier'))}]");

            $target_index = array_search($nextTier, $this->t('Tier'));

            if ($target_index !== false && $target_index !== 0) {
                $tier = $this->t('Tier')[$target_index - 1];
            }
        }

        if (!empty($tier)) {
            $st->addProperty('Tier', $tier);
        }

        $neededToNextFreeDay = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Rental Credits Until'))}]/ancestor::*[{$this->contains($this->t('NEXT FREE DAY'))}][1]", null, true, "/^\s*(\d+)\s*{$this->opt($this->t('Rental Credits Until NEXT FREE DAY'))}\s*$/i");

        if (!empty($neededToNextFreeDay)) {
            $st->addProperty('NeededToNextFreeDay', $neededToNextFreeDay);
        }

        $balance = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Credit Balance'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([\d\.\,\']+)\s*$/");

        if ($balance != null) {
            $st->setBalance(PriceHelper::parse($balance));
        } else {
            $st->setNoBalance(true);
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

    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function opt($field, $delimiter = '/')
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
            return str_replace(' ', '\s+', preg_quote($s, $delimiter));
        }, $field)) . ')';
    }
}
