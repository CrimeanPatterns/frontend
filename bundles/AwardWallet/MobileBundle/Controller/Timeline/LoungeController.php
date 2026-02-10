<?php

namespace AwardWallet\MobileBundle\Controller\Timeline;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\DragonPass;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\LoungeKey;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\PriorityPass;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\ViewInflater;
use AwardWallet\MainBundle\Service\Lounge\Logger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/lounge")
 */
class LoungeController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;

    private EntityManagerInterface $em;

    private AuthorizationCheckerInterface $authChecker;

    private TripsegmentRepository $tripsegmentRepository;

    private Logger $logger;

    private ApiVersioningService $apiVersioningService;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $em,
        AuthorizationCheckerInterface $authChecker,
        TripsegmentRepository $tripsegmentRepository,
        Logger $logger,
        ApiVersioningService $apiVersionMobile
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->authChecker = $authChecker;
        $this->tripsegmentRepository = $tripsegmentRepository;
        $this->logger = $logger;
        $this->apiVersioningService = $apiVersionMobile;
    }

    /**
     * @Route("/select-cards",
     *     name="awm_timeline_lounge_select_cards",
     *     methods={"GET", "POST"}
     * )
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     */
    public function selectCardsAction(Request $request, ViewInflater $inflater): JsonResponse
    {
        /** @var Usr $user */
        $user = $this->tokenStorage->getUser();

        if (!$user) {
            return new JsonResponse(null, 404);
        }

        if ($request->isMethod('POST')) {
            $autoDetectFeature = $this->apiVersioningService->supports(MobileVersions::LOUNGE_AUTO_DETECT_CARDS);
            $autoDetectEnabled = false;

            if (
                $request->request->has('autoDetect')
                && is_numeric($request->request->get('autoDetect'))
                && $request->request->get('autoDetect') == 1
            ) {
                $autoDetectEnabled = true;
            }

            if (!$request->request->has('selectedCards') || !is_array($selectedCards = $request->request->get('selectedCards'))) {
                return new JsonResponse(null, 404);
            }

            return new JsonResponse(['success' => $this->saveSelectedCards(
                $user,
                $selectedCards,
                $autoDetectFeature && $autoDetectEnabled
            )]);
        }

        return new JsonResponse($inflater->listCards($user));
    }

    /**
     * @Route("/list", name="awm_timeline_lounge_list", methods={"POST"})
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     */
    public function listLoungesAction(Request $request, ViewInflater $inflater): JsonResponse
    {
        $segmentId = $request->request->get('segmentId');

        if (!is_string($segmentId) || !preg_match("/^(T|L)\.(\d+)$/ims", $segmentId, $matches)) {
            return new JsonResponse(null, 404);
        }

        $kind = strtoupper($matches[1]);
        /** @var Tripsegment $tripSegment */
        $tripSegment = $this->tripsegmentRepository->find($matches[2]);

        /** @var Usr $user */
        $user = $this->tokenStorage->getUser();

        if (
            !$tripSegment
            || !$user
            || !$this->authChecker->isGranted('VIEW', $tripSegment->getTripid())
        ) {
            return new JsonResponse(null, 404);
        }

        switch ($kind) {
            case 'T':
                $stage = $this->getStringParam($request, 'stage');

                if (empty($stage) || !in_array($stage, [ViewInflater::STAGE_DEP, ViewInflater::STAGE_ARR])) {
                    return new JsonResponse(null, 404);
                }

                return new JsonResponse($inflater->listLounges($user, $tripSegment, $stage));

            default:
                return new JsonResponse($inflater->listLounges(
                    $user,
                    $tripSegment,
                    null,
                    $this->getStringParam($request, 'arrTerminal'),
                    $this->getStringParam($request, 'depTerminal')
                ));
        }
    }

    /**
     * @Route("/{loungeId}",
     *     name="awm_timeline_lounge_details",
     *     requirements={
     *         "loungeId" = "\d+",
     *     },
     *     methods={"GET"}
     * )
     * @ParamConverter("lounge", class="AwardWalletMainBundle:Lounge", options={"id": "loungeId"})
     */
    public function detailsAction(ViewInflater $inflater, Lounge $lounge): JsonResponse
    {
        /** @var Usr $user */
        $user = $this->tokenStorage->getUser();

        if (
            !$user
            || !$lounge->isVisible()
            || !$lounge->isAvailable()
        ) {
            return new JsonResponse(null, 404);
        }

        return new JsonResponse($inflater->details($user, $lounge));
    }

    private function saveSelectedCards(Usr $user, array $cards, bool $autoDetect): bool
    {
        if ($this->authChecker->isGranted('USER_IMPERSONATED')) {
            return false;
        }

        $this->logger->info(sprintf(
            'saveSelectedCards json: %s, detect cards: %s, user: %d',
            json_encode($cards),
            $autoDetect ? 'true' : 'false',
            $user->getId()
        ));

        $cards = array_map(function ($id) {
            if (in_array($id, [PriorityPass::getCardId(), DragonPass::getCardId(), LoungeKey::getCardId()])) {
                return $id;
            }

            return substr($id, 2);
        }, array_filter($cards, function ($id) use ($user) {
            if (is_string($id) && (in_array($id, [PriorityPass::getCardId(), DragonPass::getCardId(), LoungeKey::getCardId()]) || preg_match('/^cc\d+$/', $id))) {
                return true;
            }

            $this->logger->error(sprintf(
                'user %d selected invalid card: "%s"',
                $user->getId(),
                json_encode($id)
            ));

            return false;
        }));
        $ccIds = array_filter($cards, 'is_numeric');
        $cards = array_flip($cards);

        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        $this->logger->info(sprintf(
            'user %d selected cards: %s',
            $user->getId(),
            implode(', ', array_keys($cards))
        ));

        try {
            if (count($ccIds) > 0) {
                $conn->executeStatement(
                    "
                        DELETE FROM UserCard WHERE UserID = ? AND CreditCardID NOT IN (?)
                    ", [$user->getId(), $ccIds], [\PDO::PARAM_INT, Connection::PARAM_INT_ARRAY]
                );
            } else {
                $conn->executeStatement("
                        DELETE FROM UserCard WHERE UserID = ?
                    ", [$user->getId()], [\PDO::PARAM_INT]
                );
            }

            foreach ($ccIds as $ccId) {
                $conn->executeStatement("
                    INSERT IGNORE INTO UserCard (UserID, CreditCardID) VALUES (?, ?)
                ", [$user->getId(), $ccId], [\PDO::PARAM_INT, \PDO::PARAM_INT]
                );
            }

            $conn->executeStatement("
                UPDATE Usr 
                SET 
                    AvailableCardsUpdateDate = NOW(),
                    AutoDetectLoungeCards = ?,
                    HavePriorityPassCard = ?,
                    HaveDragonPassCard = ?,
                    HaveLoungeKeyCard = ?
                WHERE UserID = ?
            ", [
                $autoDetect ? 1 : 0,
                isset($cards[PriorityPass::getCardId()]) ? 1 : 0,
                isset($cards[DragonPass::getCardId()]) ? 1 : 0,
                isset($cards[LoungeKey::getCardId()]) ? 1 : 0,
                $user->getId(),
            ]);

            $conn->commit();

            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'user %d failed to select cards: %s',
                $user->getId(),
                $e->getMessage()
            ));
            $conn->rollBack();

            return false;
        }
    }

    private function getStringParam(Request $request, string $key): ?string
    {
        if (
            $request->request->has($key)
            && is_string($val = $request->request->get($key))
            && !empty($val)
        ) {
            return $val;
        }

        return null;
    }
}
