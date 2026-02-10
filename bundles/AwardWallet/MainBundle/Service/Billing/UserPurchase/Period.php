<?php

namespace AwardWallet\MainBundle\Service\Billing\UserPurchase;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Cart;

/**
 * @NoDI()
 */
class Period
{
    private \DateTime $startDate;

    private \DateTime $endDate;

    private ?Cart $cart;

    private bool $active;

    public function __construct(\DateTime $startDate, \DateTime $endDate, ?Cart $cart = null, bool $active = true)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->cart = $cart;
        $this->active = $active;
    }

    public function __toString(): string
    {
        if ($this->isGracePeriod()) {
            $details = 'grace period';
        } else {
            $texts = [];

            if ($this->cart->isAwPlus()) {
                $texts[] = 'aw plus';
            }

            if (!is_null($this->cart->getAT201Item())) {
                $texts[] = 'at201';
            }

            if ($this->cart->isAwPlusSubscription()) {
                $texts[] = 'subscription';
            }

            if ($this->cart->hasMobileAwPlusSubscription()) {
                $texts[] = 'mobile';
            }

            $texts[] = sprintf('cartId: %d', $this->cart->getCartid());
            $texts[] = sprintf('pay: %s', $this->cart->getPaydate()->format('Y-m-d'));
            $texts[] = sprintf('active: %s', $this->isActive() ? 'yes' : 'no');

            if ($this->cart->getTotalPrice() > 0) {
                $texts[] = sprintf('price: $%s', $this->cart->getTotalPrice());
            } else {
                $texts[] = 'price: free';
            }

            $details = implode(', ', $texts);
        }

        return sprintf(
            '%s - %s: %s',
            $this->startDate->format('Y-m-d'),
            $this->endDate->format('Y-m-d'),
            $details
        );
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isGracePeriod(): bool
    {
        return is_null($this->cart);
    }

    public function setEndDate(\DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
}
