<?php

class RecoverPage
{
    public static $route = 'aw_home';

    public static $selector_button = '//a[contains(@href, "/restore")]';
    public static $selector_popup = '//*[contains(@class, "forgot-password")]';
    public static $selector_emailOrLogin = 'username';
    public static $selector_submit = '//*[contains(@class, "forgot-password")]//button[not(@disabled)]';
}
