<?php

namespace AwardWallet\Engine\vivaaerobus\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FlightInformation extends \TAccountChecker
{
    public $mailFiles = "vivaaerobus/it-897373406.eml";
    public $subjects = [
        'Información importante sobre el vuelo',
    ];

    public $lang = 'es';

    public static $dictionary = [
        "es" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@vivaaerobus.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Enviado por Viva'))}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Tu vuelo con clave de reservación'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Vuelo'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Salida'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Equipo de Experiencia al Pasajero'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]vivaaerobus.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->ParseFlight($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseFlight(Email $email)
    {
        $f = $email->add()->flight();

        $confirmation = $this->http->FindSingleNode("//text()[normalize-space()='Código de reserva']/following::text()[normalize-space()][1]", null, true, "/^([A-Z\d]{6})$/");

        if (empty($confirmation)) {
            $confirmation = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'clave de reservaci�n')]/following::text()[normalize-space()][1]", null, true, "/^([A-Z\d]{6})$/");
        }
        $f->general()
            ->confirmation($confirmation)
            ->traveller($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Hola')]", null, true, "/{$this->opt($this->t('Hola'))}\s*(\D+)\,/"));

        $nodes = $this->http->XPath->query("//text()[starts-with(normalize-space(), 'Vuelo')]/ancestor::tr[2]/following-sibling::tr/descendant::td[1][not(contains(@style, 'solid'))]/descendant::text()[contains(normalize-space(), ':')][1]/ancestor::tr[2]");

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            $infoFlight = $this->http->FindSingleNode("./descendant::td[1]", $root);
            $date = '';

            if (preg_match("/^(?<date>\d+\s+de\s+\w+\s+del\s+\d{4})\s*(?<aName>[A-Z\d]{2})\s+(?<fNumber>\d{4})$/", $infoFlight, $m)) {
                $s->airline()
                    ->name($m['aName'])
                    ->number($m['fNumber']);

                $date = $this->normalizeDate($m['date']);
            }

            $depInfo = implode("\n", $this->http->FindNodes("./descendant::td[5]/descendant::text()[normalize-space()]", $root));

            if (preg_match("/Salida\n(?<depTime>[\d\:]+\s*A?P?M?)\s*hrs\n(?<depName>.+)/", $depInfo, $m)) {
                $s->departure()
                    ->name($m['depName'])
                    ->noCode()
                    ->date(strtotime($date . ', ' . $m['depTime']));
            }

            $arrInfo = implode("\n", $this->http->FindNodes("./descendant::td[last()]/ancestor::td[1]/descendant::text()[normalize-space()]", $root));

            if (preg_match("/^Llegada\n(?<arrTime>[\d\:]+\s*A?P?M?)\s*hrs\n(?<arrName>.+)\n(?<arrCode>[A-Z]{3})$/", $arrInfo, $m)) {
                $s->arrival()
                    ->name($m['arrName'])
                    ->code($m['arrCode'])
                    ->date(strtotime($date . ', ' . $m['arrTime']));
            }
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

    private function normalizeDate($date)
    {
        $in = [
            // 01 de abril del 2025
            "/^(\d+)\s+de\s+(\w+)\s+del\s+(\d{4})$/u",
        ];

        $out = [
            "$1 $2 $3",
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        return $date;
    }
}
