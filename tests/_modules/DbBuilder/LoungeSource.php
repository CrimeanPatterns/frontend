<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Globals\StringHandler;

class LoungeSource extends AbstractDbEntity
{
    private ?Lounge $lounge = null;

    /**
     * @var LoungeSourceChange[]
     */
    private array $loungeSourceChanges = [];

    public function __construct(string $airCode, string $name, string $sourceCode, array $fields = [])
    {
        parent::__construct(array_merge([
            'IsAvailable' => 1,
            'SourceID' => StringHandler::getRandomCode(10),
            'PageBody' => StringHandler::getRandomCode(10),
        ], $fields, [
            'AirportCode' => $airCode,
            'Name' => $name,
            'SourceCode' => $sourceCode,
        ]));
    }

    public function getLounge(): ?Lounge
    {
        return $this->lounge;
    }

    public function setLounge(Lounge $lounge): self
    {
        $this->lounge = $lounge;

        return $this;
    }

    public function addLoungeSourceChange(LoungeSourceChange $loungeSourceChange): self
    {
        $this->loungeSourceChanges[] = $loungeSourceChange;

        return $this;
    }

    public function setLoungeSourceChanges(array $loungeSourceChanges): self
    {
        $this->loungeSourceChanges = $loungeSourceChanges;

        return $this;
    }

    public function getLoungeSourceChanges(): array
    {
        return $this->loungeSourceChanges;
    }
}
