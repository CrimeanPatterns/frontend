<?

require_once (__DIR__.'/../OfferPlugin.php');

class AaawOfferPlugin extends OfferPlugin{

    public function searchUsers(){
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        flush();
        set_time_limit(59);
        $this->log('Searching for users...');
        flush();
        $q = new TQuery("
            select UserID, FirstName
            from Usr
            where not (FirstName in ('emailVerifier', 'test0001'))
            and CreationDateTime < '2013-08-06 14:00:00'
        ");
        set_time_limit(59);
        $this->log('Adding users...');
        flush();
        foreach ($q as $r){
//          $this->log("Adding user ".$r['UserID']." named ".$r['FirstName']);
            $this->addUser($r['UserID'], array('Name' => $r['FirstName']));
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
