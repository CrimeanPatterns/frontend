<?php

namespace AwardWallet\Engine\lanai\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BookingNumberPDF extends \TAccountChecker
{
    public $mailFiles = "lanai/it-127305644.eml, lanai/it-127308854.eml, lanai/it-892463623.eml, lanai/it-895494098.eml, lanai/it-896271412.eml";

    public $subject;
    public $detectSubject = [
        ['- Reference', '(Departing'],
        ['- Booking Number', '(Departing'],
    ];

    public $pdfNamePattern = ".*pdf";

    public $isDetectPdf = false;
    public $providerCode;
    public static $providers = [
        'lanai' => [
            'code'          => 'lanai',
            'from'          => ['@lanaiair.com'],
            'uniqueSubject' => ['Lanai Air'],
            'pdfBody'       => [
                'www.lanaiair.com',
                '@lanaiair.com',
            ],
            'htmlBody' => [
                'www.lanaiair.com',
                '@lanaiair.com',
                'Welcome on board Lana’i Air',
            ],
        ],
        'airtindi' => [
            'code'          => 'airtindi',
            'from'          => ['@airtindi.com'],
            'uniqueSubject' => ['Air Tindi'],
            'pdfBody'       => [
                'www.airtindi.com',
                '@airtindi.com',
                'Air Tindi Ltd.',
            ],
            // 'htmlBody' => [
            //     'www.lanaiair.com',
            //     '@lanaiair.com',
            //     'Welcome on board Lana’i Air',
            // ],
        ],
        [
            'code'          => 'none',
            'from'          => ['@soundsair.com'],
            'uniqueSubject' => ['Sounds Air'],
            'pdfBody'       => [
                'www.soundsair.com',
                '@soundsair.com',
            ],
            'htmlBody' => [
                '@soundsair.com',
                'Air Tindi Ltd.',
                'choosing to fly with Sounds Air',
            ],
        ],
        [
            'code' => 'none',
            'from' => ['@flyalaskaseaplanes.com'],
            // 'uniqueSubject' => ['Sounds Air',],
            // 'pdfBody' => [
            //     'www.soundsair.com',
            //     '@soundsair.com',
            // ],
            'htmlBody' => [
                '@flyalaskaseaplanes.com',
                'www.flyalaskaseaplanes.com/',
            ],
        ],
        [
            'code' => 'none',
            'from' => ['@barrierair.kiwi'],
            // 'uniqueSubject' => ['Sounds Air',],
            // 'pdfBody' => [
            //     'www.soundsair.com',
            //     '@soundsair.com',
            // ],
            'htmlBody' => [
                '@barrierair.kiwi',
                'choosing to fly with Barrier Air',
            ],
        ],
    ];

    public $lang = 'en';
    public static $dictionary = [
        "en" => [
            // pdf
            'BOOKING NUMBER:'           => 'BOOKING NUMBER:',
            'DEPARTING PASSENGER NAMES' => ['DEPARTING PASSENGER NAMES', 'DEPARTING PASSENGER NAME', 'RETURNING PASSENGER NAME', 'RETURNING PASSENGER NAMES'],
            'FLIGHT:'                   => 'FLIGHT:',
            'TAXES'                     => ['TAXES', 'EXTRAS'],

            // html
            'Booking number' => 'Booking number',
            'Departing'      => 'Departing',
            'Arriving'       => 'Arriving',
        ],
    ];

    public static function getEmailProviders()
    {
        return array_diff(array_column(self::$providers, 'code'), ['none']);
    }

    public function detectEmailByHeaders(array $headers)
    {
        foreach (self::$providers as $detectProvider) {
            if (
                (!empty($detectProvider['from']) && $this->containsiText($headers['from'], $detectProvider['from']) === true)
                || (!empty($detectProvider['uniqueSubject']) && $this->containsiText($headers['subject'], $detectProvider['uniqueSubject']) === true)
            ) {
                $this->providerCode = $detectProvider['code'];

                foreach ($this->detectSubject as $dSubject) {
                    foreach ($dSubject as $ds) {
                        if (stripos($headers['subject'], $ds) === false) {
                            continue;
                        }
                    }
                }

                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text) === true) {
                return true;
            }
        }

        // html
        $detectedProvider = false;

        foreach (self::$providers as $detectProvider) {
            if (!empty($detectProvider['htmlBody']) && $this->http->XPath->query("//text()[{$this->contains($detectProvider['htmlBody'])}]")->length > 0) {
                $detectedProvider = true;
                $this->providerCode = $detectProvider['code'];

                break;
            }
        }

        if ($detectedProvider === false) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['Booking number']) && $this->http->XPath->query("//text()[{$this->starts($this->t('Booking number'))}]")->length > 0
                && !empty($dict['Departing']) && $this->http->XPath->query("//text()[{$this->starts($this->t('Departing'))}]")->length > 0
                && !empty($dict['Arriving']) && $this->http->XPath->query("//text()[{$this->starts($this->t('Arriving'))}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf($text)
    {
        $detectedProvider = false;

        foreach (self::$providers as $detectProvider) {
            if (!empty($detectProvider['pdfBody']) && $this->containsText($text, $detectProvider['pdfBody']) === true) {
                $detectedProvider = true;
                $this->providerCode = $detectProvider['code'];

                break;
            }
        }

        if ($detectedProvider === false) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['DEPARTING PASSENGER NAMES']) && $this->containsText($text, $dict['DEPARTING PASSENGER NAMES']) === true
               && !empty($dict['BOOKING NUMBER:']) && $this->containsText($text, $dict['BOOKING NUMBER:']) === true
               && !empty($dict['FLIGHT:']) && $this->containsText($text, $dict['FLIGHT:']) === true
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@\.]lanaiair\.com$/', $from) > 0;
    }

    public function ParseFlightHTML(Email $email)
    {
        $f = $email->add()->flight();

        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Booking number')]", null, true, "/{$this->opt($this->t('Booking number'))}\s*(\d{6})/"));

        $traveller = $this->re("/\s*\-\s*(\D+)\s*\)$/", $this->subject);

        if (!empty($traveller)) {
            $f->general()
                ->traveller($traveller, true);
        }

        $operator = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'operated by')]", null, true, "/{$this->opt($this->t('operated by'))}\s*(.+)/");
        $airlineName = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'Welcome on board')]", null, true, "/{$this->opt($this->t('Welcome on board'))}\s*(.+)\,/");

        $xpath = "//text()[starts-with(normalize-space(), 'Departing Flight')]";
        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            $al = $this->http->FindSingleNode(".", $root, true, "/{$this->opt($this->t('Departing Flight'))}\s*([A-Z][A-Z\d]|[A-Z\d][A-Z]) ?\d+\:/");
            $al = !empty($al) ? $al : $airlineName;

            if (!empty($al)) {
                $s->airline()
                    ->name($al);
            } else {
                $s->airline()
                    ->noName();
            }
            $s->airline()
                ->number($this->http->FindSingleNode(".", $root, true, "/{$this->opt($this->t('Departing Flight'))}\s*(?:[A-Z][A-Z\d]|[A-Z\d][A-Z])? ?(\d+)\:/"));

            if (!empty($operator)) {
                $s->airline()
                    ->operator(trim($operator, '.'));
            }

            $depText = $this->http->FindSingleNode("./following::text()[normalize-space()][1][{$this->starts($this->t('Departing'))}]", $root);

            $re = "/(?:{$this->opt($this->t('Departing'))}|{$this->opt($this->t('Arriving'))})\s+(?<name>.+?)\s*\((?<code>[A-Z]{3})\)\s*at\s*(?<time>\d{1,2}:\d{2}(?:\s*[AP]M)?)\s+\w+\s+(?<date>\d+\s*\w+\s*\d{4})\.$/";

            if (preg_match($re, $depText, $m)) {
                $s->departure()
                    ->code($m['code'])
                    ->name($m['name'])
                    ->date(strtotime($m['date'] . ' ' . $m['time']));
            }

            $arrText = $this->http->FindSingleNode("./following::text()[normalize-space()][2][{$this->starts($this->t('Arriving'))}]", $root);

            if (preg_match($re, $arrText, $m)) {
                $s->arrival()
                    ->code($m['code'])
                    ->name($m['name'])
                    ->date(strtotime($m['date'] . ' ' . $m['time']));
            }
        }
    }

    public function ParseFlightPDF(Email $email, $text)
    {
        $f = $email->add()->flight();

        $operator = trim($this->re("/{$this->opt($this->t('Operated by'))} (.+)/", $text), '.');
        $airlineName = $this->re("/All transportation of passengers and baggage for\s*(.+)\s*operated/", $text);

        // General
        $f->general()
            ->confirmation($this->re("/ {2,}{$this->opt($this->t('BOOKING NUMBER:'))} *(\d+)\n/", $text));

        if (preg_match_all("/\n *{$this->opt($this->t('DEPARTING PASSENGER NAMES'))} *(?:\(\d+\))?\n(\s*[•]\D+\n)\s*{$this->opt($this->t('CHECK IN BY'))}/", $text, $m)) {
            $pax = preg_split("/\s*[•]\s*/", trim(implode("\n", $m[1])));

            $f->general()
                ->travellers(array_unique(array_filter($pax)), true);
        }

        // Segments
        // Get Airport Codes
        $airportsCodes = [];
        /* v1: Departing Flight 212:
               Departing Honolulu (HNL) at 10:00 Tue 02 Jul 2024.
               Arriving Lanai (LNY) at 10:30 Tue 02 Jul 2024.
        */
        $airports = array_unique($this->http->FindNodes("//text()[{$this->starts($this->t('Departing'))} or {$this->starts($this->t('Arriving'))}]",
            null, "/(?:{$this->opt($this->t('Departing'))}|{$this->opt($this->t('Arriving'))})\s+(.+?\s*\([A-Z]{3}\))/"));

        foreach ($airports as $airText) {
            if (preg_match("/^(?<name>.+?)\s*\((?<code>[A-Z]{3})\)\s*$/", $airText, $m)) {
                $airportsCodes[$m['name']] = $m['code'];
            }
        }
        /* v2:  Depart                          Arrive
                01:45 PM                        02:40 PM
                Wed, 16 Apr         206         Wed, 16 Apr
                Yellowknife                     Gameti
                YZF                             YRA
        */
        foreach ($this->http->XPath->query("//text()[{$this->eq($this->t('Depart'))} or {$this->eq($this->t('Arrive'))}]/ancestor::td[count(.//text()[normalize-space()]) = 5][1]") as $cRoot) {
            $code = $this->http->FindSingleNode("descendant::text()[normalize-space()][last()]", $cRoot, null, "/^\s*([A-Z]{3})\s*$/");
            $name = $this->http->FindSingleNode("descendant::text()[normalize-space()][last() - 1]", $cRoot);

            if (!empty($code) && !empty($name)) {
                $airportsCodes[$name] = $code;
            }
        }

        $preg = "/\n(.+ {3,}{$this->opt($this->t('FLIGHT:'))}(?:.+\n+){1,3}.+ {3,}{$this->opt($this->t('CLASS:'))}.+\n)/u";

        if (preg_match_all($preg, $text, $m)) {
            foreach ($m[1] as $seg) {
                $s = $f->addSegment();
                $re = "/^\s*\d{1,2}:\d{2}(?: ?[AP]M)? {2,}(?<depName>\S.+?)[ ]{2,}->[ ]{2,}(?<arrName>\S.+?) {2,}{$this->opt($this->t('FLIGHT:'))}\s*(?<al>[A-Z][A-Z\d]|[A-Z\d][A-Z])? ?(?<flightNumber>\d{1,4})\n+" .
                    "\s*(?<depDate>.+\d{1,2}:\d{2}(?: ?[AP]M)?)[ ]{4,}(?<arrDate>.+\d{1,2}:\d{2}(?: ?[AP]M)?)[ ]{4,}{$this->opt($this->t('CLASS:'))} *(?<cabin>.+)/u";

                if (preg_match($re, $seg, $match)) {
                    $s->airline()
                        ->number($match['flightNumber']);
                    $al = !empty($match['al']) ? $match['al'] : $airlineName;

                    if (!empty($al)) {
                        $s->airline()
                            ->name($al);
                    } else {
                        $s->airline()
                            ->noName();
                    }

                    if (!empty($operator)) {
                        $s->airline()
                            ->operator($operator);
                    }

                    $s->departure()
                        ->name($match['depName'])
                        ->date(strtotime($match['depDate']));

                    if (isset($airportsCodes[$match['depName']])) {
                        $s->departure()
                            ->code($airportsCodes[$match['depName']]);
                    } else {
                        $s->departure()
                            ->noCode();
                    }

                    $s->arrival()
                        ->name($match['arrName'])
                        ->date(strtotime($match['arrDate']));

                    if (isset($airportsCodes[$match['arrName']])) {
                        $s->arrival()
                            ->code($airportsCodes[$match['arrName']]);
                    } else {
                        $s->arrival()
                            ->noCode();
                    }

                    if (preg_match("/^\s*(\S.+?)\s*\(([A-Z]{1,2})\)\s*/", $match['cabin'], $m)) {
                        $s->extra()
                            ->cabin($m[1])
                            ->bookingCode($m[2])
                        ;
                    } else {
                        $s->extra()
                            ->cabin($match['cabin']);
                    }
                }
            }
        }

        // Price
        if (preg_match("/(?:{$this->opt($this->t('All prices in'))}|{$this->opt($this->t('TOTAL PRICE:'))}|{$this->opt($this->t('BASE FARE'))})/", $text)) {
            $currency = $this->re("/{$this->opt($this->t('All prices in'))} ([A-Z]{3})[\.\)]/", $text);
            $f->price()
                ->total(PriceHelper::parse($this->re("/\n *{$this->opt($this->t('TOTAL PRICE:'))} +\D{0,4}(\d[\d., ]*?)\D{0,4}\n/", $text), $currency))
                ->currency($currency);

            $priceText = $this->re("/\n {0,10}{$this->opt($this->t('BASE FARE'))}(?: +.*)?\n([\s\S]+?)\n *{$this->opt($this->t('TOTAL PRICE:'))}/",
                $text);
            $isFare = true;
            $cost = 0.0;

            foreach (explode("\n", $priceText) as $row) {
                if (preg_match("/^ {0,10}([A-Z][A-Z\W ]+|{$this->opt($this->t('TAXES'))})$/", $row)) {
                    $isFare = false;

                    continue;
                }
                $amount = PriceHelper::parse($this->re("/ {2,}\D{0,4}(\d[\d., ]*?)\D{0,4}$/", $row), $currency);

                if ($amount === null) {
                    continue;
                }

                if ($isFare === true) {
                    $cost += $amount;
                } else {
                    $f->price()
                        ->fee($this->re("/^ {0,10}(\S.+?) {2,}/", $row), $amount);
                }
            }

            if (!empty($cost)) {
                $f->price()
                    ->cost($cost);
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text)) {
                $this->ParseFlightPDF($email, $text);
            }
        }

        if (empty($email->getItineraries())) {
            $this->subject = $parser->getSubject();
            $this->ParseFlightHTML($email);
        }

        if (empty($this->providerCode)) {
            foreach (self::$providers as $detectProvider) {
                if (
                    (!empty($detectProvider['from']) && $this->containsiText($parser->getCleanFrom(), $detectProvider['from']) === true)
                    || (!empty($detectProvider['uniqueSubject']) && $this->containsiText($parser->getSubject(), $detectProvider['uniqueSubject']) === true)
                ) {
                    $this->providerCode = $detectProvider['code'];

                    break;
                }
            }
        }

        if (!empty($this->providerCode) && $this->providerCode !== 'none') {
            $email->setProviderCode($this->providerCode);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'normalize-space(.)="' . $s . '"'; }, $field)) . ')';
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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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

    private function containsiText($text, $needle): bool
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

    private function containsText($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (strpos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && strpos($text, $needle) !== false) {
            return true;
        }

        return false;
    }
}
