<?php

namespace AwardWallet\MainBundle\Service\AwardSeason;

class AwardSeasonSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->TableName = 'AwardSeason';
        $this->Fields['AwardSeasonID']['Caption'] = 'ID';
    }
}
