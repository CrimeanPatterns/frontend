<?php

namespace AwardWallet\Engine\ctrip\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class BookingAtHotel2023 extends \TAccountChecker
{
    public $mailFiles = "ctrip/it-628624495-cancelled.eml, ctrip/it-629891777.eml, ctrip/it-631158217.eml, ctrip/it-702492991-pt.eml, ctrip/it-822658611.eml";

    public $lang = '';

    public static $dictionary = [
        'pt' => [
            'hotelNameFromSubjectRe' => [
                '/Sua reserva no (?<name>.+?) foi (?:confirmada|cancelada gratuitamente|mantida)（PIN /u',
                '/Sua estadia no Hotel (?<name>.+?) está chegando（PIN /u',
                '/Reembolso iniciado referente à sua reserva no (?<name>.+?)（PIN /u',
            ],
            'otaConfNumber'        => ['N.º da reserva'],
            'Hi'                   => ['Olá'],
            'confNumber'           => ['Número de confirmação do hotel'],
            'statusPhrases'        => ['está'],
            'statusVariants'       => ['confirmada'],
            'cancelledPhrases'     => ['sua reserva foi cancelada'],
            'cancelledStatus'      => ['Cancelado'],
            'Refund Details'       => 'Detalhes do reembolso',
            'Booking Details'      => 'Detalhes da reserva',
            'checkIn'              => ['Check-in'],
            'checkOut'             => ['Check-out'],
            'address'              => ['Endereço'],
            'Hotel Contact Number' => 'Número de contato do hotel',
            'After'                => 'Depois de',
            'Before'               => 'Antes de',
            'Your Booking'         => 'Sua reserva',
            'Room'                 => 'quarto',
            'Booking for'          => 'Reserva para',
            'adult'                => 'adulto',
            'child'                => 'criança',
            'Cancellation Policy'  => 'Política de Cancelamento',
            'Free Cancellation'    => 'Cancelamento gratuito',
            'nonRefundablePhrases' => [
                'Essa reserva não pode ser modificada, e não haverá reembolso caso você a cancelle.',
                'Essa reserva não pode ser modificada, e não haverá reembolso caso você a cancele.',
            ],
            'Guest Names'        => 'Nomes dos hóspedes',
            'Price Details'      => 'Detalhamento do preço',
            'Total'              => 'Total',
            'totalPricePrefixes' => ['Pagar on-line', 'Pagar no hotel'],
            'costStart'          => ['quarto×noite', 'noite×quarto'],
            'feeNames'           => ['IVA'],
        ],
        'en' => [
            'hotelNameFromSubjectRe' => [
                '/Your stay at (?<name>.+?) is coming up/',
                '/Reminder for your stay at (?<name>.+?)（PIN /',
                '/Your booking at (?<name>.+?) has been (?:cancell?ed for free|confirmed)\s*（PIN/u',
            ],
            'otaConfNumber'        => ['Booking No.', 'Reservation No.', 'Reservation no.'],
            'Hi'                   => ['Hi', 'Dear'],
            'confNumber'           => ['Updated hotel confirmation number', 'Hotel confirmation number'],
            'statusPhrases'        => ['has been'],
            'statusVariants'       => ['confirmed', 'cancelled', 'canceled', 'awaiting confirmation', 'updated'],
            'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            // 'Booking Details' => '',
            'checkIn'   => ['Check-in', 'Check in'],
            'checkOut'  => ['Check-out', 'Check out'],
            'address'   => ['Address'],
            // 'Hotel Contact Number' => '',
            // 'After' => '',
            // 'Before' => '',
            'Your Booking' => ['Your Booking', 'Your booking'],
            'Room'         => ['Room', 'Bed'],
            // 'Booking for' => '',
            // 'adult' => '',
            // 'child' => '',
            // 'Cancellation Policy' => '',
            // 'Free Cancellation' => '',
            'nonRefundablePhrases' => [
                'This booking cannot be modified, and no refund will be given if you cancell it.',
                'This booking cannot be modified, and no refund will be given if you cancel it.',
            ],
            // 'Occupancy (Per Room)' => '',
            // 'Guest Names' => '',
            // 'Price Details' => '',
            // 'Total' => '',
            'totalPricePrefixes' => ['Prepay Online', 'Pay at Hotel'],
            'costStart'          => ['NightxRoom', 'NightsxRoom', 'Room×Night', 'Rooms×Night'],
            'feeNames'           => ['Other Taxes', 'Tourism Tax', 'Tourism Fee', 'Accommodation Tax', 'City Tax'],
        ],
        'es' => [
            'hotelNameFromSubjectRe' => ['/Recordatorio sobre tu reserva en (?<name>.+?)（PIN/'],
            'otaConfNumber'          => ['N.º de reserva'],
            'Hi'                     => ['¡Hola,'],
            'confNumber'             => ['Nuevo número de confirmación del hotel:'],
            // 'statusPhrases'        => ['has been'],
            // 'statusVariants'       => ['confirmed', 'cancelled', 'canceled', 'awaiting confirmation', 'updated'],
            // 'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            // 'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            'Booking Details'      => 'Datos de la reserva',
            'checkIn'              => ['Entrada'],
            'checkOut'             => ['Salida'],
            'address'              => ['Dirección'],
            'Hotel Contact Number' => 'N.º de teléfono del alojamiento',
            'After'                => 'Después del',
            'Before'               => 'Antes del',
            'Your Booking'         => 'Tu reserva',
            'Room'                 => 'habitación',
            // 'Booking for' => '',
            // 'adult' => '',
            // 'child' => '',
            // 'Cancellation Policy' => '',
            // 'Free Cancellation' => '',
            // 'nonRefundablePhrases' => [
            //     'This booking cannot be modified, and no refund will be given if you cancell it.',
            //     'This booking cannot be modified, and no refund will be given if you cancel it.',
            // ],
            // 'Occupancy (Per Room)' => '',
            'Guest Names'        => 'Nombres de los huéspedes',
            'Price Details'      => 'Desglose del precio',
            'Total'              => 'Total',
            'totalPricePrefixes' => ['Prepago en línea'],
            'costStart'          => ['habitaciónxnoche'],
            'feeNames'           => ['IVA (incl. en el precio de la habitación)'],
        ],
        'de' => [
            'hotelNameFromSubjectRe' => [
                '/Deine Buchung im (?<name>.+?) wurde bestätigt（PIN /',
                '/Erinnerung an deinen Aufenthalt im (?<name>.+?)（PIN /',
            ],
            'otaConfNumber'        => ['Buchungsnr.'],
            'Hi'                   => ['Hi'],
            'confNumber'           => ['Bestätigungsnummer des Hotels'],
            'statusPhrases'        => ['nach wurde'],
            'statusVariants'       => ['bestätigt'],
            // 'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            // 'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            'Booking Details'      => 'Buchungsdetails',
            'checkIn'              => ['Check-in'],
            'checkOut'             => ['Check-out'],
            'address'              => ['Adresse', 'Address'],
            'Hotel Contact Number' => ['Kontaktnummer des Hotels', 'Hotel Contact Number'],
            'After'                => 'Nach',
            'Before'               => 'Vor',
            'Your Booking'         => ['Deine Buchung', 'Your Booking'],
            'Room'                 => 'Zimmer',
            'Booking for'          => 'Buchung für',
            'adult'                => 'Erwachsene',
            // 'child' => '',
            'Cancellation Policy'  => ['Stornierungsbedingungen', 'Cancellation Policy'],
            'Free Cancellation'    => 'Kostenlose Stornierung',
            'nonRefundablePhrases' => [
                'Diese Buchung kann nicht geändert werden und bei einer Stornierung erfolgt keine Rückerstattung.',
            ],
            'Occupancy (Per Room)' => 'Belegung (pro Zimmer)',
            'Guest Names'          => 'Namen der Gäste',
            'Price Details'        => ['Preisdetails', 'Price Details'],
            'Total'                => 'Gesamt',
            'totalPricePrefixes'   => ['Online-Vorauszahlung'],
            'costStart'            => ['NachtxZimmer', 'Zimmer×Nächte', 'Zimmer×Nacht'],
            'feeNames'             => ['MWSt (Im Zimmerpreis inbegriffen)'],
        ],
        'ru' => [
            // 'hotelNameFromSubjectRe' => ['/(?<name>.+?)/'],
            'otaConfNumber'        => ['Номер бронирования'],
            'Hi'                   => ['Здравствуйте,'],
            // 'confNumber'           => ['Nuevo número de confirmación del hotel:'],
            // 'statusPhrases'        => ['nach wurde'],
            // 'statusVariants'       => ['bestätigt'],
            // 'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            // 'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            'Booking Details'      => 'Информация о бронировании',
            'checkIn'              => ['Заезд'],
            'checkOut'             => ['Выезд'],
            'address'              => ['Адрес'],
            'Hotel Contact Number' => 'Контактный номер отеля',
            'After'                => 'После',
            'Before'               => 'До',
            'Your Booking'         => 'Ваше бронирование',
            'Room'                 => 'номер',
            // 'Booking for' => 'Buchung für',
            // 'adult' => 'Erwachsene',
            // 'child' => '',
            // 'Cancellation Policy' => '',
            // 'Free Cancellation' => '',
            'nonRefundablePhrases' => [
                'Изменить это бронирование невозможно, и в случае его отмены возврат средств не производится. ',
            ],
            // 'Occupancy (Per Room)' => '',
            'Guest Names'        => 'Имена гостей',
            'Price Details'      => 'Информация о цене',
            'Total'              => 'Общая стоимость',
            'totalPricePrefixes' => ['Предоплата онлайн'],
            'costStart'          => ['номер×ночь'],
            'feeNames'           => ['Туристический налог', 'Налог на оказание услуг продажи (входит в стоимость проживания)'],
        ],
        'fr' => [
            'hotelNameFromSubjectRe' => [
                '/Le numéro de confirmation de votre réservation à l\'hôtel (?<name>.+?) a été envoyé（Code PIN /',
                '/Votre réservation à l\'hôtel \((?<name>.+?)\) a été confirmée（Code PIN/u',
                // '/(?<name>.+?)/',
            ],
            'otaConfNumber'        => ['N° réservation'],
            'Hi'                   => ['Bonjour'],
            'confNumber'           => ['Numéro de confirmation de l\'hôtel mis à jour'],
            // 'statusPhrases'        => ['nach wurde'],
            // 'statusVariants'       => ['bestätigt'],
            // 'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            // 'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            'Booking Details'      => 'Informations de réservation',
            'checkIn'              => ['Enregistrement'],
            'checkOut'             => ['Départ'],
            'address'              => ['Adresse'],
            'Hotel Contact Number' => "Numéro de téléphone de l'hôtel",
            'After'                => 'après',
            'Before'               => ['Avant le', 'avant'],
            'Your Booking'         => 'Votre réservation',
            'Room'                 => 'chambre',
            // 'Booking for' => 'Buchung für',
            'adult' => 'adulte',
            // 'child' => '',
            'Cancellation Policy'  => "Conditions d'annulation",
            'Free Cancellation'    => 'Annulation gratuite',
            'nonRefundablePhrases' => [
                'Cette réservation ne peut être modifiée et aucun remboursement ne sera accordé si vous l\'annulez.',
            ],
            'Occupancy (Per Room)' => 'Occupation (par chambre)',
            'Guest Names'          => 'Nom des voyageurs',
            'Price Details'        => 'Détails du prix',
            'Total'                => 'Total général',
            'totalPricePrefixes'   => ['Prépaiement en ligne'],
            'costStart'            => ['chambrexnuit'],
            'feeNames'             => ['Autres surtaxes', 'Frais municipaux', 'Taxe gouvernementale'],
        ],
        'sv' => [
            'hotelNameFromSubjectRe' => [
                '/Din bokning på ?<name>.+?) har bekräftats（PIN-kod/u',
                // '/(?<name>.+?)/',
            ],
            'otaConfNumber'        => ['Bokningsnummer'],
            'Hi'                   => ['Hej'],
            'confNumber'           => ['Hotellets bekräftelsenummer'],
            // 'statusPhrases'        => ['nach wurde'],
            // 'statusVariants'       => ['bestätigt'],
            // 'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            // 'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            'Booking Details'      => 'Bokningsuppgifter',
            'checkIn'              => ['Incheckning'],
            'checkOut'             => ['Utcheckning'],
            'address'              => ['Adress'],
            'Hotel Contact Number' => "Hotellets telefonnummer",
            // 'After'                => 'après',
            'Before'               => 'Före',
            'Your Booking'         => 'Din bokning',
            'Room'                 => 'rum',
            // 'Booking for' => 'Buchung für',
            'adult' => 'adulte',
            // 'child' => '',
            'Cancellation Policy' => "Avbokningsregler",
            // 'Free Cancellation' => '',
            'nonRefundablePhrases' => [
                'Det går inte att ändra den här bokningen och du får ingen återbetalning om du avbokar den.',
            ],
            'Occupancy (Per Room)' => 'Occupation (par chambre)',
            'Guest Names'          => 'Gästnamn',
            'Price Details'        => 'Prisuppgifter',
            'costStart'            => ['rum×Nätter'],
            'Total'                => 'Totalbelopp',
            'totalPricePrefixes'   => ['Förskottsbetala online'],
            // 'feeNames'           => ['Autres surtaxes', 'Frais municipaux', 'Taxe gouvernementale'],
        ],
        'ja' => [
            'hotelNameFromSubjectRe' => [
                '/]\s*(?<name>.+?)\s*のご予約の確認が完了しました（お問合せコード/u',
            ],
            'otaConfNumber'        => ['予約番号'],
            // 'Hi'                   => ['Hej'],
            'confNumber'           => ['予約番号'],
            // 'statusPhrases'        => ['nach wurde'],
            // 'statusVariants'       => ['bestätigt'],
            // 'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            // 'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            'Booking Details'      => '予約詳細',
            'checkIn'              => ['チェックイン'],
            'checkOut'             => ['チェックアウト'],
            'address'              => ['所在地'],
            'Hotel Contact Number' => "電話番号",
            // 'After'                => 'après',
            // 'Before'               => 'Före',
            'Your Booking'         => 'お客様のご予約',
            'Room'                 => '室',
            // 'Booking for' => 'Buchung für',
            'adult' => 'adulte',
            // 'child' => '',
            'Cancellation Policy' => "キャンセル条件",
            'Free Cancellation'   => 'キャンセル無料',
            // 'nonRefundablePhrases' => [
            //     'Det går inte att ändra den här bokningen och du får ingen återbetalning om du avbokar den.',
            // ],
            'Occupancy (Per Room)' => '定員（1室につき）',
            'Guest Names'          => '宿泊者姓名',
            'Price Details'        => '料金明細',
            'costStart'            => ['室×泊'],
            'Total'                => '合計金額',
            'totalPricePrefixes'   => ['現地払い'],
            // 'feeNames'           => ['Autres surtaxes', 'Frais municipaux', 'Taxe gouvernementale'],
        ],
        'it' => [
            'hotelNameFromSubjectRe' => [
                '/La tua prenotazione presso (?<name>.+?) è stata confermata（PIN/u',
            ],
            'otaConfNumber'        => ['Prenotazione n.'],
            'Hi'                   => ['Ciao'],
            'confNumber'           => ["Numero di conferma dell'hotel"],
            // 'statusPhrases'        => ['nach wurde'],
            // 'statusVariants'       => ['bestätigt'],
            // 'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            // 'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            'Booking Details'      => 'Dettagli della prenotazione',
            'checkIn'              => ['Check-in'],
            'checkOut'             => ['Check-out'],
            'address'              => ['Indirizzo'],
            'Hotel Contact Number' => "Numero di telefono dell'hotel",
            'After'                => 'Dopo le ore',
            'Before'               => 'Prima delle ore',
            'Your Booking'         => 'La tua prenotazione',
            'Room'                 => 'camera',
            // 'Booking for' => 'Buchung für',
            'adult' => 'adulte',
            // 'child' => '',
            'Cancellation Policy'  => "Regolamento per la cancellazione",
            'Free Cancellation'    => 'Cancellazione gratuita',
            'nonRefundablePhrases' => [
                'Questa prenotazione non può essere modificata e non verrà emesso alcun rimborso in caso di cancellazione.',
            ],
            'Occupancy (Per Room)' => 'Numero di ospiti (per camera)',
            'Guest Names'          => 'Nomi degli ospiti',
            'Price Details'        => 'Dettagli del prezzo',
            'costStart'            => ['camera×notte', 'camera×notti'],
            'Total'                => 'Totale',
            'totalPricePrefixes'   => ['Pagamento online anticipato', 'effettua il pagamento in hotel'],
            'feeNames'             => ['Tassa di soggiorno'],
        ],
        'zh' => [
            'hotelNameFromSubjectRe' => [
                '/您的(?<name>.+?)訂單已確認（PIN 碼 /u',
            ],
            'otaConfNumber'        => ['訂單編號'],
            'Hi'                   => ['您好，'],
            'confNumber'           => ["酒店確認編號"],
            // 'statusPhrases'        => ['nach wurde'],
            // 'statusVariants'       => ['bestätigt'],
            // 'cancelledPhrases'     => ['your booking has been cancelled', 'your booking has been canceled'],
            // 'cancelledStatus'      => ['Cancelled', 'Canceled'],
            // 'Refund Details' => '',
            'Booking Details'      => '訂單詳情',
            'checkIn'              => ['入住時間'],
            'checkOut'             => ['退房時間'],
            'address'              => ['地址'],
            'Hotel Contact Number' => "酒店聯絡電話",
            'After'                => '後',
            'Before'               => '前',
            'Your Booking'         => '您的訂單',
            'Room'                 => '間',
            // 'Booking for' => 'Buchung für',
            // 'adult' => 'adulte',
            // 'child' => '',
            'Cancellation Policy'  => "取消政策",
            'Free Cancellation'    => '免費取消',
            // 'nonRefundablePhrases' => [
            //     'Questa prenotazione non può essere modificata e non verrà emesso alcun rimborso in caso di cancellazione.',
            // ],
            'Occupancy (Per Room)' => '可入住人數（每間房）',
            'Guest Names'          => '旅客姓名',
            'Price Details'        => '價格詳情',
            'costStart'            => ['間x 晚'],
            'Total'                => '總額',
            'totalPricePrefixes'   => ['網上預付'],
            // 'feeNames'             => ['Tassa di soggiorno'],
        ],
    ];

    private $subjects = [
        'pt' => ['Sua reserva no '],
        'en' => ['Your booking at ', 'Your stay at '],
        'es' => ['Número de confirmación de la reserva en'],
        'de' => ['Deine Buchung im'],
        'ru' => ['ваше бронирование подтверждено'],
        'fr' => ['Votre réservation à'],
        'sv' => ['Din bokning på'],
        'ja' => ['のご予約の確認が完了しました'],
        'it' => ['Conferma della prenotazione'],
        'zh' => ['訂單已確認'],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]trip\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (is_string($phrase) && stripos($headers['subject'], $phrase) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//a/@href[{$this->contains([".trip.com/", "www.trip.com", '.trip.com%2F'])}]")->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"thanks for booking with Trip.com") or contains(normalize-space(),"Trip.com all rights reserved")]')->length === 0
        ) {
            return false;
        }

        return $this->assignLang();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }
        $email->setType('BookingAtHotel2023' . ucfirst($this->lang));

        $xpathBold = '(self::b or self::strong or contains(translate(@style," ",""),"font-weight:bold") or contains(translate(@style," ",""),"font-weight:600"))';

        $patterns = [
            'date'          => '(?:.{4,}\b\d{4}\b(?: *г\.)?|\d{4}年.{4,})', // Apr 5, 2024    |    8 jul 2024
            'time'          => '\d{1,2}(?:(?:[:：\.]| h )\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.    |    3pm
            'phone'         => '[+(\d][-+. \d)(]{4,}[\d)]', // +377 (93) 15 48 52    |    (+351) 21 342 09 07    |    713.680.2992    |    400033
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $h = $email->add()->hotel();

        $otaConfirmations = array_values(array_unique(array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('otaConfNumber'))}]/following::text()[normalize-space()][1]", null, '/^[A-Z\d]{5,}$/'))));

        if (count($otaConfirmations) === 1) {
            $otaConfirmationTitle = $this->http->FindSingleNode("descendant::text()[{$this->starts($this->t('otaConfNumber'))}][last()]", null, true, '/^(.+?)[\s:：]*$/u');
            $email->ota()->confirmation($otaConfirmations[0], $otaConfirmationTitle);
        }

        $confirmationVal = $this->http->FindSingleNode("//text()[{$this->eq($this->t('confNumber'), "translate(.,':','')")}]/following::text()[normalize-space()][1]")
            ?? $this->http->FindSingleNode("//text()[{$this->eq($this->t('confNumber'))}]/following::text()[normalize-space()][1]")
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('confNumber'))}]", null, true, "/^{$this->opt($this->t('confNumber'))}[:\s：]+([^:\s].*)$/")
        ;

        if (preg_match('/^[#\s]*([A-Z\d]{5,35}(?:\s*,\s*[A-Z\d]{5,35})*)$/', $confirmationVal, $m)) {
            $confirmationTitle = $this->http->FindSingleNode("//text()[{$this->eq($this->t('confNumber'), "translate(.,':','')")}]", null, true, '/^(.+?)[\s:：]*$/u')
                ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('confNumber'))}]", null, true, "/^({$this->opt($this->t('confNumber'))})[:\s：]+[^:\s].*$/");
            $confs = preg_split("/\s*,\s*/", $m[1]);

            foreach ($confs as $conf) {
                $h->general()->confirmation($conf, $confirmationTitle);
            }
        } elseif (count($otaConfirmations) === 1) {
            $h->general()->noConfirmation();
        }

        // it-631158217.eml
        $roomConfirmations = preg_split('/(\s*[;,]+\s*)+/', trim($confirmationVal));

        if (!preg_match('/^[A-Z\d]+$/', implode('', $roomConfirmations))) {
            $roomConfirmations = [];
        }

        $hotelName = null;
        $hotelNameTexts = $this->http->FindNodes("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('checkIn'))}] ]"
            . "/preceding::tr[not(.//tr) and normalize-space() and not({$this->eq($this->t('Booking Details'))} or {$this->eq($this->t('Refund Details'))})][position()<4]"
            . "[following-sibling::tr[.//img]]");
        $hotelNameTexts = array_merge($hotelNameTexts, $this->http->FindNodes("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('checkIn'))}] ]"
            . "/preceding::tr[not(.//tr) and normalize-space() and not({$this->eq($this->t('Booking Details'))} or {$this->eq($this->t('Refund Details'))})][position()<4]"
            . "[*[normalize-space()][2][{$this->eq($this->t('cancelledStatus'))}]]/*[normalize-space()][1]"));
        $hotelNameTexts = array_reverse($hotelNameTexts);

        foreach ($hotelNameTexts as $hotelName_temp) {
            if ($this->http->XPath->query("//text()[{$this->contains($hotelName_temp)}]")->length > 1) {
                $hotelName = $hotelName_temp;

                break;
            }
        }

        if (empty($hotelName)) {
            foreach ($this->t('hotelNameFromSubjectRe') as $re) {
                if (strpos($re, '/') === 0 && preg_match($re, $parser->getSubject(), $m) && !empty($m['name'])) {
                    $hotelName = $m['name'];
                }
            }
        }

        $xpathCheckIn = "//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('checkIn'))}] ]/*[normalize-space()][2]/descendant-or-self::*[count(node()[normalize-space() and not(self::comment())])>1][1]";
        $dateCheckIn = strtotime($this->normalizeDate($this->http->FindSingleNode($xpathCheckIn . "/descendant::text()[normalize-space()][1]", null, true, "/^{$patterns['date']}$/u")));
        $timeCheckIn = $this->http->FindSingleNode($xpathCheckIn . "/descendant::text()[normalize-space()][2]", null, true, "/^(?:{$this->opt($this->t('After'))}\s+)?({$patterns['time']})(?:\s*[-–～]|$|\s*{$this->opt($this->t('After'))})/iu");

        if ($dateCheckIn && $timeCheckIn) {
            $h->booked()->checkIn(strtotime($timeCheckIn, $dateCheckIn));
        }

        $xpathCheckOut = "//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('checkOut'))}] ]/*[normalize-space()][2]/descendant-or-self::*[count(node()[normalize-space() and not(self::comment())])>1][1]";
        $dateCheckOut = strtotime($this->normalizeDate($this->http->FindSingleNode($xpathCheckOut . "/descendant::text()[normalize-space()][1]", null, true, "/^{$patterns['date']}$/u")));
        $timeCheckOut = $this->http->FindSingleNode($xpathCheckOut . "/descendant::text()[normalize-space()][2]", null, true, "/({$patterns['time']})(?:\s*{$this->opt($this->t('Before'))})?\s*$/ui");

        if ($dateCheckOut && $timeCheckOut) {
            $h->booked()->checkOut(strtotime($timeCheckOut, $dateCheckOut));
        }

        $roomsCount = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Your Booking'))}] ]/*[normalize-space()][2]", null, true, "/\b(\d{1,3})\s*{$this->opt($this->t('Room'))}/i");
        $h->booked()->rooms($roomsCount);

        $bookingForVal = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Booking for'))}] ]/*[normalize-space()][2]");

        if (empty($bookingForVal)) {
            $bookingForVal = implode("\n", $this->http->FindNodes("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Occupancy (Per Room)'))}] ]/*[normalize-space()][2]",
                null, "/^\s*(\d+ \S+)+(\s*\W+\s*\d+ \S+)*$/"));
        }

        if (preg_match_all("/\b(\d{1,3})\s*{$this->opt($this->t('adult'))}/i", $bookingForVal, $m)) {
            $h->booked()->guests(array_sum($m[1]));
        }

        if (preg_match_all("/\b(\d{1,3})\s*{$this->opt($this->t('child'))}/i", $bookingForVal, $m)) {
            $h->booked()->kids(array_sum($m[1]));
        }

        $address = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('address'))}] ]/*[normalize-space()][2]/descendant::*[ tr[normalize-space() and not(.//tr[normalize-space()])][2] ][1]/tr[normalize-space()][1]")
            ?? $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('address'))}] ]/*[normalize-space()][2]");
        $phone = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Hotel Contact Number'))}] ]/*[normalize-space()][2]/descendant::*[ tr[normalize-space() and not(.//tr[normalize-space()])][2] ][1]/tr[normalize-space()][1]", null, true, "/^{$patterns['phone']}$/")
            ?? $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Hotel Contact Number'))}] ]/*[normalize-space()][2]", null, true, "/^{$patterns['phone']}$/");
        $h->hotel()->name($hotelName)->address($address)->phone($phone, false, true);

        $freeCancellation = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][2][{$this->eq($this->t('Free Cancellation'))}] ]/*[normalize-space()][1]");

        if (empty($freeCancellation)) {
            $freeCancellation = $this->http->FindSingleNode("//tr[count(*[normalize-space()])=2][*[normalize-space()][1][{$this->eq($this->t('Cancellation Policy'))}]]"
                . "//*[{$this->eq($this->t('Free Cancellation'))}]/following::text()[normalize-space()][1]");
        }

        if (preg_match("/^{$this->opt($this->t('Before'))}\s+(?<time>{$patterns['time']})[,\s]+(?<date>{$patterns['date']})$/i", $freeCancellation, $m)
            || preg_match("/^{$this->opt($this->t('Before'))}\s+(?<date>{$patterns['date']})[,\s]+(?<time>{$patterns['time']})$/i", $freeCancellation, $m)
        ) {
            $m['time'] = preg_replace("/^\s*(\d+) h (\d+)\s*$/", '$1:$2', $m['time']);
            $h->booked()->deadline(strtotime($m['time'], strtotime($this->normalizeDate($m['date']))));
        }

        $nonRefundableVal = $this->http->FindSingleNode("descendant::*[{$this->eq($this->t('nonRefundablePhrases'))}][last()]");

        if (!$nonRefundableVal) {
            $nonRefundableTexts = $this->http->FindNodes("descendant::text()[{$this->starts($this->t('nonRefundablePhrases'))}]");

            if (count(array_unique($nonRefundableTexts)) === 1) {
                $nonRefundableVal = array_shift($nonRefundableTexts);
            }
        }

        if ($nonRefundableVal) {
            $h->booked()->nonRefundable();
            $h->general()->cancellation($nonRefundableVal);
        }

        $statusTexts = array_filter($this->http->FindNodes("//text()[{$this->contains($this->t('statusPhrases'))}]", null, "/{$this->opt($this->t('statusPhrases'))}[:\s]+({$this->opt($this->t('statusVariants'))})(?:\s*[,.;:!?]|$)/i"));

        if (count(array_unique(array_map('mb_strtolower', $statusTexts))) === 1) {
            $status = array_shift($statusTexts);
            $h->general()->status($status);
        } elseif ($hotelName && preg_match("/^{$this->opt($this->t('statusVariants'))}$/i", $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq([$hotelName, mb_strtolower($hotelName), mb_strtoupper($hotelName)])}] ]/*[normalize-space()][2]"), $m)) {
            $h->general()->status($m[0]);
        }

        if ($this->http->XPath->query("//*[{$this->contains($this->t('cancelledPhrases'))}]")->length > 0
            || !empty($h->getStatus()) && preg_match("/^{$this->opt($this->t('cancelledStatus'))}$/i", $h->getStatus())
        ) {
            $h->general()->cancelled();
        }

        $roomType = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Guest Names'))}] ]/preceding::tr[not(.//tr) and normalize-space()][1][ descendant::text()[normalize-space()][2][{$this->contains($this->t('Room'))}] ]/descendant::text()[normalize-space()][1][ ancestor::*[{$xpathBold}] ]")
            ?? $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Guest Names'))}] ]/preceding::tr[not(.//tr) and normalize-space()][1][descendant::text()[normalize-space() and ancestor::*[{$xpathBold}]] and count(descendant::text()[normalize-space() and not(ancestor::*[{$xpathBold}])])=0]");

        if (count($roomConfirmations) > 0 || $roomType) {
            if (count($roomConfirmations) > 0 && ($roomsCount === null || $roomsCount !== null && count($roomConfirmations) === (int) $roomsCount)) {
                // it-631158217.eml
                foreach ($roomConfirmations as $roomConf) {
                    $room = $h->addRoom();
                    $room->setConfirmation($roomConf);

                    if ($roomType) {
                        $room->setType($roomType);
                    }
                }
            } elseif ($roomsCount !== null && $roomType) {
                // it-629891777.eml
                for ($i = 0; $i < (int) $roomsCount; $i++) {
                    $room = $h->addRoom();
                    $room->setType($roomType);
                }
            } elseif ($roomType) {
                $room = $h->addRoom();
                $room->setType($roomType);
            }
        }

        $travellers = [];
        $guestNamesVal = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Guest Names'))}] ]/*[normalize-space()][2]");
        $guestNames = preg_split('/(\s*[,]+\s*)+/', $guestNamesVal);

        foreach ($guestNames as $gName) {
            if (preg_match("/^{$patterns['travellerName']}$/u", $gName) > 0) {
                $travellers[] = $gName;
            } else {
                $travellers = [];

                break;
            }
        }

        if (!$guestNamesVal) {
            // it-628624495-cancelled.eml
            $travellerNames = array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Hi'))}]", null, "/^{$this->opt($this->t('Hi'))}[,\s]+({$patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));

            if (count(array_unique($travellerNames)) === 1) {
                $traveller = array_shift($travellerNames);
                $travellers = [$traveller];
            }
        }

        if (count($travellers) > 0) {
            $h->general()->travellers($travellers, true);
        }

        // price
        $xpathPriceHeader = $this->eq($this->t('Price Details'));
        $xpathTotalPrice = "count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Total'))}]";
        $totalPrice = $this->http->FindSingleNode("//tr[{$xpathPriceHeader}]/following::tr[{$xpathTotalPrice}]/*[normalize-space()][2]", null, true, "/^(?:{$this->opt($this->t('totalPricePrefixes'))}\s*)?(.*\d.*)$/");

        if (preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.‘\'\d ]*)$/u', $totalPrice, $matches)
            || preg_match('/^(?<amount>\d[,.‘\'\d ]*?)[ ]*(?<currency>[^\-\d)(]+)$/u', $totalPrice, $matches)
        ) {
            // HK$ 1,113.00    |    £ 140.56    |    145,56 €
            $currency = $this->normalizeCurrency($matches['currency']);
            $currencyCode = preg_match('/^[A-Z]{3}$/', $currency) ? $currency : null;
            $h->price()->currency($currency)->total(PriceHelper::parse($matches['amount'], $currencyCode));

            $baseFare = $this->http->FindSingleNode("//tr[{$xpathPriceHeader}]/following::tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->contains($this->t('costStart'), 'translate(.,"0123456789 ","")')}] ]/*[normalize-space()][2]", null, true, '/^.*\d.*$/');

            if (preg_match('/^(?:' . preg_quote($matches['currency'], '/') . ')?[ ]*(?<amount>\d[,.‘\'\d ]*)$/u', $baseFare, $m)
                || preg_match('/^(?<amount>\d[,.‘\'\d ]*?)[ ]*(?:' . preg_quote($matches['currency'], '/') . ')?$/u', $baseFare, $m)
            ) {
                $h->price()->cost(PriceHelper::parse($m['amount'], $currencyCode));
            }

            $discountAmounts = [];
            $discountRows = $this->http->XPath->query("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][2][starts-with(normalize-space(),'-')] and preceding::tr[{$xpathPriceHeader}] and following::tr[{$xpathTotalPrice}] ]");

            foreach ($discountRows as $dRow) {
                $dCharge = $this->http->FindSingleNode('*[normalize-space()][2]', $dRow, true, '/^[-–]+\s*(.*?\d.*?)\s*(?:\(|$)/');

                if (preg_match('/^(?:' . preg_quote($matches['currency'], '/') . ')?[ ]*(?<amount>\d[,.‘\'\d ]*)$/u', $dCharge, $m)
                    || preg_match('/^(?<amount>\d[,.‘\'\d ]*?)[ ]*(?:' . preg_quote($matches['currency'], '/') . ')?$/u', $dCharge, $m)
                ) {
                    $discountAmounts[] = PriceHelper::parse($m['amount'], $currencyCode);
                }
            }

            if (count($discountAmounts) > 0) {
                $h->price()->discount(array_sum($discountAmounts));
            }

            $feeRows = $this->http->XPath->query("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('feeNames'), 'translate(.,":","")')}] and preceding::tr[{$xpathPriceHeader}] and following::tr[{$xpathTotalPrice}] ]");

            foreach ($feeRows as $feeRow) {
                $feeCharge = $this->http->FindSingleNode('*[normalize-space()][2]', $feeRow, true, '/^(.*?\d.*?)\s*(?:\(|$)/');

                if (preg_match('/^(?:' . preg_quote($matches['currency'], '/') . ')?[ ]*(?<amount>\d[,.‘\'\d ]*)$/u', $feeCharge, $m)
                    || preg_match('/^(?<amount>\d[,.‘\'\d ]*?)[ ]*(?:' . preg_quote($matches['currency'], '/') . ')?$/u', $feeCharge, $m)
                ) {
                    $feeName = $this->http->FindSingleNode('*[normalize-space()][1]', $feeRow, true, '/^(.+?)[\s:：]*$/u');
                    $h->price()->fee($feeName, PriceHelper::parse($m['amount'], $currencyCode));
                }
            }
        }

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

    private function assignLang(): bool
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['checkIn']) || empty($phrases['address']) || empty($phrases['otaConfNumber'])) {
                continue;
            }

            if ($this->http->XPath->query("//tr[ *[not(.//tr[normalize-space()]) and normalize-space()][1][{$this->eq($phrases['checkIn'])}] ]")->length > 0
                && $this->http->XPath->query("//tr[ *[not(.//tr[normalize-space()]) and normalize-space()][1][{$this->eq($phrases['address'])}] ]")->length > 0
                && $this->http->XPath->query("//*[{$this->contains($phrases['otaConfNumber'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return $phrase;
        }

        if ($lang === '') {
            $lang = $this->lang;
        }

        if (empty(self::$dictionary[$lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$lang][$phrase];
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

    /**
     * @param string $string Unformatted string with currency
     */
    private function normalizeCurrency(string $string): string
    {
        $string = trim($string);
        $currencies = [
            // do not add unused currency!
            'USD' => ['US$'],
            'HKD' => ['HK$'],
            'SGD' => ['S$'],
        ];

        foreach ($currencies as $currencyCode => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency) {
                    return $currencyCode;
                }
            }
        }

        return $string;
    }

    /**
     * Dependencies `use AwardWallet\Engine\MonthTranslate` and `$this->lang`.
     *
     * @param string|null $text Unformatted string with date
     */
    private function normalizeDate(?string $text): ?string
    {
        if (preg_match('/^([[:alpha:]]+)\s+(\d{1,2})[,\s]+(\d{4})$/u', $text, $m)) {
            // Jun 30, 2024
            $month = $m[1];
            $day = $m[2];
            $year = $m[3];
        } elseif (preg_match('/^(\d{1,2})\.?\s+([[:alpha:]]+)\.?,?\s+(\d{4})(?: *г\.)?$/u', $text, $m)) {
            // 23 ago 2024
            $day = $m[1];
            $month = $m[2];
            $year = $m[3];
        } elseif (preg_match('/^\s*(\d{4})\s*年\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日\s*$/u', $text, $m)) {
            // 23 ago 2024
            $day = $m[3];
            $month = $m[2];
            $year = $m[1];
        }

        if (isset($day, $month, $year)) {
            if (preg_match('/^\s*(\d{1,2})\s*$/', $month, $m)) {
                return $m[1] . '/' . $day . ($year ? '/' . $year : '');
            }

            if (($monthNew = MonthTranslate::translate($month, $this->lang)) !== false) {
                $month = $monthNew;
            }

            return $day . ' ' . $month . ($year ? ' ' . $year : '');
        }

        return null;
    }
}
