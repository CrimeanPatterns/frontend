<?php

namespace AwardWallet\Engine\tapportugal\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ChangeTime extends \TAccountChecker
{
    public $mailFiles = "tapportugal/it-689384737.eml, tapportugal/it-689828332-pt.eml, tapportugal/it-920340591.eml, tapportugal/it-922849031.eml, tapportugal/it-922850512.eml";
    public $subjects = [
        'An important message regarding flight',
        'Informação importante sobre o seu voo',
    ];

    public $lang = '';

    public $detectLang = [
        'pt' => ['Informação importante sobre seu voo', 'Código de Reserva:', 'Código de Reserva :'],
        'en' => ['Important Flight Information', 'Booking reference:', 'Booking reference :'],
    ];

    public static $dictionary = [
        "pt" => [
            'Departure Time Change'                    => 'Alteração de horário de voo',
            'Booking reference:'                       => 'Código de Reserva:',
            'Dear'                                     => 'Caro/a',
            'Customer'                                 => 'Cliente',
            'mainTextStart'                            => [
                'A TAP informa que o seu voo',
            ],
            'from'                                     => 'de',
            'to'                                       => 'para',
            'on'                                       => 'no dia',
            'The flight will be departing at'          => 'O seu voo partirá às',
        ],
        "en" => [
            'Departure Time Change' => ['Departure Time Change', 'Flight Update', 'Gate Change'],
            // 'Booking reference:' => '',
            // 'Dear' => '',
            // 'Customer' => '',
            'mainTextStart' => [
                'TAP informs you that your flight',
                'We inform that the NEW boarding gate for your flight',
                'We inform that your flight',
                'We inform that flight',
            ],
            // 'from' => '',
            // 'to' => '',
            // 'on' => '',
            'The flight will be departing at' => ['The flight will be departing at', 'will depart at'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@my-notification.flytap.com') !== false) {
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
        $this->assignLang();

        return $this->http->XPath->query("//text()[contains(normalize-space(), 'TAP Air Portugal')]")->length > 0
            && $this->http->XPath->query("//*[{$this->starts($this->t('Departure Time Change'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]my\-notification\.flytap\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        $this->ParseFlight($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseFlight(Email $email): void
    {
        $patterns = [
            'date' => '\d{1,2}\s*[[:alpha:]]+\s*\d{4}\b', // 16 Jan 2025
            'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $f = $email->add()->flight();

        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking reference:'))}]/ancestor::tr[1]", null, true, "/\:\s*([A-Z\d]{6})$/u"));

        $traveller = null;
        $travellerNames = array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Dear'))}]", null, "/^{$this->opt($this->t('Dear'))}[,\s]+({$patterns['travellerName']})(?:\s*[,;:!?]|$)/u"));

        if (count(array_unique($travellerNames)) === 1) {
            $traveller = array_shift($travellerNames);
        }

        if (preg_match("/^{$this->opt($this->t('Customer'))}$/i", $traveller)) {
            $traveller = null;
        }

        if ($traveller) {
            $f->general()->traveller($traveller);
        }

        $s = $f->addSegment();

        $mainText = $this->http->FindSingleNode("//text()[{$this->starts($this->t('mainTextStart'))}]");
        $this->logger->info('MAIN TEXT:');
        $this->logger->debug($mainText);

        // it-689384737.eml, it-689828332-pt.eml, it-920340591.eml
        $pattern1 = "/{$this->opt($this->t('mainTextStart'))}(?:\s+Flight)?\s*(?<aName>[A-Z][A-Z\d]|[A-Z\d][A-Z])(?<fNumber>\d+)\s+{$this->opt($this->t('from'))}\s+(?<depName>.+?)\s*\(\s*(?<depCode>[A-Z]{3})\s*\)\s+{$this->opt($this->t('to'))}\s+(?<arrName>.+?)\s*\(\s*(?<arrCode>[A-Z]{3})\s*\)\s+{$this->opt($this->t('on'))}\s+(?<depDate1>{$patterns['date']}).*{$this->opt($this->t('The flight will be departing at'))}\s+(?<depTime>{$patterns['time']})(?:\s+{$this->opt($this->t('on'))}\s+(?<depDate2>{$patterns['date']}))?(?:\s*[.;!]|$)/u";

        // it-922849031.eml
        $pattern2 = "/{$this->opt($this->t('mainTextStart'))}(?:\s+Flight)?\s*(?<aName>[A-Z][A-Z\d]|[A-Z\d][A-Z])(?<fNumber>\d+)\s+{$this->opt($this->t('from'))}\s+(?<depName>.+?)\s*\(\s*(?<depCode>[A-Z]{3})\s*\)\s+{$this->opt($this->t('to'))}\s+(?<arrName>.+?)\s*\(\s*(?<arrCode>[A-Z]{3})\s*\)\s+{$this->opt($this->t('on'))}\s+(?<depDate1>{$patterns['date']})\s+is delayed and new information about the departure will be given at {$patterns['time']}(?:\s*[.;!]|$)/u";

        // it-922850512.eml
        $pattern3 = "/{$this->opt($this->t('mainTextStart'))}(?:\s+Flight)?\s*(?<aName>[A-Z][A-Z\d]|[A-Z\d][A-Z])(?<fNumber>\d+)\s+{$this->opt($this->t('from'))}\s+(?<depName>.+?)\s*\(\s*(?<depCode>[A-Z]{3})\s*\)\s+{$this->opt($this->t('to'))}\s+(?<arrName>.+?)\s*\(\s*(?<arrCode>[A-Z]{3})\s*\)\s+{$this->opt($this->t('on'))}\s+(?<depDate1>{$patterns['date']})\s+is gate\s+[-A-z\d\s]+(?:\s*[.;!]|$)/u";

        if (preg_match($pattern1, $mainText, $m)) {
            $s->airline()->name($m['aName'])->number($m['fNumber']);
            $dateDep = empty($m['depDate2']) ? strtotime($m['depDate1']) : strtotime($m['depDate2']);

            $s->departure()
                ->name($m['depName'])
                ->code($m['depCode'])
                ->date(strtotime($m['depTime'], $dateDep))
            ;
    
            $s->arrival()
                ->name($m['arrName'])
                ->code($m['arrCode'])
                ->noDate()
            ;
        } elseif (preg_match($pattern2, $mainText, $m) || preg_match($pattern3, $mainText, $m)) {
            $s->airline()->name($m['aName'])->number($m['fNumber']);
            $dateDep = strtotime($m['depDate1']);

            $s->departure()
                ->name($m['depName'])
                ->code($m['depCode'])
                ->day($dateDep)
                ->noDate()
            ;

            $s->arrival()
                ->name($m['arrName'])
                ->code($m['arrCode'])
                ->noDate()
            ;
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

    private function assignLang(): bool
    {
        foreach ($this->detectLang as $lang => $words) {
            foreach ($words as $word) {
                if ($this->http->XPath->query("//text()[{$this->contains($word)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function eq($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'normalize-space(' . $node . ")='" . $s . "'";
        }, $field)) . ')';
    }
}
