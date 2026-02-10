<?php

namespace AwardWallet\Engine\agoda\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;

class Itinerary1 extends \TAccountChecker
{
    public $mailFiles = "agoda/it-3121110.eml, agoda/it-3121116.eml, agoda/it-3121118.eml, agoda/it-3332149-de.eml, agoda/it-48301859-ar.eml";

    private $subjects = [
        'zh' => ['確認訂單編號', '预订确认'],
        'ja' => ['確認メール、予約'],
        'it' => ['Conferma della Prenotazione'],
        'ar' => ['تأكيد  لحجز رقم'],
        'sv' => ['Bekräftelse för Boknings-ID'],
    ];

    private $detects = [
        'en'  => 'Please present either an electronic or paper copy of your',
        'en2' => 'Hotel Contact Number',
        'en3' => 'is confirmed and complete with Agoda price guarantee',
        'en4' => '‫‪Booking Reference No :‬‬',
        'en5' => 'Ваше бронювання підтверджене та завершене!', // TODO: Writing in ua and en languages
        'en6' => 'Agoda price guarantee',
        'en7' => 'Booked And Payable By',
        'zh'  => '預訂編號：',
        'zh2' => 'Agoda價格保證',
        'zh3' => '的预订已成功确认，并享有Agoda价格保证。',
        'de'  => ['wurde bestätigt und mit der Agoda Preisgarantie abgeschlossen', 'Bitte legen Sie beim Check-in eine elektronische oder ausgedruckte Kopie dieses Buchungsbelegs vor'],
        "da"  => 'er bekræftet og afsluttet med Agodas Prisgaranti',
        "pl"  => 'gwarancją ceny Agoda, została potwierdzona i zakończona',
        "pt"  => 'confirmada e completa com garantia de preço Agoda',
        "id"  => 'dibuat dengan jaminan harga Agoda',
        "es"  => 'confirmada con la garantía de precio Agoda',
        "no"  => 'bekreftet og dekkes av Agodas prisgaranti',
        "ru"  => 'завершено с гарантией лучшей цены Agoda',
        'ru2' => 'Пожалуйста, предъявите электронную или распечатанную копию данного подтверждения при регистрации заезда.',
        "ko"  => '최저가 보장이 적용됩니다',
        "fi"  => 'Esitä joko elektroninen tai paperinen varausvoucher',
        //'fr' => 'Utilisez le Self Service Agoda pour gérer votre réservation',
        'nl'  => 'U kunt uw boeking gemakkelijk beheren met onze zelfservice',
        'ja'  => '空港から宿泊施設までの交通手段を手配できます',
        'ja2' => 'アゴダ®ベスト料金保証',
        'ja3' => 'アゴダのセルフサービスページより予約を管理できます',
        'fr'  => 'votre réservation est confirmée (Garantie de prix Agoda',
        'it'  => 'completa e confermata e con la garanzia sul prezzo di Agoda',
        'ar'  => 'مؤكد و مكتمل مع ضمان أفضل سعر من أجودا',
        'sv'  => 'är bekräftad och klar, med Agoda prisgaranti',
        'th'  => 'หากมีขอ้ สงสัยหรือต้องการสอบถามเพิมเติม กรุณาไปที www.agoda.com/support',
        'sv2' => 'Skaffa en billig hyrbil och spara pengar när du bokar bilen online idag',
    ];

    private static $dict = [
        'en' => [
            'ConfirmationNumber'  => 'Booking ID:',
            'HotelName'           => "#Your booking at (.*?) is confirmed#i",
            'CheckInDate'         => 'Check in:',
            'CheckOutDate' => 'Check out:',
            'GuestNames'          => 'Lead Guest:',
            'Guests' => 'Occupancy:',
            'Adults' => ['Adults', 'Adult'],
            'Rooms'               => 'Reservations:',
            'CancellationPolicy' => "contains(text(), 'Cancellation') and contains(text(), 'Policy')",
            // 'Hotel policy' => '',
            'Total'               => ['Total price', 'Total Price:', 'Total Charge to Card:', 'Total charge to card:', 'Total Charge to Credit Card', 'Total Due / Charge to ', 'Total amount pre-authorized'],
            'RoomTypeDescription' => 'Special requests:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'is confirmed',
        ],
        'zh' => [
            'ConfirmationNumber'  => ['预订编码：', '订单号', '編號：', '預訂編號：'],
            'HotelName'           => "#(?:您在|已獲得)(.*?)的预订已成功确认#ui",
            'CheckInDate'         => ['入住日期：'],
            'CheckOutDate' => '退房日期：',
            'GuestNames'          => ['顾客姓名:', '顧客姓名:', '住客姓名：'],
            'Guests' => ['入住人数：', '入住人數：'],
            'Adults' => '位成人',
            'Rooms'               => ['预订信息：', '預訂細節：', '訂房摘要：'],
            'CancellationPolicy' => "contains(text(), '取消') and contains(text(), '修改政策')",
            'Hotel policy'        => ['飯店政策', '酒店政策'],
            'Total'               => ['总价：', '刷卡總金額：', '從信用卡扣除總金額：', '總金額：', '總價格：'],
            'RoomTypeDescription' => ['特殊要求：','特殊需求：'],
            // 'Meal option:' => '',
            'StatusConfirmed'     => ['您的预订已成功确认', '您的預訂已經完成並確認', '預訂成功，訂單已經獲得確認'],
        ],
        'de' => [
            'ConfirmationNumber'  => 'Buchungs-ID:',
            'HotelName'           => "#Ihre Buchung im (.*?) wurde bestätigt#i",
            'CheckInDate'         => ['Check-in:', 'Check-In:'],
            'CheckOutDate' => ['Check-out:', 'Check-Out:'],
            'GuestNames'          => 'Hauptgast:',
            'Guests' => 'Belegung:',
            // 'Adults' => '',
            'Rooms'               => 'Reservierung:',
            'CancellationPolicy' => "contains(text(), 'Stornierungs') and contains(text(), 'Änderungsbedingungen')",
            // 'Hotel policy' => '',
            'Total'               => ['Gesamter Abbuchungsbetrag', 'Gesamtpreis'],
            'RoomTypeDescription' => 'Sonderwünsche:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'Ihre Buchung wurde bestätigt',
        ],
        'nl' => [
            'ConfirmationNumber'  => 'Boekings-ID:',
            'HotelName'           => "#Uw boeking bij (.*?) is bevestigd#i",
            'CheckInDate'         => 'Inchecken:',
            'CheckOutDate' => 'Uitchecken:',
            'GuestNames'          => 'Aanhef Gast:',
            'Guests' => 'Bezetting:',
            // 'Adults' => '',
            'Rooms'               => 'Reservering:',
            'CancellationPolicy' => "contains(text(), 'Annulerings') and contains(text(), 'Aanpassingsbeleid')",
            'Hotel policy' => 'Hotelbeleid',
            'Total'               => ['Totaalprijs:', 'Totaal belast op kaart:'],
            'RoomTypeDescription' => 'Speciale verzoeken:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'Uw boeking is bevestigd',
        ],
        'da' => [
            'ConfirmationNumber'  => 'Dit reservations-ID:',
            'HotelName'           => "#Din reservation på (.*?) er bekræftet#i",
            'CheckInDate'         => 'Indtjekning:',
            'CheckOutDate' => 'Udtjekning:',
            'GuestNames'          => 'Hovedgæst:',
            'Guests' => 'Belægning:',
            // 'Adults' => '',
            'Rooms'               => 'Reservationer:',
            'CancellationPolicy' => "contains(text(), 'Afbestillings') and contains(text(), 'ændringspolitik')",
            // 'Hotel policy' => '',
            'Total'               => ['Total Price:', 'Samlet opkrævning fra kort:'],
            'RoomTypeDescription' => 'Specielle forespørgsler:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'er bekræftet',
        ],
        'pl' => [
            'ConfirmationNumber'  => 'Numer rezerwacji:',
            'HotelName'           => "#Twoja rezerwacja w: (.*?), wraz z gwarancją#i",
            'CheckInDate'         => 'Zameldowanie:',
            'CheckOutDate' => 'Wymeldowanie:',
            'GuestNames'          => 'Główny gość:',
            'Guests' => 'Ilość osób:',
            // 'Adults' => '',
            'Rooms'               => 'Rezerwacje:',
            'CancellationPolicy' => "contains(text(), 'anulowania') and contains(text(), 'Polityka')",
            'Hotel policy' => 'zgodnie z zasadami hotelu',
            'Total'               => ['Kartę obciążono łączną kwotą:'],
            'RoomTypeDescription' => 'Specjalne życzenia:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'została potwierdzona',
        ],
        'pt' => [
            'ConfirmationNumber'  => 'O seu ID de reserva:',
            'HotelName'           => "#A sua reserva em (.*?) está confirmada#i",
            'CheckInDate'         => 'Entrada:',
            'CheckOutDate' => 'Saída:',
            'GuestNames'          => 'Hóspede Principal:',
            'Guests' => 'Ocupação:',
            // 'Adults' => '',
            'Rooms'               => 'Reservas:',
            'CancellationPolicy' => "contains(text(), 'Política') and contains(text(), 'Cancelamento')",
            'Hotel policy' => 'Política do hotel',
            'Total'               => ['Custo total para cartão'],
            'RoomTypeDescription' => 'Pedidos Especiais:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'está confirmada',
        ],
        'id' => [
            'ConfirmationNumber'  => 'ID Pesanan Anda:',
            'HotelName'           => "#Pesanan Anda di (.*?) telah dikonfirmasi#i",
            'CheckInDate'         => 'Check-in:',
            'CheckOutDate' => 'Check-out:',
            'GuestNames'          => 'Tamu Utama:',
            'Guests' => 'Okupansi:',
            // 'Adults' => '',
            'Rooms'               => 'Pesanan:',
            'CancellationPolicy' => "contains(text(), 'Kebijakan') and contains(text(), 'Pembatalan')",
            'Hotel policy' => 'Kebijakan hotel',
            'Total'               => ['Total yang dibebankan ke kartu'],
            'RoomTypeDescription' => 'Permintaan khusus:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'telah dikonfirmasi',
        ],
        'es' => [
            'ConfirmationNumber'  => ['Tu ID de reserva:', 'ID Reserva:'],
            'HotelName'           => "#Tu reserva en (.*?) ha sido completada #i",
            'CheckInDate'         => 'Entrada:',
            'CheckOutDate' => 'Salida:',
            'GuestNames'          => 'Huésped Principal',
            'Guests' => 'Capacidad:',
            // 'Adults' => '',
            'Rooms'               => 'Reservas:',
            'CancellationPolicy' => "contains(text(), 'Política') and contains(text(), 'Cancelación')",
            'Hotel policy' => 'Política del Hotel',
            'Total'               => ['A cobrar en la tarjeta', "Precio Final:", 'Cargo Total en Tarjeta:'],
            'RoomTypeDescription' => 'Solicitud especial:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'está completa',
        ],
        'no' => [
            'ConfirmationNumber'  => 'Din booking-ID:',
            'HotelName'           => "#Din booking på (.*?) er bekreftet#i",
            'CheckInDate'         => 'Innsjekking:',
            'CheckOutDate' => 'Utsjekking:',
            'GuestNames'          => 'Hovedgjest',
            'Guests' => 'Kapasitet:',
            // 'Adults' => '',
            'Rooms'               => 'Bestilling:',
            'CancellationPolicy' => "contains(text(), 'Avbestilling') and contains(text(), 'endringsregler')",
            'Hotel policy' => 'hotellregel',
            'Total'               => ['Endelig belastning av kortet'],
            'RoomTypeDescription' => 'Forespørsler',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'er bekreftet',
        ],
        'ru' => [
            'ConfirmationNumber'  => 'ID номер вашего бронирования:',
            'HotelName'           => "#бронирование в (.*?) подтверждено и#i",
            'CheckInDate'         => 'Дата заезда:',
            'CheckOutDate' => 'Дата выезда:',
            'GuestNames'          => 'Имя гостя',
            'Guests' => 'Размещение:',
            // 'Adults' => '',
            'Rooms'               => 'Бронирование:',
            'CancellationPolicy' => "contains(text(), 'Политика') and contains(text(), 'отмены')",
            'Hotel policy' => 'Политика отеля',
            'Total'               => ['Общая сумма:', 'Полная сумма к списанию с карты:'],
            'RoomTypeDescription' => 'Специальные запросы:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'бронирование подтверждено',
        ],
        'ko' => [
            'ConfirmationNumber'  => '예약 번호:',
            'HotelName'           => "#(.*?)의 예약이 확정#i",
            'CheckInDate'         => ['의 예약이 확정:', '체크인 날짜:'],
            'CheckOutDate' => '체크아웃 날짜:',
            'GuestNames'          => '투숙객 이름:',
            'Guests' => '총 숙박 인원:',
            // 'Adults' => '',
            'Rooms'               => '숙박일수 및 객실수:',
            'CancellationPolicy' => "contains(text(), '취소 및 변경 정책')",
            'Hotel policy' => '호텔 정책',
            'Total'               => ['총 카드 결제액:'],
            'RoomTypeDescription' => '특별요청사항:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => '예약이 확정',
        ],
        'fi' => [
            'ConfirmationNumber'  => 'Varauksesi ID:',
            'HotelName'           => "#Varauksesi kohteessa (.*?) on vahvistettu#i",
            'CheckInDate'         => 'Check-in:',
            'CheckOutDate' => 'Check-out:',
            'GuestNames'          => 'Varauksesta vastaava:',
            'Guests'              => 'Saatavuus:',
            // 'Adults' => '',
            'Rooms'               => 'Varaukset:',
            'CancellationPolicy' => "contains(text(), 'Peruutus-') and contains(text(), 'ja vaihtokäytäntö')",
            // 'Hotel policy' => '',
            'Total'               => ['Kokonaisveloitus kortilta:'],
            'RoomTypeDescription' => 'Erityispyynnöt:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'бронирование подтверждено',
        ],
        'ja' => [
            'ConfirmationNumber'  => 'ご予約',
            'HotelName'           => "#ご予約手続きが完了しました。(.*?) のご予約確定です#i",
            'CheckInDate'         => 'チェックイン日：',
            'CheckOutDate' => 'チェックアウト日：',
            'GuestNames'          => '代表者名：',
            'Guests'              => '定員：',
            // 'Adults' => '',
            'Rooms'               => '部屋',
            'CancellationPolicy' => "contains(text(), 'キャンセルポリシー') and contains(text(), '変更ポリシー')",
            // 'Hotel policy' => '',
            'Total'               => ['合計金額：', 'カード課金額：'],
            'RoomTypeDescription' => '特別なリクエスト：',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'ご予約が確定しました!',
        ],
        'fr' => [
            'ConfirmationNumber'  => 'Numéro de réservation :',
            'HotelName'           => "#^\s*(.+?) : votre réservation est confirmée#i",
            'CheckInDate'         => 'Arrivée :',
            'CheckOutDate' => 'Départ :',
            'GuestNames'          => 'Hôte principal:',
            'Guests'              => 'Occupation :',
            'Adults'              => ['adulte', 'adultes'],
            'Rooms'               => 'Séjour :',
            'CancellationPolicy' => "contains(text(), 'Conditions') and contains(text(), \"d'annulation\") and not(ancestor::a)",
            // 'Hotel policy' => '',
            'Total'               => ['Montant total débité de votre carte :'],
            'RoomTypeDescription' => 'Demandes spéciales :',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'réservation est confirmée',
        ],
        'it' => [
            'ConfirmationNumber'  => 'Numero Prenotazione (booking ID):',
            'HotelName'           => "#La tua prenotazione presso (.*?) è completa#i",
            'CheckInDate'         => 'Check-in:',
            'CheckOutDate' => 'Check-out:',
            'GuestNames'          => 'Ospite principale:',
            'Guests'              => 'Ospiti:',
            // 'Adults' => '',
            'Rooms'               => 'Prenotazione:',
            'CancellationPolicy' => "contains(text(), 'Cancellazione') and contains(text(), 'Termini')",
            'Hotel policy'        => "Politica dell'Hotel",
            'Total'               => ['Prezzo totale:'],
            'RoomTypeDescription' => 'Richieste speciali:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'è confermata e completa',
        ],
        'ar' => [
            'ConfirmationNumber'  => 'رقم حجزك:',
            'HotelName'           => "#حجزك في (.*?) مؤكد و مكتمل مع ضمان أفضل سعر من أجودا.#i",
            'CheckInDate'         => 'تسجيل الدخول:',
            'CheckOutDate'        => 'تسجيل الخروج:',
            'GuestNames'          => 'النزيل الرئيسي:',
            'Guests'              => 'الإشغال:',
            // 'Adults' => '',
            'Rooms'               => 'الحجوزات:',
            'CancellationPolicy'  => "contains(text(), 'إلغاء والتغيير') and contains(text(), 'سياسة ال')",
            // 'Hotel policy' => '',
            'Total'               => ['السعر الكلي:', 'السعر الكلي'],
            'RoomTypeDescription' => 'طلبات خاصة:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => ['حجزك مؤكد و مك'],
        ],
        'sv'=> [
            'ConfirmationNumber'  => 'Boknings-ID:',
            'HotelName'           => "#Din bokning på (.*?) är bekräftad och klar, med Agoda prisgaranti.#",
            'CheckInDate'         => 'Incheckning:',
            'CheckOutDate'        => 'Utcheckning:',
            'GuestNames'          => 'Huvudgäst:',
            'Guests'              => 'Gäster:',
            'Adults'              => ['vuxna'],
            'Rooms'               => 'Bokningar:',
            'CancellationPolicy'  => "contains(text(), 'Avboknings') and contains(text(), '- och ändringsvillkor')",
            // 'Hotel policy' => '',
            'Total'               => ['Totalbelopp debiterat på kortet:'],
            'RoomTypeDescription' => 'Särskilda önskemål:',
            // 'Meal option:' => '',
            'StatusConfirmed'     => 'är bekräftad',
        ],
        'th' => [
            'ConfirmationNumber'  => 'หมายเลขการจอง:',
            'HotelName'           => "#การจองห้องพักของท่านที่ (.*?) ได้รับการยืนยันเรียบร้อยแล้ว#i",
            'CheckInDate'         => 'เช็คอิน:',
            'CheckOutDate'        => 'เช็คเอาต์:',
            'GuestNames'          => 'ผู้เข้าพัก:',
            'Guests'              => 'ผู้เข้าพัก:',
            'Adults'              => ['ผู้ใหญ่'],
            'Rooms'               => 'การจองห้องพัก:',
            'CancellationPolicy'  => "contains(text(), 'นโยบายการยกเลิกและ') and contains(text(), 'การเปลี่ยนแปลงการจองห้องพัก')",
            // 'Hotel policy' => '',
            'Total'               => ['จำนวนเงินที่เรียกเก็บจากบัตร:'],
            // 'RoomTypeDescription' => '',
            'Meal option:'        => 'อาหารเช้า:',
            'StatusConfirmed'     => 'ได้รับการยืนยันเรียบร้อยแล้ว',
        ],
    ];

    private $lang = 'en';
    private $htmlOrPlain = '';
    private $patterns = [
        'time' => '\d{1,2}:\d{2}(?:\s*[AaPp][Mm])?', // 4:19PM
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ($phrases as $phrase) {
                if (is_string($phrase) && array_key_exists('subject', $headers) && stripos($headers['subject'], $phrase) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return strpos($from, 'Agoda ') !== false || preg_match('/[@.]agoda\.com$/i', $from) > 0;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//span[@id='lbl_Confirmation']")->length > 0
            && $this->http->XPath->query("//span[@id='lbl_NotAmendVoucher' or @id='lbl_AmendVoucher']")->length > 0
        ) {
            return false;
        }

        $textBody = $parser->getHTMLBody();

        if (empty($textBody)) {
            $textBody = $parser->getPlainBody();
        }

        foreach ($this->detects as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (stripos($textBody, $phrase) !== false) {
                    return true;
                }
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
        return count(self::$dict);
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->htmlOrPlain = $parser->getHTMLBody();

        if (empty($this->htmlOrPlain)) {
            $this->htmlOrPlain = $parser->getPlainBody();
        }

        foreach (self::$dict as $lang => $dict) {
            if (is_string($dict['ConfirmationNumber']) && stripos($parser->getHTMLBody(),
                    $dict['ConfirmationNumber']) !== false
            ) {
                $this->lang = $lang;

                break;
            } elseif (is_array($dict['ConfirmationNumber'])) {
                foreach ($dict['ConfirmationNumber'] as $confNo) {
                    if (stripos($parser->getHTMLBody(), $confNo) !== false) {
                        $this->lang = $lang;

                        break 2;
                    }
                }
            }
        }

        $this->parseHtml($email);
        $email->setType('Itinerary1' . ucfirst($this->lang));

        return $email;
    }

    private function parseHtml(Email $email): void
    {
        $h = $email->add()->hotel();

        $text = $this->htmlOrPlain;

        if (empty($confirmationNumber = $this->http->FindSingleNode("//text()[{$this->contains($this->t('ConfirmationNumber'))}]/ancestor-or-self::td[1]/following-sibling::td[1]"))) {
            if (empty($confirmationNumber = $this->re("/\n\s*{$this->preg_implode($this->t('ConfirmationNumber'))}\s*([\w-]+)/msi", $text))) {
                $confirmationNumber = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('ConfirmationNumber'))}]/ancestor-or-self::td[1]/following-sibling::td[1])[2]");
            }
        }

        if (empty($confirmationNumber)) {
            // ar
            $confirmationNumber = $this->http->FindSingleNode("//title[{$this->starts('Agoda')}]/following::tr[1]/td[1]", null, true, "/{$this->preg_implode($this->t('ConfirmationNumber'))}\s+(\d+)/m");
        }

        if (!empty($confirmationNumber)) {
            $h->general()->confirmation($confirmationNumber);
        }

        $hotelName = $this->re($this->t('HotelName'), $text);

        if (empty($hotelName)) {
            $hotelName = $this->http->FindSingleNode("(//h2/ancestor::tr[1]/following-sibling::tr[2]//td[3])[1][.//img[contains(@src,'star') or contains(@id,'Star') or contains(@alt,'star')]]/preceding::tr[string-length(normalize-space())>3][1]/descendant::text()[normalize-space()!=''][1]");
        }

        if (!empty($hotelName)) {
            $h->hotel()->name($hotelName);
        }

        if (empty($checkInDateText = $this->http->FindSingleNode("//text()[" . $this->contains($this->t('CheckInDate')) . "]/ancestor-or-self::td[1]/following-sibling::td[1]"))) {
            $checkInDateText = $this->re("/\n\s*{$this->preg_implode($this->t('CheckInDate'))}\s*(.+)/i", $text);
        }

        if (preg_match('/^(?<date>.+)\s+\([[:alpha:]\s]*(?<time>' . $this->patterns['time'] . ')\)/u', $checkInDateText,
                $matches)
            || preg_match('/^(?<date>.+)\s+(?<time>' . $this->patterns['time'] . ')/', $checkInDateText, $matches)
        ) {
            $checkInDateNormal = $this->normalizeDate($matches['date']);
            $checkInTimeNormal = $this->normalizeTime($matches['time']);

            if ($checkInDateNormal) {
                $h->booked()->checkIn(strtotime($checkInDateNormal . ', ' . $checkInTimeNormal));
            }
        } elseif ($checkInDateText) {
            $checkInDateNormal = $this->normalizeDate($checkInDateText);

            if ($checkInDateNormal) {
                $h->booked()->checkIn(strtotime($checkInDateNormal));
            }
        }

        if (empty($checkOutDateText = $this->http->FindSingleNode("//text()[" . $this->contains($this->t('CheckOutDate')) . "]/ancestor-or-self::td[1]/following-sibling::td[1]"))) {
            $checkOutDateText = $this->re("/\n\s*{$this->preg_implode($this->t('CheckOutDate'))}\s*(.+)/i", $text);
        }

        if (preg_match('/^(?<date>.+)\s+\([[:alpha:]\s]*(?<time>' . $this->patterns['time'] . ')\)/u', $checkOutDateText,
                $matches)
            || preg_match('/^(?<date>.+)\s+(?<time>' . $this->patterns['time'] . ')/', $checkOutDateText, $matches)
        ) {
            $checkOutDateNormal = $this->normalizeDate($matches['date']);
            $checkOutTimeNormal = $this->normalizeTime($matches['time']);

            if ($checkOutDateNormal) {
                $h->booked()->checkOut(strtotime($checkOutDateNormal . ', ' . $checkOutTimeNormal));
            }
        } elseif ($checkOutDateText) {
            $checkOutDateNormal = $this->normalizeDate($checkOutDateText);

            if ($checkOutDateNormal) {
                $h->booked()->checkOut(strtotime($checkOutDateNormal));
            }
        }

        /** @var \DOMNodeList $nodes */
        $nodes = $this->http->XPath->query("(//h2/ancestor::tr[1]/following-sibling::tr[2]//td[3])[1][.//img[contains(@src,'star') or contains(@id,'Star') or contains(@alt,'star')]]/descendant::text()[normalize-space()!=''][1]");

        if ($nodes->length === 0) {
            $nodes = $this->http->XPath->query("(//h2[contains(normalize-space(), \"" . html_entity_decode($hotelName) . "\")]/ancestor::tr[1]/following-sibling::tr[2]//td[3])[1]/descendant::text()[normalize-space()][1]");
        }
        $address = '';

        if ($nodes->length > 0) {
            $text1 = trim($nodes->item(0)->nodeValue);

            if ($this->re("#\b(\d+)\b.+?\b\g{1}\b#s", $text1)) {
                $arr = explode("\n", $text1);
                $cnt = (count($arr) > 1) + (count($arr) % 2);
                $address = nice(implode(" ", array_slice($arr, 0, $cnt)));
            } else {
                $address = nice($text1);
            }
        }
        $h->hotel()->address($address);

        $addressParts = explode(',', $address);

        if (count($addressParts) === 4) {
            $da = $h->hotel()->detailed();
            $da->address(trim($addressParts[0]))
                ->city(trim($addressParts[2]))
                ->country(trim($this->re('/(\w+)/', end($addressParts))))
            ;

            if (($zip = $this->re('/\w+\s+(\d{4,})/', end($addressParts)))) {
                $da->zip($zip);
            }
        }

        if (empty($guestName = $this->http->FindSingleNode("//text()[{$this->contains($this->t('GuestNames'))}]/ancestor-or-self::td[1]/following-sibling::td[1]"))) {
            if (empty($guestName = $this->re("/\n\s*{$this->preg_implode($this->t('GuestNames'))}\s*([^\n]+)/", $text))) {
                $guestName = $this->http->FindSingleNode("//node()[starts-with(normalize-space(.), 'Uw boeking is bevestigd en voltooid!')]/preceding-sibling::node()[normalize-space(.)!=''][1]");
            }
        }

        if (empty($guestName) and $this->lang == 'th') {
            $guestName = $this->http->FindSingleNode("//*[{$this->starts($this->t('ConfirmationNumber'))}]/following::tr[normalize-space()][1][{$this->starts($this->t('GuestNames'))}]/td[2]");
        }

        $cancellation = $this->http->FindSingleNode("//*[" . $this->t('CancellationPolicy') . "]/ancestor::tr[1]/following-sibling::tr[1]");

        if (empty($cancellation)) {
            $cancellation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Guests'))}]/following::text()[{$this->eq($this->t('Conditions d\'annulation et de modification'))}]/ancestor::tr[1]/following-sibling::tr[1]");
        }

        if (empty($cancellation)) {
            $cancellation = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Booking ID')]/following::text()[normalize-space()='Cancellation policy'][1]/following::text()[normalize-space()][1]");
        }

        $h->general()
            ->traveller($guestName)
            ->cancellation($cancellation);

        $guests = $this->re("/\b(\d{1,3})[ ]*{$this->preg_implode($this->t('Adults'))}/i",
            $this->http->FindSingleNode("//text()[{$this->contains($this->t('Guests'))}]/ancestor-or-self::td[1]/following-sibling::td[1]"));

        if (empty($guests) and $this->lang == 'th') {
            $guests = $this->re("/{$this->preg_implode($this->t('Adults'))}\s*(\d{1,3})\b/i",
                $this->http->FindSingleNode("//*[{$this->starts($this->t('Meal option:'))}]/preceding-sibling::tr[normalize-space()][1][{$this->starts($this->t('Guests'))}]/td[2]"));
        }

        if ($this->lang != 'ar' && !empty($guests)) {
            $h->booked()->guests($guests);
        } elseif (!empty($guests) and $this->lang == 'ar') {
            $h->booked()->guests($guests);
        }

        if (empty($rooms = $this->re("#(\d+)\s?(?:Room|kamer|間房|ห้อง|rum)#i",
            $this->http->FindSingleNode("//text()[{$this->contains($this->t('Rooms'))}]/ancestor-or-self::td[1]/following-sibling::td[1]")))) {
            //ar
            $rooms = $this->re("/.*?غرفة\s+(\w+)/mu",
                    $this->http->FindSingleNode("//text()[{$this->contains($this->t('Rooms'))}]/ancestor-or-self::td[1]/following-sibling::td[1]"));

            switch ($rooms) {
                case 'واحدة':
                case 'واحد':
                    $rooms = 1;

                    break;
            }
        }

        if (!empty($rooms)) {
            $h->booked()->rooms($rooms);
        }

        $room = $h->addRoom();
        $room->setType(trim($this->http->FindSingleNode("//text()[{$this->contains($this->t('Total'))}]/ancestor::tr[1]/preceding-sibling::tr[string-length(normalize-space(./td[2]))>2][last()]/td[1]"), ': '));

        $description = $this->http->FindSingleNode("//text()[{$this->contains($this->t('RoomTypeDescription'))}]/ancestor-or-self::td[1]/following-sibling::td[1]");

        if (!empty($description)) {
            $room->setDescription($description, true, true);
        }

        $total = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Total'))}]/ancestor-or-self::td[1]/following-sibling::td[1]");

        if (preg_match('/([A-Z]{3})\s+([\d\.\, ]+)/', $total, $m)) {
            $h->price()
                ->currency($m[1])
                ->total(PriceHelper::parse($m[2], $m[1]));
        }

        $discount = $this->http->FindSingleNode("//text()[contains(normalize-space(),'Discount')]", null, true, "/\s\D(\d[\d,.]*)\s*$/");
        $h->price()->discount($discount, false, true);

        if ($this->http->XPath->query("//node()[{$this->contains($this->t('StatusConfirmed'))}]")->length > 0) {
            $h->general()->status('Confirmed');
        }

        if (!empty($node = $h->getCancellation())) {
            $this->detectDeadLine($h, $node);
        }
    }

    /**
     * Here we describe various variations in the definition of dates deadLine.
     */
    private function detectDeadLine(\AwardWallet\Schema\Parser\Common\Hotel $h, string $cancellationText)
    {
        if (
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

    private function re($re, $text, $multiple = false)
    {
        if (!$multiple) {
            if (is_string($text) && preg_match($re, $text, $m)) {
                return $m[1];
            } else {
                return null;
            }
        } else {
            if (is_string($text) && preg_match($re, $text, $m)) {
                return $m;
            } else {
                return null;
            }
        }
    }

    private function t($s)
    {
        if (!isset(self::$dict[$this->lang]) || !isset(self::$dict[$this->lang][$s])) {
            return $s;
        }

        return self::$dict[$this->lang][$s];
    }

    private function normalizeDate(string $string)
    {
        $this->logger->debug('IN-' . $string);

        if (preg_match('/([^\d\W]{3,})\s+(\d{1,2})\s*,\s*(\d{4})/u', $string, $matches)) { // December 9, 2015
            $month = $matches[1];
            $day = $matches[2];
            $year = $matches[3];
        } elseif (preg_match('/(\d{1,2})[.\s]+([^\d\W]{3,})[.\s]+(\d{4})/u', $string, $matches)) { // 08 July 2018
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
        } elseif (preg_match('/^(\d{4})年(\d+)月(\d+)日(?:.+)?$/u', $string, $matches)) { // 2018年11月28日
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        } elseif (($this->lang == 'th') and (preg_match('/(\d+)\s+(\S+)\s+(\d{4})/u', $string, $matches))) { // 23 ธันวาคม 2562 (หลัง THAI
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            if (($year - date('Y')) > 400) {
                $year = $year - 543;
            }
        } elseif (($this->lang == 'ar') and (preg_match('/^(\d+)\s+(\S+)[,]\s+(\d+)/', $string, $matches))) { // 10 ديسمبر, 2019 (بعد
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
        }

        if (isset($day, $month, $year)) {
            if (preg_match('/^\s*(\d{1,2})\s*$/', $month, $m)) {
                return $m[1] . '/' . $day . ($year ? '/' . $year : '');
            }

            if (($monthNew = MonthTranslate::translate($month, $this->lang)) !== false) {
                $month = $monthNew;
            }
            $this->logger->debug('OUR-' . $day . ' ' . $month . ($year ? ' ' . $year : ''));

            return $day . ' ' . $month . ($year ? ' ' . $year : '');
        }

        return false;
    }

    private function normalizeTime(string $s): string
    {
        if (preg_match('/^((\d{1,2})[ ]*:[ ]*\d{2})\s*[AaPp][Mm]$/', $s, $m) && (int) $m[2] > 12) {
            $s = $m[1];
        } // 21:51 PM    ->    21:51

        return $s;
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'normalize-space(.)="' . $s . '"'; }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'starts-with(normalize-space(.),"' . $s . '")'; }, $field)) . ')';
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'contains(normalize-space(.),"' . $s . '")'; }, $field)) . ')';
    }

    private function preg_implode($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }

        return '(?:' . implode("|", array_map(function ($v) {return preg_quote($v, '/'); }, $field)) . ')';
    }
}
