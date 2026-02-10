<?php

namespace AwardWallet\MainBundle\Service\Backup;

use Symfony\Component\Console\Input\InputInterface;

interface ProcessorInterestInterface
{
    public function getInput(): InputInterface;

    public function isFullDump(): bool;

    /**
     * @param string $join - for example: join Usr on Account.UserID = Usr.UserID
     */
    public function addJoin(string $table, string $join): self;

    /**
     * @param string $columns - for example: "DATE('Y-m-d') as PostingDate, Usr.FirstName"
     */
    public function addExtraColumns(string $table, string $columns): self;

    public function addOnExportRow(string $table, callable $callback): self;

    public function addPostProcessor(callable $callback): self;
}
