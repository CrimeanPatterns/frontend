<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class LoungeNormalized
{
    private string $airportCode;

    private ?string $nameSource;

    private ?string $nameNormalized;

    private ?string $nameNormalizedWithoutStopWords;

    private ?string $terminalSource;

    private ?string $terminalNormalized;

    private ?string $gate1Source;

    private array $gate1Structured;

    private ?string $gate2Source;

    private array $gate2Structured;

    public function __construct(
        string $airportCode,
        ?string $nameSource,
        ?string $nameNormalized,
        ?string $nameNormalizedWithoutStopWords,
        ?string $terminalSource,
        ?string $terminalNormalized,
        ?string $gate1Source,
        array $gate1Structured,
        ?string $gate2Source,
        array $gate2Structured
    ) {
        $this->airportCode = $airportCode;
        $this->nameSource = $nameSource;
        $this->nameNormalized = $nameNormalized;
        $this->nameNormalizedWithoutStopWords = $nameNormalizedWithoutStopWords;
        $this->terminalSource = $terminalSource;
        $this->terminalNormalized = $terminalNormalized;
        $this->gate1Source = $gate1Source;
        $this->gate1Structured = $gate1Structured;
        $this->gate2Source = $gate2Source;
        $this->gate2Structured = $gate2Structured;
    }

    public function getAirportCode(): string
    {
        return $this->airportCode;
    }

    public function getNameSource(): ?string
    {
        return $this->nameSource;
    }

    public function getNameNormalized(): ?string
    {
        return $this->nameNormalized;
    }

    public function getNameNormalizedWithoutStopWords(): ?string
    {
        return $this->nameNormalizedWithoutStopWords;
    }

    public function getTerminalSource(): ?string
    {
        return $this->terminalSource;
    }

    public function getTerminalNormalized(): ?string
    {
        return $this->terminalNormalized;
    }

    public function getGate1Source(): ?string
    {
        return $this->gate1Source;
    }

    public function getGate1Structured(): array
    {
        return $this->gate1Structured;
    }

    public function getGate1Normalized(): ?string
    {
        return $this->gate1Structured['normalized'];
    }

    public function getGate1Prefix(): ?string
    {
        return $this->gate1Structured['prefix'];
    }

    public function getGate1Number(): ?int
    {
        return $this->gate1Structured['number'];
    }

    public function getGate2Source(): ?string
    {
        return $this->gate2Source;
    }

    public function getGate2Structured(): array
    {
        return $this->gate2Structured;
    }

    public function getGate2Normalized(): ?string
    {
        return $this->gate2Structured['normalized'];
    }

    public function getGate2Prefix(): ?string
    {
        return $this->gate2Structured['prefix'];
    }

    public function getGate2Number(): ?int
    {
        return $this->gate2Structured['number'];
    }
}
