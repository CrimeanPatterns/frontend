<?php

namespace AwardWallet\MainBundle\Service\Backup\Model;

use AwardWallet\MainBundle\Service\Backup\ProcessorInterestInterface;
use Symfony\Component\Console\Input\InputInterface;

class ProcessorInterest implements ProcessorInterestInterface
{
    /**
     * @var array ["tableName" => [callable1,  callable2, ..],
     */
    private $onExportRow = [];
    private InputInterface $input;
    /**
     * @var array ["tableName" => ["join Usr on Usr.UserID = Account.UserID",  "join Cart on ..", ..],
     */
    private array $joins = [];
    /**
     * @var array ["tableName" => ["DATE('Y-m-d') as PostingDate, Usr.FirstName", "CONCAT(Usr.LastName, Usr.Title)"]
     */
    private array $extraColumns = [];
    /**
     * @var callable[]
     */
    private array $postProcessors = [];
    private bool $fullDump;

    public function __construct(InputInterface $input, bool $fullDump)
    {
        $this->input = $input;
        $this->fullDump = $fullDump;
    }

    public function addOnExportRow(string $table, callable $callback): self
    {
        $this->onExportRow[$table][] = $callback;

        return $this;
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function isFullDump(): bool
    {
        return $this->fullDump;
    }

    public function addJoin(string $table, string $join): ProcessorInterestInterface
    {
        $this->joins[$table][] = $join;

        return $this;
    }

    public function addExtraColumns(string $table, string $columns): ProcessorInterestInterface
    {
        $this->extraColumns[$table][] = $columns;

        return $this;
    }

    public function addPostProcessor(callable $callback): ProcessorInterestInterface
    {
        $this->postProcessors[] = $callback;

        return $this;
    }

    public function getOnExportRow(): array
    {
        return $this->onExportRow;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getExtraColumns(): array
    {
        return $this->extraColumns;
    }

    public function getPostProcessors(): array
    {
        return $this->postProcessors;
    }
}
