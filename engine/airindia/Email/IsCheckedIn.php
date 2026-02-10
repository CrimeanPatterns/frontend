<?php

namespace AwardWallet\Engine\airindia\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class IsCheckedIn extends \TAccountChecker
{
    public $mailFiles = "airindia/it-882146303.eml, airindia/it-890612881.eml, airindia/it-9871997.eml, airindia/it-9901046.eml, airindia/it-9916291.eml";
    public $reFrom = "no_reply@airindia.in";
    public $reSubject = [
        "en" => "is checked in",
        "Confirmation document",
    ];
    public $date;
    public $reBody = ['AirIndia', 'FLYING WITH AIR INDIA'];
    public $reBody2 = [
        "en"=> ["Your boarding pass is attached to this email.", "Please find enclosed a confirmation document of your journey."],
    ];

    public static $dictionary = [
        "en" => [],
    ];

    public $lang = "en";

    private $pdfNamePattern = '.*pdf';

    public function parseHtml(Email $email, $pdfText)
    {
        $nodes = $this->http->XPath->query("//td[.//text()[{$this->eq('Depart')}]][following-sibling::td[descendant::text()[{$this->eq('Important Information')}]]]");

        $f = $email->add()->flight();

        // General
        $rls = array_unique($this->nextTexts("PNR", null, "/^\s*([A-Z\d]{5,7})\s*$/"));

        foreach ($rls as $rl) {
            $f->general()
                ->confirmation($rl);
        }

        foreach ($nodes as $root) {
            $traveller = preg_replace('/^\s*(.+?)\s*\/\s*(.+?)\s*$/', '$1 $2',
                $this->http->FindSingleNode("descendant::text()[normalize-space()][1]", $root, true, "/^(\D+)$/"));

            if (!in_array($traveller, array_column($f->getTravellers(), 0))) {
                $f->general()
                    ->traveller($traveller, true);
            }

            $ticket = $this->http->FindSingleNode(".//text()[" . $this->eq("TKNE") . "]/ancestor::td[1]/following-sibling::td[1]", $root);

            if (!empty($ticket) && !in_array($traveller, array_column($f->getTicketNumbers(), 0))) {
                $f->issued()
                    ->ticket($ticket, false, $traveller);
            }
            $account = $this->http->FindSingleNode(".//text()[" . $this->eq("FQTV") . "]/ancestor::td[1]/following-sibling::td[1]", $root);

            if (empty($account)) {
                $account = $this->http->FindSingleNode(".//text()[" . $this->starts("FF ") . "]/ancestor::td[1]",
                    $root, true, "/^FF AI(\d{5,})\s*(?:\/|$)/");
            }

            if (!empty($account) && !in_array($traveller, array_column($f->getAccountNumbers(), 0))) {
                $f->program()
                    ->account($account, false, $traveller);
            }

            // Segment
            $s = $f->addSegment();

            $dateText = $this->nextText("Date", $root);

            if (empty($this->date) && !empty($pdfText) && preg_match("/^\s*(\d{1,2})([[:alpha:]]{3,10})\s*$/", $dateText, $m)
                && preg_match("/(?:\n *| {3,})({$m[1]} ?{$m[2]} ?20\d{2})(?: {3,}|\s*\n)/i", $pdfText, $mp)
            ) {
                $this->date = strtotime($mp[1]);
            }
            $date = $this->normalizeDate($dateText);

            // Airline
            $s->airline()
                ->name($this->http->FindSingleNode(".//text()[" . $this->eq("Date") . "]/ancestor::tr[1]/preceding::tr[1]/td[1]", $root, true, "#^(\w{2})\d+$#"))
                ->number($this->http->FindSingleNode(".//text()[" . $this->eq("Date") . "]/ancestor::tr[1]/preceding::tr[1]/td[1]", $root, true, "#^[A-Z\d]{2}(\d+)$#"))
            ;

            // Departure
            $s->departure()
                ->code($this->http->FindSingleNode(".//text()[" . $this->eq("Date") . "]/ancestor::tr[1]/preceding::tr[1]/td[2]", $root, true, "#^([A-Z]{3}) to [A-Z]{3}$#"));

            if (!empty($s->getDepCode())) {
                $s->departure()
                    ->terminal($this->http->FindSingleNode("(//text()[" . $this->starts("All AI International") . "][{$this->contains($s->getDepCode())}])[1]",
                        null, true, "# (T\d+) at {$s->getDepCode()}#"), true, true);
            }

            $time = $this->http->FindSingleNode(".//text()[" . $this->eq("Depart") . "]/ancestor::td[1]/following-sibling::td[1]", $root, true, "/^[\W\d]+$/");

            if (!empty($date)) {
                if (!empty($time)) {
                    $s->departure()
                        ->date(strtotime($time, $date));
                } else {
                    $s->departure()
                        ->noDate();
                }
            }

            // Arrival
            $s->arrival()
                ->code($this->http->FindSingleNode(".//text()[" . $this->eq("Date") . "]/ancestor::tr[1]/preceding::tr[1]/td[2]", $root, true, "#^[A-Z]{3} to ([A-Z]{3})$#"));

            if (!empty($s->getArrCode())) {
                $s->arrival()
                    ->terminal($this->http->FindSingleNode("(//text()[" . $this->starts("All AI International") . "][{$this->contains($s->getArrCode())}])[1]",
                        null, true, "# (T\d+) at {$s->getArrCode()}#"), true, true);
            }

            $time = $this->http->FindSingleNode(".//text()[" . $this->eq("Arrive") . "]/ancestor::td[1]/following-sibling::td[1]", $root);

            if (!empty($date)) {
                if (!empty($time)) {
                    $s->arrival()
                        ->date(strtotime($time, $date));
                } else {
                    $s->arrival()
                        ->noDate();
                }
            }

            // Extra
            $s->extra()
                ->cabin($this->http->FindSingleNode(".//text()[" . $this->eq("Cabin") . "]/ancestor::td[1]/following-sibling::td[1]", $root))
                ->seat($this->http->FindSingleNode(".//text()[" . $this->eq("Seat") . "]/ancestor::td[1]/following-sibling::td[1]", $root), true, true, $traveller)
            ;
        }
    }

    public function detectEmailFromProvider($from)
    {
        return strpos($from, $this->reFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (strpos($headers["from"], $this->reFrom) === false) {
            return false;
        }

        foreach ($this->reSubject as $re) {
            if (stripos($headers["subject"], $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $body = $parser->getHTMLBody();

        $detectedProvider = false;

        foreach ($this->reBody as $re) {
            if (strpos($body, $re) !== false) {
                $detectedProvider = true;

                break;
            }
        }

        if ($detectedProvider === false) {
            return false;
        }

        foreach ($this->reBody2 as $re) {
            if ($this->http->XPath->query("//node()[{$this->contains($re)}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->date = EmailDateHelper::getEmailDate($this, $parser);
        $pdfText = '';

        if (empty($this->date)) {
            $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

            foreach ($pdfs as $pdf) {
                $pdfText .= \PDF::convertToText($parser->getAttachmentBody($pdf));
            }
        }

        $this->http->FilterHTML = true;
        $itineraries = [];

        foreach ($this->reBody2 as $lang => $re) {
            if ($this->http->XPath->query("//node()[{$this->contains($re)}]")->length > 0) {
                $this->lang = $lang;

                break;
            }
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        $this->parseHtml($email, $pdfText);

        return $email;
    }

    public function ParsePlanEmail(\PlancakeEmailParser $parser)
    {
        $this->date = EmailDateHelper::getEmailDate($this, $parser);
        $pdfText = '';

        if (empty($this->date)) {
            $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

            foreach ($pdfs as $pdf) {
                $pdfText .= \PDF::convertToText($parser->getAttachmentBody($pdf));
            }
        }

        $this->http->FilterHTML = true;
        $itineraries = [];

        foreach ($this->reBody2 as $lang => $re) {
            if ($this->http->XPath->query("//node()[{$this->contains($re)}]")->length > 0) {
                $this->lang = $lang;

                break;
            }
        }

        $this->parseHtml($itineraries, $pdfText);
        $class = explode('\\', __CLASS__);
        $result = [
            'emailType'  => end($class) . ucfirst($this->lang),
            'parsedData' => [
                'Itineraries' => $itineraries,
            ],
        ];

        return $result;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function t($word)
    {
        // $this->http->log($word);
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function normalizeDate($string)
    {
        // $this->http->log($str);
        $year = date("Y", $this->date);
        $in = [
            "#^(\d+)([^\s\d]+)$#", //02Jun
        ];
        $out = [
            "$1 $2 $year",
        ];
        $string = preg_replace($in, $out, $string);

        // if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
        //     if ($en = MonthTranslate::translate($m[1], $this->lang)) {
        //         $str = str_replace($m[1], $en, $str);
        //     }
        // }

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $string, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $string = str_replace($m[1], $en, $string);
            }
        }

        if (!empty($this->date) && $this->date > strtotime('01.01.2000') && strpos($string, '%year%') !== false && preg_match('/^\s*(?<date>\d+ \w+) %year%(?:\s*,\s(?<time>\d{1,2}:\d{1,2}.*))?$/', $string, $m)) {
            // $this->logger->debug('$date (no week, no year) = '.print_r( $m['date'],true));
            $string = EmailDateHelper::parseDateRelative($m['date'], $this->date);

            if (!empty($string) && !empty($m['time'])) {
                return strtotime($m['time'], $string);
            }

            return $string;
        } elseif ($year > 2000 && preg_match("/^(?<week>\w+), (?<date>\d+ \w+ .+)/u", $string, $m)) {
            // $this->logger->debug('$date (week no year) = '.print_r( $string,true));
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));

            return EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/^\d+ [[:alpha:]]+ \d{4}(,\s*\d{1,2}:\d{2}(?: ?[ap]m)?)?$/ui", $string)) {
            // $this->logger->debug('$date (year) = '.print_r( $str,true));
            return strtotime($string);
        } else {
            return null;
        }

        return null;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function amount($s)
    {
        return (float) str_replace(",", ".", preg_replace("#[.,](\d{3})#", "$1", $this->re("#([\d\,\.]+)#", $s)));
    }

    private function currency($s)
    {
        $sym = [
            '€'=> 'EUR',
            '$'=> 'USD',
            '£'=> 'GBP',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3})(?:$|\s)#", $s)) {
            return $code;
        }
        $s = $this->re("#([^\d\,\.]+)#", $s);

        foreach ($sym as $f=> $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return null;
    }

    private function nextText($field, $root = null, $regexp = null)
    {
        $rule = $this->eq($field);

        return $this->http->FindSingleNode("(.//text()[{$rule}])[1]/following::text()[normalize-space(.)][1]", $root, true, $regexp);
    }

    private function nextTexts($field, $root = null, $regexp = null)
    {
        $rule = $this->eq($field);

        return $this->http->FindNodes(".//text()[{$rule}]/following::text()[normalize-space(.)][1]", $root, $regexp);
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(" or ", array_map(function ($s) { return "normalize-space(.)=\"{$s}\""; }, $field));
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(" or ", array_map(function ($s) { return "starts-with(normalize-space(.), \"{$s}\")"; }, $field));
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(" or ", array_map(function ($s) { return "contains(normalize-space(.), \"{$s}\")"; }, $field));
    }
}
