<?php
// http://redmine.awardwallet.com/issues/5024

$schema = "UsersIncome";
require( "../start.php" );

// compatibility for old links
Redirect('/manager/list.php?' . $_SERVER['QUERY_STRING'] . '&Schema=UsersIncome');

/*if(isset($_POST['export'])){
	$list->ExportName = date('Y-m-d h:i:s');
	$list->ExportCSV();
}else{
	drawHeader();
	$list->Draw();
	drawFooter();
}*/

?>