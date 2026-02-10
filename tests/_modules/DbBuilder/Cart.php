<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Entity\Cart as CartEntity;

class Cart extends AbstractDbEntity
{
    /**
     * @var CartItem[]
     */
    private array $cartItems;

    /**
     * @param CartItem[] $cartItems
     */
    public function __construct(array $cartItems = [], array $fields = [])
    {
        parent::__construct(array_merge([
            'PayDate' => date('Y-m-d H:i:s'),
        ], $fields));

        $this->cartItems = $cartItems;
    }

    public function addCartItem(CartItem $item): self
    {
        $this->cartItems[] = $item;

        return $this;
    }

    public function setCartItems(array $cartItems): self
    {
        $this->cartItems = $cartItems;

        return $this;
    }

    public function getCartItems(): array
    {
        return $this->cartItems;
    }

    public static function paid(\DateTime $payDate, array $fields = []): self
    {
        return new self([], array_merge($fields, [
            'PayDate' => $payDate->format('Y-m-d H:i:s'),
        ]));
    }

    public static function paidAppStore(\DateTime $payDate, array $fields = []): self
    {
        return new self([], array_merge($fields, [
            'PayDate' => $payDate->format('Y-m-d H:i:s'),
            'PaymentType' => CartEntity::PAYMENTTYPE_APPSTORE,
        ]));
    }

    public static function paidAndroid(\DateTime $payDate, array $fields = []): self
    {
        return new self([], array_merge($fields, [
            'PayDate' => $payDate->format('Y-m-d H:i:s'),
            'PaymentType' => CartEntity::PAYMENTTYPE_ANDROIDMARKET,
        ]));
    }

    public static function paidStripe(\DateTime $payDate, array $fields = []): self
    {
        return new self([], array_merge($fields, [
            'PayDate' => $payDate->format('Y-m-d H:i:s'),
            'PaymentType' => CartEntity::PAYMENTTYPE_STRIPE_INTENT,
        ]));
    }
}
