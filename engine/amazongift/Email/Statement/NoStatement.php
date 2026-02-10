<?php

namespace AwardWallet\Engine\amazongift\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class NoStatement extends \TAccountChecker
{
    public $mailFiles = "amazongift/statements/it-65578798.eml, amazongift/statements/it-908620139.eml, amazongift/statements/it-911455821.eml";
    public $subjects = [
        '/(^|:\s*)Notification of a change to the Amazon Rewards Visa Card in your Amazon/i',
        '/^Your Amazon.com account/u',
        '/Account Recovery: Reset your Amazon.com account password/u',
        '/Your Amazon Associates account is at risk/',
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
            && $this->isMembership($text);
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]amazon\.com/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $text = $parser->getBody();

        if ($this->isMembership($text)) {
            $st = $email->add()->statement();
            $st->setMembership(true);
        }

        return $email;
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function isMembership($text): bool
    {
        $phrases = [
            'Congratulations! The Amazon Rewards Visa Signature Card',
            'We have cancelled your gift card order',
            'We have processed your gift card order',
            'Your order is still being processed and we will dispatch it after we get your authorization',
            'We are writing to let you know that your credit or debit card issuer received a report of unauthorized use of your card',
            'We are writing to let you know that the issuer of your credit or debit card received a report of unauthorized use of your card',
            'We have canceled your order and voided the Amazon gift card balance used in the purchase.',
            'The hold has also been removed from your Amazon.com account',
            'Amazon.com uses TRS Recovery Services, Inc.',
            'We believe that an unauthorized party may have accessed your account',
            'Your Amazon Associates account is at risk',
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
