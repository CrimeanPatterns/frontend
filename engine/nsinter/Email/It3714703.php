<?php

namespace AwardWallet\Engine\nsinter\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Train;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class It3714703 extends \TAccountCheckerExtended
{
    public $mailFiles = "nsinter/it-61545320-cancelled.eml, nsinter/it-7012241.eml, nsinter/it-921536325.eml, nsinter/it-920367150-nl.eml, nsinter/it-921381341-nl.eml";

    private $subjects = [
        'nl' => ['Annulering van uw boeking'],
        'en' => ['Booking cancellation with reference', 'Booking cancelation with reference'],
    ];

    public static $dictionary = [
        "nl" => [
            "Booking code" => "Boekingscode",
            "Total price"  => "Totaalprijs",
            "departure"    => "Vertrek:",
            "Trainnumber:" => "Treinnummer:",
            "By:"          => "Met:",
            "arrival"      => "Aankomst:",
            "Passengers:"  => "Reizigers:",
            "Tariff"       => "Tariefsoort",
            "with tariff"  => "met tarief",
            "Travel time:" => "Reistijd:",
            "Coach"        => "Rijtuig",
            "Seat"         => "Zitplaats",
            "Class:"       => "Comfortklasse:",

            // Cancelled
            'cancelledText'           => [
                'Uw tickets zijn (gedeeltelijk) geannuleerd.',
                'Hierbij bevestigen wij de annulering van uw treintickets bij NS International.',
            ],
            'Dear '                   => 'Beste ',
            'dnr'                     => 'Uw boekingscode (DNR)',
            // 'Ticket ID' => '',
            'Refund amount'           => 'Terug te ontvangen',
            'From'                    => 'Van', // From AMSTERDAM C. to ZONE BRUXELLES/BRUSSEL on 11/8/2020 with NRT ticket (BeNeLux)
            'to'                      => 'naar',
            'on'                      => 'op',
            'with'                    => 'met',

            'D'  => 'V',
            // 'A' => '',
            'at' => 'om',
        ],
        "en" => [
            'Booking code' => ['Booking code', 'Bookingcode'],
            'Total price' => ['Total price', 'New total price'],
            'departure' => ['Departure:'],
            // 'Trainnumber:' => '',
            // 'By:' => '',
            'arrival'   => ['Arrival:'],
            // 'Passengers:' => '',
            // 'Tariff' => '',
            // 'with tariff' => '',
            // 'Travel time:' => '',
            'Coach' => ['Coach', 'Rijtuig'],
            'Seat' => ['Seats', 'Seat'],
            // 'Class:' => '',

            // Cancelled
            'cancelledText' => [
                'Your tickets are (partially) cancelled.',
                'Your tickets are (partially) canceled.',
                'We confirm that the following booking has been cancelled as requested.',
                'We confirm that the following booking has been canceled as requested.',
            ],
            // 'Dear ' => '',
            'dnr'           => ['Your booking code (DNR)'],
            // 'Ticket ID' => '',
            // 'Refund amount' => '',
            // 'From' => '',
            // 'to' => '',
            // 'on' => '',
            // 'with' => '',

            // 'D' => '',
            // 'A' => '',
            // 'at' => '',
        ],
    ];

    public $lang = "en";

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('cancelledText'))}]")->length > 0) {
            $this->parseEmailCancelled($email);
        } else {
            $this->parseEmail($email);
        }

        $email->setType('TrainTrip' . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]nsinternational\.nl$/i', $from) > 0;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $href = ['.nsinternational.nl/', 'www.nsinternational.nl'];

        $providerPhrases = [
            'Bedankt voor uw boeking bij NS International', // nl
            'Hierbij bevestigen wij de annulering van uw treintickets bij NS International.', // nl
            'Thank you for choosing NS International',
            'Thank you for buying your train tickets at NS International',
        ];

        if ($this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//text()[normalize-space(translate(.,"|0123456789",""))="NS International ©"]')->length === 0
            && $this->http->XPath->query("//*[{$this->contains($providerPhrases)}]")->length === 0
        ) {
            return false;
        }

        return $this->assignLang();
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array)$phrases as $phrase) {
                if (is_string($phrase) && array_key_exists('subject', $headers) && stripos($headers['subject'], $phrase) !== false)
                    return true;
            }
        }

        return false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    private function parseEmailCancelled(Email $email): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        $r = $email->add()->train();
        $r->general()->traveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear '))}]", null, false, "/{$this->opt($this->t('Dear '))}(.+?),/"));
        $r->general()->confirmation($this->http->FindSingleNode("//text()[{$this->starts($this->t('dnr'))}]/following-sibling::*[1]"));
        $r->general()->cancelled();

        $segmentsTexts = array_unique($this->http->FindNodes("//tr[ *[{$this->eq($this->t('Ticket ID'), "translate(.,':','')")} or {$this->eq($this->t('Refund amount'), "translate(.,':','')")}] ]/preceding-sibling::tr[normalize-space()][1]", null, "/^.*\s+{$this->opt($this->t('with'))}\s/"));

        foreach ($segmentsTexts as $segText) {
            if (preg_match("/{$this->opt($this->t('From'))} (.+?) {$this->opt($this->t('to'))} (.+?)\s*" . "{$this->opt($this->t('on'))} (.+?) {$this->opt($this->t('with'))} /", $segText, $m)) {
                // From AMSTERDAM C. to ZONE BRUXELLES/BRUSSEL on 11/8/2020 with NRT ticket (BeNeLux)
                $s = $r->addSegment();
                $s->departure()->name($m[1]);
                $s->arrival()->name($m[2]);
                $s->departure()->date2($this->ModifyDateFormat($m[3]));
            }
        }
    }

    private function parseEmail(Email $email): void
    {
        $this->logger->debug(__FUNCTION__ . '()');

        $r = $email->add()->train();
        $email->ota()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->starts($this->t('Booking code'))}]/following::text()[normalize-space()!=''][1]"));
        $confNo = $this->http->FindSingleNode("//text()[{$this->starts($this->t('PNR(s)'))}]/following::text()[normalize-space()!=''][1]");

        if (empty($confNo) && $this->http->XPath->query("//text()[{$this->contains($this->t('PNR(s)'))}]")->length === 0) {
            $r->general()
                ->noConfirmation();
        } else {
            $r->general()->confirmation($confNo);
        }

        $pax = array_unique($this->http->FindNodes("//img[contains(@src, '/reiziger.')]/ancestor::td[1]/following-sibling::td[1]", null, "/^(.*?)\s+-/"));

        if (!empty($pax)) {
            $r->general()
                ->travellers($pax);
        }

        $totalPrice = $this->http->FindSingleNode("//*/tr[normalize-space()][1][{$this->eq($this->t('Total price'), "translate(.,':','')")}]/following-sibling::tr[normalize-space()][1]", null, true, '/^.*\d.*$/');

        if (preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $totalPrice, $matches)) {
            // €55 80  ->  €55,80
            $matches['amount'] = preg_replace('/(\d+)\s+(\d{2})$/', '$1,$2', $matches['amount']);
            $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
            $r->price()->currency($matches['currency'])->total(PriceHelper::parse($matches['amount'], $currencyCode));
        }

        $this->segmentsRoutedetails($r);

        if (count($r->getSegments()) > 0) {
            return;
        }
        $xpath = "//*[{$this->eq($this->t('departure'))}]/ancestor::tr[1]";
        $segments = $this->http->XPath->query($xpath);
        $this->logger->debug("[XPATH]: " . $xpath);

        foreach ($segments as $i => $root) {
            $s = $r->addSegment();
            $train = $this->http->FindSingleNode("./following-sibling::tr[" . $this->contains($this->t("Trainnumber:")) . "][1]/td[3]",
                $root);

            if (!empty($train)) {
                $s->extra()->number($train);
            } else {
                $s->extra()->noNumber();
            }

            $s->departure()
                ->name($this->http->FindSingleNode("./preceding-sibling::tr[2]", $root, true, "/^(.*?)\s+-\s+.*?$/"))
                ->date(strtotime($this->normalizeDate($this->http->FindSingleNode("./td[3]", $root))));

            $s->arrival()
                ->name($this->http->FindSingleNode("./preceding-sibling::tr[2]", $root, true, "/^.*?\s+-\s+(.*?)$/"));

            $arrDate = strtotime($this->normalizeDate($this->http->FindSingleNode("following-sibling::tr[1][{$this->contains($this->t('arrival'))}]/td[3]", $root)));

            if (!empty($arrDate)) {
                $s->arrival()
                    ->date($arrDate);
            } elseif (empty($this->http->FindSingleNode("following-sibling::tr[position()<4][not({$this->contains($this->t('arrival'))})]", $root))
                && !empty($this->http->FindSingleNode("./td[3]", $root, true, "/^[^:]+$/"))
                && !empty($s->getDepName())
            ) {
                $s->arrival()
                    ->noDate();
                $r->removeSegment($s);

                continue;
            }

            $passenger = $this->http->FindSingleNode("./following-sibling::tr[position() < 10][{$this->contains($this->t("Passengers:"))}][1]/following-sibling::tr[1]/td[3]",
                $root);

            if (preg_match("/{$this->opt($this->t('Coach'))}[ ]+(\d+)/", $passenger, $m)) {
                $s->extra()
                    ->car($m[1]);
            }

            if (preg_match("/{$this->opt($this->t('Seat'))}[ ]+(\w+)/", $passenger, $m)) {
                $s->extra()->seat($m[1]);
            }

            if (preg_match("/^\s*\d+\s+x\s+.+(?: - | Seat reservation )(.*\b(class|klas)\b.*)/u", $this->http->FindSingleNode("./following-sibling::tr[position() < 10][{$this->contains($this->t("Passengers:"))}][1]/td[3]",
                $root), $m)) {
                $s->extra()
                    ->cabin($m[1]);
            }

            $type = $this->http->FindSingleNode("./following-sibling::tr[{$this->starts($this->t("By:"))}][1]/td[3]",
                    $root);

            if (!empty($type)) {
                $s->extra()->type($type);
            }

            $s->extra()
                ->duration($this->http->FindSingleNode("./following-sibling::tr[position() < 5][{$this->contains($this->t("Travel time:"))}][1]/td[3]",
                    $root), true, true);
        }
    }

    private function segmentsRoutedetails(Train $r): void
    {
        $xpath = "//tr[td[normalize-space(.)][1][{$this->eq($this->t("D"))}] and following-sibling::tr[1][td[normalize-space(.)][1][{$this->eq($this->t("A"))}]]]";
        $segments = $this->http->XPath->query($xpath);

        if ($segments->length == 0) {
            return;
        }

        $dates = $this->http->FindNodes("//tr[td[normalize-space()][1][{$this->eq($this->t('departure'))}]]/td[normalize-space()][2]", null, "/(.+) {$this->opt($this->t('at'))} /u");
        $routedetails = $this->http->FindNodes("//text()[{$this->eq($this->t("Routedetails"))}]");

        if (count($dates) == 0 || count($dates) > 2 || count($dates) !== count($routedetails)) {
            return;
        }

        foreach ($segments as $root) {
            if (!empty($this->http->FindSingleNode("(./ancestor::*[not({$this->contains($this->t("Routedetails"))})]/preceding-sibling::*" . $xpath . ")[1]",
                $root))) {
                $columns[2][] = $root;
            } else {
                $columns[1][] = $root;
            }
        }

        if (count($dates) !== count($columns)) {
            return;
        }

        $detailinfo = [];

        foreach ($columns as $i => $roots) {
            $details = [];
            $tariff = $this->http->FindNodes("./following::text()[" . $this->eq($this->t('Tariff')) . "]/ancestor::tr[1]/following-sibling::tr[1]/td[normalize-space()][2]");
            $tariff = implode("\n", $this->http->FindNodes("//tr[.//text()[" . $this->eq($this->t('Tariff')) . "]]/following-sibling::tr[1]/td[normalize-space()][" . $i . "]/descendant::tr[1]/ancestor::*[1]/*"));
            $tariff = "\n" . $tariff . "\n";

            $segments = array_filter(preg_split("/\n([\p{Lu}\W\d ]+ - [\p{Lu}\W\d ]+)\n/u", $tariff, -1, PREG_SPLIT_DELIM_CAPTURE));

            foreach ($segments as $row) {
                if (preg_match("/^\s*([\p{Lu}\W\d ]+ - [\p{Lu}\W\d ]+)\s*$/u", $row)) {
                    $name = $row;
                } else {
                    $details[isset($name) ? $name : 'unknown'][] = $row;
                }
            }

            if (count($roots) !== count($details)) {
                $detailinfo[$i] = [];
            } else {
                foreach ($details as $d) {
                    $detailinfo[$i][] = $d;
                }
            }
        }

//        $this->logger->debug('$detailinfo = ' . print_r($detailinfo, true));

        foreach ($columns as $i => $roots) {
            foreach ($roots as $j => $root) {
                $s = $r->addSegment();

                $s->extra()->noNumber();

                $date = $this->normalizeDate($dates[$i - 1]);

                $time = $this->http->FindSingleNode("./td[normalize-space()][2]", $root);
                $datetime = null;

                if (!empty($date) && !empty($time)) {
                    $datetime = strtotime($this->normalizeDate($date . " " . $time));
                }

                $s->departure()
                    ->name($this->http->FindSingleNode("./td[normalize-space()][3]", $root))
                    ->date($datetime);

                $time = $this->http->FindSingleNode("./following-sibling::tr[1]/td[normalize-space()][2]", $root);
                $datetime = null;

                if (!empty($date) && !empty($time)) {
                    $datetime = strtotime($this->normalizeDate($date . " " . $time));
                }
                $s->arrival()
                    ->name($this->http->FindSingleNode("./following-sibling::tr[1]/td[normalize-space()][3]", $root))
                    ->date($datetime)
                ;

                $type = null;
                $typeSrt = $this->http->FindSingleNode("./td[.//img][last()]//img/@src", $root, false, "/(\w+)\.png\s*$/");

                switch ($typeSrt) {
                    case 'tha':
                        $type = 'Thalys';

                        break;

                    case 'ic':
                        $type = 'IC';

                        break;
                }

                if (!empty($type)) {
                    $s->extra()->type($type);
                }

                $info = implode("\n", $detailinfo[$i][$j] ?? []);

                if (preg_match("/(?:^|\n)\s*\d+\s+x\s+.+(?: - | Seat reservation | with tariff )(.*\b(class|klas)\b.*)/u", $info, $m)) {
                    $s->extra()
                        ->cabin($m[1], true, true);
                }

                if (preg_match("/{$this->opt($this->t('Coach'))}\s+(\d+),?\s+{$this->opt($this->t('Seat'))}\s+(\w+)/", $info, $m)) {
                    $s->extra()
                        ->car($m[1])
                        ->seat($m[2]);
                }
            }
        }
    }

    private function assignLang(): bool
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) ) {
                continue;
            }
            if (!empty($phrases['departure']) && !empty($phrases['arrival']) && $this->http->XPath->query("//*[{$this->eq($phrases['departure'])}]/following::*[{$this->eq($phrases['arrival'])}]")->length > 0
                || !empty($phrases['dnr']) && $this->http->XPath->query("//text()[{$this->starts($phrases['dnr'])}]")->length > 0
            ) {
                $this->lang = $lang;
                return true;
            }
        }
        return false;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function normalizeDate($str)
    {
        $in = [
            "/^\w+\s+(\d+\s+\w+\s+\d{4})(?:\s+at|\s+om)?\s+(\d+:\d+)$/",
            "/^\w+\s+(\d+\s+\w+\s+\d{4})$/",
        ];
        $out = [
            "$1, $2",
            "$1",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("/\d+\s+([^\d\s]+)\s+\d{4}/", $str, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return $str;
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

    private function starts($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function opt($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }

        return implode("|", array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field));
    }
}
