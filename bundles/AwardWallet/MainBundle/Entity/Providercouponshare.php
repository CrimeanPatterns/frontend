<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Providercouponshare.
 *
 * @ORM\Table(name="ProviderCouponShare")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\ProvidercouponshareRepository")
 */
class Providercouponshare
{
    /**
     * @var int
     * @ORM\Column(name="ProviderCouponShareID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providercouponshareid;

    /**
     * @var \Providercoupon
     * @ORM\ManyToOne(targetEntity="Providercoupon", inversedBy="shares")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderCouponID", referencedColumnName="ProviderCouponID")
     * })
     */
    protected $providercouponid;

    /**
     * @var \Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $useragentid;

    /**
     * Get providercouponshareid.
     *
     * @return int
     */
    public function getProvidercouponshareid()
    {
        return $this->providercouponshareid;
    }

    /**
     * Set providercouponid.
     *
     * @return Providercouponshare
     */
    public function setProvidercouponid(?Providercoupon $providercouponid = null)
    {
        $this->providercouponid = $providercouponid;

        return $this;
    }

    /**
     * Get providercouponid.
     *
     * @return \AwardWallet\MainBundle\Entity\Providercoupon
     */
    public function getProvidercouponid()
    {
        return $this->providercouponid;
    }

    /**
     * Set useragentid.
     *
     * @return Providercouponshare
     */
    public function setUseragentid(?Useragent $useragentid = null)
    {
        $this->useragentid = $useragentid;

        return $this;
    }

    /**
     * Get useragentid.
     *
     * @return \AwardWallet\MainBundle\Entity\Useragent
     */
    public function getUseragentid()
    {
        return $this->useragentid;
    }
}
