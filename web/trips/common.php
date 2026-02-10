<?php

function TripsSQL( $arWhere = array() ){
	$arWhere[] = "t.TripID = ts.TripID";
	$s = "select t.TripID as ID, 'T' as Kind, t.Category, coalesce(p.Name, tp.Name) as ProviderName, t.TravelPlanID, t.Cancelled, 
	min(ts.DepDate) as StartDate, max(ts.ArrDate) as EndDate, t.Hidden, t.AccountID, t.Parsed,
	coalesce(a.ProviderID, t.ProviderID) as ProviderID,
	coalesce(a.UserID, t.UserID) as UserID,
	t.UserAgentID, t.Moved, t.Modified, t.RecordLocator as ConfirmationNumber, IF(Direction = 1, 25, 10) as SortIndex,
	t.PlanIndex, t.Hash, concat('T', coalesce(t.RecordLocator, t.Hash), '-', t.Direction) as UniqueNumber
	from Trip t
	left outer join Account a on a.AccountID = t.AccountID
	left outer join Provider p on a.ProviderID = p.ProviderID
	left outer join Provider tp on t.ProviderID = tp.ProviderID,
	TripSegment ts
	".(count($arWhere) > 0?"where ".implode(" and ", $arWhere):"")."
	group by ID, Kind, Category, ProviderName, TravelPlanID, UserID, t.Moved, t.Modified, t.RecordLocator, t.Cancelled, t.Hidden, t.AccountID, t.Parsed, ProviderID, t.UserAgentID, t.Direction, t.PlanIndex, t.Hash";
	$s = str_ireplace('[StartDate]', 'ts.DepDate', $s);
	$s = str_ireplace('[EndDate]', 'ts.ArrDate', $s);
	return $s;
}

function RentalsSQL( $arWhere = array() ){
	$s = "select t.RentalID as ID, 'L' as Kind, 0 as Category, coalesce(p.Name, tp.Name) as ProviderName, t.TravelPlanID, t.Cancelled,
	t.PickupDatetime as StartDate, t.DropoffDatetime as EndDate, t.Hidden, t.AccountID, t.Parsed,
	coalesce(a.ProviderID, t.ProviderID) as ProviderID,
	coalesce(a.UserID, t.UserID) as UserID,
	t.UserAgentID, t.Moved, t.Modified, t.Number as ConfirmationNumber, 20 as SortIndex, t.PlanIndex, null as Hash,
	concat('L', t.Number) as UniqueNumber
	from Rental t
	left outer join Account a on t.AccountID = a.AccountID
	left outer join Provider p on a.ProviderID = p.ProviderID
	left outer join Provider tp on t.ProviderID = tp.ProviderID
	".(count($arWhere) > 0?"where ".implode(" and ", $arWhere):"");
	$s = str_ireplace('[StartDate]', 't.PickupDatetime', $s);
	$s = str_ireplace('[EndDate]', 't.DropoffDatetime', $s);
	return $s;
}

function ReservationsSQL( $arWhere = array() ){
	$s = "select t.ReservationID as ID, 'R' as Kind, 0 as Category, coalesce(p.Name, tp.Name) as ProviderName, t.TravelPlanID, t.Cancelled,
	t.CheckInDate as StartDate, t.CheckOutDate as EndDate, t.Hidden, t.AccountID, t.Parsed,
	coalesce(a.ProviderID, t.ProviderID) as ProviderID,
	coalesce(a.UserID, t.UserID) as UserID,
	t.UserAgentID, t.Moved, t.Modified, t.ConfirmationNumber, 40 as SortIndex, t.PlanIndex, null as Hash,
	concat('R', t.ConfirmationNumber) as UniqueNumber
	from Reservation t
	left outer join Account a on t.AccountID = a.AccountID
	left outer join Provider p on a.ProviderID = p.ProviderID
	left outer join Provider tp on t.ProviderID = tp.ProviderID
	".(count($arWhere) > 0?"where ".implode(" and ", $arWhere):"");
	$s = str_ireplace('[StartDate]', 't.CheckInDate', $s);
	$s = str_ireplace('[EndDate]', 't.CheckOutDate', $s);
	return $s;
}

function RestaurantsSQL( $arWhere = array() ){
    getSymfonyContainer()->get("logger")->warning("RestaurantsSQL");
	$s = "select t.RestaurantID as ID, 'E' as Kind, 0 as Category, p.Name as ProviderName, t.TravelPlanID, 0 AS Cancelled,
	t.StartDate as StartDate, t.EndDate as EndDate, t.Hidden, t.AccountID, t.Parsed,
	coalesce(a.ProviderID, t.ProviderID) as ProviderID,
	coalesce(a.UserID, t.UserID) as UserID,
	t.UserAgentID, t.Moved, t.Modified, t.ConfNo as ConfirmationNumber, 50 as SortIndex, t.PlanIndex, null as Hash,
	concat('E', t.ConfNo) as UniqueNumber
	from Restaurant t
	left outer join Account a on t.AccountID = a.AccountID
	left outer join Provider p on a.ProviderID = p.ProviderID
	".(count($arWhere) > 0?"where ".implode(" and ", $arWhere):"");
	$s = str_ireplace('[StartDate]', 't.StartDate', $s);
	$s = str_ireplace('[EndDate]', 't.EndDate', $s);
	return $s;
}

function DirectionsSQL( $arWhere = array() ){
	$s = "select t.DirectionID as ID, 'D' as Kind, 0 as Category, null as ProviderName, t.TravelPlanID, 0 AS Cancelled,
	t.StartDate as StartDate, t.StartDate as EndDate, t.Hidden, null as AccountID, null as Parsed,
	null as ProviderID,
	t.UserID as UserID,
	t.UserAgentID, t.Moved, t.Modified, Null as ConfirmationNumber,
	case
		when ToKind = 'T' then 5
		when FromKind = 'T' then 15
		when FromKind = 'L' then 25
		when FromKind = 'R' and ToKind <> 'R' then 45
		when FromKind = 'E' and ToKind <> 'E'  then 55
		else 30
	end as SortIndex, t.PlanIndex, null as Hash,
	concat('D', t.DirectionID) as UniqueNumber
	from Direction t
	left outer join Account a on t.AccountID = a.AccountID
	".(count($arWhere) > 0?"where ".implode(" and ", $arWhere):"");
	$s = str_ireplace('[StartDate]', 't.StartDate', $s);
	$s = str_ireplace('[EndDate]', 't.StartDate', $s);
	return $s;
}

function ItenarariesSQL($arWhere = array()){
	return TripsSQL($arWhere)
	."\nunion\n".RentalsSQL($arWhere)
	."\nunion\n".ReservationsSQL($arWhere)
	."\nunion\n".DirectionsSQL($arWhere)
	."\nunion\n".RestaurantsSQL($arWhere)
	." order by Date(StartDate), SortIndex, StartDate";
}

