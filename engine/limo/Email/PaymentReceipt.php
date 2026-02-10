<?php

namespace AwardWallet\Engine\limo\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;

class PaymentReceipt extends \TAccountChecker
{
    public $mailFiles = "limo/it-138020097.eml, limo/it-140343358.eml, limo/it-140546383.eml, limo/it-895218690.eml, limo/it-892363129.eml";

    public $lang = 'en';
    public static $dictionary = [
        'en' => [
            //            'Reservation Confirmation #' => 'Reservation Confirmation #',
            'Passenger' => ['Customer', 'Passenger'],
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return array_key_exists('subject', $headers)
            && preg_match("/Payment\s+Receipt\s*\[\s*For\s+Conf#\s*\d+\s*\]/i", $headers['subject']) > 0;
    }

    private function detectHtml(): bool
    {
        if ($this->http->XPath->query("//*[{$this->eq('Routing Information', "translate(.,':','')")}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//img[{$this->contains(['.mylimobiz.com', 'mylimowebsite.com'], '@src')}]")->length === 0) {
            return false;
        }

        return $this->detectHtml();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectHtml()) {
            $this->parseEmailHtml($email);
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

    private function parseEmailHtml(Email $email): void
    {
        $this->logger->debug(__FUNCTION__ . '()');
        $t = $email->add()->transfer();

        // General
        $t->general()
            ->confirmation($this->nextTd($this->t("Trip Confirmation#"), "/\b\d{5,}\b/"))
            ->traveller(preg_replace('/\s*\/\/.*/', '', $this->nextTd($this->t('Passenger'))), true)
        ;

        $startDate = $this->normalizeDate($this->nextTd($this->t("Trip Date & Time")));

        $duration = $this->http->FindSingleNode("//text()[{$this->starts($this->t("Per Hour"))}]", null, true, "/{$this->opt($this->t("Per Hour"))}.*\s+(\d+[\d.]*) x \d+[\d ,.]*$/");

        $routingInfo = $this->getField($this->t('Routing Information'));
        $this->logger->info('Routing Information:');
        $this->logger->debug($routingInfo);
        $rowPrefixes = ['Pick-up Location', 'Drop-off Location', 'Wait', 'Stop'];
        $locations = $this->splitText($routingInfo, "/^([ ]*{$this->opt($rowPrefixes)}[ ]*:)/m", true);

        foreach ($locations as $i => $row) {
            if (preg_match("/^(?:[ ]*{$this->opt($rowPrefixes)}[ ]*[:]+)?\s*AS DIRECTED/i", $row)
                || preg_match("/^\s*(?:Wait|Stop)[ ]*:/i", $row)
            ) {
                unset($locations[$i]);

                continue;
            }
            $locations[$i] = preg_replace(["/\s+Notes:[\s\S]+/i", '/[\s,;!]+$/'], '', $row);
        }
        $locations = array_values($locations);

        foreach ($locations as $i => $row) {
            if (preg_match("/^\s*(?:Pick-up Location|Drop-off Location)[ ]*:/i", $row)
                && $i !== 0 && $i !== count($locations) - 1
            ) {
                unset($locations[$i]);

                continue;
            }
        }
        $locations = array_values($locations);

        if (!empty($startDate)
            && count($locations) === 1 && preg_match("/^\s*Pick-up Location[ ]*:/i", $locations[0])
        ) {
            $email->removeItinerary($t);
            $email->setIsJunk(true, 'empty drop-off location');

            return;
        }

        foreach ($locations as $i => $row) {
            if ($i == count($locations) - 1) {
                break;
            }

            $s = $t->addSegment();
            $row = preg_replace("/^[ ]*{$this->opt($rowPrefixes)}[ ]*[:]+\s*/i", '', $row);

            // Departure
            if (preg_match("/^([A-Z]{3})\s*(?:,|$)/", $row, $m)) {
                $s->departure()
                    ->code($m[1]);
            } else {
                $s->departure()
                    ->address(preg_replace("/ - Ph:[\d \(\)\-\+]+$/", '', $row))
                ;
            }

            if ($i == 0) {
                $s->departure()
                    ->date($startDate);
            } else {
                $s->departure()
                    ->noDate();
            }

            // Arrival
            $rowNext = array_key_exists($i + 1, $locations) ? preg_replace("/^[ ]*{$this->opt($rowPrefixes)}[ ]*[:]+\s*/i", '', $locations[$i + 1]) : '';

            if (preg_match("/^([A-Z]{3})\s*(?:,|$)/", $rowNext, $m)) {
                $s->arrival()
                    ->code($m[1]);
            } else {
                $s->arrival()
                    ->address(preg_replace("/ - Ph:[\d \(\)\-\+]+$/", '', $rowNext));
            }

            if (($i + 1 == count($locations) - 1) && !empty($duration) && $startDate) {
                $s->arrival()
                    ->date(strtotime("+" . ((int) (str_replace(',', '.', $duration) * 60.0)) . ' minutes', $startDate));
            } else {
                $s->arrival()
                    ->noDate();
            }
        }

        // Price

        $total = $this->nextTd($this->t("Reservation Total"));

        if (preg_match("#^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$#", $total, $m)
            || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$#", $total, $m)
        ) {
            $t->price()
                ->total(PriceHelper::parse($m['amount']))
                ->currency($m['currency'])
            ;
        }
    }

    private function nextTd($name, $regexp = null): ?string
    {
        return $this->http->FindSingleNode("//td[not(.//tr[normalize-space()]) and {$this->eq($name, "translate(.,':','')")}]/following-sibling::td[normalize-space()][1]", null, true, $regexp);
    }

    private function getField($name, $regexp = null): ?string
    {
        $textRows = [];

        $xpath = "//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($name, "translate(.,':','')")}] ]/*[normalize-space()][2]";
        $nodes = $this->http->XPath->query($xpath . "/descendant-or-self::*[ p[normalize-space()][2] ][1]/p[normalize-space()]");

        if ($nodes->length === 0) {
            $nodes = $this->http->XPath->query($xpath);
        }

        if ($nodes->length === 0) {
            return null;
        }

        foreach ($nodes as $root) {
            $textRows[] = $this->htmlToText( $this->http->FindHTMLByXpath('.', null, $root) );
        }
        $result = implode("\n", $textRows);

        if ($regexp !== null && preg_match($regexp, $result, $m) && array_key_exists(1, $m)) {
            return $m[1];
        }

        return $result;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
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

    private function eq($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'normalize-space(' . $node . ')=' . $s;
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

    private function normalizeDate(?string $date)
    {
//        $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }

        $in = [
            //            // 10/28/2021 @ 03:30 PM
            '/^\s*(\d{2}\\/\d{2}\\/\d{4})\s+@\s+(\d{1,2}:\d{2}\s*([ap]m)?)\s*$/iu',
        ];
        $out = [
            '$1, $2',
        ];

        $date = preg_replace($in, $out, $date);

        return strtotime($date);
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

    private function splitText(?string $textSource, string $pattern, bool $saveDelimiter = false): array
    {
        $result = [];
        if ($saveDelimiter) {
            $textFragments = preg_split($pattern, $textSource, -1, PREG_SPLIT_DELIM_CAPTURE);
            array_shift($textFragments);
            for ($i=0; $i < count($textFragments)-1; $i+=2)
                $result[] = $textFragments[$i] . $textFragments[$i+1];
        } else {
            $result = preg_split($pattern, $textSource);
            array_shift($result);
        }
        return $result;
    }

    private function htmlToText(?string $s, bool $brConvert = true): string
    {
        if (!is_string($s) || $s === '') {
            return '';
        }
        $s = str_replace("\r", '', $s);
        $s = preg_replace('/<!--.*?-->/s', '', $s); // comments

        if ($brConvert) {
            $s = preg_replace('/\s+/', ' ', $s);
            $s = preg_replace('/<[Bb][Rr]\b.*?\/?>/', "\n", $s); // only <br> tags
        }
        $s = preg_replace('/<[A-z][A-z\d:]*\b.*?\/?>/', '', $s); // opening tags
        $s = preg_replace('/<\/[A-z][A-z\d:]*\b[ ]*>/', '', $s); // closing tags
        $s = html_entity_decode($s);
        $s = str_replace(chr(194) . chr(160), ' ', $s); // NBSP to SPACE

        return trim($s);
    }
}
