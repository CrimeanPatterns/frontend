<?

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;

define('PLEASE_UPGRADE_FOR_EXPIRATION', "<a href='#' onclick=\"showQuestionPopup('info', 'Please upgrade', 'Award Wallet can detect expiration date on some reward programs. You can only see expiration date which AwardWallet automatically detects for ".EXPIRATION_DATE_LIMIT." reward programs, in order to see this value for this particular program <a href=/user/pay.php>please upgrade your account to Award Wallet Plus</a>. You get to choose the upgrade price.', 'Upgrade', 'document.location.href = \'/user/pay.php\'', 'Cancel', 'cancelPopup()'); return false;\"><table cellpadding='0' cellspacing='0'><tr><td><div class='date'>Please upgrade</div></td></tr></table></a>");

class TAccountList {

	public $Sort;
	public $orderby;
	public $limit = '';
	public $atLeatOneAccountAdded;
	public $CheckCount;
	public $ProgramCount;
	public $ShowManageLinks = true;
	public $ColCount = 10;
	public $ExpirationDatesShown = 0;
	public $Totals = array();
	public $TotalUsers = array();
	public $Grouped = true;
	public $Rows;
	public $CouponCount = 0;
	public $CouponSubAccounts = 0;
	public $ShowListCaption;
	public $ShowExpirationDates;
	public $ShowNames = true;
	public $Caption;
	public $NoticeAutoLogin;
	public $NoticeBrowserAutoLogin;
	public $ShowLimitMessage = false;
	public $MaxProgram;
	
	protected $Page = "/account/list.php";
	private $ProgramColTitle = "Award Program";
	protected $AllProps;
	private $NothingExpiresText = array();
	private $NothingExpiresHtml = array();

	private $PageSize;
	private $PageNavigator;
	private $ShowFilters = false;
	private $Filters = array();

	private static $usrUnPaid = false;
	private $tips = array(
		'multi_accounts' => 'Multiple statuses', /*checked*/
	);

	function __construct(){
		$this->setDefaultParams();
		$this->buildOrderBy();
	}

	function setDefaultParams(){
		global $awardTabs, $leftMenu;
		
		$issetAgents = false;
		if (!empty($leftMenu)){
			foreach($leftMenu as $k=>$m){
				if (strstr($k,'Client_')) {
					if (isset($m['count']))
						if (intval($m['count']) > 0){
							$issetAgents = true;
							break;
						}
				}
			}
		}
		
		if(isset($_COOKIE['grouped']) && ($_COOKIE['grouped'] == 'false'))
			$this->Grouped = false;
		$this->atLeatOneAccountAdded = false;
		// begin checkong to see if the current user has any award programs or coupons added to the profile
		$this->CheckCount = 0;
		$this->ProgramCount = 0;
		$this->NoticeAutoLogin = false;
		$this->NoticeBrowserAutoLogin = false;
		$this->Rows = array();
		$this->ShowListCaption = isset($awardTabs) && ($awardTabs->selectedTab == "All");
		$this->ShowExpirationDates = ($_SESSION['AccountLevel'] == ACCOUNT_LEVEL_AWPLUS) || ($_SESSION['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS);
		$this->ShowNames = !isset($_GET['ProviderID']) && (ArrayVal($_GET, 'UserAgentID') == 'All') && $issetAgents;
		if(isset($_GET['ProviderID'])){
			$this->Grouped = false;
			$this->ShowListCaption = true;
			$this->Caption = Lookup("Provider", "ProviderID", "DisplayName", intval($_GET['ProviderID']));
			$this->ProgramColTitle = "Account Owner";
			$this->PageSize = 50;
			$this->ShowFilters = true;
			$this->Filters = array(
				"Program" => array(
					"Field" => SQL_USER_NAME,
					"Value" => ArrayVal($_GET, 'Program'),
				),
				"Account" => array(
					"Field" => "a.Login",
					"Value" => ArrayVal($_GET, 'Account')
				)
			);
		}
		if(isset($_GET['UserAgentID']) && ($_GET['UserAgentID'] != 'All') && ($_GET['UserAgentID'] != '0')){
			$q = new TQuery("select
				".SQL_USER_NAME." as UserName
			from
				UserAgent ua
				left outer join Usr u on ua.ClientID = u.UserID
			where
				ua.UserAgentID = ".intval($_GET['UserAgentID']));
			$this->Caption = "{$q->Fields['UserName']}";
			$this->Grouped = false;
		}

		$q = new TQuery("SELECT CurrencyID, Name FROM Currency");
		while (!$q->EOF) {
			$name = $q->Fields['Name'];
			$caption = "$name don't<br/>expire";
			$this->NothingExpiresText[$q->Fields['CurrencyID']] = StripTags($caption);
			$this->NothingExpiresHtml[$q->Fields['CurrencyID']] = $caption;
			$q->Next();
		}
	}

	protected function getFirstSort(){
		if($this->Grouped)
			$firstSort = " case when UserAgentID is null and UserID = {$_SESSION['UserID']} then 1 else 2 end, UserName, ";
		else
			$firstSort = "";
		return $firstSort;
	}

	function buildOrderBy(){
		$firstSort = $this->getFirstSort();
		if(isset($_GET["sort"])){
			switch($_GET["sort"]){
				case "carrier":
					$this->Sort = "carrier";
					if(isset($_GET['ProviderID']))
						$this->orderby = "order by UserName, RawBalance DESC";
					else
						$this->orderby = "order by {$firstSort}DisplayName, RawBalance DESC";
				break;
				case "program":
					$this->Sort = "program";
					$this->orderby = "order by {$firstSort}DisplayName, RawBalance DESC";
				break;
				case "balance":
					$this->Sort = "balance";
					$this->orderby = "order by {$firstSort}RawBalance DESC";
				break;
				case "expiration":
					$this->Sort = "expiration";
					$this->orderby = "order by {$firstSort}IsNull(ExpirationDate), ExpirationDate ASC, CanCheckExpiration DESC";
				break;
				default:
					$this->Sort = "carrier";
					$this->orderby = "order by {$firstSort}DisplayName, RawBalance DESC";
			}
		}
		else{
			$this->Sort = "carrier";
			$this->orderby = "order by {$firstSort}DisplayName, Balance DESC";
		}
	}

	function buildSQL($sFilter, $sCouponFilter){
		global $sUserAgentAccountFilter, $sUserAgentCouponFilter, $nUserAgentID;
		$this->addFilters($sFilter);
		$sSQL = AccountsSQL($_SESSION['UserID'], $sUserAgentAccountFilter, $sUserAgentCouponFilter, $sFilter, $sCouponFilter, $nUserAgentID);
		$sSQL .= " $this->orderby";
		$sSQL .= " $this->limit";
		return $sSQL;
	}

	function addFilters(&$filters){
		foreach($this->Filters as $filter)
			if($filter['Value'] != "")
				$filters .= " and {$filter['Field']} like '%".addslashes($filter['Value'])."%'";
	}

	function loadProperties($accounts){
		if(count($accounts) == 0)
			return array();
		$q = new TQuery("select ap.AccountID, ap.SubAccountID, pp.Name, pp.Code, ap.Val, pp.Kind, pp.SortIndex
		from AccountProperty ap, ProviderProperty pp
		where ap.ProviderPropertyID = pp.ProviderPropertyID and ap.AccountID in (".implode(",", $accounts).") and (ap.SubAccountID is null)");
		$result = array();
		while(!$q->EOF){
			$result[$q->Fields['AccountID']][$q->Fields['SubAccountID']][] = array(
				"Code" => $q->Fields['Code'],
				"Name" => $q->Fields['Name'],
				"Val" => $q->Fields['Val'],
				"Kind" => $q->Fields['Kind'],
				"SortIndex" => $q->Fields['SortIndex'],
			);
			$q->Next();
		}
		return $result;
	}

	function prepareExpirationMessage() {

	}

	protected function drawGroupCaption($sListCaption){
		global $lastRowKind;
		?>
		<tr class="accounts after<?=$lastRowKind?>">
			<td colspan="<?=$this->ColCount?>" style="%tabStyle%" class="tabHeader">
				<div class="icon"><div class="left"></div></div>
				<div class="caption"><?
				echo $sListCaption;
				?></div>
			</td>
		</tr>
		<?
		$lastRowKind = "Group";
	}

	protected function beginRow($arFields){

	}

	function ListCategory( $sFilter, $sCouponFilter, $sListCaption, $nSectionIndex, $nKind ){
		global $Connection, $Interface, $lastRowKind,
		$awardTabs,
		$nUserAgentID;
		# do query
		$sql = $this->buildSQL($sFilter, $sCouponFilter);
		$sql = str_replace(['/*)fields*/', 'from ProviderCoupon c'], [',NULL as CardNumber /*)fields*/', ', c.CardNumber  from ProviderCoupon c'], $sql);
		$accounts = array();
		$arPrograms = array();
		$userIds = array();
		$AllianceEliteLevels = array();
		$q = new TQuery($sql);
		if($q->EOF && !empty($awardTabs)){
			if($awardTabs->selectedTab == 'Active'){
				echo "<tr class='head accounts after{$lastRowKind}'><td class='c1head' style='border-bottom: none;'><div class='icon'><div class='inner'></div></div>
				<div class='caption' style='padding-left: 0px;'>";
				$Interface->DrawMessage("There are no active accounts in your profile now.", "info"); /*checked*/
				echo "</div></td></tr>";
				echo "<tr class='after{$lastRowKind} accounts'><td class='c1 pad' style='padding-top: 0px; padding-right: 0px; width: 100%;'></td></tr>";
				echo "<tr><td class='c1 lastRow'></td></tr>";
				return;				
			}
		}
		if(isset($this->PageSize)){
			$q->PageSize = $this->PageSize;
			$q->SelectPageByURL();
			$this->PageNavigator = $q->PageNavigator();
		}
		while((!isset($this->PageSize) && !$q->EOF) || (isset($this->PageSize) && !$q->EndOfPage())){
			$arPrograms[] = $q->Fields;
			if($q->Fields['TableName'] == 'Account') {
				$accounts[] = $q->Fields['ID'];
				$userIds[] = $q->Fields['UserID'];
			}
			$q->Next();
		}
		
		//Bug #3079
		/*$userNames = getNameOwnerAccount(array_unique($userIds));
		foreach ($arPrograms as &$program) {
			$program['UserName'] = (isset($userNames[$program['UserID']])) ? $userNames[$program['UserID']] : $program['UserName'];
		}*/
		
		$this->AllProps = $this->loadProperties($accounts);
		if( count( $arPrograms ) > 0 ){
			$carrierTitle = "Carrier";
			if($nKind == PROVIDER_KIND_HOTEL)
				$carrierTitle = "Brands";
			elseif($nKind == PROVIDER_KIND_CAR_RENTAL)
				$carrierTitle = "Company";
			elseif($nKind == PROVIDER_KIND_CREDITCARD)
				$carrierTitle = "Credit Card";
			elseif($nKind == PROVIDER_KIND_OTHER)
				$carrierTitle = "Company";
			if ($this->ShowListCaption || isset($this->Caption)){
				if($this->Grouped || isset($this->Caption)){
					$this->DrawGroupCaption(isset($this->Caption) ? $this->Caption : $sListCaption);
				}
			}
		if(isset($_GET['showTabG']))
			$sURLFilter = "&showTabG=".urlencode($_GET['showTabG']);
		else
			$sURLFilter = "";
		if(isset($_GET['ProviderID']))
			$sURLFilter .= "&ProviderID=".urlencode($_GET['ProviderID']);
		$this->beginRow($arPrograms[0]);
	?>
	<tr class="head accounts after<?=$lastRowKind?>" style="<?if( $this->ProgramCount > 0 ) echo "%nextHeadersDisplay%"; ?>">
		<td class="c1head notPrintable printW0" colspan="2"><div class="icon"><div class="inner"></div></div><div id="rowPopupMarker"></div></td>
		<td class="c2head"></td>
		<td class="colProgram" nowrap><div class="left40"><a href="<?=htmlspecialchars($this->Page."?sort=carrier&UserAgentID=".$nUserAgentID.$sURLFilter)?>"><?=$this->ProgramColTitle?></a><? if($this->Sort == "carrier") echo "<span class=\"sortArrow\">&nbsp;</span>"?></div></td>
		<td class="leftDots">Account</td>
		<td class="leftDots">Status</td>
		<td class="leftDots"><a href="<?=htmlspecialchars($this->Page."?sort=balance&UserAgentID=".$nUserAgentID.$sURLFilter)?>">Balance</a><? if($this->Sort == "balance") echo "<span class=\"sortArrow\">&nbsp;</span>"?></td>
		<td class="leftDots noRight colExpiration" colspan="2"><a href="<?=htmlspecialchars($this->Page."?sort=expiration&UserAgentID=".$nUserAgentID.$sURLFilter)?>">Expiration</a><? if($this->Sort == "expiration") echo "<span class=\"sortArrow\">&nbsp;</span>"?></td>
		<? if($this->ShowManageLinks) {?>
		<td class="leftDots noRight manageHead" style="text-align: center" class="notPrintable printW0"></td>
		<? } ?>
	</tr>
	<?
		$lastRowKind = "Head";
		if($this->ShowFilters)
			$this->DrawFiltersForm();

	$sUserName = "";
	$nRow = 0;
	$afterHeader = true;
	$afterSubAccounts = false;
	$paidUsr = array();
	$_i = 0;
	$lastRow = false;
	// Getting Alliances Elite Levels for User
//	$AllianceEliteLevels = $this->GetAlliancesEliteLevels($_SESSION['UserID']);
	//Grouping by UserAgentID
	foreach ( $arPrograms as $arFields ){
		$_i++;
		if (is_null($arFields))
			break;
		self::$usrUnPaid = 
			SITE_MODE == SITE_MODE_BUSINESS
            && array_key_exists('UserAgentID', $paidUsr)
			&& (!in_array($arFields['UserAgentID'], $paidUsr['UserAgentID']) && !in_array($arFields['UserID'], $paidUsr['UserID']));
		$this->FormatFields($arFields);
//		if(($arFields["ProviderCode"] == "bankofamerica") || ($arFields["ProviderCode"] == "rbcvisa"))
//			$arFields["Login"] = str_pad(substr($arFields["Login"], strlen($arFields["Login"]) - 4, 4), 16, "x", STR_PAD_LEFT);
		// calc totals
		if(!isset($this->Totals[$arFields['Kind']]))
			$this->Totals[$arFields['Kind']] = array(
				"Accounts" => 0,
				"Points" => 0,
			);
		$this->Totals[$arFields['Kind']]["Accounts"]++;
		#if($arFields["Balance"] > 0 && $arFields['SubAccounts'] == 0){
		// we will get TotalBalance for basic accounts and Balance for custom ones
		$this->Totals[$arFields['Kind']]["Points"] += round(floatval(ArrayVal($arFields, "TotalBalance", $arFields['Balance'])));
		#} elseif($arFields["Balance"] > 0 && $arFields['SubAccounts'] > 0 && $this->Page == '/account/overview.php'){
		#	if($arFields['Accounts'] > 1)
			#	$this->Totals[$arFields['Kind']]["Points"] += round(floatval($arFields["Balance"]));
		#}
		if(!in_array($arFields["UserName"], $this->TotalUsers))
			$this->TotalUsers[] = $arFields["UserName"];
 		$nRow++;
		$this->ProgramCount++;
		if($_i > 1)
			$this->beginRow($arFields);
	if($sUserName != $arFields["UserName"]){
		$sUserName = $arFields["UserName"];
		if(!((trim( $nUserAgentID ) == "All") && !$this->Grouped)){
			if($this->ShowNames){
				$afterHeader = true;
					?>
					<tr class="accounts after<?=$lastRowKind?>">
						<td colspan="<?=$this->ColCount?>" class="userName">
							<div class="icon"><div class="inner"></div></div>
							<div class="name"><?=$sUserName?></div>
						</td>
					</tr>
					<?
				$lastRowKind = "Name";
			}
		}
	}

	$sRowStyle = "";
	$sBgColor = "whiteBg";
	if( ( $nRow % 2 ) == 0 ){
		$sBgColor = "grayBg";
	}
	unset($props);
	$arFields["ShowMoreInfo"] = true;
	if( $arFields["TableName"] == "Account" ){
        if ($arFields['CustomDisplayName'] == '1') {
			require_once __DIR__ . "/../../engine/{$arFields['ProviderCode']}/functions.php";
			$arFields['DisplayName'] = call_user_func(array("TAccountChecker".ucfirst($arFields['ProviderCode']), "DisplayName"), $arFields);
		}
		$this->atLeatOneAccountAdded = true;
		$arEditLinks = array();
		$props = $this->getProps($arFields);
		$arFields['PopupID'] = $arFields['ID'];
		// subaccounts
		$arSubAccounts = array();
		$isCoupon = false;
		if($arFields['SubAccounts'] > 0){
			$arSubAccounts = TAccountInfo::getSubaccountsInfo($arFields, (ArrayVal($_GET, 'ExpCoupons') != '1'));
			if (isset($arSubAccounts[0]['Kind']) && $arSubAccounts[0]['Kind'] == 'C') {
				$isCoupon = true;
				$this->CouponCount += $arFields['SubAccounts'];
			}
            if (isset($arSubAccounts[0]['Kind']) && $arSubAccounts[0]['Kind'] == 'S')
                $this->CouponSubAccounts += $arFields['SubAccounts'];
		}
		// trade
		if(($arFields["Balance"] > 0)
		&& ($arFields["UserID"] == $_SESSION['UserID'])
		&& ($arFields["TradeMin"] > 0)){
			$arEditLinks["trade"] = "<a href=\"#\" onclick=\"tradeMiles({$arFields["ID"]}); return false;\">redeem</a>";
		}
		if ($isCoupon) {
			if (isBusinessMismanagement()) {
				$balance = '<a href="/agent/mismanagement.php">Temporary Blocked</a>';
			} elseif(self::$usrUnPaid)
				$balance = '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>';
			else {
                $defaultCurrency = null;
                if (isset($props->Rows['Currency']['Val']))
                    $defaultCurrency = $props->Rows['Currency']['Val'];
                $balance = getSymfonyContainer()->get(LocalizeService::class)->formatCurrency($arFields["Balance"], $defaultCurrency);
            }
		} else {
			$balance = $this->FormatBalance($arFields, $props->CodedValues);
		}
		if($balance == "Error")
			$balance = "<span class='error'>$balance</span>";
		if( ( $arFields['ProviderID'] != '' ) && ( $arFields["CanCheck"] == 0 ) && ( $arFields['BalanceFormat'] == '' ) )
			$balance = "n/a";
		if( ( $arFields['ProviderID'] != '' ) && ( $arFields["CanCheckBalance"] == 0 ) && ( $arFields['BalanceFormat'] == '' ) )
			$balance = "n/a";
		if (is_null($balance))
			$balance = "n/a";
		$arFields['FormattedBalance'] = StripTags($balance);

		// fish chase codes
		if($arFields['ProviderCode'] == 'chase' && $arFields['CheckInBrowser'] != CHECK_IN_SERVER){
			$bait = getSymfonyContainer()->get("aw.memcached")->get('chase_code_bait');
			if(is_array($bait) &&
                ($bait['state'] == 'waiting' && ($bait['accountId'] == $arFields['ID'] || $bait['accountId'] == 'all'))
            )
				$arFields['CheckInBrowser'] = CHECK_IN_SERVER;
		}

		if ( ( $arFields["CanCheck"] == "1" ) ){
			$link = "<a name=\"acc{$arFields['ID']}\"";
			if(!self::$usrUnPaid)
				$link .= " onclick='checkAccountIdx(accountInfo({$this->CheckCount}), 0, false); return false;'";
			if(self::$usrUnPaid)
			    $link .= " class='iconLink checkLink' href='#' onclick=\"showPopupWindow(document.getElementById('needPay'), true); return false;\">".$Interface->getTitleIconLink('update')."</a>";
			else
			    $link .= " class='iconLink checkLink' ProviderCode='{$arFields['ProviderCode']}' CheckInBrowser='{$arFields['CheckInBrowser']}' ProviderKind='{$arFields['Kind']}' AccountID='{$arFields["ID"]}' UserName=\"".htmlspecialchars($sUserName)."\" Balance=\"".htmlspecialchars($balance)."\" href='check.php?ID={$arFields["ID"]}'>".$Interface->getTitleIconLink('update')."</a>";
			$arEditLinks["check"] = $link;
		}
		$arEditLinks["edit"] = "<a class='iconLink editLink' href=\"edit.php?ID={$arFields["ID"]}\">".$Interface->getTitleIconLink('edit')."</a>";
		$arEditLinks["delete"] = "<a class='iconLink deleteLink' href=\"#\" onclick=\"return deleteAccountConfirmation({$arFields["ID"]})\">".$Interface->getTitleIconLink('delete')."</a>";
		#begin getting balance
		if( ( $arFields['ProviderID'] != '' ) && ( $arFields["CanCheckBalance"] == 0 ) && ( $arFields['BalanceFormat'] == '' ) )
			$balance = "<a href=\"#\" onclick=\"showMessagePopup('warning', 'Balance warning', 'This particular Award Program does not offer any points so there is nothing to keep track of.'); return false;\">n/a</a>";
		if(($arFields["ErrorCode"] == ACCOUNT_UNCHECKED) && ( $arFields['ProviderID'] != '' ) && ( $arFields["CanCheckBalance"] == 1 ))
			$balance = "&nbsp;";
		if(($balance == "n/a") && ($arFields['ErrorCode'] != ACCOUNT_CHECKED) && ($arFields['ProviderID'] != "") && ( $arFields["CanCheckBalance"] == 1 )){
			$balance = "<a href=\"#\" onclick=\"clickRow({$arFields['ID']}); return false;\" onmouseover=\"overRow({$arFields['ID']})\" onmouseout=\"outRow({$arFields['ID']})\">Error</a>";
		}
		if (self::$usrUnPaid) 
			$balance = '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>';
		if (isBusinessMismanagement())
			$balance = '<a href="/agent/mismanagement.php">Temporary Blocked</a>';
		#end getting balance
		$sExpirationDate = "&nbsp;";
		if(isset($_GET['TestExpiration']))
			$arFields['ExpirationDate'] = date('Y-m-d', strtotime("+1 month"));

        if( $arFields['ExpirationDate'] != '' ){
            $this->formatExpirationDateVars($arFields, $props, $sExpirationDate, $sExpirationWarning);
        }
        if($arFields['Balance'] > 0){
            if((($arFields["CanCheckExpiration"] == CAN_CHECK_EXPIRATION_YES)           && ($arFields["ExpirationAutoSet"] != EXPIRATION_UNKNOWN) && ($arFields['ExpirationDate'] == ''))
            || (($arFields["CanCheckExpiration"] == CAN_CHECK_EXPIRATION_NEVER_EXPIRES) && ($arFields['ExpirationAlwaysKnown'] == '1'))){
                if ($this->ShowExpirationDates || ($this->ExpirationDatesShown < EXPIRATION_DATE_LIMIT)){
                    $sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$arFields['ID']}').innerHTML, 'success'); return false;\"><table cellpadding='0' cellspacing='0'><tr><td><div class='success state' title=\"{$this->NothingExpiresText[$arFields['Currency']]}\"></div></td><td><div class='date successDate'>{$this->NothingExpiresHtml[$arFields['Currency']]}</div></td></tr></table></a>";
                    $sExpirationWarning = str_ireplace("[DisplayName]", $arFields['DisplayName'], "We've determined that no points or miles are
                    due to expire on this reward program. We cannot guarantee this, as reward program rules
                    change all the time, so the best way to check is to contact [DisplayName] directly.
                    We will do our best at monitoring this reward program, and notifying you of any changes.");
                    if ($arFields["ExpirationDateNote"] != "")
                        $sExpirationWarning .= "<br><br>".html_entity_decode($arFields["ExpirationDateNote"]);
                    $sExpirationDate .= "<div style='display: none;' id='expMessage{$arFields['ID']}'>$sExpirationWarning</div>";
                    $this->ExpirationDatesShown++;
                }
                else{
                    $sExpirationDate = PLEASE_UPGRADE_FOR_EXPIRATION;
                }
            }else{
                if(($this->ShowExpirationDates || $arFields["ExpirationAutoSet"] == EXPIRATION_UNKNOWN) && !$isCoupon && ($arFields['ExpirationDate'] == '')){
                    $sExpirationDate = "<table cellpadding='0' cellspacing='0'><tr><td><div class='warning state' title='Expiration unknown'></div></td><td><div class='date warningDate'>Unknown</div></td></tr></table>";
                    if (!is_null($arFields["ProviderID"]))
                        $sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$arFields['ID']}').innerHTML, 'warning'); return false;\">" .$sExpirationDate. "</a>";
                    if ($arFields['ExpirationUnknownNote'] == '')
                        $sExpirationWarning = str_ireplace("[DisplayName]", $arFields['DisplayName'],
                            "At this point AwardWallet doesn’t know how to get your expiration date for this program.
                            We are constantly working on figuring out how to calculate expiration dates for reward programs.
                            If you happen to know a way to determine expiration date for [DisplayName] by looking at
                            your profile please send us a note with a detailed description of what you do to figure out
                            expiration and we will attempt to implement it.");
                    else
                        $sExpirationWarning = $arFields['ExpirationUnknownNote'];
                    $sExpirationDate .= "<div style='display: none;' id='expMessage{$arFields['ID']}'>$sExpirationWarning</div>";
                }
            }
        }
        if(($arFields['DontTrackExpiration'] == '1') && ('' != $arFields['ProviderID'])){
            $sExpirationDate = Lookup('Currency', 'CurrencyID', 'Name', $arFields['Currency']) . " don't<br/>expire";
        }
		$sExpirationDate = (self::$usrUnPaid) ? '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>' : $sExpirationDate;
		if (isBusinessMismanagement())
			$sExpirationDate = '<a href="/agent/mismanagement.php">Temporary Blocked</a>';
		unset( $sAccountNumber );
		if (isset($props->Number))
			$sAccountNumber = $props->Number;
		if(isset($sAccountNumber) && ($sAccountNumber!= $arFields["Login"]))
			$arFields['Number'] = html_entity_decode($sAccountNumber);
		
		# hide credit card
		if (isset($sAccountNumber)){
			$hidden = hideCreditCards(array('Account' => $sAccountNumber), $arFields['Kind']);
			$sAccountNumber = $hidden["Account"];
		}
		$arFields = hideCreditCards($arFields, $arFields['Kind']);
		
		$bCanRedirect = true;
		$bShowExt = true;
		$arFields['ShowBalance'] = true;
		if ( $arFields["UserID"] != $_SESSION['UserID'] ) {
			switch ( $arFields["AccessLevel"] ) {
				case ACCESS_READ_NUMBER:
					$balance = "";
					$arFields['ShowBalance'] = false;
				case ACCESS_READ_BALANCE_AND_STATUS:
					$bShowExt = false;
					$bCanRedirect = false;
					unset( $arEditLinks["check"] );
					$sExpirationDate = "&nbsp;";
					$arFields["ShowMoreInfo"] = false;
				case ACCESS_READ_ALL:
					$bCanRedirect = false;
					unset( $arEditLinks["edit"] );
					unset( $arEditLinks["delete"] );
				case ACCESS_WRITE:
				default:
					break;
			}
		}
		
		//Last Update time
		global $lastUpdateDates;
		if(
			(
				$_SESSION['UserID'] == $arFields["UserID"] || 
				(
					$_SESSION['UserID'] != $arFields["UserID"] && 
					(
						$arFields["AccessLevel"] == ACCESS_READ_ALL || 
						$arFields["AccessLevel"] == ACCESS_WRITE
					)
				)
			) 
			&& $arFields["State"] > 0 
			&& $arFields["ErrorCode"] == 1
			&& SITE_MODE == SITE_MODE_PERSONAL			
		){
			$lastUpdateDates[] = $arFields["RawUpdateDate"];
		}
		
		$this->TuneEditLinks($arFields, $arEditLinks);
		if ( isset($arEditLinks['check']) )
			$this->CheckCount++;
		if(count($arEditLinks) > 0)
			$manageLinks = $Interface->getEditIconLinks($arEditLinks);
		else
			$manageLinks = "";
		$manageLinks = "<td id=manageCell{$arFields['ID']} class='manage leftDots notPrintable printW0'>$manageLinks</td>";
		//if( ( $arFields["ErrorCode"] != ACCOUNT_CHECKED ) && ( $arFields["ErrorCode"] != ACCOUNT_WARNING ) && ( $arFields["ErrorCode"] != ACCOUNT_UNCHECKED ) && ( $arFields["CanCheck"] == 1 ) )
		//	$sBgColor = "errorBg";
		$sRedirectURL = "redirect.php?ID=" . $arFields["ID"];
		if( $arFields["ProviderID"] != "" ){
			// basic account
				#begin getting program link
				$sProgramLink = $this->GetProgramLink($arFields, $sRedirectURL, $bCanRedirect);
				#end getting program link
				$sProgramIcon = $this->GetProgramIcon($arFields, $AllianceEliteLevels);
				#begin getting account
				$sAccount = "<span class='login'>".$arFields["Login"]."</span>";
				if($arFields['SavePassword'] == SAVE_PASSWORD_LOCALLY)
					$sAccount .= "<span class='localPassword' title='The password for this reward program is stored locally and not in the AwardWallet database.'>&nbsp;</span>";
				if(isset($sAccountNumber) && (strtolower(preg_replace("/\s/ims", "", $sAccountNumber)) != strtolower(preg_replace("/\s/ims", "", $arFields["Login"]))) && (stripos($arFields["comment"], $sAccountNumber)===false))
					$sAccount .= "<br><table class='whiteBgRow' cellspacing='0'><tr><td class='left'></td><td class='center'><span class='login'>" . $sAccountNumber . "</span></td><td class='right'></td></tr></table>";
				if($arFields["comment"] != "") {
					getSymfonyContainer();
					$sAccount .= "<div class='comment'>(" . AwardWallet\MainBundle\Globals\StringHandler::strLimit($arFields["comment"], 35) . ")</div>";
				}
				#end getting account
		}
		$accountState = $this->AccountState($arFields);
		if($arFields['CheckInBrowser'] > 0)
			$Interface->ScriptFiles["date"] = "/lib/3dParty/date.js";
	}
	if( $arFields["TableName"] == "Coupon" ){	
		$this->CouponCount++;
		$arFields['PopupID'] = $arFields['ID'];
		$sCouponState = CouponState( $arFields );
		$arFields["ErrorCode"] = $sCouponState;
		$arEditLinks = array();
		$arEditLinks["edit"] = "<a title='Edit' class=\"iconLink editLink\" href=\"/coupon/edit.php?ID={$arFields["ID"]}\">".$Interface->getTitleIconLink('edit')."</a>";
        $arEditLinks["delete"] = "<a title='Delete' class=\"iconLink deleteLink\" href=\"#\" onclick=\"return deleteCouponConfirmation({$arFields["ID"]});\">".$Interface->getTitleIconLink('delete')."</a>";
		if ( $arFields["UserID"] != $_SESSION['UserID'] ) {
			switch ( $arFields["AccessLevel"] ) {
				case ACCESS_READ_NUMBER:
				case ACCESS_READ_BALANCE_AND_STATUS:
				case ACCESS_READ_ALL:
					unset( $arEditLinks["edit"] );
					unset( $arEditLinks["delete"] );
				case ACCESS_WRITE:
				default:
					break;
			}
		}
		if(count($arEditLinks) > 0)
			$manageLinks = $Interface->getEditIconLinks($arEditLinks);
		else
			$manageLinks = "";
		$manageLinks = "<td class='leftDots notPrintable printW0 manage'>$manageLinks</td>";
		if (empty($arFields['Number']) && !empty($arFields['CardNumber']))
		    $arFields['Number'] = $arFields['CardNumber'];
        if (empty($arFields['comment']) && !empty($arFields['Description']))
            $arFields['comment'] = $arFields['Description'];
        if (empty($arFields['Balance']) && !empty($arFields['Value']))
            $arFields['Balance'] = $arFields['Value'];
	}
	if($arFields["FullProviderName"] != $arFields["ProviderName"])
		 $arFields["ProviderName"] = "<span title='".addslashes($arFields["FullProviderName"])."'>{$arFields['ProviderName']}</span>";
	# common for all types
	$this->PostFormatFields($arFields);
	$rowClasses = "accounts $sBgColor after$lastRowKind";
	if(($arFields['TableName'] == 'Account') && ($accountState != ""))
		$rowClasses .= " error";
	?>
	<tr class="<?=$rowClasses?>" id="row<?=$arFields['ID']?>" checkInBrowser="<?=$arFields['CheckInBrowser']?>"
	data-checkinbrowser="<?=$arFields['CheckInBrowser']?>">
		<td class="c1 notPrintable printW0">
			<?
				if($afterHeader){
					echo "<div class=\"icon\"><div class=\"inner\"></div></div>";
					$afterHeader = false;
				}
			?>
		</td>
		<td class="c1a notPrintable printW0">
	<?
	if($sBgColor == "whiteBg")
		$lastRowKind = "White";
	else
		$lastRowKind = "Gray";
	$this->DrawFirstCell($arFields);
	$this->DrawPopup($arFields, null);
	?>
	</td>
	<?
	# end common for all types
	if( $arFields["TableName"] == "Account" ){
		if( $arFields['ProviderID'] != "" ){ // begin basic account
				$lastBalance = $arFields['LastBalance'];
				$lastChangeDate = $arFields['LastChangeDate'];
                // detected cards
                $detectedCards = '';
                if ($arFields['Kind'] == PROVIDER_KIND_CREDITCARD && isset($props->CodedValues['DetectedCards'])) {
                    $allDetectedCards = @unserialize($props->CodedValues['DetectedCards']);
                    $countDetectedCards = count($allDetectedCards);
                    if (is_array($allDetectedCards) && $countDetectedCards > 0) {
                        // showing Detected Cards
                        $showingDC = false;
                        if ($countDetectedCards > $arFields['SubAccounts'] && $arFields['SubAccounts'] != 0)
                            $showingDC = true;

                        $detectedCardHTML = '<div>We were able to login to your account and we see that you have the following cards in your profile, please read the notes for each card</div></br>';/*review*/
                        $detectedCardHTML .= '<table cellspacing="0" border="0" class="roundedTable" width="100%"><tbody>';
                        foreach ($allDetectedCards as $i => $detectedCard) {
                            // column colors
                            $sBgColor = "whiteBg";
                            if (($i % 2) != 0)
                                $sBgColor = "grayBg";

                            if ($sBgColor == "whiteBg")
                                $lastRowKind = "White";
                            else
                                $lastRowKind = "Gray";
                            $rowClasses = "accounts $sBgColor after$lastRowKind";

                            $detectedCardHTML .= "<tr class=\"{$rowClasses}\"><td class='bordered'><div style='padding: 7px;'><b>".$detectedCard['DisplayName']."</b></div></td><td class='bordered borderRight'><div style='padding: 7px;'>";
                            $detectedCardHTML .= (isset($detectedCard['CardDescription']))
                                ? $detectedCard['CardDescription'] : "";
                            $detectedCardHTML .= "</div></td></tr>";
                            // showing Detected Cards
                            if (!empty($detectedCard['CardDescription'])
                                && $detectedCard['CardDescription'] != C_CARD_DESC_ACTIVE)
                                $showingDC = true;
                        }// foreach ($allDetectedCards as $i => $detectedCard)
                        $detectedCardHTML .= '</tbody></table>';
                        // showing Detected Cards (exclude business interface with a complete list of accounts)
                        if ($showingDC && $this->Page != '/account/overview.php') {
                            $detectedCards = "<span style='float: right;' title='Detected cards'><a href='#' onclick=\"showDetectedCards(document.getElementById('detectedCards{$arFields['ID']}').innerHTML, 'detectedCards'); return false;\"><img src=\"/images/creditcards/creditCards.png\" alt=\"Detected cards\"></a></span>";
                            $detectedCards .= "<div style='display: none;' id='detectedCards{$arFields['ID']}'>{$detectedCardHTML}</div>";
                        }
                    }// if (count($allDetectedCards) > 0)*/
                }// if ($arFields['Kind'] == PROVIDER_KIND_CREDITCARD)
                unset($props->CodedValues['DetectedCards']);
				?>
				<td class="c2"><?
				if($accountState != "")
					echo $accountState;
				else
					if(isset($lastBalance) && isset($arFields['Balance']) && isset($lastChangeDate) && (abs($arFields['Balance'] - $lastBalance) > 0.001)){
						if($Connection->SQLToDateTime($lastChangeDate) > (time() - SECONDS_PER_DAY)){
							if(($arFields['Balance'] - $lastBalance) > 0)
								$class = "incBar";
							else
								$class = "decBar";
							echo "<div class=\"redBar {$class}\" title=\"Balance was changed within last 24 hours\"></div>";
						}
					}
				?></td>
				<td id="accountCell<?=$arFields['ID']?>" class="program" <?=$sProgramIcon?> ><?=$sProgramLink?> <?=$detectedCards?></td>
				<td class="login leftDots">
				<?=$sAccount?>
				</td>
				<?
				$eliteLevels = array();
				if($arFields['CheckInBrowser'] > 0){
					$eliteLevels = getEliteLevelFields($arFields['ProviderID']);
				}

				?>
				<td class="leftDots Status withGoal" eliteLevels='<?=json_encode($eliteLevels)?>' eliteLevelsCount="<?=$arFields['EliteLevelsCount']?>">
				<?
					$status = $this->getAccountProperty($arFields['ID'], "", PROPERTY_KIND_STATUS);
					$arFields['Status'] = $status;
					if($status != null && $arFields['CheckInBrowser'] != CHECK_IN_CLIENT){
						$eliteLevelFields = getEliteLevelFields($arFields['ProviderID'], $status);
						$EliteLevelsCount = intval($arFields['EliteLevelsCount']);
						if (!empty($eliteLevelFields)) {
                            $elitism = getElitism($eliteLevelFields['Rank'], $EliteLevelsCount) * 100;
                            # for limiting progress
                            if (!isGranted('ROLE_IMPERSONATED') && $elitism > 100) {
                                $elitism = 100;
                            }
                            # ---
                        } else {
						    $elitism = 0;
                        }
                        $tip = $this->tips['multi_accounts'];
						$label = (isset($eliteLevelFields['Name'])) ? $eliteLevelFields['Name'] : $status;
						if (isset($arFields['Accounts']) && $arFields['Accounts'] > 1)
							$label = "<span title=\"$tip\">$label<span style=\"padding-left: 2px;\">*</span></span>";
							
						echo "<div class='cont'>$label</div>";
						echo "<div class='goal' title='Progress to the highest possible elite level on this program: {$elitism}%'><div class='progress' style='width: {$elitism}%'></div></div>"; /*checked*/
					}

				?>
				</td>
				<td class="balance leftDots<?
					if($accountState != "")
						echo " error";
					if($arFields['Goal'] != "")
						echo " withGoal";
				?>" nowrap>
				<div class="balancePad"><table cellpadding="0" cellspacing="0"><tr>
				<?
				if(isset($balance) && isset($lastBalance) && isset($lastChangeDate) && (!is_null($arFields['Balance'])) && (abs($arFields['Balance'] - $lastBalance) > 0.001) && !self::$usrUnPaid && !isBusinessMismanagement()){
					$rawChange = $arFields['Balance'] - $lastBalance;
					$change = formatFullBalance(abs($rawChange), $arFields['ProviderCode'], $arFields['BalanceFormat'], true);
					if($rawChange > 0){
						$class = "inc";
						$change = "+".$change;
					}
					else{
						$class = "dec";
						$change = "-".$change;
					}
					$arFields['LastChange'] = $change;
					echo "<td class='balanceCell'><div class=\"balance\">$balance</div>";
					echo "<div class=\"{$class}Balance\">$change</div></td>";
					echo "<td class='changeCell'><div class=\"{$class}Arrow\">";
					echo "</div></td>";
				}
				else
					echo "<td class='balanceCell'><div class='balance'>".$balance."</div></td>";
				?>
				</tr></table></div>
				<? if($arFields['Goal'] != '') {
					$goalProgress = $arFields['Goal'] > 0 ? round(min(intval($arFields['Balance']), $arFields['Goal']) / $arFields['Goal'] * 100) : 100;
					echo "<div class='goal' title='You goal {$arFields['Goal']}, progress: {$goalProgress}%'><div class='progress' style='width: {$goalProgress}%'></div></div>";
				}
				?>
				</td>
				<td class="leftDots expiration">
				<?
				echo $sExpirationDate;
				?>
				</td>
				<td class="expirationCorner">
				<?
				if($arFields['DontTrackExpiration'] == '1')
				    echo "<div class='greenCorner' title='AwardWallet not watching expiration for this program'></div>";
				elseif(in_array($arFields['ExpirationAutoSet'], array(EXPIRATION_UNKNOWN, EXPIRATION_USER))
                        && ($arFields['ExpirationDate'] != '')) {
					echo "<div class='greenCorner' title='This date is not being updated by AwardWallet automatically'></div>";
					}
				?>
				</td>
				<? if($this->ShowManageLinks) echo $manageLinks ?>
				</tr>
				<?
				if(!isset($arFields['Accounts']) || (isset($arFields['Accounts']) && $arFields['Accounts'] == 1))
					$this->DrawSubAccounts($arFields, $arSubAccounts, $accountState, $nRow, $props);
		} // end of basic account
		else{ // begin of custom account
            $sProgramName = $arFields["DisplayName"];
            $lastBalance = $arFields['LastBalance'];
            $lastChangeDate = $arFields['LastChangeDate'];
            if( $arFields['LoginURL'] != '' ){
                if( !preg_match( "/^http(s?)\:\/\//i", $arFields['LoginURL'] ) )
                    $arFields['LoginURL'] = "http://" . $arFields['LoginURL'];
                $sProgramName = "<a href='".getSymfonyContainer()->get("router")->generate("aw_out", ["url" => $arFields['LoginURL']])."' target=_blank>{$sProgramName}</a>";
		    }
            if($arFields['DontTrackExpiration'] == '1'){
                //$sExpirationDate = "Points don't<br/>expire";
                $sExpirationDate = "<table cellpadding='0' cellspacing='0'><tr><td><div class='success state' title='Expiration date'></div></td><td><div class='date successDate'>Points don't<br/>expire</div></td></tr></table>";
            }
			?>
		<td class="c2"><a href='#' onclick='return false;'><?=$this->AccountState( $arFields )?></a></td>
		<td class="program" id="accountCell<?=$arFields['ID']?>"><?=$sProgramName?></td>
		<?
			getSymfonyContainer();

            $change = '';
            $changeCell = '';
            if(isset($balance) && isset($lastBalance) && isset($lastChangeDate) && (!is_null($arFields['Balance'])) && (abs($arFields['Balance'] - $lastBalance) > 0.001) && !self::$usrUnPaid && !isBusinessMismanagement()){
                $rawChange = $arFields['Balance'] - $lastBalance;
                $change = formatFullBalance(abs($rawChange), $arFields['ProviderCode'], $arFields['BalanceFormat'], true);
                if ($rawChange > 0) {
                    $class = "inc";
                    $change = "+".$change;
                }
                else {
                    $class = "dec";
                    $change = "-".$change;
                }
                $arFields['LastChange'] = $change;
                $change = "<div class=\"{$class}Balance\">$change</div></td>";
                $changeCell =  "<td class='changeCell'><div class=\"{$class}Arrow\">";
            }

		?>
		<td class="login leftDots"><span class="login"><?=$arFields["Login"]?></span><?(($arFields["comment"] != "") ? print "<div class=\"comment\">(" . AwardWallet\MainBundle\Globals\StringHandler::strLimit($arFields["comment"], 35) . ")</div>" : "" )?></td>
		<td class="leftDots Status withGoal">
				</td>
		<td nowrap class="balance leftDots<?
            if($arFields['Goal'] != "")
                echo " withGoal";
        ?>">
            <?
                if ($arFields['Goal'] != "") {
                    ?>
                    <div class="balancePad"<?=(empty($change) ? ' style="padding-bottom: 7px;"' : '')?>>
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <div class="balance"><?=$balance?></div>
                                    <?=$change?>
                                </td>
                                <?=$changeCell?>
                                <td class="stateCell">
                                    <div class="state custom" title="This is a custom program that you added. Award Wallet cannot automatically check the balance on this program."></div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?
                    $goalProgress = $arFields['Goal'] > 0 ? round(min(intval($arFields['Balance']), $arFields['Goal']) / $arFields['Goal'] * 100) : 100;
                    echo "<div class='goal' title='You goal {$arFields['Goal']}, progress: {$goalProgress}%'><div class='progress' style='width: {$goalProgress}%'></div></div>";
                } else {
                    ?>
                    <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <div class="balance"><?=$balance?></div>
                                <?=$change?>
                            </td>
                            <?=$changeCell?>
                            <td class="stateCell">
                                <div class="state custom" title="This is a custom program that you added. Award Wallet cannot automatically check the balance on this program."></div>
                            </td>
                        </tr>
                    </table>
                    <?
                }
            ?>

        </td>
		<td nowrap class="expiration leftDots" colspan="2"><?=$sExpirationDate?></td>
			<? if($this->ShowManageLinks) echo $manageLinks ?>
	</tr>
	<?
				} // end custom account
			} // end of account
			else{ // begin coupon
			?>
		<td class="c2"><?=CouponIcon($arFields["ErrorCode"]) ?></td>
		<td class="program" id="accountCell<?=$arFields['ID']?>"><?=$arFields["DisplayName"]?><br>
		</td>
		<td colspan="3" class="login leftDots">
			<table cellspacing='0' cellpadding='0' border='0' style="border: 0px none;" id="tblNoBorder">
			<tr>
				<td style='font-weight: normal;'>Coupon Description:</td>
				<td width='10'>&nbsp;</td>
				<td><?=$arFields["Description"]?></td>
			</tr>
			<tr>
				<td style='font-weight: normal;'>Coupon Value:</td>
				<td width='10'>&nbsp;</td>
				<td><?=$arFields["Value"]?></td>
			</tr>
			</table>
		</td>
        <?
            // refs #5923
            $attributes = "class='warning state' title='Expiration unknown'";
            if (CouponState($arFields) != COUPON_NO_EXPIRATION) {
                $sExpiresAt = strtotime($arFields["ExpirationDate"]);
                $nExpires = ( time() - $sExpiresAt ) / SECONDS_PER_DAY;
                if (($nExpires > -90) && ($nExpires <= 0)) {
                    $attributes = "class='warning state' title='Expiration warning'";
                }
                if ($nExpires <= -90) {
                    $attributes = "class='success state' title='Expiration date'";
                }
                if ($nExpires > 0) {
                    $attributes = "class='errorCoupon state' title='Coupon has expired'";
                }
            }
        ?>
		<td nowrap class="expiration leftDots" colspan="2">
			<table border="0" cellpadding="0" cellspacing="0"><tr><td><div <?=$attributes?>></div></td>
			<td><div class='date'><?=(CouponState($arFields) == COUPON_NO_EXPIRATION) ? 'Unknown' : date(DATE_FORMAT, $Connection->SQLToDateTime($arFields["ExpirationDate"]) )?></div></td></tr>
			</table>
		</td>
	<?
			if($this->ShowManageLinks){
				echo $manageLinks;
			}
			} // end coupon
			if ($_i == $this->MaxProgram && (isset($arPrograms[$_i]) || PERSONAL_INTERFACE_MAX_ACCOUNTS - $this->ProgramCount==0) && $this->ShowLimitMessage) {
				$lastRow = true;
				echo '<tr>
						<td class="c1 notPrintable printW0 topBorder"></td>
						<td class="topBorder" colspan="9">
							'.$this->ShowLimitMessage.'
						</td>
					  </tr>';

			}

			$this->Rows[] = $this->addProperties($arFields);
            if(isset($arSubAccounts) && !empty($arSubAccounts))
                foreach($arSubAccounts as $row){
                    $row['Kind'] = 'SubAccount';
                    $this->Rows[] = $this->addProperties($row);
                }
			if ($lastRow)
				break;
		}
		}
	}

	protected function addProperties(array $row){
		global $arPropertiesKinds;
		$subAccountID = ArrayVal($row, 'SubAccountID');
		if(isset($this->AllProps[$row['ID']][$subAccountID]))
			$row['Properties'] = $this->AllProps[$row['ID']][$subAccountID];
		return $row;
	}

	protected function formatExpirationDateVars($arFields, $props, &$sExpirationDate, &$sExpirationWarning)
	{
		global $Connection;
		$d = $Connection->SQLToDateTime($arFields["ExpirationDate"]);
		$sExpirationDate = date(DATE_FORMAT, $d);
		if ($arFields["ProviderCode"] == "continental")
			$sExpirationDate = "Miles don't<br/>expire";
		$nExpires = (time() - $d) / SECONDS_PER_DAY;
		if (($this->ShowExpirationDates)
				|| ($this->ExpirationDatesShown < EXPIRATION_DATE_LIMIT)
				|| ($arFields["ExpirationAutoSet"] == EXPIRATION_UNKNOWN)
		) {
			if ($arFields["ExpirationDateNote"] != "") {
				$sNote = "<br><br>
	This expiration date was calculated by AwardWallet and not by {$arFields['DisplayName']},
	so this date could be inaccurate. AwardWallet has no way to guarantee the accuracy of this calculated expiration date.
	Please remember that the most accurate way to determine your point expiration date
	is to contact {$arFields['DisplayName']} directly. Here is how we got this value:<br><br>";
				$replacement = '$1';
				if (!isset($props->CodedValues['LastActivity']))
					$replacement = '$2';
				$expMsg = preg_replace('/\[activity\](.*)\[\/activity\]\s*\[noactivity\](.*)\[\/noactivity\]/ims', $replacement, $arFields['ExpirationDateNote'], 1);
				$sNote .= $expMsg;
				$sDefaultValue = "Unknown date";
				$sNote = str_ireplace("[LastActivity]", ArrayVal($props->CodedValues, "LastActivity", $sDefaultValue), $sNote);
                ## New property is used in calculating Expiration Date for preflight, lukoil etc.
                $sNote = str_ireplace("[EarningDate]", ArrayVal($props->CodedValues, "EarningDate", $sDefaultValue), $sNote);
			} else
				$sNote = "";
			$sNote = html_entity_decode($sNote);
			$sExpirationWarning = "The balance on this award program [ExpireAction] on " . date(DATE_LONG_FORMAT, $d);
			if ($arFields["ExpirationWarning"] != "") {
				$sExpirationWarning = str_ireplace("[NoNote]", "", $arFields["ExpirationWarning"]);
				if ($sExpirationWarning != $arFields["ExpirationWarning"])
					$sNote = "";
			}
			$sExpirationWarning .= $sNote;
            // refs #8918
            if (empty($sNote) && empty($arFields["ExpirationWarning"]) && $nExpires <= 0)
                $sExpirationWarning = "{$arFields['DisplayName']} on their website state that the balance on this award program is due to expire on " . date(DATE_LONG_FORMAT, $d);
			$sExpireAction = "due to expire";
			$popupId = ArrayVal($arFields, 'SubAccountID', $arFields['ID']);
			if (($nExpires > -90) && ($nExpires <= 0)) {
				//$arFields["ExpirationState"] = "soon";
				$sExpireAction = "due to expire";
                $sExpirationDate = "<table cellpadding='0' cellspacing='0'><tr><td><div class='warning state' title='Expiration warning'></div></td><td><div class=\"date\">" . $sExpirationDate . "</div></td></tr></table>";
                    if (!isset($arFields['SubAccountID']))
				        $sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$popupId}').innerHTML, 'warning'); return false;\">" .$sExpirationDate. "</a>";
				if ($arFields["RenewNote"] != "") {
                    $sExpirationDate .= "<a href=# onclick=\"showRenewNote({$arFields["ID"]}); return false;\"><img src=\"/lib/images/refreshSmall.gif\" style=\"margin-bottom: -1px; margin-right: 6px; margin-left: 0px;\" border=0></a><a href=# class='renew' onclick=\"showRenewNote({$arFields["ID"]}); return false;\">Renew now</a>";
                }
			}
			if ($nExpires <= -90) {
				//$arFields["ExpirationState"] = "far";
				$sExpireAction = "due to expire";
                $sExpirationDate = "<table cellpadding='0' cellspacing='0'><tr><td><div class='success state' title='Expiration date'></div></td><td><div class='date successDate'>" . $sExpirationDate . "</div></td></tr></table>";
                if (!isset($arFields['SubAccountID']))
				    $sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$popupId}').innerHTML, 'success'); return false;\">" .$sExpirationDate. "</a>";
			}
			if ($nExpires > 0) {
				//$arFields["ExpirationState"] = "expired";
				$sExpireAction = "might have expired";
                $sExpirationDate = "<table cellpadding='0' cellspacing='0'><tr><td><div class='error state' title='Miles have expired'></div></td><td><div class='date errorDate'>" . $sExpirationDate . "</div></td></tr></table>";
                if (!isset($arFields['SubAccountID']))
				    $sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$popupId}').innerHTML, 'error'); return false;\">" . $sExpirationDate . "</a>";
			}
			if (self::$usrUnPaid) {
				$sExpirationDate = '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>';
			}
			if (isBusinessMismanagement())
				$sExpirationDate = '<a href="/agent/mismanagement.php">Temporary Blocked</a>';
			if ($arFields["ExpirationAutoSet"] > EXPIRATION_UNKNOWN)
				$this->ExpirationDatesShown++;
            if ($arFields["ExpirationAutoSet"] == EXPIRATION_USER)
                $sExpirationWarning = "You have specified that the balance on this award program is due to expire on " . date(DATE_LONG_FORMAT, $d);/*checked*/
			$sExpirationWarning = str_ireplace("[ExpireAction]", $sExpireAction, $sExpirationWarning);
			$sExpirationDate .= "<div style='display: none;' id='expMessage{$arFields['ID']}'>$sExpirationWarning</div>";
		} else {
			$sExpirationDate = PLEASE_UPGRADE_FOR_EXPIRATION;
		}
	}

	function DrawSubAccounts($arFields, &$arSubAccounts, $accountState, &$nRow, $props){
		global $Connection, $Interface;
		$fRow = $nRow;
		$rowCount = count($arSubAccounts);
		#if($rowCount > 0)
		#	echo '<tr><td colspan="9" class = "topgradient accounts"></tr>';
		foreach($arSubAccounts as $i => $row){
			#if($row["Balance"] > 0)
			#	$this->Totals[$arFields['Kind']]["Points"] += round(floatval($row["Balance"]));
			ob_start();
			$nRow++;
			$subProps = &$row['SubAccountInfo'];
			$isCoupon = isset($row['Kind']) && $row['Kind'] == 'C';
			if ($isCoupon) {
				$grayFont = '';
				if ($subProps->CodedValues['Quantity'] == 0)
					$grayFont = ' style="color: #BCBCBC;"';
				$row['DisplayName'] = $subProps->CodedValues['Name'];
			}
			if(isset($subProps->Number) &&($subProps->Number != $arFields["Login"]))
				$row['DisplayName'] .= "<br><span style='font-size: 11px; color: #666666'>(" . $subProps->Number . ")</span>";
			$arFields['PopupID'] = $arFields['ID'].'sa'.$row['SubAccountID'];

			$sBgColor = "whiteBg";    
			if( ( $nRow % 2 ) == 0 )
				$sBgColor = "grayBg";

			if($sBgColor == "whiteBg")
				$lastRowKind = "White";
			else
				$lastRowKind = "Gray";

			$rowClasses = "accounts $sBgColor after$lastRowKind";

			//************* replace balance // rewrite FormatBalance function api?
			$tmpBalance = $arFields['Balance'];
			$arFields['Balance'] = $row['Balance'];
			$subProps->CodedValues['SubAccountCode'] = $row['Code'];
			$balance = $this->FormatBalance($arFields, $subProps->CodedValues);
			$arFields['Balance'] = $tmpBalance;
			//*************
			?>
			<tr class="accounts <!-- RowClass -->" id="row<?=$arFields['ID']?>sa<?=$row['SubAccountID']?>" style="<!-- RowStyle -->">
			<td class="c1 notPrintable printW0">
				<!-- DownCorner -->
			</td>
			<td class="c1a subaccountplus notPrintable printW0">
			<?
            $this->DrawPopup($arFields, $row['SubAccountID']);
            $this->DrawFirstCell($arFields);
            $subRedirectURL = false;
            $sRedirectURL = "redirect.php?ID=" . $arFields["ID"];
            if (isset($subProps->CodedValues['RedirectURL'])){
                $subRedirectURL = true;
                $sRedirectURL = "redirect.php?ID=" . $arFields["ID"] . "&SubAccountID=" . $row['SubAccountID'];
                $arFields["DisplayName"] = $row['DisplayName'];
            }
			?>
			</td>
			<td class="c2">
			</td>
			<td class="login subaccount" colspan="3"<?=((isset($grayFont)&&$grayFont!="")? $grayFont : '')?>><?=($subRedirectURL ? $this->GetProgramLink($arFields, $sRedirectURL, true): $row['DisplayName'])?></td>
			
			<td class="balance leftDots<?
				if($accountState != "")
					echo " error";
			?>" nowrap>
			<? if($arFields['ShowBalance']) { ?>
			<div class="balancePad"><table cellpadding="0" cellspacing="0"><tr>
			<?
			$lastBalance = $row['LastBalance'];
			$lastChangeDate = $row['LastChangeDate'];
			if(isset($lastBalance) && isset($row['Balance']) && !$isCoupon && !self::$usrUnPaid && !isBusinessMismanagement()){
				$rawChange = $row['Balance'] - $lastBalance;
				$change = formatFullBalance(abs($rawChange), $arFields['ProviderCode'], $arFields['BalanceFormat'], true);
				if($rawChange > 0){
					$class = "inc";
					$change = "+".$change;
				}
				else{
					$class = "dec";
					$change = "-".$change;
				}
				$arSubAccounts[$i]['LastChange'] = $change;
				echo "<td class='balanceCell'><div class=\"balance\">".$balance."</div>";
				echo "<div class=\"{$class}Balance\">$change</div></td>";
				echo "<td class='changeCell'><div class=\"{$class}Arrow\">";				
				echo "</div></td>";
			}
			else {
				if($isCoupon) {
					$balance = (self::$usrUnPaid) ? '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>' : getSymfonyContainer()->get(LocalizeService::class)->formatCurrency($balance, $subProps->CodedValues['Currency'])." &times; ".$subProps->CodedValues['Quantity'];
					if (isBusinessMismanagement())
						$balance = '<a href="/agent/mismanagement.php">Temporary Blocked</a>';
					echo "<td".((isset($grayFont)&&$grayFont!="")? $grayFont : '')."><div class='balance'>".$balance."</div></td>";
				} else {
					$balance = (self::$usrUnPaid) ? '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>' : $balance;
					if (isBusinessMismanagement())
						$balance = '<a href="/agent/mismanagement.php">Temporary Blocked</a>';
					echo "<td><div class='balance'>".$balance."</div></td>";
				}
			}
			?>			
			</tr></table></div>
			<? } ?>
			</td>
			<td class="leftDots expiration">
			<?
				if($isCoupon) {
					$sExpirationDate = '';
					if (is_null($row['ExpirationDate']) && $row['ExpirationAutoSet'] != EXPIRATION_UNKNOWN) {
						$sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$row['SubAccountID']}').innerHTML, 'success'); return false;\"><table cellpadding='0' cellspacing='0'><tr><td><div class='success state' title='Expiration date'></div></td><td><div class='date successDate'>Coupons don't<br/>expire</div></td></tr></table></a>";
						$sExpirationWarning = "<p>We've determined that no coupons are due to expire on this daily deals site. We cannot guarantee this, as site's rules change all the time, so the best way to check is to contact [DisplayName] directly. We will do our best at monitoring this site, and notifying you of any changes.</p>
<p>We always recommend you to double check your expiration date directly with [DisplayName].</p>";
						$sExpirationWarning = html_entity_decode(str_replace("[DisplayName]", $arFields['DisplayName'], $sExpirationWarning));
						$sExpirationDate .= "<div style='display: none;' id='expMessage{$row['SubAccountID']}'>$sExpirationWarning</div>";
						$sExpiresAt = $row['sExpirationDate'];
					} elseif (!is_null($row['ExpirationDate'])) {
						$sExpiresAt = $row['sExpirationDate'];
						$sExpirationDate = date( DATE_FORMAT, $sExpiresAt );
						$nExpires = ( time() - $sExpiresAt ) / SECONDS_PER_DAY;
						if(($this->ShowExpirationDates)
						|| ($this->ExpirationDatesShown < EXPIRATION_DATE_LIMIT)){
							$sExpirationWarning = "This coupon is due to expire on ".date( DATE_LONG_FORMAT, $sExpiresAt )."";
							$sExpirationWarning = html_entity_decode($sExpirationWarning);
							if( ( $nExpires > -90 ) && ( $nExpires <= 0 ) ){
								$sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$row['SubAccountID']}').innerHTML, 'warning'); return false;\"><table cellpadding='0' cellspacing='0'><tr><td><div class='warning state' title='Expiration warning'></div></td><td><div class=\"date\">" . $sExpirationDate . "</div></td></tr></table></a>";
							}
							if( $nExpires <= -90 ){
								$sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$row['SubAccountID']}').innerHTML, 'success'); return false;\"><table cellpadding='0' cellspacing='0'><tr><td><div class='success state' title='Expiration date'></div></td><td><div class='date successDate'>" . $sExpirationDate . "</div></td></tr></table></a>";
							}
							if( $nExpires > 0 ){
								$sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$row['SubAccountID']}').innerHTML, 'error'); return false;\"><table cellpadding='0' cellspacing='0'><tr><td><div class='errorCoupon state' title='Expiration warning'></div></td><td><div class='date errorDate'>" . $sExpirationDate . "</div></td></tr></table></a>";
							}
							$sExpirationDate .= "<div style='display: none;' id='expMessage{$row['SubAccountID']}'>$sExpirationWarning</div>";
						}
						else{
							$sExpirationDate = PLEASE_UPGRADE_FOR_EXPIRATION;
						}
					}
					$sExpirationDate = (self::$usrUnPaid) ? '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>' : $sExpirationDate;
					if (isBusinessMismanagement())
						$sExpirationDate = '<a href="/agent/mismanagement.php">Temporary Blocked</a>';
					echo $sExpirationDate;
				}
				else {
					$sExpirationDate = '';
					if (is_null($row['ExpirationDate']) && $row['ExpirationAutoSet'] != EXPIRATION_UNKNOWN) {
						$sExpirationDate = "<a href='#' onclick=\"showExpirationWarning(document.getElementById('expMessage{$row['SubAccountID']}').innerHTML, 'success'); return false;\"><table cellpadding='0' cellspacing='0'><tr><td><div class='success state' title='Expiration date'></div></td><td><div class='date successDate'>Coupons don't<br/>expire</div></td></tr></table></a>";
						$sExpirationWarning = str_ireplace("[DisplayName]", $arFields['DisplayName'], "We've determined that no points or miles are
						due to expire on this reward program. We cannot guarantee this, as reward program rules
						change all the time, so the best way to check is to contact [DisplayName] directly.
						We will do our best at monitoring this reward program, and notifying you of any changes.");
						$sExpirationDate .= "<div style='display: none;' id='expMessage{$row['SubAccountID']}'>$sExpirationWarning</div>";
					} elseif (!is_null($row['ExpirationDate'])) {
						$subFields = $arFields;
						foreach(array('SubAccountID', 'ExpirationDate', 'ExpirationAutoSet', 'ExpirationWarning', 'RenewNote', 'RenewProperties') as $key)
							$subFields[$key] = $row[$key];
						$this->formatExpirationDateVars($subFields, $props, $sExpirationDate, $sExpirationWarning);
						$sExpirationDate .= "<div style='display: none;' id='expMessage{$row['SubAccountID']}'>$sExpirationWarning</div>";
					}
					$sExpirationDate = (self::$usrUnPaid) ? '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>' : $sExpirationDate;
					if (isBusinessMismanagement())
						$sExpirationDate = '<a href="/agent/mismanagement.php">Temporary Blocked</a>';
					echo $sExpirationDate;
				}

			?>
			</td>
			<td class="expirationCorner"></td>
			<td class="manage leftDots notPrintable printW0">
				<?
				#DieTrace(print_r($subProps->CodedValues, true));
				$editLinks = array();
				if($isCoupon) {
					$printOnClick = (self::$usrUnPaid) ? 'showPopupWindow(document.getElementById(\'needPay\'), true); return false;' : "printCoupon({$row['SubAccountID']}, {$arFields['ID']}, {$arFields['ProviderID']});return false;";
					$markOnClick = (self::$usrUnPaid) ? 'showPopupWindow(document.getElementById(\'needPay\'), true); return false;' : "markCoupon({$row['SubAccountID']}, {$arFields['ID']});return false;";
					if (!isset($row['SubAccountInfo']->CodedValues['UnablePrint']) || $row['SubAccountInfo']->CodedValues['UnablePrint'] == 0)
						$editLinks['print'] = "<a class='iconLink printLink' href=\"#\" onclick=\"".htmlspecialchars($printOnClick)."\">".$Interface->getTitleIconLink('print')."</a>";
					if (!isset($row['SubAccountInfo']->CodedValues['UnableMark']) || $row['SubAccountInfo']->CodedValues['UnableMark'] == 0)
						$editLinks['mark used'] = "<a class='iconLink usedLink' href=\"#\" onclick=\"".htmlspecialchars($markOnClick)."\">".$Interface->getTitleIconLink('used / unused')."</a>";
				}
				if(count($editLinks) > 0)
					echo $Interface->getEditIconLinks($editLinks);
				else
					echo '&nbsp;';
				?>
			</td>
			</tr>
		<?
			$out = ob_get_clean();
			
			$sRowStyle = '';
			$downCorner = (($nRow - $fRow) == 1) ? $this->DrawDownCorner($nRow) : '';
			$out = str_replace("<!-- DownCorner -->", $downCorner, $out);
			if (($nRow - $fRow) == 1) {
				$sRowStyle = 'border-top: #c9c9c9 solid 1px;';
				$sRowStyle .= (($nRow - $fRow) == $rowCount) ? 'border-bottom: #c9c9c9 solid 1px;' : '';
			} elseif (($nRow - $fRow) == $rowCount)
				$sRowStyle = 'border-bottom: #c9c9c9 solid 1px;';
			if(!in_array(intval($arFields['ErrorCode']), array(ACCOUNT_CHECKED, ACCOUNT_WARNING)))
				$rowClasses .= " error";
			$out = str_replace("<!-- RowStyle -->", $sRowStyle, $out);
			$out = str_replace("<!-- RowClass -->", $rowClasses, $out);
			echo $out;
													
		}			
	}

	function DrawDownCorner($nRow) {
		$imgStyle = "style=\"position:relative;top:-1px;left:52px;margin-left: -7px\"";
		if(($nRow % 2) == 1)
			return "<img src=\"/images/grayarrowdown.png\" $imgStyle />";
		else
			return "<img src=\"/images/whitearrowdown.png\" $imgStyle />";
	}

	function PostFormatFields(&$arFields){

	}

	function getProps($arFields){
		return new TAccountInfo($arFields, null, $this->AllProps);
	}

	function FormatFields(&$arFields){
		if(isset($_GET['ProviderID']))
			$arFields['DisplayName'] = $arFields['UserName'];
		$arFields["Login"] = htmlspecialchars($arFields["Login"]);
	}

	function TuneEditLinks($arFields, &$arEditLinks){

	}

	static function getLastChange($arFields, $curBalance, $subAccountId, &$lastBalance, &$lastChangeDate, &$obj = null){
		global $Connection;
		$lastBalance = null;
		$lastChangeDate = null;
		if(!isset($subAccountId) && ($arFields['LastBalance'] != '')){
			$lastBalance = $arFields['LastBalance'];
			$lastChangeDate = $arFields['LastChangeDate'];
		}
		else{
			if($arFields['ChangeCount'] > 0){
				$qHistory = new TQuery("select AccountBalanceID, trim(trailing '.' from trim(trailing '0' from round(Balance, 7))) as Balance, UpdateDate
				from AccountBalance
				where AccountID = {$arFields['ID']}".(isset($subAccountId)?" and SubAccountID = {$subAccountId}":" and SubAccountID is null")." and round(Balance, 2) <> ".round($curBalance, 2)."
				order by UpdateDate desc limit 1");
				if(!$qHistory->EOF){
					$qLast = new TQuery("select AccountBalanceID, trim(trailing '.' from trim(trailing '0' from round(Balance, 7))) as Balance, UpdateDate
					from AccountBalance
					where AccountID = {$arFields['ID']}".(isset($subAccountId)?" and SubAccountID = {$subAccountId}":" and SubAccountID is null")."
					order by UpdateDate desc limit 1");
					if($qHistory->Fields['AccountBalanceID'] != $qLast->Fields['AccountBalanceID']){
						$lastBalance = $qHistory->Fields['Balance'];
						$lastChangeDate = $qLast->Fields['UpdateDate'];
					}
				}
				//if(($arFields['LastBalance'] == '') && !isset($subAccountId) && isset($lastBalance))
			//		TAccountList::saveChangeDate($arFields['ID'], $curBalance, $lastBalance, $lastChangeDate);
			}
		}
	}

	static function saveChangeDate($accountId, $curBalance, $lastBalance, $lastChangeDate){
		global $Connection;
		if(isset($lastChangeDate))
			$lastChangeDate = $Connection->DateTimeToSQL($Connection->SQLToDateTime($lastChangeDate));
		else
			$lastChangeDate = "UpdateDate";
		if(!isset($lastBalance))
			$lastBalance = "Balance";
		$Connection->Execute("update Account set
		LastChangeDate = {$lastChangeDate}
		where AccountID = {$accountId}");
	}

	static function FormatBalance($arFields, $arProperties){
		if($arFields['BalanceFormat'] == 'function'){
			require_once __DIR__ . "/../../engine/{$arFields['ProviderCode']}/functions.php";
			return call_user_func(array("TAccountChecker".ucfirst($arFields['ProviderCode']), "FormatBalance"), $arFields, $arProperties);
		}
		$s = formatFullBalance($arFields['Balance'], $arFields['ProviderCode'], $arFields['BalanceFormat']);
		if(is_null($s) && ($arFields['ErrorCode'] != ACCOUNT_CHECKED) && ($arFields['ErrorCode'] != ACCOUNT_WARNING) && ($arFields['ProviderID'] != "")){
			$s = 'Error';
		}
		if(is_null($s) && ($arFields['ProviderID'] == "")){
			$s = 'n/a';
		}
		if(self::$usrUnPaid) {
			$s = '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>';
		}
//		if(isset($arFields['AccountLevel'])
//		&& $arFields['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS
//		&& SITE_MODE == SITE_MODE_PERSONAL
//		&& isset($_SESSION["BusinessAccountID"])
//		&& userHasExpiredBusiness($_SESSION['BusinessAccountID']))
//			$s = "<a href='/security/switchTo.php'>Upgrade business</a>";

		if (isBusinessMismanagement())
			$s = "<a href='/agent/mismanagement.php'>Temporary Blocked</a>";

		return $s;
	}

	function DrawFirstCell($arFields){
		if($arFields["ShowMoreInfo"]) {
			if (self::$usrUnPaid)
				echo "<a href='#' id='plus{$arFields['PopupID']}' class='plus' onclick=\"showPopupWindow(document.getElementById('needPay'), true); return false;\"></a>";
			else
				echo "<a href='#' id='plus{$arFields['PopupID']}' class='plus' onclick=\"clickRow('{$arFields['PopupID']}'); return false;\" onmouseover=\"overRow('{$arFields['PopupID']}')\" onmouseout=\"outRow('{$arFields['PopupID']}')\"></a>";
		}
	}

	function DrawPopup($arFields, $subAccountID = null){
		global $Interface;
		$id = $arFields['ID'];
		if(isset($subAccountID))
			$id .= "sa".$subAccountID;
		?>
		<div id="extRow<?=$id?>" tableName="<?=$arFields['TableName']?>" rowId="<?=$arFields['ID']?>" subAccountId="<?=$subAccountID?>" style="display: none;" class="rowPopup roundedBox" onmouseover="overRow('<?=$id?>')" onmouseout="outRow('<?=$id?>')">Loading..</div>
		<?
	}

	function GetProgramIcon($arFields, $AllianceEliteLevels){
		$sProgramIcon = '';
		if($arFields['Kind'] == PROVIDER_KIND_AIRLINE and isset($arFields['AllianceAlias']) && !empty($arFields['AllianceAlias'])){
		/* -------------------------- commented because of http://redmine.itlogy.com/issues/3626  -------------------------------------------------------
			if (isset($AllianceEliteLevels[$arFields['UserID']][$arFields['UserAgentID']][$arFields['AllianceAlias']]['Level']))
			{	// for family members (they dont have own UserID)
				$sProgramIcon = 'style="background: url(/images/alliances/'.$arFields['AllianceAlias'].$AllianceEliteLevels[$arFields['UserID']][$arFields['UserAgentID']][$arFields['AllianceAlias']]['Level'].'.png) no-repeat 99% 50%;" ';
			}
			else	// for own and shared accounts
			{
				$sProgramIcon = 'style="background: url(/images/alliances/'.$arFields['AllianceAlias'].$AllianceEliteLevels[$arFields['UserID']][$arFields['AllianceAlias']]['Level'].'.png) no-repeat 99% 50%;" ';
			}
		*/
			$q = new TQuery("SELECT ael.Name as Name
														FROM AccountProperty ap
														JOIN ProviderProperty pp
														ON ap.ProviderPropertyID = pp.ProviderPropertyID
														JOIN Account a
														ON ap.AccountID = a.AccountID
														JOIN Provider p
														ON pp.ProviderID = p.ProviderID
														JOIN TextEliteLevel tel
														ON LOWER(ap.Val) = LOWER(tel.ValueText)
														JOIN EliteLevel el
														ON tel.EliteLevelID = el.EliteLevelID
														JOIN AllianceEliteLevel ael
														ON el.AllianceEliteLevelID = ael.AllianceEliteLevelID
														WHERE pp.Kind = 3
														AND
														a.UserID = {$arFields['UserID']}
														AND a.AccountID = {$arFields['ID']}
														AND el.ProviderID = {$arFields['ProviderID']}");
			$level = strtolower(str_replace(" ", "", $q->Fields['Name']));
			$sProgramIcon = ' data-alliance="'.$arFields['AllianceAlias'].'" style="background: url(/images/alliances/'.$arFields['AllianceAlias'].$level.'.png) no-repeat 99% 50%;" ';
		}

		return $sProgramIcon;
	}
	
	function GetAlliancesEliteLevels($UserID){
	/*	---------------------------  commented because of http://redmine.itlogy.com/issues/3626  -------------------------------------------------------
		$UserAgents = new TQuery("select UserAgentID, ClientID from UserAgent where AgentID = {$_SESSION['UserID']}");
		while (!$UserAgents->EOF)
		{
			if ($UserAgents->Fields['ClientID'] != null)	// shared accounts
			{
				$AllianceIDs = new TQuery("select AllianceID, Alias from Alliance");	
				while (!$AllianceIDs->EOF)
				{																// finding max alliance level for all alliances
					$AllianceEliteLevel = new TQuery("SELECT ael.Name as Name
														FROM AccountProperty ap
														JOIN ProviderProperty pp
														ON ap.ProviderPropertyID = pp.ProviderPropertyID
														JOIN Account a
														ON ap.AccountID = a.AccountID
														JOIN Provider p
														ON pp.ProviderID = p.ProviderID
														JOIN EliteLevel el
														ON LOWER(ap.Val) = LOWER(el.Name)
														AND
														el.ProviderID = p.ProviderID
														LEFT JOIN AllianceEliteLevel ael
														ON el.AllianceEliteLevelID = ael.AllianceEliteLevelID
														WHERE pp.Kind = 3
														AND
														a.UserID = {$UserAgents->Fields['ClientID']}
														AND
														p.AllianceID = {$AllianceIDs->Fields['AllianceID']}
														ORDER BY ael.Rank DESC
														LIMIT 1");
					if (isset($AllianceEliteLevel->Fields['Name']) && ($AllianceEliteLevel->Fields['Name'] != '') && ($AllianceEliteLevel->Fields['Name'] != null))
					{	// if founded - set it
						$temp[$UserAgents->Fields['ClientID']][$AllianceIDs->Fields['Alias']]['Level'] = strtolower(str_replace(" ", "", $AllianceEliteLevel->Fields['Name']));
					}
					else
					{	// if not - set ''
						$temp[$UserAgents->Fields['ClientID']][$AllianceIDs->Fields['Alias']]['Level'] = '';
					}
				
					$AllianceIDs->Next();
				}
			}
			else		// family members
			{
				$AllianceIDs = new TQuery("select AllianceID, Alias from Alliance");
				while (!$AllianceIDs->EOF)
				{																		// finding max alliance level for all alliances
					$AllianceEliteLevel = new TQuery("SELECT ael.Name as Name
														FROM AccountProperty ap
														JOIN ProviderProperty pp
														ON ap.ProviderPropertyID = pp.ProviderPropertyID
														JOIN Account a
														ON ap.AccountID = a.AccountID
														JOIN Provider p
														ON pp.ProviderID = p.ProviderID
														JOIN EliteLevel el
														ON LOWER(ap.Val) = LOWER(el.Name)
														AND
														el.ProviderID = p.ProviderID
														LEFT JOIN AllianceEliteLevel ael
														ON el.AllianceEliteLevelID = ael.AllianceEliteLevelID
														WHERE pp.Kind = 3
														AND
														a.UserID = {$_SESSION['UserID']}
														AND
														a.UserAgentID = {$UserAgents->Fields['UserAgentID']}
														AND
														p.AllianceID = {$AllianceIDs->Fields['AllianceID']}
														ORDER BY ael.Rank DESC
														LIMIT 1");
					if (isset($AllianceEliteLevel->Fields['Name']) && ($AllianceEliteLevel->Fields['Name'] != '') && ($AllianceEliteLevel->Fields['Name'] != null))
					{
						$temp[$_SESSION['UserID']][$UserAgents->Fields['UserAgentID']][$AllianceIDs->Fields['Alias']]['Level'] = strtolower(str_replace(" ", "", $AllianceEliteLevel->Fields['Name']));
					}
					else
					{
						$temp[$_SESSION['UserID']][$UserAgents->Fields['UserAgentID']][$AllianceIDs->Fields['Alias']]['Level'] = '';
					}
				
					$AllianceIDs->Next();
				}
			}
			$UserAgents->Next();
		}
		
		$AllianceIDs = new TQuery("select AllianceID, Alias from Alliance");	// its for own accounts
		while (!$AllianceIDs->EOF)
		{																		// all the same
			$AllianceEliteLevel = new TQuery("SELECT ael.Name as Name
												FROM AccountProperty ap
												JOIN ProviderProperty pp
												ON ap.ProviderPropertyID = pp.ProviderPropertyID
												JOIN Account a
												ON ap.AccountID = a.AccountID
												JOIN Provider p
												ON pp.ProviderID = p.ProviderID
												JOIN EliteLevel el
												ON LOWER(ap.Val) = LOWER(el.Name)
												AND
												el.ProviderID = p.ProviderID
												LEFT JOIN AllianceEliteLevel ael
												ON el.AllianceEliteLevelID = ael.AllianceEliteLevelID
												WHERE pp.Kind = 3
												AND
												a.UserID = {$_SESSION['UserID']}
												AND
												a.UserAgentID is null
												AND
												p.AllianceID = {$AllianceIDs->Fields['AllianceID']}
												ORDER BY ael.Rank DESC
												LIMIT 1");
			if (isset($AllianceEliteLevel->Fields['Name']) && ($AllianceEliteLevel->Fields['Name'] != '') && ($AllianceEliteLevel->Fields['Name'] != null))
			{
				$temp[$_SESSION['UserID']][$AllianceIDs->Fields['Alias']]['Level'] = strtolower(str_replace(" ", "", $AllianceEliteLevel->Fields['Name']));
			}
			else
			{
				$temp[$_SESSION['UserID']][$AllianceIDs->Fields['Alias']]['Level'] = '';
			}
		
			$AllianceIDs->Next();
		}
		
		return $temp;	*/
	}

	function GetProgramLink($arFields, $sRedirectURL, $bCanRedirect){
		if($bCanRedirect && !isBusinessMismanagement()){
			$onclick = "";
			$usingExtension = in_array($arFields['AutoLogin'], array(AUTOLOGIN_EXTENSION, AUTOLOGIN_MIXED))
				&& !isset($_COOKIE['DBE']);
			if($usingExtension)
				$onclick = "onclick = 'return autoLoginAccountById({$arFields['ID']})' target='_blank'";
			$sProgramLink = "<a class='alLink' {$onclick} href=\"$sRedirectURL\" ".(($onclick != "")?"":"target=_blank")."><span id=\"rewardName{$arFields['ID']}\">{$arFields["DisplayName"]}</span></a>";
			if($arFields["AutoLogin"] == AUTOLOGIN_DISABLED){
				$sProgramLink .= "&nbsp;<a href='#autoLogin' style='text-decoration: none;'>*</a>";
				$this->NoticeAutoLogin = true;
			}
			if($usingExtension){
				$sProgramLink .= "&nbsp;<a class='browserAutoLogin' href='#browserAutoLogin' style='text-decoration: none;'>**</a>";
				$this->NoticeBrowserAutoLogin = true;
			}
		}
		else
			$sProgramLink = "<span id=\"rewardName{$arFields['ID']}\">".$arFields["DisplayName"]."</span>";
		return $sProgramLink;
	}

	function DrawTotals(){
		global $arProviderKind;
		if((count($this->Totals) == 0) || ($this->PageNavigator != ""))
			return;
		$accounts = 0;
		$points = floatval(0);
		$kinds = $arProviderKind;
		$kinds[""] = "Custom";
		if(count($this->TotalUsers) > 1)
			$users = "Multiple Users (<span style='color: black;'>".count($this->TotalUsers)."</span>)";
		else
			$users = $this->TotalUsers[0];
		?>
		<tr class="accounts totals">
			<td class="c1"></td>
			<td class="c1a notPrintable printW0">
				<a href='#' class='plus' id='plus1' onclick='clickRow(1); return false;' onmouseover="overRow(1)" onmouseout="outRow(1)"></a>
				<div id="extRow1" style="display: none;" class="rowPopup roundedBox" onmouseover="overRow(1)" onmouseout="outRow(1)">
					<div>
					<table cellpadding="0" cellspacing="0" class="frame" style="width: 100%; border: none;">
						<tr class="top">
							<td class="left"></td>
							<td class="center">
								<div class="name"><?=$users?></div>
								<div class="program">Totals</div>
							</td>
							<td class="right"></td>
						</tr>
						<tr class="middle"><td class="left"><div class="bg"></div></td><td class="center">
						<div  style="padding: 15px;">
							<div style="font-size: 10pt;">
								<table cellspacing="0" cellpadding="0" class="props">
								<tr class="row1 odd">
									<td class="icon"></td>
									<td class="name"></td>
									<td class="value">Accounts</td>
									<td class="value">Total</td>
								</tr>
								<?
								$n = 2;
								foreach($this->Totals as $kind => $totals){
									if($kind == PROVIDER_KIND_AIRLINE)
										$items = "mile";
									else
										$items = "point";
									$items .= s($totals['Points']);
									$n++;
									$classes = "row$n kind{$kind}";
									if(($n % 2) == 0)
										$classes .= " even";
									else
										$classes .= " odd";
									?>
									<tr class="<?=$classes?>">
										<td class="icon"><div></div></td>
										<td class="name"><? echo $kinds[$kind] ?></td>
										<td class="value"><? echo $totals['Accounts'] ?></td>
										<td class="value"><span class='balance'><? echo number_format_localized($totals['Points'], 0)."</span> $items"; ?></td>
									</tr>
									<?
									$accounts += $totals['Accounts'];
									$points += $totals['Points'];
								}
								?>
								</table>
							</div>
						</div>
						</td><td class="right"><div></div></td></tr>
						<tr class="bottom"><td class="left"></td><td class="center"></td><td class="right"></td></tr>
					</table>
					</div>
				</div>
			</td>
			<td class="c2"></td>
			<td id="accountCell1" class="program">Totals:</td>
			<td class="login" colspan="2"><?=$accounts?> Account<?=s($accounts)?></td>
			<td class="balance" nowrap><?=number_format_localized($points, 0)?></td>
<? 			if($this->ShowManageLinks){?>
				<td colspan="3" class='notPrintable printW0' style='padding-right: 15px; text-align: right;'>
				</td>
<?}?>
		</tr>
		<?
	}

	function DrawPageNavigator()
	{
		if($this->PageNavigator != ""){
			echo "<tr class='pageNav'><td class='c1' colspan='{$this->ColCount}'><div class='caption'>{$this->PageNavigator}</div></td></tr>";
		}
	}

	function AccountState($arFields){
		if( $arFields["ProviderID"] == '' )
			return "";
		if( ( $arFields["CanCheck"] == 0 ) && ( $arFields["CanCheckBalance"] == 0 ) )
			return "";
		if( $arFields["CanCheck"] == 0 )
			return "";
		if( $arFields["State"] == ACCOUNT_DISABLED )
			return "<div class=\"redBar\"></div>";
		else
			switch( $arFields["ErrorCode"] ){
				case ACCOUNT_CHECKED:
					return "";
				default:
					return "<div class=\"redBar\"></div>";
			}
	}

	function DrawFiltersForm(){
		global $lastRowKind, $Interface;
		?>
		<tr class="head after<?=$lastRowKind?>">
			<td class="c1head notPrintable printW0" colspan="2"><div class="icon"><div class="inner"></div></div></td>
			<td class="c2head"></td>
			<td class="colProgram" nowrap><div class="left40"><? $this->DrawFilterInput("Program") ?></div></td>
			<td class="leftDots"><? $this->DrawFilterInput("Account") ?></td>			
			<td class="leftDots noRight colExpiration"></td>
			<td class="leftDots"></td>
			<td class="leftDots" colspan="2"></td>
			<? if($this->ShowManageLinks) {?>
			<td class="leftDots noRight manageHead" style="text-align: center" class="notPrintable printW0">
				<?=$Interface->getEditLinks(array("clear" => "<a href='#' onclick=\"FilterForm = document.forms['accountFilterForm']; clearForm(FilterForm); FilterForm.submit(); return false;\">clear filters</a>"))?>
			</td>
			<? } ?>
		</tr>
		<?
	}

	function DrawFilterInput($field){
		echo '<input class="inputTxt" type="text" name="'.$field.'" value="'.htmlspecialchars($this->Filters[$field]["Value"]).'" maxlength="50"/>'.
		"&nbsp;<input type=Image name=s1 width=8 height=7 src='/lib/images/button1.gif' style='border: none; margin-bottom: 1px; margin-right: 0px;'>";
	}
	
	function getAccountProperty($accountID, $subAccountID = "", $kind){
		if(isset($this->AllProps[$accountID][$subAccountID]))
			foreach($this->AllProps[$accountID][$subAccountID] as $prop){
				if($prop['Kind'] == $kind){
					return $prop['Val'];
				}
			}
		return null;
	}

}


