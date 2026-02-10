<?php

class CartPage
{
    public static $pay_route = 'aw_users_pay';
    public static $payment_type_route = 'aw_cart_common_paymenttype';
    public static $order_details_route = 'aw_cart_common_orderdetails';
    public static $order_preview_route = 'aw_cart_common_orderpreview';

    public static $selector_submit = '//button[contains(@type, "submit")]';
    public static $selector_input_submit = '//input[contains(@type, "submit")]';
    public static $selector_test_credit_card = '//input[contains(@id, "select_payment_type_type_2")]';
    public static $selector_one_card = '#user_pay_onecard';

    public static $selector_billing_address1 = '//input[contains(@id, "billing_address_address1")]';
    public static $selector_billing_city = '//input[contains(@id, "billing_address_city")]';
    public static $selector_billing_country = '//select[contains(@id, "billing_address_countryid")]';
    public static $selector_billing_state = '//select[contains(@id, "billing_address_stateid")]';
    public static $selector_billing_zip = '//input[contains(@id, "billing_address_zip")]';

    public static $selector_card_number = '//input[contains(@id, "card_info_card_number")]';
    public static $selector_card_security_code = '//input[contains(@id, "card_info_security_code")]';
    public static $selector_card_expiration_month = '//select[contains(@id, "card_info_expiration_month")]';
    public static $selector_card_expiration_year = '//select[contains(@id, "card_info_expiration_year")]';

    public static $selector_pay = '#cardPayButton';
}
