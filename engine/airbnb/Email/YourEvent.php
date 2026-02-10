<?php

namespace AwardWallet\Engine\airbnb\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class YourEvent extends \TAccountChecker
{
    public $mailFiles = "airbnb/it-909100984.eml";
    public $subjects = [
        '/^(?:Confirmed: Your experience on|Get ready for your experience in)/',
    ];

    public $lang = 'en';
    public $headers = false;
    public $year;

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@airbnb.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (preg_match($subject, $headers['subject'])) {
                    $this->headers = true;

                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        return $this->http->XPath->query("//text()[contains(normalize-space(), 'Airbnb')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Reservation details'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Show more details'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Hosted by'))}]")->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]airbnb\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->headers === true) {
            $this->year = date('Y', strtotime($parser->getDate()));
            $this->ParseEvent($email);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseEvent(Email $email)
    {
        $e = $email->add()->event();

        $e->setEventType(EVENT_EVENT);

        $e->general()
            ->confirmation($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Reservation code:')]", null, true, "/{$this->opt($this->t('Reservation code:'))}\s*([A-Z\d]{5,})$/"));

        $guests = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'guest')]", null, true, "/^(\d+)\s*{$this->opt($this->t('guest'))}/");

        if (!empty($guests)) {
            $e->setGuestCount($guests);
        }

        $e->setName($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Hosted by')]/preceding::text()[normalize-space()][1]"));
        $e->setAddress($this->http->FindSingleNode("//text()[normalize-space()='Reservation details']/following::text()[starts-with(normalize-space(), 'Show more details')][1]/preceding::text()[normalize-space()][1]"));

        $dateText = $this->http->FindSingleNode("//text()[normalize-space()='Reservation details']/following::text()[starts-with(normalize-space(), 'Show more details')][1]/preceding::text()[normalize-space()][2]");

        if (preg_match("/^(?<day>\w+\s*\d+)\s+at\s*(?<startTime>[\d\:]+\s*A?P?M?)\s*\–\s*(?<endTime>[\d\:]+\s*A?P?M?)\s*\(/", $dateText, $m)) {
            $e->booked()
                ->start(strtotime($m['day'] . ' ' . $this->year . ', ' . $m['startTime']))
                ->end(strtotime($m['day'] . ' ' . $this->year . ', ' . $m['endTime']));
        }

        $totalText = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Amount paid')][1]/ancestor::tr[normalize-space()][2]", null, true, "/Amount paid\s*(\(.+\d)/");

        if (preg_match("/^\((?<currency>[A-Z]{3})\)\D{1,3}(?<total>[\d\.\,\']+)$/", $totalText, $m)) {
            $e->price()
                ->total(PriceHelper::parse($m['total'], $m['currency']))
                ->currency($m['currency']);
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
}
