<?php

namespace AwardWallet\Engine\foratravel\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class HotelReservation extends \TAccountChecker
{
	public $mailFiles = "foratravel/it-901474873.eml, foratravel/it-901614514.eml";
    public $subjects = [
        'Advisor Copy of Confirmation:',
    ];

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'detectPhrase'            => ["You're all set! You have successfully created a new booking.", "You're all set! We have processed your email and created a new booking"],
            'Hotel Confirmation #'    => ['Hotel Confirmation #', "Confirmation #"],
            'Supplier name'           => ['Supplier name'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'fora.travel') !== false) {
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
            $this->http->XPath->query("//a/@href[{$this->contains(['Fora'])}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Fora Travel'])}]")->length === 0
        ) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['detectPhrase']) && $this->http->XPath->query("//*[{$this->contains($dict['detectPhrase'])}]")->length > 0
                && !empty($dict['Hotel Confirmation #']) && $this->http->XPath->query("//*[{$this->contains($dict['Hotel Confirmation #'])}]")->length > 0
                && !empty($dict['Supplier name']) && $this->http->XPath->query("//*[{$this->contains($dict['Supplier name'])}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]fora\.travel$/', $from) > 0;
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

        $otaNumber = $this->http->FindSingleNode("//text()[{$this->contains($this->t('detectPhrase'))}][1]", null, false, "/{$this->opt($this->t('with ID'))}[ ]*([\dA-Z\-]{5,})\.$/");

        if ($otaNumber !== null){
            $h->obtainTravelAgency();

            $h->ota()
                ->confirmation($otaNumber);
        }

        $h->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Hotel Confirmation #'))}][1]/following::text()[normalize-space()][1]", null, false, "/^([\dA-z\-]{5,})$/"), 'Hotel Confirmation #');

        $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Guest name'))}]/following::text()[normalize-space()][1]", null, false, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/u");

        $h->addTraveller(preg_replace("/^(Mr|Mrs|Ms)\b/", "", $traveller), false);

        $h->hotel()
            ->name($this->http->FindSingleNode("//text()[{$this->eq($this->t('Supplier name'))}][1]/following::text()[normalize-space()][1]"));

        $address = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Address'))}][1]/following::text()[normalize-space()][1]");

        if ($address !== null) {
            $h->hotel()
                ->address($address);
        } else {
            $h->hotel()
                ->noAddress();
        }

        $dates = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Dates'))}][1]/following::text()[normalize-space()][1]");

        if (preg_match("/^(?<checkIn>\d{4}\-\d{1,2}\-\d{1,2})[ ]*\-[ ]*(?<checkOut>\d{4}\-\d{1,2}\-\d{1,2})$/u", $dates, $m)){
            $checkInTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Check-in'))}][1]/following::text()[normalize-space()][1]", null, false, "/^{$this->opt($this->t('Check-in between'))}[ ]*(\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]*\-/u");
            $checkOutTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Check-out'))}][1]/following::text()[normalize-space()][1]", null, false, "/^{$this->opt($this->t('Check-out before'))}[ ]*(\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)$/u");

            if ($checkInTime !== null && $checkOutTime !== null){
                $h->booked()
                    ->checkIn(strtotime($checkInTime, $this->normalizeDate($m['checkIn'])))
                    ->checkOut(strtotime($checkOutTime, $this->normalizeDate($m['checkOut'])));
            } else {
                $h->booked()
                    ->checkIn($this->normalizeDate($m['checkIn']))
                    ->checkOut($this->normalizeDate($m['checkOut']));
            }
        }



        $roomType = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Room Summary'))}][1]/following::text()[normalize-space()][1]");

        if (strlen($roomType) > 250){
            $roomType = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Room detail override'))}][1]/following::text()[normalize-space()][1]");
        }
        if ($roomType !== null){
            $h->addRoom()->setType($roomType);
        }

        $fax = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Fax'))}][1]/following::text()[normalize-space()][1]", null, true, '/^[\d.\-\s()]{5,}$/');

        if ($fax !== null){
            $h->hotel()->fax($fax);
        }

        $phone = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Phone'))}][1]/following::text()[normalize-space()][1]", null, true, '/^[\d.\-\s()]{5,}$/');

        if ($phone !== null){
            $h->hotel()->phone($phone);
        }

        $priceInfo = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Total'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^(?<currency>\D{1,3})\s*(?<price>\d[\d\.\,\']*)$/", $priceInfo, $m)
            || preg_match("/^(?<price>\d[\d\.\,\']*)\s*(?<currency>\D{1,3})$/", $priceInfo, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);

            $h->price()
                ->currency($currency)
                ->total(PriceHelper::parse($m['price'], $currency))
                ->cost(PriceHelper::parse($this->http->FindSingleNode("//text()[{$this->eq($this->t('Subtotal'))}]/following::text()[normalize-space()][1]", null, false, "/^(?:\D{1,3})?[ ]*(\d[\d\.\,\']*)[ ]*(?:\D{1,3})?$/"), $currency))
                ->tax(PriceHelper::parse($this->http->FindSingleNode("//text()[{$this->eq($this->t('Taxes & fees'))}]/following::text()[normalize-space()][1]", null, false, "/^(?:\D{1,3})?[ ]*(\d[\d\.\,\']*)[ ]*(?:\D{1,3})?$/"), $currency));
        }

        $cancellation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Cancellation Policy'))}]/following::text()[normalize-space()][1]");

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

        if (preg_match("/Fully refundable from \d{4}\-\d{1,2}+\-\d{1,2} until (\d{4}\-\d{1,2}+\-\d{1,2})\./", $cancellation, $m)) {
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
