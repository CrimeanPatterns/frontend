<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class SqlQuery
{
    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @return [['UserID' => 7, 'Email' => 'siteadmin@awardwallet.com', 'Cards' => [<Constants::CARD_ID_HYATT>, <Constants::CARD_ID_MARRIOTT>], 'Matches' => [['Criteria' => 'hyatt', 'ID' => 'R.123.Hotel']], [...
     */
    public function getUsers(?int $userId = null)
    {
        $this->logger->info("loading users with fresh itineraries");
        $unitedAirlineId = $this->connection->executeQuery("select AirlineID from Airline where FSCode = 'UA'")->fetchColumn();
        $unitedProviderId = $this->connection->executeQuery("select ProviderID from Provider where Code = 'mileageplus'")->fetchColumn();
        $hyattProviderId = $this->connection->executeQuery("select ProviderID from Provider where Code = 'goldpassport'")->fetchColumn();
        $marriottProviderId = $this->connection->executeQuery("select ProviderID from Provider where Code = 'marriott'")->fetchColumn();
        $unitedLevelPropertyId = $this->connection->executeQuery("select ProviderPropertyID from ProviderProperty where ProviderID = $unitedProviderId and Kind = " . PROPERTY_KIND_STATUS)->fetchColumn();

        $userFilter = null;

        if ($userId !== null) {
            $userFilter = "and UserID = $userId";
        }

        $rows = $this->connection->executeQuery("
        select 
            Users.UserID,
            Usr.Email,
            Users.Cards,
            Users.Criteria,
            Users.ID
        
        from 
            (
                select distinct
                    UserID,
                    '" . Constants::CARD_ID_UNITED_EXPLORER . "' as Cards,
                    'united' as Criteria,
                    concat('T.', max(TripID), '.ArrCity') as ID
                from 
                    (
                        select
                            Trip.UserID,
                            Trip.TripID
                        from
                            Trip
                            join TripSegment on Trip.TripID = TripSegment.TripID
                            left outer join Airline on Airline.AirlineID = coalesce(TripSegment.OperatingAirlineID, TripSegment.AirlineID, Trip.AirlineID)
                            left outer join AirClassDictionary CommonClass on coalesce(TripSegment.CabinClass, Trip.CabinClass) = CommonClass.Source and CommonClass.AirlineCode is null
                            left outer join AirClassDictionary AirlineClass on coalesce(TripSegment.CabinClass, Trip.CabinClass) = AirlineClass.Source and AirlineClass.AirlineCode = Airline.Code
                        where 
                            Trip.CreateDate <= adddate(now(), -3) and Trip.CreateDate > adddate(now(), -30)
                            and Trip.Hidden = 0 and TripSegment.Hidden = 0
                            and TripSegment.DepDate > now()
                            and Trip.AirlineID = :UnitedAirlineID
                            and (TripSegment.OperatingAirlineID = :UnitedAirlineID or TripSegment.OperatingAirlineID is null)
                            and not coalesce(AirlineClass.Target, CommonClass.Target, 'Unknown') in ('Business', 'First', 'Unknown')
                            and (
                                select
                                    group_concat(distinct da.CountryCode) as Countries
                                from
                                    Trip t
                                    join TripSegment ts on t.TripID = ts.TripID
                                    join AirCode da on ts.DepCode = da.AirCode
                                where
                                    t.TripID = Trip.TripID
                            ) = 'US'
                            and (
                                select
                                    group_concat(distinct aa.CountryCode) as Countries
                                from
                                    Trip t
                                    join TripSegment ts on t.TripID = ts.TripID
                                    join AirCode aa on ts.ArrCode = aa.AirCode
                                where
                                    t.TripID = Trip.TripID
                            ) = 'US'
                            and Trip.UserID not in (
                                select 
                                    a.UserID
                                from
                                    Account a
                                    join AccountProperty ap on a.AccountID = ap.AccountID
                                    join TextEliteLevel tel on ap.Val = tel.ValueText
                                    join EliteLevel el on tel.EliteLevelID = el.EliteLevelID
                                where
                                    a.ProviderID = :UnitedProviderID
                                    and ap.ProviderPropertyID = :UnitedLevelPropertyID
                                    and el.Rank > 0
                            )
                            $userFilter
                        group by 
                            Trip.UserID,
                            Trip.TripID
                    ) DomesticTrip
                group by 
                    UserID,
                    Cards,
                    Criteria
                
                union 
                
                select distinct 
                    UserID,
                    '" . Constants::CARD_ID_HYATT . "' as Cards,
                    'hyatt' as Criteria,
                    concat('R.', max(ReservationID), '.City') as ID
                from 
                    Reservation
                where
                    CreateDate <= adddate(now(), -3) and CreateDate > adddate(now(), -30)
                    and CheckInDate > now()
                    and Hidden = 0
                    and ProviderID = :HyattProviderID             
                    $userFilter
                group by 
                    UserID,
                    Cards,
                    Criteria
                
                union 
                
                select distinct 
                    UserID,
                    '" . Constants::CARD_ID_MARRIOTT . "' as Cards,
                    'marriott' as Criteria,
                    concat('R.', max(ReservationID), '.City') as ID
                from 
                    Reservation
                where
                    CreateDate <= adddate(now(), -3) and CreateDate > adddate(now(), -30)
                    and CheckInDate > now()
                    and Hidden = 0
                    and ProviderID = :MarriottProviderID
                    $userFilter
                group by 
                    UserID,
                    Cards,
                    Criteria
                
                union
                
                select distinct
                    UserID,
                    '" . Constants::CARD_ID_CSP . "|" . Constants::CARD_ID_CFU . "' as Cards,
                    'economy_air' as Criteria,
                    concat('T.', max(Trip.TripID), '.ArrCity') as ID
                from 
                    Trip
                    join TripSegment on Trip.TripID = TripSegment.TripID
                    left outer join Airline on Airline.AirlineID = coalesce(TripSegment.OperatingAirlineID, TripSegment.AirlineID, Trip.AirlineID)
                    left outer join AirClassDictionary CommonClass on coalesce(TripSegment.CabinClass, Trip.CabinClass) = CommonClass.Source and CommonClass.AirlineCode is null
                    left outer join AirClassDictionary AirlineClass on coalesce(TripSegment.CabinClass, Trip.CabinClass) = AirlineClass.Source and AirlineClass.AirlineCode = Airline.Code
                where 
                    CreateDate <= adddate(now(), -3) and CreateDate > adddate(now(), -30)
                    and Trip.Hidden = 0 and TripSegment.Hidden = 0
                    and TripSegment.DepDate > now()
                    and Trip.Category = :AirCategory
                    and Trip.SpentAwards is null
                    and coalesce(AirlineClass.Target, CommonClass.Target) in ('Basic Economy', 'Economy Plus', 'Economy', 'Premium Economy')
                    $userFilter
                group by 
                    UserID,
                    Cards,
                    Criteria
                
                union
                
                select distinct
                    UserID,
                    '" . Constants::CARD_ID_CSP . "|" . Constants::CARD_ID_CFU . "' as Cards,
                    'us_business_or_first' as Criteria,
                    concat('T.', max(Trip.TripID), '.ArrCity') as ID
                from 
                    Trip
                    join TripSegment on Trip.TripID = TripSegment.TripID
                    join AirCode DepAirCode on TripSegment.DepCode = DepAirCode.AirCode 
                    join AirCode ArrAirCode on TripSegment.ArrCode = ArrAirCode.AirCode 
                    left outer join Airline on Airline.AirlineID = coalesce(TripSegment.OperatingAirlineID, TripSegment.AirlineID, Trip.AirlineID)
                    left outer join AirClassDictionary CommonClass on coalesce(TripSegment.CabinClass, Trip.CabinClass) = CommonClass.Source and CommonClass.AirlineCode is null
                    left outer join AirClassDictionary AirlineClass on coalesce(TripSegment.CabinClass, Trip.CabinClass) = AirlineClass.Source and AirlineClass.AirlineCode = Airline.Code
                    left outer join TripSegment NonUsSegment on TripSegment.TripID = NonUsSegment.TripID and TripSegment.TripSegmentID <> NonUsSegment.TripSegmentID  
                    left outer join AirCode NonUsDepAirCode on TripSegment.DepCode = NonUsDepAirCode.AirCode and NonUsDepAirCode.CountryCode not in ('US', 'CA') 
                    left outer join AirCode NonUsArrAirCode on TripSegment.ArrCode = NonUsArrAirCode.AirCode and NonUsArrAirCode.CountryCode not in ('US', 'CA') 
                where 
                    CreateDate <= adddate(now(), -3) and CreateDate > adddate(now(), -30)
                    and Trip.Hidden = 0 and TripSegment.Hidden = 0
                    and TripSegment.DepDate > now()
                    and Trip.Category = :AirCategory
                    and Trip.SpentAwards is null
                    and coalesce(AirlineClass.Target, CommonClass.Target) in ('Business', 'First')
                    and DepAirCode.CountryCode in ('US', 'CA')
                    and ArrAirCode.CountryCode in ('US', 'CA')
                    $userFilter
                group by 
                    UserID,
                    Cards,
                    Criteria
                
            ) Users
            
            left outer join EmailLog LastEmail on LastEmail.UserID = Users.UserID and LastEmail.EmailDate >= adddate(now(), -5) and LastEmail.MessageKind = :MessageKind
            
            left outer join EmailLog LastCodeEmail on LastCodeEmail.UserID = Users.UserID and LastCodeEmail.EmailDate >= adddate(now(), -90) and LastCodeEmail.MessageKind = :MessageKind and LastCodeEmail.Code regexp concat('^', Users.Cards, '$')
            
            left outer join (
                select 
                    UserID,
                    count(EmailLogID) as Emails
                from 
                    EmailLog
                where
                    EmailDate >= adddate(now(), -30) and MessageKind = :MessageKind
                group by 
                    UserID
            ) MonthEmail on MonthEmail.UserID = Users.UserID
        
            join Usr on Users.UserID = Usr.UserID
        
            left outer join awardwallet.DoNotSend on Usr.Email = DoNotSend.Email
        
        where 
            LastEmail.EmailLogID is null
            and LastCodeEmail.EmailLogID is null
            and (MonthEmail.Emails is null or MonthEmail.Emails < 2)
            and Usr.CountryID = :UsaCountryID
            and DoNotSend.Email is null
            and Usr.EmailOffers = 1
        ",
            [
                "MessageKind" => EmailLog::MESSAGE_KIND_CHASE,
                "UsaCountryID" => Country::UNITED_STATES,
                "UnitedProviderID" => $unitedProviderId,
                "UnitedLevelPropertyID" => $unitedLevelPropertyId,
                "HyattProviderID" => $hyattProviderId,
                "MarriottProviderID" => $marriottProviderId,
                "UnitedAirlineID" => $unitedAirlineId,
                "AirCategory" => Trip::CATEGORY_AIR,
            ]
        )->fetchAll(FetchMode::ASSOCIATIVE);

        $result = [];

        foreach ($rows as $row) {
            $row['UserID'] = (int) $row['UserID'];

            if (!isset($result[$row['UserID']])) {
                $result[$row['UserID']] = [
                    'UserID' => $row['UserID'],
                    'Email' => $row['Email'],
                    'Cards' => [],
                    'Matches' => [],
                ];
            }
            $row['Cards'] = array_map('intval', explode('|', $row['Cards']));
            $result[$row['UserID']]['Cards'] = array_unique(array_merge($result[$row['UserID']]['Cards'], $row['Cards']));
            $result[$row['UserID']]['Matches'][] = [
                'Cards' => $row['Cards'],
                'Criteria' => $row['Criteria'],
                'ID' => $row['ID'],
            ];
        }

        $this->logger->info("loaded " . count($result) . " users with possible emails");

        return $result;
    }
}
