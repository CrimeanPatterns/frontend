<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

class RegionalModel extends AbstractEntityAwareModel
{
    /**
     * @var string
     * @Assert\NotBlank
     */
    protected $language;

    /**
     * @var string
     */
    protected $region;

    protected $currency;

    /**
     * @var bool
     */
    protected $modelChanged = false;

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return RegionalModel
     */
    public function setLanguage($language)
    {
        if ($language !== $this->language) {
            $this->modelChanged = true;
        }

        $this->language = $language;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $region
     * @return RegionalModel
     */
    public function setRegion($region)
    {
        if ($region !== $this->region) {
            $this->modelChanged = true;
        }

        $this->region = $region;

        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency): self
    {
        if ($currency !== $this->currency) {
            $this->modelChanged = true;
        }

        $this->currency = $currency;

        return $this;
    }

    /**
     * @return bool
     */
    public function isModelChanged()
    {
        return $this->modelChanged;
    }

    /**
     * @param bool $modelChanged
     * @return RegionalModel
     */
    public function setModelChanged($modelChanged)
    {
        $this->modelChanged = $modelChanged;

        return $this;
    }
}
