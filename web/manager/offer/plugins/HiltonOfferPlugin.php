<?

require_once (__DIR__.'/../OfferPlugin.php');

class HiltonOfferPlugin extends OfferPlugin{

    public function searchUsers(){
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        flush();
        $q0 = new TQuery("select ProviderID from Provider where code = 'hhonors'");
        $pcode = intval($q0->Fields['ProviderID']);
        set_time_limit(59);
        $this->log('Searching for users...');
        flush();
        $q = new TQuery("
            select distinct(UserID)
            from Account
            where Account.ProviderID = $pcode
            and ErrorCode = 1
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
