<?php

namespace AwardWallet\Engine\brmuseum\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class TicketPdf extends \TAccountChecker
{
    public $mailFiles = "brmuseum/it-892823518.eml, brmuseum/it-895437875.eml, brmuseum/it-897034062.eml, brmuseum/it-897440297.eml, brmuseum/it-899439058.eml";

    public $lang = 'en';

    public $pdfNamePattern = ".*pdf";

    public $detectSubjects = [
        'en' => [
            'British Museum - your tickets',
        ],
    ];

    public static $dictionary = [
        'en' => [
            'ticketStart'   => ['General admission', 'FOOD AND DRINK', 'Exhibition ticket'],
            'Event'         => ['Event', 'Experience'],
            'Ticket number' => ['Ticket number', 'Ticket Number'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]britishmuseum\.org$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from']) || stripos($headers['from'], '@britishmuseum.org') === false)
            && stripos($headers['subject'], 'British Museum') === false
        ) {
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
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text) === true) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf($text)
    {
        if (empty($text)) {
            return false;
        }

        // detect Provider
        if (stripos($text, 'British Museum') === false
            && stripos($text, 'britishmuseum.org') === false
        ) {
            return false;
        }

        // detect Format
        if (stripos($text, 'Ticket holder’s name:') !== false
            && stripos($text, 'Ticket type:') !== false
            && stripos($text, 'Order number:') !== false
            && stripos($text, 'When to arrive') !== false
            && stripos($text, 'Photography') !== false
        ) {
            return true;
        }

        return false;
    }

    public function parseEvent(Email $email, $text)
    {
        $e = null;

        $patterns = [
            'time'  => '\d{1,2}(?:[:：.]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.
        ];

        $tickets = $this->split("/((?:{$this->opt($this->t('ticketStart'))}\s+)?{$this->opt($this->t('Ticket holder’s name'))})/i", $text);

        foreach ($tickets as $ticket) {
            // collect date and time (start date)
            $date = $this->normalizeDate($this->re("/{$this->opt($this->t('Date'))}\:\s+(.+?)[ ]*\n/", $ticket));
            $time = $this->re("/{$this->opt($this->t('Time'))}\:\s+({$patterns['time']})\s+/", $ticket);

            $startDate = strtotime($time, $date);

            if (empty($time)) {
                $startDate = $date;
            }

            // if new event
            if ($e === null || $e->getStartDate() !== $startDate) {
                $e = $email->add()->event();
            }
            // if old event - add ticket number
            else {
                $ticketNumber = $this->re("/{$this->opt($this->t('Ticket number'))}\:\s+(\d{8,})\s+/", $ticket);

                if (!empty($e->getNotes()) && !empty($ticketNumber)) {
                    $e->setNotes($e->getNotes() . ", " . $this->re("/{$this->opt($this->t('Ticket number'))}\:\s+(\d{8,})\s+/", $ticket));
                }

                continue;
            }

            // collect event type
            if (stripos($ticket, 'FOOD AND DRINK') !== false) {
                $e->type()->restaurant();
            } else {
                $e->type()->event();
            }

            // collect reservation info
            if (preg_match("/(?<desc>{$this->opt($this->t('Order number'))})\:\s+(?<confNumber>\d{6,})\s+/", $text, $m)) {
                $e->general()
                    ->confirmation($m['confNumber'], $m['desc']);
            }

            // collect travellers
            $traveller = $this->re("/{$this->opt($this->t('Ticket holder’s name'))}\:\s+(?:{$this->opt(['Miss', 'Mrs', 'Mr', 'Ms'])}\.?\s*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\s+/u", $ticket);
            $e->addTraveller($traveller);

            // Ticket text does not contain address (and often - event name).
            // But ticket is for the British Museum only, because event name and address can be hardcoded.
            $e->setAddress('The British Museum, Great Russell Street, London, WC1B 3DG');

            // collect event name
            $eventName = $this->re("/{$this->opt($this->t('Event'))}\:\s+(.+?)\s{5,}/", $ticket)
                ?? trim($this->re("/{$traveller}\s+(.+?)\s+{$this->opt($this->t('Date'))}/s", $ticket));

            if (empty($eventName)) {
                $eventName = 'Visiting the British Museum';
            }

            $e->setName($eventName);

            // set dates
            $e->booked()
                ->start($startDate)
                ->noEnd();

            // collect ticket number into notes
            $ticketNumber = $this->re("/{$this->opt($this->t('Ticket number'))}\:\s+(\d{8,})\s+/i", $ticket);

            if (!empty($ticketNumber)) {
                $e->setNotes('Ticket numbers: ' . $ticketNumber);
            }
        }

        foreach ($email->getItineraries() as $e) {
            // set guest count
            if (!empty($e->getNotes())) {
                $e->setGuestCount(substr_count($e->getNotes(), ',') + 1);
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));
            $this->parseEvent($email, $text);
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $in = [
            "/^\s*(\d+)\/(\d+)\/(\d{4})\s*$/", // 28/05/2025 => 28.05.2025
            "/^\s*(\d+)\/([^\d\s]+)\/(\d{4})\s*$/", // 18/Jun/2025 => 18 Jun 2025
        ];
        $out = [
            "$1.$2.$3",
            "$1 $2 $3",
        ];
        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        return strtotime($date);
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
