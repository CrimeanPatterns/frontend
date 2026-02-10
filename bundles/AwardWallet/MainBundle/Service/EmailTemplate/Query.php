<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Query
{
    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    protected $statement;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var PreparedSQL
     */
    private $preparedSql;

    /**
     * @var PreparedSQL
     */
    private $preparedCountSql;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $debugInfo = [];

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     * @return Query
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return PreparedSQL
     */
    public function getPreparedSql()
    {
        return $this->preparedSql;
    }

    /**
     * @param PreparedSQL $preparedSql
     * @return Query
     */
    public function setPreparedSql($preparedSql)
    {
        $this->preparedSql = $preparedSql;

        return $this;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function getStatement()
    {
        if ($this->statement) {
            return $this->statement;
        }

        return $this->statement = $this->connection->executeQuery(
            $this->preparedSql->getSql(),
            $this->preparedSql->getParams(),
            $this->preparedSql->getTypes()
        );
    }

    /**
     * @param \Doctrine\DBAL\Driver\Statement $statement
     * @return Query
     */
    public function setStatement($statement)
    {
        $this->statement = $statement;

        return $this;
    }

    /**
     * @return PreparedSQL
     */
    public function getPreparedCountSql()
    {
        return $this->preparedCountSql;
    }

    /**
     * @param PreparedSQL $preparedCountSql
     * @return Query
     */
    public function setPreparedCountSql($preparedCountSql)
    {
        $this->preparedCountSql = $preparedCountSql;

        return $this;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if (is_int($this->count)) {
            return $this->count;
        }

        return $this->count = (int) $this->connection->executeQuery(
            $this->preparedCountSql->getSql(),
            $this->preparedCountSql->getParams(),
            $this->preparedCountSql->getTypes()
        )->fetchColumn();
    }

    /**
     * @param int $count
     * @return Query
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     * @return Query
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public function addDebug($message, $data = null)
    {
        $this->debugInfo[$message] = $data;

        return $this;
    }

    public function getDebug()
    {
        return $this->debugInfo;
    }
}
