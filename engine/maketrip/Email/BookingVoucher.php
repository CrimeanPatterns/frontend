<?php

namespace AwardWallet\Engine\maketrip\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

// parsers with similar formats: goibibo/HotelBookingVoucher, maketrip/HotelBooking

class BookingVoucher extends \TAccountChecker
{
    public $mailFiles = "maketrip/it-153613355.eml, maketrip/it-891874000.eml, maketrip/it-892850493.eml, maketrip/it-899652051.eml, maketrip/it-900010422.eml, maketrip/it-900044466.eml";
    public $subjects = [
        'Your Booking Confirmation Voucher for',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'starts-cancellation' => ['Free Cancellation (100% refund)', 'This booking is non-refundable'],
            'Amount'              => ['Amount', 'Paid Amount'],
            'Modify Guests'       => ['Modify Guests', 'Add Guests'],
            'Adults'              => ['Adults', 'Adult'],
            'Children'            => ['Children', 'Child'],
            'Room'                => ['Room', 'ROOM(s)', 'ENTIRE APARTMENT', 'BED(s)', 'ENTIRE VILLA'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@makemytrip.com') !== false) {
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
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'MakeMyTrip')]")->length === 0
        && $this->http->XPath->query("//a[contains(@href, 'makemytrip')]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Voucher'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Important information'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Change Dates'))}]")->length > 0) {
            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Primary Guest'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Important information'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('DETAILS & Inclusions'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]makemytrip\.com$/', $from) > 0;
    }

    public function ParseHotel(Email $email)
    {
        $h = $email->add()->hotel();

        $guestText = array_filter(explode(", ", $this->http->FindSingleNode("//text()[{$this->contains($this->t('Primary Guest'))}][1]/ancestor::td[1]/following::text()[normalize-space()][1][contains(normalize-space(), ',')]", null, true, "/^(\D+)$/")));

        if (count($guestText) === 0) {
            $guestText = [$this->http->FindSingleNode("//text()[{$this->contains($this->t('Primary Guest'))}]/ancestor::td[1]", null, true, "/^(.+)\s*\({$this->opt($this->t('Primary Guest'))}/u")];
        }

        $conf = $this->http->FindSingleNode("//text()[normalize-space()='PNR:']/following::text()[normalize-space()][1]", null, true, "/^([\d\_,]{7,})$/");

        if (empty($conf) && $this->http->XPath->query("//text()[{$this->starts($this->t('PNR:'))}]")->length === 0
            && empty($this->http->FindSingleNode("//text()[normalize-space()='Booking ID:']/preceding::text()[normalize-space()][1]", null, true, "/^([\d\_A-Z]{7,})$/"))
        ) {
            $h->general()
                ->noConfirmation();
        } else {
            $confs = preg_split("/\s*,\s*/", $conf);

            foreach ($confs as $cf) {
                $h->general()
                    ->confirmation($cf);
            }
        }

        $h->general()
            ->travellers($guestText)
            ->date(strtotime($this->http->FindSingleNode("//text()[normalize-space()='Booking ID:']/ancestor::tr[1]/following::text()[normalize-space()][1]", null, true, "/{$this->opt($this->t('Booked on'))}\s*(.+)\)/u")));

        $cancellation = $this->http->FindSingleNode("//text()[normalize-space()='Important information']/following::text()[{$this->starts($this->t('starts-cancellation'))}][1]");

        if (!empty($cancellation)) {
            $h->general()
                ->cancellation($cancellation);
        }

        $h->hotel()
            ->name($this->http->FindSingleNode("//text()[normalize-space()='DETAILS & Inclusions']/following::text()[normalize-space()][1]"))
            ->address($this->http->FindSingleNode("//text()[normalize-space()='DETAILS & Inclusions']/following::text()[not(ancestor::td[1][.//img[contains(@src, 'star.')]])][normalize-space()][2]"))
            ->phone($this->http->FindSingleNode("//text()[normalize-space()='DETAILS & Inclusions']/following::text()[not(ancestor::td[1][.//img[contains(@src, 'star.')]])][normalize-space()][3]", null, true, "/^(?:.{3,}\,)?\s*([\d\-\s\+]{5,}?)\s*(?:\:|,|$)/u"), true, true);

        $checkIn = implode("\n", $this->http->FindNodes("//text()[normalize-space()='Check-in']/ancestor::td[1]//text()[normalize-space()]"));

        if (
            preg_match("/{$this->opt($this->t('Check-in'))}\s*(?<date>.+)\s+{$this->opt($this->t('After'))}\s*(?<time>[\d\:]+\s*A?P?M)\b/s", $checkIn, $m)
            || preg_match("/{$this->opt($this->t('Check-in'))}\s*(?<date>.+)\n(?<time>\d{1,2}:\d{2}(?:\s*[AP]M)?)\s*-\s*/s", $checkIn, $m)
            || preg_match("/{$this->opt($this->t('Check-in'))}\s*(?<date>.+)/s", $checkIn, $m)
        ) {
            $h->booked()
                ->checkIn(strtotime(preg_replace("/\s+/", ' ',
                    $m['date'] . (!empty($m['time']) ? ', ' . $m['time'] : ''))));
        }

        $checkOut = implode("\n", $this->http->FindNodes("//text()[normalize-space()='Check-out']/ancestor::td[1]//text()[normalize-space()]"));

        if (
            preg_match("/{$this->opt($this->t('Check-out'))}\s+(?<date>.+)\s+{$this->opt($this->t('Before'))}\s*(?<time>[\d\:]+\s*A?P?M)\b/s", $checkOut, $m)
            || preg_match("/{$this->opt($this->t('Check-out'))}\s*(?<date>.+)\n\d{1,2}:\d{2}(?:\s*[AP]M)?\s*-\s*(?<time>\d{1,2}:\d{2}(?:\s*[AP]M)?)/s", $checkOut, $m)
            || preg_match("/{$this->opt($this->t('Check-out'))}\s*(?<date>.+)/s", $checkOut, $m)
        ) {
            $h->booked()
                ->checkOut(strtotime(preg_replace("/\s+/", ' ',
                    $m['date'] . (!empty($m['time']) ? ', ' . $m['time'] : ''))));
        }

        if (empty($checkIn) && empty($checkOut)) {
            $timing = implode("\n", $this->http->FindNodes("//text()[normalize-space()='Stay timings']/ancestor::td[1]//text()[normalize-space()]"));

            if (preg_match("/{$this->opt($this->t('Stay timings'))}\s+(?<time1>\d{1,2}:\d{2}(?:\s*[AP]M)?)\s*-\s*(?<time2>\d{1,2}:\d{2}(?:\s*[AP]M)?)\s*\(.+?\)\s*(?<date>.+)/s", $timing, $m)
            ) {
                $h->booked()
                    ->checkIn(strtotime(preg_replace("/\s+/", ' ',
                        $m['date'] . ', ' . $m['time1'])))
                    ->checkOut(strtotime(preg_replace("/\s+/", ' ',
                        $m['date'] . ', ' . $m['time2'])));
            }
        }

        $adults = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Primary Guest'))}]/preceding::text()[{$this->contains($this->t('Adults'))}][1]/ancestor::td[1]", null, true, "/\s*(\d+)\s*{$this->opt($this->t('Adult'))}/u");

        if (!empty($adults)) {
            $h->booked()
                ->guests($adults);
        }

        $kids = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Primary Guest'))}]/preceding::text()[{$this->contains($this->t('Adults'))}][1]/ancestor::td[1]", null, true, "/\s*(\d+)\s*{$this->opt($this->t('Children'))}/");

        if (!empty($kids)) {
            $h->booked()
                ->kids($kids);
        }

        $h->booked()
            ->rooms($this->http->FindSingleNode("//text()[{$this->contains($this->t('Primary Guest'))}]/following::text()[{$this->contains($this->t('Room'))}][1]/ancestor::td[1]", null, true, "/\s*(\d+)\s*{$this->opt($this->t('Room'))}/"));

        $roomNodes = $this->http->XPath->query("//text()[contains(normalize-space(), 'Primary Guest')]/following::text()[{$this->contains($this->t('Room'))}][1]/following::td[1]/descendant::text()[normalize-space()='Read more']/ancestor::table[1]");

        if ($roomNodes->length == 0) {
            $roomNodes = $this->http->XPath->query("//text()[contains(normalize-space(), 'Primary Guest')]/following::text()[{$this->contains($this->t('Room'))}][1][following::text()[normalize-space()='Add Rooms']]/following::text()[normalize-space()][1]/ancestor::*[not(.//text()[normalize-space()='Add Rooms'])][1]");
        }

        foreach ($roomNodes as $roomNode) {
            $roomType = $this->http->FindSingleNode("./descendant::text()[normalize-space()][1]", $roomNode);
            $roomDesc = $this->http->FindSingleNode("./descendant::text()[normalize-space()][last()-1]", $roomNode);

            if (!empty($roomType) || !empty($roomDesc)) {
                $count = 1;

                if (preg_match("/^\s*(\d+) X (.+)/", $roomType, $m)) {
                    $roomType = $m[2];
                    $count = $m[1];
                }

                for ($i = 1; $i <= $count; $i++) {
                    $room = $h->addRoom();

                    if (!empty($roomType)) {
                        $room->setType($roomType);

                        if (preg_match("/^\s*Day Use Room From (\d{1,2}) (Am) To (\d{1,2}) (Pm) \(/", $roomType, $m)
                            && !empty($h->getCheckInDate()) && $h->getCheckInDate() === strtotime('00:00',
                                $h->getCheckInDate())
                            && !empty($h->getCheckOutDate()) && $h->getCheckOutDate() === strtotime('00:00',
                                $h->getCheckOutDate())
                        ) {
                            $h->booked()
                                ->checkIn(strtotime($m[1] . ':00' . $m[2], $h->getCheckInDate()))
                                ->checkOut(strtotime($m[3] . ':00' . $m[4], $h->getCheckOutDate()));
                        }
                    }

                    if (!empty($roomDesc)) {
                        $room->setDescription($roomDesc);
                    }
                }
            }
        }

        $price = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Amount'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^([A-Z]{3})\s*([\d\.\,]+)$/u", $price, $m)) {
            $h->price()
                ->currency($m[1])
                ->total(PriceHelper::parse($m[2], $m[1]));
        }

        $this->detectDeadLine($h);
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $otaConf = $this->http->FindSingleNode("//text()[normalize-space()='Booking ID:']/following::text()[normalize-space()][1]", null, true, "/^([\dA-Z]{15,})$/u");

        $email->ota()
            ->confirmation($otaConf);

        $this->ParseHotel($email);

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

    private function detectDeadLine(Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        if (preg_match('/Free Cancellation \(100[%] refund\) till (\w+\,\s*\d+\s*\w+\s*\d{4}\,\s*[\d\:]+\s*A?P?M)/ui',
            $cancellationText, $m)) {
            $h->booked()->deadline(strtotime($m[1]));
        }

        if (preg_match('/This booking is non\-refundable/ui', $cancellationText, $m)) {
            $h->booked()->nonRefundable();
        }
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field));
    }
}
