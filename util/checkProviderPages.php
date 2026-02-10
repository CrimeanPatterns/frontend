<?php
require "../web/kernel/public.php";
require_once "../web/schema/ProviderPage.php";
set_time_limit(300);
echo "checking provider pages\n";
$q = new TQuery("select pp.ProviderPageID, p.DisplayName, pp.PageName from ProviderPage pp
join Provider p on p.ProviderID = pp.ProviderID
order by p.DisplayName, pp.PageName");
$sText = "";
while(!$q->EOF){
	echo "{$q->Fields["ProviderPageID"]} - {$q->Fields["DisplayName"]} - {$q->Fields["PageName"]} - ";
	$nStatus = CheckProviderPage($q->Fields["ProviderPageID"]);
	echo $arPageStatus[$nStatus]."\n";
	if($nStatus >= PAGE_STATUS_DIFF)
		$sText .= "{$q->Fields["ProviderPageID"]} - {$q->Fields["DisplayName"]} - {$q->Fields["PageName"]} - ".$arPageStatus[$nStatus]."\n";
	$q->Next();
}
if($sText != ""){
	echo "we have changes, mailing\n";
	mailTo(SUPPORT_EMAIL, "Provider pages changed", $sText, EMAIL_HEADERS);
}
else
	echo "no changes\n";
echo "finished\n";
?>
