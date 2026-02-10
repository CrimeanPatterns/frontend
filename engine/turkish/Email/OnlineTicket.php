<?php

namespace AwardWallet\Engine\turkish\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class OnlineTicket extends \TAccountChecker
{
    public $mailFiles = "turkish/it-153731479.eml, turkish/it-154439353.eml, turkish/it-193164096.eml, turkish/it-826088730.eml, turkish/it-828738567.eml, turkish/it-828785091.eml, turkish/it-887140657.eml, turkish/it-889102078.eml";

    private $detectSubject = [
        // en, fr, zh
        'Turkish Airlines - Online Ticket - Information Message',
        // de
        'Turkish Airlines – Online-Ticket – Informationsmeldung',
        // tr
        'Türk Hava Yolları - Online Bilet - Bilgi Mesajı',
        // ru
        'Turkish Airlines — Электронный билет — Информационное сообщение',
        // fr
        'Turkish Airlines - Billet en ligne - Message d\'information',
        // zh
        'Turkish Airlines - 在线购票 - 信息消息',
    ];

    private static $dictionary = [
        'en' => [
            //            'Dear' => '',
            'Booking Reference' => 'Booking Reference',
            'Journey Duration'  => ['Journey Duration', 'Journey duration'],
            'Next day'          => 'Next day',
            //'Direct flight' => '',
            //            'Seat' => '',
            //            'Baggage' => '',
            'Miles' => ['MIL', 'MILES'],
            //            'Taxes and other charges' => '',
            //            'Additional Services' => '',
            // 'Airline imposed fees' => '',
            'TOTAL:'            => ['TOTAL:', 'TOTAL :', 'Total :'],
            'Request e-Invoice' => ['Request e-Invoice', 'Request e-invoice'],
        ],
        'de' => [
            'Dear'                    => 'Sehr geehrte/r',
            'Booking Reference'       => 'Buchungsreferenz',
            'Journey Duration'        => ['Reisedauer', 'Journey duration'],
            //'Direct flight' => '',
            // 'Next day' => '',
            'Seat'                    => ['Sitzplatz', 'Seat'],
            'Baggage'                 => ['Gepäck', 'Baggage'],
            'Miles'                   => ['MIL', 'MILES'],
            'Taxes and other charges' => ['Steuern und andere Gebühren', 'Taxes, fees and expenses', 'Taxes and other charges'],
            'Additional Services'     => 'Additional services',
            // 'Airline imposed fees' => '',
            'TOTAL:'            => ['GESAMT:', 'GESAMT :'],
            'Request e-Invoice' => ['Elektronische Rechnung anfordern', 'Request e-invoice'],
        ],
        'tr' => [
            'Dear'                    => 'Sayın',
            'Booking Reference'       => 'Rezervasyon kodu',
            'Journey Duration'        => ['Yolculuk Süresi', 'Yolculuk süresi'],
            'Direct flight'           => 'Direkt uçuş',
            'Next day'                => 'Sonraki gün',
            'Seat'                    => 'Koltuk',
            'Baggage'                 => 'Bagaj',
            'Miles'                   => ['MIL', 'MILES'],
            'Taxes and other charges' => ['Vergi ve diğer harçlar'],
            'Additional Services'     => 'Ek Hizmetler',
            'TOTAL:'                  => ['TOPLAM:', 'Toplam :', 'TOPLAM :'],
            'Request e-Invoice'       => ['E-Fatura Talep Et', 'E-fatura talep et'],
            'Airline imposed fees'    => 'Hava yolu tarafından belirlenen ücretler',
        ],
        'ru' => [
            'Dear'                    => 'Уважаемый(ая)',
            'Booking Reference'       => 'Номер бронирования',
            'Journey Duration'        => 'Продолжительность путешествия',
            'Direct flight'           => 'Прямой рейс',
            'Next day'                => 'Следующий день',
            'Seat'                    => 'Место',
            'Baggage'                 => 'Багаж',
            'Miles'                   => ['MIL', 'MILES'],
            'Taxes and other charges' => 'Налоги и другие сборы',
            'Additional Services'     => 'Дополнительные услуги',
            // 'Airline imposed fees' => '',
            'TOTAL:'                  => ['ВСЕГО:', 'ВСЕГО :'],
            'Request e-Invoice'       => 'Запросить электронный счет на оплату',
        ],
        'fr' => [
            'Dear'                    => ['Dear', "Chère/Cher"],
            'Booking Reference'       => 'Référence de réservation',
            'Journey Duration'        => ['Journey duration', 'Durée du voyage'],
            'Direct flight'           => ['Direct flight', 'Vols directs'],
            'Next day'                => 'Jour suivant',
            'Seat'                    => ['Seat', 'Siège'],
            'Baggage'                 => ['Baggage', 'Bagages'],
            'Miles'                   => ['MIL', 'MILES', 'Miles'],
            'Taxes and other charges' => ['Taxes and other charges', 'Taxes et autres frais'],
            'Additional Services'     => 'Additional Services',
            // 'Airline imposed fees' => '',
            'TOTAL:'                  => ['Total :', 'TOTAL :'],
            'Request e-Invoice'       => ['Request e-Invoice', 'Request e-invoice', 'Demander une facture électronique'],
        ],
        'zh' => [
            'Dear'                    => ['Dear', '尊贵的'],
            'Booking Reference'       => '预订参考',
            'Journey Duration'        => ['Journey duration', '旅程时长'],
            'Direct flight'           => ['Direct flight', '直飞航班'],
            'Next day'                => '次日',
            'Seat'                    => ['Seat', '座位选择'],
            'Baggage'                 => ['Baggage', '行李'],
            'Miles'                   => ['MIL', 'MILES'],
            'Taxes and other charges' => ['Taxes and other charges', '税费和其他费用'],
            'Additional Services'     => ['Additional Services', '附加服务'],
            'TOTAL:'                  => '总计 :',
            'Request e-Invoice'       => ['Request e-Invoice', 'Request e-invoice', '申请电子发票'],
        ],
        'pt' => [
            'Dear'                    => 'Dear',
            'Booking Reference'       => 'Referência da reserva',
            'Journey Duration'        => ['Journey duration', 'Duração da viagem'],
            'Direct flight'           => ['Direct flight', 'Voo direto'],
            'Next day'                => 'Dia seguinte',
            'Seat'                    => ['Seat', 'Seleção de lugar'],
            'Baggage'                 => 'Baggage',
            'Miles'                   => ['MIL', 'MILES'],
            'Taxes and other charges' => 'Taxes and other charges',
            'Additional Services'     => 'Additional Services',
            'Airline imposed fees'    => 'Taxas impostas pela companhia aérea',
            'TOTAL:'                  => 'Total :',
            'Request e-Invoice'       => ['Request e-Invoice', 'Request e-invoice', 'Pedido de fatura eletrónica'],
        ],
        'es' => [
            'Dear'                    => 'Estimado/a ',
            'Booking Reference'       => 'Referencia de reserva',
            'Journey Duration'        => 'Duración del trayecto',
            // 'Direct flight'           => 'Direct flight',
            // 'Next day'                => 'Dia seguinte',
            'Seat'                    => 'Selección de asientos',
            'Baggage'                 => 'Equipaje',
            'Miles'                   => ['MIL', 'MILES', 'MILE'],
            'Taxes and other charges' => 'Impuestos y otros cargos',
            'Additional Services'     => 'Servicios adicionales',
            'Airline imposed fees'    => 'Tarifas impuestas por la aerolínea',
            'TOTAL:'                  => 'Total :',
            'Request e-Invoice'       => ['Solicitar factura electrónica'],
        ],
        'it' => [
            'Dear'                    => ['Dear ', 'Gentile '],
            'Booking Reference'       => 'Riferimento prenotazione',
            'Journey Duration'        => ['Journey duration', 'Durata del viaggio'],
            'Direct flight'           => 'Direct flight',
            'Next day'                => 'Giorno successivo',
            'Seat'                    => ['Seat selection', 'Selezione del posto'],
            'Baggage'                 => 'Baggage',
            'Miles'                   => ['MIL', 'MILES', 'MILE'],
            'Taxes and other charges' => 'Taxes and other charges',
            'Additional Services'     => 'Additional Services',
            'Airline imposed fees'    => 'Airline imposed fees',
            'TOTAL:'                  => 'Totale :',
            'Request e-Invoice'       => ['Request e-invoice'],
        ],
    ];

    private $lang = '';

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->detectBody();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return null;
        }

        $this->parseEmail($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//a[contains(@href,'turkishairlines.com')]")->length > 0
            || $this->http->XPath->query("//img[contains(@src,'.thy.com/')]")->length > 0
        ) {
            return $this->detectBody();
        }

        return false;
    }

    public function detectBody()
    {
        foreach (self::$dictionary as $lang => $detect) {
            if (!empty($detect['Journey Duration'])
                && $this->http->XPath->query("//tr[count(td[normalize-space()]) > 3]/td[normalize-space()][last()][" . $this->starts($detect['Journey Duration']) . "]")->length > 0
                && !empty($detect['Booking Reference'])
                && $this->http->XPath->query("//*[" . $this->starts($detect['Booking Reference']) . "]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (
            stripos($headers['from'], 'onlineticket@thy.com') === false
            && stripos($headers['from'], '@mail.turkishairlines.com') === false
            && stripos($headers['subject'], 'Turkish Airlines') === false
            && stripos($headers['subject'], 'Türk Hava Yolları') === false
        ) {
            return false;
        }

        foreach ($this->detectSubject as $sub) {
            if (stripos($headers['subject'], $sub) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@thy.com') !== false
            || stripos($from, '@mail.turkishairlines.com') !== false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function parseEmail(Email $email)
    {
        $f = $email->add()->flight();

        $confirmation = $this->http->FindSingleNode('//text()[' . $this->eq($this->t('Booking Reference')) . ']/following::text()[normalize-space(.)][1]',
            null, true, '/^\s*[A-Z\d]{5,7}\s*$/');

        if (empty($confirmation)) {
            $confirmation = array_filter($this->http->FindNodes('//text()[' . $this->starts($this->t('Booking Reference')) . ']',
                null, "/^\s*{$this->opt($this->t('Booking Reference'))}\s*:\s*([A-Z\d]{5,7})\s*$/u"))[0] ?? null;
        }

        $f->general()
            ->confirmation($confirmation);

        // Passengers adn Tickets
        $isAllTravellers = false;
        $travellerRegexp = "[A-Z][A-Z\-]*(?: [A-Z\-]+)+";
        $travellers = [];
        $tickets = [];
        $pXpath = "//*[" . $this->eq($this->t("Request e-Invoice")) . "]/preceding-sibling::*[normalize-space()][1]";
        $pRows = $this->http->XPath->query($pXpath);

        foreach ($pRows as $pRoot) {
            $isAllTravellers = true;
            $values = $this->http->FindNodes(".//text()[normalize-space()]", $pRoot);

            if (count($values) == 2) {
                $travellers[] = $values[1];
                $f->addTicketNumber($values[0], false, $values[1]);
            } else {
                $travellers = [];

                break;
            }
        }

        if (empty($travellers)) {
            $route = $this->http->FindSingleNode("(//text()[" . $this->eq($this->t("Seat")) . " or " . $this->eq($this->t("Baggage")) . "]/following::text()[normalize-space()][2])[1]",
                null, true, "/^[^-]+ - [^-]+$/");

            if (!empty($route)) {
                $isAllTravellers = true;
                $travellers = array_unique($this->http->FindNodes("//text()[" . $this->eq($route) . "]/preceding::text()[normalize-space()][1][preceding::text()[" . $this->eq($this->t("Seat")) . " or " . $this->eq($this->t("Baggage")) . "]]"));
            }
        }

        if ($isAllTravellers) {
            $f->general()
                ->travellers($travellers, true);
        } else {
            $pax = $this->http->FindSingleNode("//text()[" . $this->eq($this->t("Dear")) . "]/following::text()[normalize-space()][1]",
                null, true, "/^\s*(?:(?:Mr|Ms|Mrs|Dr|先生|Sig)[\. ]+)?({$travellerRegexp}),?\s*$/i");

            if (!empty($pax)) {
                $f->general()
                    ->traveller($pax, true);
            }
        }

        // Price
        $total = $this->http->FindSingleNode("//td[not(.//td)][" . $this->starts($this->t('TOTAL:')) . "][not(preceding::tr[normalize-space()][1][{$this->eq($this->t('Airline imposed fees'))}])]",
            null, true, "/^\s*" . $this->opt($this->t('TOTAL:')) . "\s*(.+)/");

        if (preg_match("/^\s*(?<miles>[\d\.\,]+\s*{$this->opt($this->t('Miles'))})\s*(?:\+|$)/", $total, $m)
            || preg_match("/^\s*(?<miles>{$this->opt($this->t('Miles'))}\s*[\d\.\,]+)\s*(?:\+|$)/", $total, $m)
        ) {
            $f->price()
                ->spentAwards($m['miles']);
            $total = trim(str_replace($m[0], '', $total));
        }

        if (preg_match('/^\s*(?<currency>[A-Z]{3})\s+(?<total>\d[.,\d ]*)\s*$/', $total, $m)
            || preg_match('/^\s*(?<total>\d[.,\d ]*)\s+(?<currency>[A-Z]{3})\s*$/', $total, $m)
        ) {
            $f->price()
                ->total(PriceHelper::parse($m['total'], $m['currency']))
                ->currency($m['currency']);

            $feeXpath = "//td[" . $this->eq($this->t("Taxes and other charges")) . "]/ancestor::tr[1]/following-sibling::*[normalize-space()][td[normalize-space()][1][not(" . $this->eq($this->t("Additional Services")) . ")]]";

            foreach ($this->http->XPath->query($feeXpath) as $fRoot) {
                $name = $this->http->FindSingleNode("td[normalize-space()][1]", $fRoot);

                if (preg_match("/^\s*" . $this->opt($this->t('TOTAL:')) . "\s*(.+)/", $name)) {
                    break;
                }
                $valueStr = $this->http->FindSingleNode("td[normalize-space()][2]", $fRoot);
                $value = null;

                if (preg_match('/^\s*' . $m['currency'] . '\s+(?<total>\d[.,\d ]*)\s*$/', $valueStr, $fm)
                    || preg_match('/^\s*(?<total>\d[.,\d ]*)\s+' . $m['currency'] . '\s*$/', $valueStr, $fm)
                ) {
                    $value = PriceHelper::parse($fm['total'], $m['currency']);
                }

                if (!empty($value) && !empty($name)) {
                    $f->price()
                        ->fee($name, $value);
                }
            }
        }

        $xpath = "//tr[count(td[normalize-space()]) > 3][td[normalize-space()][last()][" . $this->starts($this->t('Journey Duration')) . "]]";
        $segments = $this->http->XPath->query($xpath);

        if ($segments->length === 0) {
            $this->logger->debug('Segments not found by: ' . $xpath);

            return $email;
        }

        foreach ($segments as $root) {
            $s = $f->addSegment();

            $date = null;
            $dateText = implode("\n", $this->http->FindNodes('preceding::tr[normalize-space(.)][1]/*', $root));
            $dateText = preg_replace("/^\s*.+\([A-Z]{3}\)\s+-\s+.+\([A-Z]{3}\)\s*/", '', $dateText);

            if (preg_match("/^\s*(?<date>.*\b\d{4}\b.*)\n\s*(?<cabin>\p{Lu}[[:alpha:]]+(?: [[:alpha:]]+)?|\p{Han}+)\s*\((?<bookingCode>[A-Z]{1,2})\)(?: - .*)?$/u", $dateText, $m)) {
                $date = $m['date'];

                $s->extra()
                    ->cabin($m['cabin'])
                    ->bookingCode($m['bookingCode']);
            } elseif (preg_match("/^\s*(?<date>.*\b\d{4}\b.*)\s*$/u", $dateText, $m)) {
                $date = $m['date'];
            }

            // Departure
            $s->departure()
                ->code($this->http->FindSingleNode('td[normalize-space()][1]', $root, null, "/^\s*\d{1,2}:\d{2}\s*([A-Z]{3})\s*$/"));

            $time = $this->http->FindSingleNode('td[normalize-space()][1]', $root, null, "/^\s*(\d{1,2}:\d{2})\s*[A-Z]{3}\s*$/");

            if (!empty($date) && !empty($time)) {
                $s->departure()
                    ->date($this->normalizeDate($date . ', ' . $time));
            }

            $flightInfo = $this->http->FindSingleNode("td[normalize-space()][2][{$this->starts($this->t('Direct flight'))}]", $root);

            if (preg_match("/^{$this->opt($this->t('Direct flight'))}\s*(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{1,4})$/", $flightInfo, $m)) {
                $s->airline()
                    ->name($m['aName'])
                    ->number($m['fNumber']);
            }

            $connections = $this->http->FindSingleNode('td[normalize-space()][2]', $root, null, "/^\s*(\d+)/");

            if (empty($connections)) {
                if (empty($s->getFlightNumber())) {
                    $s->airline()
                        ->name('TK')
                        ->noNumber();
                }
                // Extra
                $s->extra()
                    ->duration($this->http->FindSingleNode('td[normalize-space()][last()]', $root, null, "/^" . $this->opt($this->t('Journey Duration')) . "\s*(\d.+)\s*$/"));
            } elseif ($connections >= 2) { //it-828785091.eml
                $email->removeItinerary($f);
                $this->logger->debug('the service will not be able to build a route');
                $email->setIsJunk(true);
            } else {
                $codes = array_values(array_filter($this->http->FindNodes('td[normalize-space()][2]/descendant::text()[normalize-space()]', $root, "/^\s*([A-Z]{3})\s*$/")));
                $connectionCode = null;

                if (count($codes) == 1) {
                    $connectionCode = $codes[0];
                }

                $alSecond = $fnSecond = null;
                $connectInfo = implode(" ", $this->http->FindNodes('td[normalize-space()][2]//text()[normalize-space()]', $root));

                if (preg_match("/^\s*1 [[:alpha:] ]+\s+(?<aNameFirst>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumberFirst>\d{1,4})\s*(?<code>[A-Z]{3})\s*(?<aNameSecond>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumberSecond>\d{1,4})$/u", $connectInfo, $m)) {
                    $s->airline()
                        ->name($m['aNameFirst'])
                        ->number($m['fNumberFirst']);
                    $connectionCode = $m['code'];
                    $alSecond = $m['aNameSecond'];
                    $fnSecond = $m['fNumberSecond'];
                } elseif (empty($s->getFlightNumber())) {
                    $s->airline()
                        ->name('TK')
                        ->noNumber();
                }

                $s->arrival()
                    ->noDate();

                if (!empty($connectionCode)) {
                    $s->arrival()
                        ->code($connectionCode);
                } else {
                    $s->arrival()
                        ->noCode();
                }
                $cabin = $s->getCabin();
                $bookingCode = $s->getBookingCode();

                $s = $f->addSegment();

                // Airline
                $s->airline()
                    ->name($alSecond ?? 'TK');

                if (!empty($fnSecond)) {
                    $s->airline()
                        ->number($fnSecond);
                } else {
                    $s->airline()
                        ->noNumber();
                }

                $s->departure()
                    ->noDate();

                if (!empty($connectionCode)) {
                    $s->departure()
                        ->code($connectionCode);
                } else {
                    $s->departure()
                        ->noCode();
                }

                if (!empty($cabin)) {
                    $s->setCabin($cabin);
                }

                if (!empty($bookingCode)) {
                    $s->setBookingCode($bookingCode);
                }
            }
            // Arrival
            $s->arrival()
                ->code($this->http->FindSingleNode('td[normalize-space()][3]', $root, null, "/^\s*\d{1,2}:\d{2}\s*([A-Z]{3})\s*$/"));

            $time = $this->http->FindSingleNode('td[normalize-space()][3]', $root, null, "/^\s*(\d{1,2}:\d{2})\s*[A-Z]{3}\s*$/");

            if (!empty($date) && !empty($time)) {
                $arrDate = $this->normalizeDate($date . ', ' . $time);

                $nextDay = $this->http->FindSingleNode('td[normalize-space()][4]', $root, null, "/^\s*" . $this->opt($this->t('Next day')) . "\s*$/");

                if (!empty($arrDate) && !empty($nextDay)) {
                    $arrDate = strtotime("+1 day", $arrDate);
                }

                $s->arrival()
                    ->date($arrDate);
            }
        }

        foreach ($f->getSegments() as $s) {
            if (!empty($s->getDepCode()) && !empty($s->getArrCode())) {
                $seatsNodes = $this->http->XPath->query("//text()[starts-with(normalize-space(), '" . $s->getDepCode() . "') and contains(normalize-space(), '" . $s->getArrCode() . "')]/ancestor::tr[1]");

                foreach ($seatsNodes as $sRoot) {
                    $seat = $this->http->FindSingleNode(".", $sRoot, true, "/^\s*{$s->getDepCode()} - {$s->getArrCode()}\s*:\s*(\d{1,3}[A-Z])\s*$/");

                    if (!empty($seat)) {
                        $pax = $this->http->FindSingleNode("ancestor::td[1][descendant::text()[normalize-space()][1][contains(., ':')]]/preceding-sibling::td[normalize-space()][last()]/descendant::text()[normalize-space()][1]", $sRoot, true, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/");

                        if (empty($pax)) {
                            $pax = $this->http->FindSingleNode("ancestor::tr[2][descendant::text()[normalize-space()][1][contains(., ' - ') and not(contains(., ':'))]]/preceding::tr[normalize-space()][1][not(contains(., ' - ')) and not(contains(., ':'))]", $sRoot, true, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/");
                        }

                        $s->extra()
                            ->seat($seat, true, true, $pax);
                    }
                }
            }
        }
    }

    private function t($str)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$str])) {
            return $str;
        }

        return self::$dictionary[$this->lang][$str];
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

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'contains(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'normalize-space(' . $node . ')="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
    }

    private function amount($s)
    {
        $s = trim(str_replace(",", ".", preg_replace("#[., ](\d{3})#", "$1", $this->re("#(\d[\d\,\. ]*)#", $s))));

        if (is_numeric($s)) {
            return (float) $s;
        }

        return null;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function normalizeDate($str)
    {
        // $this->logger->debug('$str = '.print_r( $str,true));
        $in = [
            //Donnerstag, 15. September 2022, 11:25
            "#^\s*[[:alpha:]\-]+\,\s*(\d+)\.\s*([[:alpha:]]+)\s*(\d{4}),\s*(\d{1,2}:\d{2})\s*$#u",
            // 18 Ekim 2022 Salı, 19:00
            "#^\s*(\d+)\s*([[:alpha:]]+)\s*(\d{4})\s*[[:alpha:]\-]+[.]?\s*,\s*(\d{1,2}:\d{2})\s*$#u",
        ];
        $out = [
            "$1 $2 $3, $4",
            "$1 $2 $3, $4",
        ];

        $str = preg_replace($in, $out, $str);
        // $this->logger->debug('$str = '.print_r( $str,true));

        if (preg_match("#\d{1,2} ([[:alpha:]]+) \d{4}#u", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }
        // $this->logger->debug('$str = '.print_r( $str,true));

        return strtotime($str);
    }
}
