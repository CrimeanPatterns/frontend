<?php

namespace AwardWallet\Engine\expedia\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class WelcomeOneKey extends \TAccountChecker
{
    public $mailFiles = "expedia/statements/it-902817930.eml, expedia/statements/it-903084272.eml";

    public $lang = 'en';
    public $providerCode;

    public $detectSubject = [
        'Your One Key monthly check-in',
    ];

    public static $detectProviders = [
        'hotels' => [
            'from'    => ['mail@eg.hotels.com'],
            'bodyUrl' => '.hotels.com',
        ],
        'expedia' => [
            'from'    => ['mail@eg.expedia.com', 'do-not-reply@accounts.expedia.com'],
            'bodyUrl' => '.hotels.com',
        ],
        'homeaway' => [
            'from'    => ['mail@eg.vrbo.com'],
            'bodyUrl' => '.vrbo.com',
        ],
    ];
    public static $dictionary = [
        "en" => [
            'membershipTrue'               => ['Your latest One Key™ update', 'Welcome to One Key™', 'As a Blue member, you get great benefits',
                'You\'ve just earned OneKeyCash', 'you know that as a One Key™ member',
                'As a Silver member, you get great benefits',
                'As a Blue member you qualify for great benefits',
                'did you know you\'re a One Key™ member?',
                'As a member, you\'ll earn OneKeyCash',
                "there's OneKeyCash* sitting in your account",
                "Remember, as a Blue member of One Key",
                "Your OneKeyCash™ is about to expire",
                'we\'ve added another one to your account',
                'As a One Key member, you can earn rewards',
            ],
            'membershipFalse'              => ['Just become a member (it\'s free!),', 'Join One Key to unlock rewards',
                'Plus, when you sign up, you can unlock Member Prices',
                'Join One Key™ for free and',
            ],
            'Save more with Member Prices' => [
                // after status
                'Save more with Member Prices', 'Congrats! As a Blue member you now qualify for great benefits',
                'As a Blue member, you get great benefits',
                'As a Silver member, you get great benefits',
                'As a Blue member you qualify for great benefits',
            ],
            'Explore your new rewards' => [
                // before status
                'Explore your new rewards', 'Explore your new rewards',
            ],
            'You have'             => ['You have', 'you have', 'you now have a total of', 'You now have', ", there's", 'It brings your total balance to',
                'you still have', ],
            'blueStatusDetect'     => ["You're Blue tier", "trip elements collected to reach Silver"],
            'silverStatusDetect'   => ["You're Silver tier"],
            'goldStatusDetect'     => ["Enjoy being Gold"],
            'platinumStatusDetect' => ["You're Platinum tier", 'Enjoy being Platinum'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.](?:expediagroup|accounts\.expedia)\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        foreach (self::$detectProviders as $code => $detects) {
            if (!empty($detects['from'])) {
                foreach ($detects['from'] as $dFrom) {
                    if (strpos($headers['from'], $dFrom) !== false) {
                        $this->providerCode = $code;

                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        return false;
        // if (!$this->assignProvider($parser->getHeaders())) {
        //     return false;
        // }
        //
        // return $this->assignLang();
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        // $this->assignLang();

        // if (empty($this->lang)) {
        //     return $email;
        // }

        $this->assignProvider($parser->getHeaders());
        $email->setProviderCode($this->providerCode);

        if ($this->http->XPath->query("//node()[{$this->contains($this->t('membershipFalse'))}]")->length > 0) {
            $email->setIsJunk(true);

            return $email;
        }

        $st = $email->add()->statement();

        $balance = $this->http->FindSingleNode("//p[{$this->starts($this->t('You have'))}][{$this->contains($this->t('in OneKeyCash'))}]");

        if (empty($balance)) {
            $balance = $this->http->FindSingleNode("(//p[{$this->contains($this->t('You have'))}][{$this->contains($this->t('in OneKeyCash'))}])[1]");
        }

        if (empty($balance)) {
            $balance = $this->http->FindSingleNode("(//h2[{$this->contains($this->t('You have'))}][{$this->contains($this->t('in OneKeyCash'))}])[1]");
        }

        if (preg_match("/{$this->opt($this->t('You have'))}\s*[\\$\£]\s*(\d[\d\., ]*)\s*{$this->opt($this->t('in OneKeyCash'))}/u", $balance, $m)) {
            $st->setBalance($m[1]);
        } elseif (empty($balance)) {
            $st->setNoBalance(true);

            if ($this->http->XPath->query("//node()[{$this->contains($this->t('membershipTrue'))}]")->length > 0) {
                $st->setMembership(true);
            }
        }

        $date = strtotime($this->http->FindSingleNode("//text()[{$this->contains($this->t('but it\'s set to expire on'))}]", null, true,
            "/{$this->opt($this->t('but it\'s set to expire on'))}\s*(.+?)\./"));

        if (!empty($date)) {
            $st->setExpirationDate($date);
        }
        $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Save more with Member Prices'))}]/preceding::text()[normalize-space()][1]/ancestor::*[1][contains(@style, 'background-color:')]");

        if (empty($status)) {
            $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Explore your new rewards'))}]/following::text()[normalize-space()][1]/ancestor::*[1][contains(@style, 'background-color:')]");
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('blueStatusDetect'))}]")->length > 0) {
            $status = 'Blue';
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('silverStatusDetect'))}]")->length > 0) {
            $status = 'Silver';
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('platinumStatusDetect'))}]")->length > 0) {
            $status = 'Platinum';
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('goldStatusDetect'))}]")->length > 0) {
            $status = 'Gold';
        }

        if (!empty($status)) {
            switch ($this->providerCode) {
                case 'hotels':
                case 'expedia':
                    $st->addProperty('Status', $status);

                break;

                case 'homeaway':
                    $st->addProperty('Tier', $status);

                break;
            }
        }

        $tripToNextStatus = $this->http->FindSingleNode("//text()[{$this->contains($this->t('trip elements collected to reach'))}]",
            null, true, "/^\s*(\d+)\s+of\s+\d+\s+{$this->opt($this->t('trip elements collected to reach'))}/");

        if (!empty($tripToNextStatus) || $tripToNextStatus === '0') {
            switch ($this->providerCode) {
                case 'hotels':
                    $st->addProperty('TripToNextStatus', $tripToNextStatus);

                    break;

                case 'expedia':
                    $st->addProperty('TripsToTheNextTier', $tripToNextStatus);

                    break;
            }
        }
        $resetDate = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your trip elements reset to 0 on'))}]",
            null, true, "/^\s*{$this->opt($this->t('Your trip elements reset to 0 on'))}\s*(.+?)\./");
        $resetDate = strtotime(preg_replace("/^\s*31\/12\/(\d{4})\s*$/", '12/31/$1', $resetDate));

        if (!empty($resetDate)) {
            switch ($this->providerCode) {
                case 'hotels':
                    $st->addProperty('TripResetDate', $resetDate);

                    break;
            }
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

    public static function getEmailProviders()
    {
        return array_keys(self::$detectProviders);
    }

    private function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["Your latest One Key™ update"])
                && $this->http->XPath->query("//*[{$this->eq($dict['Your latest One Key™ update'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    private function assignProvider($headers): bool
    {
        foreach (self::$detectProviders as $providerCode => $detects) {
            if (!empty($detects['from'])) {
                foreach ($detects['from'] as $dFrom) {
                    if (strpos($headers['from'], $dFrom) !== false) {
                        $this->providerCode = $providerCode;

                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
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
