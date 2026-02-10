<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Hoteltariff.
 *
 * @ORM\Table(name="HotelTariff")
 * @ORM\Entity
 */
class Hoteltariff
{
    /**
     * @var int
     * @ORM\Column(name="HotelTariffID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $hoteltariffid;

    /**
     * @var int
     * @ORM\Column(name="WeekDayStart", type="integer", nullable=false)
     */
    protected $weekdaystart;

    /**
     * @var int
     * @ORM\Column(name="WeekDayEnd", type="integer", nullable=false)
     */
    protected $weekdayend;

    /**
     * @var int
     * @ORM\Column(name="Days", type="integer", nullable=false)
     */
    protected $days;

    /**
     * @var int
     * @ORM\Column(name="PriceOpp", type="integer", nullable=true)
     */
    protected $priceopp;

    /**
     * @var int
     * @ORM\Column(name="Price1", type="integer", nullable=true)
     */
    protected $price1;

    /**
     * @var int
     * @ORM\Column(name="Price2", type="integer", nullable=true)
     */
    protected $price2;

    /**
     * @var int
     * @ORM\Column(name="Price3", type="integer", nullable=true)
     */
    protected $price3;

    /**
     * @var int
     * @ORM\Column(name="Price4", type="integer", nullable=true)
     */
    protected $price4;

    /**
     * @var int
     * @ORM\Column(name="Price5", type="integer", nullable=true)
     */
    protected $price5;

    /**
     * @var int
     * @ORM\Column(name="Price6", type="integer", nullable=true)
     */
    protected $price6;

    /**
     * @var int
     * @ORM\Column(name="Price7", type="integer", nullable=true)
     */
    protected $price7;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get hoteltariffid.
     *
     * @return int
     */
    public function getHoteltariffid()
    {
        return $this->hoteltariffid;
    }

    /**
     * Set weekdaystart.
     *
     * @param int $weekdaystart
     * @return Hoteltariff
     */
    public function setWeekdaystart($weekdaystart)
    {
        $this->weekdaystart = $weekdaystart;

        return $this;
    }

    /**
     * Get weekdaystart.
     *
     * @return int
     */
    public function getWeekdaystart()
    {
        return $this->weekdaystart;
    }

    /**
     * Set weekdayend.
     *
     * @param int $weekdayend
     * @return Hoteltariff
     */
    public function setWeekdayend($weekdayend)
    {
        $this->weekdayend = $weekdayend;

        return $this;
    }

    /**
     * Get weekdayend.
     *
     * @return int
     */
    public function getWeekdayend()
    {
        return $this->weekdayend;
    }

    /**
     * Set days.
     *
     * @param int $days
     * @return Hoteltariff
     */
    public function setDays($days)
    {
        $this->days = $days;

        return $this;
    }

    /**
     * Get days.
     *
     * @return int
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Set priceopp.
     *
     * @param int $priceopp
     * @return Hoteltariff
     */
    public function setPriceopp($priceopp)
    {
        $this->priceopp = $priceopp;

        return $this;
    }

    /**
     * Get priceopp.
     *
     * @return int
     */
    public function getPriceopp()
    {
        return $this->priceopp;
    }

    /**
     * Set price1.
     *
     * @param int $price1
     * @return Hoteltariff
     */
    public function setPrice1($price1)
    {
        $this->price1 = $price1;

        return $this;
    }

    /**
     * Get price1.
     *
     * @return int
     */
    public function getPrice1()
    {
        return $this->price1;
    }

    /**
     * Set price2.
     *
     * @param int $price2
     * @return Hoteltariff
     */
    public function setPrice2($price2)
    {
        $this->price2 = $price2;

        return $this;
    }

    /**
     * Get price2.
     *
     * @return int
     */
    public function getPrice2()
    {
        return $this->price2;
    }

    /**
     * Set price3.
     *
     * @param int $price3
     * @return Hoteltariff
     */
    public function setPrice3($price3)
    {
        $this->price3 = $price3;

        return $this;
    }

    /**
     * Get price3.
     *
     * @return int
     */
    public function getPrice3()
    {
        return $this->price3;
    }

    /**
     * Set price4.
     *
     * @param int $price4
     * @return Hoteltariff
     */
    public function setPrice4($price4)
    {
        $this->price4 = $price4;

        return $this;
    }

    /**
     * Get price4.
     *
     * @return int
     */
    public function getPrice4()
    {
        return $this->price4;
    }

    /**
     * Set price5.
     *
     * @param int $price5
     * @return Hoteltariff
     */
    public function setPrice5($price5)
    {
        $this->price5 = $price5;

        return $this;
    }

    /**
     * Get price5.
     *
     * @return int
     */
    public function getPrice5()
    {
        return $this->price5;
    }

    /**
     * Set price6.
     *
     * @param int $price6
     * @return Hoteltariff
     */
    public function setPrice6($price6)
    {
        $this->price6 = $price6;

        return $this;
    }

    /**
     * Get price6.
     *
     * @return int
     */
    public function getPrice6()
    {
        return $this->price6;
    }

    /**
     * Set price7.
     *
     * @param int $price7
     * @return Hoteltariff
     */
    public function setPrice7($price7)
    {
        $this->price7 = $price7;

        return $this;
    }

    /**
     * Get price7.
     *
     * @return int
     */
    public function getPrice7()
    {
        return $this->price7;
    }

    /**
     * Set providerid.
     *
     * @return Hoteltariff
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
