<?php

namespace AwardWallet\Engine\hhonors\Email;

use AwardWallet\Schema\Parser\Email\Email;

class Cancellation extends \TAccountChecker
{
    public $mailFiles = "hhonors/it-49123519.eml, hhonors/it-56806541.eml, hhonors/it-58091764.eml, hhonors/it-892284708.eml";
    private $lang = '';
    private $reFrom = [
        'hilton.com',
    ];
    private $reSubject = [
        // en
        'Cancellation #',
        // ja
        'キャンセル番号',
        // es
        'N.º de cancelación:',
        // pt
        'N.º de cancelamento #',
        // ko
        ' 취소 번호#',
        // zh
        '取消编号 #',
    ];
    private $reProvider = ['Hilton Honors', 'Hilton Tokyo'];
    private $detectLang = [
        'en' => [
            're sorry to see you go',
            'your reservation has been cancel',
        ],
        'ja' => [
            '様のご予約がキャンセルされました。',
        ],
        'es' => [
            'reservación ha sido cancelada',
        ],
        'pt' => [
            'sua reserva foi cancelada',
        ],
        'ko' => [
            '귀하의 예약이 취소되었습니다',
        ],
        'zh' => [
            '您的预订已被取消',
        ],
    ];
    private static $dictionary = [
        'en' => [
            'Stay Dates'                                => ['Stay Dates', 'Stay Dates:'],
            'This email advertisement was delivered to' => ['This email advertisement was delivered to', 'This email was delivered to'],
        ],
        'ja' => [
            'Cancellation #'                   => 'キャンセル番号',
            'Stay Dates'                       => '滞在日程',
            'your reservation has been cancel' => '様のご予約がキャンセルされました。',
        ],
        'es' => [
            'Cancellation #'                            => 'Cancelación #',
            'Stay Dates'                                => 'Fechas de estancia',
            'your reservation has been cancel'          => 'su reservación ha sido cancelada',
            'This email advertisement was delivered to' => ['This email advertisement was delivered to', 'This email was delivered to'],
        ],
        'pt' => [
            'Cancellation #'                            => 'Cancelamento #',
            'Stay Dates'                                => 'Datas de permanência',
            'your reservation has been cancel'          => 'sua reserva foi cancelada',
            'This email advertisement was delivered to' => ['Este e-mail foi enviado a'],
        ],
        'ko' => [
            'Cancellation #'                            => '취소 번호',
            'Stay Dates'                                => '숙박 일정',
            'your reservation has been cancel'          => '귀하의 예약이 취소되었습니다',
            'This email advertisement was delivered to' => ['이메일이'],
        ],
        'zh' => [
            'Cancellation #'                            => '取消编号',
            'Stay Dates'                                => '保持日期',
            'your reservation has been cancel'          => '您的预订已被取消',
            'This email advertisement was delivered to' => ['此电子邮件已发送至'],
        ],
    ];

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if (!$this->assignLang()) {
            $this->logger->debug("Can't determine a language");

            return $email;
        }
        $h = $email->add()->hotel();

        $cancellation = $this->http->FindSingleNode("//strong[{$this->contains($this->t('Cancellation #'))}]", null, false, "/{$this->opt($this->t('Cancellation #'))}\s*([\w\-]+)/");

        if (!empty($cancellation)) {
            $h->general()->cancellationNumber($cancellation);
        } else {
            $h->general()
                ->noConfirmation();
        }

        if ($tr = $this->http->FindSingleNode("//strong[{$this->contains($this->t('your reservation has been cancel'))}]",
            null, false, '/^(.+?)(?:,\s+|様、|님,)/u')) {
            $h->general()->traveller($tr);
        }

        $text = implode("\n", $this->http->FindNodes("//*[{$this->eq($this->t('Stay Dates'))}]/ancestor::tr[1]//text()"));
//        $this->logger->debug('$text = '. print_r($text, true));
        /*
        Stay Dates
        : Jul-10-2020 - Jul-12-2020
        Hilton Garden Inn Ithaca
        130 E. Seneca Street Ithaca NY 14850 US
        T: + 16072778900
         */
        $pattern = "/"
            . "{$this->opt($this->t('Stay Dates'))}\s*:?\s+"
            . "(?<checkin>.+?[\d]{4}?)\s*.\s*"
            . "(?<checkout>.+?[\d]{4}?)\s*"
            . "(?<name>.+?)\n\s*"
            . "(?<address>.{15,100}\s+[A-Z]{2}?)\s*T:\s*"
            . "(?<phone>[+\-\d\s()]{7,})"
            . "/u";
//        $this->logger->debug('$pattern = '. print_r($pattern, true));

        //$this->logger->debug($text);
        //$this->logger->notice("//*[{$this->eq($this->t('Stay Dates'))}]/ancestor::tr[1]//text()");

        if (preg_match($pattern, $text, $m)) {
            $h->hotel()
                ->name($m['name'])
                ->address($m['address'])
                ->phone(preg_replace('/\+\s+/', '+', $m['phone']));
            $h->booked()
                ->checkIn(strtotime($m['checkin']))
                ->checkOut(strtotime($m['checkout']));
        }

        $cancellation = $this->http->FindSingleNode("//strong[{$this->contains($this->t('free of charge'))}]");
        $cancellation .= $this->http->FindSingleNode("//strong[{$this->contains($this->t('free of charge'))}]/following-sibling::text()[1]");

        if (!empty($cancellation)) {
            $h->setCancellation($cancellation);

            if (preg_match('/free of charge, up to (\d+ hours?) prior to your arrival date/', $cancellation, $m)) {
                $h->booked()->deadlineRelative("{$m[1]}");
            }
        }

        $h->general()->status($this->t('cancelled'));
        $h->general()->cancelled();

        //it-892284708.eml
        $login = $this->http->FindSingleNode("//img[contains(@alt, 'explore')]/@src", null, true, "/(?:customerid|mi_u)[=](\d{8,})/");

        if (!empty($login)) {
            $st = $email->add()->statement();

            $st->setLogin($login)
                ->setNumber($login)
                ->setNoBalance(true);

            if (!empty($tr)) {
                $st->addProperty('Name', trim($tr, ','));
            }

            $email->setUserEmail($this->http->FindSingleNode("//text()[{$this->starts($this->t('This email advertisement was delivered to'))}]/following::text()[normalize-space()][1]", null, true, "/^(\S+[@]\S+)$/"));
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (self::detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        return $this->arrikey($headers['subject'], $this->reSubject) !== false;
    }

    public function detectEmailFromProvider($from)
    {
        return $this->arrikey($from, $this->reFrom) !== false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[{$this->contains($this->reProvider)}]")->length === 0) {
            return false;
        }

        if ($this->assignLang() && ($this->http->XPath->query("//strong[{$this->contains($this->t('Cancellation #'))}]")->length > 0
            || $this->http->XPath->query("//strong[{$this->contains($this->t('Cancellation : Late Cancellation'))}]")->length > 0)) {
            return true;
        }

        return false;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 1;
    }

    private function assignLang()
    {
        foreach ($this->detectLang as $lang => $value) {
            if ($this->arrikey($this->http->Response['body'], $value) !== false) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
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

    private function eq($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false';
        }

        return '(' . implode(' or ',
                array_map(function ($s) use ($node) {
                    return 'normalize-space(' . $node . ')="' . $s . '"';
                }, $field)) . ')';
    }

    private function contains($field, $node = '.'): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ',
                array_map(function ($s) use ($node) {
                    return 'contains(normalize-space(' . $node . '),"' . $s . '")';
                }, $field))
            . ')';
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function arrikey($haystack, array $arrayNeedle)
    {
        foreach ($arrayNeedle as $key => $needles) {
            if (is_array($needles)) {
                foreach ($needles as $needle) {
                    if (stripos($haystack, $needle) !== false) {
                        return $key;
                    }
                }
            } else {
                if (stripos($haystack, $needles) !== false) {
                    return $key;
                }
            }
        }

        return false;
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), \"{$s}\")";
        }, $field));
    }
}
