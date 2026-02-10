<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Rentalcompany.
 *
 * @ORM\Table(name="RentalCompany")
 * @ORM\Entity
 */
class Rentalcompany
{
    /**
     * @var int
     * @ORM\Column(name="RentalCompanyID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $rentalcompanyid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=200, nullable=false)
     */
    protected $name;

    /**
     * Get rentalcompanyid.
     *
     * @return int
     */
    public function getRentalcompanyid()
    {
        return $this->rentalcompanyid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Rentalcompany
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
}
