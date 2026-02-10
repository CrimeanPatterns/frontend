<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use AwardWallet\MainBundle\Globals\StackTraceUtils;
use AwardWallet\MainBundle\Worker\ProcessControlWrapper;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Container\ContainerInterface;

/**
 * @deprecated use \AwardWallet\MainBundle\Service\TaskScheduler\ConsumerRouter instead
 */
class Worker implements ConsumerInterface
{
    private Logger $logger;
    private ContainerInterface $container;
    private Storage $storage;
    private bool $needRestart = false;
    private ProcessControlWrapper $processControl;
    private Process $asyncProcess;
    private AppProcessor $appProcessor;
    private EntityManagerInterface $entityManager;

    private array $backupServer = [];

    private Connection $connection;

    public function __construct(
        ContainerInterface $container,
        Process $process,
        Logger $logger,
        Storage $storage,
        AppProcessor $appProcessor,
        ProcessControlWrapper $processControlWrapper,
        EntityManagerInterface $entityManager,
        Connection $connection
    ) {
        $this->backupServer = $_SERVER ?? [];
        $this->logger = $logger;
        $this->storage = $storage;
        $this->processControl = $processControlWrapper;
        $this->asyncProcess = $process;

        $this->container = $container;
        $this->appProcessor = $appProcessor;
        $this->logger->info("started");
        $this->entityManager = $entityManager;
        $this->connection = $connection;
    }

    public function execute(AMQPMessage $message)
    {
        $this->appProcessor->setNewRequestId();

        if (
            $this->needRestart
        ) {
            $this->processControl->exit(1);

            // unreachable in production
            return true;
        }

        $task = @unserialize($message->body);

        if (!($task instanceof Task)) {
            $this->logger->error("invalid message", ["body" => $message->body]);

            return true; // ACK
        }

        $response = $this->storage->getResponse($task);
        $logContext = [
            'task_requestid' => $task->requestId,
            'task_class' => \get_class($task),
            'worker' => 'async',
            'serviceId' => $task->serviceId,
            'serviceMethod' => $task->method,
        ];
        $this->logger->pushProcessor(function (array $record) use (&$logContext) {
            $record['extra'] = array_merge($record['extra'], $logContext);

            return $record;
        });

        $timeStart = (int) (\microtime(true) * 1000);
        $needRetry = false;
        $delay = 0;

        try {
            // этот код будет постоянно триггерить при использовании Memcached в качестве хранилища
            // как следствие этого Executor не будет вызываться
            if (Response::STATUS_NONE === $response->status) {
                $this->logger->info('Task was not queued properly', $logContext);

                return true; // ACK
            }

            // we will check execution count before execution, because worker could be killed on last execution
            if ($response->executionCount > $task->getMaxRetriesCount()) {
                $response->executionCount++;
                $this->handleRetriesExceeded($task, $response);

                return true; // ACK
            }

            $response->executionCount++;
            $executionCount = $response->executionCount;
            $this->logger->info(
                "processing: " . \get_class($task) . " for the {$response->executionCount} time",
                [
                    'executionCount' => $response->executionCount,
                    'responseStatus' => $response->status,
                ]
            );
            $response->status = Response::STATUS_PROCESSING;
            $this->storage->setResponse($task, $response);

            /** @var ExecutorInterface $executor */
            try {
                $executor = $this->container->get($task->serviceId);

                if ($executor instanceof ExecutorInterface) {
                    $response = $executor->execute($task, $this->getDelay($message));
                } else {
                    // этот код никогда не вызывается
                    $this->logger->info("calling $task->serviceId::$task->method");
                    $response = call_user_func_array([$executor, $task->method], $task->parameters);

                    if (!($response instanceof Response)) {
                        $response = new Response();
                    }
                }

                if (Response::STATUS_NONE === $response->status) {
                    $response->status = Response::STATUS_READY;
                }

                $response->executionCount = $executionCount;
            } catch (TaskNeedsRetryException $e) {
                $task->retry++;

                if ($task->retry > $task->getMaxRetriesCount()) {
                    $this->handleRetriesExceeded($task, $response);
                } else {
                    $this->logger->info(
                        \sprintf('retrying task: %s(%s), retry %d of %d',
                            \get_class($task),
                            $task->requestId,
                            $task->retry,
                            $task->getMaxRetriesCount()
                        )
                    );
                    $response->status = Response::STATUS_QUEUED;
                    $needRetry = true;
                    $delay = $e->getDelay();
                }

                return true;
            } catch (\Throwable $e) {
                if ($task instanceof EmailCallbackTask) {
                    $logFile = tempnam(sys_get_temp_dir(), "ecbError");
                    file_put_contents($logFile, $message->getBody());
                    $logContext['logFile'] = $logFile;
                }
                $this->handleException($e, $response, $logContext);
                $this->needRestart = true;
            }
        } finally {
            $this->saveResponse($task, $response, ((int) (\microtime(true) * 1000)) - $timeStart);

            if ($needRetry) {
                $priority = $message->get_properties()['priority'] ?? Process::PRIORITY_LOW;
                $this->logger->info("sending task to retry queue with delay $delay, priorioty: $priority");
                $this->asyncProcess->execute($task, $delay, true, $priority);
            }
            $this->handleStateClearing();
            $this->logger->popProcessor();
        }

        return true; // ACK
    }

    private function handleRetriesExceeded(Task $task, Response $response)
    {
        $this->logger->error(
            \sprintf(
                'retries (max: %d) exceeded for task: %s(%s)',
                $task->getMaxRetriesCount(),
                \get_class($task),
                $task->requestId
            )
        );
        $response->status = Response::STATUS_ERROR;
    }

    private function saveResponse(Task $task, Response $response, int $executionTime): void
    {
        $this->logger->info("saving response", ["task_response" => $response, "executedTimeMs" => $executionTime]);
        $this->storage->setResponse($task, $response);
    }

    private function handleException(\Throwable $exception, ?Response $response = null, array $loggerContext = []): void
    {
        if (\stripos(\get_class($exception), 'PHPUnit') !== false) {
            throw $exception;
        }

        if ($response) {
            $response->status = Response::STATUS_ERROR;
        }

        $this->logger->critical(
            \sprintf(
                '%s: %s (uncaught exception) at %s line %s',
                \get_class($exception),
                TraceProcessor::filterMessage($exception),
                $exception->getFile(),
                $exception->getLine()
            ),
            \array_merge(
                $loggerContext,
                ['traces' => StackTraceUtils::flattenExceptionTraces($exception)]
            )
        );
    }

    private function getDelay(AMQPMessage $message)
    {
        if (!$message->has('application_headers')) {
            return null;
        }

        $headers = $message->get('application_headers')->getNativeData();

        if (!isset($headers['x-delay'])) {
            return null;
        }

        return (int) $headers['x-delay'];
    }

    private function handleStateClearing(): void
    {
        try {
            $this->clearState();
        } catch (\Throwable $e) {
            $this->handleException($e);
            $this->needRestart = true;
        }
    }

    private function clearState(): void
    {
        global $_GET, $_POST, $_SERVER;

        $_GET = [];
        $_POST = [];
        $_SERVER = $this->backupServer;

        $this->entityManager->clear();

        if ($this->connection->isConnected()) {
            $this->connection->close();
        }

        $this->connection->connect();
    }
}
