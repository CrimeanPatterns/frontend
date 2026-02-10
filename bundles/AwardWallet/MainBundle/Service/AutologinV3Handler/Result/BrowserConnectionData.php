<?php

namespace AwardWallet\MainBundle\Service\AutologinV3Handler\Result;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class BrowserConnectionData implements GetConnectionResultInterface
{
    private string $sessionId;
    private string $token;

    public function __construct(
        string $sessionId,
        string $token
    ) {
        $this->sessionId = $sessionId;
        $this->token = $token;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
