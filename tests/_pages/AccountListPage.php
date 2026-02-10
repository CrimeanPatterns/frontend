<?php

class AccountListPage
{
    public static $router = 'aw_account_list';

    public static $is_successInitialized = '//div[contains(@class, "account-row")]';
    public static $is_successFiltered = '//div[contains(@class, "filtered-row")]/a[contains(@class, "btn-silver")]';
    public static $is_successChangeWeek = '//div[contains(@class, "filtered-row")]/p/span[contains(., "week")]';

    public static $selector_archivedTab = '//ul[@class = "tabs-navigation middle"]/li[2]/a';
    public static $selector_allTab = '//ul[@class = "tabs-navigation middle"]/li[1]/a';

    public static $selector_allAgent = '//ul[contains(@class, "persons")]/li[1]//a';
    public static $selector_firstAgent = '//ul[contains(@class, "persons")]/li[2]//a[not(contains(@class, "add"))]';

    public static $selector_filterMenu = '//a[@data-target = "list-options"]';
    public static $selector_filterFullList = '//ul[@data-id = "list-options"]/li/a/span/i[@class = "icon-full-list"]/../..';
    public static $selector_filterChangeWeek = '//ul[@data-id = "list-options"]/li/a/span/i[@class = "icon-change-week"]/../..';
    public static $selector_filterGroup = '//ul[@data-id = "list-options"]/li/a/span/i[@class = "icon-group"]/../..';
    public static $selector_filterUngroup = '//ul[@data-id = "list-options"]/li/a/span/i[@class = "icon-ungroup"]/../..';
    public static $selector_filterHideErrors = '//ul[@data-id = "list-options"]/li/a/span/i[@class = "icon-hide-errors"]/../..';
    public static $selector_filterShowErrors = '//ul[@data-id = "list-options"]/li/a/span/i[@class = "icon-show-errors"]/../..';

    public static $selector_searchField = '//input[@data-ng-model = "search.search"]';
    public static $selector_searchClear = '//input[@data-ng-model = "search.search"]/../a';

    public static $selector_order = '//div[contains(@class,"account-title-row")]/div[@class="%field%"]/a';
    public static $selector_orderDirection = '//div[contains(@class,"account-title-row")]/div[@class="%field%"]/a/i';
    public static $selector_orderCurrent = '//div[contains(@class,"account-title-row")]/div/a/i/../..';
    public static $data_orders = ['program', 'balance', 'expire'];
    public static $data_directions = ['icon-silver-arrow-up', 'icon-silver-arrow-down'];

    public static $selector_accountRow = '//div[@class="row-account" or @class="row-coupon"]';
    public static $selector_kindRow = '//div[@class="row-kind"]';
    public static $selector_kindUserRow = '//div[@class="row-kind-user"]';
    public static $selector_errorRow = '//div[contains(@class, "account-row-error")]';
    public static $selector_firstRow = '//div[@class="row-account" or @class="row-coupon"][1]/div[contains(@class,"account-row")]/div';
    public static $selector_lastRow = '//div[@class="row-account" or @class="row-coupon"][last()]/div[contains(@class,"account-row")]/div';

    public static $selector_accountDisabledRow = '//div[@class="row-account"]/div[contains(@class,"account-row")]//div[contains(@class,"disabled")]';
    public static $selector_updaterIcon = '//i[@class="icon-dark-refresh"]';

    public static $js_hideMenu = '$("div.main-body").addClass("hide-menu")';
    public static $js_showMenu = '$("div.main-body").removeClass("hide-menu")';
}
