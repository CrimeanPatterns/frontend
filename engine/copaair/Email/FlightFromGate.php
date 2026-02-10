<?php

namespace AwardWallet\Engine\copaair\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class FlightFromGate extends \TAccountChecker
{
    public $mailFiles = "copaair/it-898944430.eml, copaair/it-900402696.eml, copaair/it-902088616.eml, copaair/it-902099450.eml, copaair/it-904414660.eml, copaair/it-905814437.eml, copaair/it-911423828.eml, copaair/it-912594087.eml, copaair/it-915408738.eml, copaair/it-915451765.eml";

    public $lang = '';

    public $date;

    public $detectSubjects = [
        'en' => [
            'assigned to flight',
            'assigned to your flight to',
            'DELAYED FLIGHT:',
            'ADVANCED FLIGHT:',
            'Gate Change: For flight',
        ],
        'es' => [
            'asignada a vuelo',
            'asignada a tu vuelo hacia',
            'VUELO RETRASADO:',
            'VUELO ADELANTADO:',
        ],
        'pt' => [
            'atribuído para voo',
            'atribuído ao seu voo para',
        ],
    ];

    public static $dictionary = [
        'en' => [
            // assign language
            'Your flight' => 'Your flight',
            'Gate'        => 'Gate',

            'will depart from gate' => [
                'will depart from gate', 'has been delayed', 'will now depart from gate', 'will depart earlier',
            ],
            'Departure Time:'       => ['Departure Time:', 'New departure time:'],
        ],
        'es' => [
            // assign language
            'Your flight' => 'Tu vuelo',
            'Gate'        => 'Puerta',

            // detects
            'This information might change' => 'Esta información puede cambiar',
            'View reservation in My Trips'  => 'Ver reserva en Mis Viajes',
            'assigned to your flight to'    => 'asignada a tu vuelo hacia',
            'will depart from gate'         => ['saldrá desde la puerta', 'está demorado', 'se ha adelantado'],
            'Flight number:'                => 'Número de vuelo:',
            'Departure Time:'               => ['Fecha:', 'Nueva hora de salida:'],
            'Flight details'                => 'Detalles del vuelo',

            'Reservation code:' => 'Código de Reserva:',
        ],
        'pt' => [
            // assign language
            'Your flight' => 'Seu vôo',
            'Gate'        => 'Portão',

            // detects
            'This information might change' => 'Esta informação pode mudar',
            'View reservation in My Trips'  => 'Ver reserva em Minhas Viagens',
            'assigned to your flight to'    => 'ao seu voo para',
            'will depart from gate'         => 'partirá do portão',
            'Gate:'                         => 'Portão:',
            'Flight number:'                => 'Número de vôo:',
            'Departure Time:'               => 'Hora de saída:',
            'Flight details'                => 'Detalhes do vôo',

            'Reservation code:' => 'Código de Reserva:',
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
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains('Copa Airlines')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('copaair.com')}]")->length === 0
            && $this->http->XPath->query("//img/@alt[{$this->contains('Copa Logo')}]")->length === 0
        ) {
            return false;
        }

        $this->assignLang();

        // detect Format
        if (($this->http->XPath->query("//text()[{$this->starts($this->t('Your flight'))} and {$this->contains($this->t('will depart from gate'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->contains($this->t('assigned to your flight to'))}]")->length > 0)
            && ($this->http->XPath->query("//text()[{$this->eq($this->t('Flight details'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->eq($this->t('Departure Time:'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->starts($this->t('This information might change'))}]")->length > 0)
            && ($this->http->XPath->query("//text()[{$this->eq($this->t('Flight number:'))}]")->length > 0
                || $this->http->XPath->query("//a[{$this->eq($this->t('View reservation in My Trips'))}]")->length > 0)
        ) {
            return true;
        }

        return false;
    }

    public function parseFlight(Email $email)
    {
        $f = $email->add()->flight();
        $s = $f->addSegment();

        // collect reservation confirmation
        $confDesc = trim($this->http->FindSingleNode("//text()[{$this->eq($this->t('Reservation code:'))}]"), ':');
        $confNumber = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Reservation code:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{5,7})\s*$/");

        if (!empty($confNumber)) {
            $f->general()->confirmation($confNumber, $confDesc);
        }

        // collect airline, flight number, depCode and arrCode
        $airlineInfo = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your flight'))} and {$this->contains($this->t('will depart from gate'))}]");

        if (preg_match("/^\s*{$this->opt($this->t('Your flight'))}\s+(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{1,4}).*?(?<depCode>[A-Z]{3}).*?(?<arrCode>[A-Z]{3}).*?{$this->opt($this->t('will depart from gate'))}/", $airlineInfo, $m)) {
            $s->airline()
                ->name($m['aName'])
                ->number($m['fNumber']);

            $s->setDepCode($m['depCode']);
            $s->setArrCode($m['arrCode']);
        }

        // example: it-911423828.eml
        if (empty($s->getAirlineName())) {
            $airlineInfo = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Flight number:'))}]/following::text()[normalize-space()][1]");

            if (preg_match("/^\s*(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{1,4})\s*$/", $airlineInfo, $m)) {
                $s->airline()
                    ->name($m['aName'])
                    ->number($m['fNumber']);
            }
        }

        // example: it-914130015.eml
        if (empty($s->getArrCode())) {
            $airlineInfo = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your flight'))} and {$this->contains($this->t('will depart from gate'))}]");

            if (preg_match("/^\s*{$this->opt($this->t('Your flight'))}\s+(?<depCode>[A-Z]{3})[\s\-]+(?<arrCode>[A-Z]{3})\s+{$this->opt($this->t('will depart from gate'))}/", $airlineInfo, $m)) {
                $s->setDepCode($m['depCode']);
                $s->setArrCode($m['arrCode']);
            }
        }

        // example: it-915451765.eml
        if (empty($s->getArrCode())) {
            $arrInfo = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Gate'))} and {$this->contains($this->t('assigned to your flight to'))}]");

            if (preg_match("/{$this->opt($this->t('assigned to your flight to'))}\s+(?<arrName>.+?)\s+\((?<arrCode>[A-Z]{3})\)/", $arrInfo, $m)) {
                $s->arrival()
                    ->name($m['arrName'])
                    ->code($m['arrCode']);
            }
        }

        // example: it-915451765.eml
        // depCode is missing in some letters
        if (empty($s->getDepCode())) {
            $s->setNoDepCode(true);
        }

        // collect depDate and set noArrDate
        // depDate is missing in some letters, arrDate is missing in all letters
        $depDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t('Departure Time:'))}]/following::text()[normalize-space()][1]"));

        if (!empty($depDate)) {
            $s->setDepDate($depDate);
        } else {
            $s->setNoDepDate(true);
        }

        $s->setNoArrDate(true);

        // collect depTerminal
        $depTerminal = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Terminal:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\S+)\s*$/");

        if (!empty($depTerminal)) {
            $s->setDepTerminal(trim($depTerminal, '.'));
        }
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }
        $this->date = strtotime($parser->getHeader('date'));
        $this->parseFlight($email);

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
            if ((!empty($dict['Your flight']) && $this->http->XPath->query("//text()[{$this->starts($dict['Your flight'])}]")->length > 0)
                || (!empty($dict['Gate']) && $this->http->XPath->query("//text()[{$this->starts($dict['Gate'])}]")->length > 0)
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
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date) || empty($this->date)) {
            return null;
        }

        $year = date('Y', $this->date);
        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:\s*[AaPp](?:\.\s*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm  |  00:00
        ];

        $in = [
            "/^\s*(\w+)\,\s+(\w+)\s+(\d+)\s+(?:at|a las|às)\s+({$patterns['time']})\s*$/u", // Thu, Dec 14 at 9:30 p.m. => Thu, 14 Dec {$year}, 9:30 pm
            "/^\s*(\w+)\,\s+(\w+)\s+(\d+)\,\s+(\d{4})\s+({$patterns['time']})\s*$/u", // Sab, Nov 11, 2023 01:51 a.m.   => Sab, Nov 11, 2023 01:51 am
        ];
        $out = [
            "$1, $3 $2 {$year}, $4",
            "$1, $3 $2 $4, $5",
        ];

        $date = preg_replace($in, $out, $date);

        // $this->logger->debug('date replace = ' . print_r( $date, true));
        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        if (preg_match("/^(?<week>\w+), (?<date>\d+ \w+ .+)/u", $date, $m)) {
            $weeknum = WeekTranslate::number1($m['week']);
            $date = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/\b\d{4}\b/", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }
        // $this->logger->debug('date end = ' . print_r( $date, true));

        return $date;
    }
}
