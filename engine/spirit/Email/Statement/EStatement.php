<?php

namespace AwardWallet\Engine\spirit\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class EStatement extends \TAccountChecker
{
    public $mailFiles = "spirit/statements/it-187097814.eml, spirit/statements/it-187436861.eml, spirit/statements/it-187437793.eml, spirit/statements/it-904494419.eml";
    public $subjects = [
        'Your eStatement for',
        'Your Free Spirit® eStatement for',
        'Your Free Spirit(R) eStatement for',
        'Your Free Spirit eStatement for',
    ];

    public $lang = 'en';

    public static $dictionary = [
        'en' => [
            'Free Spirit #' => ['Free Spirit #', 'FREE SPIRIT #'],
            'Free Spirit Number' => ['Free Spirit Number', 'FREE SPIRIT NUMBER'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $subject) {
            if (is_string($subject) && array_key_exists('subject', $headers) && stripos($headers['subject'], $subject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $href = ['.spirit-airlines.com/', 'save.spirit-airlines.com'];

        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//text()[starts-with(normalize-space(),"Copyright ©") and contains(normalize-space(),"Spirit Airlines")]')->length === 0
        ) {
            return false;
        }

        return $this->findRoot1()->length > 0 || $this->findRoot2()->length > 0;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.](?:save|fly)\.spirit-airlines\.com$/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();
        $name = $number = $sqPoints = $balance = $balanceExpiration = null;

        $roots1 = $this->findRoot1();

        if ($roots1->length === 1) {
            $this->logger->debug('Found root1.');
            $root1 = $roots1->item(0);

            $name = $this->http->FindSingleNode("*[normalize-space()][1]/descendant::text()[{$this->starts($this->t('Hey'))}]", $root1, true, "/{$this->opt($this->t('Hey'))}[,!\s]+(\w+)(?:\s*[,;!]|$)/u");

            $number = $this->http->FindSingleNode("*[normalize-space()][2]/descendant::text()[{$this->starts($this->t('Free Spirit #'))}]", $root1, true, "/{$this->opt($this->t('Free Spirit #'))}[:\s]*(\d[-\d]*\d)$/");

            $balance = $this->http->FindSingleNode("following::text()[{$this->starts($this->t('YOU HAVE'))}]/following::td[normalize-space()][1][{$this->contains($this->t('POINTS'))}]", $root1, true, "/^\s*(\d[\d,.]*?)\s*{$this->opt($this->t('POINTS'))}/i");
        }

        $roots2 = $this->findRoot2();

        if ($roots2->length === 1) {
            $this->logger->debug('Found root2.');
            $root2 = $roots2->item(0);

            $name = $this->http->FindSingleNode("*[normalize-space()][1]/descendant::text()[{$this->starts($this->t('Hi'))}]", $root2, true, "/{$this->opt($this->t('Hi'))}[,!\s]+(\w+)[,.;:!?\s]*$/u");

            $number = $this->http->FindSingleNode("*[normalize-space()][2]/descendant::text()[{$this->eq($this->t('Free Spirit Number'), "translate(.,':','')")}]/following::text()[normalize-space()][1]", $root2, true, "/^\d[-\d]*\d$/");

            $balance = $this->http->FindSingleNode("following::text()[{$this->eq($this->t('Free Spirit Points'), "translate(.,':','')")}]/following::text()[normalize-space()][1]", $root2, true, "/^\d[\d,.]*$/");

            $balanceExpirationVal = $this->http->FindSingleNode("following::text()[{$this->eq($this->t('Free Spirit Points'), "translate(.,':','')")}]/following::text()[normalize-space()][position()<5][{$this->contains($this->t('Valid until'))}]", $root2, true, "/{$this->opt($this->t('Valid until'))}[:\s]+(\d{1,2}\/\d{1,2}\/\d{2,4})(?:\s*\)|[,.\s]*$)/");
            $balanceExpiration = strtotime($balanceExpirationVal);

            if ($balanceExpirationVal) {
                $st->setExpirationDate($balanceExpiration);
            }
        }

        if (!empty($name)) {
            $st->addProperty('Name', $name);
        }

        if ($number !== null) {
            $st->setNumber($number);
        }

        $sqPoints = $this->getSQPoints();

        if ($sqPoints !== null) {
            $st->addProperty('StatusQualifyingPoints', $sqPoints);
        }

        if ($balance !== null) {
            $st->setBalance(str_replace(',', '', $balance));
        } elseif ($name || $number !== null || $sqPoints !== null || $balanceExpiration) {
            $st->setNoBalance(true);
        }

        return $email;
    }

    private function findRoot1(): \DOMNodeList
    {
        // examples: it-187097814.eml, it-187436861.eml, it-187437793.eml
        return $this->http->XPath->query("//*[ *[normalize-space()][1]/descendant::text()[{$this->starts($this->t('E-Statement for'))}] and *[normalize-space()][2]/descendant::text()[{$this->starts($this->t('Free Spirit #'))}] ]");
    }

    private function findRoot2(): \DOMNodeList
    {
        // examples: it-904494419.eml
        return $this->http->XPath->query("//*[ *[normalize-space()][1]/descendant::text()[{$this->starts($this->t('Hi'))}] and *[normalize-space()][2]/descendant::text()[{$this->starts($this->t('Free Spirit Number'))}] ]");
    }

    private function getSQPoints(): ?string
    {
        return $this->http->FindSingleNode("//img[contains(@src,'mi_points')]/@src", null, true, "/(?:\?|&)mi_points=(\d+)(?:&|$)/i");
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function t(string $phrase, string $lang = '')
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return $phrase;
        }
        if ($lang === '') {
            $lang = $this->lang;
        }
        if ( empty(self::$dictionary[$lang][$phrase]) ) {
            return $phrase;
        }
        return self::$dictionary[$lang][$phrase];
    }

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }
}
