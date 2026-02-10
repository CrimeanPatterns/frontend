<?php

namespace AwardWallet\Engine\goldpassport\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class YourAccount2 extends \TAccountChecker
{
    public $mailFiles = "goldpassport/statements/it-673295048.eml, goldpassport/statements/it-673407326.eml, goldpassport/statements/it-674023391.eml, goldpassport/statements/it-674890661.eml, goldpassport/statements/it-678561116.eml, goldpassport/statements/it-678751764.eml, goldpassport/statements/it-678956060.eml, goldpassport/statements/it-86102176.eml, goldpassport/statements/it-92812564.eml, goldpassport/statements/it-903848009.eml, goldpassport/statements/it-912617488.eml";

    public $subjects = [
        // zh
        '您的账户摘要 ',
        '您的賬戶摘要',
        // ko
        '계정 요약 정보 - ',
        // de
        'Ihre Kontoübersicht –',
        // es
        'Resumen de su cuenta - ',
        // fr
        'Récapitulatif de votre compte – ',
        // ja
        'アカウントサマリー：',
        // en
        'Welcome to the World of Hyatt',
        'Your Account Summary',
        'Still in Your Plans?',
        'Reminder: Keep Your Account Active',
    ];

    // only for Short-format or other-formats!!!
    public $detectBody = [
        // en
        'Which one will it be?',
        'Choose from three incredible awards.',
        'Time to get some travel on the books.',
        'Your next trip could be just around the corner.', // it-678751764.eml
        'Register before you stay to earn thousands of points', // it-92812564.eml
        'It’s time to take action so you can keep your points.',
        'Make plans to enjoy your award before it expires.',
        "Here's a look at your year:",
        "benefits for World of Hyatt members",
        "have an award in your account that is waiting",
        "New rewards to earn and new ways to redeem points are coming",
        "Plus, you get a Guest of Honor Award",
        "You must be a member of World of Hyatt in good standing",
        "Earn Bonus Points at hotels within The Unbound Collection by Hyatt",
        "Earn with a World of Hyatt Business Credit Card, redeem",
        "Nights with the World of Hyatt Credit Card",
        "Your next stay could be on us with this new cardmember offer",
        'The gift that’ll make your team feel truly rewarded.',
    ];

    public $lang = '';

    public static $dictionary = [
        // Member   |   59,049 Current Points   |   3,536 Lifetime Base Points
        //          0            5
        //        Base       Qualifying
        //       Points       Nights
        "zh" => [
            'tierValues'           => ['會員', '会员', '探索者', '冒險家', '冒险家', '环球客', '環球客'],
            'Current Points'       => ['当前积分', '當前積分'],
            'Lifetime Base Points' => ['终身基本积分', '終身基本積分'],
            'Base Points'          => ['基本积分', '基本積分'],
            'Qualifying Nights'    => ['认可房晚', '認可房晚'],
            'detectProvider'       => ['凯悦酒店集团版权所有。保留所有权利。', '凱悅酒店集團版權所有。保留所有權利。'],
        ],
        "ko" => [
            'tierValues'           => ['멤버', '글로벌리스트', '디스커버리스트', '익스플로리스트'],
            'Current Points'       => '현재 포인트',
            'Lifetime Base Points' => '라이프타임 기본 포인트',
            'Base Points'          => '기본 포인트',
            'Qualifying Nights'    => ['정규 숙박'],
            'detectProvider'       => 'Hyatt Corporation. All rights reserved',
        ],
        "de" => [
            // 'tierValues' => [''],
            'Current Points'       => 'aktuelle Punkte',
            'Lifetime Base Points' => 'Lifetime Basispunkte',
            'Base Points'          => 'Basispunkte',
            'Qualifying Nights'    => ['Qualifizierende Übernachtungen'],
            'detectProvider'       => 'Hyatt Corporation. Alle Rechte vorbehalten.',
        ],
        "es" => [
            // 'tierValues' => [''],
            'Current Points'       => ['Puntos actuales', 'puntos actuales'],
            'Lifetime Base Points' => 'Puntos Básicos Lifetime',
            'Base Points'          => 'Puntos Básicos',
            'Qualifying Nights'    => ['Noches válidas'],
            'detectProvider'       => 'Hyatt Corporation. Todos los derechos reservados.',
        ],
        "fr" => [
            // 'tierValues' => [''],
            'Current Points'       => ['Solde actuel', 'solde actuel'],
            'Lifetime Base Points' => 'Points de base Lifetime',
            'Base Points'          => 'Points de base',
            'Qualifying Nights'    => ['Nuits éligibles'],
            'detectProvider'       => 'Hyatt Corporation. Tous droits réservés.',
        ],
        "ja" => [
            'tierValues'           => ['メンバー', 'グローバリスト'],
            'Current Points'       => '現在のポイント',
            'Lifetime Base Points' => 'これまでに獲得した総ベースポイント数',
            'Base Points'          => '対象ベースポイント数',
            'Qualifying Nights'    => ['対象宿泊数'],
            'detectProvider'       => 'Hyatt Corporation. All rights reserved.',
        ],
        "en" => [ // always last!
            'tierValues'           => ['Member', 'Discoverist', 'Explorist', 'Globalist', 'Lifetime Globalist', 'Courtesy Card'],
            'Current Points'       => 'Current Points',
            'Lifetime Base Points' => 'Lifetime Base Points',
            'Base Points'          => ['Base Points', 'BASE POINTS'],
            'Qualifying Nights'    => ['Qualifying Nights', 'QUALIFYING NIGHTS', 'Qualifying Night', 'QUALIFYING NIGHT'],
            'detectProvider'       => 'Hyatt Corporation. All rights reserved',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrase) {
            if (is_string($phrase) && array_key_exists('subject', $headers) && stripos($headers['subject'], $phrase) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $detectedProvider = false;

        if ($this->http->XPath->query("//a/@href[{$this->contains('//go.hyatt.com/link/v2')}]")->length > 3
            || $this->http->XPath->query("//a/@href[{$this->contains('//links.t1.hyatt.com/els')}]")->length > 3
        ) {
            $detectedProvider = true;
        }

        foreach (self::$dictionary as $dict) {
            if ($detectedProvider === false && !empty($dict['detectProvider']) && $this->http->XPath->query("//node()[{$this->contains($dict['detectProvider'])}]")->length > 0) {
                $detectedProvider = true;
            }

            if (!$detectedProvider) {
                continue;
            }

            if (!empty($dict['Current Points']) && !empty($dict['Lifetime Base Points']) && $this->findHeaderFull($dict['Current Points'], $dict['Lifetime Base Points'])->length > 0) {
                $this->logger->info('DETECT FORMAT: Full');

                return true;
            }

            if (!empty($dict['Current Points']) && $this->findHeaderShort($dict['Current Points'])->length > 0) {
                $this->logger->info('DETECT FORMAT: Short');

                return true;
            }

            if (!empty($dict['tierValues']) && $this->findHeaderVeryShort($dict['tierValues'])->length > 0) {
                $this->logger->info('DETECT FORMAT: Very Short');

                return true;
            }

            if (!empty($dict['Base Points']) && !empty($dict['Qualifying Nights']) && $this->findTable($dict['Base Points'], $dict['Qualifying Nights'])->length > 0) {
                $this->logger->info('DETECT FORMAT: table');

                return true;
            }
        }

        if ($detectedProvider && !empty(self::$dictionary['en']['tierValues']) && $this->findHeaderVeryShort(self::$dictionary['en']['tierValues'])->length > 0) {
            $this->logger->info('DETECT FORMAT: Very Short');

            return true;
        }

        if ($detectedProvider && $this->http->XPath->query("//node()[{$this->contains($this->detectBody)}]")->length > 0) {
            $this->logger->info('DETECT FORMAT: other');

            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@t1.hyatt.com') !== false || stripos($from, '@em.hyatt.com') !== false
            || stripos($from, '@m1.hpe-esp.hyatt.com') !== false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Current Points'])
                && $this->http->XPath->query("//*[{$this->contains($dict['Current Points'])}]")->length > 0
            ) {
                $this->lang = $lang;

                break;
            }
        }

        if (empty($this->lang)) {
            foreach (self::$dictionary as $lang => $dict) {
                if (!empty($dict['tierValues']) && $this->findHeaderVeryShort($dict['tierValues'])->length > 0) {
                    $this->lang = $lang;
    
                    break;
                }
            }
        }

        if (empty($this->lang)) {
            $this->lang = 'en';
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        $patterns = [
            'tier' => '[[:alpha:]]+(?:\s+[[:alpha:]]+)?', // Lifetime Globalist
            'currentPoints' => '-?\d[\d,]*', // 0  |  -4,355
        ];

        $st = $email->add()->statement();

        $tier = $noTier = $balance = $noBalance = $LTBasePoints = null;

        $headersFull = $this->findHeaderFull($this->t('Current Points'), $this->t('Lifetime Base Points'));
        $headerTextFull = $headersFull->length === 1 ? $this->http->FindSingleNode('.', $headersFull->item(0)) : '';

        if (preg_match("/^\s*(?<tier>{$patterns['tier']})\s*\|\s*(?<cp>{$patterns['currentPoints']})\s*{$this->opt($this->t('Current Points'))}\s*[\|\s]\s*(?<ltp>\d[\d,]*)\s*{$this->opt($this->t('Lifetime Base Points'))}\s*$/u", $headerTextFull, $m)
            || (in_array($this->lang, ['ko', 'ja']) && preg_match("/^\s*(?<tier>{$patterns['tier']})\s*\|\s*{$this->opt($this->t('Current Points'))}[:：\s]*(?<cp>{$patterns['currentPoints']})\s*점?\s*[\|\s]\s*{$this->opt($this->t('Lifetime Base Points'))}[:：\s]*(?<ltp>\d[\d,]*)\s*점?\s*$/u", $headerTextFull, $m))
            || (in_array($this->lang, ['fr']) &&       preg_match("/^\s*(?<tier>{$patterns['tier']})\s*\|\s*{$this->opt($this->t('Current Points'))}[:：\s]*(?<cp>{$patterns['currentPoints']})\s*(?:points?|pts)?\s*[\|\s]\s*(?<ltp>\d[\d,]*)\s*{$this->opt($this->t('Lifetime Base Points'))}\s*$/iu", $headerTextFull, $m))
        ) {
            /*
                Member   |   59,049 Current Points   |   3,536 Lifetime Base Points
            */
            $this->logger->debug('PARSING FORMAT: Full');

            $tier = $m['tier'];
            $balance = $m['cp'] !== null ? str_replace(',', '', $m['cp']) : null;
            $LTBasePoints = $m['ltp'] !== null ? str_replace(',', '', $m['ltp']) : null;
        } elseif (preg_match("/^\s*(?<tier>{$patterns['tier']})\s*\|\s*(?<cp>{$patterns['currentPoints']})?\s*{$this->opt($this->t('Current Points'))}[:：\s]*[\|\s]\s*(?<ltp>\d[\d,]*)?\s*{$this->opt($this->t('Lifetime Base Points'))}[:：\s]*$/u", $headerTextFull, $m)) {
            /*
                Member   |   Current Points   |   Lifetime Base Points
            */
            $this->logger->debug('PARSING FORMAT: Full (special)');

            $tier = $m['tier'];

            if (array_key_exists('cp', $m) && $m['cp'] !== '') {
                $balance = str_replace(',', '', $m['cp']);
            } else {
                $noBalance = true;
            }

            if (array_key_exists('ltp', $m) && $m['ltp'] !== '') {
                $LTBasePoints = str_replace(',', '', $m['ltp']);
            }
        }

        $headersShort = $this->findHeaderShort($this->t('Current Points'));

        if ($headersFull->length === 0) {
            $headerTextShort = $headersShort->length === 1 ? $this->http->FindSingleNode('.', $headersShort->item(0)) : '';

            if (preg_match("/^\s*(?<tier>{$patterns['tier']})\s*\|\s*(?<cp>{$patterns['currentPoints']})\s*{$this->opt($this->t('Current Points'))}[\|\s]*$/u", $headerTextShort, $m)
                || (in_array($this->lang, ['ko', 'ja', 'fr']) && preg_match("/^\s*(?<tier>{$patterns['tier']})\s*\|\s*{$this->opt($this->t('Current Points'))}[:：\s]*(?<cp>{$patterns['currentPoints']})\s*(?:점|points?|pts)?[\|\s]*$/iu", $headerTextShort, $m))
            ) {
                /*
                    Member   |   59,049 Current Points
                */
                $this->logger->debug('PARSING FORMAT: Short');

                $tier = $m['tier'];
                $balance = $m['cp'] !== null ? str_replace(',', '', $m['cp']) : null;
            } elseif (preg_match("/^\s*(?<tier>{$patterns['tier']})\s*\|\s*{$this->opt($this->t('Current Points'))}[:：\|\s]*$/u", $headerTextShort, $m)) {
                /*
                    Member   |   Current Points
                */
                $this->logger->debug('PARSING FORMAT: Short (special-1)');

                $tier = $m['tier'];
                $noBalance = true;
            } elseif (preg_match("/^[\|\s]*(?<cp>{$patterns['currentPoints']})\s*{$this->opt($this->t('Current Points'))}[\|\s]*$/u", $headerTextShort, $m)) {
                /*
                    |   7065 Current Points   |
                */
                $this->logger->debug('PARSING FORMAT: Short (special-2)');

                $noTier = true;
                $balance = str_replace(',', '', $m['cp']);
            }
        }

        $headersVeryShort = $this->findHeaderVeryShort($this->tPlusEn('tierValues'));

        if ($headersFull->length === 0 && $headersShort->length === 0) {
            $headerTextVeryShort = $headersVeryShort->length === 1 ? $this->http->FindSingleNode('.', $headersVeryShort->item(0)) : '';

            if (preg_match("/^\s*(?<tier>{$this->opt($this->tPlusEn('tierValues'))})[\|\s]*$/iu", $headerTextVeryShort, $m)) {
                /*
                    Member
                */
                $this->logger->debug('PARSING FORMAT: Very Short');

                $tier = $m['tier'];
                $noBalance = true;
            }
        }

        if (!$tier && $noTier) {
        } else {
            $st->addProperty('Tier', $tier);
        }
        
        if ($balance !== null) {
            $st->setBalance($balance);
        } elseif ($noBalance) {
            $st->setNoBalance(true);
        }

        if ($LTBasePoints !== null) {
            $st->addProperty('LifetimeBasePoints', $LTBasePoints);
        }

        $dateBalance = $this->http->FindSingleNode("//text()[starts-with(normalize-space(),'*Current as of')]", null, true, "/^{$this->opt('*Current as of')}\s*([[:alpha:]]+[.\s]*\d{1,2}[,\s]+\d{4})$/u");

        if (!empty($dateBalance)) {
            // examples: ???
            $st->setBalanceDate(strtotime($dateBalance));
        }

        $tables = $this->findTable($this->t('Base Points'), $this->t('Qualifying Nights'));

        if ($tables->length > 0) {
            $tableRoot = $tables->item(0);
            
            $basePoints =
                $this->http->FindSingleNode("*[normalize-space()][1]", $tableRoot, true, "/^(\d[\d,.]*?)\s*{$this->opt($this->t('Base Points'))}/i") // it-678956060.eml
                ?? $this->http->FindSingleNode("preceding-sibling::tr[normalize-space()][1]/*[normalize-space()][1]", $tableRoot, true, "/^\d[\d,.]*$/") // it-86102176.eml
            ;

            $nights =
                $this->http->FindSingleNode("*[normalize-space()][2]", $tableRoot, true, "/^(\d+)\s*{$this->opt($this->t('Qualifying Nights'))}/i")
                ?? $this->http->FindSingleNode("preceding-sibling::tr[normalize-space()][1]/*[normalize-space()][2]", $tableRoot, true, "/^\d+$/")
            ;

            $st->addProperty('BasePointsYTD', $basePoints !== null ? str_replace(',', '', $basePoints) : null);
            $st->addProperty('Nights', $nights);
        } elseif ($headersFull->length > 0 || $headersShort->length > 0 || $this->http->XPath->query("//node()[{$this->contains($this->detectBody)}]")->length > 0) {
            // examples: ???

            $basePoints = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Base Points'), true)}]/ancestor::tr[1]/preceding::text()[normalize-space()][1]", null, true, "/^\d[\d,.]*$/");

            if ($basePoints !== null) {
                $st->addProperty('BasePointsYTD', str_replace(',', '', $basePoints));
            }

            $nights = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Qualifying Nights'), true)}]/ancestor::tr[1]/preceding::text()[normalize-space()][1]", null, true, "/^\d+$/");

            if ($nights !== null) {
                $st->addProperty('Nights', $nights);
            }
        }

        return $email;
    }

    private function findHeaderFull($text1, $text2): \DOMNodeList
    {
        // examples: it-903848009.eml
        return $this->http->XPath->query("//*[../self::tr and not(.//tr[normalize-space()]) and count(descendant::text()[normalize-space()])<10 and contains(.,'|') and {$this->contains($text1)} and {$this->contains($text2)}]");
    }

    private function findHeaderShort($text): \DOMNodeList
    {
        // examples: it-92812564.eml
        return $this->http->XPath->query("//*[../self::tr and not(.//tr[normalize-space()]) and count(descendant::text()[normalize-space()])<10 and contains(.,'|') and {$this->contains($text)}]");
    }

    private function findHeaderVeryShort($text): \DOMNodeList
    {
        // examples: it-912617488.eml
        return $this->http->XPath->query("//*[../self::tr and not(.//tr[normalize-space()]) and {$this->eq($text, "translate(.,'|','')")} and count(preceding::text()[normalize-space()][position()<7][ancestor::a])>1]");
    }

    private function findTable($text1, $text2): \DOMNodeList
    {
        // examples: it-86102176.eml, it-678956060.eml
        return $this->http->XPath->query("//tr[ *[normalize-space()][1]/descendant-or-self::*[{$this->eq($text1, true)}] and *[normalize-space()][2]/descendant-or-self::*[{$this->eq($text2, true)}] ]");
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function t(string $phrase, string $lang = '')
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return $phrase;
        }
        if ($lang === '') {
            $lang = $this->lang;
        }
        if ( empty(self::$dictionary[$lang][$phrase]) ) {
            return $phrase;
        }
        return self::$dictionary[$lang][$phrase];
    }

    private function tPlusEn(string $s): array
    {
        return array_unique(array_merge((array) $this->t($s), (array) $this->t($s, 'en')));
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function eq($field, $deleteSpace = false)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }
        $text = 'normalize-space(.)';

        if ($deleteSpace == true) {
            $field = str_replace(' ', '', $field);
            $text = 'translate(normalize-space(.), " ", "")';
        }

        return '(' . implode(" or ", array_map(function ($s) use ($text) {
            return $text . "=\"{$s}\"";
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
