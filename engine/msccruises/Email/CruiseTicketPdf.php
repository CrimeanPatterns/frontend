<?php

namespace AwardWallet\Engine\msccruises\Email;

use AwardWallet\Schema\Parser\Email\Email;

class CruiseTicketPdf extends \TAccountChecker
{
    public $mailFiles = "msccruises/it-698290014.eml, msccruises/it-706584565-de.eml, msccruises/it-904385017-pt.eml";

    public $dateFormat = ''; // 'mdy' or 'dmy'

    public $detectSubjects = [
        // en, es, pt, de
        'Eticket - ',
    ];

    public $detectBody = [
        'en' => ['THIS FORM MUST BE PRINTED AND PRESENTED AT EMBARKATION'],
        'es' => ['ESTE FORMULARIO DEBE IMPRIMIRSE Y PRESENTARSE EN EL EMBARQUE'],
        'pt' => ['ESTE FORMULÁRIO DEVE SER APRESENTADO IMPRESSO NO EMBARQUE'],
        'de' => ['DIESES FORMULAR MUSS AUSGEDRUCKT UND BEI DER EINSCHIFFUNG VORGELEGT'],
    ];

    public $lang = '';
    public static $dictionary = [
        'en' => [
            // 'BOOKING NUMBER' => '',
            // 'Ship' => '',
            // 'Cabin' => '',
            // 'Cabin Type' => '',
            // 'First Name' => '',
            // 'Last Name' => '',
            // 'Nationality' => '',
            // 'YOUR CRUISE' => '',
            // 'Embarkation Date' => '',
            // 'Guest(s)' => '',
            // 'Day' => '',
            // 'Port' => '',
            'Arrival and departure times' => ['Arrival and departure times', 'Arrival and departure timings'],
            // 'LUGGAGE TAGS' => '',
            // 'Deck' => '',
        ],
        'es' => [
            'BOOKING NUMBER'              => 'NÚMERO DE RESERVA',
            'Ship'                        => 'Barco',
            'Cabin'                       => 'Camarote',
            'Cabin Type'                  => 'Tipo de Camarote',
            'First Name'                  => 'Nombre',
            'Last Name'                   => 'Apellido',
            // 'Nationality' => '',
            'YOUR CRUISE'                 => 'TU CRUCERO',
            // 'Embarkation Date' => '',
            // 'Guest(s)' => '',
            'Day'                         => 'Día',
            'Port'                        => 'Puerto',
            'Arrival and departure times' => 'Los horarios de llegada y salida',
            'LUGGAGE TAGS'                => 'ETIQUETAS DE EQUIPAJE',
            'Deck'                        => 'Deck',
        ],
        'pt' => [
            'BOOKING NUMBER'              => 'NÚMERO DA RESERVA',
            'Ship'                        => 'Navio',
            'Cabin'                       => 'Cabine',
            'Cabin Type'                  => 'Categoria de cabine',
            'First Name'                  => 'Primeiro nome',
            'Last Name'                   => 'Último nome',
            'Nationality'                 => 'Nacionalidade',
            'YOUR CRUISE'                 => 'SEU CRUZEIRO',
            'Embarkation Date'            => 'Data de embarque',
            'Guest(s)'                    => 'Hóspede(s)',
            'Day'                         => 'Dia',
            'Port'                        => 'Porto',
            'Arrival and departure times' => 'Os horários de chegada e partida',
            'LUGGAGE TAGS'                => 'ETIQUETAS DE BAGAGENS',
            'Deck'                        => 'Deck',
        ],
        'de' => [
            'BOOKING NUMBER'              => 'BUCHUNGSNUMMER',
            'Ship'                        => ['Schiff', 'Schiﬀ'],
            'Cabin'                       => 'Kabine',
            'Cabin Type'                  => 'Kabinentyp',
            'First Name'                  => 'Vorname',
            'Last Name'                   => 'Nachname',
            'Nationality'                 => 'Nationalität',
            'YOUR CRUISE'                 => 'IHRE KREUZFAHRT',
            'Embarkation Date'            => ['Datum der Einschiffung', 'Datum der'],
            'Guest(s)'                    => 'Gast/Gäste',
            'Day'                         => 'Tag',
            'Port'                        => 'Hafen',
            'Arrival and departure times' => 'Die Ankunfts- und Abfahrtszeiten',
            'LUGGAGE TAGS'                => 'GEPÄCKANHÄNGER',
            'Deck'                        => 'Deck',
        ],
    ];

    private $patterns = [
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]',
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@msccrociere.it') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->detectSubjects as $dSubject) {
            if (is_string($dSubject) && array_key_exists('subject', $headers) && stripos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (!$textPdf) {
                continue;
            }

            if ($this->detectPdf($textPdf)) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf(?string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        if (stripos($text, 'MSC for Me') === false
            && stripos($text, 'Shore Excursions with MSC') === false
            && stripos($text, '@msccruises.com') === false
            && stripos($text, 'LADEN SIE UNSERE "MSC') === false // de
        ) {
            return false;
        }

        foreach ($this->detectBody as $lang => $phrases) {
            if ($this->strposArray($text, $phrases) !== false) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName('.*\.pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            // remove garbage
            $textPdf = preg_replace("/^(?:[ ]*|.+[ ]{2})Page[ ]\d+$/im", '', $textPdf);

            if ($this->detectPdf($textPdf)) {
                $this->parsePdf($email, $textPdf);
            }
        }

        $email->setType('BookingConfirmationPdf' . ucfirst($this->lang));

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

    private function parsePdf(Email $email, $pdfText): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        $parts = preg_split("/\n[ ]{0,5}(?:A?{$this->opt($this->t('YOUR CRUISE'))}[ ]*\||{$this->opt($this->t('LUGGAGE TAGS'))})/u", $pdfText);
        $text1 = count($parts) > 0 ? $parts[0] : '';
        $text2 = count($parts) > 1 ? $parts[1] : '';
        $text3 = count($parts) > 2 ? $parts[2] : '';

        $cr = $email->add()->cruise();

        // General
        $cr->general()
            ->confirmation($this->re("/\n *{$this->opt($this->t('BOOKING NUMBER'))} *(\d{5,})\s*\n/u", $text1))
        ;

        $travellers = [];

        // travellers (v1)
        if (preg_match_all("/(.*\d\n+[ ]*(?:{$this->opt($this->t('First Name'))}|{$this->opt($this->t('Last Name'))})[\s:][\s\S]*?\n+[ ]*{$this->opt($this->t('Nationality'))}(?:[ :]|\n))/", $text1, $travellerMatches)) {
            foreach ($travellerMatches[1] as $tRow) {
                $tableTravPos = [0];

                if (preg_match("/^(.{20,} ){$this->opt($this->t('First Name'))}/m", $tRow, $matches)
                    || preg_match("/^(.{20,} ){$this->opt($this->t('Last Name'))}/m", $tRow, $matches)
                ) {
                    $tableTravPos[] = mb_strlen($matches[1]);
                }

                $tableTrav = $this->createTable($tRow, $tableTravPos);

                foreach ($tableTrav as $travellerText) {
                    if (!preg_match("/^[ ]*{$this->opt($this->t('First Name'))}/m", $travellerText)
                        || !preg_match("/^[ ]*{$this->opt($this->t('Last Name'))}/m", $travellerText)
                    ) {
                        continue;
                    }

                    $travellerName = trim(preg_replace([
                        '/^.*\d\n+/',
                        "/^[ ]*{$this->opt($this->t('First Name'))}[ :]*/m",
                        "/^[ ]*{$this->opt($this->t('Last Name'))}[ :]*/m",
                        "/^[ ]*{$this->opt($this->t('Nationality'))}[ :]*/m",
                        '/\s+/',
                    ], ['', '', '', '', ' '], $travellerText));

                    if (preg_match("/^{$this->patterns['travellerName']}$/u", $travellerName)) {
                        $travellers[] = $travellerName;
                    } else {
                        $this->logger->debug('Wrong traveller name!');
                    }
                }
            }
        }

        // travellers (v2)
        if (count($travellers) === 0 && preg_match_all("/^.*({$this->opt($this->t('First Name'))}|{$this->opt($this->t('Last Name'))}).*$/mu", $text1, $m)) {
            $travelText = ['col1' => '', 'col2' => ''];

            foreach ($m[0] as $row) {
                if (preg_match("/(.{20,}) {3,}((?:{$this->opt($this->t('First Name'))}|{$this->opt($this->t('Last Name'))}).*)/u", $row, $mat)) {
                    $travelText['col1'] .= "\n" . trim($mat[1]);
                    $travelText['col2'] .= "\n" . trim($mat[2]);
                } else {
                    $travelText['col1'] .= "\n" . trim($row);
                }
            }
            $travelText = $travelText['col1'] . "\n" . $travelText['col2'];

            if (preg_match_all("/\n *{$this->opt($this->t('First Name'))} *(.+)\n\s*{$this->opt($this->t('Last Name'))} +(.+)/u", "\n" . $travelText, $trM)) {
                foreach ($trM[0] as $i => $v) {
                    $travellers[] = $trM[1][$i] . ' ' . $trM[2][$i];
                }
            }
        }

        if (count($travellers) > 0) {
            $cr->general()->travellers($travellers, true);
        }

        $ship = $room = $roomClass = null;

        $table1Text = $this->re("/\n[ ]*{$this->opt($this->t('BOOKING NUMBER'))}[ ]*\d.*\n+([\s\S]+?)(?:\n\n\n|$)/", $text1);

        $table1Pos = [0];

        if (preg_match("/^(.{20,} ){$this->opt($this->t('Guest(s)'))}(?:[ :]|$)/m", $table1Text, $matches)
            || preg_match("/^(.{20,} ){$this->opt($this->t('Embarkation Date'))}(?:[ :]|$)/m", $table1Text, $matches)
        ) {
            $table1Pos[] = mb_strlen($matches[1]);
        }

        $table1 = $this->createTable($table1Text, $table1Pos);

        if (count($table1) === 2 && preg_match("/^((?:.+\n+){1,3}?)[ ]*{$this->opt($this->t('Cabin'))}/", $table1[0], $m)
            && preg_match("/^[ ]*{$this->opt($this->t('Ship'))}(?:[ :]+|$)/m", $m[1])
        ) {
            $ship = preg_replace(["/^[ ]*{$this->opt($this->t('Ship'))}(?:[ :]+|$)/m", '/\s+/'], ['', ' '], trim($m[1]));
        } elseif (preg_match("/^[ ]*{$this->opt($this->t('Ship'))}[ :]*(.+?)(?:[ ]{2}|$)/mu", $text1, $m)) {
            $ship = $m[1];
        }

        if (preg_match("/^[ ]*{$this->opt($this->t('Cabin'))}[ :]*(.+?)(?:[ ]{2}|$)/mu", $text1, $m)) {
            $room = $m[1];
        }

        if (preg_match("/^[ ]*{$this->opt($this->t('Cabin Type'))}[ :]*(.+?)(?:[ ]{2}|$)/mu", $text1, $m)) {
            $roomClass = $m[1];
        }

        // Details
        $cr->details()->ship($ship)->room($room)->roomClass($roomClass);

        if (!empty($cr->getRoom())
            && preg_match("/\n *{$this->opt($this->t('Deck'))}\n\s*{$cr->getRoom()} *(\w{1,4})\n/u", $text3, $m)
        ) {
            $cr->details()
                ->deck($m[1]);
        }

        $tableSegsPos = [0];
        if (preg_match("/^(.{50,} ){$this->opt($this->t('Guest(s)'))}/im", $text2, $matches)) {
            $tableSegsPos[] = mb_strlen($matches[1]);
        }

        // Segments
        $segmentsText = $this->re("/\n( *{$this->opt($this->t('Day'))} +{$this->opt($this->t('Port'))} +[\s\S]+)\n *{$this->opt($this->t('Arrival and departure times'))}/u", $text2);

        $tablePos = $this->columnPositions($this->inOneRow($segmentsText));

        if (isset($tablePos[4])) {
            $table = $this->createTable($segmentsText, [0, $tablePos[4]], false);
            $segmentsText = $table[0];
        }

        // detect date format (v1)
        if (preg_match_all("/^.{0,7}\b(\d{2})\/(\d{2})\/(\d{2})\b/mu", $segmentsText, $m)
            && count($m[0]) > 1
        ) {
            if ($m[3][0] === $m[3][1]) { // year
                if ($m[1][0] === $m[1][1] && ((int) $m[2][0] + 1) == (int) $m[2][1]) {
                    $this->dateFormat = 'mdy';
                } elseif ($m[2][0] === $m[2][1] && ((int) $m[1][0] + 1) == (int) $m[1][1]) {
                    $this->dateFormat = 'dmy';
                }
            }
        }

        // detect date format (v2)
        if (!$this->dateFormat && preg_match_all('/\b(\d{1,2})\s*\/\s*(\d{1,2})\s*\/\s*\d{2,4}\b/', $segmentsText, $dateMatches, PREG_SET_ORDER)) {
            foreach ($dateMatches as $m) {
                if ($m[2] > 12) {
                    $this->dateFormat = 'mdy';

                    break;
                } elseif ($m[1] > 12) {
                    $this->dateFormat = 'dmy';

                    break;
                }
            }
        }

        // remove right column from segments
        $tableSegs = $this->createTable($segmentsText, $tableSegsPos);

        if (count($tableSegs) === 2) {
            $segmentsText = $tableSegs[0];
        }

        $rows = $this->split("/\n( {0,5}\S)/", $segmentsText);
        $emptyTime = '--:--';

        foreach ($rows as $i => $row) {
            $values = [];

            if (preg_match("/^[ ]*(?<date>.*\b\d{1,2}\/\d{1,2}\/\d{2,4})[ ]+(?<name1>\S.{0,50}?\S)[ ]+(?<time1>\d{1,2}:\d{2}.*?|--:--)[ ]+(?<time2>\d{1,2}:\d{2}.*?|--:--)(?<name2>\n[\s\S]+)?\s*$/u", $row, $m)) {
                $values['date'] = $m['date'];
                $values['name'] = $m['name1'] . (empty($m['name2']) ? '' : ' ' . $m['name2']);
                $values['time1'] = $m['time1'];
                $values['time2'] = $m['time2'];
            } else {
                $this->logger->debug('Segments is wrong!');
                $cr->addSegment();

                break;
            }
            $values = preg_replace('/\s*\n\s*/', ' ', array_map('trim', $values));

            if ($values['time1'] == $emptyTime && $values['time2'] == $emptyTime) {
                continue;
            }

            if ($values['time1'] == $emptyTime) {
                $values['time1'] = null;
            }

            if ($values['time2'] == $emptyTime) {
                $values['time2'] = null;
            }

            if (empty($values['time1']) && isset($segment) && empty($segment->getAboard()) && $segment->getName() === $values['name']) {
                $segment->setAboard($this->normalizeDate($values['date'] . ', ' . $values['time2']));
            }

            $segment = $cr->addSegment();
            $segment
                ->setName($values['name']);

            if (!empty($values['time1'])) {
                $segment->setAshore($this->normalizeDate($values['date'] . ', ' . $values['time1']));
            }

            if (!empty($values['time2'])) {
                $segment->setAboard($this->normalizeDate($values['date'] . ', ' . $values['time2']));
            }
        }
    }

    private function strposArray(?string $text, $phrases, bool $reversed = false)
    {
        if (empty($text)) {
            return false;
        }

        foreach ((array) $phrases as $phrase) {
            if (!is_string($phrase)) {
                continue;
            }
            $result = $reversed ? strrpos($text, $phrase) : strpos($text, $phrase);

            if ($result !== false) {
                return $result;
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

    private function re($re, $str, $c = 1): ?string
    {
        if (preg_match($re, $str, $m) && array_key_exists($c, $m)) {
            return $m[$c];
        }

        return null;
    }

    private function normalizeDate(?string $text)
    {
        if (!is_string($text) || empty($text)) {
            return null;
        }

        if (preg_match('/^\s*[[:alpha:]]+\s+(\d{2})\/(\d{2})\/(\d{2})\s*,\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)$/ui', $text, $m)) {
            if ($this->dateFormat === 'dmy') {
                return strtotime($m[1] . '.' . $m[2] . '.20' . $m[3] . ', ' . $m[4]);
            } elseif ($this->dateFormat === 'mdy') {
                return strtotime($m[2] . '.' . $m[1] . '.20' . $m[3] . ', ' . $m[4]);
            }
        }

        return null;
    }

    private function columnPositions($table, $correct = 5)
    {
        $pos = [];
        $rows = explode("\n", $table);

        foreach ($rows as $row) {
            $pos = array_merge($pos, $this->rowColumnPositions($row));
        }
        $pos = array_unique($pos);
        sort($pos);
        $pos = array_merge([], $pos);

        foreach ($pos as $i => $p) {
            if (!isset($prev) || $prev < 0) {
                $prev = $i - 1;
            }

            if (isset($pos[$i], $pos[$prev])) {
                if ($pos[$i] - $pos[$prev] < $correct) {
                    unset($pos[$i]);
                } else {
                    $prev = $i;
                }
            }
        }
        sort($pos);
        $pos = array_merge([], $pos);

        return $pos;
    }

    private function createTable(?string $text, $pos = [], $trim = true): array
    {
        $cols = [];
        $rows = explode("\n", $text);

        if (!$pos) {
            $pos = $this->rowColumnPositions($rows[0]);
        }
        arsort($pos);

        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $v = mb_substr($row, $p, null, 'UTF-8');

                if ($trim) {
                    $cols[$k][] = rtrim($v);
                } else {
                    $cols[$k][] = $v;
                }
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

    private function inOneRow($text)
    {
        $textRows = array_filter(explode("\n", $text));

        if (empty($textRows)) {
            return '';
        }
        $length = [];

        foreach ($textRows as $key => $row) {
            $length[] = mb_strlen($row);
        }
        $length = max($length);
        $oneRow = '';

        for ($l = 0; $l < $length; $l++) {
            $notspace = false;

            foreach ($textRows as $key => $row) {
                $sym = mb_substr($row, $l, 1);

                if ($sym !== false && trim($sym) !== '') {
                    $notspace = true;
                    $oneRow[$l] = 'a';
                }
            }

            if ($notspace == false) {
                $oneRow[$l] = ' ';
            }
        }

        return $oneRow;
    }

    private function split($re, $text, $shiftFirst = true)
    {
        $r = preg_split($re, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ret = [];

        if (count($r) > 1) {
            if ($shiftFirst == true || ($shiftFirst == false && empty($r[0]))) {
                array_shift($r);
            }

            for ($i = 0; $i < count($r) - 1; $i += 2) {
                $ret[] = $r[$i] . $r[$i + 1];
            }
        } elseif (count($r) == 1) {
            $ret[] = reset($r);
        }

        return $ret;
    }
}
