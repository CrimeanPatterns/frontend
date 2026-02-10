<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\CartItem\Discount;

class CartItem extends AbstractDbEntity
{
    public function __construct(string $name, int $type, float $price, array $fields = [])
    {
        parent::__construct(array_merge($fields, [
            'TypeID' => $type,
            'Name' => $name,
            'Price' => $price,
        ]));
    }

    public static function at201Subscription1Year(array $fields = []): self
    {
        return new self(
            'Award Travel 201 Subscription for 1 year',
            AT201Subscription1Year::TYPE,
            AT201Subscription1Year::PRICE,
            $fields
        );
    }

    public static function at201Subscription1Month(array $fields = []): self
    {
        return new self(
            'Award Travel 201 Subscription for 1 month',
            AT201Subscription1Month::TYPE,
            AT201Subscription1Month::PRICE,
            $fields
        );
    }

    public static function at201Subscription6Months(array $fields = []): self
    {
        return new self(
            'Award Travel 201 Subscription for 6 months',
            AT201Subscription6Months::TYPE,
            AT201Subscription6Months::PRICE,
            $fields
        );
    }

    public static function awPlusSubscription1Year(array $fields = []): self
    {
        return new self(
            'AwardWallet Plus Subscription for 1 year',
            AwPlusSubscription::TYPE,
            AwPlusSubscription::PRICE,
            $fields
        );
    }

    public static function awPlusSubscription6Months(array $fields = []): self
    {
        return new self(
            'AwardWallet Plus Subscription for 6 months',
            AwPlusSubscription6Months::TYPE,
            AwPlusSubscription6Months::PRICE,
            $fields
        );
    }

    public static function awPlusTrial(array $fields = []): self
    {
        return new self(
            'AwardWallet Plus Trial',
            AwPlusTrial::TYPE,
            AwPlusTrial::PRICE,
            $fields
        );
    }

    public static function awPlus1Year(array $fields = []): self
    {
        return new self(
            'AwardWallet Plus for 1 year',
            AwPlus1Year::TYPE,
            AwPlus1Year::PRICE,
            $fields
        );
    }

    public static function awPlus6Months(array $fields = []): self
    {
        return new self(
            'AwardWallet Plus for 6 months',
            AwPlus::TYPE,
            AwPlus::PRICE,
            $fields
        );
    }

    public static function discount(int $price, array $fields = []): self
    {
        return new self(
            'Discount',
            Discount::TYPE,
            $price,
            $fields
        );
    }
}
