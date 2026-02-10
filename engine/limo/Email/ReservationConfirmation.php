<?php

namespace AwardWallet\Engine\limo\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class ReservationConfirmation extends \TAccountChecker
{
    public $mailFiles = "limo/it-139433435.eml, limo/it-140185982.eml, limo/it-654175621.eml, limo/it-655205607.eml, limo/it-138087190.eml, limo/it-138104974.eml, limo/it-141369373.eml, limo/it-890778380.eml";

    public $dateFormat; // 'dmy' or 'mdy'
    public $lang = 'en';
    public static $dictionary = [
        'en' => [
            'confNumber'    => ['Reservation Confirmation #', 'Ride Confirmation #'],
            'modifiedOn'    => ['Last Modified On:', 'Modified On:'],
            'Passenger'     => ['Passenger', 'Client', 'Guest'],
            'cancelledText' => [
                'Cancelled Reservation Confirmation', 'Canceled Reservation Confirmation',
                'This reservation has been cancelled', 'This reservation has been canceled',
            ],
            'totalPrice'    => ['Reservation Total', 'Ride Total'],
        ],
    ];

    private $junkReasons = [];

    private $patterns = [
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
    ];

    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return array_key_exists('subject', $headers) && preg_match("/\bConf#\s*\d+\s+(?i)For\s+\w[-.\'’()\w ]*(?:\[ *[\d\/]+ *- *\d+[:.]\d+.*?\]|$)/u", $headers['subject']) > 0;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect HTML

        if ($this->detectHtml()) {
            if ($this->http->XPath->query("//img[{$this->contains(['.mylimobiz.com/', 'mylimowebsite.com/', '//loosta.net/'], '@src')}] | //*[{$this->contains(['Thank you for choosing Limopedia'])} or {$this->contains(['Email:info@asandiegolimo.com', 'Email:info@prideconnecticutlimo.com', 'Email:Reservations@pewtransportation.com', 'Email:reservations@book201taxicab.com'], "translate(.,' ', '')")}] | //text()[{$this->contains(['Ascend in Motion'])}]")->length > 0) {
                return true;
            }
        }

        // detect PDF

        $pdfs = $parser->searchAttachmentByName('.*\.pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($textPdf)) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $type = '';
        $isJunk = [];

        // parse PDFs

        $pdfs = $parser->searchAttachmentByName('.*\.pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($textPdf)) {
                $type = 'Pdf';
                $this->parsePdf($email, $textPdf, $isJunk);
            }
        }

        // parse HTML

        if (count($email->getItineraries()) === 0 && $this->detectHtml()) {
            $type = 'Html';
            $isJunk = [];
            $this->parseHtml($email, $isJunk);
        }

        if (count(array_unique($isJunk)) === 1 && $isJunk[0] === 'YES') {
            $email->clearItineraries();
            $email->setIsJunk(true, count($this->junkReasons) > 0 ? implode('; ', array_unique($this->junkReasons)) : null);
        }

        $email->setType('ReservationConfirmation' . $type . ucfirst($this->lang));

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

    private function detectHtml(): bool
    {
        if ($this->http->XPath->query("//*[{$this->eq('Trip Routing Information', "translate(.,':','')")}]")->length > 0) {
            return true;
        }

        return false;
    }

    private function detectPdf($text): bool
    {
        if (preg_match("/\n[ ]*{$this->opt($this->t('Trip Routing Information'))}[:\s]/", $text)) {
            return true;
        }

        return false;
    }

    private function parsePdf(Email $email, ?string $textPdf = null, array &$isJunk): void
    {
        // examples: it-138104974.eml, it-890778380.eml
        $this->logger->debug(__FUNCTION__ . '()');
        // $this->logger->debug('Pdf text = ' . print_r($textPdf, true));

        // assign date format
        if (preg_match("/{$this->opt($this->t('modifiedOn'))}[ ]*(\d{1,2})\/(\d{1,2})\/(\d{4})\b/", $textPdf, $m)) {
            if ($m[1] > 12 && $m[2] <= 12) {
                $this->dateFormat = 'dmy';
            }

            if ($m[1] <= 12 && $m[2] > 12) {
                $this->dateFormat = 'mdy';
            }
        }

        $t = $email->add()->transfer();

        // General
        $confirmation = $confirmationTitle = null;

        if (preg_match("/\n[ ]*(?:\w+ )?({$this->opt($this->t('confNumber'))})[ ]{0,5}(\d{4,})(?:[ ]{2}|\n)/", $textPdf, $m)) {
            $confirmation = $m[2];
            $confirmationTitle = $m[1];
        }

        $t->general()
            ->confirmation($confirmation, $confirmationTitle)
            ->traveller($this->re("/\n[ ]*{$this->opt($this->t('Passenger'))}[: ]+(.*?)(?:[ ]{2}|\n)/", $textPdf), true)
        ;

        $type = $this->re("/\n[ ]*{$this->opt($this->t('ServiceType'))}[: ]+(.*?)(?:[ ]{2}|\n)/", $textPdf);

        if (in_array($type, ['Wedding', 'Domestic Airport Arrival Greet']) && !empty($confirmation)) {
            $email->removeItinerary($t);
            $isJunk[] = 'YES';

            return;
        }

        if (preg_match("/{$this->opt($this->t('cancelledText'))}/", $textPdf)) {
            $t->general()
                ->cancelled()
                ->status('Cancelled');
        }

        $pudate = $this->normalizeDate($this->re("/\n[ ]*{$this->opt($this->t('Pick-up Date'))}[: ]+([^: ].*)/", $textPdf));
        $putime = $this->re("/\n[ ]*{$this->opt($this->t('Pick-up Time'))}[: ]+([^: ].*)/", $textPdf)
            ?? $this->re("/\n[ ]*{$this->opt($this->t('Pick-up Date'))}[: ]+\d{1,2}\/\d{1,2}\/\d{4}\s*-\s*({$this->patterns['time']})\n/", $textPdf);
        $dotime = $this->re("/\n[ ]*{$this->opt($this->t('Estimated Drop-off Time'))}[: ]+([^: ].*)/", $textPdf);
        $guests = $this->re("/\n[ ]*{$this->opt($this->t('No. of Pass'))}[: ]+([^: ].*)/", $textPdf);
        $carType = $this->re("/\n[ ]*{$this->opt($this->t('Vehicle Type'))}[: ]+([^: ].*)/", $textPdf);
        $tripText = $this->re("/\n[ ]*{$this->opt($this->t('Trip Routing Information'))}[:\s]+(.+(?:\n*[ ]{20,}.*)+)\n\n/", $textPdf);

        $this->logger->info('Trip Routing Information:');
        $this->logger->debug($tripText);

        if (in_array($type, ['Hourly/As Directed'])
            && preg_match("/^[-*\s]*(NO ROUTING INFORMATION PROVIDED)[-*.;!\s]*$/i", $tripText, $m)
        ) {
            $email->removeItinerary($t);
            $isJunk[] = 'YES';
            $this->junkReasons[] = $m[1];

            return;
        }

        $rows = array_map(function ($item) {
            return preg_replace('/\s+/', ' ', $item);
        }, $this->split("/^[ ]*((?:PU|DO|WT|ST)(?:[ ]*[:]+[ ]*|[ ]+\d{1,2}:|[ ]*--[: ]+))/m", $tripText));

        foreach ($rows as $i => $row) {
            if (preg_match("/^\s*(?:WT|ST)[:\s]*--\s*:/", $row)) {
                unset($rows[$i]);

                continue;
            }
            $rows[$i] = preg_replace("/\s+Notes:[\s\S]+/i", '', $row);
        }

        $rows = array_values($rows);

        if (in_array($type, ['Hourly/As Directed'])
            && count($rows) === 1 && preg_match("/^\s*PU[:\s]*--\s*:/", array_values($rows)[0])
        ) {
            $email->removeItinerary($t);
            $isJunk[] = 'YES';
            $this->junkReasons[] = 'empty drop-off location';

            return;
        }

        foreach ($rows as $i => $row) {
            if ($i == count($rows) - 1) {
                break;
            }

            $s = $t->addSegment();

            // Departure
            $r = $this->parseRow($row);

            if (!empty($r['code'])) {
                $s->departure()
                    ->code($r['code']);
            } else {
                $s->departure()
                    ->address($r['address']);
            }
            $time = $r['time'];

            if ($i === 0 && !empty($putime)) {
                $time = $putime;
            }

            if (!empty($pudate) && !empty($time)) {
                $s->departure()
                    ->date(strtotime($time, $pudate));
            } else {
                $s->departure()
                    ->noDate();
            }

            // Arrival
            $r = $this->parseRow(array_key_exists($i + 1, $rows) ? $rows[$i + 1] : '');

            if (!empty($r['code'])) {
                $s->arrival()
                    ->code($r['code']);
            } else {
                $s->arrival()
                    ->address($r['address']);
            }
            $time = $r['time'];

            if ($i === count($rows) - 2 && !empty($dotime)) {
                $time = $dotime;
            }

            if (!empty($pudate) && !empty($time)) {
                $s->arrival()
                    ->date(strtotime($time, $pudate));
            } else {
                $s->arrival()
                    ->noDate();
            }

            // Extra
            $s->extra()
                ->type($carType, false, true)
                ->adults($guests)
            ;

            if (($s->getDepName() === 'TBD' || $s->getArrName() === 'TBD')) {
                if (count($rows) === 2) {
                    $isJunk[] = 'YES';
                    $this->junkReasons[] = 'empty address (TBD = to be determined)';

                    return;
                } else {
                    $t->removeSegment($s);
                }
            }
        }

        $isJunk[] = 'NO';

        // Price

        if ($t->getCancelled()) {
            return;
        }
        $total = $this->re("/\n[ ]*{$this->opt($this->t('totalPrice'))}[: ]+([^: ].*)/", $textPdf)
            ?? $this->re("/\n[ ]*{$this->opt($this->t('Total Due'))}[: ]+([^: ].*)/", $textPdf);

        if (preg_match("/^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d,. ]*?)\s*$/", $total, $m)
            || preg_match("/^\s*(?<amount>\d[\d,. ]*?)\s*(?<currency>[^\d\s]{1,5})\s*$/", $total, $m)
        ) {
            // $1,707.75
            $t->price()
                ->total(PriceHelper::parse($m['amount']))
                ->currency($m['currency'])
            ;
        } elseif (preg_match("/^\s*(?<amount>\d[\d,. ]*?)\s*$/", $total, $m)) {
            // 990.00
            $t->price()->total(PriceHelper::parse($m['amount']));
        }
    }

    private function parseHtml(Email $email, array &$isJunk): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        // assign date format
        $modDate = $this->http->FindSingleNode("//text()[{$this->starts($this->t('modifiedOn'))}]");

        if (preg_match("/:\s*(\d{1,2})\/(\d{1,2})\/(\d{4})\b/", $modDate, $m)) {
            if ($m[1] > 12 && $m[2] <= 12) {
                $this->dateFormat = 'dmy';
            }

            if ($m[1] <= 12 && $m[2] > 12) {
                $this->dateFormat = 'mdy';
            }
        }

        $t = $email->add()->transfer();

        // General
        $confirmation = $confirmationTitle = null;

        if (preg_match("/({$this->opt($this->t('confNumber'))})\s*(\d{5,})\s*$/i", $this->http->FindSingleNode("//td[not(.//tr[normalize-space()]) and {$this->contains($this->t('confNumber'))}]"), $m)) {
            $confirmation = $m[2];
            $confirmationTitle = $m[1];
        }

        $t->general()
            ->confirmation($confirmation, $confirmationTitle)
            ->traveller($this->getField($this->t('Passenger'), "/^\s*(.+?)(?:\s*\(.*)?\s*$/"), true)
        ;

        $type = $this->getField($this->t('ServiceType'));

        if (in_array($type, ['Wedding', 'Domestic Airport Arrival Greet']) && !empty($confirmation)) {
            $email->removeItinerary($t);
            $isJunk[] = 'YES';

            return;
        }

        if ($this->http->XPath->query("//*[{$this->contains($this->t('cancelledText'))}]")->length > 0) {
            $t->general()
                ->cancelled()
                ->status('Cancelled');
        }

        $pudate = $this->normalizeDate($this->getField($this->t('Pick-up Date'), "/^(.+?)\s*(?:Add to .*)?$/"));
        $putime = $this->getField($this->t('Pick-up Time')) ?? $this->getField($this->t('Pick-up Date'), "/^\s*\d{1,2}\/\d{1,2}\/\d{4}\s*-\s*({$this->patterns['time']})\s*$/");
        // 01:05 PM / 13:05    ->    13:05
        $putime = preg_replace("/^\s*\d{1,2}:\d{2}\s*[ap]m\s*\/\s*(\d{1,2}:\d{2})\s*$/i", '$1', $putime);
        $dotime = $this->getField($this->t('Estimated Drop-off Time'));

        $startDate = null;

        if (!empty($pudate) && !empty($putime)) {
            $startDate = strtotime($putime, $pudate);
        }

        $endDate = null;

        if (!empty($dotime) && !empty($pudate)) {
            $endDate = strtotime($dotime, $pudate);

            if (!empty($startDate) && !empty($endDate) && $endDate < $startDate) {
                $endDate = strtotime('+1 day', $endDate);
            }
        }

        $guests = $this->getField($this->t('No. of Pass'));
        $carType = $this->getField($this->t('Vehicle Type'));
        $tripText = $this->getField($this->t('Trip Routing Information'));

        $this->logger->info('Trip Routing Information:');
        $this->logger->debug($tripText);

        if (in_array($type, ['Hourly/As Directed'])
            && preg_match("/^[-*\s]*(NO ROUTING INFORMATION PROVIDED)[-*.;!\s]*$/i", $tripText, $m)
        ) {
            $email->removeItinerary($t);
            $isJunk[] = 'YES';
            $this->junkReasons[] = $m[1];

            return;
        }

        /*
            PU - Pick Up        DO - Drop Off
            WT - Wait           ST - Stop
        */

        $rows = $this->split("/^[ ]*((?:PU|DO|WT|ST)(?:[ ]*[:]+[ ]*|[ ]+\d{1,2}:|[ ]*--[: ]+))/m", $tripText);

        foreach ($rows as $i => $row) {
            if (preg_match("/^\s*(?:WT|ST)[:\s]*--\s*:/", $row)) {
                unset($rows[$i]);

                continue;
            }
            $rows[$i] = preg_replace("/\s+Notes:[\s\S]+/i", '', $row);
        }

        $rows = array_values($rows);

        if (in_array($type, ['Hourly/As Directed'])
            && count($rows) === 1 && preg_match("/^\s*PU[:\s]*--\s*:/", array_values($rows)[0])
        ) {
            $email->removeItinerary($t);
            $isJunk[] = 'YES';
            $this->junkReasons[] = 'empty drop-off location';

            return;
        }

        foreach ($rows as $i => $row) {
            if ($i == count($rows) - 1) {
                break;
            }

            $s = $t->addSegment();

            // Departure
            $r = $this->parseRow($row);

            if (!empty($r['code'])) {
                $s->departure()
                    ->code($r['code']);
            } else {
                if (strlen(preg_replace('/\D/', '', $r['address'])) < 3) {
                    $s->departure()
                        ->name($r['address']);
                } else {
                    $s->departure()
                        ->address($r['address']);
                }
            }
            $time = $r['time'];

            if ($i === 0 && !empty($startDate)) {
                $s->departure()
                    ->date($startDate);
            } elseif (!empty($pudate) && !empty($time)) {
                $s->departure()
                    ->date(strtotime($time, $pudate));
            } else {
                $s->departure()
                    ->noDate();
            }

            // Arrival
            $r = $this->parseRow(array_key_exists($i + 1, $rows) ? $rows[$i + 1] : '');

            if (!empty($r['code'])) {
                $s->arrival()
                    ->code($r['code']);
            } else {
                if (strlen(preg_replace('/\D/', '', $r['address'])) < 3) {
                    $s->arrival()
                        ->name($r['address']);
                } else {
                    $s->arrival()
                        ->address($r['address']);
                }
            }
            $time = $r['time'];

            if (($i + 1 === count($rows) - 1) && !empty($endDate)) {
                $s->arrival()
                    ->date($endDate);
            } elseif (!empty($pudate) && !empty($time)) {
                $s->arrival()
                    ->date(strtotime($time, $pudate));
            } elseif (!empty($pudate) && !empty($r['flightTime'])
                && ((!empty($s->getDepCode()) && $s->getDepCode() === $s->getArrCode())
                    || (!empty($s->getDepName()) && $s->getDepName() === $s->getArrName())
                    || !empty($s->getDepDate()) && strtotime($r['flightTime'], $pudate) - $s->getDepDate() > 60 * 60 * 6) // 6 hour
            ) {
                $ft = strtotime('- 2 hours', strtotime($r['flightTime'], $pudate));

                if (empty($s->getDepDate()) || $ft > $s->getDepDate()) {
                    $s->arrival()
                        ->date($ft);
                } else {
                    $s->arrival()
                        ->noDate();
                }
            } else {
                $s->arrival()
                    ->noDate();
            }

            // Extra
            $s->extra()
                ->type($carType, false, true)
                ->adults($guests)
            ;

            if (($s->getDepName() === 'TBD' || $s->getArrName() === 'TBD')) {
                if (count($rows) === 2) {
                    $isJunk[] = 'YES';
                    $this->junkReasons[] = 'empty address (TBD = to be determined)';

                    return;
                } else {
                    $t->removeSegment($s);
                }
            }
        }

        $isJunk[] = 'NO';

        // Price

        if ($t->getCancelled()) {
            return;
        }
        $total = $this->getField($this->t('totalPrice')) ?? $this->getField($this->t('Total Due'));

        if (preg_match("/^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d,. ]*?)\s*$/", $total, $m)
            || preg_match("/^\s*(?<amount>\d[\d,. ]*?)\s*(?<currency>[^\d\s]{1,5})\s*$/", $total, $m)
        ) {
            // $1,707.75
            $t->price()
                ->total(PriceHelper::parse($m['amount'], $m['currency']))
                ->currency($m['currency'])
            ;
        } elseif (preg_match("/^\s*(?<amount>\d[\d,. ]*?)\s*$/", $total, $m)) {
            // 990.00
            $t->price()->total(PriceHelper::parse($m['amount']));
        }
    }

    private function parseRow($row): array
    {
        $result = [
            'code'          => null,
            'address'       => null,
            'time'          => null,
            'flightTime'    => null,
        ];

        if (preg_match("/^\s*[A-z]+(?:\s*:\s*|[ ]+)([^\d:]+|\d{1,2}:\d+[^\d:]*)\s*:\s*(.+)/", $row, $m)) {
            // "PU: -- : JIA - Jacksonville International Airport / UA - United Airlines , Flt# 506" - JIA not iata code
            $result['address'] = preg_replace('/^\s*([^,]+?)\s*,?\s*\/\s*[A-Z\d]{2} .+Flt# .+/', '$1', $m[2]);
            // Philadelphia International Airport, / AA - American AirlinesFrom/To: LHR, Term/Gate: A Flt# 737, ETA/ETD: 15:15:00,
            $result['address'] = preg_replace('/^\s*(.+)\s*\/\s*[A-Z\d]{2} - .+,\s*Flt# \d+.+/', '$1', $m[2]);
            $result['address'] = preg_replace('/^\s*([^,]+? airport)\s*,?\s*\/\s*[A-Z\d]{2} - .+/i', '$1', $result['address']);
            $result['address'] = trim($result['address'], ' ,');

            if (preg_match("/^\d{1,2}:\d{2}(?:\b|\D)/", $m[1])) {
                $result['time'] = $m[1];
            }

            if (preg_match("/ETA\/ETD[ ]*:[ ]*({$this->patterns['time']}|\d{1,2}:\d{2}(?::\d{2})?)\s*\W?\s*$/", $m[2], $mat)) {
                $result['flightTime'] = $mat[1];
            }
        }

        return $result;
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
            $textRows[] = $this->htmlToText($this->http->FindHTMLByXpath('.', null, $root));
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

    private function normalizeDate(?string $date)
    {
        // $this->logger->debug('date begin = ' . print_r($date, true));
        if (empty($date)) {
            return null;
        }

        if (preg_match("/^\s*(?<date>(?<d1>\d{1,2})\/(?<d2>\d{1,2})\/\d{4})(?:\s*-\s*(?<wday>[[:alpha:]]+))?\s*$/u", $date, $m)
            || preg_match("/^\s*(?<date>(?<d1>\d{1,2})\/(?<d2>\d{1,2})\/\d{4})\s*-\s*{$this->patterns['time']}\s*$/", $date, $m)
        ) {
            // 02/19/2022 - Saturday    |    16/03/2025 - 02:00 PM
            if ($this->dateFormat == 'dmy') {
                $date = str_replace("/", '.', $m['date']);

                return strtotime($date);
            } elseif ($this->dateFormat == 'mdy') {
                $date = $m['date'];

                return strtotime($date);
            } elseif (empty($this->dateFormat) && (int) $m['d1'] > 12 && (int) $m['d2'] <= 12) {
                $date = str_replace("/", '.', $m['date']);

                return strtotime($date);
            } elseif (empty($this->dateFormat) && (int) $m['d1'] <= 12 && (int) $m['d2'] > 12) {
                $date = $m['date'];

                return strtotime($date);
            } elseif (!empty($m['wday'])) {
                $w = WeekTranslate::number1($m['wday']);
                $date1 = strtotime($m['date']);
                $date2 = strtotime(str_replace("/", '.', $m['date']));

                if (!empty($date1) && empty($date2) && $w == date("w", $date1)) {
                    $date = $m['date'];
                    $this->dateFormat = 'mdy';

                    return strtotime($date);
                }

                if (!empty($date1) && $w == date("w", $date1)
                    && (empty($date2) || $w !== date("w", $date2))
                ) {
                    $this->dateFormat = 'mdy';

                    return $date1;
                } elseif (!empty($date2) && $w == date("w", $date2)
                    && (empty($date1) || $w !== date("w", $date1))
                ) {
                    $this->dateFormat = 'dmy';

                    return $date2;
                }

                $w1 = date("w", $date1);
                $w2 = date("w", $date2);

                if ($w === $w1) {
                    $date = $m['date'];
                    $this->dateFormat = 'mdy';

                    return strtotime($date);
                }

                if ($w === $w2) {
                    $this->dateFormat = 'dmy';
                    $date = str_replace("/", '.', $m['date']);

                    return strtotime($date);
                }
                $date = $m['date'];
            }
        }

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

    private function split($re, $text): array
    {
        $r = preg_split($re, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ret = [];

        if (count($r) > 1) {
            array_shift($r);

            for ($i = 0; $i < count($r) - 1; $i += 2) {
                $ret[] = $r[$i] . $r[$i + 1];
            }
        } elseif (count($r) == 1) {
            $ret[] = reset($r);
        }

        return $ret;
    }

    private function re(string $re, ?string $str, $c = 1): ?string
    {
        if (preg_match($re, $str ?? '', $m) && array_key_exists($c, $m)) {
            return $m[$c];
        }

        return null;
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
