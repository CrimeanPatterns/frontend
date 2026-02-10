<?

use AwardWallet\MainBundle\Security\StringSanitizer;

$schema = "qaac";
require "../../start.php";

class AAMembershipList extends TBaseList{
    function __construct(){
        parent::__construct("Usr", array(
            "FirstName" => array(
                "Type" => "string",
                "Caption" => "FirstName",
                ),
            "LastName" => array(
                "Type" => "string",
                "Caption" => "Last Name",
                ),
            "s" => array(
                "Type" => "string",
                "FilterField" => "s",
                "Caption" => "Visits",
                "Database" => false,
                ),
            "Balance" => array(
                "Type" => "string",
                "Caption" => "Balance",
                ),
            "ExpirationDate" => array(
                "Type" => "string",
                "Caption" => "Expiration",
                ),
            "Login" => array(
                "Type" => "string",
                "Caption" => "Account",
                ),
            "Status" => array(
                "Type" => "string",
                "Caption" => "Status",
                "Database" => false,
                "FilterField" => "Status",
                ),
            "Provider" => array(
                "Type" => "string",
                "Caption" => "Airline",
                "Database" => false,
                "FilterField" => "Airline",
                ),
            "t1" => array(
                "Type" => "string",
                "Database" => false,
                "Caption" => "Tier 1",
                ),
            "t2" => array(
                "Type" => "string",
                "Database" => false,
                "Caption" => "Tier 2",
                ),
            "t3" => array(
                "Type" => "string",
                "Database" => false,
                "Caption" => "Tier 3",
                ),
/*            "aa" => array(
                "Type" => "string",
                "Database" => false,
                "Caption" => "AA Specified Data Elements ",
                ), */
            ),
            "LastName");
        $this->ShowExport = true;
        $this->TopButtons = true;
        $this->ExportName = "AAMembers";
    }

    function FormatFields($output = "html"){
        parent::FormatFields($output);
        $this->Query->Fields['Balance'] = number_format($this->Query->Fields['Balance'], 0, '.', ',');
        $this->Query->Fields['ExpirationDate'] = date("d/m/Y", strtotime($this->Query->Fields['ExpirationDate']));
        if ($this->Query->Fields['utype'] == 1){
            $visitFilter = '';
            if (isset($_GET["month"]) && isset($_GET["year"])){
                $m = intval($_GET["month"]);
                $y = intval($_GET["year"]);
                if (($m >= 1) && ($m <= 16)){
                    if ($m > 12){
                        $m = 3 * ($m - 12) - 2;
                        $sdm = $m;
                        $edm = $m + 2;
                    }
                    else
                        $sdm = $edm = $m;
                    $visitFilter = " and year(VisitDate) = $y and month (VisitDate) >= $sdm and month (VisitDate) <= $edm";
                }
            }
            /* left join
            (select
                UserID,
                sum(Visits) as s
            from
            Visit
            ".
            $visitFilter.
            "
            group by UserID) x on x.UserID = u.UserID */
            $q = new TQuery('select coalesce(sum(Visits), 0) as s from Visit where UserID = '.$this->Query->Fields['UserID'].$visitFilter);
            if (!$q->EOF)
                $visits = $q->Fields['s'];
            else
                $visits = '0';
            $this->Query->Fields['s'] = $visits;
        }else
            $this->Query->Fields['s'] = '';
        $q = new TQuery('select Val from AccountProperty ap where ap.AccountID = '.$this->Query->Fields['AccountID'].' and ap.ProviderPropertyID in (select ProviderPropertyID from ProviderProperty pp where (pp.ProviderID = '.Lookup("Provider", "Code", "ProviderID", "'aa'", true).' or pp.ProviderID = '.Lookup("Provider", "Code", "ProviderID", "'dividendmiles'", true).') and pp.Code = "Status")');
        if (!$q->EOF)
            $val = $q->Fields['Val'];
        else
            $val = '';
        $this->Query->Fields['Status'] = $val;
        if ($this->Query->Fields['utype'] == 1){
            $q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where a.UserID = ".$this->Query->Fields['UserID']." and a.UserAgentID is null and p.Category = 1 and p.Kind = 1");
            $this->Query->Fields['t1'] = $q->Fields['c'];
            $q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where a.UserID = ".$this->Query->Fields['UserID']." and a.UserAgentID is null and p.Category = 2 and p.Kind = 1");
            $this->Query->Fields['t2'] = $q->Fields['c'];
            $q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where a.UserID = ".$this->Query->Fields['UserID']." and a.UserAgentID is null and p.Category = 3 and p.Kind = 1");
            $this->Query->Fields['t3'] = $q->Fields['c'];
        }else{
            $q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where a.UserAgentID = ".$this->Query->Fields['UserAgentID']." and p.Category = 1 and p.Kind = 1");
            $this->Query->Fields['t1'] = $q->Fields['c'];
            $q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where a.UserAgentID = ".$this->Query->Fields['UserAgentID']." and p.Category = 2 and p.Kind = 1");
            $this->Query->Fields['t2'] = $q->Fields['c'];
            $q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where a.UserAgentID = ".$this->Query->Fields['UserAgentID']." and p.Category = 3 and p.Kind = 1");
            $this->Query->Fields['t3'] = $q->Fields['c'];
        }
        $q = new TQuery("select Name from Provider where ProviderID = '".$this->Query->Fields['ProviderID']."'");
        $this->Query->Fields['Provider'] = $q->Fields['Name'];
    }

    function DrawButtonsInternal(){
        $triggers = array();
        if( $this->ShowExport){
            echo "<input id=\"ExportId\" class='button' type=button value=\"Export\" onclick=\"location.href = '?Export&" .  StringSanitizer::encodeHtmlEntities($_SERVER['QUERY_STRING']) . "'\"> ";
            $triggers[] = array('ExportId', 'Export all to CSV');
        }
        return $triggers;
    }
}

$list = new AAMembershipList;
$list->SQL = "
select UserID, FirstName, LastName, Balance, ExpirationDate, Login, AccountID, UserAgentID, ProviderID, utype
from
(
select
    u.UserID,
    u.FirstName,
    u.LastName,
    a.Balance,
    a.ExpirationDate,
    a.Login,
    a.AccountID,
    a.UserAgentID,
    a.ProviderID,
    1 as utype
from
	Usr u
	join Account a on a.UserID = u.UserID
where
    a.UserAgentID is null and
    (a.ProviderID = ".Lookup("Provider", "Code", "ProviderID", "'aa'", true)." or a.ProviderID = ".Lookup("Provider", "Code", "ProviderID", "'dividendmiles'", true).")
union
select
    ua.AgentID as UserID,
    ua.FirstName,
    ua.LastName,
    a.Balance,
    a.ExpirationDate,
    a.Login,
    a.AccountID,
    a.UserAgentID,
    a.ProviderID,
    2 as utype
from
    Account a
    join UserAgent ua on a.UserAgentID = ua.UserAgentID
where
    ua.ClientID is null and
    (a.ProviderID = ".Lookup("Provider", "Code", "ProviderID", "'aa'", true)." or a.ProviderID = ".Lookup("Provider", "Code", "ProviderID", "'dividendmiles'", true).")) x where 1 = 1";
$list->ReadOnly = true;
$list->ShowFilters = true;
if(isset($_GET['Export']))
    $list->ExportCSV();
else{
    $omonth = date("F", time());
    $oyear = date("Y", time());
    if  ((isset($_GET["month"]) && isset($_GET["year"]))){
        $omonth = $_GET["month"];
        $oyear = $_GET["year"];
    }
    $months = "";
    for ($q = 1; $q <= 4; $q++) {
        $months.='<option value="'.($q + 12).'"';
        if ($omonth == $q + 12)
            $months.=' selected';
        $months.='>Q'.$q.'</option>';
    }
    for ($m = 1; $m <= 12; $m++) {
        $f = date('F', mktime(0,0,0,$m));
        $months.='<option value="'.$m.'"';
        if ($omonth == $f || $omonth == $m)
            $months.=' selected';
        $months.='>'.$f.'</option>';
    }
    drawHeader("AA Membership");
    ?>
    <form method="get">
    <table>
        <tr>
            <td><strong>Month / Quarter</strong></td>
            <td><strong>Year</strong></td>
        </tr>
        <tr>
            <td><select name="month"><?=$months?></select></td>
            <td><input name="year" value="<?=$oyear?>" size="4"></td>
        </tr>
    </table>
    <input type="SUBMIT" value="Filter">
    </form>
    <?
    $list->Draw();
    drawFooter();
}
?>
