<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\DateRangeInterface;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\NoForeignFeesCardsQuery;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * Class TimelineController.
 */
class TimelineController extends AbstractController implements TranslationContainerInterface
{
    use JsonTrait;

    private Manager $manager;
    private TravelPlanController $planController;
    private OperatedByResolver $operatedByResolver;
    private EmailScannerApi $scannerApi;
    private EntityManagerInterface $entityManager;
    private Environment $twig;
    private RouterInterface $router;
    private AwTokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private UseragentRepository $useragentRepository;
    private UserMailboxCounter $mailboxCounter;

    public function __construct(
        Manager $manager,
        TravelPlanController $planController,
        OperatedByResolver $operatedByResolver,
        EmailScannerApi $scannerApi,
        EntityManagerInterface $entityManager,
        Environment $twig,
        RouterInterface $router,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        UseragentRepository $useragentRepository,
        UserMailboxCounter $mailboxCounter
    ) {
        $this->manager = $manager;
        $this->planController = $planController;
        $this->operatedByResolver = $operatedByResolver;
        $this->scannerApi = $scannerApi;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->useragentRepository = $useragentRepository;
        $this->mailboxCounter = $mailboxCounter;
    }

    /**
     * @Security("is_granted('ROLE_STAFF')")
     * @Route("/new-timeline/", name="aw_newtimeline")
     * @Template("@AwardWalletMain/Timeline/indexReact.html.twig")
     */
    public function newIndexAction(Request $request)
    {
        $this->twig->addGlobal('webpack', true);
        $access = true;

        if ($agentId = $request->get('agent')) {
            $agent = $this->useragentRepository->find($agentId);

            $access = $this->authorizationChecker->isGranted('EDIT_TIMELINE', $agent);
        }

        return [
            'isShowDeleted' => $access,
        ];
    }

    /**
     * @Route("/timeline/", name="aw_timeline", options={"expose"=true})
     * @Route("/timeline/{agentId}", name="aw_timeline_html5", requirements={"agentId" = "\d+"}, options={"expose"=true})
     * @Route("/timeline/{agentId}/itineraries/{itIds}", name="aw_timeline_html5_agent_itineraries", requirements={"agentId" = "\d+"}, options={"expose"=true})
     * @Route("/timeline/itineraries/{itIds}", name="aw_timeline_html5_itineraries", options={"expose"=false})
     * @Template("@AwardWalletMain/Timeline/index.html.twig")
     */
    public function indexAction(Request $request, PageVisitLogger $pageVisitLogger, string $vapidPublicKey, string $webpushIdParam)
    {
        $this->twig->addGlobal('webpack', true);

        $access = true;

        if ($agentId = $request->get('agent')) {
            $agent = $this->useragentRepository->find($agentId);

            $access = $this->authorizationChecker->isGranted('EDIT_TIMELINE', $agent);
        }

        $tripitHasAccessToken = false;
        $authorized = $this->tokenStorage->getUser();

        if ($authorized) {
            $tripitUser = new TripitUser($authorized, $this->entityManager);
            $tripitHasAccessToken = $tripitUser->hasAccessTokens();
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_TRIPS);

        return [
            'isShowDeleted' => $access,
            'vapid_public_key' => $vapidPublicKey,
            'webpush_id' => $webpushIdParam,
            'linkedBoxes' => $authorized ? $this->mailboxCounter->total($this->tokenStorage->getUser()->getId()) : 0,
            'tripitHasAccessToken' => $tripitHasAccessToken,
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/timeline/show/trip/{tripId}", name="aw_timeline_show_trip", options={"expose"=true}, requirements={"tripId" = "\d+"})
     * @Route("/timeline/show/{segmentId}", name="aw_timeline_show", options={"expose"=true}, requirements={"segmentId" = "\S{1,3}\.\d+"})
     */
    public function showSegmentAction(?string $segmentId = null, ?int $tripId = null)
    {
        try {
            /** @var DateRangeInterface $itinerary */
            if (isset($tripId)) {
                $itinerary = $this->getDoctrine()
                    ->getRepository(\AwardWallet\MainBundle\Entity\Trip::class)
                    ->find($tripId);
            } else {
                $itinerary = $this->manager->getEntityByItCode($segmentId);
            }

            if (!$itinerary) {
                return new RedirectResponse($this->router->generate('aw_timeline'));
            }

            if ($itinerary instanceof Tripsegment) {
                $userAgent = $itinerary->getTripid()->getOwner()->getUseragentForUser($this->tokenStorage->getBusinessUser());
            } else {
                $userAgent = $itinerary->getOwner()->getUseragentForUser($this->tokenStorage->getBusinessUser());
            }

            if (null !== $userAgent) {
                $timelineRoute = $this->router->generate('aw_timeline_html5', ['agentId' => $userAgent->getUseragentid()]);
            } else {
                $timelineRoute = $this->router->generate('aw_timeline');
            }

            $startDate = (clone $itinerary->getStartDate())->modify('-3 day')->getTimestamp();

            if (isset($tripId)) {
                /** @var Trip $itinerary */
                /** @var Tripsegment $segment */
                $segment = $itinerary->getSegments()->first();
                $openSegment = sprintf(
                    '%s.%s',
                    $itinerary->getKind(),
                    $segment->getTripsegmentid()
                );
            } else {
                $openSegment = $segmentId;
            }

            return new RedirectResponse(
                $timelineRoute
                . "?openSegment={$openSegment}"
                . "&openSegmentDate={$startDate}"
                . ($itinerary->getHidden() ? "&showDeleted=1" : "")
            );
        } catch (\InvalidArgumentException $e) {
            return new RedirectResponse($this->router->generate('aw_timeline'));
        }
    }

    /**
     * @Route("/timeline/print/", name="aw_timeline_print", options={"expose"=true})
     * @Route("/timeline/print/shared-plan/{params}", name="aw_timeline_print_shared_plant", options={"expose"=false})
     * @Route("/timeline/print/{params}", name="aw_timeline_print_html5", options={"expose"=false})
     */
    public function printAction()
    {
        $this->twig->addGlobal('webpack', true);

        return $this->render('@AwardWalletMain/Timeline/print.html.twig');
    }

    /**
     * possible query options:
     *        before=<unixtime>    - will return 50 segments before that date
     *        showDeleted=1        - will show hidden segments.
     *
     * @Security("is_granted('ROLE_USER')")
     * @Route("/timeline/data/{agentId}", name="aw_timeline_data", options={"expose"=true}, defaults={"agentId" = null})
     */
    public function dataAction(
        Request $request,
        NoForeignFeesCardsQuery $noForeignFeesCardsQuery,
        $agentId = null,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        string $requiresChannel,
        string $host
    ): JsonResponse {
        $user = $this->tokenStorage->getBusinessUser();
        $agent = null;

        if ($agentId === 'my') {
            $agentId = null;
        }

        $travelPlanAccess = $canAdd = true;

        if (!empty($agentId)) {
            /** @var Useragent $agent */
            $agent = $this->useragentRepository->find($agentId);

            if (empty($agent)) {
                throw new AccessDeniedException('Unknown user agent');
            }

            if ($agent->getAgentid()->getId() !== $user->getId() || !$agent->isFamilyMember()) {
                if ($agent->isFamilyMember()) {
                    $timelineShare = $user->getTimelineShareWith($agent->getAgentid(), $agent);
                } else {
                    $timelineShare = $user->getTimelineShareWith($agent->getClientid());
                }

                if (
                    $user->isBusiness()
                    && empty($timelineShare)
                ) {
                    /** @var Useragent $reverseUserAgent */
                    $reverseUserAgent = $agent->getClientid()->getConnectionWith($user);

                    return $this->jsonResponse([
                        "error" => "Access to the travel timeline that belongs to {$agent->getFullName()} has not been granted to you. Feel free to request it by clicking OK below and requesting it from the user sharing page.",
                        "agentId" => $reverseUserAgent->getUseragentid(),
                    ], 406);
                }

                if (empty($timelineShare)) {
                    throw new AccessDeniedException('Unknown user agent');
                }
                $travelPlanAccess = (bool) $timelineShare->getUserAgent()->getTripAccessLevel();
                $canAdd = $travelPlanAccess;
            }
        }

        $queryOptions = (new QueryOptions())
            ->setUser($user)
            ->setWithDetails(true)
            ->setFormat(ItemFormatterInterface::DESKTOP);

        if (null !== $agent) {
            $queryOptions->setUserAgent($agent);
        }

        if ($request->query->has('before')) {
            $queryOptions->setEndDate(new \DateTime('@' . intval($request->query->get('before'))));
            $queryOptions->setMaxSegments(50);
        } elseif ($request->query->has('after')) {
            $queryOptions->setStartDate(new \DateTime('@' . intval($request->query->get('after'))));
        } else {
            $queryOptions
                ->setFuture(true)
                ->setMaxFutureSegments(Manager::MAX_FUTURE_SEGMENTS);
        }

        $queryOptions->setShowDeleted($request->query->get('showDeleted') == '1');

        // Отключаем планы в Ie8
        //        if(preg_match('/(?i)msie [5-8]/i', $request->headers->get('user-agent')))
        //            $queryOptions->setShowPlans(false);

        if (empty($agent)) {
            $email = $user->getItineraryForwardingEmail();
            $fullName = $user->getFullName();
        } else {
            $email = $agent->getItineraryForwardingEmail();
            $fullName = $agent->getFullName();
        }

        $agents = [];

        if (!$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            $totals = $this->manager->getTotals($user);

            $agents[] = [
                'id' => 'my',
                'name' => $user->getFullName(),
                'sharable' => $agent ? true : false,
                'count' => $totals['']['count'],
                'family' => false,
                'mailboxes' => $this->mailboxCounter->onlyMy($user->getId()),
            ];

            //            $current = $this->getCurrentUser()->getUserid();

            $connections = $user->getConnections()->toArray();

            /** @var Useragent $ua */
            foreach ($connections as $ua) {
                if ($ua->isFamilyMember() || ($ua->getClientid() && !$ua->getClientid()->isBusiness() && $ua->isApproved())) {
                    $haveAccess = isset($totals[$ua->getId()]) && ($ua->getTripAccessLevel() > 0 || $ua->isFamilyMember());
                    $agents[] = [
                        'id' => $ua->getId(),
                        'name' => $ua->isFamilyMember() ? $ua->getFullName() : $ua->getClientid()->getFullName(),
                        'sharable' => $ua->getId() != $agentId && $haveAccess,
                        'count' => isset($totals[$ua->getId()]) ? $totals[$ua->getId()]['count'] : 0,
                        'family' => $ua->isFamilyMember(),
                        'mailboxes' => $ua->isFamilyMember()
                            ? $this->mailboxCounter->byFamilyMember($ua->getAgentid()->getId(), $ua->getId())
                            : $this->mailboxCounter->onlyMy($ua->getClientid()->getId()),
                    ];
                }

                /** @var TimelineShare $timelines */
                foreach ($ua->getSharedTimelines() as $timelines) {
                    if (($agent = $timelines->getFamilyMember()) && isset($totals[$agent->getId()])) {
                        $agents[] = [
                            'id' => $agent->getId(),
                            'name' => $agent->getFullName(),
                            'sharable' => $ua->getTripAccessLevel() > 0,
                            'count' => $totals[$agent->getId()]['count'],
                            'family' => $agent->isFamilyMember(),
                            'mailboxes' => $this->mailboxCounter->byFamilyMember($agent->getAgentid()->getId(), $ua->getId()),
                        ];
                    }
                }
            }
        }

        $data = $this->manager->query($queryOptions);
        $plansToRemove = [];

        foreach ($data as $index => $value) {
            if (\is_null($agent) || $this->authorizationChecker->isGranted('EDIT_TIMELINE', $agent)) {
                if ($value['type'] == 'planStart' && (isset($data[$index + 1]) && $data[$index + 1]['type'] == 'planEnd')) {
                    $plansToRemove[] = [$index, $value['planId']];
                }
            }

            if ((\count($data) - \count($plansToRemove) * 2) > 0) {
                if ($value['type'] == 'date' && $travelPlanAccess == false) {
                    $data[$index]['createPlan'] = false;
                }

                if ($value['type'] == 'planStart') {
                    if (!empty($value['shareCode'])) {
                        $data[$index]['shareUrl'] = $requiresChannel . "://" . $host
                            . $this->router->generate('aw_travelplan_shared', ['shareCode' => $value['shareCode']]);
                    }
                } else {
                    if (!empty($value['details']) && is_array($value['details']) && !empty($value['details']['shareCode'])) {
                        $data[$index]['shareUrl'] = $requiresChannel . "://" . $host
                            . $this->router->generate('aw_timeline_shared', ['shareCode' => $value['details']['shareCode']]);
                    }
                }

                //            if (!empty($value['details']) && is_array($value['details']) && !empty($value['details']['canEdit'])) {
                //                $data[$index]['details']['canEdit'] = empty($timelineShare);
                //            }
                if (!empty($value['details']) && is_array($value['details']) && !empty($value['details']['refreshLink'])) {
                    if (!empty($timelineShare) && strpos('retrieve', $value['details']['refreshLink']) !== false) {
                        unset($data[$index]['details']['refreshLink']);
                    }
                }
            }
        }

        foreach ($plansToRemove as [$plansStartIndex, $planId]) {
            $logger->info(
                sprintf(
                    'removing plan #%d from timeline, userId: %d',
                    $planId,
                    $user->getId()
                ), [
                    'startPlan' => isset($data[$plansStartIndex]) ? json_encode($data[$plansStartIndex]) : null,
                    'endPlan' => isset($data[$plansStartIndex + 1]) ? json_encode($data[$plansStartIndex + 1]) : null,
                ]
            );

            unset($data[$plansStartIndex]); // remove plan start
            unset($data[$plansStartIndex + 1]); // remove plan end
            //            refs #24572 bug with deleting plans
            //            $this->planController->deleteAction($planId, $request);
        }

        $cards = false !== array_search(1, array_column($data, 'isShowNoForeignFeesCards'))
            ? $noForeignFeesCardsQuery->getCards($user->getId())
            : [];

        return new JsonResponse([
            'segments' => \array_values($data),
            'forwardingEmail' => $email,
            'fullName' => htmlspecialchars($fullName),
            'agents' => $agents,
            'canAdd' => $canAdd,
            'noForeignFeesCards' => $cards,
            'options' => [
                'reservation' => [
                    'collapseFieldProperties' => [
                        $translator->trans('itineraries.cancellation-policy', [], 'trips'),
                        $translator->trans('itineraries.reservation.room-type-description', [], 'trips'),
                        $translator->trans('itineraries.comment', [], 'trips'),
                    ],
                ],
            ],
        ]);
    }

    /**
     * @Route("/timeline/data/shared/{shareCode}", name="aw_timeline_data_shared", options={"expose"=true})
     */
    public function sharedDataAction($shareCode, TranslatorInterface $translator)
    {
        [$segments, $email, $fullName] = $this->getSharedSegmentData($shareCode);

        return $this->jsonResponse(
            [
                'segments' => $segments,
                "fullName" => $fullName,
                'options' => [
                    'reservation' => [
                        'collapseFieldProperties' => [
                            $translator->trans('itineraries.cancellation-policy', [], 'trips'),
                            $translator->trans('itineraries.reservation.room-type-description', [], 'trips'),
                            $translator->trans('itineraries.comment', [], 'trips'),
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @Route("/timeline/data/shared-plan/{shareCode}", name="aw_travelplan_data_shared", options={"expose"=true})
     */
    public function sharedPlanDataAction($shareCode)
    {
        [$segments, $email, $fullName] = $this->getSharedSegmentData($shareCode, true);

        return $this->jsonResponse(
            [
                'segments' => $segments,
                "forwardingEmail" => $email,
                "fullName" => $fullName,
            ]
        );
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/timeline/data/itineraries/{itIds}", name="aw_timeline_data_segments", requirements={"itIds" = "([A-Z]{1,2}\.\d+)(\,([A-Z]{1,2}\.\d+))*"}, options={"expose"=true})
     */
    public function itinerariesDataAction(Request $request, $itIds)
    {
        [$segments, $email, $fullName] = $this->getItinerariesData($itIds, $request->query->get('agentId'));

        return $this->jsonResponse(
            [
                'segments' => $segments,
                "forwardingEmail" => false,
                "fullName" => $fullName,
            ]
        );
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/timeline/delete/{segmentId}", name="aw_timeline_delete", methods={"POST"}, options={"expose"=true})
     */
    public function deleteAction($segmentId, Request $request)
    {
        return $this->jsonResponse($this->manager->deleteSegment($segmentId, $request->query->get("undelete") == 'true'));
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/timeline/confirm-changes/{segmentId}", name="aw_timeline_confirm_changes", methods={"POST"}, options={"expose"=true})
     */
    public function confirmChangesAction($segmentId, Request $request)
    {
        return $this->jsonResponse($this->manager->confirmSegmentChanges($segmentId));
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/timeline/hide-ai-warning/{segmentId}", name="aw_timeline_hide_ai_warning", methods={"POST"}, options={"expose"=true})
     */
    public function hideAIWarningAction($segmentId)
    {
        return $this->jsonResponse($this->manager->hideAIWarning($segmentId));
    }

    /**
     * @Route("/timeline/shared/{shareCode}", name="aw_timeline_shared", options={"expose"=true})
     * @Route("/{_locale}/timeline/shared/{shareCode}", name="aw_timeline_shared_locale", requirements={"_locale" = "%route_locales%"}, options={"expose"=true})
     */
    public function sharedAction($shareCode)
    {
        $this->twig->addGlobal('webpack', true);
        [$segments, $email, $fullName] = $this->getSharedSegmentData($shareCode);

        if (empty($segments)) {
            throw new NotFoundHttpException();
        }

        $mapUrl = '';
        $codes = [];

        foreach ($segments as $segment) {
            if ($segment['type'] == 'segment' && !empty($segment['map'])) {
                $points = $segment['map']['points'];

                if (count($points) > 1) {
                    $codes[] = implode('-', $points);
                } else {
                    $codes[] = $points[0];
                }
            }
        }

        if (count($codes)) {
            $mapUrl = urldecode($this->router->generate('aw_flight_map', ['code' => implode(',', $codes), 'size' => '350x350'], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return $this->render('@AwardWalletMain/Timeline/shared.html.twig', [
            'data' => [
                'segments' => $segments,
                "forwardingEmail" => $email,
                "fullName" => $fullName,
            ],
            'map' => $mapUrl,
        ]);
    }

    /**
     * @Route("/timeline/move/{itCode}/{agent}", name="aw_timeline_move", methods={"POST"}, options={"expose"=true}, defaults={"agent" = null}, requirements={"itCode" = "\S{1,3}\.\d+"})
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id" = "agent"})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     */
    public function moveAction(?Useragent $agent = null, $itCode, Request $request)
    {
        $this->manager->moveItinerary($itCode, $agent, $request->request->get('copy') == "true");

        return $this->successJsonResponse();
    }

    /**
     * @Route("/timeline/shared-plan/{shareCode}", name="aw_travelplan_shared", options={"expose"=true})
     * @Route("/{_locale}/timeline/shared-plan/{shareCode}", name="aw_travelplan_shared_locale", requirements={"_locale" = "%route_locales%"}, options={"expose"=true})
     */
    public function sharedPlanAction(Request $request, $shareCode)
    {
        $this->twig->addGlobal('webpack', true);

        $iframe = $request->get('iframe', 0) == 1;
        [$segments, $email, $fullName] = $this->getSharedSegmentData($shareCode, true);

        if (empty($segments)) {
            throw new NotFoundHttpException();
        }

        $mapUrl = '';
        $codes = [];

        foreach ($segments as $segment) {
            if ($segment['type'] == 'segment' && !empty($segment['map'])) {
                $points = $segment['map']['points'];

                if (count($points) > 1) {
                    $codes[] = implode('-', $points);
                } else {
                    $codes[] = $points[0];
                }
            }
        }

        if (count($codes)) {
            $mapUrl = urldecode($this->router->generate('aw_flight_map', ['code' => implode(',', $codes), 'size' => '350x350'], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        $params = [
            'data' => [
                'segments' => $iframe ? it($segments)->filter(function (array $segment) {
                    return !in_array($segment['type'], ['planStart', 'planEnd']);
                })->map(function (array $segment) {
                    return array_merge($segment, ['opened' => true]);
                })->toArray() : $segments,
                "forwardingEmail" => $email,
                "fullName" => $fullName,
            ],
            'map' => $mapUrl,
        ];

        if ($iframe) {
            return $this->render('@AwardWalletMain/Timeline/sharedPlanIframe.html.twig', $params);
        }

        return $this->render('@AwardWalletMain/Timeline/shared.html.twig', $params);
    }

    /**
     * @return array<Message>
     */
    public static function getTranslationMessages(): array
    {
        return [
            (new Message('marketing_airline', 'trips'))->setDesc('Marketing Airline'),
            (new Message('operating_airline', 'trips'))->setDesc('Operating Airline'),
            (new Message('issuing_airline', 'trips'))->setDesc('Issuing Airline'),
        ];
    }

    /**
     * @return array
     */
    protected function getSharedSegmentData($shareCode, $withPlans = false)
    {
        $queryOptions = QueryOptions::createDesktop()->setWithDetails(true)->setShowPlans($withPlans)->setOperatedByResolver($this->operatedByResolver);
        $segments = $this->manager->queryByShareCode($shareCode, $queryOptions);
        $agent = $queryOptions->getUserAgent();
        $user = $queryOptions->getUser();

        if (empty($agent) && !empty($user)) {
            $email = $user->getItineraryForwardingEmail();
            $fullName = $user->getFullName();
        } elseif (!empty($agent)) {
            $email = $agent->getItineraryForwardingEmail();
            $fullName = $agent->getFullName();
        } else {
            $decoded = $this->getEmailAndFullNameByShareCode($shareCode);

            if (empty($decoded)) {
                $email = null;
                $fullName = null;
            } else {
                [$email, $fullName] = $decoded;
            }
        }

        return [$segments, $email, $fullName];
    }

    /**
     * @param Useragent|null $ua
     * @return array
     * @throws \Exception
     */
    protected function getItinerariesData($itIds, $ua = null)
    {
        $queryOptions = QueryOptions::createDesktop()
            ->setWithDetails(true)
            ->setShowPlans(false)
            ->setShowDeleted(true)
            ->setOperatedByResolver($this->operatedByResolver);

        if ($ua && $ua != 'my') {
            /** @var Useragent $agent */
            $agent = $this->entityManager->getRepository(Useragent::class)->find($ua);
            $this->denyAccessUnlessGranted('EDIT_TIMELINE', $agent);
            $owner = OwnerRepository::getByUseragent($agent);
            $queryOptions->setUserAgent($owner->getFamilyMember());
        }

        $segments = $this->manager->queryByItineraries(explode(",", $itIds), $queryOptions);

        if (isset($agent) && $queryOptions->getUserAgent() === null) {
            // correct link on "Show on timeline" button
            $segments = $this->correctUserAgentForSharedTimeline($segments, $agent);
        }

        $agent = $queryOptions->getUserAgent();
        $user = $queryOptions->getUser();

        $email = $fullName = '';

        if (empty($agent) && !empty($user)) {
            $email = $user->getItineraryForwardingEmail();
            $fullName = $user->getFullName();
        } elseif (!empty($agent)) {
            $email = $agent->getItineraryForwardingEmail();
            $fullName = $agent->getFullName();
        } elseif ($this->tokenStorage->getBusinessUser()) {
            $email = $this->tokenStorage->getBusinessUser()->getItineraryForwardingEmail();
            $fullName = $this->tokenStorage->getBusinessUser()->getFullName();
        }

        return [$segments, $email, $fullName];
    }

    private function getEmailAndFullNameByShareCode($shareCode)
    {
        [$kind, $id, $code] = $this->manager->decodeShareCode($shareCode);

        if ($kind === 'Travelplan') {
            /** @var Plan $travelPlan */
            $travelPlan = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Plan::class)->find($id);

            if (empty($travelPlan) || $travelPlan->getShareCode() != $code) {
                return [];
            }

            if (null !== $travelPlan->getUserAgent()) {
                return [$travelPlan->getUserAgent()->getEmail(), $travelPlan->getUserAgent()->getFullname()];
            } else {
                return [$travelPlan->getUser()->getEmail(), $travelPlan->getUser()->getFullname()];
            }
        } else {
            if (!isset(Itinerary::$table[$kind])) {
                return [];
            }
            $entity = $this->getDoctrine()->getRepository(Itinerary::getItineraryClass($kind))->find($id);

            /** @var Itinerary $entity */
            if (empty($entity) || $entity->getSharecode() != $code) {
                return [];
            }

            if (null !== $entity->getUserAgent()) {
                return [$entity->getUserAgent()->getEmail(), $entity->getUserAgent()->getFullName()];
            } else {
                return [$entity->getUser()->getEmail(), $entity->getUser()->getFullName()];
            }
        }
    }

    private function correctUserAgentForSharedTimeline(array $segments, Useragent $agent): array
    {
        return it($segments)
            ->map(function (array $item) use ($agent) {
                if ($item['type'] !== 'segment' || !isset($item['details'])) {
                    return $item;
                }

                $item['details']['agentId'] = $agent->getUseragentid();

                return $item;
            })
            ->toArray();
    }
}
