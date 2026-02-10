<?php

namespace AwardWallet\Engine\japanair\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class BookingChangedJa extends \TAccountChecker
{
	public $mailFiles = "japanair/it-914035030.eml, japanair/it-918977132.eml";
	public $detectFrom = "noreply@skyinfo.jal.com";
    public $detectSubject = [
        // ja
        '〔JAL国内線〕座席変更のお知らせ',
        '〔JAL国内線〕時間変更のお知らせ',
    ];
    public $detectBody = [
        'ja' => [
            '予約番号',
            'フライト詳細',
            '変更後',
        ],
    ];

    public $lang;

    public static $dictionary = [
        'ja' => [
            "予約番号" => ['予約番号'],
            "フライト詳細" => ['フライト詳細'],
            "変更後" => ['変更後'],
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
            $this->http->XPath->query("//a[{$this->contains(['.jal.com'], '@href')}]")->length === 0
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
                !empty($dict["予約番号"]) && $this->http->XPath->query("//*[{$this->contains($dict['予約番号'])}]")->length > 0
                || !empty($dict["フライト詳細"]) && $this->http->XPath->query("//*[{$this->contains($dict['フライト詳細'])}]")->length > 0
                || !empty($dict["変更後"]) && $this->http->XPath->query("//*[{$this->contains($dict['変更後'])}]")->length > 0
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

        // General
        $f->general()
            ->traveller($traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('ご搭乗者名'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\s*$/u"), true)
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('予約番号'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{5,7})\s*$/u"));

        $flightInfo = $this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][1]/child::td[normalize-space()][last()]");

        $s = $f->addSegment();

        if (preg_match("/^(?<code>[A-Z][A-Z\d]|[A-Z\d][A-Z]|JAL)[ ]*(?<number>[0-9]{1,5})$/u", $flightInfo, $m)){
            if ($m['code'] === "JAL"){
                $m['code'] = 'JL';
            }

            $s->airline()
                ->name($m['code'])
                ->number($m['number']);
        }

        $s->departure()
            ->noCode()
            ->name($this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][4]/child::td[normalize-space()][last()]"));

        $s->arrival()
            ->noCode()
            ->name($this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][7]/child::td[normalize-space()][last()]"));

        $depDate = $this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][2]/child::td[normalize-space()][last()]");
        $depTime = $this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][3]/child::td[normalize-space()][last()]");

        if ($depDate != null && $depTime !== null){
            $s->departure()
                ->date($this->normalizeDate($depDate . ', ' . $depTime));
        }

        $arrDate = $this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][5]/child::td[normalize-space()][last()]");
        $arrTime = $this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][6]/child::td[normalize-space()][last()]");

        if ($arrDate != null && $arrTime !== null){
            $s->arrival()
                ->date($this->normalizeDate($arrDate . ', ' . $arrTime));
        }

        $class = $this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][{$this->contains($this->t('クラス'))}][1]/child::td[normalize-space()][last()]", null, false, "/^{$this->opt($this->t('クラス'))}[ ]*(.+)$/u");

        if ($class !== null){
            $s->extra()->bookingCode($class);
        }

        $seat = $this->http->FindSingleNode("//tr[./child::th[{$this->eq($this->t('変更後'))}]]/following-sibling::tr[normalize-space()][{$this->contains($this->t('座席'))}][1]/child::td[normalize-space()][last()]/descendant::text()[normalize-space()][2]", null, false, "/^([0-9]{1,}[A-Z]+)$/u");

        if ($seat !== null){
            $s->extra()->seat($seat, false, false, $traveller);
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

        $in = [
            // 2024年8月16日, 08:40
            '/^(\d{4})\s*年\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日\s*\（\D+\）\s*,\s*(\d{1,2}:\d{2}(?:\s*[AaPp][Mm])?)\s*$/ui',
        ];
        $out = [
            "$1-$2-$3, $4",
        ];

        $date = preg_replace($in, $out, $date);

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
