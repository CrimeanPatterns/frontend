<?php

namespace AwardWallet\Engine\frontierairlines\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class UpdatedFlight extends \TAccountChecker
{
    public $mailFiles = "frontierairlines/it-925250236.eml, frontierairlines/it-925259254.eml, frontierairlines/it-925941733.eml, frontierairlines/it-925943352.eml";
    public $subjects = [
        'IMPORTANT: Flight delay notice. Confirmation Code',
        'Important Travel Information: Your gate has been changed',
        'Important Travel Information:Your flight',
        'Important information about your upcoming flight. Confirmation Code',
        'Important- Action Required. Confirmation Code',
        'Important changes to your Itinerary - Action Required. Confirmation Code',
        'Important Travel Information: Your flight',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'Updated flight itinerary' => ['Updated flight itinerary', 'New Gate'],
            'nameString' => ["we're sorry that your flight to", "the gate for your trip to",
                'the schedule for your trip to', "we're sorry that your flight has been affected by a schedule change that adjusted your flight times.",
                "we're sorry that your flight has been delayed.", "we're sorry that your flight"],
            'canceled' => ['Canceled', 'has been canceled', 'Missed Connection', 'Canceled to allow for a late arriving aircraft'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@reservation.flyfrontier.com') !== false) {
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
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Frontier Airlines')]")->length > 0) {
            return (($this->http->XPath->query("//text()[{$this->contains($this->t('Updated flight itinerary'))}]")->length > 0
                    || $this->http->XPath->query("//text()[{$this->contains($this->t('New flight itinerary'))}]")->length > 0)
                    && $this->http->XPath->query("//text()[{$this->contains($this->t('Confirmation code'))}]")->length > 0)
                    || ($this->http->XPath->query("//text()[{$this->contains($this->t('Original flight itinerary'))}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->contains($this->t('Confirmation code'))}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($this->t('canceled'))}]")->length > 0);
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]reservation\.flyfrontier\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->ParseFlight($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseFlight(Email $email)
    {
        $f = $email->add()->flight();

        $conf = $this->http->FindSingleNode("//td[./child::p[{$this->eq($this->t('Updated flight itinerary'))}]][1]/descendant::text()[{$this->eq($this->t('Confirmation code:'))}][1]/following::text()[normalize-space()][1]", null, true, "/^([A-Z\d]{5,8})$/");

        if ($conf === null){
            $conf = $this->http->FindSingleNode("//td[./child::p[{$this->eq($this->t('Original flight itinerary'))}]][1]/descendant::text()[{$this->eq($this->t('Confirmation code:'))}][1]/following::text()[normalize-space()][1]", null, true, "/^([A-Z\d]{5,8})$/");
        }

        $f->general()
            ->confirmation($conf);

        $traveller = $this->http->FindSingleNode("//text()[{$this->contains($this->t('nameString'))}][1]/ancestor::tr[normalize-space()][1]", null, true, "/(?:^|^.+\.)([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\,[ ]*{$this->opt($this->t('nameString'))}/");

        if ($traveller !== null){
            $f->general()
                ->traveller($traveller, false);
        }


        $segments = $this->http->XPath->query("//td[./child::p[{$this->eq($this->t('New flight itinerary'))}]][1]/descendant::text()[{$this->eq($this->t('Frontier flight number:'))}]");

        if ($segments->length === 0){
            $segments = $this->http->XPath->query("//td[./child::p[{$this->eq($this->t('Updated flight itinerary'))}]][1]/descendant::text()[{$this->eq($this->t('Frontier flight number:'))}]");
        }

        $cancelled = $this->http->XPath->query("//*[{$this->contains($this->t('canceled'))}]");

        if ($segments->length === 0 && $cancelled->length > 0){
            $f->general()
                ->cancelled()
                ->status('Cancelled');

            $segments = $this->http->XPath->query("//td[./child::p[{$this->eq($this->t('Original flight itinerary'))}]][1]/descendant::text()[{$this->eq($this->t('Frontier flight number:'))}]");
        }

        foreach ($segments as $segment){
            $s = $f->addSegment();

            $s->airline()
                ->name("F9")
                ->number($this->http->FindSingleNode("./following::text()[normalize-space()][1]", $segment, true, "/^\#[ ]*(\d{1,5})$/"));

            $routeString = $this->http->FindSingleNode("./preceding::text()[normalize-space()][1]", $segment);

            if (preg_match("/^(?<depCode>[A-Z]{3})?(?:[ ]*\-[ ]*)?(?<depName>.+)?[ ]*\b{$this->opt($this->t('to'))}\b[ ]*(?<arrCode>[A-Z]{3})?(?:[ ]*\-[ ]*)?(?<arrName>.+)?$/u", $routeString, $m)){
                if (isset($m['depName']) && !empty($m['depName'])){
                    $s->departure()
                        ->name($m['depName']);
                }

                if (isset($m['depCode']) && !empty($m['depCode'])){
                    $s->departure()
                        ->code($m['depCode']);
                } else {
                    $s->departure()
                        ->noCode();
                }

                if (isset($m['arrName']) && !empty($m['arrName'])){
                    $s->arrival()
                        ->name($m['arrName']);
                }

                if (isset($m['arrCode']) && !empty($m['arrCode'])){
                    $s->arrival()
                        ->code($m['arrCode']);
                } else {
                    $s->arrival()
                        ->noCode();
                }
            } else {
                $routeString = $this->http->FindSingleNode("//p[{$this->starts($this->t('The schedule for your trip to'))}][1]/descendant::text()[normalize-space()][1]");

                if (preg_match("/^{$this->opt($this->t('The schedule for your trip to'))}[ ]*(?<arrCode>[A-Z]{3})[ ]*\-[ ]*(?<arrName>.+)[ ]*{$this->opt($this->t('has changed.'))}/u", $routeString, $m)) {
                    $s->departure()
                        ->noCode();

                    $s->arrival()
                        ->code($m['arrCode'])
                        ->name($m['arrName']);
                }
            }

            $depDate = $this->http->FindSingleNode("./following::text()[{$this->eq($this->t('Estimated departure:'))}][1]/following::text()[normalize-space()][1]", $segment, true, "/^(\w+[ ]*\d{1,2}\,[ ]*\d{4}[\, ]*\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)$/");

            if ($depDate === null) {
                $depDate = $this->http->FindSingleNode("./following::text()[{$this->eq($this->t('Original departure:'))}][1]/following::text()[normalize-space()][1]", $segment, true, "/^(\w+[ ]*\d{1,2}\,[ ]*\d{4}[\, ]*\d{1,2}\:\d{2}[ ]*[Aa]?[Pp]?[Mm]?)$/");
            }

            $s->departure()
                ->date(strtotime($depDate));

            $s->arrival()
                ->noDate();
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
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
            return str_replace(' ', '\s+', preg_quote($s));
        }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            //May 02, 2022, 6:48 PM
            "#^(\w+)\s*(\d+)\,\s*(\d{4})\,\s*([\d\:]+\s*A?P?M)$#u",
        ];
        $out = [
            "$2 $1 $3, $4",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }
}
