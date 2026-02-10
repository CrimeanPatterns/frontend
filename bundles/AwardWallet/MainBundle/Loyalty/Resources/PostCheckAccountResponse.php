<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'PostCheckAccountResponse'.
 */
class PostCheckAccountResponse
{
    /**
     * @var string
     * @Type("string")
     */
    private $requestId;
    /**
     * @var string
     * @Type("string")
     */
    private $browserExtensionSessionId;
    /**
     * @var string
     * @Type("string")
     */
    private $browserExtensionConnectionToken;

    /**
     * @param string
     * @return $this
     */
    public function setRequestid($requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * @return string
     */
    public function getRequestid()
    {
        return $this->requestId;
    }

    public function getBrowserExtensionSessionId(): ?string
    {
        return $this->browserExtensionSessionId;
    }

    public function getBrowserExtensionConnectionToken(): ?string
    {
        return $this->browserExtensionConnectionToken;
    }
}
