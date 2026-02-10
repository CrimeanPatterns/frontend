<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class EmailApiLoyaltyProgram
{
    /**
     * @var string
     * @Type("string")
     */
    private $balance;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $providerCode;

    /**
     * @var array
     * @Assert\Collection()
     * @Type("array")
     */
    private $properties;

    /**
     * @var array
     * @Assert\NotNull()
     * @Type("array")
     */
    private $activity;

    /**
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @return string
     */
    public function getProviderCode()
    {
        return $this->providerCode;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getActivity()
    {
        return $this->activity;
    }
}
