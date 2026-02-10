<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ClosureExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('closure', [$this, 'executeClosure']),
        ];
    }

    public function executeClosure(\Closure $closure, $arguments)
    {
        return $closure(...$arguments);
    }
}
