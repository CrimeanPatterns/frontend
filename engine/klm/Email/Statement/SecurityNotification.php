<?php

namespace AwardWallet\Engine\klm\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class SecurityNotification extends \TAccountChecker
{
    public $mailFiles = "klm/statements/it-903922458.eml, klm/statements/it-904443110.eml, klm/statements/it-904594249.eml, klm/statements/it-904606414.eml, klm/statements/it-904606624.eml, klm/statements/it-904641566.eml, klm/statements/it-904641567.eml, klm/statements/it-904681915.eml, klm/statements/it-909444781.eml, klm/statements/it-909783995.eml, klm/statements/it-911897019.eml, klm/statements/it-921915895.eml, klm/statements/it-923057937.eml, klm/statements/it-923196007.eml, klm/statements/it-923324266.eml, klm/statements/it-923372427.eml";
    public $subjects = [
        //en
        'Account security notification',
        'Set your Flying Blue password',
        'Your Flying Blue password has been changed',
        'Your email address has been updated',
        //fr
        'Votre mot de passe Flying Blue a été modifié',
        'Créez votre mot de passe Flying Blue',
        //zh
        '您的藍天飛行密碼已更改',
        //it
        'La password Flying Blue è stata modificata',
        //es
        'Establezca su contraseña de Flying Blue',
        'Su contraseña de Flying Blue ha sido cambiada',
        //nl
        'Beveiligingsmelding voor uw account',
        'Uw Flying Blue-wachtwoord is gewijzigd',
        //de
        'Ihr Flying Blue-Passwort wurde geändert',
        //ru
        'Уведомление о безопасности вашего аккаунта',
        //ko
        '계정 보안 관련 알림',
    ];

    public $lang = '';

    public $detectLang = [
        'en' => ['Your Flying Blue team', 'Your Flying Blue Team', 'Flying Blue account'],
        'es' => ['Su equipo Flying Blue'],
        'nl' => ['Uw Flying Blue-team'],
        'pt' => ['Sua Equipe Flying Blue'],
        'it' => ['Il suo Team Flying Blue', 'Il suo team Flying Blue'],
        'zh' => ['您的藍天飛行團隊', '您的蓝天飞行团队', '你的藍天飛行團隊'],
        'fr' => ['Votre équipe Flying Blue', 'Flying Blue est le programme de fidélité de'],
        'de' => ['Ihr Flying Blue-Team'],
        'ru' => ['Ваша Команда Flying Blue'],
        'ko' => ['플라잉 블루 팀 드림'],
    ];

    public static $dictionary = [
        "en" => [
            'We noticed an unusual activity on your account' => [
                'We noticed an unusual activity on your account',
                'We noticed an update in your security settings',
                'Confirmation of your recent password change',
                'Set your password with these simple steps',
                'Your e-mail address has been changed',
                'Lost and found',
                'Reactivate your account now',
            ],
            //'View online' => '',
            'Here are the details of the detected activity:' => [
                'Here are the details of the detected activity:',
                'Your password to log in to your Flying Blue',
                'Secure your Flying Blue account with a password',
                'The security settings in your profile have been updated.',
                'We have just updated your profile with the following e-mail address',
                'Your Flying Blue account is linked to the following user ID',
                'We noticed your Flying Blue account has been inactive for an extended period of time',
            ],
            'Log in'                => ['Log in', 'Reactivate now'],
            'Your Flying Blue team' => ['Your Flying Blue team', 'Your Flying Blue Team', 'Flying Blue account'],
        ],

        "es" => [
            'We noticed an unusual activity on your account' => [
                'Confirmación de su reciente cambio de contraseña',
                'Establezca su contraseña con estos simples pasos',
                'Confirmación de la eliminación de la cuenta',
                'Hemos identificado actividad inusual en su cuenta',
            ],

            'View online' => 'Ver online',

            'Here are the details of the detected activity:' => [
                'Su contraseña para iniciar sesión en su espacio Flying Blue',
                'Asegure su espacio Flying Blue con una contraseña.',
                'Le confirmamos su solicitud para eliminar su cuenta Flying Blue',
                'Aquí tiene los detalles de la actividad detectada',
            ],

            'Log in' => ['Conectarse', 'centro de atención al cliente'],

            'Your Flying Blue team' => ['Su equipo Flying Blue', 'Su equipo Flying Blue'],
        ],

        "nl" => [
            'We noticed an unusual activity on your account' => [
                'Wij hebben een ongebruikelijke actie opgemerkt op uw account',
                'Bevestiging van uw recente wachtwoordwijziging',
            ],

            'View online' => 'Online versie',

            'Here are the details of the detected activity:' => [
                'Dit zijn de gegevens van de gedetecteerde actie:',
                'Uw wachtwoord om in te loggen op uw Flying Blue-account',
            ],

            'Log in' => 'Inloggen',

            'Your Flying Blue team' => 'Uw Flying Blue-team',
        ],

        "pt" => [
            'We noticed an unusual activity on your account' => [
                'Confirmação da sua alteração recente de senha',
                'Defina a sua senha com estes passos simples',
            ],

            'View online' => 'Ver online',

            'Here are the details of the detected activity:' => [
                'A sua senha para o login em sua conta Flying Blue',
                'Proteja a sua conta Flying Blue com uma senha',
            ],

            'Log in' => 'Login',

            'Your Flying Blue team' => 'Sua Equipe Flying Blue',
        ],

        "it" => [
            'We noticed an unusual activity on your account' => [
                'Conferma di modifica recente della password',
                'Imposti la sua password seguendo questi semplici passaggi',
                'Abbiamo notato un’attività insolita sul suo account',
            ],

            'View online' => ['Ver online', 'Versione online'],

            'Here are the details of the detected activity:' => [
                'La sua password di accesso all’account Flying Blue',
                'Protegga il suo account Flying Blue con una password',
                'Ecco i dettagli dell’attività rilevata',
            ],

            'Log in' => ['Login', 'Acceda'],

            'Your Flying Blue team' => ['Il suo Team Flying Blue', 'Il suo team Flying Blue'],
        ],

        "zh" => [
            'We noticed an unusual activity on your account' => [
                '確認您最近的密碼更改',
                '透過以下簡單步驟設置您的密碼',
                '确认您最近的密码更改',
                '我們注意到您的帳戶有異常活動',
                '我们注意到您的账户存在异常活动',
            ],

            'View online' => ['線上查看', '在线查看'],

            'Here are the details of the detected activity:' => [
                '登入到藍天飛行帳號以及訪問您的個人資料和儀錶板的密碼已更改',
                '我們將向您發送此臨時密碼',
                '登录到蓝天飞行账号以及访问您的个人资料和控制面板的密码已更改',
                '以下是檢測到的活動詳情',
                '以下是检测到的活动的详细信息',
            ],

            'Log in' => ['登入', '登录'],

            'Your Flying Blue team' => ['您的藍天飛行團隊', '您的蓝天飞行团队', '你的藍天飛行團隊'],
        ],

        "fr" => [
            'We noticed an unusual activity on your account' => [
                'Confirmation de la modification récente de votre mot de passe',
                'Créez votre mot de passe en suivant ces quelques étapes',
                'Activité inhabituelle sur votre compte',
                'Réactivez votre compte',
            ],

            'View online' => 'Consulter en ligne',

            'Here are the details of the detected activity:' => [
                'Le mot de passe vous permettant de vous connecter à votre compte Flying Blue',
                'Sécurisez votre compte Flying Blue par un mot de passe',
                'Voici les détails de l’activité détectée',
                'Votre compte Flying Blue est inactif depuis une longue période',
            ],

            'Log in' => ['Se connecter', 'Réactivez votre compte'],

            'Your Flying Blue team' => ['Votre équipe Flying Blue', 'Flying Blue est le programme de fidélité de'],
        ],

        "de" => [
            'We noticed an unusual activity on your account' => [
                'Bestätigung der Passwortänderung',
                'Wir haben eine ungewöhnliche Kontoaktivität festgestellt',
            ],

            'View online' => ['Consulter en ligne', 'Online ansehen'],

            'Here are the details of the detected activity:' => [
                'Das Passwort, mit dem Sie sich in Ihr Flying Blue-Konto einloggen und Zugriff auf Ihr Profil und Ihr Dashboard erhalten, wurde geändert',
                'Detaillierte Informationen zu dieser Aktivität auf Ihrem Konto',
            ],

            'Log in' => ['Se connecter', 'Einloggen'],

            'Your Flying Blue team' => 'Ihr Flying Blue-Team',
        ],

        "ru" => [
            'We noticed an unusual activity on your account' => [
                'Мы заметили необычную активность в Вашей учетной записи',
            ],

            'View online' => 'Посмотреть на сайте',

            'Here are the details of the detected activity:' => [
                'Данные обнаруженной активности',
            ],

            'Log in' => 'Войти в систему',

            'Your Flying Blue team' => 'Ваша Команда Flying Blue',
        ],

        "ko" => [
            'We noticed an unusual activity on your account' => [
                '고객님의 계정에서 평소와 다른 활동이 감지되었습니다',
            ],

            'View online' => '온라인으로 보기',

            'Here are the details of the detected activity:' => [
                '감지된 활동의 세부 사항은 다음과 같습니다',
            ],

            'Log in' => '로그인',

            'Your Flying Blue team' => '플라잉 블루 팀 드림',
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

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('We noticed an unusual activity on your account'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Here are the details of the detected activity:'))}]")->length > 0
            /*&& $this->http->XPath->query("//text()[{$this->contains($this->t('Log in'))}]")->length > 0*/) {
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

            $name = $this->http->FindSingleNode("//text()[{$this->eq($this->t('We noticed an unusual activity on your account'))}]/preceding::text()[normalize-space()][1][not({$this->contains($this->t('View online'))})]", null, true, "/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])$/");

            if (!empty($name)) {
                $st->addProperty('Name', trim($name, ','));
            }

            if (empty($name)) {
                $accountInfo = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('We noticed an unusual activity on your account'))}]/preceding::text()[contains(translate(normalize-space(.), '0123456789', 'dddddddddd'), 'dddd')][1]/ancestor::table[1]/descendant::text()[normalize-space()]"));
                $this->logger->debug($accountInfo);

                if (preg_match("/^(?<status>\D+)\n(?<pax>[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\n[X]*(?<account>\d{3,})$/", $accountInfo, $m)) {
                    $st->setNumber($m['account']);

                    $st->addProperty('Name', trim($m['pax'], ','))
                        ->addProperty('Status', trim($m['status'], ','));
                }
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
