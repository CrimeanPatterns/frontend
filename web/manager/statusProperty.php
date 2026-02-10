<?
$schema = 'statusProperty';
require "start.php";
require_once "$sPath/schema/ProviderPhone.php";
drawHeader("statusProperty");
ini_set('memory_limit', '2G');
?>
<?

$updateDays = (isset($_GET['UpdateDaysAgo']))?intval($_GET['UpdateDaysAgo']):7;
$kind = (!isset($_GET['Kind']))?1:intval($_GET['Kind']);
if(isset($_GET['AccountStatus'])){
	$ProviderPropertyID = intval($_GET['ProviderPropertyID']);
	$Val = (isset($_GET['Val'])) ? addslashes($_GET['Val']) : null;
	/*$sql = "
	SELECT
		a.UserID,
		a.AccountID,
		ap.val Status
	FROM Account a
	JOIN AccountProperty ap ON ap.AccountID = a.AccountID
	JOIN EliteLevel el ON el.ProviderID = a.ProviderID AND el.Rank = $Rank
	JOIN TextEliteLevel tel ON el.EliteLevelID = tel.EliteLevelID AND tel.ValueText = ap.Val
	WHERE
		a.ProviderID = $ProviderID
	ORDER BY
		a.UserID
	";*/
	$sql = "
	SELECT 
		a.AccountID, 
		a.UserID
	FROM
		AccountProperty ap 
		JOIN Account a ON ap.AccountID = a.AccountID 
	WHERE 
		ap.ProviderPropertyID = $ProviderPropertyID 
		AND ap.Val = '$Val'
		AND a.State IN(".ACCOUNT_ENABLED.", ".ACCOUNT_DISABLED.")
		AND a.ErrorCode not IN (".ACCOUNT_INVALID_PASSWORD.", ".ACCOUNT_LOCKOUT.", ".ACCOUNT_MISSING_PASSWORD.", ".ACCOUNT_PREVENT_LOCKOUT.")
	";
	$q = new TQuery($sql);
	if($q->EOF)
		echo 'Account Not Found';
	else {
?>
<a href="/manager/statusProperty.php?Kind=<?=$kind?>">Back</a>
<br />
<br />
<table cellpadding="5" cellspacing="0" class="detailsTable">
	<tr>
		<td>#</td>
		<td><strong>UserID</strong></td>
		<td><strong>AccountID</strong></td>
		<td><strong></strong></td>
		<td><strong></strong></td>
		<td><strong></strong></td>
		<td><strong>v</strong></td>
	</tr>
<?
		$i = 0;
		while(!$q->EOF){
			$i++;
?>

	<tr>
		<td><?=$i?></td>
		<td><?=$q->Fields['UserID']?></td>
		<td><?=$q->Fields['AccountID']?></td>
		<td><a href="/manager/impersonate?UserID=<?=$q->Fields['UserID']?>&AutoSubmit&AwPlus=1" target="_blank">Impersonate</a></td>
		<td><a href="/manager/passwordVault/requestPassword.php?ID=<?=$q->Fields['AccountID']?>&AutoSubmit" target="_blank">Request Pass</a></td>
		<td><a href="/manager/loyalty/logs?AccountID=<?=$q->Fields['AccountID']?>&Partner=awardwallet" target="_blank">logs</a></td>
        <td><input type=checkbox></td>
	</tr>
<?
			$q->Next();
		}
?>
</table>
<?
	}
	exit;
}

$q = new TQuery("SELECT COUNT(*) AS Cnt FROM Provider WHERE State = 1");
$AllProgram = (!$q->EOF) ? $q->Fields['Cnt'] : 0;
$q = new TQuery("SELECT COUNT(*) AS Cnt FROM Provider WHERE State = 1 AND EliteLevelsCount > 0");
$wStatus = (!$q->EOF) ? $q->Fields['Cnt'] : 0;
$sql = "
SELECT COUNT(distinct p.ProviderID) AS Cnt
FROM Provider p
JOIN ProviderPhone ph on ph.ProviderID = p.ProviderID
WHERE p.State = 1";
$q = new TQuery($sql);
$wPhone = (!$q->EOF) ? $q->Fields['Cnt'] : 0;
$sql = "
SELECT COUNT(distinct p.ProviderID) AS Cnt
FROM Provider p
JOIN ProviderPhone ph on ph.ProviderID = p.ProviderID
WHERE p.State = 1
AND p.EliteLevelsCount > 0
";
$q = new TQuery($sql);
$wBoth = (!$q->EOF) ? $q->Fields['Cnt'] : 0;
?>
<table cellpadding="5" cellspacing="0" class="detailsTable">
	<tr>
		<td>All Programs</td>
		<td>w/Status</td>
		<td>w/Phone</td>
		<td>w/Status && Phone</td>
	</tr>
	<tr>
		<td><?=$AllProgram?></td>
		<td><?=$wStatus?></td>
		<td><?=$wPhone?></td>
		<td><?=$wBoth?></td>
	</tr>
</table>
<br />
<style>
.cat {position:relative; bottom:-1px;}
	.cat a {display:block; padding:5px 10px; float: left; color:#000; text-decoration:none; }
		.cat a.sel {border:1px solid #ccc; border-width: 1px 1px 0; background: #eee;}
.cb {clear:both;}
.wLight {background:#FDBAAA;}
.wLightBlue {background:#b3d9ff;}
</style>
<div class="cat">
	<a <?=($kind == 1)?'class="sel"':''?> href="statusProperty.php?Kind=1">Airlines</a>
	<a <?=($kind == 2)?'class="sel"':''?> href="statusProperty.php?Kind=2">Hotels</a>
	<a <?=($kind == 3)?'class="sel"':''?> href="statusProperty.php?Kind=3">Rentals</a>
	<a <?=($kind == 4)?'class="sel"':''?> href="statusProperty.php?Kind=4">Trains</a>
	<a <?=($kind == 10)?'class="sel"':''?> href="statusProperty.php?Kind=10">Cruises</a>
	<a <?=($kind == 6)?'class="sel"':''?> href="statusProperty.php?Kind=6">Credit Cards</a>
	<a <?=($kind == 7)?'class="sel"':''?> href="statusProperty.php?Kind=7">Shopping</a>
	<a <?=($kind == 8)?'class="sel"':''?> href="statusProperty.php?Kind=8">Dining</a>
	<a <?=($kind == 9)?'class="sel"':''?> href="statusProperty.php?Kind=9">Surveys</a>
	<a <?=($kind == 5)?'class="sel"':''?> href="statusProperty.php?Kind=5">Other</a>
	<div class="cb"></div>
</div>
<div style="padding: 8px; border: 1px solid #ccc; background: #eee;">
<table cellpadding="5" cellspacing="0" class="detailsTable" style="background: #fff;">
	<tr>
		<td>#</td>
		<td>Loyalty Program</td>
		<td>EliteLevelsCount</td>
		<td>Status Values</td>
		<td>Sample Account</td>
		<td>Rank</td>
		<td>Elitism</td>
		<td>Support Phone</td>
	</tr>
<?

$providersRS = new TQuery("
	SELECT 	
        p.ProviderID,
		p.EliteLevelsCount,
        p.HasSupportPhones,
        p.DisplayName,
        p.Name,
	    p.Accounts,
	    p.Kind,
        p.State
	FROM 
		Provider p
	WHERE
		p.State >= " . PROVIDER_ENABLED . "
		AND p.Kind = $kind
	GROUP BY 
		p.ProviderID, p.EliteLevelsCount, p.HasSupportPhones, p.DisplayName, p.Name, p.State, p.Accounts, p.Kind
	ORDER BY
		p.Accounts DESC, p.Name
");
$light = " class='wLight' ";
$lightBlue = " class='wLightBlue' ";
$i = 0;
$connection = getSymfonyContainer()->get('doctrine')->getConnection();
while(!$providersRS->EOF){
	set_time_limit(120);
	$i++;
	$EliteLevelsCount = (isset($providersRS->Fields["EliteLevelsCount"]) && $providersRS->Fields["EliteLevelsCount"] != "") ?$providersRS->Fields["EliteLevelsCount"] : 0;
	$phoneLink = TProviderPhoneSchema::getPhonesLink($providersRS->Fields['ProviderID'], null);
	echo "
	<tr>
		<td>{$i}</td>
		<td>
			<a href='/manager/edit.php?ID={$providersRS->Fields["ProviderID"]}&Schema=Provider'><strong>" . $providersRS->Fields["DisplayName"] . "</strong></a>
		</td>
		<td ".(($EliteLevelsCount == 0)?$light:'').">
			<a href='/manager/list.php?ProviderID={$providersRS->Fields["ProviderID"]}&Schema=EliteLevel'>" . $EliteLevelsCount . "</a>
		</td>
		<td colspan=\"4\">&nbsp;</td>
		<td ".((strstr($phoneLink,">None<") && $providersRS->Fields['HasSupportPhones'])?$lightBlue:'')." >".$phoneLink." <a href='/manager/edit.php?ID={$providersRS->Fields['ProviderID']}&Schema=Provider'>Edit</a></td>
	</tr>";
	$statusPropertyRS = new TQuery("SELECT * FROM ProviderProperty WHERE Kind = 3 AND ProviderID = " . $providersRS->Fields["ProviderID"] );
	if(!$statusPropertyRS->EOF){
		$uniqueValuesRs = new TQuery("SELECT ap.Val, max(a.AccountID) as AccountID FROM AccountProperty ap
		join Account a on ap.AccountID = a.AccountID
		WHERE ap.ProviderPropertyID = " . $statusPropertyRS->Fields["ProviderPropertyID"]."
		and a.UpdateDate > adddate(now(), -{$updateDays})
		group by ap.Val");
		$output = array();
		while(!$uniqueValuesRs->EOF){
			set_time_limit(120);
            $eliteStatusStmt = $connection->executeQuery("
                SELECT
                    el.Rank,
                    el.EliteLevelID,
                    el.NoElitePhone
                FROM EliteLevel el
                JOIN TextEliteLevel tel ON
                    el.EliteLevelID = tel.EliteLevelID
                WHERE
                    el.ProviderID = ? AND
                    tel.ValueText = ?",
            [$providersRS->Fields["ProviderID"], $uniqueValuesRs->Fields["Val"]],
            [\PDO::PARAM_INT, \PDO::PARAM_STR]);

            $eliteStatus = $eliteStatusStmt->fetch(\PDO::FETCH_ASSOC);

			$elitism = (!$eliteStatusStmt->rowCount() || $EliteLevelsCount == 0) ? 0 : round($eliteStatus['Rank'] / $EliteLevelsCount, 2);
            $phoneLink = null;
            if ($eliteStatus !== false) {
                if ($eliteStatus['NoElitePhone'] == 1) {
                    $phoneLink = 'No Dedicated Number';
                } else {
                    $phoneLink = TProviderPhoneSchema::getPhonesLink($providersRS->Fields["ProviderID"], $eliteStatus['EliteLevelID']);
                }
            }
			$output[] = [
				'ID' => $eliteStatus !== false ? $eliteStatus["EliteLevelID"] : null,
				'Status' => $uniqueValuesRs->Fields["Val"],
				'Phone' =>  $phoneLink,
				'Links'	 => $uniqueValuesRs->Fields['AccountID'],
				'Rank'	 => $eliteStatus !== false ? $eliteStatus['Rank'] : null,
				'Elitism'=> $elitism,
			];
			$uniqueValuesRs->Next();
		}
		# Sort
		usort($output, function($a, $b){
			if ($a['Rank'] == $b['Rank'])
				return 0;
			return ($a['Rank'] < $b['Rank']) ? -1 : 1;
		});
		foreach ($output as $val) {
			echo "
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>
                        <a href='/manager/statusProperty.php?Kind=$kind&AccountStatus=1&ProviderPropertyID={$statusPropertyRS->Fields["ProviderPropertyID"]}&Val={$val["Status"]}'>
                            " . $val["Status"] . "
                        </a>
                    </td>
                    <td>" . $val["Links"] . "</td>
                    <td ".($val["Rank"] == ''?$light:'')." >
                        " . $val["Rank"] . "
                    </td>
                    <td>" . $val["Elitism"] . "</td>
                    <td ".((strstr($val["Phone"],">None<") && intval($val["Rank"]) > 0)?$light:'')." >
                        " . (
                            !is_null($val["Phone"])
                                ? $val["Phone"] . " <a href='/manager/list.php?ID={$val["ID"]}&ProviderID={$providersRS->Fields["ProviderID"]}&Schema=EliteLevel'>Edit</a>"
                                : ''
                            )
                        . "
                    </td>
                </tr>";
		}
	}
	$providersRS->Next();
}
?>
</table>
</div>
<?
drawFooter();
?>
