<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Repositories\LocationRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class LoyaltyLocation
{
    private CacheManager $cache;

    private LocationRepository $locationRep;

    private EntityManagerInterface $em;
    private TrackedLocationsLimiter $trackedLocationsLimiter;

    /**
     * LoyaltyLocation constructor.
     */
    public function __construct(CacheManager $cache, EntityManagerInterface $em, TrackedLocationsLimiter $trackedLocationsLimiter)
    {
        $this->cache = $cache;
        $this->locationRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Location::class);
        $this->em = $em;
        $this->trackedLocationsLimiter = $trackedLocationsLimiter;
    }

    public function getLocations(Usr $user)
    {
        return $this->cache->load(new CacheItemReference(
            Tags::getLoyaltyLocationsKey($user->getUserid()),
            Tags::getLoyaltyLocationsTags($user->getUserid()),
            function () use ($user) {
                return $this->locationRep->getLocationsByUser($user)->fetchAll(\PDO::FETCH_ASSOC);
            }
        ));
    }

    public function getCountTotal(Usr $user)
    {
        return $this->cache->load(new CacheItemReference(
            Tags::getLoyaltyLocationsKey($user->getUserid()) . "_total_count",
            Tags::getLoyaltyLocationsTags($user->getUserid()),
            function () use ($user) {
                return $this->locationRep->getCountTotal($user);
            }
        ));
    }

    public function getCountTracked(Usr $user)
    {
        return $this->cache->load(new CacheItemReference(
            Tags::getLoyaltyLocationsKey($user->getUserid()) . "_tracked_count",
            Tags::getLoyaltyLocationsTags($user->getUserid()),
            function () use ($user) {
                return $this->locationRep->getCountTracked($user);
            }
        ));
    }

    public function changeSettings(Usr $user, $locationId, $tracked): void
    {
        $this->em->getConnection()->executeQuery("
                INSERT INTO LocationSetting (LocationID, UserID, Tracked) 
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Tracked = ?
            ", [$locationId, $user->getUserid(), $tracked, $tracked],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]);

        $this->em->getConnection()->executeUpdate(
            'update `Location` set `IsGenerated` = 0 where `LocationID` = ?',
            [$locationId],
            [\PDO::PARAM_INT]
        );
    }

    public function disableLocations(Usr $user, array $locationIds): void
    {
        if (!$locationIds) {
            return;
        }

        $invalidate = false;
        $stm = $this->locationRep->getLocationsByUser($user, " AND l.LocationID IN (" . implode(", ", $locationIds) . ")");

        while ($row = $stm->fetch(\PDO::FETCH_ASSOC)) {
            $invalidate = true;
            $this->changeSettings($user, $row['LocationID'], 0);
        }

        if ($invalidate) {
            $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        }
    }

    public function enableLocations(Usr $user, array $locationIds): void
    {
        if (!$locationIds) {
            return;
        }

        $invalidate = false;
        $trackedCount = $this->locationRep->getCountTracked($user);
        $availableCount = \max($this->trackedLocationsLimiter->getMaxTrackedLocations() - $trackedCount, 0);
        $stm = $this->locationRep->getLocationsByUser($user, " AND l.LocationID IN (" . implode(", ", $locationIds) . ")");

        foreach (
            it($stm)
            ->filterByColumn('Tracked', 0)
            ->take($availableCount) as $row
        ) {
            $invalidate = true;
            $this->changeSettings($user, $row['LocationID'], 1);
        }

        if ($invalidate) {
            $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        }
    }
}
