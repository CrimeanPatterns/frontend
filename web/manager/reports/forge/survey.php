<?
$schema = "OfferUser";
require "../../start.php";
$sTitle = "Survey offer reports";

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
                    and `Rank` = $rank)
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

foreach (array(7,26,27) as $pid){
    echo "Provider $pid<br />";
    for ($rank = 0; $rank < 7; $rank++)
        echo "Rank $rank:".count(getUsersByLevel($pid, $rank))."<br />";
}

echo "Splitters:<br />";
for ($rank = 0; $rank < 7; $rank++){
    echo "Rank $rank:";print_r(getUsersWithSameRank(array(7,26,27), $rank));echo "<br />";
}

/*
===== SPLITTER CHECKING QUERY =====
select ap.Val from AccountProperty ap join Account a on ap.AccountId = a.AccountID join ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID where UserID = 160472 and a.ProviderID in (7,26,27) and pp.Kind = 3
===================================
 */

/* $r = getEliteLevelFields(7);
$i = 0;
foreach ($r as $k => $v){
    print "$i <br />";
    print_r($v);
    $i++;
} */

/* $q = new TQuery("
select distinct(UserID)
from AccountProperty ap join Account a on ap.AccountID = a.AccountID
where ProviderPropertyID = 11
and Val in (select ValueText
            from TextEliteLevel
            where EliteLevelID = 29)
and a.UserAgentID is null
");
$u = 0;
while (!$q->EOF){
    $q->Next();
    $u++;
}
echo "$u users have rank 0<br />";

$q = new TQuery("
select distinct(UserID)
from AccountProperty ap join Account a on ap.AccountID = a.AccountID
where ProviderPropertyID = 11
and not Val in (select ValueText
                from TextEliteLevel
                where EliteLevelID = 29)
and a.UserAgentID is null
");
$u = 0;
while (!$q->EOF){
    $q->Next();
    $u++;
}
echo "$u users have rank > 0"; */
/*

<body style="padding: 0px; margin: 0px">
<iframe src="https://www.surveymonkey.com/s/Y9CKP6V?c=140594" width="100%" frameborder="0" height="100%"></iframe>
</body>

*/
