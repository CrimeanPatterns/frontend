<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Elitelevel;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\FunctionalUtils;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

class ProviderRepository extends EntityRepository
{
    public const PROVIDER_SEARCH_ALLOWED_STATES = [
        PROVIDER_DISABLED,
        PROVIDER_ENABLED,
        PROVIDER_FIXING,
        PROVIDER_COLLECTING_ACCOUNTS,
        PROVIDER_CHECKING_OFF,
        PROVIDER_CHECKING_WITH_MAILBOX,
        PROVIDER_CHECKING_EXTENSION_ONLY,
    ];
    private $stopWords = [
        '/rewards?|bonus|(?<!choice\s)privileges?/', '/airlines?/', 'air', 'business', 'card',
        'miles', 'com', '/programs?/', 'plus', 'status',
        'club', 'frequent flyer', '/awards?/', 'loyalty', '/affiliate programs?/', '/dollars?/',
    ];
    private $filteredWords;

    public function getAllowedProvider(Provider $provider, Usr $user)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
			SELECT *
			FROM   Provider p
			WHERE  " . $user->getProviderFilter() . "
			AND ProviderID     = ?
		";
        $stmt = $connection->executeQuery($sql, [$provider->getProviderid()], [\PDO::PARAM_INT]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }

    public function getLPCount($root, $updateCache = false)
    {
        $lps = \Cache::getInstance()->get("LPStats");

        if (empty($lps) || $updateCache) {
            $connection = $this->getEntityManager()->getConnection();
            $sql = "
				SELECT Code
				FROM   Provider
				WHERE  State >= ?
			";
            $stmt = $connection->executeQuery($sql,
                [PROVIDER_ENABLED],
                [\PDO::PARAM_INT]
            );
            $lps = 0;

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (file_exists("$root/engine/{$row['Code']}")) {
                    $lps++;
                }
            }

            \Cache::getInstance()->set("LPStats", $lps, 0);
        }

        return $lps;
    }

    public function isAgregatorProvider($providerId)
    {
        $agregatorProviders = [
            '482' => 'booking',
            '161' => 'expedia',
            '284' => 'priceline',
            '344' => "travelocity",
        ];

        return array_key_exists($providerId, $agregatorProviders);
    }

    public function getProviderLikeThe($name, $providerMultiname)
    {
        $name = $this->getProviderByAlias($name, $providerMultiname);
        $row = $this->getEntityManager()->getConnection()->executeQuery("
			SELECT
				ProviderID
			FROM 
				Provider
			WHERE
				Name = ?
			",
            [$name],
            [\PDO::PARAM_STR]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $row['ProviderID'];
    }

    public function getProviderByAlias($name, array $providerMultiname)
    {
        foreach ($providerMultiname as $provider => $aliasArr) {
            foreach ($aliasArr as $alias) {
                if ($alias == $name) {
                    $name = $provider;
                }
            }
        }

        return $name;
    }

    public function getSuccessRateProvider($providerID): ?float
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "SELECT
					round(sum(case when a.ErrorCode = ? then 1 else 0 end)/count(a.AccountID)*100, 2) AS SuccessRate
				FROM
					Account a
					inner join Provider p on a.ProviderID = p.ProviderID
				WHERE
					p.State >= ?
					and p.State <> ?
					and p.CanCheck = 1
					AND p.ProviderID = ?
					and a.UpdateDate > DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $row = $connection->executeQuery($sql,
            [ACCOUNT_CHECKED, PROVIDER_ENABLED, PROVIDER_COLLECTING_ACCOUNTS, $providerID],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row !== false) {
            return $row['SuccessRate'];
        } else {
            return null;
        }
    }

    /**
     * @param string $sort
     * @param int $limit
     * @param string $filter
     * @param array $preferred_choices
     * @param bool $withoutNotSuppored
     * @param null $target
     * @return array
     * @deprecated use self::searchProviderByText
     */
    public function findProviderByText($text,
        $sort = 'ASC',
        $limit = 50,
        $filter = '',
        $preferred_choices = [],
        $withoutNotSuppored = false,
        $target = null
    ) {
        $text = "%" . $text . "%";
        $preferredSort = (sizeof($preferred_choices)) ? "if(ProviderID IN (" . implode(", ", $preferred_choices) . "),1,0) DESC," : '';
        $sql = "
            SELECT *
            FROM Provider
            WHERE ( Name LIKE ?
                    OR DisplayName LIKE ?
                    OR ProgramName LIKE ?
                )
                AND " . userProviderFilter(null, "State", /* #20892 */ "(Code = 'aa' OR State = " . PROVIDER_RETAIL . ")") . " $filter
            ORDER BY $preferredSort Corporate $sort,
                Accounts DESC,
                DisplayName
        " . (isset($limit) ? "LIMIT $limit" : "");
        $providers = $this->getEntityManager()->getConnection()->executeQuery($sql,
            [$text, $text, $text],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]
        )->fetchAll(\PDO::FETCH_ASSOC);

        // without not suppored
        if ($withoutNotSuppored) {
            foreach ($providers as $k => $v) {
                foreach ($v as $k2 => $v2) {
                    $find = ["(Not Supported)", "(*Not Supported)"];
                    $providers[$k][$k2] = trim(str_replace($find, "", $v2));
                }
            }
        }

        if (isset($target)) {
            if ($target == 'booking') {
                foreach ($providers as $k => $v) {
                    if (isset($v['ProviderID']) && $v['ProviderID'] == 7) {
                        $providers[$k]['ProgramName'] = 'Delta Air Lines';
                        $providers[$k]['Name'] = 'Delta Air Lines';
                        $providers[$k]['DisplayName'] = 'Delta Air Lines';
                    }
                }
            }
        }

        return $providers;
    }

    /**
     * Get list for booking programs autocomplete.
     *
     * @param array $whiteListCodes
     * @param array $notShowCodes
     * @return array
     */
    public function findBookingProgs($query, $whiteListCodes = [], $notShowCodes = [], $states = [])
    {
        static $topPrograms = [
            'Air France',
            'Air Canada',
            'American Airlines',
            'United Airlines',
            'Delta Air Lines',
            'US Airways',
            'Virgin Atlantic Airways',
            'Alaska Air',
            'British Airways',
        ];

        $builder = $this->_em->createQueryBuilder();
        $max = 10;

        if (empty($query)) {
            $max = 50;
        } else {
            $query = preg_replace('/\s+/', '%', $query);
        }
        $whiteListFilter = $builder->expr()->eq(1, 0);

        if (sizeof($whiteListCodes)) {
            $whiteListFilter = $builder->expr()->in('p.code', $whiteListCodes);
        }
        $notShowFilter = $builder->expr()->eq(1, 1);

        if (sizeof($notShowCodes)) {
            $notShowFilter = $builder->expr()->notIn('p.code', $notShowCodes);
        }
        $stateFilter = $builder->expr()->gt('p.state', 0);

        if (sizeof($states) > 0) {
            $stateFilter = $builder->expr()->orX(
                $builder->expr()->gt('p.state', 0),
                $builder->expr()->in('p.state', array_map(function ($v) {
                    return strval($v);
                }, $states))
            );
        }

        $result = $builder->select('p.displayname, p.providerid, p.name')
            ->from(Provider::class, 'p')
            ->where($builder->expr()->orX(
                $builder->expr()->like('p.displayname', ':searchterm'),
                $builder->expr()->like('p.programname', ':searchterm'),
                $builder->expr()->like('p.name', ':searchterm')
            ))
            ->andWhere(
                $notShowFilter
            )
            ->andWhere(
                $builder->expr()->orX(
                    $builder->expr()->andX(
                        $builder->expr()->in('p.kind', [PROVIDER_KIND_AIRLINE, PROVIDER_KIND_CREDITCARD]),
                        $stateFilter,
                        $builder->expr()->gt('p.abaccounts', 1)
                    ),
                    $builder->expr()->andX(
                        $whiteListFilter,
                        $stateFilter
                    )
                )
            )
            ->orderBy('p.accounts', 'DESC')
            ->setMaxResults($max)
            ->setParameter('searchterm', '%' . $query . '%')
            ->getQuery()->getResult();
        $progs = [];
        $topProgs = [];
        $find = ["(Not Supported)", "(*Not Supported)"];

        foreach ($result as $item) {
            $item['displayname'] = htmlspecialchars_decode(trim(str_replace($find, "", $item['displayname'])));
            $prog = [
                'value' => $item['displayname'],
                /** @Ignore */
                'label' => $item['displayname'],
                'id' => $item['providerid'],
                //				'levels' => $this->getEliteLevels($item['providerid'])
            ];

            if (in_array($item['name'], $topPrograms)) {
                $topProgs[$item['name']] = $prog;
            } else {
                $progs[] = $prog;
            }
        }

        foreach (array_reverse($topPrograms) as $program) {
            if (array_key_exists($program, $topProgs)) {
                array_unshift($progs, $topProgs[$program]);
            }
        }

        return $progs;
    }

    public function getEliteLevels($providerId)
    {
        $levels = array_map(
            function (Elitelevel $level) {
                return $level->getName();
            },
            $this->_em->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class)->findBy(['providerid' => $providerId], ["rank" => 'asc'])
        );

        return $levels;
    }

    public function searchProviderByText(
        $text,
        ?int $user = null,
        $stmt = null,
        ?int $maxResults = null,
        array $allowedStates = self::PROVIDER_SEARCH_ALLOWED_STATES,
        $providerSqlFilters = ['p.CollectingRequests'],
        ?callable $matchingCallback = null
    ) {
        $result = [];
        $connection = $this->getEntityManager()->getConnection();
        $isRegExp = function ($str) {
            return substr($str, 0, 1) == '/' && substr($str, -1) == '/';
        };
        $prepareRegExp = function ($str, $isStopword = false) {
            $str = substr_replace($str, "", -1);
            $str = substr_replace($str, "", 0, 1);

            if ($isStopword) {
                $str = "^{$str}$";
            }

            return "($str)";
        };
        $prepareKeyword = function ($str, $isStopword = false) {
            $str = preg_quote($str);
            $str = str_replace(" ", '\s+', $str);
            $str = str_replace("/", '\\/', $str);

            if ($isStopword) {
                $str = "^{$str}$";
            }

            return $str;
        };
        // Prepare stop words
        $_stopWords = [];

        foreach ($this->stopWords as $i => $v) {
            if ($isRegExp($v)) {
                $_stopWords[$i] = $prepareRegExp($v, true);
            } else {
                $_stopWords[$i] = $prepareKeyword($v, true);
            }
        }
        $stopWordsRegExp = "/\b(" . implode("|", $_stopWords) . ")\b/iums";
        $prepareProviderRegexp = function ($providerFields) use ($stopWordsRegExp, $isRegExp, $prepareRegExp, $prepareKeyword) {
            if (!isset($providerFields['KeyWords'])) {
                $providerFields['KeyWords'] = '';
            }
            $words = explode(",", $providerFields['KeyWords']);

            foreach ($words as $i => $v) {
                $words[$i] = $v = htmlspecialchars_decode(trim($v));
                $isR = $isRegExp($v);

                if ((!$isR && preg_match($stopWordsRegExp, $v)) || $v == '') {
                    unset($words[$i]);

                    continue;
                }

                // RegExp?
                if ($isR) {
                    $words[$i] = $v = $prepareRegExp($v);
                } else {
                    $words[$i] = $v = $prepareKeyword($v);
                }
            }
            $temp = implode("|", $words);

            if (sizeof($words)) {
                return "/\b(" . $temp . ")\b/iums";
            }

            return null;
        };

        $providerSqlFilters = '(' . implode(' or ', $providerSqlFilters ?: ['1 = 0']) . ')';

        if (!isset($stmt)) {
            $providersFilter = ['delta', 'mileageplus', 'rapidrewards'];

            if (isset($user)) {
                $sql = "
					SELECT
					    p.*,
					    IF(ma.AccountID IS NOT NULL, 1, 0) AS my,
					    IF(voted.ProviderID IS NOT NULL, 1, 0) AS Voted,
						pv.Votes
					FROM     
					    Provider p
						LEFT OUTER JOIN
                            (
                                SELECT AccountID, ProviderID
                                FROM Account
                                WHERE UserID = ?
                            ) ma ON ma.ProviderID = p.ProviderID
						LEFT OUTER JOIN
							(
							    SELECT ProviderID, COUNT(*) AS Votes
								FROM ProviderVote
								GROUP BY ProviderID
							) pv ON pv.ProviderID = p.ProviderID
					    LEFT JOIN
					        (
					            SELECT ProviderID
					            FROM ProviderVote
					            WHERE UserID = ?
					        ) voted ON voted.ProviderID = p.ProviderID
					WHERE
					    p.State IN (?) OR p.Code IN (?) or {$providerSqlFilters}
					ORDER BY 
					    my DESC,
						p.State DESC,
						p.Accounts DESC,
						pv.Votes DESC
				";
                $stmt = $connection->executeQuery(
                    $sql,
                    [$user, $user, $allowedStates, $providersFilter],
                    [\PDO::PARAM_INT, \PDO::PARAM_INT, Connection::PARAM_INT_ARRAY, Connection::PARAM_STR_ARRAY]
                );
            } else {
                $sql = "
					SELECT   p.*,
							 pv.Votes
					FROM     Provider p
					LEFT OUTER JOIN
									  (SELECT ProviderID, COUNT(*) AS Votes
									  FROM    ProviderVote
									  GROUP BY ProviderID
									  )
									  pv
							 ON       pv.ProviderID = p.ProviderID
					WHERE	 p.State IN (?) OR p.Code IN (?) or {$providerSqlFilters}
                            OR p.DisplayName LIKE ? COLLATE utf8mb4_general_ci
					ORDER BY CASE WHEN p.DisplayName LIKE ? COLLATE utf8mb4_general_ci THEN 0 ELSE 1 END,
					        IF(p.State = ?, p.Accounts, p.State) DESC,
							p.Accounts DESC,
							pv.Votes DESC
				";
                $stmt = $connection->executeQuery(
                    $sql,
                    [$allowedStates, $providersFilter, $text, $text, PROVIDER_RETAIL],
                    [Connection::PARAM_INT_ARRAY, Connection::PARAM_STR_ARRAY, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT]
                );
            }
        }

        $isCallable = is_callable($matchingCallback);

        while (($provider = $stmt->fetch()) && (!$maxResults || sizeof($result) < $maxResults)) {
            $pattern = $prepareProviderRegexp($provider);

            if (
                (isset($pattern) && preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE))
                || ($isCallable && $matchingCallback($provider))
            ) {
                $result[$provider['ProviderID']] = $provider;

                if (!$isCallable) {
                    $result[$provider['ProviderID']]['debug'] = [
                        'pattern' => $pattern,
                        'matches' => $matches,
                    ];
                }
            }
        }
        // close cursor as it stores statement result cache
        $stmt->free();

        return $result;
    }

    /**
     * get providers statistic for user.
     *
     * @return array
     */
    public function getProviderAccountsCount(Usr $user)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT ProviderID, count(*) as cnt
		FROM   Account
		WHERE  UserID     = ?
		       AND ProviderID IS NOT NULL
		GROUP BY ProviderID
		";
        $stmt = $conn->executeQuery($sql,
            [$user->getUserid()],
            [\PDO::PARAM_INT]
        );
        $stats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $providers = [];

        foreach ($stats as $m) {
            $providers[$m['ProviderID']] = (int) $m['cnt'];
        }

        return $providers;
    }

    /**
     * @return Provider[]
     */
    public function getSupportedProviders()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->from(Provider::class, 'provider')
            ->select(['provider.providerid', 'provider.displayname', 'provider.name', 'provider.kind', 'provider.keywords'])
            ->where('provider.state > 0')
            ->orderBy('provider.displayname', 'ASC');
        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param string $oldKeywords
     * @param string[] $newKeyWords
     * @return string|null
     */
    public function modifyKeywords($oldKeywords, array $newKeyWords)
    {
        $newKeyWords = array_map('trim', $newKeyWords);
        $newKeyWords = array_filter($newKeyWords, [StringUtils::class, 'isNotEmpty']);

        if (!$newKeyWords) {
            return $oldKeywords;
        }

        if (StringUtils::isNotEmpty($oldKeywords)) {
            $keyWordsExploded = array_map('trim', explode(',', $oldKeywords));
        } else {
            $keyWordsExploded = [];
        }

        $keyWordsNormalized = array_map(
            FunctionalUtils::composition(
                'trim',
                'strtolower'
            ),
            $keyWordsExploded
        );
        $keyWordsNormalized = array_filter($keyWordsNormalized, [StringUtils::class, 'isNotEmpty']);

        foreach ($newKeyWords as $newKeyWord) {
            if (!in_array($keyWordNormalized = strtolower($newKeyWord), $keyWordsNormalized)) {
                $keyWordsNormalized[] = $keyWordNormalized;
                $keyWordsExploded[] = $newKeyWord;
            }
        }

        return implode(', ', $keyWordsExploded);
    }

    /**
     * @return Provider|null
     */
    public function findProviderByContainsText($text)
    {
        $qb = $this->getEntityManager()
            ->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)
                   ->createQueryBuilder('p');

        $provider = $qb->add('where', $qb->expr()->orX(
            $qb->expr()->like('p.name', $qb->expr()->literal($text)),
            $qb->expr()->like('p.name', $qb->expr()->literal($text . ' %')),
            $qb->expr()->like('p.name', $qb->expr()->literal('% ' . $text)),
            $qb->expr()->like('p.name', $qb->expr()->literal('% ' . $text . ' %')),
            $qb->expr()->like('p.displayname', $qb->expr()->literal($text . ' %'))
        ))
            ->getQuery()->getResult();

        if (!sizeof($provider)) {
            return null;
        }

        return $provider[0];
    }
}
