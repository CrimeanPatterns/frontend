<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Error;

class SafeExecutor
{
    /**
     * @var callable
     */
    private $block;
    /**
     * @var ?callable
     */
    private $exceptionHandler;

    public function __construct(callable $block, ?callable $exceptionHandler = null)
    {
        $this->block = $block;
        $this->exceptionHandler = $exceptionHandler;
    }

    public function __invoke()
    {
        return $this->run();
    }

    public function orValue($orElseValue): self
    {
        return new self(
            function () use ($orElseValue) {
                try {
                    return ($this->block)();
                } catch (\Throwable $throwable) {
                    $this->handleThrowable($throwable);

                    return $orElseValue;
                }
            },
            $this->exceptionHandler
        );
    }

    public function orElse(callable $orElseBlock): self
    {
        return new self(
            function () use ($orElseBlock) {
                try {
                    return ($this->block)();
                } catch (\Throwable $throwable) {
                    $this->handleThrowable($throwable);

                    return $orElseBlock($throwable);
                }
            },
            $this->exceptionHandler
        );
    }

    public function run()
    {
        try {
            return ($this->block)();
        } catch (\Throwable $throwable) {
            $this->handleThrowable($throwable);

            return null;
        }
    }

    public function runOrNull()
    {
        return $this->runOrValue(null);
    }

    public function runOrValue($value)
    {
        return $this->orValue($value)->run();
    }

    public function runOrElse(callable $elseBlock)
    {
        return $this->orElse($elseBlock)->run();
    }

    public static function create(callable $callable, ?callable $exceptionHandler = null): self
    {
        return new self($callable);
    }

    private function handleThrowable(\Throwable $throwable): void
    {
        if (isset($this->exceptionHandler)) {
            ($this->exceptionHandler)($throwable);
        }
    }
}
