<?php

namespace AwardWallet\Engine\copaair\Email;

use AwardWallet\Schema\Parser\Email\Email;

class JunkEntertainment extends \TAccountChecker
{
    public $mailFiles = "copaair/it-917713531.eml";
    public $lang = 'es';

    public $date;

    public $detectSubject = [
        // es
        'Entretenimiento y servicio en tu viaje hacia',
    ];

    public static $dictionary = [
        'es' => [
            // not in email
            'Flight details' => [
                'Detalles del vuelo', 'Detalles del viaje', 'Nuevo Itinerario', 'Mi itinerario',
                'Tu itinerario actualizado', 'Detalles del itinerario', 'Detalles de los vuelos',
                'Detalles de su Itinerario Aéreo',
            ],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]cns\.copaair\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers["from"], '@cns.copaair.com') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//img/@src[{$this->contains('copaair.com')}]")->length === 0
            && $this->http->XPath->query("//img/@alt[{$this->contains('Copa Logo')}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains('Copa Airlines')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Tu experiencia durante el vuelo'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Alimentos y Bebidas'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Entretenimiento a bordo'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Flight details'))}]")->length === 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByBody($parser)
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Detalles sobre el servicio de alimentos y entretenimiento disponible durante tu vuelo.'))}]")->length > 0
            && ($this->http->XPath->query("//text()[{$this->eq($this->t('El entretenimiento a bordo podría variar debido a cambios operativos de último momento.'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->eq($this->t('El entretenimiento a bordo podría variar debido a cambios de aeronave de último momento.'))}]")->length > 0)
            && $this->http->XPath->query("//text()[{$this->eq($this->t('También te puede interesar'))}]")->length > 0
        ) {
            $email->setIsJunk(true, "Not reservation, letter about entertainment and service on trip");
        }

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

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
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
}
