<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability;

use JMS\Serializer\Annotation\Type;

class Payments
{
    /**
     * @Type("string")
     * @var string
     */
    private $currency;
    /**
     * @Type("string")
     * @var string
     */
    private $originalCurrency;
    /**
     * @Type("double")
     * @var float
     */
    private $conversionRate;
    /**
     * @Type("double")
     * @var float
     */
    private $taxes;
    /**
     * @Type("double")
     * @var float
     */
    private $fees;

    public function __construct(?string $currency, ?string $originalCurrency, ?float $conversionRate, ?float $taxes, ?float $fees = null)
    {
        $this->currency = $currency;
        $this->originalCurrency = $originalCurrency;
        $this->conversionRate = $conversionRate;
        $this->taxes = $taxes;
        $this->fees = $fees;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getTaxes(): ?float
    {
        return $this->taxes;
    }

    public function getFees(): ?float
    {
        return $this->fees;
    }
}
