<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess\Callback;

use AwardWallet\MainBundle\DependencyInjection\CallbackTaskAutowirePass\CallbackTaskAutowirePass;
use AwardWallet\MainBundle\DependencyInjection\CallbackTaskAutowirePass\CallbackTaskServiceLocatorHolder;
use AwardWallet\MainBundle\Globals\StaticClosureCodeExtractor;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Opis\Closure\SerializableClosure;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CallbackTaskExecutor implements ExecutorInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var ProducerInterface
     */
    private $delayedProducer;
    /**
     * @var CallbackTask[]
     */
    private $taskStack = [];
    /**
     * Delay in milliseconds from previous execution.
     *
     * @var int[]
     */
    private $delayStack = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ContainerInterface $container,
        ProducerInterface $delayedProducer,
        LoggerInterface $logger,
        $secretKey
    ) {
        $this->container = $container;
        $this->delayedProducer = $delayedProducer;
        $this->logger = $logger;

        SerializableClosure::setSecretKey($secretKey);
    }

    /**
     * @param CallbackTask|Task $task
     * @param int|null $delay
     * @return CallbackResponse|Response
     */
    public function execute(Task $task, $delay = null): Response
    {
        $func = $task->callable;

        if ($func instanceof SerializableClosure) {
            if (!$this->validateCode($task, $func->getReflector()->getCode())) {
                throw new \RuntimeException(sprintf('Incompatible code versions, task: "%s"', json_encode(['hash' => $task->getHash(), 'requestId' => $task->requestId])));
            }

            CallbackTask::prepareReflector($func->getReflector());
            $func = $func->getClosure();
        }

        if ($func instanceof \Closure && isset($task->closureScopeClass)) {
            $func = $func->bindTo(null, $task->closureScopeClass);
        }

        array_push($this->taskStack, $task);
        array_push($this->delayStack, $delay);

        try {
            $response = new CallbackResponse($func(
                ...$this->expandArguments(
                    $task->arguments,
                    $func,
                    $task
                )
            ));
        } finally {
            array_pop($this->taskStack);
            array_pop($this->delayStack);
        }

        return $response;
    }

    /**
     * @param int $seconds
     */
    public function delayTask($seconds)
    {
        if (!$this->taskStack) {
            return;
        }

        $task = $this->taskStack[count($this->taskStack) - 1];
        $this->logger->warning('delaying_task', ['hash' => $task->getHash(), 'requestId' => $task->requestId]);

        $this->delayedProducer->publish(@serialize($task), '', [
            'application_headers' => [
                'x-delay' => ['I', $seconds * 1000 + 1],
            ],
        ]);
    }

    /**
     * @return int
     */
    public function getLastDelay()
    {
        return (int) $this->delayStack[count($this->delayStack) - 1];
    }

    public function expBackoffDelayTask($maxExp, ?\Exception $lastException = null, $expBase = 2)
    {
        if ($this->delayStack) {
            if ($lastDelay = $this->getLastDelay()) {
                $newExp = floor(log($lastDelay / 1000, $expBase)) + 1;
            } else {
                $newExp = 0;
            }

            if ($newExp > $maxExp) {
                $task = $this->taskStack[count($this->taskStack) - 1];
                $taskException = new TaskRetriesExceededException(
                    sprintf("Task retries exceeded, max: %d, task: %s", $maxExp, json_encode(['hash' => $task->getHash(), 'requestId' => $task->requestId])),
                    0,
                    $lastException
                );

                throw new TaskRetriesExceededException(sprintf("Task retries exceeded, file: %s, max: %d", ($task->fileName ?? 'undefined') . ':' . ($task->startLine ?? 'undefined'), $maxExp), 0, $taskException);
            }

            $seconds = $expBase ** $newExp;
        } else {
            $seconds = 1;
        }

        $this->delayTask($seconds);
    }

    /**
     * @param string $unknownCode
     * @return bool
     */
    protected function validateCode(CallbackTask $task, $unknownCode)
    {
        $codeExtractor = new StaticClosureCodeExtractor(
            $task->fileName,
            $task->startLine,
            $task->endLine,
            $task->closureScopeClass,
            $task->namespace
        );

        return $codeExtractor->getCode() === $unknownCode;
    }

    protected function expandArguments(array $arguments, callable $func, CallbackTask $task): array
    {
        if ($func instanceof \Closure) {
            if ($arguments && $this->arrayIsSequential($arguments)) {
                return $this->expandFromContainer($arguments);
            }

            $refl = new \ReflectionFunction($func);
            $callerId = CallbackTaskAutowirePass::createServiceId(
                $task->fileName,
                $task->startLine
            );

            $locator = null;
            $locatorHolderParams = [];

            if ($this->container->has($callerId)) {
                /** @var CallbackTaskServiceLocatorHolder $locatorHolder */
                $locatorHolder = $this->container->get($callerId);
                $locator = $locatorHolder->getLocator();
                $locatorHolderParams = $locatorHolder->getParametersMap();
            }

            $reflParams = $refl->getParameters();
            $newArguments = [];

            foreach ($reflParams as $reflParam) {
                $paramName = $reflParam->getName();

                if (\array_key_exists($paramName, $arguments)) {
                    $newArguments[] = $arguments[$paramName];
                } elseif (\array_key_exists($paramName, $locatorHolderParams)) {
                    $newArguments[] = $locatorHolderParams[$paramName];
                } elseif ($locator && $locator->has($paramName)) {
                    $newArguments[] = $locator->get($paramName);
                } elseif ($reflParam->isDefaultValueAvailable()) {
                    $newArguments[] = $reflParam->getDefaultValue();
                } elseif ($reflParamClass = $reflParam->getClass()) {
                    $newArguments[] = new Service($reflParamClass->getName());
                }
            }

            $arguments = $newArguments;
        }

        return $this->expandFromContainer($arguments);
    }

    private function expandFromContainer(array $arguments): array
    {
        return
            it($arguments)
            ->map(function ($argument) {
                if ($argument instanceof Service) {
                    return $this->container->get($argument->getName());
                } elseif ($argument instanceof Parameter) {
                    return $this->container->getParameter($argument->getName());
                } else {
                    return $argument;
                }
            })
            ->toArrayWithKeys();
    }

    private function arrayIsSequential(array $array): bool
    {
        if ($count = \count($array)) {
            return
                (isset($array[0]) || \array_key_exists(0, $array))
                && (\array_keys($array) === \range(0, $count - 1));
        }

        return true;
    }
}
