<?php

namespace AwardWallet\Engine\eva\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Common\Parser\Util\PriceHelper;

class InfinityMileageLands extends \TAccountChecker
{
    public $mailFiles = "eva/statements/it-110508877.eml, eva/statements/it-66934630.eml, eva/statements/it-66965904-zh.eml, eva/statements/it-66971075.eml, eva/statements/it-73765316-zh.eml, eva/statements/it-917493776.eml, eva/statements/it-917481184.eml, eva/statements/it-917416866-zh.eml";

    public $lang = '';

    public static $dictionary = [
        "en" => [
            // 'Dear' => '',
            // 'Card Number' => '',
            'Member No.:'                 => ['Member No.:', 'Member No. :'],
            'Your current card status is' => ['Your current card status is', 'Your membership status is'],
            // 'Award Miles' => '',
            // 'miles' => '',
        ],
        "zh" => [
            'Dear'                        => '親愛的',
            'Card Number'                 => '您的會員卡號',
            // 'Member No.:' => '',
            'Your current card status is' => '您是本公司 綠卡會員，',
            'Award Miles'                 => '獎勵哩程',
            'miles'                       => '哩',
        ],
    ];

    private $detectors = [
        'en' => [
            'We’ve just received your "Forgot Password" inquiry',
            'name in your membership account is',
            'Infinity MileageLands Mileage Statement',
            'If you cannot view this Mileage Statement',
            'Your remaining self Award Miles in your account',
            'Please click the below hyperlink and enter the Verification Code to reset your new password',
        ],
        'zh' => ['含賺取與購買哩程'],
    ];

    private $membershipPhrases = [
        'Dear Member,',
        '親愛的會員您好', // zh
        '親愛的會員先生/小姐您好', // zh
        'You had successfully logged in EVA AIR App',
        'You had successfully logged in EVA Air Official Website',
    ];

    private $otcPhrases = [
        'Your one-time password (OTP) is',
        '您的一次性動態密碼(OTP)是', // zh
    ];

    private $patterns = [
        'travellerName' => '[[:alpha:]][-.\'\/[:alpha:] ]*[[:alpha:]]', // Mr. KURTZ JERID    |    Mr. KURTZ/JERID
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]evaair\.com$/i', $from) > 0;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && stripos($parser->getSubject(), 'EVA Air Infinity MileageLands') === false
            && $this->http->XPath->query('//a[contains(@href,".evaair.com/") or contains(@href,"www.evaair.com")]')->length === 0
            && $this->http->XPath->query('//node()[contains(normalize-space(),"Sincerely Yours, Infinity MileageLands Service") or contains(normalize-space(),"EVA AIRWAYS Copyright") or contains(normalize-space(),"© EVA Airways Corp") or contains(.,"www.evaair.com") or contains(.,"@mh1.evaair.com")]')->length === 0
        ) {
            return false;
        }

        return $this->isMembership($parser->getPlainBody()) || $this->detectBody();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if ($this->http->XPath->query('//node()[contains(normalize-space(),"Statement") or contains(normalize-space(),"哩程核對表")]')->length > 0) {
            return $email;
        }

        $this->assignLang();

        if (empty($this->lang)) {
            $this->lang = 'en';
        }

        if (empty($textPlain = $parser->getPlainBody())) {
            $textPlain = $this->http->Response['body'];
        }

        $this->parseOTC($email, $textPlain);

        $st = $email->add()->statement();

        $name = $number = $status = $balance = null;

        /*
            Step 1: parse fields values
        */

        // Name
        $familyName = $this->http->FindSingleNode("//text()[{$this->starts('Family Name')}]", null, true, "/{$this->opt('Family Name')}[:\s]+({$this->patterns['travellerName']})(?:\s*[,.;:!?]|$)/u");
        $givenName = $this->http->FindSingleNode("//text()[{$this->starts('Given Name')}]", null, true, "/{$this->opt('Given Name')}[:\s]+({$this->patterns['travellerName']})(?:\s*[,.;:!?]|$)/u");
        $name = $familyName && $givenName ? $givenName . ' ' . $familyName : null; // it-66934630.eml

        if (!$name) {
            $name = $this->normalizeTraveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]", null, true, "/{$this->opt($this->t('Dear'))}\s+({$this->patterns['travellerName']})(?:\s*[,.;:!?]|$)/u"));
        }

        if (!$name) {
            $name = $this->normalizeTraveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]", null, true, "/{$this->opt($this->t('Dear'))}\s*({$this->patterns['travellerName']})\s*(?:先生您好)(?:\s*[,.;:!?]|$)/u"));
        }

        if (!$name) {
            $name = $this->normalizeTraveller($this->http->FindSingleNode("//text()[{$this->starts('Card Number')}]/preceding::text()[normalize-space()][1]", null, true, "/^(?:Mr\.|Ms\.)\s*{$this->patterns['travellerName']}$/u"));
        }

        if (preg_match("/^Member$/i", $name)) {
            $name = null;
        }

        // Number
        $number = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Member No.:'))}]/following::text()[normalize-space()][1]", null, true, '/^[A-Z\d]{5,}$/');

        if (!$number) {
            // it-66954208.eml
            $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Card Number'))}]", null, true, "/^{$this->opt($this->t('Card Number'))}[:\s]+([A-Z\d ]{5,})$/");
        }

        if (!$number) {
            // it-66954208.eml
            $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Card Number'))}]", null, true, "/^{$this->opt($this->t('Card Number'))}\:?\s*+([A-Z\d ]{5,})/");
        }

        if (!$number) {
            // it-66874008.eml
            $number = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Your remaining self Award Miles in your account'))}]", null, true, "/{$this->opt($this->t('Your remaining self Award Miles in your account'))}[:\s]+([A-Z\d]{5,})(?: |from|$)/");
        }
        $number = str_replace(' ', '', $number);

        // Status
        $status = $this->http->FindSingleNode("descendant::*[{$this->contains($this->t('Your current card status is'))}][last()]", null, true, "/{$this->opt($this->t('Your current card status is'))}\s+([-A-z]{2,})(?:\s+Card)?(?:\s*[.]|$)/");

        if (!$status) {
            $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your current card status is'))}]", null, true, "/{$this->opt($this->t('Your current card status is'))}\s*(.+)$/");
        }

        // Balance
        $balance = $this->http->FindSingleNode("//table/descendant::text()[normalize-space()][1][{$this->eq($this->t('Mileage Balance'))}]/following::text()[normalize-space()][1]", null, true, "/^\d[,.\'\d ]*$/");

        if ($balance === null) {
            // it-66874008.eml
            $balance = $this->http->FindSingleNode("//tr[{$this->eq($this->t('Award Miles'))}]/following-sibling::tr[normalize-space()][1]", null, true, "/^(\d[,.\'\d ]*){$this->opt($this->t('miles'))}?$/i");
        }

        /*
            Step 2: set fields values
        */

        if ($name) {
            $st->addProperty('Name', $name);
        }

        if (preg_match("/^(\d+)[Xx]+(\d+[A-Z]{0,2})$/", $number, $m)) {
            // 130XXXX771GC
            $numberMasked = $m[1] . '**' . $m[2];
            $st->setNumber($numberMasked)->masked('center')
                ->setLogin($numberMasked)->masked('center');
        } elseif (preg_match("/^\d+$/", $number)) {
            // 1309771365
            $st->setNumber($number)
                ->setLogin($number);
        }

        if ($status) {
            // it-66954208.eml
            $st->addProperty('Status', $status);
        }

        if ($balance !== null) {
            $st->setBalance(PriceHelper::parse($balance));

            return $email;
        } elseif ($name || $number || $status) {
            $st->setNoBalance(true);

            return $email;
        }

        if ($this->isMembership($textPlain)) {
            // it-66971075.eml, it-66965904-zh.eml
            $st->setMembership(true);

            return $email;
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

    private function isMembership(?string $text = ''): bool
    {
        // examples: ???

        $phrases = array_merge($this->membershipPhrases, $this->otcPhrases);

        if ($this->http->XPath->query("//node()[{$this->contains($phrases)}]")->length > 0) {
            $this->logger->debug(__FUNCTION__ . '()');

            return true;
        }

        if (empty($text)) {
            return false;
        }

        $text = preg_replace('/\s+/', ' ', $text);

        foreach ($phrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $this->logger->debug(__FUNCTION__ . '()');

                return true;
            }
        }

        return false;
    }

    private function parseOTC(Email $email, string $textPlain): bool
    {
        // examples: it-917481184.eml, it-917416866-zh.eml

        $otcPattern = "/{$this->opt($this->otcPhrases)}[:：\s]*(\d+)(?:\s*[,.;!]|\s|$)/";
        $code = $this->http->FindSingleNode("descendant::text()[{$this->contains($this->otcPhrases)}][1]", null, true, $otcPattern);

        if ($code === null && preg_match($otcPattern, preg_replace('/\s+/', ' ', $textPlain), $m)) {
            $code = $m[1];
        }

        if ($code !== null) {
            $this->logger->debug(__FUNCTION__ . '()');
            $otс = $email->add()->oneTimeCode();
            $otс->setCode($code);

            return true;
        }

        return false;
    }

    private function detectBody(): bool
    {
        if ($this->assignLang()) {
            if (!isset($this->detectors)) {
                return false;
            }

            foreach ($this->detectors as $phrases) {
                foreach ((array) $phrases as $phrase) {
                    if (!is_string($phrase)) {
                        continue;
                    }

                    if ($this->http->XPath->query("//node()[{$this->contains($phrase)}]")->length > 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function assignLang(): bool
    {
        foreach ($this->detectors as $lang => $words) {
            foreach ($words as $word) {
                if ($this->http->XPath->query("//node()[{$this->contains($word)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
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
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
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

    private function normalizeTraveller(?string $s): ?string
    {
        if (empty($s)) {
            return null;
        }

        $namePrefixes = '(?:MASTER|MSTR|MISS|MRS|MR|MS|DR)';

        return preg_replace([
            "/^(?:{$namePrefixes}[.\s]+)+(.{2,})$/is",
            '/^([^\/]+?)(?:\s*[\/]+\s*)+([^\/]+)$/',
        ], [
            '$1',
            '$2 $1',
        ], $s);
    }
}
