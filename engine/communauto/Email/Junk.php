<?php

namespace AwardWallet\Engine\communauto\Email;

use AwardWallet\Schema\Parser\Email\Email;

class Junk extends \TAccountChecker
{
    public $mailFiles = "communauto/it-868491608.eml, communauto/it-868738459.eml, communauto/it-870713320.eml, communauto/it-874519476.eml";

    public $lang;

    public static $dictionary = [
        'en' => [
            // exist in all letters
            'title'             => ['End trip confirmation', 'END TRIP CONFIRMATION'],
            'trip with vehicle' => ['The vehicle was used', 'Vehicle used starting'],
        ],
        'fr' => [
            // exist in all letters
            'title'                  => ['confirmation de fin de trajet', 'Confirmation de la fin de trajet'],
            'trip with vehicle'      => ['Vehicule utilisé', 'Le véhicule a été utilisé'],
            // not in all letters
            'New reservation number' => ['Nouveau # de réservation'],
            'Car information'        => ['Infos du véhicule'],
            'Start date/hour'        => ['Date/heure de début'],
            'End date/hour'          => ['Date/heure de fin'],
            'Car station'            => ['Station du véhicule'],
            // exist in letters of Type 1
            'Travel time'  => 'Durée du trajet',
            'Billed time'  => 'Temps facturé',
            'Distance'     => 'Distance',
            'Applied rate' => 'Tarif appliqué',
            // exist in letters of Type 2
            'Trip details'                                => 'Détails du trajet',
            'From'                                        => 'Du',
            'To'                                          => 'Au',
            'In what condition did you find the vehicle?' => 'Dans quel état avez-vous trouvé le véhicule ?',
        ],
    ];

    private $detectSubjects = [
        'en' => [
            'End trip confirmation',
        ],
        'fr' => [
            'Confirmation de fin de trajet',
            'Confirmation de la fin de',
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

        // detect Format
        if (
            $this->http->XPath->query("//text()[{$this->starts($this->t('New reservation number'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('confirmation of your reservation'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Car information'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Start date'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('End date'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Car station'))}]")->length === 0
            && (
                // Letters Type 1
                (
                    $this->http->XPath->query("//text()[{$this->starts($this->t('Travel time'))}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->starts($this->t('Billed time'))}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->starts($this->t('Distance'))}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->starts($this->t('Applied rate'))}]")->length > 0
                )
                // Letters Type 2
                || (
                    $this->http->XPath->query("//text()[{$this->starts($this->t('Trip details'))}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->starts($this->t('From'))}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->starts($this->t('To'))}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->starts($this->t('In what condition did you find the vehicle?'))}]")->length > 0
                )
            )
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

        if ($this->detectEmailByBody($parser) === true) {
            $email->setIsJunk(true, "Rental info without reservation number, places and vehicle");
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

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict['title']) && !empty($dict['trip with vehicle'])) {
                if ($this->http->XPath->query("//*[{$this->eq($dict['title'])}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->starts($dict['trip with vehicle'])}]")->length > 0
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
}
