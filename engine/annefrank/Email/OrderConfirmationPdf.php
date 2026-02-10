<?php

namespace AwardWallet\Engine\annefrank\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class OrderConfirmationPdf extends \TAccountChecker
{
	public $mailFiles = "annefrank/it-889114673.eml, annefrank/it-892996817.eml";
    public $pdfNamePattern = ".*\.pdf";

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'age' => ['Adult', '10-17 years old', '0-9 years old'],
            'adult' => ['Adult'],
            'infant' => ['0-9 years old', '10-17 years old'],
        ],
    ];

    private $detectSubject = [
        "Order confirmation for 'the Anne Frank House' (order number:",
    ];
    private $detectBody = [
        'en' => [
            'TICKET',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]annefrank\.nl/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ($this->detectEmailFromProvider($headers['from']) !== true) {
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
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if ($this->detectPdf($text) == true) {
                return true;
            }
        }

        return false;
    }

    public function detectPdf($text)
    {
        // detect provider
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Anne Frank House')]")->length === 0 &&
            $this->http->XPath->query("//text()[contains(normalize-space(), 'Balboa Vacations')]")->length === 0) {
            return false;
        }

        // detect Format
        foreach ($this->detectBody as $lang => $detectBody) {
            if ($this->containsText($text, $detectBody) !== false) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf), false);

            if ($this->detectPdf($text) == true) {
                $conf = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Your order number is:'))}]",
                    null, true, "/{$this->opt($this->t('Your order number is:'))}\s*([A-Z\d]{5,})$/");

                if (empty($conf)) {
                    $conf = $this->re("/([A-Z\d]{10,})\.pdf/", $this->getAttachmentName($parser, $pdf));
                }
                
                if (!empty($conf)){
                    $email->ota()
                        ->confirmation($conf);
                }

                $this->parseEmailPdf($email, $text);
            }
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

    private function parseEmailPdf(Email $email, ?string $textPdf = null)
    {
        $tickets = preg_split("/(\n+(?:You can enter the|Be on time, at least 5).*?(?:trolleys\.\n+))/s", $textPdf, null, PREG_SPLIT_NO_EMPTY);
        $this->logger->debug(var_export($tickets, true));
        foreach ($tickets as $ticketText) {
            $date = $this->re("/[ ]*{$this->opt($this->t('DATE'))}[ ]*\n+(\d{1,2}[ ]+[[:alpha:]]+[ ]+\d{4})[ ]*/u", $ticketText);
            $startTime = $this->re("/[ ]*(?:{$this->opt($this->t('START TIME'))}|{$this->opt($this->t('TIME SLOT'))}).*?\n+[ ]*(\d{1,2}\:\d{2})[ ]*/su", $ticketText);

            $typeText = $this->re("/[ ]*{$this->opt($this->t('TICKET'))}.*?({$this->opt($this->t('age'))})[ ]*\n+{$this->opt($this->t('PRICE'))}/s", $ticketText);

            if (preg_match("/(^({$this->opt($this->t('adult'))})$)/", $typeText)){
                $type = 'adult';
            } else if (preg_match("/(^({$this->opt($this->t('infant'))})$)/", $typeText)){
                $type = 'infant';
            }

            $price = PriceHelper::parse($this->re("/\n+ *{$this->opt($this->t('PRICE'))}[ ]{3,}€ ?(\d[\d,. ]*)[ ]*{$this->opt($this->t('ENTRANCE'))}/", $ticketText), 'EUR');

            $currency = 'EUR';

            $travellerName = trim(str_replace("\n", ' ', $this->re("/[ ]*{$this->opt($this->t('NAME'))}[ ]*\n+[ ]*(.*?)[ ]*\n+{$this->opt($this->t('DATE'))}/us", $ticketText)));

            if (!empty($date) && !empty($startTime)) {
                $eventDate = $this->normalizeDate($date . ', ' . $startTime);
            } else {
                $this->logger->debug('parsing error: datetime');
                $email->add()->event();

                continue;
            }

            $its = $email->getItineraries();
            $foundTicket = false;

            foreach ($its as $it) {
                /** @var \AwardWallet\Schema\Parser\Common\Event $it */
                if ($it->getStartDate() == $eventDate) {
                    $foundTicket = true;

                    if ($type == 'adult') {
                        $it->booked()
                            ->guests(1 + ($it->getGuestCount() ?? 0));
                    } else if ($type == 'infant') {
                        $it->booked()
                            ->kids(1 + ($it->getKidsCount() ?? 0));
                    }

                    $it->price()
                        ->total($price + $it->getPrice()->getTotal());

                    if (!in_array($travellerName, array_column($it->getTravellers(), 0))) {
                        $it->general()
                            ->traveller($travellerName, true);
                    }
                }
            }

            if ($foundTicket === true) {
                continue;
            }

            $event = $email->add()->event();
            $event
                ->type()->event();

            $notes = [];
            $notes[] = $this->re("/[ ]*({$this->opt($this->t('ENTRANCE'))}[ ]+.+)[ ]*/", $ticketText);
            $notes = implode('. ', preg_replace('/\s+/', ' ', array_filter($notes)));
            $event->general()
                ->notes($notes);

            // General
            $event->general()
                ->noConfirmation()
                ->traveller($travellerName, true);

            // Place
            $event->place()
                ->name('Anne Frank House')
                ->address('Westermarkt 20, 1016 GV Amsterdam, Netherlands')
            ;

            // Booked
            $event->booked()
                ->start($eventDate)
                ->noEnd();


            if ($type == 'adult') {
                $event->booked()
                    ->guests(1);
            } elseif ($type == 'infant') {
                $event->booked()
                    ->kids(1);
            }

            // Price
            $event->price()
                ->total($price)
                ->currency($currency);
        }

        return $email;
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function containsText($text, $needle): bool
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

    // additional methods


    private function normalizeDate(?string $date): ?int
    {
        //$this->logger->debug('date begin = ' . print_r($date, true));

        $in = [
            // 12 March 2023, 14:30
            // 12 Ma rch 2023, 14:30
            '/^\s*(\d+)\s+([[:alpha:]]+(?: ?[[:alpha:]]+)*)\s+(\d{4}),\s*(\d{1,2}:\d{2}(\s*[ap]m)?)\s*$/ui',
            // 26/03/2023
            '/^\s*(\d{1,2})\/(\d{2})\/(\d{4}),\s*(\d{1,2}:\d{2}(\s*[ap]m)?)\s*$/ui',
        ];
        $out = [
            '$1 $2 $3, $4',
            '$1.$2.$3, $4',
        ];

        if (preg_match('/^\s*(\d+\s+)([[:alpha:]]+(?: ?[[:alpha:]]+)*)(\s+\d{4}.*)\s*$/ui', $date, $m)) {
            $date = $m[1] . str_replace(' ', '', $m[2]) . $m[3];
        }
        $date = preg_replace($in, $out, $date);
//        $this->logger->debug('date replace = ' . print_r( $date, true));
        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        // $this->logger->debug('date end = ' . print_r($date, true));

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

    private function contains($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                return 'contains(normalize-space(' . $node . '),"' . $s . '")';
            }, $field)) . ')';
    }

    private function getAttachmentName(\PlancakeEmailParser $parser, $pdf)
    {
        $header = $parser->getAttachmentHeader($pdf, 'Content-Type');

        if (preg_match('/name=[\"\']*(.+\.pdf)[\'\"]*/i', $header, $matches)) {
            return $matches[1];
        }

        return false;
    }
}
