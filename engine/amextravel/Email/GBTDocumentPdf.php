<?php

namespace AwardWallet\Engine\amextravel\Email;

use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Common\Parser\Util\PriceHelper;

class GBTDocumentPdf extends \TAccountChecker
{
    public $mailFiles = "amextravel/it-891744840.eml, amextravel/it-891737932.eml, amextravel/it-891737477.eml, amextravel/it-891746656.eml, amextravel/it-892093642.eml";

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'passenger' => ['Passenger', 'Pasenger'],
            'ticketNumber' => ['Ticket Number', 'Ticket Nr.'],
            'from' => ['From'],
            'to' => ['To'],
            'totalInclVAT' => ['Total Incl. V.A.T', 'Total Incl. V.A.T.', 'Total'],
        ],
    ];

    private $enDatesInverted = null;
    private $isJunk = [];

    private $patterns = [
        'date' => '\b\d{1,2}[.\/]\d{1,2}[.\/]\d{4}\b', // 16/03/2025
        'travellerName' => '[[:upper:]]+(?: [[:upper:]]+)*[ ]*\/[ ]*(?:[[:upper:]]+ )*[[:upper:]]+', // KOH / KIM LENG MR
        'eTicket' => '\d{3}(?: | ?- ?)?\d{5,}(?: | ?[-\/] ?)?\d{1,3}', // 175-2345005149-23  |  1752345005149/23
    ];

    private function parsePdf(Email $email, string $text): void
    {
        // remove garbage
        $text = preg_replace([
            '/.*\bPage\s*\d+\s*of\s*\d+\b.*/i',
            '/.*(?:trading\s*as\s*American\s*Express|\bE14\s+5HU\b|\bCompany\s*Number[ ]*[:]+\s*\d{8}\b|\buse\s*certain\s*trademark|\blimited\s*license\b|Licensed\s*Marks|separate\s*company).*/i',
        ], '', $text);

        $this->enDatesInverted = true;

        if (preg_match("/(?:^[ ]*|[ ]{2})({$this->opt($this->t('Booking Ref'))})[ ]*[:]+[ ]*([-A-Z\d]{5,})(?:[ ]{2}|$)/m", $text, $m)) {
            $email->ota()->confirmation($m[2], $m[1]);
        }

        $passenger = $this->normalizeTraveller($this->re("/^[ ]{0,20}{$this->opt($this->t('passenger'))}[ ]*[:]+[ ]*({$this->patterns['travellerName']})$/imu", $text));

        $parsedIts = [];
        $tripSegments = $this->splitText($text, "/^((?:[ ]{0,40}AIR TICKET\b|[ ]{0,40}LOW COST CARRIER BOOKING\b|[ ]{0,40}EUROSTAR E[- ]{0,5}TICKET\b|[ ]{0,40}CAR RENTAL\b|[ ]{0,40}HOTEL RESERVATION\b|.+ (?i){$this->opt($this->t('ticketNumber'))}[ ]*:))/m", true);

        foreach ($tripSegments as $tsText) {
            if (preg_match("/^(?:[ ]{0,40}AIR TICKET\b|[ ]{0,40}LOW COST CARRIER BOOKING\b|[ ]{0,40}EUROSTAR E[- ]{0,5}TICKET\b|.+ (?i){$this->opt($this->t('ticketNumber'))}[ ]*:)/i", $tsText)) {
                $this->parseFlight($email, $tsText, $parsedIts);
            } elseif (preg_match("/^[ ]{0,40}CAR RENTAL\b/i", $tsText)) {
                $this->parseCars($email, $tsText, $parsedIts);
            } elseif (preg_match("/^[ ]{0,40}HOTEL RESERVATION\b/i", $tsText)) {
                $this->parseHotels($email, $tsText, $parsedIts);
            }
        }

        foreach ($parsedIts as $it) {
            $it->general()->traveller($passenger, true);
        }

        // price

        if (count($parsedIts) !== 1 || count($tripSegments) > 1) {
            return;
        }

        // examples: it-891737477.eml

        foreach ($parsedIts as $pdfIt) {
            /** @var \AwardWallet\Schema\Parser\Common\Hotel $it */
            $it = $pdfIt;
        }

        if (!empty($it->getPrice()) && !empty($it->getPrice()->getCurrencyCode())) {
            $itCurrency = $it->getPrice()->getCurrencyCode();
        } elseif (!empty($it->getPrice()) && !empty($it->getPrice()->getCurrencySign())) {
            $itCurrency = $it->getPrice()->getCurrencySign();
        } else {
            $itCurrency = null;
        }

        $totalPrice = $this->re("/\n[ ]*{$this->opt($this->t('Total'))}[ ]{2,}(.*\d.*)/", $text);

        if (preg_match('/^(?<amount>\d[,.’‘\'\d ]*?)[ ]*(?<currency>[^\-\d)(]+)$/u', $totalPrice, $matches)) {
            if ($itCurrency === null) {
                $it->price()->currency($matches['currency']);
                $itCurrency = $matches['currency'];
            }

            if (empty($it->getPrice())
                || $it->getPrice()->getTotal() === null && $itCurrency === $matches['currency']
            ) {
                $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
                $it->price()->total(PriceHelper::parse($matches['amount'], $currencyCode));
            }

            /*
            $baseFare = $this->re("/\n[ ]*{$this->opt($this->t('Amount excl. V.A.T'))}\.?[ ]{2,}(.*\d.*)/", $text);

            if ( preg_match('/^(?<amount>\d[,.’‘\'\d ]*?)[ ]*(?:' . preg_quote($matches['currency'], '/') . ')?$/u', $baseFare, $m) ) {
                $it->price()->cost(PriceHelper::parse($m['amount'], $currencyCode));
            }

            $taxes = $this->re("/\n[ ]*{$this->opt($this->t('Total V.A.T'))}\.?[ ]{2,}(.*\d.*)/", $text);

            if ( preg_match('/^(?<amount>\d[,.’‘\'\d ]*?)[ ]*(?:' . preg_quote($matches['currency'], '/') . ')?$/u', $taxes, $m) ) {
                $it->price()->tax(PriceHelper::parse($m['amount'], $currencyCode));
            }
            */
        }
    }

    private function parseFlight(Email $email, string $text, array &$parsedIts): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        $isTrain = false;

        if (preg_match("/^[ ]{0,40}EUROSTAR E[- ]{0,5}TICKET\b/i", $text, $m)) {
            $this->logger->debug('Found signs of train on flight header.');
            $isTrain = true;
        }

        $f = $email->add()->flight();

        $confNumber = null;

        if (preg_match("/[ ]{2}{$this->opt($this->t('ticketNumber'))}[ ]*[:]+[ ]*([^:\s].*)$/im", $text, $m)) {
            $confNumber = $m[1];
        }

        if (empty($confNumber) && preg_match("/^(?:[ ]{0,40}AIR TICKET|[ ]{0,40}LOW COST CARRIER BOOKING|[ ]{0,40}EUROSTAR E[- ]{0,5}TICKET) ?- ?([A-Z\d]{5,17})(?:[ ]{2}|$)/m", $text, $m)) {
            $confNumber = $m[1];
        }

        if ($confNumber === '0') {
            $f->general()->noConfirmation();
        } elseif (preg_match("/^[A-Z\d]{5,8}$/", $confNumber)) {
            $f->general()->confirmation($confNumber);
        } elseif (preg_match("/^{$this->patterns['eTicket']}$/", $confNumber)) {
            $f->issued()->ticket($confNumber, false);
            $f->general()->noConfirmation();
        }

        $tableHead = $tableBody = '';

        if (preg_match("/^(?<thead>.*\S[ ]+{$this->opt($this->t('from'))}[ ]+{$this->opt($this->t('to'))} .+)\n+(?<tbody>[\s\S]+?)\n+.*\b{$this->opt($this->t('totalInclVAT'))}\.?(?:[ ]{2}|$)/im", $text, $m)) {
            $tableHead = $m['thead'];
            $tableBody = $m['tbody'];
        }

        $tablePos = $this->rowColsPos($tableHead);
        $segments = $this->splitText($tableBody, "/^([ ]{0,20}{$this->patterns['date']}\s)/mu", true);

        foreach ($segments as $sText) {
            $s = $f->addSegment();

            $table = $this->splitCols($sText, $tablePos);

            if (count($table) > 0 && preg_match("/^\s*({$this->patterns['date']})\s*$/u", $table[0], $m)) {
                $s->departure()->day2($this->normalizeDate($m[1]))->noDate()->noCode();
                $s->arrival()->noDate()->noCode();
            }

            if (count($table) > 1) {
                $s->departure()->name(preg_replace('/\s+/', ' ', $table[1]));
            }

            if (count($table) > 2) {
                $s->arrival()->name(preg_replace('/\s+/', ' ', $table[2]));
            }

            if (count($table) > 3 && preg_match("/^EUROSTAR$/i", preg_replace('/\s+/', ' ', $table[3]))) {
                $this->logger->debug('Found signs of train on flight segments.');
                $isTrain = true;
            }

            if (count($table) > 4 && preg_match("/^\s*(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<number>\d+)\s*$/", $table[4], $m)) {
                $s->airline()->name($m['name'])->number($m['number']);
            }

            if (count($table) > 5) {
                if (preg_match("/^[\s(]*([A-Z]{1,2})[)\s]*$/", $table[5], $m)) {
                    $s->extra()->bookingCode($m[1]);
                } elseif (preg_match("/^(.+?)[\s(]+([A-Z]{1,2})[)\s]*$/", $table[5], $m)) {
                    $s->extra()->cabin($m[1])->bookingCode($m[2]);
                } else {
                    $s->extra()->cabin(preg_replace('/\s+/', ' ', $table[5]));
                }
            }
        }

        // price (cost + fees)

        $feesText = '';

        if (preg_match("/^([\s\S]*?)\n+(.*{$this->opt($this->t('TRANSACTION FEE'))}[\s\S]*)$/", $text, $m)) {
            $text = $m[1];
            $feesText = $m[2];
        }

        if (preg_match_all("/^[ ]{0,40}(.*{$this->opt($this->t('TRANSACTION FEE'))}.*?)(?:[ ]{2}|[ ]+{$this->opt($this->t('Ref'))}[. ]*:|$)/m", $feesText, $feeHeaderMatches)
            && count(array_unique($feeHeaderMatches[1])) === 1 && preg_match("/{$this->opt($this->t('TRANSACTION FEE'))}\s*-\s*RAIL$/i", $feeHeaderMatches[1][0])
        ) {
            $this->logger->debug('Found signs of train on TRANSACTION FEE.');
            $isTrain = true;
        }

        if ($isTrain) {
            // it-892093642.eml
            $this->logger->debug('Found train itinerary.');
            $email->removeItinerary($f);
            $this->isJunk[] = 'YES';

            return;
        }

        $parsedIts[] = $f;
        $this->isJunk[] = 'NO';

        $priceTable = $this->parsePriceTable($text, 'flight');

        if (count($priceTable) === 0) {
            $this->logger->debug('Wrong flight price table!');

            return;
        }

        $currency = $priceTable[0]['currency'];
        $f->price()->currency($currency);

        $costAmounts = $costCurrencies = [];

        foreach ($priceTable as $priceItem) {
            if ($priceItem['name'] === null) {
                $costAmounts[] = $priceItem['amount'];
                $costCurrencies[] = $priceItem['currency'];
            } elseif ($priceItem['currency'] === $currency) {
                $f->price()->fee($priceItem['name'], $priceItem['amount']);
            }
        }

        if (count(array_unique($costCurrencies)) === 1) {
            if ($feesText || count($f->getPrice()->getFees()) > 0) {
                $f->price()->cost(array_sum($costAmounts));
            } else {
                $f->price()->total(array_sum($costAmounts));
            }
        }

        // price (+ transaction fees)

        $feeSections = $this->splitText($feesText, "/^(.*{$this->opt($this->t('TRANSACTION FEE'))}.*)$/m", true);

        foreach ($feeSections as $feeText) {
            $feeName = $this->re("/^[ ]{0,40}(\S.*?\S)[ ]{2,}{$this->opt($this->t('Ref'))}[. ]*:/", $feeText);
            $refValue = $this->re("/[ ]{2}{$this->opt($this->t('Ref'))}[. ]*[:]+[ ]*([^:\s].*)$/im", $feeText);

            if (!$refValue || !$confNumber) {
                continue;
            }

            if (!preg_match("/^[0]*{$this->opt($refValue)}/i", $confNumber) && !preg_match("/^[0]*{$this->opt($confNumber)}/i", $refValue)) {
                continue;
            }

            $feeAmounts = $feeCurrencies = [];
            $priceFeeTable = $this->parsePriceTable($feeText);

            foreach ($priceFeeTable as $priceItem) {
                if ($priceItem['name'] === null) {
                    $feeAmounts[] = $priceItem['amount'];
                    $feeCurrencies[] = $priceItem['currency'];
                } elseif ($priceItem['currency'] === $currency) {
                    $f->price()->fee($priceItem['name'], $priceItem['amount']);
                }
            }

            if (count(array_unique($feeCurrencies)) === 1 && $feeCurrencies[0] === $currency) {
                $f->price()->fee($feeName, array_sum($feeAmounts));
            }
        }
    }

    private function parseCars(Email $email, string $text, array &$parsedIts): void
    {
        $this->logger->debug(__FUNCTION__ . '()');
        $confNumber = $confNumberTitle = null;

        if (preg_match("/[ ]{2}({$this->opt($this->t('Ref'))})[. ]*[:]+[ ]*([^:\s].*)$/im", $text, $m)) {
            $confNumber = $m[2];
            $confNumberTitle = $m[1];
        }

        if (empty($confNumber) && preg_match("/^[ ]{0,40}CAR RENTAL ?- ?([A-Z\d]{5,17})(?:[ ]{2}|$)/m", $text, $m)) {
            $confNumber = $m[1];
            $confNumberTitle = null;
        }

        $tableHead = $tableBody = '';

        if (preg_match("/^(?<thead>.*\S[ ]+{$this->opt($this->t('from'))}[ ]+{$this->opt($this->t('to'))} .+)\n+(?<tbody>[\s\S]+?)\n+.*\b{$this->opt($this->t('totalInclVAT'))}\.?(?:[ ]{2}|$)/im", $text, $m)) {
            $tableHead = $m['thead'];
            $tableBody = $m['tbody'];
        }

        $tablePos = $this->rowColsPos($tableHead);
        $cars = $this->splitText($tableBody, "/^(.+? {$this->patterns['date']}\s)/mu", true);

        foreach ($cars as $carText) {
            $car = $email->add()->rental();

            if ($confNumber === '0') {
                $car->general()->noConfirmation();
            } else {
                $car->general()->confirmation($confNumber, $confNumberTitle);
            }

            $table = $this->splitCols($carText, $tablePos);

            if (count($table) > 0) {
                $car->pickup()->location(preg_replace('/\s+/', ' ', $table[0]));
                $car->dropoff()->noLocation();
            }

            if (count($table) > 1 && preg_match("/^\s*({$this->patterns['date']})\s*$/u", $table[1], $m)) {
                $car->pickup()->date2($this->normalizeDate($m[1]));
            }

            if (count($table) > 2 && preg_match("/^\s*({$this->patterns['date']})\s*$/u", $table[2], $m)) {
                $car->dropoff()->date2($this->normalizeDate($m[1]));
            }

            if (count($table) > 4) {
                $car->extra()->company(preg_replace('/\s+/', ' ', $table[4]));
            }

            $parsedIts[] = $car;
            $this->isJunk[] = 'NO';
        }

        if (count($cars) > 1) {
            return;
        }

        // price (cost + fees)

        $feesText = '';

        if (preg_match("/^([\s\S]*?)\n+(.*{$this->opt($this->t('TRANSACTION FEE'))}[\s\S]*)$/", $text, $m)) {
            $text = $m[1];
            $feesText = $m[2];
        }

        $priceTable = $this->parsePriceTable($text);

        if (count($priceTable) === 0) {
            $this->logger->debug('Wrong car price table!');

            return;
        }

        $currency = $priceTable[0]['currency'];
        $car->price()->currency($currency);

        $costAmounts = $costCurrencies = [];

        foreach ($priceTable as $priceItem) {
            if ($priceItem['name'] === null) {
                $costAmounts[] = $priceItem['amount'];
                $costCurrencies[] = $priceItem['currency'];
            } elseif ($priceItem['currency'] === $currency) {
                $car->price()->fee($priceItem['name'], $priceItem['amount']);
            }
        }

        if (count(array_unique($costCurrencies)) === 1) {
            if ($feesText || count($car->getPrice()->getFees()) > 0) {
                $car->price()->cost(array_sum($costAmounts));
            } else {
                $car->price()->total(array_sum($costAmounts));
            }
        }

        // price (+ transaction fees)

        $feeSections = $this->splitText($feesText, "/^(.*{$this->opt($this->t('TRANSACTION FEE'))}.*)$/m", true);

        foreach ($feeSections as $feeText) {
            $feeName = $this->re("/^[ ]{0,40}(\S.*?\S)[ ]{2,}{$this->opt($this->t('Ref'))}[. ]*:/", $feeText);
            $refValue = $this->re("/[ ]{2}{$this->opt($this->t('Ref'))}[. ]*[:]+[ ]*([^:\s].*)$/im", $feeText);

            if (!$refValue || !$confNumber) {
                continue;
            }

            if (!preg_match("/^[0]*{$this->opt($refValue)}/i", $confNumber) && !preg_match("/^[0]*{$this->opt($confNumber)}/i", $refValue)) {
                continue;
            }

            $feeAmounts = $feeCurrencies = [];
            $priceFeeTable = $this->parsePriceTable($feeText);

            foreach ($priceFeeTable as $priceItem) {
                if ($priceItem['name'] === null) {
                    $feeAmounts[] = $priceItem['amount'];
                    $feeCurrencies[] = $priceItem['currency'];
                } elseif ($priceItem['currency'] === $currency) {
                    $car->price()->fee($priceItem['name'], $priceItem['amount']);
                }
            }

            if (count(array_unique($feeCurrencies)) === 1 && $feeCurrencies[0] === $currency) {
                $car->price()->fee($feeName, array_sum($feeAmounts));
            }
        }
    }

    private function parseHotels(Email $email, string $text, array &$parsedIts): void
    {
        $this->logger->debug(__FUNCTION__ . '()');
        $confNumber = $confNumberTitle = null;

        if (preg_match("/[ ]{2}({$this->opt($this->t('Ref'))})[. ]*[:]+[ ]*([^:\s].*)$/im", $text, $m)) {
            $confNumber = $m[2];
            $confNumberTitle = $m[1];
        }

        if (empty($confNumber) && preg_match("/^[ ]{0,40}HOTEL RESERVATION ?- ?([A-Z\d]{5,17})(?:[ ]{2}|$)/m", $text, $m)) {
            $confNumber = $m[1];
            $confNumberTitle = null;
        }

        $tableHead = $tableBody = '';

        if (preg_match("/^(?<thead>[ ]{0,20}{$this->opt($this->t('from'))}[ ]+{$this->opt($this->t('to'))} .+)\n+(?<tbody>[\s\S]+?)\n+.*\b{$this->opt($this->t('totalInclVAT'))}\.?(?:[ ]{2}|$)/im", $text, $m)) {
            $tableHead = $m['thead'];
            $tableBody = $m['tbody'];
        }

        $tablePos = $this->rowColsPos($tableHead);
        $hotels = $this->splitText($tableBody, "/^([ ]{0,20}{$this->patterns['date']}\s)/mu", true);

        foreach ($hotels as $hText) {
            $h = $email->add()->hotel();

            if ($confNumber === '0') {
                $h->general()->noConfirmation();
            } else {
                $h->general()->confirmation($confNumber, $confNumberTitle);
            }

            $table = $this->splitCols($hText, $tablePos);

            if (count($table) > 0 && preg_match("/^\s*({$this->patterns['date']})\s*$/u", $table[0], $m)) {
                $h->booked()->checkIn2($this->normalizeDate($m[1]));
            }

            if (count($table) > 1 && preg_match("/^\s*({$this->patterns['date']})\s*$/u", $table[1], $m)) {
                $h->booked()->checkOut2($this->normalizeDate($m[1]));
            }

            if (count($table) > 3 && preg_match("/^\s*(\d{1,3})\s*$/", $table[3], $m)) {
                $h->booked()->guests($m[1]);
            }

            if (count($table) > 4) {
                $h->hotel()->name(preg_replace('/\s+/', ' ', $table[4]))->noAddress();
            }

            $parsedIts[] = $h;
            $this->isJunk[] = 'NO';
        }

        if (count($hotels) > 1) {
            return;
        }

        // price (cost + fees)

        $feesText = '';

        if (preg_match("/^([\s\S]*?)\n+(.*{$this->opt($this->t('TRANSACTION FEE'))}[\s\S]*)$/", $text, $m)) {
            $text = $m[1];
            $feesText = $m[2];
        }

        $priceTable = $this->parsePriceTable($text);

        if (count($priceTable) === 0) {
            $this->logger->debug('Wrong hotel price table!');

            return;
        }

        $currency = $priceTable[0]['currency'];
        $h->price()->currency($currency);

        $costAmounts = $costCurrencies = [];

        foreach ($priceTable as $priceItem) {
            if ($priceItem['name'] === null) {
                $costAmounts[] = $priceItem['amount'];
                $costCurrencies[] = $priceItem['currency'];
            } elseif ($priceItem['currency'] === $currency) {
                $h->price()->fee($priceItem['name'], $priceItem['amount']);
            }
        }

        if (count(array_unique($costCurrencies)) === 1) {
            if ($feesText || count($h->getPrice()->getFees()) > 0) {
                $h->price()->cost(array_sum($costAmounts));
            } else {
                $h->price()->total(array_sum($costAmounts));
            }
        }

        // price (+ transaction fees)

        $feeSections = $this->splitText($feesText, "/^(.*{$this->opt($this->t('TRANSACTION FEE'))}.*)$/m", true);

        foreach ($feeSections as $feeText) {
            $feeName = $this->re("/^[ ]{0,40}(\S.*?\S)[ ]{2,}{$this->opt($this->t('Ref'))}[. ]*:/", $feeText);
            $refValue = $this->re("/[ ]{2}{$this->opt($this->t('Ref'))}[. ]*[:]+[ ]*([^:\s].*)$/im", $feeText);

            if (!$refValue || !$confNumber) {
                continue;
            }

            if (!preg_match("/^[0]*{$this->opt($refValue)}/i", $confNumber) && !preg_match("/^[0]*{$this->opt($confNumber)}/i", $refValue)) {
                continue;
            }

            $feeAmounts = $feeCurrencies = [];
            $priceFeeTable = $this->parsePriceTable($feeText);

            foreach ($priceFeeTable as $priceItem) {
                if ($priceItem['name'] === null) {
                    $feeAmounts[] = $priceItem['amount'];
                    $feeCurrencies[] = $priceItem['currency'];
                } elseif ($priceItem['currency'] === $currency) {
                    $h->price()->fee($priceItem['name'], $priceItem['amount']);
                }
            }

            if (count(array_unique($feeCurrencies)) === 1 && $feeCurrencies[0] === $currency) {
                $h->price()->fee($feeName, array_sum($feeAmounts));
            }
        }
    }

    private function parsePriceTable(string $text, ?string $type = null): array
    {
        $result = []; // Item example: ['name' => null, 'amount' => null, 'currency' => null]
        $alignRight = $type === 'flight';
        $tableHeadText = $tableBodyText = '';

        if (preg_match("/\n(?<thead>.*[ ]+{$this->opt($this->t('totalInclVAT'))}\.?)\n+(?<tbody>[ ]*\S[\s\S]*?)(?:\n\n|$)/i", $text, $m)) {
            $tableHeadText = $m['thead'];
            $tableBodyText = $m['tbody'];
        }

        $tableHeadPos = array_values(array_unique(array_merge([0], $this->rowColsPos($tableHeadText, $alignRight))));
        $tableHead = array_map('trim', $this->splitCols($tableHeadText, $tableHeadPos));
        $targetIndex = count($tableHead) - 1;

        if (count($tableHead) === 0 || !preg_match("/^{$this->opt($this->t('totalInclVAT'))}\.?$/i", $tableHead[$targetIndex])) {
            return [];
        }

        $priceRows = $this->splitText($tableBodyText, "/^(.*\d\D*)$/m", true);

        foreach ($priceRows as $pRow) {
            $table = array_map('trim', $this->splitCols($pRow, $tableHeadPos));
            $tableV2 = array_map('trim', $this->splitCols($pRow, $this->rowColsPos($pRow)));

            if (!array_key_exists($targetIndex, $table) || $table[$targetIndex] === '') {
                continue;
            }

            $price = [];
            $price['name'] = count($tableV2) > 0 && preg_match("/^\D*[[:alpha:]]\D*$/u", $tableV2[0]) ? $tableV2[0] : null;
            $totalPrice = preg_replace('/\s+/', ' ', $table[$targetIndex]);

            if (preg_match('/^(?<amount>\d[,.’‘\'\d ]*?)[ ]*(?<currency>[^\-\d)(]+)$/u', $totalPrice, $matches)) {
                $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
                $price['amount'] = PriceHelper::parse($matches['amount'], $currencyCode);
                $price['currency'] = $matches['currency'];
                $result[] = $price;
            }
        }

        return $result;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]amexgbt\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return array_key_exists('subject', $headers) && stripos($headers['subject'], 'Amex GBT DOCUMENT #') !== false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (!$textPdf || stripos($textPdf, 'American Express') === false) {
                continue;
            }

            if ($this->assignLang($textPdf)) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (!$textPdf) {
                continue;
            }

            if ($this->assignLang($textPdf)) {
                $this->parsePdf($email, $textPdf);

                break;
            }
        }

        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
        }
        $email->setType('GBTDocumentPdf' . ucfirst($this->lang));

        $this->logger->info('isJunk list:');
        $this->logger->debug(print_r($this->isJunk, true));

        if (count(array_unique($this->isJunk)) === 1 && $this->isJunk[0] === 'YES') {
            $email->setIsJunk(true, 'train itinerary without times');
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

    private function assignLang(?string $text): bool
    {
        if ( empty($text) || !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['passenger']) || empty($phrases['from']) || empty($phrases['to']) ) {
                continue;
            }
            if (preg_match("/^[ ]*{$this->opt($phrases['passenger'])}[ ]*:/im", $text) > 0
                && preg_match("/\s{$this->opt($phrases['from'])}[ ]+{$this->opt($phrases['to'])}\s/i", $text) > 0
            ) {
                $this->lang = $lang;
                return true;
            }
        }
        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return $phrase;
        }
        if ($lang === '') {
            $lang = $this->lang;
        }
        if ( empty(self::$dictionary[$lang][$phrase]) ) {
            return $phrase;
        }
        return self::$dictionary[$lang][$phrase];
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

    private function re(string $re, ?string $str, $c = 1): ?string
    {
        if (preg_match($re, $str ?? '', $m) && array_key_exists($c, $m)) {
            return $m[$c];
        }

        return null;
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

    private function rowColsPos(?string $row, bool $alignRight = false): array
    {
        if ($row === null) { return []; }
        $pos = [];
        $head = preg_split('/\s{2,}/', $row, -1, PREG_SPLIT_NO_EMPTY);
        $lastpos = 0;
        foreach ($head as $word) {
            $posStart = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $wordLength = mb_strlen($word, 'UTF-8');
            $pos[] = $alignRight ? $posStart + $wordLength : $posStart;
            $lastpos = $posStart + $wordLength;
        }
        if ($alignRight) {
            array_pop($pos);
            $pos = array_merge([0], $pos);
        }
        return $pos;
    }

    private function splitCols(?string $text, ?array $pos = null): array
    {
        $cols = [];
        if ($text === null)
            return $cols;
        $rows = explode("\n", $text);
        if ($pos === null || count($pos) === 0) $pos = $this->rowColsPos($rows[0]);
        arsort($pos);
        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $cols[$k][] = rtrim(mb_substr($row, $p, null, 'UTF-8'));
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);
        foreach ($cols as &$col) $col = implode("\n", $col);
        return $cols;
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

    /**
     * @param string|null $text Unformatted string with date
     * @return string
     */
    private function normalizeDate(?string $text): string
    {
        if ( !is_string($text) || empty($text) )
            return '';
        $in = [
            // 16/03/2025
            '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/',
        ];
        $out[0] = $this->enDatesInverted === true ? '$2/$1/$3' : '$1/$2/$3';
        return preg_replace($in, $out, $text);
    }
}
