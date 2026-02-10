<?php

namespace AwardWallet\Engine\priceline\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Schema\Parser\Email\Email;

class ReceiptCancelled extends \TAccountChecker
{
    public $mailFiles = "priceline/it-653884152.eml, priceline/it-653917533.eml, priceline/it-653922466.eml";

    public $detectFrom = "info@travel.priceline.com";
    public $detectSubject = [
        // en
        'Your flight has been cancelled (Trip #:', 'Your flight has been canceled (Trip #:',
        'Your rental car has been cancelled (Trip #:', 'Your rental car has been canceled (Trip #:',
        'Your hotel booking has been cancelled (Trip #:', 'Your hotel booking has been canceled (Trip #:',
    ];

    public $lang = '';
    public static $dictionary = [
        'en' => [
            'otaConfNumber'  => ['Priceline Trip Number', 'Priceline trip number'],
            'header'         => 'Your receipt from Priceline',
            'subHeader'      => ['This email confirms the cancellation of your trip.', 'This email confirms the cancelation of your trip.'],
            'paymentSummary' => 'Payment Summary',
            'purchaseDate'  => ['Purchase Date', 'Purchase date'],
        ],
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

        return $this->assignLang();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();
        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
            return $email;
        }
        $email->setType('ReceiptCancelled' . ucfirst($this->lang));

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

    private function parseEmailHtml(Email $email): void
    {
        $otaConfirmation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('otaConfNumber'), "translate(.,':','')")} and not(preceding::*[{$this->eq($this->t('paymentSummary'), "translate(.,':','')")}])]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d[-\d]{5,})\s*$/u");
        $otaConfirmationTitle = $this->http->FindSingleNode("//text()[{$this->eq($this->t('otaConfNumber'), "translate(.,':','')")} and not(preceding::*[{$this->eq($this->t('paymentSummary'), "translate(.,':','')")}])]", null, true, '/^(.+?)[\s:：]*$/u');

        if (!$otaConfirmation) {
            $otaConfirmation = $this->http->FindSingleNode("//*[ count(div[normalize-space()])=2 and div[normalize-space()][1][{$this->eq($this->t('otaConfNumber'), "translate(.,':','')")}] ]/div[normalize-space()][2]", null, true, "/^\s*(\d[-\d]{5,})\s*$/u");
            $otaConfirmationTitle = $this->http->FindSingleNode("//*[count(div[normalize-space()])=2]/div[normalize-space()][1][{$this->eq($this->t('otaConfNumber'), "translate(.,':','')")}]", null, true, '/^(.+?)[\s:：]*$/u');
        }

        $email->ota()->confirmation($otaConfirmation, $otaConfirmationTitle);

        /* CARS */

        $cNodes = $this->http->XPath->query("//img/@src[contains(.,'/cars.png')]/ancestor::div[ descendant::text()[normalize-space()][2] ][1]");

        foreach ($cNodes as $root) {
            $r = $email->add()->rental();

            $r->general()
                ->cancelled()
                ->status('Cancelled');

            $text = implode("\n", $this->http->FindNodes("descendant::text()[normalize-space()]", $root));
            $this->logger->info('General Text:');
            $this->logger->debug($text);

            if (preg_match("/\n\s*(Confirmation #)\s*[:]+\s*([-A-Z\d]{5,})\s*(?:\n|$)/", $text, $m)) {
                $r->general()->confirmation($m[2], $m[1]);
            }
            $date = strtotime($this->http->FindSingleNode("//text()[{$this->eq($this->t('purchaseDate'))}]/following::text()[normalize-space()][1]"));

            if (!empty($date)) {
                $date = strtotime('- 2 month', $date);
            }

            if (!empty($date) && preg_match("/^.+\n\s*(.+?)\s+-\s+(.+?)\s*•\s*Pick-up\s*[:]+\s*(.+?)\s*(?:\n|$)/", $text, $m)) {
                $r->pickup()
                    ->date(strtotime($this->normalizeTime($m[3]), EmailDateHelper::parseDateRelative($m[1], $date)));

                if (strcmp($m[1], $m[2]) !== 0) {
                    $r->dropoff()
                        ->date(EmailDateHelper::parseDateRelative($m[2], $date));
                }
            }
        }

        /* HOTELS */

        $hNodes = $this->http->XPath->query("//img/@src[contains(.,'/stay_blue.png')]/ancestor::div[ descendant::text()[2] ][1]");

        foreach ($hNodes as $root) {
            $h = $email->add()->hotel();

            $h->general()
                ->cancelled()
                ->status('Cancelled');

            $text = implode("\n", $this->http->FindNodes("descendant::text()[normalize-space()]", $root));
            $this->logger->info('General Text:');
            $this->logger->debug($text);

            if (preg_match("/\n\s*(Confirmation #)\s*[:]+\s*([-A-Z\d]{5,})\s*(?:\n|$)/", $text, $m)) {
                $h->general()->confirmation($m[2], $m[1]);
            }

            if (preg_match_all("/^\s*Room[ ]*\d+\s*[:]+\s*(.+)\s*$/im", $text, $m)) {
                $h->general()
                    ->travellers($m[1], true);
            }

            $date = strtotime($this->http->FindSingleNode("//text()[{$this->eq($this->t('purchaseDate'))}]/following::text()[normalize-space()][1]"));

            if (!empty($date)) {
                $date = strtotime('- 1 month', $date);
            }

            if (preg_match("/^\s*(.+)\n/", $text, $m)) {
                $h->hotel()
                    ->name($m[1]);
            }

            if (!empty($date) && preg_match("/^.+\n\s*(.+?)\s+–\s+(.+?)\s*(?:\n|$)/", $text, $m)) {
                $h->booked()
                    ->checkIn(EmailDateHelper::parseDateRelative($m[1], $date))
                    ->checkOut(EmailDateHelper::parseDateRelative($m[2], $date));
            }
        }

        /* FLIGHTS */

        $fNodes = $this->http->XPath->query("//img/@src[contains(.,'/flights_blue.png')]/ancestor::div[ descendant::text()[2] ][1]");

        if ($fNodes->length > 0) {
            $f = $email->add()->flight();

            $confs = array_unique(array_filter(preg_replace('/^\s*X{5,7}\s*$/', '',
                $this->http->FindNodes("//node()[{$this->eq($this->t('Airline Confirmation #:'))}]/following::text()[normalize-space()][1]"))));

            foreach ($confs as $conf) {
                $f->general()
                    ->confirmation($conf);
            }

            $f->general()
                ->cancelled()
                ->status('Cancelled');
        }
    }

    private function normalizeTime(string $string): string
    {
        if (preg_match('/^((\d{1,2})[ ]*:[ ]*\d{2})\s*[AaPp][Mm]$/', $string, $m) && (int) $m[2] > 12) {
            $string = $m[1];
        } // 21:51 PM    ->    21:51

        return $string;
    }

    private function assignLang(): bool
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $dict) {
            if ( !is_string($lang) ) {
                continue;
            }
            if (!empty($dict['header']) && !empty($dict['subHeader']) && !empty($dict['paymentSummary'])
                && $this->http->XPath->query("//*[{$this->eq($dict['header'])}]/following::*[{$this->starts($dict['subHeader'])}]/following::*[{$this->eq($dict['paymentSummary'])}]")->length > 0
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
}
