<?
$deltaId = 7;
$unitedId = 26;
$usairwaysId = 27;

function getUsersByLevel($providerId, $rank){
    // 7 - Delta, 26 - United, 27 - US Airways
    $q = new TQuery("
        select ProviderPropertyID ppid
        from ProviderProperty
        where ProviderID = $providerId
        and Kind = 3
    ");
    if ($q->EOF)
        return false;
    else{
        $ppid = $q->Fields['ppid'];
        $q2 = new TQuery("
        select distinct(UserID)
        from AccountProperty ap join Account a on ap.AccountID = a.AccountID
        where ProviderPropertyID = $ppid
        and Val in (select ValueText
                    from TextEliteLevel tel join EliteLevel el on tel.EliteLevelID = el.EliteLevelID
                    where ProviderID = $providerId
                    and Rank = $rank)
        and UserAgentID is null
        ");
        $result = array();
        while (!$q2->EOF){
            $result[] = $q2->Fields['UserID'];
            $q2->Next();
        }
        return $result;
    }
}

function getUsersWithSameRank($arProviders, $rank){
//    $users = array();
    $result = array();
    $first = true;
    foreach ($arProviders as $prov){
//        $users[$prov] = getUsersByLevel($prov, $rank);
        if ($first){
            $result = getUsersByLevel($prov, $rank);
            $first = false;
        }
        else
            $result = array_intersect($result, getUsersByLevel($prov, $rank));
    }
/*    foreach($users[$arProviders[0]] as $u){
        $fits = true;
        foreach ($arProviders as $ap){
            if (!in_array($u, $users[$ap])){
                $fits = false;
                break;
            }
        }
        if ($fits)
            $result[] = $u;
    } */
    return $result;
}
?>