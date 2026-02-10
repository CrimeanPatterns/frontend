<?php

namespace AwardWallet\Engine\milleniumnclc\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ReservationConfirmationHtml extends \TAccountChecker
{
	public $mailFiles = "milleniumnclc/it-914133630.eml, milleniumnclc/it-914323266.eml";
    public $subjects = [
        'Reservation Confirmation',
        'Reservation Cancellation',
        'Reservation Hold',
    ];

    public $lang = 'en';

    public static $dictionary = [
        'en' => [

        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'millenniumpark2010@gmail.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Reservation ID'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Room Rent'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Check in'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/^millenniumpark2010\@gmail\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email): Email
    {
        $this->HotelReservation($email, $parser->getSubject());

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function HotelReservation(Email $email, $subject)
    {
        $h = $email->add()->hotel();

        $otaName = $this->http->FindSingleNode("//td[{$this->eq($this->t('Company Name'))}]/following-sibling::td[normalize-space()][1]");
        $otaConf = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Reference ID'))}]/following::text()[normalize-space()][1]", null, false, "/^([\dA-Z\-]+)$/u");

        if ($otaName !== null){
            $h->ota()
                ->keyword($otaName);

            if ($otaConf !== null) {
                $h->ota()
                    ->confirmation($otaConf, "Reference ID");
            }
        }

        $status = $this->http->FindSingleNode("//td[{$this->starts($this->t('Check in'))} and {$this->contains($this->t('Check out'))}]/following-sibling::td[1]/child::img[1]/@src");

        if (preg_match("/cfm\.png$/u", $status)){
            $h->general()->status('Confirmed');
        } else if (preg_match("/hld\.png$/u", $status)){
            $h->general()->status('On hold');
        } else if (preg_match("/cel\.png$/u", $status)){
            $h->general()
                ->status('Cancelled')
                ->cancelled();
        }

        $h->general()
            ->confirmation($confNum = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Reservation ID'))}][1]/following::text()[normalize-space()][1]", null, false, "/^([\dA-Z\-]+)$/"), 'Reservation ID');

        if (preg_match("/^.*\-[ ]*(.+)[ ]*\-[ ]*{$confNum}$/u", $subject, $hName))
        {
            $h->hotel()->name($hName[1]);
        }

        $traveller = $this->http->FindSingleNode("//td[{$this->eq($this->t('Guest Name'))}]/following-sibling::td[normalize-space()][1]", null, false, "/^\:?[ ]*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/u");

        $h->addTraveller(preg_replace("/^((?:Ms|Mr|Mrs|Mstr)\.)/u", "", $traveller));

        $h->hotel()
            ->noAddress();

        $datesText = $this->http->FindSingleNode("//td[{$this->starts($this->t('Check in'))} and {$this->contains($this->t('Check out'))}]");

        if (preg_match("/^{$this->opt($this->t('Check in'))}[ ]*(?<checkIn>\d{1,2}[ ]*\w+\,[ ]*\d{4}[ ]*\d{1,2}\:\d{1,2}[ ]*[Aa]?[Pp]?[Mm]?)(?:\:[0-9]{1,2})?[ ]*{$this->opt($this->t('Check out'))}[ ]*(?<checkOut>\d{1,2}[ ]*\w+\,[ ]*\d{4}[ ]*\d{1,2}\:\d{1,2}[ ]*[Aa]?[Pp]?[Mm]?)(?:\:[0-9]{1,2})?$/u", $datesText, $m)){
            $h->booked()
                ->checkIn(strtotime(str_replace(",", '', $m['checkIn'])))
                ->checkOut(strtotime(str_replace(",", '', $m['checkOut'])));
        }

        $roomsNodes = $this->http->XPath->query("//tr[./descendant::th[{$this->eq($this->t('Room'))}] and ./descendant::th[{$this->eq($this->t('Nights'))}]]/following-sibling::tr[count(./descendant::td) = 10]");

        $guestsCount = 0;
        $kidsCount = 0;

        foreach ($roomsNodes as $roomsNode){
            $r = $h->addRoom();

            $r->setType($this->http->FindSingleNode("./descendant::td[normalize-space()][2]", $roomsNode));

            $guestsCount += $this->http->FindSingleNode("./descendant::td[normalize-space()][4]", $roomsNode, false, "/^([0-9]+)$/u");

            $kidsCount += $this->http->FindSingleNode("./descendant::td[normalize-space()][5]", $roomsNode, false, "/^([0-9]+)$/u");
            $kidsCount += $this->http->FindSingleNode("./descendant::td[normalize-space()][6]", $roomsNode, false, "/^([0-9]+)$/u");
        }

        $h->booked()
            ->rooms($roomsNodes->count())
            ->guests($guestsCount)
            ->kids($kidsCount);


        $priceInfo = $this->http->FindSingleNode("//tr[./td[1][{$this->eq($this->t('Total'))}]]/descendant::td[3]");

        if (preg_match("/^(?<price>\d[\d\.\,\']*)$/", $priceInfo, $m) && $h->getCancelled() !== true) {
            $h->price()
                ->currency($currency = $this->http->FindSingleNode("//tr[./td[1][{$this->eq($this->t('Total'))}]]/descendant::td[normalize-space()][2]", null, false, "/^(\D{1,3})$/u"))
                ->cost($cost = PriceHelper::parse($m['price'], $currency))
                ->tax($tax = PriceHelper::parse($this->http->FindSingleNode("//tr[./td[1][{$this->eq($this->t('Total Taxes'))}]]/descendant::td[normalize-space()][3]", null, false, "/^(\d[\d\.\,\']*)$/u"), $currency))
                ->discount($discount = PriceHelper::parse($this->http->FindSingleNode("//tr[./td[1][{$this->eq($this->t('Discount Amount'))}]]/descendant::td[normalize-space()][3]", null, false, "/^(\d[\d\.\,\']*)$/u"), $currency));

            if ($cost !== null && $tax !== null && $discount !== null){
                $totalPrice = $cost + $tax - $discount;

                $h->price()
                    ->total($totalPrice);
            }
        }
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
                return str_replace(' ', '\s+', preg_quote($s, '/'));
            }, $field)) . ')';
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
}
