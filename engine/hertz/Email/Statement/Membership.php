<?php

namespace AwardWallet\Engine\hertz\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class Membership extends \TAccountChecker
{
    public $mailFiles = "hertz/statements/it-904401756.eml, hertz/statements/it-905617990.eml, hertz/statements/it-906569365.eml, hertz/statements/it-906854285.eml";
    public $lang = '';

    public $subjects = [
        // en
        'Important updates to Hertz Gold Plus Rewards® program',
        'Congratulations! Welcome to Hertz Five Star® status.',
        'Congratulations! Welcome to Hertz President’s Circle® status.',
        'congratulations on your President’s Circle® status!',
        'congratulations on your Hertz Five Star status!',
        'How to maintain your',
        'Update on your loyalty program status.',
        // pt
        'Atualizações importantes do programa Hertz Gold Plus Rewards®',
        // es
        'Actualizaciones importantes del programa Hertz Gold Plus Rewards®',
    ];
    public static $dictionary = [
        'en' => [
            'detectPhrase'      => ['Updates to Hertz Gold Plus Rewards®', 'Your Hertz Gold Plus Rewards® tier status.',
                'Thanks for choosing Hertz', 'Thank you for being a valued member of our loyalty program, and for choosing Hertz'],
            'detectPhrase2'     => ['Thank you for being a valued Hertz Gold Plus Rewards member', 'you’re now receiving elite benefits with Hertz Gold Plus Rewards',
                'You are now upgraded to', 'We hope you’ve enjoyed being a', 'We hope you’ve enjoyed'],
            'Hey'               => ['Hi', 'Hey'],
        ],
        'pt' => [
            'detectPhrase'      => ['Atualizações do Hertz Gold Plus Rewards®'],
            'detectPhrase2'     => ['Obrigado por ser um valioso membro do Hertz Gold Plus Rewards.'],
        ],
        'es' => [
            'detectPhrase'      => ['No esperes para usar tus puntos en aventuras emocionantes'],
            'detectPhrase2'     => ['Gracias por ser un valioso miembro de Hertz Gold Plus Rewards'],
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

        $email->setType('Membership' . ucfirst($this->lang));

        if ($this->http->XPath->query("//*[{$this->contains($this->t('detectPhrase'))}]")->length > 0
            && $this->http->XPath->query("//*[{$this->contains($this->t('detectPhrase2'))}]")->length > 0
            && $this->detectEmailFromProvider($parser->getHeader('from')) === true
        ) {
            $st = $email->add()->statement();

            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hey'))}]", null, false, "/^{$this->opt($this->t('Hey'))}[ ]*\,[ ]*(.+)[\:\,\.\!]$/");

            if ($name !== null){
                $st->addProperty('Name', $name);
            }
            
            $status = $this->http->FindSingleNode("//text()[{$this->contains($this->t('you’re now receiving elite benefits with Hertz Gold Plus Rewards'))}]/ancestor::td[normalize-space()][1]", null, false, "/{$this->opt($this->t('you’re now receiving elite benefits with Hertz Gold Plus Rewards'))}[ ]*\®[ ]*(.+)\./");

            if ($status === null){
                $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('You are now upgraded to'))}]", null, false, "/^{$this->opt($this->t('You are now upgraded to'))}[ ]*(.+)$/");
            }

            if ($status === null){
                $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We hope you’ve enjoyed being a'))}]", null, false, "/^{$this->opt($this->t('We hope you’ve enjoyed being a'))}[ ]*(.+)[ ]*{$this->opt($this->t('member in'))}/");
            }

            if ($status === null){
                $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We hope you’ve enjoyed'))}]", null, false, "/^{$this->opt($this->t('We hope you’ve enjoyed'))}[ ]*(.+)$/");
            }

            if ($status !== null){
                $st->addProperty('Status', $status)->setNoBalance(true);
            } else {
                $st->setMembership(true)->setNoBalance(true);
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
            if (empty($phrases['detectPhrase'])) {
                continue;
            }

            if ($this->http->XPath->query("//*[{$this->contains($phrases['detectPhrase'])}]")->length > 0
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
