<?php

namespace AwardWallet\MobileBundle\View\Booking\Messages;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbRequestMark;
use AwardWallet\MainBundle\Entity\BusinessInfo;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MobileBundle\View\Booking\Block\Message;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MessagesLoader
{
    public const AUTO_REPLY_MESSAGE_ID = 0;

    public const READ_MESSAGES_CHUNK_SIZE = 10;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var MessagesFormatter
     */
    private $messagesFormatter;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessagesFormatter $messagesFormatter
    ) {
        $this->entityManager = $entityManager;
        $this->messagesFormatter = $messagesFormatter;
    }

    /**
     * @param Usr $user viewer
     * @param array $messagesMetadata map of (messageId => versionDate)
     * @return Message[]
     */
    public function syncRequestMessages(AbRequest $request, Usr $user, array $messagesMetadata)
    {
        $min = null;
        $autoReplyExists = false;

        foreach (array_keys($messagesMetadata) as $messageId) {
            if ($messageId === self::AUTO_REPLY_MESSAGE_ID) {
                $autoReplyExists = true;

                continue;
            }

            if ($messageId < $min || !isset($min)) {
                $min = $messageId;
            }
        }

        return $this->loadMessageViews(
            [(new MessageCriterion($request, $user))
                ->setLowerMessageId($min)
                ->setLoadAutoReply($autoReplyExists)
                ->setMessageVersions($messagesMetadata),
            ]
        )[$request->getAbRequestID()];
    }

    /**
     * @param Usr $user viewer
     * @param int|null $oldestSeenMessageId the oldest messageId client has
     * @return MessagesView
     */
    public function loadMessageViewBeforeOldest(AbRequest $abRequest, Usr $user, $oldestSeenMessageId = null)
    {
        $requestId = $abRequest->getAbRequestID();
        $criterion = (new MessageCriterion($abRequest, $user))
            ->setFlags(MessageCriterion::FLAG_LOAD_CHUNK);

        if (isset($oldestSeenMessageId)) {
            $criterion->setUpperMessageId($oldestSeenMessageId - 1);
        }

        return $this->loadMessageViews([$criterion])[$requestId];
    }

    /**
     * @param MessageCriterion[] $messageCriteria
     * @return Message[][]
     */
    public function loadMessageViews(array $messageCriteria)
    {
        /** @var MessageCriterion[] $messageCriteriaByRequestId */
        $messageCriteriaByRequestId = [];

        foreach ($messageCriteria as $messageCriterion) {
            $user = $messageCriterion->viewer;
            $userId = $user->getUserid();

            // compute last read date for request by user
            if (!array_key_exists($userId, $messageCriterion->lastReadByUser)) {
                /** @var AbRequestMark[] $readsByUser */
                $readsByUser = $messageCriterion->request->getRequestsMark()->filter(
                    function (AbRequestMark $abRequestMark) use ($user) {
                        return $abRequestMark->getUser()->getUserid() === $user->getUserid();
                    }
                )->getValues();

                $messageCriterion->lastReadByUser[$userId] = isset($readsByUser[0]) ?
                    $readsByUser[0]->getReadDate() : false;
            }

            $messageCriteriaByRequestId[$messageCriterion->requestId] = $messageCriterion;
        }

        $messagesViews = $this->doLoadMessageViews($messageCriteria);
        $messageIds = [];

        /** @var int[] $messagesView */
        foreach ($messagesViews as &$messagesView) {
            if ($messagesView) {
                $messageIds = array_merge($messageIds, $messagesView);
                // clear
                $messagesView = [];
            }
        }
        unset($messagesView);

        if ($messageIds) {
            $formatted = $this->formatMessages($this->loadMessageEntities($messageIds), $messageCriteriaByRequestId);

            foreach ($formatted as $requestId => $formattedMessages) {
                $messagesViews[$requestId] = $formattedMessages;
            }
        }

        foreach ($messageCriteriaByRequestId as $requestId => $messageCriterion) {
            $request = $messageCriterion->request;
            $requestId = $messageCriterion->requestId;

            /** @var Message[][] $messagesViews */
            if (
                isset($messagesViews[$requestId])
                && (
                    (
                        (MessageCriterion::FLAG_LOAD_CHUNK & $messageCriterion->flags)
                        && (count($messagesViews[$requestId]) < self::READ_MESSAGES_CHUNK_SIZE)
                    )
                    || $messageCriterion->loadAutoReply
                )
                && $request->hasAutoReplyMessage()
            ) {
                array_unshift(
                    $messagesViews[$requestId],
                    $this->messagesFormatter->formatMessage(
                        $request->getAutoReplyMessage(),
                        $messageCriterion
                    )
                );
            }
        }

        return $messagesViews;
    }

    /**
     * @param MessageCriterion[] $messageCriteria
     * @return array<int[]> message views
     */
    protected function doLoadMessageViews(array $messageCriteria)
    {
        /** @var int[][] $messagesViewsByRequestId */
        $messagesViewsByRequestId = [];
        /** @var MessageLoadState[] $loadStatesByRequestId */
        $loadStatesByRequestId = [];

        foreach ($messageCriteria as $messageCriterion) {
            $loadStatesByRequestId[$messageCriterion->requestId] = new MessageLoadState(
                $messageCriterion,
                self::READ_MESSAGES_CHUNK_SIZE,
                false
            );
        }

        $messagesMetadataStmt = $this->loadMessagesMetadata($messageCriteria);

        while ($messageMetadata = $messagesMetadataStmt->fetch(\PDO::FETCH_ASSOC)) {
            $requestId = (int) $messageMetadata['RequestID'];
            $abMessageId = (int) $messageMetadata['AbMessageID'];

            if (!isset($messagesViewsByRequestId[$requestId])) {
                $messagesViewsByRequestId[$requestId] = [];
            }

            $loadState = $loadStatesByRequestId[$requestId];

            /** @var int[] $messageView */
            $messageView = &$messagesViewsByRequestId[$requestId];
            $messageCriterion = $loadState->messageCriterion;
            $userId = $messageCriterion->viewer->getUserid();

            if (MessageCriterion::FLAG_LOAD_LAST_UNREAD & $messageCriterion->flags) {
                $criterion = $loadState->messageCriterion;
                $isRead =
                    ((int) $messageMetadata['UserID'] === $userId)
                    || (
                        array_key_exists($userId, $criterion->lastReadByUser) ?
                            (
                                (false === $messageCriterion->lastReadByUser[$userId]) // read mark absents, so message is read
                                || (
                                    ($messageCriterion->lastReadByUser[$userId] instanceof \DateTimeInterface)
                                    && ($criterion->lastReadByUser[$userId] >= new \DateTime($messageMetadata['CreateDate']))
                                )
                            ) :
                            false
                    );

                if (!$loadState->isUnreadTaken) {
                    if ($isRead) {
                        $loadState->isUnreadTaken = true;
                    } else {
                        $messageView[] = $abMessageId;
                        $loadState->messagesBudget--;

                        continue;
                    }
                }
            }

            if (MessageCriterion::FLAG_LOAD_CHUNK & $messageCriterion->flags) {
                if ($loadState->messagesBudget > 0) {
                    $messageView[] = $abMessageId;
                    $loadState->messagesBudget--;

                    continue;
                }
            } else {
                $messageView[] = $abMessageId;
                $loadState->messagesBudget--;
            }
        }
        unset($messageView);

        foreach ($loadStatesByRequestId as $requestId => $_) {
            // create epmty view
            if (!isset($messagesViewsByRequestId[$requestId])) {
                $messagesViewsByRequestId[$requestId] = [];
            } else {
                // natural order
                $messagesViewsByRequestId[$requestId] = array_reverse($messagesViewsByRequestId[$requestId]);
            }
        }

        return $messagesViewsByRequestId;
    }

    /**
     * @param AbMessage[] $messageEntities
     * @param MessageCriterion[] $messageCriteriaByRequestId
     * @return Message[] messages by request
     */
    protected function formatMessages($messageEntities, array $messageCriteriaByRequestId)
    {
        $messagesByRequest = [];

        foreach ($messageEntities as $messageEntity) {
            $abRequest = $messageEntity->getRequest();
            $requestId = $abRequest->getAbRequestID();

            $messagesByRequest[$requestId][] = $this->messagesFormatter->formatMessage(
                $messageEntity,
                $messageCriteriaByRequestId[$requestId]
            );
        }

        return $messagesByRequest;
    }

    /**
     * @param int[] $messageIds
     * @return AbMessage[]
     */
    protected function loadMessageEntities(array $messageIds)
    {
        $messages =
            $this->entityManager->createQueryBuilder()
            ->select('abm')
            ->from(AbMessage::class, 'abm')
            ->where('abm.AbMessageID IN (:messageIds)')
            ->setParameter('messageIds', $messageIds)
            ->orderBy('abm.AbMessageID', 'asc')
            ->getQuery()
            ->getResult();

        $getUninitializedEntityIds = function (string $pathToEntity, string $entityIdPropertyPath) use ($messages) {
            return
                it($messages)
                ->propertyPath($pathToEntity)
                ->filterIsInstance(Proxy::class)
                ->filterByPropertyPath('__isInitialized', false)
                ->propertyPath($entityIdPropertyPath)
                ->collect()
                ->unique()
                ->toArray();
        };

        $usersToLoad = $getUninitializedEntityIds('user', 'userid');

        if ($usersToLoad) {
            $this->entityManager->createQueryBuilder()
                ->select('u')
                ->from(Usr::class, 'u')
                ->where('u.userid IN (:userIds)')
                ->setParameter('userIds', $usersToLoad)
                ->getQuery()
                ->getResult();
        }

        $businessInfoToLoad = $getUninitializedEntityIds('user.businessInfo', 'id');

        if ($businessInfoToLoad) {
            $this->entityManager->createQueryBuilder()
                ->select('bi')
                ->from(BusinessInfo::class, 'bi')
                ->where('bi.id IN (:businessInfo)')
                ->setParameter('businessInfo', $businessInfoToLoad)
                ->getQuery()
                ->getResult();
        }

        $this->entityManager->createQueryBuilder()
            ->select('abmi, abmi_i, abmi_m, partial abm.{AbMessageID}')
            ->from(AbMessage::class, 'abm')
            ->leftJoin('abm.Invoice', 'abmi')
            ->leftJoin('abmi.items', 'abmi_i')
            ->leftJoin('abmi.miles', 'abmi_m')
            ->where('abm.AbMessageID IN (:messageIds)')
            ->setParameter('messageIds', $messageIds)
            ->getQuery()
            ->getResult();

        $this->entityManager->createQueryBuilder()
            ->select('abmc, partial abm.{AbMessageID}')
            ->from(AbMessage::class, 'abm')
            ->leftJoin('abm.Color', 'abmc')
            ->where('abm.AbMessageID IN (:messageIds)')
            ->setParameter('messageIds', $messageIds)
            ->getQuery()
            ->getResult();

        $this->entityManager->createQueryBuilder()
            ->select('abpn, partial abm.{AbMessageID}')
            ->from(AbMessage::class, 'abm')
            ->leftJoin('abm.PhoneNumbers', 'abpn')
            ->where('abm.AbMessageID IN (:messageIds)')
            ->setParameter('messageIds', $messageIds)
            ->getQuery()
            ->getResult();

        $abRequests =
            it($messages)
            ->propertyPath('requestId')
            ->flatMap(function (AbRequest $abRequest) {
                $abrm = $abRequest->getRequestsMark();

                if (
                    ($abrm instanceof PersistentCollection)
                    && (!$abrm->isInitialized())
                ) {
                    yield $abRequest->getAbRequestID();
                }
            })
            ->collect()
            ->unique()
            ->toArray();

        if ($abRequests) {
            $this->entityManager->createQueryBuilder()
                ->select('abrm, partial abr.{AbRequestID}')
                ->from(AbRequest::class, 'abr')
                ->leftJoin('abr.RequestsMark', 'abrm')
                ->where('abr.AbRequestID IN (:requestIds)')
                ->setParameter('requestIds', $abRequests)
                ->getQuery()
                ->getResult();
        }

        return $messages;
    }

    /**
     * @param MessageCriterion[] $messageCriteria
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws \InvalidArgumentException
     */
    protected function loadMessagesMetadata(array $messageCriteria)
    {
        $paramN = 0;

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder()
            ->select(
                'RequestID',
                'AbMessageID',
                'CreateDate',
                'UserID'
            )
            ->from('AbMessage')
            ->orderBy('RequestID', 'asc')
            ->addOrderBy('AbMessageID', 'desc');

        /** @var callable $exprFactory */
        $exprFactory = [$queryBuilder, 'expr'];
        $orX = [];

        foreach ($messageCriteria as $messageCriterion) {
            // requestId
            $andX = [$exprFactory()->eq('RequestID', '?')];
            $queryBuilder->setParameter($paramN++, $messageCriterion->requestId, \PDO::PARAM_INT);

            // lower bound
            if (isset($messageCriterion->lowerMessageId)) {
                $andX[] = $exprFactory()->gte('AbMessageID', '?');
                $queryBuilder->setParameter($paramN++, $messageCriterion->lowerMessageId, \PDO::PARAM_INT);
            }

            // upper bound
            if (isset($messageCriterion->upperMessageId)) {
                $andX[] = $exprFactory()->lte('AbMessageID', '?');
                $queryBuilder->setParameter($paramN++, $messageCriterion->upperMessageId, \PDO::PARAM_INT);
            }

            $orX[] = $exprFactory()->andX(...$andX);
        }

        $queryBuilder
            ->where($exprFactory()->orX(...$orX))
            ->andWhere('Type NOT IN (?)')
            ->setParameter(
                $paramN++,
                [
                    AbMessage::TYPE_INTERNAL,
                    AbMessage::TYPE_SHARE_ACCOUNTS_INTERNAL,
                    AbMessage::TYPE_PAYMENT,
                    AbMessage::TYPE_REF,
                ],
                Connection::PARAM_INT_ARRAY
            )

            ->andWhere('UserID IS NOT NULL');

        return $queryBuilder->execute();
    }
}

class MessageLoadState
{
    /**
     * @var MessageCriterion
     */
    public $messageCriterion;
    /**
     * @var int
     */
    public $messagesBudget;
    /**
     * @var bool
     */
    public $isUnreadTaken;

    /**
     * MessageLoadState constructor.
     *
     * @param int $messagesBudget
     * @param bool $isUnreadTaken
     */
    public function __construct(MessageCriterion $messageCriterion, $messagesBudget, $isUnreadTaken)
    {
        $this->messageCriterion = $messageCriterion;
        $this->messagesBudget = $messagesBudget;
        $this->isUnreadTaken = $isUnreadTaken;
    }
}
