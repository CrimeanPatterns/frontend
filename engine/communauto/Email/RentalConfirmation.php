<?php

namespace AwardWallet\Engine\communauto\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class RentalConfirmation extends \TAccountChecker
{
    public $mailFiles = "communauto/it-868499590.eml, communauto/it-868866624.eml, communauto/it-871472727.eml, communauto/it-873927912.eml, communauto/it-874381798.eml";

    public $lang;

    public static $dictionary = [
        'en' => [
            'title' => [
                'Your reservation confirmation', 'your reservation confirmation',
                'Modification confirmation of your reservation',
                'Confirmation of the cancellation of your reservation',
                'reservation cancelled',
            ],
            'by phone'               => ['by phone'],
            'New reservation number' => ['New reservation number'],
            'Start date/hour'        => ['Start date/hour'],
            'Car information'        => ['Car information', 'Car informations'],
        ],
        'fr' => [
            'title' => [
                'Confirmation de la modification de votre réservation',
                'Confirmation de votre réservation',
            ],
            'Hi'                     => ['Bonjour'],
            'by phone'               => ['par téléphone'],
            'New reservation number' => ['Nouveau # de réservation'],
            'Car information'        => ['Infos du véhicule'],
            'Start date/hour'        => ['Date/heure de début'],
            'End date/hour'          => ['Date/heure de fin'],
            'Car station'            => ['Station du véhicule'],
        ],
    ];

    private $detectSubjects = [
        'en' => [
            'Confirmation of your reservation starting on',
            'Your reservation confirmation starting on',
            'Modification confirmation of your reservation starting on',
            'Confirmation of the cancellation of your reservation starting on',
            'Reservation cancelled starting on',
        ],
        'fr' => [
            'Confirmation de votre réservation',
            'Confirmation de modification de votre réservation',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]communauto\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers['from'], 'communauto.com') === false) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubject) {
                if (stripos($headers["subject"], $dSubject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $this->assignLang();

        // detect Provider
        if (
            $this->http->XPath->query("//a/@href[{$this->contains('communauto.com')}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains('Communauto')}]")->length === 0
        ) {
            return false;
        }

        if (
            $this->http->XPath->query("//text()[{$this->starts($this->t('Car information'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Start date/hour'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Car station'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }

        $this->parseRental($email);

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

    public function parseRental(Email $email)
    {
        $r = $email->add()->rental();

        // collect reservation confirmation
        $confirmationText = $this->http->FindSingleNode("//text()[{$this->starts($this->t('New reservation number'))}]/ancestor::*[count(descendant::text()[normalize-space()])=2][normalize-space()][1]")
            ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('confirmation of your reservation'))}][1]");

        if (preg_match("/^(?<desc>{$this->opt($this->t('New reservation number'))})[\:\s]*(?<number>\d+)\s*$/mi", $confirmationText, $m)
            || preg_match("/^.*?(?<desc>{$this->opt($this->t('confirmation of your reservation'))})\s*\#?(?<number>\d+)\:\s*$/mi", $confirmationText, $m)
        ) {
            $r->general()
                ->confirmation($m['number'], $m['desc']);
        }

        // example: it-871472727.eml
        if (empty($confirmationText)) {
            $confirmationText = $this->http->FindSingleNode("//text()[{$this->contains($this->t('has been cancelled'))}]");

            if (preg_match("/(?<desc>{$this->opt($this->t('reservation'))})\s*\#(?<number>\d+)\s.+?(?<cancelledStatus>{$this->opt($this->t('cancelled'))})/", $confirmationText, $m)) {
                $r->general()
                    ->confirmation($m['number'], $m['desc'])
                    ->status($m['cancelledStatus'])
                    ->cancelled();
            }
        }

        // collect pickUp and dropOff dateTimes
        $pickUpDateTime = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Start date/hour'))}]/following::text()[normalize-space()][1]");
        $r->pickup()->date($this->normalizeDate($pickUpDateTime));

        $dropOffDateTime = $this->http->FindSingleNode("//text()[{$this->starts($this->t('End date/hour'))}]/following::text()[normalize-space()][1]");
        $r->dropoff()->date($this->normalizeDate($dropOffDateTime));

        // collect pickUp and dropOff locations
        $location = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Car station'))}]/following::text()[normalize-space()][1]", null, true);
        $r->pickup()->location($location);
        $r->dropoff()->same();

        // collect traveller
        $traveller = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('title'))}])[last()]/following::text()[normalize-space()][1]", null, true, "/^{$this->opt($this->t('Hi'))}?\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\,\s*$/u");

        if (!empty($traveller) && !in_array($traveller, (array) $this->t('Hi'))) {
            $r->general()->traveller($traveller, true);
        }

        // collect car model
        $carModel = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Car information'))}]/following::text()[normalize-space()][1]", null, true, "/^\d+[\s\/]+(.+?)[\s\/]+\w+$/");

        if (!empty($carModel)) {
            $r->car()->model($carModel);
        }
    }

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['title']) && !empty($dict['by phone'])) {
                if ($this->http->XPath->query("//*[{$this->eq($dict['title'])}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->contains($dict['by phone'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $in = [
            '/^\s*(\w+)\s+(\d+)\,\s+(\d{4})\s+(\d+\:\d+)\s*$/iu', // Feb 21, 2025 15:00
            '/^\s*(\d+)\s+([^\d\s]+)\.\s+(\d{4})\s+(\d+\:\d+)\s*$/ui', // 6 fév. 2025 18:00
        ];
        $out = [
            '$2 $1 $3, $4',
            '$1 $2 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = strtotime(str_replace($m[1], $en, $date));
            }
        }

        return $date;
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
}
