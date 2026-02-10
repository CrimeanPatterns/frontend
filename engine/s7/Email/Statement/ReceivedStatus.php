<?php

namespace AwardWallet\Engine\s7\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ReceivedStatus extends \TAccountChecker
{
    public $mailFiles = "s7/statements/it-63871158.eml, s7/statements/it-73745643.eml, s7/statements/it-73853316.eml, s7/statements/it-74420522.eml";
    private $lang = '';
    private $reProvider = ['S7 Airlines', 'S7 Priority'];
    private $detectSubjects = [
        'en' => [
            'S7 Priority status',
        ],
        'ru' => [
            'статус S7 Priority',
            'На ваш счет начислены мили',
        ],
    ];
    private $reBody = [
        'ru' => [
            ['Поздравляем с получением статуса', 'в программе S7 Priority'],
            ['S7', ['В личный кабинет', 'В профиль']],
            ['S7 Priority', 'История операций'],
        ],
        'en' => [
            ['S7 Airlines', 'Congratulations on getting your'],
        ],
    ];
    private static $dictionary = [
        'ru' => [
        ],
        'en' => [
            'Здравствуйте'       => 'Hello,',
            'получением статуса' => 'getting your',
        ],
    ];

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Здравствуйте'))}]", null, true,
            "/^\s*{$this->opt($this->t('Здравствуйте'))}\,\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\b/u")
            ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('Здравствуйте'))}]/following-sibling::*[1]", null, true,
            "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\b/u");
        $status = $this->http->FindSingleNode("//text()[{$this->contains($this->t('получением статуса'))}]/following-sibling::*[1]", null, true,
            "/^([[:alpha:]\s]{4,})$/u");

        if (isset($name)) {
            $st->addProperty('Name', trim($name, '!'));
        }

        if (isset($status)) {
            $st->addProperty('Status', $status);
        }

        $number = $this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'На ваш')]/following::text()[normalize-space()][1]", null, true, "/^(\d+)$/");

        if (!empty($number)) {
            $st->setNumber($number)
                ->setLogin($number);
        }

        $balance = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'Текущий баланс')]/following::text()[normalize-space()][1]", null, true, "/^(\d+)$/");

        if (!empty($balance)) {
            $st->setBalance($balance);
        } else {
            $st->setNoBalance(true);
            $st->setMembership(true);
        }

        $dateBalance = $this->http->FindSingleNode("//text()[normalize-space()='Дата:']/following::text()[normalize-space()][1]");

        if (!empty($dateBalance)) {
            $st->setBalanceDate(strtotime($dateBalance));
        }

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]s7\.ru$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from']) || stripos($headers['from'], 's7.ru') === false)
            && strpos($headers['subject'], 'S7') === false
        ) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[{$this->contains($this->reProvider)}]")->length === 0) {
            return false;
        }

        if ($this->assignLang()
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Код подтверждения для входа в профиль:'))}]")->length === 0) {
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

    private function assignLang()
    {
        foreach ($this->reBody as $lang => $values) {
            foreach ($values as $value) {
                if ($this->http->XPath->query("//text()[{$this->contains($value[0])}]")->length > 0
                    && $this->http->XPath->query("//text()[{$this->contains($value[1])}]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
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

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(' or ', array_map(function ($s) {
            return 'contains(normalize-space(.),"' . $s . '")';
        }, $field));
    }

    private function starts($field, $node = '.'): string
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
}
