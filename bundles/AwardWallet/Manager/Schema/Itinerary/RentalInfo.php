<?php

namespace AwardWallet\Manager\Schema\Itinerary;

class RentalInfo extends AbstractSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->TableName = 'Rental';
        $this->ListClass = ItinerariesList::class;
        $this->Fields = [
            'RentalID' => [
                'Caption' => 'ID',
                'Type' => 'integer',
                'Required' => true,
                'filterWidth' => 30,
            ],
            'RentalCompanyName' => [
                'Type' => 'string',
                'AllowFilters' => false,
            ],
            'PickupDatetime' => [
                'Caption' => 'Pickup',
                'Type' => 'datetime',
            ],
            'DropoffDatetime' => [
                'Caption' => 'Dropoff',
                'Type' => 'datetime',
            ],
            'Number' => [
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
                t.RentalID,
                t.RentalCompanyName,
                t.PickupDatetime,
                t.DropoffDatetime,
                t.Number,
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
                pickupGt.TimeZoneLocation AS PickupTimeZone,
                dropOffGt.TimeZoneLocation AS DropoffTimeZone
            FROM
                Rental t
                LEFT JOIN Usr u ON u.UserID = t.UserID
                LEFT JOIN UserAgent ua ON ua.UserAgentID = t.UserAgentID
                LEFT JOIN Provider p ON p.ProviderID = t.ProviderID
                LEFT JOIN GeoTag pickupGt ON pickupGt.GeoTagID = t.PickupGeoTagID
                LEFT JOIN GeoTag dropOffGt ON dropOffGt.GeoTagID = t.DropoffGeoTagID
            WHERE 
                1 = 1
                [Filters]
        ";
    }
}
