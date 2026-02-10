<?php

namespace AwardWallet\Engine\sj\Email;

use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Engine\MonthTranslate;
use PlancakeEmailParser;

class BookingConfirmation extends \TAccountChecker
{
    public $mailFiles = "sj/it-723182154.eml, sj/it-904205604.eml, sj/it-903631346-sv.eml";
    public $subjects = [
        'Bokningsbekräftelse', // sv
        'Booking Confirmation', // en
    ];

    public $lang = '';

    public static $dictionary = [
        'sv' => [
            'Booked by:' => 'Bokat av:',
            'Booking number:' => 'Bokningsnummer:',
            'traveller' => ['resenärer', 'Resenär'],
            'train' => 'tåg',
            'bus' => 'expressbuss',
            'class' => 'klass',
        ],
        'en' => [
            // 'Booked by:' => '',
            'Booking number:' => 'Booking number:',
            'traveller' => ['traveller', 'Traveller'],
            // 'train' => '',
            // 'bus' => '',
            // 'class' => '',
        ],
    ];

    private $xpath = [
        'time' => 'contains(translate(.,"0123456789 ","∆∆∆∆∆∆∆∆∆∆"),"∆:∆∆")',
    ];

    private $patterns = [
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $subject) {
            if (is_string($subject) && array_key_exists('subject', $headers) && stripos($headers['subject'], $subject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $href = ['.sj.se/', 'www.sj.se'];

        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"Copyright © SJ AB")]')->length === 0
        ) {
            return false;
        }

        return $this->findSegments()->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]info\.sj\.se$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
        }
        $email->setType('BookingConfirmation' . ucfirst($this->lang));

        $this->parseHtml($email);
        return $email;
    }

    private function findSegments(): \DOMNodeList
    {
        return $this->http->XPath->query("//*[ count(tr)=3 and tr[normalize-space()][1][{$this->xpath['time']}] and tr[normalize-space()][last()][{$this->xpath['time']}] ]");
    }

    private function parseHtml(Email $email): void
    {
        $t = $email->add()->train();
        $b = $email->add()->bus();

        $confirmation = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Booked by:'))}]/following::text()[{$this->starts($this->t('Booking number:'))}]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Booking number:'))}[:\s]*([A-Z\d]{5,35})$/");

        $travellers = array_unique($this->http->FindNodes("//text()[{$this->contains($this->t('traveller'))}][1]/ancestor::tr[1]/following::table[1]/descendant::text()[normalize-space()]/ancestor::tr[1]/descendant::text()[normalize-space()][1]", null, "/^{$this->patterns['travellerName']}$/u"));

        $t->general()->confirmation($confirmation)->travellers($travellers);
        $b->general()->confirmation($confirmation)->travellers($travellers);

        $segments = $this->findSegments();

        foreach ($segments as $root) {
            $segType = 'train';
            $date = null;

            $preRoots = $this->http->XPath->query("preceding::text()[normalize-space()][1]", $root);
            $preRoot = $preRoots->length > 0 ? $preRoots->item(0) : null;

            while ($preRoot) {
                $dateVal = $this->http->FindSingleNode('.', $preRoot);

                if (preg_match("/^[-[:alpha:]]+[,.\s]*(\d{1,2}[,.\s]*[[:alpha:]]+[,.\s]*\d{4})$/u", $dateVal, $matches)) {
                    $date = strtotime($this->normalizeDate($matches[1]));

                    break;
                }

                $preRoots = $this->http->XPath->query("preceding::text()[normalize-space()][1]", $preRoot);
                $preRoot = $preRoots->length > 0 ? $preRoots->item(0) : null;
            }

            $trainInfo = $this->http->FindSingleNode("tr[normalize-space()][2]/descendant::*[ (normalize-space() or .//img) and tr[2] ][1]/tr[normalize-space()][1]", $root);

            $number = $busType = null;

            if (preg_match("/(?:^|,\s*)({$this->opt($this->t('bus'))})[-\s]*(\d+)(?:\s*,|$)/i", $trainInfo, $m)) {
                $segType = 'bus'; // it-903631346-sv.eml
                $number = $m[2];
                $busType = $m[1];
            } elseif (preg_match("/(?:^|,\s*){$this->opt($this->t('train'))}[-\s]*(\d+)(?:\s*,|$)/i", $trainInfo, $m)) {
                $segType = 'train';
                $number = $m[1];
            }

            $s = $segType === 'bus' ? $b->addSegment() : $t->addSegment();

            $s->extra()->number($number)->type($busType, false, true);

            if (preg_match("/^(.+?)\s*,\s*{$this->opt($this->t('train'))}[-\s]*\d/i", $trainInfo, $m)) {
                $s->extra()->service($m[1]);
            }

            if (preg_match("/(?:^|,\s*)(\d+[-\s]*{$this->opt($this->t('class'))}[^,]*?)(?:\s*,|$)/i", $trainInfo, $m)) {
                $s->extra()->cabin($m[1]);
            }

            $depInfo = $this->http->FindSingleNode("tr[normalize-space()][1]", $root);

            if (preg_match("/^(?<time>{$this->patterns['time']})[,\s]+(?<name>[^,\s].+)$/i", $depInfo, $m)) {
                $s->departure()
                    ->name($m['name'] . ', Europe')
                    ->date(strtotime($m['time'], $date));
            }

            $arrInfo = $this->http->FindSingleNode("tr[normalize-space()][3]", $root);

            if (preg_match("/^(?<time>{$this->patterns['time']})[,\s]+(?<name>[^,\s].+)$/i", $arrInfo, $m)) {
                $s->arrival()
                    ->name($m['name'] . ', Europe')
                    ->date(strtotime($m['time'], $date));
            }
        }

        if (count($t->getSegments()) === 0) {
            $email->removeItinerary($t);
        }

        if (count($b->getSegments()) === 0) {
            $email->removeItinerary($b);
        }
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function assignLang(): bool
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['Booking number:']) ) {
                continue;
            }
            if ($this->http->XPath->query("//*[{$this->contains($phrases['Booking number:'])}]")->length > 0) {
                $this->lang = $lang;
                return true;
            }
        }
        return false;
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

    private function starts($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
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

    /**
     * Dependencies `use AwardWallet\Engine\MonthTranslate` and `$this->lang`
     * @param string|null $text Unformatted string with date
     * @return string|null
     */
    private function normalizeDate(?string $text): ?string
    {
        if ( preg_match('/^(\d{1,2})[-,.\s]*([[:alpha:]]+)[-,.\s]*(\d{4})$/u', $text, $m) ) {
            // 5 maj 2025
            $day = $m[1];
            $month = $m[2];
            $year = $m[3];
        }
        if ( isset($day, $month, $year) ) {
            if ( preg_match('/^\s*(\d{1,2})\s*$/', $month, $m) )
                return $m[1] . '/' . $day . ($year ? '/' . $year : '');
            if ( ($monthNew = MonthTranslate::translate($month, $this->lang)) !== false )
                $month = $monthNew;
            return $day . ' ' . $month . ($year ? ' ' . $year : '');
        }
        return null;
    }
}
