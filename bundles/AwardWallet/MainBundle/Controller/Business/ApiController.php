<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountInfo\Info as AccountInfo;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\AccountAccessApi\AuthStateManager;
use AwardWallet\MainBundle\Service\AccountAccessApi\Model\AuthState;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsDateUtils;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use AwardWallet\MainBundle\Service\AmericanAirlinesAAdvantageDetector;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiController extends AbstractController
{
    public const DENY_PROVIDERS = [];
    public const RESTRICTED_AA_FIELDS = ['login', 'balance', 'balanceRaw', 'lastDetectedChange'];
    public const RESTRICTED_MSG = 'restricted';

    public static $accessLevels = [
        ACCESS_READ_NUMBER => "Read numbers",
        ACCESS_READ_BALANCE_AND_STATUS => "Read balances",
        ACCESS_READ_ALL => "Read all",
        ACCESS_WRITE => "Full control",
        ACCESS_ADMIN => "Administrator",
        ACCESS_BOOKING_MANAGER => "Booking Administrator",
        ACCESS_BOOKING_VIEW_ONLY => "Booking View Only",
        ACCESS_NONE => "Regular",
    ];

    private LoggerInterface $securityLogger;
    private BankTransactionsAnalyser $bankTransactionsAnalyser;
    private SpentAnalysisService $spentAnalysisService;
    private EntityManagerInterface $entityManager;
    private AuthStateManager $authStateManager;
    private RouterInterface $router;
    private AntiBruteforceLockerService $antiBruteforceLocker;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private TranslatorInterface $translator;
    private AccountInfo $accountInfo;

    public function __construct(
        LoggerInterface $securityLogger,
        BankTransactionsAnalyser $bankTransactionsAnalyser,
        SpentAnalysisService $spentAnalysisService,
        EntityManagerInterface $entityManager,
        AuthStateManager $authStateManager,
        RouterInterface $router,
        AntiBruteforceLockerService $securityAntibruteforceApiExport,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        TranslatorInterface $translator,
        AccountInfo $accountInfo
    ) {
        $this->securityLogger = $securityLogger;
        $this->bankTransactionsAnalyser = $bankTransactionsAnalyser;
        $this->spentAnalysisService = $spentAnalysisService;
        $this->entityManager = $entityManager;
        $this->authStateManager = $authStateManager;
        $this->router = $router;
        $this->antiBruteforceLocker = $securityAntibruteforceApiExport;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->translator = $translator;
        $this->accountInfo = $accountInfo;
    }

    /**
     * @Security("is_granted('SITE_BUSINESS_AREA')")
     * @Route("/api/export/v1/create-auth-url", name="aw_business_create_auth_url", methods={"POST"})
     * @return JsonResponse
     */
    public function createAuthUrl(Request $request)
    {
        try {
            $business = $this->authenticate($request);
        } catch (HttpException $exception) {
            return (new JsonResponse(['error' => $exception->getMessage()], $exception->getStatusCode()))->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        $params = @json_decode($request->getContent(), true);

        $access = (int) ($params["access"] ?? -1);

        if (!in_array($access, [ACCESS_READ_NUMBER, ACCESS_READ_BALANCE_AND_STATUS, ACCESS_READ_ALL, ACCESS_WRITE])) {
            return new JsonResponse(['error' => "Invalid access"], 400);
        }

        $platform = $params["platform"] ?? null;

        if (!in_array($platform, ["mobile", "desktop"])) {
            return new JsonResponse(['error' => "Invalid platform"], 400);
        }

        $granularSharing = (bool) ($params["granularSharing"] ?? false);

        $authKey = $this->authStateManager->save(new AuthState($access, $business->getUserid(), $params["state"] ?? null));

        if ($platform === "desktop") {
            $url = $this->router->generate("aw_api_connection", ["access" => $access, "granularSharing" => ($granularSharing ? true : null), "authKey" => $authKey, "id" => $business->getRefcode()], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            $url = str_replace("/api/", "/", $this->router->generate("awm_connections_approve", ["accessLevel" => $access, "code" => $business->getRefcode(), "authKey" => $authKey], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return new JsonResponse(["url" => $url]);
    }

    /**
     * @Security("is_granted('SITE_BUSINESS_AREA')")
     * @Route("/api/export/v1/get-connection-info/{code}", name="aw_business_get_connection_info", methods={"GET"})
     * @return JsonResponse
     */
    public function getConnectionInfo(string $code, Request $request)
    {
        try {
            $business = $this->authenticate($request);
        } catch (HttpException $exception) {
            return (new JsonResponse(['error' => $exception->getMessage()], $exception->getStatusCode()))->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        $userId = $this->authStateManager->getAuthUserId($business, $code);

        if ($userId === null) {
            return new JsonResponse(['error' => "Invalid code"], 404);
        }

        return new JsonResponse(['userId' => $userId]);
    }

    /**
     * @Security("is_granted('SITE_BUSINESS_AREA')")
     * @Route("/api/export/v1", name="aw_business_api_no_method", options={"expose"=true})
     * @Route("/api/export/v1/{dataset}", name="aw_business_api", options={"expose"=true})
     * @Route("/api/export/v1/{dataset}/{id}", name="aw_business_api_by_id", options={"expose"=true})
     * @param string|null $dataset
     * @param int|null $id
     * @return JsonResponse
     */
    public function apiAction(Request $request, LoggerInterface $statLogger, ApiCommunicator $apiCommunicator, $dataset = null, $id = null, AntiBruteforceLockerService $accountAccessApiLocker)
    {
        $encoding = JSON_UNESCAPED_UNICODE;

        try {
            $business = $this->authenticate($request);
        } catch (HttpException $exception) {
            return (new JsonResponse(['error' => $exception->getMessage()], $exception->getStatusCode()))->setEncodingOptions($encoding);
        }

        if (!in_array($request->getMethod(), [Request::METHOD_GET])) {
            // 405 Method Not Allowed
            return (new JsonResponse(['error' => 'You tried to access an API with an invalid method'], 405))->setEncodingOptions($encoding);
        }

        if (empty($dataset)) {
            // 400 Bad Request
            return (new JsonResponse(['error' => 'Method required'], 400))->setEncodingOptions($encoding);
        }

        if (!empty(trim($business->getBusinessInfo()->getApiAllowIp())) && !empty($business->getBusinessInfo()->getPublicKey())) {
            $passwordsKey = openssl_get_publickey($business->getBusinessInfo()->getPublicKey());
        } else {
            $passwordsKey = null;
        }

        if (!empty($id) && $id !== 'list' && $dataset !== "providers") {
            $id = intval($id);
        }

        $statLogger->info("account access api hit", ["ip" => $request->getClientIp(), "UserID" => $business->getUserid(), "dataset" => $dataset, "id" => strval($id)]);

        if ($accountAccessApiLocker->checkForLockout("api_export_{$business->getId()}_{$dataset}_{$id}") !== null) {
            $this->securityLogger->info("throttled business api request", ["ip" => $request->getClientIp(), "UserID" => $business->getUserid(), "dataset" => $dataset, "id" => strval($id)]);

            return (new JsonResponse(['error' => 'Too many requests'], 429))->setEncodingOptions($encoding);
        }

        $statLogger->info("api access allowed", ["ip" => $request->getClientIp(), "UserID" => $business->getUserid(), "dataset" => $dataset, "id" => strval($id)]);

        switch ($dataset) {
            case "connectedUser":
                if (empty($id) || $id == 'list') {
                    $response = $this->connectedUserListResponse($business, $request);
                } else {
                    $response = $this->connectedUserAccountsResponse($business, $id, $passwordsKey);
                }

                break;

            case "member":
                if (empty($id) || $id == 'list') {
                    $response = $this->memberListResponse($business, $request);
                } else {
                    $response = $this->memberAccountsResponse($business, $id, $passwordsKey);
                }

                break;

            case "account":
                $response = $this->accountResponse($business, $id, $passwordsKey);

                break;

            case "providers":
                try {
                    if (empty($id) || $id == 'list') {
                        $response = $apiCommunicator->getProvidersList(false);
                    } else {
                        $response = $apiCommunicator->getProviderInfo($id, false);
                    }
                    $response = json_decode($response, true);
                } catch (ApiCommunicatorException $e) {
                    $res = json_decode($e->getMessage(), true);
                    $msg = $res['message'] ?? 'Server error';

                    return new JsonResponse(['error' => $msg], $e->getCode());
                }

                break;

            case "spend-analysis":
                $response = $this->getSpendAnalysis($business);

                break;

            default:
                // 400 Bad Request
                return (new JsonResponse(['error' => 'Unknown method'], 400))->setEncodingOptions($encoding);
        }

        if (!empty($passwordsKey)) {
            openssl_free_key($passwordsKey);
        }

        if ($response instanceof JsonResponse) {
            return $response->setEncodingOptions($encoding);
        }

        return (new JsonResponse($response))->setEncodingOptions($encoding);
    }

    public function camelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = lcfirst($str);

        return $str;
    }

    /**
     * @throws HttpException
     */
    private function authenticate(Request $request): Usr
    {
        $apiKey = $request->headers->get('X-Authentication');

        if (empty($apiKey)) {
            // 403 Forbidden
            throw new HttpException(403, 'Authentication required');
        }

        $business = $this->checkKey($apiKey);

        $ip = $request->getClientIp();

        if ($error = $this->antiBruteforceLocker->checkForLockout($ip, true)) {
            // for error text check bundles/AwardWallet/MainBundle/Resources/config/security.yml
            $this->securityLogger->warning("ip lockout on business api, business: " . ($business ? $business->getId() : "none"));
            $this->antiBruteforceLocker->checkForLockout($ip);

            throw new HttpException(403, $error);
        }

        if (!$business) {
            $this->antiBruteforceLocker->checkForLockout($ip);

            // 401 Unauthorized
            throw new HttpException(401, 'Invalid API Key or API Access disabled in your profile');
        }

        if (!$this->checkAccess($business, $ip)) {
            $this->antiBruteforceLocker->checkForLockout($ip);

            // 401 Unauthorized
            throw new HttpException(401, 'Access Denied as your IP is not white-listed');
        }

        return $business;
    }

    /**
     * @param string $apiKey
     * @return Usr|bool
     */
    private function checkKey($apiKey)
    {
        if (empty($apiKey)) {
            return false;
        }
        $businessInfoRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\BusinessInfo::class);
        $businessInfo = $businessInfoRep->findOneBy(['apiEnabled' => true, 'apiKey' => $apiKey]);

        if ($businessInfo) {
            return $businessInfo->getUser();
        }

        return false;
    }

    private function checkAccess(Usr $business, $ip)
    {
        $allowedIp = preg_split("/\s+/", $business->getBusinessInfo()->getApiAllowIp());

        if (is_array($allowedIp) && count($allowedIp)) {
            if (in_array($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    private function accountResponse(Usr $business, $accountId, $allowReadPasswords)
    {
        if (empty($accountId) || $accountId === 'list') {
            // 400 Bad Request
            return new JsonResponse(['error' => 'Account ID required'], 400);
        }

        $accounts = $this->accountListManager
            ->getAccountList(
                $this->optionsFactory->createExportListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER, $business)
                        ->set(Options::OPTION_DENY_PROVIDERS, self::DENY_PROVIDERS)
                        ->set(Options::OPTION_ACCOUNT_IDS, [$accountId])
                )
            )
            ->getAccounts();

        if (empty($accounts) || !is_array($accounts)) {
            // 404 Not Found
            return new JsonResponse(['error' => 'Account not found'], 404);
        }

        $accountsData = $this->getAccountsData($business, $accounts, $allowReadPasswords, 100000);
        $accountData = $accountsData[0];

        $result = [
            'account' => $accountData,
        ];

        $accountRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $account = $accountRep->find($accountId);

        if ($account->getUser()->getId() == $business->getId()) {
            if (!is_null($account->getUserAgent())) {
                $result['member'] = $this->getMemberData($account->getUserAgent());
            }
        } else {
            $result['connectedUser'] = $this->getConnectedUserData($business->getConnectionWith($account->getUserid()), $business->isBooker());
        }

        return $result;
    }

    private function memberListResponse(Usr $business, $request = null)
    {
        set_time_limit(600);
        $agentRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $connection = $this->getDoctrine()->getConnection();

        $result = [];
        $memberIndex = [];

        $members = $agentRep->findBy(['agentid' => $business->getUserid(), 'clientid' => null]);

        foreach ($members as $member) {
            $id = count($result);
            $data = $this->getMemberData($member);
            $data['accountsIndex'] = [];
            $result[] = $data;
            $memberIndex[$member->getUseragentid()] = $id;
        }

        $accounts = $connection
            ->executeQuery(
                'select a.AccountID, a.UserAgentID, a.LastChangeDate, a.CreationDate, a.UpdateDate
                    from Account a
                    left join Provider p on a.ProviderID = p.ProviderID
                    where a.UserID = ?
                        and ' . $business->getProviderFilter(),
                [$business->getUserid()],
                [\PDO::PARAM_INT])
            ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($accounts as $account) {
            if (empty($account['UserAgentID'])) {
                continue;
            }

            if (!array_key_exists($account['UserAgentID'], $memberIndex)) {
                continue;
            }
            $data = [];
            $data['accountId'] = intval($account['AccountID']);
            $data['lastChangeDate'] = date('c', strtotime($account['LastChangeDate'] ?: $account['CreationDate']));

            if (!empty($account['UpdateDate'])) {
                $data['lastRetrieveDate'] = date('c', strtotime($account['UpdateDate']));
            }

            $result[$memberIndex[$account['UserAgentID']]]['accountsIndex'][] = $data;
        }

        return [
            'members' => $result,
        ];
    }

    private function connectedUserListResponse(Usr $business, $request = null)
    {
        set_time_limit(600);
        $agentRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $connection = $this->getDoctrine()->getConnection();

        $isBooker = $business->isBooker();

        $result = [];
        $connectedUserIndex = [];

        $connectedUsers = $connection
            ->executeQuery(
                "select u.UserID, u.Login, u.AccountLevel, u.Email, " . SQL_USER_NAME . " as FullName,
                        ua.UserAgentID, au.UserAgentID as BackUserAgentID,
                        ua.AccessLevel, ua.IsApproved, ua.ShareByDefault,
                        au.AccessLevel as BackAccessLevel, ua.IsApproved as BackIsApproved, ua.ShareByDefault as BackShareByDefault
                    from UserAgent ua
                    join Usr u on u.UserID = ua.ClientID
                    left join UserAgent au on au.ClientID = ua.AgentID and ua.ClientID = au.AgentID
                    where ua.AgentID = ?
                    and ua.IsApproved = 1",
                [$business->getUserid()],
                [\PDO::PARAM_INT])
            ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($connectedUsers as $connectedUser) {
            $id = count($result);
            $data = $this->getConnectedUserData($connectedUser, $isBooker);
            $data['accountsIndex'] = [];
            $result[] = $data;
            $connectedUserIndex[$connectedUser['UserAgentID']] = $id;
        }

        $ids = array_keys($connectedUserIndex);
        $accounts = $connection
            ->executeQuery(
                'select a.AccountID, ash.UserAgentID, a.LastChangeDate, a.CreationDate, a.UpdateDate
                    from Account a
                    join AccountShare ash on a.AccountID = ash.AccountID
                    left join Provider p on a.ProviderID = p.ProviderID
                    where ash.UserAgentID in (' . implode(', ', $ids) . ')
                        and ' . $business->getProviderFilter()
            )
            ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($accounts as $account) {
            if (!array_key_exists($account['UserAgentID'], $connectedUserIndex)) {
                continue;
            }
            $data = [];
            $data['accountId'] = intval($account['AccountID']);
            $data['lastChangeDate'] = date('c', strtotime($account['LastChangeDate'] ?: $account['CreationDate']));

            if (!empty($account['UpdateDate'])) {
                $data['lastRetrieveDate'] = date('c', strtotime($account['UpdateDate']));
            }

            $result[$connectedUserIndex[$account['UserAgentID']]]['accountsIndex'][] = $data;
        }

        return [
            'connectedUsers' => $result,
        ];
    }

    private function memberAccountsResponse(Usr $business, $memberId, $allowReadPasswords)
    {
        set_time_limit(600);

        if (empty($memberId)) {
            // 400 Bad Request
            return new JsonResponse(['error' => 'Member ID required'], 400);
        }

        //        if (!is_array($memberIds)) $memberIds = [$memberIds];
        //        $memberIds = array_map(function ($id) use ($business) { return $id == 'my' ? $business->getUserid() : intval($id);}, $memberIds);
        //        $memberIds = array_unique($memberIds);
        //        $memberIds = array_filter($memberIds, function ($id) {return !empty($id);});
        $memberId = intval($memberId);

        $agentRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $member = $agentRep->findOneBy(['agentid' => $business->getUserid(), 'clientid' => null, 'useragentid' => $memberId]);

        if (empty($member)) {
            // 404 Not Found
            return new JsonResponse(['error' => 'Member not found'], 404);
        }

        $result = $this->getMemberData($member);

        $accounts = $this->accountListManager
            ->getAccountList(
                $this->optionsFactory->createExportListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER, $business)
                        ->set(Options::OPTION_DENY_PROVIDERS, self::DENY_PROVIDERS)
                        ->set(Options::OPTION_AGENTID, $memberId)
                )
            )
            ->getAccounts();

        $result['accounts'] = $this->getAccountsData($business, $accounts, $allowReadPasswords);

        return $result;
    }

    private function connectedUserAccountsResponse(Usr $business, $userId, $allowReadPasswords)
    {
        set_time_limit(600);

        if (empty($userId)) {
            // 400 Bad Request
            return new JsonResponse(['error' => 'User ID required'], 400);
        }

        //        if (!is_array($memberIds)) $memberIds = [$memberIds];
        //        $memberIds = array_map(function ($id) use ($business) { return $id == 'my' ? $business->getUserid() : intval($id);}, $memberIds);
        //        $memberIds = array_unique($memberIds);
        //        $memberIds = array_filter($memberIds, function ($id) {return !empty($id);});
        $userId = intval($userId);

        $agentRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $connectedUser = $agentRep->findOneBy(['agentid' => $business->getUserid(), 'clientid' => $userId]);

        if (empty($connectedUser)) {
            // 404 Not Found
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $result = $this->getConnectedUserData($connectedUser, $business->isBooker());

        $accounts = $this->accountListManager
            ->getAccountList(
                $this->optionsFactory->createExportListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER, $business)
                        ->set(Options::OPTION_DENY_PROVIDERS, self::DENY_PROVIDERS)
                        ->set(Options::OPTION_AGENTID, $connectedUser->getUseragentid())
                )
            )
            ->getAccounts();

        $result['accounts'] = $this->getAccountsData($business, $accounts, $allowReadPasswords);

        return $result;
    }

    private function getAccountsData(Usr $business, $accounts, $passwordsKey, $historyLimit = 10)
    {
        $kinds = Provider::getKinds();

        foreach ($kinds as &$kind) {
            $kind = $this->translator->trans(/** @Ignore */ $kind);
        }

        $result = [];
        $totalIndex = $rowIndex = 0;

        foreach ($accounts as $row) {
            $accountKey = $rowIndex;
            $code = $row['ProviderCode'];

            if (empty($code) && $this->isAmericanAirlinesAAdvantage($row)) {
                $code = 'aa';
            }

            $account = [
                "accountId" => intval($row['ID']),
                "code" => $code,
                "displayName" => $row["DisplayName"],
                "kind" => $kinds[$row['Kind']],
                "login" => !empty($row["Login"]) ? $row["Login"] : 'n/a',
            ];

            if (!empty($row['Login2'])) {
                $account['login2'] = $row['Login2'];
            }

            $account['autologinUrl'] = $this->router->generate('aw_account_redirect', ['ID' => $row['ID']], UrlGeneratorInterface::ABSOLUTE_URL);
            $account = hideCreditCards($account, $account['kind']);

            if ($row['TableName'] == 'Account') {
                if ($row['CanCheck'] && ($row['ProviderID'] != '')) {
                    $account["updateUrl"] = $this->router->generate('aw_account_edit', ['accountId' => $row['ID'], 'autosubmit' => 1], UrlGeneratorInterface::ABSOLUTE_URL);
                } else {
                    $account["updateUrl"] = 'n/a';
                }

                if ($row['Access']['edit']) {
                    $account["editURL"] = $this->router->generate('aw_account_edit', ['accountId' => $row['ID']], UrlGeneratorInterface::ABSOLUTE_URL);
                } else {
                    $account["editUrl"] = 'n/a';
                    $account["updateUrl"] = 'n/a';
                    $account["autologinUrl"] = 'n/a';
                }

                if ($row['ProviderID'] != '') {
                    $this->loadAccountHistory($account, $historyLimit, $account['accountId']);
                }

                $this->loadAccountInfo($account, $row);

                if ($row['Access']['read_password'] && !empty($passwordsKey)) {
                    $pass = $this->entityManager->getRepository(Account::class)->find($row['ID'])->getPass();
                    openssl_public_encrypt($pass, $crypted, $passwordsKey, OPENSSL_PKCS1_OAEP_PADDING);
                    $crypted = base64_encode($crypted);
                    $account['password'] = $crypted;
                }

                $result[$accountKey] = $account;

                if (!empty($row['SubAccountsArray'])) {
                    $subAccountIndex = 0;
                    $result[$accountKey]['subAccounts'] = [];

                    foreach ($row['SubAccountsArray'] as $subRow) {
                        $subAccount = [];
                        $subAccountKey = $subAccountIndex;
                        $subAccount["subAccountId"] = intval($subRow['SubAccountID']);
                        $subAccount["displayName"] = $account['displayName'] . " - " . $subRow['DisplayName'];
                        $subRow["ProviderID"] = $row["ProviderID"];
                        $this->loadAccountHistory($subAccount, $historyLimit, $account['accountId'], $subAccount["subAccountId"]);
                        $this->loadAccountInfo($subAccount, $subRow);
                        $result[$accountKey]['subAccounts'][$subAccountKey] = $subAccount;
                        $subAccountIndex++;
                    }
                }
                $rowIndex++;
            }

            //            if($row['TableName'] == 'Coupon') {
            //                $this->loadCouponInfo($account, $row);
            //                $account["AutoLoginURL"] = 'n/a';
            //                $account["CheckURL"]     = 'n/a';
            //                if ($row['Access']['edit']) {
            //                    $account["EditURL"] = $this->generateUrl('aw_coupon_edit', ['couponId' => $row['ID']], true);
            //                } else {
            //                    $account["EditURL"] = 'n/a';
            //                }
            //                $result[$userKey]["Accounts"][$accountKey] = $account;
            //                $result[$userKey]["SharedPrograms"]++;
            //                $rowIndex++;
            //            }

            $totalIndex++;
        }

        return $result;
    }

    private function loadAccountHistory(&$account, $limit, $accId, $subAccId = null)
    {
        $Account = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accId);
        $history = $this->accountInfo->getAccountHistory($Account, $limit, 0, false, $subAccId);

        if (!empty($history) && is_array($history) && !empty($history['data'])) {
            [$columns, $types] = array_values($this->accountInfo->getAccountHistoryColumns($Account));
            $account["history"] = [];
            array_reverse($history['data']);

            foreach ($history['data'] as $d) {
                $historyFields = [];

                foreach ($columns as $columnId => $column) {
                    if (
                        array_key_exists($columnId, $types)
                        && array_key_exists($columnId, $d)
                    ) {
                        $historyFields[] = [
                            'name' => $column,
                            'code' => $types[$columnId],
                            'value' => $d[$columnId],
                        ];
                    }
                }
                $account["history"][] = ['fields' => $historyFields];
            }
        }
    }

    private function indexArray($groups)
    {
        $result = [];
        $groupIndex = 0;

        foreach ($groups as $groupName => $groupValue) {
            $groupKey = $groupIndex;
            $result[$groupKey] = ["name" => $groupName];

            if (is_array($groupValue)) {
                if (isset($groupValue['value'])) {
                    $result[$groupKey] = array_merge($result[$groupKey], $groupValue);
                } else {
                    $result[$groupKey]["value"] = $this->indexArray($groupValue);
                }
            } else {
                $result[$groupKey]["value"] = $groupValue;
            }
            $groupIndex++;
        }

        return $result;
    }

    private function loadCouponInfo(&$account, $couponRow)
    {
        $attributes = [
            'Description' => $couponRow['Description'],
            'Value' => $couponRow['Value'],
        ];
        $account['owner'] = !empty($couponRow['FamilyMemberName']) ? $couponRow['FamilyMemberName'] : $couponRow['UserName'];

        if ($couponRow['expirationDate'] != '') {
            $account['expirationDate'] = $couponRow['ExpirationDate'];
            //            $account["expirationState"] = $arFields['ExpirationState'];
        }

        if (!empty($attributes)) {
            $account['properties'] = $this->indexArray($attributes);
        }
    }

    private function loadAccountInfo(&$account, $accountRow)
    {
        // Hide credit cards info
        $accountRow = hideCreditCards($accountRow, $accountRow['Kind']);

        // Account Info
        $attributes = [];
        // correct balance
        $account["balance"] = $accountRow["Balance"] ?? null;
        $account["balanceRaw"] = isset($accountRow["BalanceRaw"]) ? $accountRow["BalanceRaw"] + 0 : 0;

        if (is_null($account["balance"])) {
            if (($accountRow["ErrorCode"] == ACCOUNT_CHECKED) || ($accountRow["ErrorCode"] == ACCOUNT_WARNING) || ($accountRow["ProviderID"] == "")) {
                $account["balance"] = "n/a";
            } else {
                $account["balance"] = "Error";
            }
        }

        if (empty($accountRow['SubAccountID'])) {
            $account["owner"] = !empty($accountRow['FamilyMemberName']) ? $accountRow['FamilyMemberName'] : $accountRow['UserName'];
            $account["errorCode"] = intval($accountRow["ErrorCode"]);

            if ($accountRow["ProviderID"] == "") {
                $account["errorCode"] = ACCOUNT_CHECKED;
            }

            if (isset($accountRow['MainProperties']['Number']) && ($accountRow['MainProperties']['Number']['Number'] != $accountRow['Login'])) {
                $attributes[$accountRow['MainProperties']['Number']['Caption']] = [
                    'name' => $accountRow['MainProperties']['Number']['Caption'],
                    'value' => $accountRow['MainProperties']['Number']['Number'],
                    'kind' => 1,
                ];
            }
        } else {
            $accountRow["ErrorCode"] = ACCOUNT_CHECKED;

            if (isset($accountRow['MainProperties']['Number']) && isset($accountRow['MainProperties']['Login']) && ($accountRow['MainProperties']['Number']['Number'] != $accountRow['MainProperties']['Login'])) {
                $attributes[$accountRow['MainProperties']['Number']['Caption']] = [
                    'name' => $accountRow['MainProperties']['Number']['Caption'],
                    'value' => $accountRow['MainProperties']['Number']['Number'],
                    'kind' => 1,
                ];
            }
        }

        if (!in_array($accountRow["ErrorCode"], [ACCOUNT_CHECKED, ACCOUNT_UNCHECKED]) && empty($accountRow['Disabled'])) {
            $account["errorMessage"] = $accountRow['ProgramMessage']['Error'];
        }

        // Status
        //        $props = new \TAccountInfo($arFields, ArrayVal($arFields, 'SubAccountID', null));
        if (isset($accountRow['MainProperties']['Status'])) {
            $attributes[$accountRow['MainProperties']['Status']['Caption']] = [
                'value' => $accountRow['MainProperties']['Status']['Status'],
                'rank' => isset($accountRow['Rank']) ? intval($accountRow['Rank']) : null,
                'kind' => 3,
            ];
        } elseif ($this->isAmericanAirlinesAAdvantage($accountRow) && !empty($accountRow['CustomEliteLevel'])) {
            $attributes['Level'] = [
                'value' => $accountRow['CustomEliteLevel'],
                'rank' => 1,
                'kind' => 3,
            ];
        }

        // Last Change
        if (isset($accountRow['LastChange'])) {
            $account["lastDetectedChange"] = $accountRow['LastChange'];
            //            $account["Change"] = $arFields['LastChange'] > 0 ? 'inc' : 'dec';
        }

        // Expiration Date
        if ($accountRow['ExpirationKnown'] && isset($accountRow['ExpirationDateTs']) && !empty($accountRow['ExpirationDateTs']) && $accountRow['ExpirationDateTs'] != 9999999999) {
            $account['expirationDate'] = date('c', $accountRow['ExpirationDateTs']);
            //            $account["expirationState"] = $arFields['ExpirationState'];
        }

        // properties
        if (($accountRow["ErrorCode"] == ACCOUNT_CHECKED) || ($accountRow["ErrorCode"] == ACCOUNT_WARNING)) {
            if (isset($accountRow['Properties']) && is_array($accountRow['Properties'])) {
                foreach ($accountRow['Properties'] as $row) {
                    if ($row['Visible'] == '1' && $row['Code'] != 'NextAccountUpdate'
                        && (
                            (empty($accountRow['SubAccountID']) && empty($row['SubAccountID']))
                            || (!empty($accountRow['SubAccountID']) && !empty($row['SubAccountID']) && $row['SubAccountID'] == $accountRow['SubAccountID'])
                        )
                    ) {
                        $s = $row['Val'];
                        $values = hideCreditCards(['Account' => $s], $accountRow['Kind']);
                        $s = array_shift($values);
                        // hideCCNumber($s);
                        $attributes[$row['Name']] = [
                            'name' => $row['Name'],
                            'value' => $s,
                        ];

                        if (!empty($row['Kind'])) {
                            $attributes[$row['Name']]['kind'] = intval($row['Kind']);
                        }
                    }
                }
            }
        }

        if (isset($accountRow['BarCode']) && $accountRow['BarCode'] != '') {
            $account['barcode'] = $accountRow['BarCode'];
        }

        if (isset($accountRow["UpdateDateTs"])) {
            $account['lastRetrieveDate'] = date('c', $accountRow["UpdateDateTs"]);
        }

        if (isset($accountRow["LastChangeDateTs"])) {
            $account['lastChangeDate'] = date('c', $accountRow['LastChangeDateTs']);
        }

        $attributes = array_reverse($attributes, true);

        if (!empty($attributes)) {
            $account['properties'] = $this->indexArray($attributes);
        }
    }

    private function splitNumber($number)
    {
        $result = "";

        while (strlen($number) > 0) {
            if ($result != "") {
                $result .= " ";
            }
            $result .= substr($number, 0, 3);
            $number = substr($number, 3);
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getMemberData(Useragent $member)
    {
        $data = [
            "memberId" => $member->getUseragentid(),
            "fullName" => $member->getFullName(),
            "email" => $member->getEmail() ?: '',
            "forwardingEmail" => $member->getItineraryForwardingEmail() ?: '',
        ];

        $data["editMemberUrl"] = $this->router->generate('aw_home', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'agent/editFamilyMember.php?ID=' . $member->getUseragentid() . '&Source=M';
        $data["accountListUrl"] = $this->router->generate('aw_account_list', ['agentId' => $member->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $data["timelineUrl"] = $this->router->generate('aw_timeline', ['agentId' => $member->getUseragentid()], UrlGeneratorInterface::ABSOLUTE_URL) . $member->getUseragentid();

        return $data;
    }

    /**
     * @param bool $forBooker
     * @return array
     */
    private function getConnectedUserData($connectedUser, $forBooker = false)
    {
        $router = $this->router;

        if ($connectedUser instanceof Useragent) {
            $user = $connectedUser->getClientid();
            $backConnection = $user->getConnectionWith($connectedUser->getAgentid());
            $data = [
                "userId" => $user->getUserid(),
                "fullName" => $user->getFullName(),
                "status" => $user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS ? 'Plus' : 'Free',
                "userName" => $user->getLogin() ?: '',
                "email" => $user->getEmail() ?: '',
                "forwardingEmail" => $user->getItineraryForwardingEmail(),
                "accessLevel" => $backConnection ? self::$accessLevels[$backConnection->getAccesslevel()] : 'n/a',
                "connectionType" => $connectedUser->getIsapproved() ? 'Connected' : 'Pending',
                "accountsAccessLevel" => self::$accessLevels[$connectedUser->getAccesslevel()],
                "accountsSharedByDefault" => $connectedUser->getSharebydefault(),
                //                TripAccessLevel: Full Controll* #TBD
                //                Trips : [TimeLineID, LastUpdated]* #TBD
            ];

            if (!empty($backConnection)) {
                $data["editConnectionUrl"] = $router->generate('aw_business_member_edit', ['userAgentId' => $backConnection->getUseragentid()], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            $data["accountListUrl"] = $router->generate('aw_account_list', ['agentId' => $connectedUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $data["timelineUrl"] = $router->generate('aw_timeline', ['agentId' => $connectedUser->getUseragentid()], UrlGeneratorInterface::ABSOLUTE_URL) . $connectedUser->getUseragentid();

            if ($forBooker) {
                $data["bookingRequestsUrl"] = $router->generate('aw_booking_list_queue', ['user_filter' => $user->getUserid()], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        } else {
            $data = [
                "userId" => intval($connectedUser['UserID']),
                "fullName" => $connectedUser['FullName'],
                "status" => $connectedUser['AccountLevel'] == ACCOUNT_LEVEL_AWPLUS ? 'Plus' : 'Free',
                "userName" => $connectedUser['Login'] ?: '',
                "email" => $connectedUser['Email'] ?: '',
                "forwardingEmail" => $connectedUser['Login'] . '@email.AwardWallet.com',
                "accessLevel" => $connectedUser['BackAccessLevel'] ? self::$accessLevels[$connectedUser['BackAccessLevel']] : 'n/a',
                "connectionType" => $connectedUser['IsApproved'] ? 'Connected' : 'Pending',
                "accountsAccessLevel" => self::$accessLevels[$connectedUser['AccessLevel']],
                "accountsSharedByDefault" => (bool) $connectedUser['ShareByDefault'],
                //                TripAccessLevel: Full Controll* #TBD
                //                Trips : [TimeLineID, LastUpdated]* #TBD
            ];

            if (!empty($connectedUser['BackUserAgentID'])) {
                $data["editConnectionUrl"] = $router->generate('aw_business_member_edit', ['userAgentId' => $connectedUser['BackUserAgentID']], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            $data["accountListUrl"] = $router->generate('aw_account_list', ['agentId' => $connectedUser['UserAgentID']], UrlGeneratorInterface::ABSOLUTE_URL);
            $data["timelineUrl"] = $router->generate('aw_timeline', ['agentId' => $connectedUser['UserAgentID']], UrlGeneratorInterface::ABSOLUTE_URL) . $connectedUser['UserAgentID'];

            if ($forBooker) {
                $data["bookingRequestsUrl"] = $router->generate('aw_booking_list_queue', ['user_filter' => $connectedUser['UserID']], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        return $data;
    }

    private function getSpendAnalysis(Usr $business): array
    {
        $result = $analysisData = $this->bankTransactionsAnalyser->getSpentAnalysisInitial($business);
        $cardsList = [];
        array_map(function (array $owner) use (&$cardsList) {
            array_map(function (array $card) use ($owner, &$cardsList) {
                $cardsList[] = [
                    'owner' => $owner['name'],
                    'subAccountId' => $card['subAccountId'],
                    'creditCardName' => $card['creditCardName'],
                ];
            }, $owner['availableCards']);
        }, $result['ownersList']);

        $range = BankTransactionsDateUtils::findRangeLimits(BankTransactionsDateUtils::LAST_YEAR);
        $merchantsData = $this->bankTransactionsAnalyser->merchantAnalytics(
            $business,
            array_map(function (array $card) {
                return $card['subAccountId'];
            }, $cardsList),
            $range['start'],
            $range['end'],
            []
        );

        $merchantsData = array_map(function (array $m) {
            return [
                'merchantName' => $m['merchantName'],
                'amount' => round($m['amount'], 2),
                'miles' => $m['miles'],
                'category' => $m['category'],
                'transactions' => $m['transactions'],
            ];
        }, $merchantsData);

        return [
            'businessId' => $business->getId(),
            'cardsList' => $cardsList,
            'merchantsData' => $merchantsData,
        ];
    }

    private function isAmericanAirlinesAAdvantage($row): bool
    {
        return empty($row['ProviderID'])
            && $row['Kind'] === PROVIDER_KIND_AIRLINE
            && AmericanAirlinesAAdvantageDetector::isMatchByName($row['DisplayName'] ?? '');
    }
}
