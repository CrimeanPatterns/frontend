<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Controller\Account\EmailParseController;
use AwardWallet\MainBundle\Email\CallbackProcessor;
use AwardWallet\MainBundle\Email\EmailOptions;
use AwardWallet\MainBundle\Email\InvalidDataException;
use AwardWallet\MainBundle\Email\ProviderCouponProcessor;
use AwardWallet\MainBundle\Email\StatementProcessor;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Scanner\MailboxOwnerHelper;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use Doctrine\DBAL\Connection;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class EmailCallbackExecutor implements ExecutorInterface
{
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private MailboxOwnerHelper $mailboxOwnerHelper;
    private Connection $connection;
    private CallbackProcessor $callbackProcessor;
    private StatementProcessor $statementProcessor;
    private ProviderCouponProcessor $pcp;
    private AccountRepository $accountRepository;
    private ClientInterface $socksClient;

    public function __construct(
        SerializerInterface $serializer,
        LoggerInterface $logger,
        MailboxOwnerHelper $mailboxOwnerHelper,
        Connection $connection,
        CallbackProcessor $callbackProcessor,
        StatementProcessor $statementProcessor,
        ProviderCouponProcessor $pcp,
        AccountRepository $accountRepository,
        ClientInterface $socksClient
    ) {
        $this->serializer = $serializer;
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->pushContext(["worker" => "EmailCallback"]);
        $this->mailboxOwnerHelper = $mailboxOwnerHelper;
        $this->connection = $connection;
        $this->callbackProcessor = $callbackProcessor;
        $this->statementProcessor = $statementProcessor;
        $this->pcp = $pcp;
        $this->accountRepository = $accountRepository;
        $this->socksClient = $socksClient;
    }

    /**
     * @param EmailCallbackTask $task
     * @return Response|void
     */
    public function execute(Task $task, $delay = null)
    {
        $requestId = null;
        $userDataRaw = null;
        $mailboxId = null;
        $userId = null;

        $addLogInfo = function (array $record) use (&$requestId, &$userDataRaw, &$mailboxId, &$userId) {
            if ($userDataRaw !== null) {
                $record['context']['userData'] = $userDataRaw;
            }

            if ($mailboxId !== null) {
                $record['context']['mailboxId'] = $mailboxId;
            }

            if ($requestId !== null) {
                $record['context']['requestId'] = $requestId;
            }

            if ($userId !== null) {
                $record['context']['userId'] = $userId;
            }

            return $record;
        };

        $this->logger->pushProcessor($addLogInfo);

        try {
            // we will consume 220M when processing 30M message
            $oldLimit = ini_set('memory_limit', '300M');

            try {
                /** @var ParseEmailResponse $data */
                $data = $this->serializer->deserialize($task->getSerializedData(), ParseEmailResponse::class, 'json');
                $requestId = $data->requestId;
                $userDataRaw = $data->userData;
                $this->logger->info("saving ParseEmailResponse $requestId");
                $owner = $accountId = null;

                if (!empty($data->status) && !empty($data->email) && !empty($data->metadata)) {
                    if (!empty($data->userData)) {
                        $this->userData = $data->userData;
                        $userData = @json_decode($data->userData, true);
                        $this->logger->info("userData: " . $data->userData);

                        // connected mailbox
                        if (!empty($userData['user'])) {
                            try {
                                $owner = $this->mailboxOwnerHelper->getOwnerByUserData($data->userData);
                            } catch (\InvalidArgumentException $e) {
                                $this->logger->warning("failed to find user by userData: " . $data->userData);

                                return new Response(Response::STATUS_READY);
                            }
                            /** @var Usr $user */
                            $userId = $owner->getUser()->getId();
                            $this->logger->info("received user: " . $owner->getOwnerId());
                        }

                        // copypaste on site
                        if (!empty($userData['accountId'])) {
                            $accountId = $this->connection->executeQuery('select 1 from Account where AccountID = ?',
                                [$userData['accountId']], [\PDO::PARAM_INT])->fetchColumn() ? $userData['accountId'] : null;
                        }
                    }

                    if (!empty($data->metadata->mailboxId)) {
                        $mailboxId = $data->metadata->mailboxId;
                    }

                    try {
                        $options = new EmailOptions($data, $owner !== null);
                        $result = $this->callbackProcessor->process($data, $options, $owner, $accountId);

                        if (!empty($userData['user'])) {
                            if (!empty($data->loyaltyAccount)) {
                                $saved = $this->statementProcessor->process($data, $owner, $options);

                                if ($result != CallbackProcessor::SAVE_MESSAGE_SUCCESS) {
                                    $result = $saved;
                                }
                            }

                            if (!empty($data->coupons)) {
                                $saved = $this->pcp->process($data, $owner);

                                if ($result != CallbackProcessor::SAVE_MESSAGE_SUCCESS) {
                                    $result = $saved;
                                }
                            }
                        }
                    } catch (InvalidDataException $e) {
                        $this->logger->error('failed to save email: ' . $e->getMessage());
                        $result = CallbackProcessor::SAVE_MESSAGE_FAIL;
                    }

                    if (!empty($userData['accountId']) && isset($userData['notifyToChannel']) && true === $userData['notifyToChannel']) {
                        $accountRequestKey = sprintf(EmailParseController::CHANNEL_TEMPLATE, $userData['accountId'], $requestId);
                        $clientData = ['status' => $result];

                        if (CallbackProcessor::SAVE_MESSAGE_SUCCESS === $result && property_exists($data->loyaltyAccount, 'balance')) {
                            $originalBalance = $userData['accountBalance'] ?? 0;
                            /** @var Account $account */
                            $account = $this->accountRepository->find($userData['accountId']);
                            $clientData['account'] = [
                                'lastBalance' => $originalBalance,
                                'balance' => $account->getBalance(),
                                'changed' => $account->getBalance() - $originalBalance,
                                'increased' => $account->getBalance() === $originalBalance ? null : ($account->getBalance() > $originalBalance),
                                'type' => $account->getBalance() === $originalBalance ? 'updated' : 'changed',
                            ];
                        }
                        $this->socksClient->publish($accountRequestKey, $clientData);
                    }
                } else {
                    $this->logger->warning("missing data, request: " . $task->getSerializedData());
                }

                return new Response(Response::STATUS_READY);
            } catch (RuntimeException $e) {
                $logFile = tempnam(sys_get_temp_dir(), "ecwError");
                file_put_contents($logFile, $task->getSerializedData());
                // sometimes we got cut json. may be rabbit connnection failure.
                // not a problem, will nack, and will be processed on retry
                $this->logger->warning("failed to deserialize: " . $e->getMessage() . ", saved to: {$logFile} at " . $e->getTraceAsString());

                throw new TaskNeedsRetryException(5);
            } finally {
                ini_set('memory_limit', $oldLimit);
            }
        } finally {
            $this->logger->popProcessor();
        }
    }
}
