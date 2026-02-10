<?php

namespace AwardWallet\Manager\Schema;

class UserDeleted extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->TableName = 'UsrDeleted';
        $this->ListClass = UserDeletedList::class;
        $this->KeyField = 'UserDeletedID';

        $this->Fields = $this->getFields();
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $this->dateKey = 'DeletionDate';
        $list->SQL = $this->getSqlBy();

        $list->MultiEdit =
        $list->InplaceEdit =
        $list->CanAdd =
        $list->ShowExport =
        $list->ShowImport = false;

        $list->DefaultSort = 'DeletionDate';
    }

    public function getSqlBy()
    {
        $conn = getSymfonyContainer()->get('database_connection');

        $where = '1';

        if (!empty($_GET['dfrom'])) {
            $where = $this->dateKey . ' >= ' . $conn->quote($_GET['dfrom'] . ' 00:00:00');

            if (!empty($_GET['dto'])) {
                $where = $this->dateKey . ' BETWEEN ' . $conn->quote($_GET['dfrom'] . ' 00:00') . ' AND ' . $conn->quote($_GET['dto'] . ' 23:59:59');
            }
        } elseif (!empty($_GET['dto'])) {
            $where = $this->dateKey . ' <= ' . $conn->quote($_GET['dto'] . ' 23:59:59');
        }

        return '
            SELECT *
            FROM ' . $this->TableName . ' t
            WHERE ' . $where . ' [Filters]
        ';
    }

    private function getFields(): array
    {
        return [
            'UserDeletedID' => [
                'Type' => 'integer',
                'Size' => null,
                'Required' => true,
                'LookupTable' => 'UserDeleted',
                'Caption' => 'id',
                'filterWidth' => 60,
                'InputAttributes' => 'readonly',
            ],
            'UserID' => [
                'Type' => 'integer',
                'Size' => null,
                'Required' => true,
                'LookupTable' => null,
                'filterWidth' => 60,
            ],
            'RegistrationDate' => ['Type' => 'date', 'Size' => null, 'Required' => true, 'LookupTable' => null],
            'FirstName' => [
                'Type' => 'string',
                'Size' => 30,
                'Required' => true,
                'LookupTable' => null,
                'Value' => '',
            ],
            'LastName' => ['Type' => 'string', 'Size' => 30, 'Required' => true, 'LookupTable' => null, 'Value' => ''],
            'Email' => ['Type' => 'string', 'Size' => 80, 'Required' => true, 'LookupTable' => null],
            'Accounts' => [
                'Type' => 'integer',
                'Size' => null,
                'Required' => true,
                'LookupTable' => null,
                'Value' => '0',
                'filterWidth' => 60,
            ],
            'ValidMailboxesCount' => [
                'Type' => 'integer',
                'Size' => null,
                'Required' => true,
                'LookupTable' => null,
                'Value' => '0',
                'filterWidth' => 60,
            ],
            'DeletionDate' => ['Type' => 'date', 'Size' => null, 'Required' => true, 'LookupTable' => null],
            'TotalContribution' => [
                'Type' => 'float',
                'Size' => null,
                'Required' => true,
                'LookupTable' => null,
                'Value' => '0.00',
                'filterWidth' => 60,
            ],
            'CameFrom' => [
                'Type' => 'integer',
                'Size' => null,
                'Required' => false,
                'LookupTable' => null,
                'filterWidth' => 60,
            ],
            'Referer' => ['Type' => 'string', 'Size' => 250, 'Required' => false, 'LookupTable' => null],
            'CardClicks' => [
                'Type' => 'integer',
                'Size' => null,
                'Required' => true,
                'LookupTable' => null,
                'Value' => '0',
                'filterWidth' => 60,
            ],
            'CardApprovals' => [
                'Type' => 'integer',
                'Size' => null,
                'Required' => true,
                'LookupTable' => null,
                'Value' => '0',
                'filterWidth' => 60,
            ],
            'CardEarnings' => [
                'Type' => 'float',
                'Size' => null,
                'Required' => true,
                'LookupTable' => null,
                'Value' => '0.00',
                'filterWidth' => 60,
            ],
            'Reason' => ['Type' => 'string', 'Size' => 4000, 'Required' => true, 'LookupTable' => null],
        ];
    }
}
