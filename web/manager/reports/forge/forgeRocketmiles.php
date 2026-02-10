<?
$schema = "OfferUser";
require "../../start.php";
drawHeader("Rocketmiles forge");
echo "<h2>Rocketmiles forge</h2><br />";
echo "Executing initial query...<br />";
flush();
/*$sql = "
select UserID, DepDate, TripSegmentID
from TripSegment ts join Trip t on ts.TripID = t.TripID
where
    (
    ArrName like '%Atlanta%' or
    ArrName like '%Boston%' or
    ArrName like '%Charlotte%' or
    ArrName like '%Chicago%' or
    ArrName like '%Dallas%' or
    ArrName like '%Denver%' or
    ArrName like '%Honolulu%' or
    ArrName like '%Las Vegas%' or
    ArrName like '%Los Angeles%' or
    ArrName like '%New York%' or
    ArrName like '%Philadelphia%' or
    ArrName like '%Phoenix%' or
    ArrName like '%San Diego%' or
    ArrName like '%San Francisco%' or
    ArrName like '%Washington D.C.%'
    )
and DepDate > now()
and t.Hidden = 0
group by UserID
having DepDate =
    (
    select min(TripSegment.DepDate) from TripSegment join Trip on Trip.TripID = TripSegment.TripID
    where Trip.UserID = t.UserID and DepDate > now() and Hidden = 0 and
        (
        ArrName like '%Atlanta%' or
        ArrName like '%Boston%' or
        ArrName like '%Charlotte%' or
        ArrName like '%Chicago%' or
        ArrName like '%Dallas%' or
        ArrName like '%Denver%' or
        ArrName like '%Honolulu%' or
        ArrName like '%Las Vegas%' or
        ArrName like '%Los Angeles%' or
        ArrName like '%New York%' or
        ArrName like '%Philadelphia%' or
        ArrName like '%Phoenix%' or
        ArrName like '%San Diego%' or
        ArrName like '%San Francisco%' or
        ArrName like '%Washington D.C.%'
        )
    )
";*/
$constAverage = 3100;
$constPoints = 5000;
$provs = Array(
    0 => 'None',
    7 => 'Delta',
    20 => 'Hawaiian',
    26 => 'United',
    27 => 'US Airways',
    664 => 'American Airlines'
);
/* $provs = Array(
    0 => 'None',
    7 => 'Delta',
    20 => 'Hawaiian',
    26 => 'United',
    27 => 'Usair',
    664 => 'Aadvantage'
); */
$programNames = Array(
    0 => 'None',
    7 => 'SkyMiles',
    20 => 'HawaiianMiles',
    26 => 'MileagePlus miles',
    27 => 'Miles',
    664 => 'miles'
);
$programUrls = Array(
    0 => 'None',
    7 => 'delta',
    20 => 'hawaiian',
    26 => 'united',
    27 => 'usair',
    664 => 'aadvantage'
);
// TODO: American Airlnes
$cities = Array(
    'Atlanta',
    'Austin',
    'Boston',
    'Charlotte',
    'Chicago',
    'Dallas',
    'Denver',
    'Fort Lauderdale',
    'Honolulu',
    'Houston',
    'Las Vegas',
    'Los Angeles',
    'Miami',
    'New York',
    'Philadelphia',
    'Phoenix',
    'Scottsdale',
    'Portland',
    'San Diego',
    'San Francisco',
    'Seattle',
    'Toronto',
    'Washington D.C.'
);
$numCities = count($cities);
$provIds = '(';
$i=0;
foreach ($provs as $k => $v){
    $provIds.=$k;
    if (++$i!==count($provs))
        $provIds.=',';
    else
        $provIds.=') ';
}
$filter = '';
$filter2 = '';

function getProvider($UserID){
    global $provIds;
    $q2 = new TQuery("
select a.ProviderID as pid, p.Name as pn
from Account a
join Provider p on a.ProviderID = p.ProviderID
where UserID = $UserID
  and a.ProviderID in $provIds
order by a.Balance desc
        ");
    $pid = 26; // If all else fails, let it be United
    if (!$q2->EOF)
        $pid = $q2->Fields['pid'] ;
    else{
        // Looking up for providers from the same Alliance
        $q3 = new TQuery("
select AllianceID
from Account
join Provider on Account.ProviderID = Provider.ProviderID
where UserID = $UserID
  and Provider.AllianceID in (1,2)
order by Balance desc
            ");
        if (!$q3->EOF);
        $pid = $q3->Fields['AllianceID'] == 1 ? 7 : array_rand(array(26 => 26, 27 => 27));
    }
    return $pid;
}

$i=0;
foreach ($cities as $city){
    $filter.="BINARY HotelName like '%$city%' or BINARY Address like '%$city%'";
    $filter2.="BINARY ArrName like '%$city%'";
    if(++$i !== $numCities){
        $filter.=' or ';
        $filter2.=' or ';
    }
}
$sql = "
select Usr.FirstName, a.UserID, CheckInDate, CheckOutDate, HotelName, Address,
       IF(CheckInDate>CheckOutDate,1,datediff(CheckOutDate, CheckInDate)) as Days
from Reservation r join Account a on r.AccountID = a.AccountID join Usr on a.UserID = Usr.UserID
where ($filter)
and r.Hidden = 0
and date_sub(r.CheckInDate, interval 2 day) > now()
group by UserID
having CheckInDate = (
    select min(Reservation.CheckInDate)
    from Reservation join Account on Reservation.AccountID = Account.AccountID where
    Reservation.Hidden = 0
    and Account.UserID = a.UserID
    and date_sub(Reservation.CheckInDate, interval 2 day) > now()
    and ($filter)
)
";
$q = new TQuery($sql);
echo "Done<br />";
flush();
$u=0;
echo "<table border = 1><tr>
          <td>UserID</td>
          <td>CheckInDate</td>
          <td>CheckOutDate</td>
          <td>Days</td>
          <td>HotelName</td>
          <td>Address</td>
          <td>City</td>
          <td>ProviderID</td>
          <td>ProviderName</td>
          <td>Params</td>
          <td>URL</td>
      </tr>";
$resu = array();
while (!$q->EOF){
    // Retrieving best matching provider, city and params
    // TODO: Phoenix+-+Scottsdale
    $i = 0;
    while ((false === strpos($q->Fields['HotelName'], $cities[$i])) && (false === strpos($q->Fields['Address'], $cities[$i])))
        $i++;
    $city = $cities[$i];
    if (true){
        $pid = getProvider($q->Fields['UserID']);
        $pn = $provs[$pid];
        $params = array();
        $params['Days'] = $q->Fields['Days'];
        $params['Points'] = $constAverage*$params['Days'];
        $params['HighestPoints'] = $constPoints*$params['Days'];
        $params['Name'] = $q->Fields['FirstName'];
        $params['ProviderName'] = $pn;
        $params['ProgramName'] = $programNames[$pid];
        $params['City'] = $city;
        $params['Date'] = date("M d",strtotime($q->Fields['CheckInDate']));
        $url = 'region=';
        if (($city == 'Phoenix') || ($city == 'Scottsdale'))
            $city.='Phoenix - Scottsdale';
        $url.=urlencode($city);
        $url.='&checkOut=';
        $url.=urlencode(date("m/d/Y",strtotime($q->Fields['CheckOutDate'])));
        $url.='&program='.$programUrls[$pid].'&searchCount=2&checkIn=';
        $url.=urlencode(date("m/d/Y",strtotime($q->Fields['CheckInDate']))).'';
        $url.='&emailSignup=true&utm_source=awardwallet&utm_medium=cpa&utm_term=*';
        $url.=ucfirst($programUrls[$pid]).'*&utm_content=*'.urlencode($city).'*&utm_campaign=interstitial-popup';
        $url = 'https://www.rocketmiles.com/search?'.$url;
        $params['url'] = $url;
//        if (($city != 'Phoenix') && ($city != 'Scottsdale'))
        echo "<tr>
              <td>{$q->Fields['UserID']}</td>
              <td>{$q->Fields['CheckInDate']}</td>
              <td>{$q->Fields['CheckOutDate']}</td>
              <td>{$q->Fields['Days']}</td>
              <td>{$q->Fields['HotelName']}</td>
              <td>{$q->Fields['Address']}</td>
              <td>$city</td>
              <td>$pid</td>
              <td>$pn</td>
              <td>";print_r($params);echo "</td>
              <td><a href=\"$url\">$url</a></td>
              </tr>";
        flush();
        $u++;
        $resu[] = $q->Fields['UserID'];
    }
    $q->Next();
}
echo "</table><br/>";
echo "$u reservation users<br />";
echo "Scanning for trip users...<br />";
echo "<table border = 1><tr>
          <td>UserID</td>
          <td>CheckInDate</td>
          <td>CheckOutDate</td>
          <td>Days</td>
          <td>Address</td>
          <td>City</td>
          <td>ProviderID</td>
          <td>ProviderName</td>
          <td>Params</td>
          <td>URL</td>
      </tr>";
flush();
$q4 = new TQuery("
select TripSegmentID, ArrName
from TripSegment
where ($filter2)
  and date_sub(DepDate, interval 2 day) > now()
");
$eu = 0; // excluded users
$tu = 0; // trip users
$ct = 0; // "cut" trips
while (!$q4->EOF){
    $tsid = $q4->Fields['TripSegmentID'];
    $i = 0;
    while (false === strpos($q4->Fields['ArrName'], $cities[$i]))
        $i++;
    $city = $cities[$i];
    $q5 = new TQuery("
select Trip.TripID as tid, Trip.UserID as uid, Usr.FirstName
from Trip join TripSegment on Trip.TripID = TripSegment.TripID
join Usr on Trip.UserID = Usr.UserID
where TripSegment.TripSegmentID = $tsid
    ");
    if (!$q5->EOF){
        $uid = $q5->Fields['uid'];
        if (in_array($uid, $resu))
            $eu++;
        else{
            $tid = $q5->Fields['tid'];
            $q6 = new TQuery("
select *
from TripSegment
where TripSegment.TripID = $tid order by DepDate
            ");

           while ((!$q6->EOF) && ($q6->Fields['TripSegmentID'] != $tsid))
               $q6->Next();
           if ((!$q6->EOF) && ($q6->Fields['TripSegmentID'] == $tsid)){
               $ad = $q6->Fields['ArrDate'];
               $an = $q6->Fields['ArrName'];
               $q6->Next();
           }
           if (!$q6->EOF){
               $dd = $q6->Fields['DepDate'];
               $ddt = strtotime($dd);
               $adt = strtotime($ad);
               if (($ddt >= $adt + 64800)){
                  $days = floor(($ddt - $adt) / (60*60*24));
                  if ($days == 0)
                      $days = 1;
                  $pid = getProvider($uid);
                  $pn = $provs[$pid];
                  $params = array();
                  $params['Days'] = $days;
                  $params['Points'] = $constAverage*$params['Days'];
                  $params['HighestPoints'] = $constPoints*$params['Days'];
                  $params['Name'] = $q5->Fields['FirstName'];
                  $params['ProviderName'] = $pn;
                  $params['ProgramName'] = $programNames[$pid];
                  $params['City'] = $city;
                  $params['Date'] = date("M d",strtotime($ad));
                  $url = 'region=';
                  if (($city == 'Phoenix') || ($city == 'Scottsdale'))
                      $city.='Phoenix - Scottsdale';
                  $url.=urlencode($city);
                  $url.='&checkOut=';
                  $url.=urlencode(date("m/d/Y",strtotime($dd)));
                  $url.='&program='.$programUrls[$pid].'&searchCount=2&checkIn=';
                  $url.=urlencode(date("m/d/Y",strtotime($ad))).'';
                  $url.='&emailSignup=true&utm_source=awardwallet&utm_medium=cpa&utm_term=*';
                  $url.=ucfirst($programUrls[$pid]).'*&utm_content=*'.urlencode($city).'*&utm_campaign=interstitial-popup';
                  $url = 'https://www.rocketmiles.com/search?'.$url;
                  $params['url'] = $url;
                  $resu[] = $uid;
                  $tu++;
                  echo "<tr>
                  <td>$uid</td>
                  <td>$ad</td>
                  <td>$dd</td>
                  <td>$days</td>
                  <td>$an</td>
                  <td>$city</td>
                  <td>$pid</td>
                  <td>$pn</td>
                  <td>";print_r($params);echo "</td>
                  <td><a href=\"$url\">$url</a></td>
                  </tr>";
//                   echo "Added user $uid with TripID = $tid, tsid = $tsid<br />";
               }
           }
        }
    }
    $q4->Next();
}
echo "</table><br/>";
echo "$tu trip users";
drawFooter();
?>
