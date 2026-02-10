<?php

namespace AwardWallet\Engine\aurigny\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;

class YourTrip extends \TAccountChecker
{
    public $mailFiles = "aurigny/it-863709351.eml, aurigny/it-895346898.eml, aurigny/it-895361516.eml";

    public $detectSubjects = [
        'en' => [
            'Thank you for booking Aurigny',
            'Flight Information',
        ],
    ];

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'confNumber' => ['Booking Reference'],
            'Seats'      => ['Seats'],
        ],
    ];

    private $travellers = [];
    private $patterns = [
        'date'           => '\b\d{1,2}[-,.\s]+[[:alpha:]]+[-,.\s]+\d{2,4}\b', // 02 Feb 25
        'time'           => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
        'travellerName'  => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        'travellerName2' => '[[:upper:]]+(?: [[:upper:]]+)*[ ]*\/[ ]*(?:[[:upper:]]+ )*[[:upper:]]+', // KOH / KIM LENG MR
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]aurigny\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers['from'], 'aurigny.com') === false) {
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

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $href = ['.aurigny.com/', 'www.aurigny.com'];

        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//text()[starts-with(normalize-space(),"Thank you for choosing Aurigny")]')->length === 0
        ) {
            return false;
        }

        return $this->findSegments()->length > 0;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");
        }
        $email->setType('YourTrip' . ucfirst($this->lang));

        $f = $email->add()->flight();

        $confirmation = $this->http->FindSingleNode("//text()[{$this->starts($this->t('confNumber'))} and contains(.,':')]");

        if (preg_match("/^({$this->opt($this->t('confNumber'))})[:\s]+([A-Z\d]{5,10})$/", $confirmation, $m)) {
            $f->general()->confirmation($m[2], $m[1]);
        }

        $segments = $this->findSegments();

        foreach ($segments as $i => $root) {
            $s = $f->addSegment();

            $airportDep = $this->http->FindSingleNode("*[1]", $root);
            $airportArr = $this->http->FindSingleNode("*[3]", $root);

            $s->departure()->name($airportDep);
            $s->arrival()->name($airportArr);

            $dateVal = $this->http->FindSingleNode("preceding::text()[normalize-space()][1]/ancestor::tr[1]", $root);

            if (preg_match("/^\s*(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<number>\d{1,5})\s+(?<date>{$this->patterns['date']})/", $dateVal, $m)) {
                $s->airline()->name($m['name'])->number($m['number']);
                $date = strtotime($m['date']);
            }

            $timeDep = $this->http->FindSingleNode("following::tr[normalize-space() and count(*)>1][1]/*[normalize-space()][1]", $root, true, "/^{$this->patterns['time']}/")
                // it-895361516.eml
                ?? $this->http->FindSingleNode("following::table[normalize-space() and count(*)>1][1]/*[normalize-space()][1]", $root, true, "/^{$this->patterns['time']}/");
            $timeArr = $this->http->FindSingleNode("following::tr[normalize-space() and count(*)>1][1]/*[normalize-space()][position()>1][last()]", $root, true, "/^{$this->patterns['time']}/")
                // it-895361516.eml
                ?? $this->http->FindSingleNode("following::table[normalize-space() and count(*)>1][1]/*[normalize-space()][position()>1][last()]", $root, true, "/^{$this->patterns['time']}/");

            if ($date && $timeDep) {
                $s->departure()->date(strtotime($timeDep, $date))->noCode();
            }

            if ($date && $timeArr) {
                $s->arrival()->date(strtotime($timeArr, $date))->noCode();
            }

            /* seats */

            $travellersRows = $this->http->XPath->query("//tr[ *[1][{$this->eq($this->t('Name'), "translate(.,':','')")}] and *[2][{$this->eq($this->t('Seats'), "translate(.,':','')")}] ]/following::table[1]/descendant::tr[ count(*)=3 and *[1][normalize-space()] ]");

            // it-895346898.eml
            if ($travellersRows->length === 0) {
                $travellersRows = $this->http->XPath->query("//tr[ *[1][{$this->eq($this->t('Name'), "translate(.,':','')")}] ]/following::table[1]/descendant::tr");
            }

            foreach ($travellersRows as $tRow) {
                $passengerName = $this->normalizeTraveller($this->http->FindSingleNode("*[1]", $tRow, true, "/^(?:{$this->patterns['travellerName']}|{$this->patterns['travellerName2']})$/u"));

                if ($passengerName && !in_array($passengerName, $this->travellers)) {
                    $this->travellers[] = $passengerName;
                }

                $seats = $this->http->FindNodes("*[2]/descendant::td", $tRow);

                if (count($seats) === $segments->length && preg_match("/^\d+[A-Z]$/", $seats[$i])) {
                    $s->extra()->seat($seats[$i], false, false, $passengerName);
                }
            }
        }

        if (count($this->travellers) > 0) {
            $f->general()->travellers($this->travellers, true);
        }

        /* price */

        $xpathTotalPrice = "count(*[normalize-space()])=3 and *[normalize-space()][1][{$this->eq($this->t('Total'), "translate(.,':','')")}]";

        $currency = $this->http->FindSingleNode("//tr[{$xpathTotalPrice}]/*[normalize-space()][2]");
        $totalPrice = $this->http->FindSingleNode("//tr[{$xpathTotalPrice}]/*[normalize-space()][3]", null, true, '/^.*\d.*$/');

        if (preg_match('/^\d[,.’‘\'\d ]*$/u', $totalPrice, $matches)) {
            $currencyCode = preg_match('/^[A-Z]{3}$/', $currency) ? $currency : null;
            $f->price()->currency($currency)->total(PriceHelper::parse($totalPrice, $currencyCode));

            $feeRows = $this->http->XPath->query("//tr[ count(*[normalize-space()])=3 and preceding::tr[*[1][{$this->eq($this->t('Charges'), "translate(.,':','')")}] and *[3][{$this->eq($this->t('Amount'), "translate(.,':','')")}]] and following::tr[{$xpathTotalPrice}] ]");

            foreach ($feeRows as $feeRow) {
                $feeCurrency = $this->http->FindSingleNode('*[normalize-space()][2]', $feeRow);
                $feeAmountVal = $this->http->FindSingleNode('*[normalize-space()][3]', $feeRow, true, '/^(.*?\d.*?)\s*(?:\(|$)/');

                if ($feeCurrency !== $currency) {
                    continue;
                }

                if (preg_match('/^\d[,.’‘\'\d ]*$/u', $feeAmountVal)) {
                    $feeName = $this->http->FindSingleNode('*[normalize-space()][1]', $feeRow, true, '/^(.+?)[\s:：]*$/u');
                    $feeAmount = PriceHelper::parse($feeAmountVal, $currencyCode);

                    if (preg_match("/^{$this->opt($this->t('Fare'))}$/i", $feeName) && $f->getPrice()->getCost() === null) {
                        $f->price()->cost($feeAmount);
                    } else {
                        $f->price()->fee($feeName, $feeAmount);
                    }
                }
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
        return count(self::$dictionary);
    }

    private function findSegments(): \DOMNodeList
    {
        $noEmptyXpath = 'string-length(normalize-space())>1';
        $flightArrowLogoXpath = "{$this->eq('Flightarrow', '@alt')} or {$this->contains('/grflightd.', '@src')}";

        // alternative XPath in case of next layout changes
        // $segments = $this->http->XPath->query("//text()[contains(translate(.,'0123456789', 'dddddddddd'), 'dd:dd')]/ancestor::tr[1]/preceding::text()[$noEmptyXpath][1]/ancestor::*[count(*)=3][1][//img[$flightArrowLogoXpath]]");

        $segments = $this->http->XPath->query("//tr[ count(*)=3 and *[1][$noEmptyXpath] and *[2][descendant::img[$flightArrowLogoXpath] and not($noEmptyXpath)] and *[3][$noEmptyXpath] ]");

        if ($segments->length === 0) {
            $segments = $this->http->XPath->query("//table[ count(*)=3 and *[1][$noEmptyXpath] and *[2][descendant::img[$flightArrowLogoXpath] and not($noEmptyXpath)] and *[3][$noEmptyXpath] ]");
        }

        return $segments;
    }

    private function assignLang(): bool
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang)) {
                continue;
            }

            if (!empty($phrases['confNumber']) && $this->http->XPath->query("//*[{$this->contains($phrases['confNumber'])}]")->length > 0
                || !empty($phrases['Seats']) && $this->http->XPath->query("//*[{$this->starts($phrases['Seats'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return $phrase;
        }

        if ($lang === '') {
            $lang = $this->lang;
        }

        if (empty(self::$dictionary[$lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$lang][$phrase];
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
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    private function normalizeTraveller(?string $s): ?string
    {
        if (empty($s)) {
            return null;
        }

        $namePrefixes = '(?:MASTER|MSTR|MISS|MRS|MR|MS|DR)';

        return preg_replace([
            "/^(.+?[[:upper:]]){$namePrefixes}$/su",
            "/^(.{2,}?)\s+(?:{$namePrefixes}[.\s]*)+$/is",
            "/^(?:{$namePrefixes}[.\s]+)+(.{2,})$/is",
            '/^([^\/]+?)(?:\s*[\/]+\s*)+([^\/]+)$/',
        ], [
            '$1',
            '$1',
            '$1',
            '$2 $1',
        ], $s);
    }
}
