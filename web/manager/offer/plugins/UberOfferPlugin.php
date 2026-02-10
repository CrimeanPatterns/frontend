<?
require_once (__DIR__.'/../OfferPlugin.php');

class UberOfferPlugin extends OfferPlugin{
    public function searchUsers(){
        $cities = Array(
            'Amsterdam',
            'Atlanta',
            'Baltimore',
            'Berlin',
            'Boston',
            'Cannes',
            'Chicago',
            'Dallas',
            'Denver',
            'Detroit',
            'Hamptons',
            'Indianapolis',
            'London',
            'Los Angeles',
            'Lyon',
            'Melbourne',
            'Mexico City',
            'Milan',
            'Minneapolis',
            'St. Paul',
            'Munich',
            'Napa',
            'New York',
            'Orange County',
            'Paris',
            'Philadelphia',
            'Phoenix',
            'Rome',
            'Sacramento',
            'San Diego',
            'San Francisco',
            'Seoul',
            'Seattle',
            'Singapore',
            'Stockholm',
            'Sydney',
            'Taipei',
            'Toronto',
            'Washington',
            'Zurich'
        );

        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        flush();
        set_time_limit(59);
        $this->log('Searching for users...');
        $users = array();

        $this->log('Stage 0: Home airports...');
        $where = '';
        $i = 0;
        foreach ($cities as $city){
            if ($i > 0)
                $where .= ' or ';
            $where .= "BINARY CityName like '%".$city."%'";
            $i++;
        }
        $sql = "select UserID, CityName
                from Usr join AirCode on Usr.HomeAirport = AirCode.AirCode
                where
                $where";
        $this->log('Executing query:');
        $this->log($sql);
        flush();
        set_time_limit(59);
        $q = new TQuery($sql);
        $ha = 0;
        foreach ($q as $r){
            $j = 0;
            while (false === strpos($r['CityName'], $cities[$j]))
                $j++;
            $users[$r['UserID']] = $cities[$j];;
            $ha++;
            }
        $this->log("$ha users by HomeAirport");

        $this->log('Stage 1: Cities...');
        $where = '';
        $i = 0;
        foreach ($cities as $city){
            if ($i > 0)
                $where .= ' or ';
            $where .= "BINARY city like '%".$city."%'";
            $i++;
        }
        $sql = "select UserID, City from Usr where
                $where";
        $this->log('Executing query:');
        $this->log($sql);
        flush();
        set_time_limit(59);
        $q = new TQuery($sql);
        foreach ($q as $r)
            if (!array_key_exists($r['UserID'], $users)){
                $j = 0;
                while (false === strpos($r['City'], $cities[$j]))
                    $j++;
                $users[$r['UserID']] = $cities[$j];;
            }
        $this->log('Stage 2: Address1...');
        $where = '';
        $i = 0;
        foreach ($cities as $city){
            if ($i > 0)
                $where .= ' or ';
            $where .= "BINARY Address1 like '%".$city."%'";
            $i++;
        }
        $sql = "select UserID, Address1 from Usr where
                $where";
        $this->log('Executing query:');
        $this->log($sql);
        flush();
        set_time_limit(59);
        $q = new TQuery($sql);
        foreach ($q as $r)
            if (!array_key_exists($r['UserID'], $users)){
                $j = 0;
                while (false === strpos($r['Address1'], $cities[$j]))
                    $j++;
                $users[$r['UserID']] = $cities[$j];;
            }

        $this->log('Stage 3: Address2...');
        $where = '';
        $i = 0;
        foreach ($cities as $city){
            if ($i > 0)
                $where .= ' or ';
            $where .= "BINARY Address2 like '%".$city."%'";
            $i++;
        }
        $sql = "select UserID, Address2 from Usr where
                $where";
        $this->log('Executing query:');
        $this->log($sql);
        set_time_limit(59);
        flush();
        $q = new TQuery($sql);
        foreach ($q as $r)
            if (!array_key_exists($r['UserID'], $users)){
                $j = 0;
                while (false === strpos($r['Address2'], $cities[$j]))
                    $j++;
                $users[$r['UserID']] = $cities[$j];;
            }

        $this->log('Stage 4: Reservations...');
        $where = '';
        $i = 0;
        foreach ($cities as $city){
            if ($i > 0)
                $where .= ' or ';
            $where .= "BINARY Reservation.Address like '%".$city."%'";
            $i++;
        }
        $sql = "select Account.UserID, Address
                from Account join Reservation on Reservation.AccountID = Account.AccountID
                where
                ($where)
                and date_add(CheckOutDate, interval 1 day) > now()
                and CheckInDate < '2013-07-08'
                order by IF(CheckInDate > now(), 0, 1), CheckInDate
                ";
        // date_add(CheckOutDate, interval 1 day) > now() is "yesterday" for 00:00 dates
        $this->log('Executing query:');
        $this->log($sql);
        flush();
        set_time_limit(59);
        $q = new TQuery($sql);
        foreach ($q as $r)
            if (!array_key_exists($r['UserID'], $users)){
                $j = 0;
                //print_r($r);
                while (false === strpos($r['Address'], $cities[$j]))
                    $j++;
                $users[$r['UserID']] = $cities[$j];;
            }

        $this->log('Stage 4.5: Preparing for Trips...');
        $q = new TQuery("select max(UserID) as m from Usr");
        foreach ($q as $r)
            $maxuid = $r['m'];
        for ($uid = 0; $uid < $maxuid; $uid += 10000){
        $nuid = $uid + 10000;
        $this->log("Stage 5: Trips... $uid to $nuid");
        $where = '';
        $i = 0;
        foreach ($cities as $city){
            if ($i > 0)
                $where .= ' or ';
            $where .= "BINARY ArrName like '%".$city."%'";
            $i++;
        }
        $sql = "select Account.UserID, ArrName from
                TripSegment join Trip on TripSegment.TripID = Trip.TripID
                join Account on Trip.AccountID = Account.AccountID
                where
                ($where)
                and Account.UserID >= $uid and Account.UserID < $nuid
                and date_add(ArrDate, interval 1 day) > now()
                and ArrDate < '2013-07-08'
                order by IF(ArrDate > now(), 0, 1), ArrDate
                ";
        // date_add(ArrDate, interval 1 day) > now() is "yesterday" for 00:00 dates
        $this->log('Executing query:');
        $this->log($sql);
        flush();
        set_time_limit(59);
        $q = new TQuery($sql);
        foreach ($q as $r)
            if (!array_key_exists($r['UserID'], $users)){
                $j = 0;
                //print_r($r);
                while (false === strpos($r['ArrName'], $cities[$j]))
                    $j++;
                $users[$r['UserID']] = $cities[$j];;
            }
        }

        foreach ($users as $usr => $city){
            $this->addUser($usr, array('City' => $city));
            $u++;
        }
        return $u;
    }
}
?>