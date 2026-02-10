<?

require_once (__DIR__.'/../OfferPlugin.php');

class MarriottOfferPlugin extends OfferPlugin{

    private $chunkSize = 10000;

    public function searchUsers(){
        $u = 0;
        $this->log('Setting time limit to 59 seconds.....');
        flush();
        set_time_limit(59);
        $this->log('Detecting maximum user ID');
        flush();
        set_time_limit(59);
        $q = new TQuery("
            select max(UserID) as MaxID
            from Usr
        ");
        $maxId = $q->Fields['MaxID'];
        $this->log("$maxId is maximum user ID");
        $this->log('Detecting Canada Country ID');
        flush();
        set_time_limit(59);
        $q = new TQuery("
            select CountryID from Country
            where Name = 'Canada'
        ");
        $canadaId = $q->Fields['CountryID'];
        $this->log("CountryID is $canadaId");
        $this->log('Searching for users...');
        flush();
        set_time_limit(59);
        $gi = $this->getCountryByIpResolver();
        for ($floorId = 0; $floorId <= $maxId; $floorId += $this->chunkSize){
            $q = new TQuery(
                "select u.UserID, AccountID, Country, CountryID, LastLogonIP, RegistrationIP, ac.CountryName, ac.CountryCode
                from Usr u join Account a on a.UserID = u.UserID
                join Provider p on p.ProviderID = a.ProviderID
                left join AirCode ac on u.HomeAirport = ac.AirCode
                where p.Code = 'marriott' and u.UserID >= $floorId and u.UserID < $floorId + $this->chunkSize and ErrorCode = 1
                group by UserID
            ");
            foreach ($q as $v){
                $toAdd = false;
                if (($v['Country'] == 'Canada') || ($v['CountryID']) == $canadaId || ($v['CountryName'] == 'Canada') || ($v['CountryCode'] == 'CA') || ($v['CountryCode'] == 'CAN'))
                    $toAdd = true;
                if (!$toAdd){
                    // TODO: IP detection
                    // check user's RegistrationIP and LastLogonIP:
                    //if ((geoip_record_by_addr($gi, $v['RegistrationIP']) == geoip_country_code_by_addr($gi, $v['LastLogonIP'])) && ('CA' == geoip_country_code_by_addr($gi, $v['LastLogonIP'])))
                    //    $toAdd = true;
                    /* if (geoip_record_by_addr($gi, $v['RegistrationIP']) != null && geoip_record_by_addr($gi, $v['LastLogonIP']) != null){
                        if ((geoip_country_code_by_addr($gi, $v['RegistrationIP']) == geoip_country_code_by_addr($gi, $v['LastLogonIP'])) && ('CA' == geoip_country_code_by_addr($gi, $v['LastLogonIP']))){
                            $toAdd = true;
                        } */
                    if (($gi($v['LastLogonIP']) != null) && ('CA' == $gi($v['LastLogonIP']))){
                        $toAdd = true;
                    }
                }
                if ($toAdd){
                    $this->addUser($v['UserID'], array());
                    $u++;
                    if ($u % 100 == 0){
                        $this->log($u.' users so far...');
                        flush();
                        set_time_limit(59);
                    }
                }
            }
            echo "...<br/>";
            flush();
            set_time_limit(59);
        }
        /*$q = new TQuery("
            select UserID
            from Usr
        ");
        set_time_limit(59);
        $this->log('Adding users...');
        flush();
        foreach ($q as $r){
            $this->addUser($r['UserID'], array());
            $u++;
            if ($u % 100 == 0){
                set_time_limit(59);
                $this->log($u.' users so far...');
                flush();
            }
        } */
        return $u;
    }
}

?>
