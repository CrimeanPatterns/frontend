<?php

namespace AwardWallet\Engine\norwegian\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Schema\Parser\Common\Flight;
use PlancakeEmailParser;

class It3963813 extends \TAccountChecker
{
    public $mailFiles = "norwegian/it-10993433.eml, norwegian/it-3963813.eml, norwegian/it-4391425-da.eml, norwegian/it-4433071.eml, norwegian/it-4447325-da.eml, norwegian/it-4449124-no.eml, norwegian/it-4777035-fi.eml, norwegian/it-692328663.eml, norwegian/it-918143964-es.eml, norwegian/it-916901806-fi.eml";

    public $reSubject = [
        "en" => "Travel information",
        "es" => "Informacion sobre tu viaje", "Recibo de viaje",
        "da" => "Rejseinformation",
        "no" => "Reiseinformasjon",
        "fi" => "Tietoa matkasta", "Matkakuitti",
    ];
    public $reBody = 'Norwegian Air';
    public $reBody2 = [
        "en"  => "Thank you for choosing us",
        "en2" => "Thank you for travelling with",
        "es"  => "Gracias por elegir Norwegian",
        "es2"  => "Gracias por viajar con",
        "da"  => "Din næste rejse",
        "no"  => "Din neste reise",
        "fi"  => "Kiitos että lennät kanssamme",
        "fi2"  => "Kiitos, että lennät",
        'sv'  => 'Tack för att du valde oss',
    ];

    public static $dictionary = [
        "en" => [
            "Booking Reference:" => ["Booking Reference:", "Booking reference:"],
            // "Passengers" => "",
            // "Outbound Flight" => "",
            // "Return flight" => "",
            // "Departure from" => "",
            // "Transit stop in" => "",

            // Html2
            // 'Flight info' => '',
            // 'From/To' => '',

            // Pdf (Receipt)
            // 'Purchase receipt' => '',
            // 'Total paid' => '',
        ],
        "es" => [
            "Booking Reference:" => ["Referencia de la Reserva:", "Referencia de la reserva:", "Código de reserva:"],
            "Passengers"         => "Pasajeros",
            "Outbound Flight"    => "Vuelo de ida",
            "Return flight"      => "Vuelo de vuelta",
            "Departure from"     => "Salida desde",
            //			"Transit stop in" => "",

            // Html2
            'Flight info' => 'Información del vuelo',
            'From/To' => 'Desde/Hasta',

            // Pdf (Receipt)
            'Purchase receipt' => 'Recibo de compra',
            'Total paid' => 'Total pagado',
        ],
        "da"=> [
            "Booking Reference:" => "Referencenummer:",
            "Passengers"         => "Passagerer",
            "Outbound Flight"    => "Udrejse",
            "Return flight"      => "Hjemrejse",
            "Departure from"     => "Afgang fra",
            "Transit stop in"    => "Mellemlanding i",

            // Html2
            // 'Flight info' => '',
            // 'From/To' => '',

            // Pdf (Receipt)
            // 'Purchase receipt' => '',
            // 'Total paid' => '',
        ],
        "no"=> [
            "Booking Reference:" => "Referansenummer:",
            "Passengers"         => "Passasjerer",
            "Outbound Flight"    => "Utreise",
            "Return flight"      => "Retur",
            "Departure from"     => "Avreise fra",
            "Transit stop in"    => "Transfer",

            // Html2
            // 'Flight info' => '',
            // 'From/To' => '',

            // Pdf (Receipt)
            // 'Purchase receipt' => '',
            // 'Total paid' => '',
        ],
        "fi"=> [
            "Booking Reference:" => ["Varausnumero:", "Varausviite:"],
            "Passengers"         => ["Matkustajat", "Matkustaja"],
            "Outbound Flight"    => "Lähtevä lento",
            "Return flight"      => "Paluulento",
            "Departure from"     => "Lähtöpaikka",
            //			"Transit stop in" => "",

            // Html2
            'Flight info' => 'Lennon tiedot',
            'From/To' => 'Lähtöpaikka/määränpää',

            // Pdf (Receipt)
            'Purchase receipt' => 'Ostokuitti',
            'Total paid' => 'Maksettu yhteensä',
        ],
        "sv"=> [
            "Booking Reference:" => "Bokningsreferens:",
            "Passengers"         => "Passagerare",
            "Outbound Flight"    => "Utresa",
            "Return flight"      => "Ankomst",
            "Departure from"     => "Avresa från",
            //			"Transit stop in" => "",

            // Html2
            // 'Flight info' => '',
            // 'From/To' => '',

            // Pdf (Receipt)
            // 'Purchase receipt' => '',
            // 'Total paid' => '',
        ],
    ];

    public $lang = "en";

    private $date;

    private $patterns = [
        'time' => '\d{1,2}[:：.]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  15.30
    ];

    public function parseHtml(Flight $f, ?string &$bookingReference): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        $confirmation = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Booking Reference:'))}]");

        if (preg_match("/^({$this->opt($this->t('Booking Reference:'))})[:\s]*([A-z\d]+)[,.;!\s]*$/", $confirmation, $m)) {
            $f->general()->confirmation($m[2], rtrim($m[1], ': '));
            $bookingReference = $m[2];
        }

        $passengers = [];
        $cnt1 = $this->http->XPath->query("//text()[{$this->eq($this->t('Passengers'))}]/ancestor::tr[1]/preceding-sibling::tr")->length;
        $cnt2 = $this->http->XPath->query("//tr[contains(., 'View booking') or contains(., 'View reservation') and not(.//tr)]/ancestor::tr[preceding-sibling::tr][1]/preceding-sibling::tr")->length;

        if ($cnt1 > 0 && $cnt2 > 0) {
            $cnt = $cnt2 - $cnt1;
            $passengers = array_unique($this->http->FindNodes("//text()[{$this->eq($this->t('Passengers'))}]/ancestor::tr[1]/following-sibling::tr[string-length(normalize-space())>2][position()<{$cnt}][not(contains(normalize-space(),'View booking')) and not(contains(normalize-space(),'View reservation'))]/descendant::text()[normalize-space()]"));
        }

        if (empty($passengers)) {
            $passengers = $this->http->FindNodes("//text()[{$this->eq($this->t('Passengers'))}]/ancestor::tr[1]/following-sibling::tr[string-length(normalize-space())>2]");
        }

        foreach ($passengers as $passengerName) {
            $f->general()->traveller($this->normalizeTraveller($passengerName));
        }

        $xpath = "//text()[normalize-space(.)='" . $this->t("Departure from") . "' or normalize-space(.)='" . $this->t("Transit stop in") . "']/ancestor::tr[1]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length == 0) {
            $this->logger->info("segments root not found: $xpath");
        }

        foreach ($nodes as $root) {
            $s = $f->addSegment();
            // 17 lip 2017 - pl lang, but for detect body are no normal string
            $date = $this->normalizeDate($this->http->FindSingleNode("./preceding-sibling::tr[contains(., '" . $this->t("Outbound Flight") . "') or contains(., '" . $this->t("Return flight") . "')][1]/following-sibling::tr[1]", $root));

            $s->airline()
                ->noName()
                ->noNumber();

            if (!empty($date)) {
                $depDate = $date . ' ' . $this->normalizeTime($this->http->FindSingleNode("following-sibling::tr[string-length(normalize-space())>2][2]", $root, true, "/{$this->patterns['time']}$/"));
                $arrDate = $date . ' ' . $this->normalizeTime($this->http->FindSingleNode("following-sibling::tr[string-length(normalize-space())>2][5]", $root, true, "/^{$this->patterns['time']}/"));
            }

            if (empty($depDate) && empty($arrDate)) {
                $depDate = $this->http->FindSingleNode("./following-sibling::tr[string-length(normalize-space(.)) > 2][2]", $root, true, "/\d{1,2}\/\d{1,2}\/\d{2,4}\s+{$this->patterns['time']}$/");
                $arrDate = $this->http->FindSingleNode("./following-sibling::tr[string-length(normalize-space(.)) > 2][5]", $root, true, "/\d{1,2}\/\d{1,2}\/\d{2,4}\s+{$this->patterns['time']}$/");

                if ($this->http->XPath->query("self::tr[contains(., 'Transit stop in')]", $root)->length > 0) {
                    $depDate = $this->http->FindSingleNode("./following-sibling::tr[string-length(normalize-space(.)) > 2][3]", $root, true, "/\d{1,2}\/\d{1,2}\/\d{2,4}\s+{$this->patterns['time']}$/");
                    $arrDate = $this->http->FindSingleNode("./following-sibling::tr[string-length(normalize-space(.)) > 2][6]", $root, true, "/\d{1,2}\/\d{1,2}\/\d{2,4}\s+{$this->patterns['time']}$/");
                }
            }

            $s->departure()
                ->date(strtotime($depDate))
                ->code($this->http->FindSingleNode("./following-sibling::tr[string-length(normalize-space(.)) > 2][1]", $root, true, "#\(([A-Z]{3})#"));

            $arrCode = $this->http->FindSingleNode("./following-sibling::tr[string-length(normalize-space(.)) > 2][4]", $root, true, "#\(([A-Z]{3})#");

            if ($this->http->XPath->query("self::tr[contains(., 'Transit stop in')]", $root)->length > 0) {
                $arrCode = $this->http->FindSingleNode("./following-sibling::tr[string-length(normalize-space(.)) > 2][5]", $root, true, "#\(([A-Z]{3})#");

                if (empty($arrCode)) {
                    $arrCode = $this->http->FindSingleNode("./following-sibling::tr[string-length(normalize-space(.)) > 2][4]", $root, true, "#\(([A-Z]{3})#");
                }
            }

            $s->arrival()
                ->date(strtotime($arrDate))
                ->code($arrCode);
        }
    }

    public function parseHtml2(Flight $f, ?string &$bookingReference): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        $confirmation = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Booking Reference:'))}]/ancestor::tr[1]");

        if (preg_match("/^({$this->opt($this->t('Booking Reference:'))})[:\s]*([A-z\d]+)[,.;!\s]*$/", $confirmation, $m)) {
            $f->general()->confirmation($m[2], rtrim($m[1], ': '));
            $bookingReference = $m[2];
        }

        $travellers = $this->http->FindNodes("//*/tr[normalize-space()][1][{$this->eq($this->t('Passengers'), "translate(.,':','')")}]/following-sibling::tr[string-length(normalize-space())>2]");

        foreach ($travellers as $passengerName) {
            $f->general()->traveller($this->normalizeTraveller($passengerName));
        }

        $nodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Flight info'), "translate(.,':','')")}]");

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            $date = 0;
            $flightInfo = $this->http->FindSingleNode("following::tr[string-length(normalize-space())>3][1]/descendant::td[string-length(normalize-space())>3][1]", $root);

            if (preg_match("/^(?<aN>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z])) ?(?<fN>\d+)-(?<date>\d{1,2}[,.\s]+\w+[,.\s]+\d{4})$/u", $flightInfo, $m)) {
                // DY1191-11 6 2025
                $s->airline()
                    ->name($m['aN'])
                    ->number($m['fN']);

                $date = strtotime($this->normalizeDate($m['date']));
            }

            $pointInfo = implode("\n", $this->http->FindNodes("following::tr[string-length(normalize-space())>3][1]/descendant::td[string-length(normalize-space())>3][2]/descendant::text()[normalize-space()]", $root));

            if (preg_match("/^(?<depTime>{$this->patterns['time']})\s+(?<depName>.+)\n(?<arrTime>{$this->patterns['time']})\s+(?<arrName>.+)$/", $pointInfo, $m)) {
                $s->departure()
                    ->noCode()
                    ->name($m['depName'])
                    ->date(strtotime($this->normalizeTime($m['depTime']), $date));

                $s->arrival()
                    ->noCode()
                    ->name($m['arrName'])
                    ->date(strtotime($this->normalizeTime($m['arrTime']), $date));
            }
        }
    }

    private function parsePdfReceipt(Flight $f, string $text): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        $totalPrice = preg_match("/^[ ]*{$this->opt($this->t('Total paid'))} ?[:]*[ ]{70,}(\S.*)$/m", $text, $m)
            && strpos($m[1], '  ') === false ? $m[1] : null;

        if (preg_match('/^(?<amount>\d[,.’‘\'\d ]*?)[ ]*(?<currency>[^\-\d)(]+)$/u', $totalPrice, $matches)) {
            // 255.67 GBP
            $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
            $f->price()->currency($matches['currency'])->total(PriceHelper::parse($matches['amount'], $currencyCode));
        }
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]norwegian\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->reSubject as $re) {
            if (is_string($re) && array_key_exists('subject', $headers) && strpos($headers['subject'], $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $body = $parser->getHTMLBody();

        if (strpos($body, $this->reBody) === false && strpos($body, ".norwegian.com") === false) {
            return false;
        }

        foreach ($this->reBody2 as $re) {
            if (strpos($body, $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->date = strtotime($parser->getHeader('date'));

        $this->http->FilterHTML = false;

        foreach ($this->reBody2 as $lang=>$re) {
            if (strpos($this->http->Response["body"], $re) !== false) {
                $this->lang = substr($lang, 0, 2);

                break;
            }
        }
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        /*
            Step 1/2: parse flight
        */

        $bookingReference = null;
        $f = $email->add()->flight();

        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Flight info'), "translate(.,':','')")}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('From/To'), "translate(.,':','')")}]")->length > 0
        ) {
            // it-692328663.eml, it-918143964-es.eml, it-916901806-fi.eml
            $this->parseHtml2($f, $bookingReference);
        } else {
            $this->parseHtml($f, $bookingReference);
        }

        if ($bookingReference === null) {
            return $email;
        }

        /*
            Step 2/2: parse price
        */

        $receiptsTexts = [];
        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (!preg_match("/(?:^[ ]*| ){$this->opt($this->t('Purchase receipt'))}(?: |$)/m", $textPdf)) {
                continue;
            }

            $reference = preg_match("/(?:^[ ]*| ){$this->opt($this->t('Booking Reference:'))}[: ]*([A-z\d]{5,10})$/m", $textPdf, $m) ? $m[1] : null;

            if ($reference !== null && $bookingReference !== null
                && strtoupper($reference) === strtoupper($bookingReference)
            ) {
                $receiptsTexts[] = $textPdf;
            }
        }

        if (count($receiptsTexts) === 1) {
            // it-918143964-es.eml, it-916901806-fi.eml
            $this->parsePdfReceipt($f, $receiptsTexts[0]);
        }

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

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function normalizeTraveller(?string $s): ?string
    {
        if (empty($s)) {
            return null;
        }

        $namePrefixes = '(?:MASTER|MSTR|MISS|MRS|MR|MS|DR)';

        return preg_replace([
            "/^(.{2,}?)\s+(?:{$namePrefixes}[.\s]*)+$/is",
            "/^(?:{$namePrefixes}[.\s]+)+(.{2,})$/is",
            '/^([^\/]+?)(?:\s*[\/]+\s*)+([^\/]+)$/',
        ], [
            '$1',
            '$1',
            '$2 $1',
        ], $s);
    }

    private function normalizeDate($str): string
    {
        $year = date("Y", $this->date);
        // TODO: it-4777035-fi.eml
        $str = str_replace([' ta1i '], [' tammi '], $str);

        $in = [
            "#^\w+\s+-\s+\w+,\s+(\w+)\s+(\d+)$#",
            "#^\w+,\s+(\w+)\s+(\d+)\s+(\d+:\d+\s+[AP]M)$#",
            "#([^\d\s]+)\d+([^\d\s]+)#",
            "/^(\d{1,2})\s+(\d{1,2})\s+(\d{4})$/", // 11 6 2025
        ];
        $out = [
            "$2 $1 $year",
            "$2 $1 $year, $3",
            "$1$2",
            '$1.$2.$3',
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if (($en = MonthTranslate::translate($m[1], $this->lang)) || ($en = MonthTranslate::translate($m[1], 'pl'))) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return $str;
    }

    private function normalizeTime(?string $s): string
    {
        $s = preg_replace([
            '/(\d)[ ]*[.][ ]*(\d)/', // 15.30    ->    15:30
        ], [
            '$1:$2',
        ], $s);
        return $s;
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
}
