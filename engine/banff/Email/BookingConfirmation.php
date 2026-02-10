<?php

namespace AwardWallet\Engine\banff\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class BookingConfirmation extends \TAccountChecker
{
    public $mailFiles = "banff/it-917978499.eml, banff/it-928773403.eml";
    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'beforeStatus' => ['your booking is', 'your booking has been'],
            'to'           => ['to', 'To'],
            'Adults'       => ['Adults', 'Seniors'],
        ],
    ];

    private $detectFrom = "banffairporter.com";
    private $detectSubject = [
        // en
        'Banff Airporter booking confirmation#',
        'Your Banff Airporter booking',
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]banffairporter\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], 'Banff Airporter') === false
        ) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//a/@href[{$this->contains(['banffairporter.com'])}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Thank you for choosing Banff Airporter'])}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('beforeStatus'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Confirmation #:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Departure:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Destination:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Passengers:'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->parseTransferHtml($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class));

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

    private function parseTransferHtml(Email $email)
    {
        $t = $email->add()->transfer();

        $patterns = [
            'time'          => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.    |    3pm
            'travellerName' => "(?:{$this->opt(['Dr', 'Miss', 'Mrs', 'Mr', 'Ms', 'Mme', 'Mr/Mrs', 'Mrs/Mr'])}[\.\s]*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])", // Mr. Hao-Li Huang => Hao-Li Huang
        ];

        // collect reservation confirmation and traveller
        $t->general()
            ->confirmation($this->http->FindSingleNode("(//text()[{$this->eq("Confirmation #:")}])[1]/following::text()[normalize-space()][1]",
                null, true, "/^\s*(\d{4,})\s*$/"), "Confirmation #", true)
            ->confirmation($this->http->FindSingleNode("(//text()[{$this->eq("Reference ID:")}])[1]/following::text()[normalize-space()][1]",
                null, true, "/^\s*([\dA-Z]{4,})\s*$/"), "Reference ID")
            ->status($this->http->FindSingleNode("//text()[{$this->contains($this->t("beforeStatus"))}]",
                null, true, "/{$this->opt($this->t("beforeStatus"))}\s*(\w+?)[\s\.]*$/"))
            ->traveller($this->http->FindSingleNode("(//text()[{$this->starts("Guest Name:")}])[1]",
                null, true, "/^\s*{$this->opt($this->t('Guest Name:'))}\s*{$patterns['travellerName']}\s*$/"))
        ;

        // collect phone
        $providerPhone = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hours of response from'))}]/preceding::table[1]/descendant::a[1]/@href", null, true, "/^\s*tel:(\d+)\s*$/");

        if (!empty($providerPhone)) {
            $t->addProviderPhone($providerPhone);
        }

        // collect pricing info
        $cost = $this->http->FindSingleNode("(//*[{$this->eq('Booking Total')}]/following-sibling::*[normalize-space()])[1]");

        if (preg_match("/^\s*(?<total>\d[\d\,\.]*)\s*(?<currency>[^\s\d]{1,3})\s*$/u", $cost, $m)
            || preg_match("/^\s*(?<currency>[^\s\d]{1,3})\s*(?<total>\d[\d\,\.]*)\s*$/u", $cost, $m)
        ) {
            $currency = $this->normalizeCurrency($m['currency']);
            $t->price()
                ->cost(PriceHelper::parse($m['total'], $currency))
                ->currency($currency);
        }
        $price = $this->http->FindSingleNode("(//*[{$this->eq('Total')}]/following-sibling::*[normalize-space()])[1]");

        if (preg_match("/^\s*(?<total>\d[\d\,\.]*)\s*(?<currency>[^\s\d]{1,3})\s*$/u", $price, $m)
            || preg_match("/^\s*(?<currency>[^\s\d]{1,3})\s*(?<total>\d[\d\,\.]*)\s*$/u", $price, $m)
        ) {
            $currency = $this->normalizeCurrency($m['currency']);
            $t->price()
                ->total(PriceHelper::parse($m['total'], $currency))
                ->currency($currency);
        }

        foreach (['Fuel Surcharge', 'GST'] as $feeName) {
            $price = $this->http->FindSingleNode("(//*[{$this->eq($feeName)}]/following-sibling::*[normalize-space()])[1]");

            if (preg_match("/^\s*(?<total>\d[\d\,\.]*)\s*(?<currency>[^\s\d]{1,3})\s*$/u", $price, $m)
                || preg_match("/^\s*(?<currency>[^\s\d]{1,3})\s*(?<total>\d[\d\,\.]*)\s*$/u", $price, $m)
            ) {
                $currency = $this->normalizeCurrency($m['currency']);
                $t->price()
                    ->fee($feeName, PriceHelper::parse($m['total'], $currency));
            }
        }

        // collect segments
        $xpath = "//*[count(*[normalize-space()]) = 5 and *[normalize-space()][1][{$this->starts($this->t('Date'))}] and *[normalize-space()][2][{$this->starts($this->t('Departure'))}]]";
        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $root) {
            $s = $t->addSegment();

            $dateAndCodeText = $this->http->FindSingleNode("./descendant::text()[{$this->eq($this->t('Date:'))}]/following::text()[normalize-space()][1]", $root);
            $date = null;

            // collect date (day) and depCode/arrCode
            if (preg_match("/^\s*(?<date>.+?)\s*@\s*\d+\s+(?:(?<depCode>[A-Z]{3})|.+?)\s+{$this->opt($this->t('to'))}\s+(?:(?<arrCode>[A-Z]{3})|.+?)$/", $dateAndCodeText, $m)) {
                $date = $this->normalizeDate($m['date']);

                if (!empty($m['depCode'])) {
                    $s->setDepCode($m['depCode']);
                }

                if (!empty($m['arrCode'])) {
                    $s->setArrCode($m['arrCode']);
                }
            }

            // collect departure name and time
            $depNameAndTime = $this->http->FindSingleNode("./descendant::text()[{$this->eq($this->t('Departure:'))}]/following::text()[normalize-space()][1]", $root);

            if (preg_match("/^\s*(?<depName>.+?)\s*@\s*(?<depTime>{$patterns['time']})\s*$/", $depNameAndTime, $m)) {
                $s->departure()
                    ->name($m['depName'])
                    ->date(strtotime($m['depTime'], $date));
            }

            // collect arrival name and time
            // Destination: Banff Centre For Arts (Prof. Development Centre) @ 20:30 - 20:35
            $arrNameAndTime = $this->http->FindSingleNode("./descendant::text()[{$this->eq($this->t('Destination:'))}]/following::text()[normalize-space()][1]", $root);

            if (preg_match("/^\s*(?<arrName>.+?)\s*@\s*(.+?\-\s+)?(?<arrTime>{$patterns['time']})\s*$/", $arrNameAndTime, $m)) {
                $arrDate = strtotime($m['arrTime'], $date);

                if ($arrDate < $s->getDepDate()) {
                    $arrDate = strtotime('+1 day', $arrDate);
                }

                $s->arrival()
                    ->name($m['arrName'])
                    ->date($arrDate);
            }

            // set geotips
            $s->setDepGeoTip('ca');
            $s->setArrGeoTip('ca');

            // collect adults and kids count
            $passengersText = $this->http->FindSingleNode("./descendant::text()[{$this->eq($this->t('Passengers:'))}]/following::text()[normalize-space()][1]", $root);

            $s->setAdults($this->re("/\b(\d+)\s+{$this->opt($this->t('Adults'))}/", $passengersText), true, true);
            $s->setKids($this->re("/\b(\d+)\s+{$this->opt($this->t('Children'))}/", $passengersText), true, true);
        }

        return true;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
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

    private function normalizeCurrency(string $string): string
    {
        $string = trim($string);
        $currencies = [
            'CAD'   => ['$'], // not error
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

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }

        $in = [
            '/^\s*(\w+)\s+(\d+)\s+(\d{4})\s*$/iu', // Jul 14 2025
        ];
        $out = [
            '$2 $1 $3',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
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
