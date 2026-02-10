<?php

namespace AwardWallet\Manager\Schema\Itinerary;

class TripInfo extends AbstractSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->TableName = 'TripSegment';
        $this->ListClass = ItinerariesList::class;
        $this->Fields = [
            'TripSegmentID' => [
                'Caption' => 'ID',
                'Type' => 'integer',
                'Required' => true,
                'filterWidth' => 30,
                'FilterField' => 't.TripSegmentID',
            ],
            'TripID' => [
                'Caption' => 'TripID',
                'filterWidth' => 30,
                'Type' => 'integer',
                'FilterField' => 't.TripID',
            ],
            'DepCode' => [
                'Type' => 'string',
                'FilterField' => 't.DepCode',
                'filterWidth' => 30,
            ],
            'ArrCode' => [
                'Type' => 'string',
                'FilterField' => 't.ArrCode',
                'filterWidth' => 30,
            ],
            'DepDate' => [
                'Caption' => 'DepDate',
                'Type' => 'datetime',
                'FilterField' => 't.DepDate',
            ],
            'ArrDate' => [
                'Caption' => 'ArrDate',
                'Type' => 'datetime',
                'FilterField' => 't.ArrDate',
            ],
            'FlightNumber' => [
                'Type' => 'string',
                'FilterField' => 't.FlightNumber',
                'filterWidth' => 40,
            ],
            'RecordLocator' => [
                'Type' => 'string',
                'FilterField' => 'tt.RecordLocator',
                'filterWidth' => 40,
            ],
            'UserID' => [
                'Type' => 'integer',
                'FilterField' => 'tt.UserID',
            ],
            'ProviderID' => [
                'Type' => 'integer',
                'Options' => $this->getProviderOptions(),
                'FilterField' => 'tt.ProviderID',
            ],
            'CreateDate' => [
                'Type' => 'datetime',
                'FilterField' => 'tt.CreateDate',
            ],
            'ChangeDate' => [
                'Type' => 'datetime',
                'FilterField' => 't.ChangeDate',
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
                t.TripSegmentID,
                t.TripID,
                t.DepCode,
                t.ArrCode,
                t.DepDate,
                t.ArrDate,
                t.FlightNumber,
                t.OperatingAirlineFlightNumber,
                tt.RecordLocator,
                tt.IssuingAirlineConfirmationNumber,
                t.MarketingAirlineConfirmationNumber,
                t.OperatingAirlineConfirmationNumber,
                tt.Category,
                tt.UserID,
                tt.UserAgentID,
                IF(ua.UserAgentID IS NOT NULL, CONCAT(ua.FirstName, ' ', ua.LastName), NULL) AS FamilyMemberName,
                IF(AccountLevel = 3, u.Company, CONCAT(u.FirstName, ' ', u.LastName)) AS UserName,
                tt.ProviderID,
                p.ShortName,
                tt.CreateDate,
                t.ChangeDate,
                t.Sources,
                IF(t.Hidden = 1 OR tt.Hidden = 1, 1, 0) AS Hidden,
                t.Undeleted,
                tt.Cancelled,
                tt.Modified,
                tt.AccountID,
                
                t.AirlineName AS MarketingAirline,
                t.AirlineID AS MarketingAirlineID,
                marketingAir.Code AS MarketingAirlineIATA,
                marketingAir.Name AS MarketingAirlineName,
                
                t.OperatingAirlineName AS OperatingAirline,
                t.OperatingAirlineID AS OperatingAirlineID,
                operatingAir.Code AS OperatingAirlineIATA,
                operatingAir.Name AS OperatingAirlineName,
                   
                tt.AirlineName AS IssuingAirline,
                tt.AirlineID AS IssuingAirlineID,
                issuingAir.Code AS IssuingAirlineIATA,
                issuingAir.Name AS IssuingAirlineName,
                   
                t.PreCheckinNotificationDate,
                t.CheckinNotificationDate,
                t.FlightDepartureNotificationDate,
                t.FlightBoardingNotificationDate,
                
                depGt.TimeZoneLocation AS DepTimeZone,
                arrGt.TimeZoneLocation AS ArrTimeZone
            FROM
                TripSegment t
                JOIN Trip tt ON tt.TripID = t.TripID
                LEFT JOIN Usr u ON u.UserID = tt.UserID
                LEFT JOIN UserAgent ua ON ua.UserAgentID = tt.UserAgentID
                LEFT JOIN Provider p ON p.ProviderID = tt.ProviderID
                LEFT JOIN Airline marketingAir ON marketingAir.AirlineID = t.AirlineID
                LEFT JOIN Airline operatingAir ON operatingAir.AirlineID = t.OperatingAirlineID
                LEFT JOIN Airline issuingAir ON issuingAir.AirlineID = tt.AirlineID
                LEFT JOIN GeoTag depGt ON depGt.GeoTagID = t.DepGeoTagID
                LEFT JOIN GeoTag arrGt ON arrGt.GeoTagID = t.ArrGeoTagID
            WHERE 
                1 = 1
                [Filters]
        ";
    }
}
