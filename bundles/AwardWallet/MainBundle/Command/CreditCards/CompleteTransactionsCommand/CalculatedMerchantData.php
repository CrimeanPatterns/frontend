<?php

namespace AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\PostponedMerchantUpdate;

/**
 * @NoDI()
 */
class CalculatedMerchantData
{
    public ?int $categoryId;
    /**
     * @var int|PostponedMerchantUpdate
     */
    public $merchantId;
    public ?float $multiplier;

    public function __construct(
        ?int $categoryId,
        $merchantId,
        ?float $multiplier
    ) {
        $this->categoryId = $categoryId;
        $this->merchantId = $merchantId;
        $this->multiplier = $multiplier;
    }
}
