<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\Common\Monolog\Processor\TraceProcessor;

class StackTraceUtils
{
    /**
     * @param array $allowedFrameFields fram fields to pass empty array to disable filter
     * @param int $frameSkipCount frames to skip starting from current call site
     * @param int $debugBackTraceOptions options for \debug_backtrace function called internally
     * @return array[]
     */
    public static function getFilteredStackTrace(
        array $allowedFrameFields = [],
        $frameSkipCount = 0,
        $debugBackTraceOptions = DEBUG_BACKTRACE_IGNORE_ARGS
    ): array {
        $allowedFrameFields = $allowedFrameFields ?
            array_combine(
                $allowedFrameFields,
                array_pad([], count($allowedFrameFields), null)
            ) : [];

        $result = [];
        $lowestAllowedFrameOffset = $frameSkipCount + 1; // skip self::getFilteredStackTrace frame

        foreach (array_slice(debug_backtrace($debugBackTraceOptions), $lowestAllowedFrameOffset) as $frame) {
            $result[] = $allowedFrameFields ? array_intersect_key($frame, $allowedFrameFields) : $frame;
        }

        return $result;
    }

    public static function flattenExceptionTraces(\Throwable $exception): array
    {
        $result = [];

        while ($exception !== null) {
            $result[] = self::formatExceptionContext($exception);
            $exception = $exception->getPrevious();
        }

        return $result;
    }

    public static function formatExceptionContext(\Throwable $exception): array
    {
        return [
            'message' => TraceProcessor::filterMessage($exception),
            'trace' => TraceProcessor::filterBackTrace($exception->getTrace()),
            'class' => get_class($exception),
        ];
    }
}
