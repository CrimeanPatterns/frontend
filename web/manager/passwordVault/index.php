<?php
$schema = "passwords";
require "../start.php";

drawHeader("Password vault", "Password vault for {$_SESSION['Login']}");

class TUserPasswordVaultList extends TBaseList{

	function __construct(){
		global $arAccountErrorCode;
		parent::__construct("PasswordVault", array(
			"PasswordVaultID" => array(
				"Type" => "integer",
				"Caption" => "ID",
				"FilterField" => "pv.PasswordVaultID",
			),
			"Partner" => array(
				"Type" => "string",
				"Caption" => "Partner",
				"filterWidth" => 50,
			),
			"Code" => array(
				"Type" => "string",
				"Caption" => "Provider Code",
				"filterWidth" => 50,
			),
			"DisplayName" => array(
				"Type" => "string",
				"Caption" => "Provider",
				"Sort" => "p.DisplayName ASC, Login ASC, a.Login2 ASC, a.Login3 ASC",
			),
			"UserID" => array(
				"Type" => "integer",
				"Caption" => "UserID",
				"FilterField" => "a.UserID",
			),
			"AccountID" => array(
				"Type" => "integer",
				"Caption" => "AccountID",
				"FilterField" => "pv.AccountID",
			),
			"Login" => array(
				"Type" => "string",
				"filterWidth" => 50,
				"FilterField" => "coalesce(a.Login, pv.Login)",
			),
			"Login2" => array(
				"Type" => "string",
				"Caption" => "Login2",
				"filterWidth" => 50,
				"FilterField" => "coalesce(a.Login2, pv.Login2)",
			),
            "Login3" => array(
				"Type" => "string",
				"Caption" => "Login3",
				"filterWidth" => 50,
				"FilterField" => "coalesce(a.Login3, pv.Login3)",
			),
			"Password" => array(
				"Type" => "string",
				"Database" => false,
			),
			"Balance" => array(
				"Type" => "float",
			),
			"Itineraries" => array(
				"Type" => "integer",
			),
			"ErrorCode" => array(
				"Type" => "integer",
				"Caption" => "Status",
				"Options" => $arAccountErrorCode,
			),
			"IssueID" => array(
				"Type" => "string",
				"Caption" => "Issue #",
				"filterWidth" => 50,
			),
			"CreationDate" => array(
				"Type" => "date",
				"IncludeTime" => true,
			),
			"LastAccessDate" => array(
				"Type" => "date",
				"IncludeTime" => true,
				"Sort" => "LastAccessDate DESC, p.DisplayName ASC, Login ASC, a.Login2 ASC",
			),
		), "LastAccessDate");
		$this->SQL = "select
			pv.PasswordVaultID,
			pv.CreationDate,
			a.UserID,
			a.AccountID,
			pv.Partner,
			coalesce(a.Login, pv.Login) as Login,
			a.Login2,
			a.Login3,
			a.Balance,
			a.ErrorCode,
			a.ProviderID,
			a.Itineraries,
			p.DisplayName,
			p.Code,
			pv.IssueID,
			la.LastAccessDate
		from
			PasswordVault pv
			join PasswordVaultUser pvu on pv.PasswordVaultID = pvu.PasswordVaultID
			left outer join Account a on pv.AccountID = a.AccountID
			left outer join Provider p on a.ProviderID = p.ProviderID or pv.ProviderID = p.ProviderID
			left outer join (
				select
					l.PasswordVaultID,
					max(l.EventDate) as LastAccessDate
				from
					PasswordVaultLog l
				where
					l.UserID = {$_SESSION['UserID']}
				group by
					l.PasswordVaultID
			) la on pv.PasswordVaultID = la.PasswordVaultID
		where
			pvu.UserID = {$_SESSION['UserID']}
			and (pv.ExpirationDate is null or (pv.ExpirationDate > now()))
			and pv.Approved = 1";
		$this->ReadOnly = true;
		$this->ShowFilters = true;
	}

	function FormatFields($output = 'html'){
		parent::FormatFields($output);
		$fields = &$this->Query->Fields;
		$fields['Password'] = '<a href="get.php?ID='.$fields['PasswordVaultID'].'">****</a>';
		$fields['PasswordVaultID'] = '<a name="#'.$fields['PasswordVaultID'].'">'.$fields['PasswordVaultID'].'</a>';
	}
	
}

$list = new TUserPasswordVaultList();
$list->Draw();
echo "<div style='padding-top: 20px;'>
	<a href='log.php'>Access log</a>
	|
	<a href='requestPassword.php'>Request password</a>
</div>";

drawFooter();
