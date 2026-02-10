<?php

class AddFamilyMemberPage
{
    // include url of current page
    public static $route = 'aw_add_agent';

    public static $selector_fname = "add_agent[firstname]";
    public static $selector_lname = "add_agent[lastname]";
    public static $selector_email = "add_agent[email]";
    public static $selector_invite = "//label[@for='add_agent_invite']";
    public static $selector_button = "//form//button[contains(text(), 'Add to your profile')]";
}
