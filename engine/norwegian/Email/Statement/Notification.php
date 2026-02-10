<?php

namespace AwardWallet\Engine\norwegian\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Notification extends \TAccountChecker
{
    public $mailFiles = "norwegian/statements/it-70309055.eml, norwegian/statements/it-70366170.eml, norwegian/statements/it-77254631.eml, norwegian/statements/it-910666721-sv.eml, norwegian/statements/it-911880337.eml, norwegian/statements/it-911447368.eml";

    public $subjects = [
        '/(?:^|:\s*)Norwegian has received an inquiry for a new password$/i',
        '/(?:^|:\s*)Norwegian har mottagit en förfrågan om ett nytt lösenord$/i', // sv
        '/(?:^|:\s*)Profile (?:Changed Notification|receipt)/i',
        '/we have some news regarding your Norwegian Reward membership$/i',
        '/(?:^|:\s*)Your one-time code(?:\s*[.!]|$)/i',
    ];

    private $membershipPhrases = [
        'A new profile has been registered at Norwegian',
        'Follow the link to set a new password',
        'Följ länken för att ställa in ett nytt lösenord', // sv
        'Your new password is',
        'Your Norwegian profile has changed',
        'We would just like to inform you about an adjustment made to our Privacy Policy',
        'You have received this email because you are a registered member of Norwegian Reward',
    ];

    private $otcPhrases = ['Your one-time code is'];

    public $lang = '';

    public static $dictionary = [
        'sv' => [
            // 'hello' => '',
        ],
        'en' => [
            'hello' => ['Hi', 'Dear'],
        ],
    ];

    private $patterns = [
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $subject) {
            if (array_key_exists('subject', $headers) && preg_match($subject, $headers['subject'])) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains(['.norwegian.com/', '.norwegianreward.com/', 'profile.norwegian.com', 'email.norwegianreward.com', 'norwegian.custhelp.com'], '@href')}] | //*[{$this->contains(['Your Norwegian profile', 'Best regards from Norwegian', 'Med vänlig hälsning Norwegian'])}]")->length === 0
        ) {
            return false;
        }

        return $this->isMembership();
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]norwegian\.(?:no|com)$/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->lang = 'en';

        $this->parseOTC($email);

        $st = $email->add()->statement();

        $name = $username = $number = null;

        $travellerNames = array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('hello'))}]", null, "/^{$this->opt($this->t('hello'))}[,\s]+({$this->patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));
        if (count(array_unique($travellerNames)) === 1) {
            $name = array_shift($travellerNames);
        }

        $username = $this->http->FindSingleNode("descendant::text()[{$this->contains('Your username is')}]", null, true, "/{$this->opt('Your username is')}[:：\s]*(\S+@\S+)(?:\s*[,.;!]|$)/");
        $number = $this->http->FindSingleNode("descendant::text()[{$this->contains('Your Reward Number is')}]", null, true, "/{$this->opt('Your Reward Number is')}[:：\s]*([-A-z\d]+)(?:\s*[,.;!]|$)/");

        if ($name !== null) {
            $st->addProperty('Name', $name);
        }

        if ($username !== null) {
            $st->setLogin($username);
        }

        if ($number !== null) {
            $st->setNumber($number);
        }

        if ($name !== null || $username !== null || $number !== null) {
            $st->setNoBalance(true);

            return $email;
        }

        if ($this->isMembership()) {
            $st->setMembership(true);

            return $email;
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

    private function isMembership(): bool
    {
        // examples: ???

        $phrases = array_merge($this->membershipPhrases, $this->otcPhrases);

        if ($this->http->XPath->query("//node()[{$this->contains($phrases)}]")->length > 0) {
            $this->logger->debug(__FUNCTION__ . '()');

            return true;
        }

        return false;
    }

    private function parseOTC(Email $email): bool
    {
        // examples: it-911880337.eml

        $otcPattern = "/{$this->opt($this->otcPhrases)}[:：\s]*([A-z\d]+)(?:\s*[,.;!]|$)/";
        $code = $this->http->FindSingleNode("descendant::text()[{$this->contains($this->otcPhrases)}]", null, true, $otcPattern);

        if ($code !== null) {
            $this->logger->debug(__FUNCTION__ . '()');
            $otс = $email->add()->oneTimeCode();
            $otс->setCode($code);

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
