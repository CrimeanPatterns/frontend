<?php

namespace AwardWallet\Engine\mabuhay\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class FromCustomerService extends \TAccountChecker
{
    public $mailFiles = "mabuhay/statements/it-912296885.eml";

    public static $dictionary = [
        'en' => [],
    ];

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query("//a[{$this->contains(['.philippineairlines.com/', 'www.philippineairlines.com'], '@href')}] | //*[{$this->contains(['Greetings from Mabuhay Miles!'])}]")->length === 0
        ) {
            return false;
        }

        return $this->isMembership($parser->getCleanFrom());
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]philippineairlines\.com$/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        if ($this->isMembership($parser->getCleanFrom())) {
            $st->setMembership(true);

            return $email;
        }

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

    private function isMembership(?string $from): bool
    {
        return preg_match('/^mabuhaymiles@philippineairlines\.com$/i', $from) > 0
            && $this->http->XPath->query("//text()[{$this->starts(['Dear'])}]")->length > 0;
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
}
