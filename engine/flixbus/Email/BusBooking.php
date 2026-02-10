<?php

namespace AwardWallet\Engine\flixbus\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BusBooking extends \TAccountChecker
{
    public $mailFiles = "flixbus/it-111317380.eml, flixbus/it-136765835.eml, flixbus/it-137381485.eml, flixbus/it-51783123.eml, flixbus/it-52817536.eml, flixbus/it-52824941.eml, flixbus/it-56504939.eml, flixbus/it-56603344.eml, flixbus/it-57617640.eml, flixbus/it-59005195.eml, flixbus/it-59207638.eml, flixbus/it-60712324.eml, flixbus/it-60986870.eml, flixbus/it-62593782.eml, flixbus/it-661689003.eml, flixbus/it-669650104.eml, flixbus/it-670797206.eml, flixbus/it-71007859.eml, flixbus/it-74390731.eml, flixbus/it-884185579.eml, flixbus/it-884369844.eml, flixbus/it-884887356.eml, flixbus/it-885204293.eml, flixbus/it-885255559.eml, flixbus/it-886259503.eml, flixbus/it-887279669.eml, flixbus/it-888088671.eml, flixbus/it-888352992.eml, flixbus/it-889459463.eml, flixbus/it-889736447.eml, flixbus/it-890239722.eml, flixbus/it-890792050.eml, flixbus/it-891086394.eml, flixbus/it-892417598.eml, flixbus/it-892542128.eml, flixbus/it-892751774.eml, flixbus/it-893456169.eml, flixbus/it-99403574.eml";
    private $detectSubject = [
        'ca' => [
            'Número de confirmació de reserva de FlixBus:',
        ],
        'cs' => [
            'FlixBus potvrzení rezervace',
        ],
        'da' => [
            'FlixBus-bookingbekræftelse',
        ],
        'de' => [
            'FlixBus Buchungsbestätigung', 'Rechnungen für Deine Buchung',
        ],
        'en' => [
            'FlixBus Booking Confirmation', 'Invoices for your booking', 'Booking Confirmation', 'FlixBus Your New Booking',
        ],
        'es' => [
            'Confirmación de reserva', 'Pasajes de tu reserva',
        ],
        'fr' => [
            'Confirmation de réservation FlixBus', 'Factures pour votre réservation', 'Confirmation de réservation',
        ],
        'hr' => [
            'Potvrda rezervacije',
        ],
        'it' => [
            'FlixBus Conferma di Prenotazione',
        ],
        'nl' => [
            'FlixBus boekingsbevestiging', 'Bevestiging van boeking', 'Je nieuwe boeking',
        ],
        'pl' => [
            'Potwierdzenie rezerwacji FlixBus #', 'Bilhetes de passagem eletrônicos (faturas) de sua reserva',
            'Potwierdzenie rezerwacji',
        ],
        'pt' => [
            'Confirmação de reserva FlixBus', 'Faturas para a tua reserva',
        ],
        'ro' => [
            'Confirmarea rezervării',
        ],
        'ru' => [
            'Ваш билет и подтверждение бронирования', 'Подтверждение бронирования',
        ],
        'sk' => [
            'Potvrdenie o rezervácii FlixBus č.', 'Potvrdenie rezervácie', 'Vaša nová rezervácia',
        ],
        'sv' => [
            'Fakturor för din bokning',
        ],
        'uk' => [
            'Номер бронювання FlixBus',
        ],
    ];

    private $detectCompany = ['FlixBus', '.flixbus.'];

    private $detectBodyHtml = [
        'en' => [
            'includes the following trips:',
        ],
        'es' => [
            'incluye los siguientes trayectos:',
        ],
        'uk' => [
            'включає такі автобусні подорожі:',
        ],
        'pt' => [
            'inclui as viagens seguintes:',
        ],
        'cs' => [
            'obsahuje tyto cesty:',
        ],
        'fr' => [
            'comprend les trajets suivants :',
        ],
        'de' => [
            'beinhaltet folgende Fahrt(en):',
        ],
        'ru' => [
            'включает следующие поездки:',
        ],
        'nl' => [
            'bevat de volgende reizen',
        ],
        'sk' => [
            'obsahuje tieto cesty:',
        ],
    ];
    private $detectBodyPdf1 = [
        'cs' => [
            'POTVRZENÍ REZERVACE',
            'Pomocí tohoto QR kódu Vás',
        ],
        'de' => [
            'DEINE BUCHUNGSBESTÄTIGUNG',
            'Mit diesem QR-Code können wir',
        ],
        'en' => [
            'YOUR BOOKING CONFIRMATION',
            'This QR-code helps us to check',
        ],
        'es' => [
            'TU CONFIRMACIÓN DE RESERVA',
            'Este código QR nos ayudará',
        ],
        'fr' => [
            'VOTRE CONFIRMATION DE',
            'Ce code QR vous permet',
        ],
        'hu' => [
            'Nyomtatott és digitális formában is érvényes',
        ],
        'it' => [
            'NUMERO DI',
            'Con questo codice QR, il check',
        ],
        'nl' => [
            'JOUW BOEKINGSBEVESTIGING',
            'Deze QR-code helpt ons om je',
        ],
        'pt' => [
            'A TUA CONFIRMAÇÃO DE RESERVA',
            'SUA CONFIRMAÇÃO DE RESERVA',
            'Use o código QR e faça o',
        ],
        'ru' => [
            'ПОДТВЕРЖДЕНИЕ БРОНИРОВАНИЯ И',
            'Покажите этот QR-код, чтобы',
        ],
        'sv' => [
            'Fakturor för din bokning',
        ],
        'uk' => [
            'ПІДТВЕРДЖЕННЯ ВАШОГО',
            'Цей QR-код полегшить',
        ],
    ];

    private $pdfPattern = ".*\.pdf";
    private $lang = 'en';
    private $bookingInfo;
    private $invoicesInfo;
    private $cancelInfo; // cancellation invoices
    private $travellers = [];
    private static $dictionary = [
        'ca' => [
            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => "TARGETA D'EMBARCAMENT",
            "Train"                  => "Tren",
            "Bus"                    => "Autobús",
            // "Route" => "",
            "Adults"                 => "Adults",
            "Additional Information" => "Informació addicional",
            "Total price:"           => "Preu total:",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "CARRER / NÚM.",
        ],
        'cs' => [
            // Html
            "includes the following trips:" => "obsahuje tyto cesty:",
            "Line"                          => "Spoj",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => "POTVRZENÍ REZERVACE",
            "BOOKING NUMBER:"           => "ČÍSLO REZERVACE:",
            "DEPARTURE"                 => "ODJEZD",
            "Platform number"           => "Číslo nástupiště",
            "Please note"               => "Pamatuj,",
            "Bus connection"            => "Autobusový spoj",
            //            "Train connection" => "",
            //            "TRANSFER IN" => "",
            //            "Seats" => "",
            "Seat:"                                 => "Sedadlo:",
            "ARRIVAL"                               => "PŘÍJEZD",

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => "Jízdenky a faktury vystavené k Vaší rezervaci",
            "Adults"                                => "Dospělý",
            "Phone number"                          => "Telefonní číslo",
            "Invoice"                               => "Faktura",
            "Booking number:"                       => "Číslo rezervace:",
            //            "GROSS" => "",
            "Total" => "Celkem",
        ],
        'da' => [
            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS" => "BOARDINGPAS",
            //            "Train" => "",
            "Bus"                    => "Bus",
            // "Route" => "",
            "Adults"                 => "Voksne",
            "Additional Information" => "Yderligere oplysninger:",
            "Total price:"           => "Samlet pris:",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "GADE/VEJ NR.",
        ],
        'de' => [
            // Html
            "includes the following trips:" => "beinhaltet folgende Fahrt(en):",
            "Line"                          => "Linie",
            "Seat"                          => "Sitz",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => "DEINE BUCHUNGSBESTÄTIGUNG",
            "BOOKING NUMBER:"           => "BUCHUNGSNUMMER:",
            "DEPARTURE"                 => "AB",
            //            "Please note" => [],
            "Bus connection" => ["Busverbindung"],
            //            "Train connection" => "",
            //            "TRANSFER IN" => "",
            "ARRIVAL"                               => "AN",
            "Seat:"                                 => "Sitz:",
            "Seats"                                 => "Sitz",

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking"  => [
                "Tickets und rechnungen für deine buchung", "Rechnungen für Deine Buchung",
                "Stornorechnungen für Deine Buchung", "Tickets und Rechnungen für Deine Buchung",
            ],
            "Cancellation invoices for your booking" => "Stornorechnungen für Deine Buchung",
            "Invoice"                                => ["Rechnung", "Stornobeleg"],
            "Cancellation"                           => "Storno",
            "Booking number:"                        => "Buchungsnummer:",
            "Phone number"                           => "Telefonnummer",
            "Total"                                  => "Gesamtpreis",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => ["BORDKARTE", "TICKET"],
            "BOOKING NUMBER"         => "BUCHUNGSNUMMER",
            "Train"                  => "Zug",
            "Bus"                    => "Bus",
            "Route"                  => "Strecke",
            'Adults'                 => ['Erwachsener', 'Erwachsene'],
            "Additional Information" => "Weitere Informationen",
            "Total price:"           => "Gesamtpreis:",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "STRASSE / NR.",
        ],
        'en' => [
            // Html
            //            "includes the following trips:" => "",
            //            "Line" => "",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            //            "YOUR BOOKING CONFIRMATION" => "",
            //            "BOOKING NUMBER:" => "",
            //            "DEPARTURE" => "",
            "Please note"    => ["Please note", "Operated by", "The line ", "The platform number", "Tickets are"],
            "Bus connection" => ["Bus connection", "Connection"],
            //            "Train connection" => "",
            //            "TRANSFER IN" => "",
            //            "ARRIVAL" => "",
            //            "Seat:" => "",
            //            "Seats" => "",

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => [
                "Tickets and invoices for your booking", "Invoices for your booking", "Tax Invoices for your booking",
                "Cancellation invoices for your booking", "Cancelation invoices for your booking",
            ],
            'Cancellation invoices for your booking'  => ['Cancellation invoices for your booking', 'Cancelation invoices for your booking'],
            "Invoice"                                 => ["Invoice", "Cancellation Invoice", "Cancelation Invoice", "Cancellation Tax Invoice", "Cancelation Tax Invoice"],
            "Cancellation"                            => ["Cancelation", "Cancellation"],

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"  => ["BOARDING PASS", "TICKET"],
            "BOOKING NUMBER" => ["BOOKING NUMBER", "Booking No.", "Booking number"],
            //            "Train" => "",
            //            "Bus" => "",
            //            "Route" => "",
            "Adults"                 => ["Adults", "Adult"],
            "Additional Information" => ["Additional Information", "Additional information"],
            //            "Total price:" => "",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "STREET / HOUSE NO.",
        ],
        'es' => [
            // Html
            "includes the following trips:" => "incluye los siguientes trayectos:",
            "Line"                          => "Línea",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => "TU CONFIRMACIÓN DE RESERVA",
            "BOOKING NUMBER:"           => "NÚMERO DE RESERVA:",
            "DEPARTURE"                 => "SALIDA",
            "Please note"               => "Por favor,",
            "Bus connection"            => "Conexión de autobús",
            //            "Train connection" => "",
            //            "TRANSFER IN" => "",
            "ARRIVAL" => "LLEGADA",
            //            "Seat:" => "",
            "Seats"   => "Asiento",

            // Pdf, Type 3: Invoices
            'Tickets and invoices for your booking' => ['Billetes y facturas de tu reserva', 'Facturas de tu reserva', 'Pasajes de tu reserva'],
            "Invoice"                               => ["Factura", "Pasaje"],
            "Booking number:"                       => "Número de reserva:",
            "Total"                                 => "Total",

            // Pdf, Type 2: Ticket (the sheet is divided into 4 equal parts)
            "BOARDING PASS"          => ["BILLETE", "BOLETO"],
            "BOOKING NUMBER"         => "NÚMERO DE RESERVA",
            "Train"                  => "Tren",
            "Bus"                    => "Autobús",
            "Route"                  => "Ruta",
            "Adults"                 => ["Adultos", "Adulto"],
            "Additional Information" => ['Información adicional', "información adicional"],
            "Total price:"           => "Precio total:",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "CALLE/N.º",
        ],
        'fr' => [
            // Html
            "includes the following trips:" => "comprend les trajets suivants :",
            "Line"                          => "Ligne",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => "VOTRE CONFIRMATION DE",
            "BOOKING NUMBER:"           => "NUMÉRO DE COMMANDE:",
            "DEPARTURE"                 => "DÉPART",
            //            "Please note" => "",
            "Bus connection" => ["Liaison par bus"],
            //            "Train connection" => "",
            "TRANSFER IN"                           => "CHANGEMENT À",
            "ARRIVAL"                               => "ARRIVÉE",
            "Seat:"                                 => "Siège:",
            "Seats"                                 => "Sièges",

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => [
                "Billets et factures pour votre réservation",
                "Factures pour votre réservation",
            ],
            "Invoice"         => "Facture",
            "Booking number:" => "Numéro de commande:",
            'Phone number'    => ['Numéro de téléphone:', 'Numéro de'],
            "Total"           => "Total",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => "BILLET",
            "BOOKING NUMBER"         => ["NUMÉRO DE COMMANDE", "N° DE LA RÉSERVATION"],
            //            "Train" => "",
            "Bus"                    => "Bus",
            // "Route" => "",
            'Adults'                 => ['Adultes', 'Adulte'],
            "Additional Information" => "Informations supplémentaires :",
            "Total price:"           => "Prix total :",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "RUE / NUMÉRO",
        ],
        'hr' => [
            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => "KARTA",
            "BOOKING NUMBER"         => "BROJ REZERVACIJE",
            //            "Train" => "",
            "Bus"                    => "Autobus",
            "Route"                  => "smjer",
            "Adults"                 => ["Odrasel", "Odrasli"],
            "Additional Information" => "Dodatne informacije",
            "Total price:"           => "Ukupna cijena:",
        ],
        'hu' => [
            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS" => "JEGY",
            //            "Train" => "",
            "Bus"                    => "Busz",
            // "Route" => "",
            "Adults"                 => "Felnőtt",
            "Additional Information" => "További információk",
            "Total price:"           => "Teljes ár:",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "UTCA / HÁZSZÁM",
        ],
        'it' => [
            // Html
            //"includes the following trips:" => "",
            //"Line"                          => "",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => "LA TUA PRENOTAZIONE",
            "BOOKING NUMBER:"           => ["NUMERO DI PRENOTAZIONE:", "NUMERO DI"],
            "DEPARTURE"                 => "PARTENZA",
            //            "Please note" => [],
            "Bus connection"   => "Linea dell'autobus",
            "Train connection" => "Tratta",
            //"TRANSFER IN" => "",
            "ARRIVAL"          => "ARRIVO",
            //"Seats"   => "",
            "Seat:"            => "Posto a sedere:",

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => "Fattura per la tua prenotazione",
            "Invoice"                               => "Ricevuta",
            "Booking number:"                       => "Numero di prenotazione:",
            "Total"                                 => "Totale",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => "BIGLIETTO",
            "BOOKING NUMBER"         => ["NUMERO DI PRENOTAZIONE", "NUMERO DI"],
            //            "Train" => "",
            "Bus"                    => "Autobus",
            // "Route" => "",
            "Adults"                 => ['Adulti/e', 'Adulto'],
            "Additional Information" => "Ulteriori informazioni",
            "Total price:"           => " Tariffa totale:",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "STRADA/ NO CIVICO",
        ],
        'nl' => [
            // Html
            "Please be at the bus stop 15 minutes" => "Zorg dat je 15 minuten voor",
            "includes the following trips:"        => "bevat de volgende reizen:",
            "Line"                                 => "Lijn",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => "JOUW BOEKINGSBEVESTIGING",
            "BOOKING NUMBER:"           => "BESTELNUMMER:",
            "DEPARTURE"                 => "VERTREK",
            //            "Please note" => [],
            "Bus connection" => "Bus",
            //            "Train connection" => "",
            //"TRANSFER IN" => "",
            "ARRIVAL" => "AANKOMST",
            "Seats"   => "Zitplaats",
            "Seat:"   => "Zitplaats:",
            "Adult"   => "Volwassene",

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => "Facturen van je boeking",
            "Invoice"                               => "Factuur",
            "Booking number:"                       => "Bestelnummer:",
            "Total"                                 => "Totaal",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            'BOARDING PASS'  => ['TICKET', 'INSTAPKAART'],
            'BOOKING NUMBER' => "BOEKINGSNUMMER",
            //            "Train" => "",
            //            "Bus" => "",
            //            "Route" => "",
            'Adults'                 => 'Volwassen',
            "Additional Information" => "Extra informatie",
            "Total price:"           => "Totaalprijs:",

            // Pdf Luggage tags
            //            "STREET / HOUSE NO." => "",
        ],
        'pl' => [
            // Html
            //            "includes the following trips:" => "",
            //            "Line"                          => "",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => "BILET",
            "BOOKING NUMBER"         => "NUMER REZERWACJI",
            //            "Train" => "",
            "Bus"                    => "Trasa",
            "Route"                  => "Kierunek",
            "Adults"                 => ["Dorosły", "Dorośli"],
            "Additional Information" => "Dodatkowe informacje",
            "Total price:"           => "Łącznie:",
            "Passport type"          => "Tipo de passaporte",
            "Passport number"        => "Número de passaporte",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "ULICA / NR DOMU",
        ],
        'pt' => [
            // Html
            "includes the following trips:" => "inclui as viagens seguintes:",
            "Line"                          => "Linha",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => ["A TUA CONFIRMAÇÃO DE RESERVA", "SUA CONFIRMAÇÃO DE RESERVA"],
            "BOOKING NUMBER:"           => "NÚMERO DE RESERVA:",
            "DEPARTURE"                 => ["PARTIDA", "SAÍDA"],
            //            "Please note" => "",
            "Bus connection" => ["Ligação de autocarro", "Linha de ônibus", "Linha"],
            //            "Train connection" => "",
            //            "TRANSFER IN" => "",
            "Seat:" => ["Lugar:", "Assento:"],
            //            "Seats" => "",
            'Adults'                                => ['Adulto', 'Adultos'],
            'Ticket'                                => 'Bilhete de passagem',
            "ARRIVAL"                               => "CHEGADA",
            'Phone number'                          => 'Numéro de',

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => [
                "Bilhetes e faturas para a tua reserva",
                "Bilhetes de passagem eletrônicos (faturas) de sua",
                'Faturas para a tua reserva',
            ],
            "Invoice"                               => ["Bilhete de passagem", "Fatura"],
            "Booking number:"                       => "Número de reserva: ",
            "Total"                                 => "Total",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => ["PASSAGEM", "BILHETE"],
            "BOOKING NUMBER"         => "NÚMERO DE RESERVA",
            "Train"                  => "Trem",
            "Bus"                    => ["Ônibus", "Autocarro"],
            // "Route" => "",
            "Additional Information" => ["Informações adicionais", 'Informação adicional'],
            "Total price:"           => "Preço total:",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "RUA / N.º CASA",
        ],
        'ro' => [
            // Pdf, Type 2: the sheet is divided into 4 equal parts
            // pdf is not recognized correctly, needs to be corrected
            "BOARDING PASS"          => "BILET",
            "BOOKING NUMBER"         => "NUMĂR DE ACHIZIȚIE",
            //            "Train" => "",
            "Bus"                    => "Traseu",
            "Route"                  => "direcția",
            "Adults"                 => "Tineri",
            "Additional Information" => "Informații suplimentare",
            "Total price:"           => "Preț total:",
        ],
        'ru' => [
            // Html
            "includes the following trips:" => "включает следующие поездки:",
            "Line"                          => "Маршрут",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => "ПОДТВЕРЖДЕНИЕ БРОНИРОВАНИЯ И",
            "BOOKING NUMBER:"           => "НОМЕР БРОНИРОВАНИЯ:",
            "DEPARTURE"                 => "ОТПРАВЛЕНИЕ В",
            //            "Please note" => [],
            "Bus connection" => ["Автобусный маршрут"],
            //            "Train connection" => "",
            "TRANSFER IN"                           => "ПЕРЕСАДКА В:",
            "ARRIVAL"                               => ["ПРИБЫТИЕ В:", "ПРИБЫТИЕ В"],
            "Seats"                                 => "Места",
            'Phone number'                          => 'Телефон',
            //            "Seat:" => "",

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => "Билеты и квитанции для вашего бронирования",
            "Invoice"                               => "Квитанция/билет",
            //            "GROSS" => "",
            "Total" => "Общая стоимость",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => "ПОСАДОЧНЫЙ ТАЛОН",
            "BOOKING NUMBER"         => "НОМЕР БРОНИРОВАНИЯ",
            //            "Train" => "",
            "Bus"                    => "Автобус",
            "Route"                  => "Направление",
            "Adults"                 => ["Взрослый", "Взрослые", "Взрослых"],
            "Additional Information" => "Дополнительная информация",
            "Total price:"           => "Всего:",

            // Pdf Luggage tags
            //            "STREET / HOUSE NO." => "",
        ],
        'sk' => [
            // Html
            "includes the following trips:" => "obsahuje tieto cesty:",
            "Line"                          => "Linka",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "Seats" => "Miesto na sedenie",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS" => "LÍSTOK",
            //            "Train" => "",
            "Bus"                    => "Autobus",
            "Route"                  => "Spoj",
            "Adults"                 => ["Dospelí", "Dospelý"],
            "Additional Information" => "Ďalšie informácie",
            "Total price:"           => "Celková cena:",

            // Pdf Luggage tags
            "STREET / HOUSE NO." => "ULICA / BUDOVA Č.",
        ],
        'sv' => [
            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => "Fakturor för din bokning",
            "Invoice"                               => "Kvitto",
            "Booking number:"                       => "Bokningsnummer:",
            "Total"                                 => "Totalt",
        ],
        'uk' => [
            // Html
            "includes the following trips:" => "включає такі автобусні подорожі:",
            "Line"                          => "Рейс",
            //            "(train)" => "",
            //            "Coach" => "",

            // Pdf, Type 1: Booking
            "YOUR BOOKING CONFIRMATION" => "ПІДТВЕРДЖЕННЯ ВАШОГО",
            "BOOKING NUMBER:"           => "НОМЕР БРОНЮВАННЯ:",
            "DEPARTURE"                 => "ВІДПРАВЛЕННЯ",
            //            "Please note" => "",
            "Bus connection" => "Автобусний маршрут",
            //            "Train connection" => "",
            //            "TRANSFER IN" => "",
            "ARRIVAL" => "ПРИБУТТЯ",
            //            "Seat:" => "",
            //            "Seats" => "",

            // Pdf, Type 3: Invoices
            "Tickets and invoices for your booking" => "Квитки та інвойси для вашого бронювання",
            "Invoice"                               => "Інвойс",
            "Booking number:"                       => "Номер бронювання:",
            //            "GROSS" => "",
            "Total" => "Усього",

            // Pdf, Type 2: the sheet is divided into 4 equal parts
            "BOARDING PASS"          => "ПОСАДКОВИЙ ТАЛОН",
            "BOOKING NUMBER"         => "НОМЕР БРОНЮВАННЯ",
            //            "Train" => "",
            "Bus"                    => "Автобус",
            // "Route" => "",
            'Adults'                 => ['Дорослі', 'Дорослий'],
            "Additional Information" => "Додаткова інформація",
            "Total price:"           => "Вартість:",

            // pdf Luggage tags
            "STREET / HOUSE NO." => "№ ВУЛ. / БУД.",
        ],
    ];

    private $patterns = [
        'time' => '\b\d{1,2}[.:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?|\b)?', // 4:19PM    |    2:00 p. m.    |    17.25
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $type = null;
        $parseSuccess = false;
        $pdfs = $parser->searchAttachmentByName($this->pdfPattern);

        $this->bookingInfo = [
            'confirmation' => [
                'number' => null,
                'desc'   => null,
            ],
            'segments'     => [],
            'travellers'   => [],
            'currency'     => null,
            'total'        => null,
            'taxes'        => [],
        ];

        $this->invoicesInfo = [
            'confirmation' => [
                'number' => null,
                'desc'   => null,
            ],
            'segments'     => [],
            'travellers'   => [],
            'currency'     => null,
            'total'        => null,
            'taxes'        => [],
        ];

        $this->cancelInfo = [
            'confirmation' => [
                'number' => null,
                'desc'   => null,
            ],
            'segments'     => [],
            'travellers'   => [],
        ];

        // PDF types:
        // Pdf, Type 1: Booking (with large Google map, may include Invoices)
        // Pdf, Type 2: Ticket (the sheet is divided into 4 equal parts)
        // Pdf, Type 3: Invoices (with Booking or without Booking)
        // Also exist pdf with luggage tags, from which may to collect travellers

        foreach ($pdfs as $pdf) {
            if (($text = \PDF::convertToText($parser->getAttachmentBody($pdf))) === null) {
                continue;
            }
            $pdfName = $parser->getAttachmentHeader($pdf, 'content-type') ?? null;

            foreach (self::$dictionary as $lang => $dict) {
                // Luggage tags
                if (!empty($dict['STREET / HOUSE NO.']) && $this->strposAll($text, $dict['STREET / HOUSE NO.']) === true
                    && preg_match_all("/\n\n\n([ ]{0,15}[[:alpha:]](?:.*\n+){1,3})[ ]+{$this->opt($dict['STREET / HOUSE NO.'])}/u", $text, $m)
                ) {
                    $this->travellers = array_unique(array_map('trim', preg_replace(["/^(.{15,}?)[ ]{3}.*/m", '/\s+/'], ['$1', ' '], $m[1])));
                }

                // Pdf, Type 2: Ticket
                if (!empty($dict['BOARDING PASS']) && !empty($dict['Additional Information'])
                    && $this->strposAll($text, $dict['BOARDING PASS']) === true
                    && $this->strposAll($text, $dict['Additional Information']) === true
                ) {
                    $this->lang = $lang;

                    $this->parsePdf2($email, $text);
                    $type = 'Pdf2';
                    $parseSuccess = true;

                    continue 2;
                }
            }

            // Pdf, Type 1: Booking
            foreach ($this->detectBodyPdf1 as $lang => $detectBody) {
                foreach ($detectBody as $dBody) {
                    if (strpos($text, $dBody) !== false && $type !== 'Pdf2') {
                        $this->lang = $lang;

                        if (!$this->parsePdf($text)) {
                            $this->logger->info("parsePdf1 is failed'");
                        } else {
                            $type = 'Pdf1';
                            $parseSuccess = true;
                        }

                        break 2;
                    }
                }
            }

            // Pdf, Type 3: Invoices
            foreach (self::$dictionary as $lang => $detectBody) {
                if (isset($detectBody['Tickets and invoices for your booking'])) {
                    foreach ((array) $detectBody['Tickets and invoices for your booking'] as $dBody) {
                        if (strpos($text, $dBody) !== false && $type !== 'Pdf2') {
                            $this->lang = $lang;

                            if (!$this->parsePdfInvoice($text, $pdfName)) {
                                $this->logger->info("parsePdfInvoice is failed'");
                            } else {
                                $type = $type == 'Pdf1' ? 'Pdf13' : 'Pdf3';
                                $parseSuccess = true;
                            }

                            break 2;
                        }
                    }
                }
            }
        }

        if ($type == 'Pdf1' || $type == 'Pdf3' || $type == 'Pdf13') {
            $this->itinerariesUnion($email, $this->bookingInfo, $this->invoicesInfo, $this->cancelInfo);
        }

        // add travellers from Luggage tags into travellers from Ticket (Pdf2)
        if ($type === 'Pdf2' && count($this->travellers) > 0) {
            foreach ($email->getItineraries() as $it) {
                $itTravellers = array_column($it->getTravellers(), 0);

                if (count($itTravellers) === 0) {
                    $it->general()->travellers($this->travellers);

                    continue;
                }

                foreach ($this->travellers as $newName) {
                    $travellerFound = false;

                    foreach ($itTravellers as $name) {
                        if (strcasecmp($newName, $name) === 0) {
                            $travellerFound = true;

                            break;
                        }
                    }

                    if ($travellerFound === false) {
                        $it->general()->traveller($newName);
                    }
                }
            }
        }

        // parse html, if pdfs are not parsed
        if ($parseSuccess === false || empty($email->getItineraries())) {
            if (!empty($email->getItineraries())) {
                $email->clearItineraries();
            }
            $body = html_entity_decode($this->http->Response["body"]);

            foreach ($this->detectBodyHtml as $lang => $detectBody) {
                foreach ($detectBody as $dBody) {
                    if (stripos($body, $dBody) !== false
                        || !empty($this->http->FindSingleNode("(//*[{$this->contains($dBody)}])[1]"))) {
                        $this->lang = $lang;

                        break;
                    }
                }
            }
            $type = 'Html';
            $this->parseHtml($email);
        }
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . $type . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]flixbus\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        foreach ($this->detectSubject as $lang => $detectSubject) {
            foreach ($detectSubject as $dSubject) {
                if (stripos($headers["subject"], $dSubject) !== false
                    && (stripos($headers["subject"], 'flixbus') !== false
                        || !empty($headers['from']) && stripos($headers['from'], 'flixbus') !== false
                    )
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $body = $this->http->Response['body'];

        foreach ($this->detectCompany as $dc) {
            if (strpos($body, $dc) !== false) {
                foreach ($this->detectBodyHtml as $detectBody) {
                    foreach ($detectBody as $dBody) {
                        if (strpos($body, $dBody) !== false) {
                            return true;
                        }
                    }
                }
            }
        }

        $pdfs = $parser->searchAttachmentByName($this->pdfPattern);

        foreach ($pdfs as $pdf) {
            if (($text = \PDF::convertToText($parser->getAttachmentBody($pdf))) === null) {
                continue;
            }

            // Pdf, Type 1: Booking
            foreach ($this->detectBodyPdf1 as $lang => $detectBody) {
                foreach ($detectBody as $dBody) {
                    if (strpos($text, $dBody) !== false) {
                        $this->lang = $lang;

                        return true;
                    }
                }
            }

            // Pdf, Type 3: Invoices
            foreach (self::$dictionary as $lang => $detectBody) {
                if (isset($detectBody['Tickets and invoices for your booking'])) {
                    foreach ((array) $detectBody['Tickets and invoices for your booking'] as $dBody) {
                        if (strpos($text, $dBody) !== false) {
                            $this->lang = $lang;

                            return true;
                        }
                    }
                }
            }

            $part = substr($text, 0, 100);

            // Pdf, Type 2: Ticket
            foreach (self::$dictionary as $lang => $dict) {
                if (!isset($dict['BOARDING PASS'])) {
                    continue;
                }

                foreach ((array) $dict['BOARDING PASS'] as $bpText) {
                    if (stripos($part, $bpText) !== false) {
                        $this->lang = $lang;

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

    private function parsePdf(string $text): bool
    {
        $total = 0;
        $changeTotal = false;
        $currency = null;

        // trim pdf, removing invoices (if exist)
        $text = $this->re("/^(.+?)(?:{$this->opt($this->t('Tickets and invoices for your booking'))}|$)/su", $text);
        $routes = array_filter(preg_split("/^\s*{$this->opt($this->t('YOUR BOOKING CONFIRMATION'))}.*$/m", $text));

        if (empty($routes)) {
            $this->logger->debug(__FUNCTION__ . ': $routes is empty');

            return false;
        }

        foreach ($routes as $route) {
            $row = $this->inOneRow(preg_replace("/^.+\n/", '', substr($route, 0, 1500)));

            $segments = [];

            $tableHeaders = $this->TableHeadPos($row);

            $table = $this->SplitCols(preg_replace("/^.+\n/", '', $route), $tableHeaders);

            if (count($table) == 3) {
                [$leftColumn, $centralColumn, $rightColumn] = $table;

                // collect reservation confirmation
                if (preg_match("/(?<desc>{$this->opt($this->t('BOOKING NUMBER:'))})\s+\#(?<confNumber>\d{9,})\b/", $leftColumn, $m)) {
                    $this->bookingInfo['confirmation'] = [
                        'number' => $m['confNumber'],
                        'desc'   => trim(preg_replace("/\s+/", ' ', $m['desc']), ':'),
                    ];
                }

                // collect travellers
                $travellersText = $this->re("/{$this->opt($this->t('Adults'))}(.+?)(?:{$this->opt($this->t('Total'))}|$)/s", $rightColumn);

                if (preg_match_all("/^\s*([[:alpha:]][-.\'[:alpha:]\s]*[[:alpha:]])\s*\n\s*(?:{$this->opt($this->t('Phone number'))}|.+?[\+\(\d][\-\+\. \d\)\(]{5,}[\d\)]|\D+?\d+\s*x)$/mu", $travellersText, $m)) {
                    foreach ($m[1] as &$traveller) {
                        $traveller = preg_replace("/\s+/", ' ', $traveller);
                        $traveller = $this->re("/(.+?)\s*(?:{$this->opt($this->t('Phone number'))}|$)/", $traveller);
                    }
                    unset($traveller);

                    $this->bookingInfo['travellers'] = $m[1];
                }

                // cut off the central column
                if (preg_match_all("/^([ ]{0," . ($tableHeaders[1] - 10) . "}.*?)(?:[ ]{2,}|$)/mu", $route, $rowsMatches)) {
                    // determine the number of reservation rows in the center column
                    foreach ($rowsMatches[1] as $i => $s) {
                        if (mb_strlen($s) > ($tableHeaders[1] - 10)) {
                            $blockLen = $i;

                            break;
                        }
                    }
                }

                if (!empty($blockLen) && preg_match("/^((?:.*\n){{$blockLen}})[\s\S]+/u", $centralColumn, $m)) {
                    $centralColumn = $m[1];
                }

                $regexp = "/\s*(?<date>.+?)[ ]\d{1,2}:\d{2}.*\,[\s\S]+\n\s*{$this->opt($this->t('DEPARTURE'))}[ ]+(?<dtime>{$this->patterns['time']}).*\s+(?<dstation>[\s\S]+)\n\s*"
                    . "(?<type>{$this->opt($this->t('Bus connection'))}|{$this->opt($this->t('Train connection'))})\s+(?<number>\S+)\s+[\s\S]+\n"
                    . "{$this->opt($this->t('ARRIVAL'))}[ ]+(?<atime>{$this->patterns['time']})(?:\(.*|.{1,3})?\s*\n\s*(?<astation>[\s\S]+)/u";

                if (preg_match($regexp, $centralColumn, $segMatch)) {
                    if (empty($blockLen)) {
                        $segMatch['astation'] = $this->re("/(.*)\n\n\s*.*/s", $segMatch['astation']);
                    }

                    if (preg_match("/(?<dstat>[\s\S]+?)\s*\n(\s*(?:{$this->opt($this->t('Bus connection'))}|{$this->opt($this->t('Train connection'))})[\s\S]+)/u",
                        $segMatch['dstation'], $m)) {
                        $nextDay = 0;
                        // segment with transit
                        $transitregexp = "/\s*(?<type>{$this->opt($this->t('Bus connection'))}|{$this->opt($this->t('Train connection'))})\s+(?<number>\S+)\s+"
                            . "[\s\S]+?\n\n(?<tstation>[\s\S]+?)\s+{$this->opt($this->t('ARRIVAL'))}\s+(?<atime>{$this->patterns['time']}).*?"
                            . "[ ]+{$this->opt($this->t('DEPARTURE'))}\s+(?<dtime>{$this->patterns['time']})/u";

                        if (preg_match_all($transitregexp, $segMatch['dstation'], $trans)) {
                            $fromDateTime = strtotime($this->normalizeTime($segMatch['dtime']), $this->normalizeDate($segMatch['date']));
                            $seg = [
                                'from' => $this->niceStationName(preg_replace("/^(.+?)\s+(?:{$this->opt($this->t('Bus connection'))}|{$this->opt($this->t('Train connection'))})\s+.+/s",
                                    '$1', $segMatch['dstation'])),
                                'fromDateTime' => $fromDateTime,
                            ];

                            foreach ($trans[0] as $key => $tr) {
                                $trans['tstation'][$key] = preg_replace("/^.*\s*{$this->opt($this->t('TRANSFER IN'))}\s*(\S.+)/s", '$1',
                                    $trans['tstation'][$key]);
                                $seg['to'] = $this->niceStationName($trans['tstation'][$key]);

                                //it-71007859.eml
                                $toDateTime = strtotime($this->normalizeTime($trans['atime'][$key]), $this->normalizeDate($segMatch['date']));

                                if (($fromDateTime - $toDateTime) < 0) {
                                    $seg['toDateTime'] = $toDateTime;
                                } else {
                                    $seg['toDateTime'] = strtotime('+1 day', $toDateTime);
                                    $nextDay = 1;
                                }

                                $seg['number'] = $trans['number'][$key];
                                $seg['type'] = $trans['type'][$key];
                                $segments[] = $seg;

                                //it-71007859.eml
                                if ($nextDay == 0) {
                                    $fromDateTime = strtotime($this->normalizeTime($trans['dtime'][$key]), $this->normalizeDate($segMatch['date']));
                                }

                                if ($nextDay == 1) {
                                    $fromDateTime = strtotime('+1 day', strtotime($this->normalizeTime($trans['dtime'][$key]), $this->normalizeDate($segMatch['date'])));
                                }

                                $seg = [
                                    'from'         => $this->niceStationName($trans['tstation'][$key]),
                                    'fromDateTime' => $fromDateTime,
                                ];
                            }
                            $seg['to'] = $this->niceStationName($segMatch['astation']);

                            //it-71007859.eml
                            if ($nextDay == 0) {
                                $toDateTime = strtotime($this->normalizeTime($segMatch['atime']), $this->normalizeDate($segMatch['date']));
                            }

                            if ($nextDay == 1) {
                                $toDateTime = strtotime('+1 day', strtotime($this->normalizeTime($segMatch['atime']), $this->normalizeDate($segMatch['date'])));
                            }
                            $seg['toDateTime'] = $toDateTime;

                            $seg['number'] = $segMatch['number'];
                            $seg['type'] = $segMatch['type'];
                            $segments[] = $seg;

                            if (preg_match_all("/^\s*{$this->opt($this->t('Seats'))}[ ]*\(.+(?:\n.+)?\s\-\s.+(?:\n.+)?\)\n{1,3}(.+)/mu", $rightColumn, $sm) && count($sm[0]) == count($segments)) {
                                foreach ($sm[1] as $i => $s) {
                                    if (preg_match("/^\s*(?:{$this->opt($this->t('Coach'))}[ ]*(?<car>[\dA-Z]{1,3})\:)?[ ]*(?<seats>\d{1,2}[A-Z]((?:\,[ ]{0,2})\d{1,2}[A-Z])*)\s*$/",
                                        $s, $match)) {
                                        $segments[$i]['seats'] = array_map('trim', explode(",", $match['seats']));

                                        if (!empty($match['car'])) {
                                            $segments[$i]['car'] = $match['car'];
                                        }
                                    }
                                }
                            } elseif (preg_match_all("/\n\s*{$this->opt($this->t('Seat:'))}\s*\n((?:.*\n)+?)(?:\n{2,}|$|{$this->opt($this->t('Total'))})/u", $rightColumn, $sm) && count($sm[0]) == count($segments)) {
                                foreach ($sm[1] as $s) {
                                    if (preg_match_all("/^\s*[ ]*.+\s[›]\s+.+[ ](?<seats>\d{1,2}[A-Z])$/mu", $s, $matches)) {
                                        foreach ($matches['seats'] as $i => $match) {
                                            $segments[$i]['seats'][] = trim($match);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $segments[] = [
                            'number'       => $segMatch['number'],
                            'type'         => $segMatch['type'],
                            'from'         => $this->niceStationName($this->re("/^(.+?)\s*(?:{$this->opt($this->t('Platform number'))}|$)/us", $segMatch['dstation'])),
                            'fromDateTime' => strtotime($this->normalizeTime($segMatch['dtime']), $this->normalizeDate($segMatch['date'])),
                            'to'           => $this->niceStationName($segMatch['astation']),
                            'toDateTime'   => strtotime($this->normalizeTime($segMatch['atime']), $this->normalizeDate($segMatch['date'])),
                        ];

                        if (preg_match("/\n\s*(?:{$this->opt($this->t('Coach'))}[ ]*(?<car>[\dA-Z]{1,3})\:)?[ ]*(?<seats>\d{1,2}[A-Z]((?:\,[ ]{0,2})\d{1,2}[A-Z])*)\s*(?:\n{5,}|$|{$this->opt($this->t('Total'))})/", $rightColumn, $match)) {
                            $segments[0]['seats'] = array_map('trim', explode(",", $match['seats']));

                            if (!empty($match['car'])) {
                                $segments[0]['car'] = $match['car'];
                            }
                        } elseif (preg_match_all("/{$this->opt($this->t('Seat:'))}\s*(\d+[A-Z])/", $rightColumn, $m)) {
                            $segments[0]['seats'] = $m[1];
                        } elseif (preg_match_all("/\n\s*{$this->opt($this->t('Seat:'))}[ ]*(?<seats>\d{1,2}[A-Z]((?:[ ]{0,2})\d{1,2}[A-Z])*)\s*(?:\n{3,}|$|{$this->opt($this->t('Total'))})/", $rightColumn, $seatsMatches)) {
                            $segments[0]['seats'] = [];

                            foreach ($seatsMatches['seats'] as $mat) {
                                $segments[0]['seats'] = array_merge($segments[0]['seats'], array_map('trim', explode(" ", $mat)));
                            }
                        }
                    }
                }

                if (isset($total)) {
                    $totalText = $this->re("/{$this->opt($this->t('Total'))}[:\s]*(\d[\d,.]*\s*\D)(?:\s|\n)/u", $text)
                        ?? $this->re("/{$this->opt($this->t('Total'))}[:\s]*([^\d\s:]+\s*\d[\d,.]*)(?:\s|\n)/u", $text);

                    if (preg_match("/(?<total>\d[\d.,]*)\s*(?<currency>\D+)/", $totalText, $m)
                        || preg_match("/(?<currency>\D+)\s*(?<total>\d[\d.,]*)/", $totalText, $m)
                    ) {
                        if (empty($currency) || $this->currency($m['currency']) === $currency) {
                            $currency = $this->currency($m['currency']);
                            $total = PriceHelper::parse($m['total'], $currency);
                        } else {
                            unset($total);
                        }
                        $changeTotal = true;
                    }
                }
            }

            $this->bookingInfo['segments'] = array_merge($this->bookingInfo['segments'], $segments);

            if (isset($total) && $changeTotal && $currency) {
                $this->bookingInfo['total'] = $total;
                $this->bookingInfo['currency'] = $currency;
            }
        }

        return true;
    }

    private function parsePdf2(Email $email, string $text): void
    {
        $routes = array_filter($this->split("#^([ ]*(?:[\d ]{10,}|{$this->opt($this->t('BOOKING NUMBER'))})[ ]{3,}{$this->opt($this->t('BOARDING PASS'))}\b.*)#mu", $text));

        foreach ($routes as $route) {
            $confirmation = null;
            $confDesc = null;

            if (preg_match("/^\s*(?<number>\d{3}[ ]?\d{3}[ ]?\d{4})[ ]*{$this->opt($this->t('BOARDING PASS'))}(?:[ ]{5}.*)?\n[ ]*(?<desc>{$this->opt($this->t('BOOKING NUMBER'))})?/u", $text, $m)
                || preg_match("/^[ ]*(?:(?<desc>{$this->opt($this->t('BOOKING NUMBER'))})|.*)[ ]*{$this->opt($this->t('BOARDING PASS'))}(?:[ ]{5}.*)?\n[ ]*(?<number>\d{3}[ ]?\d{3}[ ]?\d{4})(?:\n|[ ]+[[:alpha:]])/u", $text, $m)
            ) {
                $confirmation = str_replace(' ', '', $m['number']);

                if (!empty($m['desc'])) {
                    $confDesc = $m['desc'];
                }
            }

            $route = preg_replace("/^.+\n/", '', $route);

            $datas = preg_split("/\n([ ]*{$this->opt($this->t('Additional Information'))})/u", $route, false);

            $currency = $totalAmount = null;
            $totalPrice = $this->re("/{$this->opt($this->t('Total price:'))}[\: ]*(\d[\d\,\. ]*[ ]*\D{1,7}|\s*\D{1,7}[ ]*\d[\d\,\. ]*?)(?:[ ]{2}|\n)/u", $datas[1] ?? '');

            if (preg_match("/^\s*(?<total>\d[\d\.\,]*)\s*(?<currency>\D*)\s*$/u", $totalPrice, $m)
                || preg_match("/^\s*(?<currency>\D+?)\s*(?<total>\d[\d\.\,]*)\s*$/u", $totalPrice, $m)
            ) {
                $currency = $this->currency($m['currency']);
                $totalAmount = PriceHelper::parse($m['total'], $currency);
            }

            $tableText = $datas[0];
            $tableHeaders = $this->TableHeadPos(preg_replace('/^(\s+\d{3,4} ?\d{3,4} ?\d{3,4})[ ]{5,}(\S)/', '$1    $2', $this->re('/^(.+)/', $tableText)));

            if (count($tableHeaders) !== 2) {
                continue;
            }
            $tableHeaders[0] = 0;
            $table = $this->SplitCols(preg_replace("/^.+\n/", '', $tableText), $tableHeaders, false);

            if ($this->lang == 'da') {
                $timeFormat = "\b\d{1,2}\.\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?|\b)?"; // 12.20  |  06.15 PM
            } else {
                $timeFormat = "\b\d{1,2}:\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?|\b)?"; // 12:20  |  06:15 PM
            }

            $segment = [];

            $rows = $this->split("/(\n[ ]{0,10}{$timeFormat}.*?[ ]{2,})/", $table[0]);
            $sDate = $this->re("/\n[ ]{0,10}(?<date>.+?)\n{1,2}\s*(?<dtime>{$timeFormat}).*[ ]{2,}/", $table[0]);

            $rowSegments = [];

            if (count($rows) % 2 === 0) {
                for ($i = 0; $i < count($rows) - 1; $i = $i + 2) {
                    $rowSegments[] = $rows[$i] . "\n" . $rows[$i + 1];
                }
            }

            $segmentsSeats = [];

            $extraText = $this->re("/\n([ ]*[^\w\s][ ]*{$this->opt($this->t('Adults'))}.*(\n.*)+?)(?:\n[ ]{0,15}[^\w\s][ ]*\w+|\s*$)/u", $table[1]);
            $extraText = preg_replace("/\n\n[ ]{0,20}\d+[× ]+.+/us", '', $extraText);
            $headers = $this->TableHeadPos($this->inOneRow($extraText));

            if (count($headers) > 0) {
                $extraTable = $this->SplitCols($extraText, [0, ($headers[1] < 15) ? $headers[2] ?? 0 : $headers[1]], false);

                $travellersText = trim(preg_replace("/^\s*.+/", '', $extraTable[0]));
                // delete header "Children"
                $travellersText = preg_replace("/^.*{$this->opt($this->t('Children'))}.*$/mu", "\n\n", $travellersText);

                if (empty($travellersText)) {
                    $travellers = [];
                } elseif (preg_match($pattern = "/\n[ ]*\d{1,2}\.\d{1,2}\.\d{2,4}$/m", $travellersText)) {
                    $travellers = array_filter(array_map('trim', preg_replace('/\s+/', ' ', preg_split($pattern, $travellersText))));
                } else {
                    $travellers = preg_split("/(\s*\n{2,}\s*)+/", $travellersText);
                }

                foreach ($travellers as &$traveller) {
                    // delete date of birth
                    $traveller = preg_replace('/\d+.\d+.\d{4}/', '', $traveller);
                    $traveller = preg_replace('/\s+/', ' ', $traveller);
                }
                unset($traveller);

                $seatsText = trim(preg_replace("/^\s*.+/", '', $extraTable[1]));
                $seatRows = empty($seatsText) ? [] : preg_split("/(\s*[\+\n]+\s*)+/", $seatsText);

                foreach ($seatRows as $st) {
                    if (count($rowSegments) == 1) {
                        if (preg_match("/^[A-Z\d]{1,4}$/", $st)) {
                            $segmentsSeats[0]['seats'][] = $st;
                        } elseif (preg_match("/^(\d+)[ ]*[^\w\s]+[ ]*([A-Z\d]{1,4})$/", $st, $m)) {
                            $segmentsSeats[0]['car'][] = $m[1];
                            $segmentsSeats[0]['seats'][] = $m[2];
                        }
                    } else {
                        $seatTabs = $this->SplitCols($st, $this->TableHeadPos($this->inOneRow($st)), false);

                        if (count($seatTabs) == count($rowSegments)) {
                            foreach ($seatTabs as $sti => $seattab) {
                                if (preg_match("/^\s*[A-Z\d]{1,4}\s*$/", $seattab)) {
                                    $segmentsSeats[$sti]['seats'][] = trim($seattab);
                                }
                            }
                        }
                    }
                }
            }

            $regexp = "/^\s*(?<dtime>{$timeFormat}).*?[ ]{2,}(?<dstation>[\s\S]+)\n\s*(?<type>{$this->opt($this->t('Route'))}|{$this->opt($this->t('Bus'))}|{$this->opt($this->t('Train'))})[ \[]+(?<number>[A-Z]*\d+[a-zA-Z]?)[\]\s]+(?<info>[\s\S]+?)\n[ ]*(?<atime>{$timeFormat}).*?[ ]{2,}(?<astation>[\s\S]+)/u";

            $regexp2 = "/^\s*(?<dtime>{$timeFormat}).*?[ ]{2,}(?<dstation>[\s\S]+)\n\s*[ ]+[^\s\w\.\,\-][ ]{0,3}(?<number>[A-Z]{0,3}\d+[a-zA-Z]?)[ ]{0,3}[^\s\w\.\,\-](?:[ ]{0,23}\S.+|[ ]*\n)\s+(?<info>[\s\S]+?)\n[ ]*(?<atime>{$timeFormat}).*?[ ]{2,}(?<astation>[\s\S]+)/u";

            foreach ($rowSegments as $i => $sText) {
                if (isset($rowSegments[$i + 1])) {
                    $sText = preg_replace("/\n[ ]{15,}\S.+\s*$/", '', $sText);
                }

                if (preg_match($regexp, $sText, $segMatch) || preg_match($regexp2, $sText, $segMatch)) {
                    $segMatch['dDate'] = $segMatch['aDate'] = null;

                    if (preg_match("/^(.+\n)[ ]{0,10}([[:alpha:]]+[\.]?[ ](?:de[ ])?\d{1,2}[\.]?|\d{1,2}[\.]?[ ](?:de[ ])?[[:alpha:]]+[\.]?)([ ]{2,}[\s\S]*)/u", $segMatch['dstation'], $m)) {
                        $segMatch['dDate'] = $m[2];
                        $segMatch['dstation'] = $m[1] . str_pad('', strlen($m[2]), ' ') . $m[3];
                    }

                    if (preg_match("/(?:^|\n)[ ]{0,10}([[:alpha:]]+[\.]?[ ](?:de[ ])?\d{1,2}[\.]?|\d{1,2}[\.]?[ ](?:de[ ])?[[:alpha:]]+[\.]?)((?:[ ]{2,}.*)?(?:\n[ ]{20}[\S\s]+)?\s*)$/u", $segMatch['info'], $m)) {
                        $segMatch['aDate'] = $m[1];
                        $segMatch['astation'] = trim($m[2]) . "\n" . $segMatch['astation'];
                    }

                    $segMatch['dstationAddress'] = $segMatch['astationAddress'] = null;

                    if (preg_match($pattern = "/^\s*(.+(?:\s*\n[ ]*\w.+)*)\s*\n[ ]*(?:\(FlixTrain\)\s+)?[^\w\s][ ]*([\[(]?[ ]?\w.+(?:\s*\n[ ]*\w.+)*)(?:\s*\n[ ]*[^\w\s][ ]*[\S\s]*)?\s*$/u", $segMatch['dstation'], $m)) {
                        $segMatch['dstation'] = $m[1];
                        $segMatch['dstationAddress'] = preg_replace('/^([\s\S]{3,}?)(?:[ ]*\n[ ]*){3,}\S.*$/', '$1', $m[2]); // remove garbage on bottom
                    }

                    if (preg_match($pattern, $segMatch['astation'], $m)) {
                        $segMatch['astation'] = $m[1];
                        $segMatch['astationAddress'] = $m[2];
                    }

                    if (!empty($sDate)) {
                        $fromDate = strtotime($this->normalizeTime($segMatch['dtime']), $this->normalizeDate($sDate));
                        $toDate = strtotime($this->normalizeTime($segMatch['atime']), $this->normalizeDate($sDate));

                        if (!empty($segMatch['dDate'])) {
                            $year = $this->re("/\b(\d{4})\b/", $sDate);
                            $segMatch['dDate'] .= ' ' . $year;
                            $fromDate1 = strtotime($this->normalizeTime($segMatch['dtime']), $this->normalizeDate($segMatch['dDate']));

                            if ($fromDate1 - $fromDate < 0) {
                                $fromDate1 = strtotime("+1 year", $fromDate1);

                                if ($fromDate1 - $fromDate < 0 && $fromDate1 - $fromDate > 60 * 60 * 24 * 5) {
                                    $fromDate1 = null;
                                }
                            }
                            $fromDate = $fromDate1;
                        }

                        if (!empty($segMatch['aDate'])) {
                            $year = $this->re("/\b(\d{4})\b/", $sDate);
                            $segMatch['aDate'] .= ' ' . $year;
                            $toDate1 = strtotime($this->normalizeTime($segMatch['atime']), $this->normalizeDate($segMatch['aDate']));

                            if ($toDate1 - $toDate < 0) {
                                $toDate1 = strtotime("+1 year", $toDate1);

                                if ($toDate1 - $toDate < 0 && $toDate1 - $toDate > 60 * 60 * 24 * 5) {
                                    $toDate1 = null;
                                }
                            }
                            $toDate = $toDate1;
                        }
                    }

                    if (!isset($segMatch['type'])
                        || preg_match("/^\s*(?:{$this->opt($this->t('Bus'))})/iu", $segMatch['type'])
                    ) {
                        $segMatch['type'] = 'bus';
                    } elseif (preg_match("/^\s*(?:{$this->opt($this->t('Train'))})/iu", $segMatch['type'])) {
                        $segMatch['type'] = 'train';
                    }
                    $segment = [
                        'number'       => $segMatch['number'],
                        'type'         => $segMatch['type'],
                        'from'         => $this->niceStationName($segMatch['dstation']),
                        'fromAddress'  => $this->niceStationName($segMatch['dstationAddress'] ?? ''),
                        'fromDateTime' => $fromDate,
                        'to'           => $this->niceStationName($segMatch['astation']),
                        'toAddress'    => $this->niceStationName($segMatch['astationAddress'] ?? ''),
                        'toDateTime'   => $toDate,
                    ];

                    if (isset($segmentsSeats[$i], $segmentsSeats[$i]['seats'])) {
                        $segment['seats'] = $segmentsSeats[$i]['seats'];

                        if (isset($segmentsSeats[$i], $segmentsSeats[$i]['car'])) {
                            $segment['car'] = $segmentsSeats[$i]['car'];
                        }
                    }
                }

                if (empty($segment)) {
                    $this->logger->error('segment not parse');
                    $email->add()->bus();

                    continue;
                }

                $trText = $this->re("/\n[ ]+{$this->opt($this->t('Passport type'))}[ ]+{$this->opt($this->t('Passport number'))}\s*\n(([ ]*[[:alpha:]][[:alpha:] \-]+[ ]{2,}\w{2,4}[ ]{2,}\d{5,}\s*\n+)+)/u", $datas[1] ?? '');

                if (!empty($trText)) {
                    $travellers = preg_replace("/^[ ]{0,10}([[:alpha:]][[:alpha:] \-]+?)[ ]{2,}.*/mu", "$1", array_filter(preg_split("/\s*\n+\s*/", $trText)));
                }

                if (empty($this->travellers) && !empty($travellers)) {
                    $this->travellers = $travellers;
                }

                $foundItinerary = false;

                foreach ($email->getItineraries() as $gt) {
                    /** @var \AwardWallet\Schema\Parser\Common\Bus $gt */
                    if ($gt->getType() === $segment['type'] && !empty($gt->getConfirmationNumbers())
                        && in_array($confirmation, $gt->getConfirmationNumbers()[0])
                    ) {
                        $s = $gt->addSegment();
                        $foundItinerary = true;

                        break;
                    }
                }

                if ($foundItinerary === false) {
                    if ($segment['type'] == 'train') {
                        $t = $email->add()->train();

                        $t->general()
                            ->confirmation($confirmation, $confDesc)
                            ->travellers($travellers ?? []);

                        $s = $t->addSegment();
                    } else {
                        $b = $email->add()->bus();

                        $b->general()
                            ->confirmation($confirmation, $confDesc);

                        $s = $b->addSegment();
                    }

                    if ($totalAmount !== null) {
                        $email->price()
                            ->currency($currency)
                            ->total($totalAmount);
                    }
                }

                $s->departure()
                    ->date($segment['fromDateTime'])
                    ->name($segment['from']);

                if (!empty($segment['fromAddress'])) {
                    $s->departure()
                        ->address($segment['fromAddress']);
                }
                $s->arrival()
                    ->date($segment['toDateTime'])
                    ->name($segment['to']);

                if (!empty($segment['toAddress'])) {
                    $s->arrival()
                        ->address($segment['toAddress']);
                }

                $s->extra()
                    ->number($segment['number']);

                if (!empty($segment['car'])) {
                    $s->extra()
                        ->car($segment['car'][0]);
                }

                if (!empty($segment['seats'])) {
                    $s->extra()
                        ->seats($segment['seats']);
                }
            }
        }
    }

    private function parsePdfInvoice(string $text, string $pdfName): bool
    {
        // collect reservation confirmation for cancelled reservations
        $cancelled = false;

        if ($this->strposAll($text, $this->t('Cancellation invoices for your booking'))) {
            $cancelled = true;
            $this->cancelInfo['confirmation']['number'] = $this->re("/\-(\d+)\.pdf$/", $pdfName);
        }

        // collect reservation confirmation
        if (!$cancelled && preg_match("/(?<desc>{$this->opt($this->t('Booking number:'))})?[ ]+\#(?<confNumber>\d+)[ ]*$/um", $text, $m)) {
            $this->invoicesInfo['confirmation']['number'] = $m['confNumber'];

            // if description is at beginning of text and separately from confNumber
            if (empty($m['desc'])) {
                $part = substr($text, 0, 300);
                $m['desc'] = $this->re("/({$this->opt($this->t('Booking number:'))})/", $part);
            }

            if (!empty($m['desc'])) {
                $this->invoicesInfo['confirmation']['desc'] = preg_replace("/\s+/", ' ', trim($m['desc'], ': '));
            } else {
                $this->invoicesInfo['confirmation']['desc'] = null;
            }
        }

        if (empty($this->invoicesInfo['confirmation']['number'])) {
            $this->invoicesInfo['confirmation']['number'] = $this->re("/\-(\d+)\.pdf$/", $pdfName);
        }

        // trim pdf, removing booking (save only invoices)
        $text = $this->re("/^.*?({$this->opt($this->t('Tickets and invoices for your booking'))}.+)$/su", $text);

        // split invoices into paymentblocks
        $paymentblocks = $this->split("/\n((?:.*{$this->opt($this->t('Cancellation'))}.*\n+)?.*[ ]{5,}{$this->opt($this->t('Invoice'))}[\s\S]*?\#[\w\-]{5,})/u", $text);

        $total = 0;
        $changeTotal = false;
        $currency = null;
        $taxes = [];
        $paidSeg = [];
        $travellers = [];

        foreach ($paymentblocks as $block) {
            $isTicket = false;

            // it is ticket, if traveller in block
            if (preg_match("/(?:\#[\w\-\/]+|{$this->opt($this->t('Adults'))}[ ]+.*?|{$this->opt($this->t('Child'))}[ ]+.*?)[ ]{5,}(?:{$this->opt(['Miss', 'Mrs', 'Mr', 'Ms'])}\.?[ ]*)?([[:alpha:]][-.\/\'’[:alpha:]\s]*[[:alpha:]])[ ]*\n/iu", $block, $m)) {
                $traveller = preg_replace("/\s+/", ' ', $m[1]);
                $traveller = preg_replace("/\s*(?:{$this->opt($this->t('Invoice'))}|{$this->opt($this->t('Cancellation'))})/", '', $traveller);

                if (!in_array($traveller, $travellers)) {
                    $travellers[] = $traveller;
                }

                $isTicket = true;
            }

            // collect itinerary type, department and arrival info
            if ($isTicket && preg_match($matchPattern = "/\n[ ]{0,10}\S+\d{4}.*(?:[ ]{4,}|\n)(?:.*\n)+?[ ]{0,10}\S+\d{4}.*(?:[ ]{4,}|\n)(?:.*\n)+?(\n\n|$)/", $block, $match)) {
                $routeText = preg_replace("#^(.{15,}?)[ ]{4,}.*#m", '$1', $match[0]);

                if (preg_match($segPattern = "/\s*(?<dDate>.*?)[\, ]+(?<dTime>{$this->patterns['time']}).*\s+(?<dName>[\s\S]+?)\s*\n\s*(?<aDate>.*?)[\, ]+(?<aTime>{$this->patterns['time']}).*(?:\s+[\d\:\+\-\(\)\s]+)?\s+(?<aName>[\s\S]+?)(?:\n\n|$)/", $routeText, $sp)) {
                    $curPaidSeg = [
                        'from'         => $this->niceStationName($sp['dName']),
                        'fromDateTime' => strtotime($this->normalizeTime($sp['dTime']), $this->normalizeDate($sp['dDate'])),
                        'to'           => $this->niceStationName($sp['aName']),
                        'toDateTime'   => strtotime($this->normalizeTime($sp['aTime']), $this->normalizeDate($sp['aDate'])),
                    ];

                    // collect itinerary type
                    if (
                        stripos($block, 'FlixTrain') !== false
                        || ($this->striposAll($curPaidSeg['from'], $this->t('(train)'))
                        && $this->striposAll($curPaidSeg['to'], $this->t('(train)')))
                    ) {
                        $ticketType = 'Train connection';
                    } else {
                        $ticketType = 'Bus connection';
                    }

                    $curPaidSeg['type'] = $ticketType;
                    $paidSeg[] = $curPaidSeg;
                }
            }

            // collect total and taxes (excluded value added tax)
            if (!$cancelled && isset($total)
                && (preg_match("/[ ]{5,}{$this->opt($this->t('Total'))}[ ]{5,}(.+)/", $block, $match)
                    || preg_match("/[ ]{5,}{$this->opt($this->t('Total'))}\n.{50,}[ ]{5,}(.+)/", $block, $match))
                && (preg_match("/^\s*(?<curr>[^\d\s]{1,5})\s*(?<amount>\d[\d\.\, ]*)\s*$/u", $match[1], $m)
                    || preg_match("/^\s*(?<amount>\d[\d\.\, ]*)\s*(?<curr>[^\d\s]{1,5})\s*$/u", $match[1], $m))) {
                $currency = $currency ?? $this->currency($m['curr']);
                $m['amount'] = PriceHelper::parse($m['amount'], $currency);

                if ($this->currency($m['curr']) === $currency) {
                    $total += $m['amount'];
                } else {
                    unset($total);
                }
                $changeTotal = true;

                if (
                    isset($taxes) && !$isTicket
                    && preg_match("/^\s*(?:flixbus)?[ ]*\d?[ ](.+)[ ]{$this->opt($this->t('Invoice'))}/iu", $block, $m2)
                ) {
                    $foundTax = false;
                    $curTax = trim(preg_replace("/\s+/", ' ', $m2[1]));

                    $curTaxSuffix = '';

                    if (
                        preg_match("/{$this->opt($this->t('Invoice'))}.*\n[ ]*\d?[ ]+(.+?)[ ]{5,}/i", $block, $m2)
                    ) {
                        $curTaxSuffix = trim(preg_replace("/\s+/", ' ', $m2[1]));
                    }
                    $curTax = trim(!empty($curTax) ? $curTax . ' ' . $curTaxSuffix : $curTaxSuffix);

                    foreach ($taxes as $i => $tax) {
                        if ($tax["name"] == $curTax) {
                            $taxes[$i]["value"] += $m['amount'];
                            $foundTax = true;

                            break;
                        }
                    }

                    if ($foundTax === false) {
                        $taxes[] = ["name" => $curTax, "value" => $m['amount']];
                    }
                }
            }
        }

        if ($cancelled) {
            $this->cancelInfo['segments'] = array_unique($paidSeg, SORT_REGULAR);
            $this->cancelInfo['travellers'] = $travellers;

            return true;
        }

        if (empty($paidSeg)) {
            return true;
        }

        $this->invoicesInfo['segments'] = array_unique($paidSeg, SORT_REGULAR);
        $this->invoicesInfo['travellers'] = $travellers;

        if (isset($total) && $changeTotal && $currency) {
            $this->invoicesInfo['total'] = $total;
            $this->invoicesInfo['currency'] = $currency;
        }

        if (!empty($taxes) && !empty($currency)) {
            foreach ($taxes as $tax) {
                $this->invoicesInfo['taxes'][] = ['feeName' => empty($tax['name']) ? 'Unknown Fee' : $tax['name'], 'amount' => $tax['value']];
            }
        }

        return true;
    }

    private function itinerariesUnion(Email $email, array $bookingInfo, array $invoicesInfo, array $cancelInfo): bool
    {
        // save info from cancellation invoices
        $b = null;
        $t = null;

        // save segments from cancellation invoices
        foreach ($cancelInfo['segments'] as $seg) {
            // add itineraries and segments
            if ($seg['type'] == 'Train connection' || $this->strposAll($seg['type'], $this->t('Train connection'))) {
                if (!isset($t)) {
                    $t = $email->add()->train();
                    $t->general()->cancelled();
                }

                $s = $t->addSegment();
            } else {
                if (!isset($b)) {
                    $b = $email->add()->bus();
                    $b->general()->cancelled();
                }

                $s = $b->addSegment();
            }

            // add department and arrival info
            $s->departure()
                ->name($seg['from'])
                ->date($seg['fromDateTime']);

            if ($seg['fromDateTime'] < $seg['toDateTime']) {
                $s->arrival()
                    ->date($seg['toDateTime']);
            } else {
                $s->arrival()
                    ->date(strtotime('+1 day', $seg['toDateTime']));
            }

            $s->arrival()
                ->name($seg['to']);

            // add extra
            if (!empty($seg['number'])) {
                $s->extra()
                    ->number($seg['number']);
            } elseif (stripos($s->getId(), 'train') === 0) {
                $s->extra()->noNumber();
            }

            if (!empty($seg['seats'])) {
                $s->extra()->seats($seg['seats']);

                if (!empty($seg['car'])) {
                    $s->extra()->car($seg['car']);
                }
            }
        }

        // save reservation confirmation from cancellation invoices
        if (isset($t)) {
            $t->general()
                ->confirmation($cancelInfo['confirmation']['number']);
        }

        if (isset($b)) {
            $b->general()
                ->confirmation($cancelInfo['confirmation']['number']);
        }

        // save travellers from cancellation invoices
        if (isset($t) && !empty($cancelInfo['travellers'])) {
            $t->general()
                ->travellers($cancelInfo['travellers']);
        }

        if (isset($b) && !empty($cancelInfo['travellers'])) {
            $b->general()
                ->travellers($cancelInfo['travellers']);
        }

        // save info from booking and invoices
        $unionInfo = [
            'confirmation' => [
                'number' => null,
                'desc'   => null,
            ],
            'segments'     => [],
            'travellers'   => [],
            'currency'     => null,
            'total'        => null,
            'taxes'        => [],
        ];

        // reset itineraries for confirmed reservations
        $b = null;
        $t = null;

        // union reservation info
        $unionInfo['confirmation']['number'] = $bookingInfo['confirmation']['number']
            ?? $invoicesInfo['confirmation']['number'];
        $unionInfo['confirmation']['desc'] = $bookingInfo['confirmation']['desc']
            ?? $invoicesInfo['confirmation']['desc'];

        // union segments: if empty segments in bookingInfo or invoicesInfo
        if (!empty($bookingInfo['segments'])) {
            $unionInfo['segments'] = $bookingInfo['segments'];
        } elseif (!empty($invoicesInfo['segments'])) {
            $unionInfo['segments'] = $invoicesInfo['segments'];
        }

        // union segments: correct dates and itinerary types
        if (!empty($bookingInfo['segments']) && !empty($invoicesInfo['segments'])) {
            $unionInfo['segments'] = $bookingInfo['segments'];

            foreach ($unionInfo['segments'] as &$seg) {
                if (!empty($seg['from']) && !empty($seg['to'])) {
                    $sF = str_replace(' ', '', $seg['from']);
                    $sT = str_replace(' ', '', $seg['to']);

                    foreach ($invoicesInfo['segments'] as $pSeg) {
                        if (!empty($pSeg['from']) && !empty($pSeg['to'])) {
                            $psF = str_replace(' ', '', $pSeg['from']);
                            $psT = str_replace(' ', '', $pSeg['to']);

                            if (($psF === $sF || strncasecmp($psF, $sF, strlen($psF)) === 0)
                                && ($psT === $sT || strncasecmp($psT, $sT, strlen($psT)) === 0)
                            ) {
                                $seg['type'] = $seg['type'] ?? $pSeg['type'];

                                if (!empty($pSeg['fromDateTime'])) {
                                    $seg['fromDateTime'] = $pSeg['fromDateTime'];
                                }

                                if (!empty($pSeg['toDateTime'])) {
                                    $seg['toDateTime'] = $pSeg['toDateTime'];
                                }

                                break;
                            }
                        }
                    }
                }
            }
            unset($seg);
        }

        // union travellers
        if (!empty($invoicesInfo['travellers'])) {
            $unionInfo['travellers'] = $invoicesInfo['travellers'];
        } elseif (!empty($bookingInfo['travellers'])) {
            $unionInfo['travellers'] = $bookingInfo['travellers'];
        }

        // union price
        // if currencies are different
        $isDifferentCurrency = false;

        if (!empty($bookingInfo['currency']) && !empty($invoicesInfo['currency']) && $bookingInfo['currency'] !== $invoicesInfo['currency']) {
            $isDifferentCurrency = true;
        }

        $unionInfo['total'] = $bookingInfo['total'] ?? $invoicesInfo['total'];
        $unionInfo['currency'] = $bookingInfo['currency'] ?? $invoicesInfo['currency'];
        $unionInfo['taxes'] = $invoicesInfo['taxes'];

        // save segments
        foreach ($unionInfo['segments'] as $seg) {
            // add itineraries and segments
            if ($seg['type'] == 'Train connection' || $this->strposAll($seg['type'], $this->t('Train connection'))) {
                if (!isset($t)) {
                    $t = $email->add()->train();
                }

                $s = $t->addSegment();
            } elseif ($seg['type'] == 'Bus connection' || $this->strposAll($seg['type'], $this->t('Bus connection'))) {
                if (!isset($b)) {
                    $b = $email->add()->bus();
                }

                $s = $b->addSegment();
            } else {
                $this->logger->error("Unknown segment type {$seg['type']}");

                return false;
            }

            // add department and arrival info
            $s->departure()
                ->name($seg['from'])
                ->date($seg['fromDateTime']);

            if ($seg['fromDateTime'] < $seg['toDateTime']) {
                $s->arrival()
                    ->date($seg['toDateTime']);
            } else {
                $s->arrival()
                    ->date(strtotime('+1 day', $seg['toDateTime']));
            }

            $s->arrival()
                ->name($seg['to']);

            // add extra
            if (!empty($seg['number'])) {
                $s->extra()
                    ->number($seg['number']);
            } elseif (stripos($s->getId(), 'train') === 0) {
                $s->extra()->noNumber();
            }

            if (!empty($seg['seats'])) {
                $s->extra()->seats($seg['seats']);

                if (!empty($seg['car'])) {
                    $s->extra()->car($seg['car']);
                }
            }
        }

        // save reservation confirmation
        if (isset($t)) {
            $t->general()
                ->confirmation($unionInfo['confirmation']['number'], trim($unionInfo['confirmation']['desc'], ':'));
        }

        if (isset($b)) {
            $b->general()
                ->confirmation($unionInfo['confirmation']['number'], trim($unionInfo['confirmation']['desc'], ':'));
        }

        // save travellers
        if (isset($t) && !empty($unionInfo['travellers'])) {
            $t->general()
                ->travellers($unionInfo['travellers']);
        }

        if (isset($b) && !empty($unionInfo['travellers'])) {
            $b->general()
                ->travellers($unionInfo['travellers']);
        }

        // price is not saved if currencies in bookingInfo and invoicesInfo are different
        if ($isDifferentCurrency) {
            return true;
        }

        // save pricing details
        if (!empty($unionInfo['currency'])) {
            $email->price()
                ->currency($unionInfo['currency']);
        }

        if (isset($unionInfo['total'])) {
            $email->price()
                ->total($unionInfo['total']);
        }

        if (!empty($unionInfo['taxes'])) {
            foreach ($unionInfo['taxes'] as $tax) {
                $email->price()
                    ->fee($tax['feeName'], $tax['amount']);
            }
        }

        return true;
    }

    private function niceStationName(string $name): ?string
    {
        if (empty($name)) {
            return null;
        }
        $name = preg_replace("#(\S+[\s\S]+?)\n\s*.*{$this->opt($this->t('Please note'))}[\s\S]+#", '$1', trim($name));
        $name = preg_replace("#(\S+[\s\S]+\([\s\S]+\))\s+[\s\S]+#", '$1', $name);
        $name = preg_replace("#\s*\n\s*#", ' ', $name);
        $name = preg_replace("#\*.+#", '', $name);
        $name = preg_replace("# \(FlixTrain\)\s*$#", '', $name);

        return $name;
    }

    private function parseHtml(Email $email): void
    {
        if (empty($this->http->FindSingleNode("(//*[{$this->contains($this->t('includes the following trips:'))}])[1]"))) {
            return;
        }
        $b = $email->add()->bus();

        // General
        $b->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->contains($this->t('includes the following trips:'))}][1]", null, true, "#\([^\d\(\)]{1,5}\s*([A-Z\d]{5,})\s*\)#"));

        // Segments
        $xpath = "//text()[{$this->starts($this->t('Line'))}]";
        $nodes = $this->http->XPath->query($xpath);

        //62593782
        if ($nodes->length > 0
            && empty($this->re("#^\s*(\D+ → \D+)\n#ms", $nodes->item(0)->nodeValue))
            && empty($this->re("#(\D+ → \D+)$#ms", $nodes->item(0)->nodeValue))
        ) {
            $xpath = "//text()[{$this->starts($this->t('Line'))}]/ancestor::div[1]";
            $nodes = $this->http->XPath->query($xpath);
        }

        foreach ($nodes as $root) {
            $s = $b->addSegment();

            $text = $root->nodeValue;

            if (preg_match("/{$this->opt($this->t('Line'))} (?<number>\w+?)\W.+? (?<date>\S*(?:\d{4}|\d{2}[.\/]\d{2}[.\/]\d{2}\b))\S* (?<time>{$this->patterns['time']})\s+(?<from>.+) → (?<to>\D+)\d?/", $text, $m)) {
                $s->departure()
                    ->name($m['from'])
                    ->date(strtotime($this->normalizeTime($m['time']), $this->normalizeDate($m['date'])));
                $s->arrival()
                    ->name($m['to'])
                    ->noDate();

                $s->extra()
                    ->number($m['number']);

                $seats = $this->http->FindSingleNode("./following::text()[normalize-space()][1]", $root, true, "#:\s*(\d{1,2}[A-Z]((?:,[ ]{0,2})\d{1,2}[A-Z])*)\s*$#");

                if (empty($seats)) {
                    $seats = $this->re("/{$this->opt($this->t('Seat'))}\:\s+\D+\d?\:\s+(\d+[A-Z]{1})\s+/", $text);
                }

                if (!empty($seats)) {
                    $s->extra()
                        ->seats(array_map('trim', explode(",", $seats)));
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

    private function starts($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'starts-with(normalize-space(.),"' . $s . '")'; }, $field)) . ')';
    }

    private function contains($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'contains(normalize-space(.),"' . $s . '")'; }, $field)) . ')';
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            // diumenge, 07 d’ag. de 2022
            // Sonntag, 07. Aug. 2022
            // terça-feira, 01 de nov. de 2022
            // 16. Okt. 2022
            // вівторок, 29 лист. 2022 р.
            // søndag den 23. okt. 2022
            // пятница, 14 апр. 2023 г
            // subota, 24. lip 2023.
            "/^\s*(?:[-[:alpha:] ]+[,\s]+)?(\d{1,2})\.?\s+(?:d’|d'|de\s+)?([[:alpha:]]+)\.?(?:\s+de)?\s+(\d{4})\s*(?:[рг]?\.)?\s*$/u",

            '/^\s*(\d{1,2})\.(\d{2})\.(\d{2})\s*$/', // 11.08.17
            '/^\s*(\d{1,2})\.(\d{2})\.(\d{4})\s*$/', // 11.01.2020

            // 2022. nov. 26., szombat
            '/^\s*(\d{4})\.\s*([[:alpha:]]+)\.\s*(\d{1,2})\.\s*,\s*[-[:alpha:]]+\s*$/u',
            // nov. 27. 2022
            '/^\s*([-[:alpha:]]+)\.\s*(\d{1,2})\.\s*(\d{4})\s*$/u',
        ];
        $out = [
            '$1 $2 $3',

            '$1.$2.20$3',
            '$1.$2.$3',

            '$3 $2 $1',
            '$2 $1 $3',
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }

    private function normalizeTime(?string $s): string
    {
        $s = preg_replace([
            '/([AaPp])\.[ ]*([Mm])\.?/', // 2:04 p. m.    ->    2:04 pm
            '/(\d)[.：](\d)/u', // 17.25    ->    17:25
        ], [
            '$1$2',
            '$1:$2',
        ], $s);

        return $s;
    }

    private function TableHeadPos($row): array
    {
        $head = array_filter(array_map('trim', explode("%", preg_replace("#\s{2,}#", "%", $row))));
        $pos = [];
        $lastpos = 0;

        foreach ($head as $word) {
            $pos[] = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $lastpos = mb_strpos($row, $word, $lastpos, 'UTF-8') + mb_strlen($word, 'UTF-8');
        }

        return $pos;
    }

    private function SplitCols($text, $pos = false, $trim = true): array
    {
        $cols = [];
        $rows = explode("\n", $text);

        if (!$pos) {
            $pos = $this->TableHeadPos($rows[0]);
        }
        arsort($pos);

        foreach ($rows as $row) {
            foreach ($pos as $k=>$p) {
                if ($trim === true) {
                    $cols[$k][] = trim(mb_substr($row, $p, null, 'UTF-8'));
                } else {
                    $cols[$k][] = mb_substr($row, $p, null, 'UTF-8');
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

    private function split($re, $text): array
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

    private function currency($s): ?string
    {
        if ($code = $this->re("#^\s*([[:alpha:]]{3})\s*$#u", $s)) {
            return $code;
        }
        $sym = [
            '$'   => 'USD',
            '€'   => 'EUR',
            '£'   => 'GBP',
            'zł'  => 'PLN',
            '₽'   => 'RUB',
            'Kč'  => 'CZK',
            'R$'  => 'BRL',
            '₹'   => 'INR',
        ];

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return null;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function strposAll($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (strpos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && strpos($text, $needle) !== false) {
            return true;
        }

        return false;
    }

    private function striposAll($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (mb_stripos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && mb_stripos($text, $needle) !== false) {
            return true;
        }

        return false;
    }
}
