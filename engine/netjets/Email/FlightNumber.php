<?php

namespace AwardWallet\Engine\netjets\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FlightNumber extends \TAccountChecker
{
    public $mailFiles = "emails2parse/it-918282946.eml, emails2parse/it-921042892.eml, emails2parse/it-921271444.eml, emails2parse/it-921375595.eml, emails2parse/it-921762073.eml, emails2parse/it-922668298.eml, emails2parse/it-923030720.eml, emails2parse/it-923032829.eml, emails2parse/it-923033031.eml, emails2parse/it-923116358.eml, emails2parse/it-923919708.eml, emails2parse/it-923997368.eml, emails2parse/it-926926278.eml, netjets/it-911988912.eml";

    public $detectFrom = '@netjets.com';

    public $detectSubject = [
        '- NetJets Tail Number Notification',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        // detect provider
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], 'NetJets') === false
        ) {
            return false;
        }

        // detect format
        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect provider
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'NetJets')]")->length === 0) {
            return false;
        }

        // detect format
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'would like to inform you of the Tail Number Assignment')]")->length > 0
            && $this->http->XPath->query("//text()[contains(normalize-space(), 'PILOTS')]")->length > 0
            && $this->http->XPath->query("//text()[contains(normalize-space(), 'LEAD PASSENGER')]")->length > 0
            && $this->http->XPath->query("//text()[contains(normalize-space(), 'Aircraft Type:')]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]netjets\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->Flight($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function Flight(Email $email)
    {
        $f = $email->add()->flight();

        $otaConf = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Request #:')]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Request #:'))}\s*(\d{5,})$/");

        $email->ota()
            ->confirmation($otaConf);

        if (!empty($otaConf)) {
            $f->setNonCommercial(true);
        }

        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Aircraft Type:')]/ancestor::tr[1]/preceding::tr[1]", null, true, "/^([A-Z\d]{5,6})$/"))
            ->traveller($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'LEAD PASSENGER')]/ancestor::tr[1]/following-sibling::tr[1]", null, true, "/^([[:alpha:]][-.\'\([:alpha:] ]*[[:alpha:]]\)?)$/"));

        $nodes = $this->http->XPath->query("//text()[normalize-space()='DEPARTURE']/ancestor::table[1]");

        foreach ($nodes as $root) {
            $s = $f->addSegment();

            $s->airline()
                ->name('1I')
                ->noNumber();

            $depAirportInfo = $this->http->FindSingleNode("./following::table[2]", $root);

            if (preg_match("/^(?<depName>.+)\s(?<depCode>[A-Z]{4})$/", $depAirportInfo, $m)) {
                $s->departure()
                    ->name($m['depName']);

                $s->setDepCodeIcao($m['depCode']);
            }

            $arrAirportInfo = $this->http->FindSingleNode("./following::table[6]", $root);

            if (preg_match("/^(?<arrName>.+)\s(?<arrCode>[A-Z]{4})$/", $arrAirportInfo, $m)) {
                $s->arrival()
                    ->name($m['arrName']);

                $s->setArrCodeIcao($m['arrCode']);
            }

            $depDate = $this->http->FindSingleNode(".", $root, true, "/{$this->opt($this->t('DEPARTURE'))}\s*(.+\d{4})/");
            $depTime = $this->http->FindSingleNode("./following::table[1]", $root, true, "/^([\d\:]+\s*A?P?M)/");
            $s->setDepDate(strtotime($depDate . ', ' . $depTime));

            $arrDate = $this->http->FindSingleNode("./following::table[4]", $root, true, "/{$this->opt($this->t('ARRIVAL'))}\s*(.+\d{4})/");
            $arrTime = $this->http->FindSingleNode("./following::table[5]", $root, true, "/^([\d\:]+\s*A?P?M)/");

            $s->setArrDate(strtotime($arrDate . ', ' . $arrTime));

            $duration = $this->http->FindSingleNode("//text()[normalize-space()='Estimated Travel Time:']/following::text()[normalize-space()][1]", null, true, "/^(\d+.*(?:HRS|MINS))$/i");

            if (!empty($duration) && $nodes->length === 1) {
                $s->setDuration($duration);
            }

            $aircraft = trim($this->http->FindSingleNode("//text()[normalize-space()='Aircraft Type:']/ancestor::tr[1]/following-sibling::tr[1]"));

            if (!empty($aircraft) && $nodes->length === 1) {
                $s->setAircraft($aircraft);
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

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function t($s)
    {
        if (!isset($this->lang) || !isset(self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }
}
