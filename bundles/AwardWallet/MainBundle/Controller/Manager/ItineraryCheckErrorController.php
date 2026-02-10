<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\ItineraryCheckError;
use AwardWallet\MainBundle\Entity\Repositories\ItineraryRepositoryInterface;
use AwardWallet\MainBundle\Entity\Repositories\ParkingRepository;
use AwardWallet\MainBundle\Entity\Repositories\RentalRepository;
use AwardWallet\MainBundle\Entity\Repositories\ReservationRepository;
use AwardWallet\MainBundle\Entity\Repositories\RestaurantRepository;
use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Service\ParserNoticeProvider;
use Aws\S3\S3Client;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/manager/itineraryCheckError")
 */
class ItineraryCheckErrorController extends AbstractController
{
    private const PAGE_SIZE = 50;
    //    private const TTL = 60 * 60;
    private const TTL = 10; // debug
    private const LAST_DAYS = 10;

    protected Connection $connection;
    protected ApiCommunicator $communicator;
    private S3Client $s3Client;
    private ParserNoticeProvider $parserNotice;
    private EntityManagerInterface $em;
    private \Memcached $memcached;

    private $currentUserId;
    private $currentUserLogin;
    private RouterInterface $router;
    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        ApiCommunicator $communicator,
        TokenStorageInterface $tokenStorage,
        S3Client $s3Client,
        ParserNoticeProvider $parserNotice,
        EntityManagerInterface $em,
        \Memcached $memcached,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->communicator = $communicator;
        $this->connection = $connection;
        $this->s3Client = $s3Client;
        $this->parserNotice = $parserNotice;
        $this->em = $em;
        $this->memcached = $memcached;

        $this->currentUserId = $tokenStorage->getToken()->getUser()->getUserid();
        $this->currentUserLogin = $tokenStorage->getToken()->getUser()->getLogin();
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("", name="aw_manager_itineraryCheckError")
     */
    public function itineraryCheckErrorAction(Request $request)
    {
        $params = $this->parseQuery($request);
        $rows = $this->getData($params);

        if (is_bool($rows)) {
            return null; // ???
        }

        $url = trim(preg_replace("/page=\d+\&?/", "", $request->getRequestUri()), "?&");

        if (stripos($url, "?") === false) {
            $url .= "?";
        } else {
            $url .= "&";
        }
        $pages = [
            "first" => $url . "page=1",
            "prev" => $url . "page=" . ($params['page'] - 1),
            "current" => $params['page'],
            "next" => $url . "page=" . ($params['page'] + 1),
        ];

        if (count($rows) < self::PAGE_SIZE) {
            unset($pages['next']);
        }

        if ($params['page'] <= 2) {
            unset($pages['first']);
        }

        if ($params['page'] == 1) {
            unset($pages['prev']);
        }

        global $arProviderState;
        $response = $this->render('@AwardWalletMain/Manager/Support/Itinerary/itineraryCheckError.html.twig', [
            "data" => $rows,
            "arProviderState" => $arProviderState,
            "inputValues" => $params,
            "statuses" => $this->getStatus(),
            "errorTypes" => $this->getErrorType(),
            "sortExt" => $params['sortExt'] ?? 'Counts',
            "sortExtNext" => (isset($params['sortExt']) && $params['sortExt'] === 'Accounts') ? 'Counts' : 'Accounts',
            "pages" => $pages,
            "currentUser" => $this->currentUserId,
        ]);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("/details", name="aw_manager_itineraryCheckError_details")
     */
    public function itineraryCheckErrorDetailsAction(Request $request)
    {
        $params = $this->parseQueryDetails($request);
        $referer = $request->server->get('HTTP_REFERER');

        if (isset($referer) && strpos($referer, '/details') === false) {
            $backLink = $referer;
        } else {
            $backLink = $request->query->get('back', $this->generateUrl('aw_manager_itineraryCheckError'));
        }

        $rows['main'] = $this->getDataDetailsMain($params);

        if (empty($rows['main'])) {
            return $this->redirect($this->generateUrl('aw_manager_itineraryCheckError'));
        }
        global $arProviderState;
        $response = $this->render('@AwardWalletMain/Manager/Support/Itinerary/itineraryCheckErrorDetails.html.twig', [
            "back" => $backLink,
            "data" => $rows,
            "arProviderState" => $arProviderState,
            "inputValues" => $params, // ['providerId' => $providerId, 'date' => $date],
            "currentUser" => $this->currentUserId,
        ]);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("/loadErrors", name="aw_manager_itineraryCheckError_loadErrors", methods={"POST"})
     */
    public function loadErrorsAction(Request $request)
    {
        $params = [];

        foreach ([
            'detectionDate',
            'date',
            'providerId',
            'errorType',
            'status',
            'comment',
            'accountId',
            'UserId',
        ] as $filter) {
            $params[$filter] = $request->get($filter, '');
        }
        $params['limit'] = $request->get('limit', 20);

        $rows['listError'] = $this->getDataDetailsAll($params);
        $forLogs = [];
        $i = 0;

        foreach ($rows['listError'] as $r) {
            if (!empty($r['file'])) {
                $forLogs[$i][$r['ItineraryCheckErrorID']] = $r['file'];

                if (count($forLogs[$i]) % 15 === 0) {
                    $i++;
                }
            }
        }
        $rows['main'] = [
            'ProviderID' => $params['providerId'],
            'DetectionDate' => $params['date'],
        ];

        return $this->render('@AwardWalletMain/Manager/Support/Itinerary/iceListError.html.twig', [
            "back" => $request->get('back', "#"),
            "data" => $rows,
            "inputValues" => $params,
            "statuses" => $this->getStatus(),
            "errorTypes" => $this->getErrorType(),
            "forLogs" => json_encode($forLogs),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("/loadList", name="aw_manager_itineraryCheckError_loadList", methods={"POST"})
     */
    public function loadListAction(Request $request)
    {
        $type = $request->get('type');
        $limit = $request->get('limit1', 10);
        $providerId = $request->get('providerId');
        $fromCache = ($request->get('fromCache', false) === 'true') ? true : false;

        if (empty($providerId) || empty($type)) {
            return new Response('');
        }

        switch ($type) {
            case 'Its':
                $rows = $this->getDataDetailsIts($providerId, $limit, $fromCache);

                break;

            case 'WithoutIts':
                $rows = $this->getDataDetailsWithoutIts($providerId, $limit, $fromCache);

                break;

            case 'NoIts':
                $rows = $this->getDataDetailsNoIts($providerId, $limit, $fromCache, $fromCache);

                break;

            default:
                return new Response('');
        }

        return $this->render('@AwardWalletMain/Manager/Support/Itinerary/iceList.html.twig', [
            "list" => $rows,
            "type" => $type,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("/getLog", name="aw_manager_itineraryCheckError_getLog", methods={"POST"})
     * @return JsonResponse
     */
    public function getLogAction(Request $request)
    {
        $data = $request->get('info');

        if (!isset($data)) {
            return new JsonResponse(['title' => "", 'html' => ""]);
        }

        $data = htmlspecialchars_decode($data);
        $data = json_decode($data, true);
        $arrData = $this->createDataForLog($data);

        return new JsonResponse($arrData);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("/getLogs", name="aw_manager_itineraryCheckError_getLogs", methods={"POST"})
     * @return JsonResponse
     */
    public function getLogsAction(Request $request)
    {
        $info = $request->get('info');

        if (!isset($info)) {
            return new JsonResponse('wrong data', 202);
        }
        $arrData = [];

        foreach ($info as $id => $data) {
            $arrData[$id] = $this->createDataForLog($data);
        }

        return new JsonResponse($arrData);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("/assignee", name="aw_manager_itineraryCheckError_assignee", methods={"POST"})
     * @return JsonResponse
     */
    public function setAssigneeAction(Request $request)
    {
        $arrData = [];
        $arrData['output'] = 'null';
        $date = strtotime($request->request->get('detectionDate', ''));

        if (!$date || $date <= strtotime('01/01/1990') || !($id = $request->request->get('providerId'))) {
            return new JsonResponse($arrData);
        }
        $date2 = strtotime("+1 day", $date);

        if ($request->request->get('type') != 0) {
            $this->connection->executeQuery(
                "UPDATE ItineraryCheckError ice SET ice.Assignee = ? WHERE ice.ProviderID = ?",
                [null, $id],
                [\PDO::PARAM_NULL, \PDO::PARAM_INT]
            );
            $arrData['Status'] = 'OK';
            $arrData['type'] = 0;

            return new JsonResponse($arrData);
        }

        $assignee = $this->connection->executeQuery(
            "SELECT MAX(ice.Assignee) FROM ItineraryCheckError ice WHERE ice.ProviderID = ?",
            [$id],
            [\PDO::PARAM_INT]
        )->fetchColumn();

        if (!empty($assignee) && $assignee != $this->currentUserId) {
            $arrData['Status'] = 'ERROR';
            $arrData['Message'] = 'already assigned. see above';

            return new JsonResponse($arrData);
        }

        $sql = "
          SELECT 
            MAX(ice.DetectionDate) as DetectionDate
          FROM ItineraryCheckError ice 
          WHERE ice.ProviderID = ?";
        $lastRow = $this->connection->executeQuery($sql, [$id], [\PDO::PARAM_INT])->fetchColumn();

        if (!$lastRow) {
            return new JsonResponse($arrData);
        }
        $dateLast = strtotime($lastRow);
        $date2Last = strtotime("+1 day", $dateLast);

        $arrData['Status'] = 'OK';
        $arrData['Message'] = '';
        $arrData['type'] = 1;
        $arrData['output'] = $this->currentUserLogin;
        $updQuerySql = "
          UPDATE ItineraryCheckError ice SET ice.Assignee = ?
            WHERE ice.ProviderID = ? AND ((ice.DetectionDate >= ? AND ice.DetectionDate < ?) OR (ice.DetectionDate >= ? AND ice.DetectionDate < ?))
        ";
        $this->connection->executeQuery($updQuerySql,
            [
                $this->currentUserId,
                $id,
                date("Y-m-d", $date),
                date("Y-m-d", $date2),
                date("Y-m-d", $dateLast),
                date("Y-m-d", $date2Last),
            ],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]);

        return new JsonResponse($arrData);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("/details/comment", name="aw_manager_itineraryCheckError_details_comment", methods={"POST"})
     * @return JsonResponse
     */
    public function saveChangesAction(Request $request)
    {// set resolved
        $arrData = [];
        $id = $request->request->get('id');

        if (empty($id)) {
            $arrData['Status'] = 'OK';
            $arrData['Message'] = '';

            return new JsonResponse($arrData);
        }
        $comment = $request->request->get('comment');

        if ($comment === 'null') {
            $arrData['Status'] = 'CANCEL';

            return new JsonResponse($arrData);
        }

        $idList = [$id];
        $all = $request->request->get('all');

        if (isset($all) && $all === 'true') {
            $rowData = $this->connection->executeQuery("SELECT DetectionDate, AccountID, ProviderID FROM ItineraryCheckError WHERE ItineraryCheckErrorID = ?",
                [$id],
                [\PDO::PARAM_INT])->fetchAll(\PDO::FETCH_ASSOC);
            $rowData = $rowData[0];

            if ($rowData['AccountID'] === null) {
                $idList = $this->connection->executeQuery("SELECT ItineraryCheckErrorID FROM ItineraryCheckError WHERE DetectionDate = ? AND AccountID IS NULL AND ProviderID = ?",
                    [$rowData['DetectionDate'], $rowData['ProviderID']],
                    [\PDO::PARAM_STR, \PDO::PARAM_INT])->fetchAll(\PDO::FETCH_COLUMN);
            } else {
                $idList = $this->connection->executeQuery("SELECT ItineraryCheckErrorID FROM ItineraryCheckError WHERE DetectionDate = ? AND AccountID = ? AND ProviderID = ?",
                    [$rowData['DetectionDate'], $rowData['AccountID'], $rowData['ProviderID']],
                    [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT])->fetchAll(\PDO::FETCH_COLUMN);
            }
        }
        $comment = "[" . date('Y-m-d H:i') . "]: " . $comment . " (" . $this->currentUserLogin . ")\n";

        $sql = "UPDATE ItineraryCheckError SET Status = ?, Comment = IFNULL(CONCAT(Comment, ?), ?)  WHERE ItineraryCheckErrorID in (?)";
        $this->connection->executeQuery($sql, [ItineraryCheckError::STATUS_RESOLVED, $comment, $comment, $idList],
            [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]);

        $sql = "SELECT Comment FROM ItineraryCheckError WHERE ItineraryCheckErrorID = ?";
        $arrData['Status'] = 'OK';
        $arrData['Message'] = [];

        foreach ($idList as $id) {
            $arrData['Message'][$id] = $this->connection->executeQuery($sql, [$id], [\PDO::PARAM_INT])->fetchColumn();
        }

        return new JsonResponse($arrData);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ITINERARYCHECKERROR')")
     * @Route("/details/check", name="aw_manager_itineraryCheckError_check_data", methods={"POST"})
     * @return JsonResponse
     */
    public function checkData(Request $request)
    {
        $params = [];

        foreach (['date', 'providerId'] as $filter) {
            $params[$filter] = $request->request->get($filter, '');
        }
        $data = $this->getDataDetailsMain($params);

        if (!empty($data)) {
            $arrData['Status'] = 'OK';
        } else {
            $arrData['Status'] = 'ERROR';
        }

        return new JsonResponse($arrData);
    }

    private function createDataForLog(array $data): array
    {
        $partner = $data['partner'] ?? 'awardwallet';
        $file = $this->getLog($data['code'], $data['acc'], $data['date'], $data['request'], $partner, true);

        if (empty($file)) {
            $title = "Log";
            $html = "No logs found";
        } else {
            $title = "Log";

            if (abs(strtotime($file["date"]) - $data['date']) < 35) {
                $txt = "Log - " . substr($file['date'], 11, 5);
            } else {
                $txt = "Latest";
            }
            $html = "<a href='" . $this->router->generate("aw_manager_loyalty_logs_item", ["cluster" => $file["cluster"], "filename" => $file["filename"]]) . "' target='_blank'>{$txt}</a>";
        }

        return [
            'title' => $title,
            'html' => $html,
        ];
    }

    private function getErrorType()
    {
        $array = [];

        foreach (ItineraryCheckError::$errorDescription as $key => $value) {
            $array[] = ['ID' => $key, 0 => $key, 'Description' => $value, 1 => $value];
        }

        return $array;
    }

    private function getStatus()
    {
        $array = [];

        foreach (ItineraryCheckError::$statusDescription as $key => $value) {
            $array[] = ['ID' => $key, 0 => $key, 'Description' => $value, 1 => $value];
        }

        return $array;
    }

    private function parseQuery($request)
    {
        $params = [];

        foreach ([
            'detectionDate',
            'providerCode',
            'providerName',
            'providerState',
            'sort',
            'direction',
            'errorType',
            'status',
            'comment',
        ] as $filter) {
            $params[$filter] = $request->query->get($filter, '');
        }

        if ($params['providerState'] === '') {
            unset($params['providerState']);
        }

        foreach (['limit', 'offset'] as $filter) {
            $params[$filter] = trim($request->query->get($filter, 0));
        }

        if ($params['sort'] === 'assignee') {// assignee top
            $params['direction'] = 'desc';
        }
        $sortExt = $request->query->get('sortExt', '');

        if (!empty($sortExt)) {
            $params['sortExt'] = $sortExt;
        }

        $params['limit'] = min($params['limit'], 1000);

        $params['page'] = (int) $request->query->get('page', 1);

        return $params;
    }

    private function parseQueryDetails($request)
    {
        $params = [];

        foreach ([
            'detectionDate',
            'date',
            'providerId',
            'errorType',
            'status',
            'comment',
            'accountId',
            'UserId',
        ] as $filter) {
            $params[$filter] = $request->query->get($filter, '');
        }

        if (empty($params['date'])) {
            $params['date'] = date("m/d/Y", time());
        }
        $params['limit'] = $request->query->get('limit', 20);

        return $params;
    }

    private function getIconExtensionProvider($checkInBrowser, $checkInMobileBrowser): string
    {
        $extCheckIndicator = '';
        $isMixed = $checkInBrowser == CHECK_IN_MIXED;
        $isMobile = $checkInMobileBrowser == 1;

        if ($isMixed || $isMobile) {
            $extCheckText = '';

            if ($isMixed && $isMobile) {
                $extCheckText = 'desktop + mobile';
            } elseif ($isMixed) {
                $extCheckText = 'desktop';
            } elseif ($isMobile) {
                $extCheckText = 'mobile';
            }
            $extCheckIndicator = "<i id='icon-ext' title='{$extCheckText}'></i>";
        }

        return $extCheckIndicator;
    }

    /**
     * @return bool|array
     */
    private function getData($params)
    {
        // refresh assignee
        $sql = "
          SELECT 
            ice.ProviderID, ice.Assignee
          FROM ItineraryCheckError ice
		  WHERE ice.Assignee > 0
          GROUP BY ice.ProviderID, ice.Assignee";
        $assignee = $this->connection->executeQuery($sql)->fetchAll();
        $this->connection->executeQuery("UPDATE ItineraryCheckError ice SET ice.Assignee = NULL");
        $usrRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $assignedProviders = [];

        foreach ($assignee as $row) {
            $sql = "SELECT MAX(ice.DetectionDate) as DetectionDate FROM ItineraryCheckError ice WHERE ice.ProviderID = ?";
            $maxDetectionDate = $this->connection->executeQuery($sql, [$row['ProviderID']],
                [\PDO::PARAM_INT])->fetchColumn();
            $dateLast = strtotime($maxDetectionDate);
            $date2Last = strtotime("+1 day", $dateLast);
            $this->connection->executeQuery(
                "UPDATE ItineraryCheckError ice SET ice.Assignee = ? WHERE ice.DetectionDate >= ? AND ice.DetectionDate < ? AND ice.ProviderID = ?",
                [$row['Assignee'], date("Y-m-d", $dateLast), date("Y-m-d", $date2Last), $row['ProviderID']],
                [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT]
            );

            if (!empty($params['detectionDate'])) {
                $assignedProviders[$row['ProviderID']] = [
                    'Assignee' => $row['Assignee'],
                    'AssigneeLogin' => $usrRep->find($row['Assignee'])->getLogin(),
                ];
            }
        }

        // region make Query
        $sql = "
          SELECT
            DATE_FORMAT(ice.DetectionDate, '%m/%d/%Y') as DetectionDate
            ,DATE(ice.DetectionDate) as DetectionDateSort
            ,ice.ProviderID
            ,MAX(p.State) as ProviderState
            ,MAX(p.Accounts) as Accounts
            ,MAX(p.Code) as Code
            ,MAX(p.DisplayName) as DisplayName 
            ,MAX(p.CheckInBrowser) AS CheckInBrowser
            ,MAX(p.CheckInMobileBrowser) AS CheckInMobileBrowser
            ,GROUP_CONCAT(ice.ErrorType) as grpErrorType
            ,GROUP_CONCAT(ice.ErrorMessage) as grpErrorMessage
            ,GROUP_CONCAT(ice.Status) as grpStatus
            ,GROUP_CONCAT(ice.Comment) as grpComment
            ,MAX(ice.Assignee) as Assignee
            ,MAX(u.Login) as AssigneeLogin
            ,MAX(IF (ice.Assignee>0, 1, 0)) as AssigneeSort
            ,COUNT(*) as cntErrors
          FROM ItineraryCheckError ice
          LEFT JOIN Provider p ON (ice.ProviderID = p.ProviderID)
          LEFT JOIN Usr u ON (u.UserID = ice.Assignee)
        ";
        $strGroup = "
          GROUP BY 1,2,3";

        $filter = [];
        $queryParams = [];
        $types = [];

        if (isset($params['detectionDate']) && !empty($params['detectionDate'])) {
            $date1 = strtotime($params['detectionDate']);

            if ($date1) {
                $date2 = strtotime("+1 day", $date1);
                $filter[] = "ice.DetectionDate >= ? ";
                $queryParams[] = date("Y-m-d", $date1);
                $types[] = \PDO::PARAM_STR;
                $filter[] = "ice.DetectionDate < ? ";
                $queryParams[] = date("Y-m-d", $date2);
                $types[] = \PDO::PARAM_STR;
            }
        }

        if (isset($params['providerCode']) && !empty($params['providerCode'])) {
            $filter[] = "p.Code like ? ";
            $queryParams[] = "%" . $params['providerCode'] . "%";
            $types[] = \PDO::PARAM_STR;
        }

        if (isset($params['providerName']) && !empty($params['providerName'])) {
            $filter[] = "p.DisplayName like ? ";
            $queryParams[] = "%" . $params['providerName'] . "%";
            $types[] = \PDO::PARAM_STR;
        }

        if (isset($params['providerState'])) {
            $filter[] = "p.State = ? ";
            $queryParams[] = $params['providerState'];
            $types[] = \PDO::PARAM_INT;
        }

        if (isset($params['comment']) && !empty($params['comment'])) {
            $sepComment = array_map("trim", explode("&", $params['comment']));

            foreach ($sepComment as $com) {
                $filter[] = "ice.Comment like ? ";
                $queryParams[] = "%" . $com . "%";
                $types[] = \PDO::PARAM_STR;
            }
        }

        if (isset($params['errorType']) && !empty($params['errorType'])) {
            $filter[] = "ice.ErrorType = ? ";
            $queryParams[] = $params['errorType'];
            $types[] = \PDO::PARAM_INT;
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $filter[] = "ice.Status = ? ";
            $queryParams[] = $params['status'];
            $types[] = \PDO::PARAM_INT;
        }

        if (isset($params['page'])) {
            $page = $params['page'];
        } else {
            $page = 1;
        }
        $params['limit'] = self::PAGE_SIZE;
        $params['offset'] = ($page - 1) * self::PAGE_SIZE;

        $strWhere = "";

        if (count($filter) > 0) {
            $strWhere = " WHERE " . implode(" AND ", $filter);
        }

        $sql .= $strWhere;
        $sql .= $strGroup;

        if (isset($params['sortExt']) && $params['sortExt'] === 'Accounts') {
            $sortExt = $params['sortExt'] . ' DESC';
        } else {
            $params['sortExt'] = 'Counts';
            $sortExt = 'cntErrors DESC, Accounts DESC';
        }

        if (isset($params['sort'], $params['direction'])) {
            $sort = $params['sort'];
            $direction = $params['direction'];
            $sortArr = [
                "detectionDate" => "DetectionDateSort",
                "providerCode" => "Code",
                "providerName" => "DisplayName",
            ];

            if (isset($sortArr[$sort]) && in_array($direction, ['desc', 'asc'])) {
                $orderBy = ' ORDER BY ' . $sortArr[$sort] . ' ' . $direction;
                $sql .= $orderBy;
            } elseif ($sort === 'assignee' && in_array($direction, ['desc', 'asc'])) {
                $sql .= ' ORDER BY AssigneeSort ' . $direction . ', DetectionDateSort DESC, ' . $sortExt;
            } else {
                $sql .= ' ORDER BY DetectionDateSort DESC, ' . $sortExt;
            }
        }

        $limit = (int) $params['limit'];
        $offset = (int) $params['offset'];

        if ($offset > 0 && $limit === 0) {
            return false;
        }

        if ($limit > 0) {
            $sql .= " limit $offset, $limit";
        }
        // endregion
        $fromCache = true;

        if (!isset($date2) || $date2 > strtotime(date("Y-m-d"))) {
            [$fromCache, $newParserErrors] = $this->parserNotice->search();
        } else {
            $newParserErrors = [];
        }

        if (!$fromCache && !empty($newParserErrors)) {
            $this->mergeDataWithDB($newParserErrors);
        }

        $result = $this->connection->executeQuery($sql, $queryParams, $types)->fetchAll(\PDO::FETCH_ASSOC);

        $provs = array_keys($assignedProviders);

        foreach ($result as &$row) {
            if (!empty($row['grpErrorType'])) {
                $strErrors = [];
                $strStats = [];
                $dataStats = [];
                $errors = array_unique(explode(",", $row['grpErrorType']));

                foreach ($errors as $error) {
                    if (isset(ItineraryCheckError::$errorDescription[$error])) {
                        $errorTxt = ItineraryCheckError::$errorDescription[$error];
                    } else {
                        $errorTxt = $error;
                    }
                    $strErrors[] = $errorTxt;

                    // TODO remake. it's work while codes is digit
                    if (!empty($error)) {
                        $strStats[] = '<span>' . $errorTxt . '</span>&nbsp;&nbsp;&nbsp;<span style="float: right">' . substr_count($row['grpErrorType'], $error) . '</span>';
                        $dataStats[str_replace(' ', '-', $error)] = substr_count($row['grpErrorType'], $error);
                    } else {
                        $strStats[] = '<span>' . $errorTxt . '</span>&nbsp;&nbsp;&nbsp;<span style="float: right">N/A</span>';
                        $dataStats[str_replace(' ', '-', $error)] = 'N/A';
                    }
                }
                $row['statErrorType'] = implode("<br/>", $strStats);
                $row['grpErrorType'] = implode("\n", $strErrors);
                $row['dataStats'] = $dataStats;
            }

            if (!empty($row['grpStatus'])) {
                $strErrors = [];
                $errors = array_unique(explode(",", $row['grpStatus']));

                foreach ($errors as $error) {
                    if (isset(ItineraryCheckError::$statusDescription[$error])) {
                        $strErrors[] = ItineraryCheckError::$statusDescription[$error];
                    } else {
                        $strErrors[] = $error;
                    }
                }
                $row['grpStatus'] = implode("\n", $strErrors);
            }

            if (!empty($row['grpComment'])) {
                while (preg_match('/(\[.+\)).*?\n(\1)/s', $row['grpComment'])) {
                    $row['grpComment'] = preg_replace('/(\[.+\))(.*?)(\1)/s', '$1$2', $row['grpComment']);
                }
                $row['grpComment'] = trim(str_replace("\n,", '', $row['grpComment']));
            }

            if (empty($row['Assignee']) && in_array($row['ProviderID'], $provs)) {
                $row['Assignee'] = $assignedProviders[$row['ProviderID']]['Assignee'];
                $row['AssigneeLogin'] = $assignedProviders[$row['ProviderID']]['AssigneeLogin'];
            }
            $row['extCheckIndicator'] = $this->getIconExtensionProvider($row['CheckInBrowser'], $row['CheckInMobileBrowser']);
        }

        return $result;
    }

    // merge Data from kibana and DB
    private function mergeDataWithDB(?array $new = [])
    {
        foreach ($new as $row) {
            // hard code
            $requestId = $row['RequestId'] ?? null;
            $this->saveErrorToDatabase($row['ProviderID'], $row['AccountId'], ItineraryCheckError::PARSER_NOTICE,
                $row['ErrorMessage'], $requestId, $row['Partner'], $row['DetectionDate']);
        }

        return true;
    }

    /**
     * @return bool|array
     */
    private function getDataDetailsMain($params)
    {
        $providerId = $params['providerId'];
        $date = $params['date'];

        $key = 'it_check_error_main_' . md5($providerId . $date);
        $data = $this->memcached->get($key);

        if (!empty($data)) {
            return $data;
        }

        if (!(isset($date) && !empty($date) && isset($providerId) && !empty($providerId))) {
            return [];
        }
        $date1 = strtotime($date);

        if (!$date1) {
            return [];
        }

        $assignee = $this->connection->executeQuery(
            "SELECT MAX(ice.Assignee) FROM ItineraryCheckError ice WHERE ice.ProviderID = ?",
            [$providerId],
            [\PDO::PARAM_INT]
        )->fetchColumn();

        if (!$assignee) {
            $assignee = 'NULL';
        }
        $sql = "
          SELECT
            '" . date("m/d/Y", $date1) . "' as DetectionDate
            ,'" . date("Y-m-d", $date1) . "' as DetectionDateSort
            ,ice.ProviderID
            ,MAX(p.State) AS ProviderState
            ,MAX(p.Code) AS Code
            ,MAX(p.DisplayName) AS DisplayName
            ,MAX(p.CheckInBrowser) AS CheckInBrowser
            ,MAX(p.CheckInMobileBrowser) AS CheckInMobileBrowser
            ,{$assignee} as Assignee
            ,MAX(u.Login) as AssigneeLogin
          FROM ItineraryCheckError ice
          LEFT JOIN Provider p ON (ice.ProviderID = p.ProviderID)
          LEFT JOIN Usr u ON (u.UserID = {$assignee})
        ";
        $strGroup = "
          GROUP BY 1,2,3";

        $filter = [];
        $queryParams = [];
        $types = [];

        $date2 = strtotime("+1 day", $date1);
        $date1 = strtotime("-14 days", $date2);

        $filter[] = "ice.DetectionDate >= ? ";
        $queryParams[] = date("Y-m-d", $date1);
        $types[] = \PDO::PARAM_STR;

        $filter[] = "ice.DetectionDate < ? ";
        $queryParams[] = date("Y-m-d", $date2);
        $types[] = \PDO::PARAM_STR;

        $filter[] = "ice.ProviderID = ? ";
        $queryParams[] = $providerId;
        $types[] = \PDO::PARAM_INT;

        $strWhere = "";

        if (count($filter) > 0) {
            $strWhere = " WHERE " . implode(" AND ", $filter);
        }

        $sql .= $strWhere;
        $sql .= $strGroup;

        $result = $this->connection->executeQuery($sql, $queryParams, $types)->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) !== 1) {
            return [];
        }
        $result = $result[0];
        $result['extCheckIndicator'] = $this->getIconExtensionProvider($result['CheckInBrowser'], $result['CheckInMobileBrowser']);

        $this->memcached->set($key, $result, 30);

        return $result;
    }

    /**
     * @return bool|array
     */
    private function getDataDetailsAll(array $params)
    {
        $providerId = $params['providerId'];
        $date = $params['date'];
        $limit = $params['limit'];

        if (empty($date) || empty($providerId) || !($date1 = strtotime($date))) {
            return [];
        }

        // region strWhere
        $filter = [];
        $queryParams = [];
        $types = [];

        if (isset($params['detectionDate']) && !empty($params['detectionDate']) && ($date2 = strtotime($params['detectionDate']))) {
            $date1 = $date2;
            $date2 = strtotime("+1 day", $date1);

            $filter[] = "ice.DetectionDate >= ? ";
            $queryParams[] = date("Y-m-d", $date1);
            $types[] = \PDO::PARAM_STR;

            $filter[] = "ice.DetectionDate < ? ";
            $queryParams[] = date("Y-m-d", $date2);
            $types[] = \PDO::PARAM_STR;
        } else {
            $date2 = strtotime("-14 days", $date1);

            $filter[] = "DATE(ice.DetectionDate) <= ? ";
            $queryParams[] = date("Y-m-d", $date1);
            $types[] = \PDO::PARAM_STR;

            if ((!isset($params['accountId']) || empty($params['accountId']))
                && (!isset($params['UserId']) || empty($params['UserId']))
            ) {
                $filter[] = "DATE(ice.DetectionDate) > ? ";
                $queryParams[] = date("Y-m-d", $date2);
                $types[] = \PDO::PARAM_STR;
            }
        }
        $filter[] = "ice.ProviderID = ? ";
        $queryParams[] = $providerId;
        $types[] = \PDO::PARAM_INT;

        if (isset($params['errorType']) && !empty($params['errorType'])) {
            $filter[] = "ice.ErrorType = ? ";
            $queryParams[] = $params['errorType'];
            $types[] = \PDO::PARAM_INT;
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $filter[] = "ice.Status = ? ";
            $queryParams[] = $params['status'];
            $types[] = \PDO::PARAM_INT;
        }

        if (isset($params['accountId']) && !empty($params['accountId'])) {
            $filter[] = "ice.AccountID = ? ";
            $queryParams[] = $params['accountId'];
            $types[] = \PDO::PARAM_INT;
        }

        if (isset($params['UserId']) && !empty($params['UserId'])) {
            $filter[] = "acc.UserID = ? ";
            $queryParams[] = $params['UserId'];
            $types[] = \PDO::PARAM_INT;
        }

        $strWhere = "";

        if (count($filter) > 0) {
            $strWhere = " WHERE " . implode(" AND ", $filter);
        }

        // endregion

        // region make Query
        if ((!isset($params['errorType']) || empty($params['errorType']))
            && (!isset($params['detectionDate']) || empty($params['detectionDate']))
            && (!isset($params['accountId']) || empty($params['accountId']))
            && (!isset($params['UserId']) || empty($params['UserId']))
        ) {
            $maxRows = intdiv($limit, 3);
            $sql = "
            SELECT 
              ice.ItineraryCheckErrorID
              ,MAX(ice.DetectionDate) as DetectionDate
              ,ice.ProviderID
              ,ice.AccountID
              ,ice.RequestID
              ,ice.Partner
              ,ice.ErrorType
              ,ice.ErrorMessage
              ,ice.Status
              ,ice.ConfirmationNumber
              ,MAX(ice.Comment) as Comment
              ,p.Code
              ,acc.UserID as UserId
              ,acc.SavePassword
            FROM (
	          SELECT 
                @row_number := CASE 
                  WHEN @last_type <> x.ErrorType OR @last_date <> x.grDate THEN 1 
                  ELSE @row_number + 1 END 
                AS `row_number`,
                @last_type := x.ErrorType,
                @last_date := x.grDate,
                x.*
              FROM (
                SELECT DATE(ice.DetectionDate) AS grDate, ice.* 
                FROM ItineraryCheckError ice 
                {$strWhere}
                ORDER BY DATE(ice.DetectionDate) DESC, ice.ErrorType 
              ) x
              CROSS JOIN (SELECT @row_number := 0, @last_type := null, @last_date := null) y
              ORDER BY x.grDate desc,x.ErrorType 
	        ) ice
            LEFT JOIN Account acc ON (acc.AccountID = ice.AccountID)
            LEFT JOIN Provider p ON (ice.ProviderID = p.ProviderID)
            WHERE ice.row_number <= {$maxRows}
            ";
        } else {
            $sql = "
          SELECT
            ice.ItineraryCheckErrorID
            ,MAX(ice.DetectionDate) as DetectionDate
            ,ice.ProviderID
            ,ice.AccountID
            ,ice.RequestID
            ,ice.Partner
            ,ice.ErrorType
            ,ice.ErrorMessage
            ,ice.Status
            ,ice.ConfirmationNumber
            ,MAX(ice.Comment) as Comment
            ,p.Code
            ,acc.UserID as UserId
            ,acc.SavePassword
          FROM ItineraryCheckError ice
          LEFT JOIN Account acc ON (acc.AccountID = ice.AccountID)
          LEFT JOIN Provider p ON (ice.ProviderID = p.ProviderID)
        ";
            $sql .= $strWhere;
        }
        $strGroup = "
          GROUP BY
            DATE(ice.DetectionDate),
            ice.ItineraryCheckErrorID,
            ice.ProviderID,
            p.Code,
            acc.UserID,
            acc.SavePassword,
            ice.AccountID,
            ice.RequestID,
            ice.Partner,
            ice.ErrorType,
            ice.ErrorMessage,
            ice.Status,
            ice.ConfirmationNumber

        ";
        $sql .= $strGroup;
        $sql .= " ORDER BY DetectionDate DESC LIMIT " . $limit;
        // endregion

        $result = $this->connection->executeQuery($sql, $queryParams, $types)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as &$row) {
            if (!empty($row['ErrorType'])) {
                if (isset(ItineraryCheckError::$errorDescription[$row['ErrorType']])) {
                    $row['ErrorType'] = ItineraryCheckError::$errorDescription[$row['ErrorType']];
                }
            }

            if (!empty($row['Status'])) {
                if (isset(ItineraryCheckError::$statusDescription[$row['Status']])) {
                    $row['Status'] = ItineraryCheckError::$statusDescription[$row['Status']];
                }
            }
            $row['file'] = [];

            if ((!empty($row['AccountID']) || !empty($row['RequestID'])) && ($date = strtotime($row['DetectionDate']))) {
                $row['file'] = [
                    'code' => $row['Code'],
                    'acc' => $row['AccountID'],
                    'date' => $date,
                    'request' => $row['RequestID'],
                    'partner' => $row['Partner'],
                ];
            }
        }

        return $result;
    }

    /**
     * @param int $providerId
     * @return bool|array
     */
    private function getDataDetailsNoIts($providerId, $limit = 10, $fromCache)
    {
        $key = 'it_check_error_no_its_' . md5($providerId . $limit);
        $data = $this->memcached->get($key);

        if ($fromCache && !empty($data)) {
            return $data;
        }

        $sql = "
    		SELECT 
    		    a.AccountID
    		    ,p.ProviderID
    		    ,p.Code
    		    ,a.UserID
    		    ,a.UpdateDate
	    	FROM Provider p
		    	JOIN Account a ON a.ProviderID = p.ProviderID
		    WHERE 
    			a.Itineraries = -1
	    		AND p.ProviderID = ?
			    AND p.CanCheck = 1
			    AND p.CanCheckItinerary = 1
			    AND a.State = 1
			    AND a.ErrorCode = 1
			    AND a.SavePassword = 1
		    ORDER BY a.UpdateDate DESC
		    LIMIT ?
        ";

        $result = $this->connection->executeQuery($sql, [$providerId, $limit],
            [\PDO::PARAM_INT, \PDO::PARAM_INT])->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as &$row) {
            $row['file'] = [];

            if (!empty($row['AccountID']) && ($date = strtotime($row['UpdateDate']))) {
                $row['file'] = [
                    'code' => $row['Code'],
                    'acc' => $row['AccountID'],
                    'date' => $date,
                    'request' => null,
                ];
            }
        }

        $this->memcached->set($key, $result, self::TTL);

        return $result;
    }

    /**
     * @return bool|array
     */
    private function getDataDetailsIts(int $providerId, ?int $limit = 10, $fromCache)
    {
        $key = 'it_check_error_its_' . md5($providerId . $limit);
        $data = $this->memcached->get($key);

        if ($fromCache && !empty($data)) {
            return $data;
        }

        $accTrip = $this->getAccountsParsed('Trip', $providerId, $limit);
        $accHotel = $this->getAccountsParsed('Reservation', $providerId, $limit);
        $accRental = $this->getAccountsParsed('Rental', $providerId, $limit);
        $accEvent = $this->getAccountsParsed('Restaurant', $providerId, $limit);
        $accParking = $this->getAccountsParsed('Parking', $providerId, $limit);

        $accountList = array_merge($accTrip, $accHotel, $accRental, $accEvent, $accParking);

        if (empty($accountList)) {
            $result = [];
            $this->memcached->set($key, $result, self::TTL);

            return $result;
        }
        $sql = "
    		SELECT 
    		    a.AccountID
    		    ,p.ProviderID
    		    ,p.Code
    		    ,a.UserID
    		    ,a.UpdateDate
	    	FROM Provider p
		    	JOIN Account a ON a.ProviderID = p.ProviderID
		    WHERE 
    			a.Itineraries > 0
    			AND a.AccountID IN (?)
	    		AND p.ProviderID = ?
			    AND p.CanCheck = 1
			    AND p.CanCheckItinerary = 1
			    AND a.State = 1
			    AND a.ErrorCode = 1
			    AND a.SavePassword = 1
		    ORDER BY a.UpdateDate DESC
		    LIMIT ?
        ";

        $result = $this->connection->executeQuery($sql, [$accountList, $providerId, $limit],
            [Connection::PARAM_INT_ARRAY, ParameterType::INTEGER, ParameterType::INTEGER])->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as &$row) {
            $row['file'] = [];

            if (!empty($row['AccountID']) && ($date = strtotime($row['UpdateDate']))) {
                $row['file'] = [
                    'code' => $row['Code'],
                    'acc' => $row['AccountID'],
                    'date' => $date,
                    'request' => null,
                ];
            }
        }

        $this->memcached->set($key, $result, self::TTL);

        return $result;
    }

    private function getAccountsParsed(string $table, int $providerId, int $limit = 10): array
    {
        /** @var ItineraryRepositoryInterface $itineraryRep */
        $itineraryRep = $this->em->getRepository(Itinerary::getItineraryClass($table));
        $provider = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($providerId);

        if ($itineraryRep instanceof TripRepository || $itineraryRep instanceof ReservationRepository
            || $itineraryRep instanceof RentalRepository || $itineraryRep instanceof RestaurantRepository
            || $itineraryRep instanceof ParkingRepository
        ) {
            $qb = $itineraryRep->createQueryBuilder('t');
            $qb->select("a.accountid")->distinct();

            if ($itineraryRep instanceof TripRepository) {
                $qb->join('t.segments', 'tripSegments');
            }
            $qb->join('t.account', 'a');
            $criteria = $itineraryRep->getFutureCriteria();
            $criteria->andWhere(Criteria::expr()->neq('t.account', null));
            $criteria->andWhere(Criteria::expr()->eq('t.provider', $provider));
            $criteria->andWhere(Criteria::expr()->eq('t.parsed', 1));
            $criteria->andWhere(Criteria::expr()->gte('t.updateDate', new \DateTime('-' . self::LAST_DAYS . ' days')));

            $qb->addCriteria($criteria);
            $qb->setMaxResults($limit);
            $data = $qb->getQuery()->getResult();
            $data = array_map(function ($s) {
                return $s['accountid'];
            }, $data);

            return $data;
        }

        return [];
    }

    /**
     * @param int $providerId
     * @return bool|array
     */
    private function getDataDetailsWithoutIts($providerId, $limit = 10, $fromCache)
    {
        $key = 'it_check_error_without_its_' . md5($providerId . $limit);
        $data = $this->memcached->get($key);

        if ($fromCache && !empty($data)) {
            return $data;
        }

        $sql = "
    		SELECT 
    		    a.AccountID
    		    ,p.ProviderID
    		    ,p.Code
    		    ,a.UserID
    		    ,a.UpdateDate
	    	FROM Provider p
		    	JOIN Account a ON a.ProviderID = p.ProviderID
		    	JOIN Usr u ON u.UserID = a.UserID
		    WHERE 
    			a.Itineraries = 0
	    		AND p.ProviderID = ?
			    AND p.CanCheck = 1
			    AND p.CanCheckItinerary = 1
			    AND a.State = 1
			    AND a.Disabled = 0
			    AND a.SavePassword = 1
			    AND a.ErrorCode < 2
			    AND u.AutoGatherPlans = 1
		    ORDER BY a.UpdateDate DESC
		    LIMIT ?
        ";

        $result = $this->connection->executeQuery($sql, [$providerId, $limit],
            [\PDO::PARAM_INT, \PDO::PARAM_INT])->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as &$row) {
            $row['file'] = [];

            if (!empty($row['AccountID']) && ($date = strtotime($row['UpdateDate']))) {
                $row['file'] = [
                    'code' => $row['Code'],
                    'acc' => $row['AccountID'],
                    'date' => $date,
                    'request' => null,
                ];
            }
        }

        $this->memcached->set($key, $result, self::TTL);

        return $result;
    }

    private function getLog($providerCode, $accountId, $approximateTime, $requestId = null, $partner = null, $useCache = false)
    {
        if (abs(time() - $approximateTime) > 60 * 60 * 24 * 11) { // log lifetime 10 days
            return [];
        }

        if ($useCache) {
            $key = 'it_check_error_getlog_' . md5(implode('-',
                [$providerCode, $accountId, $approximateTime, $requestId, $partner]));
            $data = $this->memcached->get($key);

            if (!empty($data)) {
                return $data;
            }
        }

        $files = [];

        // Logs from loyalty
        try {
            $allFiles = $this->getLoyaltyCheckerLogs($partner, $accountId, $requestId, $providerCode);

            foreach ($allFiles as $file) {
                $files[] = [
                    'date' => $this->prettyDate($file->getUpdatedate()),
                    'filename' => $file->getFilename(),
                    'cluster' => 'awardwallet',
                ];
            }

            if (!empty($accountId)) {
                // TODO debug
                $this->logger->info("ICE-controller",
                    ['files' => json_encode($files), 'approximateTime' => date('Y-m-d H:i:s', $approximateTime)]);
                $extLogsFiles = $this->getExtensionAccountLogsFromS3($accountId);

                foreach ($extLogsFiles as $file) {
                    $files[] = [
                        'date' => $this->prettyDate($file['LastModified']),
                        'filename' => $file['Key'],
                        'cluster' => 'extension',
                    ];
                }
            }
            usort($files, function ($log1, $log2) {
                return strtotime($log2["date"]) - strtotime($log1["date"]);
            });
        } catch (ApiCommunicatorException $e) {
            $files[] = [
                'date' => date('Y-m-d H:i:s'),
                'filename' => 'bla-bla.test',
                'cluster' => 'awardwallet',
            ];
        }

        $log = [];

        if (count($files) === 1) {
            $log = array_shift($files);
        } elseif (count($files) > 1) {
            foreach ($files as $file) {
                if (abs(strtotime($file["date"]) - $approximateTime) < 35) {
                    $log = $file;

                    break;
                }
            }
        }

        if (isset($key)) {
            $this->memcached->set($key, $log, self::TTL);
        }

        return $log;
    }

    private function prettyDate($date)
    {
        return str_replace(["T", "+00:00", ".000Z"], [" ", "", ""], $date);
    }

    private function getExtensionAccountLogsFromS3($accountId)
    {
        $iterator = $this->s3Client->getIterator('ListObjects',
            ['Bucket' => 'awardwallet-logs', 'Prefix' => "account-{$accountId}-"]);

        $result = [];

        foreach ($iterator as $object) {
            $result[] = $object;
        }

        return $result;
    }

    private function getLoyaltyCheckerLogs($partner, $accountId, $requestId = null, $providerCode = null): array
    {
        if (isset($requestId)) {
            $request = new \AwardWallet\MainBundle\Loyalty\Resources\AdminLogsRequest([
                'partner' => $partner,
                'requestId' => $requestId,
                'method' => 'CheckAccount',
            ]);
        } else {
            $request = new \AwardWallet\MainBundle\Loyalty\Resources\AdminLogsRequest([
                'userData' => $accountId,
                'partner' => $partner,
                'provider' => $providerCode,
                'method' => 'CheckAccount',
            ]);
        }

        /** @var \AwardWallet\MainBundle\Loyalty\Resources\AdminLogsResponse $response */
        $responseAccount = $this->communicator->GetCheckerLogs($request);
        /** @var \AwardWallet\MainBundle\Loyalty\Resources\AdminLogsResponse $response */
        $responseConfirmation = $this->communicator->GetCheckerLogs($request->setMethod('CheckConfirmation'));

        $allFiles = array_merge(
            is_array($responseAccount->getFiles()) ? $responseAccount->getFiles() : [],
            is_array($responseConfirmation->getFiles()) ? $responseConfirmation->getFiles() : []
        );

        return $allFiles;
    }

    private function saveErrorToDatabase(
        int $providerId,
        ?int $accountId,
        int $errorType,
        ?string $errorMsg,
        ?string $requestId,
        ?string $partner,
        int $detectionDate
    ) {
        $error = $this->em->getRepository(\AwardWallet\MainBundle\Entity\ItineraryCheckError::class);
        $providerRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $accountRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);

        $dt = new \DateTime();
        $dt->setTimestamp($detectionDate);

        if (null !== $accountId && $error->checkDuplicatesPerDay($providerId, $accountId, $dt, $errorType, $errorMsg)) {
            // not save. already in DB
            return;
        }

        $providerId = $providerRepository->find($providerId);
        $accountId = $accountId ? $accountRepository->find($accountId) : null;

        if (!isset($providerId)) {
            return;
        }

        /** @var ItineraryCheckError $errorRow */
        $errorRows = $error->findBy([
            'detectiondate' => $dt,
            'providerid' => $providerId,
            'errortype' => $errorType,
            'accountid' => $accountId,
        ], ['detectiondate' => 'DESC'], 1);

        if (empty($errorRows)) {
            $errorRow = new ItineraryCheckError();
            $errorRow->setDetectiondate($dt)
                ->setProviderid($providerId)
                ->setAccountid($accountId)
                ->setErrorType($errorType)
                ->setPartner($partner)
                ->setErrorMessage($errorMsg);

            if (null !== $requestId) {
                $errorRow->setRequestid($requestId);
            }
        } else {
            $errorRow = array_shift($errorRows);
        }

        if (empty($errorRow->getStatus())) {
            $errorRow->setStatus(ItineraryCheckError::STATUS_NEW);
        }

        $this->em->persist($errorRow);
        $this->em->flush();
        $this->em->clear();
    }
}
