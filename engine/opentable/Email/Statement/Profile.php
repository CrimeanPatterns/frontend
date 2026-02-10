<?php

namespace AwardWallet\Engine\opentable\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class Profile extends \TAccountChecker
{
    public $mailFiles = "opentable/statements/it-63791546.eml, opentable/statements/it-64258345.eml, opentable/statements/it-64259292.eml";

    private $detectFrom = ["@opentable.", ".opentable."];
    private $detectSubjects = [
        "Your midyear dining stats check in!",
        " dining stats: How'd you fare?",
        ", welcome to OpenTable!",
    ];

    public function detectEmailFromProvider($from)
    {
        return $this->striposAll($from, $this->detectFrom);
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->detectSubjects as $dSubjects) {
            if (is_string($dSubjects) && array_key_exists('subject', $headers) && stripos($headers['subject'], $dSubjects) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true) {
            return false;
        }

        return $this->isMembership();
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $st = $email->add()->statement();

        if ($this->isMembership()) {
            $st->setMembership(true);
        }

        $pointsText = $this->http->FindSingleNode("//text()[contains(normalize-space(),', you have') and contains(normalize-space(),'Dining Points.')]");

        if (preg_match("/^(?<name>\w+)\s*,\s*you have\s+(?<balance>\d[\d,.]*)\s+Dining Points\./u", $pointsText, $m)) {
            // it-63791546.eml
            $st->setBalance(str_replace(',', '', $m['balance']));
            $st->addProperty('Name', $m['name']);
        }
        $class = explode('\\', __CLASS__);
        $email->setType('Statement' . end($class));

        return $email;
    }

    private function isMembership(): bool
    {
        // examples: it-64259292.eml

        $profileUpdateButtons = $this->http->XPath->query("//a[normalize-space()='Update your profile' and contains(@href,'.opentable.com/')]")->length;

        if ($profileUpdateButtons > 0 && (
                $this->http->FindSingleNode("//*[ ../self::tr and not(normalize-space()) and .//img[contains(@src,'/Metros_Light.') or contains(@src,'opentable.com/INTL/Icons/header_icon.')] ]/following-sibling::*[normalize-space()][1][contains(.,'Update')]", null, true, "/Update[\s)]*$/i") !== null
                || $this->http->XPath->query("//tr[{$this->starts(['OpenTable Dining Stats for', 'OpenTable Dining Statsfor'])}]")->length > 0 // it-64258345.eml
                || $this->http->XPath->query("//tr[contains(normalize-space(),', thanks for booking with OpenTable')]")->length > 0
            )
        ) {
            $this->logger->debug(__FUNCTION__ . '()');

            return true;
        }

        return false;
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function striposAll($text, $needle): bool
    {
        if (empty($text) || empty($needle)) {
            return false;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (stripos($text, $n) !== false) {
                    return true;
                }
            }
        } elseif (is_string($needle) && stripos($text, $needle) !== false) {
            return true;
        }

        return false;
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
