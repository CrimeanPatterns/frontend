<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Form\Account\Builder;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/manager/find-itineraries-provider")
 */
class FindItinerariesProviderController extends AbstractController
{
    private const LIMIT = 100;
    private const LAST_DAYS = 14;
    private const TTL = 60 * 60 * 4; // 4 hours
    private const arKind = [
        'T' => 'Trip',
        'L' => 'Rental (Car)',
        'R' => 'Reservation (Hotel)',
        'E' => 'Events',
        'P' => 'Parking',
    ];

    protected Connection $connection;
    private Builder $builder;
    private EntityManagerInterface $em;
    private \Memcached $memcached;
    private RouterInterface $router;
    private string $requiresChannel;
    private string $host;

    public function __construct(
        Connection $connection,
        Builder $builder,
        EntityManagerInterface $em,
        \Memcached $memcached,
        RouterInterface $router,
        string $requiresChannel,
        string $host
    ) {
        $this->connection = $connection;
        $this->builder = $builder;
        $this->em = $em;
        $this->memcached = $memcached;
        $this->router = $router;
        $this->requiresChannel = $requiresChannel;
        $this->host = $host;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ACCOUNTBYREGION')")
     * @Route("", name="aw_manager_finditinerariesprovider")
     */
    public function indexAction(Request $request)
    {
        $params = $this->parseQuery($request);

        $data = ["message" => '', "data" => []];

        if (!empty($params['search'])) {
            $data = $this->getData($params);
        }

        $providers = $this->getProviders();

        $response = $this->render('@AwardWalletMain/Manager/Support/Itinerary/findItinerariesProvider.html.twig', [
            "providerCode" => $providers['Code'],
            "providerName" => $providers['Name'],
            "inputValue" => $params,
            "kind" => $this->getElements(self::arKind),
            "category" => $this->getElements(Trip::CATEGORY_NAMES),
            "etype" => $this->getElements(Restaurant::EVENT_TYPE_NAMES),
            "data" => $data,
        ]);

        return $response;
    }

    private function getProviders()
    {
        $qb = $this->em->createQueryBuilder();

        $provList = [7 => '', 16 => '', 26 => '', 145 => ''];
        $q = $qb->select(['p'])
            ->from(Provider::class, 'p')
            ->andWhere('p.cancheckitinerary = 1 or p.cancheckconfirmation = 1')
            ->andWhere('p.state <> :disabled')
            ->orWhere('p.providerid IN (:ids)')
            ->setParameter('disabled', PROVIDER_DISABLED)
            ->setParameter('ids', array_keys($provList), Connection::PARAM_INT_ARRAY)
            ->orderBy('p.code', 'ASC')
            ->getQuery();

        $rows = $q->getResult();

        $array = [];

        foreach ($rows as $value) {
            $array['Code'][] = [
                'ID' => $value->getProviderid(),
                'Description' => $value->getCode(),
                'CanCheckCancelled' => $value->getCanCheckCancelled(),
            ];
            $array['Name'][] = ['ID' => $value->getProviderid(), 'Description' => $value->getDisplayname()];
        }

        return $array;
    }

    private function getElements(array $ar)
    {
        $array = [];

        foreach ($ar as $key => $value) {
            $array[] = ['ID' => $key, 0 => $key, 'Description' => $value, 1 => $value];
        }

        return $array;
    }

    private function parseQuery($request)
    {
        $params = [];

        foreach ([
            'providerID',
            'login2',
            'login3',
            'kind',
            'category',
            'etype',
            'cancelled',
            'type_search',
            'search',
        ] as $filter) {
            $params[$filter] = $request->query->get($filter, '');
        }

        return $params;
    }

    /**
     * @return bool|array
     */
    private function getData($params): array
    {
        if (!is_numeric($params['providerID'])) {
            return ["message" => 'some thing went wrong', "data" => []];
        }
        $key = "fip_" . md5(implode('_', $params));
        $data = $this->memcached->get($key);

        if (!empty($data)) {
            $this->memcached->set($key, $data, 60 * 60); // for refresh after fixed bug TODO del this row in 2022

            return $data;
        }
        $cancelled = !empty($params['cancelled']) && $params['cancelled'] === 'on';

        $where = ['t.ProviderID = ?', 't.Parsed = 1'];
        $queryParams[] = $params['providerID'];
        $types[] = \PDO::PARAM_INT;
        $where[] = '[StartDate] > NOW()';

        if (is_numeric($params['category'])) {
            $where[] = 't.Category = ?';
            $queryParams[] = $params['category'];
            $types[] = \PDO::PARAM_INT;
        }

        if (is_numeric($params['etype'])) {
            $where[] = 't.EventType = ?';
            $queryParams[] = $params['etype'];
            $types[] = \PDO::PARAM_INT;
        }

        if ($params['type_search'] === "Find retrieved") {
            $where = array_merge($where, ["t.AccountID is null", "not (t.ConfFields  is null)"]);
        } elseif ($params['type_search'] === "Find accounts") {
            $where = array_merge($where,
                ["a.SavePassword = " . SAVE_PASSWORD_DATABASE, "a.ProviderID = ?"]);
            $queryParams[] = $params['providerID'];
            $types[] = \PDO::PARAM_INT;
        } else {
            return ["message" => 'some thing went wrong', "data" => []];
        }

        if (!empty($params['login2']) && is_string($params['login2'])) {
            $where[] = 'a.Login2 = ?';
            $queryParams[] = $params['login2'];
            $types[] = \PDO::PARAM_STR;
        }

        if (!empty($params['login3']) && is_string($params['login3'])) {
            $where[] = 'a.Login3 = ?';
            $queryParams[] = $params['login3'];
            $types[] = \PDO::PARAM_STR;
        }
        $resParams = $resTypes = [];

        switch ($params['kind']) {
            case "T":
                $sql = $this->getTripsSQL($where, $cancelled, $queryParams, $types, $resParams, $resTypes);

                break;

            case "L":
            case "R":
            case "E":
            case "P":
                $sql = $this->getTableSQL($params['kind'], $where, $cancelled, $queryParams, $types, $resParams, $resTypes);

                break;

            default:
                $sql = $this->getTripsSQL($where, $cancelled, $queryParams, $types, $resParams, $resTypes)
                    . "\nUNION\n" . $this->getTableSQL('L', $where, $cancelled, $queryParams, $types, $resParams, $resTypes)
                    . "\nUNION\n" . $this->getTableSQL('R', $where, $cancelled, $queryParams, $types, $resParams, $resTypes)
                    . "\nUNION\n" . $this->getTableSQL('E', $where, $cancelled, $queryParams, $types, $resParams, $resTypes)
                    . "\nUNION\n" . $this->getTableSQL('P', $where, $cancelled, $queryParams, $types, $resParams, $resTypes)
                    . "\nORDER BY DATE(StartDate), SortIndex, StartDate";
        }

        $sql = "
            SELECT 
                q.Kind,
                q.ID, 
                q.AccountID, 
                q.UserID, 
                q.Category, 
                q.Cancelled, 
                q.StartDate,
                IF (q.Itineraries IS NULL, 1, q.Itineraries) AS Counter,
                q.ConfirmationNumber,
                q.ShareCode 
            FROM ({$sql}) AS q 
            WHERE q.Itineraries IS NULL OR q.Itineraries > 0
            ORDER BY Counter, q.StartDate, q.AccountID
        ";
        $conn = $this->em->getConnection();

        $result = $conn->executeQuery($sql, $resParams, $resTypes)->fetchAll(Query::HYDRATE_ARRAY);
        $data = [];

        foreach ($result as &$row) {
            $shareCode = base64_encode($row['Kind'] . '.' . $row['ID'] . '.' . $row['ShareCode']);
            $url = $this->getURL($shareCode);
            $data[] = array_merge($row, ['link' => $url]);
        }
        $data = ["message" => '', "data" => $data];
        $this->memcached->set($key, $data, self::TTL);

        return $data;
    }

    private function getTripsSQL(array $arWhere, bool $cancelled, $queryParams, $types, &$resParams, &$resTypes)
    {
        $limit = self::LIMIT;

        if ($cancelled) {
            $arWhere = array_merge($arWhere, ['t.Hidden = 1', 't.Cancelled > 0']);
        } else {
            $arWhere = array_merge($arWhere, ['t.Cancelled = 0']);
        }

        $arWhereSubSelect = [];

        foreach ($arWhere as $key => $value) {
            if (strpos($value, '[StartDate]') !== false || strpos($value, '[EndDate]') !== false) {
                $arWhereSubSelect[] = $value;
                unset($arWhere[$key]);
            }
        }
        $whereSubSelect = (count($arWhereSubSelect) > 0 ? "WHERE " . implode(" AND ", $arWhereSubSelect) : "");
        $whereSubSelect = str_ireplace('[StartDate]', 'tss.DepDate', $whereSubSelect);
        $whereSubSelect = str_ireplace('[EndDate]', 'tss.ArrDate', $whereSubSelect);

        $joinAccount = "
            LEFT OUTER JOIN Account a ON a.AccountID = t.AccountID
  	    ";

        $select = "
          SELECT 
            t.TripID AS ID,
            'T' AS Kind,
            t.Category,
            t.AccountID,
            t.UserID,
            t.Cancelled, 
            ts.DepDate AS StartDate,
            ts.ArrDate AS EndDate,
            t.RecordLocator AS ConfirmationNumber,
            a.Itineraries,
            IF(t.Direction = 1, 25, 10) AS SortIndex,
            t.ShareCode
            FROM Trip t
        ";

        $where = "";

        if (count($arWhere) > 0) {
            $where = "WHERE " . implode(" AND ", $arWhere);
            $resParams = array_merge($resParams, $queryParams);
            $resTypes = array_merge($resTypes, $types);
        }
        $s = "(
            {$select}
            INNER JOIN (
                SELECT 
                    tss.TripID, 
                    MAX(tss.DepDate) AS DepDate, 
                    MAX(tss.ArrDate) AS ArrDate 
                FROM TripSegment tss 
                {$whereSubSelect}
                GROUP BY tss.TripID
            ) ts ON ts.TripID = t.TripID
            {$joinAccount}
            {$where}
            LIMIT {$limit}
        )";

        if ($cancelled) {
            $arWhere = array_merge($arWhere, ['t.UpdateDate >= NOW() - INTERVAL 14 DAY']);
            $where = "";

            if (count($arWhere) > 0) {
                $where = "WHERE " . implode(" AND ", $arWhere);
                $resParams = array_merge($resParams, $queryParams);
                $resTypes = array_merge($resTypes, $types);
            }
            // cause without TripSegment
            $select = str_replace(['ts.DepDate', 'ts.ArrDate'], 't.UpdateDate', $select);
            $s .= "
            UNION (
            {$select}
            {$joinAccount}
            {$where}
            LIMIT {$limit}
            )";
        }

        return $s;
    }

    private function getTableSQL(string $kind, array $arWhere, bool $cancelled, $queryParams, $types, &$resParams, &$resTypes)
    {
        $limit = self::LIMIT;

        if ($cancelled) {
            $arWhere = array_merge($arWhere, ['t.Hidden = 1', 't.Cancelled > 0']);
            $arWhereCancelled = $arWhere;

            foreach ($arWhere as $key => $value) {
                if (strpos($value, '[StartDate]') !== false || strpos($value, '[EndDate]') !== false) {
                    unset($arWhereCancelled[$key]);
                }
            }
            $arWhere = array_merge($arWhereCancelled, ['t.UpdateDate >= NOW() - INTERVAL 14 DAY']);
        } else {
            $arWhere = array_merge($arWhere, ['t.Cancelled = 0']);
        }
        $where = "";

        if (count($arWhere) > 0) {
            $where = "WHERE " . implode(" AND ", $arWhere);
            $resParams = array_merge($resParams, $queryParams);
            $resTypes = array_merge($resTypes, $types);
        }

        switch ($kind) {
            case "L":
                $select = "
                SELECT
                  t.RentalID AS ID, 
                  'L' AS Kind, 
                  0 AS Category,
                  t.AccountID, 
                  t.UserID,
                  t.Cancelled,
                  t.PickupDatetime AS StartDate,
 	              t.DropoffDatetime AS EndDate, 
	              t.Number AS ConfirmationNumber,
	              a.Itineraries,
	              20 AS SortIndex,
                  t.ShareCode
	            FROM Rental t
	            LEFT OUTER JOIN Account a ON t.AccountID = a.AccountID
	          ";
                $where = str_ireplace('[StartDate]', 't.PickupDatetime', $where);
                $where = str_ireplace('[EndDate]', 't.DropoffDatetime', $where);

                break;

            case "P":
                $select = "
                SELECT
                  t.ParkingID AS ID, 
                  'P' AS Kind, 
                  0 AS Category,
                  t.AccountID, 
                  t.UserID,
                  t.Cancelled,
                  t.StartDatetime AS StartDate,
 	              t.EndDatetime AS EndDate, 
	              t.Number AS ConfirmationNumber,
	              a.Itineraries,
	              60 AS SortIndex,
                  t.ShareCode
	            FROM Parking t
	            LEFT OUTER JOIN Account a ON t.AccountID = a.AccountID
	          ";
                $where = str_ireplace('[StartDate]', 't.StartDatetime', $where);
                $where = str_ireplace('[EndDate]', 't.EndDatetime', $where);

                break;

            case "R":
                $select = "
                SELECT
                  t.ReservationID AS ID, 
                  'R' AS Kind, 
                  0 AS Category,
                  t.AccountID, 
                  t.UserID,
                  t.Cancelled,
                  t.CheckInDate AS StartDate,
 	              t.CheckOutDate AS EndDate, 
	              t.ConfirmationNumber AS ConfirmationNumber,
	              a.Itineraries,
	              40 AS SortIndex,
                  t.ShareCode
	            FROM Reservation t
	            LEFT OUTER JOIN Account a ON t.AccountID = a.AccountID
	          ";
                $where = str_ireplace('[StartDate]', 't.CheckInDate', $where);
                $where = str_ireplace('[EndDate]', 't.CheckOutDate', $where);

                break;

            case "E":
                $select = "
                SELECT
                  t.RestaurantID AS ID, 
                  'E' AS Kind, 
                  t.EventType AS Category,
                  t.AccountID, 
                  t.UserID,
                  t.Cancelled,
                  t.StartDate AS StartDate,
 	              t.EndDate AS EndDate, 
	              t.ConfNo AS ConfirmationNumber,
	              a.Itineraries,
	              50 AS SortIndex,
                  t.ShareCode
	            FROM Restaurant t
	            LEFT OUTER JOIN Account a ON t.AccountID = a.AccountID
	          ";
                $where = str_ireplace('[StartDate]', 't.StartDate', $where);
                $where = str_ireplace('[EndDate]', 't.EndDate', $where);

                break;

            default:
                return "";
        }

        $s = "({$select} {$where} LIMIT {$limit})";

        return $s;
    }

    private function getURL($shareCode)
    {
        if (empty($shareCode)) {
            return "";
        }

        return $this->requiresChannel . "://"
            . $this->host
            . $this->router->generate('aw_timeline_shared', ['shareCode' => $shareCode]);
    }
}
