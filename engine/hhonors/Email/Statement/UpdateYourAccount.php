<?php

namespace AwardWallet\Engine\hhonors\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

// TODO: merge with parsers hhonors/Statement/SummerStatement

class UpdateYourAccount extends \TAccountChecker
{
    public $mailFiles = "hhonors/statements/it-63616485.eml, hhonors/statements/it-77519855.eml, hhonors/statements/it-79399968.eml, hhonors/statements/it-886767301.eml, hhonors/statements/it-886829253.eml, hhonors/statements/it-886834646.eml";

    public static $dictionary = [
        "en" => [
            'You have successfully updated your' => [
                'You have successfully updated your',
                'your Hilton Honors number to get started:',
                'Base Points referenced above are calculated off',
                'Your Travel Flexibility',
                'Thanks for joining Hilton Honors',
                'Important information regarding your',
                'You’ve successfully updated your business phone for',
            ],

            'Hilton Honors account' => [
                'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],

            'Here\'s your Hilton Honors number to get started:' => [
                'Here\'s your Hilton Honors number to get started:',
                'Hilton Honors Account Number',
                'Your Hilton Honors Account Number is',
                'As a reminder, your Hilton Honors account number is',
            ],

            'Dear' => ['Dear', 'Hello', 'Hi '],

            'This email was delivered to' => ['This email was delivered to', 'This email advertisement was delivered to'],
        ],
        "nl" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'Hilton Honors-accountnummer is',
            ],

            'Dear' => ['Hallo '],

            'This email was delivered to' => ['Deze e-mail is gestuurd naar'],
        ],
        "zh" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                '您的希尔顿荣誉客会账户编号为',
            ],

            'Dear' => ['您好 '],

            'This email was delivered to' => ['此电子邮件已发送至'],
        ],
        "ru" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'Номер вашей учетной записи в программе Hilton Honors:',
            ],

            'Dear' => ['Здравствуйте,'],

            'This email was delivered to' => ['Данное электронное письмо было отправлено по адресу'],
        ],
        "ar" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'رقم حساب برنامج هيلتون أونورز الخاص بك هو',
            ],

            'Dear' => ['مرحبًا'],

            'This email was delivered to' => ['تم إرسال هذا البريد الإلكتروني إلى'],
        ],
        "de" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'Die Nummer Ihres Hilton Honors Mitgliedskontos ist',
            ],

            'Dear' => ['Hallo '],

            'This email was delivered to' => ['Diese E-Mail wurde an'],
        ],
        "es" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'Su número de cuenta de Hilton Honors es',
            ],

            'Dear' => ['Hola '],

            'This email was delivered to' => ['Este correo electrónico fue enviado a'],
        ],
        "fr" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'Votre numéro de compte Hilton Honors est',
            ],

            'Dear' => ['Bonjour '],

            'This email was delivered to' => ['Ce courriel a été envoyé à'],
        ],
        "pt" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'O número da sua conta Hilton Honors é',
            ],

            'Dear' => ['Olá '],

            'This email was delivered to' => ['Este e-mail foi enviado a'],
        ],
        "it" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'Il numero del tuo conto Honors è',
            ],

            'Dear' => ['Ciao '],

            'This email was delivered to' => ['Questa e-mail è stata recapitata a'],
        ],
        "sv" => [
            // 'You have successfully updated your' => [
            //     'You have successfully updated your',
            //     'your Hilton Honors number to get started:',
            //     'Base Points referenced above are calculated off',
            //     'Your Travel Flexibility',
            //     'Thanks for joining Hilton Honors',
            //     'Important information regarding your',
            //     'You’ve successfully updated your business phone for',
            // ],

            // 'Hilton Honors account' => [
            //     'Hilton Honors account', 'Hilton Honors™ membership', 'Your Hilton Honors Status and Points', ],
            //
            'Here\'s your Hilton Honors number to get started:' => [
                'Ditt Hilton Honors-kontonummer är',
            ],

            'Dear' => ['Hej,'],

            'This email was delivered to' => ['Detta e-postmeddelande skickades till'],
        ],
    ];

    public $lang = 'en';

    private $patterns = [
        'boundary' => '(?:[&"%\s]|$)',
    ];

    private $subjects = [
        'en' => [
            'you have successfully updated your phone number on your Hilton',
            'you have successfully updated your address on your Hilton',
            'you have successfully updated your email address on your Hilton',
            'Welcome to Hilton Honors',
            'Welcome to Hilton for Business',
            'you have successfully added your credit card to your Hilton',
            'you have successfully updated your business phone in Hilton',
            'here is the Hilton Honors account information you requested',
            'Your gift of Hilton Honors Points is confirmed',
            'Someone just sent you Hilton Honors Points',
        ],
        'nl' => [
            'Welkom bij Hilton Honors',
        ],
        'zh' => [
            '欢迎加入希尔顿荣誉客会',
        ],
        'ru' => [
            'приветствуем вас в Hilton Honors',
        ],
        'ar' => [
            'مرحبًا بكم في هيلتون إتش أونورز',
        ],
        'de' => [
            'Willkommen bei Hilton Honors',
        ],
        'es' => [
            'Bienvenido a Hilton Honors',
        ],
        'fr' => [
            'Bienvenue à Hilton Honors',
        ],
        'pt' => [
            'Bem-vindo ao Hilton Honors',
        ],
        'it' => [
            'Benvenuti in Hilton Honors',
        ],
        'sv' => [
            'Välkommen till Hilton Honors',
        ],
    ];

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['This email was delivered to']) && $this->http->XPath->query("//text()[{$this->contains($dict['This email was delivered to'])}]")->length > 0) {
                $this->lang = $lang;

                break;
            }
        }

        $st = $email->add()->statement();

        $st->setNoBalance(true);

        $name = $this->http->FindSingleNode("//text()[{$this->contains($this->t('You have successfully updated your'))}]", null, true, "/^{$this->opt($this->t('Hi'))}\s*(\w+)\,/s");

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'You have successfully updated your')]/preceding::text()[starts-with(normalize-space(), 'Hi')][1]", null, true, "/^{$this->opt($this->t('Hi'))}\s*(\w+)\,/");
        }

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]", null, true, "/^{$this->opt($this->t('Dear'))}\s*([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])[\,،]/u");
        }

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Thanks for joining Hilton Honors,')]", null, true, "/^{$this->opt($this->t('Thanks for joining Hilton Honors,'))}\s*(\w+)\./u");
        }

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//img[contains(@alt, 'Welcome to Hilton for')]/@alt", null, true, "/{$this->opt($this->t('Dear'))}\,?\s*(\w+)\./");
        }

        if (!empty($name)) {
            $st->addProperty('Name', $name);
        }

        $userEmail = $this->http->FindSingleNode("//text()[{$this->starts($this->t('This email was delivered to'))}]/following::a[contains(normalize-space(), '@')][1]");

        if ($this->lang === 'ar' && empty($userEmail)) {
            $userEmail = $this->http->FindSingleNode("//text()[{$this->starts($this->t('This email was delivered to'))}]/preceding::a[contains(normalize-space(), '@')][1]");
        }
        $email->setUserEmail($userEmail);

        $rawData = implode(' ', $this->http->FindNodes("//a[normalize-space(@href)]/@href | //img[normalize-space(@src)]/@src"));
        $rawData = preg_replace("/[-_A-z\d]*interaction_point=\D.*?{$this->patterns['boundary']}/i", '', $rawData);

        if (preg_match_all("/hh_num=(\d+){$this->patterns['boundary']}/u", $rawData, $m)
            && count(array_unique($m[1])) === 1
        ) {
            $st->setNumber($m[1][0]);
        }

        $number = $this->http->FindSingleNode('//text()[' . $this->starts($this->t('Here\'s your Hilton Honors number to get started:')) . ']/following::text()[normalize-space()][1]', null, true, "/[#]?(\d+)$/");

        if (empty($number)) {
            $number = $this->http->FindSingleNode('//text()[' . $this->starts($this->t('Here\'s your Hilton Honors number to get started:')) . ']', null, true,
                "/{$this->opt($this->t('Here\'s your Hilton Honors number to get started:'))}\s*[#]?(\d+)(?:$|\.)/");
        }

        if (empty($number)) {
            $number = $this->http->FindSingleNode("//img[contains(@alt, 'Sign In')]/@alt", null, true, "/[#](\d{5,})\./");
        }

        if (!empty($number)) {
            $st->setNumber($number);
        }

        return $email;
    }

    public function detectEmailByHeaders(array $headers)
    {
        foreach ($this->subjects as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (is_string($phrase) && stripos($headers['subject'], $phrase) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.](?:h4|h6)\.hilton\.com/i', $from) > 0;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[{$this->starts($this->t('Hilton Reservations and Customer Care'))}]")->length === 0
            && $this->http->XPath->query("//img[contains(@alt, 'Hilton Honors')]")->length === 0) {
            return false;
        }

        //it-886834646.eml
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Here\'s your Hilton Honors number to get started:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Your next steps'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('EXPERT TIP #'))}]")->length > 0) {
            return true;
        }

        //it-886829253.eml
        if ($this->http->XPath->query("//img[contains(@alt, 'Hilton For')]")->length > 0
            && $this->http->XPath->query("//img[contains(@alt, 'Welcome to Hilton for')]")->length > 0
            && $this->http->XPath->query("//img[contains(@alt, 'Hilton FOR THE STAY')]")->length > 0) {
            return true;
        }

        //it-886767301.eml
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Here\'s your Hilton Honors number to get started:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('book your next trip'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Dear'))}]")->length > 0) {
            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Hilton Honors account'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('You have successfully updated your'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('This email was delivered to'))}]")->length > 0
            && $this->http->XPath->query("//img[contains(@src, 'hh_num=') and contains(@src, 'mi_name=')]/@src")->length == 0
            && $this->http->XPath->query("//img[contains(@src, 'mi_tier=')]/@src")->length == 0) {
            return true;
        }

        return false;
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

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }
}
