<?php

namespace Codeception\Module;

use Codeception\Lib\Driver\Db as Driver;
use Codeception\Lib\ModuleContainer;
use Codeception\TestInterface;
use PHPUnit\Framework\Exception;

class CustomDb extends Db
{
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config['remove_inserted'] = true;

        parent::__construct($moduleContainer, $config);
    }

    public function _initialize()
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $container = $symfony->_getContainer();

        $this->config['dsn'] = 'mysql:host=' . $container->getParameter("database_host") . ':' . $container->getParameter("database_port") . ';dbname=' . $container->getParameter("database_name");
        $this->config['user'] = $container->getParameter("database_user");
        $this->config['password'] = $container->getParameter('database_password');
        $this->backupConfig['dsn'] = $this->config['dsn'];
        $this->backupConfig['user'] = $this->config['user'];
        $this->backupConfig['password'] = $this->config['password'];

        parent::_initialize();
    }

    public function _after(TestInterface $test)
    {
        if ($this->config['remove_inserted'] === false) {
            $this->insertedRows = [];
        }

        parent::_after($test);
    }

    /**
     * @param array|int $rowCriteria
     */
    public function haveInsertedInDatabase($table, $rowCriteria)
    {
        $this->insertedRows[$this->currentDatabase][] = [
            'table' => $table,
            'primary' => $rowCriteria,
        ];
    }

    /**
     * @param string $query
     * @return int affected rows count
     */
    public function executeQuery($query)
    {
        return $this->catchGoneAway(function () use ($query) { return $this->_getDbh()->exec($query); });
    }

    /**
     * @param string $query
     * @return \PDOStatement
     */
    public function query($query, $params = null)
    {
        return $this->catchGoneAway(function () use ($query, $params) {
            $statement = $this->_getDbh()->prepare($query, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $statement->execute($params);

            return $statement;
        });
    }

    public function getLastInsertId()
    {
        return $this->catchGoneAway(function () { return $this->_getDbh()->lastInsertId(); });
    }

    public function shouldHaveInDatabase($tableName, array $values)
    {
        $key = $this->getPrimaryColumn($tableName);
        $existing = $this->grabFromDatabase($tableName, $key, $values);

        if (empty($existing)) {
            return $this->haveInDatabase($tableName, $values);
        }

        return $existing;
    }

    public function grabCountFromDatabase($table, $criteria = [])
    {
        return $this->catchGoneAway(function () use ($table, $criteria) { return $this->proceedSeeInDatabase($table, 'count(*)', $criteria); });
    }

    public function haveInDatabase($table, array $data)
    {
        return $this->catchGoneAway(function () use ($table, $data) { return parent::haveInDatabase($table, $data); });
    }

    //    public function updateInDatabase($table, array $key, array $data)
    //    {
    //        $keyColumns = array_map(
    //            [$this->driver, 'getQuotedName'],
    //            array_keys($key)
    //        );
    //        $dataColumns = array_map(
    //            [$this->driver, 'getQuotedName'],
    //            array_keys($data)
    //        );
    //
    //        $query = sprintf(
    //            "UPDATE %s SET %s WHERE %s",
    //            $this->driver->getQuotedName($table),
    //            implode(', ', array_map(function ($column) { return "{$column} = ?"; }, $dataColumns)),
    //            implode(
    //                ' AND ',
    //                array_map(
    //                    function ($column) use ($key) {
    //                        return null === $key[trim($column, '`')] ?
    //                            "{$column} IS NULL" :
    //                            "{$column} = ?";
    //                    },
    //                    $keyColumns
    //                )
    //            )
    //        );
    //
    //        $parameters = array_merge(
    //            array_values($data),
    //            array_filter(
    //                array_values($key),
    //                function ($datum) { return isset($datum); }
    //            )
    //        );
    //        $this->debugSection('Query', $query);
    //        $this->debugSection('Parameters', $parameters);
    //        $this->driver->executeQuery($query, $parameters);
    //    }

    //    protected function removeInserted() : void
    //    {
    //        foreach ($this->insertedRows as $i => $insertedRow) {
    //            $column = key($insertedRow['primary']);
    //            $id = array_values($insertedRow['primary'])[0];
    //
    //            try {
    //                $query = "delete from {$insertedRow['table']} where $column = {$id}";
    //                $this->debugSection('Query', $query);
    //                $this->driver->getDbh()->exec($query);
    //            } catch (\Exception $e) {
    //                $this->debug("coudn\\'t delete record {$id} from {$insertedRow['table']}");
    //            }
    //
    //            unset($this->insertedRows[$i]);
    //        }
    //    }

    protected function proceedSeeInDatabase($table, $column, $criteria)
    {
        return $this->catchGoneAway(function () use ($table, $column, $criteria) { return parent::proceedSeeInDatabase($table, $column, $criteria); });
    }

    private function getPrimaryColumn($tableName)
    {
        $st = $this->query("DESCRIBE `{$tableName}`");

        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            if ('PRI' === $row['Key']) {
                return $row['Field'];
            }
        }

        throw new \RuntimeException('Unable to find primary column for table \'' . $tableName . '\'');
    }

    private function catchGoneAway(callable $function)
    {
        $processException = function (\Exception $e) use ($function) {
            if (stripos($e->getMessage(), 'server has gone away') !== false) {
                $this->drivers['default'] = Driver::create($this->config['dsn'], $this->config['user'], $this->config['password']);
                $this->dbhs['default'] = $this->drivers['default']->getDbh();

                return call_user_func($function);
            } else {
                throw $e;
            }
        };

        try {
            // MySql gone away will be issued as warning, despite of  $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            // in Driver/Db, so we will supress warnings
            return @call_user_func($function);
        } catch (\PDOException $e) {
            return $processException($e);
        } catch (Exception $e) {
            return $processException($e);
        }
    }
}
