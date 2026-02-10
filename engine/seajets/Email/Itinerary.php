<?php

namespace AwardWallet\Engine\seajets\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;

class Itinerary extends \TAccountChecker
{
    public $mailFiles = "seajets/it-107419390.eml, seajets/it-378842104.eml";

    public $pdfNamePattern = ".*\.pdf";
    public $pdfInfo = [];
    public $subject = '';

    public $lang = '';
    public static $dictionary = [
        'el' => [
            'Passenger Name'                           => 'Passenger Name',
            'Child'                                    => ['Child', 'Infant'],
            'Order Id'                                 => ['Αριθμός Παραγγελίας', 'Order Id', 'orderId'],
            'Type'                                     => 'Tύπος',
            'Booking Reference'                        => 'Αριθμός Κράτησης',
            'Reservation Date'                         => 'Ημερομηνία Κράτησης',
            'Departure'                                => 'Αναχώρηση',
            'Vessel'                                   => 'Πλοίο',
            'TOTAL AMOUNT CHARGED TO YOUR CREDIT CARD' => 'ΣΥΝΟΛΙΚΟ ΠΟΣΟ ΧΡΕΩΣΗΣ ΣΤΗΝ ΚΑΡΤΑ ΣΑΣ',
        ],
        'en' => [
            'Passenger Name' => 'Passenger Name',
            'Child'          => ['Child', 'Infant'],
            'Order Id'       => ['Order Id', 'orderId'],
            'Type'           => 'Type',
            // 'Booking Reference' => '',
            // 'Reservation Date' => '',
            // 'Departure' => '',
            // 'Vessel' => '',
            // 'TOTAL AMOUNT CHARGED TO YOUR CREDIT CARD' => '',
        ],
    ];

    private $detectFrom = "reservations@seajets.gr";
    private $detectSubject = [
        // en
        // if array - contains all phrases
        ['Dear', 'orderId:'], // Dear KIM KIRK orderId: 691248 Booking Reference: 1112TO8VJ
    ];
    private $detectBody = [
        'en' => [
            'See you on board ',
        ],
        'el' => [
            'Θα σε δούμε στο πλοίο ',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, $this->detectFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (is_string($dSubject) && stripos($headers["subject"], $dSubject) !== false) {
                return true;
            } elseif (is_array($dSubject)) {
                $detected = true;

                foreach ($dSubject as $ds) {
                    if (stripos($headers["subject"], $ds) === false) {
                        $detected = false;

                        continue 2;
                    }
                }

                if ($detected == true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//a[{$this->contains(['www.seajets.gr', 'www.seajets.com'], '@href')}]")->length === 0) {
            return false;
        }

        foreach ($this->detectBody as $detectBody) {
            if ($this->http->XPath->query("//*[{$this->contains($detectBody)}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if (!$this->assignLang()) {
            $this->logger->debug("can't determine a language");

            return $email;
        }

        $this->subject = $parser->getSubject();

        $this->pdfInfo = [];
        $pdfs = $parser->searchAttachmentByName($this->pdfNamePattern);

        foreach ($pdfs as $pdf) {
            $text = \PDF::convertToText($parser->getAttachmentBody($pdf));

            $orderId = $this->re("/^(\d{5,})\W*/", $this->getAttachmentName($parser, $pdf));

            if (!empty($orderId) && stripos($text, '@seajets.gr') !== false) {
                $this->pdfInfo[$orderId] = [];
                $segments = $this->split("/(?:^|\n)(.+ \d{1,2}:\d{2} )/", $text);

                foreach ($segments as $segment) {
                    if (preg_match("/(\S.+) {3,}(?<date>\S.+) {3,}(?<arrival>\S.+)\n\s*.+@.+\s*\n *(?<name>[[:alpha:] \-]+) +(?<accommodation>\w+ +[A-Z\d]{1,5})\s*\n/", $segment, $m)) {
                        $date = $this->normalizeDate($m['date']);
                        $foundSeg = false;

                        foreach ($this->pdfInfo[$orderId] as $i => $st) {
                            if ($st['date'] === $date && $st['arrival'] === $m['arrival']) {
                                $this->pdfInfo[$orderId][$i]['name'][] = $m['name'];
                                $this->pdfInfo[$orderId][$i]['accommodation'][] = preg_replace("/\s+/", ', ', trim($m['accommodation']));
                                $foundSeg = true;

                                break;
                            }
                        }

                        if ($foundSeg === false) {
                            $this->pdfInfo[$orderId][] = [
                                'date'          => $date,
                                'arrival'       => $m['arrival'],
                                'name'          => [$m['name']],
                                'accommodation' => [preg_replace("/\s+/", ', ', trim($m['accommodation']))],
                            ];
                        }
                    }
                }
            }
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

    private function parseEmailHtml(Email $email): void
    {
        $this->logger->debug(__FUNCTION__ . '()');
        $ferry = $email->add()->ferry();

        // General

        $bookingRef = $this->http->FindSingleNode("descendant::text()[{$this->eq($this->t('Booking Reference'), "translate(.,':','')")}][1]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{3,33})\s*$/");
        $bookingRefTitle = $this->http->FindSingleNode("descendant::text()[{$this->eq($this->t('Booking Reference'), "translate(.,':','')")}][1]", null, true, '/^(.+?)[\s:：]*$/u');

        if (!$bookingRef && preg_match("/({$this->opt($this->t('Booking Reference'))})[:\s]*([A-Z\d]{3,33})(?:\s*[\[(,;!?]|\s+[[:alpha:]]|$)/u", $this->subject, $m)) {
            $bookingRef = $m[2];
            $bookingRefTitle = $m[1];
        }

        $ferry->general()->confirmation($bookingRef, $bookingRefTitle);
        
        $orderId = $this->http->FindSingleNode("descendant::text()[{$this->eq($this->t('Order Id'), "translate(.,':','')")}][1]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{3,33})\s*$/");
        $orderIdTitle = $this->http->FindSingleNode("descendant::text()[{$this->eq($this->t('Order Id'), "translate(.,':','')")}][1]", null, true, '/^(.+?)[\s:：]*$/u');

        if (!$orderId && preg_match("/({$this->opt($this->t('Order Id'))})[:\s]*([A-Z\d]{3,33})(?:\s*[\[(,;!?]|\s+[[:alpha:]]|$)/u", $this->subject, $m)) {
            $orderId = $m[2];
            $orderIdTitle = $m[1];
        }

        if ($orderId && $orderId !== $bookingRef) {
            $ferry->general()->confirmation($orderId, $orderIdTitle);
        }

        $ferry->general()
            ->travellers(array_unique($this->http->FindNodes("//tr[td[1][" . $this->eq($this->t("Passenger Name")) . "]]/following-sibling::tr/td[1]")))
            ->date($this->normalizeDate($this->http->FindSingleNode("//text()[{$this->eq($this->t("Reservation Date"), "translate(.,':','')")}]/following::text()[normalize-space()][1]")))
        ;

        // Segments

        $xpath = "//tr[td[" . $this->eq($this->t("Departure")) . "] and td[" . $this->eq($this->t("Vessel")) . "]]/following-sibling::tr[normalize-space()]";
        $nodes = $this->http->XPath->query($xpath);

        foreach ($nodes as $i => $root) {
            $s = $ferry->addSegment();
            $s->departure()
                ->name($this->http->FindSingleNode("*[1]", $root, null, "/^\s*(\S.+)? - \S.+/"))
                ->date(strtotime($this->http->FindSingleNode("*[2]", $root)))
            ;
            $s->arrival()
                ->name($this->http->FindSingleNode("*[1]", $root, null, "/^\s*\S.+ - (\S.+)/"))
                ->date(strtotime($this->http->FindSingleNode("*[3]", $root)))
            ;
            $s->extra()
                ->vessel($this->http->FindSingleNode("*[4]", $root))
            ;

            $followingCount = $nodes->length - $i - 1;
            $travellerXpath = "following::text()[" . $this->eq($this->t("Passenger Name")) . "]/following::tr[1][count(following::tr[td[" . $this->eq($this->t("Departure")) . "] and td[" . $this->eq($this->t("Vessel")) . "]]) = $followingCount]";

            if (!empty($this->pdfInfo[$orderId]) && !empty($s->getDepDate()) && !empty($s->getArrName())) {
                foreach ($this->pdfInfo[$orderId] as $pInfo) {
                    if ($pInfo['date'] === $s->getDepDate() && $pInfo['arrival'] === $s->getArrName()) {
                        $s->booked()
                            ->accommodations($pInfo['accommodation']);
                    }
                }
            }

            if (empty($s->getAccommodations())) {
                $accommodations = $this->http->FindNodes($travellerXpath . '/td[5]', $root);
                $s->booked()
                    ->accommodations($accommodations)
                ;
            }

            $adultsCount = count(array_filter($this->http->FindNodes($travellerXpath . '/td[3]', $root, "/^{$this->opt($this->t('Adult'))}/i")));
            $childrenCount = count(array_filter($this->http->FindNodes($travellerXpath . '/td[3]', $root, "/^{$this->opt($this->t('Child'))}/i")));

            if ($adultsCount > 0 && $adultsCount === count($ferry->getTravellers())) {
                $s->booked()->adults($adultsCount);
            } elseif ($childrenCount > 0 && $childrenCount === count($ferry->getTravellers())) {
                $s->booked()->kids($childrenCount);
            } elseif (($adultsCount > 0 || $childrenCount > 0) && $adultsCount + $childrenCount === count($ferry->getTravellers())) {
                $s->booked()->adults($adultsCount)->kids($childrenCount);
            }
        }

        // Price

        $totalPrice = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('TOTAL AMOUNT CHARGED TO YOUR CREDIT CARD'), "translate(.,':','')")}] ]/*[normalize-space()][2]", null, true, '/^.*\d.*$/');

        if (preg_match("/^\s*(?<curr>[^\d\s]{1,5})\s*(?<amount>\d[\d., ]*?)\s*$/", $totalPrice, $m)
            || preg_match("/^\s*(?<amount>\d[\d., ]*?)\s*(?<curr>[^\d\s]{1,5})\s*$/", $totalPrice, $m)
        ) {
            // 1052.40€
            $currency = $this->currency($m['curr']);
            $ferry->price()->total(PriceHelper::parse($m['amount'], $currency))->currency($currency);
        }
    }

    private function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $words) {
            if (isset($words['Type'])) {
                if ($this->http->XPath->query("//*[{$this->contains($words['Type'])}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return $phrase;
        }
        if ($lang === '') {
            $lang = $this->lang;
        }
        if ( empty(self::$dictionary[$lang][$phrase]) ) {
            return $phrase;
        }
        return self::$dictionary[$lang][$phrase];
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

    private function eq($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function normalizeDate(?string $date)
    {
//        $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }

        $in = [
            // 20/06/2021
            '/^\s*(\d{1,2})\/(\d{2})\/(\d{4})\s*$/iu',
            // 20/06/2021 11:10
            '/^\s*(\d{1,2})\/(\d{2})\/(\d{4})\s+(\d{1,2}:\d{2})\s*$/iu',
        ];
        $out = [
            '$1.$2.$3',
            '$1.$2.$3, $4',
        ];

        $date = preg_replace($in, $out, $date);

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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function currency($s)
    {
        if ($code = $this->re("#^\s*([A-Z]{3})\s*$#", $s)) {
            return $code;
        }
        $sym = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
        ];

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return null;
    }

    private function getAttachmentName(\PlancakeEmailParser $parser, $pdf)
    {
        $header = $parser->getAttachmentHeader($pdf, 'Content-Disposition');

        if (preg_match('/name=[\"\']*(.+\.pdf)[\'\"]*/i', $header, $matches)) {
            return $matches[1];
        }

        return false;
    }

    private function split($re, $text)
    {
        $r = preg_split($re, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ret = [];

        if (count($r) > 1) {
            array_shift($r);

            for ($i = 0; $i < count($r) - 1; $i += 2) {
                $ret[] = $r[$i] . $r[$i + 1];
            }
        } elseif (count($r) == 1) {
            $ret[] = reset($r);
        }

        return $ret;
    }
}
