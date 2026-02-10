<?php
$schema = "ProviderPhone";
require "start.php";

function outputJson($response) {
	header("Content-type: application/json");
	echo json_encode( $response );
	exit();
}
function isAjax() {
	return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

$providerPhoneID = intval(ArrayVal($_GET, 'providerPhoneID', 0));
$state = intval(ArrayVal($_GET, 'state', 1));
if (!isAjax() || $providerPhoneID == 0 || !in_array($state, array(0, 1, 2)))
	outputJson(array('error' => 'Incorrect request'));

$now = time();
$fields = array(
	'CheckedBy' 	=> ($state == 2) ? 'NULL' : $_SESSION['UserID'],
	'CheckedDate'	=> ($state == 2) ? 'NULL' : $Connection->DateTimeToSQL($now),
	'Valid'			=> ($state == 2) ? 'NULL' : $state,
);

$Connection->Execute(UpdateSQL("ProviderPhone", array("ProviderPhoneID" => $providerPhoneID), $fields));

if ($Connection->GetAffectedRows() == 1) {
	# output
	if ($fields['CheckedBy'] != 'NULL') {
		$q = new TQuery('SELECT Login FROM Usr WHERE UserID = '.$fields['CheckedBy']);
		if (!$q->EOF)
			$userName = $q->Fields['Login'];
	}
	
	$fields['CheckedBy'] 	= ($fields['CheckedBy'] == 'NULL' && !isset($userName)) ? '' : $userName;
	$fields['CheckedDate'] 	= ($fields['CheckedDate'] == 'NULL') ? '' : date(DATE_FORMAT, $now);
	if ($state == 2)
		$fields['Valid'] = '';
	else
		$fields['Valid'] = ($state == 1) ? 'Yes' : 'No';
	
	outputJson($fields);
} else
	outputJson(array('error' => 'no'));
?>
