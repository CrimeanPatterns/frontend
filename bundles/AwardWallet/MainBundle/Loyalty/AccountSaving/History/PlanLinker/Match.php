<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

class Match
{
    /**
     * @var string
     */
    private $table;
    /**
     * @var int
     */
    private $id;

    public function __construct(string $table, int $id)
    {
        $this->table = $table;
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
