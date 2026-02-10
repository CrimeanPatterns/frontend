<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MerchantPatternGroup.
 *
 * @ORM\Entity
 * @ORM\Table(name="MerchantPatternGroup")
 */
class MerchantPatternGroup
{
    /**
     * @var ?MerchantGroup
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="MerchantGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MerchantGroupID", referencedColumnName="MerchantGroupID")
     * })
     */
    private $merchantgroup;

    /**
     * @var ?MerchantPattern
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="MerchantPattern")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MerchantPatternID", referencedColumnName="MerchantPatternID")
     * })
     */
    private $merchantpattern;

    public function setMerchantGroup(MerchantGroup $merchantgroup): self
    {
        $this->merchantgroup = $merchantgroup;

        return $this;
    }

    public function getMerchantGroup(): ?MerchantGroup
    {
        return $this->merchantgroup;
    }

    public function setMerchantPattern(MerchantPattern $merchantPattern): self
    {
        $this->merchantpattern = $merchantPattern;

        return $this;
    }

    public function getMerchantPattern(): ?MerchantPattern
    {
        return $this->merchantpattern;
    }
}
