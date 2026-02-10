<?php

namespace AwardWallet\Engine\hertz\Email;

use AwardWallet\Schema\Parser\Email\Email;

class ReturnDetails extends \TAccountChecker
{
	public $mailFiles = "hertz/it-911201501.eml";
    public $lang = '';

    public $year = '';

    public $subjects = [
        // en
        'post pickup rental resources',
    ];

    public static $dictionary = [
        'en' => [
            'detectPhrase'      => ['Return Details'],
            'detectPhrase2'     => ['Your Rental Resources'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@emails.hertz.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) !== true) {
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
        if ($this->http->XPath->query('//a[contains(@href,"emails.hertz.com/") or contains(@href,"emails.hertz.com%2F")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"The Hertz Corporation")]')->length === 0
            && $this->http->XPath->query("//*[{$this->contains($this->t('detectPhrase'))}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains($this->t('detectPhrase2'))}]")->length === 0
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

        $email->setType('ReturnDetails' . ucfirst($this->lang));

        $r = $email->add()->rental();

        $r->general()->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('RENTAL RECORD #'))}]/ancestor::tr[normalize-space()][1]/following-sibling::tr[normalize-space()][1]",null, false, "/^([A-z\d]{5,})$/u"), 'RENTAL RECORD #');

        $this->year = $this->http->FindSingleNode("//text()[{$this->starts($this->t('©'))} and {$this->contains($this->t('The Hertz Corporation'))}]", null, false, "/^\©(\d{4})[ ]*The Hertz Corporation/u");

        $r->pickup()->noDate()->noLocation();

        $placeName = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Location Information'))}]", null, false, "/^(.+)[ ]+{$this->opt($this->t('Location Information'))}$/u");
        $placeAddress = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Location Information'))}]/ancestor::tr[1]/following-sibling::tr[normalize-space()][1]");

        if ($placeName !== null && $placeAddress !== null){
            $r->dropoff()
                ->location($placeName . ', ' . $placeAddress);
        }

        $dateString = $this->http->FindSingleNode("//text()[{$this->starts($this->t('See you back on'))}]");

        if (preg_match("/^{$this->opt($this->t('See you back on'))}[ ]*(?<date>.+)[ ]*{$this->opt($this->t('at'))}[ ]*(?<time>\d[\d\:]*[ ]*[Aa]?[Pp]?[Mm]?)$/u", $dateString, $m)){
            $r->dropoff()
                ->date($this->normalizeDate($m['date'] . ' ' . $m['time']));
        }

        return $email;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    private function normalizeDate($str)
    {
        $year = $this->year;

        $in = [
            "/^(\w+)[ ]*(\d{1,2})\w+[ ]*(\d{1,2}[Aa]?[Pp]?[Mm]?)$/u" // May 19th 5PM
        ];
        $out = [
            "$2 $1 $year $3",
        ];

        $str = preg_replace($in, $out, $str);

        return strtotime($str);
    }

    public function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $phrases) {
            if (empty($phrases['detectPhrase'])) {
                continue;
            }

            if ($this->http->XPath->query("//*[{$this->contains($phrases['detectPhrase'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
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
                return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
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
