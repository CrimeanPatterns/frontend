<?php

namespace AwardWallet\Engine\celestyal\Email;

use AwardWallet\Schema\Parser\Email\Email;

class ETicket extends \TAccountChecker
{
    public $mailFiles = "celestyal/it-914485370.eml";

    public $pdfNamePattern = ".*\.pdf";

    public $lang = "en";

    public static $dictionary = [
        'en' => [
        ],
    ];

    private $detectFrom = "celestyal.com";

    private $detectSubject = [
        // en
        'Celestyal Cruises - eTicket.',
    ];
    private $detectBody = [
        'en' => [
            'IT\'S TIME TO CRUISE',
            'Your booking number',
            'Pre-Bookable Extras',
            'Travel Documents',
            'STATEROOM NUMBER',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]celestyal\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], 'Celestyal Cruises') === false
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

            if ($this->detectPdf($text)) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf($text)
    {
        // detect Provider
        if ($this->containsText($text, ['Celestyal Cruises']) === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectBody as $detectBody) {
            // if array -  all phrase of array
            if (is_array($detectBody)) {
                foreach ($detectBody as $phrase) {
                    if (strpos($text, $phrase) === false) {
                        continue 2;
                    }
                }

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

            if ($this->detectPdf($text)) {
                $this->parseCruisePdf($email, $text);
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

    private function parseCruisePdf(Email $email, ?string $textPdf = null)
    {
        $cr = $email->add()->cruise();

        $patterns = [
            'phone'         => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52    |    (+351) 21 342 09 07    |    713.680.2992
            'travellerName' => "(?:{$this->opt(['Dr', 'Miss', 'Mrs', 'Mr', 'Ms', 'Mme', 'Mr/Mrs', 'Mrs/Mr'])}[\.\s]*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])", // Mr. Hao-Li Huang => Hao-Li Huang
        ];

        $mainText = $this->re("/({$this->opt($this->t('IT\'S TIME TO CRUISE'))}.+?){$this->opt($this->t('Celestyal Cruises Centre Limited'))}/s", $textPdf);

        // find column positions
        $pos = [0, mb_strlen($this->re("/^(.+?){$this->opt($this->t('DATE'))}[ ]+{$this->opt($this->t('PORT'))}/m", $mainText)) - 1];

        // create table from mainText block
        $mainTable = $this->createTable($mainText, $pos);
        $reservationText = $mainTable[1];

        // collect reservation confirmation
        if (preg_match("/(?<confDesc>{$this->opt($this->t('Your booking number'))})\s+(?<confNumber>\d{4,})[ ]*\n/", $reservationText, $m)) {
            $cr->general()
                ->confirmation($m['confNumber'], $m['confDesc']);
        }

        // collect travellers
        $travellersText = $this->re("/{$this->opt($this->t('Guests'))}\s+(.+?)\s+{$this->opt($this->t('DEPARTURE DATE'))}/s", $reservationText);

        if (preg_match_all("/{$patterns['travellerName']}[ ]+\(.\)/", $travellersText, $m)) {
            $cr->setTravellers($m[1]);
        }

        // collect ship name and description
        if (preg_match("/{$this->opt($this->t('you aboard the'))}\s+(?<ship>.+?)[\,\s]+{$this->opt($this->t('for your'))}\s+(?<desc>.+?)[ ]*\n/s", $mainTable[0], $m)) {
            $cr->setShip($m['ship'])
                ->setDescription($m['desc']);
        }

        // collect deck, room and class
        if (preg_match("/{$this->opt($this->t('STATEROOM NUMBER'))}[ ]+{$this->opt($this->t('DECK'))}\s+(?<room>\d+)[\, ]+(?<class>[A-Z]+)[ ]*{$this->opt($this->t('DECK'))}[ ]*(?<deck>\d+)[ ]*\n/s", $reservationText, $m)) {
            $cr->setDeck($m['deck'])
                ->setRoom($m['room'])
                ->setClass($m['class']);
        }

        // collect segments
        $segmentsText = $this->re("/{$this->opt($this->t('DATE'))}[^\n]+?{$this->opt($this->t('DEPART'))}[ ]*\n(.+)$/s", $reservationText);
        $pos = $this->columnPositions($segmentsText);

        if (count($pos) !== 4) {
            $this->logger->debug("Wrong columns count!");

            return false;
        }

        $segments = $this->split("/(\d+[ ]+[^\d\s]+\s+\d{4})/", $segmentsText);

        $s = null;
        $preTime = 'ashoreTime';

        foreach ($segments as $segment) {
            // split segment into parts/cells (date, name, ashoreTime, aboardTime)
            $segmentTable = $this->createTable($segment, $pos);

            // remove redundant space symbols
            foreach ($segmentTable as &$column) {
                $column = preg_replace("/\s{2,}/", ' ', trim($column));
            }

            // pretty names
            $date = strtotime($segmentTable[0]);
            $name = $segmentTable[1];
            $ashoreTime = $segmentTable[2];
            $aboardTime = $segmentTable[3];

            // if at sea
            if (empty($ashoreTime) && empty($aboardTime)) {
                continue;
            }

            // if aboardTime is missing at preceding segment: set aboardTime as '16:00'
            if (!empty($s) && !empty($ashoreTime) && $preTime === 'ashoreTime') {
                $s->setAboard(strtotime('16:00', $s->getAshore()));
                $preTime = 'aboardTime';
            }

            // if new segment
            if (empty($s) || !empty($s->getAboard())) {
                $s = $cr->addSegment();
                // collect port/city name
                $s->setName(preg_replace("/\s+/", ' ', $name));
            }

            // collect ashore and aboard
            if (!empty($ashoreTime) && $preTime === 'aboardTime') {
                $s->setAshore(strtotime($ashoreTime, $date));
                $preTime = 'ashoreTime';
            }

            if (!empty($aboardTime) && $preTime === 'ashoreTime') {
                $s->setAboard(strtotime($aboardTime, $date));
                $preTime = 'aboardTime';
            }
        }

        // collect providers phones
        if (preg_match_all("/^[ ]*(?<desc>.+?)[ ]+(?<phone>{$patterns['phone']})(?:[ ]+{$this->opt($this->t('or'))})?[ ]*$/m", $mainTable[0], $m)) {
            foreach (array_map(null, $m['phone'], $m['desc']) as [$phone, $desc]) {
                if (preg_match("/^[-+.()\d ]+$/", $desc)) {
                    continue;
                }
                $cr->addProviderPhone($phone, $desc);
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

    private function split($re, $text)
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
}
