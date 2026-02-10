<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Airtariff.
 *
 * @ORM\Table(name="AirTariff")
 * @ORM\Entity
 */
class Airtariff
{
    /**
     * @var int
     * @ORM\Column(name="AirTariffID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $airtariffid;

    /**
     * @var int
     * @ORM\Column(name="SrcRegionID", type="integer", nullable=false)
     */
    protected $srcregionid;

    /**
     * @var int
     * @ORM\Column(name="DstRegionID", type="integer", nullable=false)
     */
    protected $dstregionid;

    /**
     * @var int
     * @ORM\Column(name="DateRangeID", type="integer", nullable=true)
     */
    protected $daterangeid;

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
     * @var int
     * @ORM\Column(name="RoundTrip", type="integer", nullable=false)
     */
    protected $roundtrip = 0;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get airtariffid.
     *
     * @return int
     */
    public function getAirtariffid()
    {
        return $this->airtariffid;
    }

    /**
     * Set srcregionid.
     *
     * @param int $srcregionid
     * @return Airtariff
     */
    public function setSrcregionid($srcregionid)
    {
        $this->srcregionid = $srcregionid;

        return $this;
    }

    /**
     * Get srcregionid.
     *
     * @return int
     */
    public function getSrcregionid()
    {
        return $this->srcregionid;
    }

    /**
     * Set dstregionid.
     *
     * @param int $dstregionid
     * @return Airtariff
     */
    public function setDstregionid($dstregionid)
    {
        $this->dstregionid = $dstregionid;

        return $this;
    }

    /**
     * Get dstregionid.
     *
     * @return int
     */
    public function getDstregionid()
    {
        return $this->dstregionid;
    }

    /**
     * Set daterangeid.
     *
     * @param int $daterangeid
     * @return Airtariff
     */
    public function setDaterangeid($daterangeid)
    {
        $this->daterangeid = $daterangeid;

        return $this;
    }

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
     * Set priceeconomy.
     *
     * @param int $priceeconomy
     * @return Airtariff
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
     * @return Airtariff
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
     * @return Airtariff
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
     * Set roundtrip.
     *
     * @param int $roundtrip
     * @return Airtariff
     */
    public function setRoundtrip($roundtrip)
    {
        $this->roundtrip = $roundtrip;

        return $this;
    }

    /**
     * Get roundtrip.
     *
     * @return int
     */
    public function getRoundtrip()
    {
        return $this->roundtrip;
    }

    /**
     * Set providerid.
     *
     * @return Airtariff
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
