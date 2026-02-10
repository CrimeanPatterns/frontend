<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class EliteLevel extends AbstractDbEntity
{
    private ?Provider $provider;

    private ?AllianceEliteLevel $allianceEliteLevel;

    /**
     * @var TextEliteLevel[]
     */
    private array $valueTexts;

    /**
     * @param TextEliteLevel[] $valueTexts
     */
    public function __construct(
        int $rank,
        ?string $name,
        ?Provider $provider = null,
        ?AllianceEliteLevel $allianceEliteLevel = null,
        array $valueTexts = [],
        array $fields = []
    ) {
        parent::__construct(array_merge([
            'ByDefault' => 0,
        ], $fields, [
            'Rank' => $rank,
            'Name' => $name,
        ]));

        $this->provider = $provider;
        $this->allianceEliteLevel = $allianceEliteLevel;
        $this->valueTexts = $valueTexts;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getAllianceEliteLevel(): ?AllianceEliteLevel
    {
        return $this->allianceEliteLevel;
    }

    public function setAllianceEliteLevel(?AllianceEliteLevel $allianceEliteLevel): self
    {
        $this->allianceEliteLevel = $allianceEliteLevel;

        return $this;
    }

    /**
     * @return TextEliteLevel[]
     */
    public function getValueTexts(): array
    {
        return $this->valueTexts;
    }

    /**
     * @param TextEliteLevel[] $valueTexts
     */
    public function setValueTexts(array $valueTexts): self
    {
        $this->valueTexts = $valueTexts;

        return $this;
    }

    public function addValueText(TextEliteLevel $valueText): self
    {
        $this->valueTexts[] = $valueText;

        return $this;
    }
}
