<?php

namespace AwardWallet\Engine\fcmtravel\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ItineraryPDF extends \TAccountChecker
{
    public $mailFiles = "fcmtravel/it-251159643.eml, fcmtravel/it-914698711.eml";
    public $lang = 'en';
    public $emailProv = ['@in.fcm.travel', '@fcmonline.in'];
    public $pdfNamePattern = "(?:[A-Z\d]{8,}|Ticket).*pdf";
    public $pdfNamePattern2 = "Mr.*\s[A-Z]{3}\-[A-Z]{3}.*pdf";

    public static $dictionary = [
        "en" => [
            'Date :' => ['Date :', 'Generation Time'],
        ],
    ];

    private $patterns = [
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm  |  12 noon  |  3:10 午後
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:]\s]*[[:alpha:]]', // Mr. Hao-Li Huang
        'eTicket' => '\d{3}(?:\s|\s?-\s?)?\d{5,}(?:\s|\s?[-\/]\s?)?\d{1,3}', // 175-2345005149-23  |  1752345005149/23
    ];

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        if (count($pdfs) == 0) {
            $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern2);
        }

        $detectProv = $this->detectEmailFromProvider($parser->getCleanFrom()) === true;

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (empty($text) || !$detectProv && stripos($text, '@in.fcm.travel') === false) {
                continue;
            }

            if (strpos($text, 'by Air') !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        foreach ($this->emailProv as $emailFrom) {
            if (preg_match("/{$this->opt($emailFrom)}$/i", $from)) {
                return true;
            }
        }

        return false;
    }

    public function ParseFlightPDF(Email $email, $text): void
    {
        if (preg_match_all("/({$this->opt($this->t('Reference Number'))})[ ]*[:]+[ ]*([A-Z\d]{5,})$/m", $text, $refNoMatches, PREG_SET_ORDER)) {
            foreach ($refNoMatches as $m) {
                if (empty($email->getTravelAgency()) || !in_array($m[2], array_column($email->getTravelAgency()->getConfirmationNumbers(), 0))) {
                    $email->ota()->confirmation($m[2], $m[1]);
                }
            }
        }

        $f = $email->add()->flight();
        $f->general()->date(strtotime($this->re("/{$this->opt($this->t('Date :'))}\s*((?:{$this->patterns['time']}[-,; ]+)?\d{1,2}-[[:alpha:]]+-\d{4}\b(?:[-,; ]+{$this->patterns['time']})?)/u", $text)));

        $travellers = $PNRs = $tickets = $amounts = $currencies = [];
        $flightParts = $this->splitText($text, "/\n+(\s*\d+\/\d+\/\d{4}\s*\-.*\-\s*by\s*Air)/u", true, false);

        foreach ($flightParts as $flightPart) {
            if (stripos($flightPart, 'by Air') == false) {
                continue;
            }

            if (preg_match_all("/{$this->opt($this->t('Freq. Flier:'))}\s*([A-Z\d]{7,})/", $flightPart, $m)) {
                $f->setAccountNumbers(array_filter(array_unique($m[1])), false);
            }

            $paxTableText = $this->re("/\n([ ]{0,20}Passenger[: ]+Status[\s\S]+?)\n+[ ]*(?:Baggage[ ]*(?:Limit|Request)?|{$this->opt($this->t('Meal'))}|Tour Code|Mobile)[ ]*:/i", $flightPart);
            $paxTable = $this->splitCols($paxTableText);

            $passengerName = count($paxTable) > 0 && preg_match("/^\s*Passenger[: ]*\n+[ ]*({$this->patterns['travellerName']})(?:\s*\(\s*[[:alpha:]]+\s*\))?\s*$/iu", $paxTable[0], $m)
                ? $this->normalizeTraveller(preg_replace('/\s+/', ' ', $m[1])) : null;
            
            if ($passengerName && !in_array($passengerName, $travellers)) {
                $f->general()->traveller($passengerName, true);
                $travellers[] = $passengerName;
            }

            $cabin = count($paxTable) > 2 && preg_match("/^\s*Class[: ]*\n+[ ]*(\S.*?)\s*$/i", $paxTable[2], $m) ? $m[1]: null;
            
            $airlinePNR = count($paxTable) > 3 && preg_match("/^\s*Airline PNR[: ]*\n+[ ]*([A-Z\d]{5,8})\s*$/i", $paxTable[3], $m) ? $m[1] : null;

            if ($airlinePNR && !in_array($airlinePNR, $PNRs)) {
                $f->general()->confirmation($airlinePNR);
                $PNRs[] = $airlinePNR;
            }

            $ticket = count($paxTable) > 5 && preg_match("/^\s*Ticket No[: ]*\n+[ ]*({$this->patterns['eTicket']})\s*$/i", $paxTable[5], $m) ? preg_replace('/\s+/', ' ', $m[1]) : null;

            if ($ticket && !in_array($ticket, $tickets)) {
                $f->issued()->ticket($ticket, false, $passengerName);
                $tickets[] = $ticket;
            }

            $seats = preg_match_all("/{$this->opt($this->t('Seat:'))}\s*(\d+[A-Z])/", $flightPart, $seatMatches)
                ? $seatMatches[1] : [];

            $meals = preg_match_all("/{$this->opt($this->t('Meal'))}[ ]*[:]+\s*(\D+),\s*{$this->opt($this->t('Seat:'))}/", $flightPart, $mealMatches)
                ? array_filter(array_unique($mealMatches[1])) : [];

            if (preg_match("/\/(?<year>\d{4})\s.*by\s*Air\n*\s*.+\n\s*(?<airlineName>[A-Z][A-Z\d]|[A-Z\d][A-Z])[-\s]+(?<flightNumber>\d+)\n*[ ]*Departs\s*(?<depDate>.+)[ ]{4,}.*-\s*(?<depCode>[A-Z]{3})\s*\)[,\s]*(?:Terminal:\s*(?<depTerminal>.*))?\n+[ ]*Arrives\s*(?<arrDate>.+)[ ]{4,}.*-\s*(?<arrCode>[A-Z]{3})\s*\)[,\s]*(?:Terminal:\s*(?<arrTerminal>.*))?\n/u", $flightPart, $m)) {
                $s = $f->addSegment();

                $s->airline()
                    ->name($m['airlineName'])
                    ->number($m['flightNumber']);

                $s->departure()
                    ->code($m['depCode'])
                    ->date($this->normalizeDate($m['depDate'] . ' ' . $m['year']));

                if (isset($m['depTerminal']) && !empty($m['depTerminal'])) {
                    $s->departure()
                        ->terminal($m['depTerminal']);
                }

                $s->arrival()
                    ->code($m['arrCode'])
                    ->date($this->normalizeDate($m['arrDate'] . ' ' . $m['year']));

                if (isset($m['arrTerminal']) && !empty($m['arrTerminal'])) {
                    $s->arrival()
                        ->terminal($m['arrTerminal']);
                }

                $s->extra()->cabin($cabin, false, true);

                foreach ($seats as $seat) {
                    $s->extra()->seat($seat, false, false, count($seats) === 1 ? $passengerName : null);
                }

                if (count($meals) > 0) {
                    $s->extra()->meals($meals);
                }
            }

            if (preg_match("/^[ ]*Total Price[:* ]+(?<total>\d[\d.,]*)[ ]*(?<currency>[A-Z]{3})\b/m", $flightPart, $matches)) {
                // 194,269 INR
                $amounts[] = PriceHelper::parse($matches['total'], $matches['currency']);
                $currencies[] = $matches['currency'];
            }
        }

        if (count(array_unique($currencies)) === 1) {
            $f->price()->total(array_sum($amounts))->currency($currencies[0]);
        }
    }

    /*public function ParseHotelPDF(Email $email, $text)
    {
        $hotelText = $this->re("/{$this->opt($this->t('Hotel Details'))}\n(.+)\n{$this->opt($this->t('Product Details'))}/s", $text);
        $hotelParts = array_filter(preg_split("/Hotel Description\D+Passengers\n/", $hotelText));

        foreach ($hotelParts as $hotelPart) {
            if (preg_match("/^(?<hotelName>.+)\n\s*(?<address>.+)\s+(?<arrDate>[\w\-]+\d{4})\s+(?<depDate>[\w\-]+\d{4})\s+\d+\s+(.+)\s+\d+\-(?<guestCount>\d+)\n/", $hotelPart, $m)) {
                if (trim($m['hotelName'], '*') == trim($this->hotelName, '*') && strtotime($m['arrDate']) == $this->arrDate && strtotime($m['depDate']) == $this->depDate) {
                    continue;
                }

                $h = $email->add()->hotel();

                $h->general()
                    ->confirmation($this->re("/Booking\s*(\d+)\s*PNR/", $text));

                $paxText = $this->re("/{$this->opt($this->t('Invoice Details'))}\n{$this->opt($this->t('Passengers'))}\D+(.+)\n{$this->opt($this->t('Product(s)'))}/su", $text);

                if (preg_match_all("/(?:^|\n)\d\s*([[:alpha:]][-.'’[:alpha:] ]*[[:alpha:]])/", $paxText, $match)) {
                    $match[1] = array_filter(array_unique(($match[1])));

                    $h->general()
                        ->travellers(str_replace(['MSTR', 'MRS', 'MR', 'MS'], '', array_filter($match[1])), true);
                }

                $h->hotel()
                    ->name(trim($m['hotelName'], '*'))
                    ->address($m['address']);

                $h->booked()
                    ->checkIn(strtotime($m['arrDate']))
                    ->checkOut(strtotime($m['depDate']))
                    ->guests($m['guestCount']);

                $this->hotelName = $h->getHotelName();
                $this->arrDate = strtotime($m['arrDate']);
                $this->depDate = strtotime($m['depDate']);
            }
        }
    }*/

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        if (count($pdfs) == 0) {
            $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern2);
        }

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (strpos($text, 'by Air') !== false) {
                $this->ParseFlightPDF($email, $text);
            }

            /*if (strpos($text, 'Hotel Details') !== false) {
                $this->ParseHotelPDF($email, $text);
            }*/
        }

        if (isset($text) && preg_match("/Total Invoice Amount\s*([\d\.]+)\s*([A-Z]{3})/u", $text, $m)) {
            $email->price()
                ->total($m[1])
                ->currency($m[2]);
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

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^([\d\:]+\s*a?p?m?)\,\s*(\w+)\s*(\d+)\-(\w+)\s*(\d{4})$#ui", //09:55, Sun 08-Jan 2023
        ];
        $out = [
            "$2, $3 $4 $5, $1",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }

    private function normalizeTraveller(?string $s): ?string
    {
        if (empty($s)) {
            return null;
        }

        $namePrefixes = '(?:MASTER|MSTR|MISS|MRS|MR|MS|DR)';

        return preg_replace([
            "/^(?:{$namePrefixes}[.\s]+)+(.{2,})$/is",
            '/^([^\/]+?)(?:\s*[\/]+\s*)+([^\/]+)$/',
        ], [
            '$1',
            '$2 $1',
        ], $s);
    }

    private function splitText(?string $textSource, string $pattern, bool $saveDelimiter = false, $deleteFirst = true): array
    {
        $result = [];

        if ($saveDelimiter) {
            $textFragments = preg_split($pattern, $textSource, -1, PREG_SPLIT_DELIM_CAPTURE);

            if ($deleteFirst === false) {
                $result[] = array_shift($textFragments);
            } else {
                array_shift($textFragments);
            }

            for ($i = 0; $i < count($textFragments) - 1; $i += 2) {
                $result[] = $textFragments[$i] . $textFragments[$i + 1];
            }
        } else {
            $result = preg_split($pattern, $textSource);
            array_shift($result);
        }

        return $result;
    }

    private function rowColsPos(?string $row): array
    {
        $pos = [];
        $head = preg_split('/\s{2,}/', $row, -1, PREG_SPLIT_NO_EMPTY);
        $lastpos = 0;

        foreach ($head as $word) {
            $pos[] = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $lastpos = mb_strpos($row, $word, $lastpos, 'UTF-8') + mb_strlen($word, 'UTF-8');
        }

        return $pos;
    }

    private function splitCols(?string $text, ?array $pos = null): array
    {
        $cols = [];

        if ($text === null) {
            return $cols;
        }
        $rows = explode("\n", $text);

        if ($pos === null || count($pos) === 0) {
            $pos = $this->rowColsPos($rows[0]);
        }
        arsort($pos);

        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $cols[$k][] = rtrim(mb_substr($row, $p, null, 'UTF-8'));
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);

        foreach ($cols as &$col) {
            $col = implode("\n", $col);
        }

        return $cols;
    }
}
