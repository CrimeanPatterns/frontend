<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Configuration\ApiVersion;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use Herrera\Version\Comparator;
use Herrera\Version\Exception\InvalidStringRepresentationException;
use Herrera\Version\Parser;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiVersionListener implements TranslationContainerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ApiVersioningService
     */
    private $service;

    public function __construct(ApiVersioningService $service, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->translator = $translator;
        $this->logger = $logger;
        $this->service = $service;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $providedVersion = $request->headers->get(MobileHeaders::MOBILE_VERSION);

        if (!isset($providedVersion)) {
            $this->service->setVersionsProvider(null);
            $this->service->setVersion(null);

            return;
        }

        try {
            $version = Parser::toVersion($providedVersion);
        } catch (InvalidStringRepresentationException $e) {
            $this->logger->warning($e->getMessage(), ['_aw_api_module' => 'vers']);

            return;
        }

        $this->service->setVersion($version);

        if ((new RequestMatcher('/m/api/'))->matches($request)) {
            $this->service->setVersionsProvider(new MobileVersions($request->headers->get(MobileHeaders::MOBILE_PLATFORM, '')));
        }
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        /** @var ApiVersion $configuration */
        if (!$configuration = $request->attributes->get('_api_version')) {
            return;
        }

        $providedVersion = $this->service->getVersion();

        if (null === $providedVersion) {
            return;
        }

        $minVersion = $configuration->getMin();

        if (isset($minVersion)) {
            try {
                if (Comparator::isLessThan($providedVersion, Parser::toVersion($minVersion))) {
                    $this->prepareResponse($configuration, $event);
                }
            } catch (InvalidStringRepresentationException $e) {
                $this->logger->warning($e->getMessage(), ['_aw_api_module' => 'vers']);

                return;
            }
        }

        $features = $configuration->getFeatures();

        if (
            is_array($features)
            && !$this->service->supportsAll($features)
        ) {
            $this->prepareResponse($configuration, $event);
        }
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('outdated.text', 'mobile'))
                ->setDesc("We've released a new version of this app please click OK to upgrade."),
        ];
    }

    protected function prepareResponse(ApiVersion $configuration, FilterControllerEvent $event)
    {
        $msg = $this->translator->trans(/** @Ignore */ 'outdated.text', [], $configuration->getDomain());
        $response = new JsonResponse(['error' => $msg]);
        $response->headers->set(MobileHeaders::MOBILE_VERSION, $msg);

        $event->setController(function () use ($response) {
            return $response;
        });
    }
}
