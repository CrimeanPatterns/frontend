<?php

namespace AwardWallet\Engine\yatra\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class HotelVoucher extends \TAccountChecker
{
	public $mailFiles = "yatra/it-892516808.eml";
    public $subjects = [
        'Hotel Confirmation Voucher',
    ];

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'detectPhrase'       => ['Your hotel booking is confirmed'],
            'BOOKING SUMMARY'    => ['BOOKING SUMMARY'],
            'Check In'           => ['Check In'],
            'discountName'       => ['E-Cash Redeemed (-)', 'Promotional Discount (-)'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'yatra.com') !== false) {
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
        if (
            $this->http->XPath->query("//a/@href[{$this->contains(['yatracdn.com'])}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Yatra'])}]")->length === 0
        ) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['detectPhrase']) && $this->http->XPath->query("//*[{$this->contains($dict['detectPhrase'])}]")->length > 0
                && !empty($dict['BOOKING SUMMARY']) && $this->http->XPath->query("//*[{$this->contains($dict['BOOKING SUMMARY'])}]")->length > 0
                && !empty($dict['Check In']) && $this->http->XPath->query("//*[{$this->contains($dict['Check In'])}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]yatra\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email): Email
    {
        $this->HotelReservation($email);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function HotelReservation(Email $email)
    {
        $h = $email->add()->hotel();

        $h->obtainTravelAgency();

        $h->ota()
            ->confirmation($this->http->FindSingleNode("(//td[{$this->starts($this->t('Reference Number'))}])[1]", null, false, "/^{$this->opt($this->t('Reference Number'))}[ ]*\-[ ]*([\dA-Z\-]{5,})$/"), 'Reference Number');

        $h->general()
            ->confirmation($this->http->FindSingleNode("(//td[{$this->starts($this->t('Hotel Confirmation Number'))}])[1]", null, false, "/^{$this->opt($this->t('Hotel Confirmation Number'))}[ ]*\-[ ]*([\dA-Z\-]{5,})[ ]*\(/"), 'Hotel Confirmation Number');

        $traveller = $this->http->FindSingleNode("//td[{$this->starts($this->t('Primary Guest:'))}]", null, false, "/^{$this->opt($this->t('Primary Guest:'))}\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/u");

        $h->addTraveller(preg_replace("/^(Mr|Mrs|Ms)\b/", "", $traveller), false);

        $h->hotel()
            ->name($this->http->FindSingleNode("//table[{$this->starts($this->t('Booking Details'))}]/preceding-sibling::table[normalize-space()][1]/descendant::tr[normalize-space()][1]"))
            ->address($this->http->FindSingleNode("//table[{$this->starts($this->t('Booking Details'))}]/preceding-sibling::table[normalize-space()][1]/descendant::tr[normalize-space()][2]"));

        $checkInDate = $this->http->FindSingleNode("//table[{$this->starts($this->t('Check In'))}]/descendant::tr[normalize-space()][2]");
        $checkInTime = $this->http->FindSingleNode("//table[{$this->starts($this->t('Check In'))}]/descendant::tr[normalize-space()][3]", null, false, "/^{$this->opt($this->t('Time:'))}[ ]*(\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)$/");

        if ($checkInDate !== null && $checkInTime !== null){
            $h->booked()->checkIn($this->normalizeDate($checkInDate . ' ' . $checkInTime));
        }

        $checkOutDate = $this->http->FindSingleNode("//table[{$this->starts($this->t('Check Out'))}]/descendant::tr[normalize-space()][2]");
        $checkOutTime = $this->http->FindSingleNode("//table[{$this->starts($this->t('Check Out'))}]/descendant::tr[normalize-space()][3]", null, false, "/^{$this->opt($this->t('Time:'))}[ ]*(\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)$/");

        if ($checkOutDate !== null && $checkOutTime !== null){
            $h->booked()->checkOut($this->normalizeDate($checkOutDate . ' ' . $checkOutTime));
        }

        $h->booked()
            ->rooms($this->http->FindSingleNode("//td[{$this->starts($this->t('No. of Rooms:'))}]", null, false, "/^{$this->opt($this->t('No. of Rooms:'))}\s*(\d+)$/"));

        $roomType = $this->http->FindSingleNode("//td[{$this->starts($this->t('Room Type:'))}]", null, false, "/^{$this->opt($this->t('Room Type:'))}\s*(.+)$/");

        if ($roomType !== null){
            $h->addRoom()->setType($roomType);
        }


        $guestsCount = $this->http->FindSingleNode("//td[{$this->starts($this->t('Pax:'))}]", null, false, "/^{$this->opt($this->t('Pax:'))}[ ]*([0-9]+)[ ]*{$this->opt($this->t('Adult(s)'))}/");
        $kidsCount = $this->http->FindSingleNode("//td[{$this->starts($this->t('Pax:'))}]", null, false, "/^{$this->opt($this->t('Pax:'))}[ ]*[0-9]+[ ]*{$this->opt($this->t('Adult(s)'))}[ ]*\,[ ]*([0-9]+)[ ]*{$this->opt($this->t('Child(s)'))}/");

        $h->booked()
            ->guests($guestsCount)
            ->kids($kidsCount);

        $priceInfo = $this->http->FindSingleNode("//td[{$this->eq($this->t('Total Amount'))}]/following-sibling::td[1]");

        if (preg_match("/^(?<currency>\D{1,3})\s*(?<price>\d[\d\.\,\']*)$/", $priceInfo, $m)
            || preg_match("/^(?<price>\d[\d\.\,\']*)\s*(?<currency>\D{1,3})$/", $priceInfo, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);

            $h->price()
                ->currency($currency)
                ->total(PriceHelper::parse($m['price'], $currency))
                ->cost(PriceHelper::parse($this->http->FindSingleNode("//td[{$this->starts($this->t('Accommodation charges'))}]/following-sibling::td[1]", null, false, "/^(?:\D{1,3})?[ ]*(\d[\d\.\,\']*)[ ]*(?:\D{1,3})?$/"), $currency))
                ->fee('Convenience Fee', PriceHelper::parse($this->http->FindSingleNode("//td[{$this->starts($this->t('Convenience Fee'))}]/following-sibling::td[1]", null, false, "/^(?:\D{1,3})?[ ]*(\d[\d\.\,\']*)[ ]*(?:\D{1,3})?$/"), $currency));

            $discountNodes = $this->http->FindNodes("//td[{$this->starts($this->t('discountName'))}]/following-sibling::td[1]", null, "/^(?:\D{1,3})?[ ]*(\d[\d\.\,\']*)[ ]*(?:\D{1,3})?$/");
            $discountArray = [];
            foreach ($discountNodes as $discountNode){
                $discountArray[] = PriceHelper::parse($discountNode, $currency);
            }

            if (!empty($discountArray)){
                $h->price()
                    ->discount(array_sum($discountArray));
            }
        }

        $cancellation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('CANCELLATION POLICY'))}]/following::tr[normalize-space()][1]");

        if ($cancellation !== null) {
            $h->general()
                ->cancellation($cancellation);
        }

        $this->detectDeadLine($h);
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function detectDeadLine(Hotel $h)
    {
        if (empty($cancellation = $h->getCancellation())) {
            return;
        }

        if (preg_match("/to[ ]*(\d{1,2}\/\S+\/\d{4}\s*[\d\:]+\s*A?P?M?)[ ]*.* you will be charged/", $cancellation, $m)
            || preg_match("/Full refund if you cancel this booking by (\d{1,2}\-\S+\-\d{2}\s*[\d\:]+\s*A?P?M?).+/", $cancellation, $m)) {
            $h->booked()
                ->deadline($this->normalizeDate($m[1]));
        }

        if (preg_match("/No refund if you cancel this booking(\.|later than)/", $cancellation, $m)) {
            $h->booked()
                ->nonRefundable();
        }

    }

    private function normalizeCurrency($s)
    {
        $sym = [
            '€'         => 'EUR',
            '£'         => 'GBP',
            '₹'         => 'INR',
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

        return $s;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^(\d{1,2})[\/\-](\S+)[\/\-](\d{2,4})\s*([\d\:]+\s*A?P?M?)$#u", // 11/Feb/2024 10:10
        ];
        $out = [
            "$1 $2 $3 $4",
        ];
        $str = preg_replace($in, $out, $str);

        return strtotime($str);
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
                return "normalize-space(.)=\"{$s}\"";
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
                return str_replace(' ', '\s+', preg_quote($s, '/'));
            }, $field)) . ')';
    }
}
