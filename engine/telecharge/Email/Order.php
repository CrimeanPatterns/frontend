<?php

namespace AwardWallet\Engine\telecharge\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Event;
use AwardWallet\Schema\Parser\Email\Email;

class Order extends \TAccountChecker
{
	public $mailFiles = "telecharge/it-884996467.eml";
    public $lang = '';

    public static $dictionary = [
        'en' => [
            'detectLangPhrase' => 'View Order Details',
        ],
    ];

    private $subjects = [
        'order is confirmed! Order Number',
    ];

    private $detectors = [
        'en' => ['View Order Details',],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]telecharge\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@telecharge.com') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query('//a[contains(@href,"www.telecharge.com")]')->length === 0
        ) {
            return false;
        }

        return $this->detectBody() && $this->assignLang();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        $this->parseEvent($email);

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

    private function parseEvent(Email $email): void
    {
        $event = $email->add()->event();

        $event->place()
            ->type(Event::TYPE_SHOW);

        $event->general()
            ->confirmation($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Order')]", null, false, "/^{$this->opt($this->t('Order'))}\b[ ]*\b([A-Z\d]{5,10})$/"));

        $name = $this->http->FindSingleNode("//a[contains(@href, 'map')]/ancestor::tr[1]/preceding::tr[normalize-space()][1]");

        $event->place()
            ->name($name);

        $addressText = $this->http->FindNodes("//a[contains(@href, 'map')]/descendant::text()[normalize-space()]");

        $event->place()
            ->address(implode(', ', $addressText));

        $detailsText = implode("\n", $this->http->FindNodes("//a[contains(@href, 'map')]/ancestor::tr[1]/following::tr[normalize-space()][contains(normalize-space(), 'Ticket')][1]/descendant::text()[normalize-space()]"));

        if (preg_match("/^\s*[[:alpha:]]+\s*,\s*(?<date>[[:alpha:]]+\s+\d{1,2}\s*,\s*\d{4})\b.+?(?<time>\d{1,2}[:：]\d{2}[ ]*A?P?M)\n/u", $detailsText, $m)) {
            $event->booked()
                ->start($this->normalizeDate($m['date'] . ', ' . $m['time']))
                ->noEnd();
        }

        if (preg_match("/\n[ ]*(?<tickets>\d{1,3})[ ]*{$this->opt($this->t('Ticket(s)'))}\n(?<seatsText>.+?)?\n(?<totalCost>\D{1,3}[ ]*\d[,.\'\d ]*)$/s", $detailsText, $m)
        ) {
            if (preg_match_all("/(\n|^)(.+?)(\,\n|$)/m", $m['seatsText'], $seatMatches)
                && count($seatMatches[2]) == $m['tickets']){
                $event->booked()
                    ->guests($m['tickets'])
                    ->seats($seatMatches[2]);
            }

            if ($m['totalCost'] !== null && preg_match("/^(?<currency>\D{1,3})[ ]*(?<amount>\d[,.\'\d ]*)$/", $m['totalCost'], $matches)){
                $event->price()
                    ->currency($matches['currency'])
                    ->total(PriceHelper::parse($matches['amount'], $matches['currency']));
            }
        }
    }

    private function detectBody(): bool
    {
        if (!isset($this->detectors)) {
            return false;
        }

        foreach ($this->detectors as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (!is_string($phrase)) {
                    continue;
                }

                if ($this->http->XPath->query("//*[{$this->contains($phrase)}]")->length > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function assignLang(): bool
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['detectLangPhrase'])) {
                continue;
            }

            if ($this->http->XPath->query("//*[{$this->contains($phrases['detectLangPhrase'])}]")->length > 0) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

                return 'contains(normalize-space(' . $node . '),' . $s . ')';
            }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
                $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

                return 'normalize-space(' . $node . ')=' . $s;
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

    private function htmlToText(?string $s, bool $brConvert = true): string
    {
        if (!is_string($s) || $s === '') {
            return '';
        }
        $s = str_replace("\r", '', $s);
        $s = preg_replace('/<!--.*?-->/s', '', $s); // comments

        if ($brConvert) {
            $s = preg_replace('/\s+/', ' ', $s);
            $s = preg_replace('/<[Bb][Rr]\b.*?\/?>/', "\n", $s); // only <br> tags
        }
        $s = preg_replace('/<[A-z][A-z\d:]*\b.*?\/?>/', '', $s); // opening tags
        $s = preg_replace('/<\/[A-z][A-z\d:]*\b[ ]*>/', '', $s); // closing tags
        $s = html_entity_decode($s);
        $s = str_replace(chr(194) . chr(160), ' ', $s); // NBSP to SPACE

        return trim($s);
    }

    private function normalizeDate($str)
    {
        $in = [
            "#^(\w+)\s*(\d+)\,\s*(\d{4})\,\s*([\d\:]+\s*A?P?M)$#u", //November 12, 2022, 8:00 PM
        ];
        $out = [
            "$2 $1 $3, $4",
        ];
        $str = preg_replace($in, $out, $str);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $str, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $str = str_replace($m[1], $en, $str);
            }
        }

        return strtotime($str);
    }
}
