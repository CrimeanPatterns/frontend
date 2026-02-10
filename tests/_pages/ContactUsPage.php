<?php

class ContactUsPage
{
    public static $route = 'aw_contactus_index';

    public static $selector_unauth_fname = "contact_us_unauth[fullname]";
    public static $selector_unauth_email = "contact_us_unauth[email]";
    public static $selector_unauth_phone = "contact_us_unauth[phone]";
    public static $selector_unauth_type = "contact_us_unauth[requesttype]";
    public static $selector_auth_type = "contact_us_auth[requesttype]";
    public static $selector_unauth_message = "contact_us_unauth[message]";
    public static $selector_auth_message = "contact_us_auth[message]";

    public static $selector_submit_button = "//button[text()='Send']";
    public static $selector_search_result = "//*[@id='programSearch']";
    public static $selector_search_result_send = "//*[@id='programSearch']/..//*[contains(text(), 'Send Anyway')]";
}
