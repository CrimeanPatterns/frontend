<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\OtherSettingsModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class OtherSettingsTransformer extends AbstractModelTransformer
{
    /**
     * @param Usr $user
     */
    public function transform($user)
    {
        return (new OtherSettingsModel())
            ->setSplashAdsDisabled($user->isSplashAdsDisabled())
            ->setLinkAdsDisabled($user->isLinkAdsDisabled())
            ->setListAdsDisabled($user->isListAdsDisabled())
            ->setIsBlogPostAds($user->isBlogPostAds())
            ->setEntity($user);
    }
}
