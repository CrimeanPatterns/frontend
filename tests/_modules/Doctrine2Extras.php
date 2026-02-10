<?php

namespace Codeception\Module;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\LoggerChain;
use Doctrine\DBAL\Logging\SQLLogger;

class Doctrine2Extras extends \Codeception\Module
{
    public function assertNoQueries(Connection $connection, callable $worker)
    {
        return $this->assertQueriesCount(0, $connection, $worker);
    }

    public function assertQueriesCount($count, Connection $connection, callable $worker)
    {
        /** @var LoggerChain $loggerChain */
        $loggerChain = $connection->getConfiguration()->getSQLLogger();

        $loggerCreated = false;

        if (null === $loggerChain) {
            $loggerCreated = true;
            $loggerChain = new LoggerChain();
            $connection->getConfiguration()->setSQLLogger($loggerChain);
        }

        $reflLoggers = new \ReflectionProperty(LoggerChain::class, 'loggers');
        $reflLoggers->setAccessible(true);

        $counter = new SqlCounter();
        $counterUid = spl_object_hash($counter);

        $reflLoggers->setValue($loggerChain, array_merge([$counter], $reflLoggers->getValue($loggerChain)));
        $reflLoggers->setAccessible(false);

        $workerResult = call_user_func($worker);

        $reflLoggers->setAccessible(true);
        $chainLoggers = $reflLoggers->getValue($loggerChain);
        $newLoggers = [];

        foreach ($chainLoggers as $logger) {
            if (spl_object_hash($logger) !== $counterUid) {
                $newLoggers[] = $logger;
            }
        }

        if (count($newLoggers) < count($chainLoggers)) {
            $reflLoggers->setValue($loggerChain, $newLoggers);
        }

        $reflLoggers->setAccessible(false);

        $this->assertEquals($count, $counter->getCounter(), 'Queries count mismatch:');

        if ($loggerCreated) {
            $connection->getConfiguration()->setSQLLogger(null);
        }

        return $workerResult;
    }
}

class SqlCounter implements SQLLogger
{
    /**
     * @var int
     */
    private $counter;
    /**
     * @var array
     */
    private $buffer;

    public function __construct()
    {
        $this->counter = 0;
    }

    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        $this->counter++;
        $this->buffer[] = [$sql, $params, $types];
    }

    public function stopQuery()
    {
    }

    /**
     * @return int
     */
    public function getCounter()
    {
        return $this->counter;
    }
}
