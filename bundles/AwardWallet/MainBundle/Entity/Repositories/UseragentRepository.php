<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\AbPassenger;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\UserAgent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\MySQLFullTextSearchUtils;
use Doctrine\ORM\EntityRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method Useragent|null findOneByUseragentid($userAgentId)
 * @template-extends EntityRepository<UserAgent>
 */
class UseragentRepository extends EntityRepository
{
    public const ALL_USERAGENTS = 'All';

    public const ACCESS_READ_NUMBER = 0;
    public const ACCESS_READ_BALANCE_AND_STATUS = 1;
    public const ACCESS_READ_ALL = 2;
    public const ACCESS_WRITE = 3;
    public const ACCESS_ADMIN = 4;
    public const ACCESS_NONE = 5;
    public const ACCESS_BOOKING_MANAGER = 6;
    public const ACCESS_BOOKING_REFERRAL = 7;

    public $userAgentAccountFilter = "";
    public $userAgentCouponFilter = "";

    protected $agentAccessLevelsPersonal = [
        self::ACCESS_READ_NUMBER => 'account.access.read_number',
        self::ACCESS_READ_BALANCE_AND_STATUS => 'account.access.read_balance_and_status',
        self::ACCESS_READ_ALL => 'account.access.except_pass',
        self::ACCESS_WRITE => 'account.access.full_control',
    ];
    protected $agentAccessLevelsBusiness = [
        self::ACCESS_ADMIN => 'business.useragent.access.full_control',
        self::ACCESS_NONE => 'business.useragent.access.regular_member',
    ];
    protected $agentAccessLevelsBooking = [
        self::ACCESS_ADMIN => 'business.useragent.access.full_control',
        self::ACCESS_BOOKING_MANAGER => 'business.useragent.access.booking_manager',
        self::ACCESS_BOOKING_REFERRAL => 'business.useragent.access.referral',
        self::ACCESS_NONE => 'business.useragent.access.regular_member',
    ];

    public function getAgentAccessLevelsAll()
    {
        return array_merge($this->agentAccessLevelsPersonal, $this->agentAccessLevelsBooking);
    }

    /**
     * @return list<UserAgent>
     */
    public function getUserFamilyMembers(Usr $user): array
    {
        return $this->createQueryBuilder('ua')
            ->select('ua')
            ->where('ua.agentid = :user')
            ->andWhere('ua.clientid IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getAgentAccessLevels($isBusiness)
    {
        if ($isBusiness) {
            return $this->agentAccessLevelsBusiness;
        } else {
            return $this->agentAccessLevelsPersonal;
        }
    }

    /**
     * @return object|Useragent
     */
    public function checkAgentExist(Usr $user, $data)
    {
        /** @var Useragent $data */
        return $this->findOneBy([
            'agentid' => $user->getUserid(),
            'firstname' => $data->getFirstName(),
            'lastname' => $data->getLastname(),
            'clientid' => null,
        ]);
    }

    public function getConnectedAgentsCount($userID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT COUNT(*) AS Cnt 
		FROM   UserAgent 
		WHERE  AgentID = ? 
		";
        $stmt = $connection->executeQuery($sql,
            [$userID],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $r['Cnt'];
    }

    /**
     * @param null $userAgentId
     * @param bool|false $returnSQL
     * @return array|string
     * @throws \Doctrine\DBAL\DBALException
     * @deprecated
     */
    public function getOtherUsers($userID, $userAgentId = null, $returnSQL = false)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT Coalesce(u.FirstName, ua.FirstName) AS FirstName, 
		       Coalesce(u.LastName, ua.LastName)   AS LastName, 
		       Coalesce(u.MidName, ua.MidName)     AS MidName,
		       " . SQL_USER_NAME . "                   AS UserName,
		       ua.UserAgentID, 
		       ua.ClientID, 
		       u.AccountLevel, 
		       u.Company,
		       ua.AccessLevel
		FROM   UserAgent ua 
		       LEFT OUTER JOIN UserAgent au 
		         ON au.ClientID = ua.AgentID 
		            AND au.AgentID = ua.ClientID 
		       LEFT OUTER JOIN Usr u 
		         ON ua.ClientID = u.UserID 
		WHERE  ua.AgentID = " . $connection->quote($userID, \PDO::PARAM_INT) . "
		       AND ua.IsApproved = 1 
		       AND ( au.IsApproved = 1 
		              OR au.IsApproved IS NULL ) 
		";

        if (isset($userAgentId)) {
            $sql .= " AND ua.UserAgentID = " . $connection->quote($userAgentId, \PDO::PARAM_INT) . "";
        }
        $sql .= " ORDER BY UserName";

        if ($returnSQL) {
            return $sql;
        }

        $sth = $connection->prepare($sql);
        $sth->execute();

        return $sth->fetchAll();
    }

    /**
     * except for family members.
     */
    public function getApprovedConnectionsCount($userID, $isBusinessVersion = false, $filter = '')
    {
        $connection = $this->getEntityManager()->getConnection();

        if ($isBusinessVersion) {
            $sql = "
			SELECT COUNT( UserAgentID ) AS Cnt
			FROM   UserAgent
			WHERE  (
			              AgentID = ?
			       )
			       AND ClientID IS NOT NULL
			       AND IsApproved         = 0
			       " . (($filter != '') ? "AND $filter" : "") . "
			";
            $stmt = $connection->executeQuery($sql,
                [$userID],
                [\PDO::PARAM_INT]
            );
            $r = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $r['Cnt'];
        }
        $count = 0;
        $sql = "
		SELECT COUNT( UserAgentID ) AS Cnt
		FROM   UserAgent
		WHERE  (
		              ClientID = ?
		       )
		       AND AgentID IS NOT NULL
		       AND IsApproved        = 1
		       " . (($filter != '') ? "AND $filter" : "") . "
		";
        $stmt = $connection->executeQuery($sql,
            [$userID],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);
        $count += $r['Cnt'];
        $sqlEmail = "
		SELECT COUNT(*) AS Cnt
		FROM   InviteCode
		WHERE  UserID = ?
		";
        $stmt = $connection->executeQuery($sqlEmail,
            [$userID],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);
        $count += $r['Cnt'];
        $sqlFamily = "
		SELECT COUNT( UserAgentID ) AS Cnt
		FROM   UserAgent
		WHERE  (
		              AgentID = ?
		       )
		       AND ClientID IS NULL
		       " . (($filter != '') ? "AND $filter" : "") . "
		";
        $stmt = $connection->executeQuery($sqlFamily,
            [$userID],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);
        $count += $r['Cnt'];

        return $count;
    }

    /**
     * Get agent filter and coupon filter.
     *
     * @param $userID integer - ID of User
     * @param $userAgentID integer
     * @param $wantCheck boolean
     * @param $wantEdit boolean
     * @return bool
     * */
    public function setAgentFilters($userID, $userAgentID = self::ALL_USERAGENTS, $wantCheck = false, $wantEdit = false)
    {
        $connection = $this->getEntityManager()->getConnection();
        $r = true;

        if ($userAgentID === self::ALL_USERAGENTS) {
            $sql = "
            SELECT ua.*,
               Coalesce(c.FirstName, ua.FirstName) AS FirstName,
               Coalesce(c.LastName, ua.LastName)   AS LastName
            FROM   UserAgent ua
                   LEFT OUTER JOIN Usr c
                     ON c.UserID = ua.ClientID
            WHERE  ua.IsApproved = 1
                   AND ua.AgentID = ? 
            ";
            $stmt = $connection->executeQuery($sql,
                [$userID],
                [\PDO::PARAM_INT]
            );
            $uAgents = $stmt->fetchAll();
            $userAgentAccountFilter = "a.UserID = {$userID}";

            if (!empty($uAgents)) {
                $userAgentAccountFilter .= " OR ";

                foreach ($uAgents as $num => $uAgent) {
                    if ($uAgent['ClientID'] > 0) {
                        if (
                            (!$wantCheck || ($uAgent['AccessLevel'] >= ACCESS_READ_ALL))
                            && (!$wantEdit || ($uAgent['AccessLevel'] >= ACCESS_WRITE))
                        ) {
                            $userAgentAccountFilter .= "
                            ( 
                                ( a.UserID = {$uAgent['ClientID']} 
                                    AND a.AccountID IN ( 
                                        SELECT ash.AccountID 
                                        FROM AccountShare ash, 
                                            Account a 
                                        WHERE ash.AccountID = a.AccountID 
                                            AND a.UserID = {$uAgent['ClientID']} 
                                            AND ash.UserAgentID = {$uAgent['UserAgentID']} 
                                    ) 
                                ) OR 
                                ( a.UserAgentID = {$uAgent['UserAgentID']} 
                                    AND a.UserID = {$userID} 
                                ) 
                            )";
                        } else {
                            $userAgentAccountFilter .= "0 = 1";
                        }
                    } else {
                        $userAgentAccountFilter .= "a.UserAgentID = {$uAgent['UserAgentID']}";
                    }

                    if ($num + 1 != count($uAgents)) {
                        $userAgentAccountFilter .= " OR ";
                    }
                }
            }
        } else {
            $userAgentAccountFilter = "0 = 1";
            $userAgentID = intval($userAgentID);

            if ($userAgentID > 0) {
                $sql = "
                SELECT ua.*, 
                    Coalesce( c.FirstName, ua.FirstName ) as FirstName, 
                    Coalesce( c.LastName, ua.LastName ) as LastName 
                FROM 
                    UserAgent ua 
                    LEFT OUTER JOIN Usr c ON c.UserID = ua.ClientID 
                WHERE ua.UserAgentID = ?
                    AND ua.IsApproved = 1 
                    AND ua.AgentID = ?
                ";
                $stmt = $connection->executeQuery($sql,
                    [$userAgentID, $userID],
                    [\PDO::PARAM_INT, \PDO::PARAM_INT]
                );
                $uAgent = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$uAgent) {
                    $r = false;
                }

                if ($r) {
                    if ($uAgent['ClientID'] > 0) {
                        if (
                            (!$wantCheck || ($uAgent['AccessLevel'] >= ACCESS_READ_ALL))
                            && (!$wantEdit || ($uAgent['AccessLevel'] >= ACCESS_WRITE))
                        ) {
                            $userAgentAccountFilter = "
                            ( 
                                ( a.UserID = {$uAgent['ClientID']} 
                                    AND a.AccountID IN ( 
                                        SELECT ash.AccountID 
                                        FROM AccountShare ash, 
                                            Account a 
                                        WHERE ash.AccountID = a.AccountID 
                                            AND a.UserID = {$uAgent['ClientID']} 
                                            AND ash.UserAgentID = {$uAgent['UserAgentID']} 
                                    ) 
                                ) OR 
                                ( a.UserAgentID = {$uAgent['UserAgentID']} 
                                    AND a.UserID = {$userID} 
                                ) 
                            )";
                        } else {
                            $userAgentAccountFilter = "0 = 1";
                        }
                    } else {
                        $userAgentAccountFilter = "a.UserAgentID = {$uAgent['UserAgentID']}";
                    }
                }
            } else {
                $userAgentAccountFilter = "a.UserID = {$userID} AND a.UserAgentID IS NULL";
            }
        }
        $userAgentCouponFilter = str_replace("a.", "c.", $userAgentAccountFilter);
        $userAgentCouponFilter = str_replace("Account", "ProviderCoupon", $userAgentCouponFilter);
        $userAgentCouponFilter = str_replace("ProviderCoupon a", "ProviderCoupon c", $userAgentCouponFilter);

        if ($r) {
            $this->userAgentAccountFilter = $userAgentAccountFilter;
            $this->userAgentCouponFilter = $userAgentCouponFilter;
        }

        return $r;
    }

    public function getShareableAgentsByUserID($userId, $connectionType = "A", ?TranslatorInterface $translator = null)
    {
        $sql = "select ua.UserAgentID, ua.ShareByDefault, " . SQL_USER_NAME . " as FullName, ua.AccessLevel, ua.ClientID
			from UserAgent ua, Usr u, UserAgent au
			where ua.AgentID = u.UserID and ua.ClientID = ?
			and ua.IsApproved = 1 and ua.AgentID = au.ClientID and ua.ClientID = au.AgentID
			and au.IsApproved = 1
			order by u.FirstName, u.LastName";

        $arAgentAccessLevelsAll = $this->getAgentAccessLevelsAll();
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->executeQuery($sql,
            [$userId]
        );
        $agents = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $data = [];

        foreach ($agents as $agent) {
            $data[] = [
                'value' => $agent['UserAgentID'],
                /** @Ignore */
                'label' => $agent['FullName'],
                'notice' => $translator ? $translator->trans(/** @Ignore */ $arAgentAccessLevelsAll[$agent['AccessLevel']]) : $arAgentAccessLevelsAll[$agent['AccessLevel']],
                'checked' => $agent['ShareByDefault'],
                'ClientID' => $agent['ClientID'],
                // 'link' => $link . '?ID=' . $agent['UserAgentID']
            ];
        }

        //				$qShare = new TQuery("select * from {$this->TableName}Share
        //					where {$this->KeyField} = {$nID} and UserAgentID = {$qAgent->Fields['UserAgentID']}");
        //				if ( $nID == 0 )
        //					$nValue = $qAgent->Fields['ShareByDefault'];
        //				else
        //					if ( $qShare->EOF )
        //						$nValue = 0;
        //					else
        //						$nValue = 1;
        //
        //				$file = (SITE_MODE == SITE_MODE_BUSINESS) ? 'editBusinessConnection.php' : 'editConnection.php';
        return $data;
    }

    public function getPossibleAccountOwners($userId)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
			SELECT   ua.UserAgentID                                    ,
			         ua.ClientID                                       ,
			         COALESCE( c.FirstName, ua.FirstName ) AS FirstName,
			         COALESCE( c.MidName, ua.MidName )     AS MidName  ,
			         COALESCE( c.LastName, ua.LastName )   AS LastName ,
			         c.AccountLevel                                    ,
			         c.Company
			FROM     UserAgent ua
			         LEFT OUTER JOIN Usr c
			         ON       ua.ClientID = c.UserID
			WHERE    ua.IsApproved        = 1
			AND
			         (
			                  (
			                           ua.AgentID        = ?
			                  AND      ua.ClientID IS NULL
			                  )
			         OR
			                  (
			                           ua.AgentID            = ?
			                  AND      ua.ClientID IS NOT NULL
			                  AND      ua.AccessLevel IN (?,?,?,?)
			                  )
			         )
			ORDER BY
			         CASE
			                  WHEN c.AccountLevel = ?
			                  THEN c.Company
			                  ELSE concat(COALESCE( c.FirstName, ua.FirstName ), COALESCE( c.LastName, ua.LastName ))
			         END
		";
        $stmt = $connection->executeQuery($sql,
            [$userId, $userId, ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCOUNT_LEVEL_BUSINESS],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($users as $key => &$user) {
            $user['UserName'] = $user['FirstName'] . " " . $user['MidName'] . " " . $user['LastName'];

            if (isset($user['AccountLevel']) && $user['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
                unset($users[$key]);
            }
        }

        return $users;
    }

    /**
     * this version works with entities.
     *
     * @param string $query search text
     * @param int $limit
     * @param bool $full
     * @return \AwardWallet\MainBundle\Entity\UserAgent[]
     */
    public function getPossibleAccountOwners2(Usr $user, $query = null, $limit = null, $full = false)
    {
        $q = $this->getEntityManager()->createQuery(
            "SELECT
			ua, u, concat(coalesce(ua.firstname, u.firstname), ' ', coalesce(ua.lastname, u.lastname)) HIDDEN fullName
		FROM
			AwardWallet\MainBundle\Entity\Useragent ua
			LEFT JOIN ua.clientid u
		WHERE
			ua.agentid = :userId
			AND ua.isapproved = 1
			AND ((ua.clientid is null) OR ( 1 = 1
			  " . ($full ? "" : "AND ua.accesslevel in (" . implode(", ", Useragent::possibleOwnerAccessLevels()) . ") ") . "
			  AND u.accountlevel <> " . ACCOUNT_LEVEL_BUSINESS . "
			))

			AND concat(coalesce(ua.firstname, u.firstname), ' ', coalesce(ua.midname, u.midname), ' ', coalesce(ua.lastname, u.lastname)) like :query
		ORDER BY
			fullName");

        if (!empty($limit)) {
            $q->setMaxResults($limit);
        }

        return $q->execute(['userId' => $user, 'query' => "%" . addcslashes($query, '%') . "%"]);
    }

    /**
     * @param bool|false $approved
     * @return UserAgent[]
     */
    public function inviteUser(Usr $inviter, Usr $invitee, $approved = false, $invites = null)
    {
        /** @var UserAgent $linkTo */
        $linkTo = $this->findOneBy(['agentid' => $inviter->getUserid(), 'clientid' => $invitee->getUserid()]);
        /** @var UserAgent $linkFrom */
        $linkFrom = $this->findOneBy(['agentid' => $invitee->getUserid(), 'clientid' => $inviter->getUserid()]);

        if (!$linkTo && $invites instanceof Invites) {
            $linkTo = $this->findOneBy(['agentid' => $inviter->getUserid(), 'useragentid' => $invites->getFamilyMember()]);
            empty($linkTo) ?: $linkTo->setClientid($invitee)->setFirstname(null)->setLastname(null)->setEmail(null);
        }

        if (!$linkTo) {
            $linkTo = new UserAgent();
            $linkTo->setAgentid($inviter);
            $linkTo->setClientid($invitee);
        }
        $linkTo->setAccesslevel(self::ACCESS_WRITE);
        $linkTo->setSharebydefault(true);
        $linkTo->setTripAccessLevel(true);
        $linkTo->setTripsharebydefault(true);
        $linkTo->setIsapproved($approved);
        $linkTo->setSendemails(true);
        $linkTo->setFirstname(null)->setLastname(null)->setEmail(null);
        $this->_em->persist($linkTo);

        $timelineRep = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class);
        $timelineRep->addTimelineShare($linkTo);

        if (!$linkFrom) {
            $linkFrom = new UserAgent();
            $linkFrom->setAgentid($invitee);
            $linkFrom->setClientid($inviter);
        }

        if ($inviter->isBusiness()) {
            $linkFrom->setAccesslevel(self::ACCESS_NONE);
            $linkFrom->setSharebydefault(false);
            $linkFrom->setTripsharebydefault(false);
        } else {
            $linkFrom->setAccesslevel(self::ACCESS_WRITE);
            $linkFrom->setSharebydefault(true);
            $linkFrom->setTripsharebydefault(true);
        }
        $linkFrom->setIsapproved(true);
        $linkFrom->setSendemails(true);
        $linkFrom->setFirstname(null)->setLastname(null)->setEmail(null);

        $this->_em->persist($linkFrom);
        $this->_em->flush();

        return [$linkTo, $linkFrom];
    }

    public function isExistingConnection(Usr $inviter, Usr $invitee)
    {
        /** @var UserAgent $linkTo */
        $linkTo = $this->findOneBy(['agentid' => $inviter->getUserid(), 'clientid' => $invitee->getUserid()]);
        /** @var UserAgent $linkFrom */
        $linkFrom = $this->findOneBy(['agentid' => $invitee->getUserid(), 'clientid' => $inviter->getUserid()]);

        if ($linkTo && $linkFrom) {
            return true;
        }

        return false;
    }

    public function updateUserAgent($userAgentID, $data)
    {
        $em = $this->getEntityManager();
        $repUserAgent = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $userAgent = $repUserAgent->findOneByUseragentid($userAgentID);

        foreach ($data as $method => $value) {
            $userAgent->$method($value);
        }
        $em->flush();
    }

    /**
     * creates unique alias for user family member
     * this alias goes to UserAgent.Alias field and used for email statement matching, like SiteAdmin.Polina@awardwallet.com
     * where 'Polina' is Alias.
     *
     * @param string $firstName
     * @param string $lastName
     * @return string
     * @throws \Exception
     */
    public function createAlias(Usr $user, $firstName, $lastName)
    {
        if ($user->isBusiness()) {
            $base = substr($firstName, 0, 1) . $lastName;
        } else {
            $base = $firstName;
        }

        $base = transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove;", $base);
        $base = preg_replace('#[^a-z\d]+#ims', '', $base);

        for ($n = 1; $n < 999; $n++) {
            if ($n == 1) {
                $result = $base;
            } else {
                $result = $base . $n;
            }

            if ($this->findOneBy(['alias' => $result, 'agentid' => $user, 'clientid' => null]) === null) {
                return $result;
            }
        }

        throw new \Exception("Could not create Alias for " . $user->getUserid() . ", $firstName, $lastName");
    }

    /**
     * get link to list of user+family member travel plans.
     *
     * @param AbPassenger $passenger
     * @param Useragent|null $targetAgent
     * @param bool $isBusiness - on business site
     * @return string|null
     */
    public function getPlansLink($passenger, $targetAgent, Usr $fromUser, $isBusiness)
    {
        $targetUser = $passenger->getUser();

        if (empty($targetUser) || (
            !$targetAgent
            && $passenger->getFullName() !== $targetUser->getFullName()
        )
        ) {
            return null;
        }

        if ($isBusiness) {
            $fromUser = $this->_em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($fromUser);
        }
        $fromUserId = $fromUser->getUserid();

        if ($targetUser->getUserid() != $fromUserId) {
            $connection = $this->findOneBy(['clientid' => $targetUser, 'agentid' => $fromUserId]);

            if (empty($connection)) {
                return null;
            }
            $userAgentId = $connection->getUseragentid();
        } else {
            $userAgentId = 'My';
        }

        if (!empty($targetAgent)) {
            if ($targetAgent->getAgentid()->getUserid() != $targetUser->getUserid()) {
                throw new \InvalidArgumentException(sprintf("Target user %d did not match agent %d", $targetUser->getUserid(), $targetAgent->getUseragentid()));
            }

            if (!$targetAgent->isFamilyMember()) {
                throw new \InvalidArgumentException(sprintf("Target agent %d must be family member", $targetAgent->getUseragentid()));
            }
            $userAgentId = $targetAgent->getUseragentid();

            if (!$targetAgent->isItinerariesSharedWith($fromUser)) {
                return null;
            }
        } elseif (!empty($connection)) {
            if (!$connection->isItinerariesSharedWith($fromUser)) {
                return null;
            }
        }

        return "/timeline/" . $userAgentId;
    }

    public function getAccessLevel(Usr $targetUser, Account $account, Usr $fromUser, $isBusiness)
    {
        if ($isBusiness && ($businessUser = $this->_em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($fromUser))) {
            $fromUserId = $businessUser->getUserid();
        } else {
            $fromUserId = $fromUser->getUserid();
        }

        if ($targetUser->getUserid() != $fromUserId) {
            /** @var Useragent $connection */
            $connection = $this->findOneBy(['clientid' => $targetUser, 'agentid' => $fromUserId]);

            if (!$connection) {
                return null;
            }
            $link = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Accountshare::class)->findOneBy(['useragentid' => $connection, 'accountid' => $account]);

            if (!$link) {
                return null;
            }

            return $connection->getAccesslevel();
        }

        return self::ACCESS_WRITE;
    }

    public function searchMembers(Usr $user, $string, $options = [])
    {
        $options = array_merge([
            'select' => "",
            'filter' => "",
            'excluding' => [],
            'limit' => 10,
            'max_words' => 3,
            'localizer' => null,
        ], $options);
        $conn = $this->getEntityManager()->getConnection();
        $filter = $options['filter'];

        if (sizeof($options['excluding'])) {
            $options['excluding'] = array_map(function ($v) use ($conn) {
                return $conn->quote($v);
            }, $options['excluding']);
            $filter .= " AND ua.UserAgentID NOT IN (" . implode(", ", $options['excluding']) . ")";
        }
        $words = preg_split('/[\\s,]+/', $string, $options['max_words'], PREG_SPLIT_NO_EMPTY);
        $countWords = sizeof($words);
        $pattern = [];

        foreach ($words as $k => $word) {
            if ($k + 1 == $countWords) {
                $word = str_replace(['_', '%'], ['\\_', '\\%'], $word);
                $pattern[] = "%field% LIKE " . $conn->quote("$word%");
            } else {
                $pattern[] = "%field% = " . $conn->quote($word);
            }
        }
        $pattern = "(" . implode(" OR ", $pattern) . ")";

        $query = "
            SELECT
                ua.UserAgentID,
                ua.Birthday AS UBirthday,
                COALESCE(u.FirstName, ua.FirstName) AS FirstName,
                COALESCE(u.MidName, ua.MidName) AS MidName,
                COALESCE(u.LastName, ua.LastName) AS LastName,
                IF(u.UserID IS NOT NULL, 1, 0) AS Connected
                {$options['select']}

            FROM
                UserAgent ua
                    LEFT OUTER JOIN Usr u ON u.UserID = ua.ClientID
            WHERE
                ua.AgentID = :user
                AND ua.IsApproved = 1
                $filter
                AND (
                    (" . str_replace("%field%", 'u.FirstName', $pattern) . " OR " . str_replace("%field%", 'u.LastName', $pattern) . ")
                    OR (" . str_replace("%field%", 'ua.FirstName', $pattern) . " OR " . str_replace("%field%", 'ua.LastName', $pattern) . ")
                )

            " . (isset($options['limit']) ? "LIMIT {$options['limit']}" : "") . "

        ";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':user', $user->getUserid(), \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $names = [];
        $dub = false;

        foreach ($result as &$row) {
            $name = implode(" ", [$row['FirstName'], $row['MidName'], $row['LastName']]);

            if (isset($names[$name])) {
                $names[$name]++;
                $dub = true;
            } else {
                $names[$name] = 0;
            }
            $row['id'] = $row['UserAgentID'];
            $row['text'] = $name;
        }

        if ($dub) {
            foreach ($result as &$row) {
                if (isset($names[$row['text']]) && $names[$row['text']] > 0 && !empty($row['UBirthday'])) {
                    $row['text'] = $row['text'] . " (" . $options['localizer']->formatDateTime(new \DateTime($row['UBirthday']), 'short', 'none') . ")";
                }
            }
        }

        return $result;
    }

    public function getUserAgentCountProgramsByUser(Usr $user)
    {
    }

    public function getAgentInfo($userId, $userAgentId, $localizer = null, $searchFilter = "")
    {
        $query = "
            SELECT
                ua.UserAgentID,
                COALESCE(u.FirstName, ua.FirstName) AS FirstName,
                COALESCE(u.MidName, ua.MidName) AS MidName,
                COALESCE(u.LastName, ua.LastName) AS LastName,
                ua.Birthday,
                IF(u.UserID IS NOT NULL, 1, 0) AS Connected
            FROM
                UserAgent ua
                    LEFT OUTER JOIN Usr u ON u.UserID = ua.ClientID
            WHERE
                ua.UserAgentID = ?
                AND ua.AgentID = ?
                AND ua.IsApproved = 1
        ";
        $stmt = $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            [$userAgentId, $userId],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        $result['id'] = $result['UserAgentID'];
        $result['text'] = $result['FirstName'] . " " . $result['MidName'] . " " . $result['LastName'];

        // find similar users
        if ($result['Birthday'] && $localizer) {
            $query = "
                SELECT
                    1
                FROM
                    UserAgent ua
                        LEFT OUTER JOIN Usr u ON u.UserID = ua.ClientID
                WHERE
                    ua.AgentID = :agent
                    AND ua.IsApproved = 1
                    AND (u.AccountLevel IS NULL OR u.AccountLevel <> " . ACCOUNT_LEVEL_BUSINESS . ")
                    AND (
                        (ua.FirstName = :fn AND ua.MidName = :mn AND ua.LastName = :ln)
                        OR (u.FirstName = :fn AND u.MidName = :mn AND u.LastName = :ln)
                    )
                    AND ua.UserAgentID <> :useragent
                    $searchFilter
                LIMIT 1
            ";
            $stmt = $this->getEntityManager()->getConnection()->prepare($query);
            $stmt->bindValue(':agent', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':useragent', $result['UserAgentID'], \PDO::PARAM_INT);
            $stmt->bindValue(':fn', $result['FirstName'], \PDO::PARAM_STR);
            $stmt->bindValue(':mn', $result['MidName'], \PDO::PARAM_STR);
            $stmt->bindValue(':ln', $result['LastName'], \PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->fetch(\PDO::FETCH_ASSOC) !== false) {
                $result['text'] = $result['text'] . " (" . $localizer->formatDateTime(new \DateTime($result['Birthday']), 'short', 'none') . ")";
            }
        }

        return $result;
    }

    public function getMembersCount($agentId)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
			SELECT count(*) cnt
            FROM UserAgent ua
            WHERE ua.AgentID = " . $agentId . " AND ua.IsApproved = 1";
        $stmt = $connection->executeQuery($sql);
        $members = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $members[0]['cnt'];
    }

    public function getKeepUpgradedMembersCount($agentId)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
			SELECT count(*) cnt
            FROM UserAgent ua
            WHERE ua.AgentID = " . $agentId . " AND ua.IsApproved = 1 and ua.KeepUpgraded = 1";
        $stmt = $connection->executeQuery($sql);
        $members = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $members[0]['cnt'];
    }

    public function getBusinessMembersInfo(Usr $agent)
    {
        $agentId = $agent->getUserid();
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
			SELECT
				ua.UserAgentID,
				" . SQL_USER_NAME . " as Name
            FROM UserAgent ua
				left outer join Usr u on ua.ClientID = u.UserID
            WHERE ua.AgentID = " . $agentId . " AND ua.IsApproved = 1";
        $stmt = $connection->executeQuery($sql);
        $members = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $members;
    }

    /** TODO: странный метод, нужно использовать сервис AwardWallet\MainBundle\Service\Counter */
    public function getBusinessMembersData(Usr $agent, $query = '', $limit = null)
    {
        $agentId = $agent->getUserid();
        $connection = $this->getEntityManager()->getConnection();
        $params = [];

        if ($query = trim($query)) {
            $params["query"] = "%" . $query . "%";
            $query = " AND " . SQL_USER_NAME . " LIKE :query";
        }

        $qMembers = "
        select
			ua.AgentID,
			ua.UserAgentID,
			ua.ShareDate,
			" . SQL_USER_NAME . " as Name,
			au.IsApproved,
			ua.ClientID,
			ua.Email,
			ua.AccessLevel,
			ua.KeepUpgraded,
			coalesce(if(ua.ClientID is not null, u.Email, ua.Email), '') as UserEmail,
			u.AccountLevel,
			u.PlusExpirationDate,
			if(ua.ClientID is null, '2', if(au.IsApproved = 0, '0' ,'1')) as type,
			coalesce(au.UserAgentID, ua.UserAgentID) as LinkUserAgentID,
			sum(case when ua.ClientID is null AND a.AccountID IS NOT NULL then 1 else case when ash.AccountShareID is not null then 1 else 0 end end) as Programs
		from UserAgent ua
			left outer join Usr u on ua.AgentID = u.UserID
			left outer join UserAgent au on ua.ClientID = au.AgentID and ua.AgentID = au.ClientID
			left outer join Account a on (u.UserID = a.UserID and ua.ClientID is not null)
				or (u.UserID = a.UserID and ua.ClientID is null and a.UserAgentID = ua.UserAgentID)
			left join Provider p ON a.ProviderID = p.ProviderID
			left outer join AccountShare ash on a.AccountID = ash.AccountID and ash.UserAgentID = au.UserAgentID
		where
			(ua.ClientID = {$agentId} or (ua.AgentID = {$agentId} and ua.ClientID is null)) AND " . $agent->getProviderFilter() . "
			{$query}
        group by
			AgentID, UserAgentID,
			ua.ShareDate,
            " . SQL_USER_NAME . ",
            au.IsApproved,
            ua.ClientID,
            ua.Email,
            ua.AccessLevel,
            ua.KeepUpgraded,
            coalesce(if(ua.ClientID is not null, u.Email, ua.Email), ''),
            u.AccountLevel,
            u.PlusExpirationDate,
            if(ua.ClientID is null, '2', if(au.IsApproved = 0, '0' ,'1')),
            coalesce(au.UserAgentID, ua.UserAgentID)

        union all

        select
			ua.AgentID,
			ua.UserAgentID,
			ua.ShareDate,
			" . SQL_USER_NAME . " as Name,
			au.IsApproved,
			ua.ClientID,
			ua.Email,
			ua.AccessLevel,
			ua.KeepUpgraded,
			coalesce(if(ua.ClientID is not null, u.Email, ua.Email), '') as UserEmail,
			u.AccountLevel,
			u.PlusExpirationDate,
			if(ua.ClientID is null, '2', if(au.IsApproved = 0, '0' ,'1')) as type,
			coalesce(au.UserAgentID, ua.UserAgentID) as LinkUserAgentID,
			sum(case when ua.ClientID is null AND a.ProviderCouponID IS NOT NULL then 1 else case when ash.ProviderCouponShareID is not null then 1 else 0 end end) as Programs
		from UserAgent ua
		    left outer join Usr u on ua.AgentID = u.UserID
			left outer join UserAgent au on ua.ClientID = au.AgentID and ua.AgentID = au.ClientID
			left outer join ProviderCoupon a on (u.UserID = a.UserID and ua.ClientID is not null)
				or (u.UserID = a.UserID and ua.ClientID is null and a.UserAgentID = ua.UserAgentID)
			left outer join ProviderCouponShare ash on a.ProviderCouponID = ash.ProviderCouponID and ash.UserAgentID = au.UserAgentID
		where
			(ua.ClientID = {$agentId} or (ua.AgentID = {$agentId} and ua.ClientID is null))
			{$query}
        group by
			AgentID, UserAgentID, 
			ua.ShareDate,
            " . SQL_USER_NAME . ",
            au.IsApproved,
            ua.ClientID,
            ua.Email,
            ua.AccessLevel,
            ua.KeepUpgraded,
            coalesce(if(ua.ClientID is not null, u.Email, ua.Email), ''),
            u.AccountLevel,
            u.PlusExpirationDate,
            if(ua.ClientID is null, '2', if(au.IsApproved = 0, '0' ,'1')),
            coalesce(au.UserAgentID, ua.UserAgentID)
		";

        if ($limit) {
            $qMembers .= " LIMIT {$limit}";
        }

        $qMembers = "select AgentID, UserAgentID, ShareDate, Name, IsApproved, ClientID, Email, AccessLevel, KeepUpgraded, UserEmail, AccountLevel, PlusExpirationDate, type, LinkUserAgentID, sum(t.Programs) as Programs from ({$qMembers}) t group by AgentID, UserAgentID, ShareDate, Name, IsApproved, ClientID, Email, AccessLevel, KeepUpgraded, UserEmail, AccountLevel, PlusExpirationDate, type, LinkUserAgentID";

        $members = $connection->executeQuery($qMembers, $params)->fetchAll(\PDO::FETCH_ASSOC);
        $userAgents = [];

        if (empty($members)) {
            return $members;
        }

        foreach ($members as $key => $member) {
            if ($member['AgentID'] != $agentId) {
                array_push($userAgents, $member['AgentID']);
            }
            $members[$key]['abRequests'] = 0;
            $members[$key]['AccountLevel'] = $member['AgentID'] != $agentId ? $member['AccountLevel'] : false;
            $members[$key]['Programs'] = (int) $member['Programs'];
            $members[$key]['KeepUpgraded'] = $member['AgentID'] != $agentId ? (int) $member['KeepUpgraded'] : -1;
        }

        if ($userAgents) {
            $qAbRequests = "
                SELECT  r.UserID,
                        count(*) AS abRequests
                FROM AbRequest r
                WHERE   r.BookerUserID = {$agentId}
                    AND r.Status in (" . implode(', ', AbRequest::getActiveStatuses()) . ")
                GROUP BY r.UserID
			";

            $abRequests = $connection->executeQuery($qAbRequests)->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($abRequests as $abRequest) {
                foreach ($members as $key => $member) {
                    if ($member['AgentID'] == $abRequest['UserID']) {
                        $members[$key]['abRequests'] = (int) $abRequest['abRequests'];

                        break;
                    }
                }
            }
        }

        $inviteRep = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Invitecode::class);
        $emailInvites = $inviteRep->findBy(['userid' => $agentId]);

        $emails = array_column($members, 'Email');

        foreach ($emailInvites as $item) {
            if (false !== array_search($item->getEmail(), $emails)) {
                continue;
            }
            $members[] = [
                'UserEmail' => $item->getEmail(),
                'type' => 0,
                'InviteCodeID' => $item->getInvitecodeid(),
            ];
        }

        return $members;
    }

    public function getUserPendingConnections(Usr $user)
    {
        $userID = $user->getUserid();
        $connection = $this->getEntityManager()->getConnection();

        $qPending = "
                        SELECT a.FirstName,
                               a.MidName,
                               a.LastName,
                               a.Company,
                               a.AccountLevel,
                               ua.UserAgentID,
                               ua.AgentID,
                               ua.AccessLevel
                        FROM UserAgent ua,
                             Usr a
                        WHERE ua.AgentID = a.UserID
                          AND ua.ClientID = {$userID}
                          AND ua.IsApproved = 0
                        ORDER BY a.FirstName,
                                 a.MidName,
                                 a.LastName
                        ";

        return $connection->executeQuery($qPending)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isAccountSharedWithBooker($accountId)
    {
        $stmt = $this->getEntityManager()->getConnection()->executeQuery('
            select
                1
            from
            AccountShare sh
                join UserAgent ua on sh.UserAgentID = ua.UserAgentID
                join AbBookerInfo bi on bi.UserID = ua.AgentID
            where AccountID = ?',
            [$accountId],
            [\PDO::PARAM_INT]
        );

        return false !== $stmt->fetch();
    }

    /**
     * @param bool $withWritePermission
     * @param string|null $name Filter by name
     * @param int $limit
     * @return UserAgent[]
     */
    public function findUserConnectionsByName(Usr $agent, string $name = '', $withWritePermission = false, $limit = 10)
    {
        $builder = $this->createQueryBuilder('connection');
        $expr = $builder->expr();
        $builder
            ->leftJoin('connection.clientid', 'client')
            ->where($expr->eq('connection.isapproved', true))
            ->andWhere($expr->eq('connection.agentid', ':agent'))
            ->andWhere($expr->isNotNull('connection.clientid'))
            ->andWhere($expr->lt('client.accountlevel', UsrRepository::ACCOUNT_LEVEL_BUSINESS))
            ->setParameter(':agent', $agent);

        if (!$agent->isBusiness()) {
            $builder->join(Useragent::class, 'link', 'with', $expr->andX(
                $expr->eq('link.agentid', 'connection.clientid'),
                $expr->eq('link.clientid', ':agent'),
                $expr->eq('link.isapproved', true)
            ));
        }

        if ('' !== $name) {
            $builder
                ->andWhere(
                    "concat(
                        case when client.firstname <> '' then concat(client.firstname, ' ') else '' end,
                        case when
                            client.midname is not null 
                            and client.midname <> ''
                        then concat(client.midname, ' ')
                        else '' end,
                        case when client.lastname <> '' then client.lastname else '' end
                    ) like :name"
                )
                ->setParameter(':name', "%$name%");
        }

        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        if ($withWritePermission) {
            $builder->andWhere($expr->in('connection.accesslevel', Useragent::possibleOwnerAccessLevels()));
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * @param string|null $name Filter by name
     * @param bool $withWritePermission
     * @param int $limit
     * @return Useragent[]
     */
    public function findUserConnectionsFamilyMembers(Usr $agent, string $name = '', $withWritePermission = false, $limit = 10)
    {
        $builder = $this->createQueryBuilder('familyMember');
        $expr = $builder->expr();
        $builder
            ->join('familyMember.agentid', 'familyOwner')
            ->join(Useragent::class, 'connection', 'with', $expr->andX(
                $expr->eq('connection.agentid', ':agent'),
                $expr->eq('connection.clientid', 'familyOwner')
            ))
            ->where($expr->eq('connection.isapproved', true))
            ->andWhere($expr->isNull('familyMember.clientid'))
            ->setParameter(':agent', $agent);

        if ('' !== $name) {
            $fullTextSearchName = MySQLFullTextSearchUtils::simpleBooleanModeFilter($name);

            if ('' !== $fullTextSearchName) {
                $builder
                    ->andWhere($expr->orX(
                        'MATCH (familyMember.firstname, familyMember.midname, familyMember.lastname) AGAINST (:nameFulltext boolean) > 0.0',
                        "concat(
                        case when familyOwner.firstname <> '' then concat(familyOwner.firstname, ' ') else '' end,
                        case when
                            familyOwner.midname is not null 
                            and familyOwner.midname <> ''
                        then concat(familyOwner.midname, ' ')
                        else '' end,
                        case when familyOwner.lastname <> '' then familyOwner.lastname else '' end
                    ) like :name"
                    ))
                    ->setParameter(':nameFulltext', $fullTextSearchName)
                    ->setParameter(':name', "%$name%");
            }
        }

        if ($withWritePermission) {
            $builder->andWhere($expr->in('connection.accesslevel', Useragent::possibleOwnerAccessLevels()));
        }

        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * @param string|null $name Filter by name
     * @param int $limit
     * @return UserAgent[]
     */
    public function findUserFamilyMembersByName(Usr $agent, string $name = '', $limit = 10)
    {
        $builder = $this->createQueryBuilder('familyMember');
        $expr = $builder->expr();
        $builder
            ->where($expr->eq('familyMember.agentid', ':agent'))
            ->andWhere($expr->isNull('familyMember.clientid'))
            ->setParameter(':agent', $agent);

        if ('' !== $name) {
            $fullTextSearchName = MySQLFullTextSearchUtils::simpleBooleanModeFilter($name);

            if ('' !== $fullTextSearchName) {
                $builder
                    ->andWhere('MATCH (familyMember.firstname, familyMember.midname, familyMember.lastname) AGAINST (:name boolean) > 0.0')
                    ->setParameter(':name', $fullTextSearchName);
            }
        }

        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        return $builder->getQuery()->getResult();
    }

    public function cancelInvite(UserAgent $agent, string $email, ?string $code = null): bool
    {
        $condition = ['email' => $email];

        if (!empty($code)) {
            $condition['code'] = $code;
        }

        $agent
            ->setSharedate(null)
            ->setSharecode(null);

        if ($invitecodeRow = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Invitecode::class)->findOneBy(['userid' => $agent->getAgentid()] + $condition)) {
            $this->getEntityManager()->remove($invitecodeRow);
        }

        if ($invitesRow = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Invites::class)->findOneBy(['inviterid' => $agent->getAgentid()] + $condition)) {
            $this->getEntityManager()->remove($invitesRow);
        }

        $this->getEntityManager()->persist($agent);
        $this->getEntityManager()->flush();

        return true;
    }
}
