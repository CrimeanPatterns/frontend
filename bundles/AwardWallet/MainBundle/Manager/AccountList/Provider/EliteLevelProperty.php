<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Elitelevel;

class EliteLevelProperty
{
    public $eliteLevelId;
    public $rank;
    public $name;
    public $allianceId;
    public $allianceRank;

    public function __construct(Elitelevel $level)
    {
        $this->eliteLevelId = $level->getElitelevelid();
        $this->rank = $level->getRank();
        $this->name = $level->getName();
        $this->allianceId = $level->getAllianceelitelevelid() ? $level->getAllianceelitelevelid()->getAllianceid()->getAllianceid() : null;
        $this->allianceRank = $level->getAllianceelitelevelid() ? $level->getAllianceelitelevelid()->getRank() : null;
    }
}
