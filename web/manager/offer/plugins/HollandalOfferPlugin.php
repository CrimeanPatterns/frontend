<?

require_once (__DIR__.'/../OfferPlugin.php');

class HollandalOfferPlugin extends OfferPlugin{

    public function searchUsers(){
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        flush();
        set_time_limit(59);
        $this->log('Searching for users...');
        flush();
        $q = new TQuery("
            select distinct(UserID)
            from Account a join Provider p
            on a.ProviderID = p.ProviderID
            where p.Kind = 10 AND a.UserID > {$this->getLastUserId()}
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
        }
        return $u;
    }
}

?>