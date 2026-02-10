<?php

namespace AwardWallet\Engine\marriott\Email\Statement;

use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class ViewActivity extends \TAccountChecker
{
    public $mailFiles = "marriott/statements/it-929649398.eml";
    public $subjects = [
        'Account Update:',
        'Marriott Bonvoy',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'MY ACCOUNT' => ['MY ACCOUNT', 'my account'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@email-marriott.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Marriott Bonvoy'))}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('View activity'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('MY ACCOUNT'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('find & reserve'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('nights this year'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]email\-marriott\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'XXXXX')]/ancestor::tr[1]/following::text()[normalize-space()][1]", null, true, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/");

        if (!empty($name)) {
            $st->addProperty('Name', trim($name, ','));
        }

        $accInfo = implode("\n", $this->http->FindNodes("//text()[contains(normalize-space(), 'XXXXX')]/ancestor::tr[1]/descendant::text()[normalize-space()]"));

        if (preg_match("/^(?<balance>\-?[\d\,]+)\s+points\n(?<status>\D+)\n\|\n[X]+(?<account>\d{3,})$/", $accInfo, $m)) {
            $st->setBalance(str_replace(',', '', $m['balance']));
            $st->addProperty('Level', $m['status']);
            $st->setNumber($m['account'])->masked();
        }

        $nightsInfo = $this->http->FindSingleNode("//text()[contains(normalize-space(), 'nights this year')]/ancestor::tr[1]");

        if (preg_match("/^(?<nights>\d+)\s*nights this year as of\s*(?<dateBalance>\d+\/\d+\/\d{4})\.?$/", $nightsInfo, $m)) {
            $st->addProperty('Nights', trim($m['nights'], ','));

            $dateBalance = $this->normalizeDate($m['dateBalance']);
            $dateEmail = strtotime($parser->getDate());

            if ($dateBalance > $dateEmail) {
                $st->setBalanceDate($this->normalizeDate($m['dateBalance'], true));
            } else {
                $st->setBalanceDate($dateBalance);
            }
        }

        $nightsUntilNextTier = $this->http->FindSingleNode("//text()[{$this->contains($this->t('nights this year'))}]/ancestor::td[1]/following::text()[normalize-space()][1]", null, true, "/^(\d+)$/");

        if (!empty($nightsUntilNextTier)) {
            $st->addProperty('NightsUntilNextTier', $nightsUntilNextTier);
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

    private function normalizeDate($date, $dateInverted = null)
    {
        $this->logger->debug($date);

        if ($dateInverted === null) {
            $enDatesInverted = true;
        } else {
            $enDatesInverted = false;
        }

        if (preg_match('/\b\d{1,2}\/(\d{1,2})\/\d{4}\b/', $date, $m) && (int) $m[1] > 12) {
            // 05/16/2019
            $enDatesInverted = false;
        }

        $in = [
            // 19/12/2018
            '#^(\d+)\/(\d+)\/(\d+)$#u',
        ];
        $out[0] = $enDatesInverted ? '$3-$2-$1' : '$3-$1-$2';
        $str = strtotime($this->dateStringToEnglish(preg_replace($in, $out, $date)));

        return $str;
    }

    private function dateStringToEnglish($date)
    {
        if (preg_match('#[[:alpha:]]+#iu', $date, $m)) {
            $monthNameOriginal = $m[0];

            if ($translatedMonthName = MonthTranslate::translate($monthNameOriginal, $this->lang)) {
                return preg_replace("#$monthNameOriginal#i", $translatedMonthName, $date);
            }
        }

        return $date;
    }
}
