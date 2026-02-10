<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\Booking;
use AwardWallet\MainBundle\Entity\CartItem\PlusItems;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\RepositoryTrait;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @method Usr|null findOneByUserid($userId)
 * @template-extends EntityRepository<Usr>
 */
class UsrRepository extends EntityRepository implements UserLoaderInterface
{
    use RepositoryTrait;

    public const ACCOUNT_LEVEL_FREE = 1;
    public const ACCOUNT_LEVEL_AWPLUS = 2;
    public const ACCOUNT_LEVEL_BUSINESS = 3;

    public const UNKNOWN_EMAIL = 'unknown@awardwallet.com';

    protected $accountLevel = [
        self::ACCOUNT_LEVEL_FREE => "Regular",
        self::ACCOUNT_LEVEL_AWPLUS => "AwardWallet Plus",
        self::ACCOUNT_LEVEL_BUSINESS => "AwardWallet Business",
    ];

    public function getAccountLevelArray()
    {
        return $this->accountLevel;
    }

    public function isBusinessById($id)
    {
        $stmt = $this->getEntityManager()->getConnection()->executeQuery("
			SELECT u.UserID
			FROM   Usr u
			WHERE  u.UserID = ?
				AND u.AccountLevel = ?
			",
            [
                $id,
                ACCOUNT_LEVEL_BUSINESS,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
            ]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($r === false) {
            return false;
        }

        return true;
    }

    public function getBusinessIdByLogin($userLogin)
    {
        $stmt = $this->getEntityManager()->getConnection()->executeQuery("
			SELECT u.UserID
			FROM   Usr u
			WHERE  u.Login = ?
				AND u.AccountLevel = ?
			",
            [
                $userLogin,
                ACCOUNT_LEVEL_BUSINESS,
            ],
            [
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
            ]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($r === false) {
            return false;
        }

        return $r['UserID'];
    }

    public function isAdminBusinessAccount($userID)
    {
        return $this->getBusinessIdByUserAdmin($userID) !== false;
    }

    public function getBusinessIdByUserAdmin($userID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT ua.ClientID
		FROM   UserAgent ua,
		       Usr u
		WHERE  ua.AgentID         = ?
		       AND ua.ClientID    = u.UserID
		       AND u.AccountLevel = ?
		       AND ua.AccessLevel in (?, ?, ?)
		";
        $stmt = $connection->executeQuery($sql,
            [
                $userID,
                ACCOUNT_LEVEL_BUSINESS,
                ACCESS_ADMIN,
                ACCESS_BOOKING_MANAGER,
                ACCESS_BOOKING_VIEW_ONLY,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
            ]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($r === false) {
            return false;
        }

        return $r['ClientID'];
    }

    /**
     * @return Usr|null
     */
    public function getBookerByUser(Usr $user)
    {
        $business = $this->getBusinessByUser($user);

        return ($business && $business->isBooker()) ? $business : null;
    }

    /**
     * @return bool
     */
    public function isUserBusinessAdmin(Usr $user)
    {
        return $this->getBusinessByUser($user, [ACCESS_ADMIN]) !== null;
    }

    /**
     * @return Usr|null
     */
    public function getBusinessByUser(
        Usr $user,
        array $accessLevels = [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY],
        bool $useCache = true
    ) {
        if ($useCache && $user->_hasBusinessByLevel($accessLevels)) {
            // Why put this dirt in the repository?
            return $user->_getBusinessByLevel($accessLevels);
        }

        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(Usr::class, 'u')

            ->join(Useragent::class, 'ua', 'WITH', 'u.userid = ua.clientid AND u.accountlevel = :accountLevel')
            ->setParameter(':accountLevel', ACCOUNT_LEVEL_BUSINESS)

            ->join(Usr::class, 'u_origin', 'WITH', 'ua.agentid = u_origin.userid')

            ->where('u_origin.userid = :user')
            ->setParameter(':user', $user)

            ->andWhere('ua.isapproved = 1')
            ->setMaxResults(1);

        if ($accessLevels) {
            $qb
                ->andWhere('ua.accesslevel IN (:accessLevels)')
                ->setParameter(':accessLevels', $accessLevels);
        }

        return $user->_setBusinessByLevel(
            $qb
                ->getQuery()
                ->getOneOrNullResult(),
            $accessLevels
        );
    }

    /**
     * @param string $field
     * @return \Doctrine\ORM\Query\Expr\Base
     */
    public function getUserAndProviderFilterDQL(QueryBuilder $qb, Usr $user, $field = "p.state")
    {
        $filter = $qb->expr()->orX(
            $qb->expr()->gt($field, 0),
            $qb->expr()->isNull($field)
        );

        if ($user->getBetaapproved()) {
            $filter->add($qb->expr()->eq($field, PROVIDER_IN_BETA));
        }

        if ($user->hasRole('ROLE_STAFF')) {
            $filter->add($qb->expr()->eq($field, PROVIDER_TEST));
        }

        return $filter;
    }

    public function isUserInGroup($userID, $sGroupName)
    {
        $stmt = $this->getEntityManager()->getConnection()->executeQuery("
			SELECT g.SiteGroupID,
			       g.GroupName
			FROM   SiteGroup g
			       INNER JOIN GroupUserLink l
			       ON     g.SiteGroupID = l.SiteGroupID
			WHERE  l.UserID             = ?
			       AND g.GroupName      = ?",
            [
                $userID,
                $sGroupName,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
            ]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($r === false) {
            return false;
        }

        return true;
    }

    public function getUserGroups($userID)
    {
        $result = [];
        $stmt = $this->getEntityManager()->getConnection()->executeQuery("
			SELECT g.GroupName
			FROM   GroupUserLink l
			       LEFT JOIN SiteGroup g
			       ON     g.SiteGroupID = l.SiteGroupID
			WHERE  l.UserID             = ?",
            [$userID],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!sizeof($r)) {
            return $result;
        }

        foreach ($r as $row) {
            $result[] = $row['GroupName'];
        }

        return $result;
    }

    /**
     * @return array Usr
     */
    public function getBusinessAdmins(Usr $business)
    {
        $query = $this->getEntityManager()->createQuery("
            SELECT
              u
            FROM AwardWallet\MainBundle\Entity\Usr u
              JOIN u.connections c
              JOIN c.clientid u2
            WHERE
              c.accesslevel = :accesslevel
              AND c.isapproved = 1
              AND u2.accountlevel = :accountlevel
              AND u2.userid = :business
        ");
        $query->setParameter('accesslevel', ACCESS_ADMIN);
        $query->setParameter('accountlevel', ACCOUNT_LEVEL_BUSINESS);
        $query->setParameter('business', $business->getUserid());
        $result = $query->getResult(); // //d
        @usort($result, function ($a, $b) {
            $_a = $a->getFullName();
            $_b = $b->getFullName();

            if ($_a == $_b) {
                return 0;
            }

            return ($_a < $_b) ? -1 : 1;
        });

        return $result;
    }

    /**
     * @return Usr[]
     */
    public function getBusinessManagers(Usr $business)
    {
        $query = $this->getEntityManager()->createQuery("
            SELECT
              u
            FROM AwardWallet\MainBundle\Entity\Usr u
              JOIN u.connections c
              JOIN c.clientid u2
            WHERE
              c.accesslevel in (:accesslevel, :accesslevelm, :accesslevelr)
              AND c.isapproved = 1
              AND u2.accountlevel = :accountlevel
              AND u2.userid = :business
        ");
        $query->setParameter('accesslevel', ACCESS_ADMIN);
        $query->setParameter('accesslevelm', ACCESS_BOOKING_MANAGER);
        $query->setParameter('accesslevelr', ACCESS_BOOKING_VIEW_ONLY);
        $query->setParameter('accountlevel', ACCOUNT_LEVEL_BUSINESS);
        $query->setParameter('business', $business->getUserid());
        $result = $query->getResult(); // //d
        @usort($result, function ($a, $b) {
            $_a = $a->getFullName();
            $_b = $b->getFullName();

            if ($_a == $_b) {
                return 0;
            }

            return ($_a < $_b) ? -1 : 1;
        });

        return $result;
    }

    /**
     * @deprecated use self::getBusinessAdmins
     * @return array
     */
    public function getBusinessAdminsDataByBusinessEmail($email)
    {
        $stmt = $this->getEntityManager()->getConnection()->executeQuery("
			SELECT u.Email                                               ,
			       concat(trim(concat(u.FirstName, ' ', coalesce(u.MidName, ''))), ' ', u.LastName) UserName,
			       u.UserID
			FROM   Usr u
			       JOIN UserAgent ua
			       ON     ua.AgentID         = u.UserID
			              AND ua.AccessLevel = ?
			              AND ua.IsApproved  = 1
			       JOIN Usr u2
			       ON     u2.UserID           = ua.ClientID
			              AND u2.AccountLevel = ?
			WHERE  u2.Email                   = ?
			ORDER BY UserName ASC
			",
            [
                ACCESS_ADMIN,
                ACCOUNT_LEVEL_BUSINESS,
                $email,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
            ]
        );

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @deprecated use self::getBusinessAdmins
     * @return array
     */
    public function getBusinessAdminsDataByBusinessID($id)
    {
        $stmt = $this->getEntityManager()->getConnection()->executeQuery("
			SELECT u.Email                                               ,
			       concat(trim(concat(u.FirstName, ' ', coalesce(u.MidName, ''))), ' ', u.LastName) UserName,
			       u.UserID
			FROM   Usr u
			       JOIN UserAgent ua
			       ON     ua.AgentID         = u.UserID
			              AND ua.AccessLevel = ?
			              AND ua.IsApproved  = 1
			       JOIN Usr u2
			       ON     u2.UserID           = ua.ClientID
			              AND u2.AccountLevel = ?
			WHERE  u2.UserID                   = ?
			ORDER BY UserName ASC
			",
            [
                ACCESS_ADMIN,
                ACCOUNT_LEVEL_BUSINESS,
                $id,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
            ]
        );

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @deprecated use self::getBusinessAdmins
     * @return array
     */
    public function getBusinessAdminsEmailsByBusinessEmail($email)
    {
        $fields = $this->getBusinessAdminsDataByBusinessEmail($email);

        if (empty($fields)) {
            return $email;
        }
        $emails = [];

        foreach ($fields as $data) {
            $emails[] = $data['Email'];
        }

        return $emails;
    }

    public function getUsersCount()
    {
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->executeQuery("SELECT COUNT(*) AS cnt FROM Usr");
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        $usersCount = ($r !== false) ? $r['cnt'] : 0;

        return $usersCount;
    }

    public function moveOwnership(Usr $from, Usr $to, $options = [])
    {
        $options = array_merge([
            'move.accounts' => true,
            'move.accounts.filter' => [],
            'move.accounts.moveBusinessAccounts' => false,
            'move.travelPlans' => true,
            'move.travelPlans.filter' => [],
            'move.user.family' => true,
            'move.user.family.filter' => [],
            'move.user.connected' => true,
            'move.user.connected.filter' => [],
            'move.log' => function () {
            },
        ], $options);

        // Logger
        $log = function ($str) use ($options) {
            call_user_func($options['move.log'], $str);
        };
        $connection = $this->getEntityManager()->getConnection();
        $accountRep = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $userAgentRep = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $userAgentRep->setAgentFilters($to->getUserid(), \AwardWallet\MainBundle\Entity\Repositories\UseragentRepository::ALL_USERAGENTS);
        $toAccountSql = $accountRep->getAccountsSQLByUser($to->getUserid(), '', '', $userAgentRep->userAgentAccountFilter, $userAgentRep->userAgentCouponFilter);
        $toTravelPlanSql = "
			SELECT
				tp.*
			FROM
				TravelPlan tp
				left outer join Usr u on tp.UserID = u.UserID
				left outer join UserAgent ua on tp.UserAgentID = ua.UserAgentID
			WHERE
				TravelPlanID in (
					SELECT
						tp.TravelPlanID
					FROM
						TravelPlan tp, UserAgent ua, TravelPlanShare tpsh
					WHERE
						ua.AgentID = " . $connection->quote($to->getUserid(), \PDO::PARAM_INT) . "
						AND ua.IsApproved = 1
						AND (ua.ClientID IS NULL AND tp.UserAgentID = ua.UserAgentID)
						AND tpsh.TravelPlanID = tp.TravelPlanID
						AND tpsh.UserAgentID = ua.UserAgentID
					UNION
					SELECT
						TravelPlanID
					FROM
						TravelPlan
					WHERE
						UserID = " . $connection->quote($to->getUserid(), \PDO::PARAM_INT) . "
				)
		";

        $log('From UserID: ' . $from->getFullName() . ' (' . $from->getUserid() . '), To UserID: ' . $to->getFullName() . ' (' . $to->getUserid() . ')');
        $log('start move...');
        $log('begin transaction');
        $connection->beginTransaction();

        try {
            // Move Family members
            if ($options['move.user.family']) {
                $log('move family members...');

                if (sizeof($options['move.user.family.filter'])) {
                    $log('only family members: ' . implode(', ', $options['move.user.family.filter']));
                }
                $sql = "
					SELECT   *
					FROM     UserAgent ua
					WHERE    ua.AgentID            = ?
					         AND ua.ClientID IS NULL
				";
                $rowsFrom = $connection->executeQuery($sql,
                    [$from->getUserid()],
                    [\PDO::PARAM_INT]
                )->fetchAll(\PDO::FETCH_ASSOC);

                if (sizeof($rowsFrom)) {
                    $temp = [];

                    foreach ($rowsFrom as $v) {
                        $temp[$v['FirstName'] . ' ' . $v['LastName']] = $v;
                    }
                    $rowsFrom = $temp;
                    unset($temp);

                    if (sizeof($options['move.user.family.filter'])) {
                        foreach ($rowsFrom as $i => $row) {
                            if (!in_array($row['UserAgentID'], $options['move.user.family.filter'])) {
                                unset($rowsFrom[$i]);
                            }
                        }
                    }

                    if (sizeof($rowsFrom)) {
                        $rowsTo = $connection->executeQuery($sql,
                            [$to->getUserid()],
                            [\PDO::PARAM_INT]
                        )->fetchAll(\PDO::FETCH_ASSOC);
                        $temp = [];

                        foreach ($rowsTo as $v) {
                            $temp[$v['FirstName'] . ' ' . $v['LastName']] = $v;
                        }
                        $rowsTo = $temp;
                        unset($temp);

                        $forMove = [];
                        $oldFamilyMembers = [];

                        foreach ($rowsFrom as $name => $row) {
                            if (!isset($rowsTo[$name])) {
                                // Move family member
                                $forMove[] = $row['UserAgentID'];
                            } else {
                                $oldFamilyMembers[$row['UserAgentID']] = $rowsTo[$name]['UserAgentID'];
                            }
                        }

                        if (sizeof($forMove)) {
                            $connection->executeUpdate('UPDATE UserAgent SET AgentID = ? WHERE UserAgentID IN (?)',
                                [
                                    $to->getUserid(),
                                    $forMove,
                                ],
                                [
                                    \PDO::PARAM_INT,
                                    \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                ]
                            );
                            $log('moved: ' . implode(', ', $forMove));
                        } else {
                            $log('family member is exists');
                        }

                        $accounts = [];
                        $accountFilter = '';

                        // Move Accounts
                        if ($options['move.accounts']) {
                            $log('move family members accounts...');
                            $corpFilter = '';

                            if (!$options['move.accounts.moveBusinessAccounts']) {
                                $corpFilter = " AND p.Corporate <> 1";
                            }

                            if (sizeof($options['move.accounts.filter'])) {
                                $log('only accounts: ' . implode(', ', $options['move.accounts.filter']));
                                $accountFilter = [];

                                foreach ($options['move.accounts.filter'] as $aid) {
                                    $accountFilter[] = $connection->quote($aid, \PDO::PARAM_INT);
                                }
                                $accountFilter = " AND a.AccountID IN (" . implode(", ", $accountFilter) . ")";
                            }

                            // Moved members
                            if (sizeof($forMove)) {
                                $rows = $connection->executeQuery("
									SELECT
										a.AccountID
									FROM Account a
										JOIN Provider p ON p.ProviderID = a.ProviderID
									WHERE 
										a.UserAgentID IN (?)
										AND a.UserID = ?
										$accountFilter
										$corpFilter
										AND CONCAT_WS('@', a.UserID, a.ProviderID, a.Login, a.Login2) NOT IN (
											SELECT CONCAT_WS('@', a.UserID, a.ProviderID, a.Login, a.Login2 )
											FROM (
												$toAccountSql
											) as a
										)
									",
                                    [
                                        $forMove,
                                        $from->getUserid(),
                                    ],
                                    [
                                        \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                        \PDO::PARAM_INT,
                                    ]
                                )->fetchAll(\PDO::FETCH_ASSOC);
                                $temp = [];

                                foreach ($rows as $row) {
                                    $temp[] = $row['AccountID'];
                                }

                                $accounts = $temp;

                                if (sizeof($temp)) {
                                    $connection->executeUpdate('UPDATE Account SET UserID = ? WHERE AccountID IN (?)',
                                        [
                                            $to->getUserid(),
                                            $temp,
                                        ],
                                        [
                                            \PDO::PARAM_INT,
                                            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                        ]
                                    );
                                }
                            }

                            if (sizeof($oldFamilyMembers)) {
                                $rows = $connection->executeQuery("
									SELECT
										a.AccountID, a.UserAgentID
									FROM Account a
										JOIN Provider p ON p.ProviderID = a.ProviderID
									WHERE 
										a.UserAgentID IN (?)
										AND a.UserID = ?
										$accountFilter
										$corpFilter
										AND CONCAT_WS('@', a.UserID, a.ProviderID, a.Login, a.Login2) NOT IN (
											SELECT CONCAT_WS('@', a.UserID, a.ProviderID, a.Login, a.Login2 )
											FROM (
												$toAccountSql
											) as a
										)
									",
                                    [
                                        array_keys($oldFamilyMembers),
                                        $from->getUserid(),
                                    ],
                                    [
                                        \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                        \PDO::PARAM_INT,
                                    ]
                                )->fetchAll(\PDO::FETCH_ASSOC);
                                $temp = [];

                                foreach ($rows as $row) {
                                    if (!isset($temp[$row['UserAgentID']])) {
                                        $temp[$row['UserAgentID']] = [];
                                    }
                                    $accounts[] = $row['AccountID'];
                                    $temp[$row['UserAgentID']][] = $row['AccountID'];
                                }

                                foreach ($temp as $uaId => $accounts) {
                                    $connection->executeUpdate('UPDATE Account SET UserID = ?, UserAgentID = ? WHERE AccountID IN (?)',
                                        [
                                            $to->getUserid(),
                                            $oldFamilyMembers[$uaId],
                                            $accounts,
                                        ],
                                        [
                                            \PDO::PARAM_INT,
                                            \PDO::PARAM_INT,
                                            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                        ]
                                    );
                                }
                            }
                        }

                        // Move Travel Plans

                        if ($options['move.travelPlans']) {
                            $log('move family members travel plans...');
                            $travelPlanFilter = '';
                            $corpFilter = '';

                            if (!$options['move.accounts.moveBusinessAccounts']) {
                                $corpFilter = " AND p.Corporate <> 1";
                            }

                            if (sizeof($options['move.travelPlans.filter'])) {
                                $log('only travel plans: ' . implode(', ', $options['move.travelPlans.filter']));
                                $travelPlanFilter = [];

                                foreach ($options['move.travelPlans.filter'] as $tpid) {
                                    $travelPlanFilter[] = $connection->quote($tpid, \PDO::PARAM_INT);
                                }
                                $travelPlanFilter = " AND tp.TravelPlanID IN (" . implode(", ", $travelPlanFilter) . ")";
                            }

                            // Moved members
                            $travelPlans = [];

                            if (sizeof($forMove)) {
                                $rows = $connection->executeQuery("
									SELECT
										tp.TravelPlanID
									FROM TravelPlan tp
									WHERE 
										tp.UserAgentID IN (?)
										AND tp.UserID = ?
										$travelPlanFilter
										AND tp.Name NOT IN (
											SELECT tp.Name
											FROM (
												$toTravelPlanSql
											) as tp
										)
									",
                                    [
                                        $forMove,
                                        $from->getUserid(),
                                    ],
                                    [
                                        \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                        \PDO::PARAM_INT,
                                    ]
                                )->fetchAll(\PDO::FETCH_ASSOC);
                                $temp = [];

                                foreach ($rows as $row) {
                                    $temp[] = $row['TravelPlanID'];
                                }

                                $travelPlans = $temp;

                                if (sizeof($temp)) {
                                    $connection->executeUpdate('UPDATE TravelPlan SET UserID = ? WHERE TravelPlanID IN (?)',
                                        [
                                            $to->getUserid(),
                                            $temp,
                                        ],
                                        [
                                            \PDO::PARAM_INT,
                                            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                        ]
                                    );
                                }
                            }

                            if (sizeof($oldFamilyMembers)) {
                                $rows = $connection->executeQuery("
									SELECT
										tp.TravelPlanID, tp.UserAgentID
									FROM TravelPlan tp
									WHERE 
										tp.UserAgentID IN (?)
										AND tp.UserID = ?
										$travelPlanFilter
										AND tp.Name NOT IN (
											SELECT tp.Name
											FROM (
												$toTravelPlanSql
											) as tp
										)
									",
                                    [
                                        array_keys($oldFamilyMembers),
                                        $from->getUserid(),
                                    ],
                                    [
                                        \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                        \PDO::PARAM_INT,
                                    ]
                                )->fetchAll(\PDO::FETCH_ASSOC);
                                $temp = [];

                                foreach ($rows as $row) {
                                    if (!isset($temp[$row['UserAgentID']])) {
                                        $temp[$row['UserAgentID']] = [];
                                    }
                                    $travelPlans[] = $row['TravelPlanID'];
                                    $temp[$row['UserAgentID']][] = $row['TravelPlanID'];
                                }

                                foreach ($temp as $uaId => $plans) {
                                    $connection->executeUpdate('UPDATE TravelPlan SET UserID = ?, UserAgentID = ? WHERE TravelPlanID IN (?)',
                                        [
                                            $to->getUserid(),
                                            $oldFamilyMembers[$uaId],
                                            $plans,
                                        ],
                                        [
                                            \PDO::PARAM_INT,
                                            \PDO::PARAM_INT,
                                            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                                        ]
                                    );
                                }
                            }

                            // Update reservations
                            /*if (sizeof($travelPlans)) {
                                foreach (array("Trip", "Reservation", "Restaurant", "Direction", "Rental") as $table) {
                                    $connection->executeUpdate("update {$table}, TravelPlan set {$table}.UserID = TravelPlan.UserID where TravelPlan.UserID IN (?) and {$table}.TravelPlanID = TravelPlan.TravelPlanID",
                                        array($to->getUserid(), $oldFamilyMembers[$uaId], $plans),
                                        array(\PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
                                    );
                                }
                            }*/
                        }
                    } else {
                        $log('family members not found');
                    }
                } else {
                    $log('family members not found');
                }
            }
        } catch (\Exception $e) {
            $log('fail transaction. Rollback...');
            $connection->rollback();
            $this->getEntityManager()->close();

            throw $e;
        }
    }

    public function loadUserByUsername($username)
    {
        $q = $this
            ->createQueryBuilder('u')
            ->where('(u.login = :login OR u.email = :email) and u.accountlevel <> ' . ACCOUNT_LEVEL_BUSINESS)
            ->setParameter('login', $username)
            ->setParameter('email', $username)
            ->setMaxResults(1)
            ->getQuery();

        return $q->getOneOrNullResult();
    }

    /**
     * @return int
     * get unknown user id
     * @throws \Exception
     */
    public function getUnknownUserId()
    {
        $result = $this->getEntityManager()->getConnection()
            ->executeQuery('select UserID from Usr where Email = ?', [self::UNKNOWN_EMAIL])
            ->fetchColumn(0);

        if ($result === false) {
            throw new \Exception("unknown user not found");
        }

        return $result;
    }

    public function isBusinessAccountByUser($userID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
			SELECT AccountLevel FROM Usr WHERE UserID = ?
		";
        $stmt = $connection->executeQuery($sql,
            [$userID],
            [\PDO::PARAM_INT]
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new \Exception("User not found");
        }

        return intval($row['AccountLevel']) == self::ACCOUNT_LEVEL_BUSINESS;
    }

    public function getAgentId(Usr $user, Usr $currentuser)
    {
        // @TODO Need refactoring

        if ($user->getId() == $currentuser->getId()) {
            return 'My';
        }

        $usr = $this->_em->createQuery('select a.useragentid from AwardWallet\MainBundle\Entity\Useragent a where a.clientid = :client and a.agentid = :agent')
            ->setParameters([
                'client' => $user->getId(),
                'agent' => $currentuser->getId(),
            ])
            ->getOneOrNullResult();

        if ($usr) {
            return $usr['useragentid'];
        }

        return null;
    }

    public function getPaymentStatsByUser($userID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $row = $connection->executeQuery(
            "
            SELECT 
                COUNT(DISTINCT CartID) AS PaidOrders, 
                ROUND(SUM(LifetimeContribution), 2) AS LifetimeContribution
            FROM (
                SELECT 
                       ci.CartID,
                       SUM(round(ci.Price * ci.Cnt * (100-ci.Discount)/100, 2)) AS LifetimeContribution
                FROM   Cart c
                       JOIN CartItem ci ON c.CartID = ci.CartID
                       LEFT OUTER JOIN CartItem cib on cib.CartID = ci.CartID AND cib.TypeID = ?
                WHERE  c.UserID                = ?
                       AND c.PayDate IS NOT NULL
                       AND (ci.TypeID <> ? OR ci.TypeID IS NULL)
                       AND c.PaymentType <> ?
                       AND
                       (
                              ci.Price * ci.Cnt * ((100-ci.Discount)/100)
                       )
                       <> 0
                       AND ci.ScheduledDate is null
                       AND cib.CartItemID is null 
                GROUP BY
                       ci.CartID
            ) c
            WHERE
                LifetimeContribution > 0
            ",
            [Booking::TYPE, $userID, Booking::TYPE, PAYMENTTYPE_BUSINESS_BALANCE],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);
        $row['LifetimeContribution'] = round($row['LifetimeContribution'], 2);

        return $row;
    }

    /**
     * creates unique login for user, based on some text
     * this alias used for email statement matching, like SiteAdmin@awardwallet.com
     * method introduced for generating business logins from company name.
     *
     * @param int $userId - what user you are editing. specify 0 for new user
     * @param string $base
     */
    public function createLogin($userId, $base)
    {
        $base = preg_replace('#[^a-z\d]+#ims', '', $base);

        for ($n = 1; $n < 999; $n++) {
            if ($n == 1) {
                $result = $base;
            } else {
                $result = $base . $n;
            }
            $match = $this->findOneBy(['login' => $result]);

            if ($match === null || $match->getUserid() == $userId) {
                return $result;
            }
        }

        throw new \Exception("Could not create Alias for " . $userId . ", $base");
    }

    public function isTrialAccount(Usr $user)
    {
        if (ACCOUNT_LEVEL_AWPLUS !== $user->getAccountlevel() || !empty($user->getSubscription())) {
            return false;
        }

        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
			    ci.TypeID
		    FROM
			    Cart c
			    JOIN CartItem ci ON c.CartID = ci.CartID
		    WHERE
			    c.PayDate IS NOT NULL
			    AND c.UserID = ?
			    AND ci.TypeID IN (" . implode(',', PlusItems::getTypes()) . ")
		    ORDER BY c.PayDate DESC
		    LIMIT 2
        ";

        $result = $conn->executeQuery($sql, [$user->getUserid()], [\PDO::PARAM_INT]);
        $row = $result->fetch(\PDO::FETCH_ASSOC);

        return $row !== false
            && in_array((int) $row['TypeID'], CartItem::TRIAL_TYPES, true)
            && $user->getPlusExpirationDate() > date_create()
            && $result->fetch(\PDO::FETCH_ASSOC) === false;
    }

    /**
     * @return Usr[]
     */
    public function getStaffUsers(): array
    {
        $userNames = $this->getEntityManager()->getConnection()->executeQuery("
            SELECT DISTINCT u.Login
            FROM Usr u
                JOIN GroupUserLink gl ON u.UserID = gl.UserID
                JOIN SiteGroup g ON gl.SiteGroupID = g.SiteGroupID
            WHERE g.GroupName = 'staff'
        ")->fetchFirstColumn();

        return $this->findBy(['login' => $userNames]);
    }

    /**
     * Поиск пользователя по токену доступа TripIt.
     */
    public function findByTripitAccessToken(string $token): ?Usr
    {
        $query = $this->createQueryBuilder('usr')
            ->where('JSON_EXTRACT(usr.tripitOauthToken, :path) = :token')
            ->setParameter('path', '$.oauth_access_token')
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->getQuery();

        return $query->getOneOrNullResult();
    }
}
