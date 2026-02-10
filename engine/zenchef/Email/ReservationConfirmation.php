<?php

namespace AwardWallet\Engine\zenchef\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ReservationConfirmation extends \TAccountChecker
{
    public $mailFiles = "zenchef/it-708714532.eml, zenchef/it-709475045.eml, zenchef/it-709507306.eml, zenchef/it-736230933.eml, zenchef/it-774249880.eml, zenchef/it-774284339.eml, zenchef/it-777675632.eml, zenchef/it-778127586.eml, zenchef/it-778865894.eml, zenchef/it-783891006.eml, zenchef/it-912688797.eml, zenchef/it-916434569.eml, zenchef/it-917227617.eml, zenchef/it-918341824.eml, zenchef/it-918871195.eml, zenchef/it-921713806.eml, zenchef/it-922387620.eml";
    public $subjects = [
        'Reservation confirmation',
        'Reconfirmation request',
        'Booking reminder for',
        '] Booking confirmation',
        // fr
        'Réservation confirmée suite au dépôt de votre empreinte bancaire',
        'VOTRE RÉSERVATION CONFIRMÉE',
        // pt
        '] Confirmação da sua reserva',
        // es
        '] Confirmación de reserva',
        // de
        '] Bestätigung Ihrer Reservierung',
        //it
        '] Conferma della prenotazione',
    ];

    public $emailSubject = '';
    public $lang = '';

    public $junkSubject = [
        //en
        'Reconfirmation request',
        'Booking not confirmed',
        'Your reservation request is awaiting confirmati',
        'Reconfirm your reservation at',
        //fr
        'Proposition de nouvel horaire pour votre réserv',
        'Demande de reconfirmation',
        'Reconfirmation en attente',
        'Réservation confirmée suite au dépôt de votre emprein',
        //pt
        'Pedido de reconfirmação',
    ];

    public $detectLang = [
        "de" => ['Uns antworten', 'Hallo'],
        "en" => ['We look forward', 'credit card', 'Manage my reservation', 'Hello ', 'Dear', 'Hi ', 'Bon dia'],
        "fr" => ['Bonjour Monsieur', 'Bonjour', 'Madame,Monsieur', 'Cher.e Monsieur', 'Confirmer ma venue', 'Monsieur'],
        "pt" => ['Olá,'],
        "nl" => ['Met vriendelijke groeten', 'Beste'],
        "es" => ['Hola,'],
        "it" => ['Ciao'],
    ];

    public static $dictionary = [
        "en" => [
            /*'Confirm my attendance' => '',
            'Manage my reservation' => '',*/

            'We are pleased to confirm your reservation for' => [
                'We are pleased to confirm your booking for',
                'We are pleased to confirm your reservation for',
                'Upon, your booking will take place on',
                'We ask our guests to reconfirm their reservation before the time of their arrival',
                'To validate your booking',
                'Following the validation of your credit card guarantee, we are pleased to confirm your reservation on',
                'upon, your booking will take place on',
                'We are pleased to confirm your reservation on',
                'We look forward to seeing you on',
                'We regret to inform you that we can no longer maintain your option for the',
                'We would like to take the opportunity to thank you again for the interest you have shown in our gastronomic restaurant',
                'Following the validation of you credit card deposit, we are pleased to confirm your booking of the',
                'We regret to inform you that we cannot confirm your reservation for',
                'Following the deposit of your bank imprint, we are pleased to confirm your reservation of',
                'We are glad to confirm your booking for:',
                'Following the deposit of your credit card imprint, we are pleased to confirm your reservation of',
                'Your reservation has been modified for',
                'Following the deposit of your bank imprint, we are pleased to confirm',
                'This is a reminder of your booking at',
                'Following the processing of your credit card imprint',
            ],
            'Hello'         => ['Hellooo,', 'Hello ', 'Dear', 'Bon dia'],
            'See you soon!' => ['See you soon!', 'Regards,', 'Best regards,', 'CONTACT', 'Kind regards,', 'See you very soon!'],
            'people)'       => ['people)', 'person(s))', 'personnes)', 'people', 'guests'],

            'CancelledText' => [
                'Cancellation reasons : either we have decided to cancel',
            ],
            'noReservation' => [
                'We regret to inform you that we cannot confirm your reservation for',
                'We sincerely regret not being able to respond favourably to your reservation',
                'Attention: This email does not confirm your reservation',
                'I found a table…..if you put your credit card it will be confirmed !',
                'We would please you to reconfirm your reservation at',
                'Thank you very much for your reservation request.',
                'has been canceled',
                'A confirmation of your presence is required',
            ],
            /*'Date :' => '',
            'Time :' => '',
            'Number of guests:' => '',*/
        ],

        "fr" => [
            'Confirm my attendance' => 'Modifier ma réservation',
            'Manage my reservation' => 'Gérer ma réservation',

            'We are pleased to confirm your reservation for' => [
                'Nous avons le plaisir de confirmer votre réservation du',
                'Nous avons le plaisir de vous confirmer votre réservation pour',
                'Nous avons pour habitude de demander à nos clients de reconfirmer',
                'Comme convenu, nous avons hâte de vous accueillir dans notre',
                'Suite au dépôt de votre empreinte bancaire, nous avons le plaisir de confirmer votre réservation du',
                'Nous avons pris bonne note de votre réservation du',
                'Votre réservation a été modifiée pour le',
                'Confirmation de votre réservation du',
                '] Confirmation de votre réservation',
                'Nous avons le plaisir de confirmer ta réservation du',
                'Suite à votre demande, nous avons le plaisir de confirmer votre réservation à la',
                'bien vouloir',
            ],

            'We are looking forward to welcoming you soon' => ['Nous sommes ravis de vous accueillir prochainement'],

            'Hello'         => ['Bonjour Monsieur', 'Bonjour', 'Madame,Monsieur', 'Cher.e Monsieur'],
            'See you soon!' => [
                'À très bientôt !',
                'Cordialement,',
                'Bien cordialement,',
                'A Presto !',
                'Nous sommes impatients de vous accueillir !',
                'À très bientôt chez',
                'A prestissimo !',
                'Au plaisir de vous accueillir dans notre établissement,',
                'À très bientôt,',
                'Nous avons hâte de vous accueillir !',
                'À très bientôt !',
                'Au plaisir de vous recevoir !',
            ],
            'people)'           => ['personnes)', 'personne(s))', 'personnes', 'personnes)'],
            'for'               => ['pour', 'le'],
            'noReservation'     => [
                'Nous vous invitons à nous reconfirmer votre réservation en cliquant sur le lien',
                'nous n\'avons pas de disponibilités à l\'heure choisie',
                'Message au sujet de votre réservation',
                'Nous avons pour habitude de demander à nos clients de reconfirmer leur réservation avant',
                'On a pour habitude de demander à nos clients de reconfirmer leur réservation avant',
                'Afin de confirmer votre venue, nous vous invitons à cliquer sur le bouton suivant',
            ],
            'Date :'            => ['Date :', 'Date et heure :'],
            'Time :'            => ['Heure :'],
            'Number of guests:' => ['Nombre d\'invités :', 'Nombre de convives :'],
        ],

        "pt" => [
            'We are pleased to confirm your reservation for' => [
                'Costumamos pedir aos nossos clientes que reconfirmem a sua reserva antes da hora da sua',
                'Temos o prazer de confirmar a sua reserva de',
                'Após o depósito de pré-autorização do cartão bancário, temos o prazer de confirmar a sua reserva para',
                'Conforme combinado, esperamos por si em',
                'Sua reserva foi alterada para',
            ],
            'Hello'                                          => 'Olá,',
            'See you soon!'                                  => ['Atentamente,', 'Até breve!'],
            'people)'                                        => ['pessoa(s))', 'pessoas)', 'pessoas'],
            'noReservation'                                  => [
                'Atenção: Este email não confirma a sua reserva',
                'Habitualmente pedimos aos nossos clientes que reconfirmem a sua reserva antes',
            ],
            /*'Date :' => '',
            'Time :' => '',
            'Number of guests:' => '',*/
        ],

        "nl" => [
            'We are pleased to confirm your reservation for' => [
                'Graag bevestigen we je reservering op',
                'Graag bevestigen we uw reservering op',
                'Zoals afgesproken, verwachten we je graag op',
            ],
            'Hello'         => 'Beste',
            'See you soon!' => ['Tot binnenkort!'],
            'people)'       => ['personen)'],
            /*'Date :' => '',
            'Time :' => '',
            'Number of guests:' => '',*/
        ],
        "es" => [
            'We are pleased to confirm your reservation for' => [
                'Estamos encantados de confirmar su reserva para el',
                'Estaremos encantados de confirmar su reserva para el',
                'Como acordamos, estamos esperándote para el',
            ],
            'Hello'         => 'Hola,',
            'See you soon!' => ['¡Nos vemos pronto!'],
            'people)'       => ['persons)', 'people)'],
            /*'Date :' => '',
            'Time :' => '',
            'Number of guests:' => '',*/
        ],
        "de" => [
            'We are pleased to confirm your reservation for' => [
                'Gerne bestätigen wir Ihre Reservierung vom',
                'Ihre Reservierung wurde auf den',
            ],
            'Hello'         => 'Hallo',
            // 'See you soon!' => ['¡Nos vemos pronto!'],
            'people)'       => ['Personen)', 'Personen'],
            /*'Date :' => '',
            'Time :' => '',
            'Number of guests:' => '',*/
            'noReservation'                                  => [
                'in het restaurant, zowel binnen als buiten.',
            ],
        ],
        "it" => [
            'We are pleased to confirm your reservation for' => [
                'Siamo felici di confermare la tua prenotazione del',
            ],
            'Hello'         => 'Ciao',
            // 'See you soon!' => ['¡Nos vemos pronto!'],
            'people)'       => ['persons)'],
            /*'Date :' => '',
            'Time :' => '',
            'Number of guests:' => '',*/
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@mg.zenchefrestaurants.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $this->assignLang();

        if (($this->http->XPath->query("//a[contains(@href, 'mg.zenchefrestaurants.com')]")->length > 0
            || $this->http->XPath->query("//img[contains(@src, 'mg.zenchefrestaurants.com')]")->length > 0)
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Confirm my attendance'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Manage my reservation'))}]")->length > 0) {
            return true;
        }

        return ($this->http->XPath->query("//a[contains(@href, 'mg.zenchefrestaurants.com')]")->length > 0
            || $this->http->XPath->query("//img[contains(@src, 'mg.zenchefrestaurants.com')]")->length > 0)
            && ($this->http->XPath->query("//text()[{$this->contains($this->t('We are pleased to confirm your reservation for'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t('junkSubject'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t('CancelledText'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t('noReservation'))}]")->length > 0);
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]mg\.zenchefrestaurants\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (preg_match("/{$this->opt($this->junkSubject)}/", $parser->getSubject())
            //it-916434569.eml
            || $this->http->XPath->query("//text()[{$this->contains($this->t('noReservation'))}]")->length > 0) {
            return $email->setIsJunk(true);
        }

        $this->emailSubject = $parser->getSubject();

        $this->Event($email);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function Event(Email $email)
    {
        $e = $email->add()->event();

        $e->type()
            ->restaurant();

        $traveller = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello'))}]", null, true, "/{$this->opt($this->t('Hello'))}\s*(.+)[\,:\!]/");

        if (empty($traveller)) {
            $traveller = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello'))}]/ancestor::p[1]", null, true, "/{$this->opt($this->t('Hello'))}\s*(.+)[\,:\!]/");
        }

        if (empty($traveller)) {
            $traveller = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We are pleased to confirm your reservation for'))}]/preceding::text()[{$this->starts($this->t('Hello'))}][1]", null, true, "/{$this->opt($this->t('Hello'))}\s*(.+)[\,:\!]/");
        }

        $e->general()
            ->traveller(preg_replace('/^\s*(?:Monsieur|Ms\.||Mrs\.|Mr\.)\s+/', '', $traveller))
            ->noConfirmation();

        if ($this->http->XPath->query("//node()[{$this->contains($this->t('CancelledText'))}]")->length > 0) {
            $e->general()
                ->status('Cancelled')
                ->cancelled();
        }

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello'))}]/preceding::text()[position() < 5][normalize-space()][not(ancestor::style)][1]");

        if (empty($name) && preg_match('/\[\s*(.+?)\s*\]/', $this->emailSubject, $m)
            && $this->http->XPath->query("//text()[{$this->eq($m[1])}]")->length > 0
        ) {
            $name = $m[1];
        }

        if (!empty($name)) {
            $e->place()
                ->name($name);
        }

        if (!empty($name)) {
            $altNames = [
                'RESTAURANT LA RAPIERE' => ["L'équipe du Restaurant"],
            ];
            $names = $altNames[$name] ?? [];
            $names[] = $name;

            $address = null;
            $addresses = array_values(array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('See you soon!'))}]/following::text()[normalize-space()][position() < 4]/ancestor::p[1]",
                null, "/{$this->opt($names)}\s*(\S.+)/isu")));

            if (count($addresses) === 1) {
                $address = $addresses[0];

                if (preg_match("/^\w+$/", $address)) {
                    $address = '';
                }

                if (preg_match("/(?<address>.+)\s*(?<phone>(?:[+])[\d\(\)\s]+)\s+\|.*/", $address, $m)) {
                    $address = $m['address'];
                    $e->setPhone($m['phone']);
                }
            }

            if (empty($address)) {
                $htmlNames = array_unique($this->http->FindPregAll("/\>({$this->opt($names)})\</"));
                $addresses = array_values(array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Hello'))}]/following::text()[{$this->eq($htmlNames)}]/ancestor::p[1][descendant::text()[normalize-space()][{$this->eq($htmlNames)}]]",
                    null, "/{$this->opt($names)}\s*(\S.+)/isu")));

                if (empty($address)) {
                    $addressText = implode("\n", array_values(array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Hello'))}]/following::text()[{$this->starts($this->t('See you soon!'))}]/ancestor::p[1]/following-sibling::p"))));

                    if (preg_match("/{$this->opt($names)}\n(?<address>.+(?:\n.+)?)(?:\n(?<phone>[\d\+]+)\s*\/.*)?/", $addressText, $m)) {
                        $address = str_replace(["\n", "Instagram"], [" ", ""], $m['address']);

                        if (isset($m['phone']) && !empty($m['phone'])) {
                            $e->setPhone($m['phone']);
                        }
                    }
                }

                if (count($addresses) === 1) {
                    $address = $addresses[0];
                }
            }

            if (empty($address)) {
                $address = implode(", ", $this->http->FindNodes("//text()[{$this->starts($this->t('See you soon!'))}]/following::span[{$this->eq($name)}]/following-sibling::span"));
            }

            if (empty($address)) {
                $addressText = implode("\n", array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('See you soon!'))}]/ancestor::p[1]/following-sibling::p/descendant::text()[normalize-space()]")));

                if (preg_match("/{$this->opt($names)}\D+\n\s*(?<address>\S.+)/", $addressText, $m)
                    || preg_match("/{$this->opt($names)}\n(?<phone>\d+)\n(?<address>.+(?:\n.+)?)$/iu", $addressText, $m)
                    || preg_match("/.+\n(?<address>.+(?:\n.+)?)$/iu", $addressText, $m)
                ) {
                    $address = $m['address'];
                }
            }

            $e->place()
                ->address(str_replace("\n", " ", $address));
        }

        if (empty($name)) {
            $addressText = implode("\n", array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('See you soon!'))}]/ancestor::p[1]/following-sibling::p")));

            if (preg_match("/^(?<name>.+)\n(?<address>.+)$/", $addressText, $m)) {
                $e->setName($m['name']);
                $e->setAddress($m['address']);
            }
        }

        if ($e->getCancelled()) {
            $date = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We are pleased to confirm your reservation for'))}]/following::text()[normalize-space()][1]", null, true, "/^(.+\d{4}.*\d+:\d+.*)$/");

            if (!empty($date)) {
                $e->setStartDate($this->normalizeDate($date));
            }
        } else {
            $guests = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('people)'))}])[1]", null,
                true, "/\(?\s*(\d+)\s*{$this->opt($this->t('people)'))}/");

            if (empty($guests)) {
                $guests = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We are pleased to confirm your reservation for'))}]/following::text()[normalize-space()][1]", null,
                    true, "/\s*{$this->opt($this->t('for'))}\s*(\d+)$/");
            }

            if (empty($guests)) {
                $guests = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Number of guests:'))}]", null,
                    true, "/{$this->opt($this->t('Number of guests:'))}\s*(\d+)$/");
            }
            $e->booked()
                ->guests($guests);

            $date = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We are pleased to confirm your reservation for'))}]/following::text()[normalize-space()][1]", null, true, "/^(.+\d{4})/");

            if (empty($date)) {
                $date = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We are pleased to confirm your reservation for'))}]", null, true, "/{$this->opt($this->t('for'))}\s*(.+\d{4})/");
            }

            if (empty($date)) {
                $date = $this->http->FindSingleNode("//text()[{$this->contains($this->t('We are looking forward to welcoming you soon'))}]/preceding::text()[normalize-space()][1]/ancestor::td[1]", null, true, "/((?:\d+\/\d+\/\d{4}|\d+\s+\w+\s+\d{4}))/");
            }

            if (empty($date)) {
                $date = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Date :'))}]/ancestor::*[1]", null, true, "/{$this->opt($this->t('Date :'))}\s*(.+\d{4})/");
            }

            if (preg_match("/{$this->opt($this->t('Date :'))}/", $date)) {
                $date = preg_replace("/{$this->opt($this->t('Date :'))}/", "", $date);
            }

            $time = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We are pleased to confirm your reservation for'))}]/following::text()[normalize-space()][3]", null, true, "/^(\d+\:\d+\s*A?P?M?)/");

            if (empty($time)) {
                $time = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We are pleased to confirm your reservation for'))}]/following::text()[normalize-space()][2]", null, true, "/^(\d+\:\d+\s*A?P?M?)/");
            }

            if (empty($time)) {
                $time = $this->http->FindSingleNode("//text()[{$this->starts($this->t('We are pleased to confirm your reservation for'))}]", null, true, "/(\d+\:\d+\s*A?P?M?)/");
            }

            if (empty($time)) {
                $time = $this->http->FindSingleNode("//text()[{$this->contains($this->t('We are looking forward to welcoming you soon'))}]/preceding::text()[normalize-space()][1]/ancestor::td[1]", null, true, "/\s(\d+\:\d+\s*A?P?M?)/iu");
            }

            if (empty($time)) {
                $time = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Time :'))}]", null, true, "/{$this->opt($this->t('Time :'))}\s*([\d\:]+\s*A?P?M?)$/iu");
            }

            if (empty($time)) {
                $time = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Date :'))}]/ancestor::*[1]", null, true, "/\s*([\d\:]+\s*A?P?M?)$/");
            }

            if (!empty($date) && !empty($time)) {
                $e->setStartDate($this->normalizeDate($date . ', ' . $time));
            }
        }

        $e->setNoEndDate(true);
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
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

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function assignLang()
    {
        foreach ($this->detectLang as $lang => $words) {
            foreach ($words as $word) {
                if ($this->http->XPath->query("//text()[{$this->contains($word)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeDate($date)
    {
        // $this->logger->debug('$date = '.print_r( $date,true));
        $in = [
            // samedi 10 août 2024, 19:30
            // Tuesday 29 October 2024 at 8:15 PM
            "/^\s*[\w\-]+\s+(\d+\s*\w+\s*\d{4})(?:,|\s+at\s+)\s*(\d+\:\d+\s*A?P?M?)\s*$/u",
            // 16/06/2025, 21:00
            "/^(\d+)\/(\d+)\/(\d+)\,\s*([\d\:]+\s*A?P?M?)$/iu",
        ];
        $out = [
            "$1, $2",
            "$1.$2.$3, $4",
        ];
        $date = preg_replace($in, $out, $date);
        // $this->logger->debug('$date = '.print_r( $date,true));

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#u", $date, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        return strtotime($date);
    }
}
