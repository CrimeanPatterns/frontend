<?php

namespace AwardWallet\Engine\japanair\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class FlightWaitingZh extends \TAccountChecker
{
	public $mailFiles = "japanair/it-918121294.eml";
	public $detectFrom = "jal.com";
    public $detectSubject = [
        // zh
        '国际奖励机票确认电子邮件（候补名单）',
    ];
    public $detectBody = [
        'zh' => [
            '预订信息',
            '状态 已进入等候名单',
            '感谢您选择日本航空公司网站预订您的奖励航班。',
        ],
    ];

    public $lang;
    public $year;

    public static $dictionary = [
        'zh' => [
            "预订编号" => ["预订编号"],
            "航班信息" => ["航班信息"],
            "预订信息" => ["预订信息"],
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]jal\.com$/", $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers["from"], $this->detectFrom) === false) {
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
            $this->http->XPath->query("//a[{$this->contains(['.jal.co.jp'], '@href')}]")->length === 0
            && $this->http->XPath->query("//*[{$this->contains(['Japan Airlines', 'JAL'])}]")->length === 0
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
                !empty($dict["预订编号"]) && $this->http->XPath->query("//*[{$this->contains($dict['预订编号'])}]")->length > 0
                || !empty($dict["航班信息"]) && $this->http->XPath->query("//*[{$this->contains($dict['航班信息'])}]")->length > 0
                || !empty($dict["预订信息"]) && $this->http->XPath->query("//*[{$this->contains($dict['预订信息'])}]")->length > 0
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

        $this->year = $this->http->FindSingleNode("//text()[{$this->eq($this->t('有效期限：'))}]/following::text()[normalize-space()][1]", null, true, "/^\d{1,2}\/\d{1,2}\/(\d{4})$/u");

        // General
        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('预订编号'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([A-Z\d]{5,7})\s*$/u"));

        $passengers = $this->http->FindNodes("//text()[{$this->eq($this->t('家庭关系：'))}]/preceding::text()[normalize-space()][1]", null, "/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])$/u");

        if (!empty($passengers)){
            $f->general()
                ->travellers(preg_replace("/(先生)/u", "", array_unique($passengers)));
        }

        $accounts = $this->http->FindNodes("//text()[{$this->eq($this->t('会员号：'))}]/following::text()[normalize-space()][1]", null, "/^([\d\-]+)$/u");

        foreach ($accounts as $account){
            $name = $this->http->FindSingleNode("//text()[{$this->eq($this->t('会员号：'))}]/following::text()[normalize-space()][{$this->eq($account)}][1]/ancestor::table[normalize-space()][1]/preceding-sibling::table[normalize-space()][1]", null, true,"/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])$/u");

            $f->addAccountNumber($account, false, preg_replace("/(先生)/u", "", $name));
        }

        $segments = $this->http->XPath->query("//table[{$this->eq($this->t('航班信息'))}]/following-sibling::table[normalize-space()][./descendant::img[contains(@src, 'destination.gif')]][1]/descendant::tr[./descendant::img[contains(@src, 'destination.gif')]]");

        foreach ($segments as $segment){
            $s = $f->addSegment();

            $flightInfo = $this->http->FindSingleNode("./following-sibling::tr[normalize-space()][2]/descendant::text()[normalize-space()][1]", $segment, false, "/^((?:[A-Z\d][A-Z]|[A-Z][A-Z\d])[ ]*[0-9]{1,5})$/u");

            if (preg_match("/^(?<code>[A-Z][A-Z\d]|[A-Z\d][A-Z])[ ]*(?<number>[0-9]{1,5})$/u", $flightInfo, $m)){
                $s->airline()
                    ->name($m['code'])
                    ->number($m['number']);
            }

            $flightDate = $this->http->FindSingleNode("./child::td[normalize-space()][2]", $segment, false, "/^(\d{1,2}月[ ]*\d{1,2}日[ ]*\(\D+\))$/u");

            $depInfo = $this->http->FindSingleNode("./following-sibling::tr[normalize-space()][3]/child::td[normalize-space()][1]", $segment);

            if (preg_match("/^(?<time>[0-9]{1,2}\:[0-9]{1,2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]+(?<name>.+)$/u", $depInfo, $m) && $flightDate !== null){
                $s->departure()
                    ->name($m['name'])
                    ->noCode()
                    ->date($this->normalizeDate($flightDate . ', ' . $m['time']));
            }

            $arrInfo = $this->http->FindSingleNode("./following-sibling::tr[normalize-space()][3]/child::td[normalize-space()][2]", $segment);

            if (preg_match("/^(?<time>[0-9]{1,2}\:[0-9]{1,2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]+(?<name>.+)$/u", $arrInfo, $m) && $flightDate !== null){
                $s->arrival()
                    ->name($m['name'])
                    ->noCode()
                    ->date($this->normalizeDate($flightDate . ', ' . $m['time']));
            }


            $cabin = $this->http->FindSingleNode("./following-sibling::tr[normalize-space()][{$this->contains($this->t('舱位：'))}][1]/child::td[normalize-space()][1]", $segment, false, "/^{$this->opt($this->t('舱位：'))}[ ]*(.+)$/u");

            if ($cabin !== null){
                $s->extra()->cabin($cabin);
            }

            $duration = $this->http->FindSingleNode("./following-sibling::tr[normalize-space()][{$this->contains($this->t('总计时长'))}][1]/child::td[normalize-space()][1]", $segment, false, "/^{$this->opt($this->t('总计时长'))}[ ]*(\d+.+)$/u");

            if ($duration !== null){
                $s->extra()->duration($duration);
            }
        }

        $priceInfo = $this->http->FindSingleNode("//text()[{$this->eq($this->t('总计'))}]/ancestor::td[normalize-space()][1]/following-sibling::td[normalize-space()][1]", null, false, "/(?:^|\d[\d\.\,\s]*[ ]*{$this->opt($this->t('里程'))}[ ]*\+[ ]*)\D*[ ]*(\d[\d\,\.\s]*[ ]*\([A-Z]{3}\))\s*$/");

        if (preg_match("/^(?<price>\d[\d\.\,\'\s]*)\s*\((?<currency>[A-Z]{3})\)$/", $priceInfo, $m)) {
            $f->price()
                ->total(PriceHelper::parse($m['price'], $m['currency']))
                ->currency($m['currency']);

            $taxes = $this->http->FindSingleNode("//text()[{$this->eq($this->t('总计'))}]/ancestor::tr[normalize-space()][1]/preceding-sibling::tr[normalize-space()][./descendant::td[{$this->eq($this->t('税费'))}]][1]/descendant::td[normalize-space()][2]", null, false, "/^\D*[ ]*(\d[\d\,\.\s]*)[ ]*\([A-Z]{3}\)\s*$/u");

            if ($taxes !== null){
                $f->price()
                    ->tax(PriceHelper::parse($taxes, $m['currency']));
            }

            $milesInfo = $this->http->FindSingleNode("//text()[{$this->eq($this->t('总计'))}]/ancestor::td[normalize-space()][1]/following-sibling::td[normalize-space()][1]", null, false, "/^(\d[\d\.\,\s]*)[ ]*{$this->opt($this->t('里程'))}[ ]*\+[ ]*\D*[ ]*\d[\d\,\.\s]*[ ]*\([A-Z]{3}\)\s*$/");

            if ($milesInfo !== null){
                $f->price()->spentAwards($milesInfo);
            }
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
        
        $in = [
            // 6月 19日 (星期四), 11:05
            '/^\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日\s*\((\D+)\),\s*(\d{1,2}:\d{2}(?:\s*[AaPp][Mm])?)\s*$/ui',
        ];
        $out = [
            "$3, $year-$1-$2, $4",
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("/^(?<week>\w+), (?<date>\d+.+)/u", $date, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));

            $date = EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);

            return $date;
        }

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
