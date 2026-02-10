<?php

namespace AwardWallet\MobileBundle\Controller\Timeline;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\Files\PlanFileManager;
use AwardWallet\MainBundle\Service\Itinerary\Form\Saver;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Helper;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\PlanManager;
use AwardWallet\MainBundle\Timeline\Util\ItineraryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/")
 */
class TimelineController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/chunk/{endTimestamp}/{userAgentId}/{deleted}",
     *      name="awm_newapp_data_timeline_chunk_before",
     *      methods={"GET"},
     *      requirements={
     *          "endTimestamp"   = "\d{1,10}",
     *          "userAgentId"    = "my|\d{1,20}",
     *          "deleted"        = "deleted"
     *      },
     *      defaults={
     *          "userAgentId" = null,
     *          "deleted"     = null
     *      }
     * )
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     * @param int $endTimestamp unix timestamp
     * @return JsonResponse
     */
    public function timelineChunkBeforeAction($endTimestamp, ?Entity\Useragent $agent = null, ?string $deleted = null, Helper $awTimelineHelperMobile)
    {
        $timeline = $awTimelineHelperMobile->getChunkedTimeline(
            null,
            (new \DateTime())->setTimestamp((int) $endTimestamp),
            $this->getCurrentUser(),
            $agent,
            'deleted' === $deleted
        );

        if (!$timeline) {
            throw $this->createNotFoundException('Unknown user-agent');
        }

        return $this->jsonResponse($timeline);
    }

    /**
     * @Route("/chunkAfter/{startTimestamp}/{userAgentId}/{deleted}",
     *      name="awm_newapp_data_timeline_chunk_after",
     *      methods={"GET"},
     *      requirements={
     *          "startTimestamp"   = "\d{1,10}",
     *          "userAgentId"    = "my|\d{1,20}",
     *          "deleted"        = "deleted"
     *      },
     *      defaults={
     *          "userAgentId" = null,
     *          "deleted"     = null
     *      }
     * )
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     * @param int $startTimestamp unix timestamp
     * @return JsonResponse
     */
    public function timelineChunkAfterAction($startTimestamp, ?Entity\Useragent $agent = null, ?string $deleted = null, Helper $awTimelineHelperMobile)
    {
        $timeline = $awTimelineHelperMobile->getChunkedTimeline(
            (new \DateTime())->setTimestamp((int) $startTimestamp),
            null,
            $this->getCurrentUser(),
            $agent,
            'deleted' === $deleted
        );

        if (!$timeline) {
            throw $this->createNotFoundException('Unknown user-agent');
        }

        return $this->jsonResponse($timeline);
    }

    /**
     * @Route("/shared/{shareCode}", name="awm_newapp_data_timeline_shared", methods={"GET"})
     */
    public function sharedDataAction($shareCode, Helper $awTimelineHelperMobile)
    {
        return $this->jsonResponse($awTimelineHelperMobile->getSharedTimelineItems($shareCode));
    }

    public function showAction($code)
    {
    }

    /**
     * @Route("/confirm-changes/{segmentId}", name="awm_mobile_confirm_changes", methods={"POST"})
     * @Security("is_granted('CSRF')")
     */
    public function confirmChangesAction($segmentId, Manager $manager)
    {
        return $this->jsonResponse($manager->confirmSegmentChanges($segmentId));
    }

    /**
     * @Route("/hide-ai-warning/{segmentId}", name="awm_hide_ai_warning", methods={"POST"})
     * @Security("is_granted('CSRF')")
     */
    public function hideAIWarningAction($segmentId, Manager $manager)
    {
        return $this->jsonResponse($manager->hideAIWarning($segmentId));
    }

    /**
     * @Route("/delete/{segmentId}", name="aw_mobile_delete_segment", methods={"POST"})
     * @Security("is_granted('CSRF')")
     */
    public function deleteAction($segmentId, Manager $manager): JsonResponse
    {
        return $this->jsonResponse($manager->deleteSegment($segmentId, false));
    }

    /**
     * @Route("/restore/{segmentId}", name="aw_mobile_restore_segment", methods={"POST"})
     * @Security("is_granted('CSRF')")
     */
    public function restoreAction($segmentId, Manager $manager): JsonResponse
    {
        return $this->jsonResponse($manager->deleteSegment($segmentId, true));
    }

    /**
     * @Route("/segment/{segmentId}",
     *      name="awm_timeline_segment",
     *      methods={"GET"},
     *      requirements={
     *          "segmentId"   = "TS\.\d{1,30}",
     *      }
     * )
     */
    public function segmentAction(string $segmentId, Helper $awTimelineHelperMobile): JsonResponse
    {
        $its = $awTimelineHelperMobile->getByItineraryIds([$segmentId]);

        if (!$its) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($its[0]);
    }

    /**
     * @Route("/notes/{id}", methods={"GET", "POST"}, requirements={"id"="\d+|([A-Z]{1,2})\.\d+"}, name="awm_timeline_notes")
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function notesAction(
        Request $request,
        EntityManagerInterface $entityManager,
        ItineraryHelper $itineraryHelper,
        PlanManager $planManager,
        PlanFileManager $planFileManager,
        Saver $saver,
        AwTwigExtension $awTwigExtension,
        string $id
    ): JsonResponse {
        if (false !== strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = \json_decode((string) $request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

            if (\is_array($data)) {
                $request->request->replace($data);
            }
        }

        if (false === strpos($id, '.')) {
            if (!$plan = $entityManager->getRepository(Plan::class)->find((int) $id)) {
                throw $this->createNotFoundException();
            }

            return $this->notesPlan($request, $plan, $planManager, $planFileManager, $awTwigExtension);
        }

        [$kind, $itineraryId] = explode('.', $id);
        $itineraryId = (int) $itineraryId;
        $type = $itineraryHelper->getTypeByKind($kind);

        if ('flight' === $type
            && $tripId = $entityManager->getConnection()->fetchOne('SELECT TripID from TripSegment WHERE TripSegmentID = ' . $itineraryId . ' LIMIT 1')
        ) {
            $itineraryId = $tripId;
        }
        $itinerary = $itineraryHelper->findItinerary($type, $itineraryId);

        if (!$this->isGranted('EDIT', $itinerary)) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->jsonResponse([
                'notes' => $awTwigExtension->auto_link_abs($itinerary->getNotes()),
                'files' => $planFileManager->getFlatFiles($itinerary->getFiles()),
            ]);
        }

        try {
            if ($request->request->has('notes')) {
                $notes = StringHandler::cleanHtmlText($request->request->get('notes', ''));
                $notes = PlanManager::cleanBlankLineInEndText($notes);
                $itinerary->setNotes($notes);
                $entityManager->persist($itinerary);
                $entityManager->flush();
            }

            if ($request->files->count()) {
                /** @var UploadedFile $uploadedFile */
                $file = $request->files->getIterator()->current();
                $saver->uploadFile($file, $itinerary);
            }

            $entityManager->refresh($itinerary);
        } catch (\LengthException $exception) {
            return new JsonResponse([
                'status' => false,
                'error' => $exception->getMessage(),
            ]);
        }

        return new JsonResponse([
            'status' => true,
            'notes' => $awTwigExtension->auto_link_abs($itinerary->getNotes()),
            'files' => $planFileManager->getFlatFiles($itinerary->getFiles()),
        ]);
    }

    /**
     * @Route("/notes/files/{id}/{fileId}", methods={"GET", "POST", "DELETE"}, requirements={"id"="\d+|([A-Z]{1,2})\.\d+","fileId"="\d+"}, name="awm_timeline_notes_files")
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function notesFilesAction(
        Request $request,
        EntityManagerInterface $entityManager,
        ItineraryHelper $itineraryHelper,
        PlanManager $planManager,
        PlanFileManager $planFileManager,
        AuthorizationCheckerInterface $authorizationChecker,
        string $id,
        int $fileId
    ) {
        if (false === strpos($id, '.')) {
            if (!$plan = $entityManager->getRepository(Plan::class)->find((int) $id)) {
                throw $this->createNotFoundException();
            }

            if (!$authorizationChecker->isGranted('EDIT', $plan)) {
                throw new AccessDeniedException('Access Denied');
            }

            if ($request->isMethod(Request::METHOD_POST)) {
                return $this->forward('AwardWallet\MainBundle\Controller\TravelPlanController::uploadFile', [
                    'request' => $request,
                    'planId' => $plan->getId(),
                    'planManager' => $planManager,
                    'planFileManager' => $planFileManager,
                ]);
            }

            $planFile = $entityManager->getRepository(Entity\Files\PlanFile::class)->find($fileId);

            if (!$planFile) {
                throw $this->createNotFoundException();
            }

            if ($request->isMethod(Request::METHOD_GET)) {
                return $this->forward('AwardWallet\MainBundle\Controller\TravelPlanController::fetchFile', [
                    'request' => $request,
                    'planFileId' => $planFile->getId(),
                    'planFileManager' => $planFileManager,
                ]);
            }

            if ($request->isMethod(Request::METHOD_DELETE)) {
                return $this->forward('AwardWallet\MainBundle\Controller\TravelPlanController::removeFile', [
                    'planFileId' => $planFile->getId(),
                    'planFileManager' => $planFileManager,
                    'planManager' => $planManager,
                ]);
            }
        }

        throw $this->createNotFoundException();
    }

    private function notesPlan(
        Request $request,
        Plan $plan,
        PlanManager $planManager,
        PlanFileManager $planFileManager,
        AwTwigExtension $awTwigExtension
    ): JsonResponse {
        if (!$this->isGranted('EDIT', $plan)) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->jsonResponse([
                'notes' => $awTwigExtension->auto_link_abs($plan->getNotes()),
                'files' => $planFileManager->getFlatFiles($plan->getFiles()),
            ]);
        }

        try {
            if ($request->request->has('notes')) {
                $planManager->updateNoteText($plan, $request->request->get('notes', ''));
            }

            if ($request->files->count()) {
                /** @var UploadedFile $uploadedFile */
                $file = $request->files->getIterator()->current();
                $planManager->attachFile($plan, $file, $plan->getUser());
            }
        } catch (\LengthException|\InvalidArgumentException $exception) {
            return new JsonResponse([
                'status' => false,
                'error' => $exception->getMessage(),
            ]);
        }

        return new JsonResponse([
            'status' => true,
            'notes' => $awTwigExtension->auto_link_abs($plan->getNotes()),
            'files' => $planFileManager->getFlatFiles($plan->getFiles()),
        ]);
    }
}
