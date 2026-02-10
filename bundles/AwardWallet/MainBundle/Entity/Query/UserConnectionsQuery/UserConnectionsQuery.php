<?php

namespace AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery;

use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Utils\Criteria;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\compareBy;
use function AwardWallet\MainBundle\Globals\Utils\f\orderBy;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class UserConnectionsQuery
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return Connection[]
     */
    public function run(Usr $user, ?callable $orderBy = null): array
    {
        [
            $connectionsListInitial,
            $pendingConnectionsListInitial,
            $invitesListInitial
        ] = $this->entityManager->transactional(function () use ($user) {
            return [
                \array_merge(
                    $this->loadConnections($user),
                    $this->loadFamilyMembers($user)
                ),
                $this->loadPendingConnections($user),
                $this->loadInvites($user),
            ];
        });

        $pendingConnectionsMap = $this->getPendingConnectionsMap($pendingConnectionsListInitial);
        [$connectionsList, $connectionsEmailsHitMap] = $this->getConnectionsData($connectionsListInitial, $pendingConnectionsMap, $orderBy);
        $invitesList = $this->getInvitesList($connectionsEmailsHitMap, $invitesListInitial);

        return [
            'connections' => $connectionsList,
            'pendingConnections' => \array_values($pendingConnectionsMap),
            'emailInvites' => $invitesList,
        ];
    }

    /**
     * @return Useragent[]
     */
    private function loadConnections(Usr $user): array
    {
        return $this->entityManager->createQuery(/** @lang DQL */ "
            select
                ua, reverseUa, u, bi
            from AwardWallet\MainBundle\Entity\Useragent ua
            " . (SITE_MODE == SITE_MODE_PERSONAL ? "left " : '') . "join ua.agentid u
            left join u.BookerInfo bi
            left join AwardWallet\MainBundle\Entity\Useragent reverseUa with
                ua.clientid = reverseUa.agentid and
                ua.agentid = reverseUa.clientid
            where
                ua.clientid = :user
        ")
        ->setParameter('user', $user)
        ->execute();
    }

    /**
     * @return Useragent[]
     */
    private function loadFamilyMembers(Usr $user): array
    {
        return $this->entityManager->createQuery(/** @lang DQL */ "
            select
                ua, reverseUa, u, bi
            from AwardWallet\MainBundle\Entity\Useragent ua
            " . (SITE_MODE == SITE_MODE_PERSONAL ? "left " : '') . "join ua.agentid u
            left join u.BookerInfo bi
            left join AwardWallet\MainBundle\Entity\Useragent reverseUa with
                ua.clientid = reverseUa.agentid and
                ua.agentid = reverseUa.clientid
            where
                ua.agentid = :user and
                ua.clientid is null
        ")
        ->setParameter('user', $user)
        ->execute();
    }

    /**
     * @return Useragent[]
     */
    private function loadPendingConnections(Usr $user): array
    {
        return $this->entityManager->createQuery(/** @lang DQL */ "
            select
                ua, u
            from AwardWallet\MainBundle\Entity\Useragent ua
            join ua.agentid u
            where
                ua.clientid = :user and
                ua.isapproved = 0
        ")
        ->setParameter('user', $user)
        ->execute();
    }

    /**
     * @return Invitecode[]
     */
    private function loadInvites(Usr $user): array
    {
        return $this->entityManager->createQuery(/** @lang DQL */ "
            select i
            from AwardWallet\MainBundle\Entity\Invitecode i
            where
                i.userid = :user and
                i.email is not null
            order by i.email, i.invitecodeid
        ")
        ->setParameter('user', $user)
        ->execute();
    }

    private function getPendingConnectionsMap($pendingConnectionsListInitial): array
    {
        return
            it($pendingConnectionsListInitial)
            ->map([Connection::class, 'new'])
            ->reindex(function (Connection $connection) { return $connection->getUseragentId(); })
            ->collectWithKeys()
            ->uasort(orderBy(
                [compareBy(function (Connection $connection) { return \strtolower($connection->getFullName()); }), Criteria::ASC],
                [compareBy(function (Connection $connection) { return $connection->getUseragentId(); }), Criteria::ASC]
            ))
            ->toArrayWithKeys();
    }

    private function getConnectionsData(array $connectionsListInitial, array $pendingConnectionsMap, ?callable $orderBy = null): array
    {
        [$connectionsList, $connectionsEmailsHitMap] =
            it($connectionsListInitial)
            ->chunk(2)
            ->mapVariadic([Connection::class, 'new'])
            ->fold([[], []], function (array $accumulator, Connection $connection) use ($pendingConnectionsMap) {
                [$connectionsList, $connectionsEmailsHitMap] = $accumulator;

                if (!isset($pendingConnectionsMap[$connection->getUseragentId()])) {
                    $connectionsList[] = $connection;
                }

                $connectionsEmailsHitMap[\strtolower($connection->getEmail())] = true;
                $connectionsEmailsHitMap[\strtolower($connection->getUserEmail())] = true;

                return [$connectionsList, $connectionsEmailsHitMap];
            });

        \usort(
            $connectionsList,
            $orderBy ?: orderBy(
                [compareBy(function (Connection $connection) { return \is_null($connection->getClientId()); }), Criteria::DESC],
                [compareBy(function (Connection $connection) { return \strtolower($connection->getFullName()); }), Criteria::ASC],
                [compareBy(function (Connection $connection) { return $connection->getUseragentId(); }), Criteria::ASC]
            )
        );

        return [$connectionsList, $connectionsEmailsHitMap];
    }

    private function getInvitesList(array $connectionsEmailsHitMap, array $invitesListInitial): array
    {
        return
            $connectionsEmailsHitMap ?
                it($invitesListInitial)
                    ->reindex(function (Invitecode $invitecode) { return \strtolower($invitecode->getEmail()); })
                    ->filterNotByKeyInMap($connectionsEmailsHitMap)
                    ->toArray() :
                $invitesListInitial;
    }
}
