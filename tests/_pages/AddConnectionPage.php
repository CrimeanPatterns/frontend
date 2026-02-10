<?php

class AddConnectionPage
{
    // include url of current page
    public static $route = 'aw_create_connection';

    public static $selector_email = "form[email]";
    public static $selector_submit_email_button = '//form//button[@type="submit"]';
    public static $selector_button_search_again = '//form[contains(@action, "invite")]//button[contains(text(), "Search")]';
    public static $selector_button_invite = '//form[contains(@action, "invite")]//button[contains(text(), "Invite")]';
    public static $selector_button_connect = '//form//button[contains(text(), "connect me with this person")]';
    public static $selector_message_success_invite = '//*[@class="success-message"]//*[contains(text(), "sent an email")]';
}
