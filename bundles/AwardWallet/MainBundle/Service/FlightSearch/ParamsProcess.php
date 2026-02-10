<?php

namespace AwardWallet\MainBundle\Service\FlightSearch;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class ParamsProcess
{
    private string $queryFrom;
    private string $queryTo;
    private string $queryType;
    private string $queryClass;

    public function __construct(string $from, string $to, string $type, string $class)
    {
        $this->queryFrom = $from;
        $this->queryTo = $to;
        $this->queryType = $type;
        $this->queryClass = $class;
    }

    public function getQueryFrom(): ?string
    {
        return $this->queryFrom;
    }

    public function getQueryFromParts(): array
    {
        if (empty($this->queryFrom) || false === strpos($this->queryFrom, '-')) {
            return [null, null];
        }
        $parts = explode('-', $this->queryFrom, 2);

        return array_map('intval', $parts);
    }

    public function getQueryTo(): ?string
    {
        return $this->queryTo;
    }

    public function getQueryToParts(): ?array
    {
        if (empty($this->queryTo) || false === strpos($this->queryTo, '-')) {
            return [null, null];
        }
        $parts = explode('-', $this->queryTo, 2);

        return array_map('intval', $parts);
    }

    public function getQueryType(): string
    {
        return $this->queryType;
    }

    public function getQueryClass(): string
    {
        return $this->queryClass;
    }
}
