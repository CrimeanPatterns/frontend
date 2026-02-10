<?php

namespace AwardWallet\MainBundle\Service\MileValue\Form\Model;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class CustomSetModel
{
    /**
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @var string
     */
    public $id;

    /**
     * @JMS\Type("integer")
     * @Assert\NotBlank
     * @Assert\Choice({0, 1, 2})
     * @var int
     */
    public $customPick;

    /**
     * @JMS\Type("float")
     * @Assert\Expression(
     *     "this.customPick !== 2 || this.customPick === 2 && (50000 >= this.customValue && 0.01 <= this.customValue)",
     *     message="The value must be in the range from 0.01 to 50000"
     * )
     * @var float
     */
    public $customValue;
}
