<?php

namespace AwardWallet\Engine\homeaway\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class HotelConfirmed extends \TAccountChecker
{
    public $mailFiles = "homeaway/it-891484084.eml, homeaway/it-897564134.eml";
    public $subjects = [
        'Your reservation has been confirmed',
    ];

    public $providerCode;

    public $date;

    public $lang = 'en';
    public static $dictionary = [
        "en" => [
            'Check-in'             => ['Check-in', 'Arrive'],
            'Check-out'            => ['Check-out', 'Departure'],
            'nights'               => ['nights', 'night'], // in cost
            'FeesNames'            => ['Host Fees', 'Service Fee'],
            'Vrbo reservation ID:' => ['Vrbo reservation ID:', 'Vrbo Reservation ID:'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers['from'], '.vrbo.com') === false) {
            return false;
        }

        foreach ($this->subjects as $subject) {
            if (stripos($headers['subject'], $subject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//node()[{$this->contains(['update on Vrbo.com', 'Download the Vrbo app'])}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains(['Vrbo logo'])}]")->length === 0
        ) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['Check-in']) && !empty($dict['Check-out'])
                && $this->http->XPath->query("//*[count(*[normalize-space()]) = 2][*[normalize-space()][1][{$this->starts($dict['Check-in'])}]][*[normalize-space()][2][{$this->starts($dict['Check-out'])}]]/following::img[position() < 5][@src[{$this->contains('.vrbo.com')}] and @src[{$this->contains('icon__place')}]]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]eg\.vrbo\.com$/', $from) > 0;
    }

    public function ParseHotel_1(Email $email)
    {
        $h = $email->add()->hotel();

        // General
        $h->general()
            ->noConfirmation();

        $traveller = $this->http->FindSingleNode("//text()[" . $this->contains($this->t(', get ready for your trip!')) . "]",
            null, true, '/^\s*([A-z\s]+?)\s*' . $this->opt($this->t(', get ready for your trip!')) . '/');

        $h->general()->traveller($traveller, false);

        $cancellation = $this->http->FindSingleNode("//*[{$this->eq($this->t("Cancellation Policy"))}]/following-sibling::*[normalize-space()][1]");

        if (!empty($cancellation)) {
            $h->general()
                ->cancellation($cancellation);
        }

        // Hotel
        $h->hotel()->house();
        $address = $this->http->FindSingleNode("//img[contains(@src, 'icon__place_color__neutral')]/following::text()[normalize-space()][1]");

        if (!empty($address)) {
            $h->hotel()
                ->name('House')
                ->address($address);
        }

        // Booked
        $xpath = "//*[count(*[normalize-space()]) = 2][*[normalize-space()][1][{$this->starts($this->t('Check-in'))}]][*[normalize-space()][2][{$this->starts($this->t('Check-out'))}]]";

        $h->booked()
           ->checkIn($this->normalizeDate(implode(", ", $this->http->FindNodes($xpath . "/*[normalize-space()][1]/descendant::text()[normalize-space()][position() > 1]"))))
           ->checkOut($this->normalizeDate(implode(", ", $this->http->FindNodes($xpath . "/*[normalize-space()][2]/descendant::text()[normalize-space()][position() > 1]"))))
        ;

        $bookedInfo = $this->http->FindSingleNode("//img[@src[{$this->contains(['icon__home_fill_color'])}]][1]/following::text()[normalize-space()][1]");

        if (preg_match("/(?<night>\d+)\s*nights?\,\s+(?<adult>\d+)\s*adults?(?:\s*\,\s*(?<kids>\d+)\s*child(?:ren)?)?/", $bookedInfo, $m)) {
            $h->booked()
                ->guests($m['adult']);

            if (isset($m['kids']) && !empty($m['kids'])) {
                $h->booked()
                    ->kids($m['kids']);
            }
        }

        $price = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Charges'))}]/following::text()[{$this->eq($this->t('Total'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^(?<currency>\D{1,3})\s*(?<total>[\d\.\,]+)/", $price, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $h->price()
               ->total(PriceHelper::parse($m['total'], $currency))
               ->currency($currency);

            $cost = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Charges'))}]/following::text()[normalize-space()][1][{$this->contains($this->t('nights'))}]/following::text()[normalize-space()][1]", null, true, "/^\D{1,3}\s*([\d\.\,]+)/");

            if ($cost !== null) {
                $h->price()
                   ->cost(PriceHelper::parse($cost, $currency));
            }

            $feeXpath = "//text()[{$this->eq($this->t('Charges'))}]/following::text()[normalize-space()][1]/ancestor::*[count(.//text()[normalize-space()]) = 2]"
                . "/following-sibling::*[normalize-space()][not(descendant::text()[normalize-space()][2][starts-with(normalize-space(), '-')])][following-sibling::*[descendant::text()[normalize-space()][1][{$this->eq($this->t('Total'))}]]]"
            ;

            foreach ($this->http->XPath->query($feeXpath) as $i => $fRoot) {
                $h->price()
                    ->fee($this->http->FindSingleNode("descendant::text()[normalize-space()][1]", $fRoot),
                        PriceHelper::parse($this->http->FindSingleNode("descendant::text()[normalize-space()][2]", $fRoot, true, "/^\D{1,3}\s*([\d\.\,]+)/"), $currency));
            }

            $discount = $this->http->FindSingleNode("//text()[normalize-space() = 'Coupon applied']/ancestor::tr[1]/descendant::td[2]", null, true, "/^\-\D*([\d\.]+)$/");

            if (!empty($discount)) {
                $h->price()
                    ->discount($discount);
            }
        }

        $pointInfo = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'OneKeyCash applied')]/ancestor::p[1]");

        if (preg_match("/OneKeyCash applied\(\-(?<spent>\D*[\d\.]+)\)/", $pointInfo, $m)) {
            $h->price()->spentAwards($m['spent']);
        }

        $pointInfo = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'in OneKeyCash')]/ancestor::p[1]");

        if (preg_match("/\s+(?<earn>\D[\d\.]+)\s*in\s*OneKeyCash/", $pointInfo, $m)) {
            $h->setEarnedAwards($m['earn']);
        }

        $this->detectDeadLine($h);
    }

    public function ParseHotel_2(Email $email)
    {
        $h = $email->add()->hotel();

        // General
        $h->general()
            ->confirmation($this->http->FindSingleNode("//text()[" . $this->starts($this->t('Vrbo reservation ID:')) . "]",
                null, true, '/' . $this->opt($this->t('Vrbo reservation ID:')) . '\s*([A-z\d\-]{5,})$/'));

        $traveller = $this->http->FindSingleNode("//text()[" . $this->starts($this->t('Get ready for your trip, ')) . "]",
            null, true, '/^\s*' . $this->opt($this->t('Get ready for your trip, ')) . '\s*([A-z\s]+?)!/');

        $h->general()->traveller($traveller, false);

        $cancellation = $this->http->FindSingleNode("//*[{$this->eq($this->t("Cancellation Policy"))}]/following-sibling::*[normalize-space()][1]");

        if (!empty($cancellation)) {
            $h->general()
                ->cancellation($cancellation);
        }

        // Hotel
        $address = $this->http->FindSingleNode("//img[@src[{$this->contains(['icon__place_fill_color'])}]]/following::text()[normalize-space()][1][{$this->eq($this->t('Get directions'))}]");
        $propertyID = $this->http->FindSingleNode("//text()[" . $this->starts($this->t('Property ID:')) . "]",
            null, true, '/' . $this->opt($this->t('Property ID:')) . '\s*([A-Z\d\-]{5,})$/');

        if ($address && !empty($propertyID)) {
            $http1 = clone $this->http;
            $http1->GetURL("http://www.vrbo.com/" . $propertyID);

            $address = $http1->FindSingleNode("//label[normalize-space() = 'Where to?']/following::*[1][self::input]/@value");
            $h->hotel()
                ->name($this->http->FindSingleNode("//text()[" . $this->starts($this->t('Vrbo reservation ID:')) . "]/preceding::text()[normalize-space()][1]")
                    . ' (' . $this->http->FindSingleNode("//text()[" . $this->starts($this->t('Property ID:')) . "]") . ')')
                ->address($address)
                ->house();
        }

        // Booked
        $xpath = "//*[count(*[normalize-space()]) = 2][*[normalize-space()][1][{$this->starts($this->t('Check-in'))}]][*[normalize-space()][2][{$this->starts($this->t('Check-out'))}]]";

        $h->booked()
           ->checkIn($this->normalizeDate(implode(", ", $this->http->FindNodes($xpath . "/*[normalize-space()][1]/descendant::text()[normalize-space()][position() > 1]"))))
           ->checkOut($this->normalizeDate(implode(", ", $this->http->FindNodes($xpath . "/*[normalize-space()][2]/descendant::text()[normalize-space()][position() > 1]"))))
        ;

        $bookedInfo = implode("\n", $this->http->FindNodes("//img[@src[{$this->contains(['icon__family_friendly_fill_color'])}]][1]/following::text()[normalize-space()][position() < 4]"));

        if (preg_match("/^\s*(\d+)\s*adults?\s*$/m", $bookedInfo, $m)) {
            $h->booked()
                ->guests($m[1]);
        }

        if (preg_match("/^\s*(\d+)\s*child(?:ren)?\s*$/m", $bookedInfo, $m)) {
            $h->booked()
                ->kids($m[1]);
        }

        $price = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Charges'))}]/following::text()[{$this->eq($this->t('Total'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^(?<currency>\D{1,3})\s*(?<total>[\d\.\,]+)/", $price, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $h->price()
               ->total(PriceHelper::parse($m['total'], $currency))
               ->currency($currency);

            $cost = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Charges'))}]/following::text()[normalize-space()][1][{$this->contains($this->t('nights'))}]/following::text()[normalize-space()][1]", null, true, "/^\D{1,3}\s*([\d\.\,]+)/");

            if ($cost !== null) {
                $h->price()
                   ->cost(PriceHelper::parse($cost, $currency));
            }

            $feeXpath = "//text()[{$this->eq($this->t('Charges'))}]/following::text()[normalize-space()][1]/ancestor::*[not(.//text()[{$this->eq($this->t('Charges'))}])][following::text()[normalize-space()][1][{$this->eq($this->t('Total'))}]]"
                . "//tr[not(.//tr)][not(*[2][starts-with(normalize-space(), '-')])]";

            foreach ($this->http->XPath->query($feeXpath) as $i => $fRoot) {
                if ($i === 0) {
                    if (preg_match("/ x \d+ night/", $fRoot->nodeValue)) {
                        continue;
                    } else {
                        break;
                    }
                }
                $value = $this->http->FindSingleNode("*[2]", $fRoot);

                if (!empty($value) && !preg_match("/^\s*\(.+\)\s*$/", $value)) {
                    $h->price()
                        ->fee($this->http->FindSingleNode("*[1]", $fRoot),
                            PriceHelper::parse($this->http->FindSingleNode("*[2]", $fRoot, true, "/^\D{1,3}\s*([\d\.\,]+)/"), $currency));
                }
            }
        }

        $pointInfo = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'OneKeyCash applied')]/ancestor::p[1]");

        if (preg_match("/OneKeyCash applied\(\-(?<spent>\D*[\d\.]+)\)/", $pointInfo, $m)) {
            $h->price()->spentAwards($m['spent']);
        }

        $pointInfo = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'in OneKeyCash')]/ancestor::p[1]");

        if (preg_match("/\s+(?<earn>\D[\d\.]+)\s*in\s*OneKeyCash/", $pointInfo, $m)) {
            $h->setEarnedAwards($m['earn']);
        }

        $this->detectDeadLine($h);
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->date = strtotime($parser->getHeader('date'));

        $type = '';

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Property ID:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Guests'))}]")->length > 0
        ) {
            $this->ParseHotel_2($email);
            $type = '2';
        } else {
            $this->ParseHotel_1($email);
            $type = '1';
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . $type . ucfirst($this->lang));

        return $email;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
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

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) { return "normalize-space(.)=\"{$s}\""; }, $field)) . ')';
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

    private function normalizeDate($str)
    {
        // $this->logger->debug('$str = '.print_r( $str,true));
        $str = str_replace("noon", "12:00PM", $str);
        $year = '';

        if (!empty($this->date)) {
            $year = date("Y", $this->date);
        }

        $in = [
            // 4:00pm, Mon, Oct 7
            "#^\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*,\s*(\w+)\,\s*(\w+)\s*(\d+)\s*$#i",
        ];
        $out = [
            "$2, $4 $3 $year, $1",
        ];
        $str = preg_replace($in, $out, $str);
        // $this->logger->debug('$str = '.print_r( $str,true));

        if (preg_match("#(.*\d+\s+)([^\d\s]+)(\s+\d{4}.*)#", $str, $m)) {
            if ($en = MonthTranslate::translate($m[2], $this->lang)) {
                $str = $m[1] . $en . $m[3];
            }
        }

        if (preg_match("/^(?<week>\w+), (?<date>\d+ \w+ .+)/u", $str, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));
            $str = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/\b\d{4}\b/", $str)) {
            $str = strtotime($str);
        } else {
            $str = null;
        }

        return $str;
    }

    private function detectDeadLine(\AwardWallet\Schema\Parser\Common\Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        $year = date("Y", $h->getCheckInDate());

        if (
            // 100% refund for cancellations requested by Mar 3, 2025 at 11:59 PM (property's local time).
            preg_match("#100% refund for cancellations requested by\s*(?<date>.+?)\s+at\s+(?<time>[\d\:]+\s*a?p?m)\s*\(#i", $cancellationText, $m)
        ) {
            $h->booked()
                ->deadline($this->normalizeDate($m['date'] . ', ' . $m['time']));

            return;
        }

        if (preg_match("#Free cancellation until\s*(?<date>\w+\s*\d+)\s*at\s*(?<time>[\d\:]+\s*a?p?m)#i", $cancellationText, $m)
        ) {
            $h->booked()
                ->deadline($this->normalizeDate($m['date'] . ' ' . $year . ', ' . $m['time']));

            return;
        }

        if (preg_match("/The room\/unit type and rate selected are non\-refundable/", $cancellationText)) {
            $h->booked()
                ->nonRefundable();

            return;
        }
    }

    private function normalizeCurrency(string $string)
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
            'EUR' => ['€'],
            '$'   => ['$'],
            'INR' => ['Rs.'],
            'USD' => ['US$'],
            'CAD' => ['CA$'],
        ];

        foreach ($currencies as $code => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency) {
                    return $code;
                }
            }
        }

        return $string;
    }
}
