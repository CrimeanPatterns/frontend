<?

require_once (__DIR__.'/../OfferPlugin.php');

class OmnihotelsOfferPlugin extends OfferPlugin{

    public function searchUsers(){
        $cities = array(
            'Scottsdale, AZ'    => 'Scottsdale%Arizona',
            'Tucson, AZ'        => 'Tucson%Arizona',
            'Carlsbad, CA'      => 'Carlsbad%California',
            'Rancho Mirage, CA' => 'Rancho Mirage%California',
            'Asheville, NC'     => 'Asheville%North Carolina',
            'Hilton Head, SC'   => 'Hilton Head%South Carolina',
            'Bedford Springs, PA' => 'Bedford Springs%Pennsylvania',
            'Hot Springs, VA'   => 'Hot Springs%Virginia',
            'Bretton Woods, NH' => 'Bretton Woods%New Hampshire',
            'Amelia Island, FL' => 'Amelia Island%Florida',
            'ChampionsGate, FL' => 'ChampionsGate%Florida',
            'Austin, TX'        => 'Austin%Texas'
        );
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        flush();
        set_time_limit(59);
        $users = array();
        $this->log('Step 1: Reservations...');
        flush();
        $sql = "
            select UserID, Address, CheckInDate
            from Reservation
            where CheckInDate > now() and(";
        $kn = 0;
        foreach($cities as $k => $v){
            if ($kn > 0)
                $sql .= ' or ';
            $sql .= "Address like '%$v%' or Address like '%$k%'";
            $kn++;
        }
        $sql .= ') group by UserID';
        $q = new TQuery($sql);
        set_time_limit(59);
        $this->log('Adding users...');
        flush();
        foreach ($q as $r){
            if (!in_array($r['UserID'], $users)){
                // echo $r['Address'].'<br />';
                foreach($cities as $k => $v){
                    if (preg_match("/.*".str_replace('%', '.*', $v).".*/is", $r['Address']) || preg_match("/.*$k.*/is", $r['Address']))
                        $city = $k;
                }
                // echo $r['CheckInDate'].'<br />';
                $this->addUser($r['UserID'], array('City' => $city, 'Address' => $r['Address'], 'Date' => date("M j", strtotime($r['CheckInDate']))));
                $users[] = $r['UserID'];
                $u++;
                if ($u % 100 == 0){
                    set_time_limit(59);
                    $this->log($u.' users so far...');
                    flush();
                }
            }
        }
        $this->log('Step 2: Trips...');
        flush();
        $sql = "
            select UserID, ArrDate, ArrName, concat(ac.CityName, ', ', ac.State) as CC
            from TripSegment ts join Trip t on ts.TripID = t.TripID
            left join AirCode ac on ts.ArrCode = ac.AirCode
            where ArrDate > now() and(";
        $kn = 0;
        foreach($cities as $k => $v){
            if ($kn > 0)
                $sql .= ' or ';
            $sql .= "ArrName like '%$k%' or ArrName like '%$v%' or concat(ac.CityName, ', ', ac.State) = '$k'";
            $kn++;
        }
        $sql .= ') group by UserID';
        $q = new TQuery($sql);
        set_time_limit(59);
        $this->log('Adding users...');
        flush();
        foreach ($q as $r){
            if (!in_array($r['UserID'], $users)){
                foreach($cities as $k => $v){
                    if (preg_match("/.*".str_replace('%', '.*', $v).".*/is", $r['ArrName']) || preg_match("/.*$k.*/is", $r['ArrName']) || $r['CC'] == $k)
                        $city = $k;
                }
                $this->addUser($r['UserID'], array('City' => $city, 'ArrName' => $r['ArrName'], 'Date' => date("j M", strtotime($r['ArrDate']))));
                $users[] = $r['UserID'];
                $u++;
                if ($u % 100 == 0){
                    set_time_limit(59);
                    $this->log($u.' users so far...');
                    flush();
                }
            }
        }
        return $u;
    }
}

?>
