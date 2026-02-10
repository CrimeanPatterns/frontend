<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class EmailApiResponse
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $status;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $userData;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $providerCode;

    /**
     * @var EmailApiLoyaltyProgram
     * @Assert\Valid()
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\EmailApiLoyaltyProgram")
     */
    private $loyaltyProgram;

    /**
     * @return string
     */
    public function getUserData()
    {
        return $this->userData;
    }

    /**
     * @return string
     */
    public function getProviderCode()
    {
        return $this->providerCode;
    }

    /**
     * @return EmailApiLoyaltyProgram
     */
    public function getLoyaltyProgram()
    {
        return $this->loyaltyProgram;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
