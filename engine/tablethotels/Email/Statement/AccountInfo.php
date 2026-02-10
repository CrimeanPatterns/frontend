<?php

namespace AwardWallet\Engine\tablethotels\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class AccountInfo extends \TAccountChecker
{
    public $mailFiles = "tablethotels/statements/it-167860040.eml, tablethotels/statements/it-925134372.eml, tablethotels/statements/it-921947049.eml, tablethotels/statements/it-926734226.eml, tablethotels/statements/it-927541070.eml";

    private $subjects = [
        'en' => ['Your Account Has Been Updated', 'Reset your Password', 'Your Plus Membership Is Active', 'We Need to Talk About Your Membership'],
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@tablethotels.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (is_string($phrase) && array_key_exists('subject', $headers) && stripos($headers['subject'], $phrase) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query('//a[contains(@href,".tablethotels.com/") or contains(@href,"cb.tablethotels.com")]')->length === 0
            && $this->http->XPath->query('//text()[starts-with(normalize-space(),"Copyright ©") and contains(normalize-space(),"Tablet Hotels LLC")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"Tablet Hotels LLC. New York City")]')->length === 0
        ) {
            return false;
        }

        return $this->findRoot()->length === 1 || $this->isMembership();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $patterns['travellerName'] = '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]';

        $st = $email->add()->statement();

        $roots = $this->findRoot();

        if ($roots->length === 1) {
            $root = $roots->item(0);

            $name = $login = null;

            $firstName = $this->http->FindSingleNode(".", $root, true, "/^First Name\s*[:]+\s*({$patterns['travellerName']})$/u");
            $lastName = $this->http->FindSingleNode("following::p[normalize-space()][1][starts-with(normalize-space(),'Last Name')]", $root, true, "/^Last Name\s*[:]+\s*({$patterns['travellerName']})$/u");
    
            if ($firstName && $lastName) {
                $name = $firstName . ' ' . $lastName;
            }
    
            $login = $this->http->FindSingleNode("following::p[normalize-space()][1][starts-with(normalize-space(),'Last Name')]/following::p[normalize-space()][1][starts-with(normalize-space(),'Email')]", $root, true, "/^Email\s*[:]+\s*(\S+@\S+)$/");

            $st->addProperty('Name', $name)->setLogin($login);

            if ($name || $login) {
                $st->setNoBalance(true);
            }

            return $email;
        }

        /*
            minor formats
        */

        $name = $balance = null;

        // it-927541070.eml
        $travellerNames = array_filter($this->http->FindNodes("//text()[{$this->starts(['Welcome'])}]", null, "/^{$this->opt(['Welcome'])}[,\s]+({$patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));

        if (count(array_unique($travellerNames)) === 1) {
            $name = array_shift($travellerNames);
        }

        if (!$name) {
            // it-926734226.eml
            $name = $this->http->FindSingleNode("//text()[{$this->starts(['We’ve received a request to reset your password', "We've received a request to reset your password"])}]/preceding::text()[normalize-space()][1]", null, true, "/^({$patterns['travellerName']})\s*,$/u");
        }

        if ($name) {
            $st->addProperty('Name', $name);
        }

        if ($balance !== null) {
            $st->setBalance($balance);
        } elseif ($name) {
            $st->setNoBalance(true);
        } elseif ($this->isMembership()) {
            $st->setMembership(true);
        }

        return $email;
    }

    public static function getEmailLanguages()
    {
        return ['en'];
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function findRoot(): \DOMNodeList
    {
        // examples: it-167860040.eml
        return $this->http->XPath->query("//p[contains(normalize-space(),'your account information')]/following::p[normalize-space()][1][starts-with(normalize-space(),'First Name')]");
    }

    private function isMembership(): bool
    {
        // examples: it-925134372.eml, it-921947049.eml

        $phrases = [
            'Your Plus Membership Is Active',
            "We've received a request to reset your password",
            'We’ve received a request to reset your password',
            "You and your Tablet Plus membership haven't spoken in a while, but we can help you work it out.",
            'To reset your password follow the link below within the next 24 hours',
        ];

        if ($this->http->XPath->query("//node()[{$this->contains($phrases)}]")->length > 0) {
            $this->logger->debug(__FUNCTION__ . '()');

            return true;
        }

        return false;
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
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }
}
