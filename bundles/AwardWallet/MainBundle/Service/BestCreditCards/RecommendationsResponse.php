<?php

namespace AwardWallet\MainBundle\Service\BestCreditCards;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation as Serializer;

/**
 * @NoDI()
 */
class RecommendationsResponse
{
    public const ACTION_NONE = "none";
    public const ACTION_SHOW_POPUP = "showPopup";

    /**
     * @Serializer\Type("string")
     */
    public string $action;
    /**
     * @Serializer\Type("string")
     */
    public ?string $merchantName;
    /**
     * @Serializer\Type("string")
     */
    public ?string $merchantShoppingCategoryGroup;
    /**
     * @Serializer\Type("string")
     */
    public ?string $merchantCardsLink;
    /**
     * @Serializer\Type("RecommendedCard")
     */
    public ?RecommendedCard $yourHighestValueCard;
    /**
     * @Serializer\Type("RecommendedCard")
     */
    public ?RecommendedCard $overallHighestValueCard;

    public function __construct(
        string $action,
        ?string $merchantName = null,
        ?string $merchantShoppingCategoryGroup = null,
        ?string $merchantCardsLink = null,
        ?RecommendedCard $yourHighestValueCard = null,
        ?RecommendedCard $overallHighestValueCard = null
    ) {
        $this->action = $action;
        $this->merchantName = $merchantName;
        $this->merchantShoppingCategoryGroup = $merchantShoppingCategoryGroup;
        $this->merchantCardsLink = $merchantCardsLink;
        $this->yourHighestValueCard = $yourHighestValueCard;
        $this->overallHighestValueCard = $overallHighestValueCard;
    }
}
