<?php

class RegisterPage
{
    public static $route = 'aw_home';

    public static $selector_button = '//a[contains(@href, "/register")]';
    public static $selector_popup = '//*[contains(@class, "sign-in")]';
    public static $selector_quickreg_button = '//button[@id="quickRegistration"]';
    public static $selector_email = 'email';
    public static $selector_password = 'pass';
    public static $selector_fn = 'firstname';
    public static $selector_ln = 'lastname';
    public static $selector_coupon = 'coupon';
    public static $selector_submit = '//button[@id="quickRegistrationSubmit"]';
}
