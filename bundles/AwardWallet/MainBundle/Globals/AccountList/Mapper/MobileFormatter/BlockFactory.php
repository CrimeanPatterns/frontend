<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @psalm-type Block = array{
 *      Kind: string,
 *      Name?: string,
 *      Val?: mixed,
 *      Visible?: mixed,
 *  }
 * @NoDI()
 */
class BlockFactory
{
    public const BLOCK_VACCINE_1ST_DOSE_VACCINE = 'vaccine_1st_dose_vaccine';
    public const BLOCK_TYPE_TEXT_PROPERTY = 'textProperty';
    public const BLOCK_STATUS = 'status';
    public const BLOCK_INSURANCE_NAME_ON_CARD = 'insurance_name_on_card';
    public const BLOCK_DRIVERS_LICENSE_SEX = 'drivers_license_sex';
    public const BLOCK_COUPON_VALUE = 'couponValue';
    public const BLOCK_VISA_ISSUED_IN = 'visa_issued_in';
    public const BLOCK_ELITE_STATUS = 'eliteStatus';
    public const BLOCK_WARNING = 'warning';
    public const BLOCK_DRIVERS_LICENSE_DATE_OF_BIRTH = 'drivers_license_date_of_birth';
    public const BLOCK_VISA_CATEGORY = 'visa_category';
    public const BLOCK_DRIVERS_LICENSE_HEIGHT = 'drivers_license_height';
    public const BLOCK_TYPE_DATE = 'date';
    public const BLOCK_PASSPORT_NUMBER = 'passport_number';
    public const BLOCK_VISA_DURATION_IN_DAYS = 'visa_duration_in_days';
    public const BLOCK_COMMENT = 'comment';
    public const BLOCK_VACCINE_COUNTRY_OF_ISSUE = 'vaccine_country_of_issue';
    public const BLOCK_INSURANCE_TYPE = 'insurance_type';
    public const BLOCK_INSURANCE_EFFECTIVE_DATE = 'insurance_effective_date';
    public const BLOCK_PRIORITY_PASS_CREDIT_CARD = 'priority_pass_credit_card';
    public const BLOCK_IATA = 'iata';
    public const BLOCK_VISA_NUMBER_ENTRIES = 'visa_number_entries';
    public const BLOCK_LOGIN = 'login';
    public const BLOCK_EXPIRATION_DATE = 'expirationDate';
    public const BLOCK_VACCINE_2ND_DOSE_VACCINE = 'vaccine_2nd_dose_vaccine';
    public const BLOCK_CARD_NUMBER = 'CardNumber';
    public const BLOCK_SUCCESS_CHECK_DATE = 'successCheckDate';
    public const BLOCK_VACCINE_BOOSTER_DATE = 'vaccine_booster_date';
    public const BLOCK_TYPE_STRING = 'string';
    public const BLOCK_PRIORITY_PASS_ACCOUNT_NUMBER = 'priority_pass_account_number';
    public const BLOCK_TYPE_UPGRADE = 'upgrade';
    public const BLOCK_DRIVERS_LICENSE_FULL_NAME = 'drivers_license_full_name';
    public const BLOCK_BLOG_PROMOTIONS = 'blog_promotions';
    public const BLOCK_VISA_FULL_NAME = 'visa_full_name';
    public const BLOCK_BARCODE = 'barcode';
    public const BLOCK_VISA_ISSUE_DATE = 'visa_issue_date';
    public const BLOCK_PROPERTIES = 'properties';
    public const BLOCK_DRIVERS_LICENSE_ISSUE_DATE = 'drivers_license_issue_date';
    public const BLOCK_PASSPORT_ISSUE_DATE = 'passport_issue_date';
    public const BLOCK_DRIVERS_LICENSE_COUNTRY = 'drivers_license_country';
    public const BLOCK_MILE_VALUE = 'mile_value';
    public const BLOCK_VISA_VALID_FROM = 'visa_valid_from';
    public const BLOCK_BALANCE = 'balance';
    public const BLOCK_VACCINE_2ND_DOSE_DATE = 'vaccine_2nd_dose_date';
    public const BLOCK_VACCINE_CERTIFICATE_ISSUED = 'vaccine_certificate_issued';
    public const BLOCK_DISABLED = 'disabled';
    public const BLOCK_TYPE_SUB_TITLE = 'subTitle';
    public const BLOCK_TRUSTED_TRAVELER_NUMBER = 'trusted_traveler_number';
    public const BLOCK_PRIORITY_PASS_IS_SELECT = 'priority_pass_is_select';
    public const BLOCK_INSURANCE_PREAUTH_PHONE = 'insurance_preauth_phone';
    public const BLOCK_PASSPORT_NAME = 'passport_name';
    public const BLOCK_SET_LOCATION_LINK = 'set_location_link';
    public const BLOCK_PIN = 'PIN';
    public const BLOCK_VACCINE_2ND_BOOSTER_VACCINE = 'vaccine_second_booster_vaccine';
    public const BLOCK_TYPE = 'TypeID';
    public const BLOCK_VISA_COUNTRY = 'visa_country';
    public const BLOCK_VISA_NUMBER = 'visa_number';
    public const BLOCK_INSURANCE_MEMBER_NUMBER = 'insurance_member_number';
    public const BLOCK_TYPE_MILE_VALUE = 'mileValue';
    public const BLOCK_OWNER = 'owner';
    public const BLOCK_DRIVERS_LICENSE_CLASS = 'drivers_license_class';
    public const BLOCK_NOTICE = 'notice';
    public const BLOCK_BALANCE_WATCH = 'balance_watch';
    public const BLOCK_VACCINE_1ST_DOSE_DATE = 'vaccine_1st_dose_date';
    public const BLOCK_INSURANCE_GROUP_NUMBER = 'insurance_group_number';
    public const BLOCK_DRIVERS_LICENSE_STATE = 'drivers_license_state';
    public const BLOCK_INSURANCE_COMPANY = 'insurance_company';
    public const BLOCK_LOCATIONS = 'storeLocations';
    public const BLOCK_DESCRIPTION = 'description';
    public const BLOCK_VACCINE_DATE_OF_BIRTH = 'vaccine_date_of_birth';
    public const BLOCK_INSURANCE_MEMBER_SERVICE_PHONE = 'insurance_member_service_phone';
    public const BLOCK_DRIVERS_LICENSE_INTERNATIONAL_LICENSE = 'drivers_license_international_license';
    public const BLOCK_BLOG_TOP_POSTS = 'blog_top_posts';
    public const BLOCK_VACCINE_PASSPORT_NAME = 'vaccine_passport_name';
    public const BLOCK_PASSPORT_ISSUE_COUNTRY = 'passport_issue_country';
    public const BLOCK_INSURANCE_POLICY_HOLDER = 'insurance_policy_holder';
    public const BLOCK_LINK = 'link';
    public const BLOCK_CARD_IMAGE = 'card_images';
    public const BLOCK_DRIVERS_LICENSE_LICENSE_NUMBER = 'drivers_license_license_number';
    public const BLOCK_ACCOUNT_NUMBER = 'accountNumber';
    public const BLOCK_VACCINE_2ND_BOOSTER_DATE = 'vaccine_second_booster_date';
    public const BLOCK_CASH_VALUE = 'cash_value';
    public const BLOCK_VACCINE_PASSPORT_NUMBER = 'vaccine_passport_number';
    public const BLOCK_VACCINE_BOOSTER_VACCINE = 'vaccine_booster_vaccine';
    public const BLOCK_DRIVERS_LICENSE_ORGAN_DONOR = 'drivers_license_organ_donor';
    public const BLOCK_SPACER = 'spacer';
    public const BLOCK_INSURANCE_TYPE_2 = 'insurance_type_2';
    public const BLOCK_LINKS = 'links';
    public const BLOCK_INSURANCE_OTHER_PHONE = 'insurance_other_phone';
    public const BLOCK_DRIVERS_LICENSE_EYES = 'drivers_license_eyes';
    public const BLOCK_BLOG_POSTS = 'blog_posts';

    /**
     * @return Block
     */
    public static function createBlock(string $kind, ?string $name = null, $value = null, $visible = null): array
    {
        $block = ['Kind' => $kind];

        if (isset($name)) {
            $block['Name'] = $name;
        }

        if (isset($value)) {
            $block['Val'] = $value;
        }

        if (isset($visible)) {
            $block['Visible'] = $visible;
        }

        return $block;
    }
}
