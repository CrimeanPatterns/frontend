<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\AwSecureTokenListener;

use AwardWallet\MainBundle\Configuration\AwSecureToken;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class AwSecureTokenListener
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    public function __construct(
        ContainerInterface $container,
        ApiVersioningService $apiVersioning
    ) {
        $this->container = $container;
        $this->apiVersioning = $apiVersioning;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        /** @var AwSecureToken $configuration */
        if (
            !($configuration = $request->attributes->get('_aw_secure_token'))
            || (!$configuration instanceof AwSecureToken)
        ) {
            return;
        }

        if (
            ($methods = $configuration->getMethods())
            && !in_array($request->getRealMethod(), $methods)
        ) {
            return;
        }

        if (
            ($triggerFeatures = $configuration->getTriggerFeatures())
            && !$this->apiVersioning->supportsAll($triggerFeatures)
        ) {
            return;
        }

        $service = $this->container->get($configuration->getService());

        if (!$service instanceof TokenCheckerInterface) {
            throw new \RuntimeException(sprintf("@AwSecureToken annotation: checker service '%s' must implement %s", $configuration->getService(), TokenCheckerInterface::class));
        }

        $tokenHandle = new SecureTokenHandle($request, $configuration);

        if (
            ($response = $service->check($tokenHandle))
            && ($response instanceof Response)
        ) {
            $event->setController(function () use ($response) {
                return $response;
            });

            return;
        }
    }
}
