<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\AwSecureTokenListener;

use Symfony\Component\HttpFoundation\Response;

interface TokenCheckerInterface
{
    /**
     * @return Response|null
     */
    public function check(SecureTokenHandle $secureTokenHandle);
}
