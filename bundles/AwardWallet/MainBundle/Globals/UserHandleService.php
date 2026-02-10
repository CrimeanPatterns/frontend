<?php

namespace AwardWallet\MainBundle\Globals;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserHandleService
{
    public const SHARE_TRIPS = 0x0100;
    public const SHARE_BALANCE = 0x0200;
    public const SHARE_TRIPS_DEF = 0x0400;
    public const SHARE_BALANCES_DEF = 0x0800;
    public const SHARE_APPROVED = 0x8000;
    public const SHARE_LEVEL_MASK = 0x00FF;
    public const ACCESS_BALANCE = 1;
    public const ACCESS_TRIPS = 2;
    public const ACCESS_ALL = 3;
    private $user;
    private $userId;
    private $em;
    private $connection;
    private $sharedConnections = [];
    private $sharedAgents = [];
    private $userAgents;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManager $em
    ) {
        $this->user = $tokenStorage->getToken()->getUser();
        $this->userId = $this->user->getUserid();
        $this->em = $em;
        $this->connection = $this->em->getConnection();
        $ua = $this->connection->executeQuery("
        SELECT a.UserAgentID as `UAID`,
        COALESCE(a.ClientID,a.AgentID) as `UID`,
        `Source`, `ShareByDefault`, `TripShareByDefault`, `AccessLevel` ,
        COALESCE(a.`FirstName`,u.`FirstName`) as `FirstName`,
        COALESCE(a.`MidName`,u.`MidName`) as `MidName`,
        COALESCE(a.`LastName`,u.`LastName`) as `LastName`,
        `IsApproved`
        FROM UserAgent a
          LEFT JOIN Usr u
            on u.UserID = a.ClientID
        WHERE a.AgentID = {$this->userId}")->fetchAll(\PDO::FETCH_ASSOC);
        $this->userAgents = [];

        foreach ($ua as &$line) {
            if ($line['UID'] == $this->userId) {
                $this->sharedAgents[] = $line['UAID'];
            } else {
                $this->sharedConnections[] = $line['UAID'];
            }
            $line['options'] = $line['AccessLevel'] & self::SHARE_LEVEL_MASK;
            unset($line['AccessLevel']);

            switch ($line['Source']) {
                case '*':
                    $line['options'] |= self::SHARE_TRIPS + self::SHARE_BALANCE;

                    break;

                case 'A':
                    $line['options'] |= self::SHARE_BALANCE;

                    break;

                case 'T':
                    $line['options'] |= self::SHARE_TRIPS;

                    break;
            }
            unset($line['Source']);

            if ($line['ShareByDefault']) {
                $line['options'] |= self::SHARE_BALANCES_DEF;
            }

            if ($line['TripShareByDefault']) {
                $line['options'] |= self::SHARE_TRIPS_DEF;
            }

            if ($line['IsApproved']) {
                $line['options'] |= self::SHARE_APPROVED;
            }
            unset($line['ShareByDefault']);
            unset($line['TripShareByDefault']);
            unset($line['IsApproved']);
            $this->userAgents[$line['UAID']] = &$line;
        }
    }

    public function isIndependentUser($userAgentId)
    {
        return in_array($userAgentId, $this->sharedConnections);
    }

    public function getUserId($UserAgentId)
    {
        if (is_numeric($UserAgentId)) {
            foreach ($this->userAgents as $ua) {
                if ($ua['UAID'] == $UserAgentId) {
                    return $ua['UID'];
                }
            }
        }

        return $this->userId;
    }

    public function getUserName($userAgentId = 'owner')
    {
        if (is_numeric($userAgentId)) {
            foreach ($this->userAgents as $ua) {
                if ($ua['UAID'] == $userAgentId) {
                    return $ua['FirstName'] . ($ua['MidName'] ? (' ' . $ua['MidName']) : '') . ' ' . $ua['LastName'];
                }
            }
        }

        return $this->user->getFullName();
    }

    public function createConnection($email)
    {
    }

    public function getMembers()
    {
        return $this->sharedAgents;
    }

    public function getAgents($kind)
    {
        $l = $this->sharedAgents;
        $kind = $this->getKind($kind);

        foreach ($this->sharedConnections as $user) {
            $add = false;
            $opt = $this->userAgents[$user]['options'];

            if (($kind & self::ACCESS_ALL) !== 0 and ($opt & (self::SHARE_BALANCE + self::SHARE_TRIPS)) !== 0) {
                $add = $user;
            }

            if (($kind & self::ACCESS_BALANCE) !== 0 and ($opt & self::SHARE_BALANCE) !== 0) {
                $add = $user;
            } elseif (($kind & self::ACCESS_TRIPS) !== 0 and ($opt & self::SHARE_TRIPS) !== 0) {
                $add = $user;
            }

            if ($add !== false) {
                if (in_array($opt & self::SHARE_LEVEL_MASK, [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY])) {
                    $l[] = $add;
                }
            }
        }

        return $l;
    }

    public function getContacts()
    {
        return array_keys($this->sharedAgents);
    }

    private function getKind($kind)
    {
        if (in_array($kind, [self::ACCESS_BALANCE, self::ACCESS_TRIPS, self::ACCESS_ALL])) {
            return $kind;
        }
        $kind = strtolower($kind);

        if (in_array($kind, ['b', 'balance'])) {
            return self::ACCESS_BALANCE;
        }

        if (in_array($kind, ['t', 'trips', 'travelplans'])) {
            return self::ACCESS_TRIPS;
        }

        if (in_array($kind, ['*', 'a', 'all'])) {
            return self::ACCESS_ALL;
        }

        return 0;
    }
}
