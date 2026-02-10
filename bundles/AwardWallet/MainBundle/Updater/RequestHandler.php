<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class RequestHandler
{
    private const LOCKED_STATUS_CODE = 423;
    private LocalPasswordsManager $localPasswordsManager;
    private AccountManager $accountManager;
    private AccountRepository $accountRepository;
    private UpdaterSession $updater;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ApiVersioningService $apiVersioning;
    private TickScheduler $tickScheduler;
    private RequestSerializer $requestSerializer;
    private ContextAwareLoggerWrapper $logger;
    private BinaryLoggerFactory $check;

    public function __construct(
        LocalPasswordsManager $localPasswordsManager,
        AccountManager $accountManager,
        AuthorizationCheckerInterface $authorizationChecker,
        AccountRepository $accountRepository,
        ApiVersioningService $apiVersioning,
        TickScheduler $tickScheduler,
        RequestSerializer $requestSerializer,
        LoggerInterface $logger
    ) {
        $this->localPasswordsManager = $localPasswordsManager;
        $this->accountManager = $accountManager;
        $this->accountRepository = $accountRepository;
        $this->authorizationChecker = $authorizationChecker;
        $this->apiVersioning = $apiVersioning;
        $this->tickScheduler = $tickScheduler;
        $this->requestSerializer = $requestSerializer;
        $this->logger = (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('updater request handler: ')
            ->withTypedContext();
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo()->uppercaseInfix();
    }

    public function setUpdater(UpdaterSession $updater)
    {
        $this->updater = $updater;

        return $this;
    }

    /**
     * @param string $key
     * @param int $eventIndex
     * @return Event\AbstractEvent[]
     */
    public function handleProgress(Request $request, $key, $eventIndex): array
    {
        $requestData = JsonRequestHandler::parse($request);
        $messagesRes = \is_array($requestData['messages'] ?? null) ?
            $this->handleUserMessages($requestData['messages']) :
            new UserMessagesHandlerResult();

        try {
            return $this->updater->tick(
                $key,
                $eventIndex,
                $messagesRes->addAccounts,
                $messagesRes->removeAccounts,
                $messagesRes->refuseLocalPasswords
            );
        } catch (UpdaterStateException $e) {
            throw new BadRequestHttpException('Updater error.', $e);
        }
    }

    public function handleAsyncProgress(Request $request, string $sessionKey): void
    {
        $this->logger->pushContext(['updater_session_key' => $sessionKey]);

        try {
            $requestData = JsonRequestHandler::parse($request);
            $messagesRes = \is_array($requestData['messages'] ?? null) ?
                $this->handleUserMessages($requestData['messages']) :
                new UserMessagesHandlerResult();
            $addAccounts = $messagesRes->addAccounts;
            $removeAccounts = $messagesRes->removeAccounts;
            $this->logger->info('new accounts will be inserted to session', [
                'accounts' =>
                    it($addAccounts)
                    ->map(fn (AddAccount $a) => $a->getAccountId())
                    ->toArray(),
            ]);

            $this->logger->info('accounts will be removed from session', [
                'accounts' => $removeAccounts,
            ]);

            $cookieModifiedRequest = $this->requestSerializer->deserializeRequest($this->requestSerializer->serializeRequest($request));
            $response = new Response();
            $this->localPasswordsManager->save($response);
            $cookieModifiedRequest->attributes->remove(LocalPasswordsManager::ATTR_NAME);

            /** @var Cookie $cookie */
            foreach ($response->headers->getCookies() as $cookie) {
                $cookieModifiedRequest->cookies->set($cookie->getName(), $cookie->getValue());
            }

            $this->tickScheduler->scheduleTickByHttpRequest($sessionKey, $cookieModifiedRequest, $messagesRes);
        } finally {
            $this->logger->popContext();
        }
    }

    /**
     * @param list<array{action: string, id?: int, data?: mixed}> $messages
     */
    private function handleUserMessages(array $messages): UserMessagesHandlerResult
    {
        $result = new UserMessagesHandlerResult();
        $this->logger->info('handling user messages', ['messages_count' => \count($messages)]);

        foreach ($messages as $message) {
            switch ($message['action']) {
                case 'add':
                    $accountId = $message['id'];
                    $this->logger->info("inserting account ({$accountId}) to session", ['account_id' => $accountId]);
                    $result->addAccounts[] = AddAccount::createLowPriority((int) $accountId);

                    break;

                case 'remove':
                    $accountId = $message['id'];
                    $this->logger->info("account ({$accountId}) will be removed from session", ['account_id' => $accountId]);
                    $result->removeAccounts[] = (int) $accountId;

                    break;

                case 'refuseLocalPassword':
                    $accountId = $message['id'];
                    $this->logger->info("account ({$accountId}) will be removed from Extension V3 wait map", ['account_id' => $accountId]);
                    $result->refuseLocalPasswords[] = (int) $accountId;

                    break;

                case 'setAnswer':
                    $accountId = $message['id'];
                    $this->logger->pushContext(['account_id' => $accountId]);

                    try {
                        $this->logger->info("setting answer for account ({$accountId})");

                        if ($this->setAnswer($accountId, $message['data'])) {
                            $result->addAccounts[] = AddAccount::createHighPriority((int) $accountId);
                        }
                    } finally {
                        $this->logger->popContext();
                    }

                    break;

                case 'setPassword':
                    $accountId = $message['id'];
                    $this->logger->pushContext(['account_id' => $accountId]);

                    try {
                        $this->logger->info("setting password for account ({$accountId})");

                        if ($this->setPassword($accountId, $message['data'])) {
                            $result->addAccounts[] = AddAccount::createLowPriority((int) $accountId);
                        }
                    } finally {
                        $this->logger->popContext();
                    }

                    break;

                case 'unpause':
                    $this->logger->info("unpausing updater session");
                    $result->unpause = true;

                    break;
            }
        }

        return $result;
    }

    private function setAnswer($accountId, $data)
    {
        $check = $this->check;
        $accountId = intval(preg_replace('/[^0-9]/', '', $accountId));

        if ($check('answer data *is not* an array', !\is_array($data))) {
            return false;
        }

        if (
            $check('question *is* empty', empty($data['question']))
            || $check('answer *is* empty', empty($data['answer']))
        ) {
            return false;
        }

        $account = $this->accountRepository->find($accountId);

        if ($check('account *was not* found', !$account)) {
            return false;
        }

        return $check(
            'answer *was* saved',
            $this->accountManager->answerSecurityQuestion($account, $data['question'], $data['answer'])
        );
    }

    private function setPassword($accountId, $password)
    {
        $check = $this->check;
        $accountId = intval(preg_replace('/[^0-9]/', '', $accountId));
        $account = $this->accountRepository->find($accountId);

        if ($check('account *is not* found', !$account)) {
            return false;
        }

        if (
            $check(
                'access *is not* granted',
                !$this->authorizationChecker->isGranted('UPDATE', $account)
            )
        ) {
            return false;
        }

        if ($check('password *is* empty', empty($password))) {
            return false;
        }

        if ($check('password *is not* string', !\is_string($password))) {
            return false;
        }

        $this->localPasswordsManager->setPassword($accountId, $password);

        return true;
    }
}
