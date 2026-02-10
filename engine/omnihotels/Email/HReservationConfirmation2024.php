<?php

namespace AwardWallet\Engine\omnihotels\Email;

use AwardWallet\Schema\Parser\Email\Email;

class HReservationConfirmation2024 extends \TAccountChecker
{
	public $mailFiles = "omnihotels/it-930091405.eml";

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'checkIn'         => ['CHECK IN:'],
            'checkOut'        => ['CHECK OUT:'],
        ],
    ];

    private $subjects = [
        'en' => ['Check out online and access your statement'],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]omnihotels\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (is_string($phrase) && stripos($headers['subject'], $phrase) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query('//a[contains(@href,".omnihotels.com/") or contains(@href,"em.omnihotels.com")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"Your reservation at Omni")] | //text()[starts-with(normalize-space(),"©") and contains(normalize-space(),"Omni Hotels & Resorts")]')->length === 0
        ) {
            return false;
        }

        return $this->assignLang();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }
        $email->setType('HReservationConfirmation2023' . ucfirst($this->lang));

        $this->parseHotel($email);

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

    private function parseHotel(Email $email): void
    {
        $patterns = [
            'time'          => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.    |    3pm
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $h = $email->add()->hotel();

        $confirmation = $this->http->FindSingleNode("//td[{$this->starts($this->t('Confirmation #'))}][1]", null, true, "/^{$this->opt($this->t('Confirmation #'))}[ ]*\:[ ]*([-A-Z\d]{5,})$/");
        $travellerName = $this->http->FindSingleNode("//tr[{$this->starts($this->t('Confirmation #'))}][1]/following-sibling::tr[normalize-space()][1]", null, true, "/^({$patterns['travellerName']})[ ]*[\.\!\,\?]*$/");

        $h->general()
            ->traveller($travellerName)
            ->confirmation($confirmation);

        $checkInVal = $this->http->FindSingleNode("//*[ count(tr[normalize-space()])=2 and tr[normalize-space()][1][{$this->eq($this->t('checkIn'))}] ]/tr[normalize-space()][2]");
        $checkOutVal = $this->http->FindSingleNode("//*[ count(tr[normalize-space()])=2 and tr[normalize-space()][1][{$this->eq($this->t('checkOut'))}] ]/tr[normalize-space()][2]");

        if (preg_match("/^(\d{1,2}\/\d{1,2}\/\d{2,4}[ ]*{$patterns['time']})$/u", $checkInVal)) {
            $h->booked()->checkIn(strtotime($checkInVal));
        }

        if (preg_match("/^(\d{1,2}\/\d{1,2}\/\d{2,4}[ ]*{$patterns['time']})$/u", $checkOutVal)) {
            $h->booked()->checkOut(strtotime($checkOutVal));
        }

        $hotelName = $this->http->FindSingleNode("//tr[{$this->starts($this->t('Confirmation #'))}][1]/preceding-sibling::tr[normalize-space()][1]");

        $h->hotel()
            ->name($hotelName)
            ->noAddress();
    }

    private function assignLang(): bool
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['checkOut']) || empty($phrases['checkIn'])) {
                continue;
            }

            if ($this->http->XPath->query("//*[{$this->eq($phrases['checkOut'])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->contains($phrases['checkIn'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return $phrase;
        }

        if ($lang === '') {
            $lang = $this->lang;
        }

        if (empty(self::$dictionary[$lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$lang][$phrase];
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }
}
