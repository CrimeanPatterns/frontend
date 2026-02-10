<?php

namespace AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class ExchangeCodeRequest
{
    /**
     * @var string
     */
    private $code;
    /**
     * @var string
     */
    private $redirectUrl;

    public function __construct(string $code, string $redirectUrl)
    {
        $this->code = $code;
        $this->redirectUrl = $redirectUrl;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }
}
