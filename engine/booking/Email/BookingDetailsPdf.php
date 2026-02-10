<?php

namespace AwardWallet\Engine\booking\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class BookingDetailsPdf extends \TAccountChecker
{
    public $mailFiles = "booking/it-12615368.eml, booking/it-12615369.eml, booking/it-894605054-de.eml";

    public $lang = '';

    public static $dictionary = [
        'de' => [
            'header'               => ['Ihre Buchungsinformationen'],
            'Kundenreferenznummer' => ['Kundenreferenznummer'],
            'segmentsEnd'          => ['Angaben Reisende', 'Gepäck', 'Sitzplätze', 'Zahlung', 'Kontaktdaten'],
            'pnr'                  => ['Buchungsnummer', 'Buchungsnum-mer', 'Buchungsnum- mer', 'Buchungsnum mer', 'Buchungsnum-', 'Buchungsnum'],
            'travellersEnd'        => ['Gepäck', 'Sitzplätze', 'Zahlung', 'Kontaktdaten'],
            'seatsEnd'             => ['Zahlung', 'Kontaktdaten'],
            'priceEnd'             => ['Kontaktdaten'],
            'feeNames'             => ['Aufgegebenes Gepäck'],
            'Flug nach'            => 'Flug nach',
            'Von'                  => 'Von',
            'nach'                 => 'nach',

            // 'Angaben Reisende' => '',
            // 'Direkt' => '',
            // 'Insgesamt' => '',
            // 'Sitzplätze' => '',
            // 'Tickets' => '',
            // 'Zahlung' => '',
            'Adult' => ['Erwachsene/r', 'Kind', 'Männlich', 'Weiblich'],
        ],
        'en' => [
            'header'               => ['Your booking details'],
            'Kundenreferenznummer' => ['Customer reference'],
            'segmentsEnd'          => ['Traveller details', 'Baggage', 'Payment', 'Contact details'],
            'Von'                  => '',
            'nach'                 => 'to',
            'Direkt'               => 'Direct',
            'stop'                 => 'stop',
            'pnr'                  => ['Booking reference', 'Booking reference:'],
            'Angaben Reisende'     => 'Traveller details',
            'Adult'                => ['Adult'],
            'travellersEnd'        => ['Baggage', 'Payment', 'Contact details'],
            // 'Tickets' => '',
            'Flug nach'  => 'Flight to',
            'Sitzplätze' => 'Seat',
            'seatsEnd'   => ['Payment', 'Contact details'],
            'Zahlung'    => 'Payment',
            'Insgesamt'  => 'Total',
            'priceEnd'   => ['Contact details'],
            // 'feeNames' => ['Aufgegebenes Gepäck'],
        ],
    ];

    private $year = '';

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (!$textPdf) {
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
        $emailDate = strtotime($parser->getDate());

        if ($emailDate) {
            $this->year = date('Y', $emailDate);
        }

        $this->logger->debug('Email Year: ' . $this->year);

        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (!$textPdf) {
                continue;
            }

            if ($this->assignLang($textPdf)) {
                $this->parsePdf($email, $textPdf);
            }
        }

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $email->setType('BookingDetailsPdf' . ucfirst($this->lang));

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

    private function parsePdf(Email $email, $text): void
    {
        $patterns = [
            'dateShort'     => '[-[:alpha:]]+[,. ]+\d{1,2}[,. ]+[[:alpha:]]+', // Do., 28. Sept.
            'time'          => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        if (preg_match("/^[ ]*({$this->opt($this->t('Kundenreferenznummer'))})[ ]*[:]+[ ]*([-A-z\d]{4,40})$/m", $text, $m)) {
            $email->ota()->confirmation($m[2], $m[1]);
        }

        $f = $email->add()->flight();
        $PNRs = [];

        $seatsText = $this->re("/\n[ ]*{$this->opt($this->t('Sitzplätze'))}\n+([\s\S]+?)(?:\n+[ ]*{$this->opt($this->t('seatsEnd'))}(?:\n|$)|\s*$)/", $text);
        $seatSections = $this->splitText($seatsText, "/^([ ]*{$this->opt($this->t('Flug nach'))}[ ]+\S.+)/im", true);

        $segmentsText = $this->re("/(?:^|\n)([ ]*(?:{$this->opt($this->t('Von'))}[ ]+)?\S.*?\S[ ]+{$this->opt($this->t('nach'))}[ ]+\S.*?\S\s[\s\S]*?)(?:\n+[ ]*{$this->opt($this->t('segmentsEnd'))}(?:\n|$)|\s*$)/", $text);

        $segments = $this->splitText($segmentsText, "/^([ ]*(?:{$this->opt($this->t('Von'))}[ ]+)?\S.*?\S[ ]+{$this->opt($this->t('nach'))}[ ]+\S.*?\S)/m", true);

        foreach ($segments as $segText) {
            /*
                Von München (MUC) nach Lisboa (LIS)
                Do., 28. Sept. · 06:05 - Do., 28. Sept. · 08:25
                Direkt · 3 Std. 20 Min. · Economy
                TAP Portugal · TP553
            */

            unset($s2);
            $s = $f->addSegment();

            $tablePos = [0];

            if (preg_match("/^(.{20,}? ){$this->opt($this->t('pnr'))}$/m", $segText, $matches)) {
                $tablePos[] = mb_strlen($matches[1]);
            }
            $table = $this->splitCols($segText, $tablePos);

            $nameDep = $nameArr = null;

            if (preg_match("/^[ ]*(?:{$this->opt($this->t('Von'))}[ ]+)?(\S.*?\S)[ ]+{$this->opt($this->t('nach'))}[ ]+(\S.*?\S)(?:\n|$)/", $table[0], $m)) {
                $nameDep = $m[1];
                $nameArr = $m[2];
            }

            $codeDep = $codeArr = null;
            $pattern = "/^(?:.{2,}?[(\s]+)?([A-Z]{3})[\s)]*$/";

            if (preg_match($pattern, $nameDep, $m)) {
                $codeDep = $m[1];
            }

            if (preg_match($pattern, $nameArr, $m)) {
                $codeArr = $m[1];
            }

            $dateDepVal = $dateArrVal = $timeDep = $timeArr = null;

            if (preg_match("/^[ ]*(?<dateDep>{$patterns['dateShort']})[. ·]+(?<timeDep>{$patterns['time']})\s+[-]+\s+(?<dateArr>{$patterns['dateShort']})[. ·]+(?<timeArr>{$patterns['time']})$/mu", $table[0], $m)) {
                $dateDepVal = $m['dateDep'];
                $dateArrVal = $m['dateArr'];
                $timeDep = $m['timeDep'];
                $timeArr = $m['timeArr'];
            }

            $dateDep = $dateArr = 0;
            $pattern = "/^(?<wday>[-[:alpha:]]+)[,. ]+(?<date>\d{1,2}[,. ]+[[:alpha:]]+)$/u";

            if (preg_match($pattern, $dateDepVal, $m)) {
                $weekDateNumber = WeekTranslate::number1($m['wday'], $this->lang);
                $dateDepNormal = $this->normalizeDate($m['date']);

                if ($weekDateNumber && $dateDepNormal && $this->year) {
                    $dateDep = EmailDateHelper::parseDateUsingWeekDay($dateDepNormal . ' ' . $this->year, $weekDateNumber);
                }
            }

            if (preg_match($pattern, $dateArrVal, $m)) {
                $weekDateNumber = WeekTranslate::number1($m['wday'], $this->lang);
                $dateArrNormal = $this->normalizeDate($m['date']);

                if ($weekDateNumber && $dateArrNormal && $this->year) {
                    $dateArr = EmailDateHelper::parseDateUsingWeekDay($dateArrNormal . ' ' . $this->year, $weekDateNumber);
                }
            }

            if (preg_match("/^[ ]*{$this->opt($this->t('Direkt'))}(?:[ ]*·|$)/im", $table[0])) {
                $s->extra()->stops(0);
            }

            if ($s->getStops() === 0 && preg_match("/(?:^|·)[ ]*((?:[ ]*\d{1,3}[ ]*(?:Std\.?|Min\.?))+)(?:[ ]*·|$)/im", $table[0], $m)) {
                $s->extra()->duration($m[1]);
            }

            if (preg_match("/(?:^|·)[ ]*(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(?<number>\d+)$/m", $table[0], $m)) {
                $s->airline()
                    ->name($m['name'])
                    ->number($m['number']);

                $s->departure()
                    ->name($nameDep)
                    ->code($codeDep)
                    ->date(strtotime($timeDep, $dateDep));

                $s->arrival()
                    ->name($nameArr)
                    ->code($codeArr)
                    ->date(strtotime($timeArr, $dateArr));
            } elseif (preg_match("/(?:^|·)[ ]*(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(?<number>\d+)\s*,\s*(?<name2>[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(?<number2>\d+)$/m", $table[0], $m)) {
                $s->airline()
                    ->name($m['name'])
                    ->number($m['number']);

                $s2 = $f->addSegment();

                $s2->airline()
                    ->name($m['name2'])
                    ->number($m['number2']);

                $s->departure()
                    ->name($nameDep)
                    ->code($codeDep)
                    ->date(strtotime($timeDep, $dateDep));

                $s->arrival()
                    ->noCode()
                    ->noDate();

                $s2->departure()
                    ->noCode()
                    ->noDate();

                $s2->arrival()
                    ->name($nameArr)
                    ->code($codeArr)
                    ->date(strtotime($timeArr, $dateArr));
            }

            $cabinVariants = ['Economy'];

            if (preg_match("/(?:^|·)[ ]*({$this->opt($cabinVariants)})(?:[ ]*·|$)/im", $table[0], $m)) {
                $s->extra()->cabin($m[1]);

                if (isset($s2)) {
                    $s->extra()->cabin($m[1]);
                }
            }

            if (count($table) > 1 && preg_match("/({$this->opt($this->t('pnr'))})\s*[:]+\n+[ ]*([A-Z\d]{5,8})$/m", $table[1], $m)
                && !in_array($m[2], $PNRs)
            ) {
                $f->general()->confirmation($m[2], preg_replace(['/(\S)[ ]*[-]+\n+[ ]*(\S)/m', '/\s+/'], ['$1$2', ' '], $m[1]));
                $PNRs[] = $m[2];
            }

            $targetCity = $this->re("/^(.{2,}?)\s*(?:\(\s*[A-Z]{3}\s*\))?$/", $nameArr);

            if (!$targetCity) {
                continue;
            }

            foreach ($seatSections as $i => $seatSecText) {
                $this->logger->info('no examples of email with stops and seats');

                if (preg_match("/^[ ]*{$this->opt($this->t('Flug nach'))}[ ]+{$this->opt($targetCity)}\n/", $seatSecText)
                    && preg_match("/^[ ]*(\d+[A-Z](?:[ ]*,[ ]*\d+[A-Z])*)$/m", $seatSecText, $m)
                ) {
                    $s->extra()->seats(preg_split('/(?:\s*,\s*)+/', $m[1]));
                    unset($seatSections[$i]);

                    break;
                }
            }
        }

        $travellers = [];
        $travellersText = $this->re("/\n[ ]*{$this->opt($this->t('Angaben Reisende'))}\n+([\s\S]+?)(?:\n+[ ]*{$this->opt($this->t('travellersEnd'))}(?:\n|$)|\s*$)/", $text);
        $travellerRows = array_map('trim', preg_split("/(?:(?:^|.*·)[ ]*{$this->opt($this->t('Adult'))}(?:[ ]*·.*|$)|\b\d{1,2}[,. ]*[[:alpha:]]+[,. ]*\d{2,4}\b)/imu", $travellersText));

        foreach (array_filter($travellerRows) as $tvlrRow) {
            if (preg_match("/^{$patterns['travellerName']}$/u", $tvlrRow)) {
                $travellers[] = $tvlrRow;
            } else {
                $this->logger->debug('Wrong traveller row!');
                $travellers = [];

                break;
            }
        }

        if (count($travellers) > 0) {
            $f->general()->travellers($travellers, true);
        }

        $priceText = $this->re("/\n[ ]*{$this->opt($this->t('Zahlung'))}\n+([\s\S]+?)(?:\n+[ ]*{$this->opt($this->t('priceEnd'))}(?:\n|$)|\s*$)/", $text);
        $totalPrice = $this->re("/^[ ]*{$this->opt($this->t('Insgesamt'))}[ ]{2,}(.*\d.*)$/m", $priceText);

        if (preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $totalPrice, $matches)) {
            // EUR 968.96
            $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
            $f->price()->currency($matches['currency'])->total(PriceHelper::parse($matches['amount'], $currencyCode));

            $baseFare = $this->re("/^[ ]*{$this->opt($this->t('Tickets'))}.*?[ ]{2,}(.*\d.*)/", $priceText);

            if (preg_match('/^(?:' . preg_quote($matches['currency'], '/') . ')?[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $baseFare, $m)) {
                $f->price()->cost(PriceHelper::parse($m['amount'], $currencyCode));
            }

            preg_match_all("/^[ ]*(?<name>{$this->opt($this->t('feeNames'))})[ ]{2,}(?<charge>.*\d.*)$/m", $priceText, $feeMatches, PREG_SET_ORDER);

            foreach ($feeMatches as $feeParams) {
                if (preg_match('/^(?:' . preg_quote($matches['currency'], '/') . ')?[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $feeParams['charge'], $m)) {
                    $f->price()->fee($feeParams['name'], PriceHelper::parse($m['amount'], $currencyCode));
                }
            }
        }
    }

    private function assignLang(?string $text): bool
    {
        if (empty($text) || !isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['header'])) {
                continue;
            }

            if (preg_match("/^[ ]*{$this->opt($phrases['header'])}$/m", $text)) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return $phrase;
        }

        if ($lang === '') {
            $lang = $this->lang;
        }

        if (empty(self::$dictionary[$lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$lang][$phrase];
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
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

            for ($i = 0; $i < count($textFragments) - 1; $i += 2) {
                $result[] = $textFragments[$i] . $textFragments[$i + 1];
            }
        } else {
            $result = preg_split($pattern, $textSource);
            array_shift($result);
        }

        return $result;
    }

    private function rowColsPos(?string $row, bool $alignRight = false): array
    {
        if ($row === null) {
            return [];
        }
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

    /**
     * Dependencies `use AwardWallet\Engine\MonthTranslate` and `$this->lang`.
     *
     * @param string|null $text Unformatted string with date
     */
    private function normalizeDate(?string $text): ?string
    {
        if (preg_match('/^(\d{1,2})[,. ]+([[:alpha:]]+)[.\s]*$/u', $text, $m)) {
            // 28. Sept.
            $day = $m[1];
            $month = $m[2];
            $year = '';
        }

        if (isset($day, $month, $year)) {
            if (preg_match('/^\s*(\d{1,2})\s*$/', $month, $m)) {
                return $m[1] . '/' . $day . ($year ? '/' . $year : '');
            }

            if (($monthNew = MonthTranslate::translate($month, $this->lang)) !== false) {
                $month = $monthNew;
            }

            return $day . ' ' . $month . ($year ? ' ' . $year : '');
        }

        return null;
    }
}
