<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig\CacheWarmer;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * This warmer if modified version of original one.
 * It will catch template compilation errors on cache:warmup stage.
 */
class TemplateCacheWarmer implements CacheWarmerInterface, ServiceSubscriberInterface
{
    private $container;
    private $twig;
    private $iterator;

    public function __construct(ContainerInterface $container, iterable $iterator)
    {
        // As this cache warmer is optional, dependencies should be lazy-loaded, that's why a container should be injected.
        $this->container = $container;
        $this->iterator = $iterator;
    }

    public function warmUp($cacheDir)
    {
        if (null === $this->twig) {
            $this->twig = $this->container->get('twig');
        }

        foreach ($this->iterator as $template) {
            try {
                // do not parse php files
                if (strpos($template, '@Module/') === 0 && strpos($template, '.twig', -5) === false) {
                    continue;
                }
                $this->twig->load($template);
            } catch (Error $e) {
                if (!CacheWarmerKnownErrors::isKnownError($e)) {
                    throw $e;
                }
                // problem during compilation, give up
                // might be a syntax error or a non-Twig template
            }
        }
    }

    public function isOptional()
    {
        return true;
    }

    public static function getSubscribedServices()
    {
        return [
            'twig' => Environment::class,
        ];
    }
}
