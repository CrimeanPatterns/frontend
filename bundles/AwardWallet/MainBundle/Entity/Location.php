<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Location.
 *
 * @ORM\Table(name="Location")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\LocationRepository")
 */
class Location
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="LocationID", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID", nullable=false)
     * })
     */
    protected $account;

    /**
     * @var Subaccount
     * @ORM\ManyToOne(targetEntity="Subaccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SubAccountID", referencedColumnName="SubAccountID", nullable=false)
     * })
     */
    protected $subaccount;

    /**
     * @var Providercoupon
     * @ORM\ManyToOne(targetEntity="Providercoupon")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderCouponID", referencedColumnName="ProviderCouponID", nullable=false)
     * })
     */
    protected $providercoupon;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", nullable=false)
     */
    protected $name;

    /**
     * @var float
     * @ORM\Column(name="Lat", type="decimal", length=10, scale=8, nullable=false)
     */
    protected $lat;

    /**
     * @var float
     * @ORM\Column(name="Lng", type="decimal", length=11, scale=8, nullable=false)
     */
    protected $lng;

    /**
     * @var int
     * @ORM\Column(name="Radius", type="integer", nullable=false)
     */
    protected $radius = 50;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationDate;

    /**
     * @var LocationSetting[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="LocationSetting",
     *     mappedBy="location",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $locationSettings;

    /**
     * @var bool
     * @ORM\Column(name="IsGenerated", type="boolean", nullable=false)
     */
    protected $isGenerated = false;

    public function __construct()
    {
        $this->creationDate = new \DateTime();
        $this->locationSettings = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return $this
     */
    public function setAccount(Account $account)
    {
        return $this->doSetContainer([&$this->providercoupon, &$this->subaccount], $this->account, $account);
    }

    /**
     * @return Subaccount
     */
    public function getSubAccount()
    {
        return $this->subaccount;
    }

    /**
     * @return $this
     */
    public function setSubAccount(Subaccount $subaccount)
    {
        return $this->doSetContainer([&$this->account, &$this->providercoupon], $this->subaccount, $subaccount);
    }

    /**
     * @return Providercoupon
     */
    public function getProvidercoupon()
    {
        return $this->providercoupon;
    }

    /**
     * @return $this
     */
    public function setProvidercoupon(Providercoupon $providercoupon)
    {
        return $this->doSetContainer([&$this->account, &$this->subaccount], $this->providercoupon, $providercoupon);
    }

    /**
     * @param LocationContainerInterface $container
     * @return $this
     */
    public function setContainer(CardImageContainerInterface $container)
    {
        if ($container instanceof Account) {
            $this->setAccount($container);
        } elseif ($container instanceof Providercoupon) {
            $this->setProviderCoupon($container);
        } elseif ($container instanceof Subaccount) {
            $this->setSubAccount($container);
        } else {
            throw new \InvalidArgumentException('Unknown container type');
        }

        return $this;
    }

    /**
     * @return LoyaltyProgramInterface
     */
    public function getContainer()
    {
        $container = $this->subaccount ?: ($this->account ?: $this->providercoupon);

        if (!$container) {
            throw new \OutOfBoundsException('Container is uninitialized');
        }

        return $container;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Location
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     * @return Location
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param float $lng
     * @return Location
     */
    public function setLng($lng)
    {
        $this->lng = $lng;

        return $this;
    }

    /**
     * @return int
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param int $radius
     * @return Location
     */
    public function setRadius($radius)
    {
        $this->radius = $radius;

        return $this;
    }

    /**
     * @return LocationSetting[]|Collection
     */
    public function getLocationSettings()
    {
        return $this->locationSettings;
    }

    /**
     * @param LocationSetting[]|Collection $locationSettings
     * @return Location
     */
    public function setLocationSettings($locationSettings)
    {
        $this->locationSettings = $locationSettings;

        return $this;
    }

    /**
     * @return $this
     */
    public function addLocationSettings(LocationSetting $locationSettings)
    {
        $this->locationSettings[] = $locationSettings;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeLocationSettings(LocationSetting $locationSettings)
    {
        $this->locationSettings->removeElement($locationSettings);

        return $this;
    }

    public function getProvider()
    {
        if ($this->subaccount) {
            return $this->subaccount->getAccountid()->getProviderid();
        } elseif ($this->account) {
            return $this->account->getProviderid();
        }

        return null;
    }

    public function getProviderName()
    {
        if ($this->subaccount) {
            return $this->subaccount->getAccountid()->getDisplayName();
        } elseif ($this->account) {
            return $this->account->getDisplayName();
        } elseif ($this->providercoupon) {
            return $this->providercoupon->getProgramname();
        }

        return null;
    }

    public function getLogin()
    {
        if ($this->subaccount) {
            return $this->subaccount->getAccountid()->getLogin();
        } elseif ($this->account) {
            return $this->account->getLogin();
        }

        return null;
    }

    public function getProviderKind()
    {
        if ($this->subaccount) {
            $account = $this->subaccount->getAccountid();

            return $account->getProviderid() ? $account->getProviderid()->getKind() : $account->getKind();
        } elseif ($this->account) {
            return $this->account->getProviderid() ? $this->account->getProviderid()->getKind() : $this->account->getKind();
        } elseif ($this->providercoupon) {
            return $this->providercoupon->getKind();
        }

        return null;
    }

    /**
     * @return Useragent[]|ArrayCollection|Collection
     */
    public function getUseragents()
    {
        $ua = new ArrayCollection();

        if ($this->subaccount) {
            $ua = $this->subaccount->getAccountid()->getUseragents();
        } elseif ($this->account) {
            $ua = $this->account->getUseragents();
        } elseif ($this->providercoupon) {
            $ua = $this->providercoupon->getUseragents();
        }
        $ua->filter(function (Useragent $ua) {
            return $ua->isApproved();
        });

        return $ua;
    }

    public function isGenerated(): bool
    {
        return $this->isGenerated;
    }

    public function setGenerated(bool $generated): self
    {
        $this->isGenerated = $generated;

        return $this;
    }

    /**
     * @param LocationContainerInterface[] $clearQueue
     * @param LocationContainerInterface $containerRef
     * @return $this
     */
    protected function doSetContainer(array $clearQueue, &$containerRef, LocationContainerInterface $newContainer)
    {
        $this->clearContainers($clearQueue);
        $containerRef = $newContainer;
        $newContainer->addLocation($this);

        return $this;
    }

    /**
     * @param LocationContainerInterface[] $clearQueue
     */
    protected function clearContainers(array $clearQueue)
    {
        foreach ($clearQueue as &$oldContainer) {
            if ($oldContainer) {
                $oldContainer->removeLocation($this);
                $oldContainer = null;
            }
        }

        unset($oldContainer);
    }
}
