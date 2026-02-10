<?php

namespace AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class AppleExchangeCodeRequest extends ExchangeCodeRequest
{
    /**
     * @var UserName
     */
    private $userName;

    public function __construct(string $code, string $redirectUrl, ?UserName $userName = null)
    {
        parent::__construct($code, $redirectUrl);
        $this->userName = $userName;
    }

    public function getUserName(): ?UserName
    {
        return $this->userName;
    }
}
