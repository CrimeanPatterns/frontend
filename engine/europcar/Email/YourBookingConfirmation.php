<?php

namespace AwardWallet\Engine\europcar\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class YourBookingConfirmation extends \TAccountCheckerExtended
{
    public $mailFiles = "europcar/it-2218095.eml, europcar/it-907193172.eml";
    public $subjects = [
        'Your Europcar reservation request for',
        'Your booking confirmation',
    ];

    public $lang = 'en';

    public $fromEmails = [
        'europcar@centprod.com',
        'europcar@mail.europcar.com',
    ];

    public static $dictionary = [
        "en" => [
            'Driver Information:'        => ['Driver Information:', 'Customer Information'],
            'Rental Details:'            => ['Rental Details:', 'Information about the rental'],
            'Thank you for booking with' => ['Thank you for booking with', 'Thank you for choosing'],
            'Reservation date'           => ['Reservation date', 'Reservation Date'],
            'Reservation number:'        => ['Reservation number:', 'Booking confirmation number is:', 'Reservation nr.'],
            'First Name'                 => ['First Name', 'First name'],
            'Last Name'                  => ['Last Name', 'NAME'],
            'e.g.:'                      => ['e.g.:', 'Example'],
            'Vehicle type:'              => ['Vehicle type:', 'Car Category'],
            'Pick up'                    => ['Pick up', 'Pick-Up'],
            'and return'                 => ['and return', 'Return'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from'])) {
            foreach ($this->fromEmails as $fromEmail) {
                if (stripos($headers['from'], $fromEmail) !== false) {
                    foreach ($this->subjects as $subject) {
                        if (stripos($headers['subject'], $subject) !== false) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[{$this->contains('Europcar')}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Driver Information:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Rental Details:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Pick up'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match("/{$this->opt($this->fromEmails)}/", $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->rentalCar($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function rentalCar(Email $email)
    {
        $r = $email->add()->rental();

        $company = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Thank you for booking with'))}]", null, true, "/{$this->opt($this->t('Thank you for booking with'))}\s*(.+)\./");

        if (!empty($company)) {
            $r->setCompany($company);
        }

        $confirmation = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Reservation number:'))}]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Reservation number:'))}[\:\s]+(\d{5,})/");

        if (empty($confirmation)) {
            $confirmation = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Reservation number:'))}]", null, true, "/{$this->opt($this->t('Reservation number:'))}[\:\s]+(\d{5,})/");
        }

        $reservDate = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Reservation date'))}]/following::text()[normalize-space()][1]", null, true, "/^[\:\s]*(\d+.*\d{2,4})$/");

        if (!empty($reservDate)) {
            $r->general()
                ->date(strtotime($reservDate));
        }

        $r->general()
            ->confirmation($confirmation);

        $firstName = $this->http->FindSingleNode("//text()[{$this->starts($this->t('First Name'))}]/following::text()[normalize-space()][1]", null, true, "/^\:?\s*(\D+)$/");
        $lastName = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Last Name'))}]/following::text()[normalize-space()][1]", null, true, "/^\:?\s*(\D+)$/");

        if (!empty($firstName) && !empty($lastName)) {
            $r->general()
                ->traveller($firstName . ' ' . $lastName, true);
        }

        $price = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Price Incl. VAT')]/following::text()[normalize-space()][1]/ancestor::td[1]", null, true, "/\/\s([\d\.\,\']+\D+)\b[ ]+\(/");

        if (empty($price)) {
            $price = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Total price:')]/following::text()[normalize-space()][1]");
        }

        if (preg_match("/^(?<currency>[A-Z]{3})\s*(?<total>[\d\.\,\']+)$/", $price, $m)
            || preg_match("/^(?<total>[\d\.\,\']+)\s+(?<currency>\D+)$/", $price, $m)) {
            $curr = $this->normalizeCurrency($m['currency']);
            $r->price()
                ->total(PriceHelper::parse($m['total'], $curr))
                ->currency($curr);
        }

        $carModel = $this->http->FindSingleNode("//text()[{$this->starts($this->t('e.g.:'))}]", null, true, "/\:\s*(.+)/");

        if (empty($carModel)) {
            $carModel = $this->http->FindSingleNode("//text()[{$this->starts($this->t('e.g.:'))}]/ancestor::td[1]/following-sibling::td[normalize-space()][1]", null, true, "/\:\s*(.+)/");
        }

        if (!empty($carModel)) {
            $r->setCarModel($carModel);
        }

        $carType = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Vehicle type:'))}]", null, true, "/\:\s*(.+)/");

        if (empty($carType)) {
            $carType = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Vehicle type:'))}]/ancestor::td[1]/following-sibling::td[normalize-space()][1]", null, true, "/\:\s*(.+)/");
        }

        if (!empty($carType)) {
            $r->setCarType($carType);
        }

        $pickUpInfo = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Pick up'))}]/ancestor::td[1]/descendant::text()[normalize-space()]"));

        if (empty($pickUpInfo) || preg_match("/^({$this->opt($this->t('Pick up'))})$/", $pickUpInfo)) {
            $pickUpInfo = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Pick up'))}]/ancestor::table[1]/descendant::tr[not(contains(normalize-space(), 'Information about the rental'))]/td[1]/descendant::text()[normalize-space()]"));
        }

        $re = "/(?:{$this->opt($this->t('Pick up'))}|{$this->opt($this->t('and return'))})\s+(?<dateTime>.*)\s+(?<location>(?s).*)\s+Tel[\s\:]+(?<phone>.*)\s+(?:Fax[\s\:]+(?<fax>.*)\s+)?Opening\s+hours[\s\:]+(?<hours>(?s).*)/i";

        if (preg_match($re, $pickUpInfo, $m)) {
            $r->pickup()
                ->date(strtotime(str_replace(' at ', ', ', $m['dateTime'])))
                ->location(str_replace("\n", ", ", $m['location']))
                ->phone($m['phone'])
                ->fax($m['fax'])
                ->openingHours(str_replace("\n", ", ", $m['hours']));

            if (isset($m['fax']) && !empty($m['fax'])) {
                $r->pickup()
                    ->fax($m['fax']);
            }
        }

        $dropOffInfo = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('and return'))}]/ancestor::td[1]/descendant::text()[normalize-space()]"));

        if (empty($dropOffInfo) || preg_match("/^\s*({$this->opt($this->t('and return'))})\s*$/iu", $dropOffInfo)) {
            $dropOffInfo = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Pick up'))}]/ancestor::table[1]/descendant::tr[not(contains(normalize-space(), 'Information about the rental'))]/td[string-length()>2][2]/descendant::text()[normalize-space()]"));
        }

        if (preg_match($re, $dropOffInfo, $m)) {
            $r->dropoff()
                ->date(strtotime(str_replace(' at ', ', ', $m['dateTime'])))
                ->location(str_replace("\n", ", ", $m['location']))
                ->phone($m['phone'])
                ->openingHours(str_replace("\n", ", ", $m['hours']));

            if (isset($m['fax']) && !empty($m['fax'])) {
                $r->dropoff()
                    ->fax($m['fax']);
            }
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

    protected function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false';
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
            return str_replace(' ', '\s+', preg_quote($s, '/'));
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

    private function normalizeCurrency($string)
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
            'AUD' => ['A$', 'Australian Dollars'],
            'EUR' => ['€', 'Euro'],
            'USD' => ['US Dollar', 'US Dollars'],
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
