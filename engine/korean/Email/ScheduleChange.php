<?php

namespace AwardWallet\Engine\korean\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ScheduleChange extends \TAccountChecker
{
    public $mailFiles = "korean/it-638892505.eml, korean/it-915109417.eml";
    public $subjects = [
        'Schedule Change Notice', 'Your Flight Has Been Delayed',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'original' => ['Original', 'Previous Itinerary'],
            'changed' => ['Changed', 'New Itinerary'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if ((!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true)
            && (!array_key_exists('subject', $headers) || strpos($headers['subject'], '[Korean Air]') === false)
        ) {
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
        $href = ['.koreanair.com/', 'www.koreanair.com'];

        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
        ) {
            return false;
        }

        return $this->http->XPath->query("//tr[{$this->starts($this->t('original'))} and {$this->contains($this->t('changed'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]koreanair\.com$/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->ParseFlight($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseFlight(Email $email): void
    {
        $f = $email->add()->flight();

        $confText = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Booking Reference')]/ancestor::tr[1]");

        if (preg_match("/(?<confTitle>Booking Reference)[\s\:]+(?<otaConf>[\d\-]+)\s*\(\s*(?<conf>[A-Z\d]{5,8})\s*\)/", $confText, $m)
            || preg_match("/^(?<confTitle>Booking Reference)[\s\:]+(?<conf>[A-Z\d]{5,8})$/", $confText, $m)
        ) {
            if (!empty($m['otaConf'])) {
                $email->ota()->confirmation($m['otaConf']);
            }
            $f->general()->confirmation($m['conf'], $m['confTitle']);
        }

        $segments = $this->http->XPath->query("//tr[{$this->starts($this->t('original'))} and {$this->contains($this->t('changed'))}]/following::table[normalize-space() and not(contains(normalize-space(),'Flight Cancel'))][2]");

        foreach ($segments as $root) {
            $s = $f->addSegment();

            $airInfo = implode("\n", $this->http->FindNodes("./descendant::img[contains(@src, 'flight')]/ancestor::tr[1]/descendant::text()[normalize-space()]", $root));
            $this->logger->info('Flight:');
            $this->logger->debug($airInfo);

            if (preg_match("/^(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d+)\n(?<aircraft>.+)\n(?<cabin>.+)$/", $airInfo, $m)
                || preg_match("/^(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d+)\nOperated by\n(?<operator>.+)\n(?<cabin>.+)$/", $airInfo, $m)
                || preg_match("/^(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d+)$/", $airInfo, $m)
            ) {
                $s->airline()
                    ->name($m['aName'])
                    ->number($m['fNumber']);

                if (!empty($m['operator'])) {
                    $s->airline()
                        ->operator($m['operator']);
                }

                if (!empty($m['aircraft'])) {
                    $s->extra()
                        ->aircraft($m['aircraft']);
                }

                if (!empty($m['cabin'])) {
                    $s->extra()->cabin($m['cabin']);
                }
            }

            $depInfo = implode("\n", $this->http->FindNodes("./descendant::img[contains(@src, 'flight')]/preceding::tr[1]/descendant::text()[normalize-space()]", $root));

            if (preg_match("/^(?<depCode>[A-Z]{3})\n(?<depName>.+)\n(?<year>\d{4})\.(?<month>\d{1,2})\.(?<day>\d{1,2})\s*\(\D*\)\s*(?<time>[\d\:]+)$/", $depInfo, $m)) {
                $s->departure()
                    ->name($m['depName'])
                    ->code($m['depCode'])
                    ->date(strtotime($m['day'] . '.' . $m['month'] . '.' . $m['year'] . ', ' . $m['time']));
            }

            $arrInfo = implode("\n", $this->http->FindNodes("./descendant::img[contains(@src, 'flight')]/following::tr[1]/descendant::text()[normalize-space()]", $root));

            if (preg_match("/^(?<arrCode>[A-Z]{3})\n(?<arrName>.+)\n(?<year>\d{4})\.(?<month>\d{1,2})\.(?<day>\d{1,2})\s*\(\D*\)\s*(?<time>[\d\:]+)$/", $arrInfo, $m)) {
                $s->arrival()
                    ->name($m['arrName'])
                    ->code($m['arrCode'])
                    ->date(strtotime($m['day'] . '.' . $m['month'] . '.' . $m['year'] . ', ' . $m['time']));
            }
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
}
