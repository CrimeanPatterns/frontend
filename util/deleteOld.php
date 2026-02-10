<?php

require __DIR__ . "/../web/kernel/public.php";

require_once __DIR__ . "/../web/trips/common.php";

echo "deleting GeoTag..\n";
$Connection->Execute("delete
	g
from
	GeoTag g
	left outer join TripSegment tsd on tsd.DepGeoTagID = g.GeoTagID
	left outer join TripSegment tsa on tsa.ArrGeoTagID = g.GeoTagID
	left outer join Reservation r on r.GeoTagID = g.GeoTagID
	left outer join Rental lp on lp.PickupGeoTagID = g.GeoTagID
	left outer join Rental ld on ld.DropoffGeoTagID = g.GeoTagID
	left outer join Restaurant e on e.GeoTagID = g.GeoTagID
    left outer join Hotel h on h.GeoTagID = g.GeoTagID
where
	g.UpdateDate < adddate(now(), -45)
	and g.Address <> ''
	and tsd.TripSegmentID is null
	and tsa.TripSegmentID is null
	and r.ReservationID is null
	and lp.RentalID is null
	and ld.RentalID is null
	and e.RestaurantID is null
	and h.HotelID is null");
echo "done, deleted: " . $Connection->GetAffectedRows() . "\n";

echo "deleting old traffic..\n";
$Connection->Execute("delete from AccountTraffic where CreationDate < adddate(now(), interval -1 day)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old oa2 codes and tokens..\n";
$Connection->Execute("delete from OA2Code where Expires < now()");
echo "done, {$Connection->GetAffectedRows()}\n";
$Connection->Execute("delete from OA2Token where Expires < now()");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old mobile log...\n";
$Connection->Execute("delete from MobileLog where AddTime < adddate(now(), interval -1 month)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting expired delta certificates..\n";
$Connection->Execute("delete from SubAccount where Code LIKE '%DeltaCertificates%'  and  ExpirationDate < adddate(now(), -2) ");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old user ips..\n";
$Connection->Execute("delete from UserIP where UpdateDate < adddate(now(), -365) ");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old user ips points..\n";
$Connection->Execute("
    delete uipp
    from UserIPPoint uipp
    left join UserIP uip on uip.UserIPID = uipp.UserIPID
    where uip.UserIPID is null
");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old user last logon points..\n";
$Connection->Execute("
    delete ullp
    from UsrLastLogonPoint ullp
    left join Usr u on u.UserID = ullp.UserID
    where u.UserID is null
");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old email log\n";
$Connection->Execute("delete from EmailLog where EmailDate < adddate(now(), -180)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old extension errors...\n";
$Connection->Execute("delete from ExtensionStat where ErrorDate < adddate(now(), -90)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old FlightInfo records...\n";
$Connection->Execute("delete from FlightInfo where FlightDate < adddate(now(), interval -2 month)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old FlightInfo logs...\n";
$Connection->Execute("delete from FlightInfoLog where CreateDate < adddate(now(), interval -2 month)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old Overlay...\n";
$Connection->Execute("delete from Overlay where ExpirationDate < now()");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old MobileKey...\n";
$Connection->Execute("delete from MobileKey where CreateDate < adddate(now(), -1) and Kind <> " . \AwardWallet\MainBundle\Manager\UserManager::KEY_KIND_JSON);
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old Travel plans...\n";
$Connection->Execute("delete from TravelPlan where EndDate < '" . date("Y-m-d H:i:s", time() - SECONDS_PER_DAY * TRIPS_DELETE_DAYS) . "'");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old expiration dates...\n";
$Connection->Execute("update Account set ExpirationDate = null, ExpirationAutoSet = 0 where ExpirationDate < adddate(now(), interval -1 month)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting extension install...\n";
$Connection->Execute("delete from ExtensionInstall where InstallDate < adddate(now(), -60)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting remember me tokens...\n";
$Connection->Execute("delete from RememberMeToken where LastUsed < adddate(now(), -365)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting coupons...\n";
$Connection->Execute("delete from Coupon where EndDate < adddate(now(), -365)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting emails...\n";
$Connection->Execute("delete from SentEmail where SentDate < adddate(now(), -90)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting fingerprints...\n";
$Connection->Execute("delete from Fingerprint where LastSeen < adddate(now(), -90)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting bloguserreport...\n";
$Connection->Execute("delete from BlogUserReport where InTime < adddate(now(), -365)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "deleting old RAFlightSegment records...\n";
$Connection->Execute("delete from RAFlightSegment where LastParsedDate < adddate(now(), interval -2 year)");
echo "done, {$Connection->GetAffectedRows()}\n";
echo "deleting old RAFlightRouteSearchVolume records...\n";
$Connection->Execute("delete from RAFlightRouteSearchVolume where LastSearch < adddate(now(), interval -2 year)");
echo "done, {$Connection->GetAffectedRows()}\n";

echo "complete\n";
