<?php

namespace AwardWallet\Engine\china\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class PointsTransfer extends \TAccountChecker
{
	public $mailFiles = "china/statements/it-908854689.eml, china/statements/it-909232175.eml, china/statements/it-909450155.eml";
    public $subjects = [
        // zh
        '中華航空酬賓獎項轉讓通知',
    ];

    public $lang = '';

    public static $dictionary = [
        "zh" => [
            'searchPhrase'    => ['親愛的會員您好, 您完成的酬賓轉讓紀錄如下：', 'Dear Dynasty Member, 您完成的酬賓轉讓紀錄如下：'],
            'searchPhrase2'   => '轉讓人會員卡號：',
            'name'   => '轉讓人姓名：',
            'balanceStr'   => '目前您尚可兌換之有效哩程為',
            'miles'   => '哩',
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@email.china-airlines.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $this->assignLang();

        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'CHINA AIRLINES')]" )->length > 0) {
            if ($this->http->XPath->query("//text()[{$this->contains($this->t('searchPhrase'))}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->contains($this->t('searchPhrase2'))}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]email\.china\-airlines\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        $st = $email->add()->statement();

        $number = $this->http->FindSingleNode("//text()[{$this->starts($this->t('searchPhrase2'))}]/ancestor::td[1]/following-sibling::td[normalize-space()][1]", null, true, "/^([A-Z\d]+)$/u");

        $st->setNumber($number);

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('name'))}]/ancestor::td[1]/following-sibling::td[normalize-space()][1]");

        $st->addProperty('Name', $name);

        $balance = $this->http->FindSingleNode("//text()[{$this->contains($this->t('balanceStr'))}]/ancestor::li[1]", null, true, "/{$this->opt($this->t('balanceStr'))}[ ]*(\d[\d\,\.]+)[ ]*{$this->opt($this->t('miles'))}[\.\。]$/u");

        if ($balance !== null){
            $st->setBalance(preg_replace("/(\,)/u", "", $balance));
        } else {
            $st->setNoBalance(true);
        }

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
        return 0;
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

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
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

    private function assignLang(): bool
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['searchPhrase']) || empty($phrases['searchPhrase2'])) {
                continue;
            }

            if ($this->http->XPath->query("//node()[{$this->contains($phrases['searchPhrase'])}]")->length > 0
                && $this->http->XPath->query("//node()[{$this->contains($phrases['searchPhrase2'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }
}
