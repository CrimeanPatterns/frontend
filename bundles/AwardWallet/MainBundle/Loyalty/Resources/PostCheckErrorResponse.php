<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class PostCheckErrorResponse
{
    /**
     * @var string
     * @Type("string")
     */
    private $message;

    /**
     * @var string
     * @Type("string")
     */
    private $userData;

    public function __construct(string $message, ?string $userData)
    {
        $this->message = $message;
        $this->userData = $userData;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getUserData(): ?string
    {
        return $this->userData;
    }

    public function setUserData(?string $userData): self
    {
        $this->userData = $userData;

        return $this;
    }
}
