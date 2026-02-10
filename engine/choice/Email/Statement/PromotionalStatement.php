<?php

namespace AwardWallet\Engine\choice\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class PromotionalStatement extends \TAccountChecker
{
    public $mailFiles = "choice/it-222661693.eml, choice/statements/it-67784198.eml, choice/statements/it-67804662.eml, choice/statements/it-68001909.eml, choice/statements/it-68007647-fr.eml, choice/statements/it-68307032.eml, choice/statements/it-68437521-junk.eml, choice/statements/it-68440547.eml, choice/statements/it-68472454.eml, choice/statements/it-901811348.eml, choice/statements/it-902728611-de.eml, choice/statements/it-902906727-es.eml, choice/statements/it-911769676.eml, choice/statements/it-912370995.eml";

    // 'email_choiceprivileges@your.choicehotels.com', 'connected@your.choicehotels.com', 'email_cp_canada@your.choicehotels.com'
    private $detectFrom = ['@your.choicehotels.com', '@members.choicehotels.com'];

    private $detectBody = [
        'fr' => [
            'Ce courriel peut inclure du contenu promotionnel de Choice Hotels International, Inc',
            'nuitées ou plus dans un établissement ou hôtel avec casino Choice Hotels',
            'lorsque vous achetez des points Choice Privileges',
            'sécurisée pour les membres Choice Privileges',
            'au moyen de l’application mobile Choice Hotels',
        ],
        'de' => [
            'Diese E-Mail enthält möglicherweise werbliche Inhalte von Choice Hotels International, Inc',
        ],
        'es' => [
            'Este correo electrónico puede contener material promocional de Choice Hotels International, Inc',
        ],
        'en' => [
            'This email may contain promotional content from Choice Hotels International, Inc',
            'For Choice Privileges program details',
            'To qualify for and earn Choice Privileges',
            '2 Steps to Enhance Your Account’s Security',
            'Bonus points are only available to newly enrolled e-Rewards members',
            'may be booked through a Choice Hotels direct channel',
            'Choice Privileges Loyalty Services',
        ],
    ];

    private $lang = '';

    private static $dictionary = [
        'fr' => [
            'Hi '               => ['Bonjour ', 'Bonjour,'],
            'points as of'      => ['points au', 'point au'],
            'linkText'          => 'Voir votre compte',
            'Membership Level:' => 'Statut de membre:',
        ],
        'de' => [
            'Hi '               => ['Hallo ', 'Hallo,'],
            'points as of'      => 'Punkte am',
            'linkText'          => 'Konto einsehen',
            // 'Membership Level:' => '',
        ],
        'es' => [
            'Hi '               => ['Hola ', 'Hola,'],
            // 'points as of' => '',
            'linkText'          => 'Cuenta',
            // 'Membership Level:' => '',
        ],
        'en' => [
            'Hi '          => ['Hi ', 'Hi,', 'Hello ', 'Hello,'],
            'points as of' => ['points as of', 'point as of'],
            'linkText'     => ['View Account', 'Account', 'Book Now'],
            //            'Membership Level:' => '',
        ],
    ];

    private $patterns = [
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]',
        'number' => '[A-Z\d]+\d[A-Z\d]+',
    ];

    private function parseTable1(&$status, &$nightsToNext): void
    {
        // examples: it-68307032.eml

        if (!$status) {
            $status = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Membership Level:'))}]/following::text()[normalize-space()][1]");
        }

        if ($nightsToNext === null) {
            $nightsToNext = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Membership Level:'))}]/following::text()[{$this->contains('nights to reach')}]/ancestor::td[1]", null, true, "/\b(\d{1,3})\s*{$this->preg_implode('nights to reach')}\s+\w+\s+status/i");
        }
    }

    private function parseTable2(&$status, &$nightsToNext): void
    {
        // examples: it-911769676.eml

        $xpathNoEmpty = "descendant::img[normalize-space(@alt)] or normalize-space()";

        if (!$status) {
            $status = $this->http->FindSingleNode("//*[ count(*[{$xpathNoEmpty}])=2 and *[{$xpathNoEmpty}][2][{$this->starts($this->t('Member Number:'))}] ]/*[{$xpathNoEmpty}][1]/descendant::img/@alt", null, true, "/^[[:alpha:]]+(?:\s+[[:alpha:]]+)*$/u");
        }

        if ($nightsToNext === null) {
            $nightsToNext = $this->http->FindSingleNode("//*[{$this->starts($this->t('Member Number:'))}]/following::text()[normalize-space()][position()<4][{$this->contains('nights away from')}]", null, true, "/You are\s*(\d{1,3})\s*{$this->preg_implode('nights away from')}\s+[[:alpha:]]+(?:\s+[[:alpha:]]+)*\s+status/iu");
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        if (!empty($this->http->FindSingleNode("//img[@alt='Search Hotels']/preceding::*[normalize-space() or self::img][1][contains(@src, 'logo-header')]/@src"))) {
            // WTF?
            $email->setIsJunk(true);

            return $email;
        }
        $st = $email->add()->statement();

        $xpathNoDisplay = 'ancestor-or-self::*[contains(translate(@style," ",""),"display:none")]';

        // type 1: #AXM639711 | 0 points as of 10/17/2020 | Account
        $info = $this->http->FindSingleNode("//text()[{$this->contains($this->t('points as of'))} and not({$xpathNoDisplay})]/ancestor::*[ descendant::a[{$this->eq($this->t('linkText'))}] ][1]");

        if (empty($info)) {
            $info = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Choice Privileges Loyalty Services')]/preceding::a[{$this->eq($this->t('linkText'))}][1]/ancestor::*[{$this->contains($this->t('points as of'))}][1]");
        }

        if (!empty($info)) {
            $expirationDate = strtotime($this->http->FindSingleNode("//text()[contains(normalize-space(),'points will be forfeited on')]/following::text()[normalize-space()][1]", null, true, "/^\d{1,2}\/\d{1,2}\/\d{4}$/"));

            if (preg_match("/\b(?<number>{$this->patterns['number']}|\s)[#\|\s]+(?<balance>\d[\d,. ]*?)\s+{$this->preg_implode($this->t('points as of'))}\s+(?<date>[,\/[:alpha:]\d ]+?)[\|\s]*{$this->preg_implode($this->t('linkText'))}\s*$/u", $info, $m)) {
                if (trim($m['number']) !== '') {
                    $st->setNumber($m['number']);
                }
                $st->setBalance(str_replace([',', ' '], '', $m['balance']));

                if (preg_match("/^\s*(\d{1,2})\/(\d{1,2})\/(\d{2}|\d{4})\s*$/", $m['date'], $mat)) {
                    $emailDate = strtotime($parser->getDate());

                    if (strlen($mat[3]) === 2) {
                        $mat[3] = '20' . $mat[3];
                    }

                    $date1 = strtotime($mat[1] . '.' . $mat[2] . '.' . $mat[3]);
                    $date2 = strtotime($mat[2] . '.' . $mat[1] . '.' . $mat[3]);

                    if (empty($date1) && !empty($date2)) {
                        $date = $date2;
                    } elseif (!empty($date1) && empty($date2)) {
                        $date = $date1;
                    } elseif (!empty($date1) && !empty($date2)) {
                        $ds1 = abs($emailDate - $date1);
                        $ds2 = abs($emailDate - $date2);

                        if ($ds1 > $ds2) {
                            $date = $date2;
                        } else {
                            $date = $date1;
                        }
                    }
                } else {
                    $date = strtotime($m[3]);
                }

                if ($date && $date > strtotime('12 hours', time()) && $date === $expirationDate) {
                } else {
                    $st->setBalanceDate($date);
                }
            }

            $name = null;

            $travellerNames = array_filter($this->http->FindNodes("//a[{$this->eq($this->t('linkText'))}]/preceding::text()[{$this->starts($this->t('Hi '))}]", null, "/^{$this->preg_implode($this->t('Hi '))}[,\s]*({$this->patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));

            if (count(array_unique($travellerNames)) === 1) {
                $name = array_shift($travellerNames);
            }

            if (empty($name)) {
                $travellerNames = array_filter($this->http->FindNodes("//text()[starts-with(normalize-space(),'Choice Privileges Loyalty Services,')]/preceding::text()[{$this->starts($this->t('Hi '))}]", null, "/^{$this->preg_implode($this->t('Hi '))}[,\s]*({$this->patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));

                if (count(array_unique($travellerNames)) === 1) {
                    $name = array_shift($travellerNames);
                }
            }

            if (!empty($name)) {
                $st->addProperty('Name', $name);
            }

            $status = $nightsToNext = null;

            $this->parseTable1($status, $nightsToNext);
            $this->parseTable2($status, $nightsToNext);

            if (!empty($status)) {
                $st->addProperty('ChoicePrivileges', ucfirst(strtolower($status)));
            }

            if ($nightsToNext !== null) {
                $st->addProperty('Eligible', $nightsToNext);
            }

            if ($expirationDate) {
                $st->setExpirationDate($expirationDate);

                return $email;
            }
        }

        /*
            type 2:

            Hello, Jennifer
            Member #: JXF9457
            Point Balance: 5,048 as of 10/18/2020
            View Account
        */
        $info = implode("\n", $this->http->FindNodes("//text()[{$this->starts($this->t('Member #:'))}]/ancestor::*[{$this->starts($this->t('Hi '))}][1]/descendant::text()[normalize-space()]"));

        if (!empty($info)) {
            if (preg_match("/{$this->preg_implode($this->t('Hi '))}[,\s]*({$this->patterns['travellerName']})[ ]*\n/u", $info, $m)) {
                $st->addProperty('Name', $m[1]);
            }

            if (preg_match("/{$this->preg_implode($this->t('Member #:'))}[:#\s]*({$this->patterns['number']})\s*\n/", $info, $m)) {
                $st->setNumber($m[1]);
            }

            if (preg_match("/{$this->preg_implode($this->t('Point Balance:'))}\s*(\d[\d,.]*) as of ([\d\/]+)\s*\n/",
                $info, $m)) {
                $st->setBalance(str_replace(',', '', $m[1]));
                $st->setBalanceDate(strtotime($m[2]));

                return $email;
            }
        }

        // type 3, type 4, type 5
        $info = implode("\n", $this->http->FindNodes("descendant::a[{$this->eq($this->t('linkText'))}][1]/ancestor::*[ ../self::tr or self::p[count(descendant::text()[string-length(normalize-space())>3])>1] ][1]/descendant::text()[normalize-space()]"));

        if (preg_match("/^#[:\s]*(?<number>{$this->patterns['number']})[#\s]*\|\s*(?<balance>\d[\d,. ]*?)\s*points?$/m", $info, $m)) {
            /*
                type 3: it-901811348.eml

                Hi, Terrance!
                #81275924827 | 1000 points
                Book Now
            */
            $name = $this->re("/^\s*{$this->preg_implode($this->t('Hi '))}[, ]*([[:alpha:] \-]+?)[,;!\s]*$/mu", $info);
            $st->addProperty('Name', $name);

            $st->setNumber($m['number'])->setBalance(str_replace([',', ''], '', $m['balance']));

            return $email;
        } elseif (preg_match("/#[:\s]*(?<number>{$this->patterns['number']})[#\s]*\|\s*{$this->preg_implode($this->t('linkText'))}\s*$/", $info, $m)
            || preg_match("/^[#:\s]*(?<number>{$this->patterns['number']})[#\s]+\d{1,2}\/\d{1,2}(?:\/\d{2}|\/\d{4})?\s*\|\s*{$this->preg_implode($this->t('linkText'))}/m", $info, $m)
        ) {
            /*
                type 4: it-67804662.eml, it-912370995.eml
                #AXP77935 | Account

                type 5: it-902906727-es.eml
                83066855764 22/04/25 | Cuenta
            */
            $name = $this->re("/^\s*{$this->preg_implode($this->t('Hi '))}[, ]*([[:alpha:] \-]+?)[,;!\s]*$/mu", $info);

            if (!$name) {
                $travellerNames = array_filter($this->http->FindNodes("//a[{$this->eq($this->t('linkText'))}]/preceding::text()[{$this->starts($this->t('Hi '))}]", null, "/^{$this->preg_implode($this->t('Hi '))}[,\s]*({$this->patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));

                if (count(array_unique($travellerNames)) === 1) {
                    $name = array_shift($travellerNames);
                }
            }

            $st->addProperty('Name', $name);

            $st->setNumber($m['number'])->setNoBalance(true);

            return $email;
        }

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return $this->striposAll($from, $this->detectFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true) {
            return false;
        }

        $ruleHref = $this->contains([
            '.choicehotels.com/', '.choicehotels.com%2F',
            'trk.choicehotels.com', 'members.choicehotels.com',
        ], '@href');

        if ($this->http->XPath->query("//a[{$ruleHref}]")->length < 4) {
            return false;
        }

        return $this->assignLang();
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function assignLang(): bool
    {
        /*
            Method 1
        */
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['Hi ']) || empty($phrases['linkText']) ) {
                continue;
            }
            if ($this->http->XPath->query("//text()[{$this->starts($phrases['Hi '])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->starts($phrases['linkText'])}]")->length > 0
            ) {
                $this->lang = $lang;
                return true;
            }
        }

        /*
            Method 2
        */
        foreach ($this->detectBody as $lang => $detectBody) {
            if ($this->http->XPath->query("//*[{$this->contains($detectBody)}]")->length > 0) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
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

    private function eq($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'normalize-space(' . $node . ")='" . $s . "'";
        }, $field)) . ')';
    }

    private function starts($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
    }

    private function preg_implode($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    private function re(string $re, ?string $str, $c = 1): ?string
    {
        if (preg_match($re, $str ?? '', $m) && array_key_exists($c, $m)) {
            return $m[$c];
        }

        return null;
    }

    private function striposAll($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (stripos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && stripos($text, $needle) !== false) {
            return true;
        }

        return false;
    }
}
