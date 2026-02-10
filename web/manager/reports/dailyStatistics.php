<?
$schema = "dailyStatistics";
require "../start.php";
drawHeader("Daily Statistics");

$sql = "
	SELECT ProviderID, DisplayName 
	FROM Provider
	WHERE Provider.State >= ".PROVIDER_ENABLED."
	ORDER BY Name";
$q = new TQuery($sql);
?>
<style>

</style>
<form action="/manager/reports/dailyStatistics.php" method="get" name="s">
    <label>Provider:</label>
    <select style="width: 200px;" name="ProviderID" onchange="document.forms['s'].elements['UnusedID'].value=this.value;">
        <?
        while(!$q->EOF){
            echo "<option value='{$q->Fields['ProviderID']}' ".((isset($_GET['ProviderID']) && $q->Fields['ProviderID'] == $_GET['ProviderID'])?'selected="selected"':'')." >{$q->Fields['DisplayName']}</option>";
            $q->Next();
        }
        ?>
    </select>
    <select name="UnusedID" onchange="document.forms['s'].elements['ProviderID'].value=this.value;" disabled="disabled">
        <?
        $sql = "
            SELECT ProviderID, Code
            FROM Provider
            WHERE Provider.State >= ".PROVIDER_ENABLED."
            ORDER BY Name";
        $q = new TQuery($sql);
        while(!$q->EOF){
            echo "<option value='{$q->Fields['ProviderID']}' ".((isset($_GET['ProviderID']) && $q->Fields['ProviderID'] == $_GET['ProviderID'])?'selected="selected"':'')." >{$q->Fields['Code']}</option>";
            $q->Next();
        }
        ?>
    </select>
    <input type="submit" value="Find" />
</form>
<script type="text/javascript">
    document.forms['s'].elements['UnusedID'].disabled = false;
</script>
</br>
<?

if (!empty($_GET["ProviderID"])) {
    $date = date('Y-m-d');

    $where = "ProviderID = ".intval($_GET['ProviderID'])."
          AND UpdateDate LIKE '".$date."%'";

    // All accounts
    $all = new TQuery("
	SELECT ErrorCode, BrowserState
    FROM Account AS Checked
    WHERE {$where}");

    // all accounts
    $checked = 0;
    // count sent emails
    $codesSent = 0;
    // Success after questions
    $successAfterQuestions = 0;
    while(!$all->EOF){
        $checked++;
        // Checking sending code to email for chase
        $browserState = $all->Fields['BrowserState'];
        if (preg_match("/^base64:/ims", $browserState)){
            $browserState = preg_replace("/^base64:/ims", '', $browserState);
            $browserState = base64_decode($browserState);
        }
        $browserState = unserialize(($browserState));
        // Checking date and Account State
        if (isset($browserState['State']['CodeSent'], $browserState['State']['CodeSentDate'])) {
            if (date('Y-m-d', $browserState['State']['CodeSentDate']) == $date){
                $codesSent++;
                // Checking Account State
                if ($all->Fields['ErrorCode'] == 1)
                    $successAfterQuestions++;
            }
        }

        $all->Next();
    }

    // Accounts without errors
    $success = new TQuery("
	SELECT COUNT(ErrorCode) AS Success
    FROM Account
    WHERE ErrorCode = 1 AND {$where}");
    // Accounts with errors
    $errors = new TQuery("
	SELECT COUNT(ErrorCode) AS Errors
    FROM Account
    WHERE ErrorCode <> 1 AND {$where}");
    // Accounts with UE
    $ue = new TQuery("
	SELECT COUNT(ErrorCode) AS UE
    FROM Account
    WHERE ErrorCode = 6 AND {$where}");

    if(!$success->EOF && !$ue->EOF && !$errors->EOF) {
        echo "<div>Statistics for <b>{$date}</b></div>
        </br>
        <table border='1' cellpadding='3' cellspacing='0'>";
        echo "<tr>
                <td>Checked</td>
                <td>Success</td>
                <td>With Errors</td>
                <td>With UE</td>
                <td>Codes Sent</td>
                <td>Success after questions</td>
            </tr>";
        echo "<tr>
                <td>{$checked}</td>
                <td>{$success->Fields['Success']}</td>
                <td>{$errors->Fields['Errors']}</td>
                <td>{$ue->Fields['UE']}</td>
                <td>{$codesSent}</td>
                <td>{$successAfterQuestions}</td>
            </tr>";
        echo "</table>";
    }
    else {
        echo "<br/><span style='color:#d0d0d0;'>Something went wrong...</span>";
    }
}

?>
