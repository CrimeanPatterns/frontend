<?php

namespace AwardWallet\Engine\agoda\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class NotificationFrom extends \TAccountChecker
{
    public $mailFiles = "agoda/it-17479947.eml, agoda/it-18659218.eml, agoda/it-18827458.eml, agoda/it-19981625.eml, agoda/it-20411770.eml, agoda/it-20588325.eml, agoda/it-66912104.eml, agoda/it-70141587.eml, agoda/it-70257797.eml, agoda/it-70334943.eml, agoda/it-71435886.eml, agoda/it-74374701.eml, agoda/it-77292364.eml"; // +1 bcdtravel(html)[ja]

    public $reFrom = "agoda-messaging.com";

    public $reSubject = [
        'Notification from ',
        'Reply from ',
        'Inquiry sent to ',
    ];
    public $lang = '';
    public static $dict = [
        'en' => [
            'New message from'       => ['New message from', 'Your inquiry has been sent to '],
            'Thanks for booking,'    => 'Thanks for booking,',
            'Here is the address:'   => 'Here is the address:',
            'or'                     => 'or',
            'Booking details'        => 'Booking details',
            'BookingID'              => ['BookingID', 'Booking ID'],
            'Check in'               => ['Check in', 'Check-in'],
            'Check out'              => ['Check out', 'Check-out'],
            'Room(s)'                => 'Room(s)',
            'Guest(s)'               => 'Guest(s)',
            'adults'                 => ['adults', 'adult'],
            'children'               => ['children', 'child'],
            'Manage my booking'      => 'Manage my booking',
            'View the deal'          => 'View the deal', // junk reservation
        ],
        'ko' => [
            'New message from' => '새 메시지(발신: ',
            // 'Thanks for booking,' => '',
            // 'Here is the address:' => '',
            // 'or' => '',
            'Booking details'   => '예약 정보',
            'BookingID'         => '예약 번호',
            'Check in'          => '체크인',
            'Check out'         => '체크아웃',
            'Room(s)'           => '객실',
            'Guest(s)'          => '투숙객',
            'adults'            => ['성인'],
            'children'          => ['아동'],
            'Manage my booking' => '예약 페이지로 이동하기',
            // 'View the deal'      => '', // junk reservation
        ],
        'zh' => [
            'New message from'       => ['的新訊息', '的新消息', '您的查詢已發送至', '你的問題已經寄給'],
            'Thanks for booking,'    => '，感謝您使用Agoda訂房',
            'Here is the address:'   => '地址為：',
            'or'                     => '，或',
            'Booking details'        => ['訂單詳情', '預訂詳情', '预订详情'],
            'BookingID'              => ['訂單編號', '預訂編號', '预订编码'],
            'Check in'               => '入住日期',
            'Check out'              => '退房日期',
            'Room(s)'                => ['客房數', '客房數目', '客房数量'],
            'Guest(s)'               => ['住客人數', '客人数量'],
            'adults'                 => ['位大人', '位成人', '名大人'],
            'children'               => ['位兒童', '位小童', '名儿童'],
            'Manage my booking'      => ['管理訂單', '管理預訂', '管理我的预订'],
            'View the deal'          => '查看優惠', // junk reservation
        ],
        'es' => [
            'New message from'       => ['Nuevo mensaje de', 'Hemos enviado tu petición a'],
            'Thanks for booking,'    => 'Gracias por reservar,',
            'Here is the address:'   => 'Esta es la dirección:',
            'or'                     => ' o ',
            'Booking details'        => 'Información de la reserva',
            'BookingID'              => 'Número de reserva',
            'Check in'               => 'Check-in',
            'Check out'              => 'Check-out',
            'Room(s)'                => 'Habitación(es)',
            'Guest(s)'               => 'Huésped(es)',
            'adults'                 => ['adultos', 'adulto'],
            'children'               => ['niño', 'niños'],
            'Manage my booking'      => 'Gestionar mi reserva',
            // 'View the deal'      => '', // junk reservation
        ],
        'pt' => [
            'New message from'       => 'Nova mensagem de',
            'Thanks for booking,'    => 'Obrigado por reservar,',
            'Here is the address:'   => 'Aqui está o endereço:',
            'or'                     => ' ou ',
            'Booking details'        => 'Detalhes da reserva',
            'BookingID'              => ['ID da Reserva', 'ID de reserva'],
            'Check in'               => 'Check-in',
            'Check out'              => 'Check-out',
            'Room(s)'                => 'Quarto(s)',
            'Guest(s)'               => 'Hóspede(s)',
            'adults'                 => ['adultos', 'adulto'],
            'children'               => ['crianças', 'criança'],
            'Manage my booking'      => ['Gerenciar a minha reserva', 'Gerir a Minha Reserva'],
            // 'View the deal'      => '', // junk reservation
        ],
        'ja' => [
            'New message from' => ['さんからの新着メッセージ', 'へお問い合わせメールが送信されました'],
            // 'Thanks for booking,' => '',
            // 'Here is the address:' => '',
            // 'or' => '',
            'Booking details'   => '予約詳細',
            'BookingID'         => '予約ID',
            'Check in'          => 'チェックイン日',
            'Check out'         => 'チェックアウト日',
            'Room(s)'           => '部屋数',
            'Guest(s)'          => '宿泊者数',
            'adults'            => ['大人'],
            'children'          => ['子ども'],
            'Manage my booking' => '予約照会',
            // 'View the deal'      => '', // junk reservation
        ],
        'id' => [
            'New message from' => ['Pesan baru dari', 'Pertanyaan Anda telah dikirimkan ke'],
            // 'Thanks for booking,' => '',
            // 'Here is the address:' => '',
            // 'or' => '',
            'Booking details'   => 'Detail pesanan',
            'BookingID'         => 'ID Pesanan',
            'Check in'          => 'Check-in',
            'Check out'         => 'Check-out',
            'Room(s)'           => 'Kamar',
            'Guest(s)'          => 'Tamu',
            'adults'            => ['dewasa'],
            'children'          => ['anak'],
            'Manage my booking' => 'Pesanan Saya',
            // 'View the deal'      => '', // junk reservation
        ],
        'ru' => [
            'New message from' => 'Новое сообщение от',
            // 'Thanks for booking,' => '',
            // 'Here is the address:' => '',
            // 'or' => '',
            'Booking details'   => 'Детали бронирования',
            'BookingID'         => 'Номер бронирования',
            'Check in'          => 'Заезд',
            'Check out'         => 'Выезд',
            'Room(s)'           => 'Номер(-а)',
            'Guest(s)'          => 'Гость(-и)',
            'adults'            => ['взрослых'],
            'children'          => ['детей'],
            'Manage my booking' => 'Управлять бронированием',
            // 'View the deal'      => '', // junk reservation
        ],
        'de' => [
            'New message from' => 'Neue Nachricht von',
            // 'Thanks for booking,' => '',
            // 'Here is the address:' => '',
            // 'or' => '',
            'Booking details'   => 'Buchungsdetails',
            'BookingID'         => 'Buchungs-ID',
            'Check in'          => 'Anreise',
            'Check out'         => 'Abreise',
            'Room(s)'           => 'Zimmer',
            'Guest(s)'          => 'Gäste',
            'adults'            => ['Erwachsener', 'Erwachsene'],
            'children'          => ['Kinder'],
            'Manage my booking' => 'Meine Buchungen',
            // 'View the deal'      => '', // junk reservation
        ],
        'fr' => [
            'New message from' => 'Nouveau message de',
            // 'Thanks for booking,' => '',
            // 'Here is the address:' => '',
            // 'or' => '',
            'Booking details'   => 'Détails de la réservation',
            'BookingID'         => 'N° de réservation',
            'Check in'          => 'Enregistrement',
            'Check out'         => 'Départ',
            'Room(s)'           => 'Chambre(s)',
            'Guest(s)'          => 'Hôte(s)',
            'adults'            => ['adultes', 'adulte'],
            'children'          => ['enfants', 'enfant'],
            'Manage my booking' => 'Gérer ma réservation',
            // 'View the deal'      => '', // junk reservation
        ],
        'sl' => [
            'New message from' => 'Novo sporočilo od',
            // 'Thanks for booking,' => '',
            // 'Here is the address:' => '',
            // 'or' => '',
            'Booking details'   => 'Podrobnosti rezervacije',
            'BookingID'         => 'ID rezervacije',
            'Check in'          => 'Prijava',
            'Check out'         => 'Odjava',
            'Room(s)'           => 'Soba',
            'Guest(s)'          => 'Gost',
            'adults'            => ['odraslih oseb'],
            'children'          => ['otrok'],
            'Manage my booking' => 'Uredi mojo rezervacijo',
            // 'View the deal'      => '', // junk reservation
        ],
        'nl' => [
            'New message from'       => 'Nieuw bericht van',
            'Thanks for booking,'    => 'Bedankt voor het boeken,',
            'Here is the address:'   => 'Hier is het adres:',
            'or'                     => ' of ',
            'Booking details'        => 'Boekingsgegevens',
            'BookingID'              => 'Boekingsnummer',
            'Check in'               => 'Inchecken',
            'Check out'              => 'Uitchecken',
            'Room(s)'                => 'Kamer(s)',
            'Guest(s)'               => 'Gast(en)',
            'adults'                 => ['volwassene', 'volwassenen'],
            'children'               => ['kinderen'],
            'Manage my booking'      => 'Beheer mijn boeking',
            // 'View the deal'      => '', // junk reservation
        ],
        'it' => [
            'New message from'       => ['Nuovo messaggio da', 'La tua richiesta è stata inviata a'],
            'Thanks for booking,'    => 'Grazie per aver prenotato,',
            'Here is the address:'   => 'Questo è l’indirizzo:',
            'or'                     => ' o ',
            'Booking details'        => 'Dettagli della prenotazione',
            'BookingID'              => 'Numero di prenotazione',
            'Check in'               => 'Check-in',
            'Check out'              => 'Check-out',
            'Room(s)'                => 'Camera/e:',
            'Guest(s)'               => 'Ospite/i:',
            'adults'                 => ['adulti', 'adulto'],
            'children'               => ['bambini'],
            'Manage my booking'      => 'Gestisci la mia prenotazione',
            // 'View the deal'      => '', // junk reservation
        ],
    ];

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        foreach (self::$dict as $lang => $phrases) {
            if (!empty($phrases['Manage my booking']) && !empty($phrases['Guest(s)'])
                && $this->http->XPath->query("//*[{$this->eq($phrases['Manage my booking'])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->eq($phrases['Guest(s)'])}]")->length > 0
            ) {
                $this->lang = $lang;

                break;
            }
        }

        if (empty($this->lang)) {
            $detectedSubject = false;

            foreach ($this->reSubject as $reSubject) {
                if (strpos($parser->getSubject(), $reSubject) !== false) {
                    $detectedSubject = true;

                    break;
                }
            }

            if ($detectedSubject === true) {
                foreach (self::$dict as $lang => $phrases) {
                    if (($this->http->XPath->query("//img[@alt='Agoda.com']")->length > 0
                            || $this->http->XPath->query("//a/@href[{$this->contains(['.agoda.com'])}]")->length > 0)
                        && !empty($phrases['New message from']) && !empty($phrases['View the deal'])
                        && $this->http->XPath->query("//*[{$this->contains($phrases['New message from'])}]")->length > 0
                        && $this->http->XPath->query("//*[{$this->eq($phrases['View the deal'])}]")->length > 0
                    ) {
                        $email->setIsJunk(true, 'Not confirmed reservation');
                        $a = explode('\\', __CLASS__);
                        $class = end($a);
                        $email->setType($class . ucfirst($this->lang));
                    }
                }
            }
        }

        if (empty($this->lang)) {
            $this->logger->debug('can\'t determine a language');

            return $email;
        }

        $a = explode('\\', __CLASS__);
        $class = end($a);
        $email->setType($class . ucfirst($this->lang));

        $this->parseEmail($email);

        return $email;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//img[@alt='Agoda.com']")->length > 0
         || $this->http->XPath->query("//a/@href[{$this->contains(['.agoda.com'])}]")->length > 0
        ) {
            foreach (self::$dict as $lang => $phrases) {
                if (!empty($phrases['New message from']) && !empty($phrases['Manage my booking'])
                    && !empty($phrases['BookingID']) && !empty($phrases['Guest(s)'])
                    && $this->http->XPath->query("//*[{$this->eq($phrases['Manage my booking'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($phrases['New message from'])}]")->length > 0
                    && $this->http->XPath->query("//tr[*[1][{$this->eq($phrases['BookingID'])}]][*[{$this->eq($phrases['Guest(s)'])}]]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }

            foreach (self::$dict as $lang => $phrases) {
                if (!empty($phrases['New message from']) && !empty($phrases['View the deal'])
                    && $this->http->XPath->query("//*[{$this->contains($phrases['New message from'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->eq($phrases['View the deal'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && isset($headers['subject']) && stripos($headers['from'],
                $this->reFrom) !== false && isset($this->reSubject)
        ) {
            foreach ($this->reSubject as $reSubject) {
                if (strpos($headers["subject"], $reSubject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, $this->reFrom) !== false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dict);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dict);
    }

    private function parseEmail(Email $email): bool
    {
        $hotelInfo = '';
        $hotelTable = "//tr[*[1][{$this->eq($this->t('BookingID'))}]][*[{$this->eq($this->t('Guest(s)'))}]]";
        $columns = $this->http->XPath->query($hotelTable . "/*[not(@rowspan)]")->length;

        for ($i = 1; $i <= $columns; $i++) {
            $hotelInfo .= $this->http->FindSingleNode($hotelTable . "/*[not(@rowspan)][{$i}]")
                . '    ' . $this->http->FindSingleNode($hotelTable . "/following-sibling::tr[1]/*[{$i}]")
            . "\n";
        }

        $this->logger->debug('$hotelInfo = ' . print_r($hotelInfo, true));

        $h = $email->add()->hotel();

        // General
        $h->general()
            ->confirmation($this->re("/^\s*{$this->opt($this->t('BookingID'))} {3,}(\d{5,})\s*$/m", $hotelInfo));

        $traveller = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Thanks for booking,'))}]",
            null, true, "/^\s*{$this->opt($this->t('Thanks for booking,'))}\s*([[:alpha:]][[:alpha:]\- ]+?)[!\.]/u");

        if (in_array($this->lang, ['zh'])) {
            $traveller = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Thanks for booking,'))}]",
                null, true, "/^\s*([[:alpha:]][[:alpha:]\- ]+?)\s*{$this->opt($this->t('Thanks for booking,'))}/u");
        }

        if (!empty($traveller)) {
            $h->general()
                ->traveller($traveller, false);
        }

        // Hotel
        $h->hotel()
            ->name($this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking details'))}]/following::text()[normalize-space()][1]"));
        $address = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Here is the address:'))}]",
            null, true, '/' . $this->opt($this->t("Here is the address:")) . '\s*(.+)' . $this->opt($this->t("or")) . '\s*' . preg_quote('https://www.google.com/maps', '/') . '/u');

        if (!empty($address)) {
            $h->hotel()
                ->address($address);
        } else {
            $h->hotel()
                ->noAddress();
        }

        // Booked
        $h->booked()
            ->checkIn($this->normalizeDate($this->re("/^\s*{$this->opt($this->t('Check in'))} {3,}(.+?)\s*$/m", $hotelInfo)))
            ->checkOut($this->normalizeDate($this->re("/^\s*{$this->opt($this->t('Check out'))} {3,}(.+?)\s*$/m", $hotelInfo)))
            ->rooms($this->re("/^\s*{$this->opt($this->t('Room(s)'))} {3,}[^\d\s]* *(\d+)\s*[^\d\s]*\s*$/m", $hotelInfo))
            ->guests($this->re("/{$this->opt($this->t('Guest(s)'))} {3,}(\d{1,3}) *{$this->opt($this->t('adults'))}/mu", $hotelInfo)
                ?? $this->re("/^\s*{$this->opt($this->t('Guest(s)'))} {3,}{$this->opt($this->t('adults'))} *(\d{1,3})(?:\b|명|名)/mu", $hotelInfo))
            ->kids($this->re("/^\s*{$this->opt($this->t('Guest(s)'))} {3,}.+, *(\d{1,3}) *{$this->opt($this->t('children'))}/mu", $hotelInfo)
                ?? $this->re("/^\s*{$this->opt($this->t('Guest(s)'))} {3,}.+, *{$this->opt($this->t('children'))} *(\d{1,3})(?:\b|명|名)/mu", $hotelInfo))
        ;

        return true;
    }

    private function normalizeDate($date)
    {
        if (in_array($this->lang, ['pt'])) {
            $date = preg_replace("/^\s*(\d{1,2})\/(\d{2})\/(\d{4})\s*$/", '$1.$2.$3', $date);
        }
        $this->logger->debug('date = ' . $date);
        $in = [
            // 2025. 3. 8.;  2025年3月17日;  2025/03/15
            '/^\s*(\d{4})(?:\. *|年|\/)(\d{1,2})(?:\. *|月|\/)(\d{1,2})(?:\.|日|)\s*$/u',
            // 10 de mai de 2025;  10 may 2025;  30 Apr. 2025;  14 мар. 2025 г.;  2. maj 2025
            '/^\s*(\d{1,2})(?:\.? +| +de +)([[:alpha:]]+)(?:\.? +| +de +)(\d{4})(?:\s*г\.)?\s*$/u',
            // Mar. 21, 2025
            '/^\s*([[:alpha:]]+)\.?\s*(\d{1,2})\s*,\s*(\d{4})\s*$/u',
        ];
        $out = [
            '$1-$2-$3',
            '$1 $2 $3',
            '$2 $1 $3',
        ];
        $str = strtotime($this->dateStringToEnglish(preg_replace($in, $out, $date)));

        return $str;
    }

    private function t($s)
    {
        if (!isset($this->lang) || !isset(self::$dict[$this->lang][$s])) {
            return $s;
        }

        return self::$dict[$this->lang][$s];
    }

    private function assignLang(): bool
    {
        if (!isset(self::$dict, $this->lang)) {
            return false;
        }

        foreach (self::$dict as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['New message from']) || empty($phrases['Manage my booking'])
                || empty($phrases['BookingID']) || empty($phrases['Guest(s)'])
            ) {
                continue;
            }

            if ($this->http->XPath->query("//*[{$this->eq($phrases['Manage my booking'])}]")->length > 0
                && $this->http->XPath->query("//*[{$this->contains($phrases['New message from'])}]")->length > 0
                && $this->http->XPath->query("//tr[*[1][{$this->eq($phrases['BookingID'])}]][*[{$this->eq($phrases['Guest(s)'])}]]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function dateStringToEnglish($date)
    {
        if (preg_match('#[.[:alpha:]]+#iu', $date, $m)) {
            $monthNameOriginal = $m[0];

            if (!empty($translatedMonthName = MonthTranslate::translate($monthNameOriginal, $this->lang))
                || $translatedMonthName = MonthTranslate::translate(trim($monthNameOriginal, '.'), $this->lang)
            ) {
                return preg_replace("#" . preg_quote($monthNameOriginal) . "#i", $translatedMonthName, $date);
            }
        }

        return $date;
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
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }
}
