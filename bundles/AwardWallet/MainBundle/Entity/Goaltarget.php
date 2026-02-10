<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Goaltarget.
 *
 * @ORM\Table(name="GoalTarget")
 * @ORM\Entity
 */
class Goaltarget
{
    /**
     * @var int
     * @ORM\Column(name="GoalTargetID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $goaltargetid;

    /**
     * @var int
     * @ORM\Column(name="PriceEconomy", type="integer", nullable=true)
     */
    protected $priceeconomy;

    /**
     * @var int
     * @ORM\Column(name="PriceBusiness", type="integer", nullable=true)
     */
    protected $pricebusiness;

    /**
     * @var int
     * @ORM\Column(name="PriceFirst", type="integer", nullable=true)
     */
    protected $pricefirst;

    /**
     * @var \Goal
     * @ORM\ManyToOne(targetEntity="Goal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="GoalID", referencedColumnName="GoalID")
     * })
     */
    protected $goalid;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get goaltargetid.
     *
     * @return int
     */
    public function getGoaltargetid()
    {
        return $this->goaltargetid;
    }

    /**
     * Set priceeconomy.
     *
     * @param int $priceeconomy
     * @return Goaltarget
     */
    public function setPriceeconomy($priceeconomy)
    {
        $this->priceeconomy = $priceeconomy;

        return $this;
    }

    /**
     * Get priceeconomy.
     *
     * @return int
     */
    public function getPriceeconomy()
    {
        return $this->priceeconomy;
    }

    /**
     * Set pricebusiness.
     *
     * @param int $pricebusiness
     * @return Goaltarget
     */
    public function setPricebusiness($pricebusiness)
    {
        $this->pricebusiness = $pricebusiness;

        return $this;
    }

    /**
     * Get pricebusiness.
     *
     * @return int
     */
    public function getPricebusiness()
    {
        return $this->pricebusiness;
    }

    /**
     * Set pricefirst.
     *
     * @param int $pricefirst
     * @return Goaltarget
     */
    public function setPricefirst($pricefirst)
    {
        $this->pricefirst = $pricefirst;

        return $this;
    }

    /**
     * Get pricefirst.
     *
     * @return int
     */
    public function getPricefirst()
    {
        return $this->pricefirst;
    }

    /**
     * Set goalid.
     *
     * @return Goaltarget
     */
    public function setGoalid(?Goal $goalid = null)
    {
        $this->goalid = $goalid;

        return $this;
    }

    /**
     * Get goalid.
     *
     * @return \AwardWallet\MainBundle\Entity\Goal
     */
    public function getGoalid()
    {
        return $this->goalid;
    }

    /**
     * Set providerid.
     *
     * @return Goaltarget
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
