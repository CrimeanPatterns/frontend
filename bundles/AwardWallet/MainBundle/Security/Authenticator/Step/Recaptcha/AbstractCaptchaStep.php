<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\AbstractStep;
use AwardWallet\MainBundle\Security\Captcha\Resolver\CaptchaResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class AbstractCaptchaStep extends AbstractStep
{
    private CaptchaStepHelper $captchaHelper;
    private LoggerInterface $logger;
    private CaptchaResolverInterface $captchaResolver;

    public function __construct(
        CaptchaResolverInterface $captchaResolver,
        CaptchaStepHelper $captchaHelper,
        LoggerInterface $securityLogger
    ) {
        $this->captchaHelper = $captchaHelper;
        $this->logger = $securityLogger;
        $this->captchaResolver = $captchaResolver;
    }

    public function supports(Credentials $credentials): bool
    {
        return $this->captchaHelper->supports($this, $credentials);
    }

    public function onSuccess(Request $request, TokenInterface $token, $providerKey): void
    {
        $this->captchaHelper->onSuccess($this, $request, $token, $providerKey);
    }

    protected function doCheck(Credentials $credentials): void
    {
        $captchaProvider = $this->captchaResolver->resolve($credentials->getRequest());
        $this->logger->info("Captcha provider: " . $captchaProvider->getVendor(), $this->getLogContext($credentials));

        $this->captchaHelper->doCheck(
            $this,
            $credentials,
            $captchaProvider
        );
    }
}
