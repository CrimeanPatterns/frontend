<?php

namespace AwardWallet\Engine\fseasons\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class UpcomingStay extends \TAccountChecker
{
    public $mailFiles = "fseasons/it-921790099.eml";
    public $subjects = [
        'Personalize Your Upcoming Stay at',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'We can’t wait to welcome you to' => ['We can’t wait to welcome you to', 'We can’t wait to welcome you back to'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@fourseasons.com') !== false) {
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
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Four Seasons Hotels Limited')]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your upcoming stay'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Dates'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Room'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('view room details'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Prepare for your arrival'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]fourseasons\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->ParseHotel($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseHotel(Email $email)
    {
        $h = $email->add()->hotel();

        $h->general()
            ->confirmation($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Confirmation # -')]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Confirmation # -'))}\s*(\d{8,})/"))
            ->traveller($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Dear')]", null, true, "/{$this->opt($this->t('Dear'))}\s*([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\,/"));

        $h->hotel()
            ->name($this->http->FindSingleNode("//text()[{$this->contains($this->t('We can’t wait to welcome you to'))}]", null, true, "/{$this->opt($this->t('We can’t wait to welcome you to'))}\s*(.+)\./"))
            ->noAddress();

        $dates = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Dates -')]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Dates -'))}\s*(.+)/");

        if (preg_match("/^(?<inDate>.+\d{4})\s+{$this->opt($this->t('to'))}\s+(?<outDate>.+\d{4})$/", $dates, $m)) {
            $h->booked()
                ->checkIn(strtotime($m['inDate']))
                ->checkOut(strtotime($m['outDate']));
        }

        $roomDescription = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Room -')]/ancestor::tr[1]", null, true, "/{$this->opt($this->t('Room -'))}\s+(.+)/");

        if (!empty($roomDescription)) {
            $h->addRoom()->setDescription($roomDescription);
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
