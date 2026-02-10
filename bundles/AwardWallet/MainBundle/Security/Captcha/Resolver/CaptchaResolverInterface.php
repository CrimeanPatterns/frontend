<?php

namespace AwardWallet\MainBundle\Security\Captcha\Resolver;

use AwardWallet\MainBundle\Security\Captcha\Provider\CaptchaProviderInterface;
use Symfony\Component\HttpFoundation\Request;

interface CaptchaResolverInterface
{
    public function resolve(Request $request): CaptchaProviderInterface;
}
