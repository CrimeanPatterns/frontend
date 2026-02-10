<?php

namespace AwardWallet\MainBundle\Command\CreditCards\MatchMerchantsAgainstMerchantPatternsCommand;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class MerchantRow
{
    /**
     * @var int
     */
    public $MerchantID;
    /**
     * @var string
     */
    public $Name;
    /**
     * @var string
     */
    public $DisplayName;
    /**
     * @var int
     */
    public $MerchantPatternID;
    /**
     * @var string
     */
    public $MerchantPatternName;
    /**
     * @var string[]
     */
    public $Descriptions;

    public function init()
    {
        $this->MerchantID = (int) $this->MerchantID;

        if (null !== $this->MerchantPatternID) {
            $this->MerchantPatternID = (int) $this->MerchantPatternID;
        }

        if (!\is_null($this->Descriptions)) {
            $this->Descriptions = \json_decode($this->Descriptions, true);
        }
    }
}
