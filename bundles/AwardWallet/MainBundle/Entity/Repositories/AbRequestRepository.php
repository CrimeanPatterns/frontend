<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\ORM\EntityRepository;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class AbRequestRepository extends EntityRepository implements TranslationContainerInterface
{
    public $statuses = [];
    public $reasons = [];
    /**
     * @var array<int, string>
     */
    public $statusCodes = [];

    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->statuses = [
            AbRequest::BOOKING_STATUS_BOOKED_OPENED => 'booking.statuses.opened-booked',
            AbRequest::BOOKING_STATUS_PENDING => 'booking.statuses.opened',
            AbRequest::BOOKING_STATUS_BOOKED => 'booking.statuses.booked',
            AbRequest::BOOKING_STATUS_PROCESSING => 'booking.statuses.paid',
            AbRequest::BOOKING_STATUS_CANCELED => 'booking.statuses.canceled',
            AbRequest::BOOKING_STATUS_FUTURE => 'booking.statuses.future',
            AbRequest::BOOKING_STATUS_NOT_VERIFIED => 'booking.statuses.not-verified',
        ];

        $this->statusCodes = [
            AbRequest::BOOKING_STATUS_BOOKED_OPENED => 'opened-booked',
            AbRequest::BOOKING_STATUS_PENDING => 'opened',
            AbRequest::BOOKING_STATUS_BOOKED => 'booked',
            AbRequest::BOOKING_STATUS_PROCESSING => 'paid',
            AbRequest::BOOKING_STATUS_CANCELED => 'canceled',
            AbRequest::BOOKING_STATUS_FUTURE => 'future',
            AbRequest::BOOKING_STATUS_NOT_VERIFIED => 'not-verified',
        ];

        $this->reasons = [
            AbRequest::BOOKING_REASON_NOT_RESPONSE => /** @Desc("No response") */ 'booking.reasons.not-response',
            AbRequest::BOOKING_REASON_DIDNT_LIKE => /** @Desc("Didn`t like") */ 'booking.reasons.didnt-like',
            AbRequest::BOOKING_REASON_CANCEL => /** @Desc("Change/cancel") */ 'booking.reasons.cancel',
            AbRequest::BOOKING_REASON_REJECTED => /** @Desc("We rejected the project") */ 'booking.reason.rejected',
            AbRequest::BOOKING_REASON_AFTER_PITCH => /** @Desc("Booked On Own AFTER Pitch") */ 'booking.reason.after-pitch',
            AbRequest::BOOKING_REASON_REVEALED => /** @Desc("Flight Details Revealed") */ 'booking.reason.revealed',
            AbRequest::BOOKING_REASON_MISSED_CALL => /** @Desc("Missed Phone Appointment") */ 'booking.reason.missed-call',
            AbRequest::BOOKING_REASON_NOBOOK_GOOD_ROUTECOST => 'booking.reason.nobook.good-routecost',
            AbRequest::BOOKING_REASON_NOBOOK_BAD_ROUTE => 'booking.reason.nobook.bad-route',
            AbRequest::BOOKING_REASON_NOBOOK_BAD_COST => 'booking.reason.nobook.bad-cost',
            AbRequest::BOOKING_REASON_NOBOOK_BAD_ROUTECOST => 'booking.reason.nobook.bad-routecost',
        ];
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('booking.statuses.not-verified', 'booking'))->setDesc("Email Not Verified"),
            new Message('booking.statuses.opened-booked', 'booking'),
            new Message('booking.statuses.opened', 'booking'),
            new Message('booking.statuses.paid', 'booking'),
            new Message('booking.statuses.booked', 'booking'),
            new Message('booking.statuses.canceled', 'booking'),
            new Message('booking.statuses.future', 'booking'),
            new Message('booking.reasons.not-response', 'booking'),
            new Message('booking.reasons.didnt-like', 'booking'),
            new Message('booking.reasons.cancel', 'booking'),
            new Message('booking.reason.rejected', 'booking'),
            new Message('booking.reason.after-pitch', 'booking'),
            (new Message('booking.reason.revealed', 'booking'))->setDesc("Flight Details Revealed"),
            (new Message('booking.reason.missed-call', 'booking'))->setDesc("Missed Phone Appointment"),
            (new Message('booking.reason.nobook.good-routecost', 'booking'))->setDesc('No book- Good routing/cost'),
            (new Message('booking.reason.nobook.bad-route', 'booking'))->setDesc('No Book- Bad routing'),
            (new Message('booking.reason.nobook.bad-cost', 'booking'))->setDesc('No book- Bad Cost'),
            (new Message('booking.reason.nobook.bad-routecost', 'booking'))->setDesc('No Book- Bad route/cost'),
        ];
    }

    public function getStatusDescription($status)
    {
        return $this->statuses[$status];
    }

    /**
     * @param string $status
     * @return string
     */
    public function getStatusCode($status)
    {
        return $this->statusCodes[$status];
    }

    public function allCommentCount(AbRequest $request, $isBooker = true)
    {
        $query = $this->_em->createQueryBuilder()->select('count(m)')->from(AbMessage::class, 'm');
        $query->where('m.RequestID = ' . $request->getAbRequestID())
            ->andWhere('m.Type >= ' . AbMessage::TYPE_COMMON)
            ->andWhere('m.Type <> ' . AbMessage::TYPE_INTERNAL);

        if ($isBooker) {
            return $query->getQuery()->getSingleScalarResult();
        }

        // +1 - automessage
        return $query->getQuery()->getSingleScalarResult() + ($request->getBooker()->getBookerInfo()->getAutoReplyMessage() ? 1 : 0);
    }

    public function internalCommentCount(AbRequest $request)
    {
        $query = $this->_em->createQueryBuilder()->select('count(m)')->from(AbMessage::class, 'm')
            ->andWhere('m.RequestID = ' . $request->getAbRequestID())
            ->andWhere('m.Type in (' . AbMessage::TYPE_INTERNAL . ', ' . AbMessage::TYPE_SHARE_ACCOUNTS_INTERNAL . ')');

        return $query->getQuery()->getSingleScalarResult();
    }

    public function isNewInternal(AbRequest $request, Entity\Usr $user)
    {
        $qb = $this->_em->createQueryBuilder();
        $e = $qb->expr();
        $qb
            ->select('count(abm)')
            ->from(AbMessage::class, 'abm')
            ->join('abm.RequestID', 'r', 'WITH')
            ->leftJoin(
                Entity\AbRequestMark::class,
                'abrm',
                'WITH',
                $e->andX(
                    $e->eq('abm.RequestID', 'abrm.RequestID'),
                    $e->eq('abrm.UserID', ':user')
                )
            )
            ->setParameter(':user', $user->getUserid(), \PDO::PARAM_INT)
            ->andWhere('abm.RequestID = :request')
            ->setParameter(':request', $request->getAbRequestID(), \PDO::PARAM_INT)
            ->andWhere('abm.Type = ' . AbMessage::TYPE_INTERNAL)
            ->andWhere($e->andX(
                $e->isNotNull('abrm.ReadDate'), // exclude requests without marks
                $e->gt('abm.CreateDate', 'abrm.ReadDate')
            ));

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getMessagesByRequestId($id)
    {
        $result = $this->_em->createQueryBuilder()
            ->from(AbMessage::class, 'm')
            ->select('m')
            ->orderBy('m.CreateDate', 'ASC')
            ->where('m.RequestID = ' . $id)
            ->getQuery()->getResult();

        return $result;
    }

    public function getQueueQuery($bId, ParameterBag $request, LocalizeService $localizer)
    {
        $qb = $this->_em->createQueryBuilder();

        if ($id = $request->get('id_filter')) {
            $qb->andWhere('r.AbRequestID = :id');
            $qb->setParameter('id', $id);
        }

        if ($id = $request->get('user_filter')) {
            $qb->andWhere('r.User = :user_id');
            $qb->setParameter('user_id', $id);
        } else {
            if ($name = $request->get('name_filter')) {
                $qb->andWhere($qb->expr()->like('r.ContactName', ":contact"));
                $qb->setParameter('contact', "%" . $name . "%");
            }
        }

        if ($id = $request->get('assiged_filter')) {
            $qb->andWhere('r.AssignedUser = :assigned');
            $qb->setParameter('assigned', $id);
        }

        $status = $request->get('status_filter');

        //        if (is_null($status)) {
        //            // force BOOKING_STATUS_BOOKED_OPENED filter
        //            $request->set('status_filter', $status = AbRequest::BOOKING_STATUS_BOOKED_OPENED);
        //        }
        if (!is_null($status) && $status != '') {
            if ($status == AbRequest::BOOKING_STATUS_BOOKED_OPENED) {
                $qb->andWhere('(r.Status = :status_booked OR r.Status = :status_pending)');
                $qb->setParameter('status_booked', AbRequest::BOOKING_STATUS_BOOKED);
                $qb->setParameter('status_pending', AbRequest::BOOKING_STATUS_PENDING);
            } else {
                $qb->andWhere('r.Status = :status');
                $qb->setParameter('status', $status);
            }
        }

        if ($status = $request->get('internal_status_filter')) {
            $qb->andWhere('r.InternalStatus = :internal_status');
            $qb->setParameter('internal_status', $status);
        }

        if ($date = $request->get('lastupdate_filter')) {
            $date = date_create($date, $localizer->getUserDateTimeZone());

            if ($date) {
                $date->setTimezone(new \DateTimeZone('UTC'));
                $qb->andWhere("r.LastUpdateDate > :from");
                $qb->andWhere("r.LastUpdateDate < :to");
                $qb->setParameter('from', "{$date->format('Y-m-d H:i:s')}");
                $qb->setParameter('to', "{$date->modify('+1 day')->format('Y-m-d H:i:s')}");
            }
        }

        $q = $qb->select('r')
            ->from(AbRequest::class, 'r')
            ->leftJoin('r.AssignedUser', 'a', 'WITH')
            ->leftJoin('r.InternalStatus', 's', 'WITH')
            ->orderBy('r.LastUpdateDate', 'desc')
            ->andWhere('r.BookerUser = ' . $bId);

        if ($bId === 116000) {
            $q->join(Entity\Useragent::class, 'ua', 'WITH', 'r.User = ua.clientid and ua.agentid = 116000');
        }

        return $q;
    }

    /**
     * Get user reference icon.
     *
     * @param string    $size 'small'|'medium'
     * @param Usr|null      $forBooker
     */
    public function getRefIcon(AbRequest $request, $size = 'small', $forBooker = null)
    {
        if ($icon = $request->getRefIconForBooker($forBooker, $size)) {
            return $icon;
        }
        $bookerInfoRep = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\AbBookerInfo::class);

        return $bookerInfoRep->getBookerByRef(0)->getIcon($size);
    }

    public function getCancelReason(AbRequest $request)
    {
        if ($request->getStatus() == AbRequest::BOOKING_STATUS_CANCELED) {
            if ($request->getCancelReason() && array_key_exists($request->getCancelReason(), $this->reasons)) {
                return $this->reasons[$request->getCancelReason()];
            }
        }

        return null;
    }

    public function getRequestsCountByUser(Usr $user, $isBooker = false)
    {
        $filter = ($isBooker) ? "r.BookerUser = :user" : "r.User = :user";

        return $this->_em->createQuery("SELECT count(r) FROM AwardWallet\MainBundle\Entity\AbRequest r WHERE $filter")
            ->setParameter('user', $user->getId())
            ->getSingleScalarResult();
    }

    public function getActiveRequestsCountByUser(Usr $user, $isBooker = false)
    {
        $filter = ($isBooker) ? "r.BookerUser = :user" : "r.User = :user";

        // todo referral counter
        return $this->_em->createQuery("SELECT count(r) FROM AwardWallet\MainBundle\Entity\AbRequest r WHERE $filter and r.Status in (:status)")
            ->setParameter('user', $user->getId())
            ->setParameter('status', AbRequest::getActiveStatuses($isBooker))
            ->getSingleScalarResult();
    }

    public function getPreviousRequestsCountByUser(Usr $user, $isBooker = false)
    {
        $filter = ($isBooker) ? "r.BookerUser = :user" : "r.User = :user";

        return (int) $this->_em->createQuery("SELECT count(r) FROM AwardWallet\MainBundle\Entity\AbRequest r WHERE $filter and r.Status in (" . implode(', ', [AbRequest::BOOKING_STATUS_CANCELED, AbRequest::BOOKING_STATUS_PROCESSING]) . ")")
            ->setParameter('user', $user->getId())
            ->getSingleScalarResult();
    }

    public function getLastActiveRequestByUser(Usr $user)
    {
        $result = $this->_em->createQuery("
                SELECT
                    r
                FROM
                    AwardWallet\MainBundle\Entity\AbRequest r
                WHERE
                    r.User = :user
                    AND r.Status in (:status)
                ORDER BY r.LastUpdateDate DESC
                ")
            ->setParameter('user', $user->getId())
            ->setParameter('status', AbRequest::getActiveStatuses())
            ->getResult();

        if ($result) {
            return array_shift($result);
        }

        return null;
    }

    public function hasInvoice(AbRequest $request)
    {
        return $this->_em->createQueryBuilder()
            ->select("count(i)")
            ->from(AbMessage::class, 'm')
            ->leftJoin('m.Invoice', 'i', 'WITH')
            ->andWhere('m.RequestID = ' . $request->getAbRequestID())
            ->getQuery()
//            ->getSQL();
            ->getSingleScalarResult();
    }

    public function removeInvoices(AbRequest $request)
    {
        $invoices = $this->_em->createQueryBuilder()
            ->select('m')
            ->from(AbMessage::class, 'm')
            ->innerJoin('m.Invoice', 'i', 'WITH')
            ->andWhere('m.RequestID = ' . $request->getAbRequestID())
            ->getQuery()->getResult();

        if (count($invoices)) {
            foreach ($invoices as $invoice) {
                $this->_em->remove($invoice);
            }
        }
        $this->_em->flush();
    }

    /**
     * @param bool $asBooker
     * @return AbRequest|null
     */
    public function getLastUnreadByUser(Usr $user, $asBooker = false)
    {
        if ($asBooker) {
            $booker = $this->_em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBookerByUser($user);

            if (empty($booker)) {
                return null;
            }
        }

        $qb = $this->_em->createQueryBuilder();
        $e = $qb->expr();
        /*
         * Find requests with messages which create date greater than
         * corresponding mark read date(if mark present),
         * Request with latest message goes first.
         */
        $qb
            ->select('r')
            ->from(AbRequest::class, 'r')
            ->leftJoin('r.Messages', 'abm', 'WITH')
            ->leftJoin(
                'r.RequestsMark',
                'abrm',
                'WITH',
                'abrm.UserID = :user'
            )
            ->setParameter(':user', $user->getUserid(), \PDO::PARAM_INT)
            ->andWhere($e->andX(
                $e->isNotNull('abrm.ReadDate'), // exclude requests without marks
                $e->orX(
                    $e->andX( // if messages exist, compare mark date with message date
                        $e->isNotNull('abm.CreateDate'),
                        $asBooker ? $e->eq(1, 1) : $e->neq('abm.Type', AbMessage::TYPE_INTERNAL),
                        $e->gt('abm.CreateDate', 'abrm.ReadDate')
                    ),
                    $e->andX( // if no messages exist, compare mark date with request create date
                        $e->isNull('abm.CreateDate'),
                        $e->gte('r.CreateDate', 'abrm.ReadDate')
                    )
                )
            ));

        if (isset($booker)) {
            $qb
                ->andWhere('r.BookerUser = :bookerUser')
                ->setParameter(':bookerUser', $booker->getUserid(), \PDO::PARAM_INT);
        } else {
            $qb->andWhere('r.User = :user');
        }

        $result = $qb
            ->orderBy('abm.CreateDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        return $result;
    }

    public function getOtherRequests(AbRequest $request)
    {
        return $this->_em->createQuery("SELECT r FROM AwardWallet\MainBundle\Entity\AbRequest r WHERE r.User = :user and r.BookerUser = :booker and r.AbRequestID != :id")
            ->setParameter('user', $request->getUser())
            ->setParameter('booker', $request->getBooker())
            ->setParameter('id', $request->getAbRequestID())
            ->getResult();
    }

    /**
     * Counts unread booking requests.
     *
     * @param bool $asBooker
     * @return int|mixed
     */
    public function getUnreadCountForUser(Usr $user, $asBooker = false, ?Usr $business = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $e = $qb->expr();
        $qb
            ->select('count(r)')
            ->from(AbRequest::class, 'r')
            ->leftJoin('r.Messages', 'abm', 'WITH')
            ->leftJoin(
                'r.RequestsMark',
                'abrm',
                'WITH',
                'abrm.UserID = :user'
            )
            ->setParameter(':user', $user->getId(), \PDO::PARAM_INT)
            ->andWhere($e->andX(
                $e->isNotNull('abrm.ReadDate'), // exclude requests without marks
                $e->orX(
                    $e->andX( // if messages exist, compare mark date with message date
                        $e->isNotNull('abm.CreateDate'),
                        $asBooker ? $e->eq(1, 1) : $e->neq('abm.Type', AbMessage::TYPE_INTERNAL),
                        $e->gt('abm.CreateDate', 'abrm.ReadDate')
                    ),
                    $e->andX( // if no messages exist, compare mark date with request create date
                        $e->isNull('abm.CreateDate'),
                        $e->gte('r.CreateDate', 'abrm.ReadDate')
                    )
                )
            ))
            ->groupBy('r.AbRequestID');

        $usrRep = $this->_em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        if (
            $usrRep->getBookerByUser($user)
            && $usrRep->getBusinessByUser($user, [ACCESS_BOOKING_VIEW_ONLY])
        ) {
            $qb->leftJoin('r.SiteAd', 'c', 'WITH');
            $qb->leftJoin('c.users', 'u', 'WITH');
            $qb->andWhere('u.userid = :user');
        }

        if ($business) {
            $qb->andWhere("r.BookerUser = :business");
            $qb->setParameter("business", $business->getUserid(), \PDO::PARAM_INT);
        }

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * @param bool $asBooker
     * @return bool
     */
    public function isRequestReadByUser(AbRequest $abRequest, Usr $user, $asBooker = false)
    {
        $qb = $this->_em->createQueryBuilder();
        $e = $qb->expr();
        $qb
            ->select('count(r)')
            ->from(AbRequest::class, 'r')
            ->leftJoin('r.Messages', 'abm', 'WITH')
            ->leftJoin(
                'r.RequestsMark',
                'abrm',
                'WITH',
                'abrm.UserID = :user'
            )
            ->setParameter(':user', $user->getUserid(), \PDO::PARAM_INT)
            ->andWhere('r.AbRequestID = :request')
            ->setParameter(':request', $abRequest->getAbRequestID(), \PDO::PARAM_INT)
            ->andWhere(
                $e->andX(
                    $e->isNotNull('abrm.ReadDate'), // exclude requests without marks
                    $e->orX(
                        $e->andX( // if messages exist, compare mark date with message date
                            $e->isNotNull('abm.CreateDate'),
                            $asBooker ? $e->eq(1, 1) : $e->neq('abm.Type', AbMessage::TYPE_INTERNAL),
                            $e->neq('abm.UserID', ':user'),
                            $e->gt('abm.CreateDate', 'abrm.ReadDate')
                        ),
                        $e->andX( // if no messages exist, compare mark date with request create date
                            $e->isNull('abm.CreateDate'),
                            $e->gte('r.CreateDate', 'abrm.ReadDate')
                        )
                    )
                )
            );

        $usrRep = $this->_em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        if (
            $usrRep->getBookerByUser($user)
            && $usrRep->getBusinessByUser($user, [ACCESS_BOOKING_VIEW_ONLY])
        ) {
            $qb->leftJoin('r.SiteAd', 'c', 'WITH');
            $qb->leftJoin('c.users', 'u', 'WITH');
            $qb->andWhere('u.userid = :user');
        }

        return $qb->getQuery()->getSingleScalarResult() == 0;
    }

    /**
     * @param \DateTime $fromTime
     * @param bool $asBooker
     * @return AbRequest[]
     */
    public function getUnreadListByUser(Usr $user, $fromTime = null, $asBooker = false)
    {
        $qb = $this->_em->createQueryBuilder();
        $e = $qb->expr();
        $query = $qb
            ->select('r')
            ->from(AbRequest::class, 'r')
            ->leftJoin('r.Messages', 'abm', 'WITH')
            ->leftJoin(
                'r.RequestsMark',
                'abrm',
                'WITH',
                'abrm.UserID = :user'
            )
            ->setParameter(':user', $user->getUserid(), \PDO::PARAM_INT)
            ->andWhere($e->andX(
                $e->isNotNull('abrm.ReadDate'), // exclude requests without marks
                $e->orX(
                    $e->andX( // if messages exist, compare mark date with message date
                        $e->isNotNull('abm.CreateDate'),
                        $asBooker ? $e->eq(1, 1) : $e->neq('abm.Type', AbMessage::TYPE_INTERNAL),
                        $e->gt('abm.CreateDate', 'abrm.ReadDate')
                    ),
                    $e->andX( // if no messages exist, compare mark date with request create date
                        $e->isNull('abm.CreateDate'),
                        $e->gte('r.CreateDate', 'abrm.ReadDate')
                    )
                )
            ))
            ->groupBy('r');

        $usrRep = $this->_em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        if (
            $usrRep->getBookerByUser($user)
            && $usrRep->getBusinessByUser($user, [ACCESS_BOOKING_VIEW_ONLY])
        ) {
            $query->leftJoin('r.SiteAd', 'c', 'WITH');
            $query->leftJoin('c.users', 'u', 'WITH');
            $query->andWhere('u.userid = :user');
        }

        if ($fromTime && $fromTime instanceof \DateTime) {
            $query
                ->andWhere('r.LastUpdateDate > :fromTime')
                ->setParameter(':fromTime', $fromTime->setTimezone((new \DateTime())->getTimezone()));
        }

        return $query->getQuery()->getResult();
    }

    // @see #12455
    public function getWhiteListProgramCodes()
    {
        return [
            // hotels
            "spg",
            "marriott",
            "hhonors",
            "aplus",
            "goldpassport",
            "choice",
            "ichotelsgroup",
            "carlson",
            "triprewards",
            "jinling",
            "solmelia",
            "goldcrown",
            "voila",
            "coasthotels",
            "drury",
            "jumeirah",
            "shangrila",
            "dorint",
            "flavours",
            "silvercloud",
            "gloriapartner",
            "woodfield",
            "fiesta",
            // other
            //			"worldpoints",
            //			"harrah",
            //			"hertz",
            //			"airmilesme",
            //			"emiles",
            //			"mypoints",
            //			"dcard",
        ];
    }
}
