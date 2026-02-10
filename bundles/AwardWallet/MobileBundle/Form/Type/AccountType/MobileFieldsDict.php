<?php

namespace AwardWallet\MobileBundle\Form\Type\AccountType;

interface MobileFieldsDict
{
    public const TOP_SEPARATOR = 'topSeparator';
    public const MIDDLE_SEPARATOR = 'middleSeparator';
    public const BOTTOM_SEPARATOR = 'bottomSeparator';
    public const TOP_DESC = 'topDesc';
    public const MIDDLE_DESC = 'middleDesc';
    public const DONT_TRACK_EXPIRATION = 'donttrackexpiration';
    public const HIDE_SUBACCOUNT = 'hidesubaccount';
    public const IS_BUSINESS = 'IsBusiness';
    public const IS_BALANCE_WATCH_DISABLED = 'IsBalanceWatchDisabled';
    public const IS_BALANCE_WATCH_AW_PLUS = 'IsBalanceWatchAwPlus';
    public const URL_PAY_CREDIT = 'URL_PayCredit';
    public const URL_PAY_AW_PLUS = 'URL_PayAwPlus';
    public const IS_BALANCE_WATCH_ACCOUNT_ERROR = 'IsBalanceWatchAccountError';
    public const IS_BALANCE_WATCH_ACCOUNT_CAN_CHECK = 'IsBalanceWatchAccountCanCheck';
    public const IS_BALANCE_WATCH_LOCAL_PASSWORD_EXCLUDE = 'IsBalanceWatchLocalPasswordExclude';
    public const IS_BALANCE_WATCH_CREDITS = 'IsBalanceWatchCredits';
    public const BALANCE_WATCH = 'BalanceWatch';
    public const POINTS_SOURCE = 'PointsSource';
    public const TRANSFER_PROVIDER_CURRENCY = 'TransferProviderCurrency';
    public const TRANSFER_FROM_PROVIDER = 'TransferFromProvider';
    public const EXPECTED_POINTS = 'ExpectedPoints';
    public const TRANSFER_REQUEST_DATE = 'TransferRequestDate';
    public const DISABLE_CLIENT_PASSWORD_ACCESS = 'disableclientpasswordaccess';
    public const CARD_IMAGES = 'cardImages';
    public const BARCODE = 'barcode';
    public const OWNER = 'owner';
}
