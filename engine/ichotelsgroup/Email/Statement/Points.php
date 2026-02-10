<?php

namespace AwardWallet\Engine\ichotelsgroup\Email\Statement;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Points extends \TAccountChecker
{
	public $mailFiles = "ichotelsgroup/statements/it-908036413.eml, ichotelsgroup/statements/it-908117080.eml";

    private $lang = '';

    private $reProvider = ['InterContinental Hotels Group'];
    private $reSubject = [
        'Say “hello” to',
        'more points',
        'more points? Act now.',
        'Unlock the power of points',
        'Flash sale is on! Increase your points now.',
        // kr
        '더 쌓이는 포인트를 만나보세요',
        '더 적립하고 싶으신가요? 서두르세요.',
        '반짝 세일이 시작됩니다! 지금 포인트를 쌓으세요.',

    ];
    private $reBody = [
        'en' => [
            'Download the IHG One Rewards App',
        ],
        'kr' => [
            'IHG One Rewards 앱을 다운로드하세요',
        ],
    ];
    private static $dictionary = [
        'en' => [
            'Sign In' => ['Sign In', 'Sign in', 'SIGN IN'],
        ],
        'kr' => [
            'Sign In' => ['로그인'],
        ],
    ];

    private $enDatesInverted = false;

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $email->setType('AccountStatement' . ucfirst($this->lang));

        $st = $email->add()->statement();

        $number = $this->http->FindSingleNode("//td[{$this->eq($this->t('Sign In'))}]/following-sibling::td[not({$this->eq($this->t('|'))})][1]/descendant::text()[normalize-space()][2]");
        $st->setNumber($number);

        $status = $this->http->FindSingleNode("//td[{$this->eq($this->t('Sign In'))}]/following-sibling::td[not({$this->eq($this->t('|'))})][1]/descendant::text()[normalize-space()][1]");
        $st->addProperty('Level', $status);

        $st->setNoBalance(true);

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/@points\-mail\.com/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (self::detectEmailFromProvider($headers['from']) !== true) {
            return false;
        }

        return $this->arrikey($headers['subject'], $this->reSubject) !== false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[{$this->contains($this->reProvider)}]")->length === 0) {
            return false;
        }

        if ($this->assignLang()) {
            return true;
        }

        return false;
    }

    public static function getEmailLanguages()
    {
        return [];
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function assignLang(): bool
    {
        foreach ($this->reBody as $lang => $value) {
            // $this->logger->debug(' = '.print_r( "//text()[{$this->contains($value)}]",true));
            if ($this->http->XPath->query("//node()[{$this->contains($value)}]")->length > 0) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                return 'contains(normalize-space(' . $node . '),"' . $s . '")';
            }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                return 'normalize-space(' . $node . ')="' . $s . '"';
            }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
            }, $field)) . ')';
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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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
}
