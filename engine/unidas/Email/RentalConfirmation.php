<?php

namespace AwardWallet\Engine\unidas\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class RentalConfirmation extends \TAccountChecker
{
    public $mailFiles = "unidas/it-892056066.eml, unidas/it-894047748.eml, unidas/it-894384430.eml, unidas/it-896091014.eml, unidas/it-896595103.eml, unidas/it-896616039.eml";

    public $lang = 'pt';

    public $detectSubjects = [
        'pt' => [
            'Sua reserva com a Unidas está confirmada. nº',
            'Confirmação de Reserva',
        ],
    ];

    public static $dictionary = [
        'pt' => [
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]unidas\.com\.br$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from']) || stripos($headers['from'], 'unidas.com.br') === false)
            && stripos($headers['subject'], 'Unidas') === false
        ) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Unidas'))}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains('unidas.com.br')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('unidas.com.br')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('obrigada por escolher'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Sua reserva nº'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('E-mail:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Valor das diárias:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('RETIRADA'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseRental(Email $email)
    {
        $r = $email->add()->rental();

        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.
        ];

        // collect reservation confirmation
        $confirmationText = $this->http->FindSingleNode("//text()[{$this->starts('Sua reserva nº')}]/ancestor::td[normalize-space()][1]");

        if (preg_match("/^\s*(?<desc>{$this->opt($this->t('Sua reserva nº'))})\s*(?<number>\d+)\s*{$this->opt($this->t('foi'))}\s*(?<status>\w+)\s/", $confirmationText, $m)) {
            $r->general()
                ->confirmation($m['number'], $m['desc'])
                ->status($m['status']);
        }

        // collect traveller
        $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Telefone:'))}]/preceding::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/u");

        if (!empty($traveller)) {
            $r->general()->traveller($traveller);
        }

        // collect car type (sometimes line with car type is split into two parts)
        $carType = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Grupo do veículo:'))}]/ancestor::tr[1]", null, true, "/^\s*{$this->opt($this->t('Grupo do veículo:'))}\s*(.+?)\s*$/")
            . ' ' . $this->http->FindSingleNode("//text()[{$this->eq($this->t('Grupo do veículo:'))}]/ancestor::tr[1]/following-sibling::tr[normalize-space()][1]");

        if (!empty($carType)) {
            $r->setCarType(trim($carType));
        }

        // collect pickUp and dropOff dateTimes
        $pickUpDateTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Data e horário de retirada:'))}]/following::text()[normalize-space()][1]");
        $r->pickup()->date($this->normalizeDate($pickUpDateTime));

        $dropOffDateTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Data e horário de devolução:'))}]/following::text()[normalize-space()][1]");
        $r->dropoff()->date($this->normalizeDate($dropOffDateTime));

        // collect pickUp and dropOff locations
        $pickUpLocation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Loja de retirada:'))}]/following::text()[normalize-space()][1]")
            . ', ' . $this->http->FindSingleNode("//text()[{$this->eq($this->t('Loja de retirada:'))}]/following::text()[{$this->eq($this->t('Endereço:'))}][1]/following::text()[normalize-space()][1][not({$this->eq($this->t('Ponto de referência:'))})]");

        $dropOffLocation = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Loja de devolução:'))}]/following::text()[normalize-space()][1]")
            . ', ' . $this->http->FindSingleNode("//text()[{$this->eq($this->t('Loja de devolução:'))}]/following::text()[{$this->eq($this->t('Endereço:'))}][1]/following::text()[normalize-space()][1][not({$this->eq($this->t('Ponto de referência:'))})]");

        $r->pickup()->location(trim($pickUpLocation, ', '));
        $r->dropoff()->location(trim($dropOffLocation, ', '));

        // collect pickUp and dropOff opening hours
        $pickUpOpeningHoursText = implode(' ', $this->http->FindNodes("(//text()[{$this->eq($this->t('Horário de funcionamento da loja:'))}])[1]/following::text()[normalize-space()][following::text()[{$this->eq($this->t('DEVOLUÇÃO'))}]]"));

        if (preg_match_all("/(.+?\:\s*de\s*{$patterns['time']}\s*às\s*{$patterns['time']}\.?)/u", $pickUpOpeningHoursText, $m, PREG_PATTERN_ORDER)) {
            $r->setPickUpOpeningHours($m[1]);
        }

        $dropOffOpeningHoursText = implode(' ', $this->http->FindNodes("(//text()[{$this->eq($this->t('Horário de funcionamento da loja:'))}])[last()]/following::text()[normalize-space()][following::text()[{$this->eq($this->t('Veja como chegar:'))}]]"));

        if (preg_match_all("/(.+?\:\s*de\s*{$patterns['time']}\s*às\s*{$patterns['time']}\.?)/u", $dropOffOpeningHoursText, $m, PREG_PATTERN_ORDER)) {
            $r->setDropOffOpeningHours($m[1]);
        }

        // collect pick-up notes and drop-off notes
        $notes = null;
        $pickUpNotes = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Ponto de referência:'))}])[1]/following::text()[normalize-space()][1][not({$this->eq($this->t('Horário de funcionamento da loja:'))}) and not({$this->eq($this->t('Observação:'))})]");
        $dropOffNotes = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('Ponto de referência:'))}])[last()]/following::text()[normalize-space()][1][not({$this->eq($this->t('Horário de funcionamento da loja:'))}) and not({$this->eq($this->t('Observação:'))})]");

        if (!empty($pickUpNotes)) {
            $notes = "Loja de retirada:\nPonto de referência: " . $pickUpNotes . "\n";
        }

        if (!empty($dropOffNotes)) {
            $notes = $notes . "Loja de devolução:\nPonto de referência: " . $dropOffNotes;
        }

        if (!empty($notes)) {
            $r->setNotes(trim($notes));
        }

        // collect provider phones
        $phoneDesc = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Central de Reservas'))}]", null, true, "/^.+?({$this->opt($this->t('Central de Reservas'))})\s*$/u");
        $phone = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Central de Reservas'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([\+\-\(\)\d ]+)\s*$/");

        if (!empty($phone)) {
            $r->program()
                ->phone(preg_replace("/[\+\-\(\)\s]+/", '', $phone), trim($phoneDesc, ':'));
        }

        $phoneDesc2 = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Serviço de Atendimento ao Cliente:'))}]", null, true, "/^\s*({$this->opt($this->t('Serviço de Atendimento ao Cliente:'))})\s*$/u");
        $phone2 = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Serviço de Atendimento ao Cliente:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([\+\-\(\)\d ]+)\s*$/");

        if (!empty($phone2)) {
            $r->program()
                ->phone(preg_replace("/[\+\-\(\)\s]+/", '', $phone2), trim($phoneDesc2, ':'));
        }

        // collect pricing details
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Valor total:'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*$/u", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $r->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);
        }

        if (!empty($currency)) {
            $feeNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Valor total:'))}]/ancestor::tr[1]/preceding-sibling::tr[preceding-sibling::tr[{$this->starts($this->t('Valor das diárias:'))}]]");

            foreach ($feeNodes as $feeRoot) {
                $feeName = trim($this->http->FindSingleNode("./descendant::td[string-length()>1][1]", $feeRoot), ':');
                $feeAmount = $this->http->FindSingleNode("./descendant::td[string-length()>1][2]", $feeRoot, true, "/\D*([\d\.\,\']+)$/");

                $r->price()
                    ->fee($feeName, PriceHelper::parse($feeAmount, $currency));
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        // detect Junk
        $pickUpDateTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Data e horário de retirada:'))}]/following::text()[normalize-space()][1]");
        $dropOffDateTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Data e horário de devolução:'))}]/following::text()[normalize-space()][1]");

        if ($pickUpDateTime === $dropOffDateTime
            && ($this->http->XPath->query("//text()[{$this->contains($this->t('%%abertura-sem%%'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->contains($this->t('%%abertura-sab%%'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->starts($this->t('%%abertura-dom%%'))}]")->length > 0)
        ) {
            $email->setIsJunk(true, "Broken letter with broken opening hours and equal pickUpDate and dropOffDate");
            $class = explode('\\', __CLASS__);
            $email->setType(end($class) . 'Junk' . ucfirst($this->lang));

            return $email;
        }

        $this->parseRental($email);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function opt($field, $delimiter = '/')
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
            return str_replace(' ', '\s+', preg_quote($s, $delimiter));
        }, $field)) . ')';
    }

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $in = [
            "/^(\d+)\/(\d+)\/(\d{4})\s*às\s*(\d+\:\d+)$/", // 28/03/2025 às 23:45 => 28.03.2025, 23:45
        ];
        $out = [
            '$1.$2.$3, $4',
        ];

        return strtotime(preg_replace($in, $out, $date));
    }

    private function normalizeCurrency($s)
    {
        $sym = [
            '€'          => 'EUR',
            'US dollars' => 'USD',
            '£'          => 'GBP',
            '₹'          => 'INR',
            'CA$'        => 'CAD',
            'R$'         => 'BRL',
            '$'          => '$',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3}\D)(?:$|\s)#", $s)) {
            return $code;
        }

        $s = $this->re("#([^\d\,\.]+)#", $s);

        foreach ($sym as $f => $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return $s;
    }
}
