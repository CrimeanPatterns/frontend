<?php

namespace AwardWallet\Engine\umai\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;
use function GuzzleHttp\Psr7\str;

class Booking extends \TAccountChecker
{
	public $mailFiles = "umai/it-911885758.eml, umai/it-912445525.eml, umai/it-912921674.eml, umai/it-913872303.eml";

    public $subjects = [
        "/Reminder for your reservation at .+/i",
        '/Your booking at .+ has been confirmed/i',
        '/Cancelled: Your booking at .+/i',
        '/Edited: Your booking at .+/i',
    ];

    public $lang = 'en';

    public static $dictionary = [
        "en" => [
            'detectPhrase' => ['Here are the details of your booking', 'Here are the details'],
            'detectPhrase2' => ['Your booking is confirmed', 'Your booking is cancelled', 'RESERVATION REMINDER', 'Your booking has been edited'],
            'nameExp' => ['Cancelled: Your booking at (.+)', 'Your booking at (.+) has been confirmed', 'Reminder for your reservation at (.+)'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@letsumai.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (preg_match($subject, $headers['subject'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query('//a[contains(@href,"/letsumai.com/") or contains(@href,".umai.io")]')->length === 0
            && $this->http->XPath->query('//img[contains(@src,"PoweredbyUMAI.png")]')->length === 0
        ) {
            return false;
        }

        if ($this->http->XPath->query("//*[{$this->contains($this->t('detectPhrase'))}]")->length > 0
            && $this->http->XPath->query("//*[{$this->contains($this->t('detectPhrase2'))}]")->length > 0){
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]letsumai\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $e = $email->add()->event();

        $e->type()->restaurant();

        $e->general()
            ->noConfirmation()
            ->traveller($this->http->FindSingleNode("//td[{$this->eq($this->t('Name:'))}]/following-sibling::td[normalize-space()][1]", null, false, "/^([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])$/u"));

        $cancelled = $this->http->FindSingleNode("//text()[{$this->contains($this->t('is cancelled'))}]");

        if ($cancelled !== null) {
            $e->general()
                ->cancelled()
                ->status("Cancelled");
        }

        $address = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Address:'))}]/ancestor::tr[normalize-space()][1]", null, false, "/^{$this->opt($this->t('Address:'))}[ ]*(.+)$/u");

        if ($address !== null){
            $e->place()->address($address);
        }

        $name = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Get Directions'))}]/ancestor::tr[last()]/preceding-sibling::tr[normalize-space()][{$this->contains($this->t('Address'))}][1]", null, false, "/^(.+)[ ]*{$this->opt($this->t('Address'))}$/u");

        if ($name === null) {
            $name = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Address:'))}]/ancestor::tr[last()]/preceding-sibling::tr[normalize-space()][{$this->contains($this->t('Address'))}][1]", null, false, "/^(.+)[ ]*{$this->opt($this->t('Address'))}$/u");
        }

        if ($name === null) {
            $name = $this->re("/{$this->opt($this->t('nameExp'))}/u", $parser->getSubject());
        }

        if ($name !== null){
            $e->place()->name($name);
        }

        $date = $this->http->FindSingleNode("//td[{$this->eq($this->t('Date:'))}]/following-sibling::td[normalize-space()][1]");

        $time = $this->http->FindSingleNode("//td[{$this->eq($this->t('Time:'))}]/following-sibling::td[normalize-space()][1]");

        if (preg_match("/^(?<startTime>[0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?)[ ]*\-?[ ]*(?:(?<endTime>[0-9]{1,2}\:[0-9]{2}[ ]*[Aa]?[Pp]?[Mm]?))?\$/", $time, $m) && $date !== null){
            $e->booked()
                ->start($start = strtotime($date . ', ' . $m['startTime']));

            if (isset($m['endTime'])){
                $end = strtotime($date . ', ' . $m['endTime']);

                if ($end <= $start){
                    $end += 86400;
                }

                $e->booked()
                    ->end($end);
            } else {
                $e->booked()->noEnd();
            }
        }

        $guests = $this->http->FindSingleNode("//td[{$this->eq($this->t('Number of guests:'))}]/following-sibling::td[normalize-space()][1]", null, false, "/^([0-9]+)$/u");

        if ($guests !== null) {
            $e->booked()->guests($guests);
        }

        $email->setType('Booking' . ucfirst($this->lang));

        return $email;
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
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
                return str_replace(' ', '\s+', $s);
            }, $field)) . ')';
    }
}
