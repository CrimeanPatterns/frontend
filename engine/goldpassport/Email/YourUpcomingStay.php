<?php

namespace AwardWallet\Engine\goldpassport\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class YourUpcomingStay extends \TAccountChecker
{
    public $mailFiles = "goldpassport/it-757348146.eml, goldpassport/it-896648183.eml, goldpassport/it-896867015.eml, goldpassport/it-897789957.eml, goldpassport/it-898587124.eml, goldpassport/it-898587684.eml, goldpassport/it-898587906.eml";
    public $subjects = [
        'Reservation Details for Your Upcoming Stay at',
        'Reminder of Your Upcoming Stay at',
        'Thank You for Staying – Online Checkout is Now Available',
        'Online Check-In is Now Available for Your Stay at',
        'Online Check-In is Now Available for Your Stay at',
        'Has Been Cancelled',
        'Your Room is Ready',
        // de
        'Vielen Dank für Ihren Aufenthalt – der Online-Check-out steht Ihnen nun zur Verfügung',
        'Der Online-Check-in für Ihren Aufenthalt im',
        // es
        'Su estadía en',
        'fue cancelada',
        // zh
        '在线登记入住现已开放办理',
        // ja
        'での次回のご滞在が変更されました',
        'での次回ご滞在の詳細',
        // ko
        '서의 투숙 예약 상세 정보',
        '서의 투숙이 취소되었습니다',
    ];

    public $subject;

    public $lang = '';
    public $dateRelative;
    public $date;

    public $detectLang = [
        "en" => ["Reservation Details"],
        "de" => ["Reservierungsdetails"],
        "es" => ["Detalles de la reserva"],
        "zh" => ["预订详情", '預訂詳情'],
        "ja" => ["ご予約の詳細"],
        "ko" => ["예약 내역"],
    ];

    public static $dictionary = [
        "en" => [
            // hotel name
            // html: searchPhrase (.+)searchPhrase2. Use
            "searchPhrase"  => ['We hope you enjoyed your stay at'],
            "searchPhrase2" => [''],
            // 'Use' => '',
            // subject: namePhrase (.+) or (.+) namePhrase
            "namePhrase"    => [
                'Reservation Details for Your Upcoming Stay at', 'Reminder of Your Upcoming Stay at',
                'Online Check-In is Now Available for Your Stay at',
            ],
            "namePhrase2"   => [ // namePhrase2 (.+) namePhrase3
                'Your Upcoming Stay at', 'Your Stay at',
            ],
            "namePhrase3"   => ['Has Been Updated', 'Has Been Cancelled', 'Has'],
            // "Welcome to" => [''], // <>Welcome to (.+)<>Hello
            // "Hello" => [''],

            "cancelPhrase"  => ['Your reservation has been cancelled.'],
        ],
        "de" => [
            // hotel name
            // html: searchPhrase (.+)searchPhrase2. Use
            "searchPhrase"  => ['Wir hoffen, dass Ihnen Ihr Aufenthalt im'],
            "searchPhrase2" => ['Ulm gefallen hat'],
            'Use'           => 'Nutzen Sie',
            // subject: namePhrase (.+) or (.+) namePhrase
            "namePhrase" => ['Der Online-Check-in für Ihren Aufenthalt im'],
            // "namePhrase2" => [''], // namePhrase2 (.+) namePhrase3
            // "namePhrase3" => [''],
            // "Welcome to" => [''], // <>Welcome to (.+)<>Hello
            // "Hello" => [''],

            // "cancelPhrase" => [''],

            "Reservation Details" => 'Reservierungsdetails',
            "Check-in"            => 'Anreise',
            "Checkout"            => 'Abreise',
            "Confirmation #"      => 'Bestätigungsnummer',
            "Guest Name"          => 'Gästename',
            "Contact"             => 'Kontakt',
            // "Adults" => '',
            // "Children" => '',
            // "Room(s) booked" => '',
            // "Room type" => '',
            // "Room description" => '',
            // "CANCELLATION POLICY" => '',
            "Membership #" => 'Mitgliedsnummer',
        ],
        "es" => [
            // hotel name
            // html: searchPhrase (.+)searchPhrase2. Use
            // "searchPhrase" => [''],
            // "searchPhrase2" => [''],
            // 'Use' => '',
            // subject: namePhrase (.+) or (.+) namePhrase
            "namePhrase"          => ['Detalles de la reserva para su próxima estadía en', 'El check-in en línea ya está disponible para su estadía en'],
            "namePhrase2"         => ['Su estadía en', 'Su próxima estadía en'], // namePhrase2 (.+) namePhrase3
            "namePhrase3"         => ['fue cancelada', 'fue actualizada'],
            "Welcome to"          => ['Esperamos recibirle pronto en'], // <>Welcome to (.+)<>Hello
            "Hello"               => ['Le recordamos brevemente los detalles de su reserva,'],

            "cancelPhrase"  => ['Su reserva ha sido cancelada.'],

            "Reservation Details" => 'Detalles de la reserva',
            // "Check-in" => '',
            "Checkout"              => 'Check-out',
            "Confirmation #"        => 'Nº de confirmación',
            "Guest Name"            => 'A nombre de',
            "Membership #"          => 'Nº de membresía',
            "Contact"               => 'Contacto',
            "Adults"                => 'Personas adultas',
            "Children"              => 'Personas de edad infantil',
            "Room(s) booked"        => 'Habitación(es) reservada(s)',
            "Room type"             => 'Tipo de habitación',
            "Type of rate"          => ['Tipo de tarifa'],
            "Nightly rate per room" => ['Tarifa por habitación y por noche'],
            // "Room description" => '',
            // "CANCELLATION POLICY" => '',
        ],
        "zh" => [
            // hotel name
            // html: searchPhrase (.+)searchPhrase2. Use
            // "searchPhrase" => [''], // subject: searchPhrase .+
            // "searchPhrase2" => [''],
            // 'Use' => '',
            // subject: namePhrase (.+) or (.+) namePhrase
            // "namePhrase" => [''],
            "namePhrase2"         => ['有關您在', '有关您在', '您在'], // namePhrase2 (.+) namePhrase3
            "namePhrase3"         => ['近期住宿的預訂詳情', '近期住宿的预订详情', '的住宿已取消'],
            "Welcome to"          => ['欢迎莅临'], // <>Welcome to (.+)<>Hello
            "Hello"               => ['在线登记入住现已开放办理。'],

            // "cancelPhrase"  => ['Su reserva ha sido cancelada.'],

            "Reservation Details"   => ['预订详情', '預訂詳情'],
            "Guest Name"            => '客人姓名',
            "Confirmation #"        => ['确认号', '確認號'],
            "Check-in"              => '入住',
            "Checkout"              => '退房',
            "Contact"               => ['联系信息', '聯絡資料'],
            "Adults"                => '成人人數',
            "Children"              => '兒童人數',
            "Room(s) booked"        => ['已預訂客房數', '已预订客房数'],
            "Room type"             => ['客房類型', '房型'],
            "Room description"      => '偏好與政策',
            "Type of rate"          => ['房價類別', '房价类别'],
            "Nightly rate per room" => ['每房每晚房價', '每房每晚房价'],
            "CANCELLATION POLICY"   => ['取消預訂政策', '预订取消政策'],
            // "Membership #" => '',
        ],
        "ja" => [
            // hotel name
            // subject: searchPhrase .+
            // html: searchPhrase (.+)searchPhrase2. Use
            // "searchPhrase" => [''],
            // "searchPhrase2" => [''],
            // 'Use' => '',
            // subject: namePhrase (.+) or (.+) namePhrase
            "namePhrase" => ['での次回のご滞在が変更されました', 'での次回ご滞在の詳細'],
            // "namePhrase2"         => ['Su estadía en'], // namePhrase2 (.+) namePhrase3
            // "namePhrase3"         => ['fue cancelada'],
            // "Welcome to" => ['欢迎莅临'], // <>Welcome to (.+)<>Hello
            // "Hello" => ['在线登记入住现已开放办理。'],

            // "cancelPhrase"  => ['Su reserva ha sido cancelada.'],

            "Reservation Details"   => 'ご予約の詳細',
            "Guest Name"            => 'お客様のお名前',
            "Confirmation #"        => 'ご予約確認番号',
            "Check-in"              => 'チェックイン',
            "Checkout"              => 'チェックアウト',
            "Contact"               => '連絡先',
            "Adults"                => '大人',
            "Children"              => 'お子様',
            "Room(s) booked"        => 'ご予約いただいた客室数',
            "Room type"             => '客室タイプ',
            "Room description"      => 'ご希望と規約',
            "Type of rate"          => '料金タイプ',
            "Nightly rate per room" => '1泊あたりの客室料金',
            // "CANCELLATION POLICY" => '',
            // "Membership #" => '',
        ],
        "ko" => [
            // hotel name
            // subject: searchPhrase .+
            // html: searchPhrase (.+)searchPhrase2. Use
            // "searchPhrase" => [''],
            // "searchPhrase2" => [''],
            // 'Use' => '',
            // subject: namePhrase (.+) or (.+) namePhrase
            "namePhrase"          => ['투숙이 취소되었습니다'],
            "namePhrase2"         => ['다가오는'], // namePhrase2 (.+) namePhrase3
            "namePhrase3"         => ['투숙 예약 상세 정보'],
            // "Welcome to" => ['欢迎莅临'], // <>Welcome to (.+)<>Hello
            // "Hello" => ['在线登记入住现已开放办理。'],

            "cancelPhrase"  => ['고객님의 예약이 취소되었습니다.'],

            "Reservation Details" => '예약 내역',
            "Guest Name"          => '고객 이름',
            "Confirmation #"      => '확인 번호',
            "Check-in"            => '체크인',
            "Checkout"            => '체크아웃',
            "Contact"             => '연락처',
            "Adults"              => '성인',
            "Children"            => '어린이',
            "Room(s) booked"      => '객실 수',
            "Room type"           => '객실 유형',
            // "Room description" => '요금 정보',
            "Type of rate"          => '요금 유형',
            "Nightly rate per room" => '객실당 1박 요금',
            "CANCELLATION POLICY"   => '취소 규정',
            // "Membership #" => '',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@t1.hpe-esp.hyatt.com') !== false) {
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

        return $this->http->XPath->query("//text()[contains(normalize-space(), 'Hyatt') or contains(normalize-space(), 'privacy.hyatt.com')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Reservation Details'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Check-in'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Checkout'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]t1\.hpe-esp\.hyatt\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        $this->dateRelative = EmailDateHelper::getEmailDate($this, $parser);

        if (!empty($this->dateRelative)) {
            $this->dateRelative = strtotime("- 5 days", $this->dateRelative);
        }

        $this->subject = $parser->getSubject();

        $this->ParseHotel($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseHotel(Email $email)
    {
        $h = $email->add()->hotel();

        $h->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Confirmation #'))}]/ancestor::td[1]", null, true, "/{$this->opt($this->t('Confirmation #'))}\s*([\dA-Z]{5,})$/u"))
            ->traveller(preg_replace("/^(?:Mrs\.|Mr\.|Ms\.)/", "", $this->http->FindSingleNode("//text()[{$this->eq($this->t('Guest Name'))}]/ancestor::table[1]", null, true, "/^{$this->opt($this->t('Guest Name'))}\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/u")));

        $hotelName = $this->re("/{$this->opt($this->t('namePhrase'))}\s*(.+)(?:Ulm)?/u", $this->subject);

        if (empty($hotelName)) {
            $hotelName = $this->re("/{$this->opt($this->t('namePhrase2'))}\s+(.+)\s+{$this->opt($this->t('namePhrase3'))}/u", $this->subject);
        }

        if (empty($hotelName)) {
            $hotelName = $this->http->FindSingleNode("//text()[{$this->contains($this->t('searchPhrase'))}]",
                null, false, "/^{$this->opt($this->t('searchPhrase'))}[ ]+(.+)(?:{$this->opt($this->t('searchPhrase2'))})\.[ ]+{$this->opt($this->t('Use'))}/u");
        }

        if (empty($hotelName)) {
            $hotelName = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Welcome to'))}]/following::text()[normalize-space()][1][following::text()[normalize-space()][1][{$this->starts($this->t('Hello'))}]]");
        }

        if (empty($hotelName)) {
            $hotelName = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Welcome to'))}][following::text()[normalize-space()][1][{$this->starts($this->t('Hello'))}]]",
                null, true, "/^\s*{$this->opt($this->t('Welcome to'))}\s+(.+)/");
        }

        if (empty($hotelName)) {
            $hotelName = $this->re("/^(?:.+: |^)\s*(.+){$this->opt($this->t('namePhrase'))}\s*$/u", $this->subject);
        }

        $h->hotel()
            ->name($hotelName)
            ->address($this->http->FindSingleNode("//text()[{$this->eq($this->t('Contact'))}]/ancestor::tr[1]/following::img[1]/following::text()[normalize-space()][1]"));

        $phone = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Contact'))}]/ancestor::tr[1]/following::img[2]/following::text()[normalize-space()][1]", null, true, "#^[＋\-+()\dA-Z\s.,\\\/:]+\d+[-+()\dA-Z\s.,\\\/:]+$#u");

        if (!empty($phone)) {
            $h->hotel()
                ->phone(str_replace('＋', '+', $phone));
        }

        $inText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Check-in'))}]/ancestor::table[1]",
            null, true, "/^\s*{$this->opt($this->t('Check-in'))}\s*(.+)/u");
        $outText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Checkout'))}]/ancestor::table[1]",
            null, true, "/^\s*{$this->opt($this->t('Checkout'))}\s*(.+)/");

        $multipleDates = false;

        if (preg_match("/^\s*\D{10,}\d{1,2}:\d{2}\D{0,5}$/", $inText)
            && preg_match("/^\s*\D{10,}\d{1,2}:\d{2}\D{0,5}$/", $outText)
        ) {
            $inTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Check-in'))}]/ancestor::table[1]/descendant::text()[normalize-space()][3]",
                null, true, "/^\s*\d{1,2}:\d{2}\D{0,5}$/u");
            $outTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Checkout'))}]/ancestor::table[1]/descendant::text()[normalize-space()][3]",
                null, true, "/^\s*\d{1,2}:\d{2}\D{0,5}$/u");
            $multipleDates = true;
        } else {
            $h->booked()
                ->checkIn($this->normalizeDate($inText))
                ->checkOut($this->normalizeDate($outText));
        }

        $guests = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Adults'))}]/ancestor::table[1]", null, true, "/^{$this->opt($this->t('Adults'))}\s*(?:{$this->opt($this->t('Adults'))})?\s*(\d+)$/u");

        if (!empty($guests)) {
            $h->setGuestCount($guests);
        }

        $kids = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Children'))}]/ancestor::table[1]", null, true, "/^{$this->opt($this->t('Children'))}\s*(\d+)$/u");

        if ($kids !== null) {
            $h->setKidsCount($kids);
        }

        $roomsCount = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Room(s) booked'))}]/ancestor::table[1]", null, true, "/^{$this->opt($this->t('Room(s) booked'))}\s*(\d+)$/u");

        if (!empty($roomsCount) && $multipleDates === false) {
            $h->setRoomsCount($roomsCount);
        }

        $rooms = [];
        $rXpath = "text()[{$this->eq($this->t('Room type'))} or normalize-space() = 'Room type']";
        $roomsNodes = $this->http->XPath->query('//' . $rXpath);

        foreach ($roomsNodes as $ri => $rRoot) {
            $roomXpath = "following::text()[normalize-space()][count(preceding::{$rXpath}) = " . ($ri + 1) . "]";

            $type = $this->http->FindSingleNode($roomXpath . "[string-length(normalize-space(.))>2][1]", $rRoot, true, "/^(?:\s*-\s*)?(.+)/");
            $typeDescriptions = $this->http->FindSingleNode($roomXpath . "[{$this->eq($this->t('Room description'))} or normalize-space() = 'Room description']/following::text()[string-length(normalize-space(.))>2][1]", $rRoot, true, "#^(?:\s*-\s*)?(.+)#u");

            $rateType = $this->http->FindSingleNode('(./' . $roomXpath . "[{$this->eq($this->t('Type of rate'))} or normalize-space() = 'Type of rate'])[1]/following::text()[string-length(normalize-space(.))>2][1]", $rRoot);

            $ratesNodes = $this->http->XPath->query($roomXpath . "[{$this->eq($this->t('Nightly rate per room'))} or normalize-space() = 'Nightly rate per room']/following::text()[normalize-space()][position() < 20]", $rRoot);
            $rates = [];
            $rateRows = [];

            foreach ($ratesNodes as $rRate) {
                $value = $this->http->FindSingleNode("self::text()[not(ancestor::b) and not(ancestor::b)]", $rRate, true, "/.+ [\-\–\-] .+/u");

                if (empty($value) || preg_match("/.+:\s*$/", $value)) {
                    break;
                }
                $rateRows[] = $value;
            }

            $freeNight = 0;
            $dateFormat = '\w+[.,]?\s+\w+[.]?';

            if (empty($this->dateRelative) && $multipleDates === false) {
                if (!empty($h->getCheckInDate())) {
                    $this->dateRelative = strtotime('-1 day', $h->getCheckInDate());
                }
            }

            if ($multipleDates === true && empty($this->dateRelative)) {
            } else {
                foreach ($rateRows as $row) {
                    if (preg_match("/^\s*({$dateFormat})\s+[\-\–\-]\s+({$dateFormat})\s+[\-\–\-]\s+(.+)/u", $row, $m)) {
                        $date1 = $this->normalizeDate($m[1]);
                        $date2 = $this->normalizeDate($m[2]);

                        $rdate = $date1;

                        if ($date2 > $date1) {
                            $i = 0;

                            while ($rdate <= $date2 && $i < 20) {
                                $rates[$rdate] = $m[3];
                                $rdate = strtotime("+1 day", $rdate);

                                if (preg_match("/^\D*0[., 0]*\D*$/", $m[3])) {
                                    $freeNight++;
                                }
                            }
                        }
                    } elseif (preg_match("/^\s*({$dateFormat})\s+[\-\–\-]\s+(.+)/u", $row, $m)) {
                        if (preg_match("/^\D*0[., 0]*\D*$/", $m[2])) {
                            $freeNight++;
                        }

                        $date1 = $this->normalizeDate($m[1]);
                        $rates[$date1] = $m[2];
                    } else {
                        $rates = [];
                        $this->logger->debug('TO DO: add this case');
                        $h->addRoom();

                        break;
                    }
                }
            }
            $rooms[] = [
                'type'                 => $type,
                'typeDescriptions'     => $typeDescriptions,
                'rates'                => $rates,
                'freeNight'            => $freeNight,
                'rateType'             => $rateType,
            ];
        }

        if ($multipleDates === true) {
            $hotelInfo = $h->toArray();
        }

        foreach ($rooms as $i => $room) {
            if ($multipleDates === false) {
                $h->booked()->rooms($roomsCount);

                $h->addRoom()
                    ->setType($room['type'])
                    ->setDescription($room['typeDescriptions'], true, true)
                    ->setRates($room['rates'])
                    ->setRateType($room['rateType'], true, true);
            } elseif ($multipleDates === true) {
                if ($i !== 0) {
                    $h = $email->add()->hotel();

                    $h->fromArray($hotelInfo);
                }

                $h->addRoom()
                    ->setType($room['type'])
                    ->setDescription($room['typeDescriptions'], true, true)
                    ->setRateType($room['rateType'], true, true)
                    ->setRates($room['rates']);

                $inDate = array_key_first($room['rates']);

                if (!empty($inDate) && !empty($inTime)) {
                    $inDate = strtotime($inTime, $inDate);
                }
                $outDate = strtotime("+ 1 day", array_key_last($room['rates']));

                if (!empty($outDate) && !empty($outTime)) {
                    $inDate = strtotime($outTime, $inDate);
                }
                $h->booked()
                    ->checkIn($inDate)
                    ->checkOut($outDate);
            }
        }

        // $this->logger->debug('$rooms = '.print_r( $rooms,true));

        $cancellation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('CANCELLATION POLICY'))}]/following::tr[1]");

        if ($cancellation !== null) {
            $h->general()
                ->cancellation($cancellation);

            $this->detectDeadLine($h);
        }

        $accounts = array_unique(array_filter($this->http->FindNodes("//text()[{$this->eq($this->t('Membership #'))}]/ancestor::table[1]", null, "/{$this->opt($this->t('Membership #'))}\s*([\*\d]+)/u")));

        if (count($accounts) > 0) {
            foreach ($accounts as $account) {
                $pax = preg_replace("/^(?:Mrs\.|Mr\.|Ms\.)/", "", $this->http->FindSingleNode("//text()[{$this->eq($this->t('Membership #'))}]/ancestor::table[1]/preceding::table[1]/descendant::text()[normalize-space()][not({$this->contains($this->t('Guest Name'))})][1][not({$this->contains($this->t('Membership #'))})]"));

                if (!empty($pax)) {
                    $h->addAccountNumber($account, true, $pax);
                } else {
                    $h->addAccountNumber($account, true);
                }
            }
        }

        $cancelled = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('cancelPhrase'))}])[1]");

        if (!empty($cancelled)) {
            $h->general()
                ->status('Cancelled')
                ->cancelled();
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
            return "normalize-space(.)=\"{$s}\"";
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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function normalizeDate($date)
    {
        // $this->logger->debug('$date in = ' . print_r($date, true));
        $year = $this->year ?? date('Y', $this->dateRelative);

        $in = [
            // Thursday, 14-Nov-2024 03:00 PM
            // martes, 8 de abril de 2025 03:00 PM
            "/\s*(?:\w+\,\s*)?(\d+)\s*(?:\-| +de +|\s+)\s*(\w+)\s*(?:\-| +de +|\s+)\s*(\d{4})\s*(\d+\:\d+\s*[AP]M)$/ui",
            // Thursday, Apr 7, 2025 03:00 PM
            "/\s*(?:\w+\,\s*)?(\w+)\s*(\d+)\,[ ]*(\d{4})\s*(\d+\:\d+\s*[AP]M)\s*$/ui",
            // June 25 2025
            "/\s*([[:alpha:]]+)\s*(\d+)\s+(\d{4})\s*$/ui",
            //2025-05-20 12:00 PM
            "/\s*(\d{4}-\d{1,2}-\d{1,2})\s+(\d+\:\d+\s*[AP]M)\s*$/ui",
            // 日曜日, 2025年6月29日 03:00 PM
            "/\s*[[:alpha:]]+\s*,\s*(\d{4})\s*年\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日\s+(\d+\:\d+\s*[AP]M)\s*$/ui",
            // 2025년 7월 11일, 금요일 03:00 PM
            // 2025年5月23日 星期五 12:00 PM
            "/\s*(\d{4})\s*[년年]\s*(\d{1,2})\s*[월月]\s*(\d{1,2})\s*[일日],?\s*[[:alpha:]]+\s+(\d+\:\d+\s*[AP]M)\s*$/ui",

            // without year
            // 7月 5日
            "/\s*(\d{1,2})\s*[月월]\s*(\d{1,2})\s*[日일]\s*$/ui",
            "/\s*([[:alpha:]]+)\s+(\d{1,2})\s*$/ui",
        ];
        $out = [
            "$1 $2 $3, $4",
            "$2 $1 $3, $4",
            "$2 $1 $3",
            "$1, $2",
            "$1-$2-$3, $4",
            "$1-$2-$3, $4",

            // without year
            "%year%-$1-$2",
            "$2 $1 %year%",
        ];
        $date = preg_replace($in, $out, $date);
        // $this->logger->debug('$date out = ' . print_r($date, true));

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        if (!empty($this->dateRelative) && $this->dateRelative > strtotime('01.01.2000') && strpos($date, '%year%') !== false
            && (preg_match('/^\s*(?<date>\d+ \w+) %year%(?:\s*,\s(?<time>\d{1,2}:\d{1,2}.*))?$/', $date, $m)
            || preg_match('/^\s*%year%(?<date>-\d+-\d+)(?:\s*,\s(?<time>\d{1,2}:\d{1,2}.*))?$/', $date, $m))
        ) {
            $format = '%D% %Y%';

            if (preg_match('/^\s*%year%(?<date>-\d+-\d+)(?:\s*,\s(?<time>\d{1,2}:\d{1,2}.*))?$/', $date)) {
                $format = '%Y%%D%';
            }
            $date = EmailDateHelper::parseDateRelative($m['date'], $this->dateRelative, true, $format);

            if (!empty($date) && !empty($m['time'])) {
                return strtotime($m['time'], $date);
            }

            return $date;
        } elseif (($year) > 2000 && preg_match("/^(?<week>\w+), (?<date>\d+ \w+ .+)/u", $date, $m)) {
            // $this->logger->debug('$date (week no year) = ' . print_r($date, true));
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));

            return EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/^\d+ [[:alpha:]]+ \d{4}(,\s*\d{1,2}:\d{2}(?: ?[ap]m)?)?$/ui", $date)
            || preg_match("/^\d+-\d+-\d+(,\s*\d{1,2}:\d{2}(?: ?[ap]m)?)?$/ui", $date)
        ) {
            // $this->logger->debug('$date (year) = ' . print_r($date, true));

            return strtotime($date);
        } else {
            return null;
        }

        return null;
    }

    private function assignLang()
    {
        foreach ($this->detectLang as $lang => $array) {
            foreach ($array as $value) {
                if ($this->http->XPath->query("//text()[{$this->contains($value)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function detectDeadLine(Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        if (preg_match("/^(?<time>\d+\:\d+A?P?M)\s+HOTEL TIME\s+(?<prior>\d+\s+(?:days?|DYS)) BFR ARRV?/iu", $cancellationText, $m)
        || preg_match("/PLEASE BE AWARE THAT CANCELLATIONS MADE LESS THAN (?<prior>\d+\s+\w+) BEFORE (?<time>\d+\:\d+) ON THE DAY OF ARRIVAL/iu", $cancellationText, $m)) {
            $h->booked()
                ->deadlineRelative($m['prior'], $m['time']);
        }

        if (preg_match("/RESERVATIONS CANCELLED WITHIN\s+(?<prior>\d+\s*\w+)\s+OF DAY\s+OF\s+ARRIVAL\s*WILL/iu", $cancellationText, $m)
        || preg_match("/RESERVATIONS CANCELLED WITHIN (?<prior>\d+\s*\w+) OF ARRIVAL/iu", $cancellationText, $m)
        || preg_match("/PLEASE BE AWARE THAT CANCELLATIONS MADE LESS THAN (?<prior>\d+\s*\w+) BEFORE ARRIVAL/iu", $cancellationText, $m)
        || preg_match("/^(?<prior>\d+\s*(?:HRS|DAYS))\s*PRIOR OR \d+\s*(?:NIGHT|NT) FEE/iu", $cancellationText, $m)) {
            $h->booked()
                ->deadlineRelative($m['prior']);
        }

        if (preg_match("/PLEASE BE AWARE THAT CANCELLATIONS MADE AFTER (?<time>\d+\:\d+) ON THE DAY OF ARRIVAL/iu", $cancellationText, $m)) {
            $h->booked()
                ->deadlineRelative('0 day', $m['time']);
        }
    }
}
