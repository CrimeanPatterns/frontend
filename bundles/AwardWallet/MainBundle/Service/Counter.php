<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\AccountCounter\Counter as AccountCounter;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class Counter
{
    protected CacheManager $cache;

    protected EntityManagerInterface $em;

    private \Memcached $memcached;

    private AccountCounter $accountCounter;

    // no need to remove, necessary for debugging problems with counters
    private Mailer $mailer;

    private $detailsCountCache = [];

    public function __construct(
        CacheManager $cache,
        EntityManagerInterface $em,
        \Memcached $memcached,
        AccountCounter $accountCounter,
        Mailer $mailer
    ) {
        $this->cache = $cache;
        $this->em = $em;
        $this->memcached = $memcached;
        $this->accountCounter = $accountCounter;
        $this->mailer = $mailer;
    }

    /**
     * @param int $userId
     * @return int
     */
    public function getTotalAccounts($userId, $userAgentId = null)
    {
        // whole profile AW
        if (is_null($userAgentId)) {
            return $this->cache->load(new CacheItemReference(
                Tags::getAllAccountsKey($userId),
                Tags::getAllAccountsCounterTags($userId),
                function () use ($userId) {
                    $summary = $this->accountCounter->calculate($userId);

                    return $summary->getCount();
                }
            ));
        } elseif (0 === $userAgentId) {
            // profile AW without family members and connections
            return $this->cache->load(new CacheItemReference(
                Tags::getUserAccountsKey($userId),
                Tags::getAllAccountsCounterTags($userId),
                function () use ($userId) {
                    $summary = $this->accountCounter->calculate($userId);

                    return $summary->getCount(0);
                }
            ));
        } elseif ($userAgentId > 0) {
            // family member or connection
            $ua = $this->em->getRepository(Useragent::class)->find($userAgentId);

            if ($ua->isFamilyMember()) {
                $tags = Tags::getAllAccountsCounterTags($ua->getAgentid()->getId());
            } else {
                $tags = Tags::getAllAccountsCounterTags($ua->getClientid()->getId());
            }

            return $this->cache->load(new CacheItemReference(
                Tags::getUserAccountsKey($ua->getAgentid()->getId(), $ua->getId()),
                $tags,
                function () use ($userId, $userAgentId) {
                    $summary = $this->accountCounter->calculate($userId);

                    return $summary->getCount($userAgentId);
                }
            ));
        }
    }

    public function getTotalItineraries(int $userId, ?int $userAgentId = null): int
    {
        $filter = ['t.UserID = :userId', 't.Hidden = :hidden'];
        $params = [
            ':userId' => $userId,
            ':hidden' => 0,
        ];
        $types = [
            \PDO::PARAM_INT,
            \PDO::PARAM_INT,
        ];

        if ($userAgentId === 0) {
            $filter[] = 't.UserAgentID IS NULL';
        } elseif ($userAgentId > 0) {
            $filter[] = "t.UserAgentID = $userAgentId";
            $params[':userAgentId'] = $userAgentId;
            $types[] = \PDO::PARAM_INT;
        }

        $em = $this->em;
        $sql = $em->getRepository(Trip::class)->TripsSQL(array_merge($filter, ['ts.Hidden = 0']))
            . ' UNION ' . $em->getRepository(Rental::class)->RentalsSQL($filter)
            . ' UNION ' . $em->getRepository(Parking::class)->ParkingsSQL($filter)
            . ' UNION ' . $em->getRepository(Reservation::class)->ReservationsSQL($filter)
            . ' UNION ' . $em->getRepository(Restaurant::class)->RestaurantsSQL($filter);
        $sql = "SELECT COUNT(*) FROM ($sql) t";

        return (int) $this->em->getConnection()
            ->executeQuery($sql, $params, $types)
            ->fetch(\PDO::FETCH_COLUMN);
    }

    public function getConnections($userId)
    {
        return $this->cache->load(new CacheItemReference(
            'connections_count_' . $userId,
            Tags::getConnectionsTags($userId),
            function () use ($userId) {
                return $this->em->getRepository(Useragent::class)->getMembersCount($userId);
            },
            null,
            null
        ));
    }

    public function getConnectedUsers(Usr $user)
    {
        return $this->cache->load(
            new CacheItemReference(
                'connected_users_count_' . $user->getId(),
                Tags::getConnectionsTags($user->getId()),
                function () use ($user) {
                    $criteria = Criteria::create()->where(Criteria::expr()->eq('agentid', $user))
                                                  ->andWhere(Criteria::expr()->neq('clientid', null));

                    return $this->em->getRepository(Useragent::class)->matching($criteria)->count();
                }
            )
        );
    }

    public function getInvites(Usr $user)
    {
        return $this->em
            ->getRepository(Invitecode::class)
                    ->count(['userid' => $user]);
    }

    /**
     * @param bool $useCache
     * @param bool $includeAll
     * @return array
     */
    public function getDetailsCountAccountsByUser(Usr $user, $useCache = true, $includeAll = true)
    {
        $userID = $user->getId();

        if ($useCache && isset($this->detailsCountCache[$userID . '#' . $includeAll])) {
            return $this->detailsCountCache[$userID . '#' . $includeAll];
        }

        $userAgentRep = $this->em->getRepository(Useragent::class);
        $accountSummary = $this->accountCounter->calculate($userID);
        $contacts = $userAgentRep->getOtherUsers($userID);
        $all = [
            'UserName' => 'All',
            'UserAgentID' => null,
            'Count' => $accountSummary->getCount(),
            'Accounts' => $accountSummary->getCountAccounts(),
            'Coupons' => $accountSummary->getCountCoupons(),
        ];

        foreach ($contacts as $k => $contact) {
            $contacts[$k]['Count'] = $accountSummary->getCount($contact['UserAgentID']);
            $contacts[$k]['Accounts'] = $accountSummary->getCountAccounts($contact['UserAgentID']);
            $contacts[$k]['Coupons'] = $contacts[$k]['Count'] - $contacts[$k]['Accounts'];
        }

        $my = [
            'FirstName' => $user->getFirstname(),
            'LastName' => $user->getLastname(),
            'UserName' => $user->getFullName(),
            'UserID' => $userID,
            'UserAgentID' => null,
            'ClientID' => null,
            'AccountLevel' => $user->getAccountlevel(),
            'Company' => $user->getCompany(),
            'AccessLevel' => ACCESS_WRITE,
            'Accounts' => $accountSummary->getCountAccounts(0),
            'Coupons' => $accountSummary->getCountCoupons(0),
            'Count' => $accountSummary->getCount(0),
        ];

        // sort
        usort($contacts, function ($a, $b) {
            return strcmp($a['UserName'], $b['UserName']);
        });

        array_unshift($contacts, $my);

        if ($includeAll && count($contacts) > 1) {
            array_unshift($contacts, $all);
        }

        $this->detailsCountCache[$userID . '#' . $includeAll] = $contacts;

        return $contacts;
    }

    public function invalidateTotalAccountsCounter($userId)
    {
        $this->cache->invalidateTags([
            Tags::getAllAccountsKey($userId),
        ]);
    }

    public function getUsersCount($updateCache = false)
    {
        $usersCount = $this->memcached->get('UsersCount');

        if (!$usersCount || $updateCache) {
            $usersCount = $this->em->getRepository(Usr::class)->getUsersCount();
            $this->memcached->set('UsersCount', $usersCount);
        }

        return $usersCount;
    }
}
