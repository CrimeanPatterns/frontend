<?php

namespace AwardWallet\Engine\fourchette\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class ReservationUpdated extends \TAccountChecker
{
    public $mailFiles = "fourchette/it-107548330.eml, fourchette/it-110756349-fr.eml, fourchette/it-898942430-es.eml";

    public $date;
    public $lang = '';
    public static $dictionary = [
        'en' => [
            //            'Hello ' => '',
            //            'people' => '',
            'Get directions' => ['Get directions', 'GET DIRECTIONS'],
        ],
        'fr' => [
            'Hello '         => 'Bonjour',
            'people'         => ['personnes', 'personne'],
            'Get directions' => ["Obtenir l'itinéraire", "OBTENIR L'ITINÉRAIRE"],
        ],
        'es' => [
            'Hello '         => 'Hola,',
            'people'         => ['personas', 'persona'],
            'Get directions' => ['Cómo llegar', 'CÓMO LLEGAR'],
        ],
        'pt' => [
            'Hello '         => 'Olá,',
            'people'         => ['pessoas', 'pessoa'],
            'Get directions' => ['Obter direções', 'OBTER DIREÇÕES', 'Veja como chegar', 'VEJA COMO CHEGAR'],
        ],
        'it' => [
            'Hello '         => 'Salve ',
            'people'         => 'persone',
            'Get directions' => ['Come raggiungerci', 'COME RAGGIUNGERCI'],
        ],
    ];

    private $detectSubject = [
        // en
        'has been updated',
        //fr
        'a été mise à jour',
        // es
        ' se ha modificado',
        // pt
        ' foi atualizada',
        // it
        ' è stata aggiornata',
    ];
    private $detectBody = [
        'en' => [
            'Your reservation has been updated. Take a look at the new details below',
            'Your booking has been updated. Take a look at the new details below',
        ],
        'fr' => [
            'Votre réservation a été mise à jour. Voici les nouvelles informations',
        ],
        'es' => [
            'La reserva se ha modificado. Consulta los nuevos detalles a continuación',
        ],
        'pt' => [
            'A sua reserva foi atualizada. Veja os novos detalhes abaixo:',
            'A reserva foi atualizada',
        ],
        'it' => [
            'La prenotazione è stata aggiornata. Vedi i nuovi dettagli sotto:',
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match('/@thefork\.[a-z]+$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || !preg_match('/\bnotification@thefork\.[a-z]+$/i', rtrim($headers['from'], '> '))) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (is_string($dSubject) && array_key_exists('subject', $headers) && stripos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $href = ['.lafourchette.com/', 'www.lafourchette.com', 'www.thefork.co', 'www.thefork.es'];

        if ($this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0) {
            return false;
        }

        foreach ($this->detectBody as $lang => $detectBody) {
            if ($this->http->XPath->query("//*[{$this->contains($detectBody)}]")->length > 0) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if (!$this->assignLang() && $this->detectEmailByBody($parser) !== true) {
            $this->logger->debug("can't determine a language");

            return $email;
        }

        $this->date = strtotime($parser->getDate());
        $this->parseEmailHtml($email);

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

    private function parseEmailHtml(Email $email): void
    {
        $xpathNoDisplay = 'ancestor-or-self::*[contains(translate(@style," ",""),"display:none")]';

        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm
            'phone' => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52  |  (+351) 21 342 09 07  |  713.680.2992
            'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        ];

        $r = $email->add()->event();

        // General
        $r->general()
            ->noConfirmation()
            ->traveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello '))}]", null, true, "/{$this->preg_implode($this->t('Hello '))}[,\s]*({$patterns['travellerName']})[,:;!\s]*$/u"), false)
        ;

        // Place
        $address = "//text()[{$this->eq($this->t("Get directions"))} and not({$xpathNoDisplay})]/preceding::tr[ count(*)=2 and *[1][normalize-space()='' and .//img] and *[2][normalize-space()] ][1]/*[2]";
        $r->place()
            ->name($this->http->FindSingleNode($address . "/descendant::text()[normalize-space()][1]"))
            ->address($this->http->FindSingleNode($address . "/descendant::text()[normalize-space()][2]"))
            ->type(EVENT_RESTAURANT);

        $phone = $this->http->FindSingleNode($address . "/descendant::text()[normalize-space()][3]", null, true, "/^{$patterns['phone']}$/");
        $r->place()->phone($phone, false, true);

        // Booked
        $info = "//tr[count(*[normalize-space()='•']) = 2]";

        $dateStart = $this->normalizeDate($this->http->FindSingleNode($info . "/*[normalize-space()!='•'][2]"));
        $timeStart = $this->http->FindSingleNode($info . "/*[normalize-space()!='•'][3]", null, true, "/^{$patterns['time']}$/");

        $r->booked()
            ->start(strtotime($timeStart, $dateStart))
            ->noEnd()
            ->guests($this->http->FindSingleNode($info . "/*[normalize-space() != '•'][1]",
                null, true, "/^\s*(\d+) " . $this->preg_implode($this->t("people")) . "/u"))
        ;
    }

    private function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $dict) {
            if ( !is_string($lang) || empty($dict['Get directions']) ) {
                continue;
            }

            if ($this->http->XPath->query("//*[{$this->contains($dict['Get directions'])}]")->length > 0) {
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

    // additional methods
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

    private function normalizeDate(?string $date)
    {
        // $this->logger->debug('date begin = ' . print_r($date, true));

        if (empty($date) || empty($this->date)) {
            return null;
        }
        $year = date("Y", $this->date);

        $in = [
            // Saturday, 17 Jul    |    Quinta-feira, 8 de out
            '/^\s*([- [:alpha:]]+?)[,.\s]+(\d{1,2})[,.\s]+(?:de\s+)?([[:alpha:]]+)[.\s]*$/iu',
        ];
        $out = [
            "$1, $2 $3 {$year}",
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("/\b\d{1,2}\s+([[:alpha:]]+)\s+\d{2,4}$/u", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            } elseif ($en = MonthTranslate::translate($m[1], 'de')) {
                $date = str_replace($m[1], $en, $date);
            } elseif ($en = MonthTranslate::translate($m[1], 'pt')) {
                $date = str_replace($m[1], $en, $date);
            }
        }

//        $this->logger->debug('date end = ' . print_r( $date, true));
        if (preg_match("/^(?<week>[- [:alpha:]]+), (?<date>\d{1,2} [[:alpha:]]+ .+)$/u", $date, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));

            if (!is_numeric($weeknum)) {
                $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], 'pt'));
            }
            $date = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/\b\d{4}\b/", $date) || preg_match("/^\d{1,2}\s+[[:alpha:]]+\s+\d{2}$/u", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }

        return $date;
    }

    private function preg_implode($field)
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }
}
