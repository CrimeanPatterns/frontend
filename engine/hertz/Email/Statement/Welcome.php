<?php

namespace AwardWallet\Engine\hertz\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class Welcome extends \TAccountChecker
{
	public $mailFiles = "hertz/statements/it-897936503.eml, hertz/statements/it-901525512.eml, hertz/statements/it-902581362.eml, hertz/statements/it-902629711.eml, hertz/statements/it-904710991.eml, hertz/statements/it-904733945.eml, hertz/statements/it-904924280.eml, hertz/statements/it-904941943.eml, hertz/statements/it-908211981.eml, hertz/statements/it-910091571.eml, hertz/statements/it-910417613.eml, hertz/statements/it-910622707.eml";
    public $lang = '';

    public $subjects = [
        // en
        'welcome to Hertz Gold Plus Rewards',
        'Welcome to Hertz Gold Plus Rewards,',
        "we've updated your Hertz Gold Plus Rewards® Account",
        'Car shopping? Unlock savings as a Gold Plus Rewards member',
        'status is yours through',
        'You’re upgraded to President’s Circle status',
        'Welcome to President’s Circle status',
        "Congrats! Welcome to Hertz President's Circle® status",
        "Members-only rates just for you",
        "Gift your status to a",
        "congratulations on your",
        "status is yours",
        "You’re upgraded to",
        // it
        'ti diamo il benvenuto su Hertz Gold Plus Rewards',
        // fr
        'bienvenue chez Hertz Gold Plus Rewards',
        // es
        'te damos la bienvenida a Hertz Gold Plus Rewards',
        // de
        'willkommen bei Hertz Gold Plus Rewards',
    ];
    public static $dictionary = [
        'en' => [
            'Member number:'                    => ['Member number:', 'Member Number:', 'MEMBER NUMBER:', 'Member Number :', 'MEMBER NUMBER :'],
            'Loyalty Tier:'                     => ['Loyalty Tier:', 'LOYALTY TIER:'],
            'Welcome to Gold Plus Rewards!'     => ['Welcome to Gold Plus Rewards!', 'Welcome to Hertz Gold Plus Rewards',
                'We just wanted to formally welcome you to our loyalty program', 'Hertz Gold Plus Rewards member',
                "We've updated your Hertz Gold Plus Rewards® Account", 'Celebrate your status in',
                'Let the Gold times roll.', 'Welcome to President’s Circle® status',
                'Welcome to President’s Circle® status.', 'You’re upgraded to President’s Circle® status',
                'Free perks. Big rewards. Better than ever.', 'Give the gift of',
                'You took the fast track.', 'Take a moment and explore your benefits.',
                'You’re upgraded to Hertz Five Star® status', 'Fast track to',
                'Congratulations! Welcome to Hertz President’s Circle® status.', "Welcome to Hertz President's Circle® tier status"],
            'Welcome to Hertz Gold Plus Rewards' => ['Welcome to Hertz Gold Plus Rewards', 'You’re upgraded to President’s Circle status']
        ],
        'it' => [
            'Member number:'                    => ['Numero Socio:'],
            'welcome to Hertz Gold Plus Rewards'=> ['ti diamo il benvenuto su Hertz Gold Plus Rewards'],
            'Welcome to Gold Plus Rewards!'     => ["Benvenuto nel programma Gold Plus Rewards"],
        ],
        'fr' => [
            'Member number:'                    => ['Numéro de membre:'],
            'welcome to Hertz Gold Plus Rewards'=> ['bienvenue chez Hertz Gold Plus Rewards'],
            'Welcome to Gold Plus Rewards!'     => ["Bienvenue à Gold Plus Rewards"],
        ],
        'es' => [
            'Member number:'                    => ['Número de socio:'],
            'welcome to Hertz Gold Plus Rewards'=> ['te damos la bienvenida a Hertz Gold Plus Rewards'],
            'Welcome to Gold Plus Rewards!'     => ["¡Bienvenido a Gold Plus Rewards!"],
        ],
        'de' => [
            'Member number:'                    => ['Mitgliedsnummer:'],
            'welcome to Hertz Gold Plus Rewards'=> ['willkommen bei Hertz Gold Plus Rewards'],
            'Welcome to Gold Plus Rewards!'     => ["Willkommen beim den Gold Plus Rewards."],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@emails.hertz.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) !== true) {
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
        if ($this->detectEmailFromProvider($parser->getHeader('from')) !== true
            && $this->http->XPath->query('//a[contains(@href,"emails.hertz.com/") or contains(@href,"emails.hertz.com%2F")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"The Hertz Corporation")]')->length === 0
        ) {
            return false;
        }

        return $this->assignLang();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $email->setType('Welcome' . ucfirst($this->lang));

        $st = $email->add()->statement();

        $patterns['travellerName'] = '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]';

        if (preg_match("/^(?:.?\s|Fw:|Fwd:)?[ ]*(?<name>\b{$patterns['travellerName']})\s*\,\s*{$this->opt($this->t('welcome to Hertz Gold Plus Rewards'))}\!?$/u", $parser->getSubject(), $m)
            || preg_match("/^(?:Fw:|Fwd:)?[ ]*{$this->opt($this->t('Welcome to Hertz Gold Plus Rewards'))}\s*\,\s*(?<name>\b{$patterns['travellerName']})$/u", $parser->getSubject(), $m)
        ) {
            $st->addProperty('Name', $m['name']);
        } else {
            $name = $this->http->FindSingleNode("//tr[./descendant::text()[{$this->eq($this->t('Loyalty Tier:'))}] and ./descendant::text()[{$this->eq($this->t('Member number:'))}] and count(./descendant::table) = 2]/preceding-sibling::tr[1]/descendant::td[normalize-space()][1]",null, true, "/^({$patterns['travellerName']})$/u");

            if ($name === null){
                $name = $this->http->FindSingleNode("//tr[count(./child::td) = 2 and ./descendant::td[{$this->starts($this->t('Member #:'))}]]/descendant::td[normalize-space()][1]",null, true, "/^({$patterns['travellerName']})$/u");
            }

            if ($name !== null){
                $st->addProperty('Name', $name);
            }
        }

        $tier = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Loyalty Tier:'))}]/following::text()[normalize-space()][1]",null, true, "/^([\s\S]+)$/");

        if ($tier !== null){
            $st->addProperty("Status", $tier);
        }

        $account = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Member number:'))}]/following::text()[normalize-space()][1])[1]",null, true, "/^\s*(\d[,.\d ]*)\s*$/");

        $st->setLogin($account)
            ->setNumber($account)
            ->setNoBalance(true);

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

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    public function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $phrases) {
            if (empty($phrases['Welcome to Gold Plus Rewards!'])) {
                continue;
            }

            if ($this->http->XPath->query("//*[{$this->contains($phrases['Welcome to Gold Plus Rewards!'])}]")->length > 0
            ) {
                $this->lang = $lang;

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

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

                return 'normalize-space(' . $node . ')=' . $s;
            }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
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
