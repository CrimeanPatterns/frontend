<?
$schema = "providerStatus";
require "start.php";

$oldUserId = intval(ArrayVal($_POST, "oldUserId"));
$newUserId = intval(ArrayVal($_POST, "newUserId"));
$providerId = intval(ArrayVal($_POST, "providerId"));
checkAjaxCSRF();

$q = new TQuery("select Assignee, State, StatePrev from Provider where ProviderID = " . $providerId);
if($q->EOF)
	die("Provider not found");
//if(!in_array($q->Fields['State'], array(PROVIDER_FIXING, PROVIDER_ENABLED)))
//	die("Unsupported provider state: ".$arProviderState[$q->Fields['State']]);

if($oldUserId != intval($q->Fields['Assignee']))
	die("Someone changed this provider's Assignee. Refresh page.");

if ($newUserId == 0) {
    $prevState = $newUserId = "null";
    $newState = $q->Fields['StatePrev'];

    if (empty($q->Fields['StatePrev']))
        exit('Something is wrong, StatePrev is EMPTY');

} else {
    $prevState = $q->Fields['State'];
    $newState = PROVIDER_FIXING;

    if (!empty($q->Fields['StatePrev']))
        exit('Something is wrong, StatePrev in db is already defined');
}

$Connection->Execute("update Provider set Assignee = $newUserId, State = $newState, StatePrev = $prevState where ProviderID = $providerId");

//*** for SlaEvent
$sql = "
SELECT Event FROM 
	SlaEvent 
WHERE 
	ProviderID = $providerId AND Event != 'variation'
ORDER BY EventDate DESC
LIMIT 1
";
$q = new TQuery($sql);
if(!$q->EOF && $newUserId != 'null'){
	if ($q->Fields['Event'] != 'assign' && $q->Fields['Event'] != 'close'){
		
		$sql = "
		INSERT INTO SlaEvent (ProviderID,EventDate,Checked,Errors,Event)
		VALUE ($providerId,NOW(),0,0,'assign')			
		";
		$Connection->Execute($sql);
		
		$sql = "
		UPDATE
			Provider
		SET
			ResponseTime = NULL
		WHERE
			ProviderID = $providerId
		";
		$Connection->Execute($sql);		
	}
}

echo "OK";
