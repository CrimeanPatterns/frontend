<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Daterange.
 *
 * @ORM\Table(name="DateRange")
 * @ORM\Entity
 */
class Daterange
{
    /**
     * @var int
     * @ORM\Column(name="DateRangeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $daterangeid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDate", type="date", nullable=false)
     */
    protected $startdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDate", type="date", nullable=false)
     */
    protected $enddate;

    /**
     * Get daterangeid.
     *
     * @return int
     */
    public function getDaterangeid()
    {
        return $this->daterangeid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Daterange
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
     * Set startdate.
     *
     * @param \DateTime $startdate
     * @return Daterange
     */
    public function setStartdate($startdate)
    {
        $this->startdate = $startdate;

        return $this;
    }

    /**
     * Get startdate.
     *
     * @return \DateTime
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * Set enddate.
     *
     * @param \DateTime $enddate
     * @return Daterange
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * Get enddate.
     *
     * @return \DateTime
     */
    public function getEnddate()
    {
        return $this->enddate;
    }
}
