<?php

namespace AwardWallet\MainBundle\Loyalty;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\Resources\AdminLogsRequest;
use AwardWallet\MainBundle\Loyalty\Resources\AdminLogsResponse;
use AwardWallet\MainBundle\Loyalty\Resources\AutoLoginRequest;
use AwardWallet\MainBundle\Loyalty\Resources\AutoLoginResponse;
use AwardWallet\MainBundle\Loyalty\Resources\AutologinWithExtensionRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountPackageRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportPackageRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportPackageResponse;
use AwardWallet\MainBundle\Loyalty\Resources\PasswordRequest;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountPackageResponse;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;
use AwardWallet\MainBundle\Loyalty\Resources\QueueInfoResponse;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ApiCommunicator
{
    public const METHOD_ADMIN_LOGS = '/admin/logs';
    public const METHOD_ADMIN_LOG = '/admin/log/%s';
    public const METHOD_ACCOUNT_CHECK = '/v2/account/check';
    public const METHOD_ACCOUNT_AUTOLOGIN_WITH_EXTENSION = '/v2/account/autologin-with-extension';
    public const METHOD_ACCOUNT_CHECK_PACKAGE = '/v2/account/check/package';
    public const METHOD_ACCOUNT_QUEUE_INFO = '/v2/account/queue';
    public const METHOD_CHECK_EXTENSION_SUPPORT_PACKAGE = '/v2/account/check-extension-support/package';
    public const METHOD_ACCOUNT_AUTOLOGIN = '/v2/autologin';
    public const METHOD_CONFIRMATION_CHECK = '/v2/confirmation/check';
    public const METHOD_CONFIRMATION_QUEUE_INFO = '/v2/confirmation/queue';
    public const METHOD_PROVIDERS_LIST = '/v2/providers/list';
    public const METHOD_PROVIDERS_INFO = '/v2/providers/%s';
    public const METHOD_PASSWORD_REQUEST_LIST = '/admin-aw/password-request/list';
    public const METHOD_PASSWORD_REQUEST_ITEM = '/admin-aw/password-request/%s';
    public const METHOD_PASSWORD_REQUEST_REGISTER = '/admin-aw/password-request';
    public const METHOD_PASSWORD_REQUEST_REMOVE = '/admin-aw/password-request/remove';
    public const METHOD_PASSWORD_REQUEST_EDIT = '/admin-aw/password-request/edit/%s';
    public const METHOD_EXTENSION_RESPONSE = '/v2/extension/response';
    public const METHOD_RA_ACCOUNT_LIST = '/admin/ra-accounts/list/%s';
    public const METHOD_RA_ACCOUNT_EDIT = '/admin/ra-accounts/edit/%s';
    public const METHOD_RA_ACCOUNT_BULK = '/admin/ra-accounts/bulk/%s';
    public const METHOD_RA_ACCOUNT_PROVIDER_FIELDS = '/ra-providers/register/%s/fields';
    public const METHOD_RA_ACCOUNT_PROVIDERS_LIST = '/ra-providers/register/list';
    public const METHOD_RA_ACCOUNT_GET = '/ra-account/register/%s';
    public const METHOD_RA_ACCOUNT_POST = '/ra-account/register';
    public const METHOD_RA_ACCOUNT_AUTO_POST = '/ra-account/register-auto';
    public const METHOD_RA_ACCOUNT_REPORT_FAIL_REGISTER = '/ra-account/report-fail-register';
    public const METHOD_RA_ACCOUNT_REGISTER_REQUEST_RETRY = '/ra-account/register-request-retry/%s';
    public const METHOD_RA_ACCOUNT_REGISTER_REQUEST_CHECK = '/ra-account/register-request-check/%s';
    public const METHOD_RA_ACCOUNT_REGISTER_REQUEST_ACCOUNT_TO_DB = '/ra-account/register-request-account/%s/%s';
    public const METHOD_RA_REGISTER_QUEUE_LIST = '/ra-account/queue/%s/list';
    public const METHOD_RA_REGISTER_QUEUE_CLEAR = '/ra-account/queue/%s/clear';
    public const METHOD_RA_REGISTER_QUEUE_DELETE = '/ra-account/queue/%s/delete';
    public const METHOD_RA_SEARCH = '/v1/search';
    public const METHOD_RA_PROVIDERS_LIST = '/v1/providers/list';
    public const METHOD_RA_BALANCE = '/admin/balance/%s';
    public const METHOD_RA_REGISTER_CONFIG_LIST = '/ra-register-config/list';
    public const METHOD_RA_REGISTER_CONFIG_CREATE = '/ra-register-config/create';
    public const METHOD_RA_REGISTER_CONFIG_EDIT = '/ra-register-config/%s/edit';
    public const METHOD_RA_REGISTER_CONFIG_DELETE = '/ra-register-config/%s/delete';
    public const METHOD_RA_HOT_SESSION_INFO = '/hot-session';
    public const METHOD_RA_HOT_SESSION_STOP = '/hot-session/stop';

    public const AUTOLOGIN_PROCESSING_TIMEOUT = 90;

    public const SERIALIZE_FORMAT = 'json';

    /** @var LoggerInterface */
    private $logger;
    /** @var SerializerInterface */
    private $serializer;
    /** @var CurlSender */
    private $sender;

    /**
     * @var CacheManager
     */
    private $cacheManager;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    public function __construct(SerializerInterface $serializer, CurlSender $sender, LoggerInterface $logger, CacheManager $cacheManager, EventDispatcherInterface $eventDispatcher, AccountRepository $accountRepository)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->sender = $sender;
        $this->cacheManager = $cacheManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->accountRepository = $accountRepository;
    }

    /**
     * @return PostCheckAccountResponse
     * @throws ApiCommunicatorException
     */
    public function CheckAccount(CheckAccountRequest $request)
    {
        $requestJson = $this->serialize($request);
        $request->setPassword(null);
        $userData = $request->getUserdata();
        /** @var CurlSenderResult $result */
        $result = $this->sender->call(self::METHOD_ACCOUNT_CHECK, $requestJson);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl result', ['request' => $request, 'curl_result' => $result]);
            /** @var Account $account */
            $account = $this->accountRepository->find($userData->getAccountId());

            if ($account) {
                $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_LOYALTY_CHECK));
                $this->eventDispatcher->dispatch(
                    new AccountUpdatedEvent(
                        $account,
                        (new CheckAccountResponse())
                            ->setState(ACCOUNT_TIMEOUT)
                            ->setMessage('Timeout')
                            ->setUserdata($request->getUserdata()),
                        new ProcessingReport(),
                        AccountUpdatedEvent::UPDATE_METHOD_LOYALTY
                    ),
                    AccountUpdatedEvent::NAME
                );
            }

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $this->deserialize($result->getResponse(), PostCheckAccountResponse::class);
    }

    /**
     * @return PostCheckAccountResponse
     * @throws ApiCommunicatorException
     */
    public function AutologinWithExtension(AutologinWithExtensionRequest $request)
    {
        $requestJson = $this->serialize($request);
        $request->setPassword(null);
        $userData = $request->getUserdata();
        /** @var CurlSenderResult $result */
        $result = $this->sender->call(self::METHOD_ACCOUNT_AUTOLOGIN_WITH_EXTENSION, $requestJson);

        if ($result->getCode() === 400 && stripos($result->getResponse(), 'Autologin with extension is not allowed to this provider') !== false) {
            throw new AutologinWithExtensionNotAllowedException($result->getResponse());
        }

        if ($result->getCode() !== 200) {
            $this->logger->error('Can not resolve loyalty curl result', ['request' => $request, 'curl_result' => $result]);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $this->deserialize($result->getResponse(), PostCheckAccountResponse::class);
    }

    public function CheckExtensionSupport(CheckExtensionSupportPackageRequest $request): CheckExtensionSupportPackageResponse
    {
        $requestJson = $this->serialize($request);
        /** @var CurlSenderResult $result */
        $result = $this->sender->call(self::METHOD_CHECK_EXTENSION_SUPPORT_PACKAGE, $requestJson);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl result', ['request' => $request, 'curl_result' => $result]);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $this->deserialize($result->getResponse(), CheckExtensionSupportPackageResponse::class);
    }

    /**
     * @return PostCheckAccountPackageResponse
     * @throws ApiCommunicatorException
     */
    public function CheckAccountsPackage(CheckAccountPackageRequest $request)
    {
        $requestJson = $this->serialize($request);
        /** @var CurlSenderResult $result */
        $result = $this->sender->call(self::METHOD_ACCOUNT_CHECK_PACKAGE, $requestJson);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl result', ['request' => $request, 'curl_result' => $result]);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $this->deserialize($result->getResponse(), PostCheckAccountPackageResponse::class);
    }

    /**
     * @return PostCheckAccountResponse
     * @throws ApiCommunicatorException
     */
    public function CheckConfirmation(CheckConfirmationRequest $request)
    {
        $requestJson = $this->serialize($request);
        /** @var CurlSenderResult $result */
        $result = $this->sender->call(self::METHOD_CONFIRMATION_CHECK, $requestJson);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl result', ['request' => $request, 'curl_result' => $result]);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse(), 1);
        }

        return $this->deserialize($result->getResponse(), PostCheckAccountResponse::class);
    }

    /**
     * @throws ApiCommunicatorException
     */
    public function ExtensionResponse(string $responseJson): void
    {
        /** @var CurlSenderResult $result */
        $result = $this->sender->call(self::METHOD_EXTENSION_RESPONSE, $responseJson);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl result', ['request' => $responseJson, 'curl_result' => $result]);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse(), 1);
        }
    }

    /**
     * @param string $method
     * @return QueueInfoResponse
     * @throws ApiCommunicatorException
     */
    public function GetQueueInfo($method = self::METHOD_ACCOUNT_QUEUE_INFO)
    {
        if (!in_array($method, [self::METHOD_ACCOUNT_QUEUE_INFO, self::METHOD_CONFIRMATION_QUEUE_INFO])) {
            throw new ApiCommunicatorException('Unavailable method for getting queue info');
        }

        /** @var CurlSenderResult $result */
        $result = $this->sender->call($method);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl ' . $method);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $this->deserialize($result->getResponse(), QueueInfoResponse::class);
    }

    public function AutoLogin(AutoLoginRequest $request): AutoLoginResponse
    {
        $method = self::METHOD_ACCOUNT_AUTOLOGIN;

        $requestJson = $this->serialize($request);
        /** @var CurlSenderResult $result */
        $result = $this->sender->call($method, $requestJson, true, self::AUTOLOGIN_PROCESSING_TIMEOUT);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl ' . $method);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $this->deserialize($result->getResponse(), AutoLoginResponse::class);
    }

    /**
     * @return AdminLogsResponse
     * @throws ApiCommunicatorException
     */
    public function GetCheckerLogs(AdminLogsRequest $request)
    {
        $method = self::METHOD_ADMIN_LOGS;

        $requestJson = $this->serialize($request);
        /** @var CurlSenderResult $result */
        $result = $this->sender->call($method, $requestJson);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl ' . $method);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $this->deserialize($result->getResponse(), AdminLogsResponse::class);
    }

    /**
     * @return AdminLogsResponse
     * @throws ApiCommunicatorException
     */
    public function GetLog(string $filename)
    {
        $method = sprintf(self::METHOD_ADMIN_LOG, $filename);

        $result = $this->sender->call($method);

        if ($result->getCode() !== 200) {
            $this->logger->warning('Can not resolve loyalty curl ' . $method);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $result->getResponse();
    }

    /**
     * usage in AccountAccess API.
     *
     * @return string mixed
     * @throws ApiCommunicatorException
     */
    public function getProvidersList(bool $isAwDefaultUser = true)
    {
        /** @var CurlSenderResult $result */
        $result = $this->call(self::METHOD_PROVIDERS_LIST, null, $isAwDefaultUser);

        return $result->getResponse();
    }

    /**
     * usage in AccountAccess API.
     *
     * @param string $provider
     * @return ProviderInfoResponse|string mixed
     * @throws ApiCommunicatorException
     */
    public function getProviderInfo($provider, bool $isAwDefaultUser = true)
    {
        $method = sprintf(self::METHOD_PROVIDERS_INFO, $provider);

        if (!$isAwDefaultUser) {
            $result = $this->call($method, null, false);

            return $result->getResponse();
        }

        $response = $this->cacheManager->load(new CacheItemReference('loyalty_provider_info_v1_' . $provider, Tags::addTagPrefix([Tags::TAG_PROVIDERS]), function () use ($method) {
            /** @var CurlSenderResult $result */
            $result = $this->call($method);

            return $result->getResponse();
        }));

        return $this->deserialize($response, ProviderInfoResponse::class);
    }

    public function passwordRequestList($id = null)
    {
        $url = empty($id) ? self::METHOD_PASSWORD_REQUEST_LIST : sprintf(self::METHOD_PASSWORD_REQUEST_ITEM, $id);
        /** @var CurlSenderResult $result */
        $result = $this->call($url);

        return $result->getResponse();
    }

    public function passwordRequest(PasswordRequest $request)
    {
        /** @var CurlSenderResult $result */
        $result = $this->call(self::METHOD_PASSWORD_REQUEST_REGISTER, $this->serialize($request));

        return $result->getResponse();
    }

    public function passwordRequestEdit($id, PasswordRequest $request)
    {
        /** @var CurlSenderResult $result */
        $result = $this->call(sprintf(self::METHOD_PASSWORD_REQUEST_EDIT, $id), $this->serialize($request));

        return $result->getResponse();
    }

    public function passwordRequestRemove($id)
    {
        /** @var CurlSenderResult $result */
        $result = $this->call(self::METHOD_PASSWORD_REQUEST_REMOVE, json_encode(['id' => $id]));

        return $result->getResponse();
    }

    public function listRaAccount(?string $id = null)
    {
        $result = $this->call(sprintf(self::METHOD_RA_ACCOUNT_LIST, $id ?? 'all'));

        return $result->getResponse();
    }

    public function editRaAccount($id, $request)
    {
        $result = $this->call(sprintf(self::METHOD_RA_ACCOUNT_EDIT, $id), json_encode($request));

        return $result->getResponse();
    }

    public function bulkRaAccountAction($ids, $method)
    {
        $result = $this->call(sprintf(self::METHOD_RA_ACCOUNT_BULK, $method), json_encode($ids));

        return $result->getResponse();
    }

    public function getBalance(string $type)
    {
        $result = $this->call(sprintf(self::METHOD_RA_BALANCE, $type), json_encode(['data' => time()]));

        return $result->getResponse();
    }

    public function makeRaRequest(array $data)
    {
        $result = $this->call(self::METHOD_RA_SEARCH, json_encode($data));

        return $result->getResponse();
    }

    public function getListRaProviders(): string
    {
        $result = $this->call(self::METHOD_RA_PROVIDERS_LIST);

        return $result->getResponse();
    }

    public function getRaRegProvidersList()
    {
        return $this->call(self::METHOD_RA_ACCOUNT_PROVIDERS_LIST)->getResponse();
    }

    public function getRaRegProviderFields(string $providerCode)
    {
        return $this->call(sprintf(self::METHOD_RA_ACCOUNT_PROVIDER_FIELDS, $providerCode))->getResponse();
    }

    public function getRaRegisterResult(string $id)
    {
        return $this->call(sprintf(self::METHOD_RA_ACCOUNT_GET, $id))->getResponse();
    }

    public function sendRaRegister(array $data)
    {
        return $this->call(self::METHOD_RA_ACCOUNT_POST, json_encode($data))->getResponse();
    }

    public function sendRaRegisterAuto(array $data)
    {
        return $this->call(self::METHOD_RA_ACCOUNT_AUTO_POST, json_encode($data))->getResponse();
    }

    public function sendRaRegisterRetry(string $id)
    {
        return $this->call(sprintf(self::METHOD_RA_ACCOUNT_REGISTER_REQUEST_RETRY, $id), json_encode([]))->getResponse();
    }

    public function sendRaRegisterCheck(string $id)
    {
        return $this->call(sprintf(self::METHOD_RA_ACCOUNT_REGISTER_REQUEST_CHECK, $id), json_encode([]))->getResponse();
    }

    public function sendRaRegisterAccountToDB(string $id, string $state)
    {
        return $this->call(sprintf(self::METHOD_RA_ACCOUNT_REGISTER_REQUEST_ACCOUNT_TO_DB, $id, strtolower($state)), json_encode([]))->getResponse();
    }

    public function getReportOfFailRegistrations()
    {
        return $this->call(self::METHOD_RA_ACCOUNT_REPORT_FAIL_REGISTER)->getResponse();
    }

    public function getRaRegisterQueueList($provider)
    {
        return $this->call(sprintf(self::METHOD_RA_REGISTER_QUEUE_LIST, $provider))->getResponse();
    }

    public function sendRaRegisterQueueDelete(string $id)
    {
        return $this->call(sprintf(self::METHOD_RA_REGISTER_QUEUE_DELETE, $id), json_encode([]))->getResponse();
    }

    public function sendRaRegisterQueueClear(string $provider)
    {
        return $this->call(sprintf(self::METHOD_RA_REGISTER_QUEUE_CLEAR, $provider), json_encode([]))->getResponse();
    }

    public function getRaRegisterConfigList()
    {
        return $this->call(self::METHOD_RA_REGISTER_CONFIG_LIST)->getResponse();
    }

    public function sendRaRegisterConfigCreate(array $data)
    {
        return $this->call(self::METHOD_RA_REGISTER_CONFIG_CREATE, json_encode($data))->getResponse();
    }

    public function sendRaRegisterConfigEdit(string $id, array $data)
    {
        return $this->call(sprintf(self::METHOD_RA_REGISTER_CONFIG_EDIT, $id), json_encode($data))->getResponse();
    }

    public function sendRaRegisterConfigDelete(string $id)
    {
        return $this->call(sprintf(self::METHOD_RA_REGISTER_CONFIG_DELETE, $id), json_encode([]))->getResponse();
    }

    public function getListHotSession()
    {
        return $this->call(self::METHOD_RA_HOT_SESSION_INFO, json_encode([]))->getResponse();
    }

    public function sendListToStopHotSessions(array $data)
    {
        return $this->call(self::METHOD_RA_HOT_SESSION_STOP, json_encode($data));
    }

    private function call($method, $jsonData = null, $isAwDefaultUser = true, $timeout = CurlSender::TIMEOUT): CurlSenderResult
    {
        /** @var CurlSenderResult $result */
        $result = $this->sender->call($method, $jsonData, $isAwDefaultUser, $timeout);

        if (!in_array($result->getCode(), [200, 201])) {
            $this->logger->warning('Can not resolve loyalty curl result', ['request' => $jsonData, 'curl_result' => $result]);

            throw new ApiCommunicatorException('Loyalty response: ' . $result->getResponse());
        }

        return $result;
    }

    private function serialize($data)
    {
        return $this->serializer->serialize($data, self::SERIALIZE_FORMAT);
    }

    private function deserialize($data, $type = 'array')
    {
        return $this->serializer->deserialize($data, $type, self::SERIALIZE_FORMAT);
    }
}
