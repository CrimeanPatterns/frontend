<?php

namespace AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class AccountHistoryRow
{
    public $UUID;
    public $Description;
    public $Miles;
    public $Amount;
    public $PostingDate;
    public $Category;
    public $ShoppingCategoryID;
    public $MerchantID;
    public $Multiplier;
    public $ProviderID;
    public $UpdateDate;
    public ?CalculatedMerchantData $CalculatedMerchantData = null;

    public function isFresh(): bool
    {
        $new = $this->CalculatedMerchantData;

        if (\is_object($new->merchantId)) {
            throw new \LogicException();
        }

        return
           (int) $this->MerchantID === (int) $new->merchantId
            && (int) $this->ShoppingCategoryID === (int) $new->categoryId
            && (float) $this->Multiplier === $new->multiplier;
    }
}
