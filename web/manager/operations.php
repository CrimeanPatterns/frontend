<?

use AwardWallet\MainBundle\FrameworkExtension\Exceptions\UserErrorException;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Resources\QueueInfoItem;
use AwardWallet\MainBundle\Loyalty\Resources\QueueInfoResponse;
use AwardWallet\MainBundle\Service\WatchdogKillListProvider;
use Doctrine\DBAL\Connection;

$schema = "operations";
require "start.php";
require_once "../kernel/TForm.php";

require_once "$sPath/wsdl/awardwallet/AwardWalletService.php";
require_once "$sPath/schema/Provider.php";

drawHeader("Operations", "");

$providerId = intval(ArrayVal($_GET, 'ID'));
$providerCode = null;
if (!empty($providerId)) {
    $providerCode = Lookup('Provider', 'ProviderID', 'Code', $providerId);
    if ($providerCode)
        echo "<script>
                setTimeout(function(){
                    var val = '{$providerCode}';
                    var option = $('#fldProvider').find('option[value = \"' + val + '\"]');
                    if (option)
                        option.attr('selected', 'selected');
                }, 100);
            </script>";
}

echo '<div id="checkDiv" style="width: 80%; height: 100%; position: relative; top: -10px;"><br />';

$objForm = new TForm(array(
	"Operation" => array(
		"Type" => "string",
		"Size" => 40,
		"Required" => true,
        "Options" => array(
            "checkProvider"   => "Check Provider with unknown errors",
            "checkProviderI"  => "Check Provider with invalid logons",
            "checkProviderR"  => "Check Provider with provider errors",
            "checkProviderW"  => "Check Provider with warnings",
            "checkProviderE"  => "Check Provider with missing expiration dates",
            "checkProviderSQ" => "Check Provider with security questions",
            "checkProviderT"  => "Check Provider with possible presence itineraries",
            "checkProviderTY" => "Check Provider with itineraries",
            "checkProviderTN" => "Check Provider with no itineraries",
            "checkProviderS"  => "Check Provider without errors",
            "checkProviderF"  => "Full Check Provider",
            "checkProviderN"  => "Check Provider with balance N/A",
            "checkProviderZB" => "Check Provider with Zero Balance",
            "resetExpDate"    => "Reset Expiration Date",
            "resetDisabledI"  => "Reset 'Disabled' state for accounts with invalid credentials",
            "resetDisabledP"  => "Reset 'Disabled' state for accounts with provider errors",
            "resetDisabledL"  => "Reset 'Disabled' state for accounts with lockouts",
            "resetDisabledU"  => "Reset 'Disabled' state for accounts with unknown errors",
            "resetDisabledA"  => "Reset 'Disabled' state for all accounts (except disabled by user)",
            "resetCache"      => "Reset history cache",
        ),
	),
    "CheckStart" => array(
        "Type" => "date",
        "Required" => false,
    ),
    "CheckEnd" => array(
        "Type" => "date",
        "Required" => false,
    ),
	"Provider" => array(
		"Type" => "string",
		"Size" => 40,
		"Required" => true,
		"Options" => array("" => "Select") + SQLToArray("select Code, DisplayName from Provider where State >= ".PROVIDER_ENABLED." or State = ".PROVIDER_TEST." order by DisplayName", "Code", "DisplayName"),
	),
    "CheckingOff" => array(
        "Caption" => "With background check off",
        "Type" => "integer",
        "Required" => false,
        "Value" => 0,
        "InputType" => "checkbox",
        "Note" => "Include providers with State 'Checking off' and 'Fixing'. Use it carefully!",
    ),
    "Limit" => array(
        "Type" => "integer",
        "Required" => false,
        "Value" => 300,
        "Size" => 9,
    ),
));
$objForm->SubmitURL = '/manager/operations.php';
$objForm->SubmitButtonCaption = "Execute";

if($objForm->IsPost && $objForm->Check()){
	switch($objForm->Fields['Operation']['Value']){
		case 'resetCache':
			resetHistoryCache($objForm->Fields['Provider']['Value']);
			break;
        case 'resetDisabledI':
            resetDisabledState($objForm->Fields['Provider']['Value'], \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_PREVENT_LOCKOUT, "accounts with invalid credentials");
            break;
        case 'resetDisabledP':
            resetDisabledState($objForm->Fields['Provider']['Value'], \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_PROVIDER_ERROR, "accounts with provider errors");
            break;
        case 'resetDisabledL':
            resetDisabledState($objForm->Fields['Provider']['Value'], \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_LOCKOUT, "accounts with lockouts");
            break;
        case 'resetDisabledU':
            resetDisabledState($objForm->Fields['Provider']['Value'], \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_ENGINE_ERROR, "accounts with unknown errors");
            break;
        case 'resetDisabledA':
            resetDisabledState($objForm->Fields['Provider']['Value'], \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_USER, "all accounts (except disabled by user)");
            break;
        case 'resetExpDate':
            resetExpDate($objForm->Fields['Provider']['Value']);
            break;
		default:
			checkProvider($objForm->Fields['Operation']['Value'], $objForm->Fields['Provider']['Value'],
                $objForm->Fields['Operation']['Options'], $objForm->Fields['Limit']['Value'], $objForm->Fields['CheckingOff']['Value'], $objForm->Fields['CheckStart']['Value'], $objForm->Fields['CheckEnd']['Value']);
	}
    $objForm->Fields['Operation']['Value'] = 'checkProvider';
    $objForm->Fields['Limit']['Value'] = '300';
    $objForm->Fields['CheckingOff']['Value'] = 0;
}

echo $objForm->HTML();

// checkDiv done
echo '</div><br />';
drawQueueInformation();

drawFooter();

echo "
<script>
$(document).ready(
    function (){
        $('#fldOperation').change(function(event) {
           if (event.target.value == 'checkProviderZB') {
                $('#trCheckStart').show();
                $('#trCheckEnd').show();               
           } else {
                $('#trCheckStart').hide();
                $('#trCheckEnd').hide();                              
           }
        });
        $('#fldOperation').trigger('change');
        // todo
        $('#ui-datepicker-div').css('display','none');
    }
);
</script>
";

function resetHistoryCache($provider){
	global $Connection;
	echo "<h1>Resetting history cache for provider '$provider'</h1>";
	$Connection->Execute("update Provider set CacheVersion = CacheVersion + 1 where Code = '".addslashes($provider)."'");
	$cacheVersion = getSymfonyContainer()->get("database_connection")->fetchOne("select CacheVersion from Provider where Code = ?", [$provider]);
    getSymfonyContainer()->get("logger")->info("updated provider cache version, provider: {$provider}, cache version now: {$cacheVersion}");
	TProviderSchema::triggerDatabaseUpdate();
	echo "<p class='providerStatus'><b>If history columns have been changed</b> then you should also <a target='_blank' href='/manager/cache/' style 'text-decoration: none;'>reset provider cache</a> (Tag: <b>TAG_PROVIDERS</b>)</p>";
	echo "update started. cache will be reset in 1 minute<br/>";
}

function resetExpDate($provider) {
    global $Connection;
    echo "<h1>Reset Expiration Date for provider '$provider'</h1><br/>";
    $q = new TQuery("SELECT ProviderID FROM Provider WHERE Code = '".addslashes($provider)."'");
    if (!$q->EOF){
        $providerID = $q->Fields["ProviderID"];
        $Connection->Execute("update Account set ExpirationDate = NULL, ExpirationAutoSet = ".EXPIRATION_UNKNOWN." where ProviderID = {$providerID} and (ExpirationAutoSet = ".EXPIRATION_AUTO." or ExpirationDate is not NULL and ExpirationAutoSet = ".EXPIRATION_UNKNOWN.")");
        TProviderSchema::triggerDatabaseUpdate();
        echo "Expiration Date was reset, accounts affected: ".$Connection->GetAffectedRows()."<br/>";
    }
    else
        echo "ProviderID is not found!<br/>";
}

function resetDisabledState($provider, $disableReason, $caption) {
    global $Connection;
    echo "<h1>Reset 'Disabled' state for {$caption} for provider '$provider'</h1><br/>";
    if ($disableReason == \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_USER)
        $condition = "not(DisableReason is null OR DisableReason = ".$disableReason.")";
    else
        $condition = "DisableReason = ".$disableReason;
    $q = new TQuery("SELECT ProviderID FROM Provider WHERE Code = '".addslashes($provider)."'");
    if (!$q->EOF){
        $providerID = $q->Fields["ProviderID"];
        $Connection->Execute("UPDATE Account
                SET ErrorCount = 0, Disabled = 0
                WHERE ProviderID = $providerID
                      AND ({$condition})
                      AND Disabled = 1");
        echo "'Disabled' state was reset, accounts affected: " . $Connection->GetAffectedRows() .  "<br/>";
    }
    else
        echo "ProviderID is not found!<br/>";
}

function checkProvider($operation, $provider, $options, $limit, $checkingOff, $d1, $d2) {
    $limit = intval($limit);
    $conn = getSymfonyContainer()->get('database_connection');

        if (isset($options[$operation]))
            echo "<h1>".CleanXMLValue(str_replace('Provider', 'Provider \''.$provider.'\' ', $options[$operation]))."</h1><br />";

			$filter = "";
			$statuses = array(
				ACCOUNT_INVALID_PASSWORD,
				ACCOUNT_LOCKOUT,
				ACCOUNT_PREVENT_LOCKOUT,
				ACCOUNT_CHECKED,
				ACCOUNT_WARNING,
				ACCOUNT_PROVIDER_ERROR,
				ACCOUNT_ENGINE_ERROR,
                ACCOUNT_QUESTION,
				ACCOUNT_PROVIDER_DISABLED,
				ACCOUNT_UNCHECKED
			);
	$withKilledAccounts = false;
			switch($operation) {
				case 'checkProviderI':
					$statuses = array(ACCOUNT_INVALID_PASSWORD);
					break;
				case 'checkProviderR':
					$statuses = array(ACCOUNT_PROVIDER_ERROR);
					break;
                case 'checkProviderW':
					$statuses = array(ACCOUNT_WARNING);
					break;
				case 'checkProvider':
					$statuses = array(ACCOUNT_ENGINE_ERROR);
					$withKilledAccounts = true;
					break;
				case 'checkProviderE':
					$filter .= " and a.ExpirationDate is not null and a.ExpirationAutoSet < ".EXPIRATION_AUTO;
					break;
                case 'checkProviderT':
					$filter .= " and a.Itineraries <> -1";
					break;
                case 'checkProviderTY':
					$filter .= " and a.Itineraries > 0";
					break;
				case 'checkProviderTN':
					$filter .= " and a.Itineraries = 0";
					break;
                case 'checkProviderS':
                    $statuses = array(ACCOUNT_CHECKED);
					break;
                case 'checkProviderSQ':
                    $statuses = array(ACCOUNT_QUESTION);
					break;
                case 'checkProviderN':
                    $statuses = array(ACCOUNT_CHECKED);
					$filter .= " and a.Balance is null";
					break;
                case 'checkProviderZB':
                    $statuses = array(ACCOUNT_CHECKED);
                    $errDate = false;
                    if (empty($d2)) {
                        $d2 = $d1;
                    }
                    if (empty($d1) || !preg_match("#^\d{2}/\d{2}/\d{4}$#", $d1) || !preg_match("#^\d{2}/\d{2}/\d{4}$#",
                            $d2)) {
                        $errDate = true;
                    }
                    $d1 = preg_replace("#^(\d{2})/(\d{2})/(\d{4})$#", '$3-$1-$2', $d1);
                    $d2 = preg_replace("#^(\d{2})/(\d{2})/(\d{4})$#", '$3-$1-$2', $d2);

                    $d1 = strtotime($d1);
                    $d2 = strtotime('+1 day', strtotime($d2));
                    if ($d1 >= $d2) {
                        $errDate = true;
                    }
                    $d1 = date('Y-m-d', $d1);
                    $d2 = date('Y-m-d', $d2);
                    if ($errDate) {
                        echo "ERROR: wrong period for check: [{$d1}:{$d2})<br/>";
                        return;
                    }
					$filter .= " and (a.Balance = 0 or a.Balance < -1) and SuccessCheckDate >= '{$d1}' and SuccessCheckDate < '{$d2}'";
					break;
			}
			try {
                if ($operation == 'checkProviderF')
                    $limit = null;

                $sql = getSQL($provider, $filter, $statuses, $limit, $checkingOff);

                if ($withKilledAccounts || $operation == 'checkProviderF') {
                    /** @var Connection $conn */
                    $providerId = (int) $conn->executeQuery("select ProviderID from Provider where Code=?", [$provider], [PDO::PARAM_STR])->fetchColumn();

                    /** @var WatchdogKillListProvider $watchdogKillListProvider */
                    $watchdogKillListProvider = getSymfonyContainer()->get(WatchdogKillListProvider::class);
                    $l = $limit;
                    $killedAccounts = $watchdogKillListProvider->search($l, $providerId);

                    if (is_array($killedAccounts) && count($killedAccounts) > 0) {
                        $accounts = array_map(function($el){ return (int) $el['AccountID']; }, $killedAccounts);
                        $accounts = implode(", ", $accounts);
                        $filter .= " and a.AccountID in({$accounts})";
                        $statuses = [
                            ACCOUNT_UNCHECKED,
                            ACCOUNT_CHECKED,
                            ACCOUNT_INVALID_PASSWORD,
                            ACCOUNT_LOCKOUT,
                            ACCOUNT_PROVIDER_ERROR,
                            ACCOUNT_PROVIDER_DISABLED,
                            ACCOUNT_ENGINE_ERROR,
                            ACCOUNT_PREVENT_LOCKOUT,
                            ACCOUNT_QUESTION,
                            ACCOUNT_WARNING,
                        ];
                        $sqlForKilled = getSQL($provider, $filter, $statuses, $limit, $checkingOff);
                    }
                }

            }
            catch(UserErrorException $e){
				echo "ERROR: " . $e->getMessage() . "<br/>";
				return;
			}
			getSymfonyContainer()->get("logger")->warning("operation query started", ["sql" => $sql, "page" => "operations"]);
            session_write_close();
			$q = $conn->executeQuery($sql)->fetchAll(PDO::FETCH_ASSOC);

    		$killedAcs = [];
            if (!empty($sqlForKilled)) {
                $killedAcs = $conn->executeQuery($sqlForKilled)->fetchAll(PDO::FETCH_ASSOC);
                echo 'sql for killed: '.htmlspecialchars($sqlForKilled)."<br/>";
            }

			$diff = array_diff(array_map(function($dat){ return $dat['AccountID']; }, $killedAcs), array_map(function ($data){ return $data['AccountID']; }, $q));

            if (count($q) < $limit) {
				$c = $limit - count($q);
				$counter = 0;
                foreach ($diff as $i => $killed) {
                    if ($counter >= $c)
                        break;
                    $q[] = $killedAcs[$i];
                    $counter++;
                }
            }

			echo htmlspecialchars($sql)."<br/>";
			getSymfonyContainer()->get("logger")->warning("operation query finished", ["page" => "operations"]);
			$checker = new GroupWsdlCheckAccount($q);
			$checker->parseHistory = true;
			$checker->priority = 6;

			$logger = function($obj, $event, $message){
				echo htmlspecialchars($message)."<br />";
				getSymfonyContainer()->get("logger")->warning($message, ["page" => "operations"]);
			};
			$wsdlLogger = function($obj, $event, $message){
				echo htmlspecialchars('[WSDL] '.$message)."<br />";
				getSymfonyContainer()->get("logger")->warning($message, ["page" => "operations"]);
			};
			$loyaltyLogger = function($obj, $event, $message){
				echo htmlspecialchars('[Loyalty] '.$message)."<br />";
				getSymfonyContainer()->get("logger")->warning($message, ["page" => "operations"]);
			};
			$checker->addObserver($logger, GroupWsdlCheckAccount::EVENT_END);

			$checker->addObserver($wsdlLogger, GroupWsdlCheckAccount::EVENT_LOG);
			$checker->addObserver($wsdlLogger, GroupWsdlCheckAccount::EVENT_ONE_COMPLETE);

			$checker->addObserver($loyaltyLogger, GroupWsdlCheckAccount::LOYALTY_EVENT_ONE_COMPLETE);
			$checker->addObserver($loyaltyLogger, GroupWsdlCheckAccount::LOYALTY_EVENT_LOG);
			$checker->addObserver($loyaltyLogger, GroupWsdlCheckAccount::LOYALTY_EVENT_ERROR);

			# Check
			$checker->WSDLCheckAccounts(UpdaterEngineInterface::SOURCE_OPERATIONS);
			getSymfonyContainer()->get("logger")->warning("operations done", ["message", "page" => "operations"]);
			echo "done<br>";
            session_start();

}

function getSQL($provider, $filter, $statuses, $limit = null, $checkingOff = false) {
    global $arProviderState;
//    $awPlusOnlyFilter = " and (p.State <> ".PROVIDER_CHECKING_AWPLUS_ONLY." or u.AccountLevel <> ".ACCOUNT_LEVEL_FREE.")";
    $awPlusOnlyFilter = "";
    if ($checkingOff) {
        if ($limit && $limit > 100)
            $limit = 100;

        // TODO: skywards gag
        if ($provider === 'skywards' && $limit && $limit > 7) {
            $limit = 7;
        }
    }
    $q = new TQuery("select * from Provider where Code = '" . addslashes($provider) . "'");
    if($q->Fields['State'] < PROVIDER_ENABLED ||  $q->Fields['State'] == PROVIDER_CHECKING_EXTENSION_ONLY)
        throw new UserErrorException("Invalid provider state: " . $arProviderState[$q->Fields["State"]]);
    if(!$checkingOff && in_array($q->Fields['State'], [PROVIDER_CHECKING_OFF, PROVIDER_FIXING]))
        throw new UserErrorException("Checking off provider state: " . $arProviderState[$q->Fields["State"]]);
    if($q->Fields["CanCheck"] != 1)
        throw new UserErrorException("CanCheck is false for this provider");

    if($provider == "aa")
        $filter .= " and a.ErrorCode in (".ACCOUNT_CHECKED.", ".ACCOUNT_ENGINE_ERROR.") and a.SuccessCheckDate > a.PassChangeDate and a.UpdateDate > '2014-04-16'";
    elseif($q->Fields["PasswordRequired"])
        $filter .= " and ((a.SavePassword = ".SAVE_PASSWORD_DATABASE." and a.Pass <> '') or a.AuthInfo is not null)";

	$sql = "select
		a.AccountID,
		a.ProviderID,
		5 as Priority,
		a.ErrorCode,
		a.SuccessCheckDate,
		a.PassChangeDate,
		a.UpdateDate,
		a.ModifyDate,
		a.PassChangeDate,
		'{$q->Fields['Code']}' as ProviderCode,
		a.SavePassword,
		'{$q->Fields['Code']}' as Code,
		a.Login,
  			a.Login2,
  			a.Login3,
  			a.Pass,
  			a.UserID,
  			a.BrowserState,
  			a.HistoryVersion,
  			(u.AutoGatherPlans = 1 AND {$q->Fields['CanCheckItinerary']} = 1) as AutoGatherPlans
	from
		Account a
		join Usr u on a.UserID = u.UserID
	where
	    a.ProviderID = {$q->Fields['ProviderID']}
		and a.ErrorCode in( ".implode(", ", $statuses)." )
		and a.Disabled = 0
		$awPlusOnlyFilter
		$filter";
	if (isset($limit))
		$sql .= " LIMIT ".$limit;
	return $sql;
}

function drawQueueInformation(){
    # Loyalty queue
    /** @var \AwardWallet\MainBundle\Loyalty\ApiCommunicator $communicator */
    $communicator = getSymfonyContainer()->get(\AwardWallet\MainBundle\Loyalty\ApiCommunicator::class);
    /** @var \AwardWallet\MainBundle\Loyalty\Resources\QueueInfoResponse $jsonQueueInfo */
    $loyaltyQueueInfo = $communicator->GetQueueInfo();

    $html = <<<HTML
        <div id="queue" style="position: absolute; top: 50px; right: 25px;">
            <h3>Queue Info</h3>
            <table>
                <tr id="loyaltyClick" style="background-color: #ffebeb; cursor: pointer;">
                    <th colspan="2">Loyalty [+]</th>
                </tr>
                %s
            </table>
        </div>
        <script>
            $('#loyaltyClick').click(function(){
                var text = $(this).text().trim();
                if (text === 'Loyalty [+]') {
                    $(this).children('th').text('Loyalty [-]');
                } else {
                    $(this).children('th').text('Loyalty [+]');
                }
                $(this).next().nextAll(':not(:has(b))').toggle();
            });
            $('#loyaltyClick').next().nextAll(':not(:has(b))').hide();
        </script>
HTML;

    $loyaltyTotal = 0;
    $loyaltyProvidersQueue = '';
	$rowHtml = '<tr><td>%s</td><td style="text-align: right">%s</td></tr>';
	$totalPriorities = [];
	$priorityMapping = [
		2 => 'Background',
		3 => 'Background (aa)',
		6 => 'Operations',
		7 => 'Users'
	];

    if (!empty($loyaltyQueueInfo->getQueues())) {
        $queue = [];
		$totalPriorities = [];
        /** @var QueueInfoItem $queueItem */
        foreach ($loyaltyQueueInfo->getQueues() as $queueItem) {
			if(!isset($queue[$queueItem->getProvider()]))
				$queue[$queueItem->getProvider()] = 0;

			$queue[$queueItem->getProvider()] += $queueItem->getItemsCount();
            $loyaltyTotal += $queueItem->getItemsCount();

			if(empty($queueItem->getPriority()))
				continue;

			if(!isset($totalPriorities[$queueItem->getPriority()]))
				$totalPriorities[$queueItem->getPriority()] = 0;

			$totalPriorities[$queueItem->getPriority()] += $queueItem->getItemsCount();
        }
        arsort($queue);
        foreach ($queue as $provider => $queueInfo)
            $loyaltyProvidersQueue .= sprintf($rowHtml, $provider, $queueInfo);
    }// if (!empty($loyaltyQueueInfo->getQueues()))

	foreach ($totalPriorities as $key => $val)
		$loyaltyProvidersQueue = sprintf($rowHtml, '<b>'.(isset($priorityMapping[$key]) ? $priorityMapping[$key] : '').' ('.$key.')</b>', '<b>'.$val.'</b>') . $loyaltyProvidersQueue;

	$loyaltyProvidersQueue = sprintf($rowHtml, '<b>Total</b>', '<b>'.$loyaltyTotal.'</b>') . $loyaltyProvidersQueue;

	echo sprintf($html, $loyaltyProvidersQueue);
}

?>
