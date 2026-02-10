<?

require_once (__DIR__.'/../OfferPlugin.php');

class AmexrpgOfferPlugin extends OfferPlugin{

    public function searchUsers(){
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        set_time_limit(59);
        $this->log('Searching for users...');
        $q = new TQuery("
            select distinct(UserID)
            from Usr
        ");
/*        $q = new TQuery("
            select distinct(UserID)
            from Account a join Provider p on a.ProviderID = p.ProviderID
            where p.Code = 'amazongift'
        "); */
        foreach ($q as $r){
            $this->addUser($r['UserID'],array());
            $u++;
        }
        return $u;
    }
}

?>
