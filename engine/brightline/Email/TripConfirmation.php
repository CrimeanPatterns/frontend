<?php

namespace AwardWallet\Engine\brightline\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Train;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class TripConfirmation extends \TAccountChecker
{
    public $mailFiles = "brightline/it-581668582.eml, brightline/it-682405518.eml, brightline/it-684700676.eml, brightline/it-685568105.eml, brightline/it-888574899.eml, brightline/it-889022116.eml, brightline/it-891310153.eml, brightline/it-893232411.eml, brightline/it-895506363.eml, brightline/it-911085957.eml";

    public $detectSubjects = [
        'en' => [
            'Your Trip Confirmation:',
            'Your Trip has been Modified',
        ],
    ];

    public $lang = 'en';
    public $confirmation = null;
    public $description = null;
    public $pdfNamePattern = ".*pdf";

    public static $dictionary = [
        "en" => [
            'Departs'                => ['Departs', 'DEPARTS'],
            'Your Trip Confirmation' => ['Your Trip Confirmation', 'Your Trip has been Modified'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers["from"], 'mail.gobrightline.com') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider and Format for html
        if (// detect Provider
            $this->http->XPath->query("//text()[{$this->contains($this->t('Brightline'))}]")->length > 0
            && $this->http->XPath->query("//a/@href[{$this->contains(['gobrightline.com'])}]")->length > 0
            // detect Format
            && $this->http->XPath->query("//text()[{$this->eq($this->t('OUTBOUND TRIP DETAILS'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Boarding Closes'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Ticket Number'))}]")->length > 0
        ) {
            return true;
        }

        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            // detect Provider and Format for pdf
            if (// detect Provider
                stripos($text, 'Brightline App') !== false
                // detect Format
                && stripos($text, 'BOARDING CLOSES') !== false
                && stripos($text, 'prior to departure') !== false
                && stripos($text, 'Extras') !== false
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]mail\.gobrightline\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if (preg_match("/(?<confDesc>{$this->opt($this->t('Your Trip Confirmation'))})[\:\-\s]+(?<confNumber>[A-Z\d]{6})\s*(\||$)/", $parser->getSubject(), $m)) {
            $this->description = 'Trip Confirmation';
            $this->confirmation = $m['confNumber'];
        }

        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        $type = '';

        if (count($pdfs) > 0) {
            foreach ($pdfs as $pdf) {
                $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

                // parse main reservation info
                if (stripos($text, 'Brightline App') !== false
                    && stripos($text, 'BOARDING CLOSES') !== false
                    && stripos($text, 'prior to departure') !== false
                    && stripos($text, 'Extras') !== false
                ) {
                    $this->ParseTrainPDF($email, $text);
                    $type = 'Pdf';
                }

                // parse pricing info
                if (stripos($text, 'Trip Details') !== false
                    && stripos($text, 'Cost Summary') !== false
                    && stripos($text, 'Guest Details') !== false
                ) {
                    $this->PricePDF($email, $text);
                    $type = 'Pdf';
                }

                if (empty($type) && empty($text)) {
                    $this->ParseTrain($email);
                    $type = 'Html';
                }
            }
        } else {
            $this->ParseTrain($email);
            $type = 'Html';
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang) . $type);

        return $email;
    }

    public function ParseTrain(Email $email)
    {
        $t = $email->add()->train();

        $t->general()
            ->confirmation($this->confirmation, $this->description);

        $travellers = [];
        $classes = explode(',', strtoupper(implode(',', array_unique(
            $this->http->FindNodes("//text()[{$this->eq($this->t('Class:'))}]/following::text()[normalize-space()][1]")))));

        if (!empty($classes) && $this->http->XPath->query("//tr[{$this->starts($this->t('FARE-'))}]/preceding-sibling::tr[1][{$this->starts($classes)}]")->length == 0) {
            $travellers = $this->http->FindNodes("//tr[{$this->starts($this->t('FARE-'))}]/preceding-sibling::tr[1][contains(., ' ')][descendant-or-self::*/@style[{$this->contains(['font-weight: 700', 'font-weight:700'])}]]");
        }

        if (empty($travellers)) {
            $travellers = array_unique(array_filter(preg_replace("/^\s*Passenger Name\s*:\s*/", '',
                $this->http->FindNodes("//text()[starts-with(normalize-space(), 'Ticket Number:')]/ancestor::tr[1]/descendant::text()[normalize-space()][not(contains(normalize-space(), 'Ticket'))][string-length()>3]"))));
        }

        $t->general()
            ->travellers($travellers);

        $tickets = array_unique($this->http->FindNodes("//text()[starts-with(normalize-space(), 'Ticket Number:')]", null, "/{$this->opt($this->t('Ticket Number:'))}\s*([A-Z\d]+)/"));

        foreach ($tickets as $ticket) {
            if ($ticket !== $this->confirmation) {
                $t->addTicketNumber($ticket, false);
            }
        }

        $this->Price($t);

        $nodes = $this->http->XPath->query("//text()[{$this->starts($this->t('Departs'))}][not(ancestor::*/@style[{$this->contains(['display:none', 'display: none'])}])]");

        foreach ($nodes as $root) {
            $s = $t->addSegment();

            $date = $this->http->FindSingleNode("./ancestor::table[starts-with(normalize-space(), 'Boarding Closes')][1]/descendant::text()[starts-with(normalize-space(), 'Boarding Closes')]/following::text()[normalize-space()][1]", $root);

            $depInfo = $this->http->FindSingleNode("./ancestor::td[1]", $root);

            if (preg_match("/^(?<depName>.+)\s+Departs\s+(?<depTime>[\d\:]+\s*[AP]M)\s*$/i", $depInfo, $m)) {
                $s->departure()
                    ->name($m['depName'])
                    ->date(strtotime($date . ' ' . $m['depTime']));
            }

            $arrInfo = $this->http->FindSingleNode("./ancestor::td[1]/following-sibling::td[2]", $root);

            if (preg_match("/^(?<arrName>.+)\s+Arrives\s+(?<arrTime>[\d\:]+\s*[AP]M)\s*$/i", $arrInfo, $m)) {
                $s->arrival()
                    ->name($m['arrName'])
                    ->date(strtotime($date . ' ' . $m['arrTime']));
            }

            $s->setNoNumber(true);

            $duration = $this->http->FindSingleNode("./ancestor::table[starts-with(normalize-space(), 'Boarding Closes')][1]/descendant::text()[starts-with(normalize-space(), 'Duration')]/following::text()[normalize-space()][1]", $root, true, "/^([\d\:]+(?:[ ]*min)?)$/");

            if (!empty($duration)) {
                $s->setDuration($duration);
            }

            $cabin = $this->http->FindSingleNode("./ancestor::table[starts-with(normalize-space(), 'Boarding Closes')][1]/descendant::text()[normalize-space() = 'Class:']/following::text()[normalize-space()][1]", $root, true, "/^([A-Z\s]+)$/i");

            if (!empty($cabin)) {
                $s->setCabin($cabin);
            }
        }
    }

    public function ParseTrainPDF(Email $email, string $text)
    {
        $t = $email->add()->train();
        $t->general()
            ->confirmation($this->confirmation, $this->description);

        $textArray = array_filter(preg_split("/BOARDING CLOSES/", $text));

        $tickets = [];
        $travellers = [];

        foreach ($textArray as $textSegment) {
            $s = $t->addSegment();

            $traveller = $this->re("/\n[ ]*([[:alpha:]][-.\'[:alpha:]\s]*[[:alpha:]])\n\s+Coach/u", $textSegment);
            $travellers[] = $traveller;

            $ticket = $this->re("/Ticket\s*[#]\s*([A-Z\d]{5,})\n/", $textSegment);

            if (empty($ticket)) {
                $ticket = $this->re("/Ticket\s*[#]?(?:.*\n){1,2}(?:.* {2,})?([A-Z\d]{5,})\n/", $textSegment);
            }

            if (empty($ticket)) {
                $ticket = $this->re("/Ticket\s*(?:.*\n){1,3}(?:.* {2,})?([A-Z\d]{5,})\n/", $textSegment);
            }
            $tickets[] = ['number' => $ticket, 'traveller' => $traveller];

            $date = $this->re("/Extras\s+(\w+[^\n\w]+\w+[^\n\w]+\d{4}\b)(?: {2,}|\n)/", $textSegment);

            if (empty($date)) {
                $temp = preg_replace(["/\s+Extras\b/", "/\s+Ticket ?#/"], "\n", $textSegment);
                $temp = preg_replace("/(\n[ ]*[A-Z]{3} {2,}[A-Z]{3}\s*\n)\s*([^\d\n]+\n){1,2}/", '$1', $temp);
                $temp = preg_replace("/(?: {2,}|\n)([A-Z\d]{5,})(?: {2,}|\n)/", "\n", $temp);
                $date = $this->re("/\n[ ]*[A-Z]{3} {2,}[A-Z]{3}\s*\n\s*(\w+\W+\w+\W+\d{4})\s*\n/", $temp);
                $date = preg_replace("/\s+/", ' ', $date);
            }

            $depTime = $this->re("/\n[ ]*(\d{1,2}\:\d{2}[ ]*[AP]M)[ ]+\d{1,2}\:\d{2}[ ]*[AP]M\s*\n/", $textSegment);
            $arrTime = $this->re("/\n[ ]*\d{1,2}\:\d{2}[ ]*[AP]M[ ]+(\d{1,2}\:\d{2}[ ]*[AP]M)\s*\n/", $textSegment);

            $depCode = $this->re("/\n[ ]*([A-Z]{3}) {2,}[A-Z]{3}\s*\n/", $textSegment);
            $arrCode = $this->re("/\n[ ]*[A-Z]{3} {2,}([A-Z]{3})\s*\n/", $textSegment);

            if (preg_match("/{$arrCode}\s+(?<depName>.+?)[ ]{5,}(?<arrName>.+?)[ ]*\n/", $textSegment, $m)) {
                $s->departure()->name($m['depName']);
                $s->arrival()->name($m['arrName']);
            }

            $s->setNoNumber(true);

            $s->departure()
                ->code($depCode)
                ->date((!empty($date) && !empty($depTime)) ? strtotime($date . ', ' . $depTime) : null);

            $s->arrival()
                ->code($arrCode)
                ->date((!empty($date) && !empty($arrTime)) ? strtotime($date . ', ' . $arrTime) : null);

            if (preg_match("/Coach\s*(?<coach>\S+)\s*(?<seat>\d+[A-Z])\n+\s*(?<cabin>[A-Z ]{3,})\n/", $textSegment, $m)) {
                $s->setCarNumber($m['coach']);
                $s->addSeat($m['seat'], true, true, $traveller);
                $s->setCabin(preg_replace("/ (\w)\b/", "$1", $m['cabin']));
            }

            $segments = $t->getSegments();

            foreach ($segments as $segment) {
                if ($segment->getId() !== $s->getId()) {
                    if (serialize(array_diff_key($segment->toArray(),
                            ['seats' => [], 'assignedSeats' => []])) === serialize(array_diff_key($s->toArray(), ['seats' => [], 'assignedSeats' => []]))) {
                        foreach ($s->toArray()['assignedSeats'] as $seatsAr) {
                            $segment->extra()->seat($seatsAr[0], true, true, $seatsAr[1]);
                        }
                        $t->removeSegment($s);

                        break;
                    }
                }
            }
        }

        $tickets = array_unique($tickets, SORT_REGULAR);

        foreach ($tickets as $ticket) {
            $t->addTicketNumber($ticket['number'], false, $ticket['traveller']);
        }

        $t->general()
            ->travellers(array_unique($travellers));

        $this->Price($t);
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    // parse price info from html
    private function Price(Train $t)
    {
        $price = $this->http->FindSingleNode("//text()[normalize-space()='Total']/ancestor::tr[1]/descendant::td[normalize-space()][last()]", null, true, "/^(\D{1,3}[\d\.\,]+)$/");

        if (preg_match("/^(?<currency>\D{1,3})(?<total>[\d\.\,]+)$/", $price, $m)) {
            $currency = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Fare-')]", null, true, "/{$this->opt($this->t('Fare-'))}\s*([A-Z]{3})/");

            if (empty($currency)) {
                $currency = $m['currency'];
            }

            $t->price()
                ->total(PriceHelper::parse($m['total'], $currency))
                ->currency($currency);

            $costsText = $this->http->FindNodes("//text()[starts-with(normalize-space(), 'Fare-') or starts-with(normalize-space(), 'FARE-')]/ancestor::tr[1]/descendant::td[normalize-space()][last()]", null, "/^\D{1,3}([\d\.\,]+)/");
            $cost = 0.0;

            foreach ($costsText as $cText) {
                $cost += PriceHelper::parse($cText, $currency);
            }

            if (!empty($cost)) {
                $t->price()
                    ->cost($cost);
            }

            $feeNodes = $this->http->XPath->query("//text()[{$this->starts(['Fare-', 'FARE-'])}]/ancestor::tr[1]/following-sibling::tr[normalize-space()][not({$this->starts(['Fare-', 'FARE-'])})][count(*[normalize-space()]) > 1]");

            foreach ($feeNodes as $feeRoot) {
                $feeName = $this->http->FindSingleNode("./descendant::td[1]", $feeRoot);
                $feeSumm = $this->http->FindSingleNode("./descendant::td[normalize-space()][last()]", $feeRoot);

                if (preg_match("/^(?:(?<minus>\-))?\D{1,3}(?<feeSumm>[\d\.\,]+)$/u", $feeSumm, $m)) {
                    $v = PriceHelper::parse($m['feeSumm'], $currency);

                    if (stripos($feeName, 'Discount') !== false || stripos($feeName, 'BL Credit/Promo Code') !== false) {
                        $t->price()
                            ->discount($v);
                    } elseif (!empty($v)) {
                        $t->price()
                            ->fee($feeName, $v);
                    }
                }
            }
        }
    }

    // parse price info from pdf (if not in html)
    private function PricePDF(Email $email, string $text)
    {
        $t = $email->getItineraries()[0];

        if (!empty($t->getPrice())) {
            return false;
        }

        $currency = null;

        // collect total and currency
        if (preg_match("/$\s*{$this->opt($this->t('Total'))}\s*\((?<currency>[A-Z]{3})\)\s*[^\w\s]\s*(?<total>[\d\.\,\']+)\s*$/m", $text, $m)) {
            $currency = $m['currency'];
            $t->price()
                ->currency($currency)
                ->total(PriceHelper::parse($m['total'], $currency));
        }

        if (empty($currency)) {
            return false;
        }

        // prepare price text
        $headersText = $this->re("/({$this->opt($this->t('Cost Summary'))}.+)$/m", $text);
        $positions = $this->rowColumnPositions($headersText);

        $costAndTravellersText = $this->re("/({$this->opt($this->t('Cost Summary'))}.+?)\s*(?:{$this->opt($this->t('Payment Details'))}|{$this->opt($this->t('Total'))})/s", $text);
        $table = $this->createTable($costAndTravellersText, $positions);

        // collect cost and fees (extra services)
        $costSummary = $this->re("/(^.+?)(?:{$this->opt($this->t('Other'))}|$)/s", $table[0]);

        $cost = null;
        $fees = [];

        foreach (explode("\n", $costSummary) as $costRow) {
            if (preg_match("/^\s*\w+\s+(?<feeName>.+?)?\s+[^\d\s]\s*(?<isDiscount>\-)?(?<amount>[\d\.\,\']+)\s*$/m", $costRow, $m)) {
                $amount = PriceHelper::parse($m['amount'], $currency);

                if (!empty($m['isDiscount'])) {
                    $t->price()
                        ->discount($amount);

                    continue;
                }

                if (empty($m['feeName']) || !preg_match("/[a-z]/", $m['feeName']) || empty($amount)) {
                    $cost += $amount;

                    continue;
                }

                if (isset($fees[$m['feeName']])) {
                    $fees[$m['feeName']] += $amount;
                } else {
                    $fees[$m['feeName']] = $amount;
                }
            }
        }

        if (isset($cost)) {
            $t->price()
                ->cost($cost, $currency);
        }

        // collect discounts and fees (taxes)
        $feesSummary = $this->re("/({$this->opt($this->t('Other'))}.+)/s", $table[0]);

        foreach (explode("\n", $feesSummary) as $feeRow) {
            if (preg_match("/^\s*(?<feeName>\S.+?\S)\s+[^\w\s]\s*(?<isDiscount>\-)?(?<amount>[\d\.\,\']+)\s*$/m", $feeRow, $m)) {
                $amount = PriceHelper::parse($m['amount'], $currency);

                if (!empty($m['isDiscount'])) {
                    $t->price()
                        ->discount($amount);

                    continue;
                }

                if (isset($fees[$m['feeName']])) {
                    $fees[$m['feeName']] += $amount;
                } else {
                    $fees[$m['feeName']] = $amount;
                }
            }
        }

        foreach ($fees as $feeName => $amount) {
            $t->price()
                ->fee($feeName, $amount);
        }

        return true;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function eq($field, $node = 'normalize-space(.)'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return $node . '="' . $s . '"';
        }, $field)) . ')';
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

    private function createTable(?string $text, $pos = []): array
    {
        $cols = [];
        $rows = explode("\n", $text);

        if (!$pos) {
            $pos = $this->rowColumnPositions($rows[0]);
        }
        arsort($pos);

        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $cols[$k][] = trim(mb_substr($row, $p, null, 'UTF-8'));
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);

        foreach ($cols as &$col) {
            $col = implode("\n", $col);
        }

        return $cols;
    }

    private function rowColumnPositions(?string $row): array
    {
        $head = array_filter(array_map('trim', explode("|", preg_replace("/\s{2,}/", "|", $row))));
        $pos = [];
        $lastpos = 0;

        foreach ($head as $word) {
            $pos[] = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $lastpos = mb_strpos($row, $word, $lastpos, 'UTF-8') + mb_strlen($word, 'UTF-8');
        }

        return $pos;
    }
}
