<?php

namespace AwardWallet\Engine\ticketmaster\Email;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Engine\WeekTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class SaveTickets extends \TAccountChecker
{
	public $mailFiles = "ticketmaster/it-893813774.eml, ticketmaster/it-893870118.eml, ticketmaster/it-894321038.eml";
    public $subjects = [
        'View and Save Your Tickets',
    ];

    public $lang = 'en';
    public $date = '';

    public static $dictionary = [
        "en" => [
            'detectPhrase' => ['view and save your tickets', 'Please view & save your tickets', 'How to Save Your Tickets', 'How to View Your Tickets'],
            'paxPhrase' => ['view and save your tickets', 'Please view & save your tickets', 'login through the', 'you have successfully accepted tickets from', 'download and access your tickets'],
            'Transferred Ticket' => ['Transferred Ticket']
        ],

    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@ticketmaster.com') !== false) {
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
        if ($this->http->XPath->query("//img[contains(@src, 'ticketmaster.com')]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Ticketmaster'))}]")->length === 0) {
            return false;
        }

        foreach (self::$dictionary as $dict) {
            if (!empty($dict['detectPhrase']) && $this->http->XPath->query("//*[{$this->contains($dict['detectPhrase'])}]")->length > 0
                && !empty($dict['Transferred Ticket']) && $this->http->XPath->query("//*[{$this->contains($dict['Transferred Ticket'])}]")->length > 0
            ) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]ticketmaster\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $subject = $parser->getSubject();

        $this->date = EmailDateHelper::getEmailDate($this, $parser);

        $this->Event($email, $subject);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function Event(Email $email, $subject)
    {
        $segmentsArr = $this->http->XPath->query("(//text()[starts-with(normalize-space(), 'Transferred Ticket')])[1]/ancestor::table[2]/following-sibling::table[normalize-space()][1]/descendant::td[1]/descendant::table/descendant::tr[normalize-space()][2][./descendant::td[1][./descendant::img] and ./descendant::td[2][count(./table) >= 3]]");

        foreach ($segmentsArr as $segment){
            $e = $email->add()->event();

            $e->type()
                ->event();

            $travName = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('paxPhrase'))}])[1]/ancestor::td[normalize-space()][1]", null, true, "/^([[:alpha:]][-.\'’&[:alpha:] ]*[[:alpha:]])[ ]*\,?[ ]+(?:click below to|follow the steps below to|don\'t forget to|Don\'t forget to|make sure to)?[ ]*{$this->opt($this->t('paxPhrase'))}/");

            if ($travName !== null){
                if (preg_match("/([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])[ ]*\&[ ]*([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])/",$travName, $m)){
                    $e->addTraveller($m[1]);
                    $e->addTraveller($m[2]);
                } else {
                    $e->general()
                        ->traveller($travName);

                    $e->program()
                        ->account($this->http->FindSingleNode("(//text()[{$this->starts($this->t('Account Number'))}])[1]", null, true, "/^{$this->opt($this->t('Account Number'))}[ ]*\:[ ]*([0-9]+)$/"), false, $travName, 'Account Number');
                }
            } else if (preg_match("/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])[ ]*\,[ ]*(?:View and Save Your Tickets)/", $subject, $m)){
                $e->general()
                    ->traveller($m[1]);

                $e->program()
                    ->account($this->http->FindSingleNode("(//text()[{$this->starts($this->t('Account Number'))}])[1]", null, true, "/^{$this->opt($this->t('Account Number'))}[ ]*\:[ ]*([0-9]+)$/"), false, $m[1], 'Account Number');
            }

            if (empty($e->getAccountNumbers())){
                $e->program()
                    ->account($this->http->FindSingleNode("(//text()[{$this->starts($this->t('Account Number'))}])[1]", null, true, "/^{$this->opt($this->t('Account Number'))}[ ]*\:[ ]*([0-9]+)$/"), false, null, 'Account Number');
            }

            $e->general()
                ->noConfirmation();

            $e->place()
                ->name($name = $this->http->FindSingleNode("./descendant::td[normalize-space()][1]/descendant::text()[normalize-space()][1]", $segment));

            $address = $this->http->FindSingleNode("./descendant::td[normalize-space()][1]/descendant::td[img[contains(@src,'location-icon-light')]]/following-sibling::td[normalize-space()][1]/descendant::text()[normalize-space()][1]", $segment);

            if ($address === null){
                $address = $this->http->FindSingleNode("./descendant::td[normalize-space()][1]/descendant::td[{$this->eq($name)}]/ancestor::tr[2]/following::text()[not({$this->eq($name)})][normalize-space()][3]", $segment);
            }

            $e->place()
                ->address(preg_replace("/([ ]*\•[ ]*)/", ', ', $address));

            $date = $this->http->FindSingleNode("./descendant::td[normalize-space()][1]/descendant::td[img[contains(@src,'calender-icon-light')]]/following-sibling::td[normalize-space()][1]/descendant::text()[normalize-space()][1]", $segment);

            if ($date === null){
                $date = $this->http->FindSingleNode("./descendant::td[normalize-space()][1]/descendant::td[{$this->eq($name)}]/ancestor::tr[2]/following::text()[not({$this->eq($name)})][normalize-space()][1]", $segment);
            }
            $this->logger->debug(preg_replace("/([ ]*\•[ ]*)/", ', ', $date));
            $e->booked()
                ->start($this->normalizeDate(preg_replace("/([ ]*\•[ ]*)/", ', ', $date)))
                ->noEnd();

            $seatNodes = array_unique($this->http->FindNodes("./descendant::td[normalize-space()][1]/descendant::td[img[contains(@src,'location-icon-light')]]/ancestor::table[normalize-space()][1]/following-sibling::table[normalize-space()][1]/descendant::text()[normalize-space()]", $segment));

            if (!empty($seatNodes)){
                $e->booked()
                    ->seats($seatNodes);
            }
        }

        /*$e = $email->add()->event();

        $e->type()
            ->event();

        $travName = $this->http->FindSingleNode("(//text()[{$this->contains($this->t('paxPhrase'))}])[1]/ancestor::td[normalize-space()][1]", null, true, "/^([[:alpha:]][-.\'’&[:alpha:] ]*[[:alpha:]])[ ]*\,?[ ]+(?:click below to|follow the steps below to|don\'t forget to|Don\'t forget to|make sure to)?[ ]*{$this->opt($this->t('paxPhrase'))}/");

        if ($travName !== null){
            if (preg_match("/([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])[ ]*\&[ ]*([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])/",$travName, $m)){
                $e->addTraveller($m[1]);
                $e->addTraveller($m[2]);
            } else {
                $e->general()
                    ->traveller($travName);

                $e->program()
                    ->account($this->http->FindSingleNode("(//text()[{$this->starts($this->t('Account Number'))}])[1]", null, true, "/^{$this->opt($this->t('Account Number'))}[ ]*\:[ ]*([0-9]+)$/"), false, $travName, 'Account Number');
            }
        } else if (preg_match("/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])[ ]*\,[ ]*(?:View and Save Your Tickets)/", $subject, $m)){
            $e->general()
                ->traveller($m[1]);

            $e->program()
                ->account($this->http->FindSingleNode("(//text()[{$this->starts($this->t('Account Number'))}])[1]", null, true, "/^{$this->opt($this->t('Account Number'))}[ ]*\:[ ]*([0-9]+)$/"), false, $m[1], 'Account Number');
        }

        if (empty($e->getAccountNumbers())){
            $e->program()
                ->account($this->http->FindSingleNode("(//text()[{$this->starts($this->t('Account Number'))}])[1]", null, true, "/^{$this->opt($this->t('Account Number'))}[ ]*\:[ ]*([0-9]+)$/"), false, null, 'Account Number');

        }

        $e->general()
            ->noConfirmation();

        $e->place()
            ->name($name = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Transferred Ticket'))}])[1]/following::table[normalize-space()][1]/descendant::table[normalize-space()][1]/descendant::td[normalize-space()][2]/descendant::text()[normalize-space()][1]"));

        $address = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Transferred Ticket'))}])[1]/following::table[normalize-space()][1]/descendant::table[normalize-space()][1]/descendant::td[normalize-space()][2]/descendant::td[img[contains(@src,'location-icon-light')]]/following-sibling::td[normalize-space()][1]/descendant::text()[normalize-space()][1]");

        if ($address === null){
            $address = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Transferred Ticket'))}])[1]/following::table[normalize-space()][1]/descendant::table[normalize-space()][1]/descendant::td[normalize-space()][2]/descendant::td[{$this->eq($name)}]/ancestor::tr[2]/following::text()[not({$this->eq($name)})][normalize-space()][3]");
        }

        $e->place()
            ->address(preg_replace("/([ ]*\•[ ]*)/", ', ', $address));

        $date = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Transferred Ticket'))}])[1]/following::table[normalize-space()][1]/descendant::table[normalize-space()][1]/descendant::td[normalize-space()][2]/descendant::td[img[contains(@src,'calender-icon-light')]]/following-sibling::td[normalize-space()][1]/descendant::text()[normalize-space()][1]");

        if ($date === null){
            $date = $this->http->FindSingleNode("(//text()[{$this->starts($this->t('Transferred Ticket'))}])[1]/following::table[normalize-space()][1]/descendant::table[normalize-space()][1]/descendant::td[normalize-space()][2]/descendant::td[{$this->eq($name)}]/ancestor::tr[2]/following::text()[not({$this->eq($name)})][normalize-space()][1]");
        }
        $this->logger->debug(preg_replace("/([ ]*\•[ ]*)/", ', ', $date));
        $e->booked()
            ->start($this->normalizeDate(preg_replace("/([ ]*\•[ ]*)/", ', ', $date)))
            ->noEnd();

        $seatNodes = array_unique($this->http->FindNodes("(//text()[{$this->starts($this->t('Transferred Ticket'))}])[1]/following::table[normalize-space()][1]/descendant::table[normalize-space()][1]/descendant::td[normalize-space()][2]/descendant::td[img[contains(@src,'location-icon-light')]]/ancestor::table[normalize-space()][1]/following-sibling::table[normalize-space()][1]/descendant::text()[normalize-space()]"));

        if (!empty($seatNodes)){
            $e->booked()
                ->seats($seatNodes);
        }*/
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
                return str_replace(' ', '\s+', preg_quote($s, '/'));
            }, $field)) . ')';
    }

    private function normalizeCurrency(string $string): string
    {
        $string = trim($string);
        $currencies = [
            'GBP' => ['£'],
            'EUR' => ['€'],
            'USD' => ['US$'],
            'SGD' => ['SG$'],
            'ZAR' => ['R'],
        ];

        foreach ($currencies as $currencyCode => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency) {
                    return $currencyCode;
                }
            }
        }

        return $string;
    }

    private function normalizeDate(string $string)
    {
        $year = date("Y", $this->date);

        $in = [
            // Thu, Oct 25, 2024, 8:15 PM
            // Sat, Jul 01, 2023 . Gates at 7:15am
            // Sat, Mar 23, 2024, GATES OPEN @ 7AM
            "/([[:alpha:]]+)\,[ ]+([[:alpha:]]+)[ ]+([0-9]{1,2})[ ]*\,[ ]*([0-9]{4})[ ]*[\,\.][ ]*(?:Gates at|GATES OPEN \@|Gates Open|Gate Time)?[ ]+\b([0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?|[0-9]{1,2}[ ]*[Aa]?[Pp]?[Mm]?)/u",
            // Fri, 21 Mar 2025, 19 h 00
            "/([[:alpha:]]+)\,[ ]+([0-9]{1,2})[ ]+([[:alpha:]]+)[ ]*([0-9]{4})[ ]*[\,\.][ ]*([0-9]{1,2})[ ]*h[ ]*([0-9]{2})[ ]*/u",
            // 11/18 SATURDAY, 6PM GATES/10PM EVENT
            "/([0-9]{1,2})\/([0-9]{1,2})[ ]*([[:alpha:]]+)[\,\.][ ]*.*\/([0-9]{1,2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]*EVENT/u",
            // FRIDAY MAY 26 . Open 6:00 AM
            "/([[:alpha:]]+)[ ]*([[:alpha:]]+)[ ]*([0-9]{1,2})[ ]*[\,\.][ ]*(?:Open)[ ]+([0-9]{1,2}\:[0-9]{1,2}[ ]*[Aa]?[Pp]?[Mm]?)/u",
            // Sat, Mar 29, 6:35pm Gates 5pm
            "/([[:alpha:]]+)\,[ ]*([[:alpha:]]+)[ ]*([0-9]{1,2})[ ]*[\,\.][ ]+([0-9]{1,2}\:[0-9]{1,2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]*(?:Gates).*/u",
        ];

        $out = [
            "$1, $2 $3, $4 $5",
            "$1, $3 $2, $4 $5:$6",
            "$3, $1/$2/{$year}, $4",
            "$1, $3 $2 {$year}, $4",
            "$1, $3 $2 {$year}, $4",
        ];

        $string = preg_replace($in, $out, trim($string));
        $this->logger->debug($string);

        if ($year > 2000 && preg_match("/^(?<week>\w+), (?<date>\d+\/\d+\/{$year}, .+|\d+[ ]*\w+[ ]*{$year}, .+)$/u", $string, $m)) {
            $weeknum = WeekTranslate::number1(WeekTranslate::translate($m['week'], $this->lang));

            return EmailDateHelper::parseDateUsingWeekDay($m['date'], $weeknum);
        }

        return strtotime($string);
    }
}
