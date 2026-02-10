<?
require '../kernel/public.php';
require_once '../trips/common.php';
ob_end_flush();
set_time_limit(3600 * 10);
echo "trying to fix missing geo tags<br>\n";
$nCount = 0;
$nFixes = 0;
$arAffectedUsers = array();
$_GET['ResetGeoTag'] = '1';
$_GET['DebugGeoTag'] = '1';
echo "fixing only tags updated in last 2 weeks<br>\n";
$q = new TQuery("select * from GeoTag where UpdateDate >= ".$Connection->DateTimeToSQL(time() - SECONDS_PER_DAY*14)." and Lat is null order by UpdateDate limit 3");
while(!$q->EOF){
    $sAddress = NormalizeAddress($q->Fields["Address"]);
    if($sAddress != $q->Fields["Address"]){
        echo "not normalized address {$q->Fields["Address"]}, removing<br>\n";
        $nFixes++;
        $Connection->Execute("delete from GeoTag where GeoTagID = {$q->Fields["GeoTagID"]}");
    }
    $q->Fields = FindGeoTag($q->Fields["Address"]);
    if($q->Fields["Lat"] != ""){
        $nFixes++;
        $qUser = new TQuery("select distinct UserID from Reservation
            where GeoTagID = {$q->Fields["GeoTagID"]}
        union
            select UserID from Rental where PickupGeoTagID = {$q->Fields["GeoTagID"]}
            or DropoffGeoTagID = {$q->Fields["GeoTagID"]}
        union
            select UserID from Restaurant where GeoTagID = {$q->Fields["GeoTagID"]}
        union
            select UserID from Direction where StartGeoTagID = {$q->Fields["GeoTagID"]}
            or EndGeoTagID = {$q->Fields["GeoTagID"]}
        ");
        while(!$qUser->EOF){
            if(($qUser->Fields["UserID"] != "") && !in_array($qUser->Fields["UserID"], $arAffectedUsers))
                $arAffectedUsers[] = $qUser->Fields["UserID"];
            $qUser->Next();
        }
    }
    $q->Next();
    $nCount++;
}
echo "done updating, fixes: $nFixes, affected users: ".count($arAffectedUsers)."<br>\n";
unset($_GET['ResetGeoTag']);
unset($_GET['DebugGeoTag']);
echo "now updating user plans<br>\n";
echo "complete, fixes: $nFixes, affected users: ".count($arAffectedUsers)."<br>\n";

?>
