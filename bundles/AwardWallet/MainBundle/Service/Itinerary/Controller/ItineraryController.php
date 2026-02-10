<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Controller;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\Common\FlightStats\Communicator;
use AwardWallet\Common\FlightStats\CommunicatorCallException;
use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\BaseConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\BusConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\CruiseConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\EventConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\FerryConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\FlightConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\ItineraryConverterInterface;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\ParkingConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\RentalConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\ReservationConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\TaxiConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter\TrainConverter;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\EventModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\ParkingModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\RentalModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\ReservationModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\TaxiModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\TripModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Saver;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\BusType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\CruiseType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\EventType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\FerryType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\FlightType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\ParkingType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\RentalType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\ReservationType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\TaxiType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\TrainType;
use AwardWallet\MainBundle\Service\Notification\Transformer\TransformerUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ItineraryController extends AbstractController
{
    public const TYPES = 'flight|reservation|rental|taxi-ride|bus-ride|train-ride|ferry-ride|cruise|event|parking';

    private EntityManagerInterface $em;
    private AwTokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private AntiBruteforceLockerService $antiBruteforceLockerService;
    private LocalizeService $localizeService;

    private array $converters;

    public function __construct(
        EntityManagerInterface $em,
        AwTokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        AntiBruteforceLockerService $antiBruteforceLockerService,
        LocalizeService $localizeService,
        iterable $converters
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->antiBruteforceLockerService = $antiBruteforceLockerService;
        $this->localizeService = $localizeService;
        $this->converters = [];

        foreach ($converters as $converter) {
            if (!$converter instanceof BaseConverter) {
                $this->converters[get_class($converter)] = $converter;
            }
        }
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route(
     *     "{type}/add",
     *     name="itinerary_create",
     *     requirements={
     *         "type": ItineraryController::TYPES
     *     }
     * )
     * @Route(
     *     "{type}/{itineraryId}/edit",
     *     name="itinerary_edit",
     *     requirements={
     *         "type": ItineraryController::TYPES
     *     }
     * )
     */
    public function editAction(
        Request $request,
        ItineraryFileManager $itineraryFileManager,
        Saver $saver,
        Environment $twigEnv,
        string $type,
        ?int $itineraryId = null
    ) {
        $twigEnv->addGlobal('webpack', true);
        $type = $this->prepareType($type);
        /** @var FormInterface $form */
        /** @var Itinerary $itinerary */
        [$form, $itinerary] = $this->createFormByEnvironment($request, $type, $itineraryId);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $fileDescriptions = $request->request->get('fileDescription', []);

            if ($itinerary instanceof Trip) {
                $startDatesBefore = [];

                foreach ($itinerary->getSegments() as $segment) {
                    $startDatesBefore[$segment->getId()] = clone $segment->getUTCStartDate();
                }
            }

            $this->getConverter($type)->reverseConvert($data, $itinerary);
            $saver->save($itinerary, $fileDescriptions);

            if ($itinerary instanceof Trip) {
                $now = date_create('now', new \DateTimeZone('UTC'));

                foreach ($itinerary->getSegments() as $segment) {
                    $startDateBefore = $startDatesBefore[$segment->getId()] ?? null;
                    $startDateAfter = $segment->getUTCStartDate();

                    if (
                        $startDateBefore
                        && $startDateAfter
                        && $startDateAfter > $startDateBefore
                        && $startDateAfter > $now
                        && ($startDateAfter->getTimestamp() - $startDateBefore->getTimestamp()) >= 3600
                    ) {
                        $this->logger->info(sprintf(
                            'trip segment %d start date was changed from "%s" to "%s", resetting notifications',
                            $segment->getId(),
                            $startDateBefore->format('c'),
                            $startDateAfter->format('c')
                        ));
                        $segment->setPreCheckinNotificationDate(null);
                        $segment->setCheckinnotificationdate(null);
                        $segment->setFlightDepartureNotificationDate(null);
                        $segment->setFlightBoardingNotificationDate(null);
                    }
                }

                $this->em->flush();
            }

            return $this->redirectToTimeline($itinerary);
        }

        if (!is_null($itineraryId)) {
            $files = $itineraryFileManager->getListFiles(
                $itineraryFileManager->getFiles(
                    $itinerary->getKind(),
                    $itinerary->getId()
                )
            );
        }

        return $this->render("@Module/Itinerary/Resources/templates/{$type}.html.twig", [
            'form' => $form->createView(),
            'edit' => !is_null($itineraryId),
            'files' => $files ?? [],
        ]);
    }

    /**
     * @Route(
     *     "{type}/{itineraryId}/upload",
     *     name="upload_note_file",
     *     requirements={
     *         "type": ItineraryController::TYPES
     *     },
     *     methods={"POST"},
     *     options={"expose"=true}
     * )
     */
    public function uploadNoteFileAction(
        Request $request,
        Saver $saver,
        ItineraryFileManager $itineraryFileManager,
        string $type,
        int $itineraryId
    ): JsonResponse {
        $isAdding = -1 === $itineraryId;
        $type = $this->prepareType($type);

        if (!$isAdding) {
            $itinerary = $this->findItinerary($type, $itineraryId);

            if (!$this->isGranted('EDIT', $itinerary)) {
                throw $this->createAccessDeniedException();
            }

            if (!$itinerary) {
                return new JsonResponse([
                    'status' => false,
                    'error' => 'Itinerary was not found',
                ], 404);
            }

            if (!$this->isGranted('EDIT', $itinerary)) {
                return new JsonResponse([
                    'status' => false,
                    'error' => 'Access denied',
                ], 403);
            }
        }

        /** @var UploadedFile $file */
        if (is_null($file = $request->files->get('file'))) {
            return new JsonResponse([
                'status' => false,
            ]);
        }

        try {
            $response = ['status' => true, 'files' => []];

            if ($isAdding) {
                $itinerary = $this->createItinerary($type);
                $itinerary->setUser($this->tokenStorage->getUser());
                $response['tmpFiles'] = [$saver->tmpUploadFile($file, $itinerary)];
                $response['uploadedFileId'] = $response['tmpFiles'][0]['id'];
            } else {
                $response['uploadedFileId'] = $saver->uploadFile($file, $itinerary);
                $response['files'] = $itineraryFileManager->getListFiles(
                    $itineraryFileManager->getFiles(
                        $itinerary->getKind(),
                        $itinerary->getId()
                    )
                );
            }

            return new JsonResponse($response);
        } catch (\LengthException|\InvalidArgumentException $exception) {
            return new JsonResponse([
                'status' => false,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @Route("/itinerary/fetch/file/{itineraryFileId}", requirements={"itineraryFileId"="\d+"}, name="aw_timeline_itinerary_fetch_file", options={"expose"=true})
     * @ParamConverter("itineraryFile", class="AwardWalletMainBundle:Files\ItineraryFile", options={"id"="itineraryFileId"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function fetchFile(
        Request $request,
        ItineraryFile $itineraryFile,
        ItineraryFileManager $itineraryFileManager,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
        if (!$authorizationChecker->isGranted('EDIT', $itineraryFile)) {
            throw new AccessDeniedException('Access Denied');
        }

        $response = $itineraryFileManager->fetchResponse(
            $itineraryFile,
            $request->get('response_streaming', false)
        );

        if (is_null($response)) {
            throw new NotFoundHttpException('Not Found');
        }

        // disable output file for ItineraryFileAccessCest
        if ($request->query->has('off')) {
            $response->setContent(null);
        }

        return $response;
    }

    /**
     * @Route("/itinerary/remove/file/{itineraryFileId}", requirements={"itineraryFileId"="\d+"}, methods={"POST"}, name="aw_timeline_itinerary_remove_file", options={"expose"=true})
     * @ParamConverter("itineraryFile", class="AwardWalletMainBundle:Files\ItineraryFile", options={"id"="itineraryFileId"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function removeFile(
        ItineraryFile $itineraryFile,
        ItineraryFileManager $itineraryFileManager,
        AuthorizationCheckerInterface $authorizationChecker
    ): JsonResponse {
        if (!$authorizationChecker->isGranted('EDIT', $itineraryFile)) {
            throw new AccessDeniedException('Access Denied');
        }

        $itineraryFileManager->removeFile($itineraryFile, true);

        return new JsonResponse(['status' => true]);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("flight/fill", name="flight_fill", options={"expose"=true})
     */
    public function fillFlightAction(Request $request, TranslatorInterface $translator, Communicator $communicator)
    {
        $userIp = $request->getClientIp();
        $error = $this->antiBruteforceLockerService->checkForLockout($userIp);

        if (!empty($error)) {
            throw new TooManyRequestsHttpException('5 minutes');
        }

        $errors = [];

        foreach (['airlineName', 'flightNumber', 'departureAirport', 'departureDate_date'] as $inputField) {
            if (empty($request->query->get($inputField))) {
                $errors[$inputField] = $translator->trans('notblank', [], 'validators');
            }
        }
        /** @var Airline $airline */
        $airline = $this->em->getRepository(Airline::class)->findOneBy(['name' => $request->query->get('airlineName')]);

        if (null === $airline) {
            $errors['airlineName'] = $translator->trans('unknown_airline', [], 'validators');
        }

        if (!ctype_digit($request->query->get('flightNumber'))) {
            $errors['flightNumber'] = $translator->trans('digit', [], 'validators');
        }
        /** @var Aircode $departureAirport */
        $departureAirport = $this->em->getRepository(Aircode::class)->findOneBy(['aircode' => $request->query->get('departureAirport')]);

        if (null === $departureAirport) {
            $errors['departureAirport'] = $translator->trans('unknown_airport', [], 'validators');
        }

        try {
            $departureDate = new \DateTime($request->query->get('departureDate_date'));
        } catch (\Exception $e) {
            $errors['departureDate_date'] = $translator->trans('invalid_date', [], 'validators');
        }

        if (!empty($errors)) {
            return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
        }

        $flight = $this->getFlightFromFlightStats($airline, $request->get('flightNumber'), $departureDate, $departureAirport, $communicator);

        if (null === $flight) {
            throw new HttpException(Response::HTTP_NOT_FOUND);
        }
        /** @var Aircode $arrivalAirport */
        $arrivalAirport = $this->em->getRepository(Aircode::class)->findOneBy(['aircode' => $flight->getArrivalAirport()->getIata()]);

        if (null === $arrivalAirport) {
            $this->logger->error("Airport code " . $flight->getArrivalAirport()->getIata() . " returned from the Flight Stats is unrecognized");

            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $fields['departureDate_time'] = $this->localizeService->formatTime(new \DateTime($flight->getDepartureTime()));
        $fields['departureDate_date'] = (new \DateTime($flight->getDepartureTime()))->format('Y-m-d');
        $fields['arrivalDate_time'] = $this->localizeService->formatTime(new \DateTime($flight->getArrivalTime()));
        $fields['arrivalDate_date'] = (new \DateTime($flight->getArrivalTime()))->format('Y-m-d');
        $fields['departureAirport'] = $departureAirport->getAircode();
        $fields['departureAirportName'] = $departureAirport->getAirname();
        $fields['arrivalAirport'] = $arrivalAirport->getAircode();
        $fields['arrivalAirportName'] = $arrivalAirport->getAirname();
        $fields['airlineName'] = $flight->getCarrier()->getName();
        $fields['flightNumber'] = $flight->getflightNumber();

        return new JsonResponse($fields);
    }

    private function getFlightFromFlightStats(
        Airline $airline,
        $flightNumber,
        \DateTime $departureDate,
        Aircode $departureAirport,
        Communicator $communicator
    ) {
        try {
            $schedule = $communicator->getScheduleByCarrierFNAndDepartureDate(
                $airline->getCode(),
                $flightNumber,
                $departureDate->format('Y-m-d')
            );
        } catch (CommunicatorCallException $e) {
            if (Response::HTTP_NOT_FOUND === $e->getCode()) {
                return null;
            } else {
                throw $e;
            }
        }

        if (null === $schedule) {
            return null;
        }

        foreach ($schedule->getScheduledFlights() as $flight) {
            if ($flight->getDepartureAirport()->getIata() === $departureAirport->getAircode()) {
                return $flight;
            }
        }

        return null;
    }

    private function getFormClass(string $type): string
    {
        switch ($type) {
            case 'reservation':
                return ReservationType::class;

            case 'rental':
                return RentalType::class;

            case 'event':
                return EventType::class;

            case 'parking':
                return ParkingType::class;

            case 'flight':
                return FlightType::class;

            case 'taxi_ride':
                return TaxiType::class;

            case 'bus_ride':
                return BusType::class;

            case 'train_ride':
                return TrainType::class;

            case 'ferry_ride':
                return FerryType::class;

            case 'cruise':
                return CruiseType::class;

            default:
                throw new \InvalidArgumentException(sprintf('Unknown itinerary "%s" type', $type));
        }
    }

    private function getConverter(string $type): ItineraryConverterInterface
    {
        switch ($type) {
            case 'reservation':
                return $this->converters[ReservationConverter::class];

            case 'rental':
                return $this->converters[RentalConverter::class];

            case 'event':
                return $this->converters[EventConverter::class];

            case 'parking':
                return $this->converters[ParkingConverter::class];

            case 'flight':
                return $this->converters[FlightConverter::class];

            case 'taxi_ride':
                return $this->converters[TaxiConverter::class];

            case 'bus_ride':
                return $this->converters[BusConverter::class];

            case 'train_ride':
                return $this->converters[TrainConverter::class];

            case 'ferry_ride':
                return $this->converters[FerryConverter::class];

            case 'cruise':
                return $this->converters[CruiseConverter::class];

            default:
                throw new \InvalidArgumentException(sprintf('Unknown itinerary "%s" type', $type));
        }
    }

    private function createItinerary(string $type): Itinerary
    {
        switch ($type) {
            case 'reservation':
                return new Reservation();

            case 'rental':
                $rental = new Rental();
                $rental->setType(Rental::TYPE_RENTAL);

                return $rental;

            case 'taxi_ride':
                $taxi = new Rental();
                $taxi->setType(Rental::TYPE_TAXI);

                return $taxi;

            case 'event':
                return new Restaurant();

            case 'parking':
                return new Parking();

            case 'flight':
            case 'bus_ride':
            case 'train_ride':
            case 'ferry_ride':
            case 'cruise':
                return new Trip();

            default:
                throw new \InvalidArgumentException(sprintf('Unknown itinerary "%s" type', $type));
        }
    }

    private function createModel(string $type): AbstractModel
    {
        switch ($type) {
            case 'reservation':
                return new ReservationModel();

            case 'rental':
                return new RentalModel();

            case 'event':
                return new EventModel();

            case 'parking':
                return new ParkingModel();

            case 'taxi_ride':
                return new TaxiModel();

            case 'flight':
            case 'bus_ride':
            case 'train_ride':
            case 'ferry_ride':
            case 'cruise':
                return new TripModel();

            default:
                throw new \InvalidArgumentException(sprintf('Unknown itinerary "%s" type', $type));
        }
    }

    private function findItinerary(string $type, int $id): ?Itinerary
    {
        switch ($type) {
            case 'reservation':
                return $this->em->getRepository(Reservation::class)->find($id);

            case 'rental':
            case 'taxi_ride':
                return $this->em->getRepository(Rental::class)->find($id);

            case 'event':
                return $this->em->getRepository(Restaurant::class)->find($id);

            case 'parking':
                return $this->em->getRepository(Parking::class)->find($id);

            case 'flight':
                return $this->em->getRepository(Trip::class)->findWithAirports($id);

            case 'bus_ride':
            case 'train_ride':
            case 'ferry_ride':
            case 'cruise':
                return $this->em->getRepository(Trip::class)->find($id);

            default:
                throw new \InvalidArgumentException(sprintf('Unknown itinerary "%s" type', $type));
        }
    }

    private function prepareType(string $type): string
    {
        return str_replace('-', '_', $type);
    }

    private function createFormByEnvironment(Request $request, string $type, ?int $id = null): array
    {
        if (!is_null($id)) {
            $itinerary = $this->findItinerary($type, $id);

            if (!$itinerary) {
                throw $this->createNotFoundException();
            }

            if (!$this->isGranted('EDIT', $itinerary)) {
                throw $this->createAccessDeniedException();
            }
        } else {
            if (
                !is_null($userAgentId = $request->query->get('agentId'))
                && is_numeric($userAgentId)
            ) {
                $userAgent = $this->em->getRepository(Useragent::class)->find($userAgentId);
            } else {
                $userAgent = null;
            }

            if (!is_null($userAgent) && !$this->isGranted('EDIT_TIMELINE', $userAgent)) {
                throw new AccessDeniedException();
            }

            $itinerary = $this->createItinerary($type);
            $itinerary->setOwner(OwnerRepository::getByUserAndUseragent($this->tokenStorage->getBusinessUser(), $userAgent));
        }

        $model = $this->createModel($type);
        $this->getConverter($type)->convert($itinerary, $model);
        $form = $this->createForm($this->getFormClass($type), $model);

        return [$form, $itinerary];
    }

    private function redirectToTimeline(Itinerary $itinerary): RedirectResponse
    {
        if ($itinerary instanceof Trip) {
            return $this->redirectToRoute('aw_timeline_show_trip', ['tripId' => $itinerary->getId()]);
        }

        return $this->redirectToRoute('aw_timeline_show', [
            'segmentId' => TransformerUtils::getTimelineKindByEntity($itinerary) . '.' . $itinerary->getId(),
        ]);
    }
}
