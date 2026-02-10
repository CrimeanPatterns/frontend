<?php

namespace AwardWallet\Engine\hhonors\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class StayFolioPdf extends \TAccountChecker
{
    public $mailFiles = "hhonors/it-218417839.eml, hhonors/it-218573392.eml, hhonors/it-219084098.eml, hhonors/it-219736109.eml, hhonors/it-918774631-redroof.eml";

    public $reFrom = "no-reply@hilton.com";
    public $reSubject = [
        // en
        "We hope you enjoyed your stay at the",
    ];
    public $reBody = ['Hilton Honors', '@Hilton.com'];
    public $langDetectorsPdf = [
        "en"=> ["Guest Folio"],
    ];
    public $emailSubject;
    public $pdfPattern = '.*\..*p.*d.*f';

    public static $dictionary = [
        "en" => [],
    ];

    public $lang = '';
    private $providerCode = '';

    public function parsePdf(Email $email, $text): void
    {
//        $this->logger->debug('$text = '.print_r( $text,true));

        $h = $email->add()->hotel();

        // General
        if (preg_match("/(?:^[ ]*|[ ]{2})(Confirmation Number)[- ]+([A-Z\d]{5,})(?:[ ]{2}|$)/m", $text, $m)) {
            $h->general()->confirmation($m[2], $m[1]);
        }

        $h->general()
            ->traveller(trim($this->re("/\n\s*Guest Name +(([A-Za-z\']+(?: ?[\-,\.])? ?)+?)(?: {3,}|\n)/", $text)))
        ;

        // Hotel
        $hotelText = $this->re("/^([\s\S]+)\n *Guest Folio\n/", $text);
        $hotelText = preg_replace(["/^( {0,30}\S.*?) {3,}.*$/m", "/^( ) {40,}\S.*$/m"], '$1', $hotelText);

        if (!empty($hotelText)) {
            $h->hotel()
                ->phone($this->re("/\n *(\d{5,})\n.*@/", $hotelText))
            ;
            $hotelText = preg_replace("/\n *\d{5,}\n.*@[\s\S]*/", '', $hotelText);
            $ha = explode("\n", $hotelText);
            if (count($ha) == 2) {
                $h->hotel()
                    ->name($ha[0])
                    ->address($ha[1])
                ;
            } else {
                $name = $this->re("/ your stay at the (.+) - come again soon/", $this->emailSubject);
                if (empty($name)) {
                    $name = $this->http->FindSingleNode("//text()[contains(., 'stay with us here at the')]",
                        null, true, "/stay with us here at the (.+?)\./");
                }

                $address = $this->re("/^\s*".str_replace([' ', '\-', ','], ['\s+', '\s*-\s*', '\s*,\s*'], preg_quote($name))."\n([\s\S]+)/", $hotelText);

                if (!empty($name) && !empty($address)) {
                    $h->hotel()
                        ->name($name)
                        ->address($address)
                    ;
                }
            }

        }

        // Program
        $account = $this->re("/ {3,}Hilton Honors\n.+ {3,}\S.* {3,}\w+\n.+ {3,}\S.* {3,}(\d{5,})\n/", $text);
        if (!empty($account)) {
            $h->program()
                ->account($account, false);
        }

        // Booked
        $date = $this->re("/\n *Check In Date +(\w+[ ,]+\w+[ ,]+\d{4})\s+/", $text);
        $time = $this->re("/\n *Check In Time +(\d{1,2}:\d{2}(?: *[apAP][mM])?)\s+/", $text);
        $h->booked()
            ->checkIn(strtotime($date . ', ' . $time))
        ;
        $date = $this->re("/\n *Check Out Date +(\w+[ ,]+\w+[ ,]+\d{4})\s+/", $text);
        $time = $this->re("/\n *Check Out Time +(\d{1,2}:\d{2}(?: *[apAP][mM])?)\s+/", $text);
        $h->booked()
            ->checkOut(strtotime($date . ', ' . $time))
        ;
        $h->booked()
            ->guests($this->re("/\n\s*Guests +(\d+)\\/\d+\s+/", $text))
            ->kids($this->re("/\n\s*Guests +\d+\\/(\d+)\s+/", $text))
        ;


        // Price
        if (preg_match("/^[ ]*(?:\S ?)+[ ]{4,}Charge[ ]{4,}(?:GUEST ROOM|ROOM CHARGES)(?:[ ]*-[ ]*\S.*?)?[ ]{4,}/m", $text)) {
            if (preg_match_all("/^[ ]*(?:\S ?)+[ ]{4,}Payments[ ]{4,}(?:\S ?)+[ (]{4,}[-]*(.*?)[ )]*$/m", $text, $mTotal)) {
                $totalAmounts = $totalCurrencies = [];

                foreach ($mTotal[1] as $value) {
                    if (preg_match("/^\s*(?<currency>[^\d\s]\D{0,4}?)\s*(?<amount>\d[,.‘\'\d ]*)\s*$/u", $value, $m)
                        || preg_match("/^\s*(?<amount>\d[,.‘\'\d ]*)\s*(?<currency>[^\d\s]\D{0,4}?)\s*$/u", $value, $m)
                    ) {
                        $totalCurrencies[] = $m['currency'];
                        $totalAmounts[] = PriceHelper::parse($m['amount'], $m['currency']);
                    } else {
                        $totalAmounts = $totalCurrencies = [];

                        break;
                    }
                }

                if (count(array_unique($totalCurrencies)) === 1) {
                    $h->price()->currency($totalCurrencies[0])->total(array_sum($totalAmounts));
                }
            }

            if (preg_match_all("/^[ ]*(?:\S ?)+[ ]{4,}(?:Tax|Charge)[ ]{4,}(?<name>(?:\S ?)*\S)[ ]{4,}(?<value>.+)$/im", $text, $mTax, PREG_SET_ORDER)) {
                foreach ($mTax as $i => $m) {
                    if (preg_match("/^(?:GUEST ROOM|ROOM CHARGES)/i", $m['name'])) {
                        unset($mTax[$i]);
                    }
                }
                $mTax = array_values($mTax);

                $this->logger->debug('$mTax = ' . print_r($mTax, true));

                foreach ($mTax as $i => $matches) {
                    if (preg_match("/-[ ]*PERMANENT GUEST$/i", $matches['name']) && preg_match('/^-/', $matches['value'])) {
                        continue; // it-918774631-redroof.eml
                    }

                    if (array_key_exists($i + 1, $mTax)
                        && preg_match("/^{$this->opt($matches['name'])}[ ]*-[ ]*PERMANENT GUEST$/i", $mTax[$i + 1]['name'])
                        && preg_match('/^-/', $mTax[$i + 1]['value'])
                    ) {
                        continue;
                    }

                    if (preg_match("/^\s*(?<currency>[^\d\s]\D{0,4}?)\s*(?<amount>\d[,.‘\'\d ]*)\s*$/u", $matches['value'], $m)
                        || preg_match("/^\s*(?<amount>\d[,.‘\'\d ]*)\s*(?<currency>[^\d\s]\D{0,4}?)\s*$/u", $matches['value'], $m)
                    ) {
                        $totalCurrency = $h->getPrice()->getCurrencyCode() ?? $h->getPrice()->getCurrencySign();

                        if (empty($totalCurrency) || $totalCurrency === $m['currency']) {
                            $h->price()->fee($matches['name'], PriceHelper::parse($m['amount'], $m['currency']));
                        }

                        if (empty($totalCurrency)) {
                            $h->price()->currency($m['currency']);
                        }
                    }
                }
            }
        }
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, $this->reFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!isset($headers['from'],$headers['subject'])) {
            return false;
        }

        if (self::detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        foreach ($this->reSubject as $re) {
            if (stripos($headers["subject"], $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfPattern);

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->assignProviderPdf($textPdf) !== true) {
                continue;
            }

            if ($this->assignLangPdf($textPdf)) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->emailSubject = $parser->getSubject();

        $pdfs = $parser->searchAttachmentByName($this->pdfPattern);
        $textPdfFull = '';
        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (empty($textPdf)) {
                continue;
            }

            if (empty($this->providerCode)) {
                $this->assignProviderPdf($textPdf);
            }

            if ($this->assignLangPdf($textPdf)) {
                $textPdfFull .= $textPdf;
            }
        }

        if (!$textPdfFull) {
            return $email;
        }

        $this->parsePdf($email, $textPdfFull);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));
        $email->setProviderCode($this->providerCode);

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

    public static function getEmailProviders()
    {
        return ['redroof', 'hhonors'];
    }

    private function assignProviderPdf(string $text): bool
    {
        if (stripos($text, '@REDROOF.COM') !== false) {
            $this->providerCode = 'redroof';

            return true;
        }

        if (stripos($text, '@Hilton.com') !== false || stripos($text, 'Hilton Honors') !== false) {
            $this->providerCode = 'hhonors';

            return true;
        }

        return false;
    }

    private function assignLangPdf($text = ''): bool
    {
        foreach ($this->langDetectorsPdf as $lang => $phrases) {
            foreach ($phrases as $phrase) {
                if (stripos($text, $phrase) !== false) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }
}
