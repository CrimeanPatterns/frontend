<?php

namespace AwardWallet\Engine\carlson\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ResConfirmation extends \TAccountChecker
{
    public $mailFiles = "carlson/it-901885041.eml";

    public $reFrom = "@bostonparkplaza.com";
    public $reBody = [
        'en' => ['Reservation Confirmation'],
    ];
    public $reSubject = [
        'Boston Park Plaza - Reservation Confirmation #',
    ];
    public $lang = '';
    public $hotelName = '';
    public static $dict = [
        'en' => [
        ],
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->AssignLang();

        $this->hotelName = str_replace("Fwd: ", "", $this->re("/^(.+)\s+\-\s+Reservation Confirmation/", $parser->getSubject()));

        $this->parseEmail($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//img[contains(@alt,'Boston Park Plaza')] | //a[contains(@href,'bostonparkplaza.com')] | //a[contains(@href,'www.newyorkerhotel.com')]")->length > 0) {
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

    private function parseEmail(Email $email)
    {
        $h = $email->add()->hotel();

        $h->general()
            ->confirmation($this->http->FindSingleNode("(//text()[contains(.,'Reservation Confirmation')])[1]", null, true, "#[\#:\s]+([A-Z\d]{5,})#"));

        $node = $this->http->FindSingleNode("//text()[starts-with(normalize-space(.),'website')]/ancestor::*[1]");

        if (stripos($node, "phone") !== false) {
            $h->hotel()
                ->name($this->http->FindSingleNode("//text()[starts-with(normalize-space(.),'website')]/ancestor::*[1]/preceding-sibling::*[1]"))
                ->address($this->re("#(.+?)\s+Phone:#", $node))
                ->phone($this->re("#Phone:\s+(.+)\s+E\-mail#", $node));
        } elseif (stripos($node, "phone") === false && !empty($this->hotelName)) {
            $h->hotel()
                ->address($this->re("#{$this->hotelName}\s+(.+)\s+website#", $node))
                ->name($this->hotelName);
        }

        $travellers = [];

        $travellers[] = $this->nextText("Guest Name:");
        $addGuests = $this->nextText("Additional Guests:");

        if (stripos($addGuests, "not provided") === false) {
            $pax = array_map("trim", $this->http->FindNodes("(.//text()[{$this->eq('Additional Guests:')}])[1]/ancestor::td[1]/following-sibling::td[1]//text()[normalize-space(.)]"));

            foreach ($pax as $p) {
                $travellers[] = $p;
            }
        }

        if (count($travellers) > 0) {
            $h->setTravellers($travellers);
        }

        $h->booked()
            ->checkIn(strtotime($this->normalizeDate($this->nextText("Check-in from:"))))
            ->checkOut(strtotime($this->normalizeDate($this->nextText("Check-out by:"))));

        $node = $this->nextText("Number of Guests:");

        $h->booked()
            ->guests($this->re("#Adults:\s+(\d+)#", $node));

        $kids = $this->re("#Children:\s+(\d+)#", $node);

        if (!empty($kids)) {
            $h->booked()
                ->kids($kids);
        }

        $r = $h->addRoom();

        $roomType = $this->nextText("Room Type:");

        if (!empty($roomType)) {
            $r->setType($roomType);
        }

        $roomDescription = $this->nextText("Smoking Preference:");

        if (!empty($roomDescription)) {
            $r->setDescription($roomDescription);
        }

        $rateType = $this->nextText("Rate Plan");

        if (!empty($rateType)) {
            $r->setRateType($rateType);
        }

        $roomRate = implode(", ", $this->http->FindNodes("//text()[normalize-space()='Room Rate:']/ancestor::tr[1]/descendant::td[2]/descendant::tr[not(contains(normalize-space(), 'Total Room Price'))]"));

        if (!empty($roomRate)) {
            $r->setRate($roomRate);
        }

        $tot = $this->getTotalCurrency($this->nextText("Total:"));

        if (!empty($tot['Total'])) {
            $h->price()
                ->total(PriceHelper::parse($tot['Total'], $tot['Currency']))
                ->currency($tot['Currency']);
        }

        $cancellation = $this->http->FindSingleNode("//text()[normalize-space(.)='Reservation Policies:']/ancestor::td[1]/following::td[1]//text()[normalize-space(.)][contains(.,'Cancellations')][1]");

        if (empty($cancellation)) {
            $cancellation = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Your reservation can be cancelled at no charge before')]");
        }

        if (!empty($cancellation)) {
            $h->general()
                ->cancellation($cancellation);

            $this->detectDeadLine($h);
        }
    }

    private function normalizeDate($date)
    {
        $in = [
            //3:00 PM, Wednesday, 17 May, 2017
            '#^\s*(\d+:\d+\s*(?:[ap]m)?),\s+\w+,\s+(\d+)\s+(\w+),\s+(\d{4})\s*$#i',
            //Friday, 6 June, 2025 from 4:00 PM
            '#^(\w+\,\s*\d+\s*\w+)\,\s*(\d{4})\s*(?:from|by)\s*([\d\:]+\s*A?P?M?)$#',
        ];

        $out = [
            '$2 $3 $4 $1',
            '$1 $2, $3',
        ];

        $str = $this->dateStringToEnglish(preg_replace($in, $out, $date));

        return $str;
    }

    private function nextText($field, $root = null, $n = 1)
    {
        $rule = $this->eq($field);

        return $this->http->FindSingleNode("(.//text()[{$rule}])[{$n}]/following::text()[normalize-space(.)][1]", $root);
    }

    private function eq($field)
    {
        $field = (array) $this->t($field);

        if (count($field) == 0) {
            return 'false';
        }

        return implode(" or ", array_map(function ($s) {
            return "normalize-space(.)=\"{$s}\"";
        }, $field));
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
            $body = $this->http->Response['body'];

            foreach ($this->reBody as $lang => $reBody) {
                foreach ($reBody as $r) {
                    if (stripos($body, $r) !== false) {
                        $this->lang = $lang;

                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getTotalCurrency($node)
    {
        $node = str_replace("$", "USD", $node);
        $tot = '';
        $cur = '';

        if (preg_match("#(?<c>[A-Z]{3})\s*(?<t>\d[\.\d\,\s]*\d*)#", $node, $m) || preg_match("#(?<t>\d[\.\d\,\s]*\d*)\s*(?<c>[A-Z]{3})#", $node, $m) || preg_match("#(?<c>\-*?)(?<t>\d[\.\d\,\s]*\d*)#", $node, $m)) {
            $m['t'] = preg_replace("#,(\d{3})$#", '$1', $m['t']);

            if (strpos($m['t'], ',') !== false && strpos($m['t'], '.') !== false) {
                if (strpos($m['t'], ',') < strpos($m['t'], '.')) {
                    $m['t'] = str_replace(',', '', $m['t']);
                } else {
                    $m['t'] = str_replace('.', '', $m['t']);
                    $m['t'] = str_replace(',', '.', $m['t']);
                }
            }
            $tot = str_replace(",", ".", str_replace(' ', '', $m['t']));
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

    private function detectDeadLine(Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        if (preg_match("/Your reservation can be cancelled at no charge before\s+(?<date>[\d\/]+)\,\s*(?<time>[\d\:]+\s*A?P?M)\s+local hotel/", $cancellationText, $m)) {
            $h->booked()
                ->deadline(strtotime($this->normalizeDate($m['date'] . ', ' . $m['time'])));
        }
    }
}
