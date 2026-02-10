<?php

namespace AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

class Property implements TranslationContainerInterface
{
    private $name;
    private $value;
    private $visible;

    public function __construct($name, $value, $visible = true)
    {
        $this->name = $name;
        $this->value = $value;
        $this->visible = $visible;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('expiring_balance', 'email'))->setDesc('Expiring Balance'),
            (new Message('expiring_coupon', 'email'))->setDesc('Expiring Coupon'),
            (new Message('coupon_value', 'email'))->setDesc('Coupon Value'),
            (new Message('total_balance', 'email'))->setDesc('Total Balance'),
            (new Message('points_expire', 'email'))->setDesc('Points Expire'),
            (new Message('coupon_expires', 'email'))->setDesc('Coupon Expires'),
            (new Message('expires', 'email'))->setDesc('Expires'),
            (new Message('last_account_update', 'email'))->setDesc('Last account update'),
            (new Message('balance_expiration.login-update-v2', 'email'))->setDesc('In order to retrieve the most accurate expiration date please %link_on%log in to AwardWallet%link_off% and %link_on%update this account%link_off%.'),
            (new Message('balance_expiration.forwarded-email', 'email'))->setDesc('In order to get your %name% balance to update automatically you need to set up your statements to be forwarded to your AwardWallet email, for detailed instructions please see this page: %link_on%%link%%link_off%'),
            (new Message('balance_expiration.set-manually', 'email'))->setDesc('This account expiration date was set by you manually (not by AwardWallet), if you believe this expiration date is inaccurate, then please update it to a new one or clear it out to see if we can calculate it for you. You can do that by clicking the edit button next to this account in your profile.'),

            (new Message('balance_expiration.question', 'email'))->setDesc('{1} The account was last updated %span_on%1 day ago%span_off%. Currently it is not working because you need to answer a security question: %span2_on%"%value%"%span2_off%.
                |]1,Inf[ The account was last updated %span_on%%count% days ago%span_off%. Currently it is not working because you need to answer a security question: %span2_on%"%value%"%span2_off%.'),
            (new Message('balance_expiration.question.local-password', 'email'))->setDesc('{1} The account was last updated %span_on%1 day ago%span_off% (the password on this program is stored locally so we are unable to automatically update your account for you). Currently it is not working because you need to answer a security question: %span2_on%"%value%"%span2_off%.
                |]1,Inf[ The account was last updated %span_on%%count% days ago%span_off% (the password on this program is stored locally so we are unable to automatically update your account for you). Currently it is not working because you need to answer a security question: %span2_on%"%value%"%span2_off%.'),
            (new Message('balance_expiration.checked-unchecked', 'email'))->setDesc('{1} The account was last updated %span_on%1 day ago%span_off%.|]1,Inf[ The account was last updated %span_on%%count% days ago%span_off%.'),
            (new Message('balance_expiration.checked-unchecked.local-password', 'email'))->setDesc('{1} The account was last updated %span_on%1 day ago%span_off% (the password on this program is stored locally so we are unable to automatically update your account for you).|]1,Inf[ The account was last updated %span_on%%count% days ago%span_off% (the password on this program is stored locally so we are unable to automatically update your account for you).'),
            (new Message('balance_expiration.error', 'email'))->setDesc('{1} The account was last updated %span_on%1 day ago%span_off% and it has the following error: %span2_on%"%value%"%span2_off%.|]1,Inf[ The account was last updated %span_on%%count% days ago%span_off% and it has the following error: %span2_on%"%value%"%span2_off%.'),
            (new Message('balance_expiration.error.local-password', 'email'))->setDesc('{1} The account was last updated %span_on%1 day ago%span_off% (the password on this program is stored locally so we are unable to automatically update your account for you) and it has the following error: %span2_on%"%value%"%span2_off%.|]1,Inf[ The account was last updated %span_on%%count% days ago%span_off% (the password on this program is stored locally so we are unable to automatically update your account for you) and it has the following error: %span2_on%"%value%"%span2_off%.'),
        ];
    }
}
