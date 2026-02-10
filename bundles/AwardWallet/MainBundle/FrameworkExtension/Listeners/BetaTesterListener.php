<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\Service\TransHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BetaTesterListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Memcached
     */
    private $cache;

    /**
     * @var TransHelper
     */
    private $transHelper;

    private $key = "tyTs5af9oWp9Awd8";

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        \Memcached $cache,
        TransHelper $transHelper
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->transHelper = $transHelper;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->tokenStorage->getToken();

        /** @var Usr $user */
        if (isset($token) && $user = $token->getUser()) {
            $user = ($user instanceof Usr) ? $user : null;
            $request = $event->getRequest();
            $enableDesktopHelper = $this->transHelper->isEnabled($request, $user);

            if ($user instanceof Usr && $this->transHelper->isUserTranslator($user)) {
                if ((new RequestMatcher('^/m/api/'))->matches($request)) {
                    $this->translator->setDumpKeysEnabled();
                } elseif ($enableDesktopHelper) {
                    $this->translator->setEnableDesktopHelper(true);
                    $encoded = md5($user->getUserid() . $this->key);
                    $this->cache->add($encoded, true);
                }
            } else {
                $this->translator->setEnableDesktopHelper($enableDesktopHelper);
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        $user = isset($token) ? $token->getUser() : null;
        $response = $event->getResponse();

        /** @var Usr $user */
        if ($user instanceof Usr && (int) $event->getRequest()->cookies->get('transhelper') == 1) {
            $encoded = md5($user->getUserid() . $this->key);

            if ($this->cache->get($encoded)) {
                $response->headers->setCookie(AwCookieFactory::createLax('transhelper', $encoded, 0, '/', null, false, false));
            }
        }

        if (!$this->translator->isDumpKeysEnabled()) {
            return;
        }

        if (!($response instanceof JsonResponse || 'application/json' === $response->headers->get('content-type'))) {
            return;
        }

        $dumpedKeys = $this->translator->getDumpedKeys();

        if (count($dumpedKeys) === 0) {
            return;
        }

        $content = $response->getContent();

        if (
            isset($content[0])
            && ('[' === $content[0])
        ) {
            return;
        }

        $data = @json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->logger->error(sprintf('Can not intercept JsonResponse, len: %d, first_2_KB: %s', strlen($content), substr($content, 0, 2048)), ['_aw_trans' => 1]);

            return;
        }

        if (!\is_array($data)) {
            return;
        }

        $data['translationKeys'] = $dumpedKeys;

        if ($response instanceof JsonResponse) {
            $response->setData($data);
        } else {
            $response->setContent(json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
        }
        $event->setResponse($response);
    }
}
