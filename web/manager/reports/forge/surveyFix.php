<?
// When survey offers were launched the prod wasn't updated as necessary,
// so users who saw the offer didn't automatically refuse to see offers
// of it's kind. This fix makes all users who saw survey refuse to see
// this kind.

$schema = "OfferUser";
require "../../start.php";
$sTitle = "Survey offer reports";
Echo "Stage 1: Making all users who saw the offer decline it...<br />";
flush();
set_time_limit(59);
$Connection->Execute("
update OfferUser set Agreed = 0
where OfferID in (4,5,6,7) and ShowsCount > 0
");
Echo "Stage 2: Making all users who saw the offer decline the kind...<br />";
flush();
set_time_limit(59);
$q = new TQuery("
select distinct(UserID) from OfferUser
where OfferID in (4,5,6,7) and ShowsCount > 0
");
Echo "Executing...<br />";
flush();
set_time_limit(59);
$u = 0;
while (!$q->EOF){
    $uid = $q->Fields['UserID'];
    $Connection->Execute("
    insert into OfferKindRefused (UserID, OfferKind)
    values ($uid, 2)
    on duplicate key update OfferKindRefusedID = OfferKindRefusedID
    ");
    $q->Next();
    $u++;
    if ($u % 1000 == 0){
        echo "$u users persed so far...<br />";
        flush();
        set_time_limit(59);
    }
}
Echo "$u users refused the kind<br />";
flush();
?>