<?
require "../kernel/public.php";
require_once "$sPath/manager/passwordVault/common.php";

$sTitle = "Fixing onecard tags";

require "$sPath/lib/admin/design/header.php";

$q = new TQuery("select
		*
	from
		OneCard
");
while(!$q->EOF){
	foreach(array('AccFront', 'PFront', 'AFront', 'SFront', 'PhFront', 'AccBack', 'PBack', 'ABack', 'SBack', 'PhBack') as $field){
		$stripped = strip_tags($q->Fields[$field]);
		if($q->Fields[$field] != $stripped){
			echo "correcting card {$q->Fields['OneCardID']}<br/>";
			echo "old: ".htmlspecialchars($q->Fields[$field])."<br/>";
			echo "new: ".htmlspecialchars($stripped)."<br/>";
			$Connection->Execute("update OneCard set {$field} = '".addslashes($stripped)."'
			where OneCardID = {$q->Fields['OneCardID']}");
			echo "<hr/>";
		}
	}
	$q->Next();
}
echo "processed: ".$q->Position."<br/>";

require "$sPath/lib/admin/design/footer.php";
