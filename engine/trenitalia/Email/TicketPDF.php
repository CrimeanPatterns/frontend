<?php

namespace AwardWallet\Engine\trenitalia\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class TicketPDF extends \TAccountChecker
{
    public $mailFiles = "trenitalia/it-106426729.eml, trenitalia/it-114888737.eml, trenitalia/it-173467622.eml, trenitalia/it-184660361.eml, trenitalia/it-31328493.eml, trenitalia/it-321396121.eml, trenitalia/it-52092385.eml, trenitalia/it-919374024.eml, trenitalia/it-922223677.eml, trenitalia/it-922457818.eml, trenitalia/it-922588552.eml, trenitalia/it-95911436.eml, trenitalia/it-96480036.eml";

    public $reSubject = [
        "fr" => "Votre Billet Trenitalia",
        // "it" => "",
        "de" => "Ihre Tickets für die Fahrt von",
        "en" => "Your Trenitalia Ticket",
    ];

    public $reBody = 'Trenitalia';
    public $reBody2 = [
        "fr" => "Gare de départ",
        "it" => "Stazione di Partenza",
        "de" => "Abfahrtsbahnhof",
        "en" => "Departure station",
    ];
    public $reBody3 = [
        "it" => "Riepilogo di Viaggio",
    ];

    public $lang = "it";

    public static $dictionary = [
        "fr" => [ // it-187572709-fr.eml
            'separator'             => '\n[ ]*VOYAGE\s+de\s+.+?\s+a\s+',
            //'Issue date'            => '',
            'Receipt n.'            => 'Reçu n°',
            'PNR'                   => 'PNR',
            'Ticket Code'           => 'Code du billet',
            'Stazione di partenza'  => ['Gare de départ', 'Gare de départ/From'],
            'Stazione di arrivo'    => ["Gare d'arrivée", "Gare d'arrivée/To"],
            'Treno'                 => 'Train',
            // 'Autobus' => '',
            'Importo totale'       => 'Montant total payé',
            'Importo pagato totale'=> 'Montant total payé',
            'DETTAGLIO PASSEGGERI' => 'DETAILS DU PASSAGER',
            'Altri dati'           => ['Acheteur:', "\n\n\n\n\n"],
            'Carrozza'             => 'Voiture',
            'Servizio'             => 'Service',
            'Posti'                => ['Sièges', 'Places'],
            'Ore'                  => 'Heure',
            'Offerta - Servizio'   => ['Offre -', 'Offre - Service'],
            'Nome Passeggero'      => 'Nom du Passager',
        ],
        "it" => [ // ?
            'separator'             => 'VIAGGIO\s+da\s+.+?\s+a\s+)|(DETTAGLIO VIAGGIO)|(\s+PNR[ ]*:+[ ]*[-A-Z\d]+\s+DETTAGLIO VIAGGIO',
            'Issue date'            => 'Data emissione',
            'Receipt n.'            => 'Ricevuta n.',
            'PNR'                   => 'PNR',
            'Ticket Code'           => ['Codice Biglietto', 'Titolo numero', 'Title number'],
            'Stazione di partenza'  => ['Stazione di partenza', 'Stazione di Partenza/From'],
            'Stazione di arrivo'    => ['Stazione di arrivo', 'Stazione di Arrivo/To'],
            //			'Treno' => 'Treno',
            //			'Autobus' => '',
            'Importo totale' => ['Importo totale', 'Total amount'],
            //          'Importo pagato totale' => '',
            //          'DETTAGLIO PASSEGGERI' => '',
            'Altri dati' => ['Altri dati', 'Acquirente', "\n\n\n\n\n"],
            //			'Carrozza' => 'Carrozza',
            //			'Servizio' => 'Servizio',
            'Posti' => ['Posti', 'Seats'],
            //			'Ore' => '',
            //			'Offerta - Servizio' => 'Offerta - Servizio',
            'Nome Passeggero' => ['Nome Passeggero', 'name (Adulto)', 'name (Adult)'],
        ],
        "de" => [ // it-114888737.eml
            'separator'            => '\n[ ]*REISE\s+von\s+.+?\s+Nach\s+)|(REISEVERBINDUNG IM DETAIL\/ITINERARY DETAILS',
            'Issue date'           => ['Ausstellungsdatum', 'Datum der Ausstellung'],
            // 'Receipt n.'            => '',
            'PNR'                  => 'PNR',
            'Ticket Code'          => ['Fahrschein-Nummer', 'Title number'],
            'Stazione di partenza' => ['Abfahrtsbahnhof', 'Abfahrtsbahnhof/From'],
            'Stazione di arrivo'   => ['Ankunftsbahnhof', 'Ankunftsbahnhof/To'],
            'Treno'                => 'Zug',
            // 'Autobus' => '',
            'Importo totale' => ['Total amount*:', 'Betrag*:'],
            // 'Importo pagato totale' => '',
            'DETTAGLIO PASSEGGERI' => 'PASSAGIEREDATEN',
            'Altri dati'           => ['Käufer:', "\n\n\n\n\n"],
            'Carrozza'             => 'Wagen',
            'Servizio'             => 'Serviceleistung',
            'Posti'                => 'Sitzplätze',
            'Ore'                  => 'Uhrzeit',
            'Offerta - Servizio'   => ['Angebot -', 'Angebot - Serviceleistung'],
            'Nome Passeggero'      => ['Passagiername', 'Passagiername/Passengername (Erwachsener)', 'Passagiername/Passenger name (Erwachsener)'],
            'Total amount'         => 'Betrag',
        ],
        "en" => [ // it-31328493.eml, it-95911436.eml, it-96480036.eml, it-106426729.eml
            'separator'             => '\n\s*(?:TRAVEL|Journey)\s+from\s+.+?\s+To\s+',
            'Receipt n.'            => 'Receipt n.',
            'PNR'                   => 'PNR',
            'Ticket Code'           => ['Ticket Code', 'Entitlement Number'],
            'Stazione di partenza'  => 'Departure station',
            'Stazione di arrivo'    => 'Arrival station',
            'Treno'                 => 'Train',
            'Autobus'               => ['Autobus', 'Bus'],
            'Importo totale'        => ['Total amount'],
            'Importo pagato totale' => 'Total Amount Paid',
            'DETTAGLIO PASSEGGERI'  => 'PASSENGERS DETAILS',
            'Altri dati'            => ['Buyer:', "TRANSPORT CONDITIONS", "\n\n\n\n\n"],
            'Carrozza'              => 'Coaches',
            'Servizio'              => 'Service',
            'Posti'                 => 'Seats',
            'Ore'                   => 'Hours',
            'Offerta - Servizio'    => ['Offer - Service', 'Service - Offer'],
            'Nome Passeggero'       => ['Passenger Name', 'Group leader:'],
        ],
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName(".*pdf");

        // main pdf with tickets
        foreach ($pdfs as $pdf) {
            if (($text = \PDF::convertToText($parser->getAttachmentBody($pdf))) === null) {
                continue;
            }

            foreach ($this->reBody2 as $lang => $phrase) {
                if (strpos($text, $phrase) !== false) {
                    $this->lang = $lang;
                    $this->parseEmail($email, $text);

                    continue 2;
                }
            }
        }

        // additional pdf with all passengers and seats
        foreach ($pdfs as $pdf) {
            if (($text = \PDF::convertToText($parser->getAttachmentBody($pdf))) === null) {
                continue;
            }

            foreach ($this->reBody3 as $lang => $phrase) {
                if (strpos($text, $phrase) !== false) {
                    $this->lang = $lang;
                    $this->parsePassengers2($email, $text);

                    continue 2;
                }
            }
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]trenitalia\.(?:com|it)\b/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) !== true && strpos($headers['subject'], 'Trenitalia') === false) {
            return false;
        }

        foreach ($this->reSubject as $re) {
            if (isset($headers['subject']) && strpos($headers["subject"], $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName('.*pdf');

        if (count($pdfs) > 0) {
            foreach ($pdfs as $pdf) {
                $body = \PDF::convertToText($parser->getAttachmentBody($pdf));

                foreach ($this->reBody2 as $re) {
                    if (stripos($body, $re) !== false) {
                        return true;
                    }
                }
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

    protected function parseEmail(Email $email, string $plainText): void
    {
        // build regex for all languages separator
        // and confirmation descriptions for all languages
        $allLangSeparator = '/((?:' . $this->opt('Issue Date');

        foreach (self::$dictionary as $phrases) {
            if (empty($phrases['Issue date'])) {
                continue;
            }

            $allLangSeparator .= '|' . $this->opt($phrases['Issue date']);
        }

        $allLangSeparator .= ')[ ]*\d+\/\d+\/\d{4})/ui';
        $segmentTexts = $this->splitter($allLangSeparator, $plainText);

        foreach ($segmentTexts as $segmentText) {
            unset($t);
            $segmentText = trim($segmentText);

            // assign lang for each segment
            foreach ($this->reBody2 as $lang => $phrase) {
                if (strpos($segmentText, $phrase) !== false) {
                    $this->lang = $lang;
                }
            }

            if (empty($segmentText) || !preg_match("/" . $this->opt($this->t('Offerta - Servizio')) . "/", $segmentText)) {
                continue;
            }

            // determine itinerary type
            $type = 'train';

            if (preg_match("/[ ]{2}{$this->opt($this->t('Autobus'))}(?:\s*\/\s*[^:]+)?:.+/", $segmentText)
                && !preg_match("/[ ]{2}{$this->opt($this->t('Treno'))}(?:\s*\/\s*[^:]+)?:.+/", $segmentText)
            ) {
                // it-96480036.eml
                $type = 'bus';
            }

            // collect PNR
            $confNumber = $this->re("/PNR\s*:\s*([-A-Z\d]{5,})[ ]*$/m", $segmentText);

            // find existing itinerary to which this prevSegment (segmentText) belongs
            foreach ($email->getItineraries() as $it) {
                if ($type === $it->getType()
                    && (in_array($confNumber, array_column($it->getConfirmationNumbers(), 0))
                        || (empty($confNumber) && count($it->getConfirmationNumbers()) === 0)
                    )
                ) {
                    $t = $it;

                    break;
                }
            }

            // if itinerary is not found, create new itinerary
            if (!isset($t)) {
                if ($type === 'bus') {
                    $t = $email->add()->bus();
                } else {
                    $t = $email->add()->train();
                }
            }

            // save PNR
            if (!empty($confNumber) && !in_array($confNumber, array_column($t->getConfirmationNumbers(), 0))) {
                $t->addConfirmationNumber($confNumber, 'PNR');
            }

            // if no PNR, set noConfirmation
            if (empty($confNumber) && count($t->getConfirmationNumbers()) === 0) {
                $t->setNoConfirmationNumber(true);
            }

            // collect tickets
            $ticketText = $this->re("/{$this->opt($this->t('Ticket Code'))}[\: ]*(.+?)[ ]*$/m", $segmentText);
            $ticket = null;

            if (preg_match_all("/(\d{9,11})(?:[\,\;]|$)/m", $ticketText, $m)) {
                if (count($m[1]) > 1) {
                    foreach ($m[1] as $ticket) {
                        if (!in_array($ticket, array_column($t->getTicketNumbers(), 0))) {
                            $t->addTicketNumber($ticket, false);
                        }
                    }
                } elseif (count($m[1]) === 1) {
                    $ticket = $m[1][0];
                }
            }

            $s = $t->addSegment();

            $this->parseAmountCharge($t, $segmentText);
            $this->parsePassengers($t, $s, $this->findCutSection($segmentText, $this->t('DETTAGLIO PASSEGGERI'), $this->t('Altri dati')), $ticket);
            $this->iterationSegments($s, $segmentText);

            // check that new segment has equal route and dates (compared to previous segments)
            // if route and dates matches - copy seats from new segment and remove new segment
            foreach ($t->getSegments() as $prevSegment) {
                if ($prevSegment->getId() !== $s->getId()
                    && $prevSegment->getDepName() === $s->getDepName() && $prevSegment->getDepDate() === $s->getDepDate()
                    && $prevSegment->getArrName() === $s->getArrName() && $prevSegment->getArrDate() === $s->getArrDate()
                ) {
                    $diffAssignedSeats = $this->array_diff_recursive($s->getAssignedSeats(), $prevSegment->getAssignedSeats());

                    foreach ($diffAssignedSeats as $diffAssignedSeat) {
                        $prevSegment->addSeat($diffAssignedSeat[0], false, false, $diffAssignedSeat[1]);
                    }

                    $t->removeSegment($s);

                    break;
                }
            }
        }
    }

    protected function parsePassengers(Itinerary $train, $segment, $plainText, $ticket)
    {
        $passengerRows = $this->splitter("/(.+{$this->opt($this->t('Offerta - Servizio'))}.*)/", $plainText);
        $travellers = [];

        foreach ($passengerRows as $passengerRow) {
            $tablePos = [0];

            if (preg_match("/^(.+?[ ]{2}){$this->opt($this->t('Offerta - Servizio'))}/m", $passengerRow, $matches)) {
                $tablePos[] = mb_strlen($matches[1]);
            }
            $table = $this->splitCols($passengerRow, $tablePos);

            if (count($table) !== 2) {
                $this->logger->debug('Wrong passengers table!');

                return null;
            }

            // collect traveller
            $traveller = null;

            if (preg_match("/" . str_replace(" ", '(?: | *\n *)', $this->opt($this->t('Nome Passeggero'))) . ".*\n+[ ]*([[:alpha:]][-.'[:alpha:]\s]{5,25}[[:alpha:]])\s*\n/u", $table[0], $m)) {
                $traveller = preg_replace('/\s+/', ' ', $m[1]);
                $travellers[] = $traveller;

                if (!in_array($traveller, array_column($train->getTravellers(), 0))) {
                    $train->addTraveller($traveller);
                }
            }

            $cpCode = null;
            // collect carNumber and seat
            if (preg_match("/[ ]{5,}(?<carNumber>\d+)[ ]+(?<seat>\d{1,2}[A-Z]?)[ ]+(?<cpCode>\d{5,7})/", $passengerRow, $m)) {
                $segment->setCarNumber($m['carNumber']);
                $cpCode = $m['cpCode'];

                if (!empty($traveller)) {
                    $segment->addSeat($m['seat'], false, false, $traveller);
                } else {
                    $segment->addSeat($m['seat']);
                }
            } elseif (preg_match_all("/\b(\d{1,2}[A-Z])\b/", $table[1], $m)) {//(?:[-\s]|$)
                foreach ($m[1] as $seat) {
                    $segment->addSeat($seat);
                }

                $carNumber = $this->re("/^(\d{1,2})$/m", $table[1]);

                if (!empty($carNumber)) {
                    $segment->setCarNumber($carNumber);
                }
            }

            // collect account number
            $accText = $this->splitCols($table[1]);

            if (preg_match('/(?:CartaFreccia|X\-GO)\s+(\d{8,10})[ ]*\n/', implode("\n", $accText), $m)
                && !in_array($m[1], array_column($train->getAccountNumbers(), 0))
                && $m[1] !== $cpCode
            ) {
                if (!empty($traveller)) {
                    $train->addAccountNumber($m[1], false, $traveller, 'CartaFreccia');
                } else {
                    $train->addAccountNumber($m[1], false, null, 'CartaFreccia');
                }
            }

            // collect points (earnedAwards)
            if (preg_match("/{$this->opt($this->t('Points'))}\s+([\d\.\,\']+)[ ]*\n/", implode("\n", $accText), $m)
            ) {
                $train->setEarnedAwards(floatval($train->getEarnedAwards()) + PriceHelper::parse($m[1]) . ' points');
            }
        }

        $travellers = array_unique($travellers);

        if (!empty($ticket) && !in_array($ticket, array_column($train->getTicketNumbers(), 0))) {
            if (count($travellers) === 1) {
                $train->addTicketNumber($ticket, false, $travellers[0]);
            } else {
                $train->addTicketNumber($ticket, false);
            }
        }
    }

    protected function parsePassengers2(Email $email, string $plainText): void
    {
        $patterns = [
            'time'           => '\(\d{2}\:\d{2}\)',
            'travellerTitle' => '(?:Dr|Miss|Mrs|Mr|Ms|Mme|Mr/Mrs|Mrs/Mr|Monsieur)\b\.?',
            'travellerName'  => '[[:alpha:]][-.\'’&[:alpha:] ]*[[:alpha:]]',
        ];

        $segmentTexts = $this->split("/(^.+?{$patterns['time']}[\- ]+.+?{$patterns['time']})/m", $plainText);

        foreach ($email->getItineraries() as $it) {
            foreach ($it->getSegments() as $s) {
                $depTime = date('H:i', $s->getDepDate());
                $arrTime = date('H:i', $s->getArrDate());
                $re = "/{$this->opt($s->getDepName())}[ ]+\({$depTime}\)[\- ]+{$this->opt($s->getArrName())}[ ]+\({$arrTime}\)/";
                $passengersText = null;

                // find matches between segments
                foreach ($segmentTexts as $segmentText) {
                    if (preg_match($re, $segmentText)) {
                        $passengersText = $this->re("/{$this->opt($this->t('Carrozza - Posto'))}[ ]*\n(.+?)$/s", $segmentText);

                        break;
                    }
                }

                if (empty($passengersText)) {
                    continue;
                }

                $passengerRows = $this->split("/(.+?\d+\/\d+\/\d{4})/", $passengersText);

                $carNumbers = [];

                foreach ($passengerRows as $passengerRow) {
                    if (preg_match("#^[ ]*(?:{$patterns['travellerTitle']}[ ]*)?(?<travellerName>{$patterns['travellerName']})[\*\- ]+\d+\/\d+\/\d{4}.+?(?<carNumber>\d+)[\- ]+(?<seat>\d{1,2}[A-Z])\b.+$#m", $passengerRow, $m)) {
                        // if already exist this traveller
                        if (!empty($existTraveller = $this->getExistTraveller($it, $m['travellerName']))) {
                            $m['travellerName'] = $existTraveller;
                        } else {
                            $it->addTraveller($m['travellerName']);
                        }

                        $carNumbers[] = $m['carNumber'];

                        if (in_array($m['seat'], $s->getSeats())) {
                            $s->removeSeat($m['seat']);
                        }
                        $s->addSeat($m['seat'], false, false, $m['travellerName']);
                    }
                }

                $carNumbers = array_unique($carNumbers);

                if (count($carNumbers) == 1) {
                    $s->setCarNumber($carNumbers[0]);
                }
            }
        }
    }

    protected function parseAmountCharge(Itinerary $it, $plainText): void
    {
        if (preg_match("/[\* ]*(?:{$this->opt($this->t('Total amount'))}|{$this->opt('Total amount')}).*?\:?\s*(.+?\d.+)[ ]*\n/iu", $plainText, $m)) {
            $tot = $this->getAmountCurrency(str_replace("$", "USD", str_replace("€", "EUR", $m[1])));

            if ($tot['Amount'] !== '') {
                $it->price()
                    ->total($it->getPrice() && $it->getPrice()->getTotal() ? $it->getPrice()->getTotal() + $tot['Amount'] : $tot['Amount'])
                    ->currency($tot['Currency']);
            }
        }
    }

    protected function array_diff_recursive($array1, $array2)
    {
        $result = [];

        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (isset($array2[$key]) && is_array($array2[$key])) {
                    $recursive_diff = $this->array_diff_recursive($value, $array2[$key]);

                    if (count($recursive_diff) > 0) {
                        $result[$key] = $recursive_diff;
                    }
                } else {
                    $result[$key] = $value;
                }
            } else {
                if (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    protected function getExistTraveller(Itinerary $it, string $name): ?string
    {
        $nameParts = explode(' ', mb_strtolower($name));

        foreach ($it->getTravellers() as $traveller) {
            $travellerParts = explode(' ', mb_strtolower($traveller[0]));

            if (count(array_diff($nameParts, $travellerParts)) === 0) {
                return $traveller[0];
            }
        }

        return null;
    }

    protected function splitter($regular, $text)
    {
        $result = [];

        $array = preg_split($regular, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        array_shift($array);

        for ($i = 0; $i < count($array) - 1; $i += 2) {
            $result[] = $array[$i] . $array[$i + 1];
        }

        return $result;
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

    private function iterationSegments($segment, $value): void
    {
        /** @var \AwardWallet\Schema\Parser\Common\TrainSegment $segment */
        $tablePos = [0];

        if (preg_match("/^(.+?[ ]{2}){$this->opt($this->t('Stazione di arrivo'))}/miu", $value, $matches)) {
            $tablePos[] = mb_strlen($matches[1]);
        }

        if (preg_match("/^(.+?[ ]{2})(?:{$this->opt($this->t('Treno'))}|{$this->opt($this->t('Servizio'))}|{$this->opt($this->t('Carrozza'))})/m", $value, $matches)) {
            $tablePos[] = mb_strlen($matches[1]);
        }

        $table = $this->splitCols($value, $tablePos);

        if (count($table) !== 3) {
            $this->logger->alert('Wrong table in segment!');

            return;
        }

        $trainType = null;

        if (preg_match("/^[ ]*(?:{$this->opt($this->t('Treno'))}|{$this->opt($this->t('Autobus'))})(?:\s*\/\s*[^:]+)?:\s*([\s\S]+?)\n+[ ]*{$this->opt($this->t('Servizio'))}/m", $table[2], $m)) {
            $m[1] = preg_replace('/\s+/', ' ', $m[1]);

            if (preg_match("/^(.{2,}?)\s+([-A-Z\d]+)$/s", $m[1], $m2)) {
                $trainType = $m2[1];
                $segment->extra()->type($m2[1])->number($m2[2]);
            } else {
                $trainType = $m[1];
                $segment->extra()->type($m[1])->noNumber();
            }
        }

        $dateDep = $dateArr = null;

        // DepName
        if (preg_match("/^[ ]*{$this->opt($this->t('Stazione di partenza'))}\n+(?<name>.{3,}?)\n+(?:{$this->opt($this->t('Ore'))}|(?<date>\d{1,2}\/\d{1,2}\/\d{2,4}))/imsu", $table[0], $m)) {
            $m['name'] = preg_replace('/\s+/', ' ', $m['name']);
            It6132072::assignRegion($m['name'], $trainType);
            $segment->departure()
                ->name($m['name'])
                ->geoTip(It6132072::$region)
            ;

            if (!empty($m['date'])) {
                $dateDep = $m['date'];
            }
        }

        // ArrName
        if (preg_match("/^[ ]*{$this->opt($this->t('Stazione di arrivo'))}\n+(?<name>.{3,}?)\n+(?:{$this->opt($this->t('Ore'))}|(?<date>\d{1,2}\/\d{1,2}\/\d{2,4}))/imsu", $table[1], $m)) {
            $m['name'] = preg_replace('/\s+/', ' ', $m['name']);
            It6132072::assignRegion($m['name'], $trainType);
            $segment->arrival()
                ->name($m['name'])
                ->geoTip(It6132072::$region)
            ;

            if (!empty($m['date'])) {
                $dateArr = $m['date'];
            }
        }

        $patterns['timeDate'] = "/{$this->opt($this->t('Ore'))}(?:\s*\/\s*\w+)?\s+(\d{1,2}:\d{2}(?:\s*[AaPp]\.?[Mm]\.?)?)\s+-\s+(.+?)(?:\s{2,}|$)/u";

        // DepDate
        if (preg_match($patterns['timeDate'], $table[0], $m)) {
            $segment->departure()
                ->date(strtotime($this->normalizeDate($m[2]) . ' ' . $m[1]));
        } elseif ($dateDep) {
            $segment->departure()->date(strtotime($this->normalizeDate($dateDep)));
        }

        // ArrDate
        if (preg_match($patterns['timeDate'], $table[1], $m)) {
            $segment->arrival()
                ->date(strtotime($this->normalizeDate($m[2]) . ' ' . $m[1]));
        } elseif (!empty($segment->getDepDate()) && $dateArr && strtotime($this->normalizeDate($dateArr)) === $segment->getDepDate()) {
            $segment->arrival()->noDate();
        } elseif ($dateArr) {
            $segment->arrival()->date(strtotime($this->normalizeDate($dateArr)));
        }

        if (preg_match("/\n[ ]*{$this->opt($this->t('Servizio'))}(?:\s*\/\s*[^:]+)?:\s*([\s\S]+?)\n *(?:{$this->opt($this->t('Carrozza'))}|VIA:|KM:|\n)/", $table[2], $m)) {
            $segment->extra()->cabin(preg_replace('/\s+/', ' ', $m[1]));
        }

        if (preg_match("/\n[ ]*{$this->opt($this->t('Carrozza'))}(?:\s*\/\s*[^:]+)?:\s*(\d+)$/m", $table[2], $m)) {
            $segment->extra()->car($m[1]);
        }

        $seats = array_filter(explode(",", $this->re('#' . $this->opt($this->t('Posti')) . ':\s+(.+?)\n#u', $value)));

        if (count($segment->getSeats()) === 0) {
            $segment->setSeats($seats);
        }
    }

    private function findCutSection($input, $searchStart, $searchFinish)
    {
        $inputResult = null;
        $left = mb_strstr($input, $searchStart);

        if (is_array($searchFinish)) {
            $i = 0;

            while (empty($inputResult)) {
                if (isset($searchFinish[$i])) {
                    $inputResult = mb_strstr($left, $searchFinish[$i], true);
                } else {
                    return false;
                }
                $i++;
            }
        } else {
            $inputResult = mb_strstr($left, $searchFinish, true);
        }

        return mb_substr($inputResult, mb_strlen($searchStart));
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^\s*(\d+)\/(\d+)\/(\d+)\s*$#",
        ];
        $out = [
            "$1.$2.$3",
        ];
        $str = preg_replace($in, $out, $str);

        return $str;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function getAmountCurrency($node): array
    {
        $tot = '';
        $cur = '';

        if (preg_match("#(?<c>[A-Z]{3})\s*(?<t>\d[\.\d\,\s]*\d*)#", $node, $m) || preg_match("#(?<t>\d[\.\d\,\s]*\d*)\s*(?<c>[A-Z]{3})#", $node, $m)) {
            if (strpos($m['t'], ',') !== false && strpos($m['t'], '.') !== false) {
                if (strpos($m['t'], ',') < strpos($m['t'], '.')) {
                    $m['t'] = str_replace(',', '', $m['t']);
                } else {
                    $m['t'] = str_replace('.', '', $m['t']);
                    $m['t'] = str_replace(',', '.', $m['t']);
                }
            }
            $tot = (float) str_replace(",", ".", str_replace(' ', '', $m['t']));
            $cur = $m['c'];
        }

        return ['Amount' => $tot, 'Currency' => $cur];
    }

    private function rowColsPos($row): array
    {
        $head = array_filter(array_map('trim', explode("|", preg_replace("#\s{2,}#", "|", $row))));
        $pos = [];
        $lastpos = 0;

        foreach ($head as $word) {
            $pos[] = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $lastpos = mb_strpos($row, $word, $lastpos, 'UTF-8') + mb_strlen($word, 'UTF-8');
        }

        return $pos;
    }

    private function splitCols($text, $pos = false): array
    {
        $cols = [];
        $rows = explode("\n", $text);

        if (!$pos) {
            $pos = $this->rowColsPos($rows[0]);
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

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) { return preg_quote($s, '/'); }, $field)) . ')';
    }
}
