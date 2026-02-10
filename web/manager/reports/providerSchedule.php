<?
$schema = "providerSchedule";
require( "../start.php" );

class providerScheduleList extends TBaseList{

	private $tier2;
	private $tier3;
	private $attributes;

	function __construct(){
		global $arDeepLinking, $arProviderKind, $arProviderState;
		parent::__construct("Provider", array(
			"Code" => array(
				"Type" => "string",
				"filterWidth" => 40,
			),
			"DisplayName" => array(
				"Type" => "string",
			),
			"Kind" => array(
				"Type" => "integer",
				"Options" => $arProviderKind,
			),
			"Accounts" => array(
				"Type" => "integer",
				"Caption" => "Popularity Ranking",
				"Sort" => "Accounts DESC",
			),
			"Attributes" => array(
				"Type" => "string",
				"Database" => false,
				"Caption" => "Loyalty program attributes",
				//"FilterType" => "having"
			),
			"CanCheckItinerary" => array(
				"Type" => "boolean",
				"Caption" => "Travel itineraries",
			),
			"CanCheckHistory" => array(
				"Type" => "boolean",
				"Caption" => "History",
			),
			"AutoLogin" => array(
				"Type" => "boolean",
				"Caption" => "Auto-login",
			),
			"DeepLinking" => array(
				"Type" => "integer",
				"Options" => $arDeepLinking,
			),
			"Tier" => array(
				"Type" => "string",
				"Database" => false,
			),
            "WSDL" => array(
				"Type" => "boolean",
			),           
            "Corporate" => array(
				"Type" => "boolean",
			),          
            "Questions" => array(
				"Type" => "boolean",
			),          
            "State" => array(
				"Type" => "integer",
				"Options" => array(14 => "for WSDL clients") + $arProviderState,
			),
			"Login" => array(
				"Type" => "string",
				"filterWidth" => 60,
			),
			"Pass" => array(
				"Caption" => 'Password',
				"Type" => "string",
				"filterWidth" => 60,
			), 
			"Login2" => array(
				"Type" => "string",
				"filterWidth" => 60,
			), 
			"Login3" => array(
				"Type" => "string",
				"filterWidth" => 60,
			),         
		), "Accounts");
			$this->SQL = "select
				p.ProviderID,
				p.Code,
				p.DisplayName,
				p.Kind,
				p.CanCheckItinerary,
				p.AutoLogin,
				p.DeepLinking,
				p.CanCheckHistory,
				p.Accounts,
				/*pprop.Attributes,*/
				p.WSDL,
				p.Corporate,
				p.Questions,
				p.State,
				cred.Login,
				cred.Login2,
				cred.Login3,
				cred.Pass
			from
				Provider p
				LEFT JOIN (
					SELECT Login, Login2, Login3, Pass, ProviderID
					FROM Account
					WHERE UserID = 73657 AND ErrorCode = 1
					GROUP BY ProviderID
				) cred ON cred.ProviderID = p.ProviderID
        ";
		set_time_limit(1000);
		$this->ShowFilters = true;
		$this->calcTiers();
		$this->PageSizes["5000"] = "5000";
		$this->PageSize = 5000;
		$this->ExportName = "wsdlProviders";
		$this->loadAttributes();
        $this->isWhere = true;
		$this->ShowExport = true;
		$this->TopButtons = true;
		$this->ShowEditors = true;
	}

	private function loadAttributes(){
		$q = new TQuery("select distinct
			pp.ProviderID, pp.Name
		from
			Account a
			inner join AccountProperty ap on ap.AccountID = a.AccountID
			inner join ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
		where
			a.UpdateDate >= adddate(now(), -7)");
		$this->attributes = array();
		while(!$q->EOF){
			if(!isset($this->attributes[$q->Fields['ProviderID']]))
				$this->attributes[$q->Fields['ProviderID']] = array();
			$this->attributes[$q->Fields['ProviderID']][] = $q->Fields['Name'];
			$q->Next();
		}
	}

	private function calcTiers(){
		$q = new TQuery("select count(*) as Cnt from Provider where WSDL = 1 and State >= ".PROVIDER_ENABLED);
		$this->tier2 = round($q->Fields["Cnt"] * 0.2);
		$this->tier3 = round($q->Fields["Cnt"] * 0.5);
	}

	function FormatFields($output = "html"){
		parent::FormatFields($output);
		$this->Query->Fields['Attributes'] = $this->getAttributes($this->Query->Fields['ProviderID']);
		$this->Query->Fields["Tier"] = "T3";
		if($this->Query->Position < $this->tier3)
			$this->Query->Fields["Tier"] = "T2";
		if($this->Query->Position < $this->tier2)
			$this->Query->Fields["Tier"] = "T1";
		$this->Query->Fields['Pass'] = getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class)->decrypt( $this->Query->Fields['Pass'] );
		if(!isset($_GET['Export']))
			$this->Query->Fields['DeepLinking'] = "<a href=\"/manager/testDeepLinking.php?ProviderID={$this->Query->Fields['ProviderID']}&Using=service\">{$this->Query->Fields['DeepLinking']}</a>";
	}

	private function getAttributes($providerId){
		if(isset($this->attributes[$providerId]))
			return implode(", ", $this->attributes[$providerId]);
		else
			return "";
	}

	public function AddFilters($sSQL){
        global $arProviderStateForWsdlClients;
		$sql = parent::AddFilters($sSQL);
		return str_replace('State = 14', 'State IN(' . implode(', ', $arProviderStateForWsdlClients) . ')', $sql);
	}

	function DrawButtonsInternal(){
		$triggers = array();
		if( $this->ShowExport){
			echo "<input id=\"ExportId\" class='button' type=button value=\"Export\" onclick=\"location.href = '?Export&" . $_SERVER['QUERY_STRING'] . "'\"> ";
			$triggers[] = array('ExportId', 'Export all to CSV');
		}
		return $triggers;
	}
}

$list = new providerScheduleList();

if(isset($_GET['Export']))
	$list->ExportCSV();
else{
	drawHeader("LPs Extended");
	$list->Draw();
	drawFooter();
}

?>
