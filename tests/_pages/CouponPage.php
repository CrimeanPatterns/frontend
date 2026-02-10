<?php

class CouponPage
{
    // include url of current page
    public static $route = 'aw_users_usecoupon';

    public static $selector_input = "form[coupon]";
    public static $selector_submit = '//button[text() = "Apply Coupon"]';
}
