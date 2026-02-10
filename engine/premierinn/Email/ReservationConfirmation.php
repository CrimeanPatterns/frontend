<?php

namespace AwardWallet\Engine\premierinn\Email;

use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Common\Parser\Util\PriceHelper;

class ReservationConfirmation extends \TAccountChecker
{
    public $mailFiles = "premierinn/it-28531620.eml, premierinn/it-890675470.eml, premierinn/it-898198634.eml";
    private $subjects = [
        'en' => ['Reservation Confirmation'],
    ];
    private $langDetectors = [
        'en' => ['Number of rooms:'],
    ];
    private $lang = '';
    private static $dict = [
        'en' => [
            'Your reservation number is' => ['Your reservation number is', 'your reservation number is'],
            'Check in time is from' => ['Check in time is from', 'Check-in from'],
            'Check out time is by' => ['Check out time is by', 'Check-out by'],
        ],
    ];
    private $namePrefixes = '(?:MASTER|MSTR|MISS|MRS|MR|MS|DR)';

    // Standard Methods

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]premierinn\.com/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ($phrases as $phrase) {
                if (is_string($phrase) && array_key_exists('subject', $headers) && stripos($headers['subject'], $phrase) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query('//a[contains(@href,"//global.premierinn.com")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"your reservation at Premier Inn") or contains(.,"@mena.premierinn.com")]')->length === 0
        ) {
            return false;
        }

        return $this->assignLang();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();
        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
            return $email;
        }
        $email->setType('ReservationConfirmation' . ucfirst($this->lang));

        $this->parseHotel($email);
        return $email;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dict);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dict);
    }

    private function parseHotel(Email $email): void
    {
        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm
            'phone' => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52  |  (+351) 21 342 09 07  |  713.680.2992
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $h = $email->add()->hotel();

        // travellers
        // hotelName
        // address
        // phone
        $travellers = [];
        $hotelName = $address = $phone = null;
        
        $guestName = $this->normalizeTraveller($this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Guest Name'), "translate(.,':','')")}] ]/*[normalize-space()][2]", null, true, "/^{$patterns['travellerName']}$/u"));

        if ($guestName) {
            $travellers[] = $guestName;
        }

        $additionalName = $this->normalizeTraveller($this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Additional Guest'), "translate(.,':','')")}] ]/*[normalize-space()][2]", null, true, "/^{$patterns['travellerName']}$/u"));

        if ($additionalName) {
            $travellers[] = $additionalName;
        }

        // it-890675470.eml
        $hotelDetails = $this->htmlToText( $this->http->FindHTMLByXpath("//text()[{$this->eq($this->t('Hotel details'), "translate(.,':','')")}]/ancestor::*[ ../self::tr and descendant::text()[normalize-space()][2] ][1]") );
        $this->logger->info('HOTEL DETAILS:');
        $this->logger->debug($hotelDetails);

        if (preg_match("/{$this->opt($this->t('Hotel details'))}[: ]*\n+[ ]*(?<name>\S.*?\S)[, ]*\n+[ ]*(?<address>[\s\S]{3,}?)[ ]*\n(?:[ ]*{$this->opt($this->t('Hotel Phone Number'))}|[ ]*{$this->opt($this->t('Hotel Email Address'))}|\n)/", $hotelDetails, $m)) {
            $hotelName = $m['name'];
            $address = preg_replace('/\s+/', ' ', $m['address']);
        }

        if (preg_match("/^[ ]*{$this->opt($this->t('Hotel Phone Number'))}[:\s]+({$patterns['phone']})[,.!; ]*$/m", $hotelDetails, $m)) {
            $phone = $m[1];
        }

        // it-898198634.eml
        $hotelFooter = $this->htmlToText( $this->http->FindHTMLByXpath("//text()[{$this->contains(['@mena.premierinn.com'])} and contains(.,'|')]/ancestor::*[ ../self::tr and descendant::text()[normalize-space()][2] ][1]") );
        $this->logger->info('HOTEL FOOTER:');
        $this->logger->debug($hotelFooter);

        if (preg_match("/^[ ]*(?<name>\S.*?\S)[, ]*\n+[ ]*(?<address>[^|]{3,}?)[ ]*\|[ ]*{$patterns['phone']}/", $hotelFooter, $m)) {
            $hotelName = $m['name'];
            $address = preg_replace(['/(?:[ ]*\n[ ]*)+/', '/(?:\s*,\s*)+/'], ', ', $m['address']);
        }

        if (!$phone && preg_match("/(?:^|\|)[ ]*({$patterns['phone']})[ ]*(?:\||$)/m", $hotelFooter, $m)) {
            $phone = $m[1];
        }

        $preRowText = $this->http->FindSingleNode("//tr[{$this->eq($this->t('Reservation details'), "translate(.,':','')")}]/preceding-sibling::tr[normalize-space()][1]");

        if (count($travellers) === 0 && preg_match("/^{$this->namePrefixes}\.\s*({$patterns['travellerName']})$/iu", $preRowText, $m)) {
            $travellers[] = $m[1];
        }

        $intro = $this->http->FindSingleNode("//text()[{$this->contains($this->t('We are pleased to confirm your reservation at'))}]/ancestor::*[1]");

        if (preg_match("/{$this->opt($this->t('We are pleased to confirm your reservation at'))}\s*(.+?)\s*{$this->opt($this->t('for'))}\s*({$patterns['travellerName']})\./u", $intro, $m)) {
            $hotelName = $m[1];

            if (!$address) {
                $h->hotel()->noAddress();
            }

            if (count($travellers) === 0) {
                $travellers[] = $this->normalizeTraveller($m[2]);
            }
        }

        if (!$hotelName) {
            $name = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Thank you for choosing to stay with us at'))}]/following::text()[normalize-space()][1][following::text()[normalize-space()][1][{$this->starts('.')}]]");

            if (strlen($name) < 50 && stripos($name, 'Premier Inn') !== false) {
                $hotelName = $name;
                
                if (!$address) {
                    $h->hotel()->noAddress();
                }

                $guestName = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Dear'))}]/following::text()[normalize-space()][1][following::text()[normalize-space()][1][{$this->eq(',')}]]", null, true, "/^\s*({$patterns['travellerName']})\s*$/u");

                if ($guestName && count($travellers) === 0) {
                    $travellers[] = $this->normalizeTraveller($guestName);
                }
            }
        }

        $contacts = $this->http->FindSingleNode("//text()[{$this->contains($this->t('you can contact us by phone on'))}]/ancestor::*[1]");

        if (!$phone && preg_match("/{$this->opt($this->t('you can contact us by phone on'))}\s*({$patterns['phone']})\s*{$this->opt($this->t('or by email at'))}/i", $contacts, $m)) {
            $phone = $m[1];
        }

        $h->hotel()->name($hotelName)->phone($phone, false, true);

        if ($address) {
            $h->hotel()->address($address);
        }

        if (count($travellers) > 0) {
            $h->general()->travellers($travellers);
        }

        // confirmation number
        $confirmation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking Reference'), "translate(.,':','')")}]/following::text()[normalize-space()][1]", null, true, '/^([A-Z\d]{5,})[,.!\s]*$/');
        $confirmationTitle = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking Reference'), "translate(.,':','')")}]", null, true, '/^(.+?)[\s:：]*$/u');

        if (!$confirmation && preg_match("/{$this->opt($this->t('Your reservation number is'))}\s*([A-Z\d]{5,})[,.!\s]*$/", $this->http->FindSingleNode("//text()[{$this->contains($this->t('Your reservation number is'))}]/ancestor::*[1]"), $m)) {
            $confirmation = $m[1];
        }

        if ($confirmation) {
            $h->general()->confirmation($confirmation, $confirmationTitle);
        }

        // checkInDate
        $dateCheckIn = $timeCheckIn = null;
        $dateCheckInVal = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Arrival date:'))}]/ancestor::td[1]/following-sibling::*[normalize-space()][last()]");

        if (preg_match("/^(?<date>.{4,}?\b\d{4})\s+[-]+\s+{$this->opt($this->t('from'))}\s+(?<time>{$patterns['time']})(?:\s*\(|$)/i", $dateCheckInVal, $m)) {
            $dateCheckIn = strtotime($m['date']);
            $timeCheckIn = $m['time'];
        } else {
            $dateCheckIn = strtotime($dateCheckInVal);
        }

        if (!$timeCheckIn) {
            $timeCheckIn = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Check in time is from'))}]", null, true, "/{$this->opt($this->t('Check in time is from'))}\s*({$patterns['time']})/");
        }

        if ($timeCheckIn) {
            $h->booked()->checkIn(strtotime($timeCheckIn, $dateCheckIn));
        } else {
            $h->booked()->checkIn($dateCheckIn);
        }

        // checkOutDate
        $dateCheckOut = $timeCheckOut = null;
        $dateCheckOutVal = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Departure date:'))}]/ancestor::td[1]/following-sibling::*[normalize-space()][last()]");

        if (preg_match("/^(?<date>.{4,}?\b\d{4})\s+[-]+\s+{$this->opt($this->t('before'))}\s+(?<time>{$patterns['time']})(?:\s*\(|$)/i", $dateCheckOutVal, $m)) {
            $dateCheckOut = strtotime($m['date']);
            $timeCheckOut = $m['time'];
        } else {
            $dateCheckOut = strtotime($dateCheckOutVal);
        }

        if (!$timeCheckOut) {
            $timeCheckOut = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Check out time is by'))}]", null, true, "/{$this->opt($this->t('Check out time is by'))}\s*({$patterns['time']})/");
        }

        if ($timeCheckOut) {
            $h->booked()->checkOut(strtotime($timeCheckOut, $dateCheckOut));
        } else {
            $h->booked()->checkOut($dateCheckOut);
        }

        // roomsCount
        $roomsCount = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Number of rooms:'))}]/ancestor::td[1]/following-sibling::*[normalize-space(.)][last()]", null, true, '/^(\d{1,3})$/');
        $h->booked()->rooms($roomsCount);

        // guestCount
        $guests = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Number of persons:'))}]/ancestor::td[1]/following-sibling::*[normalize-space(.)][last()]", null, true, '/^(\d{1,3})$/');
        $h->booked()->guests($guests);

        // kidsCount
        $kids = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Number of children:'))}]/ancestor::td[1]/following-sibling::*[normalize-space(.)][last()]", null, true, '/^(\d{1,3})$/');
        $h->booked()->kids($kids);

        $r = $h->addRoom();

        // r.rateType
        $rateType = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Rate:'))}]/ancestor::td[1]/following-sibling::*[normalize-space(.)][last()]");
        $r->setRateType($rateType);

        // r.type
        $roomType = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Room type:'))}]/ancestor::td[1]/following-sibling::*[normalize-space(.)][last()]");
        $r->setType($roomType);

        // r.description
//        $roomDesc = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Room requests:'))}]/ancestor::td[1]/following-sibling::*[normalize-space(.)][last()]");
//        $r->setDescription($roomDesc, true);

        // r.rate
        $rates = [];
        $rateText = $this->htmlToText( $this->http->FindHTMLByXpath("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Daily rate'), "translate(.,':','')")}] ]/*[normalize-space()][2]") );
        $rateRows = preg_split("/(?:[ ]*\n[ ]*)+/", $rateText);

        foreach ($rateRows as $rateRow) {
            if (preg_match("/^.{6,}?\s*([A-Z]{3}\s*\d[,.\'\d ]*)$/", $rateRow, $m)) {
                // Thursday, January 10, 2019 AED 261.75
                $rates[] = $m[1];
            } else {
                $rates = [];
                $this->logger->debug('Rates is wrong!');

                break;
            }
        }

        if (count($rates) > 0) {
            $r->setRates($rates);
        }

        // p.currencyCode
        // p.total
        // p.tax
        $payment = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Total Stay:'))}]/ancestor::td[1]/following-sibling::*[normalize-space(.)][last()]");

        if (preg_match('/^(?<currency>[A-Z]{3})\s*(?<amount>\d[,.\'\d ]*)/', $payment, $matches)) {
            // AED 598.12
            $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
            $h->price()
                ->currency($matches['currency'])
                ->total(PriceHelper::parse($matches['amount'], $currencyCode));

            $taxes = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Taxes / Service:'))}]/ancestor::td[1]/following-sibling::*[normalize-space(.)][last()]");

            if (preg_match('/^' . preg_quote($matches['currency'], '/') . '\s*(?<amount>\d[,.\'\d ]*)/', $taxes, $m)) {
                $h->price()->tax(PriceHelper::parse($m['amount'], $currencyCode));
            }
        }

        // cancellation
        $cancellationText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Guarantee and cancellation policies:'))}]/following::text()[normalize-space(.)][1][not(./ancestor::*[self::b or self::strong])]");
        $h->general()->cancellation($cancellationText);

        // deadline
        if (preg_match("/Reservations (?i)must be cancell?ed by\s+(?<hour>{$patterns['time']})\s+on the day of arrival or the first night rate will be charged/", $cancellationText, $m)) {
            $h->booked()->deadlineRelative('00:00', $m['hour']);
        }

        // nonRefundable
        $h->booked()
            ->parseNonRefundable('Non-refundable booking.')
            ->parseNonRefundable('If cancelled, full stay will be charged')
            ->parseNonRefundable('Non-Refundable Rate.')
            ->parseNonRefundable('Rate is non-refundable.')
        ;
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

    private function starts($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) { return 'starts-with(normalize-space(' . $node . "),'" . $s . "')"; }, $field)) . ')';
    }

    private function contains($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) { return 'contains(normalize-space(' . $node . "),'" . $s . "')"; }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) { return preg_quote($s, '/'); }, $field)) . ')';
    }

    private function t($phrase)
    {
        if (!isset(self::$dict[$this->lang]) || !isset(self::$dict[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dict[$this->lang][$phrase];
    }

    private function assignLang(): bool
    {
        foreach ($this->langDetectors as $lang => $phrases) {
            foreach ($phrases as $phrase) {
                if ($this->http->XPath->query("//*[{$this->contains($phrase)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function htmlToText(?string $s, bool $brConvert = true): string
    {
        if (!is_string($s) || $s === '') {
            return '';
        }
        $s = str_replace("\r", '', $s);
        $s = preg_replace('/<!--.*?-->/s', '', $s); // comments

        if ($brConvert) {
            $s = preg_replace('/\s+/', ' ', $s);
            $s = preg_replace('/<[Bb][Rr]\b.*?\/?>/', "\n", $s); // only <br> tags
        }
        $s = preg_replace('/<[A-z][A-z\d:]*\b.*?\/?>/', '', $s); // opening tags
        $s = preg_replace('/<\/[A-z][A-z\d:]*\b[ ]*>/', '', $s); // closing tags
        $s = html_entity_decode($s);
        $s = str_replace(chr(194) . chr(160), ' ', $s); // NBSP to SPACE

        return trim($s);
    }

    private function normalizeTraveller(?string $s): ?string
    {
        if (empty($s)) {
            return null;
        }

        return preg_replace([
            "/^(?:{$this->namePrefixes}[.\s]+)+(.{2,})$/is",
        ], [
            '$1',
        ], $s);
    }
}
