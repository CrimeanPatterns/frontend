<?php

namespace AwardWallet\Engine\airfrance\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class FlyingBlue extends \TAccountChecker
{
    public $mailFiles = "airfrance/it-56355544.eml, airfrance/it-56398674.eml, airfrance/it-920368586.eml, airfrance/it-924741607.eml";
    public $reFrom = ["@airfrance.com", '@service-flyingblue.com'];
    public $reSubject = [
        "en"=> "Flying Blue booking confirmation email",
        "Flying Blue booking confirmation",
        // fr
        "Confirmation de réservation Flying Blue",
        // nl
        "Bevestiging Flying Blue-boeking",
        // de
        "Flying Blue-Buchungsbestätigung",
        //pt
        "Confirmação de reserva Flying Blue",
        //es
        "Confirmación de reserva Flying Blue",
        //zh
        '蓝天飞行预定确认',
    ];
    public $reBody = 'airfrance';
    public $reBody2 = [
        "en" => ["Flying Blue booking confirmation"],
        "fr" => ["Confirmation de réservation Flying Blue"],
        "nl" => ["Bevestiging Flying Blue-boeking"],
        "de" => ["Flying Blue-Buchungsbestätigung"],
        "pt" => ["Confirmação de reserva Flying Blue"],
        "es" => ["Confirmación de reserva Flying Blue"],
        "zh" => ["蓝天飞行预订确认", "藍天飛行預訂確認", '航班資訊'],
    ];

    private static $dictionary = [
        'en' => [
            // 'Flying Blue booking confirmation' => '',
            // 'Your reservation reference number' => '',
            // 'Operated by' => '',
            // 'Aircraft type:' => '',
            // 'Passenger details' => '',
            // 'Blue number' => '',
            // 'Payment' => '',
            // 'Taxes and surcharges' => '',
            // 'Total amount paid in Miles' => '',
            // 'Total amount paid online' => '',
        ],
        'fr' => [
            'Flying Blue booking confirmation'  => 'Confirmation de réservation Flying Blue',
            'Your reservation reference number' => 'Votre numéro de référence de réservation',
            'Operated by'                       => 'Opéré par',
            'Aircraft type:'                    => 'Type d’appareil :',
            'Passenger details'                 => 'Détails passager',
            'Blue number'                       => 'Numéro Flying Blue',
            'Payment'                           => 'Paiement',
            'Taxes and surcharges'              => 'Taxes et surcharges',
            'Total amount paid in Miles'        => 'Montant total payé en Miles',
            'Total amount paid online'          => 'Montant total payé en ligne',
            //'Travel class:' => '',
        ],
        'nl' => [
            'Flying Blue booking confirmation'  => 'Bevestiging Flying Blue-boeking',
            'Your reservation reference number' => 'De referentiecode van uw boeking is',
            'Operated by'                       => 'Uitgevoerd door',
            'Aircraft type:'                    => 'Type toestel:',
            'Passenger details'                 => 'Passagiersgegevens',
            'Blue number'                       => 'Flying Blue-nummer',
            'Payment'                           => 'Betaling',
            'Taxes and surcharges'              => 'Belasting en toeslagen',
            'Total amount paid in Miles'        => 'Totaalbedrag betaald met Miles',
            'Total amount paid online'          => 'Totaalbedrag betaald met online betaling',
            'Travel class:'                     => 'Reisklasse:',
        ],
        'de' => [
            'Flying Blue booking confirmation'  => 'Flying Blue-Buchungsbestätigung',
            'Your reservation reference number' => 'Ihr Buchungscode:',
            'Operated by'                       => 'durchgeführt von',
            'Aircraft type:'                    => 'Flugzeugtyp:',
            'Passenger details'                 => 'Passagierdaten',
            'Blue number'                       => 'Flying Blue-Nummer',
            'Payment'                           => 'Zahlung',
            'Taxes and surcharges'              => 'Steuern, Gebühren und Zuschläge',
            'Total amount paid in Miles'        => 'Mit Meilen beglichener Gesamtbetrag',
            'Total amount paid online'          => 'Online bezahlter Gesamtbetrag',
            //'Travel class:' => '',
        ],
        'pt' => [
            'Flying Blue booking confirmation'  => 'Confirmação de reserva Flying Blue',
            'Your reservation reference number' => 'Número de referência da sua reserva:',
            'Operated by'                       => 'Operado por',
            'Aircraft type:'                    => 'Tipo da aeronave:',
            'Passenger details'                 => 'Dados do passageiro',
            //'Blue number'                       => '',
            'Payment'                           => 'Pagamento',
            'Taxes and surcharges'              => 'Taxas e sobretaxas',
            'Total amount paid in Miles'        => 'Total pago em Milhas',
            'Total amount paid online'          => 'Total pago online',
            'Travel class:'                     => 'Classe de viagem:',
        ],
        'es' => [
            'Flying Blue booking confirmation'  => 'Confirmación de reserva Flying Blue',
            'Your reservation reference number' => 'Número de referencia de su reserva:',
            'Operated by'                       => 'Operado por',
            'Aircraft type:'                    => 'Tipo de avión:',
            'Passenger details'                 => 'Datos del pasajero',
            //'Blue number'                       => '',
            'Payment'                           => 'Pago',
            'Taxes and surcharges'              => 'Impuestos y recargos',
            'Total amount paid in Miles'        => 'Importe total pagado con Millas',
            'Total amount paid online'          => 'Importe total pagado online',
            'Travel class:'                     => 'Clase de viaje:',
        ],
        'zh' => [
            'Flying Blue booking confirmation'  => ['蓝天飞行预订确认', '藍天飛行預訂確認'],
            'Your reservation reference number' => ['您的预订参考编号：', '您的預訂參考編號：'],
            'Operated by'                       => ['运营航空公司：', '承運航空公司：'],
            'Aircraft type:'                    => ['飞机型号：', '航班類型：'],
            'Passenger details'                 => ['乘客详情', '乘客資訊'],
            //'Blue number'                       => '',
            'Payment'                           => ['支付', '付款'],
            'Taxes and surcharges'              => ['税费和附加费', '稅款和附加費'],
            'Total amount paid in Miles'        => ['以里数支付的总金额', '以里數付款總額'],
            'Total amount paid online'          => ['在线支付的总金额', '線上付款總額'],
            'Travel class:'                     => ['预订舱位：', '艙等：'],
        ],
    ];
    private $lang = '';
    private $date;

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        foreach ($this->reBody2 as $lang => $re) {
            if ($this->http->XPath->query('//*[' . $this->contains($re) . ']')->length > 0) {
                $this->lang = $lang;

                break;
            }
        }

        $this->date = strtotime($parser->getHeader('date'));

        $flight = $email->add()->flight();

        $flight->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->starts($this->t('Your reservation reference number'))}]", null, true, '/\:?\s*([A-Z\d]{5,7})\s*$/'));

        $travellersXpath = "//text()[{$this->eq($this->t('Passenger details'))}]/following::text()[normalize-space(.)][1]/ancestor::*[not({$this->contains($this->t('Passenger details'))})][last()]//text()[contains(., '•')]/ancestor::tr[1]";
        $flight->general()
            ->travellers($this->http->FindNodes($travellersXpath, null, "/^[\s\W]*(\D+)\s*(?:[-].*{$this->preg_implode($this->t('Blue number'))}|$)/"), true);

        // Program
        $accounts = array_unique(array_filter($this->http->FindNodes($travellersXpath, null, "/{$this->preg_implode($this->t('Blue number'))}\s*:?\s*(\d{10,15})$/")));

        if ($accounts) {
            $flight->program()
                ->accounts($accounts, false);
        } else {
            $account = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Flying Blue booking confirmation'))}]/preceding::text()[normalize-space()][1]",
                null, true, "/^\s*([\dX]{9,15})\s*$/");

            if ($account) {
                $pax = $this->http->FindSingleNode("//text()[{$this->eq($account)}]/preceding::text()[normalize-space()][1]", null, true, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/");

                if ($pax) {
                    $flight->program()
                        ->account($account, false, ucwords(strtolower($pax)));
                } else {
                    $flight->program()
                        ->account($account, false);
                }
            }
        }

        // Price
        $flight->price()
            ->total($this->http->FindSingleNode("//text()[{$this->starts($this->t('Payment'))}]/following::text()[{$this->starts($this->t('Total amount paid online'))}]/following::text()[normalize-space()][1]", null, true, '/([\d\.]+)\s*[A-Z]{3}/'))
            ->tax($this->http->FindSingleNode("//text()[{$this->starts($this->t('Payment'))}]/following::text()[{$this->starts($this->t('Taxes and surcharges'))}]/following::text()[normalize-space()][1]", null, true, '/([\d\.]+)\s*[A-Z]{3}/'))
            ->currency($this->http->FindSingleNode("//text()[{$this->starts($this->t('Payment'))}]/following::text()[{$this->starts($this->t('Total amount paid online'))}]/following::text()[normalize-space()][1]", null, true, '/[\d\.]+\s*([A-Z]{3})/'));

        $spentAwards = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Payment'))}]/following::text()[{$this->starts($this->t('Total amount paid in Miles'))}]/following::text()[normalize-space()][1]", null, true, '/(\d+)/');

        if (!empty($spentAwards)) {
            $flight->price()
                ->spentAwards($spentAwards);
        }

        $xpath = "//text()[{$this->contains($this->t('Operated by'))}]";
        $roots = $this->http->XPath->query($xpath);

        foreach ($roots as $root) {
            $segment = $flight->addSegment();

            $dateText = $this->http->FindSingleNode("./ancestor::tr[1]/descendant::text()[contains(normalize-space(), '-')][last()]", $root);
            $depDate = null;
            $arrDate = null;

            if (preg_match("/^(?<date>.+?)[:]\s*(?<dTime>[\dh:]+)\s*-\s*(?<aTime>[\dh:]+)\s*(?:\(\s*D(?<overnight>[-+]\d)\))?/", $dateText, $m)) {
                $depDate = $this->normalizeDate($m['date'] . ', ' . str_replace('h', ':', $m['dTime']));
                $arrDate = $this->normalizeDate($m['date'] . ', ' . str_replace('h', ':', $m['aTime']));

                if (!empty($m['overnight'])) {
                    $arrDate = strtotime($m['overnight'] . ' days', $arrDate);
                }
            }

            $segment->departure()
                ->date($depDate)
                ->name($this->http->FindSingleNode("./ancestor::table[1]/preceding::table[contains(normalize-space(), ', (')][2]", $root))
                ->noCode();

            $segment->arrival()
                ->date($arrDate)
                ->name($this->http->FindSingleNode("./ancestor::table[1]/preceding::table[contains(normalize-space(), ', (')][1]", $root))
                ->noCode();

            $operator = $this->http->FindSingleNode("./.", $root, true, "/{$this->preg_implode($this->t('Operated by'))} ?[:]?\s+(\D+)\s+[-]/");

            if (empty($operator)) {
                $operator = $this->http->FindSingleNode("./.", $root, true, "/{$this->preg_implode($this->t('Operated by'))} ?[:]?\s+(\D+)$/");
            }

            $segment->airline()
                ->name($this->http->FindSingleNode("./.", $root, true, "/^([A-Z\d]{2})\d+/"))
                ->number($this->http->FindSingleNode("./.", $root, true, "/^[A-Z\d]{2}(\d{2,4})/"))
                ->operator($operator);

            $aircraft = $this->http->FindSingleNode("./.", $root, true, "/{$this->preg_implode($this->t('Aircraft type:'))}\s+(.+)$/");

            if (!empty($aircraft)) {
                $segment->extra()
                    ->aircraft($aircraft);
            }

            $cabin = $this->http->FindSingleNode("./ancestor::tr[1]/descendant::text()[{$this->starts($this->t('Travel class:'))}]", $root, true, "/{$this->opt($this->t('Travel class:'))}\s*(.+)/");

            if (!empty($cabin)) {
                $segment->setCabin($cabin);
            }
        }
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        foreach ($this->reFrom as $reFrom) {
            if (stripos($from, $reFrom) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers["from"]) === false) {
            return false;
        }

        foreach ($this->reSubject as $re) {
            if (stripos($headers["subject"], $re) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $body = $parser->getHTMLBody();

        if (stripos($body, $this->reBody) === false) {
            return false;
        }

        foreach ($this->reBody2 as $lang => $re) {
            if ($this->http->XPath->query('//*[' . $this->contains($re) . ']')->length > 0) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function normalizeDate($str)
    {
        $this->logger->debug($str);
        $year = date("Y", $this->date);
        $in = [
            // Thursday, March 19, 20:20
            '/^([-[:alpha:]]{2,}),\s+([[:alpha:]]{3,})\s+(\d{1,2})\s*,\s*(\d+:\d+)\s*$/u',
            // Saturday 27 November 2021, 06:00
            // Dienstag 31. Oktober 2023, 18:40
            '/^[-[:alpha:]]{2,}\s+(\d{1,2})\.?\s+([[:alpha:]]{3,})\s+(\d{4})\s*,\s*(\d+:\d+)\s*$/u',
            //  Jeudi 30 septembre, 11:40
            '/^\s*([-[:alpha:]]{2,})\,?\s+(\d{1,2})\.?\s+(?:de\s*)?([[:alpha:]]{3,})\s*,\s*(\d+:\d+)\s*$/u',
            //年6月19日星期四, 16:30
            '/^年(\d+)月(\d+)日(\D+)\,\s+(\d+\:\d+)$/',
            //2025年6月7日星期六, 07:00
            '/^(\d{4})年(\d+)月(\d+)日\D+\s+(\d+\:\d+)$/',
        ];
        $out = [
            '$1, $3 $2 ' . $year . ', $4',
            '$1 $2 $3, $4',
            '$1, $2 $3 ' . $year . ', $4',
            '$3 $2.$1.' . $year . ', $4',
            '$3.$2.$1, $4',
        ];
        $str = preg_replace($in, $out, $str);

        $langReserv = '';

        if ($this->lang == 'en' && preg_match("/^\w+\-\w+/", $str)) {
            $langReserv = 'pt';
        }

        if (preg_match("#\d+\s+([^\d\s]+)(?:\s+\d{4}|\s*\,|$)#", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }

            if (!empty($langReserv)) {
                $en = MonthTranslate::translate($m[1], $langReserv);
                $str = str_replace($m[1], $en, $str);
            }
        }

        if (preg_match("#^(?<week>[-[:alpha:]]{2,})\,? (?<date>(?:\d+ \w+ .+|\d+\.\d+.+))#u", $str, $m)) {
            if (!empty($langReserv)) {
                $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $langReserv));
                $str = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
            } else {
                $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));
                $str = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
            }
        } else {
            $str = strtotime($str);
        }

        return $str;
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field));
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(" or ", array_map(function ($s) { return "starts-with(normalize-space(.), \"{$s}\")"; }, $field));
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false';
        }

        return implode(" or ", array_map(function ($s) { return "contains(normalize-space(.), \"{$s}\")"; }, $field));
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function preg_implode($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false';
        }

        return '(?:' . implode("|", array_map(function ($v) {return preg_quote($v, '/'); }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) { return preg_quote($s, '/'); }, $field)) . ')';
    }
}
