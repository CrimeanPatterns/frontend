<?php

namespace AwardWallet\Engine\cayman\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class YourProfile extends \TAccountChecker
{
    public $mailFiles = "cayman/statements/it-907777476.eml, cayman/statements/it-905564988.eml, cayman/statements/it-905564987.eml, cayman/statements/it-901819932.eml";

    private $subjects = [
        'en' => ['Profile Created', 'Reset password to Travel Bank', 'Travel Bank Welcome Email', 'Travel Bank Ticket Purchase Confirmation']
    ];

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'hello' => ['Hello', 'Dear', 'Welcome'],
            'membershipNo' => ['your Sir Turtle Rewards / Travel Bank Login id is'],
        ]
    ];

    private $patterns = [
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@caymanairways.sabre.com') !== false || stripos($from, 'caymanairways.donotreply@sabre.com') !== false
            || preg_match('/[.@]caymanairways\.(?:net|com)$/i', $from) > 0;
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
        $href = ['.caymanairways.com/', 'www.caymanairways.com'];

        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"At Cayman Airways we") or contains(normalize-space(),"Cayman Airways ID:")]')->length === 0
        ) {
            return false;
        }

        return $this->isMembership() || $this->findRoot1()->length > 0;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->lang = 'en';
        $email->setType('YourProfile' . ucfirst($this->lang));

        $st = $email->add()->statement();

        $name = $number = $balance = null;

        /*
            Step 1: parse fields
        */

        $roots1 = $this->findRoot1(); // it-907777476.eml

        if ($roots1->length > 0) {
            $this->logger->debug('Found root1.');
            $root1 = $roots1->item(0);

            $name = $this->http->FindSingleNode("following::text()[{$this->eq($this->t('Passenger Name'), "translate(.,':*','')")}][1]/following::text()[normalize-space()][1]", $root1, true, "/^[*:\s]*({$this->patterns['travellerName']})$/u");
            $balance = $this->http->FindSingleNode("following::text()[{$this->eq($this->t('Account Balance'), "translate(.,':*','')")}][1]/following::text()[normalize-space()][1]", $root1, true, "/^[*:\s]*(\d[,.\'\d ]*)$/u");
        }

        if (!$name) {
            $travellerNames = array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('hello'))}]", null, "/^{$this->opt($this->t('hello'))}[,\s]+({$this->patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));
    
            if (count(array_unique($travellerNames)) === 1) {
                $name = array_shift($travellerNames);
            }
        }

        if (!$number) {
            $number = $this->http->FindSingleNode("//text()[{$this->eq($this->t('membershipNo'), "translate(.,':*','')")}]/following::text()[normalize-space()][1]", null, true, "/^[*:\s]*(\d{3,})$/");
        }

        /*
            Step 2: set fields
        */

        if ($name) {
            $st->addProperty('Name', $name);
        }

        if ($number) {
            $st->setNumber($number)->setLogin($number)->addProperty('AccountNumber', $number);
        }

        if ($balance !== null) {
            $st->setBalance($balance);
        } elseif ($name || $number) {
            $st->setNoBalance(true);
        }

        if (!$name && !$number && $balance === null
            && $this->isMembership()
        ) {
            $st->setMembership(true);
        }

        return $email;
    }

    private function findRoot1(): \DOMNodeList
    {
        return $this->http->XPath->query("//node()[{$this->eq($this->t('Purchase Transaction Details'), "translate(.,':*','')")}]");
    }

    private function isMembership(): bool
    {
        $phrases = [
            'Your password reset link is:',
            'Your new Cayman Airways Web Profile has been created.',
            'This is your Cayman Airways Web Profile password.',
            'Your Cayman Airways frequent flyer profile and Sir Turtle travel bank credit account have been created.',
        ];

        if ($this->http->XPath->query("//node()[{$this->contains($phrases)}]")->length > 0) {
            $this->logger->debug(__FUNCTION__ . '()');

            return true;
        }

        return false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 0;
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
