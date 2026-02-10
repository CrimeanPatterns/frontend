<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dealrelatedprovider.
 *
 * @ORM\Table(name="DealRelatedProvider")
 * @ORM\Entity
 */
class Dealrelatedprovider
{
    /**
     * @var int
     * @ORM\Column(name="DealRelatedProviderID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $dealrelatedproviderid;

    /**
     * @var \Deal
     * @ORM\ManyToOne(targetEntity="Deal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="DealID", referencedColumnName="DealID")
     * })
     */
    protected $dealid;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get dealrelatedproviderid.
     *
     * @return int
     */
    public function getDealrelatedproviderid()
    {
        return $this->dealrelatedproviderid;
    }

    /**
     * Set dealid.
     *
     * @return Dealrelatedprovider
     */
    public function setDealid(?Deal $dealid = null)
    {
        $this->dealid = $dealid;

        return $this;
    }

    /**
     * Get dealid.
     *
     * @return \AwardWallet\MainBundle\Entity\Deal
     */
    public function getDealid()
    {
        return $this->dealid;
    }

    /**
     * Set providerid.
     *
     * @return Dealrelatedprovider
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
}
