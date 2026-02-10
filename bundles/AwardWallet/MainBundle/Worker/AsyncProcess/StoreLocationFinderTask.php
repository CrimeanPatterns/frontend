<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\LoyaltyProgramInterface;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;

class StoreLocationFinderTask extends Task
{
    /**
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $id;
    /**
     * @var bool
     */
    public $clearExistingPoints = false;

    public function __construct(?Usr $user, ?LoyaltyProgramInterface $loyaltyProgram = null)
    {
        parent::__construct("aw.async.executor.store_location_finder", StringUtils::getRandomCode(20));

        if ($loyaltyProgram instanceof Account) {
            $this->type = 'account';
            $this->id = $loyaltyProgram->getId();
        } elseif ($loyaltyProgram instanceof Providercoupon) {
            $this->type = 'coupon';
            $this->id = $loyaltyProgram->getId();
        } elseif ($user instanceof Usr) {
            $this->type = 'user';
            $this->id = $user->getUserid();
        } else {
            throw new \InvalidArgumentException('unknown loyalty type ' . get_class($loyaltyProgram));
        }
    }

    public function setClearExistingPoints(bool $clearPoints): self
    {
        $this->clearExistingPoints = $clearPoints;

        return $this;
    }

    public static function createFromUser(Usr $user)
    {
        if (!self::isValidZip($user)) {
            return null;
        }

        return new self($user);
    }

    public static function createFromLoyalty(LoyaltyProgramInterface $loyaltyProgram)
    {
        $user = $loyaltyProgram->getUserid();

        if (!self::isValidZip($user)) {
            return null;
        }

        if (
            (
                ($loyaltyProgram instanceof Account)
                && (
                    (
                        ($provider = $loyaltyProgram->getProviderid())
                        && (
                            ($provider->getState() === PROVIDER_RETAIL)
                            || ($provider->getKind() === PROVIDER_KIND_SHOPPING)
                            || in_array($provider->getCode(), ['kroger'])
                        )
                    )
                    || ($loyaltyProgram->getKind() === PROVIDER_KIND_SHOPPING)
                )
            )
            || (
                ($loyaltyProgram instanceof Providercoupon)
                && ($loyaltyProgram->getKind() === PROVIDER_KIND_SHOPPING)
            )
        ) {
            return new self(null, $loyaltyProgram);
        }

        return null;
    }

    protected static function isValidZip(Usr $user): bool
    {
        return
            $user->getZipCodeUpdateDate()
            && preg_match('/^[0-9]{5}([^0-9]*[0-9]{4})?$/', trim($user->getZip()));
    }
}
