<?php

namespace AwardWallet\Engine\japanair\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class BookingJa extends \TAccountChecker
{
	public $mailFiles = "japanair/it-908829367.eml";
    public $detectFrom = "noreply@skyinfo.jal.com";
    public $detectSubject = [
        // ja
        'JAL国内線 ご搭乗時のお願い',
    ];
    public $detectBody = [
        'ja' => [
            'お客さまの予約情報',
            'JAL予約番号',
            'このメールはご搭乗予約をお持ちのお客さまにお送りしております。',
        ],
    ];

    public $lang;
    public $year;

    public static $dictionary = [
        'ja' => [
            "JAL予約番号：" => ["JAL予約番号："],
            "運航便：" => ["運航便："],
            "お客さまの予約情報" => ["お客さまの予約情報"],
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]jal\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false
            && strpos($headers["subject"], 'JAL国内線') === false
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
            $this->http->XPath->query("//a[{$this->contains(['www.jal.co.jp'], '@href')}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Copyright©Japan Airlines'])}]")->length === 0
        ) {
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
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("can't determine a language");

            return $email;
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

    private function assignLang()
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (
                !empty($dict["JAL予約番号："]) && $this->http->XPath->query("//*[{$this->contains($dict['JAL予約番号：'])}]")->length > 0
                || !empty($dict["運航便："]) && $this->http->XPath->query("//*[{$this->contains($dict['運航便：'])}]")->length > 0
                || !empty($dict["お客さまの予約情報"]) && $this->http->XPath->query("//*[{$this->contains($dict['お客さまの予約情報'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function parseEmailHtml(Email $email)
    {
        $f = $email->add()->flight();

        $this->year = $this->http->FindSingleNode("//td[{$this->eq($this->t('メールが表示されない方はこちら'))}]/preceding::text()[normalize-space()][1]", null, true, "/^(\d{4})年\d{1,2}月\d{1,2}日$/u");

        // General
        $f->general()
            ->confirmation($this->http->FindSingleNode("//td[{$this->eq($this->t('JAL予約番号：'))}]/following-sibling::td[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{5,7})\s*$/u"));

        $flightInfo = $this->http->FindSingleNode("//td[{$this->eq($this->t('運航便：'))}]/following-sibling::td[normalize-space()][1]");

        $s = $f->addSegment();

        if (preg_match("/^(?<code>[A-Z][A-Z\d]|[A-Z\d][A-Z])[ ]*(?<number>[0-9]{1,5})$/u", $flightInfo, $m)){
            $s->airline()
                ->name($m['code'])
                ->number($m['number']);
        }

        $flightDate = $this->http->FindSingleNode("//td[{$this->eq($this->t('出発日：'))}]/following-sibling::td[normalize-space()][1]/descendant::text()[normalize-space()][1]");

        $depInfo = $this->http->FindSingleNode("//td[{$this->eq($this->t('出発日：'))}]/following-sibling::td[normalize-space()][1]/descendant::text()[normalize-space()][2]");

        if (preg_match("/^(?<time>[0-9]{1,2}\:[0-9]{1,2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]+(?<name>.+)発$/u", $depInfo, $m) && $flightDate !== null){
            $s->departure()
                ->name($m['name'])
                ->noCode()
                ->date($this->normalizeDate($flightDate . ', ' . $m['time']));
        }

        $arrInfo = $this->http->FindSingleNode("//td[{$this->eq($this->t('出発日：'))}]/following-sibling::td[normalize-space()][1]/descendant::text()[normalize-space()][3]");

        if (preg_match("/^(?<time>[0-9]{1,2}\:[0-9]{1,2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]+(?<name>.+)着$/u", $arrInfo, $m) && $flightDate !== null){
            $s->arrival()
                ->name($m['name'])
                ->noCode()
                ->date($this->normalizeDate($flightDate . ', ' . $m['time']));
        }

        if ($s->getDepDate() > $s->getArrDate()){
            $s->setArrDate($s->getArrDate() + 86400);
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

    private function eq($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
                return $text . '="' . $s . '"';
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

    private function dateTranslate($date)
    {
        if (preg_match('/[[:alpha:]]+/iu', $date, $m)) {
            $monthNameOriginal = $m[0];

            if ($translatedMonthName = MonthTranslate::translate($monthNameOriginal, $this->lang)) {
                return preg_replace("/$monthNameOriginal/i", $translatedMonthName, $date);
            }
        }

        return $date;
    }

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        $year = $this->year;
        $this->logger->debug($date);
        $in = [
            // 2024年8月16日, 08:40
            '/^\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日\s*,\s*(\d{1,2}:\d{2}(?:\s*[AaPp][Mm])?)\s*$/ui',
        ];
        $out = [
            "$year-$1-$2, $3",
        ];

        $date = preg_replace($in, $out, $date);
        $this->logger->debug($date);
        // $this->logger->debug('date end = ' . print_r( $date, true));
        if (preg_match('/^\s*\d{1,2}\s+[[:alpha:]]+\s+(\d{4}),\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/ui', $date)
            || preg_match('/^\s*\d{4}-\d{1,2}-\d{1,2},\s*(\d{1,2}:\d{2}(?:\s*[ap]m)?)\s*$/ui', $date)
        ) {
            return strtotime($date);
        }

        return null;
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

    private function getTotal($text)
    {
        $result = ['amount' => null, 'currency' => null];

        if (preg_match("#^\s*(?<currency>[^\d\s]{1,5})\s*(?<amount>\d[\d\., ]*)\s*$#", $text, $m)
            || preg_match("#^\s*(?<amount>\d[\d\., ]*)\s*(?<currency>[^\d\s]{1,5})\s*$#", $text, $m)
            // $232.83 USD
            || preg_match("#^\s*\D{1,5}(?<amount>\d[\d\., ]*)\s*(?<currency>[A-Z]{3})\s*$#", $text, $m)
        ) {
            $m['currency'] = $this->currency($m['currency']);
            $m['amount'] = PriceHelper::parse($m['amount'], $m['currency']);

            if (is_numeric($m['amount'])) {
                $m['amount'] = (float) $m['amount'];
            } else {
                $m['amount'] = null;
            }
            $result = ['amount' => $m['amount'], 'currency' => $m['currency']];
        }

        return $result;
    }

    private function currency($s)
    {
        $sym = [
            '円'  => 'JPY',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3})(?:$|\s)#", $s)) {
            return $code;
        }

        foreach ($sym as $f => $r) {
            if ($s == $f) {
                return $r;
            }
        }

        return null;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }
}
