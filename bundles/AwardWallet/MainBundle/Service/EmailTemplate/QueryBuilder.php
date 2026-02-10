<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\At201Items;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusVIP1YearUpgrade;
use AwardWallet\MainBundle\Entity\CartItem\Booking;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\CartItem\Supporters3MonthsUpgrade;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Service\EmailTemplate\AccountProperty\PropertyKind;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Circle;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\ShapeInterface;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Square;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtColumn;

class QueryBuilder
{
    protected EntityManagerInterface $em;

    protected \Doctrine\DBAL\Connection $connection;

    private ContainerInterface $container;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container
    ) {
        $this->em = $em;
        $this->connection = $em->getConnection();
        $this->connection->getConfiguration()->setSQLLogger(null);
        $this->container = $container;
    }

    /**
     * @param Options[] $options
     * @return Query
     */
    public function getQuery(array $options)
    {
        $query = new Query();

        if ($options[0]->useReadReplica) {
            $query->setConnection($this->container->get('doctrine.dbal.read_replica_connection'));
        } else {
            $query->setConnection($this->connection);
        }

        $params = $paramsTypes = [];

        $builders = [];

        foreach ($options as $n => $option) {
            $builders[] = $personalBuilder = $this->buildQuery($option, false);
            $params += $personalBuilder->getParameters();
            $paramsTypes += $personalBuilder->getParameterTypes();

            if (!$option->notBusiness) {
                $builders[] = $businessBuilder = $this->buildQuery($option, true);
                $params += $businessBuilder->getParameters();
                $paramsTypes += $businessBuilder->getParameterTypes();
            }
        }
        $this->buildSelectParts($builders);

        if (empty($query->getFields())) {
            $query->setFields(
                $this->getSelectFields($builders[0]->getQueryPart("select"))
            );
        }
        $sql = [];

        /** @var DBALQueryBuilder $builder */
        foreach ($builders as $builder) {
            $sql[] = '(' . $builder->getSQL() . ')';
        }

        $exclusionQueries = [];

        if ($options[0]->exclusionDataProviders) {
            if (isset($options[0]->messageId)) {
                $stubTemplate =
                    $this->container->get('doctrine')
                        ->getRepository(\AwardWallet\MainBundle\Entity\EmailTemplate::class)
                    ->find($options[0]->messageId);
            } else {
                // web phase, when all data providers are instantiated
                $stubTemplate = new EmailTemplate();
            }

            foreach ($options[0]->exclusionDataProviders as $exclusionDataProviderClass) {
                /** @var PreparedSQL $exclusionQuery */
                [$exclusionQuery, $params, $paramsTypes] = $this->makeExclusionDataProvider($exclusionDataProviderClass, $stubTemplate, $options, $params, $paramsTypes);
                $exclusionQueries[] = $exclusionQuery->getSql();
            }
        }

        if ($exclusionMode = $options[0]->exclusionMode) {
            $groupBy = ['UserID'];
        } else {
            $groupBy = [
                'UserID',
                'FirstName',
                'LastName',
                'Email',
                'Login',
                'RegistrationIP',
                'LastLogonIP',
                'RefCode',
                'Zip',
            ];
        }

        $metaGroupBy =
            it($builders)
            ->flatMap(function (QueryBuilderWithMetaGroupBy $builder) { return $builder->getMetaGroupBy(); })
            ->collect()
            ->unique()
            ->toArray();

        $metaSelect =
            it($builders)
            ->flatMap(function (QueryBuilderWithMetaGroupBy $builder) { return $builder->getMetaSelect(); })
            ->collect()
            ->unique()
            ->toArray();

        if ($metaGroupBy) {
            $groupBy = \array_merge($groupBy, $metaGroupBy);
        }

        $unions = implode(" UNION DISTINCT ", $sql);
        $metaSelectSql = $metaSelect ? (',' . \implode(', ', $metaSelect)) : '';
        $sql = "
            select 
               " . ($exclusionMode ?
                    "distinct UserID as UserID"
                    :
                    ("UserID,
                    FirstName,
                    LastName,
                    Email,
                    Login,
                    RegistrationIP,
                    LastLogonIP,
                    MAX(isBusiness) as isBusiness,
                    RefCode,
                    Zip" . $metaSelectSql)
        ) . "
            from (
                {$unions}
            ) `Table`
            " . (
            $exclusionQueries ?
                'where ' .
                it($exclusionQueries)
                ->map(function (string $exclusionQuery) { return "UserID not in ({$exclusionQuery})"; })
                ->joinToString("\n\nAND\n\n") :
                ''
        ) .
            ($exclusionMode ? '' : ("
                group by
                " . \implode(',', $groupBy)) . ";"
            );

        if (\count($options[0]->countCriteriaFields) > 1) {
            $countDistinctCriteria =
                "CONCAT(" .
                \implode(", '_', ", $options[0]->countCriteriaFields) .
                ")";
        } elseif (\count($options[0]->countCriteriaFields) == 1) {
            $countDistinctCriteria = $options[0]->countCriteriaFields[0];
        } else {
            $countDistinctCriteria = 'UserID';
        }

        $countSql = "
            select
                count(distinct {$countDistinctCriteria}) as Count
            from (
                {$unions}
            ) `Table`
            " . (
            $exclusionQueries ?
                'where ' .
                it($exclusionQueries)
                ->map(function (string $exclusionQuery) { return "UserID not in ({$exclusionQuery})"; })
                ->joinToString("\n\nAND\n\n") :
                ''
        ) . ($exclusionMode ? "" : ";");

        $query->addDebug("SQL", $sql);
        $query->addDebug("Count SQL", $countSql);
        $query->addDebug("SQL Params", [$params, $paramsTypes]);
        $CTEs = $builder->getWith();
        $query->setPreparedSql(new PreparedSQL(($CTEs ? \implode(",\n", $CTEs) : '') . $sql, $params, $paramsTypes));
        $query->setPreparedCountSql(new PreparedSQL(($CTEs ? \implode(",\n", $CTEs) : '') . $countSql, $params, $paramsTypes));

        return $query;
    }

    /**
     * @param array $parts
     */
    private function getSelectFields($parts)
    {
        $result = [];

        foreach ($parts as $part) {
            if (preg_match("/([a-z0-9]+)\s*\/\*\s*([^\*]+)\s*\*\//ims", $part, $matches)) {
                $result[trim($matches[1])] = trim($matches[2]);
            }
        }

        return $result;
    }

    private function buildQuery(Options $options, bool $business = false): QueryBuilderWithMetaGroupBy
    {
        $builder = new QueryBuilderWithMetaGroupBy($this->connection);
        $e = $builder->expr();
        $k = uniqid('p');
        $metaGroupBy = [];
        $metaSelect = [];
        $userTablePrefix = $business ? 'u2' : 'u';

        // # SELECT ##
        $builder->select([
            'u.UserID /* User ID (or business admin ID) */',
            'u.FirstName /* First name of user (or business admin) */',
            'u.LastName /* Last name of user (or business admin) */',
            'u.Email /* Email of user (or business admin) */',
            'u.Login /* Login of user (or business admin) */',
            'u.RegistrationIP /* Registration IP of user (or business admin) */',
            'u.LastLogonIP /* Last logon IP of user (or business admin) */',
            ($business ? "1" : "0") . ' AS isBusiness /* 0/1 - whether user is a business (1) or not (0) */',
            'u.RefCode /* Referral code */',
            'IF (
                u.ZipCodeUpdateDate is not null and
                u.ZipCodeAccountID is not null and
                u.ZipCodeProviderID is not null and
                trim(u.Zip) regexp \'^[0-9]{5}([^0-9]*[0-9]{4})?$\',
                substr(trim(u.Zip), 1, 5),
                null
            ) as Zip /* Zip-code (5-digits) */',
        ]);

        if ($options->hasUserFullname) {
            $builder->andWhere(
                $e->in(
                    "lower(trim(concat({$userTablePrefix}.FirstName, ' ', {$userTablePrefix}.LastName)))",
                    array_map(
                        function ($username) { return $this->connection->quote($username, \PDO::PARAM_STR); },
                        $options->hasUserFullname
                    )
                )
            );
        }

        if ($options->hasNotUserFullname) {
            $builder->andWhere(
                $e->notIn(
                    "lower(trim(concat({$userTablePrefix}.FirstName, ' ', {$userTablePrefix}.LastName)))",
                    array_map(
                        function ($username) { return $this->connection->quote($username, \PDO::PARAM_STR); },
                        $options->hasNotUserFullname
                    )
                )
            );
        }

        if ($options->hasTripTarget) {
            $builder
                ->join($userTablePrefix, 'UserTripTargeting', 'utt',
                    $e->andX(
                        $e->eq($userTablePrefix . '.UserID', 'utt.UserID'),
                        isset($options->hasTripTarget['DestinationAirport']) ?
                            $e->eq('utt.DestinationAirport', $options->hasTripTarget['DestinationAirport']) :
                            '1 = 1',
                        isset($options->hasTripTarget['LastOriginAirport']) ?
                            $e->eq('utt.LastOriginAirport', $options->hasTripTarget['LastOriginAirport']) :
                            '1 = 1'
                    )
                );
        }

        if ($options->airHelpCompensationEpoch) {
            $builder
                ->addSelect("JSON_ARRAYAGG(JSON_OBJECT(
                    'locale', ahc.locale,
                    'record_locator', ut.RecordLocator,
                    'url', ahc.url,
                    'flight_status', ahc.flight_status,
                    'delay_info', ahc.delay_info,
                    'ec261_compensation_gross', ahc.ec261_compensation_gross,
                    'ec261_compensation_currency', ahc.ec261_compensation_currency,

                    'departure_city', ahc.departure_city,
                    'localized_departure_city', ahc.localized_departure_city,
                    'flight_start', ahc.flight_start,
                    'flight_scheduled_departure', ahc.flight_scheduled_departure,

                    'arrival_city', ahc.arrival_city,
                    'localized_arrival_city', ahc.localized_arrival_city,
                    'flight_end', ahc.flight_end,
                    'flight_scheduled_arrival', ahc.flight_scheduled_arrival
                )) as aggregated_trips /* */")
                ->join($userTablePrefix, 'AirHelpCompensation', 'ahc',
                    $e->eq("{$userTablePrefix}.UserID", "ahc.UserID")
                )
                ->join('ahc', 'Trip', 'ut',
                    $e->eq('ut.TripID', 'ahc.partner_travel_id')
                )
                ->andWhere($e->in("ahc.epoch", ":{$k}airHelpEpoch"))
                ->andWhere($e->eq("{$userTablePrefix}.EmailPlansChanges", 1))
                ->andHaving("SUM(IF(ahc.Reminded is not null, 1, 0)) = 0")
                ->setParameter(":{$k}airHelpEpoch", $options->airHelpCompensationEpoch, Connection::PARAM_STR_ARRAY);

            if ($options->airHelpCompensationLocalesCheck) {
                $builder
                    ->andWhere($e->in('ahc.locale', ":{$k}airHelpLocale"))
                    ->setParameter(":{$k}airHelpLocale", $options->airHelpCompensationLocalesCheck, Connection::PARAM_STR_ARRAY);
            }

            $metaSelect[] = 'JSON_OBJECTAGG(isBusiness, aggregated_trips) as aggregated_trips';
        }

        if ($options->countries || $options->notCountries) {
            if (!$options->usUsers2) {
                $builder->andWhere($this->buildCountryFilter($options->countries, $options->notCountries));
            }

            $builder->leftJoin('u', 'Country', 'c', $e->eq('u.CountryID', 'c.CountryID'));
        }

        if ($options->statesCodes) {
            $this->buildStateFilter($options->statesCodes, $builder);
        }

        if ($options->hasDisneyTransactions) {
            $builder->andWhere("
                EXISTS(
                    select 1
                    from Account aDisney
                    join DisneyMerchantsAccount20250306 disneyTempTable on 
                        aDisney.AccountID = disneyTempTable.AccountID
                    where
                        aDisney.UserID = {$userTablePrefix}.UserID
                )
            ");
        }

        if ($options->usUsers2) {
            $connectedAccountsExistsExpr = "
                EXISTS(
                    select 1
                    from Account account_us_users_2
                    where
                        {$userTablePrefix}.UserID = account_us_users_2.UserID
                        and account_us_users_2.ProviderID in (103, 106, 75, 98, 87, 84, 364, 104, 123)
                        and (
                            account_us_users_2.ProviderID IN (103, 106, 75, 98, 87) OR
                            (
                                account_us_users_2.ProviderID = 84 AND
                                account_us_users_2.Login2 = 'United States'
                            ) OR
                            (
                                account_us_users_2.ProviderID = 364 AND
                                (
                                    account_us_users_2.Login2 = 'USA' OR
                                    account_us_users_2.Login2 = '' OR
                                    account_us_users_2.Login2 is null
                                )
                            ) OR
                            (
                                account_us_users_2.ProviderID = 104 AND
                                (
                                    account_us_users_2.Login2 = 'US' OR
                                    account_us_users_2.Login2 = '' OR
                                    account_us_users_2.Login2 is null
                                )
                            ) OR
                            (
                                account_us_users_2.ProviderID = 123 AND
                                account_us_users_2.Login2 = 'USA'
                            )
                        )
                )
            ";

            $userCreditCartExistsExpr = "
                EXISTS(
                    select 1
                    from UserCreditCard ucc
                    where ucc.UserID = {$userTablePrefix}.UserID
                )
            ";

            $qsTransactionExistsExpr = "
                EXISTS(
                    select 1
                    from QsTransaction qs_transactions_us_users_2
                    where
                        {$userTablePrefix}.UserID = qs_transactions_us_users_2.UserID
                        and qs_transactions_us_users_2.applications > 0
            )";

            $zipCodeExpr = "
                (
                    {$userTablePrefix}.ZipCodeUpdateDate is not null and
                    trim({$userTablePrefix}.Zip) regexp '^[0-9]{5}([^0-9]*[0-9]{4})?$'
                )
            ";

            $builder->andWhere($e->or(
                $zipCodeExpr,
                $this->buildCountryFilter($options->countries, $options->notCountries),
                $connectedAccountsExistsExpr,
                $userCreditCartExistsExpr,
                $qsTransactionExistsExpr,
            ));
        }

        if ($options->hasBusinessCard || $options->hasNoBusinessCard) {
            if ($options->hasBusinessCard && $options->hasNoBusinessCard) {
                throw new \RuntimeException('Invalid business card filter');
            }

            $aggregateExpression = "SUM(
                IF(
                    (
                        account_business_card.ProviderID is not null AND
                        (
                            account_business_provider.Corporate = 1 OR
                            (
                                account_business_card.ProviderID in (:{$k}businessProviders) AND
                                account_property_business_card.Val IS NOT NULL AND
                                (
                                    LOCATE('business', account_property_business_card.Val) > 0 OR
                                    LOCATE('The Plum Card', account_property_business_card.Val) > 0
                                )
                            )
                        )
                    ) OR
                    (
                        " . (
                $options->businessInterestRefCode ?
                    (
                        $business ?
                            "u.RefCode in (:{$k}orRefCode) or u2.RefCode in (:{$k}orRefCode)" :
                            "u.RefCode in (:{$k}orRefCode)"
                    ) :
                    "1 = 0"
            ) . "
                    ),
                    1,
                    0
                )
            )";

            $builder
                ->addSelect("{$aggregateExpression} as BusinessCardScore")
                ->leftJoin($userTablePrefix, 'Account', 'account_business_card',
                    $e->eq("{$userTablePrefix}.UserID", 'account_business_card.UserID')
                )
                ->leftJoin('account_business_card', 'Provider', 'account_business_provider',
                    $e->eq('account_business_card.ProviderID', 'account_business_provider.ProviderID')
                )
                ->leftJoin('account_business_card', 'AccountProperty', 'account_property_business_card',
                    $e->andX(
                        $e->eq('account_property_business_card.AccountID', 'account_business_card.AccountID'),
                        $e->eq('account_property_business_card.ProviderPropertyID', 3928) // ProviderProperty.Code = 'DetectedCards'
                    )
                )
                ->andHaving($options->hasBusinessCard ? "{$aggregateExpression} > 0" : "{$aggregateExpression} = 0")
                ->setParameter(":{$k}businessProviders", [84, 503, 75, 364, 123, 104, 87, 49, 103], Connection::PARAM_INT_ARRAY);

            if ($options->businessInterestRefCode) {
                $builder->setParameter(":{$k}orRefCode", $options->businessInterestRefCode, Connection::PARAM_STR_ARRAY);
            }

            $metaGroupBy[] = 'BusinessCardScore';
        }

        if ($options->nearPoints) {
            $builder
                ->leftJoin('u', 'ZipCode', 'zc', $e->eq(
                    'IF (
                        u.ZipCodeUpdateDate is not null and
                        u.ZipCodeAccountID is not null and
                        u.ZipCodeProviderID is not null and
                        trim(u.Zip) regexp \'^[0-9]{5}([^0-9]*[0-9]{4})?$\',
                        substr(trim(u.Zip), 1, 5),
                        null
                    )',
                    'zc.Zip'
                ))
                ->leftJoin('u', 'UsrLastLogonPoint', 'ullp',
                    $e->eq('u.UserID', 'ullp.UserID')
                )
                ->andWhere(
                    '('
                    . it($options->nearPoints)
                    ->map(function (ShapeInterface $shape) {
                        if ($shape instanceof Circle) {
                            $center = $shape->getCenter();
                            $radius = (int) $shape->getRadius()->getAsMeters();
                            $lat = $center->getLatitude();
                            $lng = $center->getLongitude();

                            return "(
                                ("
                                . (
                                    $shape->isUseZip() ?
                                        "MBRContains(ST_Buffer(ST_SRID(Point({$lng}, {$lat}), 4326), {$radius}), zc.`Point`)
                                        and zc.Zip is not null
                                        and (ST_DISTANCE_SPHERE(ST_SRID(Point({$lng}, {$lat}), 4326), zc.`Point`) <= {$radius})"
                                        : "1 = 0"
                                ) . ")
                                OR ("
                                . (
                                    $shape->isUseLastLogon() ?
                                        "MBRContains(ST_Buffer(ST_SRID(Point({$lng}, {$lat}), 4326), {$radius}), ullp.Point)
                                        and " . ($shape->isUseZip() ? "zc.Zip is null" : "1=1") . "
                                        and ullp.IsSet = 1
                                        and (ST_DISTANCE_SPHERE(ST_SRID(Point({$lng}, {$lat}), 4326), ullp.Point) <= {$radius})"
                                        : "1 = 0"
                                ) . ")
                            )";
                        } elseif ($shape instanceof Square) {
                            $center = $shape->getCenter();
                            $lat = $center->getLatitude();
                            $lng = $center->getLongitude();
                            $radius = (int) ($shape->getSide()->getAsMeters() / 2);

                            return "(
                                ("
                                . (
                                    $shape->isUseZip() ?
                                        "MBRContains(ST_Buffer(ST_SRID(Point({$lng}, {$lat}), 4326), {$radius}), zc.`Point`)
                                        and zc.Zip is not null"
                                        : "1 = 0"
                                ) . ")
                                OR ("
                                . (
                                    $shape->isUseLastLogon() ?
                                        "MBRContains(ST_Buffer(ST_SRID(Point({$lng}, {$lat}), 4326), {$radius}), ullp.Point)
                                        and " . ($shape->isUseZip() ? "zc.Zip is null" : "1=1") . "
                                        and ullp.IsSet = 1"
                                        : "1 = 0"
                                ) . ")
                            )";
                        } else {
                            throw new \LogicException('Unknown shape type: ' . \get_class($shape));
                        }
                    })
                    ->joinToString(' OR ')
                    . ')'
                );
        }

        if (
            $options->hasNotAccountFromProviders
            || $options->hasAccountFromProviders
            || $options->hasAccountFromProvidersByKind
            || $options->hasAccountPropertyContains
            || $options->hasNotAccountPropertyContains
            || $options->hasNotAccountHistoryContains
            || $options->hasNotSubAccountContains
            || $options->hasFicoScore
            || isset($options->minEliteLevel)
            || isset($options->maxEliteLevel)
            || $options->hasAccountPropertyExpr
            || $options->hasBalanceExpr
        ) {
            $builder
                ->leftJoin($userTablePrefix, 'Account', 'a',
                    $e->eq("{$userTablePrefix}.UserID", 'a.UserID')
                );

            if ($options->hasBalanceExpr) {
                $selectPositive = [];

                foreach ($options->hasBalanceExpr as $providerId => $exprCallable) {
                    $providerSql = ('*' === $providerId) ?
                        '1 = 1' :
                        "a.ProviderID = {$providerId}";
                    $selectPositive[] = "({$providerSql} AND (" . $exprCallable('a.Balance') . "))";
                }

                $aggregateExpression = "SUM(
                   IF((a.State > 0) AND " . implode(" AND ", $selectPositive) . ", 1 /** some */, 0)
                )";

                $builder
                    ->addSelect("{$aggregateExpression} AS BalanceExprPositiveScore")
                    ->andHaving("{$aggregateExpression} > 0");

                $metaGroupBy[] = 'BalanceExprPositiveScore';
            }

            if (
                $options->hasAccountPropertyContains
                || $options->hasNotAccountPropertyContains
                || $options->hasAccountPropertyExpr
            ) {
                $hasWildcard = false;

                if ($options->hasAccountPropertyExpr) {
                    $selectPositive = [];

                    foreach ($options->hasAccountPropertyExpr as $providerId => $exprs) {
                        if ('*' === $providerId) {
                            $hasWildcard = true;
                            $providerSql = '1 = 1';
                        } else {
                            $providerSql = "a.ProviderID = {$providerId}";
                        }

                        $iterable = $exprs instanceof \SplObjectStorage ?
                            it($exprs)
                            ->map(fn (PropertyKind $kind) => [$kind, $exprs[$kind]])
                            ->fromPairs() :
                            $exprs;

                        foreach ($iterable as $name => $callable) {
                            $prefix = $name instanceof PropertyKind ?
                                ("pp.Kind = " . $name->getKind()) :
                                ("pp.Code = '" . \addslashes($name) . "'");
                            $selectPositive[] = "({$providerSql} AND {$prefix} AND (" . $callable('ap.Val') . "))";
                        }
                    }

                    $aggregateExpression = "SUM(
                       IF((a.State > 0) AND " . implode(" AND ", $selectPositive) . ", 1 /** some */, 0)
                    )";

                    $builder
                        ->leftJoin('a', 'AccountProperty', 'ap',
                            $e->andX(
                                $hasWildcard ?
                                    '1 = 1' :
                                    $e->in('a.ProviderID', array_keys($options->hasAccountPropertyExpr)),
                                $e->eq('ap.AccountID', 'a.AccountID')
                            )
                        )
                        ->leftJoin('ap', 'ProviderProperty', 'pp',
                            $e->eq('pp.ProviderPropertyID', 'ap.ProviderPropertyID'),
                        );

                    $builder
                        ->addSelect("{$aggregateExpression} AS PropertyExprPositiveScore")
                        ->andHaving("{$aggregateExpression} > 0");

                    $metaGroupBy[] = 'PropertyExprPositiveScore';
                }

                if ($options->hasAccountPropertyContains) {
                    // TODO: implement like hasNotAccountHistoryDescriptionContains case
                    $selectPositive = [];

                    foreach ($options->hasAccountPropertyContains as $name => $values) {
                        $selectPositive[] = "(pp.Code = '" . addslashes($name) . "' AND (" . implode(" OR ", array_map(function ($v) {
                            return "LOCATE('" . addslashes($v) . "', ap.Val) > 0";
                        }, $values)) . "))";
                    }

                    $aggregateExpression = "SUM(
                       IF((a.State > 0) AND " . implode(" AND ", $selectPositive) . ", 1, 0)
                    )";

                    $builder
                        ->addSelect("{$aggregateExpression} AS PropertyPositiveScore")
                        ->andHaving("{$aggregateExpression} > 0");

                    $metaGroupBy[] = 'PropertyPositiveScore';
                }

                if ($options->hasNotAccountPropertyContains) {
                    $selectNegative = [];

                    foreach ($options->hasNotAccountPropertyContains as $providerId => $providerData) {
                        $providerSelectNegative = [];

                        if ('*' === $providerId) {
                            $hasWildcard = true;
                            $providerSql = '1 = 1';
                        } else {
                            $providerSql = "a.ProviderID = {$providerId}";
                        }

                        foreach ($providerData as $name => $values) {
                            $providerSelectNegative[] = "({$providerSql} AND pp.Code = '" . addslashes($name) . "' AND (" . implode(" OR ", array_map(function ($v) {
                                return "LOCATE('" . addslashes($v) . "', ap.Val) > 0";
                            }, $values)) . "))";
                        }

                        $selectNegative[] = implode(" OR ", $providerSelectNegative);
                    }

                    $builder
                        ->leftJoin('a', 'AccountProperty', 'ap',
                            $e->andX(
                                $hasWildcard ?
                                    '1 = 1' :
                                    $e->in('a.ProviderID', array_merge(
                                        array_keys($options->hasNotAccountPropertyContains),
                                        array_keys($options->hasAccountPropertyContains)
                                    )),
                                $e->eq('ap.AccountID', 'a.AccountID')
                            )
                        )
                        ->leftJoin('ap', 'ProviderProperty', 'pp', $e->andX(
                            $e->eq('pp.ProviderPropertyID', 'ap.ProviderPropertyID'),
                            $e->in('pp.Code', ":{$k}accountProperties")
                        ))
                        ->setParameter(
                            ":{$k}accountProperties",
                            array_values(array_unique(array_merge(
                                $options->hasAccountPropertyContains ?
                                    array_keys(array_merge(...array_values($options->hasAccountPropertyContains))) :
                                    [],
                                $options->hasNotAccountPropertyContains ?
                                    array_keys(array_merge(...array_values($options->hasNotAccountPropertyContains))) :
                                    []
                            ))),
                            Connection::PARAM_STR_ARRAY
                        );

                    $aggregateExpression = "SUM(
                       IF((a.State > 0) AND " . implode(" OR ", $selectNegative) . ", 1, 0)
                    )";

                    $builder
                        ->addSelect("{$aggregateExpression} AS PropertyNegativeScore")
                        ->andHaving("{$aggregateExpression} = 0");

                    $metaGroupBy[] = 'PropertyNegativeScore';
                }
            }

            if ($options->hasNotAccountHistoryContains) {
                $selectNegative = [];
                $hasWildcard = false;
                $minDate = ' 1 = 1 ';

                foreach ($options->hasNotAccountHistoryContains as $providerId => $providerData) {
                    if ('*' === $providerId) {
                        $hasWildcard = true;
                        $providerSql = '1 = 1';
                    } else {
                        $providerSql = "a.ProviderID = {$providerId}";
                    }

                    $selectNegative[] = "({$providerSql} AND (" . implode(
                        ' OR ',
                        array_map(function ($desc) { return "LOCATE('" . addslashes($desc) . "', ah.Description) > 0"; }, $providerData['Description'])
                    ) . '))';

                    if (isset($providerData['minDate'])) {
                        $minDate = $e->andX(
                            $minDate,
                            $e->gte('ah.PostingDate', "'" . $providerData['minDate']->format('Y-m-d H:i:s') . "'")
                        );
                    }
                }

                $aggregateExpression = "SUM(
                    IF((a.State > 0) AND (ah.Description IS NOT NULL) AND (" . implode(' OR ', $selectNegative) . "), 1, 0)
                )";

                $builder
                    ->addSelect("{$aggregateExpression} AS HistoryNegativeScore")
                    ->leftJoin('a', 'AccountHistory', 'ah',
                        $e->andX(
                            $hasWildcard ?
                                '1 = 1' :
                                 $e->in('a.ProviderID', array_keys($options->hasNotAccountHistoryContains)),
                            $e->eq('ah.AccountID', 'a.AccountID'),
                            $minDate
                        )
                    )
                    ->andHaving("{$aggregateExpression} = 0");

                $metaGroupBy[] = 'HistoryNegativeScore';
            }

            if ($options->hasNotSubAccountContains) {
                $selectNegative = [];
                $hasWildcard = false;

                foreach ($options->hasNotSubAccountContains as $providerId => $providerData) {
                    if ('*' === $providerId) {
                        $hasWildcard = true;
                        $providerSql = '1 = 1';
                    } else {
                        $providerSql = "a.ProviderID = {$providerId}";
                    }

                    $selectNegative[] = "({$providerSql} AND (" . implode(
                        ' OR ',
                        array_map(function ($desc) { return "LOCATE('" . addslashes($desc) . "', sa.DisplayName) > 0"; }, $providerData['DisplayName'])
                    ) . '))';
                }

                $aggregateExpression = "SUM(
                    IF((a.State > 0) AND (sa.DisplayName IS NOT NULL) AND (" . implode(' OR ', $selectNegative) . "), 1, 0)
                )";

                $builder
                    ->addSelect("{$aggregateExpression} AS SubAccountNegativeScore")
                    ->leftJoin('a', 'SubAccount', 'sa',
                        $e->andX(
                            $e->eq('sa.AccountID', 'a.AccountID'),
                            $hasWildcard ?
                                '1 = 1' :
                                 $e->in('a.ProviderID', array_keys($options->hasNotSubAccountContains))
                        )
                    )
                    ->andHaving("{$aggregateExpression} = 0");

                $metaGroupBy[] = 'SubAccountNegativeScore';
            }

            if ($options->hasFicoScore) {
                $builder
                    ->leftJoin('a', 'SubAccount', 'saFico',
                        $e->andX(
                            $e->in('a.ProviderID', [84, 75, 123, 364, 98]),
                            $e->eq('saFico.AccountID', 'a.AccountID'),
                            "substring(saFico.Code from -4) = 'FICO'"
                        )
                    )
                    ->andWhere('(a.State > 0)')
                    ->andHaving('(
                        sum(
                            if(saFico.SubAccountID is not null and saFico.Balance >= 700, 1, 0)
                        ) = 0 AND
                        sum(
                            if(a.ProviderID is not null and a.ProviderID in (84, 75, 123, 364, 98), 1, 0)
                        ) > 0)');
            }

            if (
                isset($options->minEliteLevel)
                || isset($options->maxEliteLevel)
            ) {
                $builder
                    ->join('a', 'EliteLevel', 'elt',
                        $e->andX(
                            $e->eq('elt.ProviderID', 'a.ProviderID'),
                            isset($options->minEliteLevel) ?
                                $e->gte('elt.Rank', (int) $options->minEliteLevel) :
                                $e->lt('elt.Rank', (int) $options->maxEliteLevel)
                        )
                    )
                    ->join('a', 'AccountProperty', 'ap',
                        $e->eq('a.AccountID', 'ap.AccountID')
                    )
                    ->join('ap', 'ProviderProperty', 'pp',
                        $e->andX(
                            $e->eq('ap.ProviderPropertyID', 'pp.ProviderPropertyID'),
                            $e->eq('pp.Kind', PROPERTY_KIND_STATUS)
                        )
                    )
                    ->leftJoin('elt', 'TextEliteLevel', 'tel',
                        $e->eq('elt.EliteLevelID', 'tel.EliteLevelID')
                    )
                    ->andWhere('(a.State > 0)')
                    ->andWhere(
                        $e->eq('ap.Val', 'IFNULL(tel.ValueText, elt.Name)')
                    );
            }
        }

        if ($options->hasAccountFromProviders) {
            $aggregateExpression = "SUM(
                IF(
                    a.ProviderID IS NOT NULL AND
                    a.ProviderID IN (:{$k}hasAccountFromProviders) AND
                    a.State > 0,
                    1, 0
                )
            )";

            $builder
                ->addSelect("{$aggregateExpression} as hasAccountFromProvidersScore")
                ->andHaving("{$aggregateExpression} > 0")
                ->setParameter(":{$k}hasAccountFromProviders", $options->hasAccountFromProviders, Connection::PARAM_INT_ARRAY);

            $metaGroupBy[] = 'hasAccountFromProvidersScore';
        }

        if ($options->hasAccountFromProvidersByKind) {
            $aggregateExpression = "SUM(
                IF(
                    a.ProviderID IS NOT NULL AND
                    p.Kind IN (:{$k}hasAccountFromProvidersByKind) AND
                    a.State > 0,
                    1, 0
                )
            )";

            $builder
                ->addSelect("{$aggregateExpression} as hasAccountFromProvidersByKindScore")
                ->leftJoin('a', 'Provider', 'p',
                    $e->eq('a.ProviderID', 'p.ProviderID')
                )
                ->andHaving("{$aggregateExpression} > 0")
                ->setParameter(":{$k}hasAccountFromProvidersByKind", $options->hasAccountFromProvidersByKind, Connection::PARAM_INT_ARRAY);

            $metaGroupBy[] = 'hasAccountFromProvidersByKindScore';
        }

        if ($options->hasNotAccountFromProviders) {
            $aggregateExpression = "SUM(
                IF(
                    a.ProviderID IS NOT NULL AND
                    a.ProviderID IN (:{$k}hasNotAccountFromProviders) AND
                    a.State > 0,
                    1, 0
                )
            ) ";
            $builder
                ->addSelect("{$aggregateExpression} as hasNotAccountFromProvidersScore")
                ->andHaving("{$aggregateExpression} = 0")
                ->setParameter(":{$k}hasNotAccountFromProviders", $options->hasNotAccountFromProviders, Connection::PARAM_INT_ARRAY);

            $metaGroupBy[] = 'hasNotAccountFromProvidersScore';
        }

        if ($options->excludedCreditCards) {
            $aggregateExpression = "
                SUM(
                    IF(
                        uccExclude.UserCreditCardID IS NOT NULL,
                        1,
                        0
                    )
                )";
            $builder
                    ->addSelect("{$aggregateExpression} as excludedCreditCardsScore")
                    ->leftJoin('u', 'UserCreditCard', 'uccExclude',
                        $e->and(
                            $e->eq("uccExclude.UserID", "u.UserID"),
                            $e->in("uccExclude.CreditCardID", ":{$k}uccExcludeList"),
                            $e->eq("uccExclude.IsClosed", 0)
                        )
                    )
                    ->andHaving("{$aggregateExpression} = 0")
                    ->setParameter(":{$k}uccExcludeList", $options->excludedCreditCards, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            $metaGroupBy[] = 'excludedCreditCardsScore';
        }

        if (false === $options->accountFromProvidersLinkedToFamilyMember) {
            $builder->andWhere('a.UserAgentID IS NULL');
        }

        if (isset($options->awPlusUpgraded)) {
            $builder
                ->andWhere($e->eq("u.AccountLevel", ":{$k}upgraded"))
                ->setParameter(":{$k}upgraded", $options->awPlusUpgraded ? ACCOUNT_LEVEL_AWPLUS : ACCOUNT_LEVEL_FREE);
        }

        if (null !== $options->hasSubscription) {
            $builder->andWhere($options->hasSubscription ?
                $e->isNotNull("u.Subscription") :
                $e->isNull("u.Subscription")
            );

            if (null !== $options->hasSubscriptionType) {
                $this->addHasSubscriptionTypeFilter($options, $builder, $k);
            }
        }

        if (null !== $options->vipHasEverSubscriptionType) {
            $this->addVipHasEverSubscriptionTypeFilter($options, $builder, $k);
        }

        if (null !== $options->hasEverSubscription) {
            $this->addHasEverSubscription($options, $builder, $k);
        }

        if (null !== $options->hasVIPUpgrade) {
            $builder->andWhere("
            " . ($options->hasVIPUpgrade ? "" : "NOT") . " EXISTS(
                    select 1
                    from Cart cFindVip
                    join CartItem ciFindVip on cFindVip.CartID = ciFindVip.CartID
                    where
                        cFindVip.UserID = u.UserID
                        AND ciFindVip.TypeID = :{$k}vip_upgrade_item_type
                )
            ");
            $builder->setParameter(":{$k}vip_upgrade_item_type", AwPlusVIP1YearUpgrade::TYPE);
        }

        if (null !== $options->hasSupporter3MUpgrade) {
            $builder->andWhere("
            " . ($options->hasSupporter3MUpgrade ? "" : "NOT") . " EXISTS(
                    select 1
                    from Cart cFind3MUpgrade
                    join CartItem ciFind3MUpgrade on cFind3MUpgrade.CartID = ciFind3MUpgrade.CartID
                    where
                        cFind3MUpgrade.UserID = u.UserID
                        AND ciFind3MUpgrade.TypeID = :{$k}sup_3m_upgrade_item_type
                )
            ");
            $builder->setParameter(":{$k}sup_3m_upgrade_item_type", Supporters3MonthsUpgrade::TYPE);
        }

        if ($options->hasPrepaidAwPlus) {
            $builder->andWhere("
                EXISTS(
                    select 1
                    from Cart cHasNewPrepaid
                    join CartItem ciHasNewPrepaid on cHasNewPrepaid.CartID = ciHasNewPrepaid.CartID
                    where
                        cHasNewPrepaid.UserID = u.UserID
                        AND cHasNewPrepaid.PayDate > '2024-11-01'
                        AND ciHasNewPrepaid.TypeID = " . AwPlusPrepaid::TYPE . "
                )
            ");
        }

        if (isset($options->builderTransformator)) {
            ($options->builderTransformator)($builder, $business, $k);
        }

        // # FROM ##
        $builder->from('Usr', $userTablePrefix);

        if ($business) {
            $builder->join("u2", "UserAgent", "ua", $e->eq("u2.UserID", "ua.ClientID"));
            $builder->join("ua", "Usr", "u", $e->eq("ua.AgentID", "u.UserID"));
        }
        $builder->leftJoin("u", "EmailLog", "el", $e->andX(
            $e->eq("u.UserID", "el.UserID"),
            $e->eq("el.MessageKind", ":{$k}messageKind")
        ));
        $builder->setParameter(":{$k}messageKind", $options->messageId, \PDO::PARAM_INT);

        if (!$options->ignoreGroupDoNotCommunicate) {
            $builder->leftJoin("u", "GroupUserLink", "gul", $e->andX(
                $e->eq("gul.UserID", "u.UserID"),
                $e->eq("gul.SiteGroupID", ":{$k}groupId")
            ));
            $builder->setParameter(":{$k}groupId", 50, \PDO::PARAM_INT);
        }

        if (isset($options->businessDetected)) {
            $businessDetectGroupId = $this->loadSiteGroupId(Sitegroup::GROUP_BUSINESS_DETECTED);

            if (!isset($businessDetectGroupId)) {
                throw new \RuntimeException('Business detect group not found!');
            }

            $builder
                ->leftJoin($userTablePrefix, 'GroupUserLink', 'gulBusinessDetect',
                    $e->andX(
                        $e->eq("gulBusinessDetect.SiteGroupID", ":{$k}BusinessDetectGroupId"),
                        $e->eq($userTablePrefix . '.UserID', 'gulBusinessDetect.UserID')
                    )
                )
                ->setParameter(":{$k}BusinessDetectGroupId", $businessDetectGroupId)
                ->andWhere($options->businessDetected ?
                    $e->isNotNull("gulBusinessDetect.SiteGroupID") :
                    $e->isNull("gulBusinessDetect.SiteGroupID")
                );
        }

        $builder->leftJoin("u", "DoNotSend", "dns", $e->eq("dns.Email", "u.Email"));

        if ($options->hasNotEmails) {
            $this->checkExcludedEmailsExist($options->hasNotEmails);
            $builder
                ->leftJoin('u', 'EmailLog', 'elOut',
                    $e->andX(
                        $e->eq("u.UserID", "elOut.UserID"),
                        $e->in('elOut.MessageKind', $options->hasNotEmails)
                    )
                )
                ->andWhere($e->isNull('elOut.EmailLogID'));
        }

        if (isset($options->paid) || isset($options->awPlusActiveSubscription)) {
            $builder
                ->leftJoin('u', 'Cart', 'c', $e->eq('c.UserID', 'u.UserID'))
                ->leftJoin('c', 'CartItem', 'ci', $e->eq('ci.CartID', 'c.CartID'));

            if (isset($options->paid)) {
                if ($options->paid) {
                    $builder
                        ->andWhere($e->isNotNull('c.PayDate'))
                        ->andWhere(
                            $e->orX(
                                $e->neq('ci.TypeID', Booking::TYPE),
                                $e->isNull('ci.TypeID')
                            )
                        )
                        ->andWhere($e->gt('IFNULL(ci.Price * ci.Cnt * ((100 - ci.Discount) / 100), 0)', 0))
                        ->andHaving($e->gt('SUM(IFNULL(ci.Price * ci.Cnt * (100-ci.Discount) / 100, 0))', 0));
                } else {
                    $builder->andHaving($e->eq('SUM(IFNULL(ci.Price * ci.Cnt * (100-ci.Discount) / 100, 0))', 0));
                }
            }
            // a query that will return users that have carts with Cart.PayDate <= DATE_SUB(NOW(), INTERVAL 1 YEAR)

            if (isset($options->awPlusActiveSubscription)) {
                $subscriptionCondition =
                    $e->andX(
                        $e->isNotNull('c.PayDate'),
                        $e->orX(
                            // mobile
                            $e->andX(
                                $e->in('c.PaymentType', [Cart::PAYMENTTYPE_APPSTORE, Cart::PAYMENTTYPE_ANDROIDMARKET]),
                                $e->eq('ci.TypeID', AwPlusSubscription::TYPE),
                                $e->orX(
                                    $e->andX(
                                        $e->gt(
                                            'c.PayDate',
                                            "(NOW() - INTERVAL " . self::filterAwPlusDuration(AwPlusSubscription::DURATION) . ")"
                                        )
                                    ),
                                    $e->andX(
                                        $e->eq('u.AccountLevel', ACCOUNT_LEVEL_AWPLUS),
                                        $e->andX(
                                            $e->gt(
                                                'c.PayDate',
                                                "(NOW() - INTERVAL " . self::filterAwPlusDuration(AwPlusSubscription::DURATION) . " - INTERVAL 7 DAY)"
                                            )
                                        )
                                    )
                                )
                            ),

                            // desktop
                            $e->andX(
                                $e->notIn('c.PaymentType', [Cart::PAYMENTTYPE_APPSTORE, Cart::PAYMENTTYPE_ANDROIDMARKET]),
                                $e->in('ci.TypeID', [AwPlusSubscription::TYPE, AwPlusRecurring::TYPE]),
                                $e->isNotNull('u.PayPalRecurringProfileID')
                            )
                        )
                    );
                $subscriptionAggregation = "SUM({$subscriptionCondition})";

                if ($options->awPlusActiveSubscription) {
                    $builder->andHaving($e->gt($subscriptionAggregation, 0));
                } else {
                    $builder->andHaving($e->eq($subscriptionAggregation, 0));
                }
            }
        }

        if ($options->offerIdUsers) {
            $builder
                ->join('u', 'OfferUser', 'ou', $e->eq('u.UserID', 'ou.UserID'))
                ->andWhere($e->andX(
                    $e->eq('ou.OfferID', ':offerId'),
                    $e->isNull('ou.Agreed')
                ))
                ->setParameter(':offerId', $options->offerIdUsers, \PDO::PARAM_INT);
        }

        // # WHERE ##
        if (!$options->ignoreGroupDoNotCommunicate) {
            $builder->andWhere($e->isNull("gul.GroupUserLinkID"));
        }

        if ($business) {
            $builder->andWhere($e->andX(
                $e->eq("u2.AccountLevel", ":{$k}accountLevel"),
                $e->eq("ua.AccessLevel", ":{$k}accessLevel")
            ));
            $builder->setParameter(":{$k}accountLevel", ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT);
            $builder->setParameter(":{$k}accessLevel", ACCESS_ADMIN, \PDO::PARAM_INT);
        } else {
            $builder->andWhere($e->neq("u.AccountLevel", ":{$k}accountLevel"));
            $builder->setParameter(":{$k}accountLevel", ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT);
        }

        if (!$options->ignoreEmailProductUpdates) {
            $builder->andWhere(
                $e->andX(
                    $e->isNull("dns.Email"),
                    $e->neq("u.EmailVerified", ":{$k}ndr")
                )
            );
            $builder->setParameter(":{$k}ndr", EMAIL_NDR, \PDO::PARAM_INT);
        }

        if ($options->onlyDoNotSendNDR) {
            $builder->andWhere(
                $e->or(
                    $e->isNotNull("dns.Email"),
                    $e->eq("u.EmailVerified", ":{$k}ndr")
                )
            );
            $builder->setParameter(":{$k}ndr", EMAIL_NDR, \PDO::PARAM_INT);
        }

        if (sizeof($options->userId) > 0) {
            $builder->andWhere(
                $business ?
                    $e->orX(
                        $e->in("u.UserID", ":{$k}users"),
                        $e->in("u2.UserID", ":{$k}users")
                    ) :
                    $e->in("u.UserID", ":{$k}users")
            );
            $builder->setParameter(":{$k}users", $options->userId, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        if ($options->userIdSplit) {
            [$parts, $partN] = $options->userIdSplit;

            $builder
                ->andWhere(
                    $e->eq("u.UserID  % :{$k}userIdSplitPartsCount", ":{$k}userIdSplitPartN")
                )
                ->setParameter(":{$k}userIdSplitPartsCount", $parts, \PDO::PARAM_INT)
                ->setParameter(":{$k}userIdSplitPartN", $partN, \PDO::PARAM_INT);
        }

        if ($options->notUserId) {
            $builder->andWhere(
                $business ?
                    $e->andX(
                        $e->notIn("u.UserID", ":{$k}usersOut"),
                        $e->notIn("u2.UserID", ":{$k}usersOut")
                    ) :
                    $e->notIn("u.UserID", ":{$k}usersOut")
            );
            $builder->setParameter(":{$k}usersOut", $options->notUserId, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        if ($options->refCode) {
            $builder->andWhere(
                $business ?
                    $e->orX(
                        $e->in("u.RefCode", ":{$k}usersRefCode"),
                        $e->in("u2.RefCode", ":{$k}usersRefCode")
                    ) :
                    $e->in("u.RefCode", ":{$k}usersRefCode")
            );
            $builder->setParameter(":{$k}usersRefCode", $options->refCode, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        if ($options->notRefCode) {
            $builder->andWhere(
                $business ?
                    $e->andX(
                        $e->notIn("u.RefCode", ":{$k}usersRefCodeOut"),
                        $e->notIn("u2.RefCode", ":{$k}usersRefCodeOut")
                    ) :
                    $e->notIn("u.RefCode", ":{$k}usersRefCodeOut")
            );
            $builder->setParameter(":{$k}usersRefCodeOut", $options->notRefCode, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        if (!$options->ignoreEmailLog) {
            $builder->andWhere($e->isNull("el.EmailLogID"));
        }

        if ($options->emailType == EmailTemplate::TYPE_OFFER && !$options->ignoreEmailOffers) {
            $builder->andWhere($e->eq("{$userTablePrefix}.EmailOffers", 1));
        }

        if ($options->emailType == EmailTemplate::TYPE_PRODUCT_UPDATE && !$options->ignoreEmailProductUpdates) {
            $builder->andWhere($e->eq("{$userTablePrefix}.EmailProductUpdates", 1));
        }

        if ($business) {
            $groupBy = [
                'UserID',
                'FirstName',
                'LastName',
                'Email',
                'Login',
                'RegistrationIP',
                'LastLogonIP',
                'RefCode',
                'Zip',
            ];
        } else {
            $groupBy = [
                'UserID',
                'FirstName',
                'LastName',
                'Email',
                'Login',
                'RegistrationIP',
                'LastLogonIP',
                'RefCode',
                'ZipCodeUpdateDate',
                'ZipCodeAccountID',
                'ZipCodeProviderID',
                'Zip',
            ];
        }

        $builder->groupBy($groupBy);
        $builder->setMetaGroupBy($metaGroupBy);
        $builder->setMetaSelect($metaSelect);

        if ($options->limit) {
            $builder->setMaxResults($options->limit);
        }

        return $builder;
    }

    private function buildStateFilter(array $states, DBALQueryBuilder $builder)
    {
        $countryQueryBuilder = $this->em->getConnection()->createQueryBuilder();
        $e = $countryQueryBuilder->expr();
        $countryQueryBuilder
            ->select('Code', 'StateID')
            ->from('State')
            ->where($e->eq('CountryID', Country::UNITED_STATES));
        $availableStatesMap =
            it($countryQueryBuilder->execute()->fetchAllKeyValue())
            ->mapKeys($normalizeState = fn (string $code) => \trim(\mb_strtolower($code)))
            ->toArrayWithKeys();
        $statesMap =
            it($states)
            ->map($normalizeState)
            ->flip()
            ->toArrayWithKeys();

        $stateWhereOr = [];

        if (\array_key_exists('unknown', $statesMap)) {
            unset($statesMap['unknown']);
            $stateWhereOr[] = $e->isNull('u.StateID');
        }

        $validatedStatesMap = \array_intersect_key($statesMap, $availableStatesMap);

        if (\count($validatedStatesMap) !== \count($statesMap)) {
            $missingStatesList = \array_keys(\array_diff_key($statesMap, $validatedStatesMap));

            throw new \InvalidArgumentException('Unknown states: ' . \json_encode($missingStatesList));
        }

        $stateWhereOr[] = $e->in(
            'u.StateID',
            it($statesMap)
            ->flip()
            ->map(fn (string $stateCode) => $availableStatesMap[$stateCode])
            ->toArray()
        );

        $builder->andWhere($e->or(...$stateWhereOr));
    }

    private function buildCountryFilter(array $countries, array $notCountries): CompositeExpression
    {
        $countryQueryBuilder = $this->em->getConnection()->createQueryBuilder();
        $e = $countryQueryBuilder->expr();
        $countryQueryBuilder
            ->select('CountryID', 'Code')
            ->from('Country')
            ->where($e->isNotNull('Code'));

        $availableCountries = $countryQueryBuilder->execute()->fetchAll();
        $availableCountries = array_combine(
            it($availableCountries)->column('Code')->mapToLower()->toArray(),
            it($availableCountries)->column('CountryID')->toArray()
        );

        $where = [];

        if ($countries) {
            $countryWhere = [];
            $countries = it($countries)->mapToLower()->mapByTrim()->toArray();

            if (false !== ($unknownKey = array_search('unknown', $countries))) {
                unset($countries[$unknownKey]);
                $countryWhere[] = $e->isNull('c.CountryID');
            }

            $missingCountries = array_diff(
                $countries,
                it($availableCountries)->keys()->mapToLower()->toArray()
            );

            if ($missingCountries) {
                throw new \InvalidArgumentException('Unknown countries: ' . json_encode($missingCountries));
            }

            foreach ($countries as $countryCode) {
                $countryWhere[] = $e->eq('c.CountryID', $availableCountries[$countryCode]);
            }
            $where[] = $e->orX(...$countryWhere);
        }

        if ($notCountries) {
            $countryWhere = [];
            $notCountries = it($notCountries)->mapToLower()->mapByTrim()->toArray();

            if (false !== ($unknownKey = array_search('unknown', $notCountries))) {
                unset($notCountries[$unknownKey]);
                $countryWhere[] = $e->isNotNull('c.CountryID');
            }

            $missingCountries = array_diff(
                $notCountries,
                it($availableCountries)->keys()->mapToLower()->toArray()
            );

            if ($missingCountries) {
                throw new \InvalidArgumentException('Unknown countries: ' . json_encode($missingCountries));
            }

            foreach ($notCountries as $countryCode) {
                $countryWhere[] = $e->neq('c.CountryID', $availableCountries[$countryCode]);
            }
            $where[] = $e->and(...$countryWhere);
        }

        return $e->or(...$where);
    }

    /**
     * @param DBALQueryBuilder[] $builders
     */
    private function buildSelectParts(array $builders)
    {
        // compile
        $prepared = [];
        $allSelect = [];

        foreach ($builders as $builder) {
            $p = [
                'builder' => $builder,
                'select' => [],
            ];

            foreach ($builder->getQueryPart('select') as $part) {
                if (preg_match("/([a-z0-9]+)\s*+(:?\/\*\s*(?:[^\*]+)\s*\*\/\s*)?$/ims", $part, $matches)) {
                    $matches[1] = trim($matches[1]);
                    $p['select'][] = $matches[1];

                    if (false === array_search($matches[1], $allSelect)) {
                        $allSelect[] = $matches[1];
                    }
                }
            }
            $prepared[] = $p;
        }

        foreach ($prepared as $b) {
            /** @var $builder DBALQueryBuilder */
            $builder = $b['builder'];

            foreach ($allSelect as $s) {
                if (false === array_search($s, $b['select'])) {
                    $builder->addSelect("NULL AS {$s}");
                }
            }
        }
    }

    private function makeExclusionDataProvider(string $exclusionDataProviderClass, EmailTemplate $emailTemplate, array $options, array $params, array $paramsTypes): array
    {
        /** @var AbstractDataProvider $exclusionDataProvider */
        $exclusionDataProvider = new $exclusionDataProviderClass($this->container, $emailTemplate);
        $clonedOptions =
            it($options)
                ->map(function (Options $options) {
                    $new = new Options();
                    $new->exclusionMode = true;
                    $new->emailType = $options->emailType;
                    $new->messageId = $options->messageId;
                    $new->userId = $options->userId;
                    $new->notUserId = $options->notUserId;

                    return $new;
                })
                ->toArray();

        $exclusionDataProvider->setQueryOptions($clonedOptions);
        $exclusionQuery = $exclusionDataProvider->getQuery()->getPreparedSql();
        $params += $exclusionQuery->getParams();
        $paramsTypes += $exclusionQuery->getTypes();

        return [$exclusionQuery, $params, $paramsTypes];
    }

    private function checkExcludedEmailsExist(array $emails): void
    {
        $conn = $this->em->getConnection();
        $emailsInDbMap =
            stmtColumn(
                $conn->executeQuery('
                    select EmailTemplateID
                    from EmailTemplate
                    where EmailTemplateID in (?)',
                    [$emails],
                    [Connection::PARAM_INT_ARRAY]
                )
            )
            ->flip()
            ->toArrayWithKeys();

        $diff = array_diff_key(\array_flip($emails), $emailsInDbMap);

        if ($diff) {
            throw new \LogicException('Exclusion emails are missing: ', \array_keys($diff));
        }
    }

    private function addHasSubscriptionTypeFilter(Options $options, QueryBuilderWithMetaGroupBy $builder, string $k): void
    {
        $e = $builder->expr();
        $lastCartInnerSQL = "
            select cLastCart.CartID
            from Cart cLastCart
            where
                cLastCart.UserID = u.UserID
                AND cLastCart.PayDate is not null
                AND cLastCart.PaymentType is not null
                AND EXISTS(
                    select 1
                    from Cart cLastCartInner
                    join CartItem ciLastCartInner on cLastCartInner.CartID = ciLastCartInner.CartID
                    where
                        cLastCartInner.CartID = cLastCart.CartID
                        AND IF(
                            cLastCartInner.PaymentType in (:{$k}mobile_cart_payment_types),

                            ciLastCartInner.TypeID in (:{$k}mobile_item_types),

                            cLastCartInner.PaymentType <> " . PAYMENTTYPE_BUSINESS_BALANCE . "
                            AND ciLastCartInner.TypeID in (:{$k}desktop_item_types)
                        )
                )
            order by cLastCart.PayDate desc, cLastCart.CartID desc
            limit 1
";

        if ($options->hasSubscriptionType !== Options::SUBSCRIPTION_TYPE_AT201) {
            $builder->andWhere("
                NOT EXISTS(
                    select 1
                    from Cart cFindNewPrepaid
                    join CartItem ciFindNewPrepaid on cFindNewPrepaid.CartID = ciFindNewPrepaid.CartID
                    where
                        cFindNewPrepaid.UserID = u.UserID
                        AND cFindNewPrepaid.PayDate > '2024-11-01'
                        AND ciFindNewPrepaid.TypeID = " . AwPlusPrepaid::TYPE . "
                )
            ");
        }

        switch ($options->hasSubscriptionType) {
            case Options::SUBSCRIPTION_TYPE_FULL_30:
                $builder->andWhere("
                    EXISTS(
                        select lastCart.CartID
                        from (
                            {$lastCartInnerSQL}
                        ) lastCart
                        join Cart c on lastCart.CartID = c.CartID
                        join CartItem ci on c.CartID = ci.CartID
                        group by c.CartID
                        having
                             round(
                                    SUM(
                                            IF(
                                                    ci.TypeID not in (" . OneCard::TYPE . "),
                                                    ci.Price * ci.Cnt * (100 - ci.Discount) / 100,
                                                    0
                                            )
                                    )
                            ) > 10
                            AND sum(if(ci.TypeID in (" . it([AwPlusSubscription::TYPE, AwPlus::TYPE, AwPlusRecurring::TYPE, AwPlus6Months::TYPE])->joinToString(', ') . "), 1, 0)) = 1
                    )
                ");

                break;

            case Options::SUBSCRIPTION_TYPE_EARLY_SUPPORTER:
                $builder->andWhere(
                    "
                        EXISTS(
                            select lastCart.CartID
                            from (
                                {$lastCartInnerSQL}
                            ) lastCart
                            join Cart c on lastCart.CartID = c.CartID
                            join CartItem ci on c.CartID = ci.CartID
                            group by c.CartID
                            having
                                round(
                                    SUM(
                                        IF(
                                            ci.TypeID not in (" . OneCard::TYPE . "),
                                            ci.Price * ci.Cnt * (100 - ci.Discount) / 100,
                                            0
                                        )
                                    )
                                ) <= 10
                                AND SUM(IF(ci.TypeID in (:{$k}at201_item_types), 1, 0)) = 0
                        )"
                );

                break;

            case Options::SUBSCRIPTION_TYPE_AT201:
                $builder->andWhere("
                    EXISTS(
                        select lastCart.CartID
                        from (
                            {$lastCartInnerSQL}
                        ) lastCart
                        join Cart c on lastCart.CartID = c.CartID
                        join CartItem ci on c.CartID = ci.CartID
                        where
                            ci.TypeID in (:{$k}at201_item_types)
                    )
                ");

                break;

            default:
                throw new \LogicException('Unknown subscription type');
        }

        $builder->setParameter(":{$k}mobile_cart_payment_types", [Cart::PAYMENTTYPE_APPSTORE, Cart::PAYMENTTYPE_ANDROIDMARKET], Connection::PARAM_INT_ARRAY);
        $builder->setParameter(":{$k}mobile_item_types", [AwPlusSubscription::TYPE], Connection::PARAM_INT_ARRAY);
        $builder->setParameter(":{$k}desktop_item_types",
            \array_merge(
                [
                    AwPlus::TYPE,
                    AwPlusSubscription::TYPE,
                    AwPlusRecurring::TYPE,
                ],
                At201Items::getTypes()
            ),
            Connection::PARAM_INT_ARRAY
        );
        $builder->setParameter(":{$k}at201_item_types",
            At201Items::getTypes(),
            Connection::PARAM_INT_ARRAY
        );
    }

    private static function filterAwPlusDuration(string $duration): string
    {
        return \str_replace(["+", 's'], "", $duration);
    }

    private function addVipHasEverSubscriptionTypeFilter(Options $options, QueryBuilderWithMetaGroupBy $builder, string $k): void
    {
        $e = $builder->expr();
        $validCartCondition = "(
            c.PayDate is not null
            and c.PaymentType is not null
            and exists(
                select 1
                from Cart cInner
                join CartItem ciInner on cInner.CartID = ciInner.CartID
                where
                    cInner.CartID = c.CartID
                    and if(
                        cInner.PaymentType in (:{$k}mobile_cart_payment_types),

                        ciInner.TypeID in (:{$k}mobile_item_types),

                        cInner.PaymentType <> :{$k}payment_type_business_balance
                        and ciInner.TypeID in (:{$k}desktop_item_types)
                    )
            )
        )";

        $full30SupporterCondition = "
            EXISTS(
                select 1
                from Cart c
                join CartItem ci on c.CartID = ci.CartID
                where
                    c.UserID = u.UserID
                    and ({$validCartCondition})
                group by c.CartID
                having
                     round(
                            SUM(
                                    IF(
                                            ci.TypeID not in (" . OneCard::TYPE . "),
                                            ci.Price * ci.Cnt * (100 - ci.Discount) / 100,
                                            0
                                    )
                            )
                    ) > 10
                    AND sum(if(ci.TypeID in (" . it([AwPlusSubscription::TYPE, AwPlus::TYPE, AwPlusRecurring::TYPE, AwPlus6Months::TYPE])->joinToString(', ') . "), 1, 0)) = 1
            )";

        $earlySupporterCondition = "
            EXISTS(
                select 1
                from Cart c
                join CartItem ci on c.CartID = ci.CartID
                where
                    c.UserID = u.UserID
                    and ({$validCartCondition})
                group by c.CartID
                having
                    round(
                        SUM(
                            IF(
                                ci.TypeID not in (" . OneCard::TYPE . "),
                                ci.Price * ci.Cnt * (100 - ci.Discount) / 100,
                                0
                            )
                        )
                    ) <= 10
                    AND SUM(IF(ci.TypeID in (:{$k}at201_item_types), 1, 0)) = 0
            )";
        $openedCardCondition = "
            EXISTS(
                select 1
                from UserCreditCard uccVip
                where
                    uccVip.UserID = u.UserID
                    and uccVip.DetectedViaQS = 1
                    and uccVip.EarliestSeenDate >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
            )
        ";

        switch ($options->vipHasEverSubscriptionType) {
            case Options::SUBSCRIPTION_TYPE_FULL_30:
                $builder->andWhere($e->or(
                    $e->and(
                        $openedCardCondition,
                        $full30SupporterCondition,
                        " NOT ({$earlySupporterCondition})",
                        $e->isNull('u.Subscription'),
                    ),
                    "
                        EXISTS(
                            select 1
                            from GroupUserLink gulVIP
                            where
                                gulVIP.UserID = u.UserID
                                AND gulVIP.SiteGroupID = :{$k}test_vip_group_id
                        )
                    "
                ));
                $builder->setParameter(":{$k}test_vip_group_id", $this->loadSiteGroupId(Sitegroup::TEST_VIP_FULL_SUPPORTER) ?? -1);

                break;

            case Options::SUBSCRIPTION_TYPE_EARLY_SUPPORTER:
                $builder->andWhere($e->or(
                    $e->and(
                        $openedCardCondition,
                        $earlySupporterCondition,
                        $e->isNull('u.Subscription'),
                    ),
                    "
                        EXISTS(
                            select 1
                            from GroupUserLink gulVIP
                            where
                                gulVIP.UserID = u.UserID
                                AND gulVIP.SiteGroupID = :{$k}vip_group_id
                        )
                    "
                ));
                $builder->setParameter(":{$k}vip_group_id", $this->loadSiteGroupId(Sitegroup::GROUP_VIP_EARLY_SUPPORTER) ?? -1);

                break;

            default:
                throw new \LogicException('Unknown subscription type');
        }

        $builder->setParameter(":{$k}mobile_cart_payment_types", [Cart::PAYMENTTYPE_APPSTORE, Cart::PAYMENTTYPE_ANDROIDMARKET], Connection::PARAM_INT_ARRAY);
        $builder->setParameter(":{$k}mobile_item_types", [AwPlusSubscription::TYPE], Connection::PARAM_INT_ARRAY);
        $builder->setParameter(":{$k}payment_type_business_balance", PAYMENTTYPE_BUSINESS_BALANCE, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}desktop_item_types",
            \array_merge(
                [
                    AwPlus::TYPE,
                    AwPlusSubscription::TYPE,
                    AwPlusRecurring::TYPE,
                ],
                At201Items::getTypes()
            ),
            Connection::PARAM_INT_ARRAY
        );
        $builder->setParameter(":{$k}at201_item_types",
            At201Items::getTypes(),
            Connection::PARAM_INT_ARRAY
        );
    }

    private function addHasEverSubscription(Options $options, QueryBuilderWithMetaGroupBy $builder, string $k): void
    {
        $e = $builder->expr();
        $validCartCondition = "(
            c.PayDate is not null
            and c.PaymentType is not null
            and exists(
                select 1
                from Cart cInner
                join CartItem ciInner on cInner.CartID = ciInner.CartID
                where
                    cInner.CartID = c.CartID
                    and if(
                        cInner.PaymentType in (:{$k}mobile_cart_payment_types),

                        ciInner.TypeID in (:{$k}mobile_item_types),

                        cInner.PaymentType <> :{$k}payment_type_business_balance
                        and ciInner.TypeID in (:{$k}desktop_item_types)
                    )
            )
        )";

        $full30SupporterCondition = "
            EXISTS(
                select 1
                from Cart c
                join CartItem ci on c.CartID = ci.CartID
                where
                    c.UserID = u.UserID
                    and ({$validCartCondition})
                group by c.CartID
                having
                     round(
                            SUM(
                                    IF(
                                            ci.TypeID not in (" . OneCard::TYPE . "),
                                            ci.Price * ci.Cnt * (100 - ci.Discount) / 100,
                                            0
                                    )
                            )
                    ) > 10
                    AND sum(if(ci.TypeID in (" . it([AwPlusSubscription::TYPE, AwPlus::TYPE, AwPlusRecurring::TYPE, AwPlus6Months::TYPE])->joinToString(', ') . "), 1, 0)) = 1
            )";

        $earlySupporterCondition = "
            EXISTS(
                select 1
                from Cart c
                join CartItem ci on c.CartID = ci.CartID
                where
                    c.UserID = u.UserID
                    and ({$validCartCondition})
                group by c.CartID
                having
                    round(
                        SUM(
                            IF(
                                ci.TypeID not in (" . OneCard::TYPE . "),
                                ci.Price * ci.Cnt * (100 - ci.Discount) / 100,
                                0
                            )
                        )
                    ) <= 10
                    AND SUM(IF(ci.TypeID in (:{$k}at201_item_types), 1, 0)) = 0
            )";

        $supportCondition = $e->or(
            $e->and(
                $e->or(
                    $full30SupporterCondition,
                    $earlySupporterCondition,
                ),
                $e->isNull('u.Subscription')
            ),
            "
                EXISTS(
                    select 1
                    from GroupUserLink gulSup3m
                    where
                        gulSup3m.UserID = u.UserID
                        AND gulSup3m.SiteGroupID = :{$k}sup_3m_test_group_id
                )
            ",
        );

        if ($options->hasEverSubscription) {
            $builder->andWhere($supportCondition);
        } else {
            $builder->andWhere(" NOT ({$supportCondition})");
        }

        $builder->setParameter(":{$k}sup_3m_test_group_id", $this->loadSiteGroupId(Sitegroup::TEST_SUPPORTER_3M_UPGRADE) ?? -1);
        $builder->setParameter(":{$k}mobile_cart_payment_types", [Cart::PAYMENTTYPE_APPSTORE, Cart::PAYMENTTYPE_ANDROIDMARKET], Connection::PARAM_INT_ARRAY);
        $builder->setParameter(":{$k}mobile_item_types", [AwPlusSubscription::TYPE], Connection::PARAM_INT_ARRAY);
        $builder->setParameter(":{$k}payment_type_business_balance", PAYMENTTYPE_BUSINESS_BALANCE, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}desktop_item_types",
            \array_merge(
                [
                    AwPlus::TYPE,
                    AwPlusSubscription::TYPE,
                    AwPlusRecurring::TYPE,
                ],
                At201Items::getTypes()
            ),
            Connection::PARAM_INT_ARRAY
        );
        $builder->setParameter(":{$k}at201_item_types",
            At201Items::getTypes(),
            Connection::PARAM_INT_ARRAY
        );
    }

    private function loadSiteGroupId(string $groupName): ?int
    {
        $id = $this->connection->executeQuery('select SiteGroupID from SiteGroup where GroupName = ?', [$groupName])->fetchOne();

        if (false === $id) {
            return null;
        }

        return (int) $id;
    }
}
