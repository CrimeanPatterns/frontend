<?php

namespace AwardWallet\Engine\finnair\Email;

use AwardWallet\Schema\Parser\Email\Email;

// parsers for HTML-format: tapportugal/AirTicket

class BoardingPassPdf extends \TAccountChecker
{
    public $mailFiles = "finnair/it-907211904.eml, finnair/it-913527743.eml";

    public $lang = '';

    public static $dictionary = [
        'en' => [
            // PDF
            'Departure' => ['Departure'],
            'Flight' => ['Flight'],
            'flightEnd' => ['Lounge', 'At the airport', 'Baggage', 'Travel documents', 'Additional travel information'],
        ]
    ];

    private $patterns = [
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
        'travellerName2' => '[[:upper:]]+(?: [[:upper:]]+)*[ ]*\/[ ]*(?:[[:upper:]]+ )*[[:upper:]]+', // KOH / KIM LENG MR
        'eTicket' => '\d{3}(?: | ?- ?)?\d{5,}(?: | ?[-\/] ?)?\d{1,3}', // 175-2345005149-23  |  1752345005149/23
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]finnair\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }
        return array_key_exists('subject', $headers) && stripos($headers['subject'], 'boarding pass confirmed') !== false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $detectProv = $this->detectEmailFromProvider( rtrim($parser->getHeader('from'), '> ') );

        $pdfs = $parser->searchAttachmentByName('.*pdf');

        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (empty($textPdf) || !$detectProv
                && strpos($textPdf, 'Spend your time at premium Finnair') === false
                && strpos($textPdf, 'information from the Finnair app') === false
            ) {
                continue;
            }

            if ($this->assignLang($textPdf)) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $pdfs = $parser->searchAttachmentByName('.*pdf');

        /* Step 1: find supported formats */

        $usingLangs = $textsBP = [];
        
        foreach ($pdfs as $pdf) {
            $textPdf = \PDF::convertToText($parser->getAttachmentBody($pdf));

            if (empty($textPdf) || !$this->assignLang($textPdf)) {
                continue;
            }

            $textPdf = preg_replace([
                "/\n[ ]*(?:PRIORITY|CAOB)$/im",
            ], [
                '',
            ], $textPdf);

            $usingLangs[] = $this->lang;
            $fileName = $this->getAttachmentName($parser, $pdf);
            $pdfParts = $this->splitText($textPdf, "/((?:^[ ]*{$this->patterns['travellerName2']}$\s+)?^[ ]{0,20}[A-Z]{3}[ ]{40,}[A-Z]{3})$/mu", true);

            foreach ($pdfParts as $partText) {
                $textsBP[] = [
                    'lang' => $this->lang,
                    'text' => $partText,
                    'filename' => $fileName,
                ];
            }
        }

        /* Step 2: parsing */

        $this->parsePdf($email, $textsBP);

        if (count(array_unique($usingLangs)) === 1
            || count(array_unique(array_filter($usingLangs, function ($item) { return $item !== 'en'; }))) === 1
        ) {
            $email->setType('BoardingPassPdf' . ucfirst($usingLangs[0]));
        }

        return $email;
    }

    private function parsePdf(Email $email, array $textsBP): void
    {
        $f = $email->add()->flight();
        $segObjects = $travellers = $bookingRefValues = $tickets = $accounts = [];

        foreach ($textsBP as $textBP) {
            $this->lang = $textBP['lang'];

            $text = preg_replace([
                "/^(.+?)\n+[ ]*{$this->opt($this->t('flightEnd'))}.*$/is",
            ], [
                '$1',
            ], $textBP['text']);

            /* Step 1: get values */

            $it = [
                'traveller' => null,
                'codeDep' => null, 'codeArr' => null,
                'nameDep' => null, 'nameArr' => null,
                'terminalDep' => null, 'terminalArr' => null,
                'seat' => null,
                'dateDep' => 0, 'dateArr' => 0,
                'airline' => null, 'flightNumber' => null,
                'cabin' => null,
                'pnr' => null,
                'ticket' => null, 'account' => null,
            ];

            if (preg_match("/^\s*({$this->patterns['travellerName']}|{$this->patterns['travellerName2']})\n/u", $text, $m)
                && strpos($m[1], '  ') === false
            ) {
                $it['traveller'] = $this->normalizeTraveller($m[1]);
            } elseif (preg_match_all("/^[ ]*({$this->patterns['travellerName2']})$/mu", $text, $travellerMatches)
                && count(array_unique($travellerMatches[1])) === 1
            ) {
                // it-913527743.eml
                $travellerName = array_shift($travellerMatches[1]);
                $it['traveller'] = $this->normalizeTraveller($travellerName);
                $text = preg_replace("/^[ ]*{$this->opt($travellerName)}$/m", '', $text); // remove all traveller names
            }

            if (preg_match("/^(([ ]*(?<codeDep>[A-Z]{3})[ ]+(?<codeArr>[A-Z]{3}))\n[\s\S]*?)(?:\n\n|\n[ ]*(?i){$this->opt($this->t('Gate opens'))})/m", $text, $matches)) {
                $it['codeDep'] = $matches['codeDep'];
                $it['codeArr'] = $matches['codeArr'];

                $tablePos = [0];
                $tablePos[] = round(mb_strlen($matches[2]) / 2);
                $table = preg_replace('/^[ ]*[A-Z]{3}[ ]*\n+(.+?)\s*$/s', '$1', $this->splitCols($matches[1], $tablePos));

                if (preg_match("/^\s*([\s\S]{2,}?)\n+[ ]*{$this->opt($this->t('Terminal'))}/i", $table[0], $m)
                    || preg_match("/^\s*(\S.*\S)\s*$/s", $table[0], $m)
                ) {
                    $it['nameDep'] = preg_replace('/\s+/', ' ', $m[1]);
                }

                if (preg_match("/^\s*([\s\S]{2,}?)\n+[ ]*{$this->opt($this->t('Terminal'))}/i", $table[1], $m)
                    || preg_match("/^\s*(\S.*\S)\s*$/s", $table[1], $m)
                ) {
                    $it['nameArr'] = preg_replace('/\s+/', ' ', $m[1]);
                }
            
                if (preg_match("/^[ ]*{$this->opt($this->t('Terminal'))}[-\s]*([^\-\s][\s\S]*)/im", $table[0], $m)) {
                    $it['terminalDep'] = preg_replace('/\s+/', ' ', $m[1]);
                }

                if (preg_match("/^[ ]*{$this->opt($this->t('Terminal'))}[-\s]*([^\-\s][\s\S]*)/im", $table[1], $m)) {
                    $it['terminalArr'] = preg_replace('/\s+/', ' ', $m[1]);
                }
            }

            if (preg_match("/\n(?<headRow>[ ]*{$this->opt($this->t('Boarding group'))}[ :]+{$this->opt($this->t('Seat'))}[ :]*)\n+(?<content>[\s\S]*?)(?:\n\n|\n[ ]*{$this->opt($this->t('Departure'))})/", $text, $matches)) {
                $tablePos = [0];
                $tablePos[] = round(mb_strlen($matches['headRow']) / 2);
                $table = $this->splitCols($matches['content'], $tablePos);

                if (count($table) === 2 && preg_match('/^\s*(\d+[A-Z])\s*$/', $table[1], $m)) {
                    $it['seat'] = $m[1];
                }
            }

            if (preg_match("/\n(?<headRow>[ ]*{$this->opt($this->t('Departure'))}[ :]+{$this->opt($this->t('Flight'))}[ :]*)\n+(?<content>[\s\S]*?)(?:\n\n|\n[ ]*{$this->opt($this->t('Sold as'))})/", $text, $matches)) {
                $tablePos = [0];
                $tablePos[] = round(mb_strlen($matches['headRow']) / 2);
                $table = $this->splitCols($matches['content'], $tablePos);

                if (count($table) > 0 && preg_match("/^\s*(?<time>{$this->patterns['time']})[,\s]+(?<date>[\s\S]*\b\d{4})\s*$/", $table[0], $m)) {
                    $dateDep = strtotime(preg_replace('/\s+/', ' ', $m['date']));
                    $it['dateDep'] = strtotime($m['time'], $dateDep);
                }

                if (count($table) === 2 && preg_match("/^\s*(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<number>\d+)\s*$/", $table[1], $m)) {
                    $it['airline'] = $m['name'];
                    $it['flightNumber'] = $m['number'];
                }
            }

            if (preg_match("/\n(?<headRow>[ ]*{$this->opt($this->t('Fare type'))}[ :]+{$this->opt($this->t('Ticket number'))}[ :]*)\n+(?<content>[\s\S]*?)(?:\n\n|\n[ ]*{$this->opt($this->t('Frequent flyer'))}|\s*$)/", $text, $matches)) {
                $tablePos = [0];
                $tablePos[] = round(mb_strlen($matches['headRow']) / 2);
                $table = $this->splitCols($matches['content'], $tablePos);

                if (count($table) === 2 && preg_match("/^\s*(\S.*?)\s*$/s", $table[0], $m)) {
                    $it['cabin'] = preg_replace('/\s+/', ' ', $m[1]);
                }

                if (count($table) === 2 && preg_match("/^\s*({$this->patterns['eTicket']})\s*$/s", $table[1], $m)) {
                    $it['ticket'] = $m[1];
                }
            }

            if (preg_match("/^[ ]*{$this->opt($this->t('Frequent flyer'))}[ :]*\n+[ ]{0,20}(?:Finnair Plus(?: Plat| Gold)?[ ]*[:]+[ ]*)?(?-i)([A-Z\d][-A-Z\d ]*?)(?i)(?: Emerald| Sapphire| Ruby|[ ]{2}|$)/im", $text, $m)) {
                $it['account'] = $m[1];
            }

            if (!empty($it['airline']) && !empty($it['flightNumber']) && !empty($it['dateDep'])) {
                // get fields from HTML

                $flightVal = $it['airline'] . $it['flightNumber'];
                $dateFromVal = date('d M Y - H:i', $it['dateDep']);

                $roots = $this->http->XPath->query("//text()[ {$this->eq($this->t('From'), "translate(.,':','')")} and following::text()[normalize-space()][position()<5][{$this->starts($dateFromVal)}] ]/ancestor::*[ descendant::text()[{$this->eq($this->t('Booking Reference'), "translate(.,':','')")}] ][1][ descendant::text()[{$this->eq($this->t('Flight'), "translate(.,':','')")}][1]/following::text()[normalize-space()][1][{$this->starts($flightVal)}] ]");

                if ($roots->length === 1) {
                    $root = $roots->item(0);
                    $it['pnr'] = $this->http->FindSingleNode("descendant::text()[{$this->eq($this->t('Booking Reference'), "translate(.,':','')")}]/following::text()[normalize-space()][1]", $root, true, "/^[A-Z\d]{5,8}$/");

                    $dateToValues = array_filter($this->http->FindNodes("descendant::text()[{$this->eq($this->t('To'), "translate(.,':','')")}]/following::text()[normalize-space()][position()<5]", $root, "/^.+?\b\d{4}[- ]+{$this->patterns['time']}/"));

                    if (count($dateToValues) === 1) {
                        $dateToVal = array_shift($dateToValues);

                        if (preg_match("/^(?<date>.+?\b\d{4})[- ]+(?<time>{$this->patterns['time']})$/", $dateToVal, $m)) {
                            $dateArr = strtotime($m['date']);
                            $it['dateArr'] = strtotime($m['time'], $dateArr);

                        }
                    }
                }
            }

            /* Step 2: save values */

            /* Boarding Pass */

            if ($textBP['filename'] !== null) {
                $bp = $email->add()->bpass();
                $bp
                    ->setAttachmentName($textBP['filename'])
                    ->setTraveller($it['traveller'])
                    ->setDepDate($it['dateDep'])
                    ->setDepCode($it['codeDep'])
                    ->setFlightNumber($it['airline'] . ' ' . $it['flightNumber'])
                    ->setRecordLocator($it['pnr'])
                ;
            }
            
            /* Flight */

            if (!empty($it['traveller']) && !in_array($it['traveller'], $travellers)) {
                $f->general()->traveller($it['traveller'], true);
                $travellers[] = $it['traveller'];
            }

            if (!empty($it['pnr']) && !in_array($it['pnr'], $bookingRefValues)) {
                $f->general()->confirmation($it['pnr']);
                $bookingRefValues[] = $it['pnr'];
            }

            if (!empty($it['ticket']) && !in_array($it['ticket'], $tickets)) {
                $f->issued()->ticket($it['ticket'], false, $it['traveller']);
                $tickets[] = $it['ticket'];
            }

            if (!empty($it['account']) && !in_array($it['account'], $accounts)) {
                $f->program()->account($it['account'], false, $it['traveller']);
                $accounts[] = $it['account'];
            }

            if (empty($it['airline']) || empty($it['flightNumber']) || empty($it['codeDep']) || empty($it['dateDep'])) {
                $this->logger->debug('Required fields for flight segment is empty!');
                $f->addSegment(); // for 100% fail
                continue;
            }

            $segIndex = $it['airline'] . $it['flightNumber'] . '_' . $it['codeDep'] . '_' . $it['dateDep'];

            if (array_key_exists($segIndex, $segObjects)) {
                /** @var \AwardWallet\Schema\Parser\Common\FlightSegment $s */
                $s = $segObjects[$segIndex];

                $seatsCurrent = $s->getSeats();

                if (count($seatsCurrent) === 0 && !empty($it['seat'])
                    || count($seatsCurrent) > 0 && !empty($it['seat']) && !in_array($it['seat'], $seatsCurrent)
                ) {
                    $s->extra()->seat($it['seat'], false, false, $it['traveller']);
                }

                $cabinCurrent = $s->getCabin();

                if (empty($cabinCurrent) && !empty($it['cabin'])) {
                    $s->extra()->cabin($it['cabin']);
                } elseif (!empty($cabinCurrent) && !empty($it['cabin']) && $cabinCurrent !== $it['cabin']) {
                    $s->extra()->cabin(null, false, true);
                }
            } else {
                $s = $f->addSegment();
                $segObjects[$segIndex] = $s;

                $s->departure()->date($it['dateDep'])->code($it['codeDep'])->terminal($it['terminalDep'], false, true)->name($it['nameDep']);
                $s->arrival()->date($it['dateArr'])->code($it['codeArr'])->terminal($it['terminalArr'], false, true)->name($it['nameArr']);
                $s->airline()->name($it['airline'])->number($it['flightNumber']);
                $s->extra()->cabin($it['cabin'], false, true);

                if (!empty($it['seat'])) {
                    $s->extra()->seat($it['seat'], false, false, $it['traveller']);
                }
            }
        }
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function assignLang(?string $text): bool
    {
        if ( empty($text) || !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['Departure']) || empty($phrases['Flight']) ) {
                continue;
            }
            if (preg_match("/^[ ]*{$this->opt($phrases['Departure'])}[ :]+{$this->opt($phrases['Flight'])}[ :]*$/im", $text)) {
                $this->lang = $lang;
                return true;
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

    private function getAttachmentName(\PlancakeEmailParser $parser, $pdf): ?string
    {
        $header = $parser->getAttachmentHeader($pdf, 'Content-Type');

        if (preg_match('/name=[\"\']*(.+\.pdf)[\'\"]*/i', $header, $m)) {
            return $m[1];
        }

        return null;
    }

    private function normalizeTraveller(?string $s): ?string
    {
        if (empty($s)) {
            return null;
        }

        $namePrefixes = '(?:MASTER|MSTR|MISS|MRS|MR|MS|DR)';

        return preg_replace([
            "/^(.{2,}?)\s+(?:{$namePrefixes}[.\s]*)+$/is",
            "/^(?:{$namePrefixes}[.\s]+)+(.{2,})$/is",
            '/^([^\/]+?)(?:\s*[\/]+\s*)+([^\/]+)$/',
        ], [
            '$1',
            '$1',
            '$2 $1',
        ], $s);
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

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function splitText(?string $textSource, string $pattern, bool $saveDelimiter = false): array
    {
        $result = [];
        if ($saveDelimiter) {
            $textFragments = preg_split($pattern, $textSource, -1, PREG_SPLIT_DELIM_CAPTURE);
            array_shift($textFragments);
            for ($i=0; $i < count($textFragments)-1; $i+=2)
                $result[] = $textFragments[$i] . $textFragments[$i+1];
        } else {
            $result = preg_split($pattern, $textSource);
            array_shift($result);
        }
        return $result;
    }

    private function rowColsPos(?string $row, bool $alignRight = false): array
    {
        if ($row === null) { return []; }
        $pos = [];
        $head = preg_split('/\s{2,}/', $row, -1, PREG_SPLIT_NO_EMPTY);
        $lastpos = 0;
        foreach ($head as $word) {
            $posStart = mb_strpos($row, $word, $lastpos, 'UTF-8');
            $wordLength = mb_strlen($word, 'UTF-8');
            $pos[] = $alignRight ? $posStart + $wordLength : $posStart;
            $lastpos = $posStart + $wordLength;
        }
        if ($alignRight) {
            array_pop($pos);
            $pos = array_merge([0], $pos);
        }
        return $pos;
    }

    private function splitCols(?string $text, ?array $pos = null): array
    {
        $cols = [];
        if ($text === null)
            return $cols;
        $rows = explode("\n", $text);
        if ($pos === null || count($pos) === 0) $pos = $this->rowColsPos($rows[0]);
        arsort($pos);
        foreach ($rows as $row) {
            foreach ($pos as $k => $p) {
                $cols[$k][] = rtrim(mb_substr($row, $p, null, 'UTF-8'));
                $row = mb_substr($row, 0, $p, 'UTF-8');
            }
        }
        ksort($cols);
        foreach ($cols as &$col) $col = implode("\n", $col);
        return $cols;
    }
}
