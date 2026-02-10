<?php

namespace AwardWallet\Engine\azamara\Email;

// TODO: delete what not use
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class TicketBookletPdf extends \TAccountChecker
{
    public $mailFiles = "azamara/it-902716643.eml, azamara/it-903432505.eml";

    public $pdfNamePattern = ".*\.pdf";

    public $lang;
    public static $dictionary = [
        'en' => [
            'THIS BOOKLET HAS BEEN PREPARED FOR' => 'THIS BOOKLET HAS BEEN PREPARED FOR',
            'VOYAGE ITINERARY'                   => 'VOYAGE ITINERARY',
        ],
    ];

    private $detectFrom = "@azamara.com";
    private $detectSubject = [
        // en
        'Get Ready to Embark: Your Azamara e-Ticket is Here',
    ];
    private $detectBody = [
        //        'en' => [
        //            '',
        //        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]azamara\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], 'Azamara') === false
        ) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text) == true) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf($text)
    {
        // detect provider
        if ($this->containsText($text, ['www.Azamara.com', 'www.azamara.com', 'Azamara Journey']) === false) {
            return false;
        }

        // detect Format
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['THIS BOOKLET HAS BEEN PREPARED FOR'])
                && $this->containsText($text, $dict['THIS BOOKLET HAS BEEN PREPARED FOR']) === true
                && !empty($dict['VOYAGE ITINERARY'])
                && $this->containsText($text, $dict['VOYAGE ITINERARY']) === true
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text) == true) {
                $this->parseEmailPdf($email, $text);
            }
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

    private function parseEmailPdf(Email $email, ?string $textPdf = null)
    {
        $cruise = $email->add()->cruise();

        $cruiseInfo = $this->re("/\n( *{$this->opt($this->t('VOYAGE SUMMARY'))} +.*\n[\s\S]+? +{$this->opt($this->t('DISEMBARK TIME:'))}.+)/", $textPdf);
        $colPos = $this->rowColumnPositions($this->inOneRow($cruiseInfo));
        $cruiseInfoTable = $this->createTable($cruiseInfo, (count($colPos) === 4) ? [$colPos[0], $colPos[2]] : []);

        $cruise->general()
            ->confirmation($this->re("/\n\s*{$this->opt($this->t('RESERVATION ID:'))} *(\d{5,})\n/", $cruiseInfoTable[0] ?? ''))
        ;

        $travellersText = $this->re("/\n *{$this->opt($this->t('THIS BOOKLET HAS BEEN PREPARED FOR'))}\b.*\n(\s*\S[\s\S]+?)\n{3,} *\S.+(?:\n.*)?\n\s*{$this->opt($this->t('VOYAGE SUMMARY'))} +/", $textPdf);
        $travellerRows = $this->split("/^( {0,5}\S+)/m", $travellersText);
        $travellers = [];

        foreach ($travellerRows as $row) {
            $table = $this->createTable($row, $this->rowColumnPositions($this->inOneRow($row)));
            $traveller = trim(preg_replace('/\s+/', ' ', $table[0] ?? ''));
            $travellers[] = $traveller;

            if (preg_match("/^(\d{5,})$/", $table[1] ?? '')) {
                $cruise->program()
                    ->account($table[1], false, $traveller);
            }
        }
        $cruise->general()
            ->travellers($travellers, true);

        $cruise->details()
            ->ship($this->re("/\n\s*{$this->opt($this->t('SHIP NAME:'))} *(.+)\n/", $cruiseInfoTable[0] ?? ''))
            ->room($this->re("/\n\s*{$this->opt($this->t('STATEROOM #:'))} *(.+)\n/", $cruiseInfoTable[0] ?? ''))
            ->deck($this->re("/\n\s*{$this->opt($this->t('DECK #:'))} *(.+)\n/", $cruiseInfoTable[0] ?? ''))
            ->roomClass($this->re("/\n\s*{$this->opt($this->t('CATEGORY:'))} *(.+)\n/", $cruiseInfoTable[0] ?? ''))
            ->description($this->re("/\n{3,} *(\S.+(?:\n.*)?)\n\s*{$this->opt($this->t('VOYAGE SUMMARY'))} +/", $textPdf))
        ;
        $cruiseSegmentsHeaderText = $this->re("/\n *{$this->opt($this->t('VOYAGE ITINERARY'))}\s*\n( *\S.+)\n+/", $textPdf);
        $cruiseSegmentsText = $this->re("/\n *{$this->opt($this->t('VOYAGE ITINERARY'))}\s*\n *\S.+\n+(\S[\s\S]+?)\n *{$this->opt($this->t('Voyage Itinerary'))}\n/", $textPdf);

        if (preg_match("/^(((.+ {2,}){$this->opt($this->t('DOCK OR TENDER'))} +){$this->opt($this->t('ARRIVE'))} +){$this->opt($this->t('DEPART'))}/", $cruiseSegmentsHeaderText, $m)) {
            $headerPos = [0, strlen($m[3]), strlen($m[2]), strlen($m[1])];
        } else {
            $headerPos = [];
            $s = $cruise->addSegment();
        }
        $travellerRows = $this->split("/^( {0,5}\S+)/m", $cruiseSegmentsText);

        foreach ($travellerRows as $row) {
            $rowTable = array_map('trim', $this->createTable($row, $headerPos));

            if (empty($rowTable[2]) && empty($rowTable[3])) {
                continue;
            }

            $date = $name = null;

            if (preg_match("/^(.+?\d{4}) +(.+)/", $rowTable[0], $m)) {
                $date = $m[1];
                $name = $m[2];
            }

            if (isset($s) && $name == $s->getName() && !empty($s->getAshore()) && empty($s->getAboard())) {
            } else {
                $s = $cruise->addSegment();
            }

            $s->setName($name);

            if (!empty($date) && !empty($rowTable[2])) {
                $s->setAshore($this->normalizeDate($date . ', ' . $rowTable[2]));
            }

            if (!empty($date) && !empty($rowTable[3])) {
                $s->setAboard($this->normalizeDate($date . ', ' . $rowTable[3]));
            }
        }

        $addItineraryText = $this->re("/\n( *(?:Air|Hotels|Shore Excursions)\n[\s\S]+?)\n *{$this->opt($this->t('VOYAGE ITINERARY'))}\n/", $textPdf);
        $addItineraryText = preg_replace("/\n {20,}.*\b\d{4}\b.*\d{2}:\d{2}.*\n+ *VOYAGE SUMMARY\n/", "\n", $addItineraryText);

        if (!empty($addItineraryText)) {
            $itineraries = $this->split("/^ *(Air|Hotels|Shore Excursions|Transfers|Add-Ons|Special Requests)/m", $addItineraryText);

            foreach ($itineraries as $itText) {
                $type = $this->re("/^ *(.+)/", $itText);
                $itText = preg_replace("/\n *Total Price: *.+/", '', $itText);

                if ($type === 'Shore Excursions') {
                    // Events
                    $events = $this->split("/^( {0,5}[A-Z\d]{3,}\-[A-Z\d]{3,})/m", $this->re("/^ *\S.+\n+.+\n([\s\S]+)/m", $itText));
                    // $this->logger->debug('$events = '.print_r( $events,true));
                    foreach ($events as $sText) {
                        // AzAmazing Evening 26 May 2025 07:00 PM
                        // $sText = preg_match('/^(.+\S) (\d{1,2} [[:alpha:]]+ \d{4})/', '$1    $2', $sText, $m) {
                        if (preg_match('/^(.+\S )(\d{1,2} [[:alpha:]]+ \d{4}(?: ?\S)+?)( +)( {2,}[\s\S]+)/', $sText, $m)) {
                            $sText = $m[1] . str_pad('', strlen($m[3]), ' ') . $m[2] . $m[4];
                        }

                        $price = $this->re("/\n *Price: *(.+)/", $sText);
                        $sText = preg_replace("/\n *Price: *[\s\S]+/", '', $sText);
                        $table = $this->createTable($sText, $this->rowColumnPositions($this->inOneRow($sText)));
                        $table = preg_replace('/\s+/', ' ', $table);

                        $event = $email->add()->event();

                        $event->type()->event();

                        $event->general()
                            ->noConfirmation();

                        foreach ($travellers as $tr) {
                            if (stripos($table[count($table) - 1] ?? '', $tr) !== false) {
                                $table[count($table) - 1] = trim(preg_replace("/\b{$tr}\b/", '', $table[count($table) - 1] ?? ''));
                                $event->general()
                                    ->traveller($tr);
                            }
                        }

                        $event->place()
                            ->name($table[1] ?? '');

                        if (!empty($table[5])) {
                            $event->place()
                                ->address($table[5]);
                        }

                        $event->booked()
                            ->start($this->normalizeDate($table[2] ?? ''))
                            ->end($this->normalizeDate($table[3] ?? ''));

                        if (preg_match("#^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$#", $price, $m)
                            || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$#", $price, $m)
                        ) {
                            $event->price()
                                ->total(PriceHelper::parse($m['amount']))
                                ->currency($m['currency'])
                            ;
                        }

                        if (empty($table[5]) && !empty($event->getStartDate()) && !empty($event->getEndDate())) {
                            $email->removeItinerary($event);
                        }
                    }
                } elseif ($type === 'Hotels') {
                    // Hotels
                    $hotels = $this->split("/^( {0,5}\S+.+\b\d{4}\b.+\b\d{4}\b)/m", $this->re("/^ *\S.+\n+.+\n+.+\n+([\s\S]+)/m", $itText));
                    // $this->logger->debug('$hotels = '.print_r( $hotels,true));
                    foreach ($hotels as $sText) {
                        $hotel = $email->add()->hotel();

                        $hotel->general()
                            ->noConfirmation();

                        $tablePos = $this->rowColumnPositions($this->inOneRow($sText));
                        $table = $this->createTable($sText, $tablePos);
                        $table = preg_replace('/\s+/', ' ', $table);

                        foreach ($travellers as $tr) {
                            if (stripos($table[count($table) - 1], $tr) !== false) {
                                $table[count($table) - 1] = preg_replace("/\b{$tr}\b/", '', $table[count($table) - 1]);
                                $hotel->general()
                                    ->traveller($tr);
                            }
                        }

                        if (preg_match("/^(.+ (\d{1,2} [[:alpha:]]+ \d{4}.*?) +(\d{1,2} [[:alpha:]]+ \d{4}.*?) +\d+) /", $sText, $m)) {
                            $hotel->booked()
                                ->checkIn($this->normalizeDate($m[2]))
                                ->checkOut($this->normalizeDate($m[3]))
                            ;

                            foreach ($tablePos as $i => $p) {
                                if ($p < strlen($m[1])) {
                                    unset($tablePos[$i]);
                                }
                            }
                            $table = $this->createTable($sText, array_values($tablePos));
                            $table = preg_replace('/\s+/', ' ', $table);
                        }

                        $hotel->hotel()
                            ->name($table[1] ?? '')
                            ->address($table[0] ?? '')
                        ;
                    }
                }
            }
        }

        return $email;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function containsText($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (mb_strpos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && mb_strpos($text, $needle) !== false) {
            return true;
        }

        return false;
    }

    // additional methods
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

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));

        $in = [
            //            // Sun, Apr 09
            //            '/^\s*(\w+),\s*(\w+)\s+(\d+)\s*$/iu',
            //            // Tue Jul 03, 2018 at 1 :43 PM
            //            '/^\s*[\w\-]+\s+(\w+)\s+(\d+),\s*(\d{4})\s+(\d{1,2}:\d{2}(\s*[ap]m)?)\s*$/ui',
        ];
        $out = [
            //            '$1, $3 $2 ' . $year,
            //            '$2 $1 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
//        $this->logger->debug('date replace = ' . print_r( $date, true));

        // $this->logger->debug('date end = ' . print_r( $date, true));

        return strtotime($date);
    }

    private function opt($field, $delimiter = '/')
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
            return preg_quote($s, $delimiter);
        }, $field)) . ')';
    }

    private function split($re, $text, $deleteFirst = true)
    {
        $r = preg_split($re, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ret = [];

        if (count($r) > 1) {
            if ($deleteFirst === true) {
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

    private function inOneRow($text)
    {
        $textRows = array_filter(explode("\n", $text));
        $pos = [];
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
}
