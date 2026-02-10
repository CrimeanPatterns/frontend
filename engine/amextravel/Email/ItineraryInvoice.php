<?php

namespace AwardWallet\Engine\amextravel\Email;

use AwardWallet\Schema\Parser\Common\Flight;
use AwardWallet\Schema\Parser\Common\Train;
use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Common\Parser\Util\PriceHelper;
use PlancakeEmailParser;

class ItineraryInvoice extends \TAccountChecker
{
    public $mailFiles = "amextravel/it-10008641.eml, amextravel/it-10024772.eml, amextravel/it-15.eml, amextravel/it-1630541.eml, amextravel/it-1630542.eml, amextravel/it-1630834.eml, amextravel/it-1631146.eml, amextravel/it-1998942.eml, amextravel/it-2418068.eml, amextravel/it-2621650.eml, amextravel/it-3879765.eml, amextravel/it-677129167.eml, amextravel/it-69560376.eml, amextravel/it-9863770.eml, amextravel/it-9865511.eml, amextravel/it-9865541.eml, amextravel/it-9914703.eml, amextravel/it-9921062.eml, amextravel/it-914614770.eml";

    public $reFrom = ["mytrips.amexgbt.com", "@welcome.aexp.com"];

    public $reBodyHTML = [
        'es'  => ['Gracias por elegir American Express Global Business Travel', 'Estamos encantados de servirle'],
        'en'  => ['Please check your attached travel itinerary to confirm', 'Your detailed travel itinerary document is attached'],
        'en2' => ['Please find attached your invoice', 'Your detailed travel invoice document is attached'],
        'en3' => ['Please find your invoice attached', 'Your detailed travel invoice document is attached'],
        'en4' => ['American Express Global Business Travel', 'Your American Express Global Business Travel Record Locator is'],
        'en5' => ['Please find the attached Travel documents for your', 'Attachment'],
    ];
    public $reBodyPDF = [
        'es'  => ['Itinerario Clave de reservación', 'American Express Global Business Travel'],
        'fr'  => ["code de réservation d'itinéraire", 'American Express Global Business Travel'],
        'en'  => ['Itinerary Booking Reference', 'American Express Global Business Travel'],
        'en2' => ['Invoice Booking Reference', 'American Express Global Business Travel'],
        'en3' => ['Invoice Booking Reference', 'by American Express Travel'],
        'en4' => ['Travel Arrangements for', 'American Express Global Business Travel'],
    ];

    public $reSubject = [
        '/ITINERARIO de Viaje para\s+.+?\s+Ref\s+[A-Z\d]{5,}/', // es
        '#ITINERARY\s+for\s+.+?\s+Ref\s+[A-Z\d]{5,}#',
        '#INVOICE\s+\d+\s+for\s+.+?\s+Ref\s+[A-Z\d]{5,}#',
    ];

    public static $dict = [
        'es' => [
            'Itinerary Booking Reference' => 'Itinerario Clave de reservación',
            'Travel Arrangements for'     => 'Arreglos de viaje para',
            'Generated'                   => 'Generado',
            'Not Assigned'                => ['No aplica', 'No asignado'],
            'Status'                      => 'Estatus',

            // FLIGHT
            'to'                    => 'a',
            'Airline Booking Ref'   => 'Código de referencia de la aerolínea',
            'Carrier'               => 'Aerolínea',
            'Flight'                => 'Vuelo',
            'Operated by'           => 'Operado por',
            'Origin'                => 'Origen',
            'Departing'             => 'Saliendo',
            'Departure Terminal'    => 'Terminal de Salida',
            'Destination'           => 'Destino',
            'Arriving'              => 'Llegando',
            'Arrival Terminal'      => 'Terminal de Llegada',
            'Class'                 => 'Clase',
            'Aircraft Type'         => 'Tipo de Aeronave',
            'Meal Service'          => 'Servicio de Alimentos',
            'Frequent Flyer Number' => 'Número de Viajero Frecuente',
            'Distance'              => 'Distancia',
            'Seat'                  => 'Asiento',
            'Estimated Time'        => 'Tiempo Estimado',
            'Number of Stops'       => 'Número de Paradas',

            // HOTEL
            'Address'                => 'Dirección',
            'Phone'                  => ['TEL', 'Teléfono'],
            'Fax'                    => 'Fax',
            'Check In Date'          => 'Fecha de Check in',
            'Check Out Date'         => 'Fecha de Check out',
            'Reference Number'       => 'Número de Referencia',
            'Additional Information' => 'Información Adicional',
            //          'Special Information' => '',
            'Number Of Rooms' => 'Número de Habitaciones',
            'Guaranteed'      => 'Garantizado',
            'Rate'            => 'Tarifa',
            'Membership ID'   => 'Número de Membresía',

            // CAR
            //          'Pickup' => '',
            //          'Location' => '',
            //          'Date and Time' => '',
            //          'Drop Off' => '',
            //          'Car Type' => '',
            //          'Approximate Total Rate' => '',

            // TRAIN
            //          'Train' => '',
        ],
        'fr' => [
            "Itinerary Booking Reference" => "code de réservation d'itinéraire",
            "Travel Arrangements for"     => "Réservations de voyage pour",
            "Generated"                   => "Généré",
            //          "Not Assigned" => "",
            "Status" => "Statut",

            // FLIGHT
            //          "to" => "",
            //          "Airline Booking Ref" => "",
            //          "Carrier" => "",
            //          "Flight" => "",
            //          "Operated by" => "",
            //          "Origin" => "",
            //          "Departing" => "",
            //          "Departure Terminal" => "",
            //          "Destination" => "",
            //          "Arriving" => "",
            //          "Arrival Terminal" => "",
            //          "Class" => "",
            //          "Aircraft Type" => "",
            //          "Meal Service" => "",
            //          "Frequent Flyer Number" => "",
            //          "Distance" => "",
            //          "Seat" => "",
            //          "Estimated Time" => "",
            //          "Number of Stops" => "",

            // HOTEL
            "Address"                => "Adresse",
            "Phone"                  => "Téléphone",
            "Fax"                    => "Télécopieur",
            "Check In Date"          => "Date d'arrivée",
            "Check Out Date"         => "Date de départ",
            "Reference Number"       => "Numéro de référence",
            "Additional Information" => "Renseignements supplémentaires",
            "Special Information"    => "Renseignements spéciaux",
            "Number Of Rooms"        => "Nombre de chambres",
            "Guaranteed"             => "Garanti",
            "Rate"                   => "Prix total approximatif",
            "Membership ID"          => "Numéro de fidélité",

            // CAR
            "Pickup"                 => "Prise en charge",
            "Location"               => "Emplacement",
            "Date and Time"          => "Date et heure",
            "Drop Off"               => "Lieu de restitution",
            "Car Type"               => "Type de véhicule",
            "Approximate Total Rate" => "Prix total approximatif",

            // TRAIN
            //          "Train" => "",
        ],
        'en' => [
            'Travel Arrangements for'     => ['Passenger Name(s)', 'Travel Arrangements for'],
            'Itinerary Booking Reference' => ['Itinerary Booking Reference', 'Invoice Booking Reference', 'Booking Reference'],
            'Operated by'                 => ['Operated by', 'Operated By'],
            'Not Assigned'                => ['Not Assigned', 'Not Applicable'],
            'Flight'                      => ['Flight', 'Flugnummer'],
            'viarailVariants'             => ['VIA RAIL', 'VIARAIL'],
            'Number of Stops'             => ['Number of Stops', 'Number Of Stops'],
            'cancelledStatus'             => ['Cancelled', 'Canceled'],
        ],
    ];

    private $lang;
    private $langPdf;
    private $pdf;
    private $pdfNamePattern = '.*pdf';
    private $patterns = [
        'travellerName2' => '[[:upper:]]+(?: [[:upper:]]+)*[ ]*\/[ ]*(?:[[:upper:]]+ )*[[:upper:]]+', // KOH / KIM LENG MR
    ];

    private $airportCodesForRoute = [];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $textPDF = '';
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            if (($text = \PDF::convertToText($parser->getAttachmentBody($pdf))) !== null) {
                $textPDF .= $text;
            }
        }

        $textPDF = preg_replace('/([^:\s])[ ]*[:]+\n+ *([-[:alpha:]]+[ ]+\d{1,2}[ ]+[[:alpha:]]{3,}[ ]+\d{4}\b)/u', '$1: $2', $textPDF);
        $this->assignLang($textPDF, true);
        $this->langPdf = $this->lang;

        $body = $this->http->Response['body'];
        $this->assignLang($body);

        // Travel Agency
        $otaConf = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking Reference:'))}]/following::text()[normalize-space(.)!=''][1]");

        if (empty($otaConf)) {
            $otaConf = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your American Express Global Business Travel Record Locator is'))}]",
                null, true, "/{$this->opt($this->t('Your American Express Global Business Travel Record Locator is'))}\s+([A-Z\d]{5,})\s*$/");
        }

        $type = 'Html';
        $this->parseFlightHtml($email, $textPDF);
        $this->parseHotelHtml($email, $textPDF);
        $this->parseCarHtml($email, $textPDF);
        $this->parseRailHtml($email, $textPDF);

        $travellers = [];
        $travellersText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Traveler:'))}]/following::text()[normalize-space()][1]");

        if ($travellersText) {
            $travellers = array_map(function ($item) {
                return $this->normalizeTraveller($item);
            }, preg_split('/(?:\s*,\s*)+/', $travellersText));
        }

        $resDate = strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Generated'))}[\s:]+(.+)#", $textPDF)));

        if (count($travellers) > 0 || !empty($resDate)) {
            foreach ($email->getItineraries() as $it) {
                if (count($travellers) > 0) {
                    $it->general()->travellers($travellers, true);
                }

                if (!empty($resDate)) {
                    $it->general()
                        ->date($resDate);
                }
            }
        }

        //if only attachment
        if (count($email->getItineraries()) === 0 && !empty($textPDF)) {
            $type = 'Pdf';
            $this->assignLang($textPDF, true);
            $segments = $this->splitSegmentPdf($textPDF);

            $otaConf = $this->re("#{$this->opt($this->t('Itinerary Booking Reference'))}[: ]+([A-Z\d]{5,})(?:[ ]{2}|$)#mu", $textPDF);

            foreach ($segments as $i => $sText) {
                if (!empty($this->re("#({$this->opt($this->t('Flight'))}[ ]*[:]+[ ]*(?:[A-Z][A-Z\d]|[A-Z\d][A-Z])[ ]+\d+)#", $sText))) {
                    $this->logger->debug('type [' . $i . '] = flight');
                    $this->parseFlightPDF($email, $sText, $textPDF);
                } elseif (!empty($this->re("#({$this->opt($this->t('Number Of Rooms'))})#", $sText))) {
                    $this->logger->debug('type [' . $i . '] = hotel');
                    $this->parseHotelPDF($email, $sText);
                } elseif (!empty($this->re("#({$this->opt($this->t('Pickup'))}[\s:]+{$this->opt($this->t('Location'))}[\s:]+)#", $sText))) {
                    $this->logger->debug('type [' . $i . '] = rental');
                    $this->parseCarPDF($email, $sText);
                } elseif (!empty($this->re("#({$this->opt($this->t('Rail Carrier'))})#", $text))) {
                    $this->logger->debug('type [' . $i . '] = train');
                    $this->parseRailPDF($email, $sText, $textPDF);
                }
            }

            $resDate = strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Generated'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $textPDF)));

            $travellers = [];
            $travellersText = $this->re("#{$this->opt($this->t('Travel Arrangements for'))}[:\s]+({$this->patterns['travellerName2']}(?:\n+[ ]*{$this->patterns['travellerName2']})*\b)(?: [[:upper:]][[:lower:]]|[ ]{2}|$)#mu", $textPDF)
                ?? $this->re("#{$this->opt($this->t('Passenger Name(s)'))}\n({$this->patterns['travellerName2']}(?:\n+[ ]*{$this->patterns['travellerName2']})*\b)(?: [[:upper:]][[:lower:]]|[ ]{2}|$)#mu", $textPDF);

            if ($travellersText) {
                $travellers = array_map(function ($item) {
                    return $this->normalizeTraveller($item);
                }, preg_split('/(?:[ ]*\n+[ ]*)+/', $travellersText));
            }

            if (count($travellers) > 0 || !empty($resDate)) {
                foreach ($email->getItineraries() as $it) {
                    if (count($travellers) > 0) {
                        $it->general()->travellers($travellers, true);
                    }

                    if (!empty($resDate)) {
                        $it->general()
                            ->date($resDate);
                    }
                }
            }

            $countTypes = [];

            foreach ($email->getItineraries() as $it) {
                $countTypes[] = $it->getType();
            }

            if (count($email->getItineraries()) === 1 || count(array_unique($countTypes)) === 1) {
                $cost = $this->re("#{$this->opt($this->t('Ticket Base Fare'))}\s+(\-?[\d\.]+)#", $textPDF);

                if (!empty($cost)) {
                    $email->price()
                        ->cost($cost);
                }

                $total = $this->re("#{$this->opt($this->t('Total'))}\s+(\-?[\d\.]+)#", $textPDF);
                $currency = $this->re("#{$this->opt($this->t('Total'))}\s+\(([A-Z]{3})\)\s+{$this->opt($this->t('Ticket Amount'))}#", $textPDF);

                if (empty($total)) {
                    $total = $this->re("#{$this->opt($this->t('Total'))}\s+\([A-Z]{3}\)\s+{$this->opt($this->t('Ticket Amount'))}\s+(\-?[\d\.]+)#s", $textPDF);
                }

                if (!empty($total) && !empty($currency)) {
                    $email->price()
                        ->total($total)
                        ->currency($currency);

                    if (!empty($str = $this->re("#({$this->opt($this->t('Online Ticket Fee'))})\s+\-?[\d\.]+#s", $textPDF))) {
                        $email->price()
                            ->fee($str,
                                $this->re("#{$this->opt($this->t('Online Ticket Fee'))}\s+(\-?[\d\.]+)#s", $textPDF));
                    }
                }
            }
        }

        if (count($this->airportCodesForRoute) > 1
            && preg_match("#^[ ]*Routing[ ]*[:]+[ ]*{$this->opt(implode('/', $this->airportCodesForRoute))}(?:[ ]{2}.+)?\n+[ ]*Total Fare[ ]*[:]+[ ]*([^:\s].*?)(?:[ ]{2}|$)#m", $textPDF, $m)
            && preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $m[1], $matches) // USD 2328.51
        ) {
            // it-914614770.eml

            foreach ($email->getItineraries() as $it) {
                /** @var \AwardWallet\Schema\Parser\Common\Flight $it */
                if ($it->getType() === 'flight') {
                    $f = $it;
    
                    break;
                }
            }

            if (isset($f)) {
                $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
                $f->price()->currency($matches['currency'])->total(PriceHelper::parse($matches['amount'], $currencyCode));
            }
        }

        $email->ota()
            ->confirmation($otaConf);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . $type . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//a[contains(@href,'amexgbt.com') or contains(@href,'axexplore.com')] | //img[contains(@src,'amexgbt.com') or contains(@src,'americanexpress.com') or alt='GBT Logo']")->length > 0) {
            $body = $this->http->Response['body'];

            return $this->assignLang($body);
        }
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        if (isset($pdfs[0])) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdfs[0]));

            return $this->assignLang($textPdf, true);
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        $prov = false;

        if (isset($headers['from'])) {
            if ($this->detectEmailFromProvider($headers['from']) === true) {
                $prov = true;
            }
        }

        if ($prov && isset($this->reSubject)) {
            foreach ($this->reSubject as $reSubject) {
                if (preg_match($reSubject, $headers['subject'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        if (strpos($from, 'American Express Global Business Travel') !== false) {
            return true;
        }

        foreach ($this->reFrom as $reFrom) {
            if (stripos($from, $reFrom) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dict);
    }

    public static function getEmailTypesCount()
    {
        $types = 9;
        $cnt = count(self::$dict) * $types;

        return $cnt;
    }

    public function dateStringToEnglish($date)
    {
        if (preg_match('#[[:alpha:]]+#iu', $date, $m)) {
            $monthNameOriginal = $m[0];

            if ($translatedMonthName = \AwardWallet\Engine\MonthTranslate::translate($monthNameOriginal, $this->lang)) {
                return preg_replace("#$monthNameOriginal#i", $translatedMonthName, $date);
            }
        }

        return $date;
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

    protected function mergeItineraries($its)
    {
        $its2 = $its;

        foreach ($its2 as $i => $it) {
            if ($i && ($j = $this->findRL($i, $it['RecordLocator'], $its)) != -1) {
                foreach ($its[$j]['TripSegments'] as $flJ => $tsJ) {
                    foreach ($its[$i]['TripSegments'] as $flI => $tsI) {
                        if (isset($tsI['FlightNumber']) && isset($tsJ['FlightNumber']) && ((int) $tsJ['FlightNumber'] === (int) $tsI['FlightNumber'])
                            && (isset($tsJ['Seats']) || isset($tsI['Seats']))
                        ) {
                            $new = "";

                            if (isset($tsJ['Seats'])) {
                                $new .= "," . $tsJ['Seats'];
                            }

                            if (isset($tsI['Seats'])) {
                                $new .= "," . $tsI['Seats'];
                            }
                            $new = implode(",", array_filter(array_unique(array_map("trim", explode(",", $new)))));
                            $its[$j]['TripSegments'][$flJ]['Seats'] = $new;
                            $its[$i]['TripSegments'][$flI]['Seats'] = $new;
                        }
                    }
                }

                $its[$j]['TripSegments'] = array_merge($its[$j]['TripSegments'], $its[$i]['TripSegments']);
                $its[$j]['TripSegments'] = array_map('unserialize', array_unique(array_map('serialize', $its[$j]['TripSegments'])));

                if (isset($its[$j]['Passengers']) || isset($its[$i]['Passengers'])) {
                    $new = "";

                    if (isset($its[$j]['Passengers'])) {
                        $new .= "," . implode(",", $its[$j]['Passengers']);
                    }

                    if (isset($its[$i]['Passengers'])) {
                        $new .= "," . implode(",", $its[$i]['Passengers']);
                    }
                    $new = array_values(array_filter(array_unique(array_map("trim", explode(",", $new)))));
                    $its[$j]['Passengers'] = $new;
                }

                if (isset($its[$j]['AccountNumbers']) || isset($its[$i]['AccountNumbers'])) {
                    $new = "";

                    if (isset($its[$j]['AccountNumbers'])) {
                        $new .= "," . implode(",", $its[$j]['AccountNumbers']);
                    }

                    if (isset($its[$i]['AccountNumbers'])) {
                        $new .= "," . implode(",", $its[$i]['AccountNumbers']);
                    }
                    $new = array_values(array_filter(array_unique(array_map("trim", explode(",", $new)))));
                    $its[$j]['AccountNumbers'] = $new;
                }

                if (isset($its[$j]['TicketNumbers']) || isset($its[$i]['TicketNumbers'])) {
                    $new = "";

                    if (isset($its[$j]['TicketNumbers'])) {
                        $new .= "," . implode(",", $its[$j]['TicketNumbers']);
                    }

                    if (isset($its[$i]['TicketNumbers'])) {
                        $new .= "," . implode(",", $its[$i]['TicketNumbers']);
                    }
                    $new = array_values(array_filter(array_unique(array_map("trim", explode(",", $new)))));
                    $its[$j]['TicketNumbers'] = $new;
                }

                unset($its[$i]);
            }
        }

        return $its;
    }

    protected function findRL($g_i, $rl, $its)
    {
        foreach ($its as $i => $it) {
            if ($g_i != $i && $it['RecordLocator'] === $rl) {
                return $i;
            }
        }

        return -1;
    }

    private function splitSegmentPdf($textPDF): array
    {
        $segments = [];
        $detectFlight = ".+\([A-Z]{3}\) {$this->opt($this->t('to'))} .+ \([A-Z]{3}\)";
        $detectCar = "[ ]*\d{1,2}:\d{2} (?:.*\n){1,3}\s*{$this->opt($this->t('Pickup'))}:\s*\n";
        $detectHotel = ".+\n\s*{$this->opt($this->t('Address'))}:";
        $detectRail = "[ ]*\d{1,2}:\d{2}(?:[ ]*[AaPp]\.?[Mm]\.?)?\s+(?:.*\n){1,3}^[ ]*{$this->opt($this->t('Train'))}:";
        $nodes = $this->splitter('/^( *[-[:alpha:]]+[ ]+\d{1,2}[ ]+[[:alpha:]]{3,}[ ]+\d{4}\b).*/mu', $textPDF);

        foreach ($nodes as $value) {
            $segments = array_merge($segments, $this->splitter("#^(" . $detectFlight . "|" . $detectCar . "|" . $detectHotel . "|" . $detectRail . ")#m", $value));
        }

        return $segments;
    }

    private function parseFlightPDF(Email $email, $text, $textPDF): void
    {
        foreach ($email->getItineraries() as $it) {
            /** @var \AwardWallet\Schema\Parser\Common\Flight $it */
            if ($it->getType() === 'flight') {
                $f = $it;

                break;
            }
        }

        if (!isset($f)) {
            $f = $email->add()->flight();

            $f->general()
                ->noConfirmation();
        }

        $s = $f->addSegment();

        // Airline
        if (preg_match('/' . $this->opt($this->t('Flight')) . '[ ]*[:]+[ ]*(?<airline>[A-Z][A-Z\d]|[A-Z\d][A-Z])?[ ]*(?<flightNumber>\d+)/', $text, $matches)) {
            if (!empty($matches['airline'])) {
                $s->airline()
                    ->name($matches['airline']);
            } else {
                $s->airline()
                    ->noName();
            }
            $s->airline()
                ->number($matches['flightNumber']);
        }
        $conf = $this->re("#{$this->opt($this->t('Airline Booking Ref'))}[ ]*[:]+[ ]*([A-Z\d]{5,})(?:[ ]{2}|$)#m", $text);

        if (!empty($conf) && !preg_match("#{$this->opt($this->t('Airline Booking Ref'))}[ ]*[:]+[ ]*{$this->opt($this->t('Not Assigned'))}#iu", $text)) {
            $s->airline()
                ->confirmation($conf);
        }

        // /endeavor Air Dba Delta Connection
        $operator = $this->re("#{$this->opt($this->t('Operated by'))}[ ]*[:]+[ ]*([^:\s].*?)(?:[ ]{2}|$)#m", $text);

        if ($operator && strpos($operator, '/') === 0) {
            $s->airline()
                ->wetlease();
        }

        if ($operator) {
            $operator = preg_replace("/^\s*(\S.*?)\s+DBA\b.*$/i", '$1', trim($operator, '/ '));
        }

        $s->airline()->operator($operator, false, true);

        $airportCodes = [];

        // Departure
        $node = $this->re("#{$this->opt($this->t('Origin'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $text);

        if (preg_match('/(.+)\s+\(\s*([A-Z]{3})\s*\)/', $node, $m)) {
            $s->departure()
                ->name($m[1])
                ->code($m[2]);
            $airportCodes[] = $m[2];
        } else {
            $s->departure()
                ->name($node)
                ->noCode();
        }
        $s->departure()
            ->strict()
            ->date(strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Departing'))}[\s:]+(.+?)\s*(?:{$this->opt($this->t('Departure Terminal'))}|$)#m", $text))));
        $node = $this->re("#{$this->opt($this->t('Departure Terminal'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $text);

        if (preg_match("/TERMINAL[ ]+(\w[\w ]*)/i", $node, $m)) {
            $s->departure()
                ->terminal($m[1]);
        } elseif (!empty($node) && !preg_match("/{$this->opt($this->t('Not Assigned'))}/i", $node)) {
            $s->departure()
                ->terminal($node);
        }

        // Arrival
        $node = $this->re("#{$this->opt($this->t('Destination'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $text);

        if (preg_match('/(.+)\s+\(\s*([A-Z]{3})\s*\)/', $node, $m)) {
            $s->arrival()
                ->name($m[1])
                ->code($m[2]);
            $airportCodes[] = $m[2];
        } else {
            $s->arrival()
                ->name($node)
                ->noCode();
        }

        $s->arrival()
            ->date(strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Arriving'))}[\s:]+(.+?)\s*(?:{$this->opt($this->t('Arrival Terminal'))}|$)#m", $text))));
        $node = $this->re("#{$this->opt($this->t('Arrival Terminal'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $text);

        if (preg_match("/TERMINAL[ ]+(\w[\w ]*)/i", $node, $m)) {
            $s->arrival()
                ->terminal($m[1]);
        } elseif (!empty($node) && !preg_match("/{$this->opt($this->t('Not Assigned'))}/i", $node)) {
            $s->arrival()
                ->terminal($node);
        }

        if (count($airportCodes) === 2) {
            if (count($this->airportCodesForRoute) === 0) {
                $this->airportCodesForRoute[] = $airportCodes[0];
            }
            $this->airportCodesForRoute[] = $airportCodes[1];
        }

        if (preg_match("#{$this->opt($this->t('Class'))}[ ]*[:]+[ ]*(?i)(Economy|Business|First Class|Premium Select)(?-i)(?: {$this->opt($this->t('Distance'))}|[ ]{2}|$)#m", $text, $m)) {
            $s->extra()
                ->cabin($m[1]);
        }

        if (preg_match("#{$this->opt($this->t('Estimated Time'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $text, $m)) {
            $s->extra()
                ->duration($m[1]);
        }

        if (preg_match("#{$this->opt($this->t('Distance'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $text, $m)) {
            $s->extra()
                ->miles($m[1]);
        }

        if (preg_match("#{$this->opt($this->t('Aircraft Type'))}[ ]*[:]+[ ]*(.+?)(?: {$this->opt($this->t('Seat'))}|[ ]{2}|$)#m", $text, $m)) {
            $s->extra()
                ->aircraft($m[1]);
        }

        // Status
        $status = $this->re("#{$this->opt($this->t('Status'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $text);
        $s->extra()
            ->status($status, true, true);

        if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $status)) {
            $s->extra()
                ->cancelled();
        }

        // Meal
        if (preg_match("#{$this->opt($this->t('Meal Service'))}[ ]*[:]+[ ]*(.+?)(?:[ ]{2}|$)#m", $text, $m)
            && !preg_match("/{$this->opt($this->t('Not Assigned'))}/i", $m[1])
        ) {
            $s->extra()
                ->meal($m[1]);
        }
        // Stops
        if (preg_match("/{$this->opt($this->t('Number of Stops'))}[ ]*[:]+[ ]*(\d{1,3})(?:[ ]{2}|$)/m", $text, $m) !== null) {
            $s->extra()
                ->stops($m[1]);
        }
        // Seats
        if (preg_match("#{$this->opt($this->t('Seat'))}[ ]*[:]+[ ]*(\d[,; A-Z\d]*?[A-Z])(?:[ ]{2}|$)#m", $text, $m)) {
            $seats = preg_split('/\s*[,;]+\s*/', $m[1]);
            $seats = array_filter($seats, function ($v) {return (preg_match('/^\s*\d{1,3}[A-Z]\s*$/', $v)) ? true : false; });

            if (!empty($seats)) {
                $s->extra()
                    ->seats($seats);
            }
        }

        // AccountNumbers
        if (preg_match("#{$this->opt($this->t('Frequent Flyer Number'))}[: ]+([^:\s].*?)(?:[ ]{2}|$)#m", $text, $m)
            && !preg_match("/{$this->opt($this->t('Not Assigned'))}/i", $m[1])
        ) {
            $accountValues = array_filter(preg_split('/(?:\s*[,;]+\s*)+/', $m[1]));

            foreach ($accountValues as $accountVal) {
                if (!in_array($accountVal, array_column($f->getAccountNumbers(), 0))) {
                    $f->program()->account($accountVal, false);
                }
            }
        }

        if (!empty($s->getFlightNumber())) {
            // BookingClass
            if (preg_match("#^ *{$s->getFlightNumber()}\s+([A-Z]{1,2})\s+{$this->opt($this->t('Class'))}#m", $textPDF, $m)) {
                $s->extra()
                    ->bookingCode($m[1]);
            }
            // TicketNumbers
            if (preg_match("#{$this->opt($this->t('Ticket Number'))}[ ]+(\d{3}(?: | ?- ?)?\d{5,}(?: | ?- ?)?\d{1,2})(?:.+\n+){3,9}[ 0]*{$s->getFlightNumber()}[ ]+[A-Z]{1,2}[ ]+{$this->opt($this->t('Class'))}#", $textPDF, $m)
                && !in_array($m[1], array_column($f->getTicketNumbers(), 0))
            ) {
                $f->issued()
                    ->ticket($m[1], false);
            }
        }

        $this->addFlightSumsFromPDF($f, $textPDF);
    }

    private function parseFlightHtml(Email $email, $textPDF): void
    {
        $xpath = "//text()[{$this->eq($this->t('Flight Information'))}]/ancestor::table[1]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length === 0) {
            $this->logger->info("Flight segments not found by xpath: {$xpath}");

            return;
        }

        $f = $email->add()->flight();

        $f->general()
            ->noConfirmation();

        $tickets = str_replace(" ", "",
            $this->http->FindNodes("//text()[{$this->starts($this->t('Ticket Number'))}]/following::text()[normalize-space(.)!=''][2]", null, "#^\s*(?::\s*)?([\d \-]{10,})\s*$#"));

        if (!empty($tickets)) {
            $f->issued()
                ->tickets($tickets, false);
        }

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            // Airline
            $node = $this->http->FindSingleNode("descendant::text()[({$this->starts($this->t('Flight'))}) and not({$this->contains($this->t('Flight Information'))})]/following::text()[string-length(normalize-space(.)) > 2][1]", $root);

            if (preg_match('/^[:\s]*([A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(\d+)\s*$/', $node, $m)) {
                $s->airline()
                    ->name($m[1])
                    ->number($m[2]);
            }

            // Operated by: /endeavor Air Dba Delta Connection
            $operator = $this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('Operated by'))}]/following::text()[normalize-space()][1]", $root, false, "/^[:\s]*(.*)$/");

            if ($operator && strpos($operator, '/') === 0) {
                $s->airline()
                    ->wetlease();
            }

            if ($operator) {
                $operator = preg_replace("/^\s*(\S.*?)\s+DBA\b.*$/i", '$1', trim($operator, '/ '));
            }

            $s->airline()->operator($operator, false, true);

            $conf = $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Airline Booking Ref'))}]/following::text()[normalize-space(.)!=''][1]", $root, true, '/^[:\s]*([A-Z\d]{5,})$/');

            if (!empty($conf)) {
                $s->airline()
                    ->confirmation($conf);
            }

            $airportCodes = [];

            // Departure
            $node = trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Origin'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": ");

            if (preg_match('/(.+)\s+\(\s*([A-Z]{3})\s*\)/', $node, $m)) {
                $s->departure()
                    ->name($m[1])
                    ->code($m[2]);
                $airportCodes[] = $m[2];
            } else {
                $s->departure()
                    ->name($node)
                    ->noCode();
            }
            $s->departure()
                ->date(strtotime($this->normalizeDate(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Departing'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "))));

            $node = trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Departure Terminal'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": ");

            if (preg_match("/TERMINAL[ ]+(\w[\w ]*)/i", $node, $m)) {
                $s->departure()
                    ->terminal($m[1]);
            } elseif (!empty($node) && !preg_match("/{$this->opt($this->t('Not Assigned'))}/i", $node)) {
                $s->departure()
                    ->terminal($node);
            }
            // Arrival
            $node = trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Destination'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": ");

            if (preg_match('/(.+)\s+\(\s*([A-Z]{3})\s*\)/', $node, $m)) {
                $s->arrival()
                    ->name($m[1])
                    ->code($m[2]);
                $airportCodes[] = $m[2];
            } else {
                $s->arrival()
                    ->name($node)
                    ->noCode();
            }
            $s->arrival()
                ->date(strtotime($this->normalizeDate(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Arriving'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "))));

            $node = trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Arrival Terminal'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": ");

            if (preg_match("/TERMINAL[ ]+(\w[\w ]*)/i", $node, $m)) {
                $s->arrival()
                    ->terminal($m[1]);
            } elseif (!empty($node) && !preg_match("/{$this->opt($this->t('Not Assigned'))}/i", $node)) {
                $s->arrival()
                    ->terminal($node);
            }

            if (count($airportCodes) === 2) {
                if (count($this->airportCodesForRoute) === 0) {
                    $this->airportCodesForRoute[] = $airportCodes[0];
                }
                $this->airportCodesForRoute[] = $airportCodes[1];
            }

            // Extra
            $s->extra()
                ->duration(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Estimated Time'))}]/following::text()[normalize-space(.)!=''][1]",
                    $root, true, "/^\s*(.+?)(?:non\W?stop)?\s*$/iu"), ": "))
                ->cabin(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Class'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "))
                ->stops(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Number of Stops'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "))
            ;
            $status = $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Status'))}]/following::text()[normalize-space(.)!=''][1]", $root);

            if (empty($status)) {
                $status = $this->http->FindSingleNode("./following::table[1][not({$this->contains($this->t('Information'))})]/descendant::text()[{$this->starts($this->t('Status'))}]/following::text()[normalize-space(.)!=''][1]", $root);
            }

            if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $status)) {
                $s->extra()
                    ->cancelled();
            }
            $s->extra()
                ->status($status);

            if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $status)) {
                $s->extra()
                    ->cancelled();
            }

            $seats = trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Seat'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": ");

            if (empty($seats)) {
                $seats = trim($this->http->FindSingleNode("./following::table[1][not({$this->contains($this->t('Information'))})]/descendant::text()[{$this->starts($this->t('Seat'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": ");
            }

            if (preg_match("/^\s*(\d{1,3}[A-Z])\s*$/", $seats)) {
                $s->extra()
                    ->seat($seats);
            }

            //try add info from pdf
            if (!empty($textPDF)) {
                $memLang = $this->lang;
                $this->lang = $this->langPdf;

                if (!empty($s->getAirlineName()) && !empty($s->getFlightNumber())) {
                    // BookingClass
                    if (preg_match("#^ *{$s->getFlightNumber()}\s+([A-Z]{1,2})\s+{$this->opt($this->t('Class'))}#m", $textPDF, $m)) {
                        $s->extra()
                            ->bookingCode($m[1]);
                    }
                    // TicketNumbers
                    if (preg_match("#{$this->opt($this->t('Ticket Number'))}[ ]+(\d{3}(?: | ?- ?)?\d{5,}(?: | ?- ?)?\d{1,2})(?:.+\n+){3,9}[ 0]*{$s->getFlightNumber()}[ ]+[A-Z]{1,2}[ ]+{$this->opt($this->t('Class'))}#", $textPDF, $m)
                        && !in_array($m[1], array_column($f->getTicketNumbers(), 0))
                    ) {
                        $f->issued()
                            ->ticket($m[1], false);
                    }
                    $text = $this->re("#({$this->opt($this->t('Flight'))}[ :]+{$s->getAirlineName()}\s+{$s->getFlightNumber()}(?:.+\n+)+? *{$this->opt($this->t('Number of Stops'))}.+)#", $textPDF);
                    // Cabin
                    if (empty($s->getCabin()) && preg_match("#{$this->opt($this->t('Class'))}[\s:]+(Economy|Business|First Class|Premium Select)(?: {$this->opt($this->t('Distance'))}|[ ]{2}|$)#m", $text, $m)) {
                        $s->extra()
                            ->cabin($m[1]);
                    }
                    // TraveledMiles
                    if (preg_match("#{$this->opt($this->t('Distance'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text, $m)) {
                        $s->extra()
                            ->miles($m[1]);
                    }
                    // Aircraft
                    if (preg_match("#{$this->opt($this->t('Aircraft Type'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text, $m)) {
                        $s->extra()
                            ->aircraft($m[1]);
                    }
                    // Meal
                    if (preg_match("#{$this->opt($this->t('Meal Service'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text, $m)
                        && !preg_match("/{$this->opt($this->t('Not Assigned'))}/i", $m[1])
                    ) {
                        $s->extra()
                            ->meal($m[1]);
                    }
                    // AccountNumbers
                    if (preg_match("#{$this->opt($this->t('Frequent Flyer Number'))}[: ]+([^:\s].*?)(?:[ ]{2}|$)#m", $text, $m)
                        && !preg_match("/{$this->opt($this->t('Not Assigned'))}/i", $m[1])
                    ) {
                        $accountValues = array_filter(preg_split('/(?:\s*[,;]+\s*)+/', $m[1]));

                        foreach ($accountValues as $accountVal) {
                            if (!in_array($accountVal, array_column($f->getAccountNumbers(), 0))) {
                                $f->program()->account($accountVal, false);
                            }
                        }
                    }
                    // Seats
                    if (preg_match("#{$this->opt($this->t('Seat'))}[ ]*[:]+[ ]*(\d[,; A-Z\d]*?[A-Z])(?:[ ]{2}|$)#m", $text, $m)) {
                        $s->extra()
                            ->seats(preg_split('/\s*[,;]+\s*/', $m[1]));
                    }
                }
                $this->lang = $memLang;
            }
        }

        if (!empty($textPDF) && !empty($f->getTicketNumbers())) {
            $this->addFlightSumsFromPDF($f, $textPDF);
        }

        return;
    }

    private function addFlightSumsFromPDF(Flight $f, $textPDF): void
    {
        $tickets = array_unique(array_column($f->getTicketNumbers(), 0));
        $base = 0.0;
        $tax = 0.0;
        $total = 0.0;
        $currency = null;

        foreach ($tickets as $ticket) {
            $ticketCharges = $this->re("#{$this->opt($this->t('Ticket Number'))}\s+{$ticket}[ ]+((?:.+\n+){1,8}.+{$this->opt($this->t('Total'))}.+)#", $textPDF);
            $base += $this->re("#{$this->opt($this->t('Ticket Base Fare'))} +(\-?[\d\.]+)$#m", $ticketCharges);
            $tax += $this->re("#{$this->opt($this->t('Ticket Tax Fare'))} +(\-?[\d\.]+)$#m", $ticketCharges);
            $currency = $this->re("#{$this->opt($this->t('Total'))} +\(([A-Z]{3})\)\s+{$this->opt($this->t('Ticket Amount'))}#", $ticketCharges);
            $totalTemp = $this->re("#{$this->opt($this->t('Total'))} +(\-?[\d\.]+)$#m", $ticketCharges);

            if (empty($totalTemp)) {
                $totalTemp = $this->re("#{$this->opt($this->t('Total'))}\s+\([A-Z]{3}\)\s+{$this->opt($this->t('Ticket Amount'))}\s+(\-?[\d\.]+)#s", $ticketCharges);
            }
            $total += $totalTemp;

            if (preg_match("#({$this->opt($this->t('Online Ticket Fee'))})\s+(\-?[\d\.]+)\n#s", $ticketCharges, $m)) {
                $f->price()
                    ->fee($m[1], $m[2]);
            }
        }

        if (!empty($base) || !empty($tax) || !empty($total) || !empty($currency)) {
            $f->price()
                ->cost($base)
                ->tax($tax)
                ->total($total)
                ->currency($currency);
        }
    }

    private function parseHotelPDF(Email $email, $text): void
    {
        $h = $email->add()->hotel();

        $conf = null;

        if (preg_match("#{$this->opt($this->t('Guaranteed'))}[^\n]+\n([^\n]*?)\s*{$this->opt($this->t('Reference Number'))}[\s:]+([^\n]*?)\n *{$this->opt($this->t('Status'))}[^\n]+\n *{$this->opt($this->t('Number Of Rooms'))}[^\n]+\n([^\n]*?)\s*{$this->opt($this->t('Additional Information'))}#", $text, $m)) {
            $str = '';

            for ($i = 1; $i <= 3; $i++) {
                if (!empty($m[$i])) {
                    $str .= trim($m[$i]);
                }
            }
            $conf = $str;
        }

        if (empty($conf)) {
            $conf = $this->re("#{$this->opt($this->t('Reference Number'))}[\s:]+([A-Z\d]{5,}\b)#", $text);
        }
        $h->general()
            ->confirmation($conf)
            ->status($this->re("#{$this->opt($this->t('Status'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text), true, true)
        ;

        if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $h->getStatus())) {
            $h->general()
                ->cancelled();
        }

        if (preg_match("#\n *(CANCEL.+)#", $text, $m)) {
            $h->general()
                ->cancellation($m[1]);
        }

        // Hotel
        $h->hotel()
            ->name(trim($this->re("#(.+)\n+ *{$this->opt($this->t('Address'))}[\s:]+#", $text)))
            ->address($this->re("#{$this->opt($this->t('Address'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))
            ->phone($this->re("#{$this->opt($this->t('Phone'))}[\s:]+([+(\d][-. \d)(]{5,}[\d)])(?:[ ]{2}|$)#m", $text), true, true)
            ->fax($this->re("#{$this->opt($this->t('Fax'))}[\s:]+([+(\d][-. \d)(]{5,}[\d)])(?:[ ]{2}|$)#m", $text), true, true)
        ;

        // Booked
        $h->booked()
            ->checkIn(strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Check In Date'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))))
            ->checkOut(strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Check Out Date'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))))
            ->rooms($this->re("#{$this->opt($this->t('Number Of Rooms'))}[ :]+(\d+)#", $text))
        ;
        // Rooms
        $h->addRoom()
            ->setRate(preg_replace('/\s+/', ' ',
                $this->re("#{$this->opt($this->t('Rate'))}[ ]*[:]+[ ]*(.+?)(?:{$this->opt($this->t('per night'))}|[ ]{2}|$)#m", $text)));

        // Program
        if (preg_match("#{$this->opt($this->t('Membership ID'))}[\s:]+([A-Z\d][A-Z\d \-]{2,}?)(?:[ ]{2}|$)#m", $text, $m)) {
            $h->program()
                ->account($m[1], false);
        }

        // Price
        $currency = $this->re("#{$this->opt($this->t('Approximate Total Cost:'))} +([A-Z]{3}) +\-?[\d\.]+$#m", $text);
        $total = $this->re("#{$this->opt($this->t('Approximate Total Cost:'))} +[A-Z]{3} +(\-?[\d\.]+)$#m", $text);

        if (preg_match("/{$this->opt($this->t('Approximate Total Cost:'))}/", $text)) {
            $h->price()
                ->total($total)
                ->currency($currency);
        }
    }

    private function parseHotelHtml(Email $email, $textPDF): void
    {
        $xpath = "//text()[{$this->eq($this->t('Hotel Information'))}]/ancestor::table[1]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length === 0) {
            $this->logger->info("Hotels not found by xpath: {$xpath}");

            return;
        }

        foreach ($nodes as $root) {
            $h = $email->add()->hotel();

            // General
            $conf = $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Reference Number'))}]/following::text()[normalize-space(.)!=''][1]", $root, true, "#([A-Z\d\-]{5,})#");

            if (!empty($conf) && !empty($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Reference Number'))}]/following::text()[normalize-space(.)!=''][1]", $root, true, "#({$this->opt($this->t('Not Assigned'))})#"))) {
                $h->general()
                    ->noConfirmation();
            } else {
                $h->general()
                    ->confirmation($conf);
            }
            $h->general()
                ->status(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Status'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "));

            if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $h->getStatus())) {
                $h->general()
                    ->cancelled();
            }

            // Hotel
            $h->hotel()
                ->name(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Hotel Name'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "))
                ->address(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Address'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "))
                ->phone($this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('Phone'))}]/following::text()[normalize-space()][1]", $root, true, '/^[: ]*([+(\d][-. \d)(]{5,}[\d)])[: ]*$/'),
                    true, true)
                ->fax($this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('Fax'))}]/following::text()[normalize-space()][1]", $root, true, '/^[: ]*([+(\d][-. \d)(]{5,}[\d)])[: ]*$/'),
                    true, true)
            ;

            // Booked
            $h->booked()
                ->checkIn(strtotime($this->normalizeDate(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Check In Date'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "))))
                ->checkOut(strtotime($this->normalizeDate(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Check Out Date'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "))))
            ;
            // Rooms
            $h->addRoom()
                ->setRate(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Rate'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "));

            //try add info from pdf
            if (!empty($textPDF)) {
                $memLang = $this->lang;
                $this->lang = $this->langPdf;

                if (!empty($h->getHotelName())) {
                    $text = $this->re("#{$this->opt($h->getHotelName())}\s+{$this->opt($this->t('Address'))}[\s:]+((?:.+\n+){15})#",
                        $textPDF);

                    // AccountNumbers
                    if (preg_match("#{$this->opt($this->t('Membership ID'))}[\s:]+([A-Z\d][A-Z\d \-]{2,}?)(?:[ ]{2}|$)#m", $text, $m)) {
                        $h->program()
                            ->account($m[1], false);
                    }

                    // CancellationPolicy
                    if (preg_match("#\n *(CANCEL.+)#", $text, $m)) {
                        $h->general()
                            ->cancellation($m[1]);
                    }

                    $currency = $this->re("#{$this->opt($this->t('Approximate Total Cost:'))} +([A-Z]{3}) +\-?[\d\.]+$#m", $text);
                    $total = $this->re("#{$this->opt($this->t('Approximate Total Cost:'))} +[A-Z]{3} +(\-?[\d\.]+)$#m", $text);

                    if (preg_match("/{$this->opt($this->t('Approximate Total Cost:'))}/", $text)) {
                        $h->price()
                            ->total($total)
                            ->currency($currency);
                    }
                }

                $this->lang = $memLang;
            }
        }
    }

    private function parseCarPDF(Email $email, $text): void
    {
        $r = $email->add()->rental();

        $r->general()
            ->confirmation($this->re("#{$this->opt($this->t('Reference Number'))}[\s:]+([A-Z\d]{5,}\b)#", $text))
            ->status($this->re("#{$this->opt($this->t('Status'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))
        ;

        if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $r->getStatus())) {
            $r->general()
                ->cancelled();
        }

        // Pickup
        $r->pickup()
            ->location($this->re("#{$this->opt($this->t('Pickup'))}[\s:]+{$this->opt($this->t('Location'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))
            ->date(strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Pickup'))}[\s:]+.+?\s+{$this->opt($this->t('Date and Time'))}[\s:]+([^\n]+)#s", $text))))
            ->phone($this->re("#{$this->opt($this->t('Pickup'))}[\s:]+.+?\s+{$this->opt($this->t('Phone'))}[\s:]+([+(\d][-. \d)(]{5,}[\d)])$#ms", $text), true, true)
        ;
        // Dropoff
        $r->dropoff()
            ->location($this->re("#{$this->opt($this->t('Drop Off'))}[\s:]+{$this->opt($this->t('Location'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))
            ->date(strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Drop Off'))}[\s:]+.+?\s+{$this->opt($this->t('Date and Time'))}[\s:]+([^\n]+)#s", $text))))
            ->phone($this->re("#{$this->opt($this->t('Drop Off'))}[\s:]+.+?\s+{$this->opt($this->t('Phone'))}[\s:]+([+(\d][-. \d)(]{5,}[\d)])$#ms", $text), true, true)
        ;

        // Car
        $r->car()
            ->type($this->re("#{$this->opt($this->t('Car Type'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text));

        // Extra
        $r->extra()
            ->company(trim($this->re("#(?:\d{1,2}(?:[:：]\d{2})?(?:\s*[AaPp]\.?[Mm]\.?)?[ ]+)?(\b.+)\n+[ ]*{$this->opt($this->t('Pickup'))}[\s:]+#", $text)));

        // Program
        if (preg_match("#{$this->opt($this->t('Membership ID'))}[\s:]+([A-Z\d][A-Z\d \-]{2,}?)(?:[ ]{2}|$)#m", $text, $m)) {
            $r->program()
                ->account($m[1], false);
        }
        // Price
        $tot = $this->getTotalCurrency($this->re("#{$this->opt($this->t('Approximate Total Rate'))}.+?{$this->opt($this->t('Approximate Total Rate'))}[\s:]+(.+)#s", $text));

        if ($tot['Total'] !== null) {
            $r->price()
                ->total($tot['Total'])
                ->currency($tot['Currency']);
        } else {
            $tot = $this->getTotalCurrency($this->re("#{$this->opt($this->t('Approximate Total Rate'))}[\s:]+(.+)#", $text));

            if ($tot['Total'] !== null) {
                $r->price()
                    ->total($tot['Total'])
                    ->currency($tot['Currency']);
            }
        }
    }

    private function parseCarHtml(Email $email, $textPDF): void
    {
        $xpath = "//text()[{$this->eq($this->t('Car Information'))}]/ancestor::table[1]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length === 0) {
            $this->logger->info("Rental not found by xpath: {$xpath}");

            return;
        }

        foreach ($nodes as $root) {
            $r = $email->add()->rental();

            // General
            $conf = $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Reference Number'))}]/following::text()[normalize-space(.)!=''][1]", $root, true, "#([A-Z\d\-]{5,})#");

            if (!empty($conf) && !empty($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Reference Number'))}]/following::text()[normalize-space(.)!=''][1]", $root, true, "#({$this->opt($this->t('Not Assigned'))})#"))) {
                $r->general()
                    ->noConfirmation();
            } else {
                $r->general()
                    ->confirmation($conf);
            }
            $status = trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Status'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": ");

            if (!empty($status)) {
                if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $status)) {
                    $r->general()
                        ->cancelled();
                }
                $r->general()
                    ->status($status);
            }

            // Pickup
            $r->pickup()
                ->location(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Pick Up Location'))}]/following::text()[normalize-space(.)!=''][1]",
                    $root), ": "))
                ->date(strtotime($this->normalizeDate(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Pick Up Date/Time'))}]/following::text()[normalize-space(.)!=''][1]",
                    $root), ": "))))
                ->phone($this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('Phone'))}]/following::text()[normalize-space()][1]",
                    $root, true, '/^[: ]*([+(\d][-. \d)(]{5,}[\d)])[: ]*$/'), true, true);

            // Dropoff
            $r->dropoff()
                ->location(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Drop Off Location'))}]/following::text()[normalize-space(.)!=''][1]",
                    $root), ": "))
                ->date(strtotime($this->normalizeDate(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Drop Off Date/Time'))}]/following::text()[normalize-space(.)!=''][1]",
                    $root), ": "))))
                ->phone($this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('Phone'))}]/following::text()[normalize-space()][1]",
                    $root, true, '/^[: ]*([+(\d][-. \d)(]{5,}[\d)])[: ]*$/'), true, true);

            // Car
            $r->car()
                ->type(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Car Type'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "));

            // Extra
            $r->extra()
                ->company(trim($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Car Company'))}]/following::text()[normalize-space(.)!=''][1]", $root), ": "));

            //try add info from pdf
            if (!empty($textPDF) && !empty($r->getCompany())) {
                $memLang = $this->lang;
                $this->lang = $this->langPdf;

                $text = $this->re("#{$r->getCompany()}[\s:]+({$this->opt($this->t('Pickup'))}[\s:]+{$this->opt($this->t('Location'))}[\s:]+(?:.+\n+){22})#",
                    $textPDF);

                // AccountNumbers
                if (preg_match("#{$this->opt($this->t('Membership ID'))}[\s:]+([A-Z\d][A-Z\d \-]{2,}?)(?:[ ]{2}|$)#m", $text, $m)) {
                    $r->program()
                        ->account($m[1], false);
                }

                if (empty($r->getStatus()) && preg_match("#{$this->opt($this->t('Status'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text, $m)) {
                    $r->general()
                        ->status($m[1]);

                    if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $m[1])) {
                        $r->general()
                            ->cancelled();
                    }
                }

                // TotalCharge
                // Currency
                $tot = $this->getTotalCurrency($this->re("#{$this->opt($this->t('Approximate Total Rate'))}.+?{$this->opt($this->t('Approximate Total Rate'))}[\s:]+(.+)#s",
                    $text));

                if ($tot['Total'] !== null) {
                    $r->price()
                        ->total($tot['Total'])
                        ->currency($tot['Currency']);
                } else {
                    $tot = $this->getTotalCurrency($this->re("#{$this->opt($this->t('Approximate Total Rate'))}[\s:]+(.+)#",
                        $text));

                    if ($tot['Total'] !== null) {
                        $r->price()
                            ->total($tot['Total'])
                            ->currency($tot['Currency']);
                    }
                }

                $this->lang = $memLang;
            }
        }
    }

    private function parseRailPDF(Email $email, $text, $textPDF): void
    {
        foreach ($email->getItineraries() as $it) {
            /** @var \AwardWallet\Schema\Parser\Common\Train $it */
            if ($it->getType() === 'train') {
                $t = $it;

                break;
            }
        }

        if (!isset($t)) {
            $t = $email->add()->train();
        }

        // RecordLocator
        $railCarrier = $this->re("#{$this->opt($this->t('Rail Carrier'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text);

        if (preg_match('/^VIA\s*RAIL/i', $railCarrier)) {
            $railCarrierRule = $this->opt($this->t('viarailVariants'));
        } elseif ($railCarrier !== null) {
            $railCarrierRule = $this->opt($railCarrier);
        } else {
            $railCarrierRule = 'carrier_not_found';
        }

        $conf = null;
        $conf = $this->re("#OTHER\s+.*?{$railCarrierRule}\s+(?:RESERVATION|LOCATOR)[-\s]+([A-Z\d]{5,})#s", $textPDF);

        if (empty($conf)) {
            $conf = $this->re("#{$this->opt($this->t('Reference Number'))}[\s:]+([A-Z\d]{5,}\b)#", $text);
        }

        if (!empty($this->re("#{$this->opt($this->t('Reference Number'))}[\s:]+({$this->opt($this->t('Not Assigned'))})#", $text))
            && empty($t->getConfirmationNumbers())
        ) {
            $t->general()
                ->noConfirmation();
        } elseif (!in_array($conf, array_column($t->getConfirmationNumbers(), 0))) {
            $t->general()
                ->confirmation($conf);
        }

        $s = $t->addSegment();

        // Departure
        $s->departure()
            ->address($this->re("#{$this->opt($this->t('Origin'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))
            ->date(strtotime($this->normalizeDate($this->re("#{$this->opt($this->t('Departing'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))))
        ;

        // Arrival
        $s->arrival()
            ->address($this->re("#{$this->opt($this->t('Destination'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))
        ;
        $dateArr = $this->re("#{$this->opt($this->t('Arriving'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text);

        if (preg_match("#^{$this->opt($this->t('Not Assigned'))}#i", $dateArr)) {
            $s->arrival()->noDate();
        } else {
            $s->arrival()
                ->date(strtotime($this->normalizeDate($dateArr)));
        }

        // Extra
        $s->extra()
            ->number($this->re("#{$this->opt($this->t('Train'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text));
        $bookingClass = $this->re("#{$this->opt($this->t('Booking Class'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text);

        if ($bookingClass !== null && !preg_match("#^{$this->opt($this->t('Not Assigned'))}#i", $bookingClass)) {
            $s->extra()
                ->cabin($bookingClass);
        }

        $seat = $this->re("#{$this->opt($this->t('Seat'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text);

        if ($seat !== null && !preg_match("#^{$this->opt($this->t('Not Assigned'))}#i", $seat)) {
            $s->extra()
                ->seat($seat);
        }

        $this->addRailSumsFromPDF($t, $textPDF);
    }

    private function parseRailHtml(Email $email, $textPDF): void
    {
        $xpath = "//text()[{$this->eq($this->t('Rail Information'))}]/ancestor::table[1]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length === 0) {
            $this->logger->info("Train segments not found by xpath: {$xpath}");

            return;
        }

        foreach ($nodes as $root) {
            $t = $email->add()->train();

            $status = $this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(.),'Status')]/following::text()[normalize-space(.)!=''][1]", $root);

            if (!empty($status)) {
                if (preg_match("/^{$this->opt($this->t('cancelledStatus'))}/ui", $status)) {
                    $t->general()
                        ->cancelled();
                }
                $t->general()
                    ->status($status);
            }

            $railCarrier = $this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('Rail Carrier'))}]/following::text()[normalize-space()][1]", $root);

            if (preg_match('/^VIA\s*RAIL/i', $railCarrier)) {
                $railCarrierRule = $this->starts($this->t('viarailVariants'));
            } elseif ($railCarrier !== null) {
                $railCarrierRule = $this->starts($railCarrier);
            } else {
                $railCarrierRule = 'false()';
            }
            $referenceNumber = $this->http->FindSingleNode("preceding::text()[normalize-space()='Other:']/following::text()[normalize-space()][1][{$railCarrierRule}]", $root, true, "/(?:RESERVATION|LOCATOR)[-\s]+([A-Z\d]{5,})$/");

            if (empty($referenceNumber)) {
                $referenceNumber = $this->http->FindSingleNode("descendant::text()[starts-with(normalize-space(),'Reference Number')]/following::text()[normalize-space()][1]", $root, true, '/^[:\s]*([A-Z\d]{5,})$/');
            }

            if (!empty($referenceNumber)) {
                $t->general()
                    ->confirmation($referenceNumber);
            } else {
                $t->general()
                    ->noConfirmation();
            }

            $s = $t->addSegment();

            // Departure
            $s->departure()
                ->name($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Origin'))}]/following::text()[normalize-space(.)!=''][1]", $root))
                ->date(strtotime($this->normalizeDate($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Departing'))}]/following::text()[normalize-space(.)!=''][1]", $root))))
                ->strict()
            ;

            // Arrival
            $s->arrival()
                ->name($this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Destination'))}]/following::text()[normalize-space(.)!=''][1]", $root))
            ;
            // ArrName
            $dateArr = $this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('Arriving'))}]/following::text()[normalize-space()][1]", $root);

            if (preg_match("#^[:\s]*{$this->opt($this->t('Not Assigned'))}#i", $dateArr)) {
                $s->arrival()
                    ->noDate();
            } else {
                $s->arrival()
                    ->date(strtotime($this->normalizeDate($dateArr)));
            }

            // Extra
            $bookingClass = $this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('Booking Class'))}]/following::text()[normalize-space()][1]", $root);

            if ($bookingClass !== null && !preg_match("#^{$this->opt($this->t('Not Assigned'))}#i", $bookingClass)) {
                $seg['Cabin'] = $bookingClass;
                $s->extra()
                    ->cabin($bookingClass);
            }

            //try add info from pdf
            if (!empty($textPDF)) {
                $memLang = $this->lang;
                $this->lang = $this->langPdf;

                $text = $this->re("#({$this->opt($this->t('Reference Number'))}.+\n+(?:.+\n+){1,3}[ ]*{$this->opt($this->t('Origin'))}[:\s]+{$this->opt($s->getDepName())}\n.+\n[ ]*{$this->opt($this->t('Destination'))}[:\s]+{$this->opt($s->getArrName())}.*\n+(?:.+\n+){1,3}[ ]*{$this->opt($this->t('Special Information'))})#", $textPDF);

                if (!empty($str = $this->re("#{$this->opt($this->t('Train'))}[\s:]+(.+?)(?:[ ]{2}|$)#m", $text))) {
                    $s->extra()
                        ->number($str);
                }
                // Seats
                $seat = $this->re("#{$this->opt($this->t('Seat'))}[\s:]+(\d{1,3}[A-Z])(?:[ ]{2}|$)#m", $text);

                if (!empty($seat)) {
                    $s->extra()
                        ->seat($seat);
                }

                $this->lang = $memLang;
            }
        }

        if (empty($s->getNumber())) {
            $s->extra()
                ->noNumber();
        }

        if (!empty($textPDF)) {
            $this->addRailSumsFromPDF($t, $textPDF);
        }

        return;
    }

    private function addRailSumsFromPDF(Train $t, $textPDF): void
    {
        if (!empty($str = $this->re("#{$this->opt($this->t('The Price For Your Rail Ticket Is'))}[\s:]+(.+)#", $textPDF))) {
            $tot = $this->getTotalCurrency($str);

            if ($tot['Total'] !== null) {
                $t->price()
                    ->cost($tot['Total'])
                    ->currency($tot['Currency'], true, true);
            }
        }
    }

    private function t($s)
    {
        if (!isset($this->lang) || !isset(self::$dict[$this->lang][$s])) {
            return $s;
        }

        return self::$dict[$this->lang][$s];
    }

    private function assignLang($body, $isPdf = false): bool
    {
        $this->lang = null;
        $reBody = $isPdf ? $this->reBodyPDF : $this->reBodyHTML;

        foreach ($reBody as $lang => $re) {
            if (stripos($body, $re[0]) !== false && stripos($body, $re[1]) !== false) {
                $this->lang = substr($lang, 0, 2);

                return true;
            }
        }

        return false;
    }

    private function normalizeTraveller(?string $s): ?string
    {
        if (empty($s)) {
            return null;
        }

        $namePrefixes = '(?:MASTER|MSTR|MISS|MRS|MR|MS|DR)';

        return preg_replace([
            "/^(.{2,}?)\s+(?:{$namePrefixes}[.\s]*)+$/is",
            '/^([^\/]+?)(?:\s*[\/]+\s*)+([^\/]+)$/',
        ], [
            '$1',
            '$2 $1',
        ], $s);
    }

    private function normalizeDate($date)
    {
        $in = [
            //Sunday 12 November 2017 at 7:45PM
            '#^\w+\s+(\d+)\s+(\w+)\s+(\d+)\s+at\s+(\d+:\d+(?:\s*[ap]m)?)$#iu',
            //Sunday 12 November 2017
            '#^\w+\s+(\d+)\s+(\w+)\s+(\d+)$#',
            //09 November 2017 17:20 GMT
            '#^(\d+)\s+(\w+)\s+(\d+)\s+(\d+:\d+(?:\s*[ap]m)?)\s+GMT$#ui',
        ];
        $out = [
            '$1 $2 $3 $4',
            '$1 $2 $3',
            '$1 $2 $3 $4',
        ];
        $str = $this->dateStringToEnglish(preg_replace($in, $out, $date));

        return $str;
    }

    private function getTotalCurrency($node): array
    {
        $node = str_replace("€", "EUR", $node);
        $node = str_replace("$", "USD", $node);
        $node = str_replace("£", "GBP", $node);
        $tot = null;
        $cur = null;

        if (preg_match("#(?<c>[A-Z]{3})\s*(?<t>\d[\.\d\,\s]*\d*)#", $node, $m) || preg_match("#(?<t>\d[\.\d\,\s]*\d*)\s*(?<c>[A-Z]{3})#", $node, $m) || preg_match("#(?<c>\-*?)(?<t>\d[\.\d\,\s]*\d*)#", $node, $m)) {
            $m['t'] = preg_replace('/\s+/', '', $m['t']);            // 11 507.00   ->  11507.00
            $m['t'] = preg_replace('/[,.](\d{3})/', '$1', $m['t']);    // 2,790     ->  2790        or  4.100,00    ->  4100,00
            $m['t'] = preg_replace('/,(\d{1,2})$/', '.$1', $m['t']);    // 18800,00     ->  18800.00
            $tot = $m['t'];
            $cur = $m['c'];
        }

        return ['Total' => $tot, 'Currency' => $cur];
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'normalize-space(' . $node . ')="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'contains(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s);
        }, $field)) . ')';
    }
}
