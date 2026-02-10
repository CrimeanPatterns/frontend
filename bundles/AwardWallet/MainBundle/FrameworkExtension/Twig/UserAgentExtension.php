<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserAgentExtension extends AbstractExtension
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('is_ios', [$this, 'isIos'], ['is_safe' => ['html']]),
            new TwigFunction('is_android', [$this, 'isAndroid'], ['is_safe' => ['html']]),
        ];
    }

    public function isIos(): bool
    {
        $request = $this->requestStack->getMasterRequest();

        if (!$request) {
            return false;
        }

        $userAgent = $request->headers->get('User-Agent');

        return preg_match('/(iPhone|iPod|iPad)/', $userAgent) === 1;
    }

    public function isAndroid(): bool
    {
        $request = $this->requestStack->getMasterRequest();

        if (!$request) {
            return false;
        }

        $userAgent = $request->headers->get('User-Agent');

        return preg_match('/Android/', $userAgent) === 1;
    }
}
