<?php

namespace AwardWallet\Engine\carlson\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ReservationSummary extends \TAccountChecker
{
	public $mailFiles = "carlson/it-923031044.eml, carlson/it-927163612.eml";
    public $reFrom = "@email.radissonhotels.com";
    public $reBody = [
        'en' => ['RESERVATION SUMMARY HOTEL GUARANTEE & RESERVATION POLICIES'],
    ];
    public $reSubject = [
        'Your reservation number is',
    ];

    public $lang = '';
    public $hotelName = '';
    public static $dict = [
        'en' => [
        ],
    ];

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

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//img[contains(@src,'radissonhotels.')]")->length > 0) {
            return $this->AssignLang();
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->AssignLang();

        $this->parseEmail($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    private function parseEmail(Email $email)
    {
        $h = $email->add()->hotel();

        $h->general()
            ->confirmation($this->http->FindSingleNode("//td[{$this->eq($this->t('Reservation Number:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^([A-Z\d]{5,})$/u"));

        $travellerNode = $this->http->FindSingleNode("//td[{$this->eq($this->t('Guest Name:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^([[:alpha:]][-.\'’,[:alpha:] ]*[[:alpha:]])$/u");

        $h->general()
            ->traveller($travellerNode);

        $hotelInfo = $this->http->FindSingleNode("//text()[{$this->starts($this->t('You’ve registered with us as:'))}][1]/preceding::text()[normalize-space()][1]/ancestor::td[normalize-space()][1]");

        if (preg_match("/^(?<name>.*?\,.*?)\,[ ]+(?<address>.+)[ ]*\,[ ]+(?<phone>\+?[\d\(\-\) ]+)$/u", $hotelInfo, $m)){
            $h->hotel()
                ->name($m['name'])
                ->address($m['address'])
                ->phone($m['phone']);
        }

        $checkInDate = $this->http->FindSingleNode("//td[{$this->eq($this->t('Arrival Time:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^(\d{1,2}\.\d{1,2}\.\d{2,4})$/u");
        $checkInTime = $this->http->FindSingleNode("//td[{$this->eq($this->t('Check-In Time:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^(\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)$/u");

        if ($checkInDate !== null && $checkInTime !== null) {
            $h->booked()
                ->checkIn($this->normalizeDate($checkInDate . ", " . $checkInTime));
        }

        $checkOutDate = $this->http->FindSingleNode("//td[{$this->eq($this->t('Departure Time:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^(\d{1,2}\.\d{1,2}\.\d{2,4})$/u");
        $checkOutTime = $this->http->FindSingleNode("//td[{$this->eq($this->t('Check Out Time:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^(\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)$/u");

        if ($checkOutDate !== null && $checkOutTime !== null) {
            $h->booked()->checkOut($this->normalizeDate($checkOutDate . ", " . $checkOutTime));
        }

        $r = $h->addRoom();

        $r->setType($this->http->FindSingleNode("//td[{$this->eq($this->t('Room Type:'))}][1]/following-sibling::td[normalize-space()][1]"));

        $h->booked()
            ->guests($this->http->FindSingleNode("//td[{$this->eq($this->t('Adults:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^([0-9]+)$/u"));

        $children = $this->http->FindSingleNode("//td[{$this->eq($this->t('Children*:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^([0-9]+)$/u");
        $infants = $this->http->FindSingleNode("//td[{$this->eq($this->t('Infants:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^([0-9]+)$/u");

        if ($children !== null && $infants !== null){
            $h->booked()
                ->kids($infants + $children);
        }

        $totalPrice = $this->http->FindSingleNode("//td[{$this->eq($this->t('Total price:'))}][1]/following-sibling::td[normalize-space()][1]");

        if (preg_match("/^(?<amount>\d[,.\'\d ]*)[ ]*(?<currency>\D{1,3})(?:[ ].+|$)/u", $totalPrice, $m)){
            $currency = $this->normalizeCurrency($m['currency']);

            $h->price()
                ->currency($currency)
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->cost(PriceHelper::parse($this->http->FindSingleNode("//td[{$this->eq($this->t('Sub Total:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^(\d[,.\'\d ]*)[ ]*\D{1,3}$/u"), $currency))
                ->tax(PriceHelper::parse($this->http->FindSingleNode("//td[{$this->eq($this->t('Estimated Taxes:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^(\d[,.\'\d ]*)[ ]*\D{1,3}$/u"), $currency))
                ->fee("Estimated Additional Fees",PriceHelper::parse($this->http->FindSingleNode("//td[{$this->eq($this->t('Estimated Additional Fees:'))}][1]/following-sibling::td[normalize-space()][1]", null, false, "/^(\d[,.\'\d ]*)[ ]*\D{1,3}$/u"), $currency));
        }

        $cancellation = $this->http->FindSingleNode("//td[{$this->eq($this->t('Cancellation Policy:'))}][1]/following-sibling::td[normalize-space()][1]");

        if ($cancellation !== null) {
            $h->general()
                ->cancellation($cancellation);

            $this->detectDeadLine($h);
        }
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dict);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dict);
    }

    private function normalizeCurrency($string)
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
            'AUD' => ['A$'],
            'EUR' => ['€'],
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

        $str = preg_replace($in, $out, $date);

        return strtotime($str);
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
            }, $field)) . ')';
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

    private function detectDeadLine(Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        if (preg_match("/Cancel by[ ]*(?<time>\d{1,2}\:\d{2})\:\d{2}[ ]*hotel time on[ ]*(?<date>\d{1,2}[ ]*\w+[ ]*\d{4})\./", $cancellationText, $m)) {
            $h->booked()
                ->deadline($this->normalizeDate($m['date'] . ', ' . $m['time']));
        }
        if (preg_match("/Reservation is non\-refundable\./", $cancellationText, $m)) {
            $h->booked()
                ->nonRefundable();
        }
    }
}
