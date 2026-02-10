<?php

namespace AwardWallet\MainBundle\Service\TaskScheduler;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use AwardWallet\MainBundle\Globals\StackTraceUtils;
use AwardWallet\MainBundle\Worker\ProcessControlWrapper;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Processor\ProcessorInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface as BaseConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ConsumerRouter implements BaseConsumerInterface
{
    private const MAX_EXECUTION_SECONDS = 120;

    private LoggerInterface $logger;

    private ProcessorInterface $logProcessor;

    private Producer $producer;

    private ServiceLocator $consumers;

    private ProcessControlWrapper $processControl;

    private AppProcessor $appProcessor;

    private EntityManagerInterface $entityManager;

    private Connection $connection;

    private int $startTime;

    private bool $needRestart = false;

    private array $backupServer;

    public function __construct(
        LoggerFactory $loggerFactory,
        Producer $producer,
        ServiceLocator $taskSchedulerConsumerList,
        AppProcessor $appProcessor,
        ProcessControlWrapper $processControlWrapper,
        EntityManagerInterface $entityManager,
        Connection $connection
    ) {
        // probably this is for dev
        $this->backupServer = $_SERVER ?? [];
        $this->startTime = time();

        $this->logProcessor = $loggerFactory->createProcessor();
        $this->logger = $loggerFactory->createLogger($this->logProcessor);
        $this->producer = $producer;
        $this->consumers = $taskSchedulerConsumerList;
        $this->appProcessor = $appProcessor;
        $this->entityManager = $entityManager;
        $this->connection = $connection;
        $this->processControl = $processControlWrapper;

        $this->logger->info('started');
    }

    public function execute(AMQPMessage $message)
    {
        $this->appProcessor->setNewRequestId();

        if (
            (time() - $this->startTime > self::MAX_EXECUTION_SECONDS)
            || $this->needRestart
        ) {
            $this->processControl->exit(1);

            // unreachable in production
            return true;
        }

        $task = @unserialize($message->body);

        if (!($task instanceof TaskInterface)) {
            $this->logger->error('invalid message', ['body' => $message->body]);

            return true; // ACK
        }

        $this->logProcessor->setBaseContext([
            'task_requestid' => $task->getRequestId(),
            'task_class' => \get_class($task),
            'worker' => 'task_scheduler',
            'serviceId' => $task->getServiceId(),
        ]);

        // ms
        $timeStart = $this->getTimestamp();
        $needRetry = false;
        $delay = 0;

        try {
            if ($task->getMaxRetriesCount() < $task->getCurrentRetriesCount()) {
                $this->handleRetriesExceeded($task);

                return true; // ACK
            }

            $this->logger->info(sprintf(
                'processing task %s_%s for the %d/%d time',
                \get_class($task),
                $task->getRequestId(),
                $task->getCurrentRetriesCount() + 1,
                $task->getMaxRetriesCount() + 1
            ));

            try {
                $consumer = $this->consumers->get($task->getServiceId());

                if (!$consumer instanceof ConsumerInterface) {
                    throw new \RuntimeException(sprintf('Consumer for serviceId %s not found', $task->getServiceId()));
                }

                $consumer->consume($task);
            } catch (TaskNeedsRetryException $e) {
                $task->incrementRetriesCount();

                if ($task->getMaxRetriesCount() < $task->getCurrentRetriesCount()) {
                    $this->handleRetriesExceeded($task);
                } else {
                    $this->logger->info(sprintf(
                        'task %s_%s, retry %d/%d',
                        \get_class($task),
                        $task->getRequestId(),
                        $task->getCurrentRetriesCount(),
                        $task->getMaxRetriesCount()
                    ));

                    $needRetry = true;
                    $delay = $e->getDelay();
                }

                return true;
            } catch (\Throwable $e) {
                $this->handleException($e);
                $this->needRestart = true;
            }
        } finally {
            $this->logger->info(sprintf(
                'task %s_%s finished in %d ms',
                \get_class($task),
                $task->getRequestId(),
                $this->getTimestamp() - $timeStart
            ));

            if ($needRetry) {
                $priority = $message->get_properties()['priority'] ?? Producer::PRIORITY_LOW;
                $this->logger->info(sprintf(
                    'sending task %s_%s to retry queue with delay %d, priority %d',
                    \get_class($task),
                    $task->getRequestId(),
                    $delay,
                    $priority
                ));
                $this->producer->publish($task, $priority, $delay);
            }

            $this->handleStateClearing();
        }

        return true; // ACK
    }

    /**
     * @return int current timestamp in milliseconds
     */
    private function getTimestamp(): int
    {
        return (int) (\microtime(true) * 1000);
    }

    private function handleStateClearing(): void
    {
        global $_GET, $_POST, $_SERVER;

        try {
            $_GET = [];
            $_POST = [];
            $_SERVER = $this->backupServer;

            $this->entityManager->clear();

            if ($this->connection->isConnected()) {
                $this->connection->close();
            }

            $this->connection->connect();
        } catch (\Throwable $e) {
            $this->handleException($e);
            $this->needRestart = true;
        }
    }

    private function handleException(\Throwable $exception, array $loggerContext = []): void
    {
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

    private function handleRetriesExceeded(TaskInterface $task): void
    {
        $this->logger->error(
            \sprintf(
                'retries (max: %d) exceeded for task: %s_%s',
                $task->getMaxRetriesCount(),
                \get_class($task),
                $task->getRequestId()
            )
        );
    }
}
