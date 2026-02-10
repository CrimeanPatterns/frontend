<?php

namespace AwardWallet\Engine\british\Email;

use AwardWallet\Schema\Parser\Email\Email;

class YourSeatsReceipt extends \TAccountChecker
{
    public $mailFiles = "british/it-898809343.eml, british/it-904580936.eml";

    public $lang;
    public static $dictionary = [
        'en' => [
            'Thank you for booking seats' => 'Thank you for booking seats',
            'Booking reference:'          => 'Booking reference:',
            'Seats'                       => 'Seats',
        ],
    ];

    private $detectFrom = "britishairways@crm.ba.com";
    private $detectSubject = [
        // en
        'Your seats receipt',
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]crm\.ba\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//a/@href[{$this->contains(['.ba.com'])}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['with British Airways'])}]")->length === 0
        ) {
            return false;
        }
        // detect Format
        foreach (self::$dictionary as $dict) {
            if (!empty($dict['Thank you for booking seats']) && $this->http->XPath->query("//*[{$this->contains($dict['Thank you for booking seats'])}]")->length > 0
                && !empty($dict['Seats']) && $this->http->XPath->query("//*[{$this->eq($dict['Seats'])}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }
        $this->parseEmailHtml($email);

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

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["Booking reference:"]) && !empty($dict["Seats"])) {
                if ($this->http->XPath->query("//*[{$this->contains($dict['Booking reference:'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($dict['Seats'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function parseEmailHtml(Email $email)
    {
        $f = $email->add()->flight();

        // General
        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking reference:'))}]/following::text()[normalize-space()][1]",
                null, true, "/^\s*([A-Z\d]{5,7})\s*$/"));

        $xpath = "//*[count(.//text()[normalize-space()]) = 3][.//text()[{$this->eq($this->t('Flight'))}]][.//text()[{$this->eq($this->t('Passenger Name'))}]]" .
            "/following::text()[normalize-space()][1]/ancestor::*[not(.//text()[{$this->eq($this->t('Passenger Name'))}])][last()]";
        $nodes = $this->http->XPath->query($xpath);

        $travellers = [];

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            $date = null;
            $flightInfo = implode("\n", $this->http->FindNodes("descendant::tr[not(.//tr)][1]//text()[normalize-space()]", $root));

            if (preg_match("/\n(?<date>.+)\s*,\s*.+ (?<al>[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(?<fn>\d{1,4})\s*$/", $flightInfo, $m)) {
                $s->airline()
                    ->name($m['al'])
                    ->number($m['fn']);
                $date = $m['date'];
            }

            $route = $this->http->FindSingleNode("preceding::text()[normalize-space()][4]", $root);

            if (preg_match("/^\s*(.+?)\s+-\s+(.+?)\s*$/", $route, $m)) {
                $s->departure()
                    ->name($m[1])
                    ->noCode()
                    ->noDate()
                ;

                if (!empty($date)) {
                    $s->departure()
                        ->day(strtotime($date));
                }
                $s->arrival()
                    ->name($m[2])
                    ->noCode()
                    ->noDate()
                ;
            }

            $seatsNodes = $this->http->XPath->query("descendant::tr[not(.//tr)][position() > 1]", $root);

            foreach ($seatsNodes as $sRoot) {
                $values = $this->http->FindNodes("*", $sRoot);

                if (count($values) === 2) {
                    $travellers[] = $values[0];

                    if (preg_match("/^\s*(\d{1,3}[A-Z])\s*$/", $values[1])) {
                        $s->extra()
                            ->seat(trim($values[1]), true, true, $values[0]);
                    }
                }
            }
        }

        $f->general()
            ->travellers(array_unique($travellers), true);

        return true;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    // additional methods
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
}
