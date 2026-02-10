<?php

namespace AwardWallet\Engine\vivaaerobus\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Common\Flight;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FlightReservation extends \TAccountChecker
{
    public $mailFiles = "vivaaerobus/it-896142405.eml, vivaaerobus/it-896851045.eml, vivaaerobus/it-897034325.eml, vivaaerobus/it-899191880.eml";
    public $lang = '';
    public $pdfNamePattern = ".*pdf";
    public static $dictionary = [
        "es" => [
        ],
        "en" => [
            'Reservación confirmada'  => 'Reservation Confirmed',
            'Itinerario de viaje'     => 'Flight Itinerary',
            'Vuelo 1'                 => 'Flight 1',
            'Pasajeros'               => 'Passengers',
            'Información de contacto' => 'Contact Information',
            'Núm. de Socio'           => 'Member no.',
            'Total de la reservación' => 'Reservation Total',
            'Descarga nuestra app'    => 'Download our app',
            'Clase tarifaria:'        => 'Fare class:',
            'Directo'                 => 'Non-Stop',
            'Terminal'                => 'Terminal',
            'Vuelo de salida'         => 'Departure Flight',
            'Equipaje y adicionales'  => 'Baggage and Extras',
            //'Tu reserva a' => '',
        ],
    ];
    public $detectLang = [
        "en" => ["Flight Itinerary"],
        "es" => ["Itinerario de viaje"],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            $this->assignLang($text);

            if (stripos($text, 'vivaaerobus.com') === false) {
                return false;
            }

            if (stripos($text, $this->t('Itinerario de viaje')) !== false
                && (stripos($text, $this->t('Vuelo 1')) !== false)
                && (stripos($text, $this->t('Pasajeros')) !== false)
                && (stripos($text, $this->t('Información de contacto')) !== false)
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]vivaaerobus\.com$/', $from) > 0;
    }

    public function ParseFlightPDF(Email $email, $text)
    {
        $f = $email->add()->flight();

        $conf = $this->re("/{$this->t('Reservación confirmada')}\s*([A-Z\d]{6})\n/", $text);

        if (empty($conf)) {
            $conf = $this->re("/{$this->t('Tu reserva a')}.*\s([A-Z\d]{6})\n/", $text);
        }
        $f->general()
            ->confirmation($conf);

        $paxText = $this->re("/{$this->t('Pasajeros')}\n+(?:[ ]+\S\n)?(.+)\n\s*{$this->t('Información de contacto')}/siu", $text);

        if (preg_match_all("/([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\n+\s+\d+/u", $paxText, $m)) {
            $f->general()
                ->travellers($m[1], true);

            foreach ($m[1] as $pax) {
                $account = $this->re("/{$pax}\n+\s+\d+.+{$this->t('Núm. de Socio')}\s+(\d{5,})/", $paxText);

                if (!empty($account)) {
                    $f->addAccountNumber($account, false, $pax);
                }
            }
        }

        if (preg_match("/{$this->t('Total de la reservación')}\s+\D*\s*(?<total>[\d\.\,\']+)\n+\s+(?<currecy>[A-Z]{3})\n/", $text, $m)) {
            $f->price()
                ->total(PriceHelper::parse($m['total'], $m['currecy']))
                ->currency($m['currecy']);
        }

        $flightText = '';

        if (stripos($text, $this->t('Descarga nuestra app')) !== false) {
            $flightText = $this->re("/{$this->opt($this->t('Itinerario de viaje'))}\n+(.+)\n*\s*{$this->opt($this->t('Descarga nuestra app'))}/su", $text);
        } else {
            $flightText = $this->re("/{$this->opt($this->t('Itinerario de viaje'))}\n+(.+)\n*\s*{$this->opt($this->t('Pasajeros'))}/su", $text);
        }

        $flightsText = array_filter($this->splitText($flightText, "/^([ ]*\w+\.?\,\s+(?:.+\n.+)?\d+\:.*)/mu", true));

        foreach ($flightsText as $segText) {
            if (stripos($segText, $this->t('Conexión')) !== false) {
                $this->segmentWithConnection($f, $segText, $text);
            } else {
                $this->segmentWithoutConnection($f, $segText, $text);
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));
            $text = str_replace([''], '', $text);

            $this->assignLang($text);

            $this->ParseFlightPDF($email, $text);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function segmentWithConnection(Flight $f, $segText, $text)
    {
        $segmentsOnly = $this->re("/^([ ]+{$this->opt($this->t('Vuelo 1'))}.+)/msu", $segText);
        $positionColumn1 = strlen($this->re("/^(.+){$this->opt($this->t('Conexión'))}/m", $segmentsOnly));
        $positionColumn2 = strlen($this->re("/^(.+{$this->opt($this->t('Conexión'))})/m", $segmentsOnly));
        $segTable = $this->splitCols($segmentsOnly, [0, $positionColumn1 - 1, $positionColumn2 + 1]);

        $day = null;

        if (preg_match("/(?<week>\w+)\.\,.+\n\s*(?<day>\d+\s*\w+)\./su", $segText, $m)) {
            $day = $m['week'] . ', ' . $m['day'];
        }

        foreach ($segTable as $segmentText) {
            if (stripos($segmentText, $this->t('Conexión')) !== false) {
                continue;
            }

            $this->segmentWithoutConnection($f, $segmentText, $text, $day);
        }
    }

    public function segmentWithoutConnection(Flight $f, $segText, $text, $day = null)
    {
        $s = $f->addSegment();

        if (preg_match("/\s+(?<aName>[A-Z\d]{2})\s+(?<fNumber>\d{1,4})\n+\s+{$this->opt($this->t('Clase tarifaria:'))}/", $segText, $m)) {
            $s->airline()
                ->name($m['aName'])
                ->number($m['fNumber']);
        }

        if (preg_match("/(?<depCode>[A-Z]{3})\s+{$this->opt($this->t('Directo'))}\s+(?<arrCode>[A-Z]{3})/su", $segText, $m)
            || preg_match("/^\s*(?<depCode>[A-Z]{3})\s+.+\s(?<arrCode>[A-Z]{3})$/mu", $segText, $m)) {
            $s->departure()
                ->code($m['depCode']);

            $s->arrival()
                ->code($m['arrCode']);
        }

        if (preg_match("/^[ ]*{$s->getDepCode()}\s+{$this->opt($this->t('Terminal'))}[ ]{1,5}(?<depTerminal>\w+)?\s+{$this->opt($this->t('Terminal'))}\s*(?<arrTerminal>\w+)?\s+{$s->getArrCode()}/usm", $segText, $m)) {
            if (isset($m['depTerminal']) && !empty($m['depTerminal'])) {
                $s->departure()
                    ->terminal($m['depTerminal']);
            }

            if (isset($m['arrTerminal']) && !empty($m['arrTerminal'])) {
                $s->arrival()
                    ->terminal($m['arrTerminal']);
            }
        }

        $seatsText = $this->re("/({$this->t('Vuelo de salida')}.+){$this->t('Equipaje y adicionales')}/msu", $text);
        $seatsTable = $this->splitCols($seatsText);

        foreach ($seatsTable as $column) {
            if (stripos($column, "{$s->getDepCode()}-{$s->getArrCode()}") !== false) {
                if (preg_match_all("/(\d+[A-Z])/", $column, $matches)) {
                    $s->extra()
                        ->seats($matches[1]);
                }
            }
        }

        $depTime = $arrTime = '';

        if (preg_match("/{$this->opt($this->t('Clase tarifaria:'))}\s+(?<bCode>.+)\n+\s+(?<duration>\d+(?:h|m).+)\n\s*(?<depTime>\d+\:\d+\s*[AP]M)\s+(?<arrTime>\d+\:\d+\s*[AP]M)/", $segText, $m)) {
            $s->extra()
                ->duration($m['duration'])
                ->bookingCode($m['bCode']);

            $depTime = $m['depTime'];
            $arrTime = $m['arrTime'];
        }

        if (preg_match("/(?<week>\w+)\.?\,.+\n\s*(?<day>\d+\s*\w+)\.?\n/su", $segText, $m)) {
            $day = $m['week'] . ', ' . $m['day'];
        }

        $year = $this->re("/\.?\,\s*(\d{4})\s+[•]/", $text);

        if (!empty($day) && !empty($depTime) && !empty($arrTime) && !empty($year)) {
            $s->departure()
                ->date($this->normalizeDate($day . ' ' . $year . ', ' . $depTime));

            $s->arrival()
                ->date($this->normalizeDate($day . ' ' . $year . ', ' . $arrTime));
        }
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s));
        }, $field)) . ')';
    }

    private function normalizeDate($date)
    {
        $in = [
            // Lun, 14 Abr, 9:35 AM
            "/^(\w+)\,\s+(\d+)\s+(\w+)\s+(\d{4})\,\s+(\d+\:\d+\s*[AP]M)$/u",
        ];
        $out = [
            "$1, $2 $3 $4, $5",
        ];
        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        if (preg_match("#^(?<week>[[:alpha:]]{2,}), (?<date>\d+ [[:alpha:]]{3,} \d{4}\,\s*[\d\:]+\s*[AP]M)#u", $date, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));
            $date = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("#\b\d{4}\b#", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }

        return $date;
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

    private function assignLang($text)
    {
        foreach ($this->detectLang as $lang => $phrases) {
            foreach ($phrases as $phrase) {
                if (stripos($text, $phrase) !== false) {
                    $this->lang = $lang;
                }
            }
        }

        return false;
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
