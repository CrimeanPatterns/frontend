<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\DTO;

class ApiSearchRequest
{
    private string $parser;

    private string $depCode;

    private \DateTime $depDate;

    private string $arrCode;

    private string $cabin;

    private int $adults;

    private int $queryId;

    private string $searchId;

    private ?string $error;

    public function __construct(
        string $parser,
        string $depCode,
        \DateTime $depDate,
        string $arrCode,
        string $cabin,
        int $adults,
        int $queryId,
        string $searchId,
        ?string $error = null
    ) {
        $this->parser = $parser;
        $this->depCode = $depCode;
        $this->depDate = $depDate;
        $this->arrCode = $arrCode;
        $this->cabin = $cabin;
        $this->adults = $adults;
        $this->queryId = $queryId;
        $this->searchId = $searchId;
        $this->error = $error;
    }

    public function getParser(): string
    {
        return $this->parser;
    }

    public function getDepCode(): string
    {
        return $this->depCode;
    }

    public function getDepDate(): \DateTime
    {
        return $this->depDate;
    }

    public function getArrCode(): string
    {
        return $this->arrCode;
    }

    public function getCabin(): string
    {
        return $this->cabin;
    }

    public function getAdults(): int
    {
        return $this->adults;
    }

    public function getQueryId(): int
    {
        return $this->queryId;
    }

    public function getSearchId(): string
    {
        return $this->searchId;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function hasError(): bool
    {
        return !empty($this->error);
    }
}
