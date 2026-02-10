<?php

namespace AwardWallet\Engine\transfeero\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Transfer extends \TAccountChecker
{
	public $mailFiles = "transfeero/it-893147549.eml";
    public $lang = 'en';

    public $subjects = [
        'Booking Confirmation',
    ];

    public static $dictionary = [
        "en" => [
            'detectPhrase' => ['We are delighted that you have chosen Transfeero for your ride.'],
            'Booking confirmation' => ['Booking confirmation'],
            'Vehicle class' => ['Vehicle class'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@transfeero.com') !== false) {
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
        if ($this->http->XPath->query("//a[contains(@href, 'transfeero.com')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains(['Formitable', 'www.transfeero.com', 'TRANSFEERO'])}]")->length > 0) {
            return true;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['detectPhrase']) && $this->http->XPath->query("//*[{$this->contains($dict['detectPhrase'])}]")->length > 0
                && !empty($dict['Booking confirmation']) && $this->http->XPath->query("//*[{$this->contains($dict['Booking confirmation'])}]")->length > 0
                && !empty($dict['Vehicle class']) && $this->http->XPath->query("//*[{$this->contains($dict['Vehicle class'])}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]transfeero\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {

        $this->Transfer($email);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function Transfer(Email $email)
    {
        $t = $email->add()->transfer();

        $t->general()
            ->traveller($this->http->FindSingleNode("//td[{$this->eq($this->t('Guest:'))}]/following-sibling::td[normalize-space()][1]", null, true, "/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])$/u"));

        $confirmation = $this->http->FindSingleNode("//td[{$this->eq($this->t('Date and time:'))}]/preceding::text()[normalize-space()][starts-with(normalize-space(), '#')][1]", null, true, "/^\#[ ]*([A-Z\d]{8})$/");

        if ($confirmation !== null){
            $t->general()
                ->confirmation($confirmation);
        } else if (!$this->http->FindSingleNode("//td[{$this->eq($this->t('Date and time:'))}]/preceding::text()[normalize-space()][starts-with(normalize-space(), '#')][1]")){
            $t->general()
                ->noConfirmation();
        }

        $s = $t->addSegment();

        $date = $this->http->FindSingleNode("//td[{$this->eq($this->t('Date and time:'))}]/following-sibling::td[normalize-space()][1]");

        if (preg_match("/^(?<date>\w+[ ]+\d+\,[ ]+\d{4})[ ]*\d{1,2}\:\d{1,2}[ ]*\((?<time>\d{1,2}\:\d{1,2}[ ]*[Aa]?[Pp]?[Mm]?)\)$/", $date, $m)){
            if ($m['date'] !== null && $m['time'] !== null){
                $s->departure()
                    ->date(strtotime($m['date'] . ' ' . $m['time']));
            }
        }

        $s->departure()
            ->address($depAddress = $this->http->FindSingleNode("//td[{$this->eq($this->t('From:'))}]/following-sibling::td[normalize-space()][1]"));

        if (preg_match("/\(([A-Z]{3})\)/", $depAddress, $m)){
            $s->departure()
                ->code($m[1]);
        }

        $arrAddress = $this->http->FindSingleNode("//td[{$this->eq($this->t('To:'))}]/following-sibling::td[normalize-space()][not({$this->eq($this->t('To be defined with your driver'))})][1]");

        if ($arrAddress !== null){
            $s->arrival()
                ->address($arrAddress);

            if (preg_match("/\(([A-Z]{3})\)/", $arrAddress, $m)){
                $s->arrival()
                    ->code($m[1]);
            }
        }

        $s->arrival()
            ->noDate();

        $s->extra()
            ->type($this->http->FindSingleNode("//td[{$this->eq($this->t('Vehicle class:'))}]/following-sibling::td[normalize-space()][1]"))
            ->adults($this->http->FindSingleNode("//td[{$this->eq($this->t('Passengers:'))}]/following-sibling::td[normalize-space()][1]"));

        $duration = $this->http->FindSingleNode("//td[{$this->eq($this->t('Hourly Service duration:'))}]/following-sibling::td[normalize-space()][1]", null, false, "/^(.+)[ ]*\-[ ]*/");

        if ($duration !== null){
            $s->extra()
                ->duration($duration);
        }

        $totalPrice = $this->http->FindSingleNode("//td[{$this->eq($this->t('Price:'))}]/following-sibling::td[normalize-space()][1]");

        if (preg_match('/^(?<currency>\D{1,3})[ ]*(?<amount>\d[\,\.\'\d ]*)$/', $totalPrice, $matches)
            || preg_match('/^(?<amount>\d[\,\.\'\d ]*)[ ]*(?<currency>\D{1,3})$/', $totalPrice, $matches)) {
            $currency = $this->normalizeCurrency($matches['currency']);

            $t->price()
                ->currency($currency)
                ->total(PriceHelper::parse($matches['amount'], $currency));
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

    private function normalizeCurrency(string $string): string
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
            'EUR' => ['€'],
            'USD' => ['US$'],
            'SGD' => ['SG$'],
            'ZAR' => ['R'],
        ];

        foreach ($currencies as $currencyCode => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency) {
                    return $currencyCode;
                }
            }
        }

        return $string;
    }
}
