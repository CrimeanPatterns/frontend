<?php

namespace AwardWallet\Engine\asia\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Notification extends \TAccountChecker
{
    public $mailFiles = "asia/it-68441419.eml, asia/statements/it-65134759.eml, asia/statements/it-65181030.eml, asia/statements/it-66402972.eml, asia/statements/it-66613763.eml, asia/statements/it-927908427.eml, asia/statements/it-928107567.eml";
    public $lang;

    public $langDetector = [
        'en' => ['Operating system', 'Yours sincerely', 'notification email', 'Dear'],
        'ko' => ['회원님', '회원 번호'],
        'zh' => ['操作系統', '謹啟', '尊敬的', '親愛的'],
    ];

    public static $dictionary = [
        'en' => [
            'access' => [
                'If you did not log in at that time and suspect someone might be trying to access your account',
                'Update your profile',
                'You can ignore this message if the above information looks familiar.',
                'We have received your password reset request for your account',
            ],
            'Marco Polo Club account' => [
                'Marco Polo Club account',
                'We’ve noticed a new sign-in for your account',
                'Cathay Pacific',
                'Cathay member account',
            ],
            'Membership number' => [
                'Membership number',
                'Membership No',
            ],
        ],
        'ko' => [
            'Dear'              => '회원님',
            'Membership number' => '회원 번호',
        ],
        'zh' => [
            'Dear'   => ['尊敬的', '親愛的'],
            'access' => [
                '若您於上述時間並無登入，並懷疑有人嘗試使用您的賬戶',
                '更新您的個人資料',
                '我们注意到您的马可孛罗会账户',
            ],
            'Marco Polo Club account' => ['的馬可孛羅會賬戶', '會員號碼：', '我们注意到您的马可孛罗会账户'],
            'Membership number'       => '会员编号',
        ],
    ];

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if (self::detectEmailFromProvider($parser->getHeader('from')) !== true
            && $this->http->XPath->query('//a[contains(@href,".cathaypacific.com/") or contains(@href,"e.cathaypacific.com")]')->length === 0
            && $this->http->XPath->query('//node()[contains(normalize-space(),"© Cathay Pacific") or contains(normalize-space(),"@club.cathaypacific.com")]')->length === 0
        ) {
            return false;
        }

        if ($this->detectLang()
            && ($this->http->XPath->query("//text()[{$this->contains($this->t('Marco Polo Club account'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->starts($this->t('Membership number'))}]")->length > 0)
            // WelcomeStatement
            && $this->http->XPath->query("//text()[{$this->contains(['Welcome to the world of travel with Cathay Pacific', 'Thank you for registering.'])}]")->length === 0
            // YourAccountSummary
            && $this->http->XPath->query("//text()[{$this->contains(['Statement as of', 'Club points', '利用明細書', 'クラブ・ポイント', '結算截至', '结算截至', '会籍积分'])}]")->length === 0
            // YourAccountSummary2023
            && $this->http->XPath->query("//text()[{$this->contains(['Your Account Summary', 'Current statement:', '您的账户概要', '结算截至:'])}]")->length === 0
            // MilesToExpire
            && $this->http->XPath->query("//text()[{$this->contains(['Your account balance', 'expiring on'])}]")->length === 0
        ) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]cathaypacific\.com/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->detectLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $patterns = [
            'prefixName'    => "{$this->opt(['Dr', 'Miss', 'Mrs', 'Mr', 'Ms', 'Mme', 'Mr/Mrs', 'Mrs/Mr', 'Monsieur'])}",
            'travellerName' => "[[:alpha:]][-.\'’[:alpha:] ]*?[[:alpha:]]?",
        ];

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]",
            null, true, "#^{$this->opt($this->t('Dear'))}\s*(?:{$patterns['prefixName']}[\s\.]+)?({$patterns['travellerName']})\s*[^A-Z]*$#ui");

        if (empty($name)) {
            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Membership number'))}]/preceding::text()[normalize-space()][1]",
                null, true, "#^({$patterns['travellerName']})\s*[^A-Z]*$#ui");
        }

        $st->addProperty('Name', $name);

        $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]/following::text()[{$this->contains($this->t('Marco Polo Club account'))}][1]", null, true, "/{$this->opt($this->t('Marco Polo Club account'))}\s+([X\d ]{5,})\b/u");

        if (empty($number)) {
            $number = $this->http->FindSingleNode("//tr[{$this->starts($this->t('Marco Polo Club account'))}]", null, true, "/{$this->opt($this->t('Marco Polo Club account'))}[:：\s]*([-Xx\d ]{5,})$/u");
        }

        if (empty($number)) {
            $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Membership number'))}][normalize-space()]", null, true, "/^{$this->opt($this->t('Membership number'))}[\s\:\.]+([\dXx ]+)$/");
        }

        $number = str_replace(' ', '', $number);

        if (preg_match("/^(\d+)[Xx]+(\d+)$/", $number, $m)) {
            // 172XXXX649
            $numberMasked = $m[1] . '**' . $m[2];
            $st->setNumber($numberMasked)->masked('center')
                ->setLogin($numberMasked)->masked('center');
        } elseif (preg_match("/^\d+$/", $number)) {
            // 1723548649
            $st->setNumber($number)
                ->setLogin($number);
        }

        if ($name || $number) {
            $st->setNoBalance(true);
        }

        $login = $this->http->FindSingleNode("//text()[{$this->contains($this->t('We’ve noticed a new sign-in for your account'))}]", null, true, "/{$this->opt($this->t('We’ve noticed a new sign-in for your account'))}\s\((\S+[@]\S+\.\S+)\)\./u");

        if (!empty($login)) {
            $st->setLogin($login);
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

    public function detectLang(): bool
    {
        foreach ($this->langDetector as $lang => $detect) {
            if ($this->http->XPath->query("//text()[{$this->contains($detect)}]")->length > 0) {
                $this->lang = substr($lang, 0, 2);

                return true;
            }
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

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s));
        }, $field)) . ')';
    }
}
