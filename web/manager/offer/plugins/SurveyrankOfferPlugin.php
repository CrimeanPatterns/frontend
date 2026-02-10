<?

require_once (__DIR__.'/../OfferPlugin.php');
require_once (__DIR__.'/surveyCommon.php');

class SurveyrankOfferPlugin extends OfferPlugin{
    public function searchUsers(){
        global $deltaId, $unitedId, $usairwaysId;
        $this->log("Searching for ranked Delta users...");
        flush();
        set_time_limit(59);
        $exclude = array();
        for ($rank = 0; $rank < 7; $rank++)
            $exclude = array_merge($exclude, getUsersWithSameRank(array($deltaId, $unitedId, $usairwaysId), $rank));
        $u = 0;
        $q1 = new TQuery("
          select ProviderPropertyID ppid
          from ProviderProperty
          where ProviderID = $deltaId
          and Kind = 3
        ");
        $ppid = $q1->Fields['ppid'];
        $q = new TQuery("
          select distinct(UserID)
            from AccountProperty ap join Account a on ap.AccountID = a.AccountID
            where ProviderPropertyID = $ppid
            and Val in (select ValueText
                        from TextEliteLevel tel join EliteLevel el on tel.EliteLevelID = el.EliteLevelID
                        where ProviderID = $deltaId
                        and Rank > 0)
            and UserAgentID is null
        ");
        $this->log("Adding...");
        flush();
        set_time_limit(59);
        while (!$q->EOF){
            $user = $q->Fields['UserID'];
            if (!in_array($user, $exclude)){
                $q3 = new TQuery("select OfferUserID
                                 from OfferUser ou join Offer o on ou.OfferID = o.OfferID
                                 where UserID = $user AND Code = 'surveysplitters'");
                if ($q3->EOF){
                    $params = array();
                    $params['URL'] = 'https://www.surveymonkey.com/s/Y9NWGJ2?c='.$user;
                    $this->addUser($user, $params);
                    $u++;
                    if ($u % 1000 == 0){
                        $this->log("$u users added so far...");
                        flush();
                        set_time_limit(59);
                    }
                }
            }
            $q->Next();
        }
        return $u;
    }
}