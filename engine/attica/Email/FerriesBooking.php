<?php

namespace AwardWallet\Engine\attica\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FerriesBooking extends \TAccountChecker
{
    public $mailFiles = "attica/it-472859264.eml, attica/it-482049545.eml";
    public $subjects = [
        'Blue Star Ferries|',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'Itinerary' => ['Itinerary', 'Itineraries'],
        ],
    ];
    private $date;

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@attica-group.com') !== false) {
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
        return $this->http->XPath->query("//text()[contains(normalize-space(), '@attica-group.com')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('This is not a ticket'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Itinerary'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]attica-group\.com$/', $from) > 0;
    }

    public function ParseFerry(Email $email)
    {
        $f = $email->add()->ferry();

        $travellers = array_unique($this->http->FindNodes("//img[contains(@src, 'icon-summary-seat')]/preceding::text()[normalize-space()][1]"));

        if (empty($travellers)) {
            $travellers = array_unique($this->http->FindNodes("//img[contains(@src, 'icon-summary-cabin')]/preceding::text()[normalize-space()][1]"));
        }

        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[normalize-space()='RESERVATION NUMBER']/ancestor::table[1]", null, true, "/{$this->opt($this->t('RESERVATION NUMBER'))}\s*(\d+)/"))
            ->travellers($travellers, true);

        $xpath = "//img[contains(@src, 'icon-passenger')]/preceding::text()[normalize-space()][1]/ancestor::tr[2]";
        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $root) {
            $s = $f->addSegment();
            $segText = $this->http->FindSingleNode(".", $root);

            if (preg_match("/^(?<depName>.+)\s+\((?<depDate>\w+\s*\d+\s*\w+\,\s*[\d\:]+)\)\s+(?<arrName>.+)\s+\((?<arrDate>\w+\s+\d+\s+\w+\,\s+[\d\:]+)\)\s*(?<guests>\d+).*\s*vessel:\s*(?<vessel>.+)$/", $segText, $m)) {
                $s->setVessel($m['vessel']);

                $s->departure()
                    ->name($m['depName'])
                    ->date($this->normalizeDate($m['depDate']));

                $s->arrival()
                    ->name($m['arrName'])
                    ->date($this->normalizeDate($m['arrDate']));

                $s->setAdults($m['guests']);

                $cabin = [];

                foreach ($travellers as $traveller) {
                    $cabin[] = $this->http->FindSingleNode("//text()[{$this->eq($s->getDepName() . ' - ' . $s->getArrName())}]/following::text()[{$this->eq($traveller)}][1]/following::text()[normalize-space()][1]");
                }

                $s->extra()
                    ->cabin(implode(", ", array_unique(preg_replace("/(?:FULL FARE|GREEK ISLAND PASS.*|ISIC CARD.*)/", "", $cabin))));

                $car = $this->http->FindSingleNode("//text()[{$this->eq($s->getDepName() . ' - ' . $s->getArrName())}]/following::text()[{$this->contains($travellers[0])}][1]/ancestor::table[3]/descendant::text()[normalize-space()='Car']/following::text()[normalize-space()][1]", null, true, "/^[A-Z\d]+\s*([\d\.\,]+\s*m)/");

                if (!empty($car)) {
                    $carInfo = $s->addVehicle();
                    $carInfo->setLength($car);
                }

                $pet = $this->http->FindSingleNode("//text()[{$this->eq($s->getDepName() . ' - ' . $s->getArrName())}]/following::text()[{$this->contains($travellers[0])}][1]/ancestor::table[3]/descendant::text()[normalize-space()='Pet']/following::text()[normalize-space()][1]");

                if (!empty($pet)) {
                    $s->setPets($pet);
                }
            }
        }

        $total = $this->http->FindSingleNode("//text()[normalize-space()='Total:']/following::text()[normalize-space()][1]", null, true, "/^(\D{1,3}\s*[\d\.\,]+)/");

        if (preg_match("/(?<currency>\D{1,3})\s*(?<total>[\d\.\,]+)/", $total, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $f->price()
                ->total(PriceHelper::parse($m['total'], $currency))
                ->currency($currency);

            $feeNodes = $this->http->XPath->query("//text()[normalize-space()='Total:']/ancestor::tr[1]/preceding-sibling::tr[contains(normalize-space(), ':')]");

            foreach ($feeNodes as $feeRoot) {
                $feeName = $this->http->FindSingleNode("./descendant::td[string-length()>1][1]", $feeRoot);
                $feeSumm = $this->http->FindSingleNode("./descendant::td[string-length()>1][2]", $feeRoot, true, "/\D*([\d\.\,]+)$/");

                if (stripos($feeName, 'Total Passenger & Vehicle Fare:') !== false) {
                    $f->price()
                        ->cost(PriceHelper::parse($feeSumm, $currency));
                } else {
                    $f->price()
                        ->fee($feeName, PriceHelper::parse($feeSumm, $currency));
                }
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->date = strtotime($parser->getHeader('date'));

        $this->ParseFerry($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

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
            return str_replace(' ', '\s+', preg_quote($s));
        }, $field)) . ')';
    }

    private function normalizeDate($date)
    {
//        $this->logger->debug('$date = '.print_r( $date,true));
        $year = date("Y", $this->date);

        $in = [
            // SAT 03 MAY, 10:00
            "/^(\w+)\s+(\d+)\s+(\w+)\,\s*([\d\:]+)$/",
        ];
        $out = [
            "$1, $2 $3 $year, $4",
        ];
        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        if (preg_match("#^(?<week>[[:alpha:]]{2,}), (?<date>\d+ [[:alpha:]]{3,} \d{4})\s*$#u", $date, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));
            $date = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("#\b\d{4}\b#", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }

        return $date;
    }

    private function normalizeCurrency($string)
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
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
