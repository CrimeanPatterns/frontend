<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\MobileRouteListener;

use AwardWallet\MainBundle\Globals\StringUtils;

class Routes
{
    /**
     * Format:
     *    'some_route_name' => []
     *        - method name will be 'route_some_route_name'
     *    'some_route_name' => ['some_method_name']
     *        - method name will be 'some_method_name'
     *    'some_route_name' => [null, []]
     *        - second element '[]' for test purposes, means route has no params
     *    'some_route_name' => [null, ['providerId' => 1]]
     *        - route with params
     *    'some_route_name' => [null, [['providerId' => 1], []]]
     *        - route has set of params.
     */
    public const METHOD_MAP = [
        // booking
        'aw_booking_list_requests' => [],
        // account
        'aw_select_provider' => [],
        'aw_account_add' => [null, [[], ['providerId' => 1]]],
        'aw_account_edit' => [null, ['accountId' => 1]],
        'aw_coupon_add' => [],
        'aw_coupon_edit' => [null, ['couponId' => 1]],
        'aw_account_list' => [],
        'aw_account_list_html5' => [null, ['params' => '']],
        // profile
        'aw_profile_overview' => [],
        'aw_profile_personal' => [],
        'aw_user_change_email' => [],
        'aw_profile_change_password' => [],
        'aw_profile_regional' => [],
        'aw_users_usecoupon' => [],
        'aw_profile_notifications' => [],
        'aw_profile_change_password_feedback' => [null, ['id' => 1, 'code' => 'abcdef1234abcdef1234abcdef1234ab']],
        'aw_users_restore_plain' => [null, ['username' => 'someuser']],
        // timeline
        'aw_timeline' => [],
        'aw_travelplan_shared' => [null, ['shareCode' => 'someC0de']],
        'aw_timeline_shared' => ['route_aw_travelplan_shared', ['shareCode' => 'someC0de']],
        // merchant
        // 'aw_merchant_lookup' => [],
        'aw_merchant_reverse_lookup' => [],
        'aw_merchant_lookup_preload' => [null, ['merchantName' => 'm3rchantName']],
        // mile transfer
        'aw_mile_purchase_times_index' => [],
        'aw_mile_transfer_times_index' => [],
    ];

    public static function aw_home()
    {
        return '/m/';
    }

    public static function route_aw_booking_list_requests()
    {
        return '/m/booking';
    }

    public static function route_aw_account_add($parameters)
    {
        return isset($parameters['providerId']) ?
            "/m/account/add/{$parameters['providerId']}" :
            "/m/account/add/custom";
    }

    public static function route_aw_account_edit($parameters)
    {
        return "/m/account/edit/a{$parameters['accountId']}/";
    }

    public static function route_aw_coupon_add()
    {
        return "/m/account/add/coupon";
    }

    public static function route_aw_coupon_edit($parameters)
    {
        return "/m/account/edit/c{$parameters['couponId']}/";
    }

    public static function route_aw_account_list_html5()
    {
        return '/m/';
    }

    public static function route_aw_account_list()
    {
        return '/m/';
    }

    public static function route_aw_profile_change_password_feedback($parameters)
    {
        return "/m/password-recovery/{$parameters['id']}/{$parameters['code']}";
    }

    public static function route_aw_users_restore_plain()
    {
        return "/m/password-recovery";
    }

    public static function route_aw_profile_overview()
    {
        return '/m/profile';
    }

    public static function route_aw_profile_personal()
    {
        return '/m/profile/personal';
    }

    public static function route_aw_user_change_email()
    {
        return '/m/profile/changeEmail';
    }

    public static function route_aw_profile_change_password()
    {
        return '/m/profile/changePassword';
    }

    public static function route_aw_profile_regional()
    {
        return '/m/profile/regional';
    }

    public static function route_aw_users_usecoupon()
    {
        return '/m/profile/useCoupon';
    }

    public static function route_aw_profile_notifications()
    {
        return '/m/profile/notifications/all';
    }

    public static function route_aw_timeline()
    {
        return '/m/timeline/my';
    }

    public static function route_aw_travelplan_shared($parameters)
    {
        return "/m/timeline/shared/{$parameters['shareCode']}";
    }

    public static function route_aw_select_provider()
    {
        return '/m/accounts/add';
    }

    public static function route_aw_merchant_lookup()
    {
        return '/m/merchants';
    }

    public static function route_aw_merchant_reverse_lookup()
    {
        return '/m/merchants/reverse';
    }

    public static function route_aw_merchant_lookup_preload($parameters)
    {
        if (StringUtils::isEmpty($parameters['merchantName'] ?? null)) {
            return null;
        }

        $url = "/m/merchants/{$parameters['merchantName']}";

        return $url;
    }

    public static function route_aw_mile_purchase_times_index()
    {
        return '/m/mile/purchase-times';
    }

    public static function route_aw_mile_transfer_times_index()
    {
        return '/m/mile/transfer-times';
    }
}
