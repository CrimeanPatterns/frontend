<?php

namespace AwardWallet\Engine\norwegiancruise\Email;

// TODO: delete what not use
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class ImportantInformation extends \TAccountChecker
{
    public $mailFiles = "norwegiancruise/it-469464774.eml, norwegiancruise/it-481140135.eml, norwegiancruise/it-484340997.eml, norwegiancruise/it-572499343.eml, norwegiancruise/it-904222634.eml, norwegiancruise/it-909607691.eml";

    public $lang;

    public static $dictionary = [
        'en' => [
            'Revised'     => 'Revised',
            'Destination' => 'Destination',
            // information regarding {shipName} 's sailing on {date}
            "'s sailing on "           => ["'s sailing on ", "'s sailings from", 'upcoming sailing on', 'upcoming sailing onboard',
                'cruise setting sail on', '\'s itineraries from', "' sailings from", "' sailings ",
            ],
            "upcoming sailing onboard" => ['upcoming sailing onboard', 'upcoming vacation on board'],
            "ShipName"                 => [
                ["information regarding", "'s sailing on "],
                ["information regarding", "'s sailings from "],
                ["result of a full ship charter,", "'s sailing on "],
                ["chosen to sail with us onboard", "and thank you for"],
                ["upcoming sailing onboard", " on "],
                ["upcoming vacation on board", " on "],
                ["itinerary for your upcoming", "cruise setting sail on"],
                ["chosen to sail with us on board", "'s "],
                ["chosen to sail with us on board", " and thank you "],
                ["The itinerary for", "'s sailings from"],
                ["The itinerary for", "' sailings from"],
                ["The itinerary for", "'s sailing on"],
                ["The itinerary for", "' sailings"],
                ["", "'s itineraries from"],
                // ["", ""],
            ],
            "has been canceled" => ["has been canceled", "ITINERARY CANCELATION", "has been cancelled"],
        ],
    ];

    private $detectFrom = "donotreply@ncl.com";
    private $detectSubject = [
        // en
        'Important Information from Norwegian Cruise Line:',
    ];
    private $emailSubject;

    private $detectBody = [
        'en' => [
            'share this information with impacted guests',
            'share the below information with impacted guests',
            'are asked to please ensure that impacted clients review this information',
            'are asked to please ensure that impacted guests review this information',
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]ncl\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false
            && stripos($headers["subject"], 'Norwegian Cruise Line') === false
        ) {
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
            $this->http->XPath->query("//a[{$this->contains(['www.ncl.com/'], '@href')}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Norwegian Cruise Line'])}]")->length === 0
        ) {
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
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
        }

        $this->emailSubject = $parser->getSubject();

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
            if (isset($dict["Revised"], $dict["Destination"])) {
                if ($this->http->XPath->query("//*[{$this->contains($dict['Revised'])}]")->length > 0
                    && $this->http->XPath->query("//*[{$this->contains($dict['Destination'])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }

            if (isset($dict["has been canceled"])) {
                if ($this->http->XPath->query("//*[{$this->contains($dict['has been canceled'])}]")->length > 0
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
        $c = $email->add()->cruise();

        // General
        $conf = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Reservation:'))}]",
            null, true, "/^\s*{$this->opt($this->t('Reservation:'))}\s*(\d{5,})\s*$/");

        if (!empty($conf)) {
            $c->general()
                ->confirmation($conf);
        } elseif (empty($this->http->FindSingleNode("(//text()[{$this->eq($this->t('Revised'))}]/preceding::text()[{$this->starts('Reservation')}])"))) {
            $c->general()
                ->noConfirmation();
        } else {
            $c->general()
                ->confirmation($conf);
        }

        $ship = '';

        foreach ((array) $this->t("ShipName") as $dict) {
            if (is_array($dict) && count($dict) == 2) {
                if (empty($dict[0])) {
                    $ship = $this->http->FindSingleNode("//text()[{$this->contains($dict[1])}]",
                        null, true, "/^\s*([A-Z][A-z ]+)\s*{$this->opt($dict[1])}/");
                } else {
                    $ship = $this->http->FindSingleNode("//text()[{$this->contains($dict[0])}]",
                        null, true, "/{$this->opt($dict[0])}\s+([A-Z][A-z ]+)\s*{$this->opt($dict[1])}/");
                }

                if (!empty($ship)) {
                    break;
                }
            }
        }

        if (empty($ship) && preg_match("/Important Information from Norwegian Cruise Line: ([A-Z][A-z ]+) [\d\.]{6,} Itinerary Change/", $this->emailSubject, $m)) {
            $ship = $m[1];
        }

        $c->details()
            ->ship($ship);

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('has been canceled'))}]")->length > 0) {
            $c->general()
                ->status('canceled')
                ->cancelled();

            return true;
        }
        $startDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->contains($this->t("'s sailing on "))}]",
            null, true, "/{$this->opt($this->t("'s sailing on "))}\s*(\w+[,. \\/]{1,3}\w+[,. \\/]{1,3}\w+[,. \\/]{0,3})(?:\.| through |, has| and )/"));

        if (empty($startDate)) {
            $startDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->contains($this->t("upcoming sailing onboard"))}]", null, true,
                "/{$this->opt($this->t("upcoming sailing onboard"))} .*? on (\w+[,. \\/]{1,3}\w+[,. \\/]{1,3}\w+[,. \\/]{0,3})(?:\.| through |, | has)/"));
        }

        if (empty($startDate)) {
            $startDate = $this->normalizeDate($this->http->FindSingleNode("//text()[{$this->contains('you have chosen to sail with us on board')}]", null, true,
                "/you have chosen to sail with us on board .*?'s (\w+[,. \\/]{1,3}\w+[,. \\/]{1,3}\d{4}[,. \\/]{0,3}) sailing and/"));
        }

        if (empty($startDate) && preg_match("/Important Information from Norwegian Cruise Line: [A-Z][A-z ]+ ([\d\.]{6,})(?: - [\d\.]{6,})? Itinerary Change/", $this->emailSubject, $m)) {
            $startDate = $this->normalizeDate(str_replace('.', '/', $m[1]));
        }

        if (empty($startDate)) {
            $this->logger->debug('empty dates');

            return false;
        }

        $xpath = "(//tr/*[descendant::text()[normalize-space()][1][{$this->eq($this->t('Revised'))}]][{$this->contains($this->t('Destination'))}])[1]"
            . "/descendant::tr[*[2][{$this->contains($this->t('Destination'))}]]/following-sibling::tr";
        $nodes = $this->http->XPath->query($xpath);
        // $this->logger->debug('$xpath = '.print_r( $xpath,true));

        $segDate = $startDate;

        foreach ($nodes as $i => $root) {
            $row = $this->http->FindNodes('*', $root);

            if (count($row) !== 5 || empty($row[0]) || empty($row[1])) {
                $c->addSegment();
                $this->logger->debug('error in table row. Segment: ' . print_r($row, true));

                break;
            }

            if ($i == 0 || $row[0] === $this->http->FindSingleNode('*[1]', $nodes->item($i - 1))) {
            } else {
                $segDate = strtotime("+1 day", $segDate);
            }

            if ((int) date("w", $segDate) !== (WeekTranslate::number1(WeekTranslate::translate($row[0])) % 7)) {
                $c->addSegment();
                $this->logger->debug('error in dates. Segment: ' . print_r($row, true));

                break;
            }

            $name = $row[1];
            $name = preg_replace("/^\s*OVERNIGHT IN\s+/", '', $name);
            $row[2] = preg_replace("/^\s*Overnight\s*$/", '', $row[2]);
            $row[3] = preg_replace("/^\s*Overnight\s*$/", '', $row[3]);

            if (empty($row[2]) && empty($row[3])) {
                continue;
            }

            $ashore = strtotime($row[2], $segDate);
            $aboard = strtotime($row[3], $segDate);

            if (!empty($aboard) && !empty($ashore)) {
                $s = $c->addSegment();
                $s
                    ->setAboard($aboard)
                    ->setAshore($ashore)
                    ->setName($name)
                ;
            } elseif (isset($s) && !empty($s->getAshore()) && empty($s->getAboard()) && $s->getName() === $name && empty($ashore) && !empty($aboard)) {
                $s
                    ->setAboard($aboard);
            } else {
                $s = $c->addSegment();

                $s->setName($name);

                if (!empty($ashore)) {
                    $s
                        ->setAshore($ashore);
                }

                if (!empty($aboard)) {
                    $s
                        ->setAboard($aboard);
                }
            }
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

        $in = [
            //            // Sun, Apr 09
            //            '/^\s*(\w+),\s*(\w+)\s+(\d+)\s*$/iu',
            //            // Tue Jul 03, 2018 at 1 :43 PM
            //            '/^\s*[\w\-]+\s+(\w+)\s+(\d+),\s*(\d{4})\s+(\d{1,2}:\d{2}(\s*[ap]m)?)\s*$/ui',
        ];
        $out = [
            //            '$1, $3 $2 ' . $year,
            //            '$2 $1 $3, $4',
        ];

        $date = preg_replace($in, $out, $date);
//        $this->logger->debug('date replace = ' . print_r( $date, true));

        // $this->logger->debug('date end = ' . print_r( $date, true));

        return strtotime($date);
    }

    private function opt($field, $delimiter = '/')
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
            return preg_quote($s, $delimiter);
        }, $field)) . ')';
    }
}
