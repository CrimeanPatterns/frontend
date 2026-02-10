<?php

namespace AwardWallet\Engine\marriott\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Notification extends \TAccountChecker
{
    public $mailFiles = "marriott/statements/it-927503910.eml";
    public $subjects = [
        'Marriott Bonvoy Account Assistance',
        'Free Night Award  Marriott Bonvoy Account Review',
        'Combine Account Request | Member Number:',
        'Marriott Bonvoy® Account Notice',
        'Confirmation of Returned Points to Your Bonvoy Account',
        'Marriott Bonvoy® Account Notification',
        'Your Marriott GiftCard - New Account',
        'HDFC Bank Marriott Bonvoy Credit Card to My Marriott Bonvoy',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'phrases-1' => [
                'Thank you for reaching out to Marriott Bonvoy',
                'I’ve personally reviewed your Marriott Bonvoy account',
                'Thank you for contacting Marriott',
                'We noticed that an airline mileage award was recently purchased with points from your Marriott Bonvoy',
                'For security and the protection of your account information, please contact Marriott Bonvoy',
                'we value your continued support of our Marriott Bonvoy',
                'Marriott Bonvoy to request help with modifying your reservation',
                'We are sorry to hear of the unauthorized activity on your',
                'Thank you for choosing Marriott International for your travel needs',
                'Thank you for your response, my name is Will and I am happy to assist you',
                'Thank you for reaching out regarding your upcoming stay',
                'Thank you for your message. I am unable to retrieve the reservation under those details.',
                'You have successfully registered your Marriott GiftCard and created an',
                'Thank you for reaching out regarding the recent cancellation of your stay',
                'account is now fully active and in good standing with hold status released',
                'I am happy to assist you with merging your duplicate accounts',
                'Thank you for being a valued Marriott Bonvoy',
            ],
            'Dear' => ['Dear', 'Hello'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@marriott.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Marriott Bonvoy'))}]")->length === 0
        && $this->http->XPath->query("//text()[{$this->contains($this->t('Marriott GiftCard'))}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('phrases-1'))}]")->length > 0
            && ($this->http->XPath->query("//text()[{$this->contains($this->t('Customer Care'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t('Customer service'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t('Loyalty Program Risk'))}]")->length > 0)
        ) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]marriott(?:\-service)?\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]", null, true, "/^{$this->opt($this->t('Dear'))}\s+([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])[\,]*$/");

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//text()[{$this->contains($this->t('phrases-1'))}]/preceding::text()[{$this->starts($this->t('Dear'))}][1]", null, true, "/^{$this->opt($this->t('Dear'))}\s+([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])[\,]*$/");
        }

        if (!empty($name)) {
            $st->addProperty('Name', trim($name, ','));
            $st->setNoBalance(true);
        } else {
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

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), \"{$s}\")";
        }, $field));
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
