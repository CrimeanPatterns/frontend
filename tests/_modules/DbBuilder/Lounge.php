<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Lounge extends AbstractDbEntity
{
    /**
     * @var LoungeAction[]
     */
    private array $loungeActions = [];

    public function __construct(string $airCode, string $name, array $fields = [])
    {
        parent::__construct(array_merge([
            'IsAvailable' => 1,
            'Visible' => 1,
        ], $fields, [
            'AirportCode' => $airCode,
            'Name' => $name,
        ]));
    }

    public function addLoungeAction(LoungeAction $action): self
    {
        $this->loungeActions[] = $action;

        return $this;
    }

    public function setLoungesActions(array $loungeActions): self
    {
        $this->loungeActions = $loungeActions;

        return $this;
    }

    public function getLoungeActions(): array
    {
        return $this->loungeActions;
    }
}
