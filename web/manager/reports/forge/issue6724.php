<?
// #6724
function doLog($s){
    echo date("Y-m-d H:i:s ").$s."\n";
        echo "<br />";
    flush();
}
$schema = "Offer";
require "../../start.php";
$sTitle = "Users who flew to Hong Kong";
doLog("Selecting users...");
$users = array();
$q = new TQuery("
select distinct uid
from
    (select t.UserID as uid, ts.TripID, ts.TripSegmentID, ts.ArrDate
    from TripSegment ts
    join Trip t on ts.TripID = t.TripID
    join Account a on a.AccountID = t.AccountID
    where ArrCode = 'HKG'
    group by ts.TripID
    having ts.ArrDate =
        (select max(tts.ArrDate) from TripSegment tts where tts.TripID = ts.TripID)) x");
while (!$q->EOF){
    $users[] = $q->Fields['uid'];
    $q->Next();
}
doLog(count($users));
echo "<pre>";
print_r($users);
echo "</pre>";
?>