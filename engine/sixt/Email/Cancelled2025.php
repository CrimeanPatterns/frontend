<?php

namespace AwardWallet\Engine\sixt\Email;

// TODO: delete what not use
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class Cancelled2025 extends \TAccountChecker
{
    public $mailFiles = "sixt/it-899581379.eml, sixt/it-901668511.eml, sixt/it-901971020.eml";

    public $lang;
    public static $dictionary = [
        'en' => [
            'Booking:'                                        => 'Booking:',
            'Hello'                                           => 'Hello',
            'Your booking is canceled'                        => 'Your booking is canceled',
            'As requested, we have canceled your booking for' => 'As requested, we have canceled your booking for',
            'at'                                              => 'at', // %date% at %location%
        ],
        'de' => [
            'Booking:'                                        => 'Buchung:',
            'Hello'                                           => 'Hallo',
            'Your booking is canceled'                        => 'Ihre Buchung musste storniert werden',
            'As requested, we have canceled your booking for' => 'Bedauerlicherweise musste Ihre Buchung für',
            'at'                                              => 'für', // %date% at %location%
        ],
        'pt' => [
            'Booking:'                                        => 'Reserva:',
            'Hello'                                           => 'Olá',
            'Your booking is canceled'                        => 'A sua reserva foi cancelada',
            'As requested, we have canceled your booking for' => 'Conforme solicitado, cancelámos a sua reserva para',
            'at'                                              => 'às', // %date% at %location%
        ],
        'fr' => [
            'Booking:'                                        => 'Réservation :',
            'Hello'                                           => 'Bonjour',
            'Your booking is canceled'                        => 'Votre réservation est annulée',
            'As requested, we have canceled your booking for' => 'Comme demandé, nous avons annulé votre réservation pour le',
            'at'                                              => 'à', // %date% at %location%
        ],
    ];

    private $detectFrom = "@sixt.com";
    private $detectSubject = [
        // en
        'Your booking is canceled - ',
        // de
        'Ihre Buchung musste storniert werden - ',
        // pt
        'A sua reserva foi cancelada - ',
        // fr
        'Votre réservation est annulée - ',
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]sixt\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//a/@href[{$this->contains(['.sixt.com'])}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Get the SIXT app', 'Sixt rent a car Srl'])}]")->length === 0
        ) {
            return false;
        }

        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["Your booking is canceled"])) {
                if ($this->http->XPath->query("//*[{$this->contains($dict['Your booking is canceled'])}]")->length > 0
                ) {
                    return true;
                }
            }
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
        $this->parseEmailHtml($email);

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
            if (!empty($dict["Booking:"]) && !empty($dict["Your booking is canceled"])) {
                if ($this->http->XPath->query("//*[{$this->contains($dict['Booking:'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($dict['Your booking is canceled'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function parseEmailHtml(Email $email)
    {
        $r = $email->add()->rental();

        $r->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Booking:'))}]/following::text()[normalize-space()][1]",
                null, true, "/^\s*(\d{5,})\s*$/"))
            ->traveller($this->http->FindSingleNode("//text()[{$this->eq($this->t('Hello'))}]/ancestor::td[1]",
                null, true, "/^\s*{$this->opt($this->t('Hello'))}\s+(\D+),\s*$/u"))
            ->status('Cancelled')
            ->cancelled()
        ;

        $text = $this->http->FindSingleNode("//text()[{$this->eq($this->t('As requested, we have canceled your booking for'))}]/following::text()[normalize-space()][1]");

        if (preg_match("/^(?<date>.+) {$this->opt($this->t('at'))} (?<location>.+)$/u", $text, $m)) {
            $r->pickup()
                ->location($m['location'])
                ->date($this->normalizeDate($m['date']));
        }

        return true;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    // additional methods
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
        $this->logger->debug('date begin = ' . print_r($date, true));
        $in = [
            // Apr 23, 2025 at 9 AM
            '/^\s*([[:alpha:]]+)\s+(\d{1,2})\s*[,\s]\s*(\d{4})\s+\D+\s+(\d{1,2})\s*([ap]m)\s*$/iu',
            // Abr 27, 2025 às 10:30
            '/^\s*([[:alpha:]]+)\s+(\d{1,2})\s*[,\s]\s*(\d{4})\s+\D+\s+(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/iu',
            // 16. April 2025 um 11:00 Uhr
            '/^\s*(\d{1,2})[.]?\s+([[:alpha:]]+)[.]?\s+(\d{4})\s+\D+\s+(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*(?:Uhr)?\s*$/iu',
        ];
        $out = [
            '$2 $1 $3, $4:00 $5',
            '$2 $1 $3, $4',
            '$1 $2 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);
        $this->logger->debug('date replace = ' . print_r($date, true));

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        $this->logger->debug('date end = ' . print_r($date, true));

        return strtotime($date);
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
