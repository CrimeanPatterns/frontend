<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Sitead.
 *
 * @ORM\Table(name="SiteAd")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\SiteadRepository")
 */
class Sitead
{
    public const REF_INVITE_OPTION = 4;

    public const BLOG_AWPLUS_6MONTHS_ID = [224, 225, 226, 227];

    /**
     * @var int
     * @ORM\Column(name="SiteAdID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $siteadid;

    /**
     * @var string
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDate", type="date", nullable=false)
     */
    protected $startdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastClick", type="datetime", nullable=true)
     */
    protected $lastclick;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastRegister", type="datetime", nullable=true)
     */
    protected $lastregister;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastPurchase", type="datetime", nullable=true)
     */
    protected $lastpurchase;

    /**
     * @var int
     * @ORM\Column(name="Clicks", type="integer", nullable=true)
     */
    protected $clicks;

    /**
     * @var int
     * @ORM\Column(name="Registers", type="integer", nullable=true)
     */
    protected $registers;

    /**
     * @var int
     * @ORM\Column(name="Purchases", type="integer", nullable=true)
     */
    protected $purchases;

    /**
     * @var float
     * @ORM\Column(name="TotalAmount", type="float", nullable=true)
     */
    protected $totalamount;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="BookerID", referencedColumnName="UserID", nullable=true)
     */
    protected $Booker;

    /**
     * @var Usr[]|Collection
     * @ORM\ManyToMany(targetEntity="Usr")
     * @ORM\JoinTable(
     *     name="SiteAdUser",
     *     joinColumns={@ORM\JoinColumn(name="SiteAdID", referencedColumnName="SiteAdID")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="UserID", referencedColumnName="UserID")}
     * )
     */
    private $users;

    /**
     * Get siteadid.
     *
     * @return int
     */
    public function getSiteadid()
    {
        return $this->siteadid;
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return Sitead
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set startdate.
     *
     * @param \DateTime $startdate
     * @return Sitead
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
     * Set lastclick.
     *
     * @param \DateTime $lastclick
     * @return Sitead
     */
    public function setLastclick($lastclick)
    {
        $this->lastclick = $lastclick;

        return $this;
    }

    /**
     * Get lastclick.
     *
     * @return \DateTime
     */
    public function getLastclick()
    {
        return $this->lastclick;
    }

    /**
     * Set lastregister.
     *
     * @param \DateTime $lastregister
     * @return Sitead
     */
    public function setLastregister($lastregister)
    {
        $this->lastregister = $lastregister;

        return $this;
    }

    /**
     * Get lastregister.
     *
     * @return \DateTime
     */
    public function getLastregister()
    {
        return $this->lastregister;
    }

    /**
     * Set lastpurchase.
     *
     * @param \DateTime $lastpurchase
     * @return Sitead
     */
    public function setLastpurchase($lastpurchase)
    {
        $this->lastpurchase = $lastpurchase;

        return $this;
    }

    /**
     * Get lastpurchase.
     *
     * @return \DateTime
     */
    public function getLastpurchase()
    {
        return $this->lastpurchase;
    }

    /**
     * Set clicks.
     *
     * @param int $clicks
     * @return Sitead
     */
    public function setClicks($clicks)
    {
        $this->clicks = $clicks;

        return $this;
    }

    /**
     * Get clicks.
     *
     * @return int
     */
    public function getClicks()
    {
        return $this->clicks;
    }

    /**
     * Set registers.
     *
     * @param int $registers
     * @return Sitead
     */
    public function setRegisters($registers)
    {
        $this->registers = $registers;

        return $this;
    }

    /**
     * Get registers.
     *
     * @return int
     */
    public function getRegisters()
    {
        return $this->registers;
    }

    /**
     * Set purchases.
     *
     * @param int $purchases
     * @return Sitead
     */
    public function setPurchases($purchases)
    {
        $this->purchases = $purchases;

        return $this;
    }

    /**
     * Get purchases.
     *
     * @return int
     */
    public function getPurchases()
    {
        return $this->purchases;
    }

    /**
     * Set totalamount.
     *
     * @param float $totalamount
     * @return Sitead
     */
    public function setTotalamount($totalamount)
    {
        $this->totalamount = $totalamount;

        return $this;
    }

    /**
     * Get totalamount.
     *
     * @return float
     */
    public function getTotalamount()
    {
        return $this->totalamount;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getBooker()
    {
        return $this->Booker;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\Usr $Booker
     */
    public function setBooker($Booker)
    {
        $this->Booker = $Booker;
    }

    public function getLogo($type = 'main')
    {
        if ($this->getBooker() && $this->getBooker()->isBooker()) {
            if ($this->users->count() > 0) {
                return $this->getBooker()->getBookerInfo()->getLogo($type, $this->getSiteadid());
            } else {
                return $this->getBooker()->getBookerInfo()->getLogo($type);
            }
        }

        return null;
    }

    public function getIcon($size = 'small')
    {
        if ($this->getBooker() && $this->getBooker()->isBooker()) {
            if ($this->users->count() > 0) {
                return $this->getBooker()->getBookerInfo()->getIcon($size, $this->getSiteadid());
            } else {
                return $this->getBooker()->getBookerInfo()->getIcon($size);
            }
        }

        return null;
    }

    /**
     * @return Collection|Usr[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }
}
