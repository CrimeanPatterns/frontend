<?php

namespace AwardWallet\Engine\copaair\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class MemberNumber extends \TAccountChecker
{
    public $mailFiles = "copaair/statements/it-910984835.eml, copaair/statements/it-911213097.eml, copaair/statements/it-911476682.eml";

    public $detectSubjects = [
        'en' => [
            'You\'ve successfully redeemed your miles',
            'Your miles are about to expire',
            'Your miles have expired',
            'You have associated your account to a new email address',
            'We\'ve approved your request to credit miles',
        ],
        'es' => [
            'Redimiste tus millas con éxito',
            'Tus millas están próximas a expirar',
            'Tus millas han expirado',
            'ha recibido con éxito tu donación de millas',
            'Hemos reembolsado tus millas con éxito',
        ],
        'pt' => [
            'Suas milhas estão prestes a expirar',
            'Suas milhas expiraram',
            'Você resgatou suas milhas com sucesso',
            'Reembolsamos suas milhas com sucesso',
            'Foundation recebeu sua doação de milhas com sucesso',
        ],
    ];

    public $lang;

    public static $dictionary = [
        'en' => [
            'Member:'                => 'Member:',
            'Frequent Flyer Number:' => ['Frequent Flyer Number:', 'Frequent flyer number:'],
        ],
        'es' => [
            'Member:'                => 'Miembro:',
            'Frequent Flyer Number:' => 'Número de viajero frecuente:',
        ],
        'pt' => [
            'Member:'                => 'Membro:',
            'Frequent Flyer Number:' => ['Número de passageiro frequente:', 'Número de Viajante Frequente:'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]cns\.copaair\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers["from"], '@cns.copaair.com') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains(['Copa Airlines', 'ConnectMiles'])}]")->length > 0
            && $this->http->XPath->query("//img/@src[{$this->contains('copaair.com')}]")->length === 0
            && $this->http->XPath->query("//img/@alt[{$this->contains('Copa Logo')}]")->length === 0
        ) {
            return false;
        }

        $this->assignLang();

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Member:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Frequent Flyer Number:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Status'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('AWARD MILES'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('QUALIFYING MILES'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('QUALIFYING SEGMENTS'))}]")->length === 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Member:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/u");

        if (!empty($name)) {
            $st->addProperty('Name', $name);
        }

        $number = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Frequent Flyer Number:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d{8,})\s*$/");

        if (!empty($number)) {
            $st->addProperty('Number', $number);
        }

        $st->setNoBalance(true);

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

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Member:']) && !empty($dict['Frequent Flyer Number:'])
                && $this->http->XPath->query("//text()[{$this->eq($dict['Member:'])}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->eq($dict['Frequent Flyer Number:'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
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

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }
}
