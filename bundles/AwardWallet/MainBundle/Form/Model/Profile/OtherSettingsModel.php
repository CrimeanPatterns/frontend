<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

class OtherSettingsModel extends AbstractEntityAwareModel
{
    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $splashAdsDisabled = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $listAdsDisabled = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $linkAdsDisabled = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $isBlogPostAds = true;

    /**
     * @return bool
     */
    public function isSplashAdsDisabled()
    {
        return $this->splashAdsDisabled;
    }

    /**
     * @param bool $splashAdsDisabled
     * @return OtherSettingsModel
     */
    public function setSplashAdsDisabled($splashAdsDisabled)
    {
        $this->splashAdsDisabled = $splashAdsDisabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLinkAdsDisabled()
    {
        return $this->linkAdsDisabled;
    }

    /**
     * @param bool $linkAdsDisabled
     * @return OtherSettingsModel
     */
    public function setLinkAdsDisabled($linkAdsDisabled)
    {
        $this->linkAdsDisabled = $linkAdsDisabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isListAdsDisabled()
    {
        return $this->listAdsDisabled;
    }

    /**
     * @param bool $listAdsDisabled
     * @return OtherSettingsModel
     */
    public function setListAdsDisabled($listAdsDisabled)
    {
        $this->listAdsDisabled = $listAdsDisabled;

        return $this;
    }

    public function isBlogPostAds()
    {
        return $this->isBlogPostAds;
    }

    public function setIsBlogPostAds(bool $isBlogPostAds): self
    {
        $this->isBlogPostAds = $isBlogPostAds;

        return $this;
    }
}
