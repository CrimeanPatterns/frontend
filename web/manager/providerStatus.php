<?php

use AwardWallet\MainBundle\Entity\Account;

$schema = "providerStatus";

require "start.php";
drawHeader('LP Status', '');
global $Interface;
// Box for asking UserIDs
$Interface->drawAddUserIDsBox();

$container = getSymfonyContainer();
/** @var \AwardWallet\MainBundle\Service\WatchdogKillListProvider $watchdogKillListProvider */
$watchdogKillListProvider = $container->get(\AwardWallet\MainBundle\Service\WatchdogKillListProvider::class);

$response = $watchdogKillListProvider->search();
/** @var \Psr\Log\LoggerInterface $logger */
// $logger = $container->get('logger');
// if( is_array($response) )
//    $logger->warning("Data on providerStatus page from ES", [$response]);

?>
<link rel="stylesheet" type="text/css" href="/../design/mainStyle.css" />
<link rel="stylesheet" type="text/css" href="/../design/adminStyle.css" />
<div id="fader" style="opacity: 0.75; z-index: 10; position: absolute; top: 0; left: 0; right: 0; background-color: white;" onclick="cancelPopup()"></div>
<script type="text/javascript">
	function setAssignee(providerId, newUserId, cell, providerName){
		cell = $(cell);
		var oldUserId = cell.find('span.name').attr('userId');
		$.ajax({
			url: "/manager/setAssignee.php",
			type: "POST",
			data: {
				providerId: providerId,
				newUserId: newUserId,
				oldUserId: oldUserId
			},
			success: function(response){
				if(response != "OK"){
					window.alert(response);
					return false;
				}
				var newName = '';
				var newState = cell.find('a.clear').attr('stateprev');
				'' == newState || undefined == newState ? newState = 'Enabled' : null;
				var timeResponse = true;
				if(newUserId != ''){
					newName = '<br/>(Assignee: <?php echo $_SESSION['Login']; ?>)';
					newState = 'Fixing';
					var timeResponse = false;
                    cell.find('a.clear').attr('stateprev', $(cell).parent().find('td.state span.provider-state').text());
                    cell.parent('tr').attr('style', 'background-color: #eaeaea;');
				}
				cell.find('span.name').html(newName).attr('userId', newUserId);
				if (!timeResponse){
					cell.parent('tr').find('td.timeResponse').html('').attr('style', '');
				}
				updateAssigneeCell(cell);
				cell.siblings('td.state').children('span.provider-state').html(newState);
                if(newUserId == ''){
					changeProviderState('openDialog', 'fixed', providerId, cell, providerName);
				}
			}
		});
	}

    const FIX_DATE_CLIENT_SIDE = 'clientSideLastFixDate';
    const FIX_DATE_SERVER_SIDE = 'serverSideLastFixDate';

    function setLastFixDate(providerId, type) {
        $.ajax({
            type: 'POST',
            url: '/manager/loyalty/last-fix-date',
            data: JSON.stringify({providerId: providerId, fieldName: type}),
            dataType: 'json',
            contentType: 'application/json',
            success: function (response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    }

	function updateAssigneeCell(cell){
		if(cell.find('span.name').attr('userId') == ''){
			cell.find('a.set').show();
			cell.find('a.clear').hide();
            cell.find('span.name').attr('style', 'display: none');
		}
		else{
			cell.find('a.set').hide();
			cell.find('a.clear').show();
            cell.find('span.name').attr('style', 'color:#9C9C9C');
            cell.parent('tr').attr('style', 'background-color: #eaeaea;');
		}
	}

    function dropDownInit(wrapper) {
        wrapper.on('click', '.dropdown-toggle', function (event) {
            event.preventDefault();
            $(this).next('.dropdown-menu').toggle();
        });

        $(document).on('click', function (event) {
            var target = event.target;
            if (!$(target).is('.dropdown-toggle') && !$(target).parents().is('.dropdown-toggle')) {
                wrapper.children('.dropdown-menu').hide();
            }
        });
    }

	$(document).ready(function(){
		$('td.assignee').each(function(index, cell){
			updateAssigneeCell($(cell));
		});

        // reload page every 5 min
        setTimeout(function() {
            window.location.reload();
        }, 1000 * 60 * 5);

        dropDownInit($('.dropdown-wrapper'));
	});

</script>

<style type="text/css">
	.fixed{
		font-family: Andale Mono, monospace;
	}
    .descriptionProviderStatus {padding:20px 50px 20px 0;}
        .descriptionProviderStatus table {border-collapse: collapse;}
            .descriptionProviderStatus table td {border:1px solid #ccc; padding:10px;}
            .descriptionProviderStatus table td.titleProvStat {font-weight:bold;}
    i[class^="icon-"], i[class*=" icon-"] {
        display: inline-block;
        text-align: center;
        text-decoration: none;
        vertical-align: middle;
        background-image: url(../assets/awardwalletnewdesign/img/sprite.png?v=2);
        background-repeat: no-repeat;
    }
    .icon-settings {
        width: 19px;
        height: 14px;
        background-position: -17px -43px;
    }
    .icon-blue-info {
        width: 13px;
        height: 13px;
        background-position: -111px -99px;
    }
    .lastHour {
        color: #9C9C9C;
    }
    @font-face {
        font-family: rub-arial-regular;
        src: url(data:font/woff;charset=utf-8;base64,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)
    }
    
    div#askUserIDBox {
        min-width: 200px !important;
        width: 317px !important;   
    }
    #askUserIDBox .ui-button .ui-button-text {display: none}
    #askUserIDBox input {
        width: 280px !important;
    }
    #askUserIDBox .btn-blue {
        cursor:pointer;
        padding: 5px 8px !important;
    }
    #askUserIDBox a[href="#"] {
        display: inline-block;
        margin: 5px 0 0 25px;
    }
</style>

<div class='SPprops' style="float: right;">
    <span class='h' title='Better simpler than clever'>Remember: </span>«<b><i>Better simpler than clever</i></b>»
    &nbsp;&nbsp;&nbsp;
</div>
<h4>Severity status for last 4h & late Problems for last month</h4>

<div class='SPprops'>
<?php
// Properties for Severity
$q = new TQuery("
SELECT
	SUM(IF(Tier = 1,1,0)) T1,
	SUM(IF(Tier = 2,1,0)) T2,
	SUM(IF(Tier = 3,1,0)) T3,
	SUM(IF(Severity = 3,1,0)) S3,
	SUM(IF(Severity = 2,1,0)) S2,
	SUM(IF(Severity = 1,1,0)) S1,
	SUM(
		CASE WHEN
			se.NewTier = 1 AND (se.NewSeverity = 1 OR se.NewSeverity = 2)
			OR
			se.NewTier = 2 AND se.NewSeverity = 1
		THEN 1 ELSE 0 END) R1,
	SUM(
		CASE WHEN
			se.NewTier = 3 AND (se.NewSeverity = 1 OR se.NewSeverity = 2)
			OR
			se.NewTier = 1 AND se.NewSeverity = 3
			OR
			se.NewTier = 2 AND se.NewSeverity = 2
		THEN 1 ELSE 0 END) R2,
	SUM(
		CASE WHEN
			se.NewTier = 3 AND (se.NewSeverity = 2 OR se.NewSeverity = 3)
			OR
			se.NewTier = 2 AND se.NewSeverity = 3
		THEN 1 ELSE 0 END) R3
FROM
	Provider p
LEFT JOIN SlaEvent se ON p.RSlaEventID = se.SlaEventID
");

while (!$q->EOF) {
    ?>
	<span class='h' title='first 20%'>T1:</span><span class='val'><?php echo $q->Fields['T1']; ?></span>&nbsp; | &nbsp;
	<span class='h' title='next 30% after first 20%'>T2:</span><span class='val'><?php echo $q->Fields['T2']; ?></span>&nbsp; | &nbsp;
	<span class='h' title='bottom 50%'>T3:</span><span class='val'><?php echo $q->Fields['T3']; ?></span>&nbsp; | &nbsp;
	<span class='h' title='50% <= Severity, Unknown Errors > 30'>S1:</span><span class='val'><?php echo $q->Fields['S1']; ?></span>&nbsp; | &nbsp;
	<span class='h' title='10% <= Severity < 50%, Unknown Errors > 30'>S2:</span><span class='val'><?php echo $q->Fields['S2']; ?></span>&nbsp; | &nbsp;
	<span class='h' title='3% <= Severity < 10%, Unknown Errors > 30'>S3:</span><span class='val'><?php echo $q->Fields['S3']; ?></span>&nbsp; | &nbsp;
	<span class='h' title='24 hours, 12 man/hours'>R1:</span><span class='val'><?php echo $q->Fields['R1']; ?></span>&nbsp; | &nbsp;
	<span class='h' title='48 hours, 6 man/hours'>R2:</span><span class='val'><?php echo $q->Fields['R2']; ?></span>&nbsp; | &nbsp;
	<span class='h' title='72 hours, 3 man/hours'>R3:</span><span class='val'><?php echo $q->Fields['R3']; ?></span>&nbsp; | &nbsp;
<?php
$q->Next();
}
unset($q);
// Late Problems
$q = new TQuery("
SELECT
	SUM(CASE WHEN Event = 'late' AND EventDate > ADDDATE(NOW(), INTERVAL -1 MONTH) THEN 1 ELSE 0 END) LateProblemsCount,
	SUM(CASE WHEN Event = 'start' AND EventDate > ADDDATE(NOW(), INTERVAL -1 MONTH) THEN 1 ELSE 0 END) EvetsProblemCount
FROM
	SlaEvent
");

while (!$q->EOF) {
    ?>
	<span class='h' title='Count of no response time'>Late Problems:</span>
	<span class='val'><?php echo $q->Fields['LateProblemsCount']; ?></span>&nbsp; | &nbsp;

	<span class='h' title='Percent Late Problems for all response time'>% Late:</span>
	<span class='val'>
		<?php
        if ($q->Fields['EvetsProblemCount'] == 0) {
            echo 0;
        } else {
            echo round((intval($q->Fields['LateProblemsCount']) / intval($q->Fields['EvetsProblemCount'])) * 100, 2);
        } ?>%
	</span>&nbsp; | &nbsp;
<?php
$q->Next();
}
unset($q);

// order for providers
$order = [['UnkErrors', true], ['SuccessRate', false]];

if (isset($_GET['order'])) {
    $ord = $_GET['order'];

    switch ($_GET['order']) {
        case 'tier': $order = [['WSDL', true], ['Tier', false], ['Severity',  true], ['TotalCount', true]];

            break;

        case 'success': $order = [['SuccessRate', false]];

            break;

        case 'responseTime': $order = [['WSDL', true], ['ResponseTime', true]];

            break;

        case 'accounts': $order = [['Accounts', true]];

            break;
    }
} else {
    $ord = false;
}
$providerSql = '';

if (isset($_GET['ID'])) {
    $providerId = intval($_GET['ID']);
    $providerSql = ' AND a.ProviderID = ' . $providerId;
}

$browser = new HttpBrowser("none", new CurlDriver());

$browser->GetURL("https://rucaptcha.com/res.php?key=" . getSymfonyContainer()->getParameter("parsing_constants")["rucaptcha_key"] . "&action=getbalance", [], 15);
$rucaptchaBalance = round($browser->Response['body'], 2) . " RUB";

$browser->GetURL("https://anti-captcha.com/res.php?key=" . getSymfonyContainer()->getParameter("parsing_constants")["antigate_key"] . "&action=getbalance", [], 15);
$antiCaptchaBalance = round($browser->Response['body'], 2);
?>
    <div style="float: right;">
        <span class='h' title='anti-captcha.com balance'>anti-captcha.com:</span>
        <span class='val'>$ <?php echo $antiCaptchaBalance; ?></span>
        &nbsp;
        <span class='h' title='ruCaptcha.com balance'>ruCaptcha.com:</span>
        <span class='val'><span style="font-family: rub-arial-regular;">Р</span> <?php echo number_format((float) $rucaptchaBalance, 2); ?></span>
        &nbsp;&nbsp;&nbsp;
    </div>
</div>

<div style="position: absolute; top: 110px; right: 29px;">
<!--    <a target="_blank" style="text-decoration: none; color: #999;" title="See all processes killed by watchdog" href="https://kibana.awardwallet.com/app/kibana#/visualize/edit/Loyalty:-Killed-by-watchdog?_g=(refreshInterval:(pause:!t,value:0),time:(from:now-24h,mode:quick,to:now))&_a=(filters:!(),linked:!f,query:(language:lucene,query:(query_string:(analyze_wildcard:!t,default_field:'*',query:'%22Process%20killed%20by%20watchdog%22'))),uiState:(vis:(legendOpen:!t)),vis:(aggs:!((enabled:!t,id:'1',params:(customLabel:'2'),schema:metric,type:count),(enabled:!t,id:'2',params:(field:context.provider,missingBucket:!f,missingBucketLabel:Missing,order:desc,orderBy:'1',otherBucket:!f,otherBucketLabel:Other,size:20),schema:segment,type:terms)),params:(addLegend:!t,addTooltip:!t,isDonut:!f,labels:(last_level:!t,show:!f,truncate:100,values:!t),legendPosition:right,shareYAxis:!t,type:pie),title:'Loyalty:%20Killed%20by%20watchdog',type:pie))"><i>Killed by watchdog</i></a>-->
    <a target="_blank" style="text-decoration: none; color: #999;" title="See all captcha expenses" href="https://kibana.awardwallet.com/goto/be4d110a47b240f79ddfbd0ea95b678c"><i>Captcha, finances</i></a>
    &nbsp;/&nbsp;
    <a target="_blank" style="text-decoration: none; color: #999;" title="See how works IsLoggedIn" href="https://kibana.awardwallet.com/app/kibana#/visualize/edit/c66e3d70-6913-11ea-8601-8313683eea38"><i>IsLoggedIn statistic</i></a>
    /&nbsp;
    <a target="_blank" style="text-decoration: none; color: #999;" title="See all processes killed by watchdog" href="https://kibana.awardwallet.com/goto/aa86c18a8095b577bff6e0d1b867ec03"><i>Killed by watchdog</i></a>
</div>
<h4>Status of LP for last 24h&nbsp;<a target="_blank" style="text-decoration: none; color: #999;" title="All accounts with UE older than 24h" href="/manager/account-with-ue"><i>&nbsp;All accounts with UE older than 24h</i></a></h4>
<table cellpadding='5' cellspacing='0' class="detailsTable providerStatus" id="mainTable">
    <tr>
        <td style="font-weight: bold;">Code</td>
		<td style="font-weight: bold;">Kind</td>
        <td style="font-weight: bold;">Provider</td>
        <td><a style="font-weight: bold;" href="?order=accounts">Accounts</a></td>
		<td style="font-weight: bold;">State</td>
		<td style="font-weight: bold;">Features</td>
		<td style="font-weight: bold; <?php echo ($ord == 'tier') ? 'background:#eee' : ''; ?>" rel="Tier"><a class='header' href='/manager/providerStatus.php?order=tier'>Tier</a></td>
        <td style="font-weight: bold;" rel="Checked">Checked</td>
        <td style="font-weight: bold;" rel="Errors">Errors</td>
	    <td style="font-weight: bold;" rel="UnknownErrors">Unknown<br/>errors</td>
        <td style="font-weight: bold; <?php echo ($ord == 'success') ? 'background:#eee' : ''; ?>" rel="Success"><a class='header' href='/manager/providerStatus.php?order=success'>Success %</a></td>
		<td style="font-weight: bold;" rel="Severity">Severity</td>
		<td style="font-weight: bold;" rel="SeverityPercent">Severity %</td>
		<td style="font-weight: bold; <?php echo ($ord == 'responseTime') ? 'background:#eee' : ''; ?>" rel="ResponseTime"><a class='header' href='/manager/providerStatus.php?order=responseTime'>Response<br/>time</a></td>
		<td style="font-weight: bold;">Admin links</td>
    <tr>
	<?php
    session_write_close();

$providers = [];

foreach (new TQuery("select 
        p.ProviderID, p.DisplayName, p.State, p.Code, p.Kind, p.InternalNote, p.WSDL, p.Assignee,
        p.AutoLogin, p.DeepLinking, p.CanCheckBalance, p.Corporate, p.CanCheckExpiration ,p.CanCheckItinerary,
        p.CanCheckConfirmation, p.Tier, p.Severity, p.ResponseTime, p.Warning, u.Login as AssigneeLogin, p.StatePrev,
        p.Accounts, p.ServerSideLastFixDate
    from 
        Provider p 
        left outer join Usr u on p.Assignee = u.UserID
    where 
        p.State >= " . PROVIDER_ENABLED . " and p.State <> " . PROVIDER_COLLECTING_ACCOUNTS . " and p.CanCheck = 1") as $row) {
    $providers[$row['ProviderID']] = $row;
}

// get loyalty stats
$successRateCalculator = getSymfonyContainer()->get(\AwardWallet\MainBundle\Loyalty\SuccessRate\SuccessRateCalculator::class);
$logger = getSymfonyContainer()->get("logger");

try {
    $loyaltySuccessRate = $successRateCalculator->getSuccessRate(getServerLastFixDatesArray($providers));
} catch (\Exception $e) {
    $logger->critical("failed to get loyalty stats: " . $e->getMessage());
    $loyaltySuccessRate = [];
}

global $Connection;
$killedAccounts = [];
$killedSql = "";
$dateUtc = strtotime(gmdate('d M Y H:i:s'));
$lastHourDate = strtotime('-1 hour', $dateUtc);
$killedAccountsForLastHour = [];

foreach ($response as $provId => $accounts) {
    foreach ($accounts as $account) {
        $killedAccounts[] = $account['AccountID'];

        if (strtotime($account['UpdateDate']) >= $lastHourDate) {
            if (!isset($killedAccountsForLastHour[$provId])) {
                $killedAccountsForLastHour[$provId] = 1;
            } else {
                $killedAccountsForLastHour[$provId]++;
            }
        }
    }
}

if (count($killedAccounts) > 0) {
    $killedSql = "and a.AccountID not in(" . implode(", ", $killedAccounts) . ")";
}
// get providers
$sql = "SELECT
        a.ProviderID,
		count(a.AccountID) as TotalCount,
		sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " then 1 else 0 end) AS UnkErrors,
		sum(case when a.CheckedBy = " . Account::CHECKED_BY_USER_BROWSER_EXTENSION . " and a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " and (p.ClientSideLastFixDate IS NULL OR a.UpdateDate > p.ClientSideLastFixDate) then 1 else 0 end) AS ClientSideUnkErrors,
		sum(case when a.CheckedBy <> " . Account::CHECKED_BY_USER_BROWSER_EXTENSION . " and a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " and (p.ServerSideLastFixDate IS NULL OR a.UpdateDate > p.ServerSideLastFixDate) then 1 else 0 end) AS ServerSideUnkErrors,
		sum(case when a.AccountID is not null and a.UpdateDate > adddate(now(), interval -4 hour) then 1 else 0 end) AS LastChecked,
		sum(case when a.AccountID is not null and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourChecked,
		sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " and a.UpdateDate > adddate(now(), interval -4 hour) then 1 else 0 end) AS LastUnkErrors,
		sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourUnkErrors,
		sum(case when a.ErrorCode = " . ACCOUNT_CHECKED . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourSuccessfullyChecked,
		sum(case when a.ErrorCode = " . ACCOUNT_PROVIDER_ERROR . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourProviderErrors,
		sum(case when a.ErrorCode = " . ACCOUNT_INVALID_PASSWORD . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourInvalidPassword,
		sum(case when a.ErrorCode in (" . ACCOUNT_LOCKOUT . ", " . ACCOUNT_PREVENT_LOCKOUT . ") and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourLockouts,
		sum(case when a.ErrorCode = " . ACCOUNT_WARNING . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourWarnings,
		sum(case when a.ErrorCode = " . ACCOUNT_QUESTION . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourQuestions,
		sum(case when a.ErrorCode <> " . ACCOUNT_CHECKED . " then 1 else 0 end) AS Errors,
		sum(case when a.ErrorCode = " . ACCOUNT_CHECKED . " then 1 else 0 end) AS SuccessfullyChecked,
		sum(case when a.ErrorCode = " . ACCOUNT_PROVIDER_ERROR . " then 1 else 0 end) AS ProviderErrors,
		sum(case when a.ErrorCode = " . ACCOUNT_INVALID_PASSWORD . " then 1 else 0 end) AS InvalidPassword,
		sum(case when a.ErrorCode in (" . ACCOUNT_LOCKOUT . ", " . ACCOUNT_PREVENT_LOCKOUT . ") then 1 else 0 end) AS Lockouts,
		sum(case when a.ErrorCode = " . ACCOUNT_WARNING . " then 1 else 0 end) AS Warnings,
		sum(case when a.ErrorCode = " . ACCOUNT_QUESTION . " then 1 else 0 end) AS Questions,
		sum(case when a.ErrorCode <> " . ACCOUNT_CHECKED . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourErrors,
		round(sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " then 1 else 0 end)/count(a.AccountID)*100, 2) AS ErrorRate,
		round(sum(case when a.ErrorCode = " . ACCOUNT_CHECKED . " then 1 else 0 end)/count(a.AccountID)*100, 2) AS SuccessRate,
		SUM(IF(a.CheckedBy = " . Account::CHECKED_BY_USER_BROWSER_EXTENSION . " AND (p.ClientSideLastFixDate IS NULL OR a.UpdateDate > p.ClientSideLastFixDate), 1, 0)) AS ClientTotalChecked,
		SUM(IF(a.CheckedBy <> " . Account::CHECKED_BY_USER_BROWSER_EXTENSION . " AND (p.ServerSideLastFixDate IS NULL OR a.UpdateDate > p.ServerSideLastFixDate), 1, 0)) AS ServerTotalChecked,
		round(SUM(IF(a.CheckedBy = " . Account::CHECKED_BY_USER_BROWSER_EXTENSION . " AND a.ErrorCode = " . ACCOUNT_CHECKED . " AND (p.ClientSideLastFixDate IS NULL OR a.UpdateDate > p.ClientSideLastFixDate), 1, 0)) / SUM(IF(a.CheckedBy = " . Account::CHECKED_BY_USER_BROWSER_EXTENSION . " AND (p.ClientSideLastFixDate IS NULL OR a.UpdateDate > p.ClientSideLastFixDate), 1, 0))*100, 2) AS SuccessRateBrowser,
		/* round(SUM(IF(a.CheckedBy <> " . Account::CHECKED_BY_USER_BROWSER_EXTENSION . " AND a.ErrorCode = " . ACCOUNT_CHECKED . " AND (p.ServerSideLastFixDate IS NULL OR a.UpdateDate > p.ServerSideLastFixDate), 1, 0)) / SUM(IF(a.CheckedBy <> " . Account::CHECKED_BY_USER_BROWSER_EXTENSION . " AND (p.ServerSideLastFixDate IS NULL OR a.UpdateDate > p.ServerSideLastFixDate), 1, 0))*100, 2) AS SuccessRateServer, */
		sum(case when a.CheckedBy = " . \AwardWallet\MainBundle\Entity\Account::CHECKED_BY_USER_BROWSER_EXTENSION . " and a.ErrorCode = " . ACCOUNT_CHECKED . " then 1 else 0 end) AS SuccessCountBrowser,
		sum(case when a.CheckedBy = " . \AwardWallet\MainBundle\Entity\Account::CHECKED_BY_USER_BROWSER_EXTENSION . " then 1 else 0 end) AS TotalCountBrowser,
		sum(case when a.CheckedBy <> " . \AwardWallet\MainBundle\Entity\Account::CHECKED_BY_USER_BROWSER_EXTENSION . " then 1 else 0 end) AS TotalCountServer,
		p.Accounts AS Popularity,
		p.ManualUpdate
	FROM
	 	Account a
    JOIN
        Provider p ON a.ProviderID = p.ProviderID
	WHERE
        a.ProviderID in (" . implode(", ", array_keys($providers)) . ")		
		and a.UpdateDate > DATE_SUB(NOW(), INTERVAL 1 DAY)
		and a.State not in (" . implode(', ', [ACCOUNT_PENDING, ACCOUNT_IGNORED]) . ")
		and a.CheckedBy <> " . \AwardWallet\MainBundle\Entity\Account::CHECKED_BY_EMAIL . "
		{$killedSql}
		{$providerSql}
	GROUP BY
		a.ProviderID";

if (ConfigValue(CONFIG_SITE_STATE) === SITE_STATE_DEBUG) {
    $sql = str_replace(['1 hour', '4 hour', '1 DAY'], ['5000 hour', '5000 hour', '180 DAY'], $sql);
}

$q = new TQuery($sql); // UnkErrors desc, SuccessRate //p.wsdl desc, Tier, Severity DESC, TotalCount

session_start();

$rows = [];
$slaPos = 0;

while (!$q->EOF) {
    $q->Fields = array_merge($q->Fields, $providers[$q->Fields["ProviderID"]]);

    /*if($q->Fields['WSDL'] == '1'){
        if($q->Fields['LastChecked'] > 0)
            $q->Fields['Severity'] = round($q->Fields['LastUnkErrors'] / $q->Fields['LastChecked'] * 100);
        $slaPos++;
    }*/
    if (isset($response[$q->Fields['ProviderID']]) && count($response[$q->Fields['ProviderID']]) > 0) {
        $q->Fields['UnkErrors'] += count($response[$q->Fields['ProviderID']]);
    }

    $q->Fields['SeverityS'] = setSeverityS($q->Fields['Severity']);
    $q->Fields["Data"] = getProviderData($q->Fields);
    $q->Fields["Accounts"] = $providers[$q->Fields["ProviderID"]]["Accounts"];

    /*foreach(array("Severity", "Tier") as $key)
        if(!isset($q->Fields[$key]))
            $q->Fields[$key] = "";*/

    $loyaltyStat = $loyaltySuccessRate[$providers[$q->Fields['ProviderID']]['Code']] ?? null;

    if ($loyaltyStat) {
        $q->Fields['TotalCount'] = $loyaltyStat->getErrorsCount() + $loyaltyStat->getSuccessCount() + $q->Fields['TotalCountBrowser'];
        $q->Fields['SuccessRate'] = round(($loyaltyStat->getSuccessCount() + $q->Fields['SuccessCountBrowser']) / $q->Fields['TotalCount'] * 100);
    }

    $q->Fields['ClientFailureRate'] = 100 - $q->Fields['SuccessRateBrowser'];

    if ($q->Fields["TotalCount"] > 0) {
        $rows[] = $q->Fields;
    }

    $q->Next();
}

usort($rows, function ($a, $b) use ($order) {
    foreach ($order as $sort) {
        $result = floatval($a[$sort[0]]) - floatval($b[$sort[0]]);

        if ($sort[1]) {
            $result = $result * -1;
        }

        if (abs($result) >= 0.01) {
            return $result > 0 ? 1 : -1;
        }
    }

    return 0;
});

// output
function getDetailedSuccess(?string $successRateBrowser, ?float $successRateServer, bool $wsdl): string
{
    $result = [];

    if ($successRateBrowser !== null) {
        $result[] = '<div>Ext: ' . $successRateBrowser . '%</div>';
    }

    if ($successRateServer !== null) {
        $msg = 'Srv: ' . $successRateServer . '%';

        if ($successRateServer <= 5 && $wsdl) {
            $msg = "<div title='WSDL is On but Server sucess rate is lower than or equal to 5%' style=\"background-color: #fcb103; color: black;\">{$msg}</div>";
        } elseif ($successRateServer >= 50 && !$wsdl) {
            $msg = "<div title='WSDL is Off but Server sucess rate is greater than or equal to 50%' style=\"background-color: #A0FBA0FF; color: black;\">{$msg}</div>";
        } else {
            $msg = '<div>' . $msg . '</div>';
        }

        $result[] = $msg;
    }

    if (count($result) > 0) {
        return "<div style='color: gray; font-size: 80%;'>" . implode("\n", $result) . "</div>";
    } else {
        return "";
    }
}

// output
function getTotalCountDetails(int $totalCountBrowser, int $totalCountServer): string
{
    $result = [];

    if ($totalCountBrowser !== 0) {
        $result[] = 'Ext: ' . $totalCountBrowser;
    }

    if ($totalCountServer !== 0) {
        $result[] = 'Srv: ' . $totalCountServer;
    }

    if (count($result) > 0) {
        return "<div style='color: gray; font-size: 80%;'>" . implode("<br>", $result) . "</div>";
    } else {
        return "";
    }
}

function getServerLastFixDatesArray(array $providers): array
{
    $result = [];

    foreach ($providers as $provider) {
        if ($provider['ServerSideLastFixDate'] === null) {
            continue;
        }

        $result[$provider['Code']] = $provider['ServerSideLastFixDate'];
    }

    return $result;
}

foreach ($rows as $row) {
    $bgColor = 'white';
    $rsStyle = '';

    if ($row['SuccessRate'] < 80) {
        $bgColor = '#ffffcc';
    }

    if ($row['SuccessRate'] < 30) {
        $bgColor = '#ffdddd';
    }

    $styles = "background-color: {$bgColor};";

    if ($row['Kind'] == PROVIDER_KIND_CREDITCARD) {
        $styles .= "color: gray;";
    }

    if ($row['WSDL'] == '1') {
        $row['WSDL'] = 'Yes';
    } else {
        $row['WSDL'] = '';
    }

    if ($row["ResponseTime"] != null) {
        $gb = floor($row["ResponseTime"] * 3.5);
        $rsStyle .= "background:rgb(255,{$gb},{$gb})";
    }

    if (!empty($row["Severity"])) {
        $row["Severity"] = "S" . $row["Severity"];
    } else {
        $row["Severity"] = '';
    }

    if (!empty($row['LastChecked']) && !empty($row['LastUnkErrors']) && $row['WSDL'] == 'Yes') {
        if (intval($row['LastChecked']) > 0) {
            $SeverityPercent = round(intval($row['LastUnkErrors']) / intval($row['LastChecked']) * 100, 2) . '%';
        } else {
            $SeverityPercent = '';
        }
    } else {
        $SeverityPercent = '';
    }

    if ($row['Warning'] != '') {
        $checkedAttr = 'style="background-color: red; color: white;" title="' . $row['Warning'] . '"';
    } else {
        $checkedAttr = "";
    }

    $escapedDisplayName = fixQuotes($row['DisplayName']);
    $unknownErrors = $row['UnkErrors'];
    $killedAccs = 0;

    if (isset($response[$row['ProviderID']])) {
        $killedAccs = count($response[$row['ProviderID']]);
    }
    $lastHourCount = intval($row["LastHourUnkErrors"]);
    $total = intval($row["LastHourErrors"]);
    $killedForLastHour = 0;

    if (isset($killedAccountsForLastHour[$row['ProviderID']])) {
        $lastHourCount += $killedAccountsForLastHour[$row['ProviderID']];
        $total += $killedAccountsForLastHour[$row['ProviderID']];
        $killedForLastHour = $killedAccountsForLastHour[$row['ProviderID']];
    }

    $assignee = " <a class='clear' stateprev='" . (empty($row["StatePrev"]) ? '' : $arProviderState[$row["StatePrev"]]) . "' style='display: none' href='#' onclick=\"setAssignee({$row['ProviderID']}, '', this.parentNode, '{$escapedDisplayName}'); return false;\">Mark as fixed</a>";
    $assignee .= " <a class='set' style='display: none' href='#' onclick=\"setAssignee({$row['ProviderID']}, {$_SESSION['UserID']}, this.parentNode, '{$escapedDisplayName}'); return false;\">Mark as broken</a>";
    $assignee .= "<span class='name' userId='{$row['Assignee']}'><br/>(Assignee: {$row['AssigneeLogin']})</span><br />";
    $assignee .= <<<HTML
<div class="dropdown-wrapper" data-provider-id="{$row['ProviderID']}">
    <a class="dropdown-toggle" href="#">Actions<span class="caret"></span></a>
    <ul class="dropdown-menu" style="left: -52px; right: auto;">
        <li><a href="/manager/voteMailer.php?type=fixed&showEnabled=1&ID={$row['ProviderID']}" target="_blank">Send emails</a></li>
        <li><a href="#" onclick="setLastFixDate({$row['ProviderID']}, FIX_DATE_CLIENT_SIDE)">Set client side last fix date</a></li>
        <li><a href="#" onclick="setLastFixDate({$row['ProviderID']}, FIX_DATE_SERVER_SIDE)">Set server side last fix date</a></li>
    </ul>
</div>
HTML;
    $assignee .= "&nbsp;<a href='operations?ID={$row['ProviderID']}' target='_blank' title='Operations'><i class='icon-settings'></i></a>";

    $loyaltyStat = $loyaltySuccessRate[$providers[$row['ProviderID']]['Code']] ?? null;

    if ($loyaltyStat) {
        $successRateServer = round($loyaltyStat->getSuccessCount() / ($loyaltyStat->getErrorsCount() + $loyaltyStat->getSuccessCount()) * 100);
    } else {
        $successRateServer = null;
    }
    $detailedSuccess = getDetailedSuccess($row['SuccessRateBrowser'], $successRateServer, $row['WSDL'] == 'Yes');
    $totalCountDetails = getTotalCountDetails($row['TotalCountBrowser'], $loyaltyStat ? $loyaltyStat->getSuccessCount() + $loyaltyStat->getErrorsCount() : 0);
    $manualUpdateDisplay = $row['ManualUpdate'] ? 'display: block;' : 'display: none;';

    echo "<tr style='{$styles}'>
			<td>{$row["Code"]}</td>
			<td>{$arProviderKind[$row["Kind"]]}</td>
			<td><a href='edit.php?Schema=Provider&ID={$row['ProviderID']}' target='_blank'>{$row["DisplayName"]}</a> <i class='icon-blue-info' rel='Info{$row['ProviderID']}' id='Info'>
                <div class='descriptionProviderStatus' style='display: none;'>
                    <table>
                        <tr>
                            <td class='titleProvStat'>Info</td>
                            <td class='descrProvStat' lab='Info{$row['ProviderID']}'>" . ($row['InternalNote'] ?? 'No notes to display') . "</td>
                        </tr>
                    </table>
                </div>
			</td>
			<td>" . number_format($row["Accounts"]) . "</td>
			<td class=\"state\">
			    <span class=\"provider-state\">{$arProviderState[$row["State"]]}</span><br />
			    <span class=\"provider-manual-update\" style=\"{$manualUpdateDisplay}\">Manual Update</span>
            </td>
			<td>{$row["Data"]}</td>
			<td>{$row["Tier"]}</td>
			<td {$checkedAttr}>
			    {$row["TotalCount"]}
			    {$totalCountDetails}
            </td>
			<td>{$row["Errors"]}</td>
			<td><a href='providerErrors.php?ID={$row['ProviderID']}'>{$unknownErrors}</a><span class='lastHour'> / " . $lastHourCount . "</span>
			    <i class='icon-blue-info' rel='Statistic{$row['ProviderID']}' id='Statistic'></i>
			    <div class='descriptionProviderStatus' style='display: none;'>
                    <table>
                        <tr>
                            <td class='descrProvStat' lab='Statistic{$row['ProviderID']}'>
                                <strong style='text-decoration: underline;'>Provider's statistic:</strong>
                                <br/>
                                <br/>
                                <strong style='text-decoration: underline;'>For the last hour:</strong>
                                <br/>
                                Total errors: " . $total . "
                                <br/>
                                <ul style='padding: 0; margin-left: 30px; margin-top: 0; margin-bottom: 2px; margin-rigth: 0;'>
                                <li>
                                    Provider errors: " . intval($row["LastHourProviderErrors"]) . "
                                </li>
                                <li>
                                    Invalid credentials: " . intval($row["LastHourInvalidPassword"]) . "
                                </li>
                                <li>
                                    Account lockouts: " . intval($row["LastHourLockouts"]) . "
                                </li>
                                <li>
                                    Questions: " . intval($row["LastHourQuestions"]) . "
                                </li>
                                <li>
                                    Warnings: " . intval($row["LastHourWarnings"]) . "
                                </li>
                                <li>
                                    Unknown errors: " . intval($row["LastHourUnkErrors"]) . "
                                </li>
                                <li>
                                    Killed: " . $killedForLastHour . "
                                </li>
                                </ul>
                                Successfully checked: " . intval($row["LastHourSuccessfullyChecked"]) . "
                                <br/>
                                Total checked: " . intval($row["LastHourChecked"]) . "
                                <br/>
                                <br/>
                                <strong style='text-decoration: underline;'>Total:</strong>
                                <br/>
                                Successfully checked: " . intval($row["SuccessfullyChecked"]) . "
                                <br/>
                                Unknown errors: " . intval($row["UnkErrors"]) . "
                                <br/>
                                Provider errors: " . intval($row["ProviderErrors"]) . "
                                <br/>
                                Invalid credentials: " . intval($row["InvalidPassword"]) . "
                                <br/>
                                Account lockouts: " . intval($row["Lockouts"]) . "
                                <br/>
                                Questions: " . intval($row["Questions"]) . "
                                <br/>
                                Warnings: " . intval($row["Warnings"]) . "
                                <br/>
                                Killed: " . $killedAccs . "
                                <br/>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style=\"font-size: 80%; color: gray;\">
                    Client: {$row['ClientSideUnkErrors']}<br />Server: {$row['ServerSideUnkErrors']}
                </div>
			</td>
			<td>
			    {$row["SuccessRate"]}%
			    {$detailedSuccess}
			</td>
			<td>{$row["Severity"]}</td>
			<td>$SeverityPercent</td>
			<td class='timeResponse' style='$rsStyle'>{$row["ResponseTime"]}</td>
			<td class='assignee noWrap'>{$assignee}</td>
		</tr>";
}
?>
</table>
<div class='descriptionProviderStatus'>
    <table>
        <tr>
            <td class="titleProvStat">Tier</td>
            <td class="descrProvStat" lab="Tier">Индикатор популярности программы. Мы смотрим на то сколько раз та или иная программа была добавленна нашими пользователями. Мы разбиваем все программы на 3 tiers. В первый попадают самые популярные порграммы (первые 1/3 программ с точки зрения популярности) соответственно в tier 3 попадают самые непопулярные программы, которые реже всего добавляют к себе в профиль.</td>
        </tr>
        <tr>
            <td class="titleProvStat">Checked</td>
            <td class="descrProvStat" lab="Checked">Сколько всего акаунтов этого провайдера было проверено за последние 24 часа (1 час). Тут включены как "удачно" так и "неудачно" проверенные программы.</td>
        </tr>
        <tr>
            <td class="titleProvStat">Unknown errors</td>
            <td class="descrProvStat" lab="UnknownErrors">Сколько неизвестных ошибок было при проверке акаунтов этого провайдера за последние 24 часа (1 час)</td>
        </tr>
        <tr>
            <td class="titleProvStat">Errors</td>
            <td class="descrProvStat" lab="Errors">Сколько ошибок (любого рода) было при проверке акаунтов этого провайдера за последние 24 часа (1 час)</td>
        </tr>
        <tr>
            <td class="titleProvStat">Success</td>
            <td class="descrProvStat" lab="Success">Это ACCOUNT_CHECKED (аккаунтов у которых нет ошибок) делённое на количество checked (общее количество проверенных) за последние 24 часа</td>
        </tr>
        <tr>
            <td class="titleProvStat">Severity</td>
            <td class="descrProvStat" lab="Severity">Это статус сломанного (у которого есть unknown errors) провайдера, он наступает в соответствии с условиями оговорёнными в контракте. Статус бывает 3х видов S1, S2, S3. У любого из этих статусов есть общее необходимое условие - количество сломанных программ должно быть не менее 30.<br/><br/>
            - S3 начинается в тот момент, когда Severity % больше или равен 3% но меньше 10%<br/>
            - S2 начинается в тот момент, когда Severity % больше или равен 10% но меньше 50%<br/>
            - S1 начинается в тот момент, когда Severity % больше или равен 50% </td>
        </tr>
        <tr>
            <td class="titleProvStat">Severity %</td>
            <td class="descrProvStat" lab="SeverityPercent">Это количество unknown errors деленое на количество checked за последние 4 часа</td>
        </tr>
        <tr>
            <td class="titleProvStat">Response time</td>
            <td class="descrProvStat" lab="ResponseTime">Это промежуток времени за который мы должны откликнутся, начинается Response Time в тот же момент когда начинается Severity. В зависимости от начальных условий количество часов отклика коррелируется от 24х до 72х часов. Response Time уменьшается с того момента как началось.<br/><br/>
            Например: 01/01/2012 13:00 у Дельты наступает событие S2 (количество unknown errors превысило 30 и Severity % равняется от 10% до 50% ), в этот момент начинается отчет Response Time (допустим 24 часа) , каждый час это время будет уменьшаться и когда наступит 01/02/2012 13:00, то наступает Late Problem (то есть это равно одной Late Problem)</td>
        </tr>
        <tr>
            <td class="titleProvStat">Late Problems</td>
            <td class="descrProvStat">Это количество "пропаренных Severity" на которые мы не успели отреагировать вовремя за последний месяц (за последние 30 дней независимо от календарного месяца)</td>
        </tr>
        <tr>
            <td class="titleProvStat">% Late</td>
            <td class="descrProvStat">Это количество Late Problems деленное на количество всех возникших Severity за последние 30 дней</td>
        </tr>
    </table>
</div>
<div style="margin-top: 30px;">
	<h4>Legend</h4>
	<table cellpadding='5' cellspacing='0' class="detailsTable">
		<tr>
			<td style="background-color: #ffdddd;">red color</td>
			<td>success rate is lower than 30%</td>
		</tr>
		<tr>
			<td style="background-color: #ffffcc;">yellow color</td>
			<td>success rate is lower than 80%</td>
		</tr>
		<tr>
			<td>WSDL</td>
			<td>SLA with points.com. High priority</td>
		</tr>
		<tr>
			<td style="color: gray;">Grayed out</td>
			<td>This is financial program. Requires special privileges to fix.</td>
		</tr>
	</table>
	<br/>
	<li>This report shows only errors. Programs with success rate 100% are hidden.</li>
	<li>Program sorted by importance (Highest on top).</li>
</div>
<script type="text/javascript">
    $("#mainTable tr:eq(0) td").hover(
        function(event){
            if($(this).attr('rel') != undefined){
                var label = $(this).attr('rel');
                var text = $('td[lab='+label+']').html();
                if($('#helper').length == 0){
                    $('body').append("<div id='helper'></div>");
                } else {
                    $('#helper').show();
                }
                var data = {
                    position:   'absolute',
                    left:       $('body').width()/2-250,
                    top:        250,
                    border:     '1px solid #ccc',
                    background: '#000',
                    color:      '#fff',
                    padding:    '10px',
                    width:      500
                };
                $('#helper').css(data).html(text);
            }
        },
        function(){
            $('#helper').hide();
        }
    );
    $("i#Info").hover(
        function(event){
            if (typeof($(this).attr('rel')) != 'undefined') {
                var label = $(this).attr('rel');
                var text = $('td[lab='+label+']').html();
                if ($('#helper').length == 0) {
                    $('body').append("<div id='helper'></div>");
                } else {
                    $('#helper').show();
                }
                var data = {
                    position:   'fixed',
                    left:       $('body').width()/2-250,
                    top:        150,
                    border:     '2px solid #000',
                    background: '#e9e9e9',
                    color:      '#000',
                    padding:    '10px',
                    width:      800
                };
                $('#helper').css(data).html(text);
            }
        },
        function(){
            $('#helper').hide();
        }
    );
    $("i#Statistic").hover(
        function(event){
            if (typeof($(this).attr('rel')) != 'undefined') {
                var label = $(this).attr('rel');
                var text = $('td[lab='+label+']').html();
                if ($('#helper').length == 0) {
                    $('body').append("<div id='helper'></div>");
                } else {
                    $('#helper').show();
                }
                var data = {
                    position:   'fixed',
                    left:       $('body').width()/2-200,
                    top:        150,
                    border:     '2px solid #000',
                    background: '#e9e9e9',
                    color:      '#000',
                    padding:    '10px',
                    width:      175
                };
                $('#helper').css(data).html(text);
            }
        },
        function(){
            $('#helper').hide();
        }
    );
</script>

<?php
drawFooter();

function setSeverityS($Severity, $Errors = false)
{
    $s = '--';

    if ($Errors >= 1 || !$Errors) {
        if ($Severity >= 3 && $Severity < 10) {
            $s = 3;
        }

        if ($Severity >= 10 && $Severity < 50) {
            $s = 2;
        }

        if ($Severity >= 50) {
            $s = 1;
        }
    }

    return $s;
}

function getProviderData($fields)
{
    $result = "";

    foreach ([
        "AutoLogin" => ["Caption" => "Auto login", "Symbol" => "A"],
        "CanCheckBalance" => ["Caption" => "Balance check", "Symbol" => "B"],
        "Corporate" => ["Caption" => "Corporate program", "Symbol" => "C"],
        "DeepLinking" => ["Caption" => "Deep linking", "Symbol" => "D"],
        "CanCheckExpiration" => ["Caption" => "Expiration date", "Symbol" => "E"],
        "CanCheckItinerary" => ["Caption" => "Itineraries", "Symbol" => "I"],
        "CanCheckConfirmation" => ["Caption" => "Parse itinerary by confirmation number", "Symbol" => "N"],
        "WSDL" => ["Caption" => "WSDL", "Symbol" => "W"],
    ] as $key => $info) {
        if ($fields[$key] == "1" || ($key == 'AutoLogin' && $fields[$key] == AUTOLOGIN_MIXED)) {
            $result .= "<span title='{$info['Caption']} supported'>{$info['Symbol']}</span>";
        } else {
            $result .= "<span title='{$info['Caption']} not supported'>-</span>";
        }
    }

    return "<span class='fixed'>$result</span>";
}
