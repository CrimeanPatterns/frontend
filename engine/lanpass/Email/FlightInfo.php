<?php

namespace AwardWallet\Engine\lanpass\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FlightInfo extends \TAccountChecker
{
	public $mailFiles = "lanpass/it-925243847.eml, lanpass/it-926518948.eml, lanpass/it-926733959.eml, lanpass/it-926763412.eml";
    public $lang = '';

    public static $dictionary = [
        "es" => [
            'reservationNumber'        => ['Código de reserva:'],
            'orderNumber'              => ['Nº de orden:', 'N° de Orden:'],
            'yourFlight'               => ['Gestionar mi viaje'],
            'detectPhrase'               =>
                ['Te compartimos detalles sobre el equipaje permitido, sus dimensiones y la opción de agregar adicionales para tu viaje.',
                    'Te compartimos toda la información que debes saber para iniciar tu viaje. Esta información aplica para todos los pasajeros de la reserva.',
                    'A continuación, te entregamos detalles sobre la experiencia a bordo de nuestra cabina',
                    'A continuación, te mostramos el estado de Check-in para tu siguiente viaje.'],
        ],
    ];

    public $subjects = [
        // es
        'Es momento de hacer tu Check-in!',
        'Revisa los horarios de tu vuelo a',
        'Descubre la experiencia a bordo de tu vuelo a',
        'Este es tu equipaje para',
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '.latam.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (mb_stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $this->assignLang();

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('LATAM Airlines'))}]")->length > 0
            || $this->http->XPath->query("//img[contains(@src, '.latam.com')]")->length > 0) {
            return $this->http->XPath->query("//text()[{$this->contains($this->t('reservationNumber'))}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($this->t('orderNumber'))}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($this->t('yourFlight'))}]")->length > 0
                && $this->http->XPath->query("//*[{$this->contains($this->t('detectPhrase'))}]")->length > 0;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]latam\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        $this->ParseFlight($email);

        return $email;
    }

    public function ParseFlight(Email $email)
    {
        $f = $email->add()->flight();

        $otaConf = $this->http->FindSingleNode("//text()[{$this->eq($this->t('orderNumber'))}]/following::text()[normalize-space()][1]", null, false, "/^([A-z0-9\- ]{5,})$/u");
        $confNum = $this->http->FindSingleNode("//text()[{$this->eq($this->t('reservationNumber'))}]/following::text()[normalize-space()][1]", null, false, "/^([A-Z\d]{5,8})$/u");

        $f->ota()
            ->confirmation($otaConf);

        $f->general()
            ->confirmation($confNum);

        $passengers = array_unique($this->http->FindNodes("//text()[{$this->eq($this->t('Los siguientes pasajeros deben completar su documentación para obtener sus tarjetas de embarque:'))}]/following::ul[normalize-space()][1]/descendant::li[normalize-space()]", null, "/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])$/u"));

        if (!empty($passengers)){
            $f->general()->travellers($passengers, true);
        }

        $flightInfo = $this->http->FindSingleNode("(//tr[{$this->contains($this->t('detectPhrase'))}])[last()]/following::tr[normalize-space()][1]");

        $reg = "/^(?<depCity>.+)\b([ ]*\((?<depCode>[A-Z]{3})\))?[ ]+{$this->opt($this->t('a'))}[ ]*(?<arrCity>.+)\b([ ]*\((?<arrCode>[A-Z]{3})\))?[ ]+(?<depDate>\w+\,[ ]*\d{1,2}[ ]*(?:de)?[ ]*\w+[ ]*(?:de)?[ ]*\d{4})[ ]*{$this->opt($this->t('a las'))}[ ]*(?<depTime>\d{1,2}\:\d{2})[ ]*\(?(?<code>[A-Z\d][A-Z]|[A-Z][A-Z\d])[ ]*(?<number>\d{1,5})\)?$/u";

        if (preg_match($reg, $flightInfo, $m)){
            $s = $f->addSegment();

            if (isset($m['depCode']) && !empty($m['depCode'])){
                $s->departure()
                    ->code($depCode = $m['depCode']);

                $depTerminal = $this->http->FindSingleNode("(//text()[{$this->contains("({$depCode})")} and {$this->contains($this->t('Terminal'))}])[last()]", null, false, "/^.+\({$depCode}\)[ ]*\-[ ]*{$this->opt($this->t('Terminal'))}[ ]*(.+)$/u");

                if ($depTerminal !== null) {
                    $s->departure()
                        ->terminal($depTerminal);
                }
            } else {
                $s->departure()
                    ->noCode();
            }

            if (isset($m['arrCode']) && !empty($m['arrCode'])){
                $s->arrival()
                    ->code($m['arrCode']);
            } else {
                $s->arrival()
                    ->noCode();
            }

            $s->airline()
                ->name($m['code'])
                ->number($m['number']);

            $s->departure()
                ->name($m['depCity'])
                ->date($this->normalizeDate($m['depDate'] . ', ' . $m['depTime']));

            $s->arrival()
                ->name($m['arrCity'])
                ->noDate();


            $cabin = $this->http->FindSingleNode("(//tr[{$this->contains($this->t('A continuación, te entregamos detalles sobre la experiencia a bordo de nuestra cabina'))}])[last()]", null, false, "/^{$this->opt($this->t('A continuación, te entregamos detalles sobre la experiencia a bordo de nuestra cabina'))}[ ]*(.+)\.$/u");

            if ($cabin !== null) {
                $s->extra()
                    ->cabin($cabin);
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

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $words) {
            if (isset($words['reservationNumber'], $words['orderNumber'])) {
                if ($this->http->XPath->query("//*[{$this->contains($words['reservationNumber'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($words['orderNumber'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
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
                return str_replace(' ', '\s+', preg_quote($s));
            }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^(\w+)\,[ ]*(\d{1,2})[ ]*(?:de)?[ ]*(\w+)[ ]*(?:de)?[ ]*(\d{4})\,[ ]*(\d{1,2}\:\d{2}[ ]*\s*A?P?M?)$#su", // lunes, 30 de junio de 2025, 10:10
        ];
        $out = [
            "$1, $2 $3 $4, $5"
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        if (preg_match("/^(\w+)\,[ ]*(\d+\s+([^\d\s]+)\s+\d{4}\,[ ]*.+)$/u", $str, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m[1], $this->lang));
            $str = EmailDateHelper::parseDateUsingWeekDay($m[2], $weeknum);

            return $str;
        }

        return strtotime($str);
    }
}
