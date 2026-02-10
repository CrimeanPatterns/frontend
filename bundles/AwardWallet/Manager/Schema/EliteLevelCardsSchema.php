<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Service\EnhancedAdmin\AbstractEnhancedSchema;
use Doctrine\DBAL\Connection;

class EliteLevelCardsSchema extends AbstractEnhancedSchema
{
    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();
        $this->connection = $connection;

        $this->TableName = 'Account';
        $this->ListClass = EliteLevelCardsList::class;
        $this->KeyField = 'AccountID';

        $this->Fields = [
            'AccountID' => [
                'Type' => 'integer',
                'Size' => null,
                'Required' => true,
                'LookupTable' => 'Account',
                'filterWidth' => 60,
                'FilterField' => 'a.AccountID',
                'InputAttributes' => 'readonly',
            ],
            'Login' => [
                'Type' => 'string',
                'Size' => null,
                'LookupTable' => 'Account',
                'filterWidth' => 60,
                'FilterField' => 'a.Login',
                'InputAttributes' => 'readonly',
            ],
            'ImageFront' => [
                'Type' => 'html',
                'Database' => false,
            ],
            'ImageBack' => [
                'Type' => 'html',
                'Database' => false,
            ],
        ];
    }

    public function GetListFields(): array
    {
        $result = parent::GetListFields();

        return $result;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->PageSizes = ['50' => '50', '100' => '100', '500' => '500'];
        $list->PageSize = 100;
        $list->CanAdd = false;
        $list->ShowImport = false;
        $list->ShowExport = false;
        $list->AllowDeletes = true;
        $list->ReadOnly = false;
        $list->DefaultSort = 'AccountID';
    }
}
