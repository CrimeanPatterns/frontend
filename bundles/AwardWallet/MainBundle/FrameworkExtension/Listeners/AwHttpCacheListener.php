<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Configuration\AwCache;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\EventListener\HttpCacheListener;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AwHttpCacheListener extends HttpCacheListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();

        $this->logger = $logger;
    }

    public function onKernelController(KernelEvent $event)
    {
        $request = $event->getRequest();

        /** @var AwCache $configuration */
        if (!$configuration = $request->attributes->get('_awcache')) {
            return;
        }

        if ($configuration->getEtag() && $configuration->getEtagContentHash()) {
            throw new \RuntimeException("@AwCache annotation conflict: 'etag' and 'etagContentHash' options ca be de declared at the same time");
        }

        $request->attributes->set('_cache', $configuration);
        parent::onKernelController($event);
    }

    public function onKernelResponse(KernelEvent $event)
    {
        parent::onKernelResponse($event);

        $request = $event->getRequest();
        $response = $event->getResponse();

        /** @var AwCache $configuration */
        if (!$configuration = $request->attributes->get('_awcache')) {
            if (!$response->headers->has('Expires')) {
                $response->headers->set("Expires", "Mon, 26 Jul 1997 05:00:00 GMT");
            }

            if (!$response->headers->has('Last-Modified')) {
                $response->headers->set("Last-Modified", gmdate("D, d M Y H:i:s") . " GMT");
            }

            if (!$response->headers->has('Pragma')) {
                $response->headers->set("Pragma", "no-cache");
            }

            if (
                !$response->headers->has('Cache-Control')
                || ('private, must-revalidate' === $response->headers->get('Cache-Control'))
            ) {
                $response->headers->addCacheControlDirective("private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0");
            }

            return;
        }

        if ($configuration->isNoCache()) {
            $response->headers->addCacheControlDirective('no-cache');
        }

        if ($configuration->isNoStore()) {
            $response->headers->addCacheControlDirective('no-store');
        }

        if ($hashAlgo = $configuration->getEtagContentHash()) {
            $etags = $request->getETags();

            if (isset($etags[0]) && (count($etags) == 1)) {
                $request->headers->set('if_none_match', preg_replace('/(-gzip)([\'"]?)$/', '$2', $etags[0]));
            }

            $response->setEtag(hash($hashAlgo, $response->getContent()));

            if ($response->isNotModified($request)) {
                $this->logger->warning('Etag cache hit', [
                    '_aw_server_module' => 'awcache',
                    '_aw_server_uri' => $request->getRequestUri(),
                ]);
            }
        }

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -1], // priority -1 runs after ControllerListener
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
