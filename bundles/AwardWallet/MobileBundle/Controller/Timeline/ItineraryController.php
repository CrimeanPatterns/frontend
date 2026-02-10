<?php

namespace AwardWallet\MobileBundle\Controller\Timeline;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use AwardWallet\MainBundle\Service\Itinerary\Form\Saver;
use AwardWallet\MainBundle\Timeline\Manager;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ItineraryController extends AbstractController
{
    /**
     * @Route("itinerary/{tripId}/file", methods={"POST"}, requirements={"tripId"="([A-Z]{1,2})\.\d+"}, name="awm_timeline_itinerary_upload_file")
     * @Security("is_granted('CSRF')")
     */
    public function uploadFileAction(
        Request $request,
        Saver $saver,
        ItineraryFileManager $itineraryFileManager,
        string $tripId,
        LocalizeService $localizeService,
        Connection $connection
    ): JsonResponse {
        [$kind, $itineraryId] = explode('.', $tripId);

        switch ($kind) {
            case Itinerary::KIND_TRIP:
                $type = 'flight';

                if ($tripId = $connection->fetchOne('SELECT TripID from TripSegment WHERE TripSegmentID = ' . $itineraryId . ' LIMIT 1')) {
                    $itineraryId = $tripId;
                }

                break;

            case Itinerary::KIND_RESERVATION:
            case in_array($kind, Reservation::getSegmentMap(), true):
                $type = 'reservation';

                break;

            case Itinerary::KIND_RENTAL:
            case in_array($kind, Rental::getSegmentMap(), true):
                $type = 'rental';

                break;

            case Itinerary::KIND_RESTAURANT:
                $type = 'event';

                break;

            case Itinerary::KIND_PARKING:
            case in_array($kind, Parking::getSegmentMap(), true):
                $type = 'parking';

                break;

            default:
                throw $this->createNotFoundException();
        }

        $response = $this->forward('AwardWallet\MainBundle\Service\Itinerary\Controller\ItineraryController::uploadNoteFileAction',
            [
                'request' => $request,
                'saver' => $saver,
                'itineraryFileManager' => $itineraryFileManager,
                'type' => $type,
                'itineraryId' => $itineraryId,
            ]);

        $result = [];
        $data = json_decode($response->getContent(), true);

        if (!empty($data['files'])) {
            foreach ($data['files'] as $file) {
                $date = new \DateTime($file['uploadDate']);
                $result[] = [
                    'id' => $file['id'],
                    'name' => $file['fileName'],
                    'description' => $file['description'],
                    'size' => $localizeService->formatNumberShort($file['fileSize'], 2),
                    'time' => $date->getTimestamp(),
                    'date' => $localizeService->formatDate($date, 'medium')
                        . ', '
                        . $localizeService->formatTime($date),
                ];
            }
        }

        return new JsonResponse([
            'files' => $result,
            'uploadedFileId' => $data['uploadedFileId'] ?? null,
        ]);
    }

    /**
     * @Route("itinerary/file/{itineraryFileId}", requirements={"itineraryFileId"="\d+"}, methods={"GET"}, name="awm_timeline_itinerary_fetch_file")
     */
    public function fetchFile(
        Request $request,
        int $itineraryFileId,
        ItineraryFileManager $itineraryFileManager,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
        return $this->forward('AwardWallet\MainBundle\Service\Itinerary\Controller\ItineraryController::fetchFile', [
            'request' => $request,
            'itineraryFileId' => $itineraryFileId,
            'itineraryFileManager' => $itineraryFileManager,
            'authorizationChecker' => $authorizationChecker,
        ]);
    }

    /**
     * @Route("itinerary/file/{itineraryFileId}", requirements={"itineraryFileId"="\d+"}, methods={"DELETE"}, name="awm_timeline_itinerary_remove_file")
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     */
    public function removeFile(
        int $itineraryFileId,
        ItineraryFileManager $itineraryFileManager,
        AuthorizationCheckerInterface $authorizationChecker
    ): JsonResponse {
        return $this->forward('AwardWallet\MainBundle\Service\Itinerary\Controller\ItineraryController::removeFile', [
            'itineraryFileId' => $itineraryFileId,
            'itineraryFileManager' => $itineraryFileManager,
            'authorizationChecker' => $authorizationChecker,
        ]);
    }

    /**
     * @Route("itinerary/move/{itCode}/{agent}", name="awm_timeline_itinerary_move", methods={"POST"}, defaults={"agent" = null}, requirements={"itCode" = "\S{1,3}\.\d+"})
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id" = "agent"})
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @JsonDecode
     */
    public function moveAction(?Useragent $agent = null, $itCode, Request $request, Manager $manager)
    {
        try {
            $copy = $request->request->get('copy') == 1;
            $manager->moveItinerary($itCode, $agent, $copy);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }

        return new JsonResponse(['success' => true]);
    }
}
