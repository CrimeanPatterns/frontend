<?php

namespace AwardWallet\MainBundle\Globals\LoggerContext;

use Monolog\Handler\PsrHandler;
use Monolog\Logger;

class Span extends Logger
{
    private ContextAwareLoggerWrapper $ctxLW;
    private bool $isStopped = false;
    private string $contextKey;

    public function __construct(ContextAwareLoggerWrapper $ctxLW, string $contextKey)
    {
        parent::__construct("decorated_span_{$contextKey}", [new PsrHandler($ctxLW)]);

        $this->ctxLW = $ctxLW;
        $this->contextKey = $contextKey;
    }

    public function __destruct()
    {
        if ($this->isStopped) {
            return;
        }

        $this->isStopped = true;
        $this->ctxLW->popContext($this->contextKey);
    }

    public function stop(): void
    {
        if ($this->isStopped) {
            return;
        }

        $this->isStopped = true;
        $this->ctxLW->popContext($this->contextKey);
    }
}
