<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;

class OwnerRepository
{
    public const FOR_ITINERARY_VIEW = 'itinerary_view';
    public const FOR_ITINERARY_ASSIGNMENT = 'itinerary_assignment';
    public const FOR_ACCOUNT_VIEW = 'account_view';
    public const FOR_ACCOUNT_ASSIGNMENT = 'account_assignment';

    /**
     * @var UsrRepository
     */
    private $userRepository;

    /**
     * @var UseragentRepository
     */
    private $useragentRepository;

    /**
     * @var TimelineShareRepository
     */
    private $timelineShareRepository;

    /**
     * OwnerRepository constructor.
     */
    public function __construct(
        UsrRepository $userRepository,
        UseragentRepository $useragentRepository,
        TimelineShareRepository $timelineShareRepository
    ) {
        $this->userRepository = $userRepository;
        $this->useragentRepository = $useragentRepository;
        $this->timelineShareRepository = $timelineShareRepository;
    }

    /**
     * @param string $designation
     * @param string|null $query
     * @return Owner[]
     */
    public function findAvailableOwners($designation, Usr $user, string $query = '', int $limit = 10)
    {
        switch ($designation) {
            case self::FOR_ITINERARY_VIEW:
                return $this->findAvailableOwnersForItinerary($user, false, $query, $limit);

            case self::FOR_ITINERARY_ASSIGNMENT:
                return $this->findAvailableOwnersForItinerary($user, true, $query, $limit);

            case self::FOR_ACCOUNT_VIEW:
                return $this->findAvailableOwnersForAccount($user, false, $query, $limit);

            case self::FOR_ACCOUNT_ASSIGNMENT:
                return $this->findAvailableOwnersForAccount($user, true, $query, $limit);

            default:
                throw new \InvalidArgumentException("Unknown designation: " . $designation);
        }
    }

    public static function getOwner(Usr $user, ?Useragent $familyMember = null): Owner
    {
        return new Owner($user, $familyMember);
    }

    public static function getByFamilyMember(Useragent $familyMember): Owner
    {
        if (!$familyMember->isFamilyMember()) {
            throw new \InvalidArgumentException("Expected family member, got connection");
        }

        return self::getOwner($familyMember->getAgentid(), $familyMember);
    }

    public static function getByConnection(Useragent $connection): Owner
    {
        if ($connection->isFamilyMember()) {
            throw new \InvalidArgumentException("Expected connection, got family member");
        }

        return self::getOwner($connection->getClientid());
    }

    public static function getByUseragent(Useragent $useragent): Owner
    {
        if ($useragent->isFamilyMember()) {
            return self::getByFamilyMember($useragent);
        } else {
            return self::getByConnection($useragent);
        }
    }

    public static function getByUserAndUseragent(Usr $user, ?Useragent $useragent = null): Owner
    {
        // User is the owner
        if (null === $useragent) {
            return static::getOwner($user);
        }

        // User and connection
        if (!$useragent->isFamilyMember()) {
            if ($user !== $useragent->getAgentid()) {
                throw new \InvalidArgumentException("If provided useragent is a connection, the user must be it's owner");
            }

            return static::getOwner($useragent->getClientid());
        }

        // Own family member
        if ($user === $useragent->getAgentid()) {
            return static::getOwner($user, $useragent);
        }
        // Family member of a connected user
        $connection = $user->getConnectionWith($useragent->getAgentid());

        if (null === $connection) {
            throw new \InvalidArgumentException("Provided family member does not belong to the provided user and no connection to the owner is found");
        }

        return static::getOwner($useragent->getAgentid(), $useragent);
    }

    public static function getByTimelineShare(TimelineShare $share): Owner
    {
        return self::getOwner($share->getTimelineOwner(), $share->getFamilyMember());
    }

    /**
     * @return bool
     */
    private function isMatchingQuery($string, $query)
    {
        $string = mb_strtolower($string);
        $query = mb_strtolower($query);

        foreach (str_word_count($string, 1) as $stringWord) {
            foreach (str_word_count($query, 1) as $queryWord) {
                if (0 === mb_strpos($stringWord, $queryWord)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param bool $withWritePermission
     * @param string $query
     * @param int $limit
     * @return Owner[]
     */
    private function findAvailableOwnersForItinerary(Usr $user, $withWritePermission = false, $query, $limit)
    {
        $owners = [];

        if ('' === $query || $this->isMatchingQuery($user->getFullName(), $query)) {
            $owners[] = self::getOwner($user);
        }
        $familyMembers = $this->useragentRepository->findUserFamilyMembersByName($user, $query, $limit);

        foreach ($familyMembers as $familyMember) {
            $owners[] = self::getByFamilyMember($familyMember);
        }
        $timelineShares = $this->timelineShareRepository->findByName($user, $query, $limit, $withWritePermission ? TimelineShareRepository::WITH_WRITE_PERMISSION : null);

        foreach ($timelineShares as $timelineShare) {
            $owners[] = self::getByTimelineShare($timelineShare);
        }
        $this->sortOwners($owners, $query);

        if ($limit) {
            return array_slice($owners, 0, $limit);
        } else {
            return $owners;
        }
    }

    /**
     * @param bool $withWritePermission
     * @param string $query
     * @param int $limit
     * @return array
     */
    private function findAvailableOwnersForAccount(Usr $user, $withWritePermission = false, $query, $limit)
    {
        $owners = [];

        if ('' === $query || $this->isMatchingQuery($user->getFullName(), $query)) {
            $owners[] = self::getOwner($user);
        }
        $familyMembers = $this->useragentRepository->findUserFamilyMembersByName($user, $query, $limit);

        foreach ($familyMembers as $familyMember) {
            $owners[] = self::getByFamilyMember($familyMember);
        }
        $connections = $this->useragentRepository->findUserConnectionsByName($user, $query, $withWritePermission, $limit);

        foreach ($connections as $connection) {
            $owners[] = self::getByConnection($connection);
        }
        $connectionsFamilyMembers = $this->useragentRepository->findUserConnectionsFamilyMembers($user, $query, $withWritePermission, $limit);

        foreach ($connectionsFamilyMembers as $connectionsFamilyMember) {
            $owners[] = self::getByFamilyMember($connectionsFamilyMember);
        }
        $this->sortOwners($owners, $query);

        if ($limit) {
            return array_slice($owners, 0, $limit);
        } else {
            return $owners;
        }
    }

    /**
     * @param Owner[] $owners
     */
    private function sortOwners(array &$owners, string $query)
    {
        if ('' === $query) {
            return;
        }
        usort($owners, function (Owner $a, Owner $b) use ($query) {
            $aSim = similar_text($a->getFullName(), $query);
            $bSim = similar_text($b->getFullName(), $query);

            if ($aSim < $bSim) {
                return 1;
            } elseif ($aSim === $bSim) {
                return 0;
            } else {
                return -1;
            }
        });
    }
}
