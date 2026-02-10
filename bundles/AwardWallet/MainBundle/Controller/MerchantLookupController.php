<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\Listeners\CustomHeadersListener;
use AwardWallet\MainBundle\Security\Captcha\Resolver\DesktopCaptchaResolver;
use AwardWallet\MainBundle\Service\AccountHistory\OfferQuery;
use AwardWallet\MainBundle\Service\MerchantLookupHandler;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class MerchantLookupController
{
    private AuthorizationCheckerInterface $checker;
    private MerchantLookupHandler $merchantLookupHandler;
    private Environment $twig;
    private DesktopCaptchaResolver $captchaResolver;

    public function __construct(
        AuthorizationCheckerInterface $checker,
        MerchantLookupHandler $merchantLookupHandlerReplica,
        DesktopCaptchaResolver $captchaResolver,
        Environment $twig
    ) {
        $this->checker = $checker;
        $this->merchantLookupHandler = $merchantLookupHandlerReplica;
        $this->twig = $twig;
        $this->captchaResolver = $captchaResolver;
    }

    /**
     * @Route("/merchants",
     *     name="aw_merchant_lookup",
     *     defaults={"_canonical" = "aw_merchant_lookup_locale", "_alternate" = "aw_merchant_lookup_locale"},
     *     options={"expose"=true}
     * )
     * @Route("/merchants/{merchantName}",
     *     name="aw_merchant_lookup_preload",
     *     defaults={"_canonical" = "aw_merchant_lookup_preload_locale", "_alternate" = "aw_merchant_lookup_preload_locale"},
     *     requirements={"merchantName"=".+"},
     *     options={"expose"=true}
     * )
     * @Route(
     *     "/{_locale}/merchants",
     *     name="aw_merchant_lookup_locale",
     *     requirements={"_locale"="%route_locales%"},
     *     defaults={"_locale"="%locale%", "_canonical" = "aw_merchant_lookup_locale", "_alternate" = "aw_merchant_lookup_locale"}
     * )
     * @Route("/{_locale}/merchants/{merchantName}",
     *     name="aw_merchant_lookup_preload_locale",
     *     requirements={
     *         "_locale"="%route_locales%",
     *         "merchantName"=".+"
     *     },
     *     defaults={
     *         "_locale"="%locale%", "_canonical" = "aw_merchant_lookup_preload_locale", "_alternate" = "aw_merchant_lookup_preload_locale"
     *     }
     * )
     * @return Response
     */
    public function indexAction(Request $request, PageVisitLogger $pageVisitLogger, $merchantName = null)
    {
        $result = [
            'captcha_provider' => $this->captchaResolver->resolve($request),
            'isAuth' => $this->checker->isGranted('ROLE_USER'),
            'showPercent' => null !== $request->get('showPercent'),
        ];

        // $session = $request->getSession();
        //
        // if ($session) {
        //     $session->save();
        // }

        if (!empty($merchantName)) {
            $data = $this->merchantLookupHandler->handleExactMatchRequest($request, $merchantName, OfferQuery::SOURCE_WEB_MCC);

            if (empty($data)) {
                throw new NotFoundHttpException();
            }
            $data['showPercent'] = $result['showPercent'];
            $result['merchantData'] = $data;
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_MERCHANT_LOOKUP_TOOL);

        return new Response($this->twig->render('@AwardWalletMain/MerchantLookup/index.html.twig', $result));
    }

    /**
     * @Route(
     *     "/api/merchants/{merchantName}",
     *     requirements={"merchantName"=".+"},
     *     methods={"GET"}
     * )
     * @return Response
     */
    public function apiMerchantInfoByNameAction(Request $request, $merchantName = null)
    {
        $data = $this->merchantLookupHandler->handleExactMatchRequest($request, $merchantName, OfferQuery::SOURCE_WEB_MCC);

        if (empty($data)) {
            throw new NotFoundHttpException();
        }

        $response = new JsonResponse($data);
        $response->headers->set('X-Robots-Tag', CustomHeadersListener::XROBOTSTAG_NOINDEX);

        return $response;
    }

    /**
     * no CSRF protection on this method, because we expose this method to potential api clients, as test bed.
     *
     * @Route("api/merchants/data", methods={"POST"}, name="aw_merchant_lookup_data", options={"expose"=true})
     * @return array|JsonResponse
     */
    public function getMerchantData(Request $request)
    {
        $session = $request->getSession();

        if ($session && $session->isStarted()) {
            $session->save();
        }

        return $this->merchantLookupHandler->handleMerchantDataRequest($request);
    }
}
