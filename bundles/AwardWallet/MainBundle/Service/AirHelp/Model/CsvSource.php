<?php

namespace AwardWallet\MainBundle\Service\AirHelp\Model;

class CsvSource
{
    private string $csv;
    private string $epoch;

    public function __construct(
        string $csv,
        string $epoch
    ) {
        $this->csv = $csv;
        $this->epoch = $epoch;
    }

    public function getCsv(): string
    {
        return $this->csv;
    }

    public function getEpoch(): string
    {
        return $this->epoch;
    }
}
