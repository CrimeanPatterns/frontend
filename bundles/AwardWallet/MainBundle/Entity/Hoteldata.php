<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Hoteldata.
 *
 * @ORM\Table(name="HotelData")
 * @ORM\Entity
 */
class Hoteldata
{
    /**
     * @var int
     * @ORM\Column(name="HotelID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $hotelid;

    /**
     * @var string
     * @ORM\Column(name="HotelName", type="string", length=255, nullable=true)
     */
    protected $hotelname;

    /**
     * @var string
     * @ORM\Column(name="ProviderCode", type="string", length=64, nullable=true)
     */
    protected $providercode;

    /**
     * @var int
     * @ORM\Column(name="Category", type="integer", nullable=true)
     */
    protected $category;

    /**
     * @var int
     * @ORM\Column(name="MinPoints", type="integer", nullable=true)
     */
    protected $minpoints;

    /**
     * @var int
     * @ORM\Column(name="MaxPoints", type="integer", nullable=true)
     */
    protected $maxpoints;

    /**
     * @var string
     * @ORM\Column(name="URL", type="string", length=250, nullable=true)
     */
    protected $url;

    /**
     * Get hotelid.
     *
     * @return int
     */
    public function getHotelid()
    {
        return $this->hotelid;
    }

    /**
     * Set hotelname.
     *
     * @param string $hotelname
     * @return Hoteldata
     */
    public function setHotelname($hotelname)
    {
        $this->hotelname = $hotelname;

        return $this;
    }

    /**
     * Get hotelname.
     *
     * @return string
     */
    public function getHotelname()
    {
        return $this->hotelname;
    }

    /**
     * Set providercode.
     *
     * @param string $providercode
     * @return Hoteldata
     */
    public function setProvidercode($providercode)
    {
        $this->providercode = $providercode;

        return $this;
    }

    /**
     * Get providercode.
     *
     * @return string
     */
    public function getProvidercode()
    {
        return $this->providercode;
    }

    /**
     * Set category.
     *
     * @param int $category
     * @return Hoteldata
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set minpoints.
     *
     * @param int $minpoints
     * @return Hoteldata
     */
    public function setMinpoints($minpoints)
    {
        $this->minpoints = $minpoints;

        return $this;
    }

    /**
     * Get minpoints.
     *
     * @return int
     */
    public function getMinpoints()
    {
        return $this->minpoints;
    }

    /**
     * Set maxpoints.
     *
     * @param int $maxpoints
     * @return Hoteldata
     */
    public function setMaxpoints($maxpoints)
    {
        $this->maxpoints = $maxpoints;

        return $this;
    }

    /**
     * Get maxpoints.
     *
     * @return int
     */
    public function getMaxpoints()
    {
        return $this->maxpoints;
    }

    /**
     * Set url.
     *
     * @param string $url
     * @return Hoteldata
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
