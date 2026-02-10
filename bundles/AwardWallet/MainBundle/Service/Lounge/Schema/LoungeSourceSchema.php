<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema;

class LoungeSourceSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->TableName = 'LoungeSource';
        $this->ListClass = LoungeSourceList::class;

        $this->Fields = [
            'LoungeSourceID' => [
                'Caption' => 'ID',
                'Type' => 'integer',
                'filterWidth' => 20,
            ],
            'Name' => [
                'Type' => 'string',
                'filterWidth' => 60,
            ],
            'AirportCode' => [
                'Type' => 'string',
                'filterWidth' => 30,
            ],
            'Terminal' => [
                'Type' => 'string',
                'Sort' => false,
                'AllowFilters' => false,
            ],
            'SourceCode' => [
                'Caption' => 'Source',
                'Type' => 'string',
                'filterWidth' => 50,
                'Options' => [
                    'amex' => 'amex',
                    'delta' => 'delta',
                    'dragonPass' => 'dragonPass',
                    'loungebuddy' => 'loungebuddy', // TODO: remove in March 2025
                    'loungeKey' => 'loungeKey',
                    'loungereview' => 'loungereview',
                    'priorityPass' => 'priorityPass',
                ],
            ],
            'OpeningHours' => [
                'Type' => 'string',
                'Database' => false,
            ],
            'IsAvailable' => [
                'Type' => 'boolean',
            ],
            'PriorityPassAccess' => [
                'Type' => 'boolean',
            ],
            'AmexPlatinumAccess' => [
                'Type' => 'boolean',
            ],
            'DragonPassAccess' => [
                'Type' => 'boolean',
            ],
            'LoungeKeyAccess' => [
                'Type' => 'boolean',
            ],
            'IsRestaurant' => [
                'Type' => 'boolean',
                'Caption' => 'Restaurant',
            ],
            'AdditionalInfo' => [
                'Type' => 'string',
                'Sort' => false,
                'AllowFilters' => false,
            ],
            'UpdateDate' => [
                'Type' => 'datetime',
            ],
            'ParseDate' => [
                'Type' => 'datetime',
            ],
            'DeleteDate' => [
                'Type' => 'datetime',
                'Sort' => 'IF(DeleteDate IS NOT NULL, 0, 1) ASC, DeleteDate ASC',
            ],
            'LoungeID' => [
                'Type' => 'integer',
            ],
        ];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->PageSizes = ['50' => '50', '100' => '100', '500' => '500'];
        $list->PageSize = 100;
        $list->CanAdd = false;
        $list->ShowImport = false;
        $list->ShowExport = false;
        $list->AllowDeletes = false;
        $list->ReadOnly = true;
        $list->DefaultSort = 'DeleteDate';
    }
}
