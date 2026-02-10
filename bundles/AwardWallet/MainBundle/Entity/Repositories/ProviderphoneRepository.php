<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

class ProviderphoneRepository extends EntityRepository
{
    /**
     * @param array $providers array(0 => array(account => account, provider => provider, status => status, country => country)
     * @param array $regions
     * @param string $orderBy
     * @return array
     */
    public function getUsefullPhonesV2($providers, $regions = [], $orderBy = null, LoggerInterface $logger)
    {
        //		$logger->warning("itinerary phones, provider criterias:\n" . json_encode($providers));
        if (empty($providers) && empty($regions)) {
            return [];
        }
        $c = $this->getEntityManager()->getConnection();

        $isExistRegions = count($regions) > 0;
        $whereStatusWithRegion = ''; // country specific elite phones
        $whereStatusWithoutCountry = ''; // international elite phones
        $whereWithoutStatusWithCountry = ''; // country specific support phones
        $whereWithoutStatusWithoutCountry = ''; // international support phones
        $existsAccounts = !empty(array_filter(array_column($providers, 'account')));
        $order = '';

        if (!$isExistRegions) {
            $first = true;

            foreach ($providers as $row) {
                [$geo, $provider, $account, $status, $rank] = $this->prepareCriteria($row);

                if (isset($geo)) {
                    [$airport, $city, $state, $country] = $geo;
                }
                $operator = ($first) ? "" : " OR ";
                $countryFix = (isset($country) && strtolower($country) == 'united states') ? 'r.Name like ' . $c->quote($country, \PDO::PARAM_STR) : '1 = 0';

                $geoCondition =
                    "(" .
                        $countryFix .
//						' OR ' .
//							 (isset($airport) ? "(r.Name like " . $c->quote($airport, \PDO::PARAM_STR) . " AND r.Kind = " . REGION_KIND_AIRPORT . ")" : "1 = 0") .
    //					' OR (' . (!isset($city) && !isset($state) && !isset($country) ? '1 = 0' : '1 = 1') .
//						' OR ' . (isset($city)    ? "(r.Name like " . $c->quote($city, \PDO::PARAM_STR)    . " AND r.Kind = " . REGION_KIND_CITY . ")" : "1 = 0") .
//						' OR ' . (isset($state)   ? "(r.Name like " . $c->quote($state, \PDO::PARAM_STR)   . " AND r.Kind = " . REGION_KIND_STATE . ")" : "1 = 0") .
                        " OR " . (isset($country) ? "(r.Name like " . $c->quote($country, \PDO::PARAM_STR) . " AND r.Kind = " . REGION_KIND_COUNTRY . ")" : "1 = 0") .
    //					')' .
                    " )";

                $whereStatusWithRegion .= $operator .
                    "(" .
                        // provider
                        " p.ProviderID = " . $c->quote($provider, \PDO::PARAM_INT) .
                        // account
                        " AND " . (isset($account) ? "a.AccountID = " . $c->quote($account, \PDO::PARAM_INT) . "" : ($existsAccounts ? "a.AccountID IS NULL" : '1 = 1')) .
                        // status
                        " AND " . (isset($rank) ?
                            'el.Rank = ' . $c->quote($rank, \PDO::PARAM_INT) :
                             "1 = 1"
                        ) .
                        // region
                        " AND " . $geoCondition .
                    ")";

                $whereStatusWithoutCountry .= $operator .
                    "(" .
                        // provider
                        " p.ProviderID = " . $c->quote($provider, \PDO::PARAM_STR) .
                        // account
                        " AND " . (isset($account) ? "a.AccountID = " . $c->quote($account, \PDO::PARAM_STR) . "" : ($existsAccounts ? "a.AccountID IS NULL" : '1 = 1')) .
                        // status
                        " AND " . (isset($rank) ?
                            'el.Rank = ' . $c->quote($rank, \PDO::PARAM_INT) :
                             "1 = 1"
                        ) .
                        // region
                        " AND " . (isset($country) ? "(r.Name not like " . $c->quote($country, \PDO::PARAM_STR) . " OR r.Kind <> " . REGION_KIND_COUNTRY . " OR r.Name IS NULL)" : "1 = 1") .
                    ")";

                $whereWithoutStatusWithCountry .= $operator .
                    "(" .
                        // provider
                        "p.ProviderID = " . $c->quote($provider, \PDO::PARAM_STR) .
                        // account
//						" AND " . (isset($account) ? "a.AccountID = " . $c->quote($account, \PDO::PARAM_STR) . "" : "a.AccountID IS NULL") .
                        // region
                        " AND " . $geoCondition .
                    ")";

                $whereWithoutStatusWithoutCountry .= $operator .
                    "(" .
                        // provider
                        "p.ProviderID = " . $c->quote($provider, \PDO::PARAM_STR) .
                        // account
//						" AND " . (isset($account) ? "a.AccountID = " . $c->quote($account, \PDO::PARAM_STR) . "" : "a.AccountID IS NULL") .
                        // region
                        " AND " . (isset($country) ? "(r.Name not like " . $c->quote($country, \PDO::PARAM_STR) . " OR r.Kind <> " . REGION_KIND_COUNTRY . " OR r.Name IS NULL)" : "1 = 1") .
                    ")";

                $first = false;
            }
            $order = "COALESCE(DefaultPhone, 0) DESC, PhoneFor ASC, Phone";
        } else {
            $first = true;

            foreach ($providers as $row) {
                [$geo, $provider, $account, $status, $rank] = array_merge(['country' => null, 'account' => null, 'status' => null, 'rank' => null, 'provider' => null], $row);
                $operator = ($first) ? "" : " OR ";
                $whereStatusWithRegion .= $operator .
                    // provider
                    "(p.ProviderID = " . $c->quote($provider, \PDO::PARAM_STR) .
                    // account
                    " AND " . (isset($account) ? "a.AccountID = " . $c->quote($account, \PDO::PARAM_STR) . "" : "1 = 1") .
                    // status
                    " AND " . (isset($rank) ?
                        'el.Rank = ' . $c->quote($rank, \PDO::PARAM_INT) :
                        "1 = 1"
                    ) .
                    // region
                    " AND (ph.RegionID IN (" . implode(", ", array_map('intval', $regions)) . ") OR ph.RegionID IS NULL))";
                $whereStatusWithoutCountry .= $operator .
                    "(1 = 0)";
                $whereWithoutStatusWithCountry .= $operator .
                    // provider
                    "(p.ProviderID = " . $c->quote($provider, \PDO::PARAM_STR) .
                    // account
                    " AND " . (isset($account) ? "a.AccountID = " . $c->quote($account, \PDO::PARAM_STR) . "" : "1 = 1") .
                    // region
                    " AND (ph.RegionID IN (" . implode(", ", $regions) . ") OR ph.RegionID IS NULL))";
                $whereWithoutStatusWithoutCountry .= $operator .
                    "(1 = 0)";

                $first = false;
            }
            $order = "RegionID DESC, COALESCE(DefaultPhone, 0) DESC, PhoneFor ASC, Phone";
        }

        $order = "AccountID DESC, PhoneGroupID ASC, " . ((!$isExistRegions) ? 'GeoDistance DESC' : '') . ", $order";

        if (isset($orderBy)) {
            $order = $orderBy;
        }
        $geoColumns = ((!$isExistRegions) ? '
			CASE r.Kind
				WHEN "' . REGION_KIND_COUNTRY . '" THEN 7
				WHEN LOWER(r.Name) = "united states"  THEN 6
				WHEN "' . REGION_KIND_REGION . '" THEN 5
				WHEN "' . REGION_KIND_CONTINENT . '" THEN 4
				ELSE 0
				END' : 'NULL') . ' as GeoDistance,
				r.Kind as RegionKind
				';

        // TODO: optimizer-friendly query
        $sql = "
				(SELECT
					p.ProviderID						,
					" . ($existsAccounts ? 'a.AccountID,' : 'NULL AS AccountID,') . "
					ph.*	     						,
					el.Name AS Name						,
					'StatusWithRegion' AS PhoneGroup	,
					'1' AS PhoneGroupID					,
					{$geoColumns}						,
					r.Name  AS RegionCaption
				FROM
					" . ($existsAccounts ? 'Account a INNER JOIN ' : '') . "
					Provider p " . ($existsAccounts ? 'ON p.ProviderID = a.ProviderID ' : '') . "
					INNER JOIN EliteLevel el
						ON p.ProviderID = el.ProviderID
					INNER JOIN ProviderPhone ph
						ON el.ProviderID = ph.ProviderID
							AND el.EliteLevelID = ph.EliteLevelID
					LEFT OUTER JOIN Region r
						ON r.RegionID = ph.RegionID
				WHERE    $whereStatusWithRegion
					AND ph.Valid = 1
				)

				UNION

				(SELECT
					p.ProviderID						,
					" . ($existsAccounts ? 'a.AccountID,' : 'NULL AS AccountID,') . "
					ph.*	     						,
					el.Name AS Name						,
					'StatusWithoutRegion' AS PhoneGroup	,
					'2' AS PhoneGroupID					,
					{$geoColumns}						,
					r.Name  AS RegionCaption
				FROM
					" . ($existsAccounts ? 'Account a INNER JOIN ' : '') . "
					Provider p " . ($existsAccounts ? 'ON p.ProviderID = a.ProviderID ' : '') . "
					INNER JOIN EliteLevel el
						ON p.ProviderID = el.ProviderID
					INNER JOIN ProviderPhone ph
						ON el.ProviderID = ph.ProviderID
							AND el.EliteLevelID = ph.EliteLevelID
					LEFT OUTER JOIN Region r
						ON r.RegionID = ph.RegionID
				WHERE    $whereStatusWithoutCountry
					AND ph.Valid = 1
				)

				UNION

				(SELECT
					p.ProviderID						,
					NULL AS AccountID,
					ph.*	     						,
					CASE ph.PhoneFor
							WHEN '1' THEN 'General'
							WHEN '2' THEN 'Reservations'
							WHEN '3' THEN 'Customer support'
							WHEN '4' THEN 'Member Services'
							WHEN '5' THEN 'Award Travel'
							END AS Name,
					'WithoutStatusWithRegion' AS PhoneGroup	,
					'3' AS PhoneGroupID					,
					{$geoColumns}						,
					r.Name  AS RegionCaption
				FROM
					Provider p
					INNER JOIN ProviderPhone ph
						ON p.ProviderID = ph.ProviderID
							AND ph.EliteLevelID IS NULL
					LEFT OUTER JOIN Region r
						ON r.RegionID = ph.RegionID
				WHERE    $whereWithoutStatusWithCountry
					AND ph.Valid = 1
				)

				UNION

				(SELECT
					p.ProviderID						,
					NULL AS AccountID,
					ph.*	     						,
					CASE ph.PhoneFor
							WHEN '1' THEN 'General'
							WHEN '2' THEN 'Reservations'
							WHEN '3' THEN 'Customer support'
							WHEN '4' THEN 'Member Services'
							WHEN '5' THEN 'Award Travel'
							END AS Name,
					'WithoutStatusWithoutRegion' AS PhoneGroup	,
					'4' AS PhoneGroupID					,
					{$geoColumns}						,
					r.Name  AS RegionCaption
				FROM
					Provider p
					INNER JOIN ProviderPhone ph
						ON p.ProviderID = ph.ProviderID
							AND ph.EliteLevelID IS NULL
					LEFT OUTER JOIN Region r
						ON r.RegionID = ph.RegionID
				WHERE    $whereWithoutStatusWithoutCountry
					AND ph.Valid = 1
				)

				ORDER BY $order
			";
        //		$logger->warning("itinerary phones, sql query:\n$sql");
        $stmt = $c->query($sql);

        if (($rowCount = $stmt->rowCount()) > 10000 && $rowCount <= 20000) {
            $logger->warning("itinerary phones, potential memory bloat, sql query:\n$sql");
        } elseif ($rowCount > 20000) {
            $logger->warning("itinerary phones, memory bloat, refusing query result, sql query:\n$sql");

            return [];
        }

        return $stmt->fetchAll();
    }

    protected function prepareCriteria($criteria)
    {
        $criteria = array_intersect_key(
            array_merge($filter = [
                'geo' => null,
                'provider' => null,
                'account' => null,
                'status' => null,
                'rank' => null,
            ],
                $criteria),
            $filter);

        if (is_array($criteria['geo'])) {
            $criteria['geo'] = array_values(array_intersect_key(
                array_merge($filter = [
                    'airport' => null,
                    'city' => null,
                    'state' => null,
                    'country' => null,
                ],
                    $criteria['geo']
                ),
                $filter
            ));
        }

        return array_values($criteria);
    }
}
