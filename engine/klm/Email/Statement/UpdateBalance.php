<?php

namespace AwardWallet\Engine\klm\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class UpdateBalance extends \TAccountChecker
{
    public $mailFiles = "klm/it-907631834.eml, klm/it-914018585.eml, klm/it-914134427.eml, klm/it-914186531.eml, klm/statements/it-904675536.eml, klm/statements/it-905241241.eml, klm/statements/it-905327662.eml, klm/statements/it-905365949.eml, klm/statements/it-907618698.eml, klm/statements/it-913175219.eml, klm/statements/it-914107902.eml, klm/statements/it-915443273.eml, klm/statements/it-921912053.eml, klm/statements/it-921939744.eml";
    public $subjects = [
        'Your purchase has been paid for',
        'Your missing Miles & XP have been credited to your account',
        'Click to continue your journey with us',
        'Your missing Miles have been credited to your account',
        //nl
        'Uw ontbrekende Miles & XP zijn bijgeschreven',
        'Uw aankoop is betaald',
        //fr
        'Nous avons crédité vos Miles & XP manquants sur votre compte',
        'Votre achat a été réglé',
        'Des Miles ont été ajoutés à votre Famille Flying Blue',
        //ro
        'Plata pentru achiziția ta a fost efectuată',
        //ru
        'На Ваш счет начислены недостающие Мили и XP',
        //it
        'Ha ricevuto le Miglia e gli XP mancanti',
        //zh
        '您的消費已付款',
        //de
        'Die fehlenden Meilen und XP wurden gutgeschrieben',
    ];

    public $lang = '';

    public $detectLang = [
        'en' => ['Your Flying Blue team'],
        'nl' => ['Uw Flying Blue-team', 'Uw Flying Blue team'],
        'fr' => ['Votre équipe Flying Blue', 'Flying Blue est le programme de fidélité de'],
        'ro' => ['Echipa Flying Blue'],
        'ru' => ['Команда Flying Blue'],
        'it' => ['Il suo Team Flying Blue'],
        'zh' => ['您的藍天飛行團隊', '您的蓝天飞行团队'],
        'de' => ['Ihr Flying Blue-Team'],
        'es' => ['Flying Blue ha sido creado por:'],
    ];

    public static $dictionary = [
        "en" => [
            'Your Miles balance has been updated' => [
                'Your Miles balance has been updated',
                'News about your Miles & XP counter',
                'News about your Miles counter', ],

            'Total number of Miles added'         => ['Total number of Miles added', 'We have updated your Miles counter on'],
            'Total number of XP added'            => ['Total number of XP added', 'Total number of Miles added'],
        ],
        "nl" => [
            'Your Flying Blue team' => ['Uw Flying Blue-team', 'Uw Flying Blue team'],

            'Your Miles balance has been updated' => ['Het laatste nieuws over uw Miles & XP-teller', 'Uw Miles-teller is bijgewerkt', 'Het laatste nieuws over uw Miles-teller'],
            'Total number of Miles added'         => ['Totaal aantal Miles toegevoegd', 'hebben we uw Miles-teller bijgewerkt'],
            'Total number of XP added'            => ['Totaal aantal XP toegevoegd', 'Totaal aantal Miles toegevoegd'],
            'Overview'                            => 'Overzicht',

            'You needed'                      => 'Voor uw aankoop op',
            'Miles for your purchase made on' => 'Miles nodig',
            'Go to your account'              => 'Ga naar uw account',
        ],

        "fr" => [
            'Your Flying Blue team' => ['Votre équipe Flying Blue', 'Flying Blue est le programme de fidélité de'],

            'Your Miles balance has been updated' => ['Le point sur votre compte Flying Blue', 'Votre solde de Miles a été mis à jour', 'Partager des Miles permet d\'en profiter plus rapidement'],
            'Total number of Miles added'         => 'Nombre total de Miles crédités',
            'Total number of XP added'            => 'Nombre total de XP crédités',
            'Overview'                            => 'Le détail',

            'You needed'                      => ['Vous aviez besoin de', 'Où irez-vous avec tous ces Miles'],
            'Miles for your purchase made on' => ['Miles pour votre achat effectué le', 'Miles ont été transférés avec succès depuis le compte'],
            'Go to your account'              => ['Accédez à votre compte', 'Consulter votre profil'],
        ],

        "ro" => [
            'Your Flying Blue team' => 'Echipa Flying Blue',

            'Your Miles balance has been updated' => ['Soldul tău de Mile a fost actualizat'],
            /*'Total number of Miles added'         => '',
            'Total number of XP added'            => '',
            'Overview'                            => '',*/

            'You needed'                      => 'Ai avut nevoie de',
            'Miles for your purchase made on' => 'Mile pentru achiziția realizată la',
            'Go to your account'              => 'Conectează-te la contul tău',
        ],

        "ru" => [
            'Your Flying Blue team' => 'Команда Flying Blue',

            'Your Miles balance has been updated' => ['Что нового в Вашем счетчике Миль и XP'],
            'Total number of Miles added'         => 'Общая сумма добавленных Миль',
            'Total number of XP added'            => 'Общая сумма добавленных XP',
            'Overview'                            => 'Обзор',

            /*'You needed'                      => '',
            'Miles for your purchase made on' => '',
            'Go to your account'              => '',*/
        ],
        "it" => [
            'Your Flying Blue team' => 'Il suo Team Flying Blue',

            'Your Miles balance has been updated' => ['Novità sul suo contatore di Miglia e XP', 'Il suo saldo delle Miglia è stato aggiornato'],
            'Total number of Miles added'         => 'Numero totale di Miglia aggiunte',
            'Total number of XP added'            => 'Numero totale di XP aggiunti',
            'Overview'                            => 'Sintesi',

            'You needed'                      => 'Le sono servite',
            'Miles for your purchase made on' => 'Miglia per il suo acquisto effettuato in',
            'Go to your account'              => 'Acceda all’account',
        ],
        "zh" => [
            'Your Flying Blue team' => ['您的藍天飛行團隊', '您的蓝天飞行团队'],

            'Your Miles balance has been updated' => ['您的里數餘額已更新', '您的里数余额已更新', '关于您里数和 XP 计量表的最新消息'],
            'Total number of Miles added'         => '里数增加总额',
            'Total number of XP added'            => 'XP 增加总额',
            'Overview'                            => '概览',

            'You needed'                      => ['您有一筆', '您有一笔'],
            'Miles for your purchase made on' => ['里數的消費，發生於', '里数的消费，发生于'],
            'Go to your account'              => ['前往您的帳戶', '前往您的账户'],
        ],
        "de" => [
            'Your Flying Blue team' => 'Ihr Flying Blue-Team',

            'Your Miles balance has been updated' => 'Aktuelle Infos zu Ihrem Meilen- und XP-Zähler',
            'Total number of Miles added'         => 'Gesamtzahl der hinzugefügten Meilen',
            'Total number of XP added'            => 'Gesamtzahl der hinzugefügten XP',
            'Overview'                            => 'Übersicht',

            /*'You needed'                      => '',
            'Miles for your purchase made on' => '',
            'Go to your account'              => '',*/
        ],
        "es" => [
            'Your Flying Blue team' => 'Flying Blue ha sido creado por:',

            'Your Miles balance has been updated' => 'Su saldo de Millas ha sido actualizado',
            'Total number of Miles added'         => 'Gesamtzahl der hinzugefügten Meilen',
            'Total number of XP added'            => 'Gesamtzahl der hinzugefügten XP',
            'Overview'                            => 'Übersicht',

            'You needed'                      => 'Usted necesitaba',
            'Miles for your purchase made on' => 'Millas para su compra efectuada el',
            'Go to your account'              => 'Ir a su cuenta',
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

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your Flying Blue team'))}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your Miles balance has been updated'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('You needed'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Miles for your purchase made on'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Go to your account'))}]")->length > 0) {
            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your Miles balance has been updated'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Total number of Miles added'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Total number of XP added'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Overview'))}]")->length > 0) {
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

            $accountInfo = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Your Miles balance has been updated'))}]/following::text()[contains(translate(normalize-space(.), '0123456789', 'dddddddddd'), 'dddddddddd')][1]/ancestor::table[1]/descendant::text()[normalize-space()]"));

            if (empty($accountInfo)) {
                $accountInfo = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Your Miles balance has been updated'))}]/following::text()[contains(translate(normalize-space(.), '0123456789', 'dddddddddd'), 'XXXXXXdddd')][1]/ancestor::table[1]/descendant::text()[normalize-space()]"));
            }

            if (empty($accountInfo)) {
                $accountInfo = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Your Miles balance has been updated'))}]/preceding::text()[contains(translate(normalize-space(.), '0123456789', 'dddddddddd'), 'dddddddddd')][1]/ancestor::table[1]/descendant::text()[normalize-space()]"));
            }

            if (empty($accountInfo)) {
                $accountInfo = implode("\n", $this->http->FindNodes("//text()[{$this->contains($this->t('Your Miles balance has been updated'))}]/preceding::text()[contains(translate(normalize-space(.), '0123456789', 'dddddddddd'), 'dddd')][1]/ancestor::table[1]/descendant::text()[normalize-space()]"));
            }

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
