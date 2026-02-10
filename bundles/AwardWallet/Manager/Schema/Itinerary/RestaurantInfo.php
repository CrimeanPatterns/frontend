<?php

namespace AwardWallet\Manager\Schema\Itinerary;

class RestaurantInfo extends AbstractSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->TableName = 'Restaurant';
        $this->ListClass = ItinerariesList::class;
        $this->Fields = [
            'RestaurantID' => [
                'Caption' => 'ID',
                'Type' => 'integer',
                'Required' => true,
                'filterWidth' => 30,
            ],
            'Name' => [
                'Type' => 'string',
                'AllowFilters' => false,
            ],
            'StartDate' => [
                'Caption' => 'Start',
                'Type' => 'datetime',
            ],
            'EndDate' => [
                'Caption' => 'End',
                'Type' => 'datetime',
            ],
            'ConfNo' => [
                'Type' => 'string',
            ],
            'UserID' => [
                'Type' => 'integer',
            ],
            'ProviderID' => [
                'Type' => 'integer',
                'Options' => $this->getProviderOptions(),
            ],
            'CreateDate' => [
                'Type' => 'datetime',
            ],
            'ChangeDate' => [
                'Type' => 'datetime',
            ],
            'Sources' => [
                'Type' => 'html',
                'Database' => false,
            ],
            'Info' => [
                'Type' => 'html',
                'Database' => false,
            ],
            'Actions' => [
                'Type' => 'html',
                'Database' => false,
            ],
        ];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->CanAdd = false;
        $list->ShowImport = false;
        $list->ShowExport = false;
        $list->AllowDeletes = false;
        $list->ReadOnly = true;
        $list->SQL = "
            SELECT
                t.RestaurantID,
                t.Name,
                t.StartDate,
                t.EndDate,
                t.ConfNo,
                t.UserID,
                t.UserAgentID,
                IF(ua.UserAgentID IS NOT NULL, CONCAT(ua.FirstName, ' ', ua.LastName), NULL) AS FamilyMemberName,
                IF(AccountLevel = 3, u.Company, CONCAT(u.FirstName, ' ', u.LastName)) AS UserName,
                t.ProviderID,
                p.ShortName,
                t.CreateDate,
                t.ChangeDate,
                t.Sources,
                t.Hidden,
                t.Undeleted,
                t.Cancelled,
                t.Modified,
                t.AccountID,
                g.TimeZoneLocation AS TimeZone
            FROM
                Restaurant t
                LEFT JOIN Usr u ON u.UserID = t.UserID
                LEFT JOIN UserAgent ua ON ua.UserAgentID = t.UserAgentID
                LEFT JOIN Provider p ON p.ProviderID = t.ProviderID
                LEFT JOIN GeoTag g ON g.GeoTagID = t.GeoTagID
            WHERE 
                1 = 1
                [Filters]
        ";
    }
}
