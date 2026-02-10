<?php

namespace AwardWallet\MainBundle\Service\BestCreditCards;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation as Serializer;

/**
 * @NoDI()
 */
class RecommendedCard
{
    /**
     * @Serializer\Type("string")
     */
    public string $logo;
    /**
     * @Serializer\Type("string")
     */
    public string $ratio;
    /**
     * @Serializer\Type("string")
     */
    public string $cardName;
    /**
     * @Serializer\Type("string")
     */
    public string $description;
    /**
     * @Serializer\Type("string")
     */
    public string $cashEquivalent;

    public function __construct(string $logo, string $ratio, string $cardName, string $description, string $cashEquivalent)
    {
        $this->logo = $logo;
        $this->ratio = $ratio;
        $this->cardName = $cardName;
        $this->description = $description;
        $this->cashEquivalent = $cashEquivalent;
    }
}
