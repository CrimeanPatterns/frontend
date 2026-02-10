<?php

namespace AwardWallet\Engine\unidas\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class RentalReservation extends \TAccountChecker
{
    public $mailFiles = "unidas/it-894371039.eml, unidas/it-896689286.eml, unidas/it-900057706.eml, unidas/it-900987664.eml";

    public $lang = 'pt';

    public $detectSubjects = [
        'pt' => [
            'É amanhã! Confira os detalhes da sua reserva',
            'veja os detalhes da sua reserva!',
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
        if (empty($headers['from']) || stripos($headers['from'], 'unidas.com.br') === false) {
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
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Confira os dados da sua reserva:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Nº da Reserva:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Grupo do Veículo:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Funcionamento:'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseRental(Email $email)
    {
        $r = $email->add()->rental();

        // collect reservation confirmation
        $confirmationText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Nº da Reserva:'))}]/ancestor::td[normalize-space()][1]");

        if (preg_match("/^\s*(?<desc>{$this->opt($this->t('Nº da Reserva:'))})\s*(?<number>\d+)\s*$/u", $confirmationText, $m)) {
            $r->general()
                ->confirmation($m['number'], trim($m['desc'], ':'));
        }

        // collect traveller
        $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Olá,'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*\!\s*$/");

        if (!empty($traveller)) {
            $r->general()->traveller($traveller);
        }

        // collect car type
        $carInfo = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Grupo do Veículo:'))}]/ancestor::td[normalize-space()][1]", null, true, "/^\s*{$this->opt($this->t('Grupo do Veículo:'))}\s*(.+?)\s*$/");

        if (preg_match("/^\s*(?<type>.+?)\s*\-\s*(?<model>.+?)\s*$/mu", $carInfo, $m)) {
            $r->car()
                ->type($m['type'])
                ->model($m['model']);
        } elseif (!empty($carType)) {
            $r->setCarType($carType);
        }

        // collect pickUp and dropOff dateTimes
        $pickUpDateTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Data e horário de retirada:'))}]/following::text()[normalize-space()][1]");
        $r->pickup()->date($this->normalizeDate($pickUpDateTime));

        $dropOffDateTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Data e horário de devolução:'))}]/following::text()[normalize-space()][1]");
        $r->dropoff()->date($this->normalizeDate($dropOffDateTime));

        // collect pickUp and dropOff locations
        $pickUpLocationAddressParts = $this->http->FindNodes("//text()[{$this->eq($this->t('Loja de retirada:'))}]/ancestor::td[1]/descendant::text()[normalize-space()][position() > 1]");
        $dropOffLocationAddressParts = $this->http->FindNodes("//text()[{$this->eq($this->t('Loja de devolução:'))}]/ancestor::td[1]/descendant::text()[normalize-space()][position() > 1]");

        $r->pickup()->location(implode(', ', $pickUpLocationAddressParts));
        $r->dropoff()->location(implode(', ', $dropOffLocationAddressParts));

        // collect pickUp and dropOff opening hours
        $pickUpOpeningHoursText = implode("\n", $this->http->FindNodes("(//*[{$this->eq($this->t('Horário de Funcionamento:'))}])[1]/ancestor::tr[1]/descendant::text()[normalize-space()][not({$this->contains(['Horário', 'Funcionamento'])})]"));
        $pickUpOpeningHoursText = preg_replace("/\s*\:\s+/", ': ', $pickUpOpeningHoursText);

        if (!empty($pickUpOpeningHoursText)) {
            $r->setPickUpOpeningHours(explode("\n", $pickUpOpeningHoursText));
        }

        $dropOffOpeningHoursText = implode("\n", $this->http->FindNodes("(//*[{$this->eq($this->t('Horário de Funcionamento:'))}])[last()]/ancestor::tr[1]/descendant::text()[normalize-space()][not({$this->contains(['Horário', 'Funcionamento'])})]"));
        $dropOffOpeningHoursText = preg_replace("/\s*\:\s+/", ': ', $dropOffOpeningHoursText);

        if (!empty($dropOffOpeningHoursText)) {
            $r->setDropOffOpeningHours(explode("\n", $dropOffOpeningHoursText));
        }

        // collect provider phones
        $phoneText = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Central de Reservas'))}]/ancestor::tr[1][normalize-space()]");

        if (preg_match("/(?<desc>{$this->opt($this->t('Central de Reservas'))})\s*(?<phone>[\+\-\(\)\d ]+)\s*\./u", $phoneText, $m)) {
            $r->program()
                ->phone($m['phone'], $m['desc']);
        }

        $phoneText2 = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Serviço de Atendimento ao Cliente:'))}]/ancestor::tr[1][normalize-space()]");

        if (preg_match("/(?<desc>{$this->opt($this->t('Serviço de Atendimento ao Cliente:'))})\s*(?<phone>[\+\-\(\)\d ]+)\s+/u", $phoneText2, $m)) {
            $r->program()
                ->phone($m['phone'], trim($m['desc'], ':'));
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
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
}
