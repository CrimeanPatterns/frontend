<?php

namespace AwardWallet\Engine\supershuttle\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Transfer2 extends \TAccountChecker
{
    public $mailFiles = "supershuttle/it-906521085.eml";
    public $subjects = [
        'SuperShuttle Booking Confirmation',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@supershuttle.co.nz') !== false) {
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
        return $this->http->XPath->query("//text()[contains(normalize-space(), 'Super Shuttle')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Uplift From:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Dropoff To:'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]supershuttle\.co\.nz$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $t = $email->add()->transfer();

        $t->general()
            ->traveller($this->http->FindSingleNode("//text()[normalize-space()='Transfer Details']/following::text()[starts-with(normalize-space(), 'for')]/ancestor::td[1]", null, true, "/{$this->opt($this->t('for'))}\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/"))
            ->confirmation($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Booking Reference:')]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Booking Reference:'))}\s*(\d{5,})/"));

        $nodes = $this->http->XPath->query("//text()[starts-with(normalize-space(), 'Uplift From:')]/ancestor::table[1]");

        foreach ($nodes as $root) {
            $s = $t->addSegment();

            $s->departure()
                ->name($this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(), 'Uplift From:')]/ancestor::tr[1]/descendant::td[2]", $root))
                ->date(strtotime($this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(), 'Pickup Time:')]/ancestor::tr[1]/descendant::td[2]", $root)));

            $s->arrival()
                ->name($this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(), 'Dropoff To:')]/ancestor::tr[1]/descendant::td[2]", $root))
                ->noDate();
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
}
