<?php

namespace AwardWallet\Engine\priceline\Email;

use AwardWallet\Schema\Parser\Email\Email;

class RentalCarFlightReceiptJunk extends \TAccountChecker
{
    public $mailFiles = "priceline/it-653693977.eml, priceline/it-654319060.eml, priceline/it-896694392.eml";

    public $lang = '';
    public static $dictionary = [
        'en' => [
            'header'          => 'Your receipt from Priceline',
            'subHeader'       => ['Your rental car on', 'Your flight on'],
            'subHeaderCar'    => ['Your rental car on'],
            'subHeaderFlight' => ['Your flight on'],
            'otaConfNumber'   => ['Priceline Trip Number', 'Priceline trip number'],
            'totalCost'       => ['Total Cost:', 'Total Charges:'],
            'startTime'       => ['• Pick-up:', '• Departure:'],
            'paymentSummary'  => 'Payment Summary',
        ],
    ];

    private $detectFrom = "info@travel.priceline.com";
    private $detectSubject = [
        // en
        'Your rental car receipt from Priceline (Trip#',
        'Your flight receipt from Priceline (Trip#',
    ];

    private $patterns = [
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]priceline\.com\b/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ((!array_key_exists('from', $headers) || stripos($headers['from'], $this->detectFrom) === false)
            && (!array_key_exists('subject', $headers) || strpos($headers['subject'], 'Priceline') === false)
        ) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (is_string($dSubject) && array_key_exists('subject', $headers) && stripos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        $href = ['.priceline.com/', 'www.priceline.com'];

        if ($this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['priceline.com LLC'])}]")->length === 0
        ) {
            return false;
        }

        // detect language and format
        return $this->assignLang() && $this->findRoots()->length > 0;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();
        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
            return $email;
        }
        $email->setType('RentalCarFlightReceiptJunk' . ucfirst($this->lang));

        $this->parseEmailHtml($email);
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

    private function removeGarbage(string $expression): void
    {
        $nodesToStip = $this->http->XPath->query($expression);

        foreach ($nodesToStip as $nodeToStip) {
            if (empty($nodeToStip) || empty($nodeToStip->parentNode)) {
                continue;
            }

            $nodeToStip->parentNode->removeChild($nodeToStip);
        }
    }

    private function findRoots(): \DOMNodeList
    {
        $this->removeGarbage("//div[not(.//div[normalize-space()]) and {$this->starts($this->t('totalCost'))}] | //*[normalize-space()='Get help with your trip, discover local experiences, and more! Chat with Penny']");
        return $this->http->XPath->query("//div[ not(.//div[normalize-space()]) and {$this->starts($this->t('subHeader'))} and following::div[not(.//div[normalize-space()]) and normalize-space()][position()=5 or position()=8][{$this->eq($this->t('paymentSummary'))}] ]");
    }

    private function parseEmailHtml(Email $email): void
    {
        $roots = $this->findRoots();

        if ($roots->length !== 1) {
            $this->logger->debug('Root-node not found!');

            return;
        }
        $root = $roots->item(0);

        $xpath = "following::div[not(.//div[normalize-space()]) and normalize-space()]";

        if (preg_match("/^\s*{$this->opt($this->t('subHeader'))}\s+[-\w,. ]*\b20\d{2}\b[-\w,. ]*\s+is confirmed\s*$/iu", $this->http->FindSingleNode('.', $root))
            && $this->http->XPath->query($xpath . "[1][{$this->starts($this->t('otaConfNumber'))}]", $root)->length > 0
            && $this->http->XPath->query($xpath . "[2]/preceding::img[normalize-space(@src)][1]/@src[contains(.,'/cars.png') or contains(.,'/flights_blue.png')]", $root)->length > 0
            && $this->http->FindSingleNode($xpath . "[3][{$this->contains($this->t('startTime'))}]", $root, true, "/^\s*\w+\s+\w+(?:\s*-\s*\w+\s+\w+)?\s*{$this->opt($this->t('startTime'))}\s*{$this->patterns['time']}\s*$/iu") !== null
            && $this->http->XPath->query($xpath . "[4][{$this->starts($this->t('Confirmation #:'))}]", $root)->length > 0
        ) {
            $reason = null;

            if ($this->http->XPath->query($xpath . "[2]/preceding::img[normalize-space(@src)][1]/@src[contains(.,'/cars.png')]", $root)->length > 0
                || preg_match("/^\s*{$this->opt($this->t('subHeaderCar'))}\s/i", $this->http->FindSingleNode('.', $root))
            ) {
                $reason = 'Rental car reservation without pickUpLocation and dropOffLocation';
            } elseif ($this->http->XPath->query($xpath . "[2]/preceding::img[normalize-space(@src)][1]/@src[contains(.,'/flights_blue.png')]", $root)->length > 0
                || preg_match("/^\s*{$this->opt($this->t('subHeaderFlight'))}\s/i", $this->http->FindSingleNode('.', $root))
            ) {
                $reason = 'Flight reservation without airlineName, flightNumber and arrTime';
            }

            $email->setIsJunk(true, $reason);
        }
    }

    private function assignLang(): bool
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['header']) ) {
                continue;
            }
            if ($this->http->XPath->query("//*[{$this->eq($phrases['header'])}]")->length > 0) {
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
}
