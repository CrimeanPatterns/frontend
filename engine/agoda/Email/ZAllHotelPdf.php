<?php

namespace AwardWallet\Engine\agoda\Email;

use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Common\Parser\Util\PriceHelper;

class ZAllHotelPdf extends \TAccountChecker
{
    public $mailFiles = "agoda/it-1.eml, agoda/it-2947986.eml, agoda/it-3047707-zh.eml, agoda/it-3088645.eml, agoda/it-3174405.eml, agoda/it-3950275-da.eml, agoda/it-3985756.eml, agoda/it-3988892.eml, agoda/it-45627770-it.eml, agoda/it-48381597-sv.eml, agoda/it-9181422-nl.eml, agoda/it-9203508-pl.eml, agoda/it-9207448.eml, agoda/it-9388091-no.eml, agoda/it-9409377-id.eml, agoda/it-9427799-pt.eml, agoda/it-9576952-ko.eml, agoda/it-9633155-zh.eml, agoda/it-9653543-ru.eml, agoda/it-9654549-ru.eml, agoda/it-9722522-zh.eml, agoda/it-9722683-zh.eml, agoda/it-9727721-es.eml, agoda/it-9727747-es.eml, agoda/it-900956510.eml";

    private $subjects = [
        'zh' => ['確認訂單編號', '预订确认'],
        'ja' => ['確認メール、予約'],
        'it' => ['Conferma della Prenotazione'],
        'ar' => ['تأكيد  لحجز رقم'],
        'sv' => ['Bekräftelse för Boknings-ID'],
        'en' => ['Booking confirmation with Agoda - Booking ID'],
    ];

    private $detects = [
        'zh'  => ['預訂編號:', '預訂編號：'],
        'zh2' => 'Agoda價格保證',
        'zh3' => '的预订已成功确认，并享有Agoda价格保证。',
        'ja'  => '空港から宿泊施設までの交通手段を手配できます',
        'ja2' => 'アゴダ®ベスト料金保証',
        'ja3' => 'アゴダのセルフサービスページより予約を管理できます',
        'de'  => ['wurde bestätigt und mit der Agoda Preisgarantie abgeschlossen', 'Bitte legen Sie beim Check-in eine elektronische oder ausgedruckte Kopie dieses Buchungsbelegs vor'],
        "da"  => 'er bekræftet og afsluttet med Agodas Prisgaranti',
        "pl"  => 'gwarancją ceny Agoda, została potwierdzona i zakończona',
        "pt"  => 'confirmada e completa com garantia de preço Agoda',
        "id"  => 'dibuat dengan jaminan harga Agoda',
        "es"  => 'confirmada con la garantía de precio Agoda',
        "no"  => 'bekreftet og dekkes av Agodas prisgaranti',
        "ru"  => 'завершено с гарантией лучшей цены Agoda',
        'ru2' => 'Пожалуйста, предъявите электронную или распечатанную копию ',
        "ko"  => '최저가 보장이 적용됩니다',
        "fi"  => 'Esitä joko elektroninen tai paperinen varausvoucher',
        // 'fr' => 'Utilisez le Self Service Agoda pour gérer votre réservation',
        'nl'  => 'U kunt uw boeking gemakkelijk beheren met onze zelfservice',
        'fr'  => 'votre réservation est confirmée (Garantie de prix Agoda',
        'it'  => 'completa e confermata e con la garanzia sul prezzo di Agoda',
        'ar'  => 'مؤكد و مكتمل مع ضمان أفضل سعر من أجودا',
        'sv'  => 'är bekräftad och klar, med Agoda prisgaranti',
        'th'  => 'หากมีขอ้ สงสัยหรือต้องการสอบถามเพิมเติม กรุณาไปที www.agoda.com/support',
        'sv2' => 'Skaffa en billig hyrbil och spara pengar när du bokar bilen online idag',
        'en'  => 'Please present either an electronic or paper copy of your',
        'en2' => 'Hotel Contact Number',
        'en3' => 'is confirmed and complete with Agoda price guarantee',
        'en4' => '‫‪Booking Reference No :‬‬',
        'en5' => 'Ваше бронювання підтверджене та завершене!', // TODO: Writing in ua and en languages
        'en6' => 'Agoda price guarantee',
        'en7' => 'Booked And Payable By',
    ];

    public $lang = 'en';

    public static $dictionary = [
        'zh' => [
            // PDF-1
            'ConfirmationNumber'  => ['预订编码:', '订单号', '編號:', '預訂編號:'],
            'Cancellation Policy' => '取消預訂條款',
            'Hotel policy' => ['飯店政策', '酒店政策'],
            
            // HTML-1
            'CheckInDate'         => ['入住日期:', '入住日期：'],
            'CheckOutDate'        => ['退房日期:', '退房日期：'],
            'Guests'              => ['入住人数:', '入住人数：', '入住人數:', '入住人數：'],
            'CancellationPolicy'  => "contains(text(), '取消') and contains(text(), '修改政策')",
            'total-1'             => ['总价:', '总价：', '刷卡總金額:', '刷卡總金額：', '從信用卡扣除總金額:', '從信用卡扣除總金額：', '總金額:', '總金額：', '總價格:', '總價格：'],
            'RoomTypeDescription' => ['特殊要求:', '特殊要求：', '特殊需求:', '特殊需求：'],
            'StatusConfirmed'     => ['您的预订已成功确认', '您的預訂已經完成並確認', '預訂成功，訂單已經獲得確認'],
            //'after' => '後', 'before' => '前',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'ja' => [
            // PDF-1
            'ConfirmationNumber'  => 'ご予約',
            // 'Cancellation Policy' => '',
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => ['チェックイン日:', 'チェックイン日：'],
            'CheckOutDate'        => ['チェックアウト日:', 'チェックアウト日：'],
            'Guests'              => ['定員:', '定員：'],
            'CancellationPolicy'  => "contains(text(), 'キャンセルポリシー') and contains(text(), '変更ポリシー')",
            'total-1'             => ['合計金額:', '合計金額：', 'カード課金額:', 'カード課金額：'],
            'RoomTypeDescription' => ['特別なリクエスト:', '特別なリクエスト：'],
            'StatusConfirmed'     => 'ご予約が確定しました!',
            'after'               => '以降', 'before' => 'まで',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'de' => [
            // PDF-1
            'ConfirmationNumber'  => 'Buchungs-ID:',
            'Cancellation Policy' => 'Stornierungsbedingungen',
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => ['Check-in:', 'Check-In:'],
            'CheckOutDate' => ['Check-out:', 'Check-Out:'],
            'Guests' => 'Belegung:',
            'CancellationPolicy'  => "contains(text(), 'Stornierungs') and contains(text(), 'Änderungsbedingungen')",
            'total-1'             => ['Gesamter Abbuchungsbetrag', 'Gesamtpreis'],
            'RoomTypeDescription' => 'Sonderwünsche:',
            'StatusConfirmed'     => 'Ihre Buchung wurde bestätigt',
            'after'               => 'nach', 'before' => 'vor',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'nl' => [
            // PDF-1
            'ConfirmationNumber'  => 'Boekingsnummer :',
            'Cancellation Policy' => 'Annuleringsbeleid',
            'Hotel policy' => 'Hotelbeleid',
            
            // HTML-1
            'CheckInDate'         => 'Inchecken:',
            'CheckOutDate' => 'Uitchecken:',
            'Guests' => 'Bezetting:',
            'CancellationPolicy'  => "contains(text(), 'Annulerings') and contains(text(), 'Aanpassingsbeleid')",
            'total-1'             => ['Totaalprijs:', 'Totaal belast op kaart:'],
            'RoomTypeDescription' => 'Speciale verzoeken:',
            'StatusConfirmed'              => 'Uw boeking is bevestigd',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'da' => [
            // PDF-1
            'ConfirmationNumber'  => 'Reservations-ID:',
            // 'Cancellation Policy' => '',
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => 'Indtjekning:',
            'CheckOutDate' => 'Udtjekning:',
            'Guests' => 'Belægning:',
            'CancellationPolicy'  => "contains(text(), 'Afbestillings') and contains(text(), 'ændringspolitik')",
            'total-1'             => ['Total Price:', 'Samlet opkrævning fra kort:'],
            'RoomTypeDescription' => 'Specielle forespørgsler:',
            'StatusConfirmed'              => 'er bekræftet',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'pl' => [
            // PDF-1
            'ConfirmationNumber'  => 'ID rezerwacji:',
            'Cancellation Policy' => 'Regulamin anulowania',
            'Hotel policy' => 'zgodnie z zasadami hotelu',
            
            // HTML-1
            'CheckInDate'         => 'Zameldowanie:',
            'CheckOutDate' => 'Wymeldowanie:',
            'Guests' => 'Ilość osób:',
            'CancellationPolicy'  => "contains(text(), 'anulowania') and contains(text(), 'Polityka')",
            'total-1'             => ['Kartę obciążono łączną kwotą:'],
            'RoomTypeDescription' => 'Specjalne życzenia:',
            'StatusConfirmed'              => 'została potwierdzona',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'pt' => [
            // PDF-1
            'ConfirmationNumber'  => 'ID de Reserva :',
            'Cancellation Policy' => 'Política de cancelamentos',
            'Hotel policy' => 'Política do hotel',
            
            // HTML-1
            'CheckInDate'         => 'Entrada:',
            'CheckOutDate' => 'Saída:',
            'Guests' => 'Ocupação:',
            'CancellationPolicy'  => "contains(text(), 'Política') and contains(text(), 'Cancelamento')",
            'total-1'             => ['Custo total para cartão'],
            'RoomTypeDescription' => 'Pedidos Especiais:',
            'StatusConfirmed'              => 'está confirmada',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'id' => [
            // PDF-1
            'ConfirmationNumber'  => 'ID Pemesanan:',
            'Cancellation Policy' => 'Kebijakan Pembatalan',
            'Hotel policy' => 'Kebijakan hotel',
            
            // HTML-1
            'CheckInDate'         => 'Check-in:',
            'CheckOutDate' => 'Check-out:',
            'Guests' => 'Okupansi:',
            'CancellationPolicy'  => "contains(text(), 'Kebijakan') and contains(text(), 'Pembatalan')",
            'total-1'             => ['Total yang dibebankan ke kartu'],
            'RoomTypeDescription' => 'Permintaan khusus:',
            'StatusConfirmed'              => 'telah dikonfirmasi',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'es' => [
            // PDF-1
            'ConfirmationNumber'  => ['ID de Reserva:', 'Tu ID de reserva:', 'ID Reserva:'],
            'Cancellation Policy' => 'Política de Cancelación',
            'Hotel policy' => 'Política del Hotel',
            
            // HTML-1
            'CheckInDate'         => 'Entrada:',
            'CheckOutDate' => 'Salida:',
            'Guests' => 'Capacidad:',
            'CancellationPolicy'  => "contains(text(), 'Política') and contains(text(), 'Cancelación')",
            'total-1'             => ['A cobrar en la tarjeta', "Precio Final:", 'Cargo Total en Tarjeta:'],
            'RoomTypeDescription' => 'Solicitud especial:',
            'StatusConfirmed'              => 'está completa',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'no' => [
            // PDF-1
            'ConfirmationNumber'  => 'Booking-ID :',
            'Cancellation Policy' => "Regler for avbestilling",
            'Hotel policy' => 'hotellregel',
            
            // HTML-1
            'CheckInDate'         => 'Innsjekking:',
            'CheckOutDate' => 'Utsjekking:',
            'Guests' => 'Kapasitet:',
            'CancellationPolicy'  => "contains(text(), 'Avbestilling') and contains(text(), 'endringsregler')",
            'total-1'             => ['Endelig belastning av kortet'],
            'RoomTypeDescription' => 'Forespørsler',
            'StatusConfirmed'              => 'er bekreftet',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'ru' => [
            // PDF-1
            'ConfirmationNumber'  => 'Номер бронирования :',
            'Cancellation Policy' => 'Правила отмены',
            'Hotel policy' => 'Политика отеля',
            
            // HTML-1
            'CheckInDate'         => 'Дата заезда:',
            'CheckOutDate' => 'Дата выезда:',
            'Guests' => 'Размещение:',
            'CancellationPolicy'  => "contains(text(), 'Политика') and contains(text(), 'отмены')",
            'total-1'             => ['Общая сумма:', 'Полная сумма к списанию с карты:'],
            'RoomTypeDescription' => 'Специальные запросы:',
            'StatusConfirmed'              => 'бронирование подтверждено',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'ko' => [
            // PDF-1
            'ConfirmationNumber'  => '예약 번호:',
            'Cancellation Policy' => '[취소 정책]',
            'Hotel policy' => '호텔 정책',
            
            // HTML-1
            'CheckInDate'         => ['의 예약이 확정:', '체크인 날짜:'],
            'CheckOutDate' => '체크아웃 날짜:',
            'Guests' => '총 숙박 인원:',
            'CancellationPolicy'  => "contains(text(), '취소 및 변경 정책')",
            'total-1'             => ['총 카드 결제액:'],
            'RoomTypeDescription' => '특별요청사항:',
            'StatusConfirmed'              => '예약이 확정',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'fi' => [
            // PDF-1
            'ConfirmationNumber'  => 'Varauksesi ID:',
            // 'Cancellation Policy' => '',
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => 'Check-in:',
            'CheckOutDate' => 'Check-out:',
            'Guests' => 'Saatavuus:',
            'CancellationPolicy'  => "contains(text(), 'Peruutus-') and contains(text(), 'ja vaihtokäytäntö')",
            'total-1'             => ['Kokonaisveloitus kortilta:'],
            'RoomTypeDescription' => 'Erityispyynnöt:',
            'StatusConfirmed'              => 'бронирование подтверждено',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'fr' => [
            // PDF-1
            'ConfirmationNumber'  => 'Numéro de réservation :',
            'Cancellation Policy' => ["Conditions d'annulation et de modification", "Conditions d annulation"],
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => 'Arrivée :',
            'CheckOutDate' => 'Départ :',
            'Guests' => 'Occupation :',
            'CancellationPolicy'  => "contains(text(), 'Conditions') and contains(text(), \"d'annulation\") and not(ancestor::a)",
            'total-1'             => ['Montant total débité de votre carte :'],
            'RoomTypeDescription' => 'Demandes spéciales :',
            'StatusConfirmed'     => 'réservation est confirmée',
            'after'               => 'Après', 'before' => 'Avant',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'it' => [
            // PDF-1
            'ConfirmationNumber'  => 'Numero della prenotazione :',
            'Cancellation Policy' => 'Politica di cancellazione',
            'Hotel policy' => "Politica dell'Hotel",
            
            // HTML-1
            'CheckInDate'         => 'Check-in:',
            'CheckOutDate' => 'Check-out:',
            'Guests' => 'Ospiti:',
            'CancellationPolicy'  => "contains(text(), 'Cancellazione') and contains(text(), 'Termini')",
            'total-1'             => ['Prezzo totale:'],
            'RoomTypeDescription' => 'Richieste speciali:',
            'StatusConfirmed'     => 'è confermata e completa',
            'after'               => 'dopo le', 'before' => 'entro le',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'ar' => [
            // PDF-1
            'ConfirmationNumber'  => 'ﺭﻗﻢ ﺍﻟﺤﺠﺰ',
            'Cancellation Policy' => ['سياسة الإلغاء والتغيير'],
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => 'تسجيل الدخول:',
            'CheckOutDate'        => 'تسجيل الخروج:',
            'Guests'              => 'الإشغال:',
            'CancellationPolicy'  => "contains(text(), 'إلغاء والتغيير') and contains(text(), 'سياسة ال')",
            'total-1'             => ['السعر الكلي:', 'السعر الكلي'],
            'RoomTypeDescription' => 'طلبات خاصة:',
            'StatusConfirmed'     => ['حجزك مؤكد و مك'],
            'after'               => 'بعد', 'before' => 'قبل',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'sv'=> [
            // PDF-1
            'ConfirmationNumber'  => 'Boknings-ID :',
            'Cancellation Policy' => ['Avboknings- och ändringsvillkor'],
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => 'Incheckning:',
            'CheckOutDate'        => 'Utcheckning:',
            'Guests'              => 'Gäster:',
            'CancellationPolicy'  => "contains(text(), 'Avboknings') and contains(text(), '- och ändringsvillkor')",
            'total-1'             => ['Totalbelopp debiterat på kortet:'],
            'RoomTypeDescription' => 'Särskilda önskemål:',
            'StatusConfirmed'     => 'är bekräftad',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'th' => [
            // PDF-1
            'ConfirmationNumber'  => 'หมายเลขการจอง:',
            'Cancellation Policy' => ['นโยบายการยกเล กการจอง'],
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => 'เช็คอิน:',
            'CheckOutDate'        => 'เช็คเอาต์:',
            'Guests'              => 'ผู้เข้าพัก:',
            'CancellationPolicy'  => "contains(text(), 'นโยบายการยกเลิกและ') and contains(text(), 'การเปลี่ยนแปลงการจองห้องพัก')",
            'total-1'             => ['จำนวนเงินที่เรียกเก็บจากบัตร:'],
            // 'RoomTypeDescription' => '',
            'StatusConfirmed'     => 'ได้รับการยืนยันเรียบร้อยแล้ว',
            'after'               => 'หลัง', 'before' => 'ก่อน',

            // HTML-2
            // 'Hotels' => '',
            // 'bookingID' => '',
            // 'Check in' => '',
            // 'Check out' => '',
            // 'total-2' => '',
        ],
        'en' => [ // always last!
            // PDF-1
            'ConfirmationNumber'  => 'Booking ID:',
            'Cancellation Policy' => ['Cancellation and Change Policy', 'Cancellation Policy', 'Cancellation policy'],
            // 'Hotel policy' => '',
            
            // HTML-1
            'CheckInDate'         => 'Check in:',
            'CheckOutDate'        => 'Check out:',
            'Guests' => 'Occupancy:',
            'CancellationPolicy'  => "contains(text(), 'Cancellation') and contains(text(), 'Policy')",
            'total-1'             => ['Total price', 'Total Price:', 'Total Charge to Card:', 'Total charge to card:', 'Total Charge to Credit Card', 'Total Due / Charge to ', 'Total amount pre-authorized'],
            'RoomTypeDescription' => 'Special requests:',
            'StatusConfirmed'     => 'is confirmed',
            // 'after' => '', 'before' => '',

            // HTML-2
            // 'Hotels' => '',
            'bookingID' => 'BookingID',
            // 'Check in' => '',
            // 'Check out' => '',
            'total-2' => 'Total Charge',
        ],
    ];

    private $htmlOrPlain = '';

    private $patterns = [
        'time' => '\d{1,2}:\d{2}(?:\s*[AaPp][Mm])?', // 4:19PM
        'phone' => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52  |  (+351) 21 342 09 07  |  713.680.2992
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/@agoda\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ((!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true)
            && (!array_key_exists('subject', $headers) || strpos($headers['subject'], 'Agoda') === false)
        ) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array)$phrases as $phrase) {
                if (is_string($phrase) && array_key_exists('subject', $headers) && stripos($headers['subject'], $phrase) !== false)
                    return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (empty($textPdf) || stripos($textPdf, 'agoda') === false) {
                continue;
            }

            $textPdf = str_replace(chr(194) . chr(160), ' ', $textPdf);

            if ($this->detectBody($textPdf)) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->htmlOrPlain = $parser->getHTMLBody();

        if (empty($this->htmlOrPlain)) {
            $this->htmlOrPlain = $parser->getPlainBody();
        }

        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (empty($textPdf)) {
                continue;
            }
            $textPdf = str_replace([mb_chr(0xAD), mb_chr(0x202A), mb_chr(0x202B), mb_chr(0x202C)], ['-', '', '', ''], $textPdf); // hidden symbols
            $this->parsePdf_1($email, $textPdf);
        }

        $email->setType('ZAllHotelPdf' . ucfirst($this->lang));
        return $email;
    }

    private function parsePdf_1(Email $email, string $pdf): bool
    {
        if (!preg_match("/^[ ]*(Booking ID)[: ]*:[:\s]*(\d{7,})(?:[ ]{2}|$)/m", $pdf, $m)) {
            $this->logger->alert('maybe other format...');

            return false;
        }

        $pdf = str_replace('：', ':', $pdf);
        $this->assignLang($pdf);
        $h = $email->add()->hotel();
        $h->general()->confirmation($m[2], $m[1]);
        $bookingID = $m[2];

        if (preg_match("/^[ ]*(Booking Reference No)[: ]*:[:\s]*([A-z\d]{4,})(?:[ ]{2}|$)/m", $pdf, $m)) {
            $h->general()->confirmation($m[2], $m[1]);
        }

        $columns1 = $this->re("/^([ ]*Booking ID[: ]*:[: ]*.+?)^[^\n]*(?i)(?:\bCancel|取消|취소하실|لاغٍ|\bRefunderbar\b|\bAvbokningar\b|\bRestitutie\b|\bBezzwrotna\b|\bKansellering\b|\bPembatalan\b|\bОтмены\b|\bВозвращается\b)/msu", $pdf);
        $table1Pos = [0];

        if (preg_match("/(.+[ ]{2})Number of Rooms[: ]*:/i", $columns1, $matches)) {
            $table1Pos[] = mb_strlen($matches[1]) - 1;
        }
        $table1 = $this->splitCols($columns1, $table1Pos);

        if (count($table1) !== 2) {
            $this->logger->debug('Wrong table1!');

            return false;
        }
        $table1[0] = $this->removeDoubleFields($table1[0]);
        $table1[1] = $this->removeDoubleFields($table1[1]);

        if (preg_match("/^[ ]*Number of Rooms[: ]*:[:\s]*(\d{1,3})[ ]*$/m", $table1[1], $m)) {
            $h->booked()->rooms($m[1]);
        }

        // travellers
        $travellers = [];

        if (preg_match("/^[ ]*Client[: ]*:[: ]*([[:alpha:]][-,.\'[:alpha:]\s]*[[:alpha:]])[ ]*$\s+^[ ]*Member ID/mu", $table1[0], $m)) {
            $clientNames = preg_split('/\s*,\s*/', preg_replace('/\s+/', ' ', $m[1]));
            $travellers = array_merge($travellers, $clientNames);
        }
        
        if (count($travellers) > 0) {
            $h->general()->travellers(array_unique($travellers));
        }

        if (preg_match("/^[ ]*(Member ID)[: ]*:[: ]*(\d{7,})[ ]*$/m", $table1[0], $m)) {
            $guestName = count($travellers) > 0 ? array_shift($travellers) : null;
            $h->program()->account($m[2], false, $guestName, $m[1]);
        }

        $html = [
            'guests' => null,
            'checkInTime' => null,
            'checkOutTime' => null,
            'cancellationPolicy' => null,
            'roomDescription' => null,
            'totalPrice' => null,
            'status' => null,
        ];
        $this->getFieldsByHtml_1($html);
        $this->getFieldsByHtml_2($html, $bookingID);

        $guests = $this->re("/^[ ]*(?:Number of Adults|Max Occupancy)[: ]*:[:\s]*(\d{1,3})[ ]*$/m", $table1[1]);

        if ($html['guests'] !== null && (empty($guests) || (int) $guests < (int) $html['guests'])) {
            $h->booked()->guests($html['guests']);
        } else {
            $h->booked()->guests($guests);
        }

        $h->booked()->kids($this->re("/^[ ]*Number of Children[: ]*:[:\s]*(\d{1,3})[ ]*$/m", $table1[1]), false, true);

        $room = $h->addRoom();

        $roomType = $this->re("/^[ ]*Room Type[: ]*:[:\s]*([^:]+)[ ]*$\s+^[ ]*Promotion[: ]*:/m", $table1[1]);
        $room->setType(preg_replace('/\s+/', ' ', $roomType));

        // hotelName
        $hotel = $this->re("/^[ ]*(?:Hotel|Property)[: ]*:[:\s]*([^:\n]{3,}?)[ ]*$/m", $table1[0]);
        $mas = explode("\n", $hotel);

        if (count($mas) == 2 && mb_stripos($mas[1], $mas[0]) === false) {
            $h->hotel()->name(preg_replace('/\s+/', ' ', $mas[0]));
        } else {
            $h->hotel()->name(preg_replace('/\s+/', ' ', $hotel));
        }

        // address
        $address = $this->re("/^[ ]*Address[: ]*:[:\s]*([^:]{3,}?)[ ]*$\s+^[ ]*(?:Hotel Contact Number|Property Contact Number)[: ]*:/m", $table1[0])
            ?? $this->re("/\n[ ]*Address[: ]*:[:\s]*([^:]{3,}?)\s*$/", $table1[0]) // it-3988892.eml
        ;

        if (!empty($address)) {
            $h->hotel()->address(preg_replace('/\s+/', ' ', $address));
        }

        $addressParts = explode(',', $h->getAddress());

        if (count($addressParts) === 5 && $this->re('/(\d+)/', $addressParts[4]) !== null) {
            $da = $h->hotel()->detailed();
            $da
                ->address(trim($addressParts[0]))
                ->city(trim($addressParts[2]))
                ->country(trim($addressParts[3]))
                ->zip(trim($addressParts[4]));
        }

        // phone
        $phone = $this->re("/^[ ]*(?:Hotel Contact Number|Property Contact Number)[: ]*?:[:+ ]*?({$this->patterns['phone']})[ ]*$/im", $table1[0]);
        $h->hotel()->phone($phone, false, true);

        $columns2 = $this->re("/\n([ ]*Arrival[: ]*:[: ]*[\s\S]+?)\n*(?:\n[ ]*Payment Details|\n(?:[ ]*|.+[ ]{2})Card No\b|\n.*\bXXXX-XXXX-XXXX-[X\d]{4}\b)/i", $pdf);
        $table2Pos = [0];

        if (preg_match("/(.+[ ]{2})Departure[: ]*:/i", $columns2, $matches)) {
            $table2Pos[] = mb_strlen($matches[1]);
        }
        $table2 = $this->splitCols($columns2, $table2Pos);

        if (count($table2) !== 2) {
            $this->logger->debug('Wrong PDF dates!');

            return false;
        }
        $table2[0] = $this->removeDoubleFields($table2[0]);
        $table2[1] = $this->removeDoubleFields($table2[1]);

        $checkInDate = strtotime($this->re("/^\s*Arrival\b.*:\s*([-[:alpha:]]+[ ]+\d{1,2}[, ]*\d{2,4})\s*$/su", $table2[0]));

        if ($html['checkInTime']) {
            $checkInDate = strtotime($this->normalizeTime($html['checkInTime']), $checkInDate);
        }

        $checkOutDate = strtotime($this->re("/^\s*Departure\b.*:\s*([-[:alpha:]]+[ ]+\d{1,2}[, ]*\d{2,4})\s*$/su", $table2[1]));

        if ($html['checkOutTime']) {
            $checkOutDate = strtotime($this->normalizeTime($html['checkOutTime']), $checkOutDate);
        }

        $h->booked()
            ->checkIn($checkInDate)
            ->checkOut($checkOutDate);

        $pdf = $this->removeDoubleFields($pdf);

        $before = [//it could be mix in languages en + other
            'di fare riferimento all e mail di conferma',
            'see confirmation email',
            'bevestigingsmail',
            'aby pozna szczeg y i warunki Promocji',
            'for kampanjen',
            'silahkan lihat email konfirmasi',
            '예약 확정 이메일을 참고하시기 바랍니다',
            'электронном письме с подтверждением',
            '优惠活动条件及其详情 敬请查看确认邮件',
            'correo de confirmación',
            'นโยบายการยกเล กการจอง:', //th
        ];
        $after = [
            'Arrival',
            'Remarks',
            'Benefits Included',
            'Voordelen inbegrepen',
            'Wliczono korzy ci',
            'Inkluderte fordeler',
            'Termasuk keuntungan',
            '[포함된 서비스 옵션]',
            '包含以下優惠內容',
            'Включены преимущества',
            'Servicios incluidos', //es
            'ส ทธ ประโยชน ท ได ร บ:', //th
        ];

        $cancellationPolicy = $this->re("/^[ ]*{$this->opt($this->t('Cancellation Policy'))}[: ]*:[: ]*(.+?)$(?:\n\n|\s+^[ ]*{$this->opt($after)})/ms", $pdf)
            ?? $this->re("/^[ ]*(?:Hotel Contact Number|Property Contact Number)[: ]*:[^\n]*$\s+^[ ]*([^\n]*(?i)Cancel.*?)$\s+^[ ]*Arrival[: ]*:/ms", $pdf);

        if (empty($cancellationPolicy)) {
            $cancellationPolicy = preg_replace('/\s+/', ' ', $this->re("/{$this->opt($before)}\s+(.+?)\n[ ]*{$this->opt($after)}/s", $pdf));
        }

        if (empty($cancellationPolicy) && !empty($html['cancellationPolicy'])) {
            $cancellationPolicy = trim(str_replace(['Cancellation and Change Policy', 'Cancellation Policy:'], ['', ''], $html['cancellationPolicy']));
        }

        $cP = preg_replace("/\s+/", ' ', $cancellationPolicy);

        if (!empty($cP)) {
            $h->general()->cancellation($cP);
        }

        $room->setDescription($html['roomDescription'], false, true);

        // p.currencyCode
        // p.total
        $totalPrice = $html['totalPrice'] ?? '';

        if (preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $totalPrice, $matches)) {
            // THB 10.190,47    |    Rp 2,353,815    |    ฿ 1,368.66
            $currency = self::normalizeCurrency($matches['currency']);
            $currencyCode = preg_match('/^[A-Z]{3}$/', $currency) ? $currency : null;
            $h->price()->currency($currency)->total(PriceHelper::parse($matches['amount'], $currencyCode));
        } elseif (stripos($this->htmlOrPlain, 'Total Charge to Credit Card') !== false
            && preg_match('/Total Charge to Credit Card\s+([A-Z]{3})\s+(\d[\d,. ]*)/', $this->htmlOrPlain, $m)
        ) {
            $h->price()->currency($m[1])->total(PriceHelper::parse($m[2], $m[1]));
        }

        // status
        if ($html['status']) {
            $h->general()->status($html['status']);
        }

        // deadline
        // nonRefundable
        if (!empty($node = $h->getCancellation())) {
            $this->detectDeadLine($h, $node);
        }

        return true;
    }

    private function getFieldsByHtml_1(array &$result): void
    {
        // examples: it-2947986.eml

        $result['guests'] = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Guests'))}]/ancestor-or-self::td[1]/following-sibling::td[1]", null, true, '/(\d+) /');

        $result['checkInTime'] = $this->http->FindSingleNode("//text()[{$this->eq($this->t('CheckInDate'))}]/ancestor::tr[1]/descendant::td[{$this->contains($this->t('after'))}][last()]", null, true, "/(?:\D|\b)({$this->patterns['time']})(?:\D|\b|$)/");

        $result['checkOutTime'] = $this->http->FindSingleNode("//text()[{$this->eq($this->t('CheckOutDate'))}]/ancestor::tr[1]/td[{$this->contains($this->t('before'))}][last()]", null, true, "/(?:\D|\b)({$this->patterns['time']})(?:\D|\b|$)/")
            ?? $this->http->FindSingleNode("//td[not(.//tr) and contains(normalize-space(),'前')]", null, true, '/\b(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*前/iu');

        $result['cancellationPolicy'] = implode(' ', $this->http->FindNodes("//*[{$this->t('CancellationPolicy')}]/ancestor::tr[1]/following-sibling::tr[normalize-space()]"));

        $result['roomDescription'] = $this->http->FindSingleNode("//text()[{$this->contains($this->t('RoomTypeDescription'))}]/ancestor-or-self::td[1]/following-sibling::td[1]", null, false);

        $result['totalPrice'] = $this->http->FindSingleNode("//text()[{$this->contains($this->t('total-1'))}]/ancestor-or-self::td[1]/following-sibling::td[1]", null, false, '/^.*\d.*$/')
            ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('total-1'))}]/ancestor-or-self::td[1]/following::td[1]", null, false, '/^.*\d.*$/')
            ?? $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('total-2'))}] ]/*[normalize-space()][2]", null, true, '/^.*\d.*$/');

        if ($this->http->XPath->query("//*[{$this->contains($this->t('StatusConfirmed'))}]")->length > 0) {
            $result['status'] = 'Confirmed';
        }
    }

    private function getFieldsByHtml_2(array &$result, ?string $bookingID): void
    {
        // examples: it-900956510.eml

        if (empty($bookingID)) {
            $this->logger->debug('HTML-2: empty $bookingID!');

            return;
        }

        $variants = [];

        foreach ((array) $this->t('bookingID') as $phrase) {
            if (!is_string($phrase) || empty($phrase)) {
                continue;
            }
            $variants[] = $phrase . ':' . $bookingID;
        }

        if (count($variants) === 0) {
            $this->logger->debug('HTML-2: empty Booking ID variants!');

            return;
        }

        $roots = $this->http->XPath->query("//*[ {$this->eq($variants, "translate(.,' ','')")} and preceding::text()[normalize-space()][1][{$this->eq($this->t('Hotels'), "translate(.,':','')")}] ]/following::*[normalize-space()][1]");

        if ($roots->length !== 1) {
            $this->logger->debug('HTML-2: root-node not found!');

            return;
        }

        $root = $roots->item(0);

        $checkInVal = $this->http->FindSingleNode("descendant::*[ *[normalize-space()][1][{$this->eq($this->t('Check in'), "translate(.,':','')")}] and *[normalize-space()][2] ]", $root);

        if (preg_match("/^{$this->opt($this->t('Check in'))}[:\s]*(?<date>.{4,}?)\s*\(\s*{$this->opt($this->t('after'))}[:\s]*(?<time>{$this->patterns['time']})\s*\)[;\s]*$/i", $checkInVal, $m)) {
            // Check in Friday April 4, 2025 (after 2:00 PM)
            $result['checkInTime'] = $m['time'];
        }

        $checkOutVal = $this->http->FindSingleNode("descendant::*[ *[normalize-space()][1][{$this->eq($this->t('Check out'), "translate(.,':','')")}] and *[normalize-space()][2] ]", $root);

        if (preg_match("/^{$this->opt($this->t('Check out'))}[:\s]*(?<date>.{4,}?)\s*\(\s*{$this->opt($this->t('before'))}[:\s]*(?<time>{$this->patterns['time']})\s*\)[;\s]*$/i", $checkOutVal, $m)) {
            $result['checkOutTime'] = $m['time'];
        }

        if ($result['totalPrice'] === null) {
            $result['totalPrice'] = $this->http->FindSingleNode("descendant::tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('total-2'))}] ]/*[normalize-space()][2]", $root, true, '/^.*\d.*$/');
        }

        if ($this->http->XPath->query("descendant::*[{$this->contains($this->t('StatusConfirmed'))}]", $root)->length > 0) {
            $result['status'] = 'Confirmed';
        } else {
            $result['status'] = null;
        }
    }

    /**
     * Here we describe various variations in the definition of dates deadLine.
     */
    private function detectDeadLine(\AwardWallet\Schema\Parser\Common\Hotel $h, string $cancellationText): void
    {
        if (preg_match("/\bCancell? (?i)before\s+(?<date>[[:alpha:]]+[,.\s]*\d{1,2}\s*,\s*\d{4})\s+and you['’]+ll pay nothing\s*(?:[.;!]+|$)/u", $cancellationText, $m) // en
        ) {
            $dateDeadline = strtotime('-1 minute', strtotime($m['date']));
            $h->booked()->deadline($dateDeadline);
        } elseif (
            preg_match("#Any cancellation received within (\d+) day\/?s? prior to arrival (?:date )?will incur the (?:full period|first night|first \d+ nights) charge.#i", // en
                $cancellationText, $m)
            || preg_match("#Bei Stornierung innerhalb von (\d+) Tagen vor Anreisedatum wird eine Geb#iu", // de
                $cancellationText, $m)
            || preg_match("#If cancelled or modified up to (\d+) days before date of arrival, no fee will be charged.#i", // en
                $cancellationText, $m)
            || preg_match("#Enhver kansellering mottatt innen (\d+) dager før ankomst vil medføre en avgift på#iu", // no
                $cancellationText, $m)
            || preg_match("#Semua pembatalan yang diterima dalam (\d+) hari sebelum kedatangan akan dikenakan biaya untuk malam pertama.#i", // id
                $cancellationText, $m)
            || preg_match("#체크인 날짜 전 날을 기준으로 (\d+)일 이내에 예약을 취소하실 경우 예약 요금의#iu", // ko
                $cancellationText, $m)
            || preg_match("#В случае отмены или изменения бронирования в срок до (\d+) суток до даты заезда штраф не взимается.#iu", // ru
                $cancellationText, $m)
            || preg_match("#Las cancelaciones recibidas con antelación inferior a (\d+) día a la fecha de llegada serán penalizadas con el importe de la primera noche.#iu", // es
                $cancellationText, $m)
            || preg_match("#Las cancelaciones recibidas con (\d{1,3}) o menos días de antelación a la fecha de llegada serán penalizadas con el importe completo de la reserva\.#iu", // es
                $cancellationText, $m)
            || preg_match("#如果在入住前(\d+)天内取消预订，将被收取第1晚的房费作为取消费。#iu", // zh
                $cancellationText, $m)
            || preg_match("#如果在入住前(\d+)天内取消预订 将被收取第\d+晚的房费作为取消费#iu", // zh
                $cancellationText, $m)
            /*|| preg_match("#如果在入住日期前(\d+)天內提交預訂取消申請，將被收取訂房總額的首晚作為取消費用#iu", // zh
                $cancellationText, $m)*/
            || preg_match("#若於入住日期前(\d+)天內取消預訂，需支付全額訂房費用#iu", // zh
                $cancellationText, $m)
            || preg_match("#Qualsiasi cancellazione ricevuta entro (\d+) giorno/i prima dell arrivo sarà soggetta all addebito#iu", // it
                $cancellationText, $m)
            || preg_match("#Qualsiasi cancellazione pervenuta (\d{1,3}) giorno prima della data di arrivo incorrerà nell'addebito della prima notte\.#iu", // it
                $cancellationText, $m)
            || preg_match("#ご到着日の(\d+)日前以降のキャンセルには、ご予約料金の全額がキャンセル料として発生します。#iu", // ja
                $cancellationText, $m)
            || preg_match("#หากท านยกเล กการจองห องพ ก (\d+) ว นก อนว นเช คอ น ท านจะถ กเร ยกเก#iu", // th
                $cancellationText, $m)
            || preg_match("#หากท่านยกเลิกการจองห้องพัก (\d+) วันก่อนวันเช็คอิน ท่านจะถูกเรียกเก็บเงินเต็มจำนวน#iu", // th
                $cancellationText, $m)
            || preg_match("/في حال استلام طلب إلغاء الحجز خلال (\d+) أيام السابقة /mu", // ar
                $cancellationText, $m)
            || preg_match("/Avbokningar mottagna inom (\d+) dag innan ankomstdatumet/mu", // sv
                $cancellationText, $m)
        ) {
            $days = $m[1]; //+1;
            $h->booked()->deadlineRelative($days . ' days', '00:00');
        }
        $h->booked()
            ->parseNonRefundable('Please note, if cancelled, modified or in case of no-show, the total price of the reservation will be charged')
            ->parseNonRefundable('Please note, if cancelled or modified, the total price of the reservation will be charged')
            ->parseNonRefundable('This booking is Non-Refundable and cannot be amended or modified.')
            ->parseNonRefundable('Denne reservation er ikke-refunderbar og kan ikke ændres eller rettes')
            ->parseNonRefundable('Ta rezerwacja jest bezzwrotna i nie może zostać poprawiona lub zmodyfikowana.')
            ->parseNonRefundable('Esta reserva não é reembolsável e não pode ser alterada ou modificada.')
            ->parseNonRefundable('Esta reserva no admite reembolso y no se puede modificar ni cancelar.')
            ->parseNonRefundable('Стоимость данного бронирования не возвращается, бронирование не может быть дополнено или изменено.')
            ->parseNonRefundable('本預訂經確認後即不退費，且不可被修正或更改')
            ->parseNonRefundable('此為不可退訂之房型，預訂完成後將不能修改或修正')
            ->parseNonRefundable('Cette réservation est non-remboursable et')
            ->parseNonRefundable('Deze boeking kan niet worden verschoven of aangepast. Er wordt geen restitutie verleend.')
            ->parseNonRefundable('このご予約をキャンセルされた場合は返金されません。');
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function detectBody(?string $text): bool
    {
        if ( empty($text) || !isset($this->detects) ) {
            return false;
        }
        foreach ($this->detects as $phrases) {
            foreach ((array)$phrases as $phrase) {
                if ( !is_string($phrase) )
                    continue;
                if (stripos($text, $phrase) !== false)
                    return true;
            }
        }
        return false;
    }

    private function assignLang(?string $text): bool
    {
        if ( empty($text) || !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['ConfirmationNumber']) ) {
                continue;
            }
            if ($this->strposArray($text, $phrases['ConfirmationNumber']) !== false) {
                $this->lang = $lang;
                return true;
            }
        }
        return false;
    }

    private function strposArray(?string $text, $phrases, bool $reversed = false)
    {
        if (empty($text)) {
            return false;
        }

        foreach ((array) $phrases as $phrase) {
            if (!is_string($phrase)) {
                continue;
            }
            $result = $reversed ? strrpos($text, $phrase) : strpos($text, $phrase);

            if ($result !== false) {
                return $result;
            }
        }

        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return $phrase;
        }
        if ($lang === '') {
            $lang = $this->lang;
        }
        if ( empty(self::$dictionary[$lang][$phrase]) ) {
            return $phrase;
        }
        return self::$dictionary[$lang][$phrase];
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function re(string $re, ?string $str, $c = 1): ?string
    {
        if (preg_match($re, $str ?? '', $m) && array_key_exists($c, $m)) {
            return $m[$c];
        }

        return null;
    }

    private function rowColsPos(?string $row, bool $alignRight = false): array
    {
        if ($row === null) { return []; }
        $pos = [];
        $head = preg_split('/\s{2,}/', $row, -1, PREG_SPLIT_NO_EMPTY);
        $lastpos = 0;
        foreach ($head as $word) {
            $posStart = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $wordLength = mb_strlen($word, 'UTF-8');
            $pos[] = $alignRight ? $posStart + $wordLength : $posStart;
            $lastpos = $posStart + $wordLength;
        }
        if ($alignRight) {
            array_pop($pos);
            $pos = array_merge([0], $pos);
        }
        return $pos;
    }

    private function splitCols(?string $text, ?array $pos = null): array
    {
        $cols = [];
        if ($text === null)
            return $cols;
        $rows = explode("\n", $text);
        if ($pos === null || count($pos) === 0) $pos = $this->rowColsPos($rows[0]);
        arsort($pos);
        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $cols[$k][] = rtrim(mb_substr($row, $p, null, 'UTF-8'));
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);
        foreach ($cols as &$col) $col = implode("\n", $col);
        return $cols;
    }

    private function normalizeTime(string $s): string
    {
        if (preg_match('/^((\d{1,2})[ ]*:[ ]*\d{2})\s*[AaPp][Mm]$/', $s, $m) && (int) $m[2] > 12) {
            $s = $m[1];
        } // 21:51 PM    ->    21:51

        return $s;
    }

    /**
     * @param string $string Unformatted string with currency
     * @return string
     */
    public static function normalizeCurrency(string $string): string
    {
        // used in: agoda/BookingConfirmed

        $string = trim($string);
        $currencies = [
            // do not add unused currency!
            'IDR' => ['Rp'],
            'INR' => ['Rs.'],
        ];
        foreach ($currencies as $currencyCode => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency)
                    return $currencyCode;
            }
        }
        return $string;
    }

    private function removeDoubleFields(?string $s): ?string
    {
        $whiteList = array_merge([
            'Booking ID', 'Booking Reference No', 'Client', 'Member ID', 'Country of Residence',
            'Country of Passport', 'Property', 'Hotel', 'Address', 'Property Contact Number',
            'Hotel Contact Number', 'Number of Rooms', 'Number of Extra Beds', 'Number of Adults',
            'Max Occupancy', 'Number of Children', 'Breakfast', 'Room Type', 'Promotion',
            'Benefits Included', 'Arrival', 'Departure', 'Payment Details', 'Payment Method',
            'Booked And Payable By', 'Remarks', 'Included',
            'behandeld als No-Show', // it-9181422-nl.eml
        ], (array) $this->t('Cancellation Policy'));
        $whitePattern = $this->opt($whiteList);
        $rows = explode("\n", $s);

        foreach ($rows as $key => $r) {
            if ((preg_match("/^[ (]*([[:alpha:]][-.[:alpha:] ]*?)[ )]*[:]+/mu", $r, $m) || preg_match("/^[ ]*(ﺍﻟﺮﻗﻢ ﺍﻟﻤﺮﺟﻌﻲ ﻟﻠﺤﺠﺰ|住客姓名)(?:[ ]|$)/mu", $r, $m))
                && !preg_match("/^{$whitePattern}$/", $m[1])
            ) {
                $rows[$key] = rtrim(preg_replace("/^{$this->opt($m[0])}/", str_repeat(' ', mb_strlen($m[0])), $r));
            }
        }

        if (count($rows)) {
            $s = implode("\n", $rows);
        }

        return $s;
    }
}
