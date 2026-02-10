<?php

namespace AwardWallet\Engine\preferred\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Common\Event;
use AwardWallet\Schema\Parser\Email\Email;

class EventConfirmation extends \TAccountChecker
{
    public $mailFiles = "preferred/it-919624248.eml, preferred/it-927897453.eml, preferred/it-928440528.eml, preferred/it-929019582.eml";

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'Reservation Itinerary' => [
                'Reservation Itinerary',
                'Reservation Confirmation',
                'RESERVATION DETAILS',
            ],
            'Cancellation Policy & Additional Information' => [
                'Cancellation Policy & Additional Information',
                'Cancellation Policy + Additional Information',
            ],
            'Preferred Hotels' => [
                'Preferred Hotels',
                'Preffered Hotels',
            ],
        ],
    ];

    private $detectFrom = [
        '@montage.com',
        '@pendry.com',
    ];

    private $detectSubject = [
        // en
        'Booking Confirmation#',
        'Itinerary for Reservation',
    ];

    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect provider
        $isDetectProvider = false;

        foreach ($this->detectFrom as $dFrom) {
            if (stripos($headers["from"], $dFrom) === false) {
                $isDetectProvider = true;

                break;
            }
        }

        if (!$isDetectProvider) {
            return false;
        }

        // detect format
        foreach ($this->detectSubject as $dSubject) {
            if (stripos($headers["subject"], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect provider
        if ($this->http->XPath->query("//a/@href[{$this->contains(['preferredhotels.com'])}]")->length === 0
            && $this->http->XPath->query("//src/@alt[{$this->contains(['Preffered Hotels'])}]")->length === 0
        ) {
            return false;
        }

        // detect format
        if ($this->http->XPath->query("//text()[{$this->eq($this->t('Reservation Itinerary'))}]")->length > 0
            && ($this->http->XPath->query("//text()[{$this->eq($this->t('Guest Name'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->eq($this->t('Technician'))}]")->length > 0)
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Confirmation Number'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Cancellation Policy & Additional Information'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->parseEmailHtml($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class));

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

    private function parseEmailHtml(Email $email): void
    {
        $patterns = [
            'phone'         => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52    |    (+351) 21 342 09 07    |    713.680.2992
            'time'          => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.    |    3pm
            'travellerName' => "(?:{$this->opt(['Dr', 'Miss', 'Mrs', 'Mr', 'Ms', 'Mme', 'Mr/Mrs', 'Mrs/Mr', 'Monsieur'])}[\.\s]+)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])", // Mr. Hao-Li Huang => Hao-Li Huang
        ];

        // ota reservation confirmation
        $otaConfInfo = $this->http->FindSingleNode("(//text()[{$this->eq('Reservation Number')}])[1]/ancestor::tr[1][normalize-space()]");

        if (preg_match("/^\s*(?<desc>{$this->opt($this->t('Reservation Number'))})\s*(?<number>\w{7,9})\s*$/", $otaConfInfo, $m)) {
            $email->ota()->confirmation($m['number'], $m['desc']);
        }

        // common fields for all reservations
        // address
        $address = implode(', ', $this->http->FindNodes("//img[{$this->contains($this->t('Preferred Hotels'), '@alt')}]/ancestor::td[1]/preceding-sibling::td[normalize-space()][1]/descendant::text()[normalize-space()]"));

        // cancellation policy
        $cancellationNodes = $this->http->FindNodes("//text()[{$this->eq($this->t('Cancellation Policy & Additional Information'))}]/following::text()[{$this->contains('cancel')}]");

        if (!empty($cancellationNodes)) {
            $cancellation = implode("\n", $cancellationNodes);
        }

        // phone
        $phone = $this->http->FindSingleNode("//a/@href[{$this->starts('tel:')}]", null, true, "/^\s*tel\:({$patterns['phone']})\s*$/");

        $roots = $this->http->XPath->query("//tr[count(td) = 2 or count(td) = 3]/td[contains(translate(.,'0123456789', 'dddddddddd'), 'dddd') and contains(translate(.,'0123456789', 'dddddddddd'), 'd:dd')]");

        foreach ($roots as $root) {
            $e = $email->add()->event();
            $e->setEventType(Event::TYPE_EVENT);

            // reservation confirmation
            $confInfo = $this->http->FindSingleNode("./preceding::text()[{$this->eq('Confirmation Number')}][1]/ancestor::tr[1][normalize-space()]", $root);

            if (preg_match("/^\s*(?<desc>{$this->opt($this->t('Confirmation Number'))})\s*(?<number>\d{7,9})\s*$/", $confInfo, $m)) {
                $e->addConfirmationNumber($m['number'], $m['desc']);
            }

            // travellers
            $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Guest Name'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*{$patterns['travellerName']}\s*$/u")
                ?? $this->http->FindSingleNode("./preceding::text()[{$this->eq($this->t('Confirmation Number'))}][1]/preceding::text()[normalize-space()][1]", $root, true, "/^\s*{$patterns['travellerName']}\s*$/u")
                ?? $this->http->FindSingleNode("(./preceding::text()[{$this->eq($this->t('Confirmation Number'))}])[1]/preceding::text()[normalize-space()][1]", $root, true, "/^\s*{$patterns['travellerName']}\s*$/u");

            $e->addTraveller($traveller);

            // address
            if (preg_match("/^\s*.{10,90}[A-Z]\s+\d{5}\s*$/", $address)) {
                $e->setAddress($address);
            }

            // cancellation policy
            if (!empty($cancellation)) {
                $e->setCancellation($cancellation);
            }

            // phone
            $e->setPhone($phone);

            $regex = "/^"
                . "(?:[ ]*(?<name>.+)[ ]*\n)?"
                . "[ ]*(?<date>.+?\d{4})[ ]*\n"
                . "[ ]*(?:\w+\:\s+)?(?<time>{$patterns['time']})[ ]*\n"
                . "(?:[ ]*(?:\w+\:\s*)?(?<duration>\d+[ ]+min)?[ ]*\n)?"
                . "[ ]*(?:\w+\:\s+)?(?<price>.+)[ ]*"
                . "$/u";

            $rootValue = implode("\n", $this->http->FindNodes("./descendant::text()[normalize-space()]", $root));

            if (preg_match($regex, $rootValue, $m)) {
                // event name
                if (!empty($m['name'])) {
                    $e->setName($m['name']);
                } else {
                    $e->setName($this->http->FindSingleNode("./preceding::text()[normalize-space()][1]", $root));
                }

                // dates
                $e->setStartDate(strtotime($m['time'], $this->normalizeDate($m['date'])));

                if (!empty($m['duration'])) {
                    $e->setEndDate(strtotime($m['duration'], $e->getStartDate()));
                }

                if (!empty($e->getStartDate()) && empty($m['duration'])) {
                    $e->setNoEndDate(true);
                }

                // pricing info
                if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)\s*(?:\s*\(.+?\)\s*)?$/u", $m['price'], $m)
                    || preg_match("/^\s*(?<amount>[\d\.\,\']+)\s*(?<currency>[^\d\s]{1,3})\s*$/u", $m['price'], $m)
                ) {
                    $currency = $this->normalizeCurrency($m['currency']);

                    $e->price()
                        ->total(PriceHelper::parse($m['amount'], $currency))
                        ->currency($currency, false, true);
                }
            }
        }
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
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

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }

        $in = [
            '/^\s*[\w\-]+\,\s+(\w+)\s+(\d+),\s*(\d{4})\s*$/ui', // Wednesday, July 2, 2025 => 2 July 2025
        ];
        $out = [
            '$2 $1 $3',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        // $this->logger->debug('date replace = ' . print_r( $date, true));

        if (preg_match("/\b\d{4}\b/", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }
        // $this->logger->debug('date end = ' . print_r( $date, true));

        return $date;
    }

    private function normalizeCurrency($s)
    {
        if (empty($s)) {
            return null;
        }

        $sym = [
            '€'          => 'EUR',
            'US dollars' => 'USD',
            '£'          => 'GBP',
            '₹'          => 'INR',
            'CA$'        => 'CAD',
            'R$'         => 'BRL',
            '$'          => '$',
            'Rp'         => 'IDR',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3}\D)(?:$|\s)#", $s)) {
            return $code;
        }

        $s = $this->re("#([^\d\,\.]+)#", $s);

        foreach ($sym as $f => $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return $s;
    }
}
