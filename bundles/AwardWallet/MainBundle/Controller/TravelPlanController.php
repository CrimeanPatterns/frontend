<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Files\PlanFile;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Repositories\TimelineShareRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Manager\Files\PlanFileManager;
use AwardWallet\MainBundle\Timeline\PlanManager;
use Doctrine\Persistence\ManagerRegistry;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/plan")
 */
class TravelPlanController extends AbstractController implements TranslationContainerInterface
{
    private PlanManager $manager;
    private UseragentRepository $uaRepo;
    private AwTokenStorageInterface $tokenStorage;
    private ManagerRegistry $doctrine;
    private TimelineShareRepository $timelineShareRepo;
    private LoggerInterface $logger;
    private AuthorizationCheckerInterface $authorizationChecker;

    /**
     * TravelPlanController constructor.
     */
    public function __construct(
        PlanManager $manager,
        UseragentRepository $uaRepo,
        TimelineShareRepository $timelineShareRepo,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        ManagerRegistry $doctrine,
        LoggerInterface $logger
    ) {
        $this->manager = $manager;
        $this->uaRepo = $uaRepo;
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->timelineShareRepo = $timelineShareRepo;
        $this->logger = $logger;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/create", name="aw_travelplan_create", methods={"POST"}, options={"expose"=true})
     */
    public function createAction(Request $request)
    {
        $agent = null;
        $user = $this->tokenStorage->getBusinessUser();

        $agentId = $request->request->get('userAgentId');

        if ($agentId == 'my') {
            $agentId = null;
        }

        if (!empty($agentId)) {
            $agent = $this->uaRepo->find($agentId);

            if (empty($agent)) {
                throw new BadRequestHttpException("Invalid user agent");
            }

            if ($agent->getAgentid() != $user || !$agent->isFamilyMember()) {
                if ($agent->isFamilyMember()) {
                    $timelineShare = $user->getTimelineShareWith($agent->getAgentid(), $agent);
                } else {
                    $timelineShare = $user->getTimelineShareWith($agent->getClientid());
                }

                if (empty($timelineShare)) {
                    throw new BadRequestHttpException("Invalid user agent");
                }

                if ($timelineShare->getUserAgent()->getTripAccessLevel() == 0) {
                    throw new BadRequestHttpException('Access denied');
                }
            }
        }

        if (isset($timelineShare)) {
            $plan = $this->manager->createShared($timelineShare, intval($request->request->get("startTime")));
        } else {
            $plan = $this->manager->create($agent, intval($request->request->get("startTime")));
        }

        if (!empty($plan)) {
            $this->logger->info('aw_travelplan_create', [
                'UserID' => $user->getUserid(),
                'AgentID' => ($agent instanceof Useragent ? ($agent->isFamilyMember() ? $agent->getAgentid() : $agent->getClientid()) : null),
                'TravelPlanID' => $plan->getId(),
                'Type' => isset($timelineShare) ? 'share' : 'plan',
            ]);

            return new JsonResponse([
                'id' => $plan->getId(),
                'startTime' => $plan->getStartDate()->getTimestamp(),
            ]);
        }

        // could not do new JsonResponse(null), see https://github.com/symfony/symfony/issues/11679
        $result = new JsonResponse();
        $result->setData(null);

        return $result;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/delete/{plan}", methods={"POST"}, name="aw_travelplan_delete", options={"expose"=true})
     */
    public function deleteAction($plan, Request $request)
    {
        $tp = $this->doctrine->getRepository(\AwardWallet\MainBundle\Entity\Plan::class)->find($plan);

        if (!$tp) {
            return new JsonResponse('error, plan not found');
        }

        if (!$this->authorizationChecker->isGranted('EDIT', $tp)) {
            return new JsonResponse('Access denied');
        }

        $this->manager->delete($tp);

        return new JsonResponse('ok');
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/rename/{plan}", methods={"POST"}, name="aw_travelplan_rename", options={"expose"=true})
     * @ParamConverter("plan", class="AwardWalletMainBundle:Plan", options={"id" = "plan"})
     * @return JsonResponse
     */
    public function renameAction(Plan $plan, Request $request)
    {
        if (!$this->authorizationChecker->isGranted('EDIT', $plan)) {
            return new JsonResponse('Access denied');
        }

        $this->manager->rename($plan, $request->get('name'));

        return new JsonResponse('ok');
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/move", name="aw_travelplan_move", methods={"POST"}, options={"expose"=true})
     * @return JsonResponse
     */
    public function moveAction(Request $request)
    {
        if (!$request->request->has('planId') || !$request->request->has('nextSegmentId') || !$request->request->has('nextSegmentTs') || !$request->request->has('type')) {
            return new JsonResponse('Invalid arguments');
        }

        if (!$plan = $this->doctrine->getRepository(\AwardWallet\MainBundle\Entity\Plan::class)->find(intval($request->request->get('planId')))) {
            return new JsonResponse('Unknown plan');
        }

        if (!$this->authorizationChecker->isGranted('EDIT', $plan)) {
            return new JsonResponse('Access denied');
        }

        if (($request->request->get('type') == 'planStart' && preg_match("/^PE/", $request->request->get('nextSegmentId')))
            || ($request->request->get('type') == 'planEnd' && preg_match("/^PS/", $request->request->get('nextSegmentId')))
        ) {
            $this->manager->delete($plan);

            return new JsonResponse('ok');
        }

        $this->manager->move(
            $plan,
            $request->request->get('nextSegmentTs'),
            $request->request->get('type'),
            $request->request->get('nextSegmentId')
        );

        return new JsonResponse([
            'id' => $plan->getId(),
            'startTime' => $plan->getStartDate()->getTimestamp(),
        ]);
    }

    /**
     * @Route("/update/note/{planId}", methods={"POST"}, name="aw_timeline_plan_update_note", options={"expose"=true})
     * @ParamConverter("plan", class="AwardWalletMainBundle:Plan", options={"id"="planId"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function updateNotes(
        Request $request,
        Plan $plan,
        PlanManager $planManager,
        PlanFileManager $planFileManager,
        TranslatorInterface $translator
    ): JsonResponse {
        $this->checkAccess($this->authorizationChecker, $plan);

        $data = json_decode($request->getContent());

        if (empty($data) || !property_exists($data, 'note')) {
            throw new \Exception('Error');
        }

        if (property_exists($data, 'fileDescription')) {
            $filesInfo = [];

            foreach ($data->fileDescription as $fileDescription) {
                $filesInfo[] = [
                    'id' => (int) $fileDescription[0],
                    'description' => (string) $fileDescription[1],
                ];
            }
            $planFileManager->updatePlanFilesDescriptions($filesInfo, $plan);
        }

        try {
            $planManager->updateNoteText($plan, $data->note);
        } catch (\LengthException $exception) {
            return new JsonResponse([
                'status' => false,
                'error' => $exception->getMessage(),
            ]);
        }

        return new JsonResponse([
            'status' => true,
            'note' => $plan->getNotes(),
            'files' => $planFileManager->getListFiles($plan->getFiles()),
        ]);
    }

    /**
     * @Route("/upload/file/{planId}", methods={"POST"}, name="aw_timeline_plan_upload_file", options={"expose"=true})
     * @ParamConverter("plan", class="AwardWalletMainBundle:Plan", options={"id"="planId"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function uploadFile(
        Request $request,
        Plan $plan,
        PlanManager $planManager,
        PlanFileManager $planFileManager
    ): JsonResponse {
        $this->checkAccess($this->authorizationChecker, $plan);

        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        if (null === $file) {
            return new JsonResponse([
                'status' => false,
            ]);
        }

        try {
            $planManager->attachFile($plan, $file, $this->tokenStorage->getBusinessUser());
        } catch (\LengthException|\InvalidArgumentException $exception) {
            return new JsonResponse([
                'status' => false,
                'error' => $exception->getMessage(),
            ]);
        }

        return new JsonResponse([
            'status' => true,
            'files' => $planFileManager->getListFiles($plan->getFiles()),
        ]);
    }

    /**
     * @Route("/fetch/file/{planFileId}", name="aw_timeline_plan_fetch_file", options={"expose"=true})
     * @ParamConverter("planFile", class="AwardWalletMainBundle:Files\PlanFile", options={"id"="planFileId"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function fetchFile(
        Request $request,
        PlanFile $planFile,
        PlanFileManager $planFileManager
    ): Response {
        $this->checkAccess($this->authorizationChecker, $planFile->getPlan());

        $response = $planFileManager->fetchResponse(
            $planFile,
            $request->get('response_streaming', false)
        );

        if (null === $response) {
            throw new NotFoundHttpException('Not Found');
        }

        return $response;
    }

    /**
     * @Route("/remove/file/{planFileId}", methods={"POST"}, name="aw_timeline_plan_remove_file", options={"expose"=true})
     * @ParamConverter("planFile", class="AwardWalletMainBundle:Files\PlanFile", options={"id"="planFileId"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function removeFile(
        PlanFile $planFile,
        PlanFileManager $planFileManager,
        PlanManager $planManager
    ): JsonResponse {
        $this->checkAccess($this->authorizationChecker, $planFile->getPlan());

        $planFileManager->removeFile($planFile);
        $planManager->updateEntity($planFile->getPlan());

        return new JsonResponse(['status' => true]);
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('confirmation', 'trips'))->setDesc('Confirmation'),
            (new Message('edit-notes', 'trips'))->setDesc('Edit Notes'),
            (new Message('you-sure-delete-file', 'trips'))->setDesc('Are you sure you wish to delete this file?'),
            (new Message('you-sure-also-delete-notes', 'trips'))->setDesc('Are you sure? This will also delete your notes.'),
            (new Message('text-is-too-big'))->setDesc('Your text is too big'),
            (new Message('drop-files-here'))->setDesc('Drop files here'),
        ];
    }

    private function checkAccess(AuthorizationCheckerInterface $authorizationChecker, Plan $plan): ?bool
    {
        if ($authorizationChecker->isGranted('EDIT', $plan)) {
            return true;
        }

        throw new AccessDeniedException('Access Denied');
    }
}
