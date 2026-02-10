<?php

namespace AwardWallet\Engine\meitre\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class EventConfirmation extends \TAccountChecker
{
    public $mailFiles = "meitre/it-879918253.eml, meitre/it-879918399.eml, meitre/it-883885457.eml, meitre/it-887279272.eml, meitre/it-888592618.eml, meitre/it-889185309.eml, meitre/it-890753020.eml, meitre/it-890840529.eml, meitre/it-892158090.eml, meitre/it-892726504.eml, meitre/it-893649042.eml, meitre/it-893957195.eml, meitre/it-895600035.eml";

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'AT'                        => 'AT',
            'FOR'                       => 'FOR',
            'PEOPLE'                    => ['PERSON', 'person', 'PEOPLE', 'people'],
            'We are writing to confirm' => ['We are writing to confirm', 'This time we are writing'],
            'Telephone'                 => ['Telephone', 'Tel.'],
            'minutes'                   => 'minutes',
        ],
        'es' => [
            'AT'                              => 'A LAS',
            'FOR'                             => 'PARA',
            'PEOPLE'                          => ['PERSONA', 'persona', 'PERSONAS', 'personas'],
            'Booking'                         => ['Reserva', 'booking'],
            'Your reservation at'             => 'Su reserva en',
            'is'                              => 'está',
            'cancelled'                       => 'cancelada',
            'We are writing to confirm'       => ['Le estamos escribiendo para confirmar', 'Su reserva cambió'],
            'Telephone'                       => 'Teléfono',
            'Address'                         => 'Dirección',
            'SEE MAP'                         => 'VER MAPA',
            'ATTENTION'                       => 'ATENCIÓN',
            'minutes'                         => 'minutos',
            'Cancellation and changes policy' => 'Política de modificaciones y cancelaciones',
        ],
        'pt' => [
            'AT'                        => 'ÀS',
            'FOR'                       => 'PARA',
            'PEOPLE'                    => ['PESSOA', 'pessoa', 'PESSOAS', 'pessoas'],
            'Booking'                   => ['Reserva', 'booking'],
            'Your reservation at'       => 'A sua reserva no',
            'is'                        => 'está',
            'cancelled'                 => 'cancelada',
            'We are writing to confirm' => [
                'Estamos escrevendo para confirmar',
                'Desta vez estamos escrevendo para confirmar',
                'Obrigado pela sua compreensão',
                'Sua reserva mudou',
                'Recebemos suas anotações',
            ],
            'Telephone'                       => 'Telefone',
            'Address'                         => 'Endereço',
            'ATTENTION'                       => 'ATENÇÃO',
            'minutes'                         => 'minutos',
            'Cancellation and changes policy' => 'Política de cancelamento e alterações',
        ],
    ];

    private $detectSubjects = [
        'en' => [
            'Confirm your reservation at',
            'Your reservation has been confirmed',
            'Remember your reservation at',
            'Your reservation has been cancelled',
        ],
        'es' => [
            'Confirme su reserva en',
            'Su reserva fue confirmada',
            'Nuevos detalles de su reserva',
            'Su reserva ha sido cancelada',
        ],
        'pt' => [
            'Confirmar a sua reserva no',
            'Sua reserva foi confirmada',
            'Lembre-se a sua reserva no',
            'Novos detalhes para sua reserva',
            'Sua reserva foi cancelada',
            'Recebemos suas anotações',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]meitre\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if (empty($headers['from']) || stripos($headers["from"], 'meitre.com') === false) {
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

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (
            $this->http->XPath->query("//a/@href[{$this->contains('meitre.com')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('meitre.com')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->assignLang()
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Cancellation and changes policy'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseEvent(\PlancakeEmailParser $parser, Email $email)
    {
        $e = $email->add()->event();
        $e->type()->restaurant();

        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:\s*[AaPp](?:\.\s*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.  |  3pm  |  00:00
        ];

        // collect reservation confirmation from subject
        if (preg_match("/(?<desc>{$this->opt($this->t('Booking'))})\s+(?<confNumber>\w+)\s*$/iu", $parser->getHeader('subject'), $m)) {
            $e->general()->confirmation($m['confNumber'], $m['desc']);
        }

        // collect reservation confirmation from json
        $jsonText = $this->http->FindSingleNode("//script[{$this->contains($this->t('reservationNumber'))}]");

        if (!empty($jsonText)) {
            $jsonObj = json_decode($jsonText);

            if (empty($e->getConfirmationNumbers())) {
                $reservationNumber = $this->re("/.+?(\d{6,}|\w{6}$)/", $jsonObj->reservationNumber);

                if (!empty($reservationNumber)) {
                    $e->general()->confirmation($reservationNumber, 'reservationNumber');
                }
            }

            if (empty($e->getStatus())) {
                $reservationStatus = $this->re("/\/(\w+)$/", $jsonObj->reservationStatus);

                if (!empty($reservationStatus)) {
                    $e->general()->status($reservationStatus);
                }
            }
        }

        // if no reservation confirmation
        if (empty($e->getConfirmationNumbers())) {
            $e->general()->noConfirmation();
        }

        // collect event name (restaurant) and reservation status (if "confirmed")
        $nameAndStatusText = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your reservation at'))}]");

        if (preg_match("/^\s*{$this->opt($this->t('Your reservation at'))}\s+(?<name>.+?)(?:\s+{$this->opt($this->t('is'))}\s+(?<status>\w+)\s.+)?\s*$/u", $nameAndStatusText, $m)) {
            $e->place()->name($m['name']);

            if (!empty($m['status'])) {
                $e->general()->status($m['status']);
            }
        }

        if (empty($e->getName())) {
            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Telephone'))}]/preceding::text()[normalize-space()][1]")
                ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('Address'))}]/preceding::text()[normalize-space()][1]");
            $e->place()->name(trim($name));
        }

        // collect reservation status (if "cancelled")
        if (empty($e->getStatus())) {
            $status = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your reservation at'))}]/following::text()[normalize-space()][1]", null, true, "/({$this->opt($this->t('cancelled'))})/");

            if (!empty($status)) {
                $e->general()->status($status);
                $e->general()->cancelled();
            }
        }

        // collect address
        $address = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Address'))}])[last()]", null, true, "/^\s*{$this->opt($this->t('Address'))}\:\s+(.+?)\s*$/u")
            // it-887279272.eml
            ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('SEE MAP'))}]", null, true, "/^\s*(.+?)\s*{$this->opt($this->t('SEE MAP'))}/u");

        if (!empty($address)) {
            $e->place()->address($address);
        }

        // collect start date and noEnd
        $startDate = $this->normalizeDate($this->http->FindSingleNode("(//text()[{$this->contains($e->getStatus())}])[last()]/following::text()[normalize-space()][1]"))
            // it-879918253.eml
            ?? $this->normalizeDate($this->http->FindSingleNode("(//text()[{$this->contains($this->t('We are writing to confirm'))}])[last()]/following::text()[normalize-space()][1]"))
            // it-892726504.eml
            ?? $this->normalizeDate($this->http->FindSingleNode("(//text()[{$this->contains($this->t('We are writing to confirm'))}])[last()]/following::text()[normalize-space()][2]"));

        $startTime = $this->http->FindSingleNode("//text()[{$this->eq($this->t('AT'))}]/ancestor::td[1]", null, true, "/^\s*{$this->opt($this->t('AT'))}\s+({$patterns['time']})(?:\s*h)?\s*$/");

        $e->booked()->start(strtotime($startTime, $startDate));
        $e->booked()->noEnd();

        // collect travellers
        $traveller = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Your reservation at'))}]/preceding::text()[normalize-space()][1]", null, true, "/^\s*(?!.*{$this->opt($this->t('PEOPLE'))})([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*\,?\s*$/u")
            // it-879918253.eml
            ?? $this->http->FindSingleNode("(//text()[{$this->contains($this->t('We are writing to confirm'))}])[1]/preceding::text()[normalize-space()][1]", null, true, "/^\s*(?!.*{$this->opt($this->t('PEOPLE'))})([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*\,?\s*$/u")
            // it-892726504.eml
            ?? $this->http->FindSingleNode("(//text()[{$this->contains($this->t('We are writing to confirm'))}])[1]/following::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/u");

        if (!empty($traveller)) {
            $e->general()->traveller($traveller);
        }

        // collect phone
        $phone = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Telephone'))}]", null, true, "/^\s*{$this->opt($this->t('Telephone'))}\:?\s+([\+\-\(\)\d\s]+?)\s*$/");

        if (!empty($phone)) {
            $e->place()->phone(preg_replace("/\s+/", '', $phone));
        }

        // collect guest count
        $guestCount = $this->http->FindSingleNode("//text()[{$this->eq($this->t('FOR'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\d+)\s*$/");

        if (!empty($guestCount)) {
            $e->booked()->guests($guestCount);
        }

        // collect notes (tolerance time)
        $notes = $this->http->FindNodes("(//text()[{$this->eq($this->t('ATTENTION'))}]/ancestor::*[count(descendant::text())>2])[last()]/descendant::text()[normalize-space()][position()>1]");

        // union all phrases with 'minutes'
        $minutesPhrases = [];

        foreach (self::$dictionary as $dict) {
            $minutesPhrases = array_unique(array_merge($minutesPhrases, (array) $dict['minutes']));
        }

        foreach ($notes as $note) {
            if ($this->striposAll($note, $this->t('Please consider an extra'))) {
                continue;
            }

            $toleranceNote = $this->re("/^(?:.*[\.\!\?\•\-]\s+)?(.*?\d+\s+{$this->opt($minutesPhrases)}.*?)(?:\.|$)/i", $note);

            if (!empty($toleranceNote)) {
                $e->general()->notes($toleranceNote);

                break;
            }
        }
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }
        $this->parseEvent($parser, $email);

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
            if (!empty($dict['AT']) && !empty($dict['FOR']) && !empty($dict['PEOPLE'])) {
                if ($this->http->XPath->query("//*[{$this->eq($dict['AT'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->eq($dict['FOR'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->eq($dict['PEOPLE'])}]")->length > 0
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $in = [
            // Tuesday, 27 May 2025
            "/^\s*\w+\s*\,\s*(\d+)\s+([^\d\s]+)\s+(\d{4})\s*$/iu",
        ];
        $out = [
            "$1 $2 $3",
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = strtotime(str_replace($m[1], $en, $date));

                return $date;
            }
        }

        return null;
    }

    private function striposAll($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (stripos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && stripos($text, $needle) !== false) {
            return true;
        }

        return false;
    }
}
