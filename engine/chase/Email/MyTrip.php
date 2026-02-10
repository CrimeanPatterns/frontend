<?php

namespace AwardWallet\Engine\chase\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Common\FlightSegment;
use AwardWallet\Schema\Parser\Email\Email;

class MyTrip extends \TAccountChecker
{
    public $mailFiles = "chase/it-563324776.eml, chase/it-563345470.eml, chase/it-584435693.eml, chase/it-587789510.eml, chase/it-602382161.eml, chase/it-603254103.eml, chase/it-603609470.eml, chase/it-604176317.eml, chase/it-604767081.eml, chase/it-605067349.eml, chase/it-612693581.eml, chase/it-697667768.eml, chase/it-698863124.eml, chase/it-699171208.eml, chase/it-699332618.eml, chase/it-703236614.eml, chase/it-703263916.eml, chase/it-891028490.eml, chase/it-904525180.eml, chase/it-904611624.eml, chase/it-911032416.eml, chase/it-911042461.eml, chase/it-912568936.eml, chase/it-912649612.eml, chase/it-913143635.eml, chase/it-913158119.eml, chase/it-913158345.eml, chase/it-913580405.eml";

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'Airline confirmation: '    => 'Airline confirmation:',
            'Hotel confirmation:'       => ['Hotel confirmation:', 'Hotel confirmation'],
            'Non-refundable'            => ['Non-refundable', 'Non-Refundable'],
            'Car confirmation:'         => 'Car confirmation:',
            'Activity confirmation:'    => 'Activity confirmation:',
            'Flight'                    => ['Flight', 'Depart', 'Return'],
            'Hybrid'                    => ['Hybrid', 'Electric'],
            'points'                    => ['points', 'point', 'pts'],
        ],
    ];

    private $providerCode;

    private static $detectProviders = [
        'chase' => [
            'from' => [
                'donotreply@chasetravel.com',
            ],
            'subject' => [
                'Travel Reservation Center Trip ID #',
                'Get ready! Your trip is almost here',
            ],
            'body' => [
                '//a[contains(@href, ".chase.com")]',
                'choosing Chase Travel',
                'call the Travel Rewards Center',
                'call the Travel Center at and have your Trip ID',
                'call the Your Rewards Program',
                'call the Travel Center at',
            ],
        ],
        'aeroplan' => [
            'from'    => ['aeroplan@aeroplan.cxloyalty.com'],
            'subject' => [
                'Aeroplan Member trip confirmation',
            ],
            'body' => [
                '//a[contains(@href,"aeroplan.cxloyalty.com")]',
                'choosing Aeroplan',
                'Contact Aeroplan',
                'Aeroplan Member',
            ],
        ],
    ];

    private $detectBody = [
        'en' => [
            'Please carefully review your itinerary below',
            'Your itinerary has been updated',
            'Please carefully review your updated itinerary below to verify all information is correct.',
            'has shared their trip details with you.',
            'Your trip is coming up in a few days',
            'You received this email to give you updates and information about your Chase relationship.',
        ],
    ];
    private $getReadyTrip = false; // в таких письмах меньше информации

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]chasetravel\.com$/", $from) > 0;
    }

    public static function getEmailProviders()
    {
        return array_keys(self::$detectProviders);
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (empty($headers['from']) || empty($headers['subject'])) {
            return false;
        }

        foreach (self::$detectProviders as $code => $detect) {
            if (empty($detect['from']) || empty($detect['subject'])) {
                continue;
            }

            $byFrom = false;

            foreach ($detect['from'] as $dfrom) {
                if (stripos($headers['from'], $dfrom) !== false) {
                    $byFrom = true;

                    break;
                }
            }

            if ($byFrom == false) {
                continue;
            }

            foreach ($detect['subject'] as $dSubject) {
                if (stripos($headers['subject'], $dSubject) !== false) {
                    $this->providerCode = $code;

                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if (empty($this->getProviderByBody())) {
            return false;
        }

        // detect Format
        foreach ($this->detectBody as $detectBody) {
            if ($this->http->XPath->query("//*[{$this->contains($detectBody)}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if ($this->http->XPath->query("//*[{$this->starts($this->t('Your trip is coming up in a few days'))}]")->length > 0) {
            $this->getReadyTrip = true;
        }

        $tripId = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Trip ID:'))}]/following::text()[normalize-space()][1]",
            null, true, "/^\s*([\dA-Z]{5,})\s*$/");

        if (empty($tripId)) {
            $tripId = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Trip ID:'))}]",
                null, true, "/^Trip ID\s*\:\s*([\dA-Z]{5,})\s*$/");
        }

        if (empty($tripId) && $this->http->XPath->query("//text()[{$this->eq($this->t('Trip ID:'))}]")->length === 0) {
            $tripId = $this->re("/[#]\s*([\dA-Z]{5,})\s*$/", $parser->getSubject());
        }

        if (empty($tripId) && !empty($this->http->FindSingleNode("//text()[{$this->eq($this->t('Trip ID:'))}]/following::text()[normalize-space()][1][{$this->eq($this->t('See trip'))}]"))
            && preg_match("/(?: Trip ID #|Get ready! Your trip is almost here|Travel Reservation Center Trip ID #)\s*$/", $parser->getSubject())
        ) {
        } else {
            $email->ota()
                ->confirmation($tripId);
        }

        $userEmail = $this->http->FindSingleNode("//text()[{$this->starts($this->t('This email was sent to:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(\S+@\S+)\s*$/");

        if (!empty($userEmail)) {
            $email->setUserEmail($userEmail);
        }

        $this->parseFlight($email);
        $this->parseHotel($email);
        $this->parseRental($email);
        $this->parseActivity($email);

        $cancelled = false;

        if ($this->http->XPath->query("//node()[{$this->contains($this->t('Your trip has been canceled'))} or {$this->contains($this->t('Cancellation summary'))}]")->length > 0) {
            $cancelled = true;

            foreach ($email->getItineraries() as $it) {
                $it->general()
                    ->cancelled()
                    ->status('Cancelled');
            }
        }

        if ($cancelled !== true) {
            $total = $this->http->FindSingleNode("//td[{$this->eq($this->t('Trip total'))}]/following-sibling::td[normalize-space()][1]");
            $this->parseAndSetPrice($email, $total);

            // collect earned awards
            $itineraries = $email->getItineraries();
            $earnedAwards = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Points to be Earned Upon Travel:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(.+?points?)\s*$/");

            if (count($itineraries) === 1 && !empty($earnedAwards)) {
                $itineraries[0]->setEarnedAwards($earnedAwards);
            }

            // collect free nights
            $itHotelsCount = 0;

            foreach ($itineraries as $it) {
                if ($it->getType() == 'hotel') {
                    $h = $it;
                    $itHotelsCount++;
                }
            }
            $freeNights = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Payment summary'))}]/following::table[normalize-space()][1][{$this->contains($this->t('FREE NIGHT APPLIED'))}]");

            if ($itHotelsCount === 1 && !empty($freeNights)) {
                $h->setFreeNights(1);
            }
        }

        if (empty($this->providerCode)) {
            $this->providerCode = $this->getProvider($parser);
        }

        if (!empty($this->providerCode)) {
            $email->setProviderCode($this->providerCode);
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

    private function getProviderByBody()
    {
        foreach (self::$detectProviders as $code => $detect) {
            if (empty($detect['body'])) {
                continue;
            }

            foreach ($detect['body'] as $search) {
                if ((stripos($search, '//') === 0 && $this->http->XPath->query($search)->length > 0)
                    || (stripos($search, '//') === false && strpos($this->http->Response['body'], $search) !== false)
                ) {
                    return $code;
                }
            }
        }

        return null;
    }

    private function getProvider(\PlancakeEmailParser $parser)
    {
        $this->detectEmailByHeaders(['from' => $parser->getCleanFrom(), 'subject' => $parser->getSubject()]);

        if (!empty($this->providerCode)) {
            return $this->providerCode;
        }

        return $this->getProviderByBody();
    }

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["Airline confirmation:"])
                && $this->http->XPath->query("//*[{$this->contains($dict['Airline confirmation:'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }

            if (!empty($dict["Hotel confirmation:"])
                && $this->http->XPath->query("//*[{$this->contains($dict['Hotel confirmation:'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }

            if (!empty($dict["Car confirmation:"])
                && $this->http->XPath->query("//*[{$this->contains($dict['Car confirmation:'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }

            if (!empty($dict["Activity confirmation:"])
                && $this->http->XPath->query("//*[{$this->contains($dict['Activity confirmation:'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function parseFlight(Email $email)
    {
        $xpath = "//text()[{$this->starts($this->t('Main Cabin Fare:'))}][1]/ancestor::td[not({$this->starts($this->t('Main Cabin Fare:'))})][1]";
        // $this->logger->debug('$xpath = '.print_r( $xpath,true));
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length === 0) {
            $xpath = "//text()[{$this->eq($this->t('Fare:'))}][1]/ancestor::td[not({$this->starts($this->t('Fare:'))})][1]";
            // $this->logger->debug('$xpath = '.print_r( $xpath,true));
            $nodes = $this->http->XPath->query($xpath);
        }

        if ($nodes->length === 0 && $this->getReadyTrip === true) {
            $xpath = "//img[contains(@src, 'arrow-right.png')]/ancestor::table[normalize-space()][1][count(.//text()[normalize-space()]) > 2]/following::text()[normalize-space()][2]/ancestor::*[contains(@style, 'font-weight:600')]/ancestor::td[not(.//img)][last()]";
            // $this->logger->debug('$xpath = '.print_r( $xpath,true));
            $nodes = $this->http->XPath->query($xpath);
        }

        if ($nodes->length == 0 && empty($this->http->FindSingleNode("(//*[{$this->starts($this->t('Airline confirmation:'))}])[1]"))) {
            return true;
        }

        $f = $email->add()->flight();

        // General
        $confs = array_unique(array_filter($this->http->FindNodes("//text()[{$this->eq($this->t('Airline confirmation:'))}]/following::text()[normalize-space()][1]", null, "/^\s*([A-Z\d]{5,7})\s*$/")));

        if (empty($confs)) {
            $confs = array_unique(array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Airline confirmation:'))}]",
                null, "/:\s*([A-Z\d]{5,7})\s*$/")));
        }

        foreach ($confs as $conf) {
            $f->general()
                ->confirmation($conf);
        }

        if (empty($confs)
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Airline confirmation:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Airline confirmation:'))}]/following::text()[normalize-space()][1][contains(., '(') and contains(., ')')]")->length
            === $this->http->XPath->query("//text()[{$this->eq($this->t('Airline confirmation:'))}]")->length
        ) {
            $f->general()
                ->noConfirmation();
        }

        $f->general()
            ->travellers(array_unique(array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Traveler'))}]/ancestor::tr[1]", null, "/^\s*{$this->opt($this->t('Traveler'))} \d+:\s*(.+)/"))), true);

        foreach ($nodes as $root) {
            unset($s1, $s2);

            if (isset($s)) {
                unset($s);
            }

            $sText = implode("\n", $this->http->FindNodes("descendant::td[not(.//td)][normalize-space()]", $root));

            $tableXpath = "preceding::text()[normalize-space()][position() < 10]/ancestor::tr[position() = 1 or position() = 2][count(*[normalize-space()]) = 2][*[1][.//img]][last()]";

            $info = $this->http->FindSingleNode($tableXpath . "/following-sibling::*[normalize-space()]", $root);
            $transitCodes = $this->res("/\(\s*([A-Z]{3})\s*\W[^()]+\)/", $info);

            $flRe = "([A-Z][A-Z\d]|[A-Z\d][A-Z]) ?(\d{1,5})";

            $mainAirText = $this->re("/^.*?((?:[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?\d{1,5}.+?)(?:{$this->opt(['Main Cabin Fare:', 'Fare:'])}|$)/s", $sText);

            if (!empty($mainAirText)) {
                $mPart = $this->split("/\n((?:[A-Z][A-Z\d]|[A-Z\d][A-Z]) ?\d{1,5})/", "\n" . $mainAirText);

                foreach ($mPart as $i => $mp) {
                    $curSeg = null;

                    if ($i === 0) {
                        if (preg_match("/^\s*{$flRe}(.+)?/s", $mp, $m)) {
                            $s1 = $f->addSegment();
                            $s1->airline()
                                ->name($m[1])
                                ->number($m[2]);

                            $curSeg = $s1;

                            $this->setStatusForFlight($m[1], $m[2], $s1);
                        }
                    } elseif ($i === count($mPart) - 1) {
                        if (preg_match("/^\s*{$flRe}(.+)?/s", $mp, $m)) {
                            $s2 = $f->addSegment();
                            $s2->airline()
                                ->name($m[1])
                                ->number($m[2]);

                            $curSeg = $s2;
                            $this->setStatusForFlight($m[1], $m[2], $s2);
                        }
                    } elseif (isset($transitCodes[$i - 1]) && isset($transitCodes[$i])) {
                        if (preg_match("/^\s*{$flRe}(.+)?/s", $mp, $m)) {
                            $s = $f->addSegment();
                            $s->airline()
                                ->name($m[1])
                                ->number($m[2]);
                            $s->departure()
                                ->code($transitCodes[$i - 1])
                                ->noDate()
                            ;
                            $s->arrival()
                                ->code($transitCodes[$i])
                                ->noDate()
                            ;

                            $curSeg = $s;
                            $this->setStatusForFlight($m[1], $m[2], $s);
                        }
                    } else {
                        $f->addSegment();
                    }

                    if (!empty($m[3]) && $curSeg !== null) {
                        $aircraftText = $m[3];

                        if (preg_match("/^\s*{$this->opt($this->t('operated by'))}\s*\/?(.+?)\s*$/m", $aircraftText, $o)) {
                            $curSeg->setOperatedBy($o[1]);
                            $aircraftText = preg_replace("/{$this->opt($o[0])}/", '', $aircraftText);
                        }

                        if (!empty($aircraftText) && preg_match("/^\s*(?<aircraft>.+?)(?:\n(?<operator>.+?))?\s*$/", $aircraftText, $a)) {
                            $curSeg->setAircraft($a['aircraft']);

                            if (empty($curSeg->getOperatedBy()) && !empty($a['operator'])) {
                                $curSeg->setOperatedBy($a['operator']);
                            }
                        }
                    }
                }
            } else {
                $s1 = $f->addSegment();
            }

            if (!isset($s1)) {
                $f->addSegment();
                $s1 = $f->addSegment();
            }

            $date = $this->http->FindSingleNode($tableXpath . "/preceding::tr[not(.//tr)][normalize-space()][1][preceding::tr[not(.//tr)][normalize-space()][1][{$this->starts($this->t('Flight'))}]]", $root);

            if (empty($date)) {
                $date = $this->http->FindSingleNode($tableXpath . "/preceding::tr[not(.//tr)][normalize-space()][1][{$this->starts($this->t('Flight'))}]", $root, true, "/^(?:.+:)\s*(.+)/");
            }

            $date = preg_replace("/\(.+?\)\s*$/", '', $date);
            $date = $this->normalizeDate($date);

            if (!isset($s2)) {
                $s1->extra()
                    ->duration($this->re("/^\s*([^\|]+?)\s*(?:\|\s*|$)/", $info));
            }

            $depart = implode("\n", $this->http->FindNodes($tableXpath . "/*[normalize-space()][1]//text()[normalize-space()]", $root));

            if (preg_match("/^\s*(?<time>\d{1,2}:\d{2}.*)\n(?<code>[A-Z]{3})(?:\n\s*.+)?\s*$/", $depart, $m)) {
                $s1->departure()
                    ->code($m['code']);

                if ($date !== null) {
                    $s1->departure()
                        ->date(strtotime($m['time'], $date));
                }

                if (isset($s2)) {
                    $s2->departure()
                        ->code($transitCodes[count($transitCodes) - 1])
                        ->noDate();
                }

                $depTime = $m['time'];
            }
            $arrive = implode("\n", $this->http->FindNodes($tableXpath . "/*[normalize-space()][2]//text()[normalize-space()]", $root));

            if (preg_match("/^\s*(?<time>\d{1,2}:\d{2}.*)\n(?<code>[A-Z]{3})\s*(?:{$this->opt($this->t('Different airport'))}\s*)?(?:(?<overnight1>{$this->opt($this->t('Next day arrival'))})|(?<overnight2>{$this->opt($this->t('2nd day arrival'))})|\n\s*.+)?\s*$/", $arrive, $m)) {
                if (isset($s2)) {
                    $s2->arrival()
                        ->code($m['code']);

                    if ($date !== null) {
                        $s2->arrival()
                            ->date(strtotime($m['time'], $date));
                    }

                    if (!empty($m['overnight1']) && !empty($s2->getArrDate())) {
                        $s2->arrival()
                            ->date(strtotime('+1 day', $s2->getArrDate()));
                    } elseif (!empty($m['overnight2']) && !empty($s2->getArrDate())) {
                        $s2->arrival()
                            ->date(strtotime('+2 day', $s2->getArrDate()));
                    }

                    if (!empty($s2->getArrCode()) && empty($date) && empty($s2->getArrDate())) {
                        $dates = $this->http->FindSingleNode("//table[{$this->eq($this->t('Rules, policies, and cancellations'))}]/following-sibling::table[descendant::img[contains(@src, 'arrow-right.png')]]/descendant::tr[descendant::td[{$this->eq($s2->getArrCode())}][preceding-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]] and descendant::img[contains(@src, 'arrow-right.png')] and descendant::td[{$this->eq($s1->getDepCode())}][following-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]]][last()]/following::tr[1]");

                        if (preg_match("/^.+[ ]*\-[ ]*(.+)$/", $dates, $d)) {
                            $aDate = $this->normalizeDate($d[1]);

                            $s2->arrival()
                                ->date(strtotime($m['time'], $aDate));
                        }
                    }

                    if (!empty($s1->getDepCode()) && $date === null && empty($s1->getDepDate())) {
                        // $this->logger->debug("//table[{$this->eq($this->t('Rules, policies, and cancellations'))}]/following-sibling::table[descendant::img[contains(@src, 'arrow-right.png')]]/descendant::tr[descendant::td[{$this->eq($s1->getDepCode())}][following-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]] and descendant::img[contains(@src, 'arrow-right.png')] and descendant::td[{$this->eq($s1->getArrCode())}][preceding-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]]][last()]/following::tr[1]");
                        $dates = $this->http->FindSingleNode("//table[{$this->eq($this->t('Rules, policies, and cancellations'))}]/following-sibling::table[descendant::img[contains(@src, 'arrow-right.png')]]/descendant::tr[descendant::td[{$this->eq($s1->getDepCode())}][following-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]] and descendant::img[contains(@src, 'arrow-right.png')] and descendant::td[{$this->eq($s2->getArrCode())}][preceding-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]]][last()]/following::tr[1]");

                        if (preg_match("/^(.+)[ ]*\-[ ]*.+$/", $dates, $d)) {
                            $depDate = $this->normalizeDate($d[1]);

                            $s1->departure()
                                ->date(strtotime($depTime, $depDate));
                        }
                    }

                    $s1->arrival()
                        ->code($transitCodes[0])
                        ->noDate();
                } else {
                    $s1->arrival()
                        ->code($m['code']);

                    if ($date !== null) {
                        $s1->arrival()
                            ->date(strtotime($m['time'], $date));
                    }

                    if (!empty($m['overnight1']) && !empty($s1->getArrDate())) {
                        $s1->arrival()
                            ->date(strtotime('+1 day', $s1->getArrDate()));
                    } elseif (!empty($m['overnight2']) && !empty($s1->getArrDate())) {
                        $s1->arrival()
                            ->date(strtotime('+2 day', $s1->getArrDate()));
                    }

                    if (!empty($s1->getArrCode()) && empty($date) && empty($s1->getArrDate())) {
                        $dates = $this->http->FindSingleNode("//table[{$this->eq($this->t('Rules, policies, and cancellations'))}]/following-sibling::table[descendant::img[contains(@src, 'arrow-right.png')]]/descendant::tr[descendant::td[{$this->eq($s1->getArrCode())}][preceding-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]] and descendant::img[contains(@src, 'arrow-right.png')] and descendant::td[{$this->eq($s1->getDepCode())}][following-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]]][last()]/following::tr[1]");

                        if (preg_match("/^.+[ ]*\-[ ]*(.+)$/", $dates, $d)) {
                            $aDate = $this->normalizeDate($d[1]);
                            $s1->arrival()
                                ->date(strtotime($m['time'], $aDate));
                        }
                    }

                    if (!empty($s1->getDepCode()) && $date === null && empty($s1->getDepDate())) {
                        // $this->logger->debug("//table[{$this->eq($this->t('Rules, policies, and cancellations'))}]/following-sibling::table[descendant::img[contains(@src, 'arrow-right.png')]]/descendant::tr[descendant::td[{$this->eq($s1->getDepCode())}][following-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]] and descendant::img[contains(@src, 'arrow-right.png')] and descendant::td[{$this->eq($s1->getArrCode())}][preceding-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]]][last()]/following::tr[1]");
                        $dates = $this->http->FindSingleNode("//table[{$this->eq($this->t('Rules, policies, and cancellations'))}]/following-sibling::table[descendant::img[contains(@src, 'arrow-right.png')]]/descendant::tr[descendant::td[{$this->eq($s1->getDepCode())}][following-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]] and descendant::img[contains(@src, 'arrow-right.png')] and descendant::td[{$this->eq($s1->getArrCode())}][preceding-sibling::td[descendant::img[contains(@src, 'arrow-right.png')]]]][last()]/following::tr[1]");

                        if (preg_match("/^(.+)[ ]*\-[ ]*.+$/", $dates, $d)) {
                            $depDate = $this->normalizeDate($d[1]);

                            $s1->departure()
                                ->date(strtotime($depTime, $depDate));
                        }
                    }
                }
            }

            // $this->logger->debug('$this->getReadyTrip = ' . print_r($this->getReadyTrip ? 'true' : 'false', true));

            if ($this->getReadyTrip !== true) {
                $cabin = $this->re("/{$this->opt($this->t('Main Cabin Fare:'))}\n(?:.+\n)?(.+?) *\([A-Z]{1,2}\)(?:\n|$)/",
                    $sText);
                $bookingCode = $this->re("/{$this->opt($this->t('Main Cabin Fare:'))}\n(?:.+\n)?.+? *\(([A-Z]{1,2})\)(?:\n|$)/",
                    $sText);

                if ($cabin === null) {
                    $cabin = $this->re("/{$this->opt($this->t('Fare:'))}\n(?:.+\n)?(.+?) *\((?:[A-Z]{1,2})?\)(?:\n|$)/",
                        $sText);
                }

                if ($cabin === null) {
                    $cabin = $this->re("/{$this->opt($this->t('Fare:'))}\n(?:.+\n)?(.+?) *(?:[A-Z]{1,2})?(?:\n|$)/",
                        $sText);
                }

                if ($bookingCode === null) {
                    $bookingCode = $this->re("/{$this->opt($this->t('Fare:'))}\n(?:.+\n)?.+? *\(([A-Z]{1,2})\)(?:\n|$)/",
                        $sText);
                }

                if ($bookingCode === null) {
                    $bookingCode = $this->re("/{$this->opt($this->t('Fare:'))}\n(?:.+\n)?.+? *([A-Z]{1,2})(?:\n|$)/",
                        $sText);
                }

                if ($cabin !== null) {
                    $s1->setCabin($cabin);

                    if (isset($s2)) {
                        $s2->setCabin($cabin);
                    }

                    if (isset($s)) {
                        $s->setCabin($cabin);
                    }
                }

                if ($bookingCode !== null) {
                    $s1->setBookingCode($bookingCode);

                    if (isset($s2)) {
                        $s2->setBookingCode($bookingCode);
                    }

                    if (isset($s)) {
                        $s->setBookingCode($bookingCode);
                    }
                }
            }

            if (isset($s1) && !empty($s1->getDepCode()) && !empty($s1->getArrCode())) {
                $name = $s1->getDepCode() . ' - ' . $s1->getArrCode();
                $seats = array_filter($this->http->FindNodes("//text()[{$this->eq($this->t('Seat assignment'))}]/following::tr[1]//text()[{$this->starts($name)}]",
                    null, "/{$name}\s*(\d{1,3}[A-Z])\s*$/"));

                if (!empty($seats)) {
                    $s1->extra()
                        ->seats($seats);
                }
            }

            if (isset($s2) && !empty($s2->getDepCode()) && !empty($s2->getArrCode())) {
                $name = $s2->getDepCode() . ' - ' . $s2->getArrCode();
                $seats = array_filter($this->http->FindNodes("//text()[{$this->eq($this->t('Seat assignment'))}]/following::tr[1]//text()[{$this->starts($name)}]",
                    null, "/{$name}\s*(\d{1,3}[A-Z])\s*$/"));

                if (!empty($seats)) {
                    $s2->extra()
                        ->seats($seats);
                }
            }
        }

        return true;
    }

    private function parseHotel(Email $email)
    {
        $xpath = "//text()[{$this->starts($this->t('Hotel confirmation:'))}][1]/ancestor::*[{$this->contains($this->t('Check-out:'))}][1]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length == 0 && !empty($this->http->FindSingleNode("(//*[{$this->contains($this->t('Hotel confirmation:'))}])[1]"))) {
            $email->add()->hotel();

            return true;
        }

        foreach ($nodes as $root) {
            $h = $email->add()->hotel();

            // General
            $conf = $this->http->FindSingleNode(".//text()[{$this->eq($this->t('Hotel confirmation:'))}]/following::text()[normalize-space()][1]", $root, true,
                "/^\s*([A-Z\d\-]{5,})\s*$/");

            if (empty($confs)) {
                $conf = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Hotel confirmation:'))}]", $root, true,
                    "/:\s*([A-Z\d\-]{5,})\s*$/");
            }
            $h->general()
                ->confirmation($conf);

            if ($this->http->XPath->query("./preceding::tr[3][starts-with(normalize-space(), 'Hotel')][1]/descendant::td[2][contains(normalize-space(), 'Canceled')]", $root)->length > 0) {
                $h->general()
                    ->cancelled();
            }

            $traveller = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Primary guest:'))}]/ancestor::tr[1]", $root, true,
                "/{$this->opt($this->t('Primary guest:'))}\s*(\D+)\s*$/");

            if (empty($traveller) && $this->getReadyTrip) {
                $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Trip ID:'))}]/following::text()[{$this->starts($this->t('Hi '))}][1]",
                    null, true, "/^\s*{$this->opt($this->t('Hi '))}\s*(\D+)\s*,\s*$/");
            }

            if (!empty($traveller) && mb_strlen($traveller) > 1) {
                $h->general()
                    ->traveller($traveller);
            }

            // example - it-904525180.eml, it-904611624.eml
            $name = $this->http->FindSingleNode("./ancestor::table[not({$this->contains($this->t('Taxes and fees included'))})]/preceding-sibling::table[1]/descendant::text()[normalize-space()][1]", $root);

            // example - it-603609470.eml
            if (empty($name) || in_array($name, (array) $this->t('Hotel'))) {
                $name = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Check-in:'))}]/ancestor::tr[1]/preceding::tr[not(.//tr)][2]", $root);
            }

            $address = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Check-in:'))}]/ancestor::*[.//img][1]/following::tr[not(.//tr)][1][.//a]", $root)
                ?? $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Check-out:'))}]/ancestor::tr[not(.//tr)][1]/following::tr[not(.//tr)][normalize-space()][1][.//a]", $root);

            $h->hotel()
                ->name($name)
                ->address($address);

            $cancellation = $this->http->FindSingleNode("./descendant::text()[{$this->eq($h->getAddress())}]/following::tr[normalize-space()][1][{$this->contains($this->t('Non-refundable'))} or {$this->contains($this->t('Free cancellation'))}]", $root)
                ?? $this->http->FindSingleNode("./descendant::text()[{$this->eq($this->t('Non-refundable'))} or {$this->eq($this->t('Free cancellation'))}]", $root);

            $h->general()
                ->cancellation($cancellation, true, true);

            $this->detectDeadLine($h);

            $h->booked()
                ->checkIn($this->normalizeDate($this->http->FindSingleNode(".//text()[{$this->starts($this->t('Check-in:'))}]/ancestor::tr[1]", $root, true,
                    "/{$this->opt($this->t('Check-in:'))}\s*(.+)\s*$/")))
                ->checkOut($this->normalizeDate($this->http->FindSingleNode(".//text()[{$this->starts($this->t('Check-out:'))}]/ancestor::tr[1]", $root, true,
                    "/{$this->opt($this->t('Check-out:'))}\s*(.+)\s*$/")));

            $guestsText = $this->http->FindSingleNode("./descendant::text()[{$this->contains($this->t('night'))} and {$this->contains($this->t('guest'))}]", $root)
                ?? $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Hotel confirmation:'))}]/preceding::text()[normalize-space()][1]", $root);

            $h->booked()
                ->guests($this->re("/\b(\d+)\s*guest/", $guestsText), $this->getReadyTrip, $this->getReadyTrip)
                ->kids($this->re("/(?:^|,)\s*(\d+)\s*child/", $guestsText), true, true);

            $roomType = $this->http->FindSingleNode("./descendant::text()[{$this->starts($this->t('Check-in:'))}]/ancestor::tr[1]/preceding::tr[not(.//tr)][1]", $root);

            if (empty($roomType)) {
                $roomType = $this->http->FindSingleNode("./descendant::text()[{$this->eq($this->t('Primary guest:'))}]/preceding::text()[normalize-space()][1]", $root);
            }

            if (!empty($roomType)) {
                $h->addRoom()
                    ->setType($roomType);
            }

            $name = $h->getHotelName();

            if (!empty($name)) {
                $total = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Payment summary'))}]/following::td[{$this->eq($name)}]/following-sibling::td[normalize-space()][1]");
                $this->parseAndSetPrice($h, $total);
            }
        }

        return true;
    }

    private function parseRental(Email $email)
    {
        $xpath = "//text()[{$this->starts($this->t('Drop-off:'))}][1]/ancestor::*[{$this->contains($this->t('Car confirmation:'))}][1]";
        // $this->logger->debug('$xpath = '.print_r( $xpath,true));
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length == 0 && !empty($this->http->FindSingleNode("(//*[{$this->contains($this->t('Car confirmation:'))}])[1]"))) {
            $email->add()->rental();

            return true;
        }

        foreach ($nodes as $root) {
            $r = $email->add()->rental();

            if ($this->http->XPath->query("//text()[starts-with(normalize-space(), 'Car confirmation')]/ancestor::tr[1]/preceding::tr[3][starts-with(normalize-space(), 'Car')][1]/descendant::td[2][contains(normalize-space(), 'Canceled')]")->length > 0) {
                $r->general()
                    ->cancelled();
            }

            // General
            $conf = $this->http->FindSingleNode(".//text()[{$this->eq($this->t('Car confirmation:'))}]/following::text()[normalize-space()][1]", $root, true,
                "/^\s*([A-Z\d\-]{5,})\s*$/");

            if (empty($confs)) {
                $conf = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Car confirmation:'))}]", $root, true,
                    "/:\s*([A-Z\d\-]{5,})\s*$/");
            }
            $r->general()
                ->confirmation($conf);

            $traveller = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Driver:'))}]/ancestor::tr[1]", $root, true,
                "/{$this->opt($this->t('Driver:'))}\s*(\D+)\s*$/");
            $r->general()
                ->traveller($traveller);

            $location = $this->http->FindSingleNode(".//tr[{$this->eq($this->t('Pick-up / drop-off location:'))}]/following::tr[not(.//tr)][1]", $root);

            if (!empty($location)) {
                $phone = $this->http->FindSingleNode(".//tr[{$this->eq($this->t('Pick-up / drop-off location:'))}]/following::tr[not(.//tr)][2]", $root, null,
                    "/^[\d\W]+$/");

                if (strlen(preg_replace("/\D+/", '', $phone)) > 5) {
                    $r->pickup()
                        ->phone($phone);
                }
                $r->pickup()
                    ->location($location);
                $r->dropoff()
                    ->same();
            } else {
                $location = $this->http->FindSingleNode(".//tr[{$this->eq($this->t('Pick-up location:'))}]/following::tr[not(.//tr)][1]", $root);
                $phone = $this->http->FindSingleNode(".//tr[{$this->eq($this->t('Pick-up location:'))}]/following::tr[not(.//tr)][2]", $root, null,
                    "/^[\d\W]+$/");
                $r->pickup()
                    ->location($location);

                if (strlen(preg_replace("/\D+/", '', $phone)) > 5) {
                    $r->pickup()
                        ->phone($phone);
                }

                $location = $this->http->FindSingleNode(".//tr[{$this->eq($this->t('Drop-off location:'))}]/following::tr[not(.//tr)][1]", $root);
                $phone = $this->http->FindSingleNode(".//tr[{$this->eq($this->t('Drop-off location:'))}]/following::tr[not(.//tr)][2]", $root, null,
                    "/^[\d\W]+$/");
                $location = preg_replace("/\s*" . preg_quote("\"/>", '/') . "\s*$/", '', $location);
                $r->dropoff()
                    ->location($location);

                if (strlen(preg_replace("/\D+/", '', $phone)) > 5) {
                    $r->dropoff()
                        ->phone($phone);
                }
            }
            $r->pickup()
                ->date($this->normalizeDate($this->http->FindSingleNode(".//text()[{$this->starts($this->t('Pick-up:'))}]/ancestor::tr[1]", $root, true,
                    "/{$this->opt($this->t('Pick-up:'))}\s*(.+)\s*$/")));
            $r->dropoff()
                ->date($this->normalizeDate($this->http->FindSingleNode(".//text()[{$this->starts($this->t('Drop-off:'))}]/ancestor::tr[1]", $root, true,
                    "/{$this->opt($this->t('Drop-off:'))}\s*(.+)\s*$/")));

            // example - it-912649612.eml
            $carInfo = $this->http->FindSingleNode("./ancestor::table[not({$this->contains($this->t('Taxes and fees included'))})]/preceding-sibling::table[1]/descendant::text()[normalize-space()][1]", $root);

            // example - it-563324776.eml
            if (empty($carInfo) || in_array($carInfo, (array) $this->t('Car'))) {
                $carInfo = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Pick-up:'))}]/ancestor::tr[1]/preceding::tr[not(.//tr)][not({$this->eq($this->t('Hybrid'))})][2]", $root);
            }

            if (preg_match("/^\s*(?<company>.+?)\s*\-\s*(?<type>.+?)\s*$/", $carInfo, $m)) {
                $r->setCompany($m['company']);
                $r->setCarType($m['type']);
            }

            // example - it-563324776.eml
            $model = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Pick-up:'))}]/ancestor::tr[1]/preceding::tr[not(.//tr)][not({$this->eq($this->t('Hybrid'))})][1]", $root);

            // example - it-912649612.eml
            if (empty($model)) {
                $model = $this->http->FindSingleNode("./descendant::img[{$this->contains($this->t('car image'), '@alt')}]/@alt", $root, true, "/^\s*{$this->opt($this->t('car image'))}\s*(.+?)\s*$/");
            }

            $r->setCarModel($model, true, true);

            $urlText = $this->http->FindSingleNode("./descendant::img[{$this->contains($this->t('car image'), '@alt')}]/@src", $root);

            // get last url
            if (!empty($urlText)) {
                $parts = $this->split('/(https:)/', $urlText);

                if (!empty($parts)) {
                    $lastUrl = end($parts);
                    $r->setCarImageUrl($lastUrl);
                }
            }

            // collect reservation name for searching in block "Payment summary"
            // example - it-563324776.eml
            $name = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Pick-up:'))}]/ancestor::tr[1]/preceding::tr[not(.//tr)][2]", $root);

            // example - it-912649612.eml
            if (empty($name)) {
                $name = $this->http->FindSingleNode("./ancestor::table[not({$this->contains($this->t('Taxes and fees included'))})]/preceding-sibling::table[1]/descendant::text()[normalize-space()][1]", $root);
            }

            if (!empty($name)) {
                $total = $this->http->FindNodes("//text()[{$this->eq($this->t('Payment summary'))}]/following::table[1]/descendant::tr[count(td)=2][normalize-space()]", null, "/^\s*{$this->opt($name)}\s*(.+?)\s*$/i")[0] ?? null;
                $this->parseAndSetPrice($r, $total);
            }
        }

        return true;
    }

    private function parseActivity(Email $email)
    {
        $xpath = "//text()[{$this->starts($this->t('View and print voucher'))}][1]/ancestor::*[{$this->contains($this->t('Activity confirmation:'))}][1]";
        // $this->logger->debug('$xpath = '.print_r( $xpath,true));
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length == 0 && !empty($this->http->FindSingleNode("(//*[{$this->contains($this->t('Activity confirmation:'))}])[1]"))) {
            $email->add()->rental();

            return true;
        }

        foreach ($nodes as $root) {
            $event = $email->add()->event();

            $event->type()->event();

            // General
            $conf = $this->http->FindSingleNode(".//text()[{$this->eq($this->t('Activity confirmation:'))}]/following::text()[normalize-space()][1]", $root, true,
                "/^\s*([A-Z\d\-]{5,})\s*$/");

            if (empty($confs)) {
                $conf = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Activity confirmation:'))}]", $root, true,
                    "/:\s*([A-Z\d\-]{5,})\s*$/");
            }
            $event->general()
                ->confirmation($conf);

            $travellers = array_unique(array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('Traveler'))}]/ancestor::tr[1]", null, "/^\s*{$this->opt($this->t('Traveler'))} \d+:\s*(.+)/")));

            if (!empty($travellers)) {
                $event->setTravellers($travellers, true);
            }

            $event->place()
                ->name($this->http->FindSingleNode(".//tr[not(.//tr)][{$this->starts($this->t('Time:'))}]/preceding::tr[not(.//tr)][normalize-space()][1]", $root));

            $date = $this->http->FindSingleNode(".//tr[not(.//tr)][{$this->starts($this->t('Activity confirmation:'))}]/preceding::tr[not(.//tr)][normalize-space()][2]", $root);

            $time = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Time:'))}]/ancestor::tr[1]", $root, true,
                "/{$this->opt($this->t('Time:'))}\s*(.+)\s*$/");
            $duration = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Duration:'))}]/ancestor::tr[1]", $root, true,
                "/{$this->opt($this->t('Duration:'))}\s*(.+)\s*$/");

            if (!empty($date) && !empty($time)) {
                $startDate = $this->normalizeDate($date . ' ' . $time);
            }

            // example - it-913158345.eml
            if (empty($startDate) && !empty($time)) {
                $startDate = $this->normalizeDate($time);
            }

            $event->setStartDate($startDate);

            // 6.30 to 8 hrs -> 8 hrs
            $duration = preg_replace("/.+ to (.*\d.*)/", '$1', $duration);
            // 8 hrs -> 8 hours
            $duration = preg_replace("/\bhrs\b/", 'hours', $duration);

            if (!empty($duration) && !empty($event->getStartDate())) {
                $event->booked()
                    ->end(strtotime('+' . $duration, $event->getStartDate()));
            } elseif ($event->getStartDate()) {
                $event->booked()->noEnd();
            }

            $guestsText = $this->http->FindSingleNode(".//text()[{$this->starts($this->t('Activity confirmation:'))}]/preceding::text()[normalize-space()][1]/ancestor::tr[1]", $root);
            $adult = $this->re("/^\s*(\d+) ?adult/i", $guestsText);
            $adult += ($this->re("/(?:^|,)\s*(\d+) ?senior/i", $guestsText) ?? 0);

            if (!empty($adult) && $adult !== 0) {
                $event->setGuestCount($adult);
            }

            $kids = $this->re("/(?:^|,)\s*(\d+) ?child/i", $guestsText);

            if (!empty($kids) && $kids !== '0') {
                $event->setKidsCount($kids);
            }

            $name = $event->getName();
            $conf = $event->getConfirmationNumbers()[0];

            $url = $this->http->FindSingleNode(".//a[{$this->eq($this->t('View and print voucher'))}]/@href", $root);
            // $this->logger->debug('$url = ' . print_r($url, true));

            if (stripos($url, 'viator') !== false) {
                // the same as viator/GetTicket
                $http2 = clone $this->http;
                $this->http->brotherBrowser($http2);

                // $http2->SetProxy($this->proxyDOP());
                $http2->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
                $http2->setDefaultHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36');

                if (stripos($url, 'www.viator.com') !== false) {
                    $http2->setDefaultHeader('Origin', 'https://www.viator.com');
                } elseif (stripos($url, 'api.viator.com') !== false) {
                    $http2->setDefaultHeader('Origin', 'https://api.viator.com');
                }

                $http2->GetURL($url);

                if (isset($http2->Response['headers']['location'])) {
                    $url2 = $http2->Response['headers']['location'];

                    $http2->setMaxRedirects(5);
                    $http2->GetURL($url2);
                }

                if (stripos($http2->currentUrl(), 'viatorapi') !== false) {
                    $http2->GetURL(str_replace('viatorapi', 'www', $http2->currentUrl()));
                }

                if ($http2->XPath->query("//*[{$this->contains($name)}]")->length > 0 || $http2->XPath->query("//text()[{$this->eq($this->t('Booking ref.'))}]/following::text()[normalize-space()][1][{$this->eq($conf)}]")->length > 0) {
                    $address = $http2->FindSingleNode("//text()[{$this->eq($this->t('Meeting and pickup'))}]/following::text()[position() < 5][not(contains(normalize-space(), 'contact'))]/ancestor::a[contains(@href, 'maps.google.com')]/@href",
                        null, true, "/\?q=(.+)/");

                    if (empty($address)) {
                        $address = $http2->FindSingleNode("//text()[{$this->eq($this->t('Redemption Point'))}]/following::text()[normalize-space()][1]/ancestor::a[contains(@href, 'maps.google.com')]/@href",
                            null, true, "/\?q=(.+)/");
                    }

                    if (empty($address)) {
                        $address = $http2->FindSingleNode("//text()[{$this->eq($this->t('Ticket redemption point'))}][1]/following::a[contains(@href, 'google.com/maps')][1]");
                    }

                    if (empty($address)) {
                        $address = $http2->FindSingleNode("//text()[{$this->eq($this->t('Ticket redemption points'))}][1]/following::a[contains(@href, 'google.com/maps')][1]");
                    }

                    if (empty($address)) {
                        $address = $http2->FindSingleNode("//text()[{$this->eq($this->t('Location Details'))}][1]/following::a[contains(@href, 'google.com/maps')][1]");
                    }

                    if (empty($address)) {
                        $address = $http2->FindSingleNode("//text()[{$this->eq($this->t('Pickup Point'))}][1]/following::a[contains(@href, 'google.com/maps')][1]", null, false, "/^(?!I will contact the supplier later)(.+)$/u");
                    }

                    if (empty($address)) {
                        $address = $http2->FindSingleNode("//text()[{$this->eq($this->t('Meeting Point'))}][1]/following::text()[normalize-space()][2]");
                    }

                    $event->place()
                        ->address($address);
                }
            }

            if (!empty($name)) {
                $total = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Payment summary'))}]/following::td[{$this->eq($name)}]/following-sibling::td[normalize-space()][1]");
                $this->parseAndSetPrice($event, $total);
            }
        }

        return true;
    }

    private function setStatusForFlight(string $airName, string $flNumber, FlightSegment $s)
    {
        if (!empty($airName) && !empty($flNumber)) {
            if ($this->http->XPath->query("//text()[{$this->eq($airName . ' ' . $flNumber)}]/preceding::text()[starts-with(normalize-space(), 'Airline confirmation:')][1]/preceding::tr[{$this->starts($this->t('Flight'))}][1]/descendant::td[2][contains(normalize-space(), 'Canceled')]")->length > 0) {
                $s->setCancelled(true);
            }
        }
    }

    private function detectDeadLine(\AwardWallet\Schema\Parser\Common\Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        $timePattern = '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?'; // 4:19PM    |    2:00 p. m.    |    3pm

        // Free cancellation until Wed, Jul 9, 2025 06:00 PM (property local time)
        if (preg_match("/Free cancellation until \w{3}, (?<month>\D+) (?<day>\d+), (?<year>\d{4}) (?<time>{$timePattern})\b/i", $cancellationText, $m)
        ) {
            $h->booked()
                ->deadline($this->normalizeDate($m['day'] . ' ' . $m['month'] . ' ' . $m['year'] . ', ' . $m['time']));
        }

        if (stripos($cancellationText, 'Non-refundable') !== false) {
            $h->setNonRefundable(true);
        }
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
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }

        $in = [
            // Tuesday, July 22, 2025 10:00 am => 22 July 2025 10:00 am
            // Friday, May 30, 2025, 8:00 am   => 30 May 2025, 8:00 am
            "/^\s*[\w\-]+,\s+(\w+)\s+(\d+),\s*(\d{4}),?\s+(\d{1,2}:\d{2}(\s*[ap]m)?)\s*$/ui",
        ];
        $out = [
            '$2 $1 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
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

    private function res($re, $str, $c = 1)
    {
        preg_match_all($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function parseAndSetPrice($it, $text)
    {
        if (empty($text)) {
            return;
        }

        if (preg_match("/(?:^|\+)\s*(.+?{$this->opt($this->t('points'))})\s*(?:\+|$)/iu", $text, $m)) {
            $it->price()
                ->spentAwards($m[1]);
            $text = str_replace($m[0], '', $text);
        }

        if (preg_match("#^\s*(?<currency>[^\d,.\s][^\d]{0,5})\s*(?<amount>\d[\d\., ]*)\s*$#u", $text, $m)
            || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d,.\s][^\d]{0,5})\s*$#u", $text, $m)
            // $232.83 USD
            || preg_match("#^\s*\D{1,5}(?<amount>\d[\d\., ]*)\s*(?<currency>[A-Z]{3})\s*$#u", $text, $m)
            // CA $227.58, $227.58
            || preg_match("#^\s*(?<currency>\D{3,5})\s*(?<amount>\d[\d\., ]*)\s*$#u", $text, $m)
        ) {
            $m['currency'] = $this->currency(trim($m['currency']));
            $m['amount'] = PriceHelper::parse($m['amount']);

            if (is_numeric($m['amount'])) {
                $m['amount'] = (float) $m['amount'];
            } else {
                $m['amount'] = null;
            }

            if ($m['amount'] !== null && !empty($m['currency'])) {
                $it->price()
                    ->total($m['amount'])
                    ->currency($m['currency']);
            }
        }
    }

    private function currency($s)
    {
        if (preg_match("#^\s*(?:\D{1,3}\s)?\b(?<c>[A-Z]{3})\b(?:\s\D{1,3})?\s*$#u", $s, $m)) {
            return $m['c'];
        }
        $sym = [
            '€'    => 'EUR',
            '£'    => 'GBP',
            'CA $' => 'CAD',
            '$'    => '$',
        ];

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return null;
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
