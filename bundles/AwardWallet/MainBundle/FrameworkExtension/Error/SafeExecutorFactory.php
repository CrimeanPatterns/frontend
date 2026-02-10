<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Error;

class SafeExecutorFactory
{
    /**
     * @var AggregateErrorReporter
     */
    private $aggregateErrorReporter;
    /**
     * @var bool
     */
    private $isDebug;

    public function __construct(AggregateErrorReporter $aggregateErrorReporter, bool $isDebug)
    {
        $this->aggregateErrorReporter = $aggregateErrorReporter;
        $this->isDebug = $isDebug;
    }

    public function __invoke(callable $block): SafeExecutor
    {
        return $this->make($block);
    }

    public function make(callable $block): SafeExecutor
    {
        return new SafeExecutor($block, function (\Throwable $throwable) {
            if ($this->isDebug) {
                throw $throwable;
            }

            $this->aggregateErrorReporter->logThrowable($throwable);
        });
    }
}
