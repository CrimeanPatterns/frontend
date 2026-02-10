<?php

namespace AwardWallet\Engine\limo\Email;

// TODO: delete what not use
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class ReservationConfirmation2 extends \TAccountChecker
{
    public $mailFiles = "limo/it-139590675.eml, limo/it-163549996.eml";

    public $dateFormat = null;
    public $lang = 'en';
    public static $dictionary = [
        'en' => [
            //            'Reservation Confirmation #' => 'Reservation Confirmation #',
        ],
    ];

    private $detectBody = [
        'en' => [
            'Passenger & Routing Information',
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (preg_match("/Conf# \d+ For .* \[ *[\d\\/]+ *- *\d+[:.]\d+[APMapm ]*\]/", $headers['subject'])) {
            return true;
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//img[{$this->contains(['.mylimobiz.com', 'mylimowebsite.com'], '@src')}]")->length === 0) {
            return false;
        }

        foreach ($this->detectBody as $detectBody) {
            if ($this->http->XPath->query("//*[{$this->eq($detectBody)}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
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

    private function parseEmailHtml(Email $email)
    {
        $t = $email->add()->transfer();

        // General
        $t->general()
            ->confirmation($this->nextTd($this->t("Reservation#"), "/^\s*(\d{4,})(?:\s+.*)?$/"))
            ->traveller(preg_replace('/\s+\d of \d+\s*$/', '',
                $this->http->FindSingleNode("//text()[{$this->starts($this->t("Primary Passenger:"))}]/following::text()[normalize-space()][1]"), true))
        ;

        // Price
        $currency = $this->http->FindSingleNode("//td[not(.//td)][{$this->starts($this->t("Total Due ("))}]",
            null, true, "/\(([A-Z]{3})\)/");

        if (!empty($currency)) {
            $t->price()
                ->total(PriceHelper::parse($this->http->FindSingleNode("//td[not(.//td)][{$this->starts($this->t("Total Due ("))}]/following-sibling::td[normalize-space()][1]"),
                    $currency))
                ->currency($currency);
        }

        $pudate = $this->normalizeDate($this->nextTd($this->t("Pick-up Date:")));
        $putime = $this->nextTd($this->t("Pick-up Time:"));
        $putime = preg_replace("/^\s*(\d+:\d+\s*(?:[ap]m)?)\s*\/\s*\d+:\d+\s*$/i", '$1', $putime);
        $guests = $this->http->FindSingleNode("//tr[not(.//tr)][td[1][{$this->starts($this->t("# of Pax"))}]]/following-sibling::tr[normalize-space()][1]/td[1]",
            null, true, "/^\s*(\d+)\s*$/");
        $carType = $this->http->FindSingleNode("//tr[not(.//tr)][td[3][{$this->starts($this->t("Vehicle Type"))}]]/following-sibling::tr[normalize-space()][1]/td[3]");

        $tripText = implode("\n",
            $this->http->FindNodes("//text()[{$this->starts($this->t("PU:"))}]/ancestor::td[1]//text()[normalize-space()]"));

        $rows = $this->split("/(?:^|\n)\s*((?:PU:|ST:|DO:|WT:)\s+)/", $tripText);

        foreach ($rows as $i => $row) {
            if (preg_match("/^\s*(?:WT|ST)\s*:\s*--\s*:/", $row)) {
                unset($rows[$i]);

                continue;
            }
            $rows[$i] = preg_replace("/\s+Notes:\s+[\s\S]+/", '', $row);
        }

        $rows = array_values($rows);

        foreach ($rows as $i => $row) {
            if ($i == count($rows) - 1) {
                break;
            }

            $s = $t->addSegment();

            // Departure
            $r = $this->parseRow($row);

            if (!empty($r['code'])) {
                $s->departure()
                    ->code($r['code']);
            } else {
                $s->departure()
                    ->address($r['address']);
            }
            $time = $r['time'];

            if ($i === 0 && !empty($putime)) {
                $time = $putime;
            }

            if (!empty($pudate) && !empty($time)) {
                $s->departure()
                    ->date(strtotime($time, $pudate));
            } else {
                $s->departure()
                    ->noDate();
            }

            // Arrival
            $r = $this->parseRow($rows[$i + 1] ?? '');

            if (!empty($r['code'])) {
                $s->arrival()
                    ->code($r['code']);
            } else {
                $s->arrival()
                    ->address($r['address']);
            }
            $time = $r['time'];

            if ($i === count($rows) - 2 && !empty($dotime)) {
                $time = $dotime;
            }

            if (!empty($pudate) && !empty($time)) {
                $s->arrival()
                    ->date(strtotime($time, $pudate));
            } else {
                $s->arrival()
                    ->noDate();
            }

            // Extra
            $s->extra()
                ->type($carType)
                ->adults($guests)
            ;
        }

        return true;
    }

    private function parseRow($row)
    {
        $result = [
            'code'    => null,
            'address' => null,
            'time'    => null,
        ];

        if (preg_match("/^\s*\w+\s*:\s*([^\d:]+|\d{1,2}:\d+[^\d:]*)\s*:\s*(.+)/", $row, $m)) {
            if (preg_match("/^([A-Z]{3}) - /", $m[2], $mat)) {
                $result['code'] = $mat[1];
            } elseif (preg_match("/^(.+?)[, ]*\/\s*[A-Z\d]{2} - .+From\/To:.+Flt#/", $m[2], $mat)) {
                // Philadelphia International Airport, / AA - American AirlinesFrom/To: LHR, Term/Gate: A Flt# 737, ETA/ETD: 15:15:00,
                $result['address'] = trim($mat[1]);
            } else {
                $result['address'] = trim($m[2]);
            }

            if (preg_match("/^\d+:\d+/", $m[1])) {
                $result['time'] = $m[1];
            }
        }

        return $result;
    }

    private function nextTd($name, $regexp = null)
    {
        return $this->http->FindSingleNode("//td[not(.//td)][{$this->eq($name)}]/following-sibling::td[normalize-space()][1]", null, true, $regexp);
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

    private function normalizeDate(?string $date)
    {
        // $this->logger->debug('date begin = ' . print_r($date, true));
        if (empty($date)) {
            return null;
        }

        if (preg_match("/^\s*(?<date>(?<d1>\d{1,2})\/(?<d2>\d{1,2})\/\d{4})(?:\s*-\s*(?<wday>[[:alpha:]]+))?\s*$/u", $date, $m)
            || preg_match("/^\s*(?<date>(?<d1>\d{1,2})\/(?<d2>\d{1,2})\/\d{4})\s*-\s*{$this->patterns['time']}\s*$/", $date, $m)
        ) {
            // 02/19/2022 - Saturday    |    16/03/2025 - 02:00 PM
            if ($this->dateFormat == 'dmy') {
                $date = str_replace("/", '.', $m['date']);

                return strtotime($date);
            } elseif ($this->dateFormat == 'mdy') {
                $date = $m['date'];

                return strtotime($date);
            } elseif (empty($this->dateFormat) && (int) $m['d1'] > 12 && (int) $m['d2'] <= 12) {
                $date = str_replace("/", '.', $m['date']);

                return strtotime($date);
            } elseif (empty($this->dateFormat) && (int) $m['d1'] <= 12 && (int) $m['d2'] > 12) {
                $date = $m['date'];

                return strtotime($date);
            } elseif (!empty($m['wday'])) {
                $w = WeekTranslate::number1($m['wday']);
                $date1 = strtotime($m['date']);
                $date2 = strtotime(str_replace("/", '.', $m['date']));

                if (!empty($date1) && $w == date("w", $date1)
                    && (empty($date2) || $w !== date("w", $date2))
                ) {
                    $this->dateFormat = 'mdy';

                    return $date1;
                } elseif (!empty($date2) && $w == date("w", $date2)
                    && (empty($date1) || $w !== date("w", $date1))
                ) {
                    $this->dateFormat = 'dmy';

                    return $date2;
                }

                if (empty($date1) && !empty($date2) && $w == date("w", $date2)) {
                    $this->dateFormat = 'dmy';
                    $date = str_replace("/", '.', $m['date']);

                    return strtotime($date);
                }
                $w1 = date("w", $date1);
                $w2 = date("w", $date2);

                if ($w === $w1) {
                    $date = $m['date'];
                    $this->dateFormat = 'mdy';

                    return strtotime($date);
                }

                if ($w === $w2) {
                    $this->dateFormat = 'dmy';
                    $date = str_replace("/", '.', $m['date']);

                    return strtotime($date);
                }
                $date = $m['date'];
            }
        }

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
