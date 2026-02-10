<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Event\NewExtensionSessionEvent;
use AwardWallet\MainBundle\Form\Account\Builder;
use AwardWallet\MainBundle\Form\Type;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\PopularityHandler;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use AwardWallet\Schema\Itineraries\Parking;
use AwardWallet\WidgetBundle\Widget\TripsPersonsWidget;
use Aws\Result;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\column;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * Class TripsController.
 *
 * @Route("/trips")
 */
class TripsController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private UseragentRepository $useragentRepository;
    private TripsPersonsWidget $tripsPersonsWidget;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        UseragentRepository $useragentRepository,
        TripsPersonsWidget $tripsPersonsWidget,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->useragentRepository = $useragentRepository;
        $this->tripsPersonsWidget = $tripsPersonsWidget;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/retrieve", name="aw_trips_add", methods={"GET", "POST"}, options={"expose"=true})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addTripAction(
        Request $request,
        PopularityHandler $popularityHandler,
        PageVisitLogger $pageVisitLogger
    ) {
        $agentId = $request->query->get('agentId', null);

        if ($agentId == 'my') {
            $agentId = null;
        }
        $agent = null;
        $user = $this->tokenStorage->getBusinessUser();

        if (!empty($agentId)) {
            $agent = $this->useragentRepository->find($agentId);

            if (empty($agent)) {
                throw new NotFoundHttpException();
            }

            if ($agent->getAgentid() != $user || !$agent->isFamilyMember()) {
                if ($agent->isFamilyMember()) {
                    $timelineShare = $user->getTimelineShareWith($agent->getAgentid(), $agent);
                } else {
                    $timelineShare = $user->getTimelineShareWith($agent->getClientid());
                }

                if (empty($timelineShare)) {
                    throw new NotFoundHttpException();
                }

                if (!$timelineShare->getUserAgent()->getTripAccessLevel()) {
                    throw new NotFoundHttpException();
                }
            }
        }

        if (empty($agent)) {
            $email = $user->getItineraryForwardingEmail();
            $fullName = $user->getFullName();
        } else {
            $email = $agent->getItineraryForwardingEmail();
            $fullName = $agent->getFullName();
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_TRIPS);

        return $this->render('@AwardWalletMain/Trips/addTrip.html.twig', [
            'agents' => $this->tripsPersonsWidget->count(),
            'providers' => $popularityHandler->getPopularPrograms(
                $this->tokenStorage->getBusinessUser(),
                ' and (CanCheckConfirmation > ' . CAN_CHECK_CONFIRMATION_NO . ' or CanCheckItinerary = 1) ',
                'ORDER BY Popularity DESC, p.Accounts DESC',
                null,
                true
            ),
            'email' => $email,
            'selectedClient' => $agent,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/edit/{tripId}", name="aw_trips_edit", requirements={"tripId" = "([A-Z]{1,2})\.\d+"}, options={"expose"=true})
     */
    public function editAction($tripId)
    {
        [$kind, $id] = explode(".", $tripId);

        switch ($kind) {
            case "T":
                /** @var Tripsegment $tripsegment */
                $tripsegment = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class)->find($id);

                if (!$tripsegment) {
                    throw $this->createNotFoundException();
                }
                /** @var Trip $trip */
                $trip = $tripsegment->getTripid();
                $id = $trip->getId();
                $userAgentId = $trip->getUserAgent() ? $trip->getUserAgent()->getUseragentid() : null;

                switch ($trip->getCategory()) {
                    case Trip::CATEGORY_AIR:
                        $url = $this->generateUrl('itinerary_edit', ['type' => 'flight', 'itineraryId' => $id]);

                        break;

                    case Trip::CATEGORY_BUS:
                        $url = $this->generateUrl('itinerary_edit', ['type' => 'bus-ride', 'itineraryId' => $id]);

                        break;

                    case Trip::CATEGORY_TRAIN:
                        $url = $this->generateUrl('itinerary_edit', ['type' => 'train-ride', 'itineraryId' => $id]);

                        break;

                    case Trip::CATEGORY_FERRY:
                        $url = $this->generateUrl('itinerary_edit', ['type' => 'ferry-ride', 'itineraryId' => $id]);

                        break;

                    case Trip::CATEGORY_CRUISE:
                        $url = $this->generateUrl('itinerary_edit', ['type' => 'cruise', 'itineraryId' => $id]);

                        break;

                    default:
                        throw new BadRequestHttpException("Unknown trip category");
                }
                $url .= null !== $userAgentId ? "?agentId=$userAgentId" : '';

                return $this->redirect($url);

                break;

            case "R":
            case "CI":
            case "CO":
                /** @var Reservation $reservation */
                $reservation = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class)->find($id);

                if (!$reservation) {
                    throw $this->createNotFoundException();
                }
                $id = $reservation->getId();
                $userAgentId = $reservation->getUserAgent() ? $reservation->getUserAgent()->getUseragentid() : null;
                $url = $this->generateUrl('itinerary_edit', ['type' => 'reservation', 'itineraryId' => $id]);
                $url .= null !== $userAgentId ? "?agentId=$userAgentId" : '';

                return $this->redirect($url);

                break;

            case "L":
            case "PU":
            case "DO":
                /** @var Rental $rental */
                $rental = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Rental::class)->find($id);

                if (!$rental) {
                    throw $this->createNotFoundException();
                }
                $id = $rental->getId();
                $userAgentId = $rental->getUserAgent() ? $rental->getUserAgent()->getUseragentid() : null;

                switch ($rental->getType()) {
                    case Rental::TYPE_RENTAL:
                        $url = $this->generateUrl('itinerary_edit', ['type' => 'rental', 'itineraryId' => $id]);

                        break;

                    case Rental::TYPE_TAXI:
                        $url = $this->generateUrl('itinerary_edit', ['type' => 'taxi-ride', 'itineraryId' => $id]);

                        break;

                    default:
                        throw new \LogicException('Unknown type "' . $rental->getType() . '""');
                }
                $url .= null !== $userAgentId ? "?agentId=$userAgentId" : '';

                return $this->redirect($url);

                break;

            case "E":
                /** @var Restaurant $event */
                $event = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Restaurant::class)->find($id);

                if (!$event) {
                    throw $this->createNotFoundException();
                }
                $id = $event->getId();
                $userAgentId = $event->getUserAgent() ? $event->getUserAgent()->getUseragentid() : null;
                $url = $this->generateUrl('itinerary_edit', ['type' => 'event', 'itineraryId' => $id]);
                $url .= null !== $userAgentId ? "?agentId=$userAgentId" : '';

                return $this->redirect($url);

                break;

            case "P":
            case "PS":
            case "PE":
                /** @var Parking $parking */
                $parking = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Parking::class)->find($id);

                if (!$parking) {
                    throw $this->createNotFoundException();
                }
                $id = $parking->getId();
                $userAgentId = $parking->getUserAgent() ? $parking->getUserAgent()->getUseragentid() : null;
                $url = $this->generateUrl('itinerary_edit', ['type' => 'parking', 'itineraryId' => $id]);
                $url .= null !== $userAgentId ? "?agentId=$userAgentId" : '';

                return $this->redirect($url);

                break;

            default:
                throw $this->createNotFoundException();

                break;
        }
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/data/retrieve/{providerid}", name="aw_trips_retrieve_data", methods={"GET"}, options={"expose"=true}, requirements={"providerid" = "\d+"})
     */
    public function retrieveTripsDataAction($providerid, Request $request)
    {
        $agentId = $request->query->get('agentId', null);

        /** @var $accountRep \AwardWallet\MainBundle\Entity\Repositories\AccountRepository */
        $accountRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        /** @var $providerRep \AwardWallet\MainBundle\Entity\Repositories\ProviderRepository */
        $providerRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

        /** @var $provider \AwardWallet\MainBundle\Entity\Provider */
        $provider = $providerRep->find($providerid);

        if (!$provider || !$this->authorizationChecker->isGranted('ADD', $provider)) {
            throw $this->createNotFoundException();
        }

        $user = $this->tokenStorage->getBusinessUser();
        $userAgent = null;
        $business = null;

        if ($agentId) {
            $userAgent = $this->useragentRepository->find($agentId);

            if ($userAgent) {
                $business = $this->tokenStorage->getBusinessUser();

                if ($userAgent->isFamilyMember()) {
                    $user = $userAgent->getAgentid();
                } elseif ($userAgent->getClientid()->findUserAgent($business->getUserid())) {
                    $user = $userAgent->getClientid();
                }
            }
        }

        // $accountRep->getUserAccountsByProvider replace
        $data = $this->accountListManager
            ->getAccountList(
                $this->optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_USER, $user)
                    ->set(Options::OPTION_FILTER,
                        " AND p.ProviderID = $providerid" .
                        ($agentId && $userAgent->isFamilyMember() ? " AND a.UserAgentID = $agentId" : "") .
                        ($agentId && !$userAgent->isFamilyMember() ? " AND a.UserID = {$userAgent->getClientid()->getUserid()}" : "")
                    )
            )
            ->getAccounts();

        $templateFields = ['ID', 'Balance', 'DisplayName', 'Login', 'ProviderCode', 'CheckInBrowser', 'CanCheck', 'UserName'];
        $template = array_combine($templateFields, array_fill(0, count($templateFields), null));

        $data['accounts'] = it($data)
            ->map(function ($account) use ($template) {
                return \array_intersect_key($account, $template);
            })
            ->filter(column('Login'))
            ->toArrayWithKeys();

        $data['CanCheckConfirmation'] = $provider->getCancheckconfirmation();
        $data['CanCheckItinerary'] = $provider->getCancheckitinerary();
        $data['Currency'] = '';
        $data['Email'] = $user->getLogin() . ($userAgent && $userAgent->isFamilyMember() ? '.' . $userAgent->getAlias() : '') . '@email.awardwallet.com';

        $response = new JsonResponse($data);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/retrieve/confirmation/{providerId}", name="aw_trips_retrieve_confirmation", options={"expose"=true}, requirements={"providerId" = "\d+"})
     */
    public function retrieveTripsByNumberAction(
        Request $request,
        $providerId,
        Builder $builder,
        UpdaterEngineInterface $updaterEngine,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher,
        Client $messaging,
        LoggerInterface $logger
    ) {
        /** @var $providerRep \AwardWallet\MainBundle\Entity\Repositories\ProviderRepository */
        $providerRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

        /** @var $provider \AwardWallet\MainBundle\Entity\Provider */
        $provider = $providerRep->find($providerId);

        if (!$provider || !$this->authorizationChecker->isGranted('ADD', $provider)) {
            throw $this->createNotFoundException();
        }

        if (!$provider->getCancheckconfirmation()) {
            throw $this->createNotFoundException();
        }

        $agentId = $request->query->get('agentId', null);
        $user = $this->tokenStorage->getBusinessUser();
        $userAgent = null;

        if ($agentId) {
            $userAgent = $this->useragentRepository->find($agentId);
            $this->tripsPersonsWidget->setActiveItem('ua' . $agentId);

            if (null !== $userAgent) {
                $this->denyAccessUnlessGranted('EDIT_TIMELINE', $userAgent);
            }
        }
        $owner = OwnerRepository::getByUserAndUseragent($user, $userAgent);

        $template = $builder->getConfirmationFormTemplate($provider, $request, $entityManager, $userAgent);

        /** @var \Symfony\Component\Form\Form */
        $form = $this->createForm(Type\ConfirmationType::class, null, ['confirmation_template' => $template]);
        $checker = $template->checker;
        $twig = '@AwardWalletMain/Trips/retrieveTripsByNumber.html.twig';

        $isError = false;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fields = $form->getData();
            $trips = [];

            foreach ($fields as $code => $value) {
                if ($value instanceof \DateTime) {
                    $fields[$code] = $value->format('m/d/Y');
                }
            }

            $fields['browserExtensionAllowed'] = $fields['browserExtensionAllowed'] === 'true' && $provider->isConfNoV3();

            if ($fields['browserExtensionAllowed'] && !$request->getSession()->has("valid_channel_" . $fields['channel'])) {
                throw new BadRequestHttpException();
            }

            $eventDispatcher->addListener(NewExtensionSessionEvent::NAME, function (NewExtensionSessionEvent $event) use ($messaging, $fields, $logger) {
                $logger->info("sending NewExtensionSessionEvent: " . $event->getBrowserExtensionSessionId());
                $messaging->publish($fields['channel'], ["sessionId" => $event->getBrowserExtensionSessionId(), "token" => $event->getBrowserExtensionConnectionToken()]);
            });

            $error = $updaterEngine->retrieveConfirmation($fields, $provider, $trips, $owner->getUser(), $owner->getFamilyMember());

            if (!empty($error)) {
                $form->addError(new FormError($error));
            }

            if (count($trips) == 0) {
                $isError = empty($error);
            } else {
                if (count($trips) > 0) {
                    $redirect = $router->generate('aw_timeline_html5_itineraries', ['itIds' => implode(',', $trips)]) . '?showDeleted=1' . ($agentId ? "&agentId={$agentId}" : '');
                } else {
                    $redirect = $router->generate('aw_timeline_html5', ['agentId' => $agentId ? $agentId : '']);
                }

                return new JsonResponse(["redirect" => $redirect]);
            }
        }

        if ($form->isSubmitted()) {
            $twig = '@AwardWalletMain/Trips/_retrieveForm.html.twig';
        }

        $regions = [];

        if ($form->has('Region') && $regionField = $form->get('Region')) {
            $data = $regionField->getConfig()->getOption('choices');
            //            unset($data[current(array_keys($data))]); // remove first element
            $keys = array_keys($data);

            foreach ($keys as $key) {
                $regions[$key] = $checker->ConfirmationNumberURL(['Region' => $key]);
            }
        }

        $channel = UserMessaging::getChannelName('confnov3' . bin2hex(random_bytes(3)), $user->getId());
        $request->getSession()->set("valid_channel_" . $channel, true);

        return $this->render(
            $twig,
            [
                'form' => $form->createView(),
                'displayName' => preg_replace('/\((.*?)\)/', '<span>(\\1)</span>', $provider->getDisplayname()),
                'provider' => $provider,
                'formUrl' => $checker->ConfirmationNumberURL(null),
                'regions' => $regions,
                'isError' => $isError,
                'autoSubmit' => $template->autoSubmit,
                'selectedUserId' => $owner->getUser()->getUserid(),
                'familyMemberId' => $owner->isFamilyMember() ? $owner->getFamilyMember()->getUseragentid() : '',
                'clientId' => $agentId,
                'useExtensionV3' => $provider->isConfNoV3(),
                'channel' => $channel,
                'centrifugeConfig' => $messaging->getClientData(),
            ]
        );
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/update", name="aw_trips_update", methods={"GET", "POST"}, options={"expose"=true})
     * @Template("@AwardWalletMain/Account/List/listTrips.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request)
    {
        // for backTo from login
        if ($request->getMethod() != 'POST') {
            return $this->redirect('/');
        }

        $accounts = $request->get('accounts', []);

        if (!(isset($accounts) && is_array($accounts) && !empty($accounts))) {
            throw $this->createNotFoundException();
        }

        $accounts = array_filter($accounts, function ($a) {
            return !empty($a);
        });

        $accountsData = $this->getAccountsData($accounts);

        if (!(isset($accountsData['accounts']) && is_array($accountsData['accounts']) && !empty($accountsData['accounts']))) {
            throw $this->createNotFoundException();
        }

        if (!$request->get('agentId')) {
            $agentId = current($accountsData['accounts'])->AccountOwner;
            $this->tripsPersonsWidget->setActiveItem('ua' . $agentId);
        } else {
            $agentId = $request->get('agentId');
        }

        return [
            'agents' => $this->tripsPersonsWidget->count(),
            'accountsData' => $this->getAccountsData($accounts),
            'agentId' => $agentId,
            'total' => count($accountsData['accounts']),
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/pass/{segmentId}", name="aw_trips_pass", methods={"GET"}, requirements={"segmentId" = "\d+"})
     */
    public function passAction($segmentId, Request $request, S3Client $s3Client, string $awsS3BoardingBucket)
    {
        /* @var \AwardWallet\MainBundle\Entity\Tripsegment $segment */
        $segment = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class)->find($segmentId);

        if (!$segment) {
            throw $this->createNotFoundException();
        }

        if (!$this->authorizationChecker->isGranted('VIEW', $segment->getTripid())) {
            throw $this->createAccessDeniedException();
        }

        $name = sprintf('bp_%d', $segment->getTripsegmentid());
        $list = $s3Client->listObjects(['Bucket' => $awsS3BoardingBucket, 'Prefix' => $name]);

        if ($list instanceof Result) {
            $list = $list->get('Contents');
        }

        if (empty($list)) {
            throw $this->createNotFoundException();
        }
        $key = array_shift($list)['Key'];
        $body = $s3Client->getObject([
            'Bucket' => $awsS3BoardingBucket,
            'Key' => $key,
        ])['Body'];
        $response = new Response();
        $ext = substr($key, -3);

        switch (strtolower($ext)) {
            case 'pdf':
                $type = 'application/pdf';

                break;

            default:
                $type = 'image/' . $ext;

                break;
        }
        $response->headers->set('Content-Type', $type);
        $response->setContent($body);

        return $response;
    }

    private function getAccountsData($accountIds)
    {
        $manager = $this->accountListManager;
        $userObject = $this->tokenStorage->getBusinessUser();
        $accounts = $manager->getAccountList(
            $this->optionsFactory->createDesktopListOptions(
                (new Options())
                    ->set(Options::OPTION_USER, $userObject)
                    ->set(Options::OPTION_ACCOUNT_IDS, $accountIds)
            )
        );
        $kinds = $manager->getProviderKindsInfo();

        if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            $agents = $manager->getBusinessAgents($userObject);
            $user = $manager->getBusinessUserInfo($userObject);
        } else {
            $agents = $manager->getAgentsInfo($userObject);
            $user = $manager->getUserInfo($userObject);
        }

        return [
            'accounts' => $accounts->getAccounts(),
            'kinds' => $kinds,
            'agents' => $agents,
            'user' => $user,
        ];
    }
}
