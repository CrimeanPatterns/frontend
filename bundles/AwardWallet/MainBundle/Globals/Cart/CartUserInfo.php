<?php

namespace AwardWallet\MainBundle\Globals\Cart;

class CartUserInfo
{
    /**
     * @var int
     */
    private $cartOwnerId;
    /**
     * @var int
     */
    private $payerId;
    /**
     * @var bool
     */
    private $anonymousOnly;

    public function __construct(int $cartOwnerId, int $payerId, bool $anonymousOnly)
    {
        $this->cartOwnerId = $cartOwnerId;
        $this->payerId = $payerId;
        $this->anonymousOnly = $anonymousOnly;
    }

    public function getCartOwnerId(): int
    {
        return $this->cartOwnerId;
    }

    public function getPayerId(): int
    {
        return $this->payerId;
    }

    public function isAnonymousOnly(): bool
    {
        return $this->anonymousOnly;
    }
}
