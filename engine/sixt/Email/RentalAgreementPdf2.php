<?php

namespace AwardWallet\Engine\sixt\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class RentalAgreementPdf2 extends \TAccountChecker
{
    public $mailFiles = "sixt/it-336632838.eml, sixt/it-337708261.eml, sixt/it-902221157.eml, sixt/it-911288998.eml";
    public $detectFrom = ["noreply@sixt.com"];
    public $detectSubject = [
        'Rental Agreement',
    ];

    public $detectProvider = ['www.sixt.com', 'Sixt Rent a Car, LLC',
        'Sixt Rent a Car, S.L.', 'Sixt SAS', 'SIXT App now', ];
    public $lang = '';
    public $oldConfirmation = [];
    public $pdfNamePattern = ".*pdf";

    public static $dictionary = [
        'en' => [
            'Rental Agreement' => ['Rental Agreement', 'SIXT+ statement of cost', 'RENTAL AGREEMENT', 'SIXT+ STATEMENT OF COST'],
            'Time out'         => ['Time out', 'Start subsc. period:'],
            'Due in'           => ['Due in', 'Next renewal:'],
            'Renter/ Driver'   => ['Renter/ Driver', 'Lessee/ Driver', 'Driver', 'Renter/ Driver(1)'],
            'endTraveller'     => ['NO ADDITIONAL', 'Renter/ Invoice Recipient', 'Lessee/ Invoice Recipient'],
            //            'Res-No.:' => '',
            //            'Sum net' => '',
            'Sum gross' => ['Sum gross', 'gross', 'Total: gross', 'Total gross', 'Total: gross:', 'Sum gross:', 'Sum: gross'],
            //            'Model:' => '',
            //            'Vehicle class:' => '',
            'Payment details' => ['Payment details', 'PAYMENT DETAILS'],
            'BOOKING DETAILS' => ['BOOKING DETAILS', 'Booking details'],
        ],
        'es' => [
            'Rental Agreement' => ['Contrato de alquiler', 'CONTRATO DE ALQUILER'],
            'Time out'         => 'Entrega',
            'Due in'           => 'Devolución',
            'Renter/ Driver'   => ['Arrendatario/ Conductor'],
            //            'endTraveller' => [''],
            'Res-No.:'        => 'No. Res.:',
            'Sum gross'       => ['Total gross', 'Total: gross:', 'Total: gross'],
            'Model:'          => 'Tipo de Vehículo:',
            'Vehicle class:'  => 'Grupo:',
            'Payment details' => ['Detalle alquiler', 'DETALLE ALQUILER'],
            'BOOKING DETAILS' => ['Detalles de la reserva', 'DETALLES DE LA RESERVA'],
        ],
        'de' => [
            'Rental Agreement' => ['Mietvertrag', 'MIETVERTRAG', 'SIXT+ KOSTEN-AUFSTELLUNG'],
            'Time out'         => 'Übergabe',
            'Due in'           => ['Rückgabe', 'Aut. Verlaengerung:'],
            'Renter/ Driver'   => ['Mieter/ Fahrer', 'Mieter'],
            'endTraveller'     => ['Mieter/ Rechnungsempfänger', 'Rückgabe'],
            'Res-No.:'         => 'Res-Nr.:',
            'Sum net'          => 'Summe netto',
            'Sum gross'        => ['Summe brutto', 'Summe brutto:', 'Summe: brutto'],
            'Model:'           => 'Modell:',
            'Vehicle class:'   => 'Fahrzeugklasse:',
            'Payment details'  => ['Zahlungsinformationen', 'ZAHLUNGSINFORMATIONEN'],
            'BOOKING DETAILS'  => ['DETAILS ZUM FAHRZEUG', 'Buchungsdetails'],
        ],
        'fi' => [
            'Rental Agreement' => 'VUOKRASOPIMUS',
            'Time out'         => 'Vuokraus alkoi',
            'Due in'           => 'Vuokraus päättyy',
            'Renter/ Driver'   => ['Vuokraaja 1/ Kuljettaja'],
            'endTraveller'     => ['Vuokraaja 2/ Yritys'],
            'Res-No.:'         => 'Varausnumero:',
            'Sum gross'        => 'Sum brutto',
            'Model:'           => 'Autotyyppi:',
            'Vehicle class:'   => 'Autoluokka:',
            'Payment details'  => 'MAKSUTIEDOT',
            // 'BOOKING DETAILS' => [''],
        ],
        'it' => [
            'Rental Agreement' => ['CONTRATTO DI NOLEGGIO', 'Contratto di noleggio'],
            'Time out'         => 'Uscita',
            'Due in'           => 'Rientro',
            'Renter/ Driver'   => ['Noleggiatore/ Conducente'],
            'endTraveller'     => ['Noleggiatore/ Destinatario della'],
            'Res-No.:'         => 'Res. No.:',
            'Sum gross'        => ['Totale lordo', 'Totale: lordo'],
            'Model:'           => 'Tipo vettura:',
            'Vehicle class:'   => 'Gruppo:',
            'Payment details'  => ['DETTAGLI DEL PAGAMENTO', 'Dettagli del pagamento'],
            'BOOKING DETAILS'  => ['DETTAGLI PRENOTAZIONE:', 'Dettagli prenotazione:'],
        ],
        'fr' => [
            'Rental Agreement' => ['CONTRAT DE LOCATION', 'Contrat de location'],
            'Time out'         => 'Départ',
            'Due in'           => 'Retour',
            'Renter/ Driver'   => ['Locataire / Conducteur'],
            'endTraveller'     => ['Locataire / Destinataire de la facture'],
            'Res-No.:'         => 'No. Res.:',
            'Sum gross'        => 'Montant TTC',
            'Model:'           => 'Modèle :',
            'Vehicle class:'   => 'Catégorie :',
            'Payment details'  => 'DéTAILS DU PAIEMENT',
            'BOOKING DETAILS'  => ['DéTAILS DE LA RéSERVATION'],
        ],
        'nl' => [
            'Rental Agreement' => 'HUUROVEREENKOMST',
            'Time out'         => 'Vertrek',
            'Due in'           => 'Terugkomst',
            'Renter/ Driver'   => ['Huurder / Bestuurder'],
            'endTraveller'     => ['Huurder / Factuurontvanger'],
            'Res-No.:'         => 'Reserveringsnummer:',
            'Sum gross'        => 'Totaal inclusief',
            'Model:'           => 'Model:',
            'Vehicle class:'   => 'Voertuigklasse:',
            'Payment details'  => 'BETALINGSGEGEVENS',
            'BOOKING DETAILS'  => ['BOEKINGGEGEVENS'],
        ],
    ];

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            if (($text = \PDF::convertToText($parser->getAttachmentBody($pdf))) !== null) {
                if ($this->detectPdfBody($text)) {
                    $this->parseEmailPdf($text, $email);
                }
            }
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdfProvider($text) === false) {
                continue;
            }

            if ($this->detectPdfBody($text)) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        foreach ($this->detectFrom as $dFrom) {
            if (stripos($from, $dFrom) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (self::detectEmailFromProvider($headers['from']) === false) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function parseEmailPdf($textPDF, Email $email)
    {
        $tableText = $this->re("/{$this->opt($this->t('Rental Agreement'))}.*\n([\s\S]+\n *{$this->opt($this->t('Sum gross'))}.+)/ui", $textPDF);

        $tableText2 = $this->re("/{$this->opt($this->t('Rental Agreement'))}.*\n([\s\S]+) {3,}{$this->opt($this->t('BOOKING DETAILS'))}\n/ui", $textPDF);

        if (empty($tableText) || mb_strlen($tableText2) > mb_strlen($tableText)) {
            $tableText = $tableText2;
        }
        $position = $this->rowColumnPositions($this->inOneRow($tableText));
        $col2 = strlen($this->re("/\n(.+ {3,}){$this->opt($this->t('Model:'))}/", $textPDF));

        foreach ($position as $p) {
            if (abs($col2 - $p) < 10) {
                $col2 = $p;
            }
        }
        $table = $this->createTable($tableText, [0, $col2], false);

        $confirmation = $this->re("/\n *{$this->opt($this->t('Res-No.:'))}\s+([\w\/]+)( {3,}|\n)/u", $table[0] ?? '');

        if (!in_array($confirmation, $this->oldConfirmation)) {
            $r = $email->add()->rental();

            //General
            if (preg_match("/^\s*n\s*\\/\s*a\s*$/i", $confirmation)) {
                $r->general()
                    ->noConfirmation();
            } else {
                $r->general()
                    ->confirmation($confirmation);
            }
            $this->oldConfirmation[] = $confirmation;

            $addressPart = $this->re("/^([\s\S]+?)\n\s*{$this->opt($this->t('Res-No.:'))}/u", $table[0] ?? '');
            $pricePart = $this->re("/\n\s*{$this->opt($this->t('Res-No.:'))}\s*\S.+\n([\s\S]+)/u", $table[0] ?? '');
            $addInfo = $table[1] ?? '';

            $table1 = $this->createTable($addressPart, $this->rowColumnPositions($this->inOneRow($addressPart)));

            $r->general()
                ->travellers(explode("\n", preg_replace("/\n\s*{$this->opt($this->t('endTraveller'))}[\s\S]*/", '',
                    $this->re("/{$this->opt($this->t('Renter/ Driver'))}\s+(.+(?:\n.+?)?)(?:\n\s*{$this->opt($this->t('endTraveller'))}|\n\n*|\s*$)/", $table1[1] ?? ''))));

            // Pick Up, Drop Off
            if (preg_match("/{$this->opt($this->t('Time out'))}\n+(?<puDate>.+)\n(?<puLocation>[\s\S]+?)\n{$this->opt($this->t('Due in'))}\n+(?<doDate>.+)\n(?<doLocation>[\s\S]+)$/", $table1[0] ?? '', $m)) {
                if (strpos($m['doLocation'], $m['puLocation']) === 0 && strlen($m['doLocation']) > strlen($m['puLocation'])) {
                    $m['puLocation'] = $m['doLocation'];
                }
                $r->pickup()
                    ->date($this->normalizeDate($m['puDate']))
                    ->location($this->nice($m['puLocation']))
                ;
                $r->dropoff()
                    ->date($this->normalizeDate($m['doDate']))
                    ->location($this->nice($m['doLocation']))
                ;
            }

            // Car
            $r->car()
                ->model($this->nice($this->re("/{$this->opt($this->t('Model:'))} *([\s\S]+?)\n {0,5}\w.*:/u", $addInfo)))
                ->type($this->nice($this->re("/{$this->opt($this->t('Vehicle class:'))} *([\s\S]+?)(?:\n {0,5}\w.*:|$)/u", $addInfo)))
            ;

            // Price
            $total = $this->getTotal($this->re("/\n *{$this->opt($this->t('Sum gross'))}:? *(.+)/", $pricePart));

            if (empty($total['amount']) && !preg_match("/ {2,}([A-Z]{3} ?\d[\d.,]*|\d[\d.,]* ?[A-Z]{3}) {2,}/", $pricePart)) {
            } else {
                $r->price()
                    ->total($total['amount'])
                    ->currency($total['currency']);
            }
        }

        return true;
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    private function stripos($haystack, $arrayNeedle)
    {
        $arrayNeedle = (array) $arrayNeedle;

        foreach ($arrayNeedle as $needle) {
            if (stripos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function detectPdfProvider($body)
    {
        foreach ($this->detectProvider as $dProvider) {
            if ($this->stripos($body, $dProvider) !== false) {
                return true;
            }
        }

        return false;
    }

    private function detectPdfBody($body)
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Rental Agreement']) && !empty($dict['Payment details'])
                && $this->stripos($body, $dict['Rental Agreement']) !== false
                && $this->stripos($body, $dict['Payment details']) !== false
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function t($s)
    {
        if (!isset($this->lang) || !isset(self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    private function normalizeDate($str)
    {
        // $this->logger->debug('$date IN = '.print_r( $str, true));
        $str = preg_replace("/\b(00:\d{2})\s*AM\s*$/i", '$1', $str);
        $in = [
            // 01.04.2023 / 14:43
            "/^\s*(\d{1,2})\.(\d{2})\.(\d{4})\s*\\/\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/iu",
        ];
        $out = [
            "$1.$2.$3, $4",
        ];
        $str = preg_replace($in, $out, $str);

//        if ($this->lang !== 'en' && preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
//            if (($en = MonthTranslate::translate($m[1], $this->lang)) || ($en = MonthTranslate::translate($m[1], 'da')) || ($en = MonthTranslate::translate($m[1], 'no'))) {
//                $str = str_replace($m[1], $en, $str);
//            }
//        }

        // $this->logger->debug('$date OUT = '.print_r($str, true));
        return strtotime($str);
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
                $cols[$k][] = mb_substr($row, $p, null, 'UTF-8');
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);

        foreach ($cols as &$col) {
            $col = implode("\n", $col);

            if ($trim === true) {
                $col = preg_replace(["/^\s*/m", "/\s*$/m"], '', $col);
            }
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

    private function getTotal($text)
    {
        $result = ['amount' => null, 'currency' => null];

        if (preg_match("#^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$#", $text, $m)
            || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$#", $text, $m)
            // $232.83 USD
            || preg_match("#^\s*\D{1,5}(?<amount>\d[\d\., ]*)\s*(?<currency>[A-Z]{3})\s*$#", $text, $m)
        ) {
            $m['currency'] = $this->currency($m['currency']);
            $m['amount'] = PriceHelper::parse($m['amount']);

            if (is_numeric($m['amount'])) {
                $m['amount'] = (float) $m['amount'];
            } else {
                $m['amount'] = null;
            }
            $result = ['amount' => $m['amount'], 'currency' => $m['currency']];
        }

        return $result;
    }

    private function currency($s)
    {
        if ($code = $this->re("#\b([A-Z]{3})\b$#", $s)) {
            return $code;
        }
        $sym = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
            '₹' => 'INR',
        ];

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return null;
    }

    private function nice($str)
    {
        return trim(preg_replace("/\s+/", ' ', $str));
    }
}
