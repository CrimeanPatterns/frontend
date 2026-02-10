<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\Paginator\Paginator;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\SocksMessaging\BookingMessaging;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use AwardWallet\WidgetBundle\Widget\BookingLeftMenuWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/awardBooking")
 */
class ListController extends AbstractController
{
    public static $perPage = 25;

    private AwTokenStorageInterface $tokenStorage;
    private BookingLeftMenuWidget $bookingLeftMenuWidget;

    public function __construct(AwTokenStorageInterface $tokenStorage, BookingLeftMenuWidget $bookingLeftMenuWidget)
    {
        $this->tokenStorage = $tokenStorage;
        $this->bookingLeftMenuWidget = $bookingLeftMenuWidget;
    }

    /**
     * @Security("is_granted('USER_BOOKING_PARTNER')")
     * @Route("/queue", name="aw_booking_list_queue")
     * @Template("@AwardWalletMain/Booking/List/queue.html.twig")
     * @return array
     */
    public function queueAction(
        Request $request,
        LocalizeService $localizeService,
        Paginator $paginator,
        ClientInterface $client,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        /** @var Usr $user */
        $user = $this->getUser();
        $this->bookingLeftMenuWidget->setActiveItem("queue");
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getQueueQuery(
            $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBookerByUser($user)->getUserid(),
            $request->query,
            $localizeService
        );

        if (!$authorizationChecker->isGranted('USER_BOOKING_MANAGER')) {
            $query->leftJoin('r.SiteAd', 'c', 'WITH');
            $query->leftJoin('c.users', 'u', 'WITH');
            $query->andWhere('u.userid = ' . $user->getUserid());
        }

        $paginator->setOptions([
            'sort_fields' => [
                'id' => 'r.AbRequestID',
                'depDate' => 's.DepDateFrom',
                'contactName' => 'r.ContactName',
                'assigned' => ['a.firstname', 'a.lastname'],
                'lastUpdate' => 'r.LastUpdateDate',
                'status' => 'r.Status',
                'internalStatus' => 's.Status',
                'until' => 'r.RemindDate',
            ],
            'default_sort' => 'lastUpdate',
            'defaultPaginationTemplate' => '@AwardWalletMain/Booking/List/pagination.html.twig',
            'defaultSortableTemplate' => '@AwardWalletMain/Booking/List/sortable.html.twig',
        ]);
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), self::$perPage);

        $items = $pagination->getItems();
        $items = array_map(function (AbRequest $item) {
            return $item->getAbRequestId();
        }, $items);

        // get booker
        $abBookerInfo = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBookerByUser($this->tokenStorage->getToken()->getUser())->getBookerInfo();

        return [
            'messaging' => json_encode($client->getClientData()),
            'channel' => BookingMessaging::CHANNEL_BOOKER_ONLINE,
            'requestRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class),
            'requestStatusRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequestStatus::class),
            'userRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
            'bookerInfoRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbBookerInfo::class),
            'pagination' => $pagination,
            'req' => $request->query,
            'bookerInfo' => $abBookerInfo,
            'allMessageCount' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbMessage::class)->allMessageCountForRequests($items),
            'internalMessageCount' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbMessage::class)->internalMessageCountForRequests($items),
            'isNewInternal' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbMessage::class)->isNewInternalForRequests($items, $user),
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Route("/requests", name="aw_booking_list_requests")
     * @Template("@AwardWalletMain/Booking/List/requests.html.twig")
     */
    public function requestsAction(
        Request $request,
        RouterInterface $router,
        SessionInterface $session,
        PageVisitLogger $pageVisitLogger
    ) {
        /** @var \Doctrine\ORM\QueryBuilder $qb */
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();

        $archive = $request->query->get('archive', 0);
        $active = $request->query->get('archive', 1) && !$archive;
        $this->bookingLeftMenuWidget->setActiveItem($archive ? "archive" : "active");

        $qb->select('r')
            ->from(AbRequest::class, 'r')
            ->orderBy('r.AbRequestID', 'desc')
            ->where('r.User = :user')
            ->andWhere('r.Status in (:status)');
        $qb->setParameter('user', $this->tokenStorage->getToken()->getUser()->getUserid());

        if ($archive) {
            $qb->setParameter('status', [AbRequest::BOOKING_STATUS_CANCELED, AbRequest::BOOKING_STATUS_PROCESSING]);
        }

        if ($active) {
            $qb->setParameter('status', AbRequest::getActiveStatuses());
        }

        $result = $qb->getQuery()->getResult();

        if (count($result) == 0) {
            return $this->redirect($router->generate('aw_booking_add_index'));
        }

        if (count($result) == 1) {
            return $this->redirect($router->generate('aw_booking_view_index', ['id' => $result[0]->getAbrequestid()]));
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_AWARD_BOOKINGS);

        return [
            'requestRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class),
            'id' => $session->getFlashBag()->get('booking_new_request_id'),
            'result' => $result,
            'archive' => $archive,
        ];
    }
}
