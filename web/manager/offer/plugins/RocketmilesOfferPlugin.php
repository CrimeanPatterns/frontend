<?

require_once (__DIR__.'/../OfferPlugin.php');

class RocketmilesOfferPlugin extends OfferPlugin{

    private $provIds;

    public function afterShow($userId, $offerUserId, array $params){
        global $Connection;
        if (isset($params['Type'])){
            $rtype = $params['Type'];
            $iid = $params['iid'];
            $Connection->Execute("
                insert into OfferRocketmilesShown (UserID, RecordID, RecordType)
                values ('$userId', '$iid', '$rtype')
                on duplicate key update UserID = UserID");
        }
//        $Connection->Execute("delete from OfferUser where OfferUserId = $offerUserId");
    }

    private function getProvider($UserID){
        $q2 = new TQuery("
select a.ProviderID as pid, p.Name as pn
from Account a
join Provider p on a.ProviderID = p.ProviderID
where UserID = $UserID
  and a.ProviderID in $this->provIds
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
  and Provider.AllianceID in (2,3)
order by Balance desc
            ");
            if (!$q3->EOF);
            $pid = $q3->Fields['AllianceID'] == 3 ? 1 : array_rand(array(26 => 26, 27 => 27));
        }
        return $pid;
    }

    public function searchUsers(){
        $constAverage = 3100;
        $constPoints = 5000;
        $provs = Array(
            0 => 'None',
            1 => 'American Airlines',
            13 => 'JetBlue Airways',
            18 => 'Alaska Airlines',
            20 => 'Hawaiian',
            26 => 'United',
            27 => 'US Airways',
            72 => 'Virgin America',
            179 => 'Etihad Airways'
        );
        $programNames = Array(
            0 => 'None',
            1 => 'miles',
            13 => 'TrueBlue points',
            18 => 'Mileage Plan Miles',
            20 => 'HawaiianMiles',
            26 => 'MileagePlus miles',
            27 => 'Miles',
            72 => 'Virgin America Elevate Points',
            179 => 'Etihad Guest Miles'
        );
        $programUrls = Array(
            0 => 'None',
            1 => 'aadvantage',
            13 => 'jetblue',
            18 => 'alaska',
            20 => 'hawaiian',
            26 => 'united',
            27 => 'usair',
            72 => 'virgin',
            179 => 'etihad',
        );
        if (($handle = fopen(__DIR__ . "/data/rocketmiles_cities.csv", "r")) !== false) {
            $csvData = array();
            $i = 0 ;
            while (($data = fgetcsv($handle, 0, ",")) !== false) {
                if ($i == 0)
                    $csvCaptions = $data;
                else
                    $csvData[] = $data;
                $i++;
            }
            fclose($handle);
        }else
            die("cAP: ERROR: csv report not found!");
        foreach ($csvData as &$d){
            foreach ($d as &$dc){
                $dc = str_replace('"', '', $dc);
                $dc = str_replace("'", "\'", $dc);
                if ($dc == 'NULL')
                    $dc = '';
            }
            $cities[$d[3]] = $d[0];
            $states[$d[3]] = $d[1];
            $countries[$d[3]] = $d[2];
        }
        /* $cities = Array(
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
        ); */
        $numCities = count($cities);
        $this->provIds = '(';
        $i=0;
        foreach ($provs as $k => $v){
            $this->provIds.=$k;
            if (++$i!==count($provs))
                $this->provIds.=',';
            else
                $this->provIds.=') ';
        }
        $filter = '';
        $filter2 = '';
        $i=0;
        foreach ($cities as $k => $city){
            $filter.="BINARY Address like '%$city,%".$countries[$k]."%' OR BINARY Address like '%$city, ".$states[$k]."%'";
            $filter2.="(BINARY ArrName like '%$city,%".$countries[$k]."%' OR BINARY ArrName like '%$city, ".$states[$k]."%' OR (AirCode.CityName = '$city' AND (AirCode.CountryName = '".$countries[$k]."' OR AirCode.State = '".$states[$k]."')))";
            if(++$i !== $numCities){
                $filter.=' or ';
                $filter2.=' or ';
            }
        }
        $resu = array();
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        set_time_limit(59);
        $this->log('Deleting agreed and not denied user records...');
        set_time_limit(59);
        global $Connection;
        $Connection->Execute("DELETE FROM OfferUser WHERE OfferID = $this->offerId AND (Agreed = 1 OR (Agreed is null and ShowsCount > 0))");
        $this->log('Searching for users...');
        $this->log('Stage 1: Executing initial queries for Trips data...');
        flush();
        set_time_limit(59);
        $airCodes = SQLToArray("select AirCode, CityName from AirCode", 'AirCode', 'CityName');
        $q4 = new TQuery("
select TripSegmentID, ArrName, ArrCode
from TripSegment join AirCode
on TripSegment.ArrCode = AirCode.AirCode
where ($filter2)
  and date_sub(DepDate, interval 2 day) > now()
        ");
        $this->log('Stage 2: Parsing Trips data...');
        set_time_limit(59);
        flush();
        while (!$q4->EOF){
            $tsid = $q4->Fields['TripSegmentID'];
            unset($city);
            unset($city_id);
            foreach ($cities as $k => $v)
                if (false !== strpos($q4->Fields['ArrName'], $v.',')){
                    $city = $v;
                    $city_id = $k;
                }
            if (!isset($city) || (!isset($city_id))){
                $city = $airCodes[strtoupper($q4->Fields['ArrCode'])];
                $city_id = array_search($city, $cities);
                // $this->Log($city." was found by AirCode for ".$q4->Fields['TripSegmentID']);
            }
            if (!isset($city) || (!isset($city_id))){
                $this->log('Couldn\'t find city:');
                $this->log("ArrName: ".$q4->Fields['ArrName']);
                $this->log("TripSegmentID: ".$q4->Fields['TripSegmentID']);
                print_r($cities);
                Die("Couldn't find city");
            }
            $q5 = new TQuery("
select Trip.TripID as tid, Trip.UserID as uid, Usr.FirstName, Usr.ThousandsSeparator
from Trip join TripSegment on Trip.TripID = TripSegment.TripID
join Usr on Trip.UserID = Usr.UserID
where TripSegment.TripSegmentID = $tsid
and Trip.Hidden = 0
and not Trip.TripID in
  (select RecordID from OfferRocketmilesShown
   where RecordType = 't')
    ");
            if (!$q5->EOF){
                $uid = $q5->Fields['uid'];
                if (!in_array($uid, $resu)){
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
                        $q6->Next();
                    }
                    if (!$q6->EOF){
                        $dd = $q6->Fields['DepDate'];
                        $ddt = strtotime($dd);
                        $adt = strtotime($ad);
                        if (($ddt >= $adt + 64800) && ($ddt <= $adt + 5443200)){
                            $days = floor(($ddt - $adt) / (60*60*24));
                            if ($days == 0)
                                $days = 1;
                            $pid = $this->getProvider($uid);
                            $pn = $provs[$pid];
                            $params = array();
                            $params['Days'] = $days;
                            $params['Points'] = number_format($constAverage*$params['Days'],0 ,'.', $q5->Fields['ThousandsSeparator']);
                            $params['HighestPoints'] = number_format($constPoints*$params['Days'],0 ,'.', $q5->Fields['ThousandsSeparator']);
                            $params['Name'] = ucfirst($q5->Fields['FirstName']);
                            $params['ProviderName'] = $pn;
                            $params['ProgramName'] = $programNames[$pid];
                            $params['City'] = $city;
                            $params['Date'] = date("M d",strtotime($ad));
                            $url = 'region=';
                            /* if (($city == 'Phoenix') || ($city == 'Scottsdale'))
                                $city='Phoenix - Scottsdale'; */
                            $url.=urlencode($city_id);
                            $url.='&checkOut=';
                            $url.=urlencode(date("m/d/Y",strtotime($dd)));
                            $url.='&program='.$programUrls[$pid].'&searchCount=2&checkIn=';
                            $url.=urlencode(date("m/d/Y",strtotime($ad))).'';
                            $url.='&emailSignup=true&utm_source=awardwallet&utm_medium=cpa&utm_term=*';
                            $url.=ucfirst($programUrls[$pid]).'*&utm_content=*'.urlencode($city).'*&utm_campaign=interstitial-popup';
                            $url = 'https://www.rocketmiles.com/search?'.$url;
                            $params['url'] = $url;
                            $params['Type'] = 't';
                            $params['iid'] = $tid;
                            $this->addUser($uid, $params);
                            $resu[] = $uid;
                            $u++;
                            if ($u % 100 == 0){
                                $this->log("$u users added");
                                flush();
                                set_time_limit(59);
                            }
                        }
                    }
                }
            }
            $q4->Next();
        }
        $sql = "
select Usr.FirstName, Usr.ThousandsSeparator, a.UserID, CheckInDate, CheckOutDate, HotelName, Address,
       IF(CheckInDate>CheckOutDate,1,datediff(CheckOutDate, CheckInDate)) as Days,
       r.ReservationID as rid
from Reservation r join Account a on r.AccountID = a.AccountID join Usr on a.UserID = Usr.UserID
where ($filter)
and r.Hidden = 0
and date_sub(r.CheckInDate, interval 2 day) > now()
and not r.ReservationID in
  (select RecordID from OfferRocketmilesShown
   where RecordType = 'r')
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


        $this->log('Stage 3: Executing initial query for Reservation data...');
        flush();
        set_time_limit(59*3);
        $q = new TQuery($sql);
        $this->log('Stage 4: Parsing Reservation data...');
        flush();
        set_time_limit(59);
        while (!$q->EOF){
            // Retrieving best matching provider, city and params
            unset($city);
            unset($city_id);
            foreach ($cities as $k => $v){
                   if (strpos($q->Fields['Address'], $v.',') !== false)
                    {
                         /* if ($q->Fields['UserID'] == 3545){// 212996
                            $this->Log("/$v.*".$countries[$k]."/ims");
                            $this->Log("/$v.*".$states[$k]."/ims");
                            $this->Log($q->Fields['Address']);
                        } */
                        $city = $v;
                        $city_id = $k;
                    }
            }
            if (!isset($city) || (!isset($city_id))){
                $this->log('Couldn\'t find city:');
                $this->log("HotelName: ".$q->Fields['HotelName']);
                $this->log("Address: ".$q->Fields['Address']);
                print_r($cities);
                Die("Couldn't find city for user ".$q->Fields['UserID']);
            }
            if (!in_array($q->Fields['UserID'], $resu)){
                $pid = $this->getProvider($q->Fields['UserID']);
                $pn = $provs[$pid];
                $params = array();
                $params['Days'] = $q->Fields['Days'];
                $params['Points'] = number_format($constAverage*$params['Days'], 0 ,'.', $q->Fields['ThousandsSeparator']);
                $params['HighestPoints'] = number_format($constPoints*$params['Days'], 0 ,'.', $q->Fields['ThousandsSeparator']);
                $params['Name'] = ucfirst($q->Fields['FirstName']);
                $params['ProviderName'] = $pn;
                $params['ProgramName'] = $programNames[$pid];
                $params['City'] = $city;
                $params['Date'] = date("M d",strtotime($q->Fields['CheckInDate']));
                $url = 'region=';
                /* if (($city == 'Phoenix') || ($city == 'Scottsdale'))
                    $city.='Phoenix - Scottsdale'; */
                $url.=urlencode($city_id);
                $url.='&checkOut=';
                $url.=urlencode(date("m/d/Y",strtotime($q->Fields['CheckOutDate'])));
                $url.='&program='.$programUrls[$pid].'&searchCount=2&checkIn=';
                $url.=urlencode(date("m/d/Y",strtotime($q->Fields['CheckInDate']))).'';
                $url.='&emailSignup=true&utm_source=awardwallet&utm_medium=cpa&utm_term=';
                //$url.=*ucfirst($programUrls[$pid])*;
                $url.=$q->Fields['UserID'];
                $url.='&utm_content=*'.urlencode($city).'*&utm_campaign=interstitial-popup';
                $url = 'https://www.rocketmiles.com/search?'.$url;
                $params['url'] = $url;
                $params['Type'] = 'r';
                $params['iid'] = $q->Fields['rid'];
//        if (($city != 'Phoenix') && ($city != 'Scottsdale'))
                $this->addUser($q->Fields['UserID'], $params);
                $resu[] = $q->Fields['UserID'];
                $u++;
                if ($u % 500 == 0){
                    $this->log("$u users added");
                    flush();
                    set_time_limit(59);
                }
            }
            $q->Next();
        }
        return $u;
    }
}