<?php

namespace AwardWallet\Engine\zenhotels\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class HotelConfirmation extends \TAccountChecker
{
    public $mailFiles = "zenhotels/it-178287752.eml, zenhotels/it-180342653.eml, zenhotels/it-181671844.eml, zenhotels/it-893972203.eml, zenhotels/it-895622266.eml, zenhotels/it-898863687.eml, zenhotels/it-898864834.eml, zenhotels/it-901041157.eml";
    public $subjects = [
        'Hotel booking confirmation',
        'Please note the change of booking number',
        //pt
        'Confirmação de reserva no hotel',
        // de
        'Hotel-Buchungsbestätigung',
        // it
        'Conferma della prenotazione n°',
        // es
        'Confirmación de reserva de hotel:',
        // ru
        'Подтверждение бронирования отеля',
    ];

    public $lang = '';

    public $detectLang = [
        'pt' => ['Endereço'],
        'de' => ['Adresse'],
        'it' => ['Indirizzo'],
        'es' => ['Dirección'],
        'ru' => ['Адрес'],
        'en' => ['Address'],
    ];

    public static $dictionary = [
        "en" => [
            'Print' => ['Print', 'Print voucher'],
            //            'Address'             => '',
            //            'Telephone number'    => '',
            // 'Booking no.' => '',
            // 'Confirmation code' => '',
            //            'Room'                => '',
            'Check-in'             => ['Check-in', 'Check-in (date)'],
            'Check-out'            => ['Check-out', 'Check-out (date)'],
            'Prices'               => 'Prices',
            'Pay at hotel'         => 'Pay at hotel',
            'Hotel on map'         => 'Hotel on map',
            'Booking details'      => 'Booking details',
            //            'Number of guests'    => '',
            //            'Guest names'         => '',
            //            'Guests in'         => '', // Guests in first room
            //            'room'         => '', // Guests in first room
            'Cancellation policy' => ['Cancellation policy', 'Cancellation/amendment policy'],
        ],

        "pt" => [
            'Print'               => 'Imprimir',
            'Address'             => 'Endereço',
            'Telephone number'    => 'Número de telefone',
            'Booking no.'         => 'Reserva nº',
            'Confirmation code'   => 'Código de confirmação',
            'Room'                => 'Quarto',
            'Check-in'            => 'Check-in',
            'Check-out'           => 'Check-out',
            // 'Prices' => '',
            // 'Pay at hotel' => '',
            'Hotel on map'        => 'Hotel no mapa',
            'Booking details'     => 'Detalhes da reserva',
            'Number of guests'    => 'Número de hóspedes',
            'Guest names'         => 'Nome do cliente',
            //            'Guests in'         => '', // Guests in first room
            //            'room'         => '', // Guests in first room
            'Cancellation policy' => 'Política de Cancelamento',
        ],
        "de" => [
            'Print'               => 'Drucken',
            'Address'             => 'Adresse',
            'Telephone number'    => 'Telefonnummer',
            'Booking no.'         => 'Buchung Nr.',
            'Confirmation code'   => 'Bestätigungscode',
            'Room'                => 'Zimmer',
            'Check-in'            => 'Anreise',
            'Check-out'           => 'Abreise',
            // 'Prices' => '',
            // 'Pay at hotel' => '',
            'Hotel on map'        => 'Hotel auf der',
            'Booking details'     => 'Buchungsdetails',
            'Number of guests'    => 'Anzahl der Gäste',
            'Guest names'         => 'Name (Gast)',
            //            'Guests in'         => '', // Guests in first room
            //            'room'         => '', // Guests in first room
            'Cancellation policy' => 'Stornierung',
        ],
        "it" => [
            'Print'               => 'Stampa',
            'Address'             => 'Indirizzo',
            'Telephone number'    => 'Numero di telefono',
            'Booking no.'         => 'Prenotazione n°',
            'Confirmation code'   => 'Codice di conferma',
            'Room'                => 'Camera',
            'Check-in'            => 'Check-in',
            'Check-out'           => 'Check-out',
            // 'Prices' => '',
            // 'Pay at hotel' => '',
            'Hotel on map'        => 'La struttura sulla mappa di',
            'Booking details'     => 'Dettagli della prenotazione',
            'Number of guests'    => 'Numero di ospiti',
            'Guest names'         => 'Nomi degli ospiti',
            //            'Guests in'         => '', // Guests in first room
            //            'room'         => '', // Guests in first room
            'Cancellation policy' => 'Politica di cancellazione',
        ],
        "es" => [
            'Print'               => 'Imprimir',
            'Address'             => 'Dirección',
            'Telephone number'    => 'Número de teléfono',
            'Booking no.'         => ['Reserva n.°'],
            // 'Confirmation code'   => '',
            'Room'                => 'Habitación',
            'Check-in'            => 'Check-in',
            'Check-out'           => 'Check-out',
            // 'Prices' => '',
            // 'Pay at hotel' => '',
            'Hotel on map'        => 'Hotel en el mapa',
            'Booking details'     => 'Detalles de la reserva',
            'Number of guests'    => 'Número de los huéspedes',
            'Guest names'         => 'Nombres de los huéspedes',
            //            'Guests in'         => '', // Guests in first room
            //            'room'         => '', // Guests in first room
            'Cancellation policy' => 'Política de cancelaciones',
        ],
        "ru" => [
            'Print'               => 'Распечатать',
            'Address'             => 'Адрес',
            'Telephone number'    => 'Телефон',
            'Booking no.'         => 'Бронирование №',
            'Confirmation code'   => 'Код подтверждения',
            'Room'                => 'Номер',
            'Check-in'            => 'Дата заезда',
            'Check-out'           => 'Дата выезда',
            'Prices'              => 'Стоимость',
            'Pay at hotel'        => 'К оплате отелю',
            'Hotel on map'        => 'Отель на карте',
            'Booking details'     => 'Детали бронирования',
            'Number of guests'    => 'Число гостей',
            'Guest names'         => 'Имена гостей',
            'Guests in'           => 'Гости в', // Guests in first room
            'room'                => 'номере', // Guests in first room
            'Cancellation policy' => 'Стоимость отмены/незаезда',
        ],
    ];

    public $providerCode;
    public static $detectProvider = [
        'zenhotels' => [
            'from'     => '@news.zenhotels.com',
            'bodyLink' => ['.zenhotels.com'],
            'bodyText' => ['support@zenhotels.com'],
        ],
        'ostrovok' => [
            'from'     => '@info.ostrovok.ru',
            'bodyLink' => '.ostrovok.ru',
            'bodyText' => ['info@ostrovok.ru'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        foreach (self::$detectProvider as $code => $params) {
            if (!empty($params['from']) && stripos($headers['from'], $params['from']) !== false) {
                $this->providerCode = $code;

                foreach ($this->subjects as $subject) {
                    if (stripos($headers['subject'], $subject) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        foreach (self::$detectProvider as $code => $params) {
            if (
                !empty($params['bodyLink']) && $this->http->XPath->query("//a/@href[{$this->contains($params['bodyLink'])}]")->length > 0
                || !empty($params['bodyText']) && $this->http->XPath->query("//node()[{$this->contains($params['bodyText'])}]")->length > 0
            ) {
                $this->providerCode = $code;

                foreach (self::$dictionary as $dict) {
                    if (!empty($dict['Print']) && !empty($dict['Booking details']) && !empty($dict['Hotel on map'])
                        && $this->http->XPath->query("//a[{$this->contains($dict['Print'])}]")->length > 0
                        && $this->http->XPath->query("//node()[{$this->contains($dict['Booking details'])}]")->length > 0
                        && $this->http->XPath->query("//node()[{$this->contains($dict['Hotel on map'])}]")->length > 0
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]news\.zenhotels\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();
        $this->ParseHotel($email);

        $email->setProviderCode($this->providerCode);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseHotel(Email $email)
    {
        // Travel Agency
        $email->ota()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking no.'))}]/following::text()[normalize-space()][1]",
                null, true, "/^\s*([\dA-Z\-]{5,})\s*$/"),
                $this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking no.'))}]"))
        ;
        $h = $email->add()->hotel();

        // General
        $conf = $this->nextTd($this->t('Confirmation code'), "/^([A-Z\d\-\/]+)$/");

        if (!empty($conf)) {
            $h->general()
                ->confirmation($conf, $this->http->FindSingleNode("//text()[{$this->eq($this->t('Confirmation code'))}]"));
        } else {
            $h->general()
                ->noConfirmation();
        }

        $travellers = $this->nextTdTexts($this->t('Guest names'));
        $twoRoomsCond = "[{$this->starts($this->t('Guests in'))}][{$this->contains($this->t('room'))}]";

        if (empty($travellers)) {
            $travellers = $this->http->FindNodes("//text(){$twoRoomsCond}/ancestor::tr[1][descendant::td[normalize-space()][1]{$twoRoomsCond}]/descendant::td[normalize-space()][2]//text()[normalize-space()]");
        }

        if (empty($travellers)) {
            $travellers = $this->http->FindNodes("//text(){$twoRoomsCond}/following::text()[normalize-space()][1]/ancestor::*[preceding-sibling::*{$twoRoomsCond}]//text()[normalize-space()]");
        }

        $h->general()
            ->travellers(array_unique($travellers), true)
            ->cancellation($this->nextTd($this->t('Cancellation policy')))
        ;

        // Hotel
        $h->hotel()
            ->name($this->http->FindSingleNode("//text()[{$this->eq($this->t('Address'))}]/preceding::text()[normalize-space()][1]"))
            ->address($this->nextTd($this->t('Address')))
            ->phone($this->nextTd($this->t('Telephone number'), null, "[following::text()[{$this->eq($this->t('Confirmation code'))}]]"), true, true)
        ;

        $h->booked()
            ->guests($this->nextTd($this->t('Number of guests')))
            ->checkIn($this->normalizeDate($this->nextTd($this->t('Check-in'), null, "[following::text()[{$this->eq($this->t('Number of guests'))}]]")))
            ->checkOut($this->normalizeDate($this->nextTd($this->t('Check-out'), null, "[following::text()[{$this->eq($this->t('Number of guests'))}]]")))
        ;

        $roomType = $this->nextTd($this->t('Room'));
        $count = 1;

        if (preg_match("/^\s*(\d+) x (.+)/", $roomType, $m)) {
            $count = $m[1];
            $roomType = $m[2];
        }

        for ($i = 1; $i <= $count; $i++) {
            $room = $h->addRoom();
            $room->setType($roomType);
        }

        // Price
        $total = $this->nextTd($this->t('Prices'), '/\s*\d.*/');

        if (empty($total)) {
            $total = $this->nextTd($this->t('К оплате отелю'), '/\s*\d.*/');
        }

        if (preg_match("#^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$#", $total, $m)
            || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$#", $total, $m)
        ) {
            $currency = $this->currency($m['currency']);
            $h->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency)
            ;
        }

        $this->detectDeadLine($h);
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    public static function getEmailProviders()
    {
        return array_keys(self::$detectProvider);
    }

    protected function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function nextTd($field, $regexp = null, $cond = '')
    {
        $value = $this->http->FindSingleNode("//text()[{$this->eq($field)}]" . $cond . "/ancestor::tr[1][descendant::td[normalize-space()][1][{$this->eq($field)}]]/descendant::td[normalize-space()][2]", null, true, $regexp);

        if ($value === null) {
            $value = $this->http->FindSingleNode("//text()[{$this->eq($field)}]" . $cond . "/following::text()[normalize-space()][1]/ancestor::*[count(.//td[not(.//td)]) = 1][preceding-sibling::*[{$this->eq($field)}]]", null, true, $regexp);
        }

        return $value;
    }

    private function nextTdTexts($field, $regexp = null, $cond = '')
    {
        $value = $this->http->FindNodes("//text()[{$this->eq($field)}]" . $cond . "/ancestor::tr[1][descendant::td[normalize-space()][1][{$this->eq($field)}]]/descendant::td[normalize-space()][2]//text()[normalize-space()]", null, $regexp);

        if ($value === []) {
            $value = $this->http->FindNodes("//text()[{$this->eq($field)}]" . $cond . "/following::text()[normalize-space()][1]/ancestor::*[preceding-sibling::*[{$this->eq($field)}]]//text()[normalize-space()]", null, $regexp);
        }

        return $value;
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
            return str_replace(' ', '\s+', preg_quote($s));
        }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        // $this->logger->debug('$str = ' . print_r($str, true));

        $in = [
            "#^\D+\,\s*(\d+\s*\D+\s*\d{4})\D+([\d\:]+)$#u", //Thu, 25 August 2022 from 12:00
        ];
        $out = [
            "$1, $2",
        ];

        // $in = [
        // вт, 13 мая 2025 до 12:00
        // '/^\s*(?:[[:alpha:]]+,\s*)?(\d{1,2})\s+([[:alpha:]]+)\s+(\d{4})\s+(?:\D+\s+)?(\d{1,2}:\d{2}(\s*[ap]m)?)\s*$/iu',
        // ];
        // $out = [
        //     '$1 $2 $3, $4',
        // ];

        $str = preg_replace($in, $out, $str);
//        $this->logger->debug('$str = '.print_r( $str,true));

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }

    private function detectDeadLine(Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        if (preg_match("/You may cancel your reservation without charge until\s*(\d+\s*\w+\s*\d{4}\s*[\d\:]+)\*/", $cancellationText, $m)
            || preg_match("/Pode cancelar a sua reserva sem custos até (\d+\s*\w+\s*\d{4}\s*[\d\:]+)\*?/", $cancellationText, $m)
            || preg_match("/Sie können Ihre Buchung gebührenfrei stornieren vor dem (\d+\s*\w+\s*\d{4}\s*[\d\:]+)\*?/", $cancellationText, $m)
            || preg_match("/^\s*Бесплатная отмена до (\d+\s*\w+\s*\d{4}\s*[\d\:]+)\*?/u", $cancellationText, $m)
        ) {
            $h->booked()
                ->deadline($this->normalizeDate($m[1]));
        }

        if (preg_match("/^\s*Full reservation cost is charged upon cancellation\./", $cancellationText, $m)
            || preg_match("/^\s*O custo total da reserva é cobrado após o cancelamento\./", $cancellationText, $m)
            || preg_match("/^\s*Der volle Reservierungspreis wird bei Stornierung belastet\./", $cancellationText, $m)
            || preg_match("/^\s*При отмене бронирования стоимость номера не возвращается./", $cancellationText, $m)
        ) {
            $h->booked()
                ->nonRefundable();
        }
    }

    private function assignLang()
    {
        foreach ($this->detectLang as $lang => $words) {
            foreach ($words as $word) {
                if ($this->http->XPath->query("//*[{$this->contains($word)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function currency($s)
    {
        if (preg_match("#^\s*([A-Z]{3})\s*$#", $s, $m)) {
            return $m[1];
        }
        $sym = [
            '₽' => 'RUB',
        ];

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return $s;
    }
}
