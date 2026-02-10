<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\CustomHeadersListener;
use AwardWallet\MainBundle\Service\Blog\Blog;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\TravelSummary\Formatter\DesktopFormatter;
use AwardWallet\MainBundle\Service\TravelSummary\PeriodDatesHelper;
use AwardWallet\MainBundle\Service\TravelSummary\TravelSummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @Route("/timeline/summary")
 */
class TravelSummaryController extends AbstractController
{
    private DesktopFormatter $formatter;
    private TravelSummaryService $summaryService;
    private Environment $twig;
    private AwTokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;
    private string $googleKey;
    private RouterInterface $router;
    private PeriodDatesHelper $periodDatesHelper;

    public function __construct(
        DesktopFormatter $desktopFormatter,
        TravelSummaryService $travelSummary,
        Environment $twig,
        AwTokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        RouterInterface $router,
        string $googleKey,
        PeriodDatesHelper $periodDatesHelper
    ) {
        $this->formatter = $desktopFormatter;
        $this->summaryService = $travelSummary;
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->googleKey = $googleKey;
        $this->router = $router;
        $this->periodDatesHelper = $periodDatesHelper;
    }

    /**
     * @Route("/", name="aw_travel_summary", options={"expose"=false})
     * @Route("/blog/", name="aw_travel_summary_blog_user")
     * @Route("/{period}", name="aw_travel_summary_period", requirements={"period" = "\d+"}, options={"expose"=true})
     * @Route("/{period}/{agentId}", name="aw_travel_summary_period_agent", requirements={"period" = "\d+"}, options={"expose"=true})
     * @Security("!is_granted('SITE_BUSINESS_AREA')")
     */
    public function indexAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Blog $blog,
        PageVisitLogger $pageVisitLogger,
        int $period = 1,
        ?int $agentId = null
    ) {
        if ('aw_travel_summary_blog_user' === $request->get('_route')) {
            if (empty($traveler = $blog->decrypt(rawurldecode($request->query->get('traveler', ''))))) {
                throw $this->createNotFoundException();
            }

            [$userIdORrefCode, $blogUserLogin] = explode(',', $traveler);
            $fieldIdentify = is_numeric($userIdORrefCode) ? 'userid' : 'refcode';
            $user = $entityManager->getRepository(Usr::class)->findOneBy([$fieldIdentify => $userIdORrefCode]);
            $availablePeriods = $this->periodDatesHelper->getAvailablePeriods($user);

            if (empty($user)) {
                throw $this->createNotFoundException();
            }

            $isBlogUserSummary = true;
        } elseif (null === $this->tokenStorage->getUser()) {
            return $this->redirectToRoute('aw_login', ['BackTo' => $this->router->generate($request->get('_route'))]);
        } else {
            $user = $this->tokenStorage->getUser();
            $availablePeriods = $this->periodDatesHelper->getAvailablePeriods($user);

            if (!in_array($period, [PeriodDatesHelper::YEAR_TO_DATE, PeriodDatesHelper::LAST_YEAR]) && true !== $user->isAwPlus()) {
                return new RedirectResponse($this->router->generate('aw_travel_summary'));
            } elseif (!in_array($period, array_keys($availablePeriods)) && $user->isAwPlus()) {
                return new RedirectResponse($this->router->generate('aw_travel_summary_period', ['period' => PeriodDatesHelper::YEAR_TO_DATE]));
            }

            $userAgent = null;
            $agents = $this->summaryService->buildAvailableUserAgents($user);

            if ($agentId && isset($agents[$agentId])) {
                $userAgent = $agents[$agentId];
            }
        }

        $response = new Response($this->twig->render(
            "@AwardWalletMain/TravelSummary/index.html.twig",
            [
                'isBlogUserSummary' => $isBlogUserSummary ?? false,
                'blogUserLogin' => isset($isBlogUserSummary) ? $blogUserLogin : null,
                'jsTest1' => isset($isBlogUserSummary) ? 'if (window === top) location.replace(\'/blog/author/' . $blogUserLogin . '\');' : '',
                'jsTest2' => "$('body').css({'overflow': 'hidden'});",
                'googleKey' => $this->googleKey,
                'data' => $this->formatter->format($user, $userAgent ?? null, $period),
                'agents' => $agents ?? [],
                'periods' => $availablePeriods,
                'translations' => [
                    /** @Desc("Historical Travel Summary") */
                    'historicalTravelSummary' => $this->translator->trans(
                        'trips.historical-travel-summary',
                        [],
                        'trips'
                    ),

                    /** @Desc("Location Statistics") */
                    'locationStatistics' => $this->translator->trans('trips.location-statistics', [], 'trips'),
                    /** @Desc("Distance") */
                    'distanceAndTime' => $this->translator->trans('trips.distance', [], 'trips'),
                    /** @Desc("Distance Traveled") */
                    'distanceTraveled' => $this->translator->trans('trips.distance-traveled', [], 'trips'),
                    /** @Desc("Times Around the world") */
                    'timesAroundWorld' => $this->translator->trans('trips.times-around-world', [], 'trips'),

                    /** @Desc("Airports") */
                    'airports' => $this->translator->trans('trips.airports', [], 'trips'),
                    /** @Desc("Countries") */
                    'countries' => $this->translator->trans('trips.countries', [], 'trips'),
                    /** @Desc("Continents") */
                    'continents' => $this->translator->trans('trips.continents', [], 'trips'),
                    /** @Desc("Airlines") */
                    'airlines' => $this->translator->trans('trips.airlines', [], 'trips'),
                    /** @Desc("Towns") */
                    'towns' => $this->translator->trans('trips.towns', [], 'trips'),
                    /** @Desc("Cities") */
                    'cities' => $this->translator->trans('trips.cities', [], 'trips'),

                    /** @Desc("compared to") */
                    'comparedTo' => $this->translator->trans('trips.compared-to', [], 'trips'),
                    /** @Desc("same as") */
                    'sameAs' => $this->translator->trans('same-as', [], 'trips'),
                    /** @Desc("We found no travel data for the selected user and the specified date range. Please import your travel plans by %linkstart%linking your mailbox.%linkend%") */
                    'noData' => $this->translator->trans(
                        'trips.traver-summary-no-data',
                        [
                            '%linkstart%' => "<a href=\"{$this->router->generate('aw_usermailbox_view')}\">",
                            '%linkend%' => '</a>',
                        ],
                        'trips'
                    ),
                    'traveler' => $this->translator->trans('traveler.name'),
                    /** @Desc("Timeframe") */
                    'timeframe' => $this->translator->trans('trips.timeframe', [], 'trips'),
                ],
            ]
        ));

        if (isset($isBlogUserSummary)) {
            $response->headers->set('X-Robots-Tag', CustomHeadersListener::XROBOTSTAG_NOINDEX);
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_TRAVEL_SUMMARY_REPORT);

        return $response;
    }

    /**
     * @Route(
     *     "/data/{period}/{agentId}",
     *     name="aw_travel_summary_data",
     *     requirements={"period" = "\d+"},
     *     options={"expose"=true}
     * )
     * @Security("is_granted('ROLE_USER') && !is_granted('SITE_BUSINESS_AREA')")
     */
    public function dataAction(Request $request, int $period, ?int $agentId = null): JsonResponse
    {
        $user = $this->tokenStorage->getUser();
        $availablePeriods = $this->periodDatesHelper->getAvailablePeriods($user);

        if (!in_array($period, [PeriodDatesHelper::YEAR_TO_DATE, PeriodDatesHelper::LAST_YEAR]) && true !== $user->isAwPlus()) {
            throw new AccessDeniedHttpException();
        } elseif (!in_array($period, array_keys($availablePeriods)) && $user->isAwPlus()) {
            throw new BadRequestHttpException('Invalid period value');
        }

        $userAgent = null;
        $agents = $this->summaryService->buildAvailableUserAgents($user);

        if ($agentId && isset($agents[$agentId])) {
            $userAgent = $agents[$agentId];
        }

        return new JsonResponse($this->formatter->format($user, $userAgent, $period));
    }
}
