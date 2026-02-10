<?
$schema = "OfferUser";
require "../../start.php";
function br($string){
    echo date("Y-m-d H:i:s ").$string.'<br />'."\n";
    flush();
}
drawHeader("SPG offer reports");
/*flush();
$q = new TQuery("select distinct(UserID) as 'u' from Account where Account.ProviderID = 25 and not UserID in (select distinct(UserID) from Account join AccountHistory on Account.AccountID = AccountHistory.AccountID where Account.ProviderID = 25 and Description like '%spg ax%')");
br('Done.');
$i = 0;
$j = 0;
while (!$q->EOF){
    set_time_limit(59);
    $u = $q->Fields['u'];
    $q2 = new TQuery("select Usr.UserID as u, Reservation.HotelName as h, Reservation.CheckIndate as d, SUM(datediff(Reservation.CheckOutdate, Reservation.CheckIndate)) as days, HotelData.MaxPoints as points, HotelData.Category as category
                                  from Reservation
                                     join Account on Reservation.AccountID = Account.AccountID
                                     join Usr on Account.UserID = Usr.UserID
                                     join HotelData on HotelData.HotelName = Reservation.HotelName
                                  where Reservation.Cancelled <> 1
                                    and Reservation.CheckIndate < now()
                                    and Reservation.ProviderID = 25
                                    and HotelData.MaxPoints <= 25000
                                    and HotelData.MaxPoints > 0
                                    and Reservation.CheckOutDate > date_sub(now(), interval 6 month)
                                    and (select count(*)
                                         from Reservation
                                         where Reservation.CheckOutDate > date_sub(now(), interval 6 month)
                                           and Reservation.Cancelled <> 1
                                           and Reservation.AccountID = Account.AccountID
                                        ) > 1
                                    and Usr.UserID = $u
                                  group by Reservation.HotelName
                                  order by days desc limit 1");
    if (!$q2->EOF){
        $users[] = $q2->Fields['u'];
        $j++;
        if ($j % 1000 == 0){
            br("Added $j users.");
            flush();
        }
    }
    $i++;
    $q->next();
    if ($i % 1000 == 0){
        br("Parsed $i users.");
        flush();
    }
}
br("Parsed $i users total.");
br("Added $j users total.");*/
/*br('Report 2: Counting users using AccountHistory who are not in the 1st report.');
$q3 = new TQuery("select distinct(UserID) as 'u' from Account join AccountHistory on Account.AccountID = AccountHistory.AccountID join HotelData on AccountHistory.Description like CONCAT('%', HotelData.HotelName, '%') where Account.ProviderID = 25 and not UserID in (select distinct(UserID) from Account join AccountHistory on Account.AccountID = AccountHistory.AccountID where Account.ProviderID = 25 and Description like '%spg ax%')");
$m = 0;
while (!$q->EOF){
    $q->next();
    $m++;
}
br("$m users before extraction");*/
//ob_end_flush();
br('Setting time limit to 50 seconds...');
set_time_limit(50);
br('Searching for users...');
br('Stage 1: Parsing account history. This can take time, please be patient.');
$q = new TQuery("
select distinct(UserID) as 'u'
from AccountHistory
   join Account on Account.AccountID = AccountHistory.AccountID
where ProviderID = 25
  and not UserID in (
      select distinct(UserID)
      from Account
      join AccountHistory on Account.AccountID = AccountHistory.AccountID
      where ProviderID = 25
        and AccountHistory.Description like '%spg%'
      )
");
br('Stage 2: Parsing each user.');
$su = 0;
$ru = 0;
$resultUsers = array();
while (!$q->EOF){
    set_time_limit(50);
    $toadd = false;
    $u = $q->Fields['u'];
    $q3 = new TQuery("select Usr.UserID as u, Reservation.HotelName as h, Reservation.CheckIndate as d, SUM(datediff(Reservation.CheckOutdate, Reservation.CheckIndate)) as days, HotelData.MaxPoints as points, HotelData.Category as category
                                  from Reservation
                                     join Account on Reservation.AccountID = Account.AccountID
                                     join Usr on Account.UserID = Usr.UserID
                                     join HotelData on HotelData.HotelName = Reservation.HotelName
                                  where Reservation.Cancelled <> 1
                                    and Reservation.CheckIndate < now()
                                    and Reservation.ProviderID = 25
                                    and HotelData.MaxPoints <= 25000
                                    and HotelData.MaxPoints > 0
                                    and Reservation.CheckOutDate > date_sub(now(), interval 1 year)
                                    and Usr.UserID = $u
                                  group by Reservation.HotelName
                                  order by days desc limit 1");
    if (!$q3->EOF){
        $toadd = true;
        $hotelName = $q3->Fields['h'];
        $category = $q3->Fields['category'];
        $points = $q3->Fields['points'];
        $nights = (int) (25000 / $points);
        $points = preg_replace('/(000)$/',',000',$points);
        $params = array(
            "Hotel"		=> $hotelName,
            "Category"	=> "$category",
            "Points"	=> "$points",
            "Nights"	=> "$nights"
            );
    }
    else{
        $q2 = new TQuery("
select AccountHistory.Description, HotelData.HotelName as 'hotelName', HotelData.Category as 'category', HotelData.MaxPoints as 'points', AccountHistory.PostingDate
from Account
   join AccountHistory on Account.AccountID = AccountHistory.AccountID
   join HotelData on AccountHistory.Description like CONCAT('%', HotelData.HotelName, '%')
where Account.ProviderID = 25
  and Account.UserID = $u
  and date_add(AccountHistory.PostingDate, interval 1 year) > now()
  and HotelData.MaxPoints <= 25000
  and HotelData.MaxPoints > 0
order by PostingDate desc
");
        if (!$q2->EOF){
            $toadd = true;
            $hotelName = $q2->Fields['hotelName'];
            $category = $q2->Fields['category'];
            $points = $q2->Fields['points'];
            $nights = (int) (25000 / $points);
            $points = preg_replace('/(000)$/',',000',$points);
            $params = array(
                "Hotel"		=> $hotelName,
                //              "Date" 		=> "$date",
                //				"DateInt"	=> "$unixDate",
                "Category"	=> "$category",
                "Points"	=> "$points",
                "Nights"	=> "$nights",
            );
        }
    }
    if ($toadd){
        $resultUsers[$u] = $params;
        $ru++;
        if ($ru % 1000 == 0)
            br("$ru users added.");
    }
    $q->Next();
    $su++;
    if ($su % 1000 == 0)
        br("$su users parsed.");
}
br("$su starwood users with no SPG card found.");
br("$ru of them have visited SPG hotels.");
drawFooter();
?>
