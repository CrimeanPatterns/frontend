<?php

namespace AwardWallet\Engine\turkish\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Common\Flight;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class CheckIn extends \TAccountChecker
{
    public $mailFiles = "turkish/it-10626529.eml, turkish/it-114087040.eml, turkish/it-12053084.eml, turkish/it-141726705-pt.eml, turkish/it-148676665.eml, turkish/it-19157696-error.eml, turkish/it-26916523.eml, turkish/it-28314245.eml, turkish/it-29140191-error.eml, turkish/it-34993798.eml, turkish/it-35140947.eml, turkish/it-35168888.eml, turkish/it-35192651.eml, turkish/it-35193200.eml, turkish/it-35195804.eml, turkish/it-39084295.eml, turkish/it-39114350-error.eml, turkish/it-5717432.eml, turkish/it-6123316.eml, turkish/it-8680852.eml, turkish/it-891042119.eml, turkish/it-910534114.eml, turkish/it-910994950.eml";

    public $lang = "en";
    private $reFrom = "@thy.com";
    private $reSubject = [
        "en" => "Turkish Airlines - Online Ticket - Information Message",
        "es" => "Turkish Airlines - Billete en línea - Mensaje de información",
        "pt" => "Turkish Airlines - Bilhete online - mensagem informativa",
        "it" => "Turkish Airlines - biglietto on line. Messaggio informativo",
        'Dettagli sulla selezione del posto',
        "de" => "Turkish Airlines - Online Ticket - Informationsanzeige",
        "fr" => "Turkish Airlines - Billet en ligne - Message d'information",
        "tr" => "Turkish Airlines - Online Bilet - Bilgi Mesaji",
        "ru" => "Turkish Airlines - Электронный билет - Информационное сообщение",
        "zh" => "Turkish Airlines - 网上机票-基本信息",
    ];
    private $reBody = 'Turkish Airlines';
    private $reBody2 = [
        "en"     => "OUTBOUND TRIP",
        "en2"    => "OUTBOUND FLIGHT",
        "fr"     => "TRAJET ALLER",
        "fr2"    => "VOL ALLER",
        "es"     => "VIAJE DE IDA",
        "es2"    => "VUELO DE IDA",
        "pt"     => "VIAGEM DE IDA",
        "de"     => "HINREISE",
        "de2"    => "HINFLUG",
        "it"     => "VIAGGIO DI ANDATA",
        "it2"    => "VOLO DI ANDATA",
        "tr"     => "GİDİŞ",
        "ru"     => "РЕЙС ВЫЛЕТА",
        "ru2"    => "ОБРАТНЫЙ РЕЙС",
        "zh"     => "去程",
    ];

    private $passengerTitle = ['Mr', 'Mrs', 'Mr', 'Ms', 'Mr', 'Ms', '先生', 'Bay', 'Г-жа', 'Г-н', 'Herr'];
    private static $dictionary = [
        "en" => [
            "Check-in complete. Have a good trip." => [
                "Check-in complete. Have a good trip.",
                "Your seat selection has been completed.",
                "Remember to create your boarding pass",
            ],
            // "Reservation Code" => "",
            // 'From' => '', // From Istanbul to Ankara on Saturday 08 October
            ' - ' => [' - ', ' to '], // Istanbul - Ankara on Saturday 08 October
            // " on " => '', // Istanbul - Ankara on Saturday 08 October
            // "PASSENGER:" => '',
            'Check-in status'     => ['Check-in status', 'CHECK-IN STATUS'],
            "Seat:"               => ["Seat:", "Standard seat:"],
            "Passengers"          => 'Passengers', // Baggage block headers
            'to'                  => 'to', // Baggage block headers: Istanbul to Ankara
        ],
        "fr" => [
            "Check-in complete. Have a good trip." => [
                "Enregistrement terminé. Nous vous souhaitons un agréable voyage !",
                "Votre sélection de siège est terminée.",
                "Your seat selection has been completed.",
            ],
            // 'Reservation Code' => '',
            // 'From' => '', // From Istanbul to Ankara on Saturday 08 October
            ' - '             => [' à ', ' - '], // Istanbul - Ankara on Saturday 08 October
            " on "            => ' le ', // Istanbul - Ankara on Saturday 08 October
            "PASSENGER:"      => 'PASSAGER :',
            'Check-in status' => ['ETAT DE L\'ENREGISTREMENT', 'Statut de l\'enregistrement'],
            "Seat:"           => ["Siège :", "Siège standard:", 'Siège:'],
            "Passengers"      => 'Passagers', // Baggage block headers
            'to'              => 'à', // Baggage block headers: Istanbul to Ankara
        ],
        "es" => [
            "Check-in complete. Have a good trip." => [
                "Check-in finalizado. Le deseamos un buen vuelo.",
                "Your seat selection has been completed.",
                "Su selección de asiento ha sido completada.",
                "Se ha completado la selección de asiento.",
            ],
            // 'Reservation Code' => '',
            'From'            => 'De', // From Istanbul to Ankara on Saturday 08 October
            ' - '             => [' a ', ' - '], // Istanbul - Ankara on Saturday 08 October
            " on "            => [" el ", " en "], // Istanbul - Ankara on Saturday 08 October
            "PASSENGER:"      => 'PASAJERO:',
            'Check-in status' => ['ESTADO DEL CHECK-IN', 'Estado del check-in'],
            "Seat:"           => ["Asiento:", "Asiento estándar:"],
            "Passengers"      => 'Pasajeros', // Baggage block headers
            'to'              => 'a', // Baggage block headers: Istanbul to Ankara
        ],
        "pt" => [
            "Check-in complete. Have a good trip." => [
                "Sua seleção de assentos foi concluída.",
            ],

            'Reservation Code' => 'Código de reserva',
            // 'From' => '', // From Istanbul to Ankara on Saturday 08 October
            ' - '             => ' para ', // Istanbul - Ankara on Saturday 08 October
            " on "            => [" na ", " em "], // Istanbul - Ankara on Saturday 08 October
            "PASSENGER:"      => 'PASSAGEIRO:',
            'Check-in status' => ['Status do check-in'],
            "Seat:"           => ["Lugar:"],
            "Passengers"      => 'Passageiros', // Baggage block headers
            'to'              => 'para', // Baggage block headers: Istanbul to Ankara
        ],
        "de" => [
            "Check-in complete. Have a good trip." => [
                "Your seat selection has been completed.",
                "Ihre Sitzplatzauswahl ist abgeschlossen.",
                "Ihre Sitzplatzauswahl wurde abgeschlossen.",
            ],

            'Reservation Code' => 'Reservierungscode',
            'From'             => 'Von', // From Istanbul to Ankara on Saturday 08 October
            ' - '              => [' nach ', ' – ', ' - '], // Istanbul - Ankara on Saturday 08 October
            " on "             => [" am "], // Istanbul - Ankara on Saturday 08 October
            "PASSENGER:"       => 'PASSAGIER:',
            'Check-in status'  => ['CHECK-IN-STATUS', 'Check-in-status'],
            "Seat:"            => ["Sitzplatz:", 'Standardsitzplatz:'],
            "Passengers"       => 'Passagiere', // Baggage block headers
            'to'               => 'nach', // Baggage block headers: Istanbul to Ankara
        ],
        "tr" => [
            "Check-in complete. Have a good trip." => [
                "Koltuk seçiminiz tamamlandı. Ancak!",
            ],

            'Reservation Code' => 'Rezervasyon Kodu',
            // 'From' => '', // From Istanbul to Ankara on Saturday 08 October
            ' - '             => ' - ', // Istanbul - Ankara on Saturday 08 October
            " on "            => [", "], // Istanbul - Ankara on Saturday 08 October
            "PASSENGER:"      => 'YOLCU:',
            'Check-in status' => ['Check-in durumu'],
            "Seat:"           => ["Koltuk:", 'Standart koltuk:'],
            "Passengers"      => 'Yolcu', // Baggage block headers
            'to'              => '-', // Baggage block headers: Istanbul to Ankara
        ],
        "it" => [
            "Check-in complete. Have a good trip." => [
                "Your seat selection has been completed.",
                "La tua selezione del posto è stata completata.",
                "Check-in completato. Buon viaggio.",
            ],

            "Reservation Code" => "Codice di prenotazione",
            'From'             => 'Da', // From Istanbul to Ankara on Saturday 08 October
            ' - '              => ' - ', // Istanbul - Ankara on Saturday 08 October
            " on "             => [", in data ", ' il '], // Istanbul - Ankara on Saturday 08 October
            "PASSENGER:"       => 'PASSEGGERO:',
            // 'Check-in status' => ['', ''],
            "Seat:"      => ["Posto a sedere:"],
            "Passengers" => 'Passeggeri', // Baggage block
            'to'         => 'a', // Baggage block: Istanbul to Ankara
        ],
        "ru" => [
            "Check-in complete. Have a good trip." => [
                "Выбор места подтвержден.",
            ],

            'Reservation Code' => 'Код бронирования',
            // 'From' => '', // From Istanbul to Ankara on Saturday 08 October
            ' - '             => [' - ', ' — '], // Istanbul - Ankara on Saturday 08 October
            " on "            => [" в ", ": "], // Istanbul - Ankara on Saturday 08 October
            "PASSENGER:"      => 'ПАССАЖИР:',
            'Check-in status' => ['Статус регистрации'],
            "Seat:"           => ["Место:", "Стандартное место:"],
            "Passengers"      => 'Пассажиры', // Baggage block headers
            'to'              => ['до', '—'], // Baggage block headers: Istanbul to Ankara
        ],
        "zh" => [
            "Check-in complete. Have a good trip." => [
                "请不要忘记创建登机牌！",
            ],

            'Reservation Code' => '预订代码',
            // 'From' => '', // From Istanbul to Ankara on Saturday 08 October
            // ' - ' => '  ', // Istanbul - Ankara on Saturday 08 October
            " on "       => [" 上的 "], // Istanbul - Ankara on Saturday 08 October
            "PASSENGER:" => '乘客：',
            // 'Check-in status' => ['', ''],
            "Seat:"      => ["座位："],
            "Passengers" => '乘客', // Baggage block headers
            // 'to' => '', // Baggage block headers: Istanbul to Ankara
        ],
    ];
    private $date = null;
    private $justFirstBP;

    /** @var \PlancakeEmailParser */
    private $parser;

    public function detectEmailFromProvider($from)
    {
        return strpos($from, $this->reFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (strpos($headers["from"], $this->reFrom) === false) {
            return false;
        }

        foreach ($this->reSubject as $re) {
            if (stripos($headers["subject"], $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $body = $parser->getHTMLBody();

        if (strpos($body, $this->reBody) === false) {
            return false;
        }

        foreach ($this->reBody2 as $re) {
            if (strpos($body, $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->parser = $parser;

        foreach ($this->reBody2 as $lang => $re) {
            if (strpos($this->http->Response["body"], $re) !== false) {
                $this->lang = substr($lang, 0, 2);

                break;
            }
        }

        $this->parseHtml($email);

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

    private function parseHtml(Email $email)
    {
        $patterns = [
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]',
        ];

        $f = $email->add()->flight();

        // Confirmation
        $confNumber = $this->http->FindSingleNode("//text()[" . $this->eq($this->t("Check-in complete. Have a good trip.")) . "]/ancestor::td[1]/following-sibling::td[1]",
            null, true, "/^\s*([A-Z\d]{6,})(?:\s*$|\s+)/");

        if (empty($confNumber)) {
            $confNumber = $this->http->FindSingleNode("(//a[contains(@href, 'http://www.turkishairlines.com') and contains(@href, 'flights/manage-booking/index.html')])[1]/@href",
                null, true, "#\?pnr=([A-Z\d]{5,7})\W#");
        }

        if (empty($confNumber)) {
            // https://cl.turkishairlines.com/p/te/cl/https%3a_s_l__s_l_www.turkishairlines.com_s_l_en-us_s_l_flights_s_l_manage-booking_q_u_e_pnr_e_q_RWQ4FC%26surname_e_q_MACHADOVALENTECAVALCANTE...
            $confNumber = $this->http->FindSingleNode("(//a[contains(@href, '.turkishairlines.com') and contains(@href, 'flights_s_l_manage-booking') and contains(@href, '_pnr_e_q_')])[1]/@href",
                null, true, "#_q_u_e_pnr_e_q_([A-Z\d]{5,7})(?:%26|%25)#");
        }

        if (empty($confNumber)) {
            $confNumber = $this->http->FindSingleNode("(//a[contains(@href, '.turkishairlines.com') and contains(@href, 'bileti_s_l_rezervasyonu-yonet') and contains(@href, '_pnr_e_q_')])[1]/@href",
                null, true, "#_q_u_e_pnr_e_q_([A-Z\d]{5,7})(?:%26|%25)#");
        }

        if (empty($confNumber)) {
            $confNumber = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Reservation Code'))}]/ancestor::table[1]",
                null, true, "#^([A-Z\d]{5,7})\s*{$this->opt($this->t('Reservation Code'))}#su");
        }

        $f->general()
            ->confirmation($confNumber);

        // Travellers
        $pax = array_filter(array_unique($this->http->FindNodes("//text()[" . $this->eq($this->t("PASSENGER:")) . "]/ancestor::tr[1]/following-sibling::tr[not(" . $this->contains($this->t("PASSENGER:")) . ")]/td[2]/descendant::text()[normalize-space(.)][1]",
            null, "/^(\D+)$/")));

        if (count($pax) === 0) {
            $pax = array_filter(array_unique($this->http->FindNodes("//text()[" . $this->eq($this->t("PASSENGER:")) . "]/ancestor::tr[1]/following-sibling::tr[not(" . $this->contains($this->t("PASSENGER:")) . ")]/td[1]",
                null, "/^\s*[A-Z]{2}\s*(\D+)\s+\(/")));
        }

        if (count($pax) == 0) {
            $pax = $this->http->FindNodes("//tr[ *[normalize-space()][1][{$this->eq($this->t("Passengers"))}] and *[normalize-space()][2] ]/following-sibling::tr/*[normalize-space()][1]/descendant::tr/*[not(.//tr) and normalize-space()][2]",
                null, "/^(?:titlelookup[.\s]*)?({$patterns['travellerName']})(?:\s*\(|$)/u");
        }

        $f->general()
            ->travellers(preg_replace("/^\s*{$this->opt($this->passengerTitle)}[\.\s]+/iu", '', $pax));

        // Seats
        $seatsByFlight = [];
        $seatsOnly = [];
        $seatsNodes = $this->http->XPath->query("//text()[{$this->starts($this->t('Seat:'))}]/ancestor::td[position() < 3][preceding-sibling::*[{$this->contains($pax)}]]");

        foreach ($seatsNodes as $sRoot) {
            $text = implode("\n", $this->http->FindNodes(".//text()[normalize-space(.)]", $sRoot));
            $parts = $this->split("/(?:^|\n)\s*((?:[A-Z][A-Z\d]|[A-Z\d][A-Z])\d{1,4}\n)/", $text);

            foreach ($parts as $part) {
                if (preg_match("/^\s*((?:[A-Z][A-Z\d]|[A-Z\d][A-Z])\d{1,4})\s+{$this->opt($this->t('Seat:'))}\s*(\d{1,3}[A-Z])\b/", $part, $m)) {
                    $seatsByFlight[$m[1]][] = [
                        'seat' => $m[2],
                        'name' => preg_replace("/^\s*{$this->opt($this->passengerTitle)}[\.\s]+/iu", '', $this->http->FindSingleNode("preceding-sibling::*[{$this->contains($pax)}]",
                            $sRoot, null, "/.*({$this->opt($pax)}).*/iu")), ];
                }
            }

            if (empty($seatsByFlight)) {
                foreach ($parts as $part) {
                    $seats = preg_split("/{$this->opt($this->t('Seat:'))}/u", $part);

                    if (count($seats) > 0) {
                        unset($seats[0]);
                        $seats = array_values($seats);
                    }

                    if (count($seatsOnly) === 0 || count($seatsOnly) === count($seats)) {
                        foreach ($seats as $i => $v) {
                            $seatsOnly[$i][] = $v;
                        }
                    } else {
                        $seatsOnly = [];

                        break;
                    }
                }
            }
        }
        // $this->logger->debug('$seatsOnly = '.print_r( $seatsOnly,true));
        // $this->logger->debug('$seatsByFlight = '.print_r( $seatsByFlight,true));

        $flightsNumbersByPart = [];
        // парсинг блока со номерами рейса и местами (указаны номера на ближайшие рейсы)
        /*
         * PASSENGER:                           Check-in status
         * FG   MR FARRUH GASANOV               TK7001 26C
         * Adult                                TK2193          */
        $segmentsFromImg = [];
        $segmentsNodesFromImg = $this->http->XPath->query("//img/ancestor::td[1][count(.//text()[contains(translate(normalize-space(),'0123456789', 'dddddddddd'),'dd:dd')])=2]");

        foreach ($this->http->XPath->query("//img/ancestor::td[1][count(.//text()[contains(translate(normalize-space(),'0123456789', 'dddddddddd'),'dd:dd')])=2]/following::text()[" . $this->starts($this->t("Seat:")) . " or {$this->starts($this->t('Check-in status'))}][1]") as $rootFromImg) {
            $flights = $this->http->FindNodes("ancestor::td[1]/descendant::text()[" . $this->starts($this->t("Seat:")) . "]/preceding::text()[normalize-space(.)][1]",
                $rootFromImg, "#^(?:[A-Z\d][A-Z]|[A-Z][A-Z\d])\s*\d+$#");

            if (empty($flights)) {
                $flights = array_filter($this->http->FindNodes("ancestor::tr[1]/following-sibling::tr[1][*[1][string-length(normalize-space(.)) = 2]]/*[normalize-space(.)][3]/descendant::text()[normalize-space(.)]",
                    $rootFromImg, "#^(?:[A-Z\d][A-Z]|[A-Z][A-Z\d])\s*\d+$#"));
            }

            if (empty($flights)) {
                $flights = array_filter($this->http->FindNodes("ancestor::tr[1]/following-sibling::tr[1]/*[normalize-space(.)][2]/descendant::text()[normalize-space(.)]",
                    $rootFromImg, "#^(?:[A-Z\d][A-Z]|[A-Z][A-Z\d])\s*\d+$#"));
            }
            $flightsNumbersByPart[] = $flights;
        }

        // $this->logger->debug('$flightsNumbersByPart = '.print_r( $flightsNumbersByPart,true));

        $segmentsPart = 0;
        // парсинг блока со датой и временем (сокращенные данные, без пересадок)
        /*
         * Ankara - Izmir on Thursday, March 21, 2019
         * Economy Class
         *                 Ankara (ESB) 22:15      23:35 Izmir (ADB) */

        foreach ($segmentsNodesFromImg as $rootFromImg) {
            $text = implode("\n", $this->http->FindNodes(".//text()[normalize-space(.)]", $rootFromImg));
            $flightHeader = implode("\n", $this->http->FindNodes("./ancestor::table[1]/preceding::text()[normalize-space(.)][2]", $rootFromImg));
            $seg = [];

            if (preg_match("#^\s*(?:{$this->opt($this->t('From'))}\s+)?(.+)\s*{$this->opt($this->t(' - '))}\s*(.+)\s*" . $this->opt($this->t(" on ")) . "\s*(.+)#u", $flightHeader, $m)
            ) {
                $seg['depName'] = $m[1];
                $seg['arrName'] = $m[2];
                $seg['date'] = $m[3];
                $seg['normalizeDate'] = $this->normalizeDate($m[3]);
            } elseif ($this->lang == 'ru'
                && preg_match("#(.+) - (.+) (\d+\s+\w+\s+\d{4})\s*г\.\s*$#u", $flightHeader, $m)
            ) {
                $seg['depName'] = $m[1];
                $seg['arrName'] = $m[2];
                $seg['date'] = $m[3];
                $seg['normalizeDate'] = $this->normalizeDate($m[3]);
            } elseif ($this->lang == 'ru'
                && preg_match("#(.+) - (.+) ([[:alpha:]]+\s+\d{1,2}+\s+[[:alpha:]]+)\s*$#u", $flightHeader, $m)
            ) {
                // Москва - Стамбул четверг 02 января
                $seg['depName'] = $m[1];
                $seg['arrName'] = $m[2];
                $seg['date'] = $m[3];
                $seg['normalizeDate'] = $this->normalizeDate($m[3]);
            } elseif ($this->lang == 'zh'
                && preg_match("#^(.+)\s*" . $this->opt($this->t(" on ")) . "\s*(.+) 至 (.+)#u", $flightHeader, $m)
            ) {
                // 星期日 12 一月 上的 巴塞罗那 至 利雅得
                $seg['depName'] = $m[2];
                $seg['arrName'] = $m[3];
                $seg['date'] = $m[1];
                $seg['normalizeDate'] = $this->normalizeDate($m[3]);
            }

            if (preg_match("/^\s*.+\s*\(([A-Z]{3})\)\s+(\d{1,2}:\d{2}.*)\n(\d{1,2}:\d{2}.*)\n.+\s*\(([A-Z]{3})\)\s*$/", $text, $m)) {
                $seg['depCode'] = $m[1];
                $seg['arrCode'] = $m[4];
                $seg['depTime'] = $m[2];
                $seg['arrTime'] = $m[3];
            }
            $seg['cabin'] = $this->http->FindSingleNode("./ancestor::table[1]/preceding::text()[normalize-space(.)][1]", $rootFromImg);

            $seg['segmentsPart'] = $segmentsPart;

            foreach ($this->http->XPath->query("following::text()[normalize-space()][position() < 90]", $rootFromImg) as $posRoot) {
                if ($this->http->XPath->query("ancestor::td[1][.//img][count(.//text()[contains(translate(normalize-space(),'0123456789', 'dddddddddd'),'dd:dd')])=2]", $posRoot)->length > 0) {
                    break;
                }

                if (!empty($this->http->FindSingleNode("./ancestor::*[1][{$this->starts($this->t("Seat:"))} or {$this->eq($this->t('Passengers'))} or {$this->contains($this->t("PASSENGER:"))}]", $posRoot))) {
                    $segmentsPart++;

                    break;
                }
            }

            $segmentsFromImg[] = $seg;
        }

        // 'depName', 'depCode', 'depTime', 'arrName', 'arrCode', 'arrTime', 'date', 'normalizeDate', 'cabin', 'segmentsPart'
        // $this->logger->debug('$segmentsFromImg = ' . print_r($segmentsFromImg, true));

        // RoutesNames
        // парсинг таблицы с информацией о багаже (указаны все сегменты из которых состоит перелет)
        /* Passengers                               Izmir to Ankara             Ankara to Izmir
        *                                              Economy                      Economy
         * FG   MR FARRUH GASANOV                   15 kg maximum               20 kg maximum
         */
        $routesNames = [];
        $routesNodes = $this->http->XPath->query("//tr[*[1][{$this->eq($this->t('Passengers'))}]]/*[position() > 1]");

        foreach ($routesNodes as $nodesRoot) {
            $route = [];
            $names = $this->http->FindSingleNode("descendant::text()[normalize-space(.)][1]", $nodesRoot);

            if (preg_match("/^\s*(?:{$this->opt($this->t('From'))}\s+)?(.+)\s+{$this->opt($this->t('to'))}\s+(.+)/u", $names, $m)) {
                $route['depName'] = $m[1];
                $route['arrName'] = $m[2];
                $route['cabin'] = $this->http->FindSingleNode("descendant::text()[normalize-space(.)][2]", $nodesRoot);
            }
            $routesNames[] = $route;
        }

        // 'depName', 'arrName', 'cabin'
        // $this->logger->debug('$routesNames = '.print_r( $routesNames,true));

        // объединение все данных в один общий массив
        $unitedSegments = [];

        $segmentsFromImgIndex = 0;

        foreach ($routesNames as $rnIndex => $routeName) {
            if (!empty($routeName['depName']) && !empty($segmentsFromImg[$segmentsFromImgIndex]['depName'])
                && !empty($routeName['arrName']) && !empty($segmentsFromImg[$segmentsFromImgIndex]['arrName'])
                && $routeName['depName'] === $segmentsFromImg[$segmentsFromImgIndex]['depName']
                && $routeName['arrName'] === $segmentsFromImg[$segmentsFromImgIndex]['arrName']
            ) {
                $seg = $segmentsFromImg[$segmentsFromImgIndex];
                $seg['cabin'] = $routeName['cabin'] ?? $segmentsFromImg[$segmentsFromImgIndex]['cabin'];

                if (!empty($flightsNumbersByPart[$segmentsFromImg[$segmentsFromImgIndex]['segmentsPart']])) {
                    $seg['flight'] = array_shift($flightsNumbersByPart[$segmentsFromImg[$segmentsFromImgIndex]['segmentsPart']]);
                } else {
                    $seg['flight'] = null;
                }
                $unitedSegments[] = $seg;
                $segmentsFromImgIndex++;

                continue;
            }

            if (!empty($routeName['depName']) && !empty($segmentsFromImg[$segmentsFromImgIndex]['depName'])
                && $routeName['depName'] === $segmentsFromImg[$segmentsFromImgIndex]['depName']
            ) {
                $seg = [
                    'date'          => $segmentsFromImg[$segmentsFromImgIndex]['date'],
                    'normalizeDate' => $segmentsFromImg[$segmentsFromImgIndex]['normalizeDate'],
                    'depName'       => $segmentsFromImg[$segmentsFromImgIndex]['depName'],
                    'depCode'       => $segmentsFromImg[$segmentsFromImgIndex]['depCode'],
                    'depTime'       => $segmentsFromImg[$segmentsFromImgIndex]['depTime'],
                ];
                $seg['arrName'] = $routeName['arrName'];
                $seg['cabin'] = $routeName['cabin'] ?? $segmentsFromImg[$segmentsFromImgIndex]['cabin'];

                if (!empty($flightsNumbersByPart[$segmentsFromImg[$segmentsFromImgIndex]['segmentsPart']])) {
                    $seg['flight'] = array_shift($flightsNumbersByPart[$segmentsFromImg[$segmentsFromImgIndex]['segmentsPart']]);
                } else {
                    $seg['flight'] = null;
                }

                $segmentsFromImg[$segmentsFromImgIndex]['useDep'] = true;
                $unitedSegments[] = $seg;

                continue;
            }

            if (!empty($routeName['arrName']) && !empty($segmentsFromImg[$segmentsFromImgIndex]['arrName'])
                && $segmentsFromImg[$segmentsFromImgIndex]['useDep'] === true
                && $routeName['arrName'] === $segmentsFromImg[$segmentsFromImgIndex]['arrName']
            ) {
                $seg = [
                    'date'          => $segmentsFromImg[$segmentsFromImgIndex]['date'],
                    'normalizeDate' => $segmentsFromImg[$segmentsFromImgIndex]['normalizeDate'],
                    'arrName'       => $segmentsFromImg[$segmentsFromImgIndex]['arrName'],
                    'arrCode'       => $segmentsFromImg[$segmentsFromImgIndex]['arrCode'],
                    'arrTime'       => $segmentsFromImg[$segmentsFromImgIndex]['arrTime'],
                ];
                $seg['depName'] = $seg['depName'] ?? $routeName['depName'];

                $seg['cabin'] = $routeName['cabin'] ?? $segmentsFromImg[$segmentsFromImgIndex]['cabin'];

                if (!empty($flightsNumbersByPart[$segmentsFromImg[$segmentsFromImgIndex]['segmentsPart']])) {
                    $seg['flight'] = array_shift($flightsNumbersByPart[$segmentsFromImg[$segmentsFromImgIndex]['segmentsPart']]);
                } else {
                    $seg['flight'] = null;
                }
                $segmentsFromImg[$segmentsFromImgIndex]['useDep'] = true;
                $unitedSegments[] = $seg;
                $segmentsFromImgIndex++;

                continue;
            }
            $this->logger->debug('error 2 $unitedSegments');
            $unitedSegments = [];

            break;
        }
        // $this->logger->debug('$unitedSegments = ' . print_r($unitedSegments, true));

        foreach ($unitedSegments as $uI => $uSeg) {
            $s = $f->addSegment();

            // Airline
            if (preg_match("#^\s*([A-Z\d]{2})(\d{1,4})\s*$#", $uSeg['flight'] ?? '', $m)) {
                $s->airline()
                    ->name($m[1])
                    ->number($m[2]);
            } elseif (empty($uSeg['flight'])) {
                $s->airline()
                    ->name('TK')
                    ->noNumber();
            }

            // Departure
            $s->departure()
                ->name($uSeg['depName'] ?? '');

            if (!empty($uSeg['depCode'])) {
                $s->departure()
                    ->code($uSeg['depCode']);
            } else {
                $s->departure()
                    ->noCode();
            }

            if (!empty($uSeg['normalizeDate']) && !empty($uSeg['depTime'])) {
                $s->departure()
                    ->date(strtotime($uSeg['depTime'], $uSeg['normalizeDate']));
            } elseif (empty($uSeg['depTime'])) {
                $s->departure()
                    ->noDate();
            }

            // Arrival
            $s->arrival()
                ->name($uSeg['arrName'] ?? '');

            if (!empty($uSeg['arrCode'])) {
                $s->arrival()
                    ->code($uSeg['arrCode']);
            } else {
                $s->arrival()
                    ->noCode();
            }

            if (!empty($uSeg['normalizeDate']) && !empty($uSeg['arrTime'])) {
                $s->arrival()
                    ->date(strtotime($uSeg['arrTime'], $uSeg['normalizeDate']));
            } elseif (empty($uSeg['arrTime'])) {
                $s->arrival()
                    ->noDate();
            }

            // Extra
            $uSeg['cabin'] = preg_replace("#^(?:cabintypelookup.)\s*#", '', $uSeg['cabin'] ?? '');

            if (preg_match("/^\s*(.+?)\s*\(([A-Z]{1,2})\)\s*$/", $uSeg['cabin'] ?? '', $m)) {
                $s->extra()
                    ->cabin($m[1])
                    ->bookingCode($m[2])
                ;
            } else {
                $s->extra()
                    ->cabin($uSeg['cabin'] ?? '');
            }

            if (!empty($uSeg['flight']) && !empty($seatsByFlight[$uSeg['flight']])) {
                foreach ($seatsByFlight[$uSeg['flight']] as $seat) {
                    $s->extra()
                        ->seat($seat['seat'], true, true, $seat['name']);
                }
            } else {
                $seats = [];

                if (!empty($uSeg['flight'])) {
                    $seats = array_filter($this->http->FindNodes(".//text()[normalize-space() = '" . $uSeg['flight'] . "']/following::text()[normalize-space(.)!=''][1]",
                        null, "#^\s*(\d{1,3}[A-Z])$#"));
                }

                if (empty($seats)) {
                    if (!empty($seatsOnly) && count($seatsOnly) === count($unitedSegments) && count($seatsOnly[$uI]) === count($f->getTravellers())) {
                        $seats = array_filter($seatsOnly[$uI], function ($v) {return preg_match("#^\s*(\d{1,3}[A-Z])\s*$#", $v) ? true : false; });
                    }
                }

                if (!empty($seats)) {
                    $s->extra()
                        ->seats($seats);
                }
            }
        }

        $this->BoardingPass($f, $email);
    }

    private function BoardingPass(Flight $f, Email $email)
    {
        if (empty($f->getConfirmationNumbers())) {
            return null;
        }
        $confNumber = $f->getConfirmationNumbers()[0][0];

        if (!empty($confNumber)) {
            $travellers = $f->getTravellers();

            foreach ($travellers as $traveller) {
                $segments = $f->getSegments();

                foreach ($segments as $segment) {
                    $depCode = $segment->getDepCode();
                    $flightNumber = $segment->getFlightNumber();

                    if (!empty($depCode) && !empty($flightNumber)) {
                        $bp = $email->add()->bpass();
                        $bp->setRecordLocator($confNumber);
                        $bp->setAttachmentName($this->http->FindSingleNode('//text()[normalize-space(.)="' . $confNumber . '"]/ancestor::a[1]/@href'));
                        $bp->setTraveller($traveller[0]);
                        $bp->setFlightNumber(trim(($segment->getAirlineName() ?? '') . ' ' . $flightNumber));
                        $bp->setDepCode($depCode);
                        $bp->setDepDate($segment->getDepDate());
                    }
                }
            }
        }
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function normalizeDate($instr, $relDate = false)
    {
        // $this->logger->debug('$instr = '.print_r( $instr,true));
        $in = [
            // 14 Ocak 2025 Salı
            "#^\s*(\d{1,2})\s*([[:alpha:]]+)\s+(\d{4})\s+[[:alpha:]]+\s*$#u",
            "#^(?<week>[^\s\d]+) (\d+) ([^\s\d]+)$#", //Saturday 08 October
        ];
        $out = [
            "$1 $2 $3",
            "$2 $3 %Y%",
        ];
        $str = preg_replace($in, $out, $instr);

        // $this->logger->debug('$str = '.print_r( $str,true));

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%Y%)#", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        if (preg_match("#^\s*[[:alpha:]]+[\s,]+(\d+\s+([[:alpha:]]+)\s+\d{4})\s*$#u", $str, $m)) {
            $str = $m[1];
        }

        foreach ($in as $re) {
            if (preg_match($re, $instr, $m) && isset($m['week'])) {
                $str = str_replace("%Y%", date('Y',
                    EmailDateHelper::calculateDateRelative(str_replace('%Y%', '', $str), $this, $this->parser)), $str);
                $dayOfWeekInt = WeekTranslate::number1($m['week'], $this->lang);

                return EmailDateHelper::parseDateUsingWeekDay($str, $dayOfWeekInt);
            }
        }

        return strtotime($str, $relDate);
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "normalize-space(.)=\"{$s}\"";
        }, $field));
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

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
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
