<?php

namespace AwardWallet\Engine\klm\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FlyingBlueFamily extends \TAccountChecker
{
    public $mailFiles = "klm/it-919273290.eml, klm/statements/it-914316042.eml, klm/statements/it-914647833.eml, klm/statements/it-916429394.eml, klm/statements/it-923943044.eml";
    public $subjects = [
        //en
        'Miles were transferred to your Flying Blue Family',
        'You are invited to join a Flying Blue Family!',
        'Choose your future Flying Blue Family',
    ];

    public $lang = '';

    public $detectLang = [
        'nl' => ['Flying Blue is het loyaliteitsprogramma van'],
        'it' => ['Flying Blue è il programma fedeltà di'],
        'es' => ['Flying Blue es el programa de fidelidad de'],
        'pt' => ['Flying Blue é o programa de fidelidade da'],
        'en' => ['Flying Blue is the loyalty programme of'],
    ];

    public static $dictionary = [
        "en" => [
            //'Flying Blue Service' => '',
            'Miles have been successfully transferred from'        => [
                'You are now a Flying Blue Family member after accepting an invitation',
                'Miles have been successfully transferred from',
                'You created a Flying Blue Family!',
                'Your Flying Blue Family is now officially closed',
                'We’re confirming that you’ve successfully declined to join the Flying Blue',
                'Good news: you have been invited by',
                'Your 18th birthday is coming soon and may bring changes to the Flying Blue Family you are part of',
                'You are no longer part of the Flying Blue Family of',
                'has recently turned 18 and left your Flying Blue Family',
            ],
            'Sharing Miles together means reaching rewards faster' => [
                'Sharing Miles together means reaching rewards faster',
                'Together, you and your other Family members can share and enjoy',
                'As leader, you choose who can join your Flying Blue Family',
                'You can create a new Flying Blue Family at any time',
                'As a reminder, Flying Blue Family is an easy way to share and enjoy Miles',
                'As leader of this Family',
                'If your Flying Blue Family already has',
                'To keep earning more Miles as a family',
                'To continue sharing more Miles and enjoying rewards even sooner',
            ],
            'Visit your profile' => ['Visit your profile', 'Manage your Family', 'Join your Family', 'Invite a new member'],
        ],

        "nl" => [
            //'Flying Blue Service' => '',
            'Miles have been successfully transferred from'        => [
                'Miles succesvol overgeboekt van het account van',
                'U heeft een Flying Blue Family aangemaakt',
                'Omdat je onlangs 18 bent geworden, maak je geen deel meer uit van de Flying Blue Family',
            ],
            'Sharing Miles together means reaching rewards faster' => [
                'Door Miles te delen, komen rewards sneller binnen handbereik',
                'Als manager bepaalt u wie er aan uw Flying Blue Family mag deelnemen',
                'Wil je samen met familie en vrienden Miles verzamelen en sneller van rewards genieten',
            ],
            'Visit your profile'                                   => ['Ga naar uw profiel', 'Beheer uw Family', 'Maak je eigen Family aan'],
        ],

        "it" => [
            'Flying Blue Service'                                  => 'Centro Servizi Flying Blue',
            'Miles have been successfully transferred from'        => 'Miglia sono state trasferite correttamente dall’account di',
            'Sharing Miles together means reaching rewards faster' => 'Condividendo le Miglia i premi arrivano più velocemente',
            'Visit your profile'                                   => 'Visiti il suo profilo',
        ],

        "es" => [
            'Flying Blue Service'                                  => 'cliente Flying Blue',
            'Miles have been successfully transferred from'        => 'Buenas noticias: ¡ha sido invitado por',
            'Sharing Miles together means reaching rewards faster' => '¡Formar parte de Flying Blue Familia significa compartir Millas juntos y disfrutar de los premios mucho antes',
            'Visit your profile'                                   => 'Únase a su Flying Blue Familia',
        ],

        "pt" => [
            'Flying Blue Service'                                  => 'Flying Blue é o programa de fidelidade da',
            'Miles have been successfully transferred from'        => 'Sua Família Flying Blue está oficialmente fechada agora',
            'Sharing Miles together means reaching rewards faster' => 'Você pode criar uma nova Família Flying Blue a qualquer momento',
            'Visit your profile'                                   => 'Visite o seu perfil',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@service-flyingblue.com') !== false) {
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
        $this->assignLang();

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Flying Blue Service'))}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Miles have been successfully transferred from'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Sharing Miles together means reaching rewards faster'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Visit your profile'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]service\-flyingblue\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if ($this->detectEmailByBody($parser) == true) {
            $st = $email->add()->statement();

            $accountInfo = implode("\n", $this->http->FindNodes("//text()[{$this->contains($this->t('Miles have been successfully transferred from'))}]/preceding::text()[normalize-space()][not(contains(normalize-space(), '!'))][1]/ancestor::table[1]/descendant::text()[normalize-space()]"));

            if (preg_match("/^(?<status>\D+)\n(?<pax>[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\n[X]*(?<account>\d{3,})$/", $accountInfo, $m)) {
                $st->setNumber($m['account']);

                $st->addProperty('Name', trim($m['pax'], ','))
                    ->addProperty('Status', trim($m['status'], ','));
            }

            $st->setNoBalance(true);
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

    public function assignLang()
    {
        foreach ($this->detectLang as $lang => $array) {
            foreach ($array as $word) {
                if ($this->http->XPath->query("//text()[{$this->contains($word)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
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

    private function eq($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'normalize-space(.)="' . $s . '"'; }, $field)) . ')';
    }
}
