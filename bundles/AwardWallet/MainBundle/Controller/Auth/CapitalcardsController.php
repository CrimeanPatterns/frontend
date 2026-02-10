<?php

namespace AwardWallet\MainBundle\Controller\Auth;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\CapitalcardsHelper;
use AwardWallet\MainBundle\Service\ProviderAuthInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/auth/capitalcards")
 */
class CapitalcardsController implements ProviderAuthInterface
{
    public const PLATFORM_DESKTOP = 'desktop';
    public const PLATFORM_MOBILE_WEB = 'mobile_web';
    public const PLATFORM_MOBILE_NATIVE = 'mobile_native';
    public const PLATFORM_MOBILE_NATIVE_V2 = 'mobile_native_v2';

    public const MOBILE_FALLBACK_HREF = '#/';

    /**
     * @var RouterInterface
     */
    protected $router;
    /**
     * @var \Memcached
     */
    protected $memcached;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var EntityManagerInterface
     */
    protected $em;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $localPasswordsKey;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;
    /**
     * @var CapitalcardsHelper
     */
    private $capitalcardsHelper;

    public function __construct(
        RouterInterface $router,
        \Memcached $memcached,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        string $localPasswordsKey,
        AuthorizationCheckerInterface $authChecker,
        CapitalcardsHelper $capitalcardsHelper
    ) {
        $this->router = $router;
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->em = $em;
        $this->translator = $translator;
        $this->localPasswordsKey = $localPasswordsKey;
        $this->authChecker = $authChecker;
        $this->capitalcardsHelper = $capitalcardsHelper;
    }

    /**
     * @Route("/authorize/{requestId}/{platform}/{encodedPayload}",
     *          name="aw_auth_capitalcards_authorize",
     *          requirements={
     *              "requestId" = "\w{1,20}",
     *              "platform" = "desktop|mobile_web|mobile_native|mobile_native_v2"
     *          }
     *  )
     */
    public function authorizeAction(Request $request, $requestId, $platform, $encodedPayload = '')
    {
        $state = [
            'host' => $request->getHost(),
            'requestId' => $requestId,
            'platform' => $platform,
            'origin' => $request->getSchemeAndHttpHost(),
            'prefix' => $request->query->getAlpha("prefix", 'rewards'),
        ];

        if (!in_array($state['prefix'], ['rewards', 'tx'])) {
            throw new BadRequestHttpException('invalid prefix');
        }

        if (null !== ($decodedPayload = $this->decodePayload($encodedPayload))) {
            $state['payload'] = $decodedPayload;
        }

        $this->memcached->set($this->getStateKey($requestId), $state, SECONDS_PER_HOUR * 24);

        return new Response("<html>
			<body>
				<div style='margin-left: auto; margin-right: auto; width: 66px; margin-top: 80px;'>
					<img style='width: 66px; height: 66px; border: none;' src='/lib/images/progressBig.gif'>
				</div>
				<script>document.location.href = '" . $this->capitalcardsHelper->getAuthorizeUrl($state['prefix'] === 'rewards', $requestId) . "';
		</script></body>");
    }

    /**
     * @Route("/callback",
     *     name="aw_auth_capitalcards_callback",
     *     host="%host%"
     * )
     */
    public function callbackAction(Request $request)
    {
        $requestId = $request->query->get("state");
        $validState = $this->memcached->get($stateKey = $this->getStateKey($requestId));
        // fallback mobile url(Account List)
        $payload = [
            'href' => self::MOBILE_FALLBACK_HREF,
        ];

        if (empty($validState)) {
            $this->memcached->delete($stateKey);

            return $this->openPlatform($this->detectPlatform(), $payload, null, "invalid state", $request->getSchemeAndHttpHost(), null);
        }
        $platform = $validState['platform'];

        if (!(new RequestMatcher(null, '^m\.'))->matches($request)) {
            if (
                isset($validState['host'])
                && preg_match('|^m\.|ims', $validState['host'])
            ) {
                return new RedirectResponse(preg_replace('|(https?://)|ims', '$1m.', $request->getUri()));
            }
        }

        if (isset($validState['payload']) && !StringHandler::isEmpty($validState['payload'])) {
            $payload = $validState['payload'];
        }

        $code = $request->query->get("code");

        if (empty($code)) {
            $this->memcached->delete($stateKey);

            return $this->openPlatform($platform, $payload, null, "empty code", $validState['origin'], $validState['prefix']);
        }

        $tokenInfo = $this->capitalcardsHelper->exchangeCode($validState['prefix'] === 'rewards', $code);

        if (empty($tokenInfo)) {
            $this->memcached->delete($stateKey);

            return $this->openPlatform($platform, $payload, null, 'invalid token received', $validState['origin'], $validState['prefix']);
        }

        $encodedOauthKey = base64_encode(AESEncode(json_encode($tokenInfo), $this->localPasswordsKey));
        $this->memcached->delete($stateKey);

        return $this->openPlatform($platform, $payload, $encodedOauthKey, null, $validState['origin'], $validState['prefix']);
    }

    public function getAuthUrl($accountId)
    {
        return $this->router->generate('aw_auth_capitalcards_authorize', ['accountId' => $accountId]);
    }

    protected function openPlatform(string $platform, array $payload, ?string $oauthCode, ?string $error, string $origin, ?string $prefix)
    {
        if (
            isset($error)
            && (self::MOBILE_FALLBACK_HREF !== $payload['href'])
        ) {
            $error = str_replace('/', '_', base64_encode($this->translator->trans('error.auth.failure')));
            $payload['href'] = trim($payload['href'], '/') . '?error=' . $error;
            $payload['state']['params']['error'] = $error;
        }

        if (isset($oauthCode, $payload['state']['params'], $payload['href'])) {
            // mobile routing-friendly conversion
            $oauthCode = str_replace('/', '_', $oauthCode);
            $payload['state']['params']['oauthKey'] = $oauthCode;
            $payload['href'] = trim($payload['href'], '/') . '/' . $oauthCode;
        }

        switch ($platform) {
            case self::PLATFORM_MOBILE_NATIVE:
                return $this->openMobileNative($payload);

            case self::PLATFORM_MOBILE_NATIVE_V2:
                return $this->openMobileNativeV2($error, $oauthCode);

            case self::PLATFORM_MOBILE_WEB:
                return $this->openMobileWeb($payload);

            case self::PLATFORM_DESKTOP:
                return $this->openDesktop($error, $oauthCode, $origin, $prefix);

            default:
                throw new \RuntimeException('Undefined platform');
        }
    }

    /**
     * @return Response
     */
    protected function openDesktop(?string $error, ?string $oauthKey, string $origin, ?string $prefix)
    {
        return new Response('<html><script>window.opener.postMessage({
            messageType: "oauth",
            error: ' . json_encode($error) . ',
            prefix:  ' . json_encode($prefix) . ',
            authInfo: ' . json_encode($oauthKey) .
        '}, ' . json_encode($origin) . ')</script></html>');
    }

    /**
     * @return Response
     */
    protected function openMobileNative(array $payload)
    {
        return new Response('<html><script>document.location.href = "awardwallet://' . $this->encodePayload($payload) . '";</script></html>');
    }

    protected function openMobileNativeV2($error, $oauthCode)
    {
        $data = [
            "code" => $oauthCode,
            "error" => $error,
        ];

        return new Response(
            '<html>' .
                '<script>' .
                    '
                    window.postMessage = String(Object.hasOwnProperty).replace(\'hasOwnProperty\', \'postMessage\');
                    function sendMessage(data) {
                        if (window.hasOwnProperty(\'ReactNativeWebView\')) {
                            window.ReactNativeWebView.postMessage(data);
                        } else {
                            if (window.postMessage.length !== 1) {
                                setTimeout(function(){ sendMessage(data); }, 200);
                            } else {
                                window.postMessage(data);
                            }
                        }
                    }
                    sendMessage(JSON.stringify(' . json_encode($data) . '));' .
                '</script>' .
            '</html>'
        );
    }

    /**
     * @return Response
     */
    protected function openMobileWeb(array $payload)
    {
        // use awardwallet:// scheme here because client has unified native\web url handling
        return new Response('<html><script>window.opener.handleOpenURL("awardwallet://' . $this->encodePayload($payload) . '");</script></html>');
    }

    protected function getStateKey($requestId)
    {
        return "capitalcards_" . $requestId . "_state_2";
    }

    /**
     * @param string $base64
     * @return array|null
     */
    private function decodePayload($base64)
    {
        $decoded = base64_decode($base64);

        if (false === $decoded) {
            return null;
        }

        $decoded = @json_decode($decoded, true);

        if (!$decoded) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param array $payload
     * @return string
     */
    private function encodePayload($payload)
    {
        return base64_encode(json_encode($payload));
    }

    private function detectPlatform()
    {
        if ($this->authChecker->isGranted('SITE_MOBILE_APP')) {
            if ($this->authChecker->isGranted('SITE_MOBILE_APP_REACT_NATIVE')) {
                return self::PLATFORM_MOBILE_NATIVE_V2;
            } else {
                return self::PLATFORM_MOBILE_NATIVE;
            }
        } elseif ($this->authChecker->isGranted('SITE_MOBILE_VERSION_SUITABLE')) {
            return self::PLATFORM_MOBILE_WEB;
        } else {
            return self::PLATFORM_DESKTOP;
        }
    }
}
