<?php

namespace AwardWallet\Engine\etihad\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
// use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class FlightBoarding extends \TAccountChecker
{
	public $mailFiles = "etihad/it-905474220.eml, etihad/it-908688658.eml, etihad/it-922884085.eml, etihad/it-924582299.eml, etihad/it-924609787.eml, etihad/it-924717475.eml";

    public $lang = 'en';
    public $date;

    public static $dictionary = [
        'en' => [
            'Your flight is now boarding' => ['Your flight is now boarding', 'Your upcoming flight has changed', 'Flight has been re-instated', 'Check in online now', 'Your flight has been delayed', 'Seat is available'],
            'Trip details' => ['Trip details', 'New flight details'],
            'confNumber' => ['Manage booking:'],
        ],
        'pt' => [
            'Your flight is now boarding' => ['O seu voo sofreu alterações'],
            'Trip details' => ['Novos detalhes do voo'],
            'confNumber' => ['Gerenciar reserva:'],
            'Guest overview' => ['Visão geral de visitantes'],
            'Seat' => ['Assento'],
            'Cabin' => ['Cabine'],
        ],
        'es' => [
            'Your flight is now boarding' => ['Su vuelo está embarcando'],
            'Trip details' => ['Datos del viaje'],
            'confNumber' => ['Gestionar la reserva:'],
            'Guest overview' => ['Resumen de clientes'],
            'Seat' => ['Asiento'],
            'Cabin' => ['Cabina'],
        ],
    ];

    private $detectFrom = "@bookings.etihad.com";
    private $detectSubject = [
        // en
        'Let’s go! Head to the gate',
        'Your Etihad Airways flight reference',
        'Check-in online now',
        'Online check-in is open',
        'Departure time change: Your flight',
        'You’ve got a seat! Get ready for flight',
        // pt
        'A referência do seu voo da Etihad Airways',
        // es
        '¡Vamos! Vaya a la puerta de embarque',
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]bookings\.etihad\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false
        ) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $this->assignLang();

        // detect Provider
        if (
            $this->http->XPath->query("//a[{$this->contains(['.etihad.com'], '@href')}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Etihad Airways'])}]")->length === 0
        ) {
            return false;
        }

        // detect Format

        if ($this->http->XPath->query("//*[{$this->contains($this->t('Your flight is now boarding'))}]")->length > 0
            && $this->http->XPath->query("//*[{$this->contains($this->t('Trip details'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

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
        $f = $email->add()->flight();

        // General
        $travellers = array_unique($this->http->FindNodes("//tr[{$this->eq($this->t('Guest overview'))}]/following-sibling::tr[normalize-space()][not({$this->contains($this->t('Seat'))}) and not({$this->contains($this->t('Cabin'))})]/descendant::text()[normalize-space()][1]"));

        foreach ($travellers as $traveller){
            $ticket = $this->http->FindSingleNode("//tr[{$this->eq($this->t('Guest overview'))}]/following-sibling::tr[normalize-space()][not({$this->contains($this->t('Seat'))}) and not({$this->contains($this->t('Cabin'))})]/descendant::text()[{$this->eq($traveller)}]/ancestor::tr[normalize-space()][2]/following-sibling::tr[normalize-space()][{$this->contains($this->t('Ticket number'))}][1]", null, false, "/^{$this->opt($this->t('Ticket number'))}[ ]*\:[ ]*([\d\D\-]+)/u");

            if ($ticket !== null) {
                $f->issued()
                    ->ticket($ticket, false, $traveller);
            }
        }

        if (empty($travellers)){
            $travellers[] = $this->http->FindSingleNode("//td[{$this->starts($this->t('Hi'))}][last()]/descendant::text()[normalize-space()][1]", null, false, "/^{$this->opt($this->t('Hi'))}[\, ]*(.+)(?:[\,\.\!\?]+|$)/u");
        }

        $accountBlock = $this->http->FindSingleNode("//td[contains(@background, 'Header_FlightTale')][last()]/descendant::table[normalize-space()][last()]/descendant::tr[normalize-space()][2]", null, false, "/^(\d+)/u");

        if ($accountBlock !== null){
            $f->addAccountNumber($accountBlock, false);
        }

        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('confNumber'))}]/following::text()[normalize-space()][1]", null, true, '/^[A-Z\d]{5,}$/'))
            ->travellers($travellers, true);

        $xpath = "//tr[{$this->eq($this->t('Trip details'))}]/following-sibling::tr[normalize-space()][./descendant::img[contains(@src, 'Plane_')]]/descendant::table[1][not(contains(@style, 'visibility:hidden;'))]/descendant::tbody[normalize-space()][count(./child::tr) = 2 and ./child::tr[2][normalize-space()][./descendant::img[contains(@src, 'Plane_')]]]";

        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            $fPos = 1;

            $flightNode = $this->http->FindSingleNode("./child::tr[normalize-space()][1]", $root);

            if (preg_match("/^.*\b(?<aName>(?:[A-Z][A-Z\d]|[A-Z\d][A-Z]))\s*(?<fNumber>\d{1,4})$/u", $flightNode, $m)){
                $s->airline()
                    ->name($m['aName'])
                    ->number($m['fNumber']);
            }

            $cancelled = $this->http->FindSingleNode("./child::tr[normalize-space()][1]/descendant::img[contains(@src, 'Cancelled')]", $root);

            if ($cancelled !== null){
                $s->setStatus('Cancelled')
                    ->setCancelled(true);
            }
            
            $blockXpath = "./child::tr[normalize-space()][2]/descendant::tbody[count(./child::tr) = 3 and ./descendant::tr[normalize-space()][./descendant::img[contains(@src, 'Plane_')]]]";

            $depCity = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]/child::td[normalize-space()][1]/descendant::text()[normalize-space()][2]", $root);
            $depAirport = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][2]/descendant::tr[1]/child::td[1]/descendant::tr[3]/descendant::text()[normalize-space()][1]", $root);

            $s->departure()
                ->code($depCode = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]/child::td[normalize-space()][1]/descendant::text()[normalize-space()][1]", $root))
                ->name($depCity . ", " . $depAirport);

            $arrCity = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]/child::td[normalize-space()][3]/descendant::text()[normalize-space()][2]", $root);
            $arrAirport = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][2]/descendant::tr[1]/child::td[3]/descendant::tr[3]/descendant::text()[normalize-space()][1]", $root);

            $s->arrival()
                ->code($arrCode = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]/child::td[normalize-space()][3]/descendant::text()[normalize-space()][1]", $root))
                ->name($arrCity . ", " . $arrAirport);

            $depTime = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][2]/descendant::tr[1]/child::td[1]/descendant::tr[1]/descendant::text()[normalize-space()][last()]", $root, false, "/^([0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?)$/u");
            $depDate = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][2]/descendant::tr[1]/child::td[1]/descendant::tr[2]/descendant::text()[normalize-space()][last()]", $root, false, "/^(\w+[\. ]*[0-9]{1,2}|[0-9]{1,2}[ ]*\w+[\. ]*)$/u");

            if ($depTime !== null && $depDate !== null){
                $s->departure()
                    ->date($this->normalizeDate($depDate . ', ' . $depTime));
            }

            $arrTime = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][2]/descendant::tr[1]/child::td[3]/descendant::tr[1]/descendant::text()[normalize-space()][last()]", $root, false, "/^([0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?)$/u");
            $arrDate = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][2]/descendant::tr[1]/child::td[3]/descendant::tr[2]/descendant::text()[normalize-space()][last()]", $root, false, "/^(\w+[\. ]*[0-9]{1,2}|[0-9]{1,2}[ ]*\w+[\. ]*)$/u");

            if ($arrTime !== null && $arrDate !== null){
                $s->arrival()
                    ->date($this->normalizeDate($arrDate . ', ' . $arrTime));
            }

            $depTerminal = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][2]/descendant::tr[1]/child::td[1]/descendant::tr[3]/descendant::text()[normalize-space()][2]", $root, false, "/^{$this->opt($this->t('Terminal'))}[ ]*(.+)$/u");

            if ($depTerminal !== null) {
                $s->departure()
                    ->terminal($depTerminal);
            }

            $arrTerminal = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][2]/descendant::tr[1]/child::td[3]/descendant::tr[3]/descendant::text()[normalize-space()][2]", $root, false, "/^{$this->opt($this->t('Terminal'))}[ ]*(.+)$/u");

            if ($arrTerminal !== null) {
                $s->arrival()
                    ->terminal($arrTerminal);
            }

            $aircraft = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]/child::td[normalize-space()][2]/descendant::tr[normalize-space()][1]/descendant::tr[2]/descendant::text()[normalize-space()][last()]", $root);

            if ($aircraft !== null){
                $s->extra()
                    ->aircraft($aircraft);
            }

            $duration = $this->http->FindSingleNode($blockXpath . "/child::tr[normalize-space()][1]/descendant::tr[normalize-space()][1]/child::td[normalize-space()][2]/descendant::tr[normalize-space()][1]/descendant::tr[1]/descendant::text()[normalize-space()][1]", $root, false, "/^(.*[0-9]+[ ]*(?:h|hour|hours|m|min|minutes))$/u");

            if ($duration !== null){
                $s->extra()
                    ->duration($duration);
            }
            $this->logger->debug("//tr[./preceding::tr[{$this->eq($this->t('Guest overview'))}] and ./descendant::th[1]/descendant::tr[{$this->eq($depCode)}] and ./descendant::th[1]/descendant::tr[{$this->eq($arrCode)}] and count(./child::th) = 3]");
            $seats = $this->http->XPath->query("//tr[./preceding::tr[{$this->eq($this->t('Guest overview'))}] and ./descendant::th[1]/descendant::tr[1]/descendant::td[2][{$this->contains($depCode)}] and ./descendant::th[1]/descendant::tr[1]/descendant::td[3][{$this->contains($arrCode)}] and count(./child::th) = 3]");

            foreach ($seats as $seatRoot){
                $seat = $this->http->FindSingleNode("./child::th[3]/descendant::text()[{$this->eq($this->t('Seat'))}]/following::text()[normalize-space()][1]", $seatRoot, false, "/^(\d{1,2}\D+)$/u");

                $seatUser = $this->http->FindSingleNode("./ancestor::tr[3]/preceding-sibling::tr[not({$this->contains($this->t('Cabin'))})][1]/descendant::text()[normalize-space()][not({$this->contains($this->t('Ticket number'))})][1]", $seatRoot);

                if ($seat !== null && $seatUser !== null){
                    $s->extra()->seat($seat, null, false, $seatUser);
                }
            }
        }
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

        return '(' . implode(' or ', array_map(function ($s) {
                return 'normalize-space(.)="' . $s . '"';
            }, $field)) . ')';
    }

    private function normalizeDate(?string $date)
    {
        if (empty($date) || empty($this->date)) {
            return null;
        }

        $year = date("Y", $this->date);

        $in = [
            // Jun 15, 09:00 AM
            "/^(\w+)[\. ]*([0-9]{1,2})\,[ ]*([0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?)$/iu",
            // 15 Jun, 09:00 AM
            "/^([0-9]{1,2})[ ]*(\w+)[\. ]*\,[ ]*([0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?)$/iu",
        ];
        $out = [
            "$1 $2 $year, $3",
            "$2 $1 $year, $3",
        ];
        $date = preg_replace($in, $out, $date);

        if (preg_match("#(.*\d+\s+)([^\d\s]+)(\s+\d{4}.*)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[2], $this->lang)) {
                $date = $m[1] . $en . $m[3];
            } elseif ($en = MonthTranslate::translate($m[2], 'de')) {
                $date = $m[1] . $en . $m[3];
            }
        }

        if (preg_match("#(\w+)([ ]+\d+)([ ]+\d{4}.*)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = $m[2] . $en . $m[3];
            }
        }

        if (preg_match("/^(?<week>\w+), (?<date>\d+ \w+ .+)/u", $date, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));
            $str = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        } elseif (preg_match("/\b\d{4}\b/", $date)) {
            $str = strtotime($date);
        } else {
            $str = null;
        }

        return $str;
    }

    private function opt($field, $delimiter = '/'): string
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
                return str_replace(' ', '\s+', preg_quote($s, $delimiter));
            }, $field)) . ')';
    }

    private function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $words) {
            if (isset($words['confNumber'], $words['Trip details'])) {
                if ($this->http->XPath->query("//*[{$this->contains($words['Trip details'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($words['confNumber'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }
}
