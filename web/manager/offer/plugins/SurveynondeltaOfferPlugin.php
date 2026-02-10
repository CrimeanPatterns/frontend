<?

require_once (__DIR__.'/../OfferPlugin.php');

class SurveynondeltaOfferPlugin extends OfferPlugin{

    private $deltaId = 7;

    public function searchUsers(){
        $this->log('Stage 1: Executing initial query...');
        flush();
        set_time_limit(59);
        $q = new TQuery("
          SELECT distinct(UserID) FROM Account
          WHERE NOT UserID in (
            SELECT distinct(UserID) FROM Account
            WHERE ProviderID = 7
            AND UserAgentID IS NULL
            )
        ");
        $this->log('Stage 2: Adding users...');
        flush();
        set_time_limit(59);
        $u = 0;
        while (!$q->EOF){
            $params = array();
            $params['URL'] = 'https://www.surveymonkey.com/s/Y9CKP6V?c='.$q->Fields["UserID"];
            $this->addUser($q->Fields['UserID'], $params);
            $u++;
            if ($u % 5000 == 0){
                $this->log("$u users added so far...");
                flush();
                set_time_limit(59);
            }
            $q->Next();
        }
        return $u;
    }

}

?>