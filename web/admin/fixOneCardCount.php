<?
require "../kernel/public.php";
require_once "$sPath/manager/passwordVault/common.php";

$sTitle = "Fixing password vault";

require "$sPath/lib/admin/design/header.php";

$q = new TQuery("select
		*
	from
		CartItem
	where
		TypeID = ".CART_ITEM_ONE_CARD_SHIPPING."
");
while(!$q->EOF){
	if(preg_match("/total cards ordered:\s*(\d+)/ims", $q->Fields['Name'], $matches))
		$count = $matches[1];
	else
		$count = $q->Fields['Cnt']; 
	$Connection->Execute("update CartItem set UserData = $count where CartItemID = ".$q->Fields['CartItemID']);
	$q->Next();
}
echo "processed: ".$q->Position."<br/>";

require "$sPath/lib/admin/design/footer.php";
