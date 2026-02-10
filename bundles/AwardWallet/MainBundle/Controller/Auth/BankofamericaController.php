<?php

namespace AwardWallet\MainBundle\Controller\Auth;

use AwardWallet\Engine\bankofamerica\APIChecker;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\ProviderAuthInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/auth/bankofamerica")
 */
class BankofamericaController implements ProviderAuthInterface
{
    public const PLATFORM_DESKTOP = 'desktop';
    public const PLATFORM_MOBILE_WEB = 'mobile_web';
    public const PLATFORM_MOBILE_NATIVE = 'mobile_native';
    public const PLATFORM_MOBILE_NATIVE_V2 = 'mobile_native_v2';

    public const MOBILE_FALLBACK_HREF = '#/';

    protected $clientId;

    protected $clientSecret;

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
     * @var bool
     */
    protected $sandbox;

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
    private $sslCertFile;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    private string $clientId2025;
    private string $clientSecret2025;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $clientId2025,
        string $clientSecret2025,
        string $sslCertFile,
        RouterInterface $router,
        \Memcached $memcached,
        LoggerInterface $logger,
        bool $sandbox,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        string $localPasswordsKey,
        AuthorizationCheckerInterface $authChecker,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->router = $router;
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->sandbox = $sandbox;
        $this->em = $em;
        $this->translator = $translator;
        $this->localPasswordsKey = $localPasswordsKey;
        $this->authChecker = $authChecker;
        $this->sslCertFile = $sslCertFile;
        $this->tokenStorage = $tokenStorage;
        $this->clientId2025 = $clientId2025;
        $this->clientSecret2025 = $clientSecret2025;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/authorize/{requestId}/{platform}/{encodedPayload}",
     *          name="aw_auth_bankofamerica_authorize",
     *          requirements={
     *              "requestId" = "\w{1,20}",
     *              "platform" = "desktop|mobile_web|mobile_native|mobile_native_v2"
     *          }
     *  )
     */
    public function authorizeAction(Request $request, $requestId, $platform, $encodedPayload = '')
    {
        $newApi = true;

        $state = [
            'host' => $request->getHost(),
            'requestId' => $requestId,
            'platform' => $platform,
            'origin' => $request->getSchemeAndHttpHost(),
            'newApi' => $newApi,
        ];

        if (null !== ($decodedPayload = $this->decodePayload($encodedPayload))) {
            $state['payload'] = $decodedPayload;
        }

        $brand = $request->query->get("brand", "bankofamerica");
        $endPoints = [
            'bankofamerica' => 'https://secure.bankofamerica.com/login/rest/sas/sparta/entry/boa/v1/registration',
            //            'merrill_edge' => 'https://secure.bankofamerica.com/login/rest/sas/sparta/entry/medg/v1/registration',
            //            'merrill_lynch' => 'https://secure.bankofamerica.com/login/rest/sas/sparta/entry/mely/v1/registration',
        ];

        if (!isset($endPoints[$brand])) {
            throw new BadRequestHttpException('Wrong site');
        }

        $scopes = [
            'bankofamerica' => 'account transaction statement',
            //            'merrill_edge' => 'account.medg transaction.medg statement.medg',
            //            'merrill_lynch' => 'account.mely transaction.mely statement.mely',
        ];

        if ($newApi) {
            $endPoints['bankofamerica'] = "https://secure.bankofamerica.com/login/rest/sas/sparta/entry/con/v1/authorize";
            $scopes["bankofamerica"] = "fdx:accountbasic:read fdx:accountdetailed:read fdx:statements:read fdx:transactions:read";
        }

        $this->memcached->set($this->getStateKey($requestId), $state, SECONDS_PER_HOUR * 24);

        return new Response("<html>
			<body>
				<div style='margin-left: auto; margin-right: auto; width: 66px; margin-top: 80px;'>
					<img style='width: 66px; height: 66px; border: none;' src='/lib/images/progressBig.gif'>
				</div>
				<script>document.location.href = '" . $endPoints[$brand] . "?" . http_build_query([
            "response_type" => "code",
            "client_id" => $newApi ? $this->clientId2025 : $this->clientId,
            "redirect_uri" => 'https://awardwallet.com' . $this->router->generate('aw_auth_bankofamerica_callback'),
            "scope" => $scopes[$brand],
            "state" => $requestId,
            "foreign_alias_id" => $this->tokenStorage->getUser()->getUserid(),
        ]) . "';
		</script></body>");
    }

    /**
     * @Route("",
     *     name="aw_auth_bankofamerica_callback",
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

            return $this->openPlatform($this->detectPlatform(), $payload, null, "invalid state", $request->getSchemeAndHttpHost());
        }

        if (!array_key_exists('newApi', $validState)) {
            $validState['newApi'] = false;
        }

        $newApi = $validState['newApi'];

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

            return $this->openPlatform($platform, $payload, null, "empty code", $validState['origin']);
        }

        $sessionId = bin2hex(random_bytes(5));
        $vpiId = $request->query->get("VPID");

        $options = [
            CURLOPT_POST => true,
            CURLOPT_COOKIEFILE => "",
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HEADER => false,
            CURLOPT_FAILONERROR => false, // want to see errors
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'redirect_uri' => $this->router->generate('aw_auth_bankofamerica_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'grant_type' => 'authorization_code',
            ]),
            CURLOPT_HTTPHEADER => [
                'User-Agent: awardwallet',
                'Accept: application/json',
                'X-BOA-Session-ID: ' . $sessionId,
                'X-BOA-Trace-ID: ' . bin2hex(random_bytes(5)),
            ],
            \CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            \CURLOPT_USERPWD => $newApi ? ($this->clientId2025 . ':' . $this->clientSecret2025) : ($this->clientId . ":" . $this->clientSecret),
            \CURLOPT_COOKIE => 'VPID: ' . urlencode($vpiId),
            \CURLOPT_SSLCERT => $this->sslCertFile,
            \CURLOPT_PROXY => 'whiteproxy.infra.awardwallet.com:3128',
        ];

        if (!file_exists($this->sslCertFile)) {
            throw new \Exception("client key not found");
        }
        $this->logger->warning("oauth started", ["options" => $options]);

        $url = APIChecker::getBaseHost(false) . "/oauth/v1/boa/exchangeToken";

        if ($newApi) {
            $url = 'https://vendorservices-awardwallet.bankofamerica.com/obcgateway/awardwallet/oauth/v2/exchangeToken';
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $options);

        $result = curl_exec($curl);
        $requestInfo = curl_getinfo($curl);
        $error = curl_errno($curl) . ": " . curl_error($curl);
        $cookies = curl_getinfo($curl, CURLINFO_COOKIELIST);
        $cookies = APIChecker::parseCurlCookies($cookies);
        curl_close($curl);

        if ($requestInfo['http_code'] !== 200) {
            $this->logger->critical("bankofamerica error: " . $result . " : " . $error, ["requestInfo" => $requestInfo]);

            return $this->openPlatform($platform, $payload, null, $error, $validState['origin']);
        }

        $tokenInfo = APIChecker::parseTokenInfo(json_decode($result, true), $this->sandbox, $newApi);

        if (empty($tokenInfo)) {
            $this->logger->error("invalid token: " . $result);
            $this->memcached->delete($stateKey);

            return $this->openPlatform($platform, $payload, null, 'invalid token received', $validState['origin']);
        }

        if (isset($cookies['VPID'])) {
            $tokenInfo['VPID'] = $cookies['VPID'];
        }

        if (isset($cookies['JS_AGW'])) {
            $tokenInfo['JS_AGW'] = $cookies['JS_AGW'];
        }

        $encodedOauthKey = base64_encode(AESEncode(json_encode($tokenInfo), $this->localPasswordsKey));
        $this->memcached->delete($stateKey);

        return $this->openPlatform($platform, $payload, $encodedOauthKey, null, $validState['origin']);
    }

    public function getAuthUrl($accountId)
    {
        return $this->router->generate('aw_auth_bankofamerica_authorize', ['accountId' => $accountId]);
    }

    protected function getStateKey($requestId)
    {
        return "capitalcards_" . $requestId . "_state_2";
    }

    protected function openPlatform(string $platform, array $payload, ?string $oauthCode, ?string $error, string $origin)
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
                return $this->openDesktop($error, $oauthCode, $origin);

            default:
                throw new \RuntimeException('Undefined platform');
        }
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

    /**
     * @return Response
     */
    protected function openDesktop(?string $error, ?string $oauthKey, string $origin)
    {
        return new Response('<html><script>window.opener.postMessage({
            messageType: "oauth",
            error: ' . json_encode($error) . ', 
            authInfo: ' . json_encode($oauthKey) .
        '}, ' . json_encode($origin) . ')</script></html>');
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

    private function getBaseHost()
    {
        if ($this->sandbox) {
            return "https://api-sandbox.capitalone.com";
        } else {
            return "https://api.capitalone.com";
        }
    }
}
