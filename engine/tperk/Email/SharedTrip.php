<?php

namespace AwardWallet\Engine\tperk\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class SharedTrip extends \TAccountChecker
{
    public $mailFiles = "tperk/it-12618074.eml, tperk/it-910680145.eml";

    public $lang = '';

    public static $dictionary = [
        'en' => [
            // Flight
            'Flight to'    => 'Flight to',
            'Departure'    => 'Departure',
            // Hotel
            'Stay at'     => 'Stay at',
            'Check-in'    => 'Check-in',
        ],
    ];

    public $detectSubjects = [
        // en
        'shared a trip with you',
    ];

    private $year = null;

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@travelperk.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        foreach ($this->detectSubjects as $dSubject) {
            if (stripos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query('//a[contains(@href,".travelperk.com/") or contains(@href,"url.travelperk.com")]')->length === 0
            && $this->http->XPath->query('//*[contains(.,"@travelperk.com") or contains(., "TravelPerk S.L.U.")]')->length === 0
        ) {
            return false;
        }

        return $this->assignLang();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        if (!empty($date)) {
            $this->year = date('Y', $date);
        }

        $email->obtainTravelAgency();

        $this->parseFlight($email);
        $this->parseHotel($email);

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

    private function parseFlight(Email $email): void
    {
        $xpath = "//text()[{$this->starts($this->t('Flight to'))}]/ancestor::table[{$this->starts($this->t('Flight to'))}][last()]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length === 0) {
            return;
        }

        $f = $email->add()->flight();

        // General
        $f->general()
            ->noConfirmation()
            ->travellers($this->http->FindNodes("//text()[{$this->eq($this->t('Travelers'))}]/ancestor::td[1][{$this->starts($this->t('Travelers'))}]//text()[normalize-space()][not({$this->eq($this->t('Travelers'))})]"))
        ;

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            $flight = $this->http->FindSingleNode("descendant::text()[contains(., '•')][1]/ancestor::*[string-length(.) > 3][1]", $root);

            if (preg_match('/•\s*(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z])[ ]*(?<number>\d+)\s*•\s*(?<cabin>\S[^•]+)\s*$/u', $flight, $m)) {
                $s->airline()
                    ->name($m['name'])
                    ->number($m['number']);

                $s->extra()
                    ->cabin($m['cabin']);
            }

            $dateText = $this->http->FindSingleNode("preceding::text()[normalize-space()][1]", $root);

            if (!preg_match("/{$this->opt($this->t('Layover in'))}/", $dateText)) {
                $date = $this->normalizeDate($dateText);
            }

            /*  17:10
                LHR•London Heathrow
            */
            $pattern = "/^\s*(?<time>\d{1,2}:\d{2})\n(?<code>[A-Z]{3})\s*•\s*(?<name>\S.+)\s*$/";

            // Departure
            $departText = implode("\n",
                $this->http->FindNodes("(.//text()[{$this->eq($this->t('Departure'))}])[1]/ancestor::*[not(.//text()[{$this->eq($this->t('Arrival'))}])][last()]/descendant::text()[normalize-space()][position() > 1]", $root));

            if (preg_match($pattern, $departText, $m)) {
                $s->departure()
                    ->code($m['code'])
                    ->name($m['name']);

                if (!empty($date)) {
                    $s->departure()
                        ->date(strtotime($m['time'], $date));
                }
            }

            // Arrival
            $arriveText = implode("\n",
                $this->http->FindNodes("(.//text()[{$this->eq($this->t('Arrival'))}])[1]/ancestor::*[not(.//text()[{$this->eq($this->t('Departure'))}])][last()]/descendant::text()[normalize-space()][position() > 1]", $root));

            if (preg_match($pattern, $arriveText, $m)) {
                $s->arrival()
                    ->code($m['code'])
                    ->name($m['name']);

                if (!empty($date)) {
                    $s->arrival()
                        ->date(strtotime($m['time'], $date));
                }
            }

            // Extra
            $s->extra()
                ->duration($this->http->FindSingleNode("descendant::text()[normalize-space()][2]", $root, true, '/^[\dhm ]+$/'))
            ;
        }
    }

    private function parseHotel(Email $email): void
    {
        $xpath = "//text()[{$this->starts($this->t('Stay at'))}]/ancestor::table[{$this->starts($this->t('Stay at'))}][last()]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length === 0) {
            return;
        }

        foreach ($nodes as $root) {
            $h = $email->add()->hotel();

            // General
            $h->general()
                ->noConfirmation()
                ->travellers($this->http->FindNodes("//text()[{$this->eq($this->t('Travelers'))}]/ancestor::td[1][{$this->starts($this->t('Travelers'))}]//text()[normalize-space()][not({$this->eq($this->t('Travelers'))})]"))
            ;

            // Hotel
            $h->hotel()
                ->name($this->http->FindSingleNode('descendant::text()[normalize-space()][1]', $root, true,
                    "/^\s*{$this->opt($this->t('Stay at'))}\s+(.+)/"))
                ->address($this->http->FindSingleNode('descendant::text()[normalize-space()][2]', $root))
            ;

            // Booked
            /*  Sun, May 18
                From 15:00
            */
            $pattern = "/^\s*(?<date>.+)\n\D*(?<time>\d{1,2}:\d{2})\s*$/";

            $checkIn = implode("\n",
                $this->http->FindNodes("(.//text()[{$this->eq($this->t('Check-in'))}])[1]/ancestor::*[not(.//text()[{$this->eq($this->t('Check-out'))}])][last()]/descendant::text()[normalize-space()][position() > 1]", $root));

            if (preg_match($pattern, $checkIn, $m)) {
                $date = $this->normalizeDate($m['date']);

                if (!empty($date)) {
                    $h->booked()
                        ->checkIn(strtotime($m['time'], $date));
                }
            }

            $checkOut = implode("\n",
                $this->http->FindNodes("(.//text()[{$this->eq($this->t('Check-out'))}])[1]/ancestor::*[not(.//text()[{$this->eq($this->t('Check-in'))}])][last()]/descendant::text()[normalize-space()][position() > 1]", $root));

            if (preg_match($pattern, $checkOut, $m)) {
                $date = $this->normalizeDate($m['date']);

                if (!empty($date)) {
                    $h->booked()
                        ->checkOut(strtotime($m['time'], $date));
                }
            }

            // Rooms
            for ($i = 3; $i < 10; $i++) {
                $roomText = $this->http->FindSingleNode("descendant::text()[normalize-space()][{$i}]", $root);

                if (preg_match("/^\s*(\d) ?x +(\S.+)/", $roomText, $m)) {
                    for ($rt = 0; $rt < $m[1]; $rt++) {
                        $h->addRoom()
                            ->setType($m[2]);
                    }
                } else {
                    break;
                }
            }
        }
    }

    private function normalizeDate(?string $text): ?int
    {
        if (preg_match('/^(\d{1,2})\s+([[:alpha:]]+)$/u', $text, $m)) {
            // 8 Apr
            $day = $m[1];
            $month = $m[2];
            $year = '';
        } elseif (preg_match('/^([[:alpha:]]+)\s+(\d{1,2})$/u', $text, $m)) {
            // Apr 8
            $month = $m[1];
            $day = $m[2];
            $year = '';
        } elseif (preg_match('/^\s*(?:[[:alpha:]]+\.?\s*[,\s])?\s*(\d+)\s*([[:alpha:]]+)\s*(\d{4})$/u', $text, $m)) {
            // mer. 18 juin 2025
            // 16 juin 2025
            $day = $m[1];
            $month = $m[2];
            $year = $m[3];
        }

        if (isset($day, $month, $year)) {
            if (preg_match('/^\s*(\d{1,2})\s*$/', $month, $m)) {
                return $m[1] . '/' . $day . ($year ? '/' . $year : '');
            }

            if (($monthNew = MonthTranslate::translate($month, $this->lang)) !== false) {
                $month = $monthNew;
            }

            return $day . ' ' . $month . ($year ? ' ' . $year : '');
        }

        return strtotime($text);
    }

    private function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Flight to']) && !empty($dict['Departure'])
                && $this->http->XPath->query("//*[{$this->starts($dict['Flight to'])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->eq($dict['Departure'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }

            if (!empty($dict['Stay at']) && !empty($dict['Check-in'])
                && $this->http->XPath->query("//*[{$this->starts($dict['Stay at'])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->eq($dict['Check-in'])}]")->length > 0
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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function currency($s)
    {
        $sym = [
            'US$'=> 'USD',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3})(?:$|\s)#", $s)) {
            return $code;
        }
        $s = trim($s);

        foreach ($sym as $f=> $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return $s;
    }
}
