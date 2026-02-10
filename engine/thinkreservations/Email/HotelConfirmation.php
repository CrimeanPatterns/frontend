<?php

namespace AwardWallet\Engine\thinkreservations\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class HotelConfirmation extends \TAccountChecker
{
    public $mailFiles = "thinkreservations/it-889732164.eml, thinkreservations/it-891469426.eml, thinkreservations/it-892566645.eml, thinkreservations/it-892781484.eml, thinkreservations/it-893310668.eml, thinkreservations/it-896127090.eml, thinkreservations/it-896405644.eml, thinkreservations/it-897586793.eml";

    public $lang = 'en';

    public $detectSubjects = [
        'en' => [
            'reservation has been confirmed!',
            'your reservation has been canceled',
            'Reservation at',
            'your receipt at',
            'your stay at',
            'Thank you for your stay',
        ],
    ];

    public $detectBody = [
        'en' => [],
    ];

    public static $dictionary = [
        "en" => [
            'cancelled'            => ['cancelled', 'canceled'],
            'Check-In Time:'       => ['Check-In Time:', 'Check-in time :', 'Check in', '*Check-in:'],
            'Check-Out Time:'      => ['Check-Out Time:', '*Check-out:'],
            'Rate ending'          => ['Subtotal:', 'Payments'],
            'guests'               => ['guests', 'guest', 'adults', 'adult'],
            'cancel'               => ['cancel', 'Cancel'],
            'Cancellation Policy:' => [
                'Cancellation Policy:', 'Cancellation Policy', 'CANCELLATION/DATE CHANGE POLICY:', 'CANCELLATION:',
            ],
            'You are'              => ['You are', 'We have you'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]thinkreservations\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers['from'], 'thinkreservations.com') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ((empty($parser->getHeaderArray('from')) && stripos($parser->getHeaderArray('from'), 'thinkreservations.com') === false)
            && $this->http->XPath->query("//a/@href[{$this->contains('thinkreservations')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('thinkreservations')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if (($this->http->XPath->query("//text()[{$this->starts($this->t('Confirmation ID:'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->starts($this->t('Room:'))}]")->length > 0)
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Subtotal:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Amount Paid:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Remaining Balance:'))}]")->length > 0
        ) {
            return true;
        }

        // detect Format for it-893310668.eml
        if ($this->http->XPath->query("//text()[{$this->starts($this->t('As requested, we have cancelled your reservation'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Thank you for your interest and time.'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Peak season'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Off-peak season'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Sincerely,'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseHotel(Email $email, PlancakeEmailParser $parser)
    {
        $h = $email->add()->hotel();

        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?|[ ]*noon)?', // 4:19PM    |    2:00 p. m.
        ];

        // collect reservation confirmation
        $confirmationText = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Confirmation ID:'))}]");

        if (preg_match("/^\s*(?<desc>{$this->opt($this->t('Confirmation ID'))})[\:\s]*(?<number>\w+)\s*$/", $confirmationText, $m)) {
            $h->general()
                ->confirmation($m['number'], $m['desc']);
        }

        // it-893310668.eml
        if (empty($h->getConfirmationNumbers())) {
            $confNumber = $this->http->FindSingleNode("//text()[{$this->contains($this->t('cancelled your reservation'))}]", null, true, "/{$this->opt($this->t('cancelled your reservation'))}\s+\((\w+)\)/");

            if (!empty($confNumber)) {
                $h->general()
                    ->confirmation($confNumber);
            }
        }

        if (empty($h->getConfirmationNumbers())) {
            $h->general()->noConfirmation();
        }

        $confirmationStatus = $this->re("/{$this->opt($this->t('reservation has been'))}\s*(\w+)\!?\s*$/", $parser->getSubject());

        if (!empty($confirmationStatus)) {
            $h->general()->status($confirmationStatus);

            if (in_array($confirmationStatus, (array) $this->t('cancelled'))) {
                $h->general()->cancelled();
            }
        }

        // collect traveller
        $traveller = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Name:'))}]", null, true, "/^\s*{$this->opt($this->t('Name:'))}\s*((?:.+?\+\s*)?[[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/");

        if (!empty($traveller)) {
            // it-892566645.eml
            if (strpos($traveller, '+') !== false) {
                $travellers = preg_split('/\s*\+\s*/', $traveller);
                $h->general()->travellers($travellers);
            } else {
                $h->general()->traveller($traveller);
            }
        }

        // collect main hotel info (name, address, phone)
        $socialMediaLogosXpath = "({$this->contains($this->t('facebook.com'), '@href')} or {$this->contains($this->t('instagram.com'), '@href')} or {$this->contains($this->t('Facebook'), '@alt')} or {$this->contains($this->t('Instagram'), '@alt')})";
        $hotelInfoNodes = $this->http->XPath->query("(//*[{$socialMediaLogosXpath}])[last()]/ancestor::td[1]/node()[not(./a)]/node()[string-length(normalize-space())>0]");

        // if there are no logos or links to social networks in the site's footer
        // it-896127090.eml
        if ($hotelInfoNodes->length === 0) {
            $hotelInfoNodes = $this->http->XPath->query("(//td)[last()]/node()[not(./a)]/node()[string-length(normalize-space())>0]");
        }

        if ($hotelInfoNodes->length > 0) {
            $h->hotel()
                ->name(trim($hotelInfoNodes[0]->nodeValue));
        }

        $address = '';

        foreach ($hotelInfoNodes as $hotelInfoNode) {
            // skip hotel name and PO Box
            if (trim($hotelInfoNode->nodeValue) === $h->getHotelName()
                || stripos($hotelInfoNode->nodeValue, 'PO Box') !== false
            ) {
                continue;
            }

            // collect phone and concatenated address
            if (preg_match("/^\s*{$this->opt($this->t('Phone:'))}?(?<phone>[\+\-\(\)\d ]+?)(?:\s*\|.+)?\s*$/", $hotelInfoNode->nodeValue, $m)) {
                $h->hotel()
                    ->phone(preg_replace("/[\+\-\(\)\s]+/", '', $m['phone']))
                    ->address($address);

                break;
            }

            // concatenate address
            if (empty($address)) {
                $address = trim($hotelInfoNode->nodeValue);
            } else {
                $address .= ', ' . trim($hotelInfoNode->nodeValue);
            }
        }

        // collect rooms info and all dates
        $firstRoomNodes = $this->http->XPath->query("//text()[{$this->starts($this->t('Room:'))}]/ancestor::tr[1]");
        $roomsCount = null;
        $dates = [];

        foreach ($firstRoomNodes as $firstRoomNode) {
            $r = $h->addRoom();
            $roomsCount++;

            // collect room type and description
            $typeAndDescText = $this->http->FindSingleNode("(.//text()[normalize-space()])[1]", $firstRoomNode);

            if (preg_match("/^\s*{$this->opt($this->t('Room:'))}\s*(?<type>.+?)(?:\s*\,\s*(?<desc>.+?))?\s*$/", $typeAndDescText, $m)) {
                $r->setType($m['type']);

                if (!empty($m['desc'])) {
                    $r->setDescription($m['desc']);
                }
            }

            // collect rate type
            $rateType = $this->http->XPath->query("./following-sibling::tr[1]", $firstRoomNode);
            $r->setRateType($rateType[0]->nodeValue);

            // collect date and rate for every night
            $rateNodes = $this->http->XPath->query("./following-sibling::tr", $firstRoomNode);
            $rates = [];

            foreach ($rateNodes as $rateNode) {
                if ($this->striposAll($rateNode->nodeValue, $this->t('Rate ending'))) {
                    break;
                }

                $date = $this->http->FindSingleNode("./td[normalize-space()][1]", $rateNode, true, "/^\s*(\d+\/\d+\/\d{4})\s*$/");

                if (!empty($date)) {
                    $date = $this->normalizeDate($date);
                    $dates[] = $date;
                } else {
                    continue;
                }

                $rateDesc = $this->http->FindSingleNode("./td[normalize-space()][2]", $rateNode);

                if ($rateDesc !== 'Room') {
                    continue;
                }

                $rateCurAmount = $this->http->FindSingleNode("./td[normalize-space()][3]", $rateNode);

                if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/", $rateCurAmount, $m)) {
                    $currency = $this->normalizeCurrency($m['currency']);
                    $rates[] += PriceHelper::parse($m['amount'], $currency);
                }
            }

            // if rates are equal
            if (count(array_unique($rates)) === 1) {
                $r->setRate(array_shift($rates) . $currency . "/night");

                continue;
            }

            // if rates are different
            foreach ($rates as $rate) {
                $r->addRate("{$rate}{$currency}/night");
            }
        }

        if (!empty($roomsCount)) {
            $h->booked()
                ->rooms($roomsCount);
        }

        // if one room is booked, then guestCount can be collected exactly
        if ($roomsCount === 1) {
            $guestCount = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Room:'))}]/following::text()[normalize-space()][1]", null, true, "/\s+(\d+)\s+{$this->opt($this->t('guests'))}/");
            $h->booked()
                ->guests($guestCount);
        }

        // calculate check-in and check-out dates
        $checkInDate = null;
        $checkOutDate = null;

        if (!empty($dates)) {
            $checkInDate = min($dates);
            $checkOutDate = strtotime('+1 day', max($dates));
        }

        // collect check-in and check-out times
        $checkInTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('CHECK-IN TIME'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*{$this->opt($this->t('is'))}\s+({$patterns['time']})[\s\.\,]+/")
            // it-891469426.eml
            ?? $this->http->FindSingleNode($xpath = "(//text()[{$this->contains($this->t('Check-In Time:'))}])[1]", null, true, $regex = "/\s*{$this->opt($this->t('Check-In Time:'))}\s*({$patterns['time']})\s*(?:[\-\.]|$)/");

        $checkOutTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('CHECK-OUT TIME'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*{$this->opt($this->t('is'))}\s+({$patterns['time']})[\s\.\,]+/")
            // it-891469426.eml
            ?? $this->http->FindSingleNode("(//text()[{$this->contains($this->t('Check-Out Time:'))}])[1]", null, true, "/\s*{$this->opt($this->t('Check-Out Time:'))}\s*({$patterns['time']})\s*(?:[\-\.]|$)/");

        // it-889732164.eml
        $checkInOutText = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Our standard check-in time is'))}]");

        if (preg_match("/\s*{$this->opt($this->t('Our standard check-in time is'))}\s+(?<checkIn>{$patterns['time']})\s+{$this->opt($this->t('and check-out is'))}\s+(?<checkOut>{$patterns['time']})\s*\./", $checkInOutText, $m)) {
            $checkInTime = $m['checkIn'];
            $checkOutTime = $m['checkOut'];
        }

        $checkInTime = $checkInTime ?? '00:00';
        $checkOutTime = $checkOutTime ?? '00:00';

        $checkInTime = preg_replace("/noon/i", 'PM', $checkInTime);
        $checkOutTime = preg_replace("/noon/i", 'PM', $checkOutTime);

        if (!empty($checkInDate) && !empty($checkOutDate)) {
            $h->booked()
                ->checkIn(strtotime($checkInTime, $checkInDate))
                ->checkOut(strtotime($checkOutTime, $checkOutDate));
        }

        // collect pricing details
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Total:'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $h->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);
        }

        $cost = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Subtotal:'))}]/following::text()[normalize-space()][1]", null, true, "/\D*([\d\.\,\']+)$/");

        if (!empty($currency) && $cost !== null) {
            $h->price()
                ->cost(PriceHelper::parse($cost, $currency));
        }

        if (!empty($currency)) {
            $feeNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Total:'))}]/ancestor::tr[1]/preceding-sibling::tr[preceding-sibling::tr[{$this->starts($this->t('Subtotal:'))}]]");

            foreach ($feeNodes as $feeRoot) {
                $feeName = trim($this->http->FindSingleNode("./descendant::td[string-length()>1][1]", $feeRoot), ':');
                $feeSumm = $this->http->FindSingleNode("./descendant::td[string-length()>1][2]", $feeRoot, true, "/\D*([\d\.\,\']+)$/");

                $h->price()
                    ->fee($feeName, PriceHelper::parse($feeSumm, $currency));
            }
        }

        // collect cancellation policy
        $cancellationPolicy = implode("\n", $this->http->FindNodes("//text()[{$this->starts($this->t('Cancellation Policy:'))}]/following::text()[{$this->contains($this->t('cancel'))}]", null, "/^\s*(?:\?\s*)?(.+?)\s*$/"));

        if (empty(trim($cancellationPolicy))) {
            $cancellationPolicy = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Should you need to cancel'))}]", null, true, "/^\s*({$this->opt($this->t('Should you need to cancel'))}.+?\.)/");
        }

        if (!empty($cancellationPolicy)) {
            $h->setCancellation(trim($cancellationPolicy, ' ?'));
            $this->detectDeadLine($h);
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->parseHotel($email, $parser);
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $in = [
            "/^(\d{2})\/(\d{2})\/(\d{4})$/", // 09/13/2019 => 13.09.2019
        ];
        $out = [
            '$2.$1.$3',
        ];

        return strtotime(preg_replace($in, $out, $date));
    }

    private function normalizeCurrency($s)
    {
        $sym = [
            '€'          => 'EUR',
            'US dollars' => 'USD',
            '£'          => 'GBP',
            '₹'          => 'INR',
            'CA$'        => 'CAD',
            '$'          => '$',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3}\D)(?:$|\s)#", $s)) {
            return $code;
        }

        $s = $this->re("#([^\d\,\.]+)#", $s);

        foreach ($sym as $f => $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return $s;
    }

    private function detectDeadLine(\AwardWallet\Schema\Parser\Common\Hotel $h): bool
    {
        $cancellationText = $h->getCancellation();

        if (empty($cancellationText)) {
            return false;
        }

        if (preg_match("/Reservations must be canceled or modified no less than (\d+ days?) prior to your arrival date/", $cancellationText, $m)
            || preg_match("/Cancellations or change of reservation dates must be made (\d+ days?) prior to the arrival date/", $cancellationText, $m)
            || preg_match("/^\s*(\d+ days?) prior to arrival date, rooms may be cancelled with no penalty/", $cancellationText, $m)
            || preg_match("/or (\d+ days?) for holidays or group reservations/", $cancellationText, $m)
        ) {
            $h->parseDeadlineRelative($m[1], '00:00');

            return true;
        }

        if (preg_match("/we require a minimum notice of \w+ (\(\d+\) days?) in order to avoid being charged for the room/", $cancellationText, $m)
            || preg_match("/Changes and Cancellations must be requested \w+ (\(\d+\) days?) prior to arrival/", $cancellationText, $m)
        ) {
            $h->parseDeadlineRelative(preg_replace("/[()]/", '', $m[1]), '00:00');

            return true;
        }

        if (preg_match("/All reservations must be cancelled within (\d+ hours?) prior to the arrival date to avoid a full day charge plus tax/", $cancellationText, $m)) {
            $h->parseDeadlineRelative($m[1], null);

            return true;
        }

        if (preg_match("/deposit is refundable minus a [$]\d+ processing fee/", $cancellationText)
            || preg_match("/All cancellations are subject to a \d+% cancellation fee regardless of when a reservation is cancelled/", $cancellationText)
            || preg_match("/minus \d+% cancellation fee/", $cancellationText)
        ) {
            $h->setNonRefundable(true);

            return true;
        }

        return false;
    }

    private function striposAll($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (stripos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && stripos($text, $needle) !== false) {
            return true;
        }

        return false;
    }
}
