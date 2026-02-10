<?
require '../kernel/public.php';
require_once '../trips/common.php';
ob_end_flush();
set_time_limit(3600 * 10);
echo "fixing account/user/provider links on travel plans<br>\n";
$nCount = 0;
$nPlanFixes = 0;
$arAffectedUsers = array();
foreach(array("Trip", "Rental", "Reservation", "Restaurant") as $sTable){
    echo "checking {$sTable}s<br>\n";
    $q = new TQuery("select
        t.{$sTable}ID as ID,
        t.UserID as TableUserID,
        t.ProviderID as TableProviderID,
        a.UserID as AccountUserID,
        a.ProviderID as AccountProviderID,
        tp.UserID as PlanUserID
    from $sTable t
    left outer join TravelPlan tp on t.TravelPlanID = tp.TravelPlanID
    , Account a
    where a.AccountID = t.AccountID and
	( a.UserID <> t.UserID or a.ProviderID <> t.ProviderID )");
    while(!$q->EOF){
		if( $q->Fields["AccountUserID"] != $q->Fields["TableUserID"] ){
			echo "fixing {$sTable} {$q->Fields["ID"]} UserID: ({$q->Fields["AccountUserID"]} -> {$q->Fields["TableUserID"]})<br>\n";
			$Connection->Execute("update {$sTable} set UserID = {$q->Fields["AccountUserID"]}
			where {$sTable}ID = {$q->Fields["ID"]}");
		}
		if( $q->Fields["AccountProviderID"] != $q->Fields["TableProviderID"] ){
			echo "fixing {$sTable} {$q->Fields["ID"]} ProviderID: ({$q->Fields["AccountProviderID"]} -> {$q->Fields["TableProviderID"]})<br>\n";
			$Connection->Execute("update {$sTable} set ProviderID = {$q->Fields["AccountProviderID"]}
			where {$sTable}ID = {$q->Fields["ID"]}");
		}
        $nCount++;
        $q->Next();
    }
}
echo "total fixed records, by account: $nCount<br>\n";
$nCount = 0;
foreach(array("Trip", "Rental", "Reservation", "Restaurant") as $sTable){
    echo "checking {$sTable}s<br>\n";
    $q = new TQuery("select
        t.{$sTable}ID as ID,
        t.UserID as TableUserID,
        tp.UserID as PlanUserID
    from $sTable t
    join TravelPlan tp on t.TravelPlanID = tp.TravelPlanID
    where
    t.AccountID is null
	and ( tp.UserID <> t.UserID )");
    while(!$q->EOF){
		if( $q->Fields["TableUserID"] != $q->Fields["PlanUserID"] ){
			echo "fixing {$sTable} {$q->Fields["ID"]} UserID: ({$q->Fields["TableUserID"]} -> {$q->Fields["PlanUserID"]})<br>\n";
			$Connection->Execute("update {$sTable} set UserID = {$q->Fields["PlanUserID"]}
			where {$sTable}ID = {$q->Fields["ID"]}");
		}
        $nCount++;
        $q->Next();
    }
}
echo "total fixed records, by plan: $nCount<br>\n";
?>
