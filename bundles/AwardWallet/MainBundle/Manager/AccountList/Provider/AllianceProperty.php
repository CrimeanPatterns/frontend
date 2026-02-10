<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Alliance;

class AllianceProperty
{
    /**
     * @var int
     */
    public $allianceId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $alias;

    /**
     * @var array
     */
    public $levels;

    public function __construct(Alliance $alliance)
    {
        $this->allianceId = $alliance->getAllianceid();
        $this->name = $alliance->getName();
        $this->alias = $alliance->getAlias();
        $this->levels = [];

        foreach ($alliance->getLevels() as $level) {
            $this->levels[$level->getRank()] = $level->getName();
        }
    }
}
