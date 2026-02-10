<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Awardcategory.
 *
 * @ORM\Table(name="AwardCategory")
 * @ORM\Entity
 */
class Awardcategory
{
    /**
     * @var int
     * @ORM\Column(name="AwardCategoryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $awardcategoryid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="ClassNumber", type="integer", nullable=true)
     */
    protected $classnumber;

    /**
     * Get awardcategoryid.
     *
     * @return int
     */
    public function getAwardcategoryid()
    {
        return $this->awardcategoryid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Awardcategory
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set classnumber.
     *
     * @param int $classnumber
     * @return Awardcategory
     */
    public function setClassnumber($classnumber)
    {
        $this->classnumber = $classnumber;

        return $this;
    }

    /**
     * Get classnumber.
     *
     * @return int
     */
    public function getClassnumber()
    {
        return $this->classnumber;
    }
}
