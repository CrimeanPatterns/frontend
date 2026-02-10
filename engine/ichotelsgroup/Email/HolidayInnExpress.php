<?php

namespace AwardWallet\Engine\ichotelsgroup\Email;

use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Common\Parser\Util\PriceHelper;

class HolidayInnExpress extends \TAccountChecker
{
    public $mailFiles = "ichotelsgroup/it-884816883.eml, ichotelsgroup/it-884940381.eml, ichotelsgroup/it-885178238.eml, ichotelsgroup/it-885206091.eml, ichotelsgroup/it-893091552-pl.eml, ichotelsgroup/it-896690597-it.eml, ichotelsgroup/it-896880917-de.eml, ichotelsgroup/it-896905052-zh.eml, ichotelsgroup/it-896913785-fr.eml, ichotelsgroup/it-896934988-ko.eml, ichotelsgroup/it-906291209-cancelled.eml, ichotelsgroup/it-906240033-pt.eml, ichotelsgroup/it-905354309-ja.eml";

    private $subjects = [
        'en' => ['Your Reservation Confirmation #', 'Your Reservation Cancellation #', 'Your Reservation Cancelation #'],
        'de' => ['Bestätigung Ihrer Reservierung', 'Bestätigung Ihrer aktualisierten Reservierung', 'Stornierung Ihrer Reservierung'],
        'zh' => ['的预订确认', '确认您的预订', '的預訂確認', '的更新预订确认', '的更新預訂確認', '的預訂取消', '的预订取消'],
        'ja' => ['ご予約確認のお知らせ'],
        'es' => ['Su confirmación de reserva', 'La cancelación de su reserva #'],
        'ko' => ['예약 확인 번호', '예약 취소 번호'],
        'nl' => ['Uw reserveringsbevestiging'],
        'pt' => ['A sua confirmação de reserva', 'A confirmação da sua reserva'],
        'fr' => ['Votre confirmation de réservation', 'Votre annulation de réservation'],
        'it' => ['Conferma della tua prenotazione'],
        'pl' => ['Nr Twojego potwierdzenia rezerwacji'],
    ];

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'cancelledPhrases' => ['Your reservation has been cancelled.', 'Your reservation has been canceled.'],
            'address' => ['Address'],
            'dates' => ['Dates'],
            'confNumber' => 'Confirmation #',
            'cancelNumber' => 'Your cancellation number is #',
            // 'Room' => '',
            // 'Adult' => '',
            // 'SIGN IN' => '',
            // 'Front Desk' => '',
            // 'Reservation' => '',
            // 'Cancellation Policy' => '',
            // 'Summary of charges' => '',
            // 'Room details' => '',
            // 'Rate' => '',
            'statusPhrases' => ['your reservation is', 'Your reservation has been'],
            'statusVariants' => ['confirmed', 'cancelled', 'canceled', 'Cancelled', 'Canceled'],
            'totalCharges' => ['Total Charges', 'Total charges'],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            'totalPoints' => ['Total Points Redeemed', 'Total points redeemed'],
            // 'Points' => '',
            'nights' => ['nights stay', 'night stay'],
            // 'Check in' => '',
            // 'Check out' => '',
        ],
        'de' => [
            'cancelledPhrases' => 'Ihre Reservierung wurde storniert.',
            'address' => ['Adresse'],
            'dates' => ['Daten'],
            'confNumber' => 'Buchungsnummer',
            'cancelNumber' => 'Ihre Stornierungsnummer lautet #',
            'Room' => 'Zimmer',
            'Adult' => ['Erwachsener', 'Erwachsene'],
            'SIGN IN' => 'ANMELDEN',
            'Front Desk' => 'Rezeption',
            'Reservation' => 'Reservierung',
            'Cancellation Policy' => 'Stornierungsbedingungen',
            'Summary of charges' => 'Zusammenfassung der Kosten',
            'Room details' => 'Nähere Informationen zum Zimmer',
            'Rate' => 'Zimmerrate',
            'statusPhrases' => ['Ihre Reservierung ist', 'Ihre Reservierung wurde'],
            'statusVariants' => ['bestätigt', 'storniert', 'Storniert', 'abbrechen', 'Abbrechen'],
            'totalCharges' => ['Gesamtsumme'],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            // 'totalPoints' => ['Total Points Redeemed', 'Total points redeemed'],
            // 'Points' => '',
            'nights' => ['nacht übernachten'],
            // 'Check in' => '',
            // 'Check out' => '',
        ],
        'zh' => [
            'cancelledPhrases' => ['您的预订已取消。', '您的訂房已取消。'],
            'address' => ['地址'],
            'dates' => ['日期'],
            'confNumber' => ['确认号', '確認號碼'],
            'cancelNumber' => ['您的取消號碼為 #', '您的预订取消号是 #'],
            'Room' => '客房',
            'Adult' => '成人',
            'SIGN IN' => '登录',
            'Front Desk' => ['前台', '櫃台'],
            'Reservation' => ['预订', '訂房'],
            'Cancellation Policy' => ['预订处理中', '取消訂房政策', '取消政策'],
            'Summary of charges' => ['收费摘要', '收費摘要'],
            'Room details' => ['客房详细信息', '客房詳細資料'],
            'Rate' => ['房价','房價'],
            'statusPhrases' => ['您的预订', '您的訂房'],
            'statusVariants' => ['已确认', '已確認', '已修改', '已取消'],
            'totalCharges' => ['总费用', '收費總額'],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            'totalPoints' => ['已兑换的积分总计', '已兌換的總積分'],
            'Points' => ['积分', '積分'],
            'nights' => '房晚 住宿',
            'Check in' => ['登记入住', '登記入住'],
            'Check out' => ['退房'],
        ],
        'ja' => [
            // 'cancelledPhrases' => '',
            'address' => ['ご住所'],
            'dates' => ['日付'],
            'confNumber' => '予約確認番号',
            // 'cancelNumber' => '',
            'Room' => '室',
            'Adult' => '大人',
            'SIGN IN' => 'ログイン',
            'Front Desk' => 'フロントデスク',
            'Reservation' => '宿泊予約',
            'Cancellation Policy' => 'キャンセルポリシー',
            'Summary of charges' => '料金概要',
            'Room details' => '客室の詳細',
            'Rate' => '料金',
            // 'statusPhrases' => [''],
            // 'statusVariants' => [''],
            'totalCharges' => '合計請求額',
            // 'totalCCCharge' => [''],
            // 'totalPoints' => '',
            // 'Points' => '',
            'nights' => '泊 ご滞在',
            'Check in' => 'チェックイン',
            'Check out' => 'チェックアウト',
        ],
        'es' => [
            'cancelledPhrases' => 'Su reservación ha sido cancelada.',
            'address' => ['Dirección'],
            'dates' => ['Fechas'],
            'confNumber' => 'N.º de confirmación',
            'cancelNumber' => 'Su número de cancelación es #',
            'Room' => 'Habitación',
            'Adult' => 'adulto',
            'SIGN IN' => 'INICIAR SESIÓN',
            'Front Desk' => 'Recepción',
            'Reservation' => ['Reservación', 'Reserva'],
            'Cancellation Policy' => 'Política de cancelación',
            'Summary of charges' => 'Resumen de los cargos',
            'Room details' => 'Detalles de la habitación',
            'Rate' => 'Tarifa',
            'statusPhrases' => ['¡Su reserva está', 'Su reservación ha sido'],
            'statusVariants' => ['confirmada', 'cancelada', 'Cancelada', 'cancelado', 'Cancelado'],
            'totalCharges' => ['Cargos totales'],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            // 'totalPoints' => ['Total Points Redeemed', 'Total points redeemed'],
            'nights' => ['noche estadía', 'noche estancia'],
            'Check in' => ['Registro de entrada'],
            'Check out' => ['Registro de salida'],
        ],
        'ko' => [
            'cancelledPhrases' => '귀하의 예약이 취소되었습니다.',
            'address' => ['주소'],
            'dates' => ['날짜'],
            'confNumber' => '확인 번호',
            'cancelNumber' => '귀하의 취소 번호',
            'Room' => '객실',
            'Adult' => '성인',
            'Child' => '어린이',
            'SIGN IN' => '로그인',
            'Front Desk' => '프런트 데스크',
            'Reservation' => '예약',
            'Cancellation Policy' => '취소에 관한 방침',
            'Summary of charges' => '결제 내역 요약',
            'Room details' => '객실 세부 정보',
            'Rate' => '요금',
            'statusPhrases' => ['귀하의 예약이'],
            'statusVariants' => ['확인되었습니다', '취소 완료'],
            // 'totalCharges' => [''],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            'totalPoints' => ['총 사용 포인트'],
            'nights' => ['박 숙박'],
            'Points' => ['포인트'],
            'Check in' => '체크인',
            'Check out' => '체크아웃',
        ],
        'nl' => [
            // 'cancelledPhrases' => '',
            'address' => ['Adres'],
            'dates' => ['Datums'],
            'confNumber' => 'Bevestigingsnummer #',
            // 'cancelNumber' => '',
            'Room' => 'Kamer',
            'Adult' => 'Volwassene',
            //'Child' => '',
            'SIGN IN' => 'AANMELDEN',
            'Front Desk' => 'Receptie',
            'Reservation' => 'Reservering',
            'Cancellation Policy' => 'Annuleringsbeleid',
            'Summary of charges' => 'Kostenoverzicht',
            'Room details' => 'Kamergegevens',
            'Rate' => 'Tarief',
            'statusPhrases' => ['Uw reservering is'],
            'statusVariants' => ['bevestigd'],
            'totalCharges' => ['Totale kosten'],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            //'totalPoints' => [''],
            'nights' => ['overnachting verblijf'],
            // 'Points' => [''],
            'Check in' => ['inchecken'],
            'Check out' => ['uitchecken'],
        ],
        'pt' => [
            // 'cancelledPhrases' => '',
            'address' => ['Morada', 'Endereço'],
            'dates' => ['Datas'],
            'confNumber' => ['N.º de confirmação #', 'Nº de confirmação'],
            // 'cancelNumber' => '',
            'Room' => 'Quarto',
            'Adult' => 'Adulto',
            'SIGN IN' => 'LOGIN',
            'Front Desk' => ['Receção', 'Recepção'],
            'Reservation' => 'Reservas',
            'Cancellation Policy' => 'Política de cancelamento',
            'Summary of charges' => ['Resumo do valor a pagar', 'Resumo das despesas'],
            'Room details' => ['Detalhes do Quarto', 'Detalhes do quarto'],
            'Rate' => 'Tarifa',
            'statusPhrases' => ['A sua reserva está', 'Sua reserva está'],
            'statusVariants' => ['confirmada'],
            'totalCharges' => ['Total de encargos', 'Custo total'],
            // 'totalCCCharge' => 'Cobrança total no cartão de crédito',
            'totalPoints' => 'Total de pontos trocados',
            'Points' => 'Pontos',
            'nights' => ['noite estadia'],
            'Check in' => ['Entrada'],
            'Check out' => ['Saída'],
        ],
        'fr' => [
            'cancelledPhrases' => 'Votre réservation a été annulée.',
            'address' => ['Adresse'],
            'dates' => ['Dates'],
            'confNumber' => 'N° de confirmation',
            'cancelNumber' => 'Votre numéro d’annulation est le',
            'Room' => 'Chambre',
            'Adult' => 'adulte',
            'SIGN IN' => 'SE CONNECTER',
            'Front Desk' => 'Réception',
            'Reservation' => 'Réservation',
            'Cancellation Policy' => "Politique d'annulation",
            'Summary of charges' => 'Résumé des frais',
            'Room details' => 'En savoir plus sur la chambre',
            'Rate' => 'Tarif',
            'statusPhrases' => ['Votre réservation est', 'Votre réservation a été'],
            'statusVariants' => ['confirmée', 'annulée', 'Annulée', 'annuler', 'Annuler'],
            'totalCharges' => ['Total des frais'],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            // 'totalPoints' => ['Total Points Redeemed', 'Total points redeemed'],
            'nights' => ['nuit séjour'],
            'Check in' => ['Arrivée'],
            'Check out' => ['Départ'],
        ],
        'it' => [
            // 'cancelledPhrases' => '',
            'address' => ['Indirizzo'],
            'dates' => ['Date'],
            'confNumber' => 'N° conferma',
            // 'cancelNumber' => '',
            'Room' => 'Camera',
            'Adult' => 'adulto',
            'SIGN IN' => 'ACCEDI',
            // 'Front Desk' => '',
            'Reservation' => 'Prenotazione',
            'Cancellation Policy' => "Politica di cancellazione",
            'Summary of charges' => 'Riepilogo dei costi',
            'Room details' => 'Dettagli della camera',
            'Rate' => 'Tariffa',
            'statusPhrases' => ['La tua prenotazione è'],
            'statusVariants' => ['confermata'],
            'totalCharges' => ['Totale addebiti'],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            // 'totalPoints' => ['Total Points Redeemed', 'Total points redeemed'],
            'nights' => ['notte soggiorno'],
            // 'Check in' => [''],
            // 'Check out' => [''],
        ],
        'pl' => [
            // 'cancelledPhrases' => '',
            'address' => ['Adres'],
            'dates' => ['Daty'],
            'confNumber' => 'Numer potwierdzenia',
            // 'cancelNumber' => '',
            'Room' => 'Pokój',
            'Adult' => 'dorosły',
            'Child' => 'Dziecko',
            'SIGN IN' => 'ZALOGUJ SIĘ',
            // 'Front Desk' => '',
            'Reservation' => 'Rezerwacja',
            'Cancellation Policy' => "Polityka anulacji",
            'Summary of charges' => 'Podsumowanie opłat',
            'Room details' => 'Szczegóły dotyczące pokoju',
            'Rate' => 'Stawka',
            'statusPhrases' => ['Twoja rezerwacja została'],
            'statusVariants' => ['potwierdzona'],
            // 'totalCharges' => [''],
            // 'totalCCCharge' => ['Total Credit Card Charge', 'Total credit card charge'],
            'totalPoints' => ['Suma wykorzystanych punktów'],
            'Points' => ['Punkty'],
            'nights' => ['nocleg pobyt'],
            'Check in' => ['Zameldowanie'],
            'Check out' => ['Wymeldowanie'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]tx\.ihg\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ((!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true)
            && (!array_key_exists('subject', $headers) || strpos($headers['subject'], 'Holiday Inn Express') === false)
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

        $this->assignLang();
        $href = ['.tx.ihg.com/', 'click.tx.ihg.com'];

        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//text()[starts-with(normalize-space(translate(.,"Â","")),"©") and contains(normalize-space(translate(.,"Â","")),"InterContinental Hotels Group")]')->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(translate(.,"Â","")),"You have received this email as a result of your recent transaction with Holiday Inn Express") or contains(normalize-space(translate(.,"Â","")),"Sie haben diese E-Mail aufgrund Ihrer kürzlich durchgeführten Transaktion bei Holiday Inn Express") or contains(normalize-space(translate(.,"Â","")),"您收到此邮件是由于您近期在Holiday Inn Express® Hotels & Resorts交易过")]')->length === 0
        ) {
            return false;
        }
        return true;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
        }
        $email->setType('HolidayInnExpress' . ucfirst($this->lang));

        $textHtml = $this->http->Response['body'];

        if (strpos($textHtml, 'â') !== false) {
            $this->http->SetEmailBody(str_replace(['â', 'Â', ''], '', $textHtml)); // 0x8c
            $this->logger->debug('Found and removed bad simbols from HTML!');
        }

        $patterns = [
            'date' => '\b\d{1,2}[ ]*\d{1,2}\S+[ ]*\d{4}\b|\d{1,2}[. ]+\d{1,2}[. ]+\d{4}\b|.{4,}?\b\d{4}\b', // 15 4 2025  |  6 avr. 2025
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm
            'phone' => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52  |  (+351) 21 342 09 07  |  713.680.2992
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $h = $email->add()->hotel();

        $accountInfo = implode(' ', $this->http->FindNodes("//tr[ *[{$this->eq($this->t('SIGN IN'))}] ]/*[normalize-space()][last()]/descendant::text()[normalize-space()]"));

        if (preg_match("/^(?:\D+\s)?(\d{3,})$/u", $accountInfo, $m)) {
            // Platinum Elite 341675824‌
            $h->program()->account($m[1], false);
        }

        $statusTexts = array_filter($this->http->FindNodes("//text()[{$this->contains($this->t('statusPhrases'))}]", null, "/{$this->opt($this->t('statusPhrases'))}[:\s]*({$this->opt($this->t('statusVariants'))})(?:\s*[,.;:!?！。`]|$)/iu"));

        if (count(array_unique(array_map('mb_strtolower', $statusTexts))) === 1) {
            $status = array_shift($statusTexts);
            $h->general()->status($status);
        }

        $cancellationNumber = $this->http->FindSingleNode("//text()[{$this->starts($this->t('cancelNumber'))}]", null, true, "/^{$this->opt($this->t('cancelNumber'))}[:#\s]*([-A-z\d]{4,25})[,.!\s]*$/u");
        $h->general()->cancellationNumber($cancellationNumber, false, true);

        if ($cancellationNumber && $this->http->XPath->query("//*[{$this->contains($this->t('cancelledPhrases'))}]")->length > 0) {
            $h->general()->cancelled();
        }

        $confirmation = $this->http->FindSingleNode("//text()[{$this->starts($this->t('confNumber'))}]");

        if (preg_match("/^({$this->opt($this->t('confNumber'))})[:#\s]*([-A-z\d]{4,25})$/u", $confirmation, $m)) {
            $h->general()->confirmation($m[2], $m[1]);
        }

        $hotelName = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('address'), "translate(.,':：','')")}] ]/preceding::text()[normalize-space()][1]/ancestor::tr[1]");
        $address = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('address'), "translate(.,':：','')")}] ]/*[normalize-space()][2]");
        $phone = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Front Desk'), "translate(.,':：','')")}] ]/*[normalize-space()][2]", null, true, "/^({$patterns['phone']})(?:(?:\s*[,\/]\s*)+{$patterns['phone']})*$/u");

        if (!$hotelName) { // it-906291209-cancelled.eml
            $hotelName = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('dates'), "translate(.,':：','')")}] ]/preceding::tr[ count(*[normalize-space()])=2 and *[normalize-space()][2][{$this->starts($this->t('confNumber'))}] ]/*[normalize-space()][1]");
        }

        $h->hotel()->name($hotelName)->phone($phone, false, true);

        if ($address) {
            $h->hotel()->address($address);
        }

        $dateCheckIn = $dateCheckOut = $timeCheckIn = $timeCheckOut = null;

        $datesVal = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('dates'), "translate(.,':：','')")}] ]/*[normalize-space()][2]");
        $timesVal = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('dates'), "translate(.,':：','')")}] ]/following-sibling::tr[normalize-space()][1]");

        if (preg_match("/^({$patterns['date']})\s*-\s*({$patterns['date']})$/", $datesVal, $m)) {
            // 7 Mar 2025 - 9 Mar 2025
            $dateCheckIn = $this->normalizeDate($m[1]);
            $dateCheckOut = $this->normalizeDate($m[2]);
        }

        if (preg_match("/^{$this->opt($this->tPlusEn('Check in'))}[-：:\s]*((?:上午|下午|오전|오후)?\s*{$patterns['time']})/u", $timesVal, $m)) {
            if (preg_match("/^(?:上午|오전)\s*({$patterns['time']})$/u", $m[1], $t)) {
                $timeCheckIn = $t[1] . ' AM';
            } elseif (preg_match("/^(?:下午|오후)\s*({$patterns['time']})$/u", $m[1], $t)) {
                $timeCheckIn = $t[1] . ' PM';
            } else {
                $timeCheckIn = $m[1];
            }
        }

        if (preg_match("/(?:^|\/\s*){$this->opt($this->tPlusEn('Check out'))}[-：:\s]*((?:上午|下午|오전|오후)?\s*{$patterns['time']})/u", $timesVal, $m)) {
            if (preg_match("/^(?:上午|오전)\s*({$patterns['time']})$/u", $m[1], $t)) {
                $timeCheckOut = $t[1] . ' AM';
            } elseif (preg_match("/^(?:下午|오후)\s*({$patterns['time']})$/u", $m[1], $t)) {
                $timeCheckOut = $t[1] . ' PM';
            } else {
                $timeCheckOut = $m[1];
            }
        }

        if ($dateCheckIn && $timeCheckIn) {
            $h->booked()->checkIn(strtotime($timeCheckIn, $dateCheckIn));
        } elseif ($dateCheckIn && !$timesVal) {
            $h->booked()->checkIn($dateCheckIn);
        }

        if ($dateCheckOut && $timeCheckOut) {
            $h->booked()->checkOut(strtotime($timeCheckOut, $dateCheckOut));
        } elseif ($dateCheckOut && !$timesVal) {
            $h->booked()->checkOut($dateCheckOut);
        }

        /* 1 Room, 2 Adults, 4 Child */
        $reservationInfo1 = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Reservation'), "translate(.,'：:','')")}] ]/*[normalize-space()][2]");

        if (preg_match("/(\b\d{1,3})[-\s]*{$this->opt($this->t('Room'))}/ui", $reservationInfo1, $m)) {
            $h->booked()->rooms($m[1]);
        }

        if (preg_match("/(\b\d{1,3})[-\s]*{$this->opt($this->t('Adult'))}/ui", $reservationInfo1, $m)) {
            $h->booked()->guests($m[1]);
        }

        if (preg_match("/(\b\d{1,3})[-\s]*{$this->opt($this->t('Child'))}/ui", $reservationInfo1, $m)) {
            $h->booked()->kids($m[1]);
        }

        $travellers = [];

        $travellerRows = $this->http->FindNodes("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Reservation'), "translate(.,':：','')")}] ]/following-sibling::tr[normalize-space()]");

        foreach ($travellerRows as $tRow) {
            if (preg_match("/^((?:{$patterns['travellerName']}\s*,\s*)+){$this->opt(['Primary', 'Additional Guest', '主要', '其他宾客', 'Primär', '주요 투숙객', '代表者', 'Hoofdtekst', 'Principal', 'Primario', 'Główna'])}$/iu", $tRow, $m)) {
                $travellerList = preg_split('/(?:\s*,\s*)+/u', rtrim($m[1], ', '));

                foreach ($travellerList as $tName) {
                    if (!in_array($tName, $travellers)) {
                        $h->general()->traveller($tName, true);
                        $travellers[] = $tName;
                    }
                }
            }
        }

        $roomDetails = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Room details'), "translate(.,'：:','')")}] ]/*[normalize-space()][2]");

        $rateVal = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Rate'), "translate(.,'：:','')")}] ]/*[normalize-space()][2]");

        if ($roomDetails || $rateVal) {
            $room = $h->addRoom();
            $room->setType($roomDetails, false, true)->setRateType($rateVal, false, true);
        }

        $cancellation = $this->http->FindSingleNode("(//p[ descendant::text()[normalize-space()][1][{$this->eq($this->t('Cancellation Policy'), "translate(.,':：','')")}] ])[1]", null, true, "/^{$this->opt($this->t('Cancellation Policy'))}[:：\s]+(.{5,})$/u");
        $h->general()->cancellation($cancellation, false, true);

        if (preg_match("/Cancell?ing (?i)your reservation before\s*(?<time>{$patterns['time']})(?:\s*\([^\d()]*\))?\s+on\s+(?<date>{$patterns['date']})\s+will result in no charge\s*(?:[.;!]|$)/u", $cancellation, $m) // en
            || preg_match("/^Wenn Sie Ihre Reservierung vor\s*(?<time>{$patterns['time']})(?:\s*\(Ortszeit\))?\s+am\s+(?:[-[:alpha:]]+[,\s]+)?(?<date>\d{1,2}[-,.\s]*[[:alpha:]]+[-,.\s]*\d{4})\s+stornieren, entstehen Ihnen keine Kosten\s*(?:[.;!]|$)/iu", $cancellation, $m) // de
            || preg_match("/^Si cancela la reserva después de las\s*(?<time>{$patterns['time']})\s+\(hora local del hotel\) del\s*(?<date>{$patterns['date']})\s*o no se presenta\, perderá su depósito\./iu", $cancellation, $m) // es
            || preg_match("/^Si cancela su reserva antes de las\s*(?<time>{$patterns['time']})\s+\(hora local del hotel\) el \w+\,\s*(?<date>{$patterns['date']})\s*\, no se le cobrará recargo alguno\./u", $cancellation, $m)
            || preg_match("/^Em caso de cancelamento da reserva após\s*(?<time>{$patterns['time']})\s+\(hora local do hotel\) em\s*(?<date>{$patterns['date']})\s*ou de não comparecimento o seu depósito não será restituido\./u", $cancellation, $m) // pt
            || preg_match("/^O cancelamento da reserva antes de\s*(?<time>{$patterns['time']})(?:\s*\([^\d()]*\))?\s+em\s+(?:[-[:alpha:]]+[,\s]+)?(?<date>{$patterns['date']})\s+não resultará em cobrança\s*(?:[.;!]|$)/iu", $cancellation, $m) // pt
            || preg_match("/^Indien u de reservering annuleert vóór\s*(?<time>{$patterns['time']})\s+\(plaatselijke tijd hotel\) op \w+\,\s*(?<date>{$patterns['date']})\s*\, worden geen kosten in rekening gebracht\./u", $cancellation, $m) // nl
            || preg_match("/^Vous pouvez annuler votre réservation avant\s*(?<time>{$patterns['time']})\s+\(heure locale de l'hôtel\) le \w+\,\s*(?<date>{$patterns['date']})\s*sans frais\./u", $cancellation, $m) // fr
            || preg_match("/^La cancellazione della prenotazione prima delle\s*(?<time>{$patterns['time']})\s+\(ora locale dell'albergo\) del \w+\,\s*(?<date>{$patterns['date']})\s*non comporterà alcun addebito\./u", $cancellation, $m) // it
            || preg_match("/^Canceling your reservation after\s*(?<time>{$patterns['time']})\s+\(local hotel time\) on\s*(?<date>{$patterns['date']})\s*\, or failing to show\, will result in a charge of 1 night per room to your credit card or other guaranteed payment method\./u", $cancellation, $m) // pl
        ) {
            $dateDeadline = $this->normalizeDate($m['date']);
            $h->booked()->deadline(strtotime($m['time'], $dateDeadline));
        }

        if (preg_match("/^(?:在|您的预订将暂时保留至)?[ ]*(?<year>\d{4})年(?<month>\d{1,2})月(?<day>\d{1,2})日.+(?<time>(?:下午|上午)\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]*\（/u", $cancellation, $m) // zh
            || preg_match("/^(?<year>\d{4})년[ ]*(?<month>\d{1,2})월[ ]*(?<day>\d{1,2})일[ ]*(?<time>(?:오후|오전)[ ]*\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]*\(/u", $cancellation, $m) // ko
        ) {
            $dateDeadline = strtotime($m['month'].'/'.$m['day'].'/'.$m['year']);
            if (preg_match("/^(?:下午|오후)[ ]*(\d{1,2}\:\d{2})$/u", $m['time'], $t)){
                $timeDeadline = $t[1] . ' PM';
            } else if (preg_match("/^(?:上午|오전)[ ]*(\d{1,2}\:\d{2})$/u", $m['time'], $t)){
                $timeDeadline = $t[1] . ' AM';
            }
            $h->booked()->deadline(strtotime($timeDeadline, $dateDeadline));
        }

        $freeNightValues = [];

        /* price */

        $xpathTotalCharges1 = "count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('totalCharges'), "translate(.,'*:：','')")}]";
        // $xpathTotalCharges2 = "count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('totalCCCharge'), "translate(.,'*:：','')")}]";

        $totalPoints = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('totalPoints'), "translate(.,'*:：','')")}] ]/*[normalize-space()][2]", null, true, '/^.*\d.*$/u');

        if (preg_match("/^\d[,.’‘\'\d ]*{$this->opt($this->t('Points'))}?$/iu", $totalPoints)) {
            // 138,000 Points
            $h->price()->spentAwards($totalPoints);
        }

        $totalCharges = $this->http->FindSingleNode("//tr[{$xpathTotalCharges1}]/*[normalize-space()][2]", null, true, '/^.*\d.*$/u')
            // ?? $this->http->FindSingleNode("//tr[{$xpathTotalCharges2}]/*[normalize-space()][2]", null, true, '/^.*\d.*$/')
        ;

        if (preg_match('/^(?<amount>\d[,.’‘\'\d ]*?)[ ]*(?<currency>[^\-\d)(]+)$/u', $totalCharges, $matches)) {
            // 5,755.18 INR
            $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
            $h->price()->currency($matches['currency'])->total(PriceHelper::parse($matches['amount'], $currencyCode));

            $feeRows = $this->http->XPath->query("//tr[ preceding::tr[{$this->eq($this->t('Summary of charges'), "translate(.,'*:：','')")}] and following::tr[{$xpathTotalCharges1}] and *[2][normalize-space()] ]");

            foreach ($feeRows as $feeRow) {
                $feeName = $this->http->FindSingleNode('*[1]', $feeRow, true, '/^[*\s]*(.+?)[\s:：]*$/u');
                $feeValue = $this->http->FindSingleNode('*[2]', $feeRow);

                if (preg_match("/^(\d+)\s*{$this->opt($this->t('nights'))}(?:\s*\(|$)/iu", $feeName, $m)
                    && preg_match("/\b{$this->opt($this->t('Free Night'))}/ui", $feeValue)
                ) {
                    $freeNightValues[] = $m[1];

                    continue;
                }

                $feeCharge = preg_match('/^(.*?\d.*?)\s*(?:\(|$)/u', $feeValue, $m) ? $m[1] : null;

                if ( preg_match('/^(?<amount>\d[,.’‘\'\d ]*?)[ ]*(?:' . preg_quote($matches['currency'], '/') . ')?$/u', $feeCharge, $m) ) {
                    $feeAmount = PriceHelper::parse($m['amount'], $currencyCode);
                } else {
                    continue;
                }

                if (empty($h->getPrice()->getCost())
                    && preg_match("/^\d+\s*{$this->opt($this->t('nights'))}(?:\s*\(|$)/ui", $feeName)
                ) {
                    $h->price()->cost($feeAmount);
                } else {
                    $h->price()->fee($feeName, $feeAmount);
                }
            }
        }

        $freeNights = count($freeNightValues) > 0 ? array_sum($freeNightValues) : null;
        $h->booked()->freeNights($freeNights, false, true); // it-884940381.eml

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
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['dates']) || $this->http->XPath->query("//*[{$this->eq($phrases['dates'], "translate(.,':：','')")}]")->length === 0) {
                continue;
            }

            if (!empty($phrases['address']) && $this->http->XPath->query("//*[{$this->eq($phrases['address'], "translate(.,':：','')")}]")->length > 0
                || !empty($phrases['cancelledPhrases']) && $this->http->XPath->query("//*[{$this->contains($phrases['cancelledPhrases'])}]")->length > 0
            ) {
                $this->lang = $lang;
                return true;
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

    private function tPlusEn(string $s): array
    {
        return array_unique(array_merge((array) $this->t($s), (array) $this->t($s, 'en')));
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

    private function starts($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            "/^(\d{1,2})[,. ]+([[:alpha:]]+)[,. ]+(\d{4})$/u", // 6 avr. 2025
            "/^(\d{1,2})[ ]+(\d{1,2})[ ]+(\d{4})$/", // 14 4 2025
        ];

        $out = [
            "$1 $2 $3",
            "$2/$1/$3",
        ];

        $str = preg_replace($in, $out, $str);
        if (preg_match("/\b\d{1,2}\s+([[:alpha:]]+)[,.\s]+\d{4}\b/u", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        if (preg_match("#\d+\s+(\S+[ ]*(?:월|月))\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], 'zh')) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        if (preg_match("#(\d+)\s+(\d{1,2})(월|月)\s+(\d{4})$#u", $str, $m)) {
            return strtotime(date("F", strtotime("$m[4]-$m[2]-1")) . ' ' . $m[1] . ', ' . $m[4]);
        }

        return strtotime($str);
    }
}
