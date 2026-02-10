<?php

namespace AwardWallet\Engine\amazongift\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class RevisionAccount extends \TAccountChecker
{
    public $mailFiles = "amazongift/statements/it-65658946.eml, amazongift/statements/it-910635315.eml, amazongift/statements/it-911807784.eml, amazongift/statements/it-911824464.eml, amazongift/statements/it-911824467.eml";
    public $subjects = [
        'Revision to Your Amazon.com Account',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            "phrases" => [
                "Per your request, we have successfully enabled Two-Step Verification on your account",
                "Per your request, we have successfully disabled Two-Step Verification on your account",
                "Per your request, we have successfully changed your preferred method for Two-Step Verification on your account to",
                "Per your request, we have successfully added Authenticator App to receive security codes on your account",
                "Per your request, we have successfully added an Authenticator App to receive security codes on your account",
                "The e-mail address associated with your account has been changed",
                "Per your request, we have changed the e-mail address associated with your account",
                "Per your request, we have added an email address to your account",
                "Per your request, we have updated your mobile phone information:",
                "Per your request, you have successfully deleted your mobile phone information",
                "Per your request, you have successfully changed your name, which now reads",
                "Per your request, we have successfully changed your password.",
                "to receive security codes on your account. You can always go to Your Account",
                "Amazon upgraded your account security by creating a passkey",
            ],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@amazon.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $bodyText = $parser->getBody();

        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Amazon.com')]")->length === 0
                && stripos($bodyText, "Amazon.com") === false) {
            return false;
        }

        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Thanks for visiting Amazon')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('phrases'))}]")->length > 0) {
            return true;
        }

        //it-910635315.eml
        if (stripos($bodyText, 'Thanks for visiting Amazon.com!') !== false
            && preg_match("/{$this->opt($this->t('phrases'))}/", $bodyText)
            && stripos($bodyText, 'Thanks again for shopping with us') !== false) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]amazon\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByBody($parser) === true) {
            $st = $email->add()->statement();
            $st->setMembership(true);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

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

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "contains(normalize-space(.), \"{$s}\")";
        }, $field));
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }
}
