<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\AwSecureTokenListener;

use AwardWallet\MainBundle\Configuration\AwSecureToken;
use Symfony\Component\HttpFoundation\Request;

class SecureTokenHandle
{
    /**
     * @var AwSecureToken
     */
    private $configuration;
    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request, AwSecureToken $configuration)
    {
        $this->request = $request;
        $this->configuration = $configuration;
    }

    public function getConfiguration(): AwSecureToken
    {
        return $this->configuration;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
