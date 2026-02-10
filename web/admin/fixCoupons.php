<?php
require "../kernel/public.php";

$sTitle = "Fixing coupons";

require "$sPath/lib/admin/design/header.php";

ob_end_flush();
$q = new TQuery("select
        ci.*
    from
        Cart c
        join CartItem ci on c.CartID = ci.CartID
        join Usr u on c.UserID = u.UserID
		join Coupon co on c.CouponID = co.CouponID
    where
        c.CouponName <> '' AND ci.Discount = 0
		and co.Discount = 100");
$total = 0;
while(!$q->EOF){
	echo "Fixing cart item {$q->Fields['CartItemID']}, <br>";
	$Connection->Execute("update CartItem set Discount = 100 where CartItemID = {$q->Fields['CartItemID']}");
	$total += floatval($q->Fields['Price']);
	$q->Next();
}
echo "Total: {$total}<br>";

require "$sPath/lib/admin/design/footer.php";
?>
