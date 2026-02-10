<?php

namespace AwardWallet\MainBundle\Controller\Timeline;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\Tripit\Async\ImportReservationsTask;
use AwardWallet\MainBundle\Service\Tripit\TripitHelper;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TripitNotificationController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;
    private TripitHelper $tripitHelper;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        TripitHelper $tripitHelper
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->tripitHelper = $tripitHelper;
    }

    /**
     * Подписка на новые события уведомлений.
     *
     * @Route("/tripit/subscribe", name="aw_timeline_tripit_subscribe", methods={"GET"})
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function actionSubscribe()
    {
        $tripitUser = new TripitUser($this->tokenStorage->getUser(), $this->entityManager);
        $this->tripitHelper->subscribe($tripitUser);

        return $this->redirectToRoute('aw_timeline');
    }

    /**
     * Отказ от подписки на новые события уведомлений.
     *
     * @Route("/tripit/unsubscribe", name="aw_timeline_tripit_unsubscribe", methods={"GET"})
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function actionUnsubscribe()
    {
        $tripitUser = new TripitUser($this->tokenStorage->getUser(), $this->entityManager);
        $this->tripitHelper->unsubscribe($tripitUser);

        return $this->redirectToRoute('aw_timeline');
    }

    /**
     * Получение новых событий из TripIt.
     *
     * В теле запроса придёт строка следующего вида
     * `type=trip&id=1001&change=plans_updated&oauth_token_key=2f4b0000`, где:
     * - type — тип объекта, который был изменён. На данный момент в нём может быть только "trip",
     * - id — идентификатор объекта, который был изменён, например, идентификатор поездки,
     * - change — тип изменения в объекте. Допустимые типы изменений: "plans_created", "plans_updated", "plans_deleted" и др.,
     * - oauth_token_key — публичная часть токена пользователя, на которого мы подписаны на получение событий.
     *
     * @Route("/tripit/notifications", name="aw_timeline_tripit_notifications", methods={"POST"})
     */
    public function actionNotifications(Request $request, LoggerInterface $logger, Process $asyncProcess)
    {
        $logger->info('TripIt notifications: ' . $request->getContent());

        $task = new ImportReservationsTask($request->getContent());
        $asyncProcess->execute($task);

        return new Response('Success');
    }
}
