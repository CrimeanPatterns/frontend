<?php

namespace AwardWallet\Engine\carlson\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class YourEStatement2 extends \TAccountChecker
{
    public $mailFiles = "carlson/statements/it-926663067.eml, carlson/statements/it-926690531.eml, carlson/statements/it-926928880.eml, carlson/statements/it-927254918.eml, carlson/statements/it-927808412.eml, carlson/statements/it-927958032.eml";
    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            "You've registered with us as" => ["You've registered with us as", "You’ve registered with us as"],
            'HELLO'                        => ['HELLO', 'Hello', 'Dear', 'DEAR'],
        ],
        "no" => [
            "You've registered with us as" => ['Du har meldt deg på til oss som'],
        ],
        "de" => [
            "You've registered with us as" => ['Sie sind bei uns bereits registriert als'],
        ],
        "es" => [
            "You've registered with us as" => ['Se ha registrado con nosotros como'],
        ],
        "pt" => [
            "You've registered with us as" => ['Você registrou-se conosco como'],
        ],
        "fi" => [
            "You've registered with us as" => ['Olet rekisteröitynyt seuraavilla tiedoilla'],
        ],
    ];

    public $subjects = [
        // en
        "tier will expire in",
        "Confirm your Radisson Rewards email subscription",
        "Activate your Radisson Rewards account now & define your password",
        "Your Radisson Rewards Points Will Expire in",
        "Last chance to redeem your Radisson Rewards points and earn points back!",
        "Suspicious activity on your Radisson Rewards user account",
        "Your rewards are waiting: earn up to",
        "thanks for using your points",
        "reasons to discover our new hotels",
        "Unlock exclusive rewards earning points",
        "The security settings of your account have been updated",
        "Go green, earn double",
        "Confirm your email subscription",
        "let's reconnect today",
        "points at your reach",
        "Explore hotels and earn",
        "Discover the city your way",
        "Visit your dream city and gain rewards",
        // no
        "Introduserer det første",
        // de
        "Introduserer det første",
        "Punkte zum Greifen nah",
        // es
        "Confirme su suscripción a los correos electrónicos",
        // pt
        "Confirme sua assinatura para receber e-mails",
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]radissonhotels\.com/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'radissonhotels.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers["subject"], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $this->detectLang();

        if ($this->http->XPath->query('//img[contains(@src,"radissonhotels")] | //*[contains(normalize-space(), "Radisson Rewards")]')->length == 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t("RESERVATION SUMMARY"))}]")->length > 0) {
            return false;
        }

        return $this->http->XPath->query("//img[contains(@src, 'm29767817.png') or contains(@src, 'm29822719.png')]")->length > 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t("You've registered with us as"))}]")->length > 0;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $this->detectLang();

        $name = $this->http->FindSingleNode("//tr[not(.//tr)][{$this->starts($this->t('HELLO'))}]", null, true, "/^{$this->opt($this->t('HELLO'))}\s+([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])[, ]*/ui");

        if ($name) {
            $st->addProperty('Name', $name);
        }

        $login = $this->http->FindSingleNode("//tr[not(.//tr)][{$this->starts($this->t("You've registered with us as"))}]", null, true, "/^{$this->opt($this->t("You've registered with us as"))}[* ]*:\s*(\S{8,})$/u");

        $st->setLogin($login);

        $patterns['tier'] = 'CLUB|SILVER|GOLD|PLATINUM|VIP|Club|Premium|Silver|Gold|Platinum';

        $accountInfo = $this->http->FindSingleNode("//td[./descendant::img[contains(@src, 'm29767817.png') or contains(@src, 'm29822719.png')]]/preceding-sibling::td[normalize-space()][contains(normalize-space(), '|')][1]");

        if ($accountInfo && preg_match("/^(?<tier>{$patterns['tier']})[ ]*\|[ ]*(?<points>\d[,.\'\d ]*)[ ]*\|[ ]*(?<number>(.*\d.*))$/u", $accountInfo, $m)) {
            $points = $m['points'];
            $number = $m['number'];
            $tier = $m['tier'];
        }

        if (!isset($number)) {
            $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your member ID is now'))}]", null, true, "/^{$this->opt($this->t('Your member ID is now'))}[* ]*:\s*(\S{8,})$/u");
        }

        if (isset($tier)) {
            $st->addProperty('Status', $tier);
        }

        if (isset($number)) {
            $st->setNumber($number);
        }

        if (isset($points)) {
            $st->setBalance($this->normalizeAmount($points));
        } else {
            $st->setNoBalance(true);
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

    private function normalizeAmount(?string $s, ?string $decimals = null): ?float
    {
        if (!empty($decimals) && preg_match_all('/(?:\d+|' . preg_quote($decimals, '/') . ')/', $s, $m)) {
            $s = implode('', $m[0]);

            if ($decimals !== '.') {
                $s = str_replace($decimals, '.', $s);
            }
        } else {
            $s = preg_replace('/\s+/', '', $s);
            $s = preg_replace('/[,.\'](\d{3})/', '$1', $s);
            $s = preg_replace('/,(\d{1,2})$/', '.$1', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    private function detectLang()
    {
        foreach (self::$dictionary as $lang => $detects) {
            foreach ($detects as $detect) {
                if ($this->http->XPath->query("//text()[{$this->contains($detect)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function t($s)
    {
        if (!isset($this->lang) || !isset(self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field));
    }
}
