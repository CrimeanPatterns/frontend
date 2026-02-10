<?php

namespace AwardWallet\MainBundle\Manager;

use Doctrine\DBAL\Connection;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\ExtractorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DbTranslationManager implements ExtractorInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $tables = [];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Connection $connection, TranslatorInterface $translator, array $locales, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->translator = $translator;
        $this->locales = array_filter($locales, function ($locale) { return $locale != 'en'; });
        $this->logger = $logger;
    }

    public function addTable($table, array $fields, $filter = null)
    {
        $this->tables[$table] = [
            'fields' => $fields,
            'filter' => $filter,
        ];
    }

    public function extract()
    {
        $catalogue = new MessageCatalogue();

        foreach ($this->tables as $tableName => $value) {
            $tableFields = $value['fields'];

            foreach ($this->getQuery($tableName, $tableFields, $value['filter']) as $row) {
                foreach ($tableFields as $fieldName) {
                    $id = strtolower($fieldName) . "." . $row['ID'];
                    $message = new Message($id, strtolower($tableName));
                    $message->setDesc($row[$fieldName]);
                    $catalogue->add($message);
                }
            }
        }

        return $catalogue;
    }

    private function getQuery($tableName, array $tableFields, $filter)
    {
        $tablePK = $this->getPrimaryColumn($tableName);
        $sql = "SELECT " . $tablePK . " AS ID, " . implode(", ", $tableFields) . " FROM " . $tableName . ($filter ? ' WHERE ' . $filter : '');
        $q = $this->connection->query($sql);
        $q->setFetchMode(\PDO::FETCH_ASSOC);

        return $q;
    }

    private function getPrimaryColumn($tableName)
    {
        $sql = "DESCRIBE `{$tableName}`";
        $st = $this->connection->prepare($sql);
        $st->execute();

        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            if ('PRI' === $row['Key']) {
                return $row['Field'];
            }
        }

        throw new \RuntimeException('Unable to find primary column for table \'' . $tableName . '\'');
    }
}
