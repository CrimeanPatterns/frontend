<?php

namespace AwardWallet\Engine\tripbiz\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class TransferDescription extends \TAccountChecker
{
    public $mailFiles = "tripbiz/it-924271203.eml";

    public static $dictionary = [
        'zh' => [
        ],
    ];

    private $detectFrom = "ct_rsv@trip.com";
    private $detectSubject = [
        // zh
        '用车凭证说明',
    ];
    private $detectBody = [
        'zh' => [
            '用车凭证说明',
            '的报销凭证，请查收',
            '用车行程详情',
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
        if ($this->http->XPath->query("//text()[contains(.,'ct_rsv@trip.com')] | //a/@href[{$this->contains(['.ctrip.com', '.ctrip.cn'])}]")->length == 0
            && $this->http->XPath->query("//text()[{$this->contains(['Trip.Biz user', 'ctrip.com. all rights reserved'])}]")->length == 0
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
        $bookingNo = $this->http->FindSingleNode("(//text()[{$this->starts($this->t("订单号"))}])[1]/following::text()[normalize-space()][not(normalize-space() = ':')][1]",
            null, true, "#^\s*([A-Z\d]{5,})(?:\s*{$this->opt($this->t('[t.ctrip.cn]'))})?\s*$#");

        $email->ota()->confirmation($bookingNo);

        $t = $email->add()->transfer();

        // no reservation confirmation
        if (!empty($bookingNo)) {
            $t->setNoConfirmationNumber(true);
        }

        // travellers
        // 李晓飞   |   李晓飞(Xiaofei Li)
        $travellers = $this->http->FindNodes("//text()[{$this->eq($this->t('乘车人信息'))}]/ancestor::tr[1]/following-sibling::tr[ td[normalize-space()][2][{$this->contains(['*', '+', '-'])}][contains(translate(.,'0123456789', 'dddddddddd'), 'ddd')] ]/td[normalize-space()][1]",
            null, "/^\s*([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])\s*(?:\(|$)/u");

        if (!empty($travellers)) {
            $t->setTravellers($travellers);
        }

        $s = $t->addSegment();

        $carRoots = $this->http->XPath->query("//text()[{$this->eq($this->t('用车行程详情'))}]/ancestor::tr[1]/following-sibling::tr[normalize-space()][1]/descendant::*[ *[normalize-space()][1][{$this->contains('·')}] and *[normalize-space()][2][{$this->contains('|')}]]");

        if ($carRoots->length === 1) {
            $carRoot = $carRoots[0];
        } else {
            $this->logger->debug('Wrong count segments!');

            return;
        }

        // car model
        $carModel = $this->http->FindSingleNode("./*[normalize-space()][1]", $carRoot, true, "/\·\s*(.+?)\s*$/");
        $s->setCarModel($carModel, false, true);

        // car type
        $carType = $this->http->FindSingleNode("./*[normalize-space()][2]", $carRoot, true, "/^\s*(.+?)\s*\|/");
        $s->setCarType($carType, false, true);

        // notes (driver name and phone)
        $notes = $this->http->FindSingleNode("./*[normalize-space()][2]", $carRoot, true, "/\|\s*(.+?)\s*$/");

        if (!empty($notes)) {
            $t->setNotes($notes);
        }

        // departure and arrival info
        $depDate = $this->http->FindSingleNode("//text()[{$this->eq($this->t('时间（用车地当地时间）'))}]/following::text()[normalize-space()][1]");
        $s->setDepDate($this->normalizeDate($depDate))
            ->setNoArrDate(true)
            ->setDepName($this->http->FindSingleNode("//text()[{$this->eq($this->t('上车地点'))}]/following::text()[normalize-space()][1]"))
            ->setArrName($this->http->FindSingleNode("//text()[{$this->eq($this->t('下车地点'))}]/following::text()[normalize-space()][1]"));

        // pricing info
        $priceNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('付款明细'))}]/ancestor::tr[1]/following-sibling::tr[normalize-space()]");

        foreach ($priceNodes as $pos => $priceNode) {
            // discount
            if ($this->http->FindSingleNode("./td[1][{$this->eq($this->t('优惠券抵扣金额'))}]", $priceNode)
                || $this->http->FindSingleNode("./td[2][{$this->starts('-')}]", $priceNode)
            ) {
                $paymentInfo = $this->parseAmountAndCurrency($this->http->FindSingleNode("./td[2]", $priceNode, true, "/^[\s*\-]*(.+?)\s*$/"));

                if (!empty($paymentInfo)) {
                    $t->price()
                        ->discount($paymentInfo['amount'])
                        ->currency($paymentInfo['currency']);
                }

                continue;
            }

            // cost
            if ($this->http->FindSingleNode("./td[1][{$this->eq($this->t('用车费'))}]", $priceNode)
                || $pos === count($priceNodes) - 1
            ) {
                $paymentInfo = $this->parseAmountAndCurrency($this->http->FindSingleNode("./td[2]", $priceNode));

                if (!empty($paymentInfo)) {
                    $t->price()
                        ->cost($paymentInfo['amount'])
                        ->currency($paymentInfo['currency']);
                }

                continue;
            }

            // fees
            $paymentInfo = $this->parseAmountAndCurrency($this->http->FindSingleNode("./td[2]", $priceNode));

            if (!empty($paymentInfo)) {
                $t->price()
                    ->fee($this->http->FindSingleNode("./td[1]", $priceNode), $paymentInfo['amount'])
                    ->currency($paymentInfo['currency']);
            }
        }

        // total
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('总价：'))}]/following::text()[normalize-space()][1]",
            null, true, "/^\s*(.+?)\s*(?:\(|$)/");

        $totalInfo = $this->parseAmountAndCurrency($totalText);

        if (!empty($totalInfo)) {
            $t->price()
                ->total($totalInfo['amount'])
                ->currency($totalInfo['currency']);
        }
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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

    private function normalizeCurrency($s): ?string
    {
        $sym = [
            '€'   => 'EUR',
            'HK$' => 'HKD',
            '$'   => 'USD',
            '£'   => 'GBP',
            'บาท' => 'THB',
            '₩'   => 'KRW',
            '¥'   => 'CNY',
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

        return null;
    }

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }

        $in = [
            // 2025年6月14日08:30 用车
            '/^\s*(\d{4})\s*年\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日\s*(\d{1,2}\:\d{2}).+$/u',
        ];
        $out = [
            "$1-$2-$3, $4",
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

    private function parseAmountAndCurrency(?string $pricingText): ?array
    {
        if (preg_match('/^(?<currency>[A-Z]{3})[ ]*(?<amount>\d[,.\'\d ]*)$/', $pricingText, $m)
            || preg_match('/^(?<amount>\d[,.\'\d ]*?)[ ]*(?<currency>[A-Z]{3})$/', $pricingText, $m)
            || preg_match('/^(?<currency>[^\s\d]{1,5}) ?(?<amount>\d[,.\'\d ]*)$/', $pricingText, $m)
            || preg_match('/^\s*(?<amount>\d[,.\'\d ]*?) ?(?<currency>[^\s\d]{1,5})\s*$/u', $pricingText, $m)
        ) {
            $currency = $this->normalizeCurrency($m['currency']);
            $amount = PriceHelper::parse($m['amount'], $currency);

            return ['currency' => $currency, 'amount' => $amount];
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
}
