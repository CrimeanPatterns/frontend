<?php

namespace AwardWallet\Engine\zenhotels\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BookingCancellation extends \TAccountChecker
{
    public $mailFiles = "zenhotels/it-182625017.eml, zenhotels/it-895622272.eml";
    public $subjects = [
        'Booking cancellation',
    ];

    public $lang = '';
    public $subject = '';

    public $detectLang = [
        'en' => ['Your booking'],
    ];

    public static $dictionary = [
        "en" => [
            'Your booking has been cancelled' => ['Your booking has been cancelled', 'Your bookinghas been cancelled'],
            'View hotel'                      => ['View hotel', 'View'],
        ],
    ];

    public $providerCode;
    public static $detectProvider = [
        'zenhotels' => [
            'from'     => '@news.zenhotels.com',
            'bodyLink' => ['.zenhotels.com'],
            'bodyText' => ['support@zenhotels.com'],
        ],
        'ostrovok' => [
            'from'     => '@info.ostrovok.ru',
            'bodyLink' => '.ostrovok.ru',
            'bodyText' => ['info@ostrovok.ru'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        foreach (self::$detectProvider as $code => $params) {
            if (!empty($params['from']) && stripos($headers['from'], $params['from']) !== false) {
                $this->providerCode = $code;

                foreach ($this->subjects as $subject) {
                    if (stripos($headers['subject'], $subject) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        foreach (self::$detectProvider as $code => $params) {
            if (
                !empty($params['bodyLink']) && $this->http->XPath->query("//a/@href[{$this->contains($params['bodyLink'])}]")->length > 0
                || !empty($params['bodyText']) && $this->http->XPath->query("//node()[{$this->contains($params['bodyText'])}]")->length > 0
            ) {
                $this->providerCode = $code;

                foreach (self::$dictionary as $dict) {
                    if (!empty($dict['Your booking has been cancelled']) && !empty($dict['View hotel'])
                        && $this->http->XPath->query("//node()[{$this->contains($dict['Your booking has been cancelled'])}]")->length > 0
                        && $this->http->XPath->query("//node()[{$this->eq($dict['View hotel'])}]")->length > 0
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]news\.zenhotels\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();
        $this->subject = $parser->getSubject();
        $this->ParseHotel($email);

        $email->setProviderCode($this->providerCode);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseHotel(Email $email)
    {
        $email->ota()
            ->confirmation($this->re("/[№]\s*([A-Z\d\-]{5,})\b/", $this->subject));

        $h = $email->add()->hotel();

        $h->general()
            ->noConfirmation()
            ->traveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('Dear'))}]", null, true, "/^{$this->opt($this->t('Dear'))}\s*(\D+)\,/"), true);

        if ($this->http->XPath->query("//node()[{$this->eq($this->t('Your booking has been cancelled'))}]")->length > 0) {
            $h->general()
                ->status('Cancelled')
                ->cancelled();
        }

        $hotelText = implode("\n", $this->http->FindNodes("(//text()[{$this->eq($this->t('View hotel'))}])[1]/ancestor::td[count(.//text()[normalize-space()]) > 1][1]//text()[normalize-space()]"));
        // $this->logger->debug('$hotelText = '.print_r( $hotelText,true));

        $re1 = "/^(?<dayIn>\d+)[\s\-]+(?<dayOut>\d+)\s*(?<month>\w+)\,\s*(?<year>\d{4})\n"
            . "(?<address>.+)\n(?<name>.+)\n(?<type>.+)\n/u";
        $re2 = "/^(?:\W+\n)?(?<address>.+)\n(?<name>.+)\n"
            . "{$this->opt($this->t('Check-in - Check-out'))}\n(?<dayIn>\d+)[\s\-]+(?<dayOut>\d+)\s*(?<month>\w+)\,\s*(?<year>\d{4})\n"
            . "{$this->opt($this->t('Room'))}\n(?<type>.+)\n/u";

        if (preg_match($re1, $hotelText, $m)
            || preg_match($re2, $hotelText, $m)
        ) {
            $h->hotel()
                ->name($m['name'])
                ->address($m['address']);

            $h->booked()
                ->checkIn($this->normalizeDate($m['dayIn'] . ' ' . $m['month'] . ' ' . $m['year']))
                ->checkOut($this->normalizeDate($m['dayOut'] . ' ' . $m['month'] . ' ' . $m['year']));

            $room = $h->addRoom();
            $room->setType($m['type']);
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

    public static function getEmailProviders()
    {
        return array_keys(self::$detectProvider);
    }

    protected function eq($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false';
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

        return implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), \"{$s}\")";
        }, $field));
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "contains(normalize-space(.), \"{$s}\")";
        }, $field));
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s));
        }, $field)) . ')';
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^\D+\,\s*(\d+\s*\D+\s*\d{4})\D+([\d\:]+)$#u", //Thu, 25 August 2022 from 12:00
        ];
        $out = [
            "$1, $2",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }

    private function assignLang()
    {
        foreach ($this->detectLang as $lang => $words) {
            foreach ($words as $word) {
                if ($this->http->XPath->query("//*[{$this->contains($word)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
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
