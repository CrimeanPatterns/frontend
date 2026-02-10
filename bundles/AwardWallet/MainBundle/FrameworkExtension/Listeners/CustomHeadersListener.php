<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\FriendsOfLoggerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CustomHeadersListener
{
    use FriendsOfLoggerTrait;
    public const CSP_DIRECTIVE_FRAME = 'frame-src';
    public const CSP_DIRECTIVE_SCRIPT = 'script-src';
    public const CSP_DIRECTIVE_STYLE = 'style-src';
    public const CSP_DIRECTIVE_IMG = 'img-src';
    public const CSP_DIRECTIVE_FONT = 'font-src';
    public const CSP_DIRECTIVE_MEDIA = 'media-src';
    public const CSP_DIRECTIVE_CONNECT = 'connect-src';
    public const XROBOTSTAG_NOINDEX = 'noindex, nofollow';
    private const CSP_HEADER_ATTRIBUTE_KEY = '_custom_headers_listener_attr_key';

    private const CSP_VALID_DIRECTIVES_MAP = [
        self::CSP_DIRECTIVE_FRAME => true,
        self::CSP_DIRECTIVE_SCRIPT => true,
        self::CSP_DIRECTIVE_STYLE => true,
        self::CSP_DIRECTIVE_IMG => true,
        self::CSP_DIRECTIVE_FONT => true,
        self::CSP_DIRECTIVE_MEDIA => true,
        self::CSP_DIRECTIVE_CONNECT => true,
    ];

    /**
     * @var AwTokenStorage
     */
    private $tokenStorage;
    /**
     * @var AppProcessor
     */
    private $appProcessor;
    /**
     * @var string
     */
    private $cometServerUrl;
    /**
     * @var string
     */
    private $requiresChannel;
    private bool $cspReport;
    private LoggerInterface $logger;
    private string $businessHost;
    private string $host;
    private string $cdnHost;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AppProcessor $appProcessor,
        LoggerInterface $securityLogger,
        string $cometServerUrl,
        string $requiresChannel,
        string $host,
        string $businessHost,
        bool $cspReport,
        string $cdnHost
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->appProcessor = $appProcessor;
        $this->cometServerUrl = $cometServerUrl;
        $this->requiresChannel = $requiresChannel;
        $this->cspReport = $cspReport;
        $this->logger = $this->makeContextAwareLogger($securityLogger);
        $this->businessHost = $businessHost;
        $this->host = $host;
        $this->cdnHost = $cdnHost;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $request->attributes->set('start_time', microtime(true));
    }

    /**
     * @param array<self::CSP_DIRECTIVE_*, list<string>> $domains
     */
    public function addDomainsToCSPDirective(Request $request, array $domains): void
    {
        $cspHeadersData = $request->attributes->get(self::CSP_HEADER_ATTRIBUTE_KEY, []);

        foreach ($domains as $directive => $directiveDomains) {
            if (!\array_key_exists($directive, self::CSP_VALID_DIRECTIVES_MAP)) {
                throw new \LogicException('Invalid CSP directive');
            }

            if (!\is_array($directiveDomains)) {
                throw new \LogicException('Invalid CSP directive format');
            }

            $cspHeadersData[$directive] = \array_unique(\array_merge(
                $cspHeadersData[$directive] ?? [],
                $directiveDomains
            ));
        }

        $request->attributes->set(self::CSP_HEADER_ATTRIBUTE_KEY, $cspHeadersData);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        $response->headers->set('X-RequestId', $this->appProcessor->getRequestId());

        if ($user = $this->tokenStorage->getUser()) {
            $response->headers->set('X-Aw-Userid', $user->getUserid());
        }

        if ($request->hasSession()) {
            $response->headers->set('X-SessionId', substr($request->getSession()->getId(), -4));
        }

        if ($request->attributes->has('start_time')) {
            $response->headers->set('X-PHPTime', round((microtime(true) - (float) $request->attributes->get('start_time')) * 1000));
        }

        $isRestrictionEnabled = true;

        if (!$isRestrictionEnabled || 0 === strpos($request->getPathInfo(), '/manager/')) {
            $response->headers->set('Content-Security-Policy', "frame-src 'self' https://facebook.com *.facebook.com https://*.youcanbook.me https://www.google.com https://www.youtube.com *.vimeo.com *.doubleclick.net");
            $response->headers->set('Content-Security-Policy-Report-Only', $this->cspHeader($request));
        } else {
            $response->headers->set('Content-Security-Policy', $this->cspHeader($request));
        }

        if (
            ($request->getPathInfo() === '/m/api/mailbox/check-status')
            && (403 === $response->getStatusCode())
        ) {
            $logContext = [
                'cookies_count' => $cookiesCount = $request->cookies->count(),
                'cookies_names' =>
                    it($request->cookies->keys())
                    ->map(fn (string $name) => \substr($name, 0, 10))
                    ->toArray(),
                'headers_names' => \array_keys($request->headers->all()),
            ];

            if ($cookiesCount > 0) {
                $pwdHashCookie = $request->cookies->get('PwdHash');

                if (null !== $pwdHashCookie) {
                    $logContext['raw_cookie_len'] = \strlen($pwdHashCookie);
                    $logContext['raw_cookie_prefix'] = \substr($pwdHashCookie, 0, 100);
                }

                $logContext['session_id_cookie_present'] = (null !== $request->cookies->get('PHPSESSID'));
                $logCookie = $request->cookies->get('Log');

                if (null !== $logCookie) {
                    $logContext['log_cookie'] = $logCookie;
                }

                $this->logger->info('Kernel response. Remember me debug.', $logContext);
            }
        }

        if ($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME)) {
            /** @var Cookie $cookie */
            foreach (
                it($response->headers->getCookies())
                ->filter(static fn (Cookie $cookie) => $cookie->getName() === 'PwdHash') as $cookieIdx => $cookie
            ) {
                $this->logger->info('Kernel Response. Remember-me cookie set in response cookies.', [
                    'raw_cookie_len' => \strlen($cookie->getValue()),
                    'expiration_ts' => $cookie->getExpiresTime(),
                    'cookie_idx' => $cookieIdx,
                ]);
            }
        }

        $event->setResponse($response);
    }

    private function cspHeader(Request $request): string
    {
        $cspHeadersData = $request->attributes->get(self::CSP_HEADER_ATTRIBUTE_KEY, []);

        $src = [];
        $src[] = "default-src 'self';";
        $src[] = 'frame-src ' . $this->frameSrc($cspHeadersData[self::CSP_DIRECTIVE_FRAME] ?? []) . ';';
        $src[] = 'script-src ' . $this->scriptSrc($cspHeadersData[self::CSP_DIRECTIVE_SCRIPT] ?? []) . ';';
        $src[] = 'style-src ' . $this->styleSrc($cspHeadersData[self::CSP_DIRECTIVE_STYLE] ?? []) . ';';
        $src[] = 'img-src ' . $this->imgSrc($cspHeadersData[self::CSP_DIRECTIVE_IMG] ?? []) . ';';
        $src[] = 'font-src ' . $this->fontSrc($cspHeadersData[self::CSP_DIRECTIVE_FONT] ?? []) . ';';
        $src[] = 'media-src ' . $this->mediaSrc($cspHeadersData[self::CSP_DIRECTIVE_MEDIA] ?? []) . ";";
        $src[] = 'connect-src ' . $this->connectSrc($cspHeadersData[self::CSP_DIRECTIVE_CONNECT] ?? []) . ';';

        if ($this->cspReport) {
            // spamming production logs, causing 429
            // $src[] = 'report-uri /csp-report;';
        }

        return implode(' ', $src);
    }

    /**
     * @param list<string> $addDomains
     */
    private function frameSrc(array $addDomains = []): string
    {
        return \implode(
            ' ',
            \array_merge(
                [
                    "'self'",
                    'https://facebook.com',
                    '*.facebook.com',
                    'https://*.youcanbook.me',
                    'https://www.google.com',
                    'https://www.youtube.com',
                    '*.vimeo.com',
                    '*.doubleclick.net',
                    'https://optimize.google.com',
                    'https://js.stripe.com',
                    'https://challenges.cloudflare.com',
                    'https://app.termly.io',
                ],
                $addDomains
            )
        );
    }

    /**
     * @param list<string> $addDomains
     */
    private function mediaSrc(array $addDomains = []): string
    {
        return \implode(
            ' ',
            \array_merge(
                [
                    "'self'",
                    "'unsafe-inline'",
                ],
                $addDomains
            )
        );
    }

    /**
     * @param list<string> $addDomains
     */
    private function fontSrc(array $addDomains = []): string
    {
        return \implode(
            ' ',
            \array_merge(
                [
                    "'self'",
                    "'unsafe-inline'",
                    "data:",
                    "https://fonts.gstatic.com",
                    "https:" . $this->cdnHost,
                ],
                $addDomains
            )
        );
    }

    /**
     * @param list<string> $addDomains
     */
    private function scriptSrc(array $addDomains = []): string
    {
        return implode(
            ' ',
            \array_merge(
                [
                    "'self'",
                    "'unsafe-inline'",
                    "'unsafe-eval'",
                    'https://cdn.digitrust.mgr.consensu.org',
                    'http://connect.facebook.net',
                    'https://connect.facebook.net',
                    'http://hm.baidu.com',
                    'http://www.google-analytics.com',
                    'https://www.google-analytics.com',
                    'https://www.google.com',
                    'https://www.gstatic.com',
                    'https://optimize.google.com',
                    'https:' . $this->cdnHost,
                    'https://cmp.quantcast.com',
                    'https://secure.quantserve.com',
                    'https://rules.quantcount.com',
                    'https://maps.googleapis.com',
                    'https://www.google-analytics.com/analytics.js',
                    'https://stats.g.doubleclick.net/dc.js',
                    'https://connect.facebook.net/en_US/sdk.js',
                    'https://unpkg.com/@google/markerclustererplus@4.0.1/dist/markerclustererplus.min.js',
                    'https://www.googletagmanager.com',
                    'http://cdnjs.cloudflare.com/ajax/libs/require.js/2.3.6/require.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/require.js/2.3.6/require.min.js',
                    'http://cdnjs.cloudflare.com/ajax/libs/jqueryui/',
                    'https://js.stripe.com', // stripe payments
                    'https://challenges.cloudflare.com/turnstile/v0/api.js',
                    'https://dist.entityclouds.com/entity.js', // tag manager +connectSrc entity.php
                    'https://app.termly.io',

                    /** elfinder */
                    'http://cdnjs.cloudflare.com/ajax/libs/jquery/',
                ],
                $addDomains
            )
        );
    }

    /**
     * @param list<string> $addDomains
     */
    private function imgSrc(array $addDomains = []): string
    {
        return implode(
            ' ',
            \array_merge(
                [
                    "'self'",
                    'data:',
                    'blob:',
                    'https://awardwallet.com',
                    'http://hm.baidu.com',
                    'https://www.facebook.com',
                    'http://www.google-analytics.com',
                    'https://www.google-analytics.com',
                    'https://www.google.com',
                    'https://optimize.google.com',
                    'https://www.gstatic.com',
                    'https:' . $this->cdnHost,
                    'https://pixel.quantserve.com',
                    'https://maps.googleapis.com',
                    'https://maps.gstatic.com',
                    'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m1.png',
                    'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m2.png',
                    'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m3.png',
                    'https://lh3.googleusercontent.com',
                    'https://s.yimg.com',
                    'https://analytics.google.com',
                    'https://dtwuzpz2q0bmy.cloudfront.net',
                    'https://www.googletagmanager.com',
                    'https://secure.gravatar.com/avatar/',

                    /** elfinder */
                    'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/',
                ],
                $addDomains
            )
        );
    }

    /**
     * @param list<string> $addDomains
     */
    private function connectSrc(array $addDomains = []): string
    {
        $wsChannel = $this->requiresChannel === 'https' ? 'wss' : 'ws';

        return implode(
            ' ',
            \array_merge(
                [
                    "'self'",
                    'https://cmp.digitru.st https://vendorlist.consensu.org https://test.cmp.quantcast.com https://audit-tcfv2.cmp.quantcast.com',
                    'https://www.google-analytics.com',
                    'https://stats.g.doubleclick.net',
                    'https://test.quantcast.mgr.consensu.org',
                    'https://cmp.quantcast.com',
                    '*.quantcast.mgr.consensu.org',
                    'https://www.googletagmanager.com',
                    'https://analytics.google.com',
                    'https://maps.googleapis.com',
                    $this->cometServerUrl,
                    \str_replace("{$this->requiresChannel}://", "{$wsChannel}://", $this->cometServerUrl),
                    "{$wsChannel}://{$this->host}",
                    "{$wsChannel}://{$this->businessHost}",
                    'https://dist.entityclouds.com/entity.php', // tag manager + scriptSrc entity.js
                    'https://app.termly.io',
                    'https://us.consent.api.termly.io',
                    'https://*.algolia.net https://*.algolianet.com https://*.algolia.io',
                ],
                $addDomains
            )
        );
    }

    /**
     * @param list<string> $addDomains
     */
    private function styleSrc(array $addDomains = []): string
    {
        return implode(
            ' ',
            \array_merge(
                [
                    "'self'",
                    "'unsafe-inline'",
                    'https://fonts.googleapis.com',
                    'https://optimize.google.com',
                    'https:' . $this->cdnHost,
                    'http://cdnjs.cloudflare.com/ajax/libs/jqueryui/',
                    'https://www.googletagmanager.com',
                ],
                $addDomains
            )
        );
    }
}
