<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="CustomLoyaltyProperty")
 * @ORM\Entity()
 */
class CustomLoyaltyProperty
{
    public const NAMES = [
        'BarCodeData',
        'BarCodeType',
    ];

    public const MAX_VALUE_LENGTH = 4096;
    public const MAX_KEY_LENGTH = 128;

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="CustomLoyaltyPropertyID", type="integer", nullable=false)
     */
    protected $customLoyaltyPropertyId;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID", nullable=true)
     * })
     */
    protected $accountid;

    /**
     * @var Subaccount
     * @ORM\ManyToOne(targetEntity="Subaccount", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SubAccountID", referencedColumnName="SubAccountID", nullable=true)
     * })
     */
    protected $subaccountid;

    /**
     * @var Providercoupon
     * @ORM\ManyToOne(targetEntity="Providercoupon", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderCouponID", referencedColumnName="ProviderCouponID", nullable=true)
     * })
     */
    protected $providercouponid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", nullable=false, length=128)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Value", type="string", nullable=false, length=4096)
     */
    protected $value;

    /**
     * CustomLoyaltyProperty constructor.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getCustomLoyaltyPropertyId()
    {
        return $this->customLoyaltyPropertyId;
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
     * @return CustomLoyaltyProperty
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return CustomLoyaltyProperty
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->accountid;
    }

    /**
     * @return Subaccount
     */
    public function getSubAccount()
    {
        return $this->subaccountid;
    }

    /**
     * @return Providercoupon
     */
    public function getProviderCoupon()
    {
        return $this->providercouponid;
    }

    /**
     * @return CustomLoyaltyProperty
     */
    public function setAccount(Account $account)
    {
        return $this->doSetContainer([&$this->providercouponid, &$this->subaccountid], $this->accountid, $account);
    }

    public function setSubAccount(Subaccount $subaccount)
    {
        return $this->doSetContainer([&$this->accountid, &$this->providercouponid], $this->subaccountid, $subaccount);
    }

    /**
     * @return $this
     */
    public function setProviderCoupon(Providercoupon $providerCoupon)
    {
        return $this->doSetContainer([&$this->accountid, &$this->subaccountid], $this->providercouponid, $providerCoupon);
    }

    /**
     * @return $this
     */
    public function setContainer(CustomLoyaltyPropertyContainerInterface $container)
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
        $container = $this->subaccountid ?: ($this->accountid ?: $this->providercouponid);

        if (!$container) {
            throw new \OutOfBoundsException('Container is uninitialized');
        }

        return $container;
    }

    /**
     * @param CustomLoyaltyPropertyContainerInterface[] $clearQueue
     * @param CustomLoyaltyPropertyContainerInterface $containerRef
     * @return $this
     */
    protected function doSetContainer(array $clearQueue, &$containerRef, CustomLoyaltyPropertyContainerInterface $newContainer)
    {
        foreach ($clearQueue as &$oldContainer) {
            if ($oldContainer) {
                $oldContainer->removeCustomLoyaltyProperty($this);
                $oldContainer = null;
            }
        }
        unset($oldContainer);

        $containerRef = $newContainer;
        $newContainer->addCustomLoyaltyProperty($this);

        return $this;
    }
}
