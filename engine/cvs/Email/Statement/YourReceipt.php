<?php

namespace AwardWallet\Engine\cvs\Email\Statement;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;

class YourReceipt extends \TAccountChecker
{
    public $mailFiles = "cvs/statements/it-64345284.eml, cvs/statements/it-71006809.eml, cvs/statements/it-907152596.eml, cvs/statements/it-907408833.eml, cvs/statements/it-908473214.eml";

    public $lang = 'en';

    public $detectSubjects = [
        'en' => [
            'Your CVS Pharmacy® Receipt -',
            'Your Receipt',
        ],
    ];

    public static $dictionary = [
        'en' => [
            'rewardsEnd' => ['Active Members', 'Access all coupons & rewards'],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]cvs\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from']) || stripos($headers['from'], 'cvs.com') === false)
            && stripos($headers['subject'], 'from CVS') === false
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

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('CVS Pharmacy, Inc.'))}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains('cvs.com')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('cvs.com')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if (($this->http->XPath->query("//text()[{$this->eq($this->t('Deals & Rewards'))}]")->length > 0
            || $this->http->XPath->query("//text()[{$this->eq($this->t('Never Miss a Deal'))}]")->length > 0)
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Discounts Applied'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Store #'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->starts($this->t('Cashier #'))}]")->length > 0
            && ($this->http->XPath->query("//text()[{$this->starts($this->t('Year to Date Savings'))}]")->length > 0
                || $this->http->XPath->query("//text()[{$this->contains($this->t('Total ExtraCare savings this year'))}]")->length > 0)
        ) {
            return true;
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        $name = $this->http->FindSingleNode("(//*[{$this->starts($this->t('ExtraCare® Member:'))}])[last()]", null, true, "/^\s*{$this->opt($this->t('ExtraCare® Member:'))}\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/u");

        if (!empty($name)) {
            $st->addProperty('Name', $name);
        }

        $seqNo = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Receipt Seq No.:'))}])[1]", null, true, "/^\s*{$this->opt($this->t('Receipt Seq No.:'))}\s*(\d{5,})\s*$/");

        if (!empty($seqNo)) {
            $st->addProperty('SequenceNumber', $seqNo);
        }

        $barcodes = array_merge(
            array_filter($this->http->FindNodes("//img[{$this->contains('.cvs.com/bca', '@src')} and normalize-space(@alt)]/@alt", null, '/^\d{7,}$/')),
            array_filter($this->http->FindNodes("//img[{$this->contains('.cvs.com/', '@src')} and {$this->contains('bc=', '@src')}]/@src", null, '/bc=(\d{7,})(?:\D|$)/i'))
        );

        $barcodes = array_values(array_unique($barcodes));

        if (count($barcodes) === 1) {
            $st->addProperty('BarCodeNumber', $barcodes[0]);
        }

        $YTDSavings = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Year to Date Savings'))}]", null, true, "/\s*{$this->opt($this->t('Year to Date Savings'))}[:\s]*([\d\.\,\']+)\s*$/i");

        if (!empty($YTDSavings)) {
            $st->addProperty('YTDSavings', PriceHelper::parse($YTDSavings));
        }

        $rewardsText = implode("\n", array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('ExtraCare Card balances as of'))}]/following::text()[normalize-space()][ following::text()[{$this->starts($this->t('rewardsEnd'))}] ]")));

        /*
            CreditsNeeded example:

            Maybelline, Spend 15 Get 5 EB
            Amount Toward this Reward 9.79
            Amount Needed to Earn Reward 5.21
        */

        $creditsNeededPattern = "/"
            . "\s+Get[ ]+[^\d\s]?[ ]*5.*\n"
            . "[ ]*Amount Toward this Reward[ ]+[\d\.\,\']+[ ]*\n"
            . "[ ]*Amount Needed to Earn Reward[ ]+([\d\.\,\']+)\s+"
            . "/i";

        $creditsNeeded = $this->re($creditsNeededPattern, $rewardsText);

        if (!empty($creditsNeeded)) {
            $st->addProperty('CreditsNeeded', PriceHelper::parse($creditsNeeded));
        }

        /*
            ToNextThreeReward example:

            Grove Co., Buy 2 Get 3 EB
            Quantity Toward this Reward 1
            Quantity Needed to Earn Reward 1
        */

        $toNextThreeRewardPattern = "/"
            . "\s+Get[ ]+[^\d\s]?[ ]*3.*\n"
            . "[ ]*Quantity Toward this Reward[ ]+\d+[ ]*\n"
            . "[ ]*Quantity Needed to Earn Reward[ ]+(\d+)\s+"
            . "/i";

        $toNextThreeReward = $this->re($toNextThreeRewardPattern, $rewardsText);

        if (!empty($toNextThreeReward)) {
            $st->addProperty('ToNextThreeReward', PriceHelper::parse($toNextThreeReward));
        }

        /*
            ToNextReward example:

            Mentos, Buy 2 Get 1 EB
            Quantity Toward this Reward 1
            Quantity Needed to Earn Reward 1
        */

        $toNextRewardPattern = "/"
            . "\s+Get[ ]+[^\d\s]?[ ]*1.*\n"
            . "[ ]*Quantity Toward this Reward[ ]+\d+[ ]*\n"
            . "[ ]*Quantity Needed to Earn Reward[ ]+(\d+)\s+"
            . "/i";

        $toNextReward = $this->re($toNextRewardPattern, $rewardsText);

        if (!empty($toNextReward)) {
            $st->addProperty('ToNextReward', PriceHelper::parse($toNextReward));
        }

        $st->setNoBalance(true);

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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
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
