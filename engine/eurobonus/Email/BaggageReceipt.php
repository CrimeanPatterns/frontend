<?php

namespace AwardWallet\Engine\eurobonus\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BaggageReceipt extends \TAccountChecker
{
	public $mailFiles = "eurobonus/it-924185048.eml";
    public $subjects = [
        'Baggage receipt(s) for your booking',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@flysas.com') !== false) {
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
        if ($this->http->XPath->query("//img[contains(@alt, 'Scandinavian Airlines')]")->length > 0) {
            return $this->http->XPath->query("//text()[{$this->contains($this->t('Thanks for checking in your baggage.'))}]")->length > 0;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]flysas\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByHeaders($parser->getHeaders()) === true) {
            $f = $email->add()->flight();

            if (preg_match("/{$this->opt($this->t('for your booking'))}\s*([A-Z\d]{6})\s*$/", $parser->getSubject(), $m)) {
                $f->general()
                    ->confirmation($m[1])
                    ->traveller($traveller = $this->http->FindSingleNode("(//tr[./descendant::td[1][{$this->contains($this->t("Don't want further emails?"))}]])[2]/preceding-sibling::tr[normalize-space()][1]/descendant::text()[normalize-space()][1]", null, false, "/^[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]$/u"), true);
            }

            $nodes = $this->http->XPath->query("//tr[./following-sibling::tr[{$this->contains($traveller)}] and ./preceding-sibling::tr[{$this->contains($this->t('Thanks for checking in'))}]]/descendant::td[normalize-space()][3]");

            foreach ($nodes as $root) {
                $s = $f->addSegment();

                $flightInfo = $this->http->FindSingleNode("./descendant::tr[normalize-space()][1]", $root);

                if (preg_match('/^(?<depName>.+)[ ]+(?<depCode>[A-Z]{3})[ ]\-[ ](?<arrName>.+)[ ]+(?<arrCode>[A-Z]{3})[ ]*\((?<code>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<number>\d+)\)$/u', $flightInfo, $m)) {
                    $s->airline()
                        ->name($m['code'])
                        ->number($m['number']);

                    $s->departure()
                        ->name($m['depName'])
                        ->code($m['depCode']);

                    $s->arrival()
                        ->name($m['arrName'])
                        ->code($m['arrCode']);
                }

                $flightDate = $this->http->FindSingleNode("./descendant::tr[normalize-space()][2]", $root, false, "/^(\d{1,2}[ ]*\w+[ ]*\d{4})$/u");

                $flightTime = $this->http->FindSingleNode("./descendant::tr[normalize-space()][3]", $root);

                if (preg_match("/^(?<depTime>\d{1,2}\:\d{2})[ ]*\-[ ]*(?<arrTime>\d{1,2}\:\d{2})[ ]*(?:\(\+(?<nextDay>\d+)\)|$)/u", $flightTime, $m)){
                    $s->departure()
                        ->date(strtotime($flightDate . ", " . $m['depTime']));

                    $s->arrival()
                        ->date($arrDate = strtotime($flightDate . ", " . $m['arrTime']));   

                    if (isset($m['nextDay']) && !empty($m['nextDay'])){
                        $s->arrival()->date($arrDate + $m['nextDay'] * 86400);
                    }
                }
            }
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
                return str_replace(' ', '\s+', preg_quote($s, '/'));
            }, $field)) . ')';
    }
}
