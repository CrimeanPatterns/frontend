<?php

namespace AwardWallet\Engine\amextravel\Email;

use AwardWallet\Schema\Parser\Email\Email;

// TODO: fix parsing reservations from it-2143442.eml

class DiningReservations2 extends \TAccountChecker
{
    public $mailFiles = "amextravel/it-2143439.eml, amextravel/it-2143442.eml, amextravel/it-2143464.eml, amextravel/it-2143465.eml, amextravel/it-2143467.eml, amextravel/it-2143472.eml, amextravel/it-2143476.eml, amextravel/it-920947312.eml";

    private $subjects = [
        'en' => ['Dining Reservations']
    ];

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'phoneNumber' => ['Phone Number', 'Phone number'],
            'reservationDate' => ['Reservation date', 'Reservation Date', 'Day/Date', 'Date:', 'Day:'],
            'reservationTime' => ['Reservation time', 'Reservation Time', 'Time:'],
        ]
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@concierge.americanexpress.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array)$phrases as $phrase) {
                if (is_string($phrase) && array_key_exists('subject', $headers) && stripos($headers['subject'], $phrase) !== false)
                    return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//node()[{$this->contains(['Thank you for your American Express', 'American Express Concierge', 'American Express Platinum Concierge'])}]")->length === 0
        ) {
            return false;
        }
        return $this->assignLang();
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
        }
        $email->setType('DiningReservations2' . ucfirst($this->lang));

        if ($this->http->XPath->query('//img[contains(@src,"Platinum-card-concierge.")]')->length > 0) {
            $this->logger->debug('Format is wrong!');

            return $email;
        }

        $patterns = [
            'date' => '(?:[-[:alpha:]]+[,\s]+)?[[:alpha:]]+\s+\d{1,2}[,\s]+\d{4}\b', // Thursday, May 1, 2008  |  Jun 26, 2025
            'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
            'phone' => '[+(\d][-+. \d\/)(]{5,15}[\d)]', // +377 (93) 15 48 52  |  (+351) 21 342 09 07  |  713.680.2992  |  514/289-9921
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $ev = $email->add()->event();
        $ev->type()->restaurant();

        $mainText = $parser->getPlainBody();

        if (empty($mainText)
            || preg_match("/<[A-z][A-z\d:]*\b.*?\/?>\s*{$this->opt($this->t('reservationTime'))}/", $mainText)
        ) {
            $mainTextParts = $this->http->FindNodes("//text()[{$this->contains($this->t('reservationTime'))}]/ancestor-or-self::node()[ descendant-or-self::text()[{$this->contains($this->t('reservationDate'))}] ][1]/descendant-or-self::text()[normalize-space()]");
            $mainText = implode("\n", $mainTextParts);
        }

        $mainText = preg_replace('/^[> ]+/m', '', $mainText);

        $this->logger->info('MAIN TEXT:');
        $this->logger->debug($mainText);

        $toRemove = $this->re("/Dear\s+.*,\s+(?:(?s).*?)\n\s*\n((?s)Date:.*?)\n\s*\n/i", $mainText)
            ?? $this->re("/Dear\s+.*?,\s*\n\s*\n\s*.*\s*\n\s*\n\s*(.*)\s*\n\s*\n/i", $mainText);

        $fixedText = $toRemove ? str_replace($toRemove, '', $mainText) : $mainText;

        $this->logger->info('FIXED TEXT:');
        $this->logger->debug($fixedText);

        $eventName = $address = $phone = null;

        if (preg_match("/(?:Dear\s+.*?,\s+(?:(?s).*?)|(?:(?s)Thank\s+you\s+for\s+calling.*?))\n\s*\n\s*(?<name>.*?)\s*\n\s*(?<address>(?s).*?)(?:{$this->opt($this->t('phoneNumber'))})?[:\s]+(?<phone>{$patterns['phone']}(?:[ ]*\w{2})?)\s*\n/i", $fixedText, $m)) {
            $eventName = $m['name'];
            $address = nice(preg_replace('/https?:\/\/.*/i', '', $m['address']), ',');
            $phone = nice($m['phone']);
        }

        if (!$eventName && preg_match("/^[ ]*Restaurant name\s*[:]+\s*([^:\s].+?(?:\n.{2,}?)?)[ ]*\n+[ ]*Address[ ]*:/im", $mainText, $m)) {;
            $eventName = preg_replace('/\s+/', ' ', $m[1]);
        }

        if (!$address && preg_match("/^[ ]*Address\s*[:]+\s*([^:\s].{2,}?(?:\n.{2,}?){0,3}?)[ ]*\n+[ ]*(?:Phone number[ ]*:|{$this->opt($this->t('reservationDate'))})/im", $mainText, $m)) {
            $address = preg_replace('/\s+/', ' ', $m[1]);
        }

        if (!$phone) {
            $phone = $this->re("/^[ ]*Phone number\s*[:]+\s*({$patterns['phone']})[ ]*\n/im", $mainText);
        }

        $ev->place()->name($eventName)->address($address)->phone($phone);

        $dateStart = $timeStart = null;

        if (preg_match("/{$this->opt($this->t('reservationDate'))}[:\s]*(?<date>{$patterns['date']}).*\s+{$this->opt($this->t('reservationTime'))}[:\s]*(?<time>{$patterns['time']})/iu", $mainText, $m)) {
            $dateStart = strtotime(nice($m['date']));
            $timeStart = nice($m['time']);
        }

        if ($dateStart && $timeStart) {
            $ev->booked()->start(strtotime($timeStart, $dateStart))->noEnd();
        }

        $guestCount = $this->re('/(?:Party\s+Of|Party\s+Size):\s*(\d{1,3})\s*(?:\n|$)/i', $mainText);
        $ev->booked()->guests($guestCount);

        $guestName = nice($this->re("/Reserved\s+Under:\s+({$patterns['travellerName']})\s*(?:\n|$)/iu", $mainText));
        $ev->general()->traveller($guestName);

        if (preg_match("/\n[ ]*Cancell?ation Policy\s*[:]+\s*([^:\s].{2,}?(?:\n.{2,}?){0,3}?)(?:[ ]*\n[ ]*\n|\s*$)/iu", $mainText, $m)) {
            $ev->general()->cancellation(preg_replace('/\s+/', ' ', $m[1]));
        }

        if (preg_match("/Confirmation\s*#\s*[:]+\s*([-A-Z\d]+)/i", $mainText, $m)) {
            $ev->general()->confirmation($m[1]);
        } elseif (!empty($mainText) && !preg_match("/Confirmation\s*#/i", $mainText)
            && !empty($ev->getAddress())
        ) {
            $ev->general()->noConfirmation();
        }

        if (preg_match("/\[\s*(Ref)\s*[:]+\s*([-A-Z\d]{3,})\s*\]/i", $parser->getSubject(), $m)) {
            $email->ota()->confirmation($m[2], $m[1]);
        }

        return $email;
    }

    private function assignLang(): bool
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['reservationDate']) || empty($phrases['reservationTime']) ) {
                continue;
            }
            if ($this->http->XPath->query("//node()[{$this->contains($phrases['reservationDate'])}]")->length > 0
                && $this->http->XPath->query("//node()[{$this->contains($phrases['reservationTime'])}]")->length > 0
            ) {
                $this->lang = $lang;
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

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function re(string $re, ?string $str, $c = 1): ?string
    {
        if (preg_match($re, $str ?? '', $m) && array_key_exists($c, $m)) {
            return $m[$c];
        }

        return null;
    }
}
