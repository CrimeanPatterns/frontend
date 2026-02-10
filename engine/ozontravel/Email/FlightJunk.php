<?php

namespace AwardWallet\Engine\ozontravel\Email;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FlightJunk extends \TAccountChecker
{
    public $mailFiles = "ozontravel/it-915945402.eml";
    public $lang = 'ru';

    public static $dictionary = [
        "ru" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Agent.ru')]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Вы создали заказ'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Стоимость перелета к оплате:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Оплатите заказ до указанного времени, чтобы получить Ваши электронные авиабилеты'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByBody($parser) === true) {
            $email->setIsJunk(true);
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
        return count(self::$dictionary);
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
}
