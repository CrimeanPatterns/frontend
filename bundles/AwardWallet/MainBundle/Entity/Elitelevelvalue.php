<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Elitelevelvalue.
 *
 * @ORM\Table(name="EliteLevelValue")
 * @ORM\Entity
 */
class Elitelevelvalue
{
    /**
     * @var int
     * @ORM\Column(name="EliteLevelValueID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $elitelevelvalueid;

    /**
     * @var int
     * @ORM\Column(name="Value", type="integer", nullable=false)
     */
    protected $value;

    /**
     * @var \Elitelevelprogress
     * @ORM\ManyToOne(targetEntity="Elitelevelprogress")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="EliteLevelProgressID", referencedColumnName="EliteLevelProgressID")
     * })
     */
    protected $elitelevelprogressid;

    /**
     * @var \Elitelevel
     * @ORM\ManyToOne(targetEntity="Elitelevel")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="EliteLevelID", referencedColumnName="EliteLevelID")
     * })
     */
    protected $elitelevelid;

    /**
     * Get elitelevelvalueid.
     *
     * @return int
     */
    public function getElitelevelvalueid()
    {
        return $this->elitelevelvalueid;
    }

    /**
     * Set value.
     *
     * @param int $value
     * @return Elitelevelvalue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set elitelevelprogressid.
     *
     * @return Elitelevelvalue
     */
    public function setElitelevelprogressid(?Elitelevelprogress $elitelevelprogressid = null)
    {
        $this->elitelevelprogressid = $elitelevelprogressid;

        return $this;
    }

    /**
     * Get elitelevelprogressid.
     *
     * @return \AwardWallet\MainBundle\Entity\Elitelevelprogress
     */
    public function getElitelevelprogressid()
    {
        return $this->elitelevelprogressid;
    }

    /**
     * Set elitelevelid.
     *
     * @return Elitelevelvalue
     */
    public function setElitelevelid(?Elitelevel $elitelevelid = null)
    {
        $this->elitelevelid = $elitelevelid;

        return $this;
    }

    /**
     * Get elitelevelid.
     *
     * @return \AwardWallet\MainBundle\Entity\Elitelevel
     */
    public function getElitelevelid()
    {
        return $this->elitelevelid;
    }
}
