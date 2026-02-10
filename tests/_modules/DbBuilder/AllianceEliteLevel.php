<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class AllianceEliteLevel extends AbstractDbEntity
{
    private ?Alliance $alliance;

    public function __construct(
        int $rank,
        string $name,
        ?Alliance $alliance = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'Rank' => $rank,
            'Name' => $name,
        ]));

        $this->alliance = $alliance;
    }

    public function getAlliance(): ?Alliance
    {
        return $this->alliance;
    }

    public function setAlliance(?Alliance $alliance): self
    {
        $this->alliance = $alliance;

        return $this;
    }
}
