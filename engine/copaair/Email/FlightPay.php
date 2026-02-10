<?php

namespace AwardWallet\Engine\copaair\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class FlightPay extends \TAccountChecker
{
    public $mailFiles = "copaair/it-899816866.eml, copaair/it-904955662.eml, copaair/it-906832055.eml, copaair/it-912593576.eml";

    public $lang = '';

    public $date;

    public $detectSubjects = [
        'en' => [
            'Don\'t lose your reservation',
        ],
        'es' => [
            'No pierdas tu reserva',
        ],
        'pt' => [
            'Não perca sua reserva',
        ],
    ];

    public static $dictionary = [
        'en' => [
            // assign language
            'Don\'t lose your reservation' => 'Don\'t lose your reservation',

            // detects
            'Trip details' => ['Trip details', 'Trip Details'],
        ],
        'es' => [
            // assign language
            'Don\'t lose your reservation' => 'No pierdas tu reserva',

            // detects
            'Pay reservation'      => 'Pagar reserva',
            'You currently have a' => 'Queremos recordarte que tienes un',
            'Trip details'         => 'Detalles del viaje',

            'reservation code'     => 'código de reserva',
            'Total due:'           => 'Total a pagar:',
        ],
        'pt' => [
            // assign language
            'Don\'t lose your reservation' => 'Não perca sua reserva',

            // detects
            'Pay reservation'      => 'Pagar reserva',
            'You currently have a' => 'Lembre-se que você tem um',
            'Trip details'         => 'Detalhes da viagem',

            'reservation code'     => 'código de reserva',
            'Total due:'           => 'Total a pagar:',
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

        // detect Format
        if ($this->assignLang()
            && $this->http->XPath->query("//a[{$this->eq($this->t('Pay reservation'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('You currently have a'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Trip details'))}]")->length > 0
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
        $confInfo = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('reservation code'))}][normalize-space()])[1]");

        if (preg_match("/(?<desc>{$this->opt($this->t('reservation code'))})\s+(?<number>[A-Z'\d]{5,})[\s\.]*$/", $confInfo, $m)) {
            $f->general()->confirmation($m['number'], $m['desc']);
        }

        // collect pricing details
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Total due:'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^\s*(?<amount>[\d\.\,\']+)\s+(?<currency>[^\d\s]{1,3})\s*$/u", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $f->price()
                ->total(PriceHelper::parse($m['amount'], $currency))
                ->currency($currency);
        }

        $flightNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Trip details'))}]/ancestor::tr[1]/following-sibling::tr[normalize-space()]");

        foreach ($flightNodes as $root) {
            $s = $f->addSegment();

            // collect day and airline info
            $airlineInfo = $this->http->FindSingleNode("./descendant::table[normalize-space()][last()]/descendant::tr[normalize-space()][1]", $root);

            $day = null;

            if (preg_match("/^\s*(?<day>.+?)(?:[ ·•]+(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{1,4}))?\s*$/", $airlineInfo, $m)) {
                if (!empty($m['aName'])) {
                    $s->airline()
                        ->name($m['aName'])
                        ->number($m['fNumber']);
                } else {
                    $s->setNoAirlineName(true);
                    $s->setNoFlightNumber(true);
                }

                $day = $this->normalizeDate($m['day']);
            }

            // collect department info
            $depInfo = $this->http->FindSingleNode("./descendant::table[normalize-space()][last()]/descendant::tr[normalize-space()][2]/td[normalize-space()][1]", $root);

            if (preg_match("/^\s*(?<depName>.+?)?\s*\((?<depCode>[A-Z]{3})\)\s*(?<depTime>{$patterns['time']})$/", $depInfo, $m)) {
                $s->departure()
                    ->code($m['depCode'])
                    ->date(strtotime($m['depTime'], $day));

                if (!empty($m['depName'])) {
                    $s->departure()
                        ->name($m['depName']);
                }
            }

            // collect arrival info
            $arrInfo = $this->http->FindSingleNode("./descendant::table[normalize-space()][last()]/descendant::tr[normalize-space()][2]/td[normalize-space()][2]", $root);

            if (preg_match("/^\s*(?<arrName>.+?)?\s*\((?<arrCode>[A-Z]{3})\)\s*(?<arrTime>{$patterns['time']})$/", $arrInfo, $m)) {
                $s->arrival()
                    ->code($m['arrCode'])
                    ->date(strtotime($m['arrTime'], $day));

                if (!empty($m['arrName'])) {
                    $s->arrival()
                        ->name($m['arrName']);
                }
            }

            // check dates
            if ($s->getDepDate() > $s->getArrDate()) {
                $s->setArrDate(strtotime("+1 day", $s->getArrDate()));
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
            if (!empty($dict['Don\'t lose your reservation'])
                && $this->http->XPath->query("//text()[{$this->eq($dict['Don\'t lose your reservation'])}]")->length > 0
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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date) || empty($this->date)) {
            return null;
        }
        $year = date('Y', $this->date);

        $in = [
            "/^\s*\w+\,\s*(\d+)\s+(\w+)\,\s*(\d{4})\s*$/u", // Fri, 20 Jun, 2025 => 20 Jun 2025
            // "/^\s*(\w+)\,\s*(\w+)\s+(\d+)\s*$/u", // Mon, May 5 => Mon, 5 May {$year}
        ];
        $out = [
            "$1 $2 $3",
            // "$1, $3 $2 {$year}",
        ];

        $date = preg_replace($in, $out, $date);

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

        return $date;
    }
}
