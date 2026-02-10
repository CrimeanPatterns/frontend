<?php

namespace AwardWallet\Engine\flixbus\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Ticket extends \TAccountChecker
{
    public $mailFiles = "flixbus/it-78721941.eml, flixbus/it-880642144.eml, flixbus/it-884830308.eml, flixbus/it-885204300.eml, flixbus/it-885692612.eml";

    public $detectSubjects = [
        'de' => [
            'erwartet Dich! Hier ist Dein E-Ticket und wichtige Infos',
        ],
        'en' => [
            'awaits you! Here\'s your e-ticket and important info about your trip!',
        ],
        'nl' => [
            'wacht op jou! Hier is je ticket en belangrijke informatie over je reis!',
        ],
        'pt' => [
            'espera por você! Aqui estão seu e-ticket e informações importantes sobre sua viagem.',
        ],
    ];

    public $lang = '';

    public static $dictionary = [
        'de' => [
            'confNumber'           => ['Buchungsnummer:'],
            'Get your e-ticket'    => ['Hol Dir Dein E-Ticket'],
            'awaits you!'          => ['erwartet Dich!'],
            '>>Map'                => ['>>Karte'],
        ],
        'en' => [
            'confNumber'        => ['Booking No.:'],
            'Get your e-ticket' => ['Get your e-ticket'],
        ],
        'nl' => [
            'confNumber'           => ['Boekingsnummer:'],
            'Get your e-ticket'    => ['Koop je e-ticket'],
            'awaits you!'          => ['wacht op jou!'],
        ],
        'pt' => [
            'confNumber'           => ['Número da reserva:'],
            'Get your e-ticket'    => ['Reserve sua passagem'],
            'awaits you!'          => ['espera por você!'],
            '>>Map'                => ['>>Mapa'],
        ],
    ];

    private $year = null;

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]email\.flixbus\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers['from'], 'flixbus.com') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubject) {
                if (stripos($headers["subject"], $dSubject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $this->assignLang();

        // detect Provider
        if (
            $this->http->XPath->query("//text()[{$this->contains(['FlixBus', 'FlixTrain', 'FlixMobility', 'Flix SE'])}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains('flixbus.com')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if (
            $this->http->XPath->query("//text()[{$this->contains($this->t('awaits you!'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('>>Map'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }

        $this->parseBus($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function parseBus(Email $email)
    {
        $patterns = [
            'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.
        ];

        $t = null;
        $b = null;

        // collect reservation confirmation
        $confirmation = $this->http->FindSingleNode("//text()[{$this->starts($this->t('confNumber'))}]");

        if (preg_match("/^(?<confDesc>{$this->opt($this->t('confNumber'))})[:\s]*(?<confNumber>[\d\.]+)$/", $confirmation, $m)) {
            $confDesc = rtrim($m['confDesc'], ': ');
            $confNumber = $m['confNumber'];
        }

        $xpath = "//text()[{$this->starts($this->t('confNumber'))}]/ancestor::tr[1]/following-sibling::tr[normalize-space()]";
        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $root) {
            $segmentText = implode("\n", $this->http->FindNodes("./descendant::text()[normalize-space()]", $root));

            // segmentText example
            // Jul 18, 2024   |   18/07/2024
            // Jul 18, 19:30  |   18/07 19:30
            // Boston (South Station)
            // >>Map
            // FlixBus 2610
            // Jul 18, 23:55  |   18/07 23:55
            // New York Midtown (31st St & 8th Ave)
            // >>Map

            // collect department info
            if (preg_match("/^.+?(?<year>\d{4})\n(?<date>.+?)[\,\s]+(?<time>{$patterns['time']})\n(?<name>.+)\n{$this->opt($this->t('>>Map'))}/u", $segmentText, $m)) {
                $this->year = $m['year'];

                $depDate = strtotime($m['time'], $this->normalizeDate($m['date']));
                $rawDepName = $m['name'];
                $depName = $this->normalizeNameStation($m['name']);
            }

            // collect arrival info
            if (preg_match("/{$this->opt($this->t('>>Map'))}\n(?<number>[\w\s]+)\n(?<date>.+?)[\,\s]+(?<time>{$patterns['time']})\n(?<name>.+)\n{$this->opt($this->t('>>Map'))}/u", $segmentText, $m)) {
                $number = $m['number'];
                $arrDate = strtotime($m['time'], $this->normalizeDate($m['date']));
                $rawArrName = $m['name'];
                $arrName = $this->normalizeNameStation($m['name']);
            }

            // detect reservation type and add reservation confirmation
            if (
                $this->http->XPath->query("//text()[{$this->contains($this->t('FlixTrain'))}]")->length > 0
                || ($this->striposAll($rawDepName, $this->t('(train)')) && $this->striposAll($rawArrName, $this->t('(train)')))
            ) {
                if ($t === null) {
                    $t = $email->add()->train();
                }
                $s = $t->addSegment();
                $t->general()->confirmation($confNumber, $confDesc);
            } else {
                if ($b === null) {
                    $b = $email->add()->bus();
                }
                $s = $b->addSegment();
                $b->general()->confirmation($confNumber, $confDesc);
            }

            $s->departure()
                ->date($depDate)
                ->name($depName);

            $s->setNumber($number);

            $s->arrival()
                ->date($arrDate)
                ->name($arrName);
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

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (
                !empty($dict['confNumber']) && !empty($dict['Get your e-ticket'])
                && $this->http->XPath->query("//text()[{$this->starts($dict['confNumber'])}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->eq($dict['Get your e-ticket'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function opt($field, $delimiter = '/')
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
            return str_replace(' ', '\s+', preg_quote($s, $delimiter));
        }, $field)) . ')';
    }

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date) || empty($this->year)) {
            return null;
        }

        $in = [
            '/^\s*(\d+)\/(\d+)\s*$/iu', // 15/11
            '/^\s*([^\d\s]+)\s+(\d+)\s*$/ui', // Jul 18
        ];
        $out = [
            "$1.$2.$this->year",
            "$2 $1 $this->year",
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = strtotime(str_replace($m[1], $en, $date));
            }
        }

        if (preg_match("#\d+\.\d+\.\d{4}#", $date, $m)) {
            return strtotime($date);
        }

        return $date;
    }

    private function normalizeNameStation(?string $s): ?string
    {
        if (preg_match("/^(?<location>[^()]{2,}?)\s*\(\s*(?<tip>[^)(]*?)\s*\)$/", $s, $m)) {
            // Prague (Main Railway Station - Parking)
            $location = $m['location'];
            $tip = $m['tip'];
        } else {
            $location = $tip = null;
        }

        if (preg_match("/^(train|main station replacement)$/i", $tip)) {
            $tip = null;
        }

        if ($location !== null && $tip === null) {
            return $location;
        }

        if ($location !== null && $tip !== null) {
            return $tip . ', ' . $location;
        }

        return $s;
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
