<?
    $s = ob_get_clean();
    $s = str_ireplace("%nextHeadersDisplay%", '', $s);
    $s = str_ireplace("%tabStyle%", '', $s);
    if(isset($objList) && ($objList->CouponCount > 0 || $objList->CouponSubAccounts > 0)){
        # <!-- show coupons -->
        if ($objList->CouponCount > 0) {
            $html = '<tr><td style="padding-bottom: 5px;"><input type="checkbox" id="couponCheck"';
            if(ArrayVal($_GET, 'Coupons') == '1')
                $html .= " checked";
            $html .= ' onclick="onlyCouponsClick()"></td><td style="padding-bottom: 5px; padding-left: 7px;"><a href="#" onclick="document.getElementById(\'couponCheck\').checked = !document.getElementById(\'couponCheck\').checked; onlyCouponsClick(); return false;" class="leftMenuLink">Only Show Coupons</a></td></tr>';
            $s = str_ireplace('<!-- show coupons -->', $html, $s);
        }
        # <!-- show inactive coupons -->
        $html = '<tr><td style="padding-bottom: 5px;"><input type="checkbox" id="expcouponCheck"';
        if(ArrayVal($_GET, 'ExpCoupons') == '1')
                $html .= " checked";
        $html .= ' onclick="expiredCouponsClick()"></td><td style="padding-bottom: 5px; padding-left: 7px;"><a href="#" onclick="document.getElementById(\'expcouponCheck\').checked = !document.getElementById(\'expcouponCheck\').checked; expiredCouponsClick(); return false;" class="leftMenuLink">Show Inactive Coupons</a></td></tr>';
        $s = str_ireplace('<!-- show inactive coupons -->', $html, $s);
    }
	ob_start();
    echo $s;

