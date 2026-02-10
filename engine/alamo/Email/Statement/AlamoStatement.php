<?php

namespace AwardWallet\Engine\alamo\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;


class AlamoStatement extends \TAccountChecker
{
	public $mailFiles = "alamo/statements/it-919777245.eml, alamo/statements/it-924409506.eml, alamo/statements/it-925351783.eml, alamo/statements/it-928227404.eml";
    public $detectSubject = [
        'FINAL Days To Use Your Offer',
        "This Deal's Going Fast",
        'Too Good To Miss',
        "You've Got An Offer Inside",
        "Your Offer is Waiting",
        "Off Base Rate",
        "Promo Alert",
        "Did You See These Savings",
        "Off Base Rate Now",
        "come back soon and save on your next rental",
        "Save on Your Rental",
        "Savings Are Here",
        "Vacation Success Without Preparation Stress",
    ];
    public $detectBody = [
        'en' => [
            'Congratulations on becoming an Alamo Insider',
            'This is confirmation that your Alamo Insiders profile has been updated.',
            'Your username and/or password information has been changed',
            'Click on the link below to reset your password',
            'Here is the information you requested:',
        ],
    ];

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'welcomePhrases' => 'Congratulations on becoming an Alamo Insider!',
            'detectMembership' => ['As a valued Alamo Insiders® member, you’re invited to share your thoughts by participating in our survey'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers['from'], '@email.alamo.com') === false) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if (stripos($parser->getCleanFrom(), '@email.alamo.com') !== false
            && $this->http->XPath->query("//a[contains(@href,'.alamo.com/') or contains(@href,'www.alamo.com')]")->length >= 0
        ) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@email.alamo.com') !== false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $number = $this->http->FindSingleNode("//table[./descendant::td[normalize-space() = 'Log In'] and ./descendant::td[normalize-space() = 'Deals'] and ./descendant::td[normalize-space() = 'Reservations']]/following::tr[count(./child::td) = 2 and ./child::td[starts-with(normalize-space(), '#')]][1]/descendant::td[2]",
            null, true, "/^\#\s*(\d{5,})\s*$/");

        if (empty($number)) {
            $number = $this->http->FindSingleNode("//tr[count(./child::td) = 2 and ./child::td[2][{$this->starts($this->t('#'))}]]/child::td[normalize-space()][2]", null, false, "/^\#\s*(\d{5,})\s*$/u");
        }

        if (!empty($number)) {
            $st->setNumber($number)
                ->setLogin($number);
        }

        $name = $this->http->FindSingleNode("//table[./descendant::td[normalize-space() = 'Log In'] and ./descendant::td[normalize-space() = 'Deals'] and ./descendant::td[normalize-space() = 'Reservations']]/following::tr[count(./child::td) = 2 and ./child::td[starts-with(normalize-space(), '#')]][1]/descendant::td[1]");

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//tr[count(./child::td) = 2 and ./child::td[2][{$this->starts($this->t('#'))}]]/child::td[normalize-space()][1]", null, false, "/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])$/u");
        }

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Congratulations'))}][1]", null, false, "/^{$this->opt($this->t('Congratulations'))}[\, ]*([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])[\,\.\! ]$/u");
        }

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//text()[{$this->contains($this->t('save time at the rental counter'))}][1]", null, false, "/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])[\, ]*{$this->opt($this->t('save time at the rental counter'))}/u");
        }

        if (!empty($name)) {
            $st->addProperty("Name", $name);
        }

        if ($number || $name) {
            $st->setNoBalance(true);
        }

        $detectMembership = $this->http->XPath->query("//*[{$this->contains($this->t('detectMembership'))}]");

        if (!$number && !$name && $detectMembership->length > 0 && $this->detectEmailByBody($parser) === true){
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
            return "normalize-space(.)=\"{$s}\"";
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), \"{$s}\")";
        }, $field)) . ')';
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
            return "contains(normalize-space(.), \"{$s}\")";
        }, $field)) . ')';
    }

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s));
        }, $field)) . ')';
    }
}
