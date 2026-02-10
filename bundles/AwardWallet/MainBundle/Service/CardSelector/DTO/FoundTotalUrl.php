<?php

namespace AwardWallet\MainBundle\Service\CardSelector\DTO;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @NoDI()
 */
class FoundTotalUrl
{
    /**
     * @Assert\NotBlank()
     * @Serializer\Type("int")
     */
    public int $datetime;
    /**
     * @Assert\NotBlank()
     * @Assert\Positive()
     * @Assert\LessThan(1000000)
     * @Serializer\Type("double")
     */
    public float $total;
    /**
     * @Assert\NotBlank()
     * @Assert\Url()
     * @Assert\Length(max="1000")
     * @Serializer\Type("string")
     */
    public string $url;
}
