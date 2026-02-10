<?php

namespace AwardWallet\MainBundle\Service\CardSelector\DTO;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @NoDI()
 */
class ReceiveTotalRequest
{
    /**
     * @Assert\NotBlank()
     * @Assert\Regex("#^\d+\.\d+\.\d+$#ims")
     * @Serializer\Type("string")
     */
    public string $extensionVersion;

    /**
     * @var FoundTotalUrl[]
     * @Assert\NotBlank()
     * @Assert\Count(min=1)
     * @Assert\Valid()
     * @Serializer\Type("array<AwardWallet\MainBundle\Service\CardSelector\DTO\FoundTotalUrl>")
     */
    public array $urls;
}
