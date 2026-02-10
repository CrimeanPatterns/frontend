<?php

namespace AwardWallet\Engine\amazongift\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Junk extends \TAccountChecker
{
    public $mailFiles = "amazongift/statements/it-910416938.eml, amazongift/statements/it-911859868.eml";
    public $subjects = [
        '/Balance paid on your Amazon seller account/',
        '/We’re verifying your Amazon Business account/',
        '/Your Amazon Business account has been closed/',
        '/Your Amazon seller account has been temporarily deactivated/',
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) === true) {
            foreach ($this->subjects as $subject) {
                if (preg_match($subject, $headers['subject'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $text = $parser->getBody();

        return ($this->http->XPath->query("//text()[contains(normalize-space(), 'Amazon')]")->length > 0
                || stripos($text, 'Amazon') !== false)
            && $this->isJunk($text);
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]amazon\.com/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $text = $parser->getBody();

        if ($this->isJunk($text)) {
            $email->setIsJunk(true);
        }

        return $email;
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function isJunk($text): bool
    {
        $phrases = [
            'an attempt to settle your Amazon seller account balance',
            'Thanks for signing up for Amazon Business',
            'We will be removing your organization and any other Amazon or Amazon Business',
            'Your Amazon seller account has been temporarily deactivated',
        ];

        foreach ($phrases as $phrase) {
            if ($this->http->XPath->query("//text()[{$this->contains($phrase)}]")->length > 0
                || stripos($text, $phrase) !== false) {
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
}
