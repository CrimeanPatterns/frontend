<?php

namespace AwardWallet\Engine\tripbiz\Email;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class HotelConfirmed extends \TAccountChecker
{
    public $mailFiles = "tripbiz/it-922733671.eml, tripbiz/it-922737751.eml";

    public static $dictionary = [
        'zh' => [
            'noAddressText' => ['欢迎您入住', '感谢您在'],
        ],
    ];

    private $detectFrom = "ct_rsv@trip.com";
    private $detectSubject = [
        // zh
        '您有一条来自',
    ];
    private $detectBody = [
        'zh' => [
            '您有一条来自',
            '酒店原文如下：',
            '可入住人数（每间房）',
        ],
    ];

    private $lang = 'zh';

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match("/[@.]trip\.com$/i", $from) > 0;
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
        if ($this->http->XPath->query("//a/@href[{$this->contains(['.ctrip.com', '.ctrip.cn'])}] | //img[contains(@src,'c-ctrip.com')]")->length == 0
            && $this->http->XPath->query("//text()[{$this->contains(['Trip.Biz user', 'ctrip.com. all rights reserved', 'ct_rsv@trip.com'])}]")->length == 0
        ) {
            return false;
        }

        // detect Format
        foreach ($this->detectBody as $detectBody) {
            // if array -  all phrase of array
            if (is_array($detectBody)) {
                foreach ($detectBody as $phrase) {
                    if ($this->http->XPath->query("//text()[{$this->contains($phrase)}]")->length === 0) {
                        continue 2;
                    }
                }

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->parseEmailHtml($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class));

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
        // ota confirmation
        $bookingNo = $this->http->FindSingleNode("(//text()[{$this->eq($this->t("订单编号"))}])[1]/following::text()[normalize-space()][1]",
            null, true, "#^\s*([A-Z\d]{5,})(?:\s*{$this->opt($this->t('[t.ctrip.cn]'))})?\s*$#");

        $email->ota()->confirmation($bookingNo);

        $h = $email->add()->hotel();

        // reservation confirmation
        $confirmationNumber = $this->http->FindSingleNode("(//text()[{$this->eq($this->t("酒店确认号"))}])[1]/following::text()[normalize-space()][1]",
            null, true, "#^\s*([A-Z\d]{5,})(?:\s*{$this->opt($this->t('[t.ctrip.cn]'))})?\s*$#");

        $h->general()->confirmation($confirmationNumber);

        // status
        if ($this->http->FindSingleNode("(//text()[{$this->contains($this->t("您的预定已成功确认"))}])[1]")) {
            $h->general()->status('确认');
        }

        // travellers
        $traveller = $this->http->FindSingleNode("//text()[{$this->eq($this->t('住客姓名'))}]/following::text()[normalize-space()][1]",
            null, true, "/^\s*([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\s*$/u");

        if (!empty($traveller)) {
            $h->addTraveller($traveller);
        }

        // hotel name
        $hotelName = $this->http->FindSingleNode("//text()[{$this->starts('您有一条来自')} and {$this->contains('的新消息')}]",
            null, true, "/^\s*{$this->opt($this->t('您有一条来自'))}(.+?){$this->opt($this->t('新消息'))}\s*$/");

        $h->setHotelName($hotelName);

        // address
        $address = $this->http->FindSingleNode("//text()[{$this->contains('我们酒店位于')}]",
            null, true, "/{$this->opt($this->t('我们酒店位于'))}(.+?)[，]/u");

        if (!empty($address)) {
            $h->setAddress($address);
        }

        // no address
        if (empty($address) && empty($this->http->FindSingleNode("//text()[{$this->contains('我们酒店位于')}]"))
            && !empty($this->http->FindSingleNode("//text()[{$this->starts($this->t('noAddressText'))}]")) // format without address
        ) {
            $h->setNoAddress(true);
        }

        // check-in and check-out
        $checkIn = $this->http->FindSingleNode("//text()[{$this->eq($this->t('入住时间'))}]/following::text()[normalize-space()][1]");
        $checkOut = $this->http->FindSingleNode("//text()[{$this->eq($this->t('退房时间'))}]/following::text()[normalize-space()][1]");

        $h->booked()
            ->checkIn($this->normalizeDate($checkIn))
            ->checkOut($this->normalizeDate($checkOut));

        // rooms count
        $roomsCount = $this->http->FindSingleNode("//text()[{$this->eq($this->t('房间数量'))}]/following::text()[normalize-space()][1]",
            null, true, "/^\s*(\d{1,2})\s*$/");

        $h->setRoomsCount($roomsCount, false, true);
    }

    // additional methods
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

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
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
            // 2025年6月19日
            '/^\s*(\d{4})\s*年\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日\s*$/u',
        ];
        $out = [
            "$1-$2-$3",
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+(?:\d{4}|%year%)#", $date, $m)) {
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
}
