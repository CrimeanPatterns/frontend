<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\MobileRouteListener;

use AwardWallet\MainBundle\Controller\HomeController;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\SiteAdManager;
use AwardWallet\MainBundle\Security\Voter\SiteVoter;
use AwardWallet\MainBundle\Service\LegacyUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

class MobileRouteListener
{
    /**
     * @var SiteVoter
     */
    private $siteVoter;

    /**
     * @var string
     */
    private $cacheDir;
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var LegacyUrlGenerator
     */
    private $legacyUrlGenerator;
    /**
     * @var string
     */
    private $host;
    /**
     * @var string
     */
    private $businessHost;
    /**
     * @var SiteAdManager
     */
    private $siteAdManager;

    public function __construct(
        SiteVoter $siteVoter,
        Environment $twig,
        SiteAdManager $siteAdManager,
        LegacyUrlGenerator $legacyUrlGenerator,
        string $cacheDir,
        string $host,
        string $businessHost
    ) {
        $this->siteVoter = $siteVoter;
        $this->twig = $twig;
        $this->cacheDir = $cacheDir;
        $this->legacyUrlGenerator = $legacyUrlGenerator;
        $this->host = $host;
        $this->businessHost = $businessHost;
        $this->siteAdManager = $siteAdManager;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $isSuitableChecker = lazy(function () {
            return $this->siteVoter->isMobileVersionSuitable();
        });

        if ($this->needRefRedirect($request, $isSuitableChecker)) {
            $this->redirectToRefHandler($event);
        } else {
            $this->redirectToMobileVersion($event, $isSuitableChecker);
        }
    }

    protected function redirectToMobileVersion(GetResponseEvent $event, callable $isSuitableChecker): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        $routes = Routes::METHOD_MAP;

        if (!isset($routes[$routeName])) {
            return;
        }

        if (!$isSuitableChecker()) {
            return;
        }

        $methodRef = $routes[$routeName];

        if (
            (count($methodRef) === 0)
            || (null === $methodRef[0])
        ) {
            $method = 'route_' . $routeName;
        } else {
            $method = $methodRef[0];
        }

        $fqcnMethod = Routes::class . '::' . $method;
        $url = $fqcnMethod($request->attributes->get('_route_params'));

        if (null === $url) {
            return;
        }

        $event->setResponse(new RedirectResponse($url));
        $event->stopPropagation();
    }

    protected function needRefRedirect(Request $request, callable $isSuitableChecker): bool
    {
        $ref = $request->query->get('ref');

        if (!\is_scalar($ref)) {
            return false;
        }

        $referer = $request->server->get('HTTP_REFERER');

        if (
            isset($referer)
            && \is_string($referer = \parse_url($referer, PHP_URL_HOST))
            && StringUtils::isNotEmpty($referer)
            && (
                ($referer === $this->host)
                || ($referer === $this->businessHost)
            )
        ) {
            return false;
        }

        if (!$isSuitableChecker()) {
            return false;
        }

        $wellKnownCache = require $this->cacheDir . "/aw/wellKnownRoutes.php";
        $requestPath = $request->getPathInfo();

        if (isset($wellKnownCache['static'][$requestPath])) {
            return true;
        }

        if (
            isset($wellKnownCache['dynamic'])
            && \preg_match($wellKnownCache['dynamic'], $requestPath)
        ) {
            return true;
        }

        return false;
    }

    protected function redirectToRefHandler(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        $query = $request->query->all();
        $query['track_click'] = 0;

        $this->siteAdManager->updateClicksForRef((int) $query['ref']);
        $session = $request->getSession();

        if ($session) {
            $session->set(ReferalListener::SESSION_REF_KEY, (int) $query['ref']);
        }

        $link = $this->legacyUrlGenerator->generateAbsoluteUrl($pathInfo . ($query ? '?' . http_build_query($query) : ""));
        $linkTrackerUrl = "https://awardwallet.page.link/?link=" . \urlencode($link) . "&apn=com.itlogy.awardwallet&ibi=com.awardwallet.iphone&isi=388442727&efr=1";
        $content = $this->twig->render('@AwardWalletMain/Layout/mobile_ref_page.html.twig', [
            'link_tracker_url' => $linkTrackerUrl,
            'isFacebookPixel' => HomeController::SUPERFLY_LEAD_ID === (int) $query['ref'],
        ]);
        $event->setResponse(new Response($content));
        $event->stopPropagation();
    }
}
