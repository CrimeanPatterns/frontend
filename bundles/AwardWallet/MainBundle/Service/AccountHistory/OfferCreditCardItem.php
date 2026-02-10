<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\CreditCard;

/**
 * @NoDI()
 */
class OfferCreditCardItem
{
    /** @var int */
    public $cardId;
    /** @var string */
    public $name;
    /** @var float */
    public $multiplier;
    /** @var float */
    public $value;
    /** @var float */
    public $mileValue;
    /** @var bool */
    public $earningAllTransactions;
    /** @var string|null */
    public $description;
    /** @var string|null */
    public $link;
    /** @var string|null */
    public $shoppingCategory;
    /** @var string|null */
    public $picturePath;
    /** @var bool|null */
    public $isUserHas;
    public CreditCard $creditCard;
    public ?bool $isConfirmed = null;
    public ?bool $isUnconfirmed = null;

    public ?float $minMileValue;
    public ?float $maxMileValue;
    public ?float $minValue;
    public ?float $maxValue;
    public ?string $multiplierPlainText;
    public ?string $earnsOnPlainText;
    public ?string $pointName;
    public ?array $cashEquivalent;
    public ?int $providerId;
    public ?int $providerKind;
    public ?string $providerName;
    public ?bool $isSortCheck = false;
    public bool $isNonAffiliateDisclosure = false;

    public function __construct(
        int $cardId,
        string $name,
        float $multiplier,
        float $mileValue,
        bool $earningAllTransactions,
        ?string $description,
        ?string $link,
        ?string $picturePath,
        ?bool $isUserHas,
        CreditCard $creditCard,
        ?float $minMileValue,
        ?float $maxMileValue
    ) {
        $this->cardId = $cardId;
        $this->name = $name;
        $this->multiplier = $multiplier;
        $this->mileValue = \round($mileValue, 2);
        $this->value = \round($mileValue * $multiplier, 2);
        $this->earningAllTransactions = $earningAllTransactions;
        $this->description = $description;
        $this->link = $link;
        $this->picturePath = $picturePath;
        $this->isUserHas = $isUserHas;
        $this->creditCard = $creditCard;
        $this->isNonAffiliateDisclosure = $creditCard->isNonAffiliateDisclosure();

        if (!$creditCard->isCashBackOnly() && is_numeric($minMileValue)) {
            $this->minMileValue = round($minMileValue, 2);
            $this->maxMileValue = round($maxMileValue, 2);
            $this->minValue = round($minMileValue * $multiplier, 2);
            $this->maxValue = round($maxMileValue * $multiplier, 2);
        }
        $this->pointName = $creditCard->getPointName();

        $provider = $creditCard->getCobrandProvider() ?? $creditCard->getProvider();
        $this->providerId = $provider->getId();
        $this->providerName = $provider->getDisplayname();
        $this->providerKind = $provider->getKind();
    }
}
