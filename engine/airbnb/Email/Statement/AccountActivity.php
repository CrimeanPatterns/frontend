<?php

namespace AwardWallet\Engine\airbnb\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class AccountActivity extends \TAccountChecker
{
    public $mailFiles = "airbnb/statements/it-66040768.eml, airbnb/statements/it-66043280.eml, airbnb/statements/it-895637697.eml";

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];
    private $subjects = [
        'en' => ['Account activity:', 'Account alert:'],
    ];

    private $junkDetectors = [
        'en' => [
            'We\'ve removed your account from the Airbnb platform because of a background check record match',
            'Your Airbnb account was canceled',
            'We\'ve removed your account from the Airbnb platform',
            'Your Airbnb account was cancelled',
        ],
    ];

    private $detectors = [
        'nl' => [
            'gezien dat er een nieuwe betaalmethode aan je Airbnb-account is toegevoegd',
        ],
        'en' => [
            'tap the button below to confirm your account',
            'your Airbnb account was logged into from a new device',
            'new payment method was added to your Airbnb account',
            'password for your Airbnb account was recently changed',
            'following phone number was recently added to your account',
            'We noticed one or more photos for this listing were recently updated.',
            'We noticed the name on your Airbnb account was recently changed.',
            'We noticed the following payout method was recently updated on your Airbnb account',
            'We noticed the following phone number was recently removed from your account.',
            'We noticed the email address for your Airbnb account was recently changed.',
            'We noticed the following phone number was recently removed from your account.',
            'We noticed a change was made to your Airbnb listing description.',
            'If this was you, you don\'t need to do anything. If this wasn\'t you, please, secure your account now.',
            'We have noticed you were recently in contact with an account that has since been removed for violating our Terms of Service',
            'Your password was exposed during a data breach on another website',
            'We\'ve reviewed and unblocked your account. You should now be able to use it normally',
            'Your account needs to be reviewed',
            'We need some details to finish setting up your hosting account',
            'During a security review, we discovered your login information in another site\'s data breach',
            'We removed some reviews from your account that we determined don’t follow our',
            'Abbiamo notato che è stato aggiunto un nuovo metodo di pagamento al tuo account Airbnb',
            'We have been unable to collect the past due balance(s) on your Airbnb account for the following reservation(s)',
            'Did you update the photos for this listing?',
            'Please know that this is our second and final attempt to contact you regarding the verification of your account',
            'We were notified by our payment processor that your bank account information has changed',
            'We noticed you were recently in contact with an account that has since been removed for violating our',
            'We’re reaching out because you were recently in contact with an account that was removed for violating the Airbnb Terms of Service',
            'If this was you, you don\'t need to do anything. If this wasn\'t you, please, secure your account now',
            'We see that you have started your account verification process',
            'We noticed the birthdate on your Airbnb account was recently changed',
            'A payment method was added to your account',
            'Your password was updated',
            'Ti basta toccare il pulsante di seguito per confermare il tuo account e ricevere assistenza più velocemente',
            'Your personal data file is ready to download',
            'We noticed the birthdate on your Airbnb account was recently changed',
            'We’re following up on our previous email about your account verification',
            'Deactivate your account?',
        ],
        'th' => [
            'เราสังเกตเห็นว่าเพิ่งมีการอัพเดทวิธีชำระเงินต่อไปนี้ในบัญชี Airbnb ของคุณ',
            'เราพบว่ามีการเพิ่มวิธีชำระเงินใหม่ในบัญชี Airbnb ของคุณ',
            'เราพบว่ามีการเพิ่มเบอร์โทรต่อไปนี้ในบัญชีผู้ใช้ของคุณเมื่อเร็วๆ',
        ],
        'it' => [
            'Abbiamo notato che è stato effettuato un accesso al tuo account Airbnb da un nuovo dispositivo',
            'Hai aggiunto questo numero di telefono al tuo account?',
            'Hai rimosso tu questo numero di telefono dal tuo account?',
            'Abbiamo notato che di recente la password del tuo account Airbnb è stata modificata',
        ],
        'de' => [
            'Uns ist aufgefallen, dass das Passwort für dein Airbnb-Nutzerkonto kürzlich geändert wurde',
        ],
        'cs' => [
            'Všimli jsme si, že k tvému účtu na Airbnb byla přidána nová platební metoda',
            'Dein Account muss überprüft werden',
            'Dein Airbnb-Account wurde deaktiviert',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@airbnb.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers['subject'], 'Confirm your Airbnb account') !== false) {
            return true;
        }

        if (self::detectEmailFromProvider($headers['from']) !== true && strpos($headers['subject'], 'Airbnb') === false) {
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
        if (self::detectEmailFromProvider($parser->getHeader('from')) !== true
            && $this->http->XPath->query('//a[contains(@href,".airbnb.com/") or contains(@href,"www.airbnb.com")]')->length === 0
            && $this->http->XPath->query('//node()[contains(normalize-space(),"Sent with ♥ from Airbnb")]')->length === 0
        ) {
            return false;
        }

        return $this->detectBody();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        foreach ($this->junkDetectors as $junkDetector) {
            if ($this->http->XPath->query("//text()[{$this->contains($junkDetector)}]")->length > 0) {
                $email->setIsJunk(true);

                return $email;
            }
        }

        $st = $email->add()->statement();

        if ($this->detectBody()) {
            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hi '))}]", null, true, "/^{$this->opt($this->t('Hi '))}\s*(\D+)\,$/");

            if (!empty($name) & stripos($name, 'first_name') === false) {
                $st->addProperty('Name', trim($name, ','));
                $st->setNoBalance(true);
            } else {
                $st->setMembership(true);
            }
        }

        return $email;
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function detectBody(): bool
    {
        if (!isset($this->detectors)) {
            return false;
        }

        foreach ($this->detectors as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (!is_string($phrase)) {
                    continue;
                }

                if ($this->http->XPath->query("//node()[{$this->contains($phrase)}]")->length > 0) {
                    return true;
                }
            }
        }

        foreach ($this->junkDetectors as $junkDetector) {
            if ($this->http->XPath->query("//node()[{$this->contains($junkDetector)}]")->length > 0) {
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
            return 'contains(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
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
