<?php

namespace AwardWallet\Engine\asiana\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\WeekTranslate;

class TravelInformation extends \TAccountChecker
{
    public $mailFiles = "asiana/it-10000615.eml, asiana/it-893219540.eml";

    public $reFrom = "flyasiana.com";
    public $reBody = [
        'en' => ['Thank you for flying Asiana Airlines', 'Expected flight time'],
    ];
    public $reSubject = [
        '[Asiana Airline] Flight Information',
    ];
    public $lang = '';
    public static $dict = [
        'en' => [
        ],
    ];
    private $date;

    public function ParsePlanEmail(\PlancakeEmailParser $parser)
    {
        $this->date = strtotime($parser->getDate());
        $this->AssignLang();

        $its = $this->parseEmail();
        $a = explode('\\', __CLASS__);

        return [
            'parsedData' => ['Itineraries' => $its],
            'emailType'  => $a[count($a) - 1] . ucfirst($this->lang),
        ];
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//img[contains(@src,'flyasiana.com')]")->length > 0) {
            return $this->AssignLang();
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], $this->reFrom) !== false && isset($this->reSubject)) {
            foreach ($this->reSubject as $reSubject) {
                if (stripos($headers["subject"], $reSubject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, $this->reFrom) !== false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dict);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dict);
    }

    public function dateStringToEnglish($date)
    {
        if (preg_match('#[[:alpha:]]+#iu', $date, $m)) {
            $monthNameOriginal = $m[0];

            if ($translatedMonthName = \AwardWallet\Engine\MonthTranslate::translate($monthNameOriginal, $this->lang)) {
                return preg_replace("#$monthNameOriginal#i", $translatedMonthName, $date);
            }
        }

        return $date;
    }

    private function parseEmail()
    {
        $it = ['Kind' => 'T', 'TripSegments' => []];
        $it['RecordLocator'] = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Reservation No.'))}]/following::text()[normalize-space(.)!=''][1]", null, true, "#^([A-Z\d]+)$#");
        $it['Passengers'] = preg_replace(["/ (MR|MRS|MISS|MSTR|MS)$/", "/^\s*(.+?)\s*\/\s*(.+?)\s*$/"], ['', '$2 $1'],
            array_values(array_unique(array_filter(preg_replace("/^\s*TBD in flights\s*$/i", '',
            $this->http->FindNodes("//text()[{$this->eq($this->t('Passenger Name'))}]/ancestor::tr[1][{$this->starts($this->t('Flight'))}]/ancestor::table[1]/descendant::tr[position()>1]/td[position() = 1 or position() = 2]", null, "#^\s*(?:\d\.\s*)?(\D+)\s*$#"))))));

        $nodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Departure'))}]/ancestor::tr[1][{$this->starts($this->t('Flight'))}]/ancestor::table[1]/descendant::tr[position()>1]");

        foreach ($nodes as $root) {
            $seg = [];

            $seg['DepCode'] = TRIP_CODE_UNKNOWN;
            $seg['ArrCode'] = TRIP_CODE_UNKNOWN;

            $node = $this->http->FindSingleNode("./td[1]", $root);

            if (preg_match("#^([A-Z\d]{2})\s*(\d+)$#", $node, $m)) {
                $seg['AirlineName'] = $m[1];
                $seg['FlightNumber'] = $m[2];

                $xpathTraveller = "//text()[{$this->eq($this->t('Passenger Name'))}]/ancestor::tr[1][{$this->starts($this->t('Flight'))}]/ancestor::table[1]/descendant::tr[position()>1][td[1][starts-with(normalize-space(),'{$m[1]}{$m[2]}')]]";
                $seg['Seats'][] = $this->http->FindSingleNode($xpathTraveller . "/td[3]", null, true, "/^\s*\d{1,3}[A-Z]\s*$/");
                $seg['Meal'] = [];
                $seg['Meal'] = array_merge($seg['Meal'], $this->http->FindNodes($xpathTraveller . "/td[position()>3]"));
                $rowspan = $this->http->FindSingleNode($xpathTraveller . "/td[1]/@rowspan", null, true, "/^\s*\d+\s*$/");

                if (!empty($rowspan)) {
                    for ($i = 1; $i < $rowspan; $i++) {
                        $seg['Seats'][] = $this->http->FindSingleNode($xpathTraveller . "/following-sibling::tr[{$i}]/td[2]", null, true, "/^\s*\d{1,3}[A-Z]\s*$/");
                        $seg['Meal'] = array_merge($seg['Meal'], $this->http->FindNodes($xpathTraveller . "/following-sibling::tr[{$i}]/td[position()>2]"));
                    }
                }

                $seg['Seats'] = array_filter($seg['Seats']);
                $seg['Meal'] = implode(', ', array_unique(array_filter(preg_replace("/^\s*N\/A\s*$/", '', $seg['Meal']))));
            }

            $seg['BookingClass'] = $this->http->FindSingleNode("./td[2]", $root, true, "#^[A-Z]{1,2}$#");

            $date = $this->normalizeDate($this->http->FindSingleNode("./td[3]", $root));
            $node = $this->http->FindSingleNode("./td[4]", $root);

            if (preg_match("#(.+?)\s*(\d+:\d+)#", $node, $m)) {
                $seg['DepName'] = $m[1];
                $seg['DepDate'] = strtotime($m[2], $date);
            }

            $node = $this->http->FindSingleNode("./td[5]", $root);

            if (preg_match("#(.+?)(?:\[([\+\-]\s*\d)\])?\s*(\d+:\d+)#", $node, $m)) {
                $seg['ArrName'] = $m[1];
                $seg['ArrDate'] = strtotime($m[3], $date);

                if (isset($m[2]) && !empty($m[2])) {
                    $seg['ArrDate'] = strtotime($m[2] . " days", $seg['ArrDate']);
                }
            }

            $it['Status'] = $this->http->FindSingleNode("./td[6]", $root);

            $seg['Duration'] = $this->http->FindSingleNode("./td[7]", $root);

            $it['TripSegments'][] = $seg;
        }

        return [$it];
    }

    private function normalizeDate($date)
    {
        $year = date('Y', $this->date);
        $in = [
            '#^(\d+)\s*([A-Z][a-z]+)\s*([A-Z][a-z]+)$#',
        ];
        $out = [
            '$1 $2 ' . $year,
        ];
        $outWeek = [
            '$3',
        ];
        $weeknum = WeekTranslate::number1(WeekTranslate::translate(preg_replace($in, $outWeek, $date), $this->lang));
        $str = $this->dateStringToEnglish(preg_replace($in, $out, $date));
        $str = EmailDateHelper::parseDateUsingWeekDay($str, $weeknum);

        return $str;
    }

    private function t($s)
    {
        if (!isset($this->lang) || !isset(self::$dict[$this->lang][$s])) {
            return $s;
        }

        return self::$dict[$this->lang][$s];
    }

    private function AssignLang()
    {
        if (isset($this->reBody)) {
            foreach ($this->reBody as $lang => $reBody) {
                if ($this->http->XPath->query("//*[contains(normalize-space(.),'{$reBody[0]}')]")->length > 0
                 && $this->http->XPath->query("//*[contains(normalize-space(.),'{$reBody[1]}')]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function getTotalCurrency($node)
    {
        $node = str_replace("€", "EUR", $node);
        $node = str_replace("$", "USD", $node);
        $node = str_replace("£", "GBP", $node);
        $tot = '';
        $cur = '';

        if (preg_match("#(?<c>[A-Z]{3})\s*(?<t>\d[\.\d\,\s]*\d*)#", $node, $m) || preg_match("#(?<t>\d[\.\d\,\s]*\d*)\s*(?<c>[A-Z]{3})#", $node, $m) || preg_match("#(?<c>\-*?)(?<t>\d[\.\d\,\s]*\d*)#", $node, $m)) {
            $m['t'] = preg_replace('/\s+/', '', $m['t']);			// 11 507.00	->	11507.00
            $m['t'] = preg_replace('/[,.](\d{3})/', '$1', $m['t']);	// 2,790		->	2790		or	4.100,00	->	4100,00
            $m['t'] = preg_replace('/,(\d{2})$/', '.$1', $m['t']);	// 18800,00		->	18800.00
            $tot = $m['t'];
            $cur = $m['c'];
        }

        return ['Total' => $tot, 'Currency' => $cur];
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field));
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field));
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(' or ', array_map(function ($s) {
            return 'contains(normalize-space(.),"' . $s . '")';
        }, $field));
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", $field) . ')';
    }
}
