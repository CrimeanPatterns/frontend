<?php

$schema = "missingProperties";

require "../start.php";
drawHeader("Missing properties");

$providerPropertyID = intval($_GET['ID']);
$providerID = Lookup("ProviderProperty", "ProviderPropertyID", "ProviderID", $providerPropertyID, true);

$q = new TQuery("
select
	a.UserID,
	a.AccountID,
	a.Login,
	a.UpdateDate,
	p.Kind
from
	Account a
	join Provider p on p.ProviderID = a.ProviderID
	left outer join AccountProperty ap on a.AccountID = ap.AccountID and ap.ProviderPropertyID = $providerPropertyID
where
	a.UpdateDate > adddate(now(), interval -1 day)
	and a.ErrorCode in(1, 9)
	and a.ProviderID = $providerID
	and ap.AccountPropertyID is null
order by
	a.UpdateDate desc
limit 100");
ShowQuery($q, "Last accounts with missing properties", "");

function ShowQuery($q, $title, $style)
{
    $router = getSymfonyContainer()->get("router");
    echo "<table class='detailsTable' cellpadding='3' style='$style'>";
    $headerStyle = "font-weight: bold; text-align: center; background-color: #dddddd;";

    if ($q->EOF) {
        echo "<tr><td style='{$headerStyle}'>{$title}</td></tr>";
        echo "<tr><td>No data</td></tr>";
    } else {
        echo "<tr><td colspan='5' style='{$headerStyle}'>{$title}</td></tr>";
        echo "<tr>
			<td style='font-weight: bold;'>AccountID</td>
			<td style='font-weight: bold;'>UserID</td>
			<td style='font-weight: bold;'>Login</td>
			<td style='font-weight: bold;'>Update Date</td>
			<td style='font-weight: bold;'>Actions</td>
		</tr>";

        while (!$q->EOF) {
            $log = null;
            $links = [
                "<a title='Impersonate' href='/manager/impersonate?UserID={$q->Fields['UserID']}'>Impersonate</a>",
                "<a href=\"/manager/checkAccount.php?ID={$q->Fields['AccountID']}\">Check</a>",
                "<a href=\"/manager/passwordVault/requestPassword.php?ID={$q->Fields['AccountID']}\">Get Password</a>",
            ];

            if ((schemaAccessAllowed("creditCards") || ($q->Fields['Kind'] != PROVIDER_KIND_CREDITCARD)) && schemaAccessAllowed("logs")) {
                $logs = getSymfonyContainer()->get(\AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface::class)->getLogs(getSymfonyContainer()->getParameter("wsdl.login"), $q->Fields['AccountID']);
                sort($logs);

                if (count($logs) > 0) {
                    $log = array_pop($logs);
                    $links[] = "<a href=\"" . getSymfonyContainer()->get("router")->generate("aw_manager_loyalty_logs_item", ["filename" => FileName(basename($log)), "Index" => 0, "AccountID" => $q->Fields['AccountID']]) . "\">Logs</a>";
                }
            }

            echo "<tr>
				<td>{$q->Fields["AccountID"]}</td>
				<td>{$q->Fields['UserID']}</td>
				<td>{$q->Fields["Login"]}</td>
				<td>{$q->Fields["UpdateDate"]}</td>
				<td>" . implode(" | ", $links) . "</td>
			</tr>";
            $q->Next();
        }
    }
    echo "</table>";
}

drawFooter();
