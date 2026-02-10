<?php

class AbRequestsListPage
{
    public static $requestsRoute = 'aw_booking_list_requests';
    public static $queueRoute = 'aw_booking_list_queue';

    public static $selector_requests = '//*[contains(@class, "my-requests")]';
    public static $selector_requestsRows = '//td[@class="booking-id"]/..';
    public static $selector_requestsMarkUnReadButton = '//tr[contains(@class, "tablebody")][%num%]//i[@class="old-icon-booking-read-message"]';
    public static $selector_requestsMarkReadButton = '//tr[contains(@class, "tablebody")][%num%]//i[@class="old-icon-booking-message"]';
    public static $selector_requestsReplyButton = '//tr[contains(@class, "tablebody")][%num%]//td[@class="booking-btn"]/a[1]';

    public static $selector_queue = '//*[@id="filter"]';
    public static $selector_queueRow = '//tr[contains(@class, "tablebody")][%num%]';
    public static $selector_queueRows = '//td[@class="booking-id"]/..';
    public static $selector_queueCellId = '//td[@class="booking-id"]';
    public static $selector_queuePagination = '.booking-pagination';
    public static $selector_queuePaginationPages = '//*[@class="booking-pagination"]//li';
    public static $selector_queuePaginationActivePage = '//*[@class="booking-pagination"]//li[@class="active"]/a[text()="%page%"]';
    public static $selector_queuePaginationPage = '//*[@class="booking-pagination"]//li/a[text()="%page%"]';
    public static $selector_queueFilterFindButton = '#find_btn';
    public static $selector_queueFilterResetButton = '//*[@id="find_btn"]/following-sibling::a';
    public static $selector_queueFilterId = 'id_filter';
    public static $selector_queueSortLink = '//*[@id="filter"]//tr[@class="booking-requests-caption"]/td[%num%]//a';
}
