<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Adprovider.
 *
 * @ORM\Table(name="AdProvider")
 * @ORM\Entity
 */
class Adprovider
{
    /**
     * @var int
     * @ORM\Column(name="AdProviderID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $adproviderid;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * @var \Socialad
     * @ORM\ManyToOne(targetEntity="Socialad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SocialAdID", referencedColumnName="SocialAdID")
     * })
     */
    protected $socialadid;

    /**
     * Get adproviderid.
     *
     * @return int
     */
    public function getAdproviderid()
    {
        return $this->adproviderid;
    }

    /**
     * Set providerid.
     *
     * @return Adprovider
     */
    public function setProviderid(?Provider $providerid = null)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }

    /**
     * Set socialadid.
     *
     * @return Adprovider
     */
    public function setSocialadid(?Socialad $socialadid = null)
    {
        $this->socialadid = $socialadid;

        return $this;
    }

    /**
     * Get socialadid.
     *
     * @return \AwardWallet\MainBundle\Entity\Socialad
     */
    public function getSocialadid()
    {
        return $this->socialadid;
    }
}
