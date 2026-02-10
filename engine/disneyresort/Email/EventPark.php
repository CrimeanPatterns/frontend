<?php

namespace AwardWallet\Engine\disneyresort\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class EventPark extends \TAccountChecker
{
    public $mailFiles = "disneyresort/it-881755024.eml, disneyresort/it-887720551.eml";
    public $subjects = [
        'Walt Disney World',
    ];

    public $lang = '';

    public $detectLang = [
        "en" => ["View Itinerary in My Plans"],
        "it" => ["Ver Itinerario en Mis Planes"],
    ];

    public static $dictionary = [
        "en" => [
        ],

        "it" => [
            'Thank You. Your Order Is'   => 'Gracias. Se confirmó tu',
            'Order Date:'                => 'Fecha del Pedido:',
            'View Itinerary in My Plans' => 'Ver Itinerario en Mis Planes',
            '(Age'                       => '(Edad',
            'Confirmation Number:'       => 'Número de Confirmación:',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@wdw.disneyonline.com') !== false) {
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

        if ($this->http->XPath->query("//a[contains(@href, 'https://disneyworld.disney.go.com/')]")->length === 0
            && $this->http->XPath->query("//text()[contains(normalize-space(), 'Walt Disney World Resort')]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Thank You. Your Order Is'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Order Date:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('View Itinerary in My Plans'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('(Age'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]wdw\.disneyonline\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        $this->Event($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function Event(Email $email)
    {
        $e = $email->add()->event();
        $e->setEventType(EVENT_EVENT);
        $e->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->starts($this->t('Confirmation Number:'))}]", null, true, "/{$this->opt($this->t('Confirmation Number:'))}\s*(\d{5,})$/"))
            ->travellers($this->http->FindNodes("//text()[{$this->contains($this->t('(Age'))}]", null, "/^(.+)\s*{$this->opt($this->t('(Age'))}/"));

        $eInfo = $this->http->FindNodes("//text()[contains(normalize-space(), 'AM') or contains(normalize-space(), 'PM')]/ancestor::*[1][not(contains(normalize-space(), 'Sent'))]/descendant::text()[normalize-space()]");

        if (count($eInfo) === 3) {
            $e->setStartDate(strtotime($eInfo[1] . ', ' . $eInfo[2]))
                ->setNoEndDate(true);
        }

        $e->setAddress("1780 East Buena Vista Drive Disney Springs, Orlando, FL 32830");
        $e->setName($this->http->FindSingleNode("//text()[contains(normalize-space(), 'AM') or contains(normalize-space(), 'PM')]/ancestor::*[1][not(contains(normalize-space(), 'Sent'))]/preceding::text()[normalize-space()][1]"));

        $e->setGuestCount(count($e->getTravellers()));
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

    private function assignLang()
    {
        foreach ($this->detectLang as $lang => $array) {
            foreach ($array as $value) {
                if ($this->http->XPath->query("//text()[{$this->contains($value)}]")->length > 0) {
                    $this->lang = $lang;
                }
            }
        }

        return false;
    }
}
