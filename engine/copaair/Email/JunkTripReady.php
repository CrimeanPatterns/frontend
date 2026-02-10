<?php

namespace AwardWallet\Engine\copaair\Email;

use AwardWallet\Schema\Parser\Email\Email;

class JunkTripReady extends \TAccountChecker
{
    public $mailFiles = "copaair/it-914013065.eml, copaair/it-914184408.eml, copaair/it-914251683.eml";

    public $lang = '';

    public $detectSubject = [
        // en
        'Get ready for your trip',
        // es
        'Prepárate para tu viaje',
        // pt
        'Prepare-se para sua viagem',
    ];

    public static $dictionary = [
        'en' => [
            // assign language
            'Get ready for your trip' => 'Get ready for your trip',

            // detects
            // not in email
            'Flight details' => [
                'Flight details', 'New Itinerary', 'New itinerary', 'Canceled Itinerary', 'Details of their Itinerary',
                'Trip details', 'Air Itinerary Details', 'Itinerary details',
            ],
        ],
        'es' => [
            // assign language
            'Get ready for your trip' => 'Prepárate para tu viaje',

            // detects
            // in email
            'Before going to the airport' => 'Antes de ir al aeropuerto',
            'See travel requirements'     => 'Ver requisitos de viaje',
            'The day of your trip'        => 'El día de tu viaje',
            // not in email
            'Flight details' => [
                'Detalles del vuelo', 'Detalles del viaje', 'Nuevo Itinerario', 'Mi itinerario',
                'Tu itinerario actualizado', 'Detalles del itinerario', 'Detalles de los vuelos',
                'Detalles de su Itinerario Aéreo',
            ],

            // junk check
            '1. Check-in online 24 hours before your flight' => '1. Realiza Check-In en línea desde 24 horas antes del vuelo',
            '2. Arrive on time at the airport'               => '2. Llega a tiempo al aeropuerto',
            '3. Information for Domestic Flights'            => '3. Información para Vuelos Nacionales',
            '4. Download our mobile app'                     => '4. Descarga nuestra aplicación móvil',
            'Onboard our aircraft'                           => 'Abordo de nuestra aeronave',
            '5. Onboard service and entertainment'           => '5. Servicio a bordo y entretenimiento',
            'You may also be interested in'                  => 'También te puede interesar',
        ],
        'pt' => [
            // assign language
            'Get ready for your trip' => 'Prepare-se para sua viagem',

            // detects
            // in email
            'Before going to the airport' => 'Antes de ir para o aeroporto',
            'See travel requirements'     => 'Consulte os requisitos de viagem',
            'The day of your trip'        => 'No dia de sua viagem',
            // not in email
            'Flight details' => [
                'Detalhes do voo', 'Detalhes do vôo', 'Detalhes da viagem', 'Novo Itinerário', 'Itinerário do voo',
                'Detalhes do itinerário',
            ],

            // junk check
            '1. Check-in online 24 hours before your flight' => '1. Faça o check-in on-line 24 horas antes de seu voo',
            '2. Arrive on time at the airport'               => '2. Chegue no horário no aeroporto',
            '3. Information for Domestic Flights'            => '3. Informações para voos domésticos',
            '4. Download our mobile app'                     => '4. Baixe nosso aplicativo para celular',
            'Onboard our aircraft'                           => 'A bordo de nossas aeronaves',
            '5. Onboard service and entertainment'           => '5. Serviço e entretenimento de bordo',
            'You may also be interested in'                  => 'Você também pode estar interessado em',
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

        $this->assignLang();

        // detect Format
        if ($this->http->XPath->query("//a[{$this->eq($this->t('See travel requirements'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Before going to the airport'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('The day of your trip'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Flight details'))}]")->length === 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }

        if ($this->detectEmailByBody($parser)
            && $this->http->XPath->query("//text()[{$this->eq($this->t('1. Check-in online 24 hours before your flight'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('2. Arrive on time at the airport'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('3. Information for Domestic Flights'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('4. Download our mobile app'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('5. Onboard service and entertainment'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Onboard our aircraft'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('You may also be interested in'))}]")->length > 0
        ) {
            $email->setIsJunk(true, "Not reservation, letter about ready for trip!");
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

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['Get ready for your trip'])
                && $this->http->XPath->query("//text()[{$this->eq($dict['Get ready for your trip'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
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
