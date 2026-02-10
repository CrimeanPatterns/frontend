<?php

namespace AwardWallet\Engine\edreams\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BookingHotel extends \TAccountChecker
{
    public $mailFiles = "edreams/it-702597192.eml, edreams/it-880943664.eml, edreams/it-884939089.eml";
    public $subjects = [
        'Booking successful: eDreams Ref. #',
        'Your booking is confirmed! (reference:',
        // pt
        'Reserva bem-sucedida: eDreams – ref. #',
        'A tua reserva está parcialmente confirmada! eDreams – ref. #',
        'A tua reserva está confirmada! (Referência:',
        // fr
        'Votre réservation est confirmée ! (Référence :',
        // de
        'Ihre Buchung wurde bestätigt! (Buchungsnummer:',
        // fi
        'Varauksesi on vahvistettu! (Varausnumero:',
        // it
        'La tua prenotazione è confermata! (Riferimento',
        // es
        '¡Tu reserva está confirmada! (localizador:',
        'Reserva exitosa: eDreams número de referencia',
        // nl
        'Boeking geslaagd: eDreams Ref. #',
        // da
        'Din booking er bekræftet! (Bookingnr.:',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            // 'booking reference:' => '',
            // 'Phone:' => '',
            // 'Address:' => '',
            // 'How to get there' => '',
            'Check-in' => 'Check-in',
            // 'Check-out' => '',
            'Your stay' => 'Your stay',
            // 'Group:' => '',
            // 'adult' => '',
            // 'children' => '',
            // 'Room:' => '',
            'Check cancellation policy' => ['Check cancellation policy', 'Check cancelation policy', 'Cancellation policy'],
            'Customer details'          => 'Customer details',
            // 'Full Name:' => '',
            // 'Prime price' => '',
            // 'discount' => '',
        ],
        "pt" => [
            'booking reference:' => 'Referência da reserva da ',
            'Phone:'             => 'Telefone:',
            'Address:'           => 'Endereço:',
            'How to get there'   => 'Como chegar',
            'Check-in'           => 'Check-in',
            'Check-out'          => 'Check-out',
            'Your stay'          => 'A tua estadia',
            'Group:'             => 'Grupo:',
            'adult'              => 'adulto',
            // 'children' => '',
            'Room:'                     => 'Quarto:',
            'Check cancellation policy' => ['Política de cancelamento'],
            'Customer details'          => 'Dados do comprador',
            'Full Name:'                => 'Primeiro Nome e Sobrenome',
            'Prime price'               => 'Já pago',
            // 'discount' => '',
        ],
        "fr" => [
            'booking reference:' => 'Référence de réservation ',
            'Phone:'             => 'Téléphone:',
            'Address:'           => 'Adresse:',
            'How to get there'   => 'Comment s\'y rendre',
            'Check-in'           => 'Arrivée',
            'Check-out'          => 'Départ',
            'Your stay'          => 'Votre séjour',
            'Group:'             => 'Groupe:',
            'adult'              => 'adult',
            // 'children' => '',
            'Room:'                     => 'Chambre:',
            'Check cancellation policy' => ['Politique d\'annulation'],
            'Customer details'          => 'Coordonnées de l’acheteur',
            'Full Name:'                => 'Prénom et Nom :',
            'Prime price'               => ['Prix Prime', 'Déjà payé'],
            'discount'                  => 'Réduction',
        ],
    ];

    public $providerCode;
    public static $detectProvider = [
        "opodo"     => [
            'companyName' => 'Opodo',
            'from'        => '@mailer.opodo.com',
        ],
        "tllink"    => [
            'companyName' => 'Travellink',
            'from'        => '@mailer.travellink.com',
        ],
        "govoyages" => [
            'companyName' => ['Govoyages', 'GO Voyages'],
            'from'        => '@mailer.govoyages.com',
        ],
        // last
        "edreams"   => [
            'companyName' => 'eDreams',
            'from'        => '@mailer.edreams.com',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        $detectedProv = false;

        foreach (self::$detectProvider as $code => $detect) {
            if (isset($headers['from']) && !empty($detect['from'])
                && stripos($headers['from'], $detect['from']) !== false) {
                $detectedProv = true;
                $this->providerCode = $code;

                break;
            }
        }

        if ($detectedProv === false) {
            return false;
        }

        foreach ($this->subjects as $subject) {
            if (stripos($headers['subject'], $subject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $detectedProv = false;

        foreach (self::$detectProvider as $code => $detect) {
            if (!empty($detect['companyName'])
                && $this->http->XPath->query("//text()[{$this->contains($detect['companyName'])}]")->length > 0) {
                $detectedProv = true;
                $this->providerCode = $code;

                break;
            }
        }

        if ($detectedProv === false) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['Your stay']) && !empty($dict['Check-in']) && !empty($dict['Customer details'])
                && $this->http->XPath->query("//text()[{$this->contains($dict['Your stay'])}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($dict['Check-in'])}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($dict['Customer details'])}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]mailer\.edreams\.com$/', $from) > 0;
    }

    public static function getEmailProviders()
    {
        return array_keys(self::$detectProvider);
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if (empty($this->providerCode)) {
            foreach (self::$detectProvider as $code => $detect) {
                if (!empty($detect['from'])
                    && stripos($parser->getCleanFrom(), $detect['from']) !== false) {
                    $this->providerCode = $code;

                    break;
                }

                if (!empty($detect['companyName'])
                    && ($this->http->XPath->query("//text()[{$this->contains($detect['companyName'])}]")->length > 0)
                ) {
                    $this->providerCode = $code;

                    break;
                }
            }
        }

        if (!empty($this->providerCode)) {
            $email->setProviderCode($this->providerCode);
        }

        $this->assignLang();
        // $date = strtotime($parser->getDate());
        // $this->year = date('Y', $date);

        $email->obtainTravelAgency(); // because eDreams is travel agency

        $confirmationTitle = $this->http->FindSingleNode("//text()[{$this->contains($this->t('booking reference:'))}][1]");

        $confirmation = array_filter($this->http->FindNodes("//text()[{$this->contains($this->t('booking reference:'))}]/following::text()[normalize-space(.)][1]", null, '/^(\d{7,})(?:\s.*)?$/'));
        $confirmation = array_shift($confirmation);
        $email->ota()->confirmation($confirmation, preg_replace('/\s*:\s*$/', '', $confirmationTitle));

        $this->Hotel($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function Hotel(Email $email)
    {
        $h = $email->add()->hotel();

        $h->general()
            ->noConfirmation()
            ->traveller(implode(' ', $this->http->FindNodes("//text()[{$this->eq($this->t('Customer details'))}]/following::text()[{$this->eq($this->t('Full Name:'))}]/following::text()[normalize-space()][position() < 3]")))
            ->cancellation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Check cancellation policy'))}]/following::text()[normalize-space()][string-length()>5][1]"));

        if ($this->http->FindSingleNode("//text()[{$this->eq($this->t('Address:'))}]") && $this->http->FindSingleNode("//text()[{$this->eq($this->t('Phone:'))}]")) {
            $h->hotel()
                ->name($this->http->FindSingleNode("//text()[{$this->eq($this->t('How to get there'))}]/preceding::text()[{$this->eq($this->t('Phone:'))}]/preceding::text()[normalize-space()][1]"))
                ->address($this->http->FindSingleNode("//text()[{$this->eq($this->t('How to get there'))}]/preceding::text()[{$this->eq($this->t('Address:'))}]/following::text()[normalize-space()][string-length()>5][1]"))
                ->phone($this->http->FindSingleNode("//text()[{$this->eq($this->t('How to get there'))}]/preceding::text()[{$this->eq($this->t('Phone:'))}]/following::text()[normalize-space()][string-length()>5][1]"))
            ;
        } elseif ($this->http->FindSingleNode("//text()[{$this->eq($this->t('Address:'))}]")) {
            $h->hotel()
                ->name($this->http->FindSingleNode("//text()[{$this->eq($this->t('How to get there'))}]/preceding::text()[{$this->eq($this->t('Address:'))}]/preceding::text()[normalize-space()][1]"))
                ->address($this->http->FindSingleNode("//text()[{$this->eq($this->t('How to get there'))}]/preceding::text()[{$this->eq($this->t('Address:'))}]/following::text()[normalize-space()][string-length()>5][1]"))
            ;
        } else {
            $h->hotel()
                ->name($this->http->FindSingleNode("//text()[{$this->eq($this->t('How to get there'))}]/preceding::text()[normalize-space()][string-length()>5][2]"))
                ->address($this->http->FindSingleNode("//text()[{$this->eq($this->t('How to get there'))}]/preceding::text()[normalize-space()][string-length()>5][1]"));
        }

        $h->booked()
            ->guests($this->http->FindSingleNode("//text()[{$this->eq($this->t('Group:'))}]/following::text()[normalize-space()][string-length()>5][1]", null, true, "/^(\d+)\s*{$this->opt($this->t('adult'))}/"))
            ->kids($this->http->FindSingleNode("//text()[{$this->eq($this->t('Group:'))}]/following::text()[normalize-space()][string-length()>5][2]", null, true, "/^(\d+)\s*{$this->opt($this->t('children'))}/"), true, true);

        $inText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Check-in'))}]/following::text()[normalize-space()][string-length()>5][1]/ancestor::table[1]//text()[normalize-space()]"));

        if (preg_match("/{$this->opt($this->t('Check-in'))}\s+(.+)\s*\n\s*\D*\s*(\d{1,2}:\d{2}(?:\s*[AP]M)?)(?:-.+)?\s*$/", $inText, $m)) {
            $h->booked()
                ->checkIn($this->normalizeDate($m[1] . ', ' . $m[2]));
        }

        $outText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Check-out'))}]/following::text()[normalize-space()][string-length()>5][1]/ancestor::table[1]//text()[normalize-space()]"));

        if (preg_match("/{$this->opt($this->t('Check-out'))}\s+(.+)\s*\n\s*\D*(?:.*-)?\s*(\d{1,2}:\d{2}(?:\s*[AP]M)?)\s*$/", $outText, $m)) {
            $h->booked()
                ->checkOut($this->normalizeDate($m[1] . ', ' . $m[2]));
        }

        $this->detectDeadLine($h);

        $rooms = $this->http->FindNodes("//text()[{$this->eq($this->t('Room:'))}]/ancestor::tr[1]/following-sibling::tr");

        foreach ($rooms as $roomItem) {
            $room = $h->addRoom();
            $room->setType($roomItem);
        }

        $price = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Prime price'))}]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Prime price'))}\s*(\D*\s*[\d\.\,]+\D*)/");

        if (preg_match("/^(?<currency>\D{1,3})\s*(?<total>[\d\.\,]+)$/", $price, $m)
            || preg_match("/^(?<total>[\d\.\,]+)\s*(?<currency>\D{1,3})$/", $price, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $h->price()
                ->total(PriceHelper::parse($m['total'], $currency))
                ->currency($currency);

            $discount = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Prime price'))}]/preceding::text()[normalize-space()][position() < 6][{$this->contains($this->t('discount'))}]/ancestor::tr[1]/descendant::td[2]", null, true, "/^\D*(\d[\d\.\,]*)\D*$/");

            if (!empty($discount)) {
                $h->price()
                    ->discount(PriceHelper::parse($discount, $currency));
            }
        }
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Your stay']) && !empty($dict['Check-in']) && !empty($dict['Customer details'])
                && $this->http->XPath->query("//text()[{$this->contains($dict['Your stay'])}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($dict['Check-in'])}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($dict['Customer details'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }
    }

    private function normalizeDate($str)
    {
        // $this->logger->debug('$str = '.print_r( $str,true));
        $in = [
            // Fri, 28 Mar 2025, 15:00
            // mar., 08 avr. 2025, 12:00
            '/^\s*[[:alpha:]]+\.?,\s*(\d{1,2})\s+([[:alpha:]]+)\.?\s+(\d{4})\s*,\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/ui',
        ];
        $out = [
            "$1 $2 $3, $4",
        ];
        $str = preg_replace($in, $out, $str);
        // $this->logger->debug('$str = '.print_r( $str,true));
        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str, false);
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

    private function detectDeadLine(Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        if (preg_match("/^\s*This booking is not cancellable\./", $cancellationText)
            || preg_match("/^\s*Esta reserva não é cancelável\./u", $cancellationText)
        ) {
            $h->booked()->nonRefundable();
        }
    }

    private function normalizeCurrency($string)
    {
        $string = trim($string);
        $currencies = [
            'TWD' => ['NT$'],
            'CAD' => ['C$'],
            'GBP' => ['£'],
            'AUD' => ['A$', 'AU$'],
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

    private function eq($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) { return 'normalize-space(' . $node . ')="' . $s . '"'; }, $field)) . ')';
    }
}
