<?php

namespace AwardWallet\Engine\goldpassport\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class MembershipAndOTC extends \TAccountChecker
{
    public $mailFiles = "goldpassport/statements/it-903555267.eml, goldpassport/statements/it-903811531.eml, goldpassport/statements/it-903557862.eml, goldpassport/statements/it-908612945.eml, goldpassport/statements/it-910184001.eml";

    private $subjects = [
        'en' => [
            'Your Account Has Been Updated',
            'Confirming Your Recent Activity', // it-903811531.eml
            'Confirm Your Device for Digital Key Access', // it-910184001.eml
            'A Change to Your Account Has Been Requested',
        ],
    ];

    private $membershipPhrases = [
        'You have successfully created a passkey for your World of Hyatt account.', // it-903555267.eml
        'You have successfully removed a passkey for your World of Hyatt account.',
        'We’re confirming that you recently made a transaction removing points from your account.', // it-903811531.eml
        'We received a request to update your World of Hyatt account information.',
        'A request was recently made to update your World of Hyatt account information.',
    ];

    private $otcPhrases = [
        'Your one-time password is', // it-903557862.eml
        'Your passcode is', // it-910184001.eml
    ];

    public static $dictionary = [
        'en' => [],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]hyatt\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (array_key_exists('subject', $headers)
            && (stripos($headers['subject'], 'Your World of Hyatt Membership Number') !== false
                || preg_match('/Your World of Hyatt Account Passkey Was (?:Created|Removed)/i', $headers['subject'])
                || preg_match('/Enter [-\s\d]+ to Verify Your World of Hyatt Account/i', $headers['subject'])
            )
        ) {
            return true;
        }

        if ((!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true)
            && (!array_key_exists('subject', $headers) || strpos($headers['subject'], 'World of Hyatt') === false)
        ) {
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
        $href = ['.hyatt.com/', '.hyatt.com%2F', 'help.hyatt.com'];

        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//text()[starts-with(normalize-space(),"©") and contains(normalize-space(),"Hyatt")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"Hyatt Corporation. All rights reserved")]')->length === 0
        ) {
            return false;
        }

        if (empty($textPlain = $parser->getPlainBody())) {
            $textPlain = $parser->getHTMLBody();
        }

        return $this->isMembership($textPlain) || $this->parseNumber() !== null;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if (empty($textPlain = $parser->getPlainBody())) {
            $textPlain = $this->http->Response['body'];
        }

        $this->parseOTC($email, $textPlain);

        $st = $email->add()->statement();

        $number = $this->parseNumber();

        if ($number) {
            // it-908612945.eml
            $st->setNumber($number)->setLogin($number)->setNoBalance(true);

            return $email;
        }

        if ($this->isMembership($textPlain)) {
            $st->setMembership(true);

            return $email;
        }

        return $email;
    }

    private function parseNumber(): ?string
    {
        return $this->http->FindSingleNode("//text()[starts-with(normalize-space(),'Your World of Hyatt membership number is')]", null, true, "/^Your World of Hyatt membership number is[:\s]+([A-Z\d]+\d[A-Z\d]+)[,.;!\s]*$/");
    }

    private function isMembership(?string $text = ''): bool
    {
        $phrases = array_merge($this->membershipPhrases, $this->otcPhrases);

        if ($this->http->XPath->query("//node()[{$this->contains($phrases)}]")->length > 0) {
            $this->logger->debug(__FUNCTION__ . '()');

            return true;
        }

        if (empty($text)) {
            return false;
        }

        $text = preg_replace('/\s+/', ' ', $text);

        foreach ($phrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $this->logger->debug(__FUNCTION__ . '()');

                return true;
            }
        }

        return false;
    }

    private function parseOTC(Email $email, string $textPlain): bool
    {
        // examples: it-903557862.eml, it-910184001.eml

        $otcPattern = "/{$this->opt($this->otcPhrases)}[:：\s]*(\d[- \d]+\d)(?:\s*[,.;!]|\s|$)/";
        $code = $this->http->FindSingleNode("//node()[{$this->eq($this->otcPhrases, "translate(.,':','')")}]/following-sibling::node()[normalize-space()][1]", null, true, "/^(\d[- \d]+\d)[,.;!\s]*$/")
            ?? $this->re($otcPattern, $this->http->FindSingleNode("//text()[{$this->contains($this->otcPhrases)}]/ancestor::*[ descendant::text()[normalize-space()][2] ][1]"))
            ?? $this->re($otcPattern, preg_replace('/\s+/', ' ', $textPlain))
        ;

        if ($code !== null) {
            $this->logger->debug(__FUNCTION__ . '()');
            $otс = $email->add()->oneTimeCode();
            $otс->setCode($code);

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
