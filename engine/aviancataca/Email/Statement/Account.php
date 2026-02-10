<?php

namespace AwardWallet\Engine\aviancataca\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class Account extends \TAccountChecker
{
    public $mailFiles = "aviancataca/statements/it-578870226.eml, aviancataca/statements/it-601277507.eml, aviancataca/statements/it-911592437.eml, aviancataca/statements/it-912034293.eml, aviancataca/statements/it-912521437.eml, aviancataca/statements/it-912590816.eml, aviancataca/statements/it-912593811.eml";

    public $lang;

    public static $dictionary = [
        'en' => [
            // detects
            'confNumber' => [
                'Booking code',
                'Booking reference',
                'Flight Boarding',
                'RECORD LOCATOR',
                'Reference number (PNR)',
                'Reservation Code',
                'Your reservation code',
            ],
            'flightInfo' => [
                'Manage your booking',
                'Booking Details',
                'Ticket details',
                'TRIP INFORMATION',
                'Trip Information',
                'Flight Details',
                'flight information',
                'Itinerary',
                'itinerary',
            ],

            // assign lang and main
            'accountNumber' => [
                'Your LifeMiles Number:',
                'Your lifemiles number:',
                'Your LifeMiles number is:',
                'Your lifemiles number is:',
                'your LifeMiles number is:',
                'your lifemiles number is:',
            ],
            'Status' => [
                'Status:',
                'Your status is:',
                'your current status is',
            ],
            'Hello' => [
                'Hello', 'Hi',
            ],
        ],
        'es' => [
            // detects
            'confNumber' => [
                'Referencia de la reserva',
                'Código da Reserva',
                'Código de reserva',
                'Embarque',
                'LOCALIZADOR',
            ],
            'flightInfo' => [
                'Gestiona tu reserva',
                'INFORMACIÓN DEL VIAJE',
                'Información del viaje',
                'Itinerario',
                'itinerario',
            ],

            // assign lang and main
            'accountNumber' => [
                'Tu número LifeMiles:',
                'Tu número lifemiles:',
                'Tu número LifeMiles es:',
                'tu número LifeMiles es:',
                'Tu número lifemiles es:',
                'tu número lifemiles es:',
                'número de lifemiles es:',
            ],
            'Status' => [
                'Estatus:',
                'Tu estatus es:',
                'tu estatus actual es',
            ],
            'Hello' => [
                'Hola',
            ],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]lifemiles\.com$/", $from) > 0;
    }

    // not used, large number of subjects
    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains(['Avianca', 'avianca'])}]")->length === 0
            && $this->http->XPath->query("//img[{$this->contains('avianca', '@alt')} or {$this->contains('avianca', '@title')}]")->length === 0
        ) {
            return false;
        }

        $this->assignLang();

        // detect Format
        if (!empty($this->lang) && !empty(self::$dictionary[$this->lang]["confNumber"]) && !empty(self::$dictionary[$this->lang]["flightInfo"])
            && $this->http->XPath->query("//text()[{$this->contains($this->t('confNumber'))}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('flightInfo'))}]")->length === 0
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
        $this->parseStatementHtml($email);

        return $email;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function parseStatementHtml(Email $email)
    {
        $st = $email->add()->statement();

        $accountText = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('accountNumber'))}])[1]/ancestor::*[contains(translate(., '0123456789', 'dddddddddd'), 'ddddd')][1]");
        $name = null;

        if (preg_match("/^\s*(?:(?<name>[[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*)?{$this->opt($this->t('accountNumber'))}[\:\s]*(?<number>\d{5,})\s*$/u", $accountText, $m)) {
            $st->addProperty('Number', $m['number']);
            $st->setLogin($m['number']);

            if (!empty($m['name'])) {
                $name = $m['name'];
            }
        }

        $name = $name ?? $this->http->FindSingleNode("(//text()[{$this->contains($this->t('Hello'))}])[1]", null,
            false, "/^{$this->opt($this->t('Hello'))}\s+([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\,/u");

        if (!empty($name)) {
            $st->addProperty('Name', $name);
        }

        $tier = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Status'))}]", null, true, "/^\s*{$this->opt($this->t('Status'))}[\:\s]*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/")
            ?? $this->http->FindSingleNode("//img/@alt[{$this->contains($this->t('Status'))}]", null, true, "/^\s*{$this->opt($this->t('Status'))}[\:\s]*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/")
            ?? $this->http->FindSingleNode("//img/@title[{$this->contains($this->t('Status'))}]", null, true, "/^\s*{$this->opt($this->t('Status'))}[\:\s]*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/");

        if (!empty($tier)) {
            $st->addProperty('EliteStatus', $tier);
        }

        $st->setNoBalance(true);

        return true;
    }

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["accountNumber"]) && !empty($dict["Status"])) {
                if ($this->http->XPath->query("//text()[{$this->contains($dict['accountNumber'])}]")->length > 0
                    && ($this->http->XPath->query("//text()[{$this->contains($dict['Status'])}]")->length > 0
                        || $this->http->XPath->query("//img[{$this->contains($dict['Status'], '@alt')} or {$this->contains($dict['Status'], '@title')}]")->length > 0)
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
