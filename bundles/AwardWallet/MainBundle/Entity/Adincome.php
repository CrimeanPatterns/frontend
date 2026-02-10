<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Adincome.
 *
 * @ORM\Table(name="AdIncome")
 * @ORM\Entity
 */
class Adincome
{
    /**
     * @var int
     * @ORM\Column(name="AdIncomeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $adincomeid;

    /**
     * @var \DateTime
     * @ORM\Column(name="PayDate", type="date", nullable=false)
     */
    protected $paydate;

    /**
     * @var float
     * @ORM\Column(name="Income", type="decimal", nullable=false)
     */
    protected $income;

    /**
     * Get adincomeid.
     *
     * @return int
     */
    public function getAdincomeid()
    {
        return $this->adincomeid;
    }

    /**
     * Set paydate.
     *
     * @param \DateTime $paydate
     * @return Adincome
     */
    public function setPaydate($paydate)
    {
        $this->paydate = $paydate;

        return $this;
    }

    /**
     * Get paydate.
     *
     * @return \DateTime
     */
    public function getPaydate()
    {
        return $this->paydate;
    }

    /**
     * Set income.
     *
     * @param float $income
     * @return Adincome
     */
    public function setIncome($income)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get income.
     *
     * @return float
     */
    public function getIncome()
    {
        return $this->income;
    }
}
