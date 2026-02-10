<?php

namespace AwardWallet\Engine\oojo\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Flight extends \TAccountChecker
{
    public $mailFiles = "oojo/it-826722873.eml, oojo/it-828708769.eml, oojo/it-900794784.eml, oojo/it-903858953.eml, oojo/it-904973847.eml, oojo/it-906909757.eml";

    public $detectSubjects = [
        'en' => [
            'OOJO: Your Travel Booking Confirmation',
        ],
    ];

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'detectPhrase'                  => ['Thank you for choosing Oojo!'],
            'confDesc'                      => ['Your OOJO Confirmation number', 'Your PNR'],
            'Depart'                        => 'Depart',
            'stop'                          => 'stop',
            "Airline's full baggage policy" => "Airline's full baggage policy",
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]oojo\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from']) || stripos($headers['from'], 'oojo.com') === false)
            && stripos($headers['subject'], 'OOJO') === false
        ) {
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

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//a/@href[{$this->contains(['oojo.com'])}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Oojo International B.V.'])}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->starts($this->t('detectPhrase'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('confDesc'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Depart'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('stop'))}]")->length > 0
            && ($this->http->XPath->query("//a[{$this->eq($this->t("Airline's full baggage policy"))} or {$this->contains($this->t('BAGGAGEPOLICY'), '@href')} or {$this->contains($this->t('BAGGAGEPOLICY'), '@originalsrc')}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->starts($this->t('Carry-on baggage'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->starts($this->t('Checked baggage'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->starts($this->t('No baggage information available'))}]")->length > 0)
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email): Email
    {
        // detect Junk
        if ($this->http->XPath->query("//a[{$this->eq($this->t("Airline's full baggage policy"))} or {$this->contains($this->t('BAGGAGEPOLICY'), '@href')} or {$this->contains($this->t('BAGGAGEPOLICY'), '@originalsrc')}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Carry-on baggage'))} or {$this->starts($this->t('Checked baggage'))}]/ancestor::*[1]/following-sibling::*/descendant::a")->length === 0
        ) {
            $email->setIsJunk(true, "No airline name and flight number");
            $class = explode('\\', __CLASS__);
            $email->setType(end($class) . 'Junk' . ucfirst($this->lang));

            return $email;
        }

        $this->Flight($email);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function Flight(Email $email)
    {
        $f = $email->add()->flight();

        $airlineXpath = "({$this->eq($this->t("Airline's full baggage policy"))} or {$this->contains($this->t('BAGGAGEPOLICY'), '@href')} or {$this->contains($this->t('BAGGAGEPOLICY'), '@originalsrc')})";

        // collect reservation confirmation
        $confDesc = $this->re("/^{$this->opt($this->t('Your'))}\s+(.+)$/", $this->http->FindSingleNode("//text()[{$this->starts($this->t('confDesc'))}]", null, true, "/^\s*({$this->opt($this->t('confDesc'))})/"));
        $confNumber = $this->http->FindSingleNode("//text()[{$this->starts($this->t('confDesc'))}]", null, true, "/^\s*{$this->opt($this->t('confDesc'))}\s*\:\s*([A-Z\d]{5,7})\s*$/")
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('confDesc'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{5,7})\s*$/");

        $f->general()
            ->confirmation($confNumber, $confDesc);

        // collect year from site footer
        $year = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Oojo International B.V.'))}]", null, false, "/^\©\s*(\d{4})\s*Oojo\s*International\s*B\.V\.\,/");

        $airlineLinks = $this->http->XPath->query("//a[{$airlineXpath}]");

        foreach ($airlineLinks as $root) {
            $s = $f->addSegment();

            $airName = $this->http->FindSingleNode("./@href", $root, true, "/\/([A-Z][A-Z\d]|[A-Z\d][A-Z])$/")
                ?? $this->http->FindSingleNode("./@originalsrc", $root, true, "/\/([A-Z][A-Z\d]|[A-Z\d][A-Z])$/");

            if ($airName !== null) {
                $s->airline()
                    ->name($airName);
            } else {
                $s->airline()
                    ->noName();
            }

            $s->airline()
                ->noNumber();

            // collect depCode and arrCode from segment
            $flightCodes = $this->http->FindSingleNode("./preceding::text()[normalize-space()][3]", $root, true, "/^.+\,.+\-.+\,.+$/")
                ?? $this->http->FindSingleNode("./preceding::text()[normalize-space()][4]", $root, true, "/^.+\,.+\-.+\,.+$/");

            if (preg_match("/^\s*(?<depCode>[A-Z]{3})\b.*\-\s*(?<arrCode>[A-Z]{3})\b.*$/", $flightCodes, $m)
                || preg_match("/^\s*(?<depName>.*?)\s*\-\s*(?<arrName>.*?)\s*$/", $flightCodes, $m)
            ) {
                if (!empty($m['depCode'])) {
                    $s->setDepCode($m['depCode']);
                    $s->setArrCode($m['arrCode']);
                } else {
                    $s->setDepName($m['depName']);
                    $s->setArrName($m['arrName']);
                }
            }

            // if flight codes not in segment, collect flight codes from headers of departure or arrival blocks
            if (empty($s->getDepCode()) && empty($s->getArrCode())) {
                $depCode = $this->http->FindSingleNode("./ancestor::td[1]/descendant::td[normalize-space()][1]/descendant::text()[normalize-space()][last()]", $root);
                $arrCode = $this->http->FindSingleNode("./ancestor::td[1]/descendant::td[normalize-space()][2]/descendant::text()[normalize-space()][last()]", $root);

                // if this airlineLink (root) is first in depart block
                if ($this->http->XPath->query("./preceding::a[{$airlineXpath}]", $root)->length === 0) {
                    $s->departure()
                        ->code($depCode);
                }

                // if this airlineLink (root) is last in depart block
                if ($this->http->FindSingleNode("./following::text()[{$this->starts($this->t('Return'))}]", $root) !== null
                    && $this->http->XPath->query("./following::a[{$airlineXpath}][following::text()[{$this->starts($this->t('Return'))}]]", $root)->length === 0) {
                    $s->arrival()
                        ->code($arrCode);
                }

                // if this airlineLink (root) is first in arrival block
                if ($this->http->FindSingleNode("./preceding::text()[{$this->starts($this->t('Return'))}]", $root) !== null
                    && $this->http->XPath->query("./preceding::a[{$airlineXpath}][preceding::text()[{$this->starts($this->t('Return'))}]]", $root)->length === 0) {
                    $s->departure()
                        ->code($depCode);
                }

                // if this airlineLink (root) is last in arrival block
                if ($this->http->XPath->query("./following::a[{$airlineXpath}]", $root)->length === 0) {
                    $s->arrival()
                        ->code($arrCode);
                }
            }

            if (empty($s->getDepCode())) {
                $s->setNoDepCode(true);
            }

            if (empty($s->getArrCode())) {
                $s->setNoArrCode(true);
            }

            // collect dates
            $departureDate = $this->http->FindSingleNode("./ancestor::td[1]/descendant::td[normalize-space()][1]", $root);

            if ($year !== null && preg_match("/^(?<time>\d{1,2}\:\d{2}\s*[Aa]?[Pp]?[Mm]?)\s+(?<date>\w+\,\s*\w+\s*\d+)\s*{$s->getDepCode()}.*$/", $departureDate, $d)) {
                $s->departure()
                    ->date($this->normalizeDate($d['date'] . ' ' . $year . ' ' . $d['time']));
            } else {
                $s->departure()
                    ->noDate();
            }

            $arrivalDate = $this->http->FindSingleNode("./ancestor::td[1]/descendant::td[normalize-space()][2]", $root);

            if ($year !== null && preg_match("/^(?<time>\d{1,2}\:\d{2}\s*[Aa]?[Pp]?[Mm]?)\s+(?<date>\w+\,\s*\w+\s*\d+)\s*{$s->getArrCode()}.*$/", $arrivalDate, $d)) {
                $s->arrival()
                    ->date($this->normalizeDate($d['date'] . ' ' . $year . ' ' . $d['time']));
            } else {
                $s->arrival()
                    ->noDate();
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

    private function normalizeDate($date)
    {
        if (preg_match("/^(?<weekDay>\w+)\,\s*(?<month>\w+)\s*(?<date>\d+)\s*(?<year>\d{4})\s*(?<time>\d{1,2}\:\d{2}\s*[Aa]?[Pp]?[Mm]?)$/u", $date, $x)) {
            $dayOfWeekInt = WeekTranslate::number1(WeekTranslate::translate($x['weekDay'], $this->lang));

            if ($en = MonthTranslate::translate($x['month'], $this->lang)) {
                $date = EmailDateHelper::parseDateUsingWeekDay($x['time'] . ' ' . $x['date'] . ' ' . $en . ' ' . $x['year'], $dayOfWeekInt);
            } else {
                $date = EmailDateHelper::parseDateUsingWeekDay($x['time'] . ' ' . $x['date'] . ' ' . $x['month'] . ' ' . $x['year'], $dayOfWeekInt);
            }
        }

        return $date;
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

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
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
}
