<?php

namespace AwardWallet\Engine\jetblue\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Flight extends \TAccountChecker
{
    public $mailFiles = "jetblue/it-29162655.eml, jetblue/it-74940178.eml, jetblue/it-75282577.eml, jetblue/it-902542101.eml, jetblue/it-902845147.eml, jetblue/it-903607144.eml";
    public static $dictionary = [
        'en' => [
            'Hello ' => ['Hello ', 'Hi,'],
            // 'Flight' => '',
            // '#' => '',
            // 'Confirmation Code:' => '',
            'departureTime'  => ['New departure time:', 'Your flight is now scheduled to depart at:', 'Updated departure time:'],
            'departureTime2' => ['Original departure time:'],
            'cancelledText'  => ['The following flight has been cancelled', 'Your upcoming flight has been cancelled'],
        ],
        'fr' => [
            'Hello ' => 'Bonjour,',
            'Flight' => 'Vol',
            '#'      => 'n°',
            // 'Confirmation Code:' => '',
            'departureTime' => ['Nouvelle heure de départ:'],
        ],
    ];

    private $detectSubject = [
        // en
        'Important Flight Information | GATE CHANGE',
        'Important Flight Information | UPDATED DEPARTURE TIME',
        'Important Flight Information | DEPARTURE DELAY',
        'Important Flight Information | CANCELLED',
        'Departure Gate has changed for ',
        'delay has decreased.',
        'is delayed.',
        'is back on time.',
        ' is delayed again.',
        ' delay update.',
        ' has been cancelled.',
        // fr
        ' est retardé.',
        ' La porte de départ du vol ',
    ];
    private $detectBody = [
        'en' => [
            'Your departure time has changed',
            'Your flight is now scheduled to depart at',
            'Updated departure time',
            'your departure gate has changed',
            'We need to reschedule your flight\'s departure time',
            'Your flight is now ready to take off at the original departure time',
            'had to make an additional change to your departure time',
            'The following flight has been cancelled',
            'Your upcoming flight has been cancelled',
        ],
        'fr' => [
            "reprogrammer l'heure de départ de votre vol",
            "votre porte d'embarquement a changé",
        ],
    ];

    private $lang = 'en';

    private $from = '/[@\.a-z]+jetblue\.com/';

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        foreach ($this->detectBody as $lang => $detect) {
            if ($this->http->XPath->query("//node()[{$this->contains($detect)}]")->length > 0) {
                $this->lang = $lang;

                break;
            }
        }

        $this->parseEmail($email);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//a/@href[{$this->contains(['jetblue.com'])}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->starts('@')}][{$this->contains(['JetBlue'])}]")->length === 0
        ) {
            return false;
        }

        foreach ($this->detectBody as $lang => $detect) {
            if ($this->http->XPath->query("//node()[{$this->contains($detect)}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!isset($headers['from']) || stripos($headers['from'], 'do-not-reply@flightupdates.jetblue.com') === false) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match($this->from, $from) > 0;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function parseEmail(Email $email): void
    {
        $f = $email->add()->flight();

        $conf = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Confirmation Code:'))}]",
            null, true, "/{$this->opt($this->t('Confirmation Code:'))}\s*([A-Z\d]{5,7})\s*$/");

        if (!empty($conf) || $this->http->XPath->query("//node()[{$this->contains($this->t('Confirmation Code:'))}]")->length > 0) {
            $f->general()
                ->confirmation($conf);
        } else {
            $f->general()
                ->noConfirmation();
        }

        $pax = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello '))}]", null, true,
            "/{$this->opt($this->t('Hello '))}\s*(.+?)[,\.]$/");

        if (!empty($pax)) {
            $f->general()
                ->traveller($pax, false);
        }

        $text = implode("\n", $this->http->FindNodes("//text()[normalize-space()]"));
        // $segments = $this->split("/\n(.+ {$this->opt($this->t('Flight #'))} ?\d{1,4}\n)/", $text);
        $segments = $this->split("/\n((?:.* )?{$this->opt($this->t('Flight'))}(?: .*)? {$this->opt($this->t('#'))} ?\d{1,4}\n)/", $text);

        foreach ($segments as $sText) {
            $s = $f->addSegment();

            if ($this->http->XPath->query("//node()[{$this->contains($this->t('cancelledText'))}]")->length > 0) {
                $s->extra()
                    ->cancelled()
                    ->status('Cancelled')
                ;
            }

            $date = '';

            if (preg_match("/^\s*(.+) +{$this->opt($this->t('Flight'))} {$this->opt($this->t('#'))} ?(\d+)\n(?<date>.+)/u", $sText, $m)
                || preg_match("/^\s*{$this->opt($this->t('Flight'))} (.+) {$this->opt($this->t('#'))} ?(\d+)\n(?<date>.+)/u", $sText, $m)
            ) {
                $s->airline()
                    ->name($m[1])
                    ->number($m[2]);
                $date = $m['date'];
            }

            if (preg_match('/\n(.+?) *\(([A-Z]{3})\) to (.+?) *\(([A-Z]{3})\)\n/', $sText, $m)) {
                $s->departure()
                    ->name($m[1])
                    ->code($m[2]);
                $s->arrival()
                    ->name($m[3])
                    ->code($m[4]);
            }

            $depTime = $this->re("/{$this->opt($this->t('departureTime'))}\s*(\d{1,2}:\d{2}\s*[AP]M)\n/", $sText);

            if (empty($depTime) && preg_match('/^\s*(\w+ \d{1,2},\s*\d{4}),? at (\d{1,2}:\d{2}(?: *[ap]m)?)\s*$/i', $date, $m)) {
                $date = $m[1];
                $depTime = $m[2];
            }

            if (empty($depTime) && preg_match("/{$this->opt($this->t('departureTime2'))}\s*(\d{1,2}:\d{2}\s*[AP]M)\n/", $sText, $m)) {
                $depTime = $m[1];
            }

            if (!empty($date) && !empty($depTime)) {
                $s->departure()
                    ->date($this->normalizeDate($date . ', ' . $depTime));

                $s->arrival()
                    ->noDate();
            } elseif (!empty($date) && empty($depTime)) {
                $s->departure()
                    ->noDate()
                    ->day($this->normalizeDate($date));

                $s->arrival()
                    ->noDate();
            }
        }
    }

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r($date, true));
        $in = [
            // Apr 23, 2025 at 9 AM
            // '/^\s*([[:alpha:]]+)\s+(\d{1,2})\s*[,\s]\s*(\d{4})\s+\D+\s+(\d{1,2})\s*([ap]m)\s*$/iu',
            // Abr 27, 2025 às 10:30
            // '/^\s*([[:alpha:]]+)\s+(\d{1,2})\s*[,\s]\s*(\d{4})\s+\D+\s+(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/iu',
            // 16. April 2025 um 11:00 Uhr
            // '/^\s*(\d{1,2})[.]?\s+([[:alpha:]]+)[.]?\s+(\d{4})\s+\D+\s+(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*(?:Uhr)?\s*$/iu',
        ];
        $out = [
            // '$2 $1 $3, $4:00 $5',
            // '$2 $1 $3, $4',
            // '$1 $2 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);
        // $this->logger->debug('date replace = ' . print_r($date, true));

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        // $this->logger->debug('date end = ' . print_r($date, true));

        return strtotime($date);
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
            return 'starts-with(normalize-space(.), "' . $s . '")';
        }, $field)) . ')';
    }

    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) use ($text) {
            return 'contains(' . $text . ', "' . $s . '")';
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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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
}
