<?

require_once (__DIR__.'/../OfferPlugin.php');
require_once (__DIR__.'/surveyCommon.php');

class SurveysplittersOfferPlugin extends OfferPlugin{
    public function searchUsers(){
        global $deltaId, $unitedId, $usairwaysId;
        $this->Log('Searching for splitters...');
        flush();
        set_time_limit(59);
        $u = 0;
        for ($rank = 0; $rank < 7; $rank++){
            $this->Log("Rank $rank...");
            flush();
            set_time_limit(59);
            $users = getUsersWithSameRank(array($deltaId, $unitedId, $usairwaysId), $rank);
            $this->Log("Adding users...");
            flush();
            set_time_limit(59);
            foreach ($users as $user){
                $params = array();
                $params['URL'] = 'https://www.surveymonkey.com/s/Y9F3T9W?c='.$user;
                $this->addUser($user, $params);
                $u++;
                if ($u % 1000 == 0){
                    $this->log("$u users added so far...");
                    flush();
                    set_time_limit(59);
                }
            }
            $this->Log("$u users so far...");
            flush();
            set_time_limit(59);
        }
        return $u;
    }
}