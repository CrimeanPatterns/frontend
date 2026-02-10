<?php

namespace AwardWallet\Engine\tablethotels\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ReservationConfirmation2 extends \TAccountChecker
{
    public $mailFiles = "tablethotels/it-799245887.eml, tablethotels/it-926690502.eml, tablethotels/it-927269937-cancelled.eml";
    public $subjects = [
        'Reservation Confirmation for', 'Reservation Cancellation for',
    ];

    public $lang = 'en';

    private $hotelName = '';

    public static $dictionary = [
        "en" => [
            'feeNames' => ['Taxes & Fees', 'Resort Fees', 'City Taxes'],
            'cancelledPhrases' => [
                'At your request we have cancelled the reservation below.',
                'Your reservation was cancelled on',
            ],
        ],
    ];

    private $patterns = [
        'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm
        'phone' => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52  |  (+351) 21 342 09 07  |  713.680.2992
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@tablethotels.com') !== false) {
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
        if ($this->http->XPath->query("//text()[contains(normalize-space(),'Tablet Hotels')]")->length === 0) {
            return false;
        }

        return $this->http->XPath->query("//text()[{$this->contains($this->t('Confirmation number:'))}]")->length > 0
            && $this->http->XPath->query("//text()[contains(normalize-space(),'Adult') and contains(normalize-space(),'Children')]")->length > 0
            && $this->http->XPath->query("//text()[normalize-space()='Hotel Info']")->length > 0
            && $this->http->XPath->query("//text()[normalize-space()='Rooms']")->length > 0
            ||
            $this->isCancelled()
        ;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]tablethotels\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if (preg_match("/{$this->opt($this->subjects)}\s+(\S.{1,80}\S)\s*:/", $parser->getSubject(), $m)) {
            $this->hotelName = $m[1];
        }

        $this->ParseHotel($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseHotel(Email $email): void
    {
        $h = $email->add()->hotel();

        $h->general()
            ->confirmation($this->http->FindSingleNode("//text()[normalize-space()='Confirmation number:']/following::text()[normalize-space()][1]"))
            ->travellers($this->http->FindNodes("//text()[contains(normalize-space(),'Adult') and contains(normalize-space(),'Children')]/preceding::text()[normalize-space()][1]", null, "/^{$this->patterns['travellerName']}$/u"))
        ;

        $dateCheckIn = $dateCheckOut = null;
        $dateText = $this->http->FindSingleNode("//text()[contains(normalize-space(),'Adult') and contains(normalize-space(),'Children')]/preceding::text()[normalize-space()][2]");

        if (preg_match("/^(?<inDate>.+\d{4})\s+-\s+(?<outDate>.+\d{4})$/", $dateText, $m)) {
            $dateCheckIn = strtotime($m['inDate']);
            $dateCheckOut = strtotime($m['outDate']);
        }

        $guestText = $this->http->FindSingleNode("//text()[contains(normalize-space(),'Adult') and contains(normalize-space(),'Children')]");

        if (preg_match("/^(?<adults>\d{1,3})\s*Adults?\s*,\s*(?<kids>\d{1,3})\s*Children$/i", $guestText, $m)) {
            $h->booked()
                ->guests($m['adults'])
                ->kids($m['kids']);
        }

        $cancellationNumber = $this->http->FindSingleNode("//text()[starts-with(normalize-space(),'Cancellation #')]", null, true, "/^Cancellation #[:\s]*([-A-Z\d]{4,40})$/");
        $h->general()->cancellationNumber($cancellationNumber, false, true);

        $roomType = $this->http->FindSingleNode("//text()[normalize-space()='Rooms']/following::text()[normalize-space()][1]");

        if (!empty($roomType)) {
            $room = $h->addRoom();
            $room->setType($roomType);

            $rates = $this->http->FindNodes("//text()[normalize-space()='Taxes & Fees']/ancestor::tr[1]/preceding-sibling::tr");

            if ($rates > 0) {
                $room->setRates($rates);
            }
        }

        $hotelName = $address = $phone = null;

        if ($this->hotelName && $this->http->XPath->query("//text()[{$this->eq($this->hotelName)}]")->length > 0) {
            $hotelName = $this->hotelName;
        }

        $hotelInfo = implode("\n", $this->http->FindNodes("//text()[normalize-space()='Hotel Info']/ancestor::td[1]/descendant::text()[normalize-space()]"));

        $this->logger->info('HOTEL INFO:');
        $this->logger->debug($hotelInfo);

        if (preg_match("/Hotel Info\n(?<hotelName>.{2,})(?<address>(?:\n.+){1,4}?)\n(?<phone>{$this->patterns['phone']})(?:\n|$)/", $hotelInfo, $m)) {
            $hotelName = $m['hotelName'];
            $address = preg_replace('/\s+/', ' ', trim($m['address']));
            $phone = $m['phone'];
        }

        if ($dateCheckIn && preg_match("/^Check in\s*[:]+\s*({$this->patterns['time']})/im", $hotelInfo, $m)) {
            $dateCheckIn = strtotime($m[1], $dateCheckIn);
        }

        if ($dateCheckOut && preg_match("/^Check out\s*[:]+\s*({$this->patterns['time']})/im", $hotelInfo, $m)) {
            $dateCheckOut = strtotime($m[1], $dateCheckOut);
        }

        $h->booked()->checkIn($dateCheckIn)->checkOut($dateCheckOut);

        if ($this->isCancelled()) {
            $h->general()->cancelled();
            $h->hotel()->name($hotelName)->noAddress();
        } else {
            $h->hotel()->name($hotelName)->address($address)->phone($phone);
        }

        $cancellation = $this->http->FindSingleNode("//text()[normalize-space()='Cancellation']/following::text()[normalize-space()][1]");
        $h->general()->cancellation($cancellation, false, true);

        $this->detectDeadLine($h);

        $totalPrice = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Total'))}] ]/*[normalize-space()][2]", null, true, '/^.*\d.*$/');

        if (preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $totalPrice, $matches)) {
            // $5,621.34
            $currency = $this->normalizeCurrency($matches['currency']);
            $currencyCode = preg_match('/^[A-Z]{3}$/', $currency) ? $currency : null;
            $h->price()->currency($matches['currency'])->total(PriceHelper::parse($matches['amount'], $currencyCode));

            $feeRows = $this->http->XPath->query("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('feeNames'))}] ]");

            foreach ($feeRows as $feeRow) {
                $feeCharge = $this->http->FindSingleNode('*[normalize-space()][2]', $feeRow, true, '/^(.*?\d.*?)\s*(?:\(|$)/');
    
                if ( preg_match('/^(?:' . preg_quote($matches['currency'], '/') . ')?[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $feeCharge, $m) ) {
                    $feeName = $this->http->FindSingleNode('*[normalize-space()][1]', $feeRow, true, '/^(.+?)[\s:：]*$/u');
                    $h->price()->fee($feeName, PriceHelper::parse($m['amount'], $currencyCode));
                }
            }
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

    public function detectDeadLine(Hotel $h)
    {
        if (empty($h->getCancellation())) {
            return;
        }

        if (preg_match('/Non-Refundable/', $h->getCancellation(), $m)) {
            $h->booked()->nonRefundable();
        }

        if (preg_match('/Free Cancellation by\s*(\w+\s*\d+\,\s*\d{4})/', $h->getCancellation(), $m)) {
            $h->booked()->deadline(strtotime($m[1]));
        }

        if (preg_match('/You may cancel free of charge until (\d+ days?) before arrival/', $h->getCancellation(), $m)) {
            $h->booked()->deadlineRelative($m[1]);
        }
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function isCancelled(): bool
    {
        if ( !isset(self::$dictionary) ) {
            return false;
        }
        foreach (self::$dictionary as $phrases) {
            if ( empty($phrases['cancelledPhrases']) ) {
                continue;
            }
            if ($this->http->XPath->query("//*[{$this->contains($phrases['cancelledPhrases'])}]")->length > 0) {
                return true;
            }
        }
        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return $phrase;
        }
        if ($lang === '') {
            $lang = $this->lang;
        }
        if ( empty(self::$dictionary[$lang][$phrase]) ) {
            return $phrase;
        }
        return self::$dictionary[$lang][$phrase];
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function normalizeCurrency(string $string): string
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
            'EUR' => ['€'],
            'USD' => ['US$'],
            'SGD' => ['SG$'],
            'ZAR' => ['R'],
        ];

        foreach ($currencies as $currencyCode => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency) {
                    return $currencyCode;
                }
            }
        }

        return $string;
    }
}
