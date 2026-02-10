<?php

namespace AwardWallet\MobileBundle\Controller\Timeline;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\MileValue\Form\Model\CustomSetModel;
use AwardWallet\MainBundle\Service\MileValue\MileValueCustom;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\AlternativeFlightsFormatter;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/alternative-flights")
 */
class AlternativeFlightsController extends AbstractController
{
    private TripsegmentRepository $tripsegmentRep;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private AlternativeFlightsFormatter $alternativeFlightsFormatter;
    private MileValueCustom $mileValueCustom;

    public function __construct(
        TripsegmentRepository $tripsegmentRep,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        AlternativeFlightsFormatter $alternativeFlightsFormatter,
        MileValueCustom $mileValueCustom
    ) {
        $this->tripsegmentRep = $tripsegmentRep;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->alternativeFlightsFormatter = $alternativeFlightsFormatter;
        $this->mileValueCustom = $mileValueCustom;
    }

    /**
     * @Route("/{segmentId}",
     *     name="awm_timeline_alternative_flights_data",
     *     requirements={
     *         "segmentId"   = "\d+",
     *     },
     *     methods={"GET"}
     * )
     * @Security("is_granted('CSRF')")
     */
    public function dataAction(string $segmentId): JsonResponse
    {
        $tripsegment = $this->loadTripSegment($segmentId);
        $trip = $tripsegment->getTripid();

        if (!$this->isGranted('VIEW', $trip)) {
            throw $this->createNotFoundException();
        }

        return $this->json($this->alternativeFlightsFormatter->format($tripsegment));
    }

    /**
     * @Route("/{segmentId}",
     *     name="awm_timeline_alternative_flights_set_value",
     *     requirements={
     *         "segmentId"   = "\d+",
     *     },
     *     methods={"POST"}
     * )
     * @JsonDecode
     * @Security("is_granted('CSRF')")
     */
    public function setValueAction(Request $request, string $segmentId): JsonResponse
    {
        $tripsegment = $this->loadTripSegment($segmentId);
        $trip = $tripsegment->getTripid();

        if (!$this->isGranted('EDIT', $trip)) {
            throw $this->createNotFoundException();
        }

        $data = $request->request->all();
        $data['id'] = (int) $segmentId;
        $data['customPick'] = $data['selected'] ?? -1;

        $model = $this->serializer->deserialize(\json_encode($data), CustomSetModel::class, 'json');
        $error =
            it($this->validator->validate($model))
            ->map(fn (ConstraintViolationInterface $violation) => $violation->getMessage())
            ->first();

        if (empty($error)) {
            $this->mileValueCustom->setCustomValue($trip->getId(), (int) $data['customPick'], (float) ($data['customValue'] ?? 0));

            return $this->json(['success' => true]);
        } else {
            return $this->json(['error' => $error]);
        }
    }

    protected function loadTripSegment(string $tripSegmentId): Tripsegment
    {
        $tripsegment = $this->tripsegmentRep->find($tripSegmentId);

        if (!$tripsegment) {
            throw $this->createNotFoundException();
        }

        return $tripsegment;
    }
}
