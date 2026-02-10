<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MasterSlaveCategoryReport.
 *
 * @ORM\Entity
 * @ORM\Table(name="MasterSlaveCategoryReport")
 */
class MasterSlaveCategoryReport
{
    /**
     * @var int
     * @ORM\Column(name="Counter", type="integer", nullable=false)
     */
    protected $counter;
    /**
     * @var ShoppingCategory
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ShoppingCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MasterCategoryID", referencedColumnName="ShoppingCategoryID")
     * })
     */
    private $masterCategory;

    /**
     * @var ShoppingCategory
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ShoppingCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SlaveCategoryID", referencedColumnName="ShoppingCategoryID")
     * })
     */
    private $slaveCategory;

    public function getMasterCategory(): ShoppingCategory
    {
        return $this->masterCategory;
    }

    /**
     * @return $this
     */
    public function setMasterCategory(ShoppingCategory $masterCategory)
    {
        $this->masterCategory = $masterCategory;

        return $this;
    }

    public function getSlaveCategory(): ShoppingCategory
    {
        return $this->slaveCategory;
    }

    /**
     * @return $this
     */
    public function setSlaveCategory(ShoppingCategory $slaveCategory)
    {
        $this->slaveCategory = $slaveCategory;

        return $this;
    }

    public function getCounter(): ?int
    {
        return $this->counter;
    }

    /**
     * @return $this
     */
    public function setCounter(int $counter)
    {
        $this->counter = $counter;

        return $this;
    }
}
