<?php

namespace AwardWallet\Engine\premierinn\Email;

use AwardWallet\Schema\Parser\Email\Email;

class YourStay extends \TAccountChecker
{
    public $mailFiles = "premierinn/it-891753503.eml, premierinn/it-896379743.eml, premierinn/it-889907412.eml";

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'confNumber' => ['Booking reference'],
            'checkIn' => ['Check in'],
            'checkOut' => ['Check out'],
        ]
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]premierinn\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }
        return array_key_exists('subject', $headers)
            && preg_match('/Your stay at\s+\S.*\S\s+starts\s+(?:in 7 days|soon)!/i', $headers['subject']) > 0;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $href = ['.premierinn.com/', 't.e.premierinn.com', 'mena.premierinn.com'];

        if ($this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//text()[starts-with(normalize-space(),"© Premier Inn")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"Thank you for continuing to choose Premier Inn")]')->length === 0
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
        }
        $email->setType('YourStay' . ucfirst($this->lang));

        $xpathNoEmpty = "(normalize-space() or descendant::img)";

        $patterns = [
            'time' => '\d{1,2}(?:[:：.]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm  |  12.00pm
            'phone' => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52  |  (+351) 21 342 09 07  |  713.680.2992
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $h = $email->add()->hotel();

        $confNumbers = array_filter($this->http->FindNodes("//text()[{$this->eq($this->t('confNumber'), "translate(.,':','')")}]/following::text()[normalize-space()][1]", null, '/^[A-Z\d]{5,}$/'));

        if (count(array_unique($confNumbers)) === 1) {
            $confirmationTitle = $this->http->FindSingleNode("descendant::text()[{$this->eq($this->t('confNumber'), "translate(.,':','')")}][1]", null, true, '/^(.+?)[\s:：]*$/u');
            $confirmation = array_shift($confNumbers);
            $h->general()->confirmation($confirmation, $confirmationTitle);
        }

        $traveller = null;
        $travellerNames = array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Hello'))}]", null, "/^{$this->opt($this->t('Hello'))}[,\s]+({$patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));
        if (count(array_unique($travellerNames)) === 1) {
            $traveller = array_shift($travellerNames);
        }
        $h->general()->traveller($traveller);

        $xpathCheckIn = "//*[ node()[normalize-space()][1][{$this->eq($this->t('checkIn'), "translate(.,':','')")}] and node()[normalize-space()][2] ]";

        $hotelName = $address = $phone = null;

        $hotelText = implode("\n", $this->http->FindNodes("//*[ *[normalize-space()][2] and *[normalize-space()][last()][{$this->starts($this->t('View hotel details'))}] ]/*[normalize-space()]"));
        
        if (!$hotelText) {
            // it-889907412.eml
            $hotelText = implode("\n", $this->http->FindNodes($xpathCheckIn . "/ancestor::*[ preceding-sibling::*[normalize-space()] ][1]/preceding-sibling::*[normalize-space()][1]/descendant::*[ count(*[{$xpathNoEmpty}])=2 and *[{$xpathNoEmpty}][1][normalize-space()=''] and *[{$xpathNoEmpty}][2][normalize-space()] ][1]/*[{$xpathNoEmpty}][2]/descendant-or-self::*[ *[normalize-space()][2] ][1]/*[normalize-space()]"));
        }

        $this->logger->info('HOTEL INFO:');
        $this->logger->debug($hotelText);

        $addressText = '';

        if (preg_match("/^(?<name>.{2,})\n+(?<address>[\s\S]{3,}?)\n+(?:{$patterns['phone']}|{$this->opt($this->t('View hotel details'))})(?:\n|$)/", $hotelText, $m)) {
            $hotelName = $m['name'];
            $addressText = $m['address'];
        }

        if (preg_match("/^{$this->opt(['Need directions? Find us on Google Maps'])}/i", $addressText)) {
            $h->hotel()->noAddress();
        } elseif (count(preg_split('/\n+/', $addressText)) < 3) {
            $address = preg_replace('/\s+/', ' ', $addressText);
        }

        if (preg_match("/^{$patterns['phone']}$/m", $hotelText, $m)) {
            $phone = $m[0];
        }

        $h->hotel()->name($hotelName)->phone($phone, false, true);
        
        if ($address) {
            $h->hotel()->address($address);
        }

        $dateCheckIn = $dateCheckOut = $timeCheckIn = $timeCheckOut = null;

        $checkInText = implode(' ', $this->http->FindNodes($xpathCheckIn . "/node()[normalize-space() and not({$this->eq($this->t('checkIn'), "translate(.,':','')")})]"));
        $checkOutText = implode(' ', $this->http->FindNodes("//*[ node()[normalize-space()][1][{$this->eq($this->t('checkOut'), "translate(.,':','')")}] and node()[normalize-space()][2] ]/node()[normalize-space() and not({$this->eq($this->t('checkOut'), "translate(.,':','')")})]"));

        if (preg_match("/^(?<date>.{4,}\b\d{4})\s+{$this->opt($this->t('from'))}\s+(?<time>{$patterns['time']})$/i", $checkInText, $m)) {
            $dateCheckIn = strtotime($m['date']);
            $timeCheckIn = $m['time'];
        }

        if (preg_match("/^(?<date>.{4,}\b\d{4})\s+{$this->opt($this->t('before'))}\s+(?<time>{$patterns['time']})$/i", $checkOutText, $m)) {
            $dateCheckOut = strtotime($m['date']);
            $timeCheckOut = $m['time'];
        }

        if ($dateCheckIn && $timeCheckIn) {
            $h->booked()->checkIn(strtotime($timeCheckIn, $dateCheckIn));
        }

        if ($dateCheckOut && $timeCheckOut) {
            $h->booked()->checkOut(strtotime($timeCheckOut, $dateCheckOut));
        }

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

    private function assignLang(): bool
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['confNumber']) || empty($phrases['checkIn']) ) {
                continue;
            }
            if ($this->http->XPath->query("//*[{$this->contains($phrases['confNumber'])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->eq($phrases['checkIn'], "translate(.,':','')")}]")->length > 0
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

    private function starts($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }
}
