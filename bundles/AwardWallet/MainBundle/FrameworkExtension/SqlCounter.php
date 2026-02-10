<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SqlCounter implements SQLLogger
{
    protected const NOT_SECRET_SLOW_QUERY_LOG_TRIGGER_HASH = '2y05PjOEkQFVWSznZRzdr5Q2OgWkeqg5LIU8yhVsPcHQ4bqYDK327S';
    protected const SLOW_QUERY_LOG_TOTAL_TIME_AUTO_TRIGGER_THRESHOLD = 5000; // ms
    protected const SLOW_QUERY_LOG_QUERY_TIME_AUTO_TRIGGER_THRESHOLD = 50; // ms

    private $count = 0;
    private $time = 0;
    private $startTime;
    private $slowQueryLogThreshold;
    private $slowQueriesMax = 10;
    private $slowQueries = [];
    private $lastQuery;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string     $sql    the SQL to be executed
     * @param array|null $params the SQL parameters
     * @param array|null $types  the SQL parameter types
     * @return void
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        $this->startTime = \microtime(true);
        $this->lastQuery = $sql;
        $this->count++;
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery()
    {
        $queryTime = \microtime(true) - $this->startTime;
        $this->time += $queryTime;
        $queryTimeMs = $queryTime * 1000;

        if (
            (
                isset($this->slowQueryLogThreshold)
                && ($queryTimeMs > $this->slowQueryLogThreshold) // ms
            )
            || (
                !isset($this->slowQueryLogThreshold)
                && ($queryTimeMs > self::SLOW_QUERY_LOG_QUERY_TIME_AUTO_TRIGGER_THRESHOLD) // ms
            )
        ) {
            $this->slowQueries[] = [
                'time' => (int) $queryTimeMs,
                'source' => $this->getAwSource(),
            ];
        }

        $this->lastQuery = null;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function init(Connection $connection)
    {
        $config = $connection->getConfiguration();

        if (empty($config->getSQLLogger())) {
            $config->setSQLLogger($this);
        }

        $this->count = 0;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $trigger = null;

        if ($request->headers->has('X-Slow-Query-Log-Trigger')) {
            $trigger = $request->headers->get('X-Slow-Query-Log-Trigger');
        } elseif ($request->cookies->has('X-Slow-Query-Log-Trigger')) {
            $trigger = $request->cookies->get('X-Slow-Query-Log-Trigger');
        }

        if (!\is_null($trigger)) {
            $triggerParts = \explode(',', $trigger);

            if (\count($triggerParts) === 2) {
                [$triggerHash, $triggerThreshold] = $triggerParts;
            } elseif (\count($triggerParts) === 3) {
                [$triggerHash, $triggerThreshold, $maxSlow] = $triggerParts;
            } else {
                return;
            }

            if (self::NOT_SECRET_SLOW_QUERY_LOG_TRIGGER_HASH === $triggerHash) {
                $this->slowQueryLogThreshold = \intval($triggerThreshold);
            }

            if (isset($maxSlow)) {
                $this->slowQueriesMax = \intval($maxSlow);
            }
        }
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();
        $totalTimeMs = $this->time * 1000;
        $context = [
            "queries" => $this->count,
            "sqlTime" => \round($totalTimeMs),
            "route" => $request->attributes->get('_route'),
            'memory' => (int) (\memory_get_peak_usage(true) / 1024 / 1024), // kilobytes
        ];

        if (
            $request->headers->has(MobileHeaders::MOBILE_DEVICE_UUID)
            && StringUtils::isNotEmpty($deviceUuid = $request->headers->get(MobileHeaders::MOBILE_DEVICE_UUID))
        ) {
            $context['deviceUuid'] = $deviceUuid;
        }

        if ($request->attributes->has('start_time')) {
            $context['php_time'] = \round((\microtime(true) - (float) $request->attributes->get('start_time')) * 1000);
        }

        if (
            $this->slowQueries
            && (
                isset($this->slowQueryLogThreshold)
                || ($totalTimeMs > self::SLOW_QUERY_LOG_TOTAL_TIME_AUTO_TRIGGER_THRESHOLD)
            )
        ) {
            \usort($this->slowQueries, function ($a, $b) { return $b['time'] <=> $a['time']; });
            $context['slowQueries'] = \array_slice($this->slowQueries, 0, $this->slowQueriesMax);
            $context['slowQueriesExists'] = true;
        }

        $this->slowQueries = [];
        $this->slowQueriesMax = 10;

        $this->logger->info(
            "stat",
            $context
        );
        $this->slowQueryLogThreshold = null;
    }

    private function getAwSource(): array
    {
        $backtrace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
        $skippedFramesCount = 0;

        $frames =
            it($backtrace)
            ->drop(2) // drop SqlCounter methods
            ->increment($skippedFramesCount)
            ->dropWhile(function ($frame) {
                return
                    !(
                        isset($frame['file'])
                        && (
                            (\strpos($frame['file'], '/www/awardwallet/bundles/AwardWallet') === 0)
                            && (\strpos($frame['file'], '/www/awardwallet/bundles/AwardWallet/MainBundle/Globals/Utils') === false) // skip iterators
                        )
                    );
            })
            ->map(function ($frame) {
                return
                    ($frame['file'] ?? 'file_not_set') .
                    ':' .
                    ($frame['line'] ?? 'line_not_set') .
                    ' (' .
                    ($frame['class'] ?? '') .
                    ($frame['type'] ?? '') .
                    ($frame['function'] ?? '') .
                    ')';
            })
            ->take(5)
            ->toArray();

        if ($skippedFramesCount > 0) {
            \array_unshift($frames, "...frames skipped: {$skippedFramesCount}");
        }

        return $frames;
    }
}
