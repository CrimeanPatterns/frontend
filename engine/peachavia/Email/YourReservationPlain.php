<?php

namespace AwardWallet\Engine\peachavia\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;

class YourReservationPlain extends \TAccountChecker
{
    public $mailFiles = "peachavia/it-431678346.eml, peachavia/it-440458079.eml, peachavia/it-808033272-zh.eml, peachavia/it-895321118.eml, peachavia/it-895573843.eml, peachavia/it-899635682.eml";

    public $lang = '';

    public static $dictionary = [
        'en' => [
            'segHeader' => [
                '【Out bound', '【 Out bound',  'Outbound', '【1 stage', '【 1 stage',
                '【In bound', '【 In bound', 'Inbound', '【2 stage', '【 2 stage',
            ],
            // 'Payment details' => '',
            // 'Other Options' => '',
            // 'Total' => '',
            'Booking Reference' => ['Booking Reference', 'Your Booking Number'],
            // 'Booking Date' => '',
            'Passenger Information' => ['Passenger Information', '【Passengers】'],
            // 'Name' => '',
            // 'Advance Seat selection' => '',
        ],
        'zh' => [
            'segHeader' => [
                '【去程', '【 去程',
                // '', '',
            ],
            'Payment details'        => '費用明細',
            'Other Options'          => '其他',
            'Total'                  => '合計',
            'Booking Reference'      => '訂單編號',
            'Booking Date'           => '訂購日期',
            'Passenger Information'  => '旅客資料',
            'Name'                   => '姓名',
            'Advance Seat selection' => '指定座位',
        ],
    ];

    private $patterns = [
        'date' => '\b\d{4}\/\d{1,2}\/\d{1,2}\b(?:[ ]*\([ ]*[[:alpha:]]+[ ]*\))?', // 2023/09/21 (Thu)
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, '@resmail.flypeach.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return strpos($headers['subject'], 'Your Peach Reservation') !== false
            || strpos($headers['subject'], '【Peach】您訂購的行程內容') !== false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $textBody = $parser->getPlainBody();

        if (empty($textBody)) {
            $textBody = $parser->getHTMLBody();
        }

        if (empty($textBody)) {
            return false;
        }

        $phrases = [
            'Copyright (C) Peach Avia',
            'Thank you for booking your flight with flypeach.com',
        ];

        if ($this->detectEmailFromProvider($parser->getHeader('from')) !== true
            && $this->strposArray($textBody, $phrases) === false
        ) {
            return false;
        }

        return $this->assignLang($textBody);
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $textBody = $parser->getPlainBody();

        if (empty($textBody)) {
            $textBody = $parser->getHTMLBody();
        }

        if (empty($textBody)) {
            $this->logger->debug('Content not found!');

            return $email;
        }

        $this->assignLang($textBody);

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            //return $email;
        }
        $email->setType('YourReservationPlain' . ucfirst($this->lang));

        $textBody = str_replace([chr(194) . chr(160), '&nbsp;', '\t'], ' ', $textBody);
        $textBody = preg_replace('/^(?:[ ]*>)+(?: |$)/m', '', $textBody);

        $f = $email->add()->flight();

        $paymentDetails = $this->re("/^[■\s]*{$this->opt($this->t('Payment details'))}\s*\n+([\s\S]+?\n+\s*{$this->opt($this->t('Total'))}[ ]*:.*)/m", $textBody);
        $otherOptions = $this->re("/\n\s*【[ ]*{$this->opt($this->t('Other Options'))}[ ]*】\s*\n+([\s\S]+?\n+\s*{$this->opt($this->t('Total'))}[ ]*:.*?)\s*$/", $paymentDetails);
        $totalPrice = $this->re("/\n\s*{$this->opt($this->t('Total'))}[ ]*[:]+[ ]*(.*\d.*?)\s*$/", $otherOptions);

        if (empty($totalPrice)) {
            $totalPrice = $this->re("/[■]Amount paid\s*\(incl\. tax\)\nTotal\s*(\D{1,3}[\d\.\,\']+)/", $textBody);
        }

        if (preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.‘\'\d ]*)$/u', $totalPrice, $matches)) {
            // ￥15,440
            $currencyCode = preg_match('/^[A-Z]{3}$/', $matches['currency']) ? $matches['currency'] : null;
            $f->price()->currency($this->normalizeCurrency($matches['currency']))->total(PriceHelper::parse($matches['amount'], $currencyCode));
        }

        if (preg_match("/^[■\s]*({$this->opt($this->t('Booking Reference'))})[\:\：\s]*([A-Z\d]{5,10})\s*$/m", $textBody, $m)) {
            $f->general()->confirmation($m[2], $m[1]);
        }

        $bookingDate = strtotime(YourReservation::normalizeDate($this->re("/^[■\s]*{$this->opt($this->t('Booking Date'))}[\:\：\s]*(.*{$this->patterns['date']}.*?)\s*$/m", $textBody)));
        $f->general()->date($bookingDate);

        $passengersText = $this->re("/^[■\s]*{$this->opt($this->t('Passenger Information'))}\s*\n+([\s\S]+?)\n+\s*[-=]{5,}\s*$/m", $textBody)
            ?? $this->re("/{$this->opt($this->t('【Passengers】'))}(.+{$this->opt($this->t('Fare Type'))}.+)\s*(?:\n|$)/s", $textBody);
        $passengerRows = $this->splitText($passengersText, "/^(\s*{$this->opt($this->t('Name'))}[ ]*[\:\：])/m", true);

        $segmentsText = $this->re("/(?:^|\n)(\s*{$this->opt($this->t('segHeader'))}[\s\S]+?)\s*\n+\s*[-=]{5,}\s*(?:\n|$)/", $textBody)
            ?? $this->re("/{$this->opt($this->t('【Itinerary】'))}(.+){$this->opt($this->t('【Passengers】'))}/s", $textBody);

        $pattern = [];
        $travellers = [];

        /*
            【Out bound | MM509】- Simple Peach（VHAPPY）
            An in-flight sales payment card has been registered
            ├ Tokyo (Narita)　Terminal 1
            ├ 2023/09/21 (Thu) 20:20
            ｜ ↓ [OR] │ ↓
            ├ Osaka (Kansai)　第1航廈
            └ 2023/09/21 (Thu) 21:55
        */

        $pattern['v1'] = "/"
            . "(?:^|\n)\s*{$this->opt($this->t('segHeader'))}[ ]*\|[ ]*(?<flight>.+?)[ ]*】.*\n+"
            . "(?:\s*\S.+\S\s*\n+)?"
            . "[├\s]*(?<airportDep>.{3,}?)\s*\n+"
            . "\s*├\s*(?<dateTimeDep>.*{$this->patterns['date']}.*?)\s*\n+"
            . "\s*[｜│] ↓\s*\n+"
            . "\s*├\s*(?<airportArr>.{3,}?)\s*\n+"
            . "[└\s]*(?<dateTimeArr>.*{$this->patterns['date']}.*?)\s*?(?:\n|$)"
        . "/u";

        /*
            Outbound:MM199
            Osaka (Kansai) 2015/06/05 18:55
            →Kagoshima 2015/06/05 20:05
        */

        $pattern['v2'] = "/"
            . "(?:^|\n)\s*{$this->opt($this->t('segHeader'))}[ ]*\:[ ]*(?<flight>.+?)s*\n+"
            . "s*(?<airportDep>.{3,}?)[ ]+(?<dateTimeDep>{$this->patterns['date']}[ ]*{$this->patterns['time']})s*\n+"
            . "s*→(?<airportArr>.{3,}?)[ ]*(?<dateTimeArr>{$this->patterns['date']}[ ]*{$this->patterns['time']})\s*?(?:\n|$)"
            . "/u";

        if (!preg_match_all($pattern['v1'], $segmentsText, $segMatches, PREG_SET_ORDER)
            && !preg_match_all($pattern['v2'], $segmentsText, $segMatches, PREG_SET_ORDER)) {
            $this->logger->debug('Segments not found!');

            return $email;
        }

        foreach ($segMatches as $matches) {
            $s = $f->addSegment();

            if (preg_match("/^(?<name>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<number>\d+)$/", $matches['flight'], $m)) {
                $s->airline()->name($m['name'])->number($m['number']);
            }

            if (preg_match(YourReservation::$patterns['nameTerminal-1'], $matches['airportDep'], $m)
                || preg_match(YourReservation::$patterns['nameTerminal-2'], $matches['airportDep'], $m)
            ) {
                $s->departure()->name($m['name'])->terminal(preg_replace(['/^Terminal[- ]+/i', '/[- ]+Terminal$/i'], '', $m['terminal']));
            } else {
                $s->departure()->name($matches['airportDep']);
            }

            if (preg_match(YourReservation::$patterns['nameTerminal-1'], $matches['airportArr'], $m)
                || preg_match(YourReservation::$patterns['nameTerminal-2'], $matches['airportArr'], $m)
            ) {
                $s->arrival()->name($m['name'])->terminal(preg_replace(['/^Terminal[- ]+/i', '/[- ]+Terminal$/i'], '', $m['terminal']));
            } else {
                $s->arrival()->name($matches['airportArr']);
            }

            $s->departure()->date2(YourReservation::normalizeDate($matches['dateTimeDep']))->noCode();
            $s->arrival()->date2(YourReservation::normalizeDate($matches['dateTimeArr']))->noCode();

            // Passenger Rows Example (for second example not letters with seats)
            /*
                Name: HELAL KHADIJA
                Title: Female Adult
                MM809
                ├ Advance Seat selection: None
                ├ Checked Baggage: None
                └ Priority Baggage Option: None
                MM808
                ├ Advance Seat selection: 5C
                ├ Checked Baggage: None
                └ Priority Baggage Option: None

                    [OR]

                Name：MS STEVENS EMMI
                Baggage：Outbound 0 Inbound 0
                Seat：Outbound  Inbound
                Fare Type：Outbound UHAPPY Inbound WPROMO
            */

            $passengerPattern = "/^"
                . "\s*{$this->opt($this->t('Name'))}[ ]*[\:\：]+[ ]*(?:{$this->opt(['Miss', 'Mrs', 'Mr', 'Ms'])}\.?\s*)?(?<traveller>[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\s*"
                . "(?:(?:.+\n+)+"
                . "\s*{$this->opt($matches['flight'])}\s*"
                . "[├└\s]*{$this->opt($this->t('Advance Seat selection'))}[ ]*[:]+[ ]*(?<seat>\d+[A-Z]))?\s*"
                . "/miu";

            foreach ($passengerRows as $pRow) {
                if (preg_match($passengerPattern, $pRow, $m)) {
                    $travellers[] = $m['traveller'];

                    if (!empty($m['seat'])) {
                        $s->extra()
                            ->seat($m['seat'], false, false, $m['traveller']);
                    }
                }
            }
        }

        $travellers = array_filter($travellers);

        if (count($travellers) > 0) {
            $f->general()->travellers(array_unique($travellers));
        }

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

    private function assignLang(?string $text): bool
    {
        if (empty($text) || !isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['segHeader'])) {
                continue;
            }

            if ($this->strposArray($text, $phrases['segHeader']) !== false) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function strposArray(?string $text, $phrases, bool $reversed = false)
    {
        if (empty($text)) {
            return false;
        }

        foreach ((array) $phrases as $phrase) {
            if (!is_string($phrase)) {
                continue;
            }
            $result = $reversed ? strrpos($text, $phrase) : strpos($text, $phrase);

            if ($result !== false) {
                return $result;
            }
        }

        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return $phrase;
        }

        if ($lang === '') {
            $lang = $this->lang;
        }

        if (empty(self::$dictionary[$lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$lang][$phrase];
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    private function re($re, $str, $c = 1): ?string
    {
        if (preg_match($re, $str, $m) && array_key_exists($c, $m)) {
            return $m[$c];
        }

        return null;
    }

    private function splitText(?string $textSource, string $pattern, bool $saveDelimiter = false): array
    {
        $result = [];

        if ($saveDelimiter) {
            $textFragments = preg_split($pattern, $textSource, -1, PREG_SPLIT_DELIM_CAPTURE);
            array_shift($textFragments);

            for ($i = 0; $i < count($textFragments) - 1; $i += 2) {
                $result[] = $textFragments[$i] . $textFragments[$i + 1];
            }
        } else {
            $result = preg_split($pattern, $textSource);
            array_shift($result);
        }

        return $result;
    }

    private function normalizeCurrency($s)
    {
        $sym = [
            '€'         => 'EUR',
            'US dollars'=> 'USD',
            '£'         => 'GBP',
            '₹'         => 'INR',
            '￥'         => 'CNY',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3})(?:$|\s)#", $s)) {
            return $code;
        }
        $s = $this->re("#([^\d\,\.]+)#", $s);

        foreach ($sym as $f=> $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return $s;
    }
}
