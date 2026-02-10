<?php

namespace AwardWallet\Engine\priceline\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class HotelReceipt extends \TAccountChecker
{
    public $mailFiles = "priceline/it-646092634.eml";
    public $subjects = [
        'Your hotel receipt from Priceline', 'Your hotel booking receipt from Priceline',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'otaConfNumber'  => ['Priceline Trip Number', 'Priceline trip number'],
            'paymentSummary' => 'Payment Summary',
            'purchaseDate'   => ['Purchase Date', 'Purchase date'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@travel.priceline.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        return $this->http->XPath->query("//text()[contains(normalize-space(), 'Your receipt from Priceline')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Your hotel on'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('otaConfNumber'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]travel\.priceline\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $otaConfirmation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('otaConfNumber'), "translate(.,':','')")} and not(preceding::*[{$this->eq($this->t('paymentSummary'), "translate(.,':','')")}])]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d[-\d]{5,})\s*$/u");
        $otaConfirmationTitle = $this->http->FindSingleNode("//text()[{$this->eq($this->t('otaConfNumber'), "translate(.,':','')")} and not(preceding::*[{$this->eq($this->t('paymentSummary'), "translate(.,':','')")}])]", null, true, '/^(.+?)[\s:：]*$/u');

        if (!$otaConfirmation) {
            $otaConfirmation = $this->http->FindSingleNode("//*[ count(div[normalize-space()])=2 and div[normalize-space()][1][{$this->eq($this->t('otaConfNumber'), "translate(.,':','')")}] ]/div[normalize-space()][2]", null, true, "/^\s*(\d[-\d]{5,})\s*$/u");
            $otaConfirmationTitle = $this->http->FindSingleNode("//*[count(div[normalize-space()])=2]/div[normalize-space()][1][{$this->eq($this->t('otaConfNumber'), "translate(.,':','')")}]", null, true, '/^(.+?)[\s:：]*$/u');
        }

        $email->ota()->confirmation($otaConfirmation, $otaConfirmationTitle);

        $this->ParseHotel($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseHotel(Email $email): void
    {
        $patterns = [
            'date' => '[[:alpha:]]+[,.\s]+\d{1,2}[,.\s]+\d{4}\b', // Feb 18, 2024
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $h = $email->add()->hotel();

        $h->general()
            ->confirmation($this->http->FindSingleNode("//text()[contains(normalize-space(), 'Confirmation #:')]/ancestor::div[1]", null, true, "/{$this->opt($this->t('Confirmation #:'))}\s*([A-Z\-\d]+)/"), 'Confirmation #')
            ->date(strtotime($this->http->FindSingleNode("//text()[{$this->eq($this->t('purchaseDate'))}]/ancestor::div[ descendant::text()[normalize-space()][2] ][1]", null, true, "/{$this->opt($this->t('purchaseDate'))}\s*({$patterns['date']})$/u")))
            ->traveller($this->http->FindSingleNode("//text()[{$this->eq($this->t('Billing Name'))}]/ancestor::div[ descendant::text()[normalize-space()][2] ][1]", null, true, "/{$this->opt($this->t('Billing Name'))}\s*({$patterns['travellerName']})$/u"));

        $price = $this->http->FindSingleNode("//text()[normalize-space()='Total Cost']/ancestor::div[2]", null, true, "/{$this->opt($this->t('Total Cost'))}\s*(\D+[\d\.\,]+)/");

        if (preg_match("/(?<currency>\D{1,3})\s*(?<total>[\d\.\,]+)/u", $price, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);

            $h->price()
                ->total(PriceHelper::parse($m['total'], $currency))
                ->currency($currency);

            $cost = $this->http->FindSingleNode("//text()[normalize-space()='Hotel Subtotal']/ancestor::div[2]", null, true, "/{$this->opt($this->t('Hotel Subtotal'))}\s*\D+([\d\.\,]+)/");

            if (!empty($cost)) {
                $h->price()
                    ->cost(PriceHelper::parse($cost, $currency));
            }

            $tax = $this->http->FindSingleNode("//text()[normalize-space()='Taxes and fees']/ancestor::div[2]", null, true, "/{$this->opt($this->t('Taxes and fees'))}\s*\D+([\d\.\,]+)/");

            if (!empty($tax)) {
                $h->price()
                    ->tax(PriceHelper::parse($tax, $currency));
            }

            $discount = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'Coupon')]/ancestor::div[2]", null, true, "/^Coupon\-\D([\d\.\,]+)$/");

            if (!empty($discount)) {
                $h->price()
                    ->discount($discount);
            }
        }

        $hotelName = $this->http->FindSingleNode("//img[contains(@src, 'stay_blue')]/ancestor::div[1]");

        if (empty($hotelName)) {
            $hotelName = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'Check-in:')]/preceding::text()[normalize-space()][1]");
        }

        $h->hotel()
            ->name($hotelName)
            ->noAddress();

        $h->booked()
            ->rooms($this->http->FindSingleNode("//text()[normalize-space()='Number of rooms']/ancestor::div[2]", null, true, "/{$this->opt($this->t('Number of rooms'))}\s*(\d{1,3})\b/"));

        $year = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Your hotel on')]", null, true, "/\,\s+(\d{4})\s*is/");

        if (!empty($year)) {
            $dateRange = $this->http->FindSingleNode("//img[contains(@src, 'stay_blue')]/ancestor::div[1]/following::div[1][contains(normalize-space(), '–')]");

            if (empty($dateRange)) {
                $dateRange = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'Check-in:')]");
            }

            if (preg_match("/^(?<depMonth>[[:alpha:]]+)\s*(?<depDay>\d{1,2})[\s\–]+(?<arrMonth>[[:alpha:]]+)\s*(?<arrDay>\d{1,2})(?:\s*[•]\s*Check-in\s*[:]+\s*after\s*(?<inTime>{$patterns['time']}))?$/u", $dateRange, $m)) {
                $depDate = $m['depDay'] . ' ' . $m['depMonth'] . ' ' . $year;

                if (!empty($m['inTime'])) {
                    $depDate .= ', ' . $m['inTime'];
                }
                $h->booked()
                    ->checkIn(strtotime($depDate))
                    ->checkOut(strtotime($m['arrDay'] . ' ' . $m['arrMonth'] . ' ' . $year));
            }
        }

        $pricePerNight = $this->http->FindSingleNode("//text()[normalize-space()='Price per night']/ancestor::div[2]", null, true, "/{$this->opt($this->t('Price per night'))}\s*(\D+[\d\.\,]+)/");

        if (!empty($pricePerNight)) {
            $h->addRoom()->setRate($pricePerNight . ' / night');
        }
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

    private function eq($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
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

    private function normalizeCurrency($string)
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
            'INR' => ['₹'],
            'AUD' => ['A$'],
            'EUR' => ['€', 'Euro'],
            'USD' => ['US Dollar'],
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
