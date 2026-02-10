<?
$schema = "Offer";
require "../start.php";
drawHeader("Offers statistics");
$offer = -1;
$year = (new TQuery("select year(now()) as year"))->Fields['year'];
$month = (new TQuery("select month(now()) as m"))->Fields['m'];
$months = array("1"=>"January",
                "2"=>"February",
                "3"=>"March",
                "4"=>"April",
                "5"=>"May",
                "6"=>"June",
                "7"=>"July",
                "8"=>"August",
                "9"=>"September",
                "10"=>"October",
                "11"=>"November",
                "12"=>"December"
);
//get offer options
if(!isset($_GET['offer'])){
    print "<h3>Page Not Found</h3>";
    drawFooter();
    return;
}

$offer = $goffer = intval($_GET['offer']);

$q0 = new TQuery("select OfferID as offerID, Code as code from Offer order by OfferID desc");
while (!$q0->EOF){
    $options[$q0->Fields['offerID']] = $q0->Fields['code'];
    $q0->Next();
}

/*
if (isset($_GET['offer']) && isset($_GET['date'])){
    $offer = intval($_GET['offer']);
    $date = addslashes($_GET['date']);

    //get offer code
    $qc = $Connection->Execute("select Code as code from Offer where OfferID = $offer");
    $rowc = mysql_fetch_assoc($qc);
    $code = $rowc['code'];

    //get offer day stats
    $qd = $Connection->Execute("
select COUNT(CASE WHEN Agreed=1 THEN 1 ELSE NULL END) as agreed,
       COUNT(CASE WHEN Agreed=0 THEN 1 ELSE NULL END) as refused,
       COUNT(ShowDate) as shows
from OfferUser
where OfferID = $offer
  and ShowDate is not null
  and DATE(ShowDate) = STR_TO_DATE('$date','%m/%d/%Y')
    ");
    $rowd = mysql_fetch_assoc($qd);
    print "<b>$code</b> statistics for <b>$date</b><br/>";
    print "Agreed = ".$rowd['agreed']." <br />";
    print "Refused = ".$rowd['refused']." <br />";
    print "Shows > ".$rowd['shows']." minimum<br />";
}

?>
<script language="javascript" type="text/javascript" src="/lib/3dParty/jquery/plugins/ui/jquery.ui.datepicker.js"></script>
<style>
#ui-datepicker-div
{
    display: none;
}
</style>
<h2>Offers statistics</h2><br>
<form method=\"get\">
    <table>
        <tr>
            <td>Choose an offer</td>
            <td width = 16></td>
            <td>Choose a date</td>
        </tr>
        <tr>
            <td><select name="offer" style = "display: table-cell; width: 100%">
<?
foreach ($options as $k => $v){
    print "<option value=\"$k\">$v</option>";
}
?>
            </select></td>
            <td width = 16></td>
            <td><input type="text" id="datepicker" name="date"></td>
        </tr>
        <tr>
            <td colspan = 3><input type="Submit" value="Display"></td>
        </tr>
    </table>
</form><br />
<?
*/
print "<h2>Offers statistics</h2><br/ >";
if (isset($_GET['offer']) && isset($_GET['month']) && isset($_GET['year'])){
    $year = $gyear = intval($_GET['year']);
    $month = $gmonth = intval($_GET['month']);

    //get offer code
    $qc = new TQuery("select Code as code from Offer where OfferID = $goffer");
    $code = $qc->Fields['code'];
    print "<b>$code</b> statistics for <b>$months[$gmonth]</b><br />";
    print "Total users agreed in <b>".$months[$gmonth]."</b>: ".(new TQuery("
            select count(distinct(UserID)) as Agrees from OfferLog
            where OfferID = $goffer
            and Action = 1
            and ActionDate is not null
            and month(ActionDate) = $gmonth
            and year(ActionDate) = $gyear
        "))->Fields["Agrees"];
    print "<table border = 1><tr><td>Day</td><td>Agreed</td><td>Refused</td><td>Shows minimum</td><td>A/Sm Ratio</td></tr>";
    for ($i = 1; $i < 32; $i++){
        /* $qd = new TQuery("
            select COUNT(CASE WHEN Agreed=1 THEN 1 ELSE NULL END) as agreed,
                   COUNT(CASE WHEN Agreed=0 THEN 1 ELSE NULL END) as refused,
                   COUNT(ShowDate) as shows
            from OfferUser
            where OfferID = $goffer
              and ShowDate is not null
              and month(ShowDate) = $gmonth
              and year(ShowDate) = $gyear
              and day(ShowDate) = $i
        ");
        $rd = $qd->Fields; */

        $shows = (new TQuery("
            select count(*) as Shows from OfferLog
            where OfferID = $goffer
            and Action is null
            and ActionDate is not null
            and month(ActionDate) = $gmonth
            and year(ActionDate) = $gyear
            and day(ActionDate) = $i
        "))->Fields["Shows"];

        $agrees = (new TQuery("
            select count(*) as Agrees from OfferLog
            where OfferID = $goffer
            and Action = 1
            and ActionDate is not null
            and month(ActionDate) = $gmonth
            and year(ActionDate) = $gyear
            and day(ActionDate) = $i
        "))->Fields["Agrees"];

        $declines = (new TQuery("
            select count(*) as Declines from OfferLog
            where OfferID = $goffer
            and Action = 0
            and ActionDate is not null
            and month(ActionDate) = $gmonth
            and year(ActionDate) = $gyear
            and day(ActionDate) = $i
        "))->Fields["Declines"];

        if ($shows > 0){
            $ratio = $shows > 0 ? number_format( $agrees / $shows, 2) : "N/A";
            print "<tr><td>$i</td><td>".$agrees."</td><td>".$declines."</td><td>".$shows."</td><td>".$ratio."</td></tr>";
        }
    }
    print "</table>";
}

?>
<form method=\"get\">
    <table>
        <tr>
            <td>Choose an offer</td>
            <td width = 16></td>
            <td>Choose a month</td>
            <td width = 16></td>
            <td>Choose a year</td>
        </tr>
        <tr>
            <td><select name="offer" style = "display: table-cell; width: 100%">
                <?
                foreach ($options as $k => $v){
                    if ($offer == $k)
                        print "<option value=\"$k\" selected>$v</option>";
                    else
                        print "<option value=\"$k\">$v</option>";
                }
                ?>
            </select></td>
            <td width = 16></td>
            <td><select name="month" style = "display: table-cell; width: 100%">
                <?
                foreach ($months as $k => $v){
                    if ($month == $k)
                        print "<option value=\"$k\" selected>$v</option>";
                    else
                        print "<option value=\"$k\">$v</option>";
                }
                ?>
            </select></td>
            <td width = 16></td>
            <td><input name="year" size = 4 maxlength = 4 style = "display: table-cell; width: 100%" value = <?=$year?>>
        </tr>
        <tr>
            <td colspan = 5><input type="SUBMIT" value="Display"></td>
        </tr>
    </table>
<br />
<?
//get offer overall stats
$lastusers = (new TQuery("
select count(*) as c
from Usr
where LastDesktopLogon is not null
  and date_add(LastDesktopLogon, interval 24 hour) > now()
  and LogonCount > 1
  and date_add(CreationDateTime, interval 24 hour) < now()
"))->Fields['c'];
$q = new TQuery("
select Offer.OfferID as offerID, Code as code, Offer.ShowsCount as sc, COUNT(distinct(UserID)) as users,
  COUNT(ShowDate) as seen, COUNT(CASE WHEN Agreed=1 THEN 1 ELSE NULL END) as agreed,
  COUNT(CASE WHEN Agreed=0 THEN 1 ELSE NULL END) as refused
from Offer
  join OfferUser on Offer.OfferID = OfferUser.OfferID and Offer.OfferID = {$goffer}
group by Offer.OfferID
order by offerID desc
");
print "<strong>Overall statistics</strong><br />";
print "<strong>$lastusers</strong> users have logged in during past <strong>24 hours</strong>";
$i=0;
print "
    <table border = 1><tr>
        <td><b>ID</b></td>
        <td><b>Code</b></td>
        <td><b>Users</b></td>
        <td><b>Have seen (total)</b></td>
        <td><b>Have seen (24h)</b></td>
        <td><b>Have not seen</b></td>
        <td><b>Total shows</b></td>
        <td><b>Agreed (total)</b></td>
        <td><b>Agreed (24h)</b></td>
        <td><b>Refused (total)</b></td>
        <td><b>Refused (24h)</b></td>
        </tr>";
while(!$q->EOF){
    $row = $q->Fields;
    $i++;
    $id = intval($row['offerID']);
    $agreed1 = (new TQuery("
        select count(distinct(UserID)) as Agreed from OfferLog
        where OfferID = $id and Action = 1 and not UserID in (
            select UserID from OfferUser where OfferID = $id and Agreed = 1
        )
    "))->Fields['Agreed'];
    $agreed2 = (new TQuery("
        select count(distinct(UserID)) as Agreed from OfferUser
        where OfferID = $id and Agreed = 1
    "))->Fields['Agreed'];
    $agreed = $agreed1 + $agreed2;
    $shows1 = (new TQuery("
        select count(distinct(UserID)) as Shows from OfferLog
        where OfferID = $id and Action is null and not UserID in (
            select UserID from OfferUser where OfferID = $id and ShowDate is not null
        )
    "))->Fields['Shows'];
    $shows2 = (new TQuery("
        select count(distinct(UserID)) as Shows from OfferUser
        where OfferID = $id and ShowDate is not null
    "))->Fields['Shows'];
    $seentotal = $shows1 + $shows2;
    $row2 = (new TQuery("
    select count(*) as seenToday
    from OfferUser
    where OfferID = $id and ShowDate is not null and DATE_ADD(ShowDate, interval 1 day) > now()
    "))->Fields;
    $row3 = (new TQuery("
    select count(*) as agreedToday
    from OfferUser
    where OfferID = $id and Agreed = 1 and DATE_ADD(ShowDate, interval 1 day) > now()
    "))->Fields;
    $row4 = (new TQuery("
    select count(*) as refusedToday
    from OfferUser
    where OfferID = $id and Agreed = 0 and DATE_ADD(ShowDate, interval 1 day) > now()
    "))->Fields;
    print "<tr><td>"
        .$row['offerID']."</td><td>"
        .$row['code']."</td><td>"
        .$row['users']."</td><td>"
        .$seentotal."</td><td>"
        .$row2['seenToday']."</td><td>"
        .($row['users']-$row['seen'])."</td><td>"
        .$row['sc']."</td><td>"
        .$agreed."</td><td>"
        .$row3['agreedToday']."</td><td>"
        .$row['refused']."</td><td>"
        .$row4['refusedToday']."</td></tr>";
    $q->Next();
}
print "</table>";
print '<script> $(function() {$( "#datepicker" ).datepicker();});</script>';
/* print "<br /><br />";
print "<a href = \"javascript:history.back()\">Back</a>"; */
drawFooter();
?>
