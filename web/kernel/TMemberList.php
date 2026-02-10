<?

class TMemberList extends TList{
    
    var $AdminCount;

	var $isBookerAdministrator;

    private $paidUsrAgentIds = array();
    
	function __construct(){
		parent::__construct(
			"UserAgent",
			array(
				"Name" => array(
					"Type" => "string",
					"Size" => 80,
					"Sort" => "Name",
					"FilterField" => "concat(coalesce(ua.FirstName, u.FirstName),	' ', coalesce(ua.LastName, u.LastName))",
				),
				"Type" => array(
					"Type" => "string",
					"Size" => 80,
					"Options" => array("Connected User" => "Connected User", "Not Connected" => "Not Connected", "Pending" => "Pending"),
					"FilterField" => "if(ua.ClientID is null, 'Not Connected', if(au.IsApproved = 0, 'Pending' , 'Connected User'))",
                    "filterWidth" => 160,                                        
				),
				"Programs" => array(
					"Type" => "integer",
					"AllowFilters" => false,
				),
				"Trips" => array(
					"Type" => "integer",
					"Database" => False,
				),
				"Rewards" => array(
					"Type" => "integer",
					"AllowFilters" => false,
				),
			),
			"Name"
		);
		
		$this->SQL = "select
			ua.AgentID,
			ua.UserAgentID,
			ua.ShareDate,
			concat(coalesce(ua.FirstName, u.FirstName),	' ', coalesce(ua.LastName, u.LastName)) as Name,
			au.IsApproved,
			ua.ClientID,
			ua.Email,
			ua.AccessLevel,
			if(ua.ClientID is null, 'Not Connected', if(au.IsApproved = 0, 'Pending' ,'Connected User')) as Type,
			coalesce(au.UserAgentID, ua.UserAgentID) as LinkUserAgentID,
			sum(case when ua.ClientID is null AND a.AccountID IS NOT NULL then 1 else case when ash.AccountShareID is not null then 1 else 0 end end) as Programs,
			sum(
					case when ua.ClientID is null then
						a.TotalBalance
					else 
						case when ash.AccountShareID is not null then 
							a.TotalBalance
						else 
							0 
						end 
					end 
			) as Rewards
		from UserAgent ua
			left outer join Usr u on ua.AgentID = u.UserID /*and ua.IsApproved = 1 show all user*/
			left outer join UserAgent au on ua.ClientID = au.AgentID and ua.AgentID = au.ClientID
			left outer join Account a on (u.UserID = a.UserID and ua.ClientID is not null)
				or (u.UserID = a.UserID and ua.ClientID is null and a.UserAgentID = ua.UserAgentID)			
			LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
			left outer join AccountShare ash on a.AccountID = ash.AccountID and ash.UserAgentID = au.UserAgentID
		where
			(ua.ClientID = {$_SESSION['UserID']} or (ua.AgentID = {$_SESSION['UserID']} and ua.ClientID is null))  AND ".userProviderFilter($_SESSION['UserID'])."
			[Filters]
		group by
			AgentID, UserAgentID/*, ShareDate, Name, IsApproved, ClientID, Email, LinkUserAgentID*/";
            
            $this->AdminCount = countBusinessAccountAdmin($_SESSION['UserID']);
        
		$this->ShowFilters = true;
		$this->ReadOnly = false;
		$this->ShowEditors = true;
		$this->MultiEdit = false;
		$this->EmptyListMessage = "You have no connections";
		$this->showTopNav = false;
	}

	function FormatFields($output = "html"){
		global $Connection, $arAgentAccessLevels;
		parent::FormatFields($output);
		$arFields = &$this->Query->Fields;
		
		$sql = "SELECT 
					SUM(CASE WHEN ua.ClientID IS NULL AND p.ProviderCouponID IS NOT NULL THEN 1 ELSE CASE WHEN psh.ProviderCouponShareID IS NOT NULL THEN 1 ELSE 0 END END) AS Programs
				FROM 
					UserAgent ua
				LEFT JOIN Usr u ON u.UserID = ua.AgentID
				LEFT JOIN UserAgent au ON ua.ClientID = au.AgentID AND ua.AgentID = au.ClientID
				LEFT JOIN ProviderCoupon p ON (u.UserID = p.UserID AND ua.ClientID IS NOT NULL) 
					OR (u.UserID = p.UserID AND ua.ClientID IS NULL AND p.UserAgentID = ua.UserAgentID)
				LEFT JOIN ProviderCouponShare psh ON p.ProviderCouponID = psh.ProviderCouponID AND psh.UserAgentID = au.UserAgentID
				WHERE
					ua.UserAgentID = {$arFields['UserAgentID']}
		";
		$couponPrograms = new TQuery($sql);
		if(!$couponPrograms->EOF){
			$arFields['Programs'] = $arFields['Programs'] + $couponPrograms->Fields['Programs'];
		}

		$params = array('UserAgentID' => $arFields['UserAgentID'], 'ClientID' => $arFields['ClientID']);
		if($arFields['ClientID'] != '')
			$params['UserAgentID'] = $arFields['LinkUserAgentID'];
		$tripCount = getAgentTripsCount($params);
//		if(!isset($arFields['ClientID'])){
//			$qPlans = new TQuery("select count(tpsh.TravelPlanShareID) as Plans
//			from 		TravelPlanShare tpsh
//			join TravelPlan tp on tpsh.TravelPlanID = tp.TravelPlanID AND tp.Hidden = 0
//			where tpsh.UserAgentID = {$arFields['UserAgentID']}
//			and EndDate >= ".$Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS));
//
//			$qPlansF = new TQuery("select count(*) as Plans from
//			TravelPlan where UserAgentID = {$arFields['UserAgentID']}
//			AND Hidden = 0
//			and EndDate >= ".$Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS));
//			$qPlans->Fields['Plans'] = $qPlans->Fields['Plans'] + $qPlansF->Fields['Plans'];
//		}
//		else{
//			$qPlans = new TQuery("
//			select count(tpsh.TravelPlanShareID) as Plans from TravelPlanShare tpsh, TravelPlan tp
//			where tpsh.TravelPlanID = tp.TravelPlanID
//			and tp.UserID = {$arFields['ClientID']}
//			AND tp.Hidden = 0
//			and tpsh.UserAgentID = {$arFields['LinkUserAgentID']}
//			and tp.EndDate >= ".$Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS));
//		}

		$tripsLink = "/trips/index.php?UserAgentID={$arFields['LinkUserAgentID']}";
		$arFields['Trips'] = "<a href='{$tripsLink}'>{$tripCount}</a>";
		if($tripCount > 0)
			$arFields['Trips'] .= " - <a href='{$tripsLink}'>View Trips</a>";
		if(isset($arFields['ClientID']) )
			$arFields['Trips'] = 'Not Shared';
		$usrUnPaid = SITE_MODE == SITE_MODE_BUSINESS && !in_array($arFields['UserAgentID'], $this->paidUsrAgentIds);
		if($usrUnPaid)
		    $arFields['Trips'] = '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>';	
		$arFields['Rewards'] = number_format_localized($arFields['Rewards'], 0);
		if($usrUnPaid)
		    $arFields['Rewards'] = '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">upgrade</a>';
		if($usrUnPaid)
		    $arFields['Name'] = '<a onclick="showPopupWindow(document.getElementById(\'needPay\'), true); return false;" href="#">'.$arFields['Name'].'</a>';
		else
		    $arFields['Name'] = '<a href="/account/list.php?UserAgentID='.$arFields['LinkUserAgentID'].'">'.$arFields['Name'].'</a>';
		/*if(isset($arFields['ClientID'])){
			if($arFields["IsApproved"] == 0)
				$arFields['Type'] = 'Pending';
			else
				$arFields['Type'] = 'Connected User';
		}
		else
			$arFields['Type'] = 'Not Connected';*/
		$classes = 'people';
		if($arFields['AccessLevel'] == ACCESS_ADMIN || $arFields['AccessLevel'] == ACCESS_BOOKING_MANAGER || $arFields['AccessLevel'] == ACCESS_BOOKING_VIEW_ONLY)
			$classes .= " peopleAdmin";
		if($arFields['ClientID'] == ''){
			$classes .= " peopleFamily";
			$title = "Virtual account";
		}
		#TODO
		#else
		#	$title = $arAgentAccessLevels[$arFields['AccessLevel']];
		else
			$title = ''; //TODO 
		$arFields['Name'] = '<div title="'.$title.'" class="'.$classes.'"></div><div class="peopleName">'.$arFields['Name']."</div><div class='clear'></div>";
	}

	function GetEditLinks(){
		global $Interface;
		$arFields = &$this->Query->Fields;
		if(!empty($arFields['ClientID']))
			$editLink = "<a href='/agent/editBusinessConnection.php?ID={$arFields['UserAgentID']}'>Edit</a>";
		else
			$editLink = "<a href='/agent/editFamilyMember.php?ID={$arFields['UserAgentID']}'>Edit</a>";
        
		$links = array(
			"edit" => $editLink,
			"disconnect" => "<a href=# onclick=\"if(window.confirm('Are you sure you want to delete this connection?')) waitAjaxRequest('/members/deny/{$arFields["UserAgentID"]}'); return false;\" title=Disconnect>Disconnect</a>",
		);
        if (empty($arFields['ClientID'])) {
            unset($links['disconnect']);
            $links['delete'] = "<a title=Delete href=\"#\" onclick=\"if(window.confirm('Are you sure you want to delete this connection?')) waitAjaxRequest('/members/deny/{$arFields["UserAgentID"]}'); return false;\">Delete</a>";
        }

        if($this->AdminCount <= 1 && $arFields['AccessLevel'] == ACCESS_ADMIN)
            $links = array(
    			"edit" => $editLink,
    		);
            
		if(isset($arFields['ClientID'])){
			if($arFields["IsApproved"] == 0)
				$links['resend'] = "<a href=\"#\" onclick=\"sendReminder({$arFields['AgentID']}, this.parentNode, '0'); return false;\">Resend</a>";
 		}
		else
			$links['invite'] = "<a class='checkLink' href='#' onclick='inviteFamilyMember(this, {$arFields['UserAgentID']}, \"{$arFields['Email']}\"); return false;'>Invite</a>";
		if ($this->isBookerAdministrator) {
			unset($links['edit']);
			unset($links['disconnect']);
		}
		return $Interface->getEditLinks($links);
	}

	function DrawButtons($closeTable=true){
		global $Interface;
		echo "<br><br>";
		echo $Interface->DrawButton("Add new member", "onclick=\"showPopupWindow(document.getElementById('newAgentPopup'), true); return false;\"", 240);
	}

	function Draw() {
		parent::Draw();
		global $Interface;
		$Interface->DrawBeginBox('id="askEmailBox" style="display: none; width: 380px; height: 260px; position: absolute; z-index: 50;"', 'Email');
		?>
			<div style="padding: 0px;">
				<div style="margin-bottom: 10px;">
					<input type="text" id="email" style="margin-top: 15px; width: 333px;" class="inputTxt">
					<input type="hidden" id="userAgentId">
				</div>
				<? echo $Interface->DrawButton("Invite", 'onclick="sendFamilyInvite(); return false;"'); ?>
				<? echo $Interface->DrawButton("Cancel", 'onclick="cancelInvite(); return false;"'); ?>
			</div>
		<?
		$Interface->DrawEndBox();
	}

}
