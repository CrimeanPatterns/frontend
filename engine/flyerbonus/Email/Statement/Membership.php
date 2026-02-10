<?php

namespace AwardWallet\Engine\flyerbonus\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;

class Membership extends \TAccountChecker
{
	public $mailFiles = "flyerbonus/statements/it-906090052.eml, flyerbonus/statements/it-909131875.eml, flyerbonus/statements/it-909133157.eml, flyerbonus/statements/it-909222890.eml";
    private $detectSubjects = [
        'Mid Year Sale! MORE Savings await when booking via LINE',
        'Enjoy Special Rate Stays and Receive',
        'Final Call! Enjoy',
        'Super Sale, FlyerBonus Members can now book special fares before anyone else!',
    ];
    private $detectBody = [
        'Dear FlyerBonus Member,',
    ];

    public function detectEmailFromProvider($from)
    {
        return stripos($from, 'flyerbonusnews@bangkokair.com') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (stripos($headers['from'], 'flyerbonusnews@bangkokair.com') === false) {
            return false;
        }
        foreach ($this->detectSubjects as $dSubject) {
            if (stripos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }
        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if (!empty($this->http->FindSingleNode("(//a[contains(@href, 'bangkokair.com')]/@href)[1]"))
            && !empty($this->http->FindSingleNode("//text()[".$this->contains($this->detectBody)."]"))
        ) {
            return true;
        }
        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if ($this->detectEmailByHeaders($parser->getHeaders()) == true
            && !empty($this->detectEmailByBody($parser) === true)
        ) {
            $st = $email->add()->statement();
            $st->setMembership(true);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class));

        return $email;
    }

    public static function getEmailLanguages()
    {
        return ['en'];
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) { return 'contains(normalize-space(.),"' . $s . '")'; }, $field)) . ')';
    }
}
