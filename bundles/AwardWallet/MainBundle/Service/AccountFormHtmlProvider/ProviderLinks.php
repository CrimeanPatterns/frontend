<?php

namespace AwardWallet\MainBundle\Service\AccountFormHtmlProvider;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class ProviderLinks
{
    public static function get(string $providerCode): array
    {
        switch ($providerCode) {
            case 'delta':
                return [
                    'profileLink' => 'https://www.delta.com/profile/basicInfo.action',
                    'emailProfileLink' => 'https://www.delta.com/profile/notificationsAction.action',
                    'screenshotLink' => 'https://awardwallet.com/blog/track-delta-skymiles-awardwallet/',
                    'targetUrl' => 'https://www.delta.com/myskymiles/overview',
                ];

            case 'deltacorp':
                return [
                    'profileLink' => 'https://skybonus.delta.com/bizCompanyContactInfoLoad.sb?',
                    'emailProfileLink' => 'https://skybonus.delta.com/bizViewCommunicationSetting.sb',
                ];

            case 'rapidrewards':
                return [
                    'profileLink' => 'https://www.southwest.com/myaccount/preferences/personal/notify/edit',
                    'emailProfileLink' => 'https://www.southwest.com/myaccount/preferences/communication/subscriptions/edit',
                    'screenshotLink' => 'https://awardwallet.com/blog/track-southwest-rapid-rewards-awardwallet/',
                    'targetUrl' => 'https://www.southwest.com/loyalty/myaccount/rapid-rewards',
                ];

            case 'mileageplus':
            case 'perksplus':
                return [
                    'profileLink' => 'https://www.united.com/web/en-US/apps/account/email/emailEdit.aspx?Key=K1',
                    'emailProfileLink' => 'https://www.united.com/web/en-US/apps/account/email/subscription/emailSubscription.aspx',
                    'screenshotLink' => 'https://awardwallet.com/blog/track-united-mileageplus-awardwallet/',
                    'targetUrl' => 'https://www.united.com/en/us/myunited',
                ];

            default:
                return [
                    'profileLink' => null,
                    'emailProfileLink' => null,
                ];
        }
    }
}
