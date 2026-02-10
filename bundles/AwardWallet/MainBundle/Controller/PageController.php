<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\Statistics;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PageController extends AbstractController
{
    private RouterInterface $router;
    private Statistics $statistics;
    private ProviderRepository $providerRepository;
    private GlobalVariables $globalVariables;
    private PageVisitLogger $pageVisitLogger;

    public function __construct(
        RouterInterface $router,
        Statistics $statistics,
        ProviderRepository $providerRepository,
        GlobalVariables $globalVariables,
        PageVisitLogger $pageVisitLogger
    ) {
        $this->router = $router;
        $this->statistics = $statistics;
        $this->providerRepository = $providerRepository;
        $this->globalVariables = $globalVariables;
        $this->pageVisitLogger = $pageVisitLogger;
    }

    /**
     * @Route("/page/{page}",
     *      name="aw_page_index",
     *      requirements={"page" = "about|privacy|terms|partners|services|ccpa"},
     *      defaults={"_canonical" = "aw_page_index_locale", "_alternate" = "aw_page_index_locale"}
     * )
     * @Route("/{_locale}/page/{page}",
     *      name="aw_page_index_locale",
     *      defaults={"_locale"="en", "_canonical" = "aw_page_index_locale", "_alternate" = "aw_page_index_locale"},
     *      requirements={"page" = "about|privacy|terms|partners|services|ccpa", "_locale" = "%route_locales%"}
     * )
     */
    public function indexAction(Request $request, $page, $_locale = 'en')
    {
        return call_user_func_array([$this, $page . 'Action'], [$request, $_locale]);
    }

    /**
     * @Route("/about", name="aw_page_about_redirect")
     */
    public function aboutRedirectAction(Request $request)
    {
        return $this->redirectToRoute("aw_page_index", ['page' => 'about']);
    }

    public function aboutAction(Request $request)
    {
        $this->pageVisitLogger->log(PageVisitLogger::PAGE_ABOUT_US);

        return $this->render('@AwardWalletMain/Page/about.html.twig',
            $this->statistics->getOverallStat($request)
        );
    }

    public function privacyAction()
    {
        $this->pageVisitLogger->log(PageVisitLogger::PAGE_PRIVACY_NOTICE);

        return $this->render('@AwardWalletMain/Page/privacy.html.twig', ['content' => 'privacy']);
    }

    public function ccpaAction()
    {
        return $this->render('@AwardWalletMain/Page/privacy.html.twig', ['content' => 'ccpa']);
    }

    public function termsAction()
    {
        $this->pageVisitLogger->log(PageVisitLogger::PAGE_TERMS_OF_USE);

        return $this->render('@AwardWalletMain/Page/terms.html.twig');
    }

    public function partnersAction()
    {
        return $this->render('@AwardWalletMain/Page/partners.html.twig');
    }

    public function servicesAction()
    {
        $lps = $this->providerRepository->getLPCount($this->globalVariables->getRoot());

        return $this->render('@AwardWalletMain/Page/services.html.twig', [
            'lps' => $lps,
        ]);
    }

    /**
     * @Route("/api/", name="aw_api_doc_main")
     */
    public function apiAction()
    {
        return $this->redirect($this->router->generate('aw_api_doc', ['item' => 'main']), 301);
    }

    /**
     * @Route("/api/{item}", name="aw_api_doc", requirements={"item"="main|account|loyalty|email"}, defaults={"_canonical"="aw_api_doc", "_withoutLocale"=true})
     * @Route("/{_locale}/api/{item}", name="aw_api_doc_locale", defaults={"item"="main", "_canonical"="aw_api_doc_locale", "_alternate"="aw_api_doc_locale"}, requirements={"item"="main|account|loyalty|email", "_locale"="%route_locales%"})
     */
    public function apiActionDocItem($item)
    {
        $item = strtolower($item);

        $documentsMapping = [
            'account' => [
                'page' => 'account_access',
                'title' => "Awardwallet Account Access API",
                'description' => "The AwardWallet Account Access API provides access to the loyalty account data that belongs to AwardWallet users.",
            ],
            'loyalty' => [
                'page' => 'loyalty',
                'title' => "Awardwallet Web Parsing API",
                'description' => "AwardWallet Web Parsing API supports retrieval of the following information from online loyalty accounts: loyalty information (e.g. account balance, expiration, elite level, etc.), travel itineraries listed under user’s profile, account activity history.",
            ],
            'email' => [
                'page' => 'email_parsing',
                'title' => "Email Parsing API Documentation - Parse Travel Data From Emails",
                'description' => "AwardWallet API documentation includes the following powerful APIs designed for our business partners: Email Parsing API, Web Parsing API, and Account Access API",
            ],
        ];

        if ($item === 'main') {
            $this->pageVisitLogger->log(PageVisitLogger::PAGE_APIS);
        }

        if (isset($documentsMapping[$item])) {
            return $this->render(
                '@AwardWalletMain/ApiDocumentation/api-doc-item.html.twig',
                ['item' => $documentsMapping[$item], 'key' => $item]
            );
        } else {
            return $this->render('@AwardWalletMain/Page/api.html.twig', ['key' => $item]);
        }
    }

    /**
     * @Route("/email-parsing-api", name="aw_api_email_parsing", defaults={"_canonical"="aw_api_email_parsing_locale"})
     * @Route("/{_locale}/email-parsing-api", name="aw_api_email_parsing_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_api_email_parsing_locale"})
     * @Template("@AwardWalletMain/Page/apiEmailParsing.html.twig")
     */
    public function apiEmailParsingAction()
    {
        return [];
    }

    /**
     * @Route("/expense-management-api", name="aw_api_expense_management", defaults={"_canonical"="aw_api_expense_management_locale"})
     * @Route("/{_locale}/expense-management-api", name="aw_api_expense_management_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_api_expense_management_locale"})
     * @Template("@AwardWalletMain/Page/apiExpenseManagement.html.twig")
     */
    public function apiExpenseManagement()
    {
        return [];
    }

    /**
     * @Route("/itinerary-management-api", name="aw_api_itinerary_management", defaults={"_canonical"="aw_api_itinerary_management_locale"})
     * @Route("/{_locale}/itinerary-management-api", name="aw_api_itinerary_management_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_api_itinerary_management_locale"})
     * @Template("@AwardWalletMain/Page/apiItineraryManagement.html.twig")
     */
    public function apiItineraryManagement()
    {
        return [];
    }

    /**
     * @Route("/green-startups-api", name="aw_api_green_startups", defaults={"_canonical"="aw_api_green_startups_locale"})
     * @Route("/{_locale}/green-startups-api", name="aw_api_green_startups_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_api_green_startups_locale"})
     * @Template("@AwardWalletMain/Page/apiGreenStartups.html.twig")
     */
    public function apiGreenStartups()
    {
        return [];
    }

    /**
     * @Route("/flight-delay-compensation-api", name="aw_api_flight_delay_compensation", defaults={"_canonical"="aw_api_flight_delay_compensation_locale"})
     * @Route("/{_locale}/flight-delay-compensation-api", name="aw_api_flight_delay_compensation_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_api_flight_delay_compensation_locale"})
     * @Template("@AwardWalletMain/Page/apiFlightDelayCompensation.html.twig")
     */
    public function apiFlightDelayCompensation()
    {
        return [];
    }

    /**
     * @see #11607
     * @Route("/{_locale}/set_locale",
     *      name="aw_set_locale",
     *      defaults={"_locale"="en"},
     *      requirements={"_locale" = "%route_locales%"}
     * )
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function setLocaleAction(
        Request $request,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $entityManager
    ) {
        $redirect = $request->get('backTo');

        if (empty($redirect)) {
            $redirect = $request->headers->get('referer');
        }

        if (empty($redirect)) {
            throw $this->createAccessDeniedException();
        }

        $host = parse_url($redirect, PHP_URL_HOST);

        if (!$host || $host != $request->getHost()) {
            throw $this->createAccessDeniedException();
        }

        $user = $tokenStorage->getUser();

        if ($user && $request->getLocale() != $user->getLanguage()) {
            if ($authorizationChecker->isGranted('USER_IMPERSONATED')) {
                $request->getSession()->set('locale', $request->getLocale());
            } else {
                $user->setLanguage($request->getLocale());
                $entityManager->flush();
            }
        }

        return $this->redirect($redirect);
    }
}
