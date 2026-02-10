<?php

declare(strict_types=1);

namespace AwardWallet\MobileBundle\Controller\Timeline;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\BlockHelper;
use AwardWallet\MainBundle\Timeline\PlanManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/plan")
 */
class PlanController extends AbstractController
{
    use JsonTrait;

    private PlanManager $planManager;
    private BlockHelper $blockHelper;
    private EntityManagerInterface $entityManager;
    private AwTokenStorageInterface $awTokenStorage;

    public function __construct(
        PlanManager $planManager,
        BlockHelper $blockHelper,
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $awTokenStorage,
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
        $this->planManager = $planManager;
        $this->blockHelper = $blockHelper;
        $this->entityManager = $entityManager;
        $this->awTokenStorage = $awTokenStorage;
    }

    /**
     * @Route("/create/{agentId}/{startTime}",
     *     name="awm_plan_create",
     *     methods={"POST"},
     *     requirements={
     *         "startTime"  = "\d{9,10}",
     *         "agentId"    = "my|\d{1,20}"
     *     }
     * )
     * @Security("is_granted('CSRF')")
     */
    public function createAction(string $agentId, string $startTime): JsonResponse
    {
        $agent = $this->getTimelineAgent($agentId);
        $timelineShare = $agent ? $this->getTimelineShare($agent) : null;
        $startTime = (int) $startTime;

        if ($timelineShare) {
            $plan = $this->planManager->createShared($timelineShare, $startTime);
        } else {
            $plan = $this->planManager->create($agent, $startTime);
        }

        if (!$plan) {
            return $this->errorJsonResponse('Invalid parameters');
        }

        return $this->jsonResponse([
            'id' => $plan->getId(),
            'startTime' => $plan->getStartDate()->getTimestamp(),
        ]);
    }

    /**
     * @Route("/delete/{planId}",
     *     name="awm_plan_delete",
     *     methods={"POST"},
     *     requirements={"planId" = "\d{1,20}"}
     * )
     * @Security("is_granted('CSRF')")
     * @ParamConverter("plan", class="AwardWalletMainBundle:Plan", options={"id" = "planId"})
     */
    public function deleteAction(Plan $plan): JsonResponse
    {
        if (!$this->isGranted('EDIT', $plan)) {
            throw $this->createNotFoundException();
        }

        $this->planManager->delete($plan);

        return $this->successJsonResponse();
    }

    /**
     * @Route("/rename/{planId}",
     *     name="awm_plan_rename",
     *     methods={"POST"},
     *     requirements={"planId" = "\d{1,20}"}
     * )
     * @Security("is_granted('CSRF')")
     * @ParamConverter("plan", class="AwardWalletMainBundle:Plan", options={"id" = "planId"})
     * @JsonDecode
     */
    public function renameAction(Request $request, Plan $plan): JsonResponse
    {
        if (!$this->isGranted('EDIT', $plan)) {
            throw $this->createNotFoundException();
        }

        $this->planManager->rename($plan, $request->request->get('name'));

        return $this->successJsonResponse();
    }

    /**
     * @Route("/move/{planId}",
     *     name="awm_plan_move",
     *     methods={"POST"},
     *     requirements={"planId" = "\d{1,20}"}
     * )
     * @Security("is_granted('CSRF')")
     * @ParamConverter("plan", class="AwardWalletMainBundle:Plan", options={"id" = "planId"})
     * @JsonDecode
     */
    public function moveAction(Request $request, Plan $plan): JsonResponse
    {
        if (!$this->isGranted('EDIT', $plan)) {
            throw $this->createNotFoundException();
        }

        if (
            !$request->request->has('nextSegmentId')
            || !$request->request->has('nextSegmentTs')
            || !$request->request->has('type')
        ) {
            return $this->errorJsonResponse('Invalid parameters');
        }

        [
            'nextSegmentId' => $nextSegmentId,
            'nextSegmentTs' => $nextSegmentTs,
            'type' => $planType,
        ] = $request->request->all();

        $this->planManager->move($plan, $nextSegmentTs, $planType, $nextSegmentId);

        return $this->jsonResponse([
            'id' => $plan->getId(),
            'startTime' => $plan->getStartDate()->getTimestamp(),
        ]);
    }

    protected function getTimelineAgent(string $agentId): ?Useragent
    {
        if ('my' === $agentId) {
            return null;
        }

        $agent = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->find($agentId);

        if (!$agent) {
            throw $this->createNotFoundException();
        }

        return $agent;
    }

    protected function getTimelineShare(Useragent $agent): ?TimelineShare
    {
        if (!$this->isGranted('EDIT_TIMELINE', $agent)) {
            throw $this->createNotFoundException();
        }

        $user = $this->awTokenStorage->getBusinessUser();
        $timelineShare = null;

        if (
            ($agent->getAgentid()->getUserid() != $user->getUserid())
            || !$agent->isFamilyMember()
        ) {
            if ($agent->isFamilyMember()) {
                $timelineShare = $user->getTimelineShareWith($agent->getAgentid(), $agent);
            } else {
                $timelineShare = $user->getTimelineShareWith($agent->getClientid());
            }

            if (!$timelineShare) {
                throw $this->createAccessDeniedException();
            }
        }

        return $timelineShare;
    }
}
