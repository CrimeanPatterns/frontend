<?php
/**
 * Created by PhpStorm.
 * User: ANelyudov
 * Date: 26.03.18
 * Time: 12:28.
 */

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class PricingInfo
{
    /**
     * Cost before taxes.
     *
     * @var float|null
     * @ORM\Column(type="decimal", precision=12, scale=2)
     */
    private $cost;

    /**
     * Currency code. I.e. USD.
     *
     * @var string|null
     * @ORM\Column(type="string", length=3)
     */
    private $currencyCode;

    /**
     * Total amount of discounts, if any.
     *
     * @var float|null
     * @ORM\Column(type="decimal", precision=12, scale=2)
     */
    private $discount;

    /**
     * @var Fee[]|null
     * @ORM\Column(type="array", length=255)
     */
    private $fees;

    /**
     * Total cost of the reservation including all taxes and fees.
     *
     * @var float|null
     * @ORM\Column(type="decimal", precision=12, scale=2)
     */
    private $total;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50)
     */
    private $spentAwards;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50)
     */
    private $earnedAwards;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50)
     */
    private $travelAgencyEarnedAwards;

    /**
     * PricingInfo constructor.
     *
     * @param Fee[]|null $fees
     */
    public function __construct(
        ?float $cost,
        ?string $currencyCode,
        ?float $discount,
        ?array $fees,
        ?float $total,
        ?string $spentAwards,
        ?string $earnedAwards,
        ?string $travelAgencyEarnedAwards
    ) {
        $this->cost = $cost;
        $this->currencyCode = $currencyCode;
        $this->discount = $discount;
        $this->fees = $fees;
        $this->total = $total;
        $this->spentAwards = $spentAwards;
        $this->earnedAwards = $earnedAwards;
        $this->travelAgencyEarnedAwards = $travelAgencyEarnedAwards;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function withCost(?float $cost): PricingInfo
    {
        return new PricingInfo(
            $cost,
            $this->currencyCode,
            $this->discount,
            $this->fees,
            $this->total,
            $this->spentAwards,
            $this->earnedAwards,
            $this->travelAgencyEarnedAwards
        );
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function withCurrencyCode(?string $currencyCode): PricingInfo
    {
        return new PricingInfo(
            $this->cost,
            $currencyCode,
            $this->discount,
            $this->fees,
            $this->total,
            $this->spentAwards,
            $this->earnedAwards,
            $this->travelAgencyEarnedAwards
        );
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function withDiscount(?float $discount): PricingInfo
    {
        return new PricingInfo(
            $this->cost,
            $this->currencyCode,
            $discount,
            $this->fees,
            $this->total,
            $this->spentAwards,
            $this->earnedAwards,
            $this->travelAgencyEarnedAwards
        );
    }

    /**
     * @return Fee[]|null
     */
    public function getFees(): ?array
    {
        return $this->fees;
    }

    public function withFees(?array $fees): PricingInfo
    {
        return new PricingInfo(
            $this->cost,
            $this->currencyCode,
            $this->discount,
            $fees,
            $this->total,
            $this->spentAwards,
            $this->earnedAwards,
            $this->travelAgencyEarnedAwards
        );
    }

    public function getFeesTotal(): ?float
    {
        if (null === $this->fees) {
            return null;
        }

        return array_sum(array_map(function (Fee $fee) {
            return $fee->getCharge();
        }, $this->getFees()));
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    /**
     * @return PricingInfo
     */
    public function withTotal(?float $total)
    {
        return new PricingInfo(
            $this->cost,
            $this->currencyCode,
            $this->discount,
            $this->fees,
            $total,
            $this->spentAwards,
            $this->earnedAwards,
            $this->travelAgencyEarnedAwards
        );
    }

    public function getSpentAwards(): ?string
    {
        return $this->spentAwards;
    }

    public function withSpentAwards(?string $spentAwards): PricingInfo
    {
        return new PricingInfo(
            $this->cost,
            $this->currencyCode,
            $this->discount,
            $this->fees,
            $this->total,
            $spentAwards,
            $this->earnedAwards,
            $this->travelAgencyEarnedAwards
        );
    }

    public function getEarnedAwards(): ?string
    {
        return $this->earnedAwards;
    }

    public function withEarnedAwards(?string $earnedAwards): PricingInfo
    {
        return new PricingInfo(
            $this->cost,
            $this->currencyCode,
            $this->discount,
            $this->fees,
            $this->total,
            $this->spentAwards,
            $earnedAwards,
            $this->travelAgencyEarnedAwards
        );
    }

    public function getTravelAgencyEarnedAwards(): ?string
    {
        return $this->travelAgencyEarnedAwards;
    }

    public function withTravelAgencyEarnedAwards(?string $travelAgencyEarnedAwards): PricingInfo
    {
        return new PricingInfo(
            $this->cost,
            $this->currencyCode,
            $this->discount,
            $this->fees,
            $this->total,
            $this->spentAwards,
            $this->earnedAwards,
            $travelAgencyEarnedAwards
        );
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        $array = [];
        $addIfNotNull = function (string $key, ?string $value) use (&$array) {
            if (!empty($value)) {
                $array[$key] = $value;
            }
        };
        $addIfNotNull('cost', $this->getCost());
        $addIfNotNull('currencyCode', $this->getCurrencyCode());
        $addIfNotNull('discount', $this->getDiscount());

        if (null !== $this->fees) {
            $array['fees'] = implode(', ', array_map(function (Fee $fee) {
                return (string) $fee;
            }, $this->getFees()));
        }
        $addIfNotNull('total', $this->getTotal());
        $addIfNotNull('spentAwards', $this->getSpentAwards());
        $addIfNotNull('earnedRewards', $this->getEarnedAwards());
        $addIfNotNull('travelAgencyEarnedRewards', $this->getTravelAgencyEarnedAwards());

        return $array;
    }

    public function getTax(): ?float
    {
        if ($this->fees === null) {
            return null;
        }

        foreach ($this->fees as $fee) {
            if ($fee->getName() === 'Tax') {
                return $fee->getCharge();
            }
        }

        return null;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function setTotal(?float $total): self
    {
        $this->total = $total;

        return $this;
    }
}
