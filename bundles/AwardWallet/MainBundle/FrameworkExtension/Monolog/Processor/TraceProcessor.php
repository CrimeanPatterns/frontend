<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Monolog\Processor;

use AwardWallet\MainBundle\FrameworkExtension\Error\ErrorUtils;
use AwardWallet\MainBundle\Globals\StackTraceUtils;

class TraceProcessor
{
    /**
     * @var int
     */
    private $level;

    public function __construct(int $level)
    {
        $this->level = $level;
    }

    /**
     * Filter sensitive data from exception message and stack. Do not add unrelated ad-hoc functionality.
     */
    public function __invoke(array $record): array
    {
        if ($record['level'] < $this->level) {
            return $record;
        }

        $exception = $record['context']['exception'] ?? null;

        if ($exception instanceof \Throwable) {
            $record['message'] = ErrorUtils::formatMessage($exception);
            $record['context']['traces'] = StackTraceUtils::flattenExceptionTraces($exception);
            $record['context']['message_hash'] = \md5($exception->getMessage());
            unset($record['context']['exception']);
        }

        return $record;
    }
}
