<?php

namespace AwardWallet\Engine\deltav\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class TripItinerary extends \TAccountChecker
{
	public $mailFiles = "deltav/it-927540081.eml";
    private $detectSubjects = [
        // en
        'Confirmed! Delta Vacations Reservation To',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'FLIGHT' => ['OUTBOUND FLIGHT', 'RETURN FLIGHT'],
            'check-in after'            => ['check-in after', 'check-in time is', 'Check-in time is', 'Hotel check-in time is'],
            'Check-out time is between' => ['Check-out time is between', 'check-out time is', 'Check-out time is', 'checkout time is'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (empty($headers['from']) || self::detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        foreach ($this->detectSubjects as $detectSubjects) {
            if (stripos($headers['subject'], $detectSubjects) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@t.deltavacations.com') !== false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('thank you for choosing Delta Vacations'))}]")->length > 0) {
            return $this->http->XPath->query("//text()[{$this->contains($this->t('Delta Vacations Confirmation #'))}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($this->t("Who's Traveling?"))}]")->length > 0;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $otaConf = array_unique($this->http->FindNodes("//text()[{$this->eq($this->t('Delta Vacations Confirmation #'))}]/following::text()[normalize-space()][1]", null, "/^(\d{8,})$/u"));

        foreach ($otaConf as $conf) {
            $email->ota()
                ->confirmation($conf);
        }

        $flightXpath = "//table[{$this->starts($this->t('Flight Confirmation #'))} and ./preceding-sibling::table[normalize-space()][1][{$this->eq($this->t('FLIGHT'))}]]/following-sibling::table[normalize-space()][{$this->contains($this->t('→'))} and {$this->contains($this->t('↓'))}]/descendant::table[normalize-space()][1]/descendant::tbody[normalize-space()][1]";
        $flightXpath2 = "//table[{$this->starts($this->t('Flight Confirmation #'))} and ./preceding-sibling::table[normalize-space()][1][{$this->eq($this->t('FLIGHT'))}]]/following-sibling::table[normalize-space()][{$this->contains($this->t('→'))} and {$this->contains($this->t('↓'))}]/descendant::table[normalize-space()][1]";

        $flightBlocks = $this->http->XPath->query($flightXpath);

        if ($flightBlocks->length === 0){
            $flightBlocks = $this->http->XPath->query($flightXpath2);
        }

        if ($flightBlocks->length > 0) {
            foreach ($flightBlocks as $flightBlock){
                $this->ParseFlight($email, $flightBlock);
            }
        }

        $hotelsXpath = "//table[{$this->starts($this->t('Hotel Confirmation #'))}]/following-sibling::table[normalize-space()][1]/descendant::table[normalize-space()][1]/descendant::tbody[normalize-space()][1]";
        $hotelsXpath2 = "//table[{$this->starts($this->t('Hotel Confirmation #'))}]/following-sibling::table[normalize-space()][1]/descendant::table[normalize-space()][1]";

        $hotelBlocks = $this->http->XPath->query($hotelsXpath);

        if ($hotelBlocks->length === 0){
            $hotelBlocks = $this->http->XPath->query($hotelsXpath2);
        }

        if ($hotelBlocks->length > 0) {
            foreach ($hotelBlocks as $hotelBlock){
                $this->ParseHotel($email, $hotelBlock);
            }
        }

        $rentalXpath = "//table[{$this->starts($this->t('Car Rental Confirmation #'))}]/following-sibling::table[normalize-space()][1]/descendant::table[normalize-space()][1]/descendant::tbody[normalize-space()][1]";
        $rentalXpath2 = "//table[{$this->starts($this->t('Car Rental Confirmation #'))}]/following-sibling::table[normalize-space()][1]/descendant::table[normalize-space()][1]";

        $rentalBlocks = $this->http->XPath->query($rentalXpath);

        if ($rentalBlocks->length === 0){
            $rentalBlocks = $this->http->XPath->query($rentalXpath2);
        }

        if ($rentalBlocks->length > 0) {
            foreach ($rentalBlocks as $rentalBlock){
                $this->ParseRental($email, $rentalBlock);
            }
        }

        $totalPrice = $this->http->FindSingleNode("//td[{$this->eq($this->t('Vacation Total'))}]/following-sibling::td[normalize-space()][ancestor::tr[count(./child::td) = 2][1]][1]");

        if (preg_match('/^(?<currency>\D{1,3})[ ]*(?<amount>\d[,.‘\'\d ]*)$/u', $totalPrice, $matches)) {
            // PHP 10,168.57
            $currencyCode = $matches['currency'];
            $email->price()
                ->currency($currencyCode)
                ->total(PriceHelper::parse($matches['amount'], $currencyCode));
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseRental(Email $email, $rentalBlock){
        $r = $email->add()->rental();

        $r->general()
            ->traveller($this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Vehicle:'))} and {$this->contains($this->t('Pickup:'))}][1]/descendant::text()[{$this->eq($this->t('Driver(s):'))}][1]/following::text()[normalize-space()][1]", $rentalBlock, false, "/^([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])$/u"))
            ->confirmation(str_replace(" ", '' ,$this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/preceding-sibling::table[normalize-space()][{$this->starts($this->t('Car Rental Confirmation #'))}][1]", $rentalBlock, false, "/^{$this->opt($this->t('Car Rental Confirmation #'))}[ ]*([A-z\d\/ ]+)$/u")));

        $r->extra()
            ->company($this->http->FindSingleNode("./child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]", $rentalBlock));

        $carInfo = $this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Vehicle:'))} and {$this->contains($this->t('Pickup:'))}][1]/descendant::text()[{$this->eq($this->t('Vehicle:'))}][1]/following::text()[normalize-space()][1]", $rentalBlock);

        if (preg_match("/^(?<type>.+)[ ]*(?:\•.+)[ ]*\•[ ]*(?<model>.+)$/u", $carInfo, $m)){
            $r->car()
                ->type($m['type'])
                ->model($m['model']);
        }

        $r->pickup()
            ->date($this->normalizeDate(preg_replace("/([ ]*\•[ ]*)/u", " ", $this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Vehicle:'))} and {$this->contains($this->t('Pickup:'))}][1]/descendant::text()[{$this->eq($this->t('Pickup:'))}][1]/following::text()[normalize-space()][1]", $rentalBlock))))
            ->location($this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Vehicle:'))} and {$this->contains($this->t('Pickup:'))}][1]/descendant::text()[{$this->eq($this->t('Pickup:'))}][1]/following::text()[{$this->eq($this->t('Location:'))}][1]/following::text()[normalize-space()][1]", $rentalBlock));

        $r->dropoff()
            ->date($this->normalizeDate(preg_replace("/([ ]*\•[ ]*)/u", " ", $this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Vehicle:'))} and {$this->contains($this->t('Pickup:'))}][1]/descendant::text()[{$this->eq($this->t('Drop Off:'))}][1]/following::text()[normalize-space()][1]", $rentalBlock))))
            ->location($this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Vehicle:'))} and {$this->contains($this->t('Pickup:'))}][1]/descendant::text()[{$this->eq($this->t('Pickup:'))}][1]/following::text()[{$this->eq($this->t('Location:'))}][1]/following::text()[normalize-space()][1]", $rentalBlock));
    }

    public function ParseHotel(Email $email, $hotelBlock){
        $h = $email->add()->hotel();

        $h->general()
            ->confirmation(str_replace(" ", '' ,$this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/preceding-sibling::table[normalize-space()][{$this->starts($this->t('Hotel Confirmation #'))}][1]", $hotelBlock, false, "/^{$this->opt($this->t('Hotel Confirmation #'))}[ ]*([A-z\d\/ ]+)$/u")));

        $h->hotel()
            ->name($this->http->FindSingleNode("./child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]", $hotelBlock))
            ->address($this->http->FindSingleNode("./child::tr[normalize-space()][2]/descendant::text()[normalize-space()][1]", $hotelBlock));

        $phone = $this->http->FindSingleNode("./child::tr[normalize-space()][2]/descendant::text()[normalize-space()][2]", $hotelBlock, false, "/^([0-9\+\(\-\) ]+)$/u");

        if ($phone !== null) {
            $h->hotel()
                ->phone($phone);
        }

        $dates = $this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Dates:'))} and {$this->contains($this->t('Reservation:'))}][1]/descendant::text()[{$this->eq($this->t('Dates:'))}][1]/following::text()[normalize-space()][1]", $hotelBlock);

        if (preg_match("/^(?<checkIn>\w+[ ]*\d{1,2}\,[ ]*\d{4})[ ]*\-[ ]*(?<checkOut>\w+[ ]*\d{1,2}\,[ ]*\d{4})$/u", $dates, $m)){
            $h->booked()
                ->checkIn($this->normalizeDate($m['checkIn']))
                ->checkOut($this->normalizeDate($m['checkOut']));
        }

        $rooms = $this->http->FindNodes("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Dates:'))} and {$this->contains($this->t('Reservation:'))}]/descendant::text()[{$this->eq($this->t('Reservation:'))}][1]/following::text()[normalize-space()][1]", $hotelBlock);

        foreach ($rooms as $room){
            $r = $h->addRoom();

            $r->setType($room);
        }

        $guests = $this->http->FindNodes("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[position() <= 3][{$this->contains($this->t('Dates:'))} and {$this->contains($this->t('Reservation:'))}]/descendant::text()[{$this->eq($this->t('Guest(s):'))}][1]/following::text()[normalize-space()][1]", $hotelBlock);

        foreach (array_unique($guests) as $guest){

            if (preg_match("/^[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]][ ]*\&[ ]*[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]/u", $guest)){
                $travellers = explode("&", $guest);

                $h->general()->travellers($travellers);
            } else {
                $h->general()
                    ->traveller($guest);
            }
        }

        $h->booked()
            ->guests(count($guests));
    }

    public function ParseFlight(Email $email, $flightBlock){
        $f = $email->add()->flight();

        $f->general()
            ->confirmation($this->http->FindSingleNode("./ancestor::table[@role = 'presentation'][position() <= 2]/preceding-sibling::table[normalize-space()][{$this->starts($this->t('Flight Confirmation #'))}][1]", $flightBlock, false, "/^{$this->opt($this->t('Flight Confirmation #'))}[ ]*([A-Z\d]{5,8})$/u"));

        $s = $f->addSegment();

        $flightCode = $this->http->FindSingleNode("./child::tr[normalize-space()][1]/descendant::tr[normalize-space()][3]", $flightBlock);

        if (preg_match("/^(?<name>(?:[A-Z\d][A-Z]|[A-Z][A-Z\d]))[ ]*(?<number>[0-9]{1,5})$/u", $flightCode, $m)){
            $s->airline()
                ->name($m['name'])
                ->number($m['number']);
        }

        $codes = $this->http->FindSingleNode("./child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]/descendant::th[normalize-space()][1]", $flightBlock);

        if (preg_match("/^(?<depCode>[A-Z]{3})[ ]*\→[ ]*(?<arrCode>[A-Z]{3})$/u", $codes, $m)){
            $s->departure()
                ->code($depCode = $m['depCode']);

            $s->arrival()
                ->code($arrCode = $m['arrCode']);
        }

        $passBlocks = $this->http->XPath->query("./ancestor::table[@role = 'presentation'][position() <= 2]/following-sibling::table[{$this->contains($this->t('PASSENGERS'))}][1]/following-sibling::table[position() <= 5][not({$this->contains($this->t('Travel Time'))}) and contains(normalize-space(), '•')]", $flightBlock);

        foreach ($passBlocks as $passBlock){
            $travellerName = $this->http->FindSingleNode("./descendant::text()[normalize-space()][1]", $passBlock, false, "/^([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])[ ]*\•?$/u");

            $f->general()
                ->traveller($travellerName, true);

            $accountNumber = $this->http->FindSingleNode("./descendant::text()[normalize-space()][2]", $passBlock, false, "/^\#[ ]*([0-9\-]+)[ ]*\•/u");

            if ($accountNumber !== null){
                $f->ota()->account($accountNumber, false, $travellerName);
            }

            $seatsInfo = $this->http->FindNodes("./descendant::th[normalize-space()][2]/descendant::table[normalize-space()][2]/descendant::tr", $passBlock);

            if (!empty($seatsInfo)){
                foreach ($seatsInfo as $seatInfo){
                    if (preg_match("/^{$depCode}[ ]*\>[ ]*{$arrCode}[ ]*\:[ ]*(\d{1,2}[ ]*[A-Z]+)$/u", $seatInfo, $m)){
                        $s->extra()->seat($m[1], false, false, $travellerName);
                    }
                }
            }
        }

        $s->departure()
            ->name($this->http->FindSingleNode("./child::tr[normalize-space()][2]/descendant::text()[normalize-space()][2]", $flightBlock));

        $s->arrival()
            ->name($this->http->FindSingleNode("./child::tr[normalize-space()][4]/descendant::text()[normalize-space()][2]", $flightBlock));

        $flightDate = $this->http->FindSingleNode("./child::tr[normalize-space()][1]/descendant::tr[normalize-space()][2]", $flightBlock, false, "/^(\w+[ ]\d{1,2}\,[ ]*\d{4})$/u");

        if ($flightDate !== null){
            $depTime = $this->http->FindSingleNode("./child::tr[normalize-space()][2]/descendant::text()[normalize-space()][1]", $flightBlock, false, "/^(\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)\,[ ]*[A-Z]{3}$/u");

            if ($depTime !== null){
                $s->departure()->date($this->normalizeDate($flightDate . ", " . $depTime));
            }

            $arrTime = $this->http->FindSingleNode("./child::tr[normalize-space()][4]/descendant::text()[normalize-space()][1]", $flightBlock, false, "/^(\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)\,[ ]*[A-Z]{3}$/u");

            if ($arrTime !== null){
                $s->arrival()->date($this->normalizeDate($flightDate . ", " . $arrTime));
            }
        }

        $duration = $this->http->FindSingleNode("./child::tr[normalize-space()][3]/descendant::text()[normalize-space()][2]", $flightBlock, false, "/^{$this->opt($this->t('Travel Time'))}[ ]*(\d+.+)(?:[ ]*\•|[ ]*\(\⚠|$)/u");

        if ($duration !== null){
            $s->extra()
                ->duration($duration);
        }

        $operatedBy = $this->http->FindSingleNode("./child::tr[normalize-space()][3]/descendant::text()[normalize-space()][2]", $flightBlock, false, "/\•[ ]*{$this->opt($this->t('Operated by'))}[ ]*(.+)$/u");

        if ($operatedBy !== null){
            $s->airline()->operator($operatedBy);
        }

        $overnight = $this->http->FindSingleNode("./child::tr[normalize-space()][3]/descendant::text()[normalize-space()][3]", $flightBlock, false, "/\([ ]*\⚠[ ]*({$this->opt($this->t('Overnight'))})[ ]*\)/u");

        if ($overnight !== null){
            $s->setArrDate($s->getArrDate() + 86400);
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
                return str_replace(' ', '\s+', preg_quote($s));
            }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^(\d+)(\D+)(\d+)\s*\,?\s*([\d\:]+\s*A?P?M?)[+]*\d?\s*$#su", //29Mar23, 12:20 AM
        ];
        $out = [
            "$1 $2 20$3, $4",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
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
