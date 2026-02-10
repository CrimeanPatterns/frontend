#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/../web/kernel/public.php';

$q = new TQuery("SELECT
		p.ProviderID, p.DisplayName, p.State, p.Code, p.Kind, p.WSDL, p.Assignee, u.Login as AssigneeLogin,
		p.AutoLogin, p.DeepLinking, p.CanCheckBalance, p.Corporate, p.CanCheckExpiration ,p.CanCheckItinerary,
		p.CanCheckConfirmation, p.Tier, p.Severity, p.ResponseTime,

		count(a.AccountID) as TotalCount,
		sum(case when a.ErrorCode = ".ACCOUNT_ENGINE_ERROR." then 1 else 0 end) AS UnkErrors,
		sum(case when a.AccountID is not null and a.UpdateDate > adddate(now(), interval -4 hour) then 1 else 0 end) AS LastChecked,
		sum(case when a.ErrorCode = ".ACCOUNT_ENGINE_ERROR." and a.UpdateDate > adddate(now(), interval -4 hour) then 1 else 0 end) AS LastUnkErrors,
		sum(case when a.ErrorCode <> ".ACCOUNT_CHECKED." then 1 else 0 end) AS Errors,
		round(sum(case when a.ErrorCode = ".ACCOUNT_ENGINE_ERROR." then 1 else 0 end)/count(a.AccountID)*100, 2) AS ErrorRate,
		round(sum(case when a.ErrorCode = ".ACCOUNT_CHECKED." then 1 else 0 end)/count(a.AccountID)*100, 2) AS SuccessRate
	FROM
	 	Account a
		inner join Provider p on a.ProviderID = p.ProviderID
		left outer join Usr u on p.Assignee = u.UserID
	WHERE
		p.State >= ".PROVIDER_ENABLED." and p.State <> ".PROVIDER_COLLECTING_ACCOUNTS." and p.CanCheck = 1
		and a.UpdateDate > DATE_SUB(NOW(), INTERVAL 1 DAY)
	GROUP BY
		p.ProviderID, p.DisplayName, p.State, p.Code, p.Kind, p.WSDL, p.Assignee, u.Login,
		p.AutoLogin, p.DeepLinking, p.CanCheckBalance, p.Corporate, p.CanCheckExpiration ,p.CanCheckItinerary,
		p.CanCheckConfirmation, p.Tier, p.Severity, p.ResponseTime
	HAVING
		round(sum(case when a.ErrorCode = ".ACCOUNT_CHECKED." then 1 else 0 end)/count(a.AccountID)*100, 2) < 100");

while(!$q->EOF){
	$msg = "";
	$subject = $q->Fields['DisplayName']." unknown errors threshold reached";
    if ($q->Fields['UnkErrors'] >= 50) {

        $state = $q->Fields['State'];
        $assigneeLogin = ($q->Fields['AssigneeLogin'] == false?"None":$q->Fields['AssigneeLogin']);
        if (
            $state != PROVIDER_FIXING
            && $state >= PROVIDER_ENABLED
            && $q->Fields['UnkErrors'] >= 250
        ) {
            $assignee = 7;
            $assigneeLogin = 'SiteAdmin (was set by default)';
            $Connection->Execute("UPDATE Provider SET State = ".PROVIDER_FIXING.", StatePrev = {$state}, Assignee = {$assignee} WHERE ProviderID = ".$q->Fields['ProviderID']);
            $state = PROVIDER_FIXING;
        }

		$msg .= "Code: ".$q->Fields['Code']."<br/>\n";
		$msg .= "Kind: ".$arProviderKind[$q->Fields['Kind']]."<br/>\n";
		$msg .= "Provider: ".$q->Fields['DisplayName']."<br/>\n";
		$msg .= "State: ".$arProviderState[$state]."<br/>\n";
		$msg .= "Features: ".getProviderData($q->Fields)."<br/>\n";
		$msg .= "Tier: ".$q->Fields['Tier']."<br/>\n";
		$msg .= "Checked: ".$q->Fields['TotalCount']."<br/>\n";
		$msg .= "Errors: ".$q->Fields['Errors']."<br/>\n";
		$msg .= "Unknown Errors: ".$q->Fields['UnkErrors']."<br/>\n";
		$msg .= "Success %: ".$q->Fields['SuccessRate']."%<br/>\n";
		$msg .= "Severity: ".$q->Fields['Severity']."<br/>\n";
		
		if (!empty($q->Fields['LastChecked']) && !empty($q->Fields['LastUnkErrors']) && $q->Fields['WSDL'] == 'Yes'){
			if(intval($q->Fields['LastChecked']) > 0) {
				$SeverityPercent = round(intval($q->Fields['LastUnkErrors'])/intval($q->Fields['LastChecked'])*100,2).'%';					
			}
			else $SeverityPercent = '';
		}
		else
			$SeverityPercent = '';
			
		$msg .= "Severity %: ".$SeverityPercent."<br/>\n";
		$msg .= "Response time: ".$q->Fields['ResponseTime']."<br/>\n";
		$msg .= "Assignee: ".$assigneeLogin."<br/>\n";
		
		mailTo(ConfigValue(CONFIG_ERROR_EMAIL), $subject, $msg, str_ireplace("text/plain", "text/html", EMAIL_HEADERS));
	}
	$q->Next();
}

function getProviderData($fields){
	$result = "";
	foreach(array(
				"AutoLogin" => array("Caption" => "Auto login", "Symbol" => "A"),
				"CanCheckBalance" => array("Caption" => "Balance check", "Symbol" => "B"),
				"Corporate" => array("Caption" => "Corporate program", "Symbol" => "C"),
				"DeepLinking" => array("Caption" => "Deep linking", "Symbol" => "D"),
				"CanCheckExpiration" => array("Caption" => "Expiration date", "Symbol" => "E"),
				"CanCheckItinerary" => array("Caption" => "Itineraries", "Symbol" => "I"),
				"CanCheckConfirmation" => array("Caption" => "Parse itinerary by confirmation number", "Symbol" => "N"),
				"WSDL" => array("Caption" => "WSDL", "Symbol" => "W"),
	) as $key => $info)
		if($fields[$key] == "1")
			$result .= "<span title='{$info['Caption']} supported'>{$info['Symbol']}</span>";
		else
			$result .= "<span title='{$info['Caption']} not supported'>-</span>";
	return "<span class='fixed'>$result</span>";
}
