<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use Doctrine\DBAL\Connection;

class SqlExecutor implements ExecutorInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param SqlTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        return new SqlResponse($this->connection->executeQuery($task->sql, $task->params)->fetchAll(\PDO::FETCH_ASSOC));
    }
}
