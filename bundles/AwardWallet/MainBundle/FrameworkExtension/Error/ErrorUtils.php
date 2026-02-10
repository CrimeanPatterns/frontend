<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Error;

use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\UserErrorException;
use AwardWallet\MainBundle\Globals\StackTraceUtils;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidatorException;

abstract class ErrorUtils
{
    public static function isCriticalPhpError($code)
    {
        return !in_array($code, [E_USER_WARNING, E_USER_NOTICE, E_CORE_WARNING, E_WARNING, E_NOTICE, E_DEPRECATED, E_STRICT]);
    }

    public static function makeLogEntry(\Throwable $throwable): LogEntry
    {
        $message = self::formatMessage($throwable);
        $messageHash = md5($message);
        $context = [
            'traces' => StackTraceUtils::flattenExceptionTraces($throwable),
            'message_hash' => $messageHash,
        ];

        return new LogEntry($message, $context, $messageHash);
    }

    public static function formatMessage(\Throwable $throwable): string
    {
        return sprintf(
            '%s: %s (uncaught exception) at %s line %s',
            get_class($throwable),
            TraceProcessor::filterMessage($throwable),
            $throwable->getFile(),
            $throwable->getLine()
        );
    }

    public static function isCriticalThrowable(\Throwable $throwable): bool
    {
        $isCritical = false;

        if (method_exists($throwable, 'getSeverity')) {
            $isCritical = ErrorUtils::isCriticalPhpError($throwable->getSeverity());
        }

        if (!$throwable instanceof HttpExceptionInterface || $throwable->getStatusCode() >= 500) {
            $isCritical = true;
        }

        if ($throwable instanceof AccessDeniedException || $throwable instanceof ValidatorException || $throwable instanceof UserErrorException) {
            $isCritical = false;
        }

        return $isCritical;
    }
}
