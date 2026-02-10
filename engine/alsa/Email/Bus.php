<?php

namespace AwardWallet\Engine\alsa\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Bus extends \TAccountChecker
{
    public $mailFiles = "alsa/it-604441528.eml, alsa/it-608352086.eml, alsa/it-610229706.eml, alsa/it-611744880.eml, alsa/it-613185681.eml, alsa/it-623306766.eml, alsa/it-890778771.eml, alsa/it-893115423.eml, alsa/it-893626471.eml, alsa/it-896820279.eml, alsa/it-899360083.eml";

    public $detectSubjects = [
        'en' => [
            'Thank you for choosing Alsa',
        ],
        'es' => [
            'Gracias por comprar en Alsa',
            'Confirmación de venta en Trenes Turísticos ALSA',
            'Confirmación de cambio de billete con localizador',
        ],
        'fr' => [
            "Merci de votre achat auprès d'Alsa",
        ],
        'it' => [
            'Grazie per il tuo acquisto presso Alsa',
        ],
        'pt' => [
            'Obrigado por comprar na Alsa',
        ],
    ];

    public $lang;

    public $pdfNamePattern = ".*pdf";
    public $datePoints = [];

    public $detectLang = [
        "en" => ["Your ticket"],
        "pt" => ["Para a sua segurança"],
        "es" => ["Tu billete"],
        "fr" => ["Votre billet"],
        "it" => ["Il tuo biglietto"],
    ];

    public static $dictionary = [
        "en" => [
            'Vehicle' => ['Bus', 'Car'], // Bus + Car
        ],

        "es" => [
            'ALSA informs:' => 'Alsa informa:',
            'Your ticket'   => 'Tu billete',
            'Observations'  => 'Observaciones',
            'Vehicle'       => ['Autobús', 'Coche'], // Bus + Car
            //Bus
            'Booking reference'      => 'Localizador',
            'Ticket No.'             => 'Nº Billete',
            'Purchase date:'         => 'Fecha de compra:',
            'Bus'                    => 'Autobús',
            'Class'                  => 'Clase',
            'Stops'                  => 'Paradas',
            'Seat'                   => 'Asiento', 'Asient',
            'Total'                  => 'Importe total',
            // Train
            'Car'                    => 'Coche',
            'Line'                   => 'Línea',
        ],

        "fr" => [
            'ALSA informs:' => 'Alsa vous informe que',
            'Your ticket'   => 'Votre billet',
            'Observations'  => 'Observations',
            'Vehicle'       => ['Autobus'], // Bus + Car
            //Bus
            'Booking reference'      => 'Numéro de référence',
            'Ticket No.'             => 'No de billet',
            'Purchase date:'         => "Date d'achat :",
            'Bus'                    => 'Autobus',
            //Class' => '',
            'Stops'                  => 'Arrêts',
            'Seat'                   => 'Place assise',
            'Total'                  => 'Prix total',
            // Train
            //'Car' => '',
            'Line'                   => 'Ligne',
        ],

        "it" => [
            'ALSA informs:' => 'ALSA informa:',
            'Your ticket'   => 'Il tuo biglietto',
            'Observations'  => 'Osservazioni',
            'Vehicle'       => ['Autobus'], // Bus + Car
            //Bus
            'Booking reference' => 'Codice di prenotazione',
            'Ticket No.'        => 'N° di Biglietto',
            'Purchase date:'    => "Data di acquisto:",
            'Bus'               => 'Autobus',
            //Class' => '',
            'Stops'             => 'Fermate',
            'Seat'              => 'Sedile',
            'Total'             => 'Importo totale',
            // Train
            //'Car' => '',
            'Line'              => 'Linea',
        ],

        "pt" => [
            'ALSA informs:' => 'ALSA INTERNACIONAL',
            'Your ticket'   => 'Tu billete',
            'Observations'  => 'Observaciones',
            'Vehicle'       => ['Autobús'], // Bus + Car
            //Bus
            'Booking reference' => 'Localizador',
            'Ticket No.'        => ['Nº Billete'],
            'Purchase date:'    => "Fecha de compra:",
            'Bus'               => 'Autobús',
            //Class' => '',
            'Stops'                  => 'Paradas',
            'Seat'                   => ['Asiento'],
            'Total'                  => 'Importe total:',
            // Train
            //'Car' => '',
            'Line'              => 'Línea',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]alsa\.es$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from']) || stripos($headers['from'], '@alsa.es') === false)
            && stripos($headers['subject'], 'Alsa') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubject) {
                if (stripos($headers['subject'], $dSubject) !== false) {
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

        $this->assignLang($text);

        if (stripos($text, $this->t('ALSA informs:')) !== false
            && stripos($text, $this->t('Your ticket')) !== false
            && stripos($text, $this->t('Observations')) !== false
        ) {
            return true;
        }

        return false;
    }

    public function ParseBusPDF(Email $email, $text)
    {
        $b = null;
        $type = null;

        if (stripos($text, $this->t('Bus')) !== false) {
            $b = $email->add()->bus();
            $type = 'bus';
        } elseif (stripos($text, $this->t('Car')) !== false) {
            $b = $email->add()->train();
            $type = 'train';
        }

        $patterns = [
            'date' => '\d+[ ]+[[:alpha:]]+[ ]+\d{4}', // 19 June 2025
            'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.
        ];

        $currency = $this->normalizeCurrency($this->re("/{$this->opt($this->t('Total'))}[\:\s]*[\d\.\,]+(\D)/u", $text));

        if (preg_match_all("/{$this->opt($this->t('Total'))}[\:\s]*([\d\.\,]+)/", $text, $m) && !empty($currency)) {
            $summ = [];

            foreach ($m[1] as $price) {
                $summ[] = PriceHelper::parse($price, $currency);
            }

            $b->price()
                ->total(array_sum($summ))
                ->currency($currency);
        }

        if (preg_match_all("/(?<desc>{$this->opt($this->t('Booking reference'))})\n*\s+(?<confNumber>[A-z\d]{6,})/", $text, $m)) {
            $confs = array_unique($m['confNumber']);

            foreach ($confs as $conf) {
                $b->general()
                    ->confirmation($conf, $m['desc'][0]);
            }
        }

        $segments = $this->splitText($text, "/((?:[ ]*\d+\s*\w+\s*\d{4}\s+\d+\s*\w+\s*\d{4}\s+)?{$this->opt($this->t('Your ticket'))})/", true);
        $travellers = [];
        $tickets = [];

        foreach ($segments as $segment) {
            $s = null;

            // collect dates (days)
            if (preg_match("/\s*(?<depDate>{$patterns['date']})\s+(?<arrDate>{$patterns['date']})/u", $segment, $m)) {
                $depDate = $m['depDate'];
                $arrDate = $m['arrDate'];
            }

            // collect times
            if (preg_match("/(?:^|\s)(?<depTime>{$patterns['time']})\s+(?<arrTime>{$patterns['time']})/u", $segment, $m)) {
                $depTime = $m['depTime'];
                $arrTime = $m['arrTime'];
            }

            // build dates
            if (!empty($depDate) && !empty($depTime)) {
                $depDateTime = $this->normalizeDate($depDate . ', ' . $depTime);
            }

            if (!empty($arrDate) && !empty($arrTime)) {
                $arrDateTime = $this->normalizeDate($arrDate . ', ' . $arrTime);
            }

            // get segment
            if (!empty($depDateTime) && (!empty($arrDateTime)) && in_array($depDateTime . ' ' . $arrDateTime, $this->datePoints) === false) {
                $s = $b->addSegment();
            } else {
                foreach ($b->getSegments() as $seg) {
                    if (!empty($depDateTime) && !empty($arrDateTime) && $seg->getDepDate() === $depDateTime && $seg->getArrDate() === $arrDateTime) {
                        $s = $seg;

                        break;
                    }
                }
            }

            if ($s === null) {
                $this->logger->debug("segment is not found");

                return false;
            }

            $s->departure()
                ->date($depDateTime);
            $s->arrival()
                ->date($arrDateTime);

            // extract segment part with department and arrival places
            $depAndArrPart = $this->re("/{$patterns['time']}\s+{$patterns['time']}.*?\n+(.+?)\s+(?:{$this->opt($this->t('Class'))}[ ]*)?{$this->opt($this->t('Vehicle'))}[ ]*{$this->opt($this->t('Seat'))}/su", $segment);
            // extract segment part with other info (bus number/car number, seat, cabin, service, stops, traveller and ticket)
            $otherPart = $this->re("/{$this->opt($this->t('Vehicle'))}[ ]*{$this->opt($this->t('Seat'))}.+?([ ]*[[:alpha:]]*[ ]+\d*[ ]+\d*.+?{$this->opt($this->t('Ticket No.'))}\s+[\d\-]+)(?:\s|$)/su", $segment);

            // get columns positions for depAndArrPart
            $pos = $this->columnPositions($depAndArrPart);
            $pos = array_slice($pos, 0, 3);
            $routeTable = $this->createTable($depAndArrPart, $pos);

            $depText = $routeTable[1];
            $arrText = $routeTable[2];

            $bookingDate = $this->re("/{$this->opt($this->t('Purchase date:'))}\s*([\d\/]+)/", $routeTable[0]);

            if (!empty($bookingDate)) {
                $b->general()
                    ->date(strtotime(str_replace('/', '.', $bookingDate)));
            }

            // example depText and arrText
            /*
                SANTIAGO
                COMPOSTELA

                ESTACION DE AUTOBUSES,
                Calle CLARA CAMPOAMOR s/n,
                SANTIAGO DE COMPOSTELA
            */

            $depAndArrPattern = "/^"
                . "\s*(?<name>\b.+\b[\.\)]?)[ ]*"
                . "\n{2,}"
                . "[ ]*(?<address>\b.+?\b)\s*$"
                . "/su";

            $depAndArrPattern2 = "/^"
                . "\s*(?<name>(?:.+?\n){1,3})"
                . "[ ]*(?<address>\b.+?\b)\s*$"
                . "/su";

            // collect department info
            if (preg_match($depAndArrPattern, $depText, $m)
                || preg_match($depAndArrPattern2, $depText, $m)
            ) {
                $s->departure()
                    ->name($this->normalizeName($m['name']))
                    ->address($this->normalizeName($m['address']) . ', SPAIN');
            }

            // collect arrival info
            if (preg_match($depAndArrPattern, $arrText, $m)) {
                $s->arrival()
                    ->name($this->normalizeName($m['name']))
                    ->address($this->normalizeName($m['address']) . ', SPAIN');
            }

            // example otherPart
            /*
                Comfort      3485        40
                Stops: 4
                Line: Santiago-Porto

                Blackall, Loretta
                PB4730807

                Booking reference
                1f58qpv
                Ticket No.
                675-3-999-3146551-1
            */

            $otherPattern = "/"
                . "\s*(?:(?<cabin>[[:alpha:] ]+)[ ]*)?(?:(?<number>\d+)[ ]*)?(?:(?<seat>\d+)[ ]*)?(?<cabin2>[[:alpha:] ]+)?\s*"
                . "[ ]*{$this->opt($this->t('Stops'))}[\:\s]+(?<stops>\d+)[ ]*\n+"
                . "[ ]*{$this->opt($this->t('Line'))}\:[ ]*(?<service>.+?)[ ]*\n+"
                . "[ ]*(?<traveller>[[:alpha:]][-.,\/\'’[:alpha:] ]*[[:alpha:]])[ ]*\n+"
                . ".+?"
                . "[ ]*{$this->opt($this->t('Booking reference'))}[ ]*\n+"
                . ".+?"
                . "[ ]*{$this->opt($this->t('Ticket No.'))}[ ]*\n+"
                . "[ ]*(?<ticket>[\d\-]+)\s*"
                . "/su";

            // collect other info
            if (preg_match($otherPattern, $otherPart, $m)) {
                $traveller = $this->normalizeName($m['traveller']);

                if (!empty($m['number'])) {
                    if ($type === 'bus') {
                        $s->extra()
                            ->number($m['number']);
                    } elseif ($type === 'train') {
                        $s->extra()
                            ->car($m['number'])
                            ->noNumber();
                    }
                } else {
                    $s->extra()
                        ->noNumber();
                }

                if (!empty($m['seat'])) {
                    $s->extra()
                        ->seat($m['seat'], false, false, $traveller);
                }

                if (!empty($m['cabin'])) {
                    $s->extra()
                        ->cabin($m['cabin']);
                } elseif (!empty($m['cabin2'])) {
                    $s->extra()
                        ->cabin($m['cabin2']);
                }

                if (!empty($m['service']) && $type === 'train') {
                    $s->extra()
                        ->service($m['service']);
                }

                // it-604441528.eml - exist segment with 11 stops
                if ($m['stops'] <= 10) {
                    $s->extra()
                        ->stops($m['stops']);
                }
            }

            if (!empty($traveller)) {
                $travellers[] = $traveller;
            }

            if (!empty($traveller) && !empty($m['ticket'])) {
                $tickets[$m['ticket']] = $traveller;
            }

            $this->datePoints[] = $depDateTime . ' ' . $arrDateTime;
            unset($traveller, $depDate, $arrDate, $depTime, $arrTime, $depDateTime, $arrDateTime);
        }

        if (!empty($travellers)) {
            $b->general()
                ->travellers(array_unique($travellers));
        }

        foreach ($tickets as $ticket => $traveller) {
            $b->addTicketNumber($ticket, false, $traveller);
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            $this->assignLang($text);

            $this->ParseBusPDF($email, $text);
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
            return preg_replace("/\s+/", '\s+', preg_quote($s, $delimiter));
        }, $field)) . ')';
    }

    private function splitText($textSource = '', $pattern = '', $saveDelimiter = false)
    {
        $result = [];

        if ($saveDelimiter) {
            $textFragments = preg_split($pattern, $textSource, null, PREG_SPLIT_DELIM_CAPTURE);
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
        foreach ($this->detectLang as $lang => $arrayWords) {
            foreach ($arrayWords as $word) {
                if (stripos($text, $word) !== false) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^(\d+)\s*(\w+)\s*(\d{4})\,\s*([\d\:]+)$#u", //10 octubre 2023, 17:30
        ];
        $out = [
            "$1 $2 $3, $4",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            } elseif ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], 'es')) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }

    private function normalizeCurrency($string)
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
            'AUD' => ['A$'],
            'EUR' => ['€', 'Euro'],
            'USD' => ['US Dollar'],
        ];

        foreach ($currencies as $code => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency) {
                    return $code;
                }
            }
        }

        return $string;
    }

    private function normalizeName($string)
    {
        $string = preg_replace("/\s+\,\s*/", ', ', $string);
        $string = preg_replace("/\s+/", ' ', $string);

        return $string;
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
