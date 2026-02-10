<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class PostponedMerchantUpdate
{
    public string $name;
    public string $displayName;
    public ?int $shoppingCategoryGroupID;
    public string $cacheKey;
    public ?int $merchantPatternId;

    public function __construct(
        string $cacheKey,
        string $name,
        string $displayName,
        ?int $shoppingCategoryGroupID,
        ?int $merchantPatternId
    ) {
        $this->name = $name;
        $this->displayName = $displayName;
        $this->shoppingCategoryGroupID = $shoppingCategoryGroupID;
        $this->cacheKey = $cacheKey;
        $this->merchantPatternId = $merchantPatternId;
    }
}
