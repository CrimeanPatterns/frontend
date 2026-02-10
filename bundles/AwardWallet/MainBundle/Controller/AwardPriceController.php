<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\RA\Async\AwardPriceTask;
use AwardWallet\MainBundle\Service\RA\AwardPriceService;
use AwardWallet\MainBundle\Service\RA\RAFlightSchema;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/manager/award-price")
 */
class AwardPriceController extends AbstractController
{
    public const AWARD_PRICE_REGION = 1;

    public const AWARD_PRICE_COUNTRY = 2;

    public const AWARD_PRICE_AIRLINE_REGION = 3;

    public const AWARD_PRICE_REGIONS = [
        self::AWARD_PRICE_REGION => 'Region',
        self::AWARD_PRICE_COUNTRY => 'Country',
        self::AWARD_PRICE_AIRLINE_REGION => 'Airline Region',
    ];

    private const MSG_BUSY = 'it is impossible to start a task until the previous one has completed its work.';

    private const SQL_REGION = /** @lang MySQL */
        "
        SELECT 
			r.RegionID AS ID, 
            COALESCE(r.Name, rco.Name, rs.Name, CONCAT(r.AirCode, ', ', ac.AirName)) AS Name,
            r.Kind,
            r.AwardChartID
		FROM
			Region r
            LEFT OUTER JOIN Country rco ON rco.CountryID = r.CountryID
            LEFT OUTER JOIN State rs ON rs.StateID = r.StateID
            LEFT JOIN AirCode ac ON r.AirCode = ac.AirCode
		WHERE r.Kind = ?
		ORDER BY Name
    ";

    /** @var Connection */
    protected $connection;

    /** @var EntityManager */
    private $em;

    /** @var AwardPriceService */
    private $awardPrice;

    /** @var \Memcached */
    private $memcached;

    /** @var string|null */
    private $userEmail;

    public function __construct(
        Connection $connection,
        EntityManagerInterface $em,
        AwardPriceService $awardPrice,
        TokenStorageInterface $tokenStorage,
        \Memcached $memcached
    ) {
        $this->connection = $connection;
        $this->em = $em;
        $this->awardPrice = $awardPrice;
        $this->memcached = $memcached;

        $login = $tokenStorage->getToken()->getUser()->getLogin();
        $user = $this->em->getRepository(Usr::class)->findOneBy(['login' => $login]);
        $this->userEmail = $user->getEmail();

        if (!preg_match("/@awardwallet.com$/", $this->userEmail)) {
            $this->userEmail = 'support@awardwallet.com';
        }
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_AWARDPRICE')")
     * @Route("", name="aw_award_price", methods={"GET"})
     */
    public function indexAction(Request $request)
    {
        $params = $this->parseQuery($request);

        if (isset($params['emailAddress'])) {
            unset($params['emailAddress']);
        }

        // $this->memcached->delete(AwardPriceTask::AWARD_PRICE_KEY);
        if ($fileName = $this->memcached->get(AwardPriceTask::AWARD_PRICE_KEY)) {
            $params['msg'] = self::MSG_BUSY;
            $params['fileName'] = 'wait for alert with file: ' . $fileName;
        }
        $response = $this->render('@AwardWalletMain/Manager/AwardPrice/index.html.twig', [
            "inputValue" => $params,
            "provider" => $this->getProviders(),
            "cabinType" => ['economy', 'premiumEconomy', 'business', 'firstClass'],
            "flightType" => RAFlightSchema::FLIGHT_TYPES,
            "origType" => self::AWARD_PRICE_REGIONS,
            "destType" => self::AWARD_PRICE_REGIONS,
            "email" => $this->userEmail,
        ]);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_AWARDPRICE')")
     * @Route("/task", name="aw_award_price_task", methods={"POST"})
     */
    public function taskAction(Request $request, Process $asyncProcess)
    {
        $params = $this->parseQuery($request);

        $errors = $this->checkParams($params);

        if (isset($params['emailAddress'])) {
            $email = $params['emailAddress'];
            unset($params['emailAddress']);
        } else {
            $email = $this->userEmail;
        }

        if (!empty($errors)) {
            $params['noData'] = 1;

            return $this->redirectToRoute('aw_award_price', $params);
        }

        $prepareData = $this->prepareData($params);

        $fileName = $this->getFileName($prepareData);
        $message = $this->getBodyMessage($prepareData);

        $task = new AwardPriceTask($params, $fileName, $message, $email);

        if (!$this->memcached->add(AwardPriceTask::AWARD_PRICE_KEY, $task->getFileName(), 60 * 60 * 1)) {
            return $this->redirectToRoute('aw_award_price', $params);
        }

        $asyncProcess->execute($task);

        return $this->redirectToRoute('aw_award_price', $params);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_AWARDPRICE')")
     * @Route("/dictionary", name="aw_award_price_dictionary", methods={"POST"})
     */
    public function dictionaryAction(Request $request)
    {
        $point = $request->get('point');

        if (!in_array($point, ['orig', 'dest'])) {
            return new Response('');
        }
        $data = null;
        $type = $request->get('type');

        switch ($type) {
            case 1: // region
                $data = $this->getKind(REGION_KIND_REGION);
                $name = $title = self::AWARD_PRICE_REGIONS[self::AWARD_PRICE_REGION];

                break;

            case 2: // country
                $data = $this->getKind(REGION_KIND_COUNTRY);
                $name = $title = self::AWARD_PRICE_REGIONS[self::AWARD_PRICE_COUNTRY];

                break;

            case 3: // award chart -> airline region
                $data = $this->getAwardChart();
                $name = 'AwardChart';
                $title = 'Award Chart';

                break;

            case 4: // airline region by award chart
                $id = $request->get('id');

                if (null === $id) {
                    return new Response('');
                }
                $data = $this->getAirlineRegionByAwardChart($id);
                $title = self::AWARD_PRICE_REGIONS[self::AWARD_PRICE_AIRLINE_REGION];
                $name = str_replace(' ', '', $title);

                break;
        }

        if (empty($data)) {
            return new Response('');
        }

        return $this->render('@AwardWalletMain/Manager/AwardPrice/dictionary.html.twig', [
            "data" => $data,
            "type" => $type,
            "name" => $point . $name,
            "point" => $point,
            "title" => $title,
            "idBlock" => $point . $name . 'Block',
        ]);
    }

    private function checkParams(array $params): array
    {
        $errors = [];

        if (isset($params['searchDate1'], $params['searchDate2']) && strtotime($params['searchDate1']) > strtotime($params['searchDate2'])) {
            $errors[] = 'start Search Date greater then end one';
        }

        if (isset($params['travelDate1'], $params['travelDate2']) && strtotime($params['travelDate1']) > strtotime($params['travelDate2'])) {
            $errors[] = 'start Travel Date greater then end one';
        }

        if (isset($params['min'], $params['max']) && $params['min'] > $params['max']) {
            $errors[] = 'min days before departure greater than max';
        }

        if (!isset($params['emailAddress'])) {
            $errors[] = 'need email';
        } elseif (!preg_match('/@awardwallet\.com$/', $params['emailAddress'])) {
            $errors[] = 'can use only @awardwallet.com';
        }

        return $errors;
    }

    private function getFileName($data): string
    {
        $include = [
            'From' => true,
            'To' => true,
        ];

        if (!empty($data['searchDate1']) && !empty($data['searchDate2'])) {
            $data['searchDate1'] = 's1-' . date('dmy', strtotime($data['searchDate1']));
            $data['searchDate2'] = 's2-' . date('dmy', strtotime($data['searchDate2']));
            $include += [
                'searchDate1' => true,
                'searchDate2' => true,
            ];
        }

        if (!empty($data['travelDate1']) && !empty($data['travelDate2'])) {
            $data['travelDate1'] = 't1-' . date('dmy', strtotime($data['travelDate1']));
            $data['travelDate2'] = 't2-' . date('dmy', strtotime($data['travelDate2']));
            $include += [
                'travelDate1' => true,
                'travelDate2' => true,
            ];
        }

        if (empty($data['ignoreFlightType'])) {
            $include += ['flightType' => true];
        }

        if (empty($data['ignoreCabinType'])) {
            $include += ['cabinType' => true];
        }

        if (empty($data['ignoreProvider'])) {
            $include += ['providerCode' => true];
        }

        $data = array_map(function ($s) {
            return str_replace([' ', '---'], ['-', '-'], strtolower($s));
        }, array_intersect_key($data, $include));

        return str_replace('/', '', 'awp_' . implode('_', $data) . '.csv');
    }

    private function getBodyMessage($data): string
    {
        $msg = [
            "Result for search with filter:",
            "",
            "Provider:                  %s",
            "Cabin type:                %s",
            "Flight Type:               %s",
            "Origin Type:               %s",
            "From:                      %s",
            "Include Children:          %s",
            "Destination Type:          %s",
            "To:                        %s",
            "Include Children:          %s",
            "Start search date:         %s",
            "End search date:           %s",
            "Start travel date:         %s",
            "End travel date:           %s",
            "Min days before Departure: %s",
            "Max days before Departure: %s",
        ];

        if (!empty($data['ignoreFlightType'])) {
            $data['flightType'] = 'all';
        }

        if (!empty($data['ignoreCabinType'])) {
            $data['cabinType'] = 'all';
        }

        if (!empty($data['ignoreProvider'])) {
            $data['providerCode'] = 'all';
        }

        return sprintf(implode("\n", $msg),
            $data['providerCode'], $data['cabinType'], $data['flightType'], $data['origType'],
            $data['From'], $data['origIncChild'], $data['destType'], $data['To'], $data['destIncChild'],
            $data['searchDate1'], $data['searchDate2'],
            $data['travelDate1'], $data['travelDate2'], $data['daysBeforeDepMin'], $data['daysBeforeDepMax']);
    }

    private function prepareData($params): array
    {
        $filters = [
            'providerCode',
            'cabinType',
            'flightType',
            'origType',
            'destType',
            'searchDate1',
            'searchDate2',
            'travelDate1',
            'travelDate2',
            'daysBeforeDepMin',
            'daysBeforeDepMax',
            'origIncChild',
            'destIncChild',
            'ignoreProvider',
            'ignoreCabinType',
            'ignoreFlightType',
        ];
        $data = [];

        foreach ($filters as $field) {
            if (isset($params[$field])) {
                $data[$field] = (string) $params[$field];
            } else {
                $data[$field] = '';
            }
        }

        $origRegionId = $params['origRegion'] ?? $params['origCountry'] ?? $params['origAirlineRegion'];
        $destRegionId = $params['destRegion'] ?? $params['destCountry'] ?? $params['destAirlineRegion'];

        if ($data['origType'] == self::AWARD_PRICE_COUNTRY) {
            $data['From'] = $this->getCountryName($origRegionId);
        } else {
            $data['From'] = $this->awardPrice->getRegionName($origRegionId);
        }

        if ($data['destType'] == self::AWARD_PRICE_COUNTRY) {
            $data['To'] = $this->getCountryName($destRegionId);
        } else {
            $data['To'] = $this->awardPrice->getRegionName($destRegionId);
        }

        if (isset(self::AWARD_PRICE_REGIONS[$data['origType']])) {
            $data['origType'] = self::AWARD_PRICE_REGIONS[$data['origType']];
        }

        if (isset(self::AWARD_PRICE_REGIONS[$data['destType']])) {
            $data['destType'] = self::AWARD_PRICE_REGIONS[$data['destType']];
        }

        if (isset(RAFlightSchema::FLIGHT_TYPES[$data['flightType']])) {
            $data['flightType'] = RAFlightSchema::FLIGHT_TYPES[$data['flightType']];
        }

        return $data;
    }

    private function parseQuery($request)
    {
        $params = [];
        $filters = [
            'providerCode',
            'cabinType',
            'flightType',
            'origType',
            'destType',
            'origRegion',
            'origCountry',
            'origAwardChart',
            'origAirlineRegion',
            'destRegion',
            'destCountry',
            'destAwardChart',
            'destAirlineRegion',
            'searchDate1',
            'searchDate2',
            'travelDate1',
            'travelDate2',
            'daysBeforeDepMin',
            'daysBeforeDepMax',
            'emailAddress',
            'noData',
            'origIncChild',
            'destIncChild',
            'ignoreProvider',
            'ignoreCabinType',
            'ignoreFlightType',
        ];

        if ($request->isMethod('POST')) {
            $requestParams = $request->request;
        } else {
            $requestParams = $request->query;
        }

        foreach ($filters as $filter) {
            $params[$filter] = $requestParams->get($filter);
        }

        return array_filter($params);
    }

    private function getProviders(): array
    {
        $q = $this->connection->executeQuery(/** @lang MySQL */ "SELECT DISTINCT Provider FROM RAFlightStat WHERE Provider <> 'testprovider' AND FirstSeen IS NOT NULL");

        $codes = $q->fetchFirstColumn();

        $qb = $this->em->createQueryBuilder();
        $rows = $qb->select(['p.code AS ID', "CONCAT(p.name, ' (', p.code,')') AS Name"])
            ->from(Provider::class, 'p')
            ->andWhere('p.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->orderBy('p.name', 'ASC')
            ->getQuery();

        return $rows->getResult();
    }

    private function getKind($kind): array
    {
        return $this->connection->executeQuery(self::SQL_REGION, [$kind])->fetchAllAssociative();
    }

    private function getAwardChart(): array
    {
        return $this->connection->executeQuery(/** @lang MySQL */ 'SELECT AwardChartID AS ID, Name FROM AwardChart ORDER BY Name')
            ->fetchAllAssociative();
    }

    private function getAirlineRegionByAwardChart($awardChartID): array
    {
        return $this->connection->executeQuery(/** @lang MySQL */ 'SELECT RegionID AS ID, Name FROM Region  WHERE AwardChartID = ?',
            [$awardChartID])->fetchAllAssociative();
    }

    private function getCountryName($ID): ?string
    {
        $row = $this->connection->executeQuery(/** @lang MySQL */ 'SELECT Name FROM Country WHERE CountryID = (SELECT CountryID FROM Region WHERE RegionID = ?)',
            [$ID])->fetchOne();

        if (false === $row) {
            return null;
        }

        return $row;
    }
}
