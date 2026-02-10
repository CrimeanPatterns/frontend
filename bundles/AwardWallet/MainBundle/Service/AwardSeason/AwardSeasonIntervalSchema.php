<?php

namespace AwardWallet\MainBundle\Service\AwardSeason;

use Doctrine\DBAL\Connection;

class AwardSeasonIntervalSchema extends \TBaseSchema
{
    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->TableName = 'AwardSeasonInterval';
        $this->Fields['AwardSeasonID']['Options'] = $connection
            ->fetchAllKeyValue('SELECT AwardSeasonID, Name FROM AwardSeason ORDER BY AwardSeasonID');
        $this->Fields['StartDate']['Type'] = 'date';
        $this->Fields['EndDate']['Type'] = 'date';
    }
}
