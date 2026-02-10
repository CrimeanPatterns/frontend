<?php

namespace AwardWallet\Engine\fourchette\Email;

// TODO: delete what not use
use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class Confirmation2023 extends \TAccountChecker
{
    public $mailFiles = "fourchette/it-559002913.eml, fourchette/it-570973792-it.eml, fourchette/it-571038815.eml, fourchette/it-894165679-pt.eml, fourchette/it-895835152-pt-cancelled.eml, fourchette/it-895930474-fr.eml, fourchette/it-899179404-es.eml, fourchette/it-921127197.eml, fourchette/it-922748903.eml, fourchette/it-924115105.eml, fourchette/it-925072899.eml, fourchette/it-925836787.eml, fourchette/it-926031467.eml, fourchette/it-926196984.eml, fourchette/it-926405127.eml";

    private $detectSubject = [
        // en
        'Your reservation confirmation for',
        'Your booking confirmation for',
        'Reminder: You have a booking tomorrow at',
        'Booking canceled:',
        'Booking cancelled:',
        // fr
        'Votre confirmation de réservation auprès de',
        'Annulation de votre réservation chez',
        'Rappel : vous avez une réservation au restaurant',
        // it
        'Conferma della prenotazione per',
        'La conferma della tua prenotazione per',
        'Prenotazione cancellata:',
        // es
        'Tu confirmación de reserva en',
        'Recuerda: tienes una reserva mañana en',
        'Reserva cancelada:', // cancelled
        // de
        'Deine Reservierungsbestätigung für',
        // pt
        'Confirmação da tua reserva no',
        'Confirmação da sua reserva no',
        'Lembrete: tens uma reserva para amanhã no',
        // sv
        'Deine Reservierungsbestätigung für',
        // nl
        'Je reserveringsbevestiging voor',
        'Bevestiging van je reservering bij',
    ];

    private $isCancelled = false;

    private $cancelledPhrases = [
        'en' => [
            ', you canceled your booking',
            ', you cancelled your booking',
            ', the restaurant has canceled your booking'
        ],
        'es' => [
            '. Has cancelado tu reserva',
        ],
        'pt' => [
            ', cancelaste a tua reserva',
            ', João cancelou a tua reserva',
        ],
        'it' => [
            ', hai cancellato la prenotazione',
        ],
        'fr' => [
            ', vous avez annulé votre réservation',
        ],
    ];

    private $notCancelledPhrases = [
        'en' => [
            ', your table is confirmed', ': your table is confirmed',
            ', get ready for your booking tomorrow', ': get ready for your booking tomorrow',
            ', get ready for your reservation tomorrow', ': get ready for your reservation tomorrow',
        ],
        'fr' => [
            ', votre table est confirmée', ': votre table est confirmée',
            ', votre réservation de demain approche',
        ],
        'it' => [
            ', il tuo tavolo è confermato', ': il tuo tavolo è confermato',
            ', preparati per la prenotazione di domani', ': preparati per la prenotazione di domani',
        ],
        'es' => [
            ', tu mesa está confirmada', ': tu mesa está confirmada',
            ', prepárate para tu reserva de mañana', ': prepárate para tu reserva de mañana',
        ],
        'de' => [
            ', dein Tisch ist bestätigt', ': dein Tisch ist bestätigt', ', Dein Tisch ist bestätigt.'
        ],
        'pt' => [
            ', a tua reserva está confirmada', ': a tua reserva está confirmada',
            ', a sua mesa está confirmada', ': a sua mesa está confirmada',
            ', a tua reserva é já amanhã', ': a tua reserva é já amanhã',
        ],
        'sv' => [
            'Din bokning är bekräftad',
        ],
        'nl' => [
            'je tafel is bevestigd',
            'je reservering is bevestigd'
        ],
    ];

    private $date;
    private $lang = '';
    private static $dictionary = [
        'en' => [
            'Hello ' => ['Hello ', 'Hi ', 'Sorry '],
            // "Your table will be booked for" => '', // Your table will be booked for 4h, between 20:30 and 00:30
            "You'll earn" => ['You\'ll earn', 'Don’t forget, you’ll receive'],
            // 'Cancel your reservation or contact the restaurant (' => '',
            'Reservation number:' => ['Booking number:', 'Reservation number:'],
            'statusPhrases' => [', your table is', ': your table is'],
            'statusVariants' => ['confirmed'],
        ],
        'fr' => [
            'Hello '                                              => 'Bonjour ',
            "Your table will be booked for"                       => ['Votre table sera réservée pour', 'Votre table sera réservée pendant'], // Votre table sera réservée pour 1h, entre 13:00 et 14:00
            "You'll earn"                                         => 'Après le repas, vous gagnerez',
            'Cancel your reservation or contact the restaurant (' => 'Si vous êtes en retard, annulez votre réservation ou contactez le restaurant (',
            'Reservation number:'                                 => ['Numéro de réservation :', 'Nº de réservation :'],
            'statusPhrases' => [', votre table est', ': votre table est'],
            'statusVariants' => ['confirmée'],
        ],
        'it' => [
            'Hello '                                              => 'Ciao ',
            "Your table will be booked for"                       => 'Il tavolo sarà prenotato per', // Il tavolo sarà prenotato per 1h30min, tra le ore 21:30 e le ore 23:00
            "You'll earn"                                         => ['Dopo il pasto guadagnerai', 'Non dimenticare che riceverai'],
            'Cancel your reservation or contact the restaurant (' => 'Cancella la prenotazione oppure contatta il ristorante (',
            'Reservation number:'                                 => 'Numero di prenotazione:',
            'statusPhrases' => [', il tuo tavolo è', ': il tuo tavolo è'],
            'statusVariants' => ['confermato'],
        ],
        'es' => [
            'Hello '                                              => ['Hola,', 'Hola '],
            "Your table will be booked for"                       => ['Tu mesa se reservará durante', 'Tu mesa estará reservada durante'], // Tu mesa se reservará durante 1h30min, entre las 14:00 y las 15:30
            "You'll earn"                                         => 'Ganarás',
            'Cancel your reservation or contact the restaurant (' => 'Cancela tu reserva o ponte en contacto con el restaurante (',
            'Reservation number:'                                 => 'Número de reserva:',
            'statusPhrases' => [', tu mesa está', ': tu mesa está'],
            'statusVariants' => ['confirmada'],
        ],
        'de' => [
            'Hello '                                              => 'Hallo ',
            "Your table will be booked for"                       => 'Der Tisch ist für', // Your table will be booked for 4h, between 20:30 and 00:30
            "You'll earn"                                         => 'Nach dem Essen erhältst du',
            'Cancel your reservation or contact the restaurant (' => 'Storniere die Reservierung oder kontaktiere das Restaurant (',
            'Reservation number:'                                 => 'Reservierungsnummer:',
            'statusPhrases' => [', dein Tisch ist', ': dein Tisch ist', ', Dein Tisch ist'],
            'statusVariants' => ['bestätigt'],
        ],
        'pt' => [
            'Hello '                                              => ['Olá,', 'Olá '],
            "Your table will be booked for"                       => ['A sua mesa ficará reservada durante', 'A duração da refeição será de'], // Your table will be booked for 4h, between 20:30 and 00:30
            "You'll earn"                                         => ['Lembra-te que vais ganhar', 'Vais ganhar', 'Irá ganhar'],
            'Cancel your reservation or contact the restaurant (' => 'Cancela a tua reserva ou contacta o restaurante (',
            'Reservation number:'                                 => 'Número da reserva:',
            'statusPhrases' => [', a sua mesa está', ': a sua mesa está'],
            'statusVariants' => ['confirmada'],
        ],
        'sv' => [
            'Hello '                                              => ['Hej '],
            // "Your table will be booked for"                       => " ", // Your table will be booked for 4h, between 20:30 and 00:30
            "You'll earn"                                         => ['Du får'],
            'Cancel your reservation or contact the restaurant (' => 'Avboka ditt bord eller kontakta restaurangen (',
            'Reservation number:'                                 => 'Bokningsnummer:',
            'statusPhrases' => ['Din bokning är'],
            'statusVariants' => ['bekräftad'],
        ],
        'nl' => [
            'Hello '                                              => ['Hallo '],
            // "Your table will be booked for"                       => " ", // Your table will be booked for 4h, between 20:30 and 00:30
            "You'll earn"                                         => ['Je verdient'],
            'Cancel your reservation or contact the restaurant (' => 'Annuleer je reservering of neem contact op met het restaurant (',
            'Reservation number:'                                 => 'Reserveringsnummer:',
            'statusPhrases' => ['je tafel is'],
            'statusVariants' => ['bevestigd'],
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match('/@(?:[-\w]+\.)?thefork(?:\.co)?\.[a-z]+$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || !preg_match('/\binfo@(?:(?:email|news)\.)?thefork(?:\.co)?\.[a-z]+$/i', rtrim($headers['from'], '> '))) {
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
        $href = ['.lafourchette.com/', '//clicksemail.thefork.'];

        if ($this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//text()[starts-with(normalize-space(),"©") and contains(normalize-space(),"LaFourchette SAS")]')->length === 0
        ) {
            return false;
        }

        return $this->assignLangCancelled() || $this->assignLang();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if (!$this->assignLangCancelled()) {
            $this->assignLang();
        }

        if (empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
            return $email;
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        $this->date = strtotime($parser->getDate());

        $this->parseEmailHtml($email);

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

    private function parseEmailHtml(Email $email): void
    {
        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm
            'phone' => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52  |  (+351) 21 342 09 07  |  713.680.2992
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $event = $email->add()->event();

        $event->type()->restaurant();

        $jsonText = $this->http->FindSingleNode("//script[contains(., 'schema.org')]");
        $json = json_decode($jsonText ?? '', true);

        if (!empty($json['reservationNumber'])) {
            $this->logger->info('Found SCRIPT(application/ld+json) in HTML!');
            // $this->logger->debug('$json = '.print_r( $json,true));

            // General
            $event->general()
                ->confirmation($json['reservationNumber'])
                ->traveller($json['underName']['name'], true)
                ->date(strtotime($this->re("/^(.+?)[\-+]\d{1,2}:\d{2}\s*$/", $json['bookingTime'])))
            ;

            if (array_key_exists('reservationStatus', $json) && preg_match("/^https?:\/\/schema\.org\/Confirmed$/i", $json['reservationStatus'])) {
                $event->general()->status('Confirmed');
            }

            // Place
            $eventName = $json['reservationFor']['name'];
            $address = implode(', ', array_diff_key($json['reservationFor']['address'], ['@type' => 1]));

            // Booking
            $dateTimeStart = strtotime($this->re("/^(.+?)[-+]\d{1,2}:\d{2}\s*$/", $json['startTime']));
            $guestCount = $json['partySize'];

            if (!empty($endTime) && !empty($dateTimeStart)) {
                $date = strtotime($endTime, $dateTimeStart);

                if ($date < $dateTimeStart) {
                    $date = strtotime("+1 day", $date);
                }

                if ($date > $dateTimeStart) {
                    $event->booked()
                        ->end($date);
                }
            } else {
                $event->booked()
                    ->noEnd();
            }
        } else {
            // General
            $statusTexts = array_filter($this->http->FindNodes("//text()[{$this->contains($this->t('statusPhrases'))}]", null, "/{$this->preg_implode($this->t('statusPhrases'))}[:\s]+({$this->preg_implode($this->t('statusVariants'))})(?:\s*[,.;:!?]|$)/i"));

            if (count(array_unique(array_map('mb_strtolower', $statusTexts))) === 1) {
                $status = array_shift($statusTexts);
                $event->general()->status($status);
            }

            $confirmation = $confirmationTitle = null;

            if (preg_match("/^({$this->preg_implode($this->t("Reservation number:"))})[:\s]*(\d{5,})\s*$/u", $this->http->FindSingleNode("//text()[{$this->starts($this->t("Reservation number:"))}]/ancestor::tr[1]"), $m)) {
                $confirmation = $m[2];
                $confirmationTitle = rtrim($m[1], ' :：');
            }

            if ($confirmation !== null && $confirmationTitle !== null){
                $event->general()->confirmation($confirmation, $confirmationTitle);
            } else {
                $event->general()->noConfirmation();
            }

            $event->general()
                ->traveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello '))}]", null,
                    true,
                    "/^\s*{$this->preg_implode($this->t('Hello '))}[,\s]*({$patterns['travellerName']})[.\s\-]*[,.:!] /u"), false);

            // Place
            $eventName = $this->http->FindSingleNode("//img[contains(@src, 'images/calendar')]/preceding::text()[normalize-space()][1]/ancestor::tr[1]");
            $address = $this->http->FindSingleNode("//img[contains(@src, 'images/pin')]/ancestor::tr[1]");

            // Booked
            $dateStart = $this->normalizeDate($this->http->FindSingleNode("//img[contains(@src,'images/calendar')]/ancestor::tr[1]"));
            $timeStart = $this->http->FindSingleNode("//img[contains(@src,'images/clock')]/ancestor::tr[1]", null, true, "/^{$patterns['time']}$/");
            $dateTimeStart = strtotime($timeStart, $dateStart);

            $guestCount = $this->http->FindSingleNode("//img[contains(@src,'images/group')]/ancestor::tr[1]", null, true, "/^\s*(\d{1,3})(?:\b|\D|$)/u");
        }

        if ($this->isCancelled) {
            $event->general()->cancelled();

            $xpath = "//*[ *[normalize-space()][1][self::h3] and *[normalize-space()][2][self::p or self::div] ]";
            $eventName = $this->http->FindSingleNode($xpath . "/*[normalize-space()][1]");
            $dateVal = $this->http->FindSingleNode($xpath . "/*[normalize-space()][2]");

            if (preg_match("/^(?<date>.{4,}?)[^[:alpha:]\d]+(?<time>{$patterns['time']})$/u", $dateVal, $m)) {
                // dom 06 abr  21:00
                $dateStart = $this->normalizeDate($m['date']);
                $timeStart = $m['time'];
                $dateTimeStart = strtotime($timeStart, $dateStart);
            }

            $guestCount = $this->http->FindSingleNode($xpath . "/*[normalize-space()][3]", null, true, "/^\s*(\d{1,3})(?:\b|\D|$)/u");

            if ($address) {
                $event->place()->address($address);
            }
        } else {
            $event->place()->address($address);
        }

        $event->place()->name($eventName);
        $event->booked()->start($dateTimeStart)->guests($guestCount);

        $event->place()
            ->phone($this->http->FindSingleNode("//text()[" . $this->eq($this->t("Cancel your reservation or contact the restaurant (")) . "]/ancestor::tr[1]",
                null, true, "/{$this->preg_implode($this->t("Cancel your reservation or contact the restaurant ("))}\s*({$patterns['phone']})\s*\)/"), true, true);

        $endTime = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your table will be booked for'))}]/ancestor::tr[1]", null, true, "/ \d{1,2}:\d{2}\D+ ({$patterns['time']})[,.;!\s]*$/");

        if (!empty($endTime) && !empty($dateTimeStart)) {
            $date = strtotime($endTime, $dateTimeStart);

            if ($date < $dateTimeStart) {
                $date = strtotime("+1 day", $date);
            }

            if ($date > $dateTimeStart) {
                $event->booked()
                    ->end($date);
            }
        } else {
            $event->booked()
                ->noEnd();
        }

        // Program
        $yums = $this->http->FindSingleNode("//text()[{$this->eq($this->t("You'll earn"))}]/following::text()[normalize-space()][1]", null, true, "/^\s*\d+\s*(?:Yums|loyalty points|punti fedeltà Yums)\s*$/iu");
        $event->program()->earnedAwards($yums, false, true);
    }

    private function assignLangCancelled(): bool
    {
        foreach ($this->cancelledPhrases as $lang => $detectBody) {
            if ($this->http->XPath->query("//*[{$this->contains($detectBody)}]")->length > 0) {
                $this->lang = $lang;
                $this->isCancelled = true;

                return true;
            }
        }

        return false;
    }

    private function assignLang(): bool
    {
        foreach ($this->notCancelledPhrases as $lang => $detectBody) {
            if ($this->http->XPath->query("//*[{$this->contains($detectBody)}]")->length > 0) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    // additional methods
    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function normalizeDate($date)
    {
        // $this->logger->debug('$date = ' . print_r($date, true));
        $year = date('Y', $this->date);

        if (empty($date) || empty($this->date)) {
            return null;
        }
        $in = [
            // quarta-feira, 26 de março 2025
            '/^\s*[-[:alpha:]]{2,}[,.\s]+(\d{1,2})[,.\s]+(?:de\s+)?([[:alpha:]]+)[,.\s]+(\d{2}|\d{4})[.\s]*$/u',
            // Mittwoch, 1. November
            '/^\s*([-[:alpha:]]{2,})[,.\s]+(\d{1,2})[,.\s]+(?:de\s+)?([[:alpha:]]+)[.\s]*$/iu',
            // Monday, October 30
            '/^\s*([-[:alpha:]]{2,})[,.\s]+([[:alpha:]]+)[,.\s]+(\d{1,2})[.\s]*$/u',
        ];
        $out = [
            '$1 $2 $3',
            '$1, $2 $3 ' . $year,
            '$1, $3 $2 ' . $year,
        ];
        $date = preg_replace($in, $out, $date);

        if (preg_match("/\b\d{1,2}\s+([[:alpha:]]+)\s+\d{2,4}\b/u", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        // $this->logger->debug('$date = ' . print_r($date, true));
        $this->logger->debug($date);
        if (preg_match("/^(?<week>[-[:alpha:]]+), (?<date>\d{1,2} [[:alpha:]]+ .+)$/u", $date, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));

            if ($weeknum === null){
                foreach (['en'] as $lang){
                    $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $lang));
                }
            }

            $date = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/^.{4,}\b\d{4}$/", $date) || preg_match("/^\d{1,2}\s+[[:alpha:]]+\s+\d{2}$/u", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }

        return $date;
    }

    private function preg_implode($field)
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }
}
