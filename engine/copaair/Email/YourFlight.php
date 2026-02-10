<?php

namespace AwardWallet\Engine\copaair\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class YourFlight extends \TAccountChecker
{
    public $mailFiles = "copaair/it-904948705.eml, copaair/it-905003498.eml, copaair/it-905007180.eml, copaair/it-905322712.eml, copaair/it-905574071.eml, copaair/it-906045165.eml, copaair/it-908569613.eml, copaair/it-914323349.eml";

    public $lang = '';

    public $date;

    public $detectSubjects = [
        'en' => [
            'assigned to your flight to',
            'is delayed.',
        ],
        'es' => [
            'asignada a tu vuelo hacia',
            'se encuentra demorado.',
            'se encuentra adelantado.',
            'Nuevo vuelo hacia',
        ],
        'pt' => [
            'designado para seu voo para',
            'atribuído ao seu voo para',
            'está atrasado.',
            'está adiantado.',
        ],
    ];

    public static $dictionary = [
        'en' => [
            // assign language
            'Flight details' => ['Flight details', 'New Itinerary'],

            // detects
            'assigned to your flight to' => ['assigned to your flight to', 'is delayed'],
            'Download the app'           => ['Download the app', 'Download the Copa Airlines mobile application'],

            'confDesc'                   => ['Reservation code:', 'Reservation code'],
            // 'operator' => '',
        ],
        'es' => [
            // assign language
            'Flight details' => ['Detalles del vuelo', 'Nuevo Itinerario', 'Nuevo vuelo:'],

            // detects
            'assigned to your flight to'      => [
                'asignada a tu vuelo hacia', 'se encuentra demorado', 'se encuentra adelantado', 'Nuevo vuelo hacia',
            ],
            'The gate closes'                 => 'La puerta de abordaje cierra',
            'Download the app'                => ['Descarga la app', 'Descarga la aplicación'],
            'stay tuned to our notifications' => 'Mantente al tanto de nuestras notificaciones',

            'confDesc' => ['Código de reserva:', 'Código de reserva'],
            'operator' => 'Operado por:',
        ],
        'pt' => [
            // assign language
            'Flight details' => ['Detalhes do voo', 'Novo Itinerário'],

            // detects
            'assigned to your flight to'      => [
                'designado para seu voo para', 'atribuído ao seu voo para', 'está atrasado', 'está adiantado',
            ],
            'The gate closes'                 => 'O portão de embarque fecha',
            'Download the app'                => ['Baixe o app', 'Faça o download do aplicativo'],
            'stay tuned to our notifications' => 'fique atento às nossas notificações',

            'confDesc'                        => ['Código de reserva:', 'Código de reserva'],
            // 'operator' => '',
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
        if ($this->http->XPath->query("//img/@src[{$this->contains('copaair.com')}]")->length === 0
            && $this->http->XPath->query("//img/@alt[{$this->contains('Copa Logo')}]")->length === 0
        ) {
            return false;
        }

        $this->assignLang();

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('assigned to your flight to'))}]")->length > 0
            && ($this->http->XPath->query("//text()[{$this->starts($this->t('The gate closes'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->contains($this->t('stay tuned to our notifications'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->starts($this->t('Debido a una demora o cancelación'))}]")->length > 0) // no english phrase
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Download the app'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseFlight(Email $email)
    {
        $f = $email->add()->flight();

        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:\s*[AaPp](?:\.\s*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm  |  00:00
        ];

        // collect reservation confirmation
        $confDesc = trim($this->http->FindSingleNode("//text()[{$this->eq($this->t('confDesc'))}]"), ':');
        $confNumber = $this->http->FindSingleNode("//text()[{$this->eq($this->t('confDesc'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{5,7})\s*$/");

        if (!empty($confNumber)) {
            $f->general()->confirmation($confNumber, $confDesc);
        }

        // collect segments
        $flightNodes = $this->http->XPath->query("(//table[{$this->eq($this->t('Flight details'))}])[1]/following-sibling::table[normalize-space()]/descendant::*[count(tr[normalize-space()])=4][1]");

        if ($flightNodes->length === 0) {
            $flightNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Flight details'))}]/following::table[normalize-space()][1]/descendant::*[count(tr[normalize-space()])=4][1]");
        }

        foreach ($flightNodes as $root) {
            $s = $f->addSegment();

            // collect day and airline info
            $airlineInfo = $this->http->FindSingleNode("./tr[normalize-space()][1]", $root);
            $day = null;

            if (preg_match("/^\s*(?<day>.+?)[ ·•]+(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{1,4})(?:\s+(?<status>.+?))?\s*$/u", $airlineInfo, $m)) {
                $s->airline()
                    ->name($m['aName'])
                    ->number($m['fNumber']);

                if (!empty($m['status'])) {
                    $s->setStatus($m['status']);
                }

                $day = $this->normalizeDate($m['day']);
            }

            // collect depTime, arrTime and duration
            $timesInfo = $this->http->FindSingleNode("./tr[normalize-space()][2]", $root);

            if (!empty($day) && preg_match("/^.*?(?<depTime>{$patterns['time']})\s+(?<duration>\d+\s*[hH]\s*\d+\s*[mM])\s+(?<arrTime>{$patterns['time']}).*$/", $timesInfo, $m)) {
                $depDate = strtotime($this->normalizeTime($m['depTime']), $day);
                $arrDate = strtotime($this->normalizeTime($m['arrTime']), $day);

                if ($depDate > $arrDate) {
                    $arrDate = strtotime('+1 day', $arrDate);
                }

                $s->setDepDate($depDate);
                $s->setArrDate($arrDate);
                $s->setDuration($m['duration']);
            }

            // collect department and arrival places
            $placesInfo = $this->http->FindSingleNode("./tr[normalize-space()][3]", $root);

            if (preg_match("/^\s*(?<depName>.+?)\s*\((?<depCode>[A-Z]{3})\)\s+(?<arrName>.+?)\s*\((?<arrCode>[A-Z]{3})\)\s*$/", $placesInfo, $m)) {
                $s->departure()
                    ->name($m['depName'])
                    ->code($m['depCode']);

                $s->arrival()
                    ->name($m['arrName'])
                    ->code($m['arrCode']);
            }

            // collect terminals
            $depTerminal = $this->http->FindSingleNode("./tr[normalize-space()][4]/descendant::tr[count(td)=2]/td[1]", $root, true, "/{$this->opt($this->t('Terminal'))}[\s\:]+(\w+)(?:\s|$)/");

            if (!empty($depTerminal)) {
                $s->setDepTerminal($depTerminal);
            }

            $arrTerminal = $this->http->FindSingleNode("./tr[normalize-space()][4]/descendant::tr[count(td)=2]/td[2]", $root, true, "/{$this->opt($this->t('Terminal'))}[\s+\:]+(\w+)(?:\s|$)/");

            if (!empty($arrTerminal)) {
                $s->setArrTerminal($arrTerminal);
            }

            // collect operator
            $operator = $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('operator'))}]", $root, null, "/^\s*{$this->opt($this->t('operator'))}\s*(.+?)\s*$/");

            if (!empty($operator)) {
                $s->setOperatedBy($operator);
            }
        }

        // collect traveller info (name and seat)
        $travellerNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Pasajeros y asientos'))}]/ancestor::tr[1]/following-sibling::tr[normalize-space()]");

        foreach ($travellerNodes as $trNode) {
            $travellerName = $this->http->FindSingleNode("./descendant::tr[count(td[normalize-space()]) = 2]/td[normalize-space()][2]/*[normalize-space()][1]", $trNode, null, "/^\s*(?:{$this->opt(['Miss', 'Mrs', 'Mr', 'Ms'])}\.?\s*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\s*$/");

            if (!empty($travellerName)) {
                $f->addTraveller($travellerName);
            }

            $seat = $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Seat'))}]", $trNode, null, "/^\s*{$this->opt($this->t('Seat'))}\s*(\d+[A-Z])\s*$/");

            if (!empty($seat) && count($segments = $f->getSegments()) === 1) {
                $segments[0]->addSeat($seat, false, false, $travellerName);
            }
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
            if (!empty($dict['Flight details'])
                && $this->http->XPath->query("//text()[{$this->eq($dict['Flight details'])}]")->length > 0
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

        $in = [
            "/^\s*(\w+)\,\s+(\w+)\s+(\d+)\s*$/u", // Mon, May 5 => Mon, 5 May {$year}
            "/^\s*(\d{4})\-(\d+)\-(\d+)\s*$/u", // 2025-05-10
        ];
        $out = [
            "$1, $3 $2 {$year}",
            "$3.$2.$1",
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        // $this->logger->debug('date replace = ' . print_r( $date, true));

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

    private function normalizeTime(string $s): string
    {
        // 21:51 PM    ->    21:51
        if (preg_match('/^((\d{1,2})[ ]*:[ ]*\d{2})\s*[AaPp][Mm]$/', $s, $m) && (int) $m[2] > 12) {
            $s = $m[1];
        }

        return $s;
    }
}
