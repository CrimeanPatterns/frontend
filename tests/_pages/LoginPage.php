<?php

class LoginPage
{
    public static $URL = '/';

    public static $selector_landing = '#watchButton';
    public static $selector_button = '#loginButton';
    public static $selector_popup = '#framePopup';
    public static $selector_frame = 'popupFrame';
    public static $selector_login = 'Login';
    public static $selector_password = 'Password';
    public static $selector_submit = '//*[@name="Login"]//*[@type="submit"]';

    // prefix '_new_' for new login
    public static $_new_route = 'aw_home';

    public static $_new_selector_landing = '//*[@ng-app="landingPage"]';
    public static $_new_selector_button = '//a[contains(@href, "/login")]';
    public static $_new_selector_popup = '//*[contains(@class, "login-in")]';
    public static $_new_selector_login = 'login';
    public static $_new_selector_password = 'password';
    public static $_new_selector_remember = '//label[@for="remember_me"]';
    public static $_new_selector_submit = '#login-button';
}
