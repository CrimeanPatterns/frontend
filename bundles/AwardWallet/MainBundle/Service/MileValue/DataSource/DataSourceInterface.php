<?php

namespace AwardWallet\MainBundle\Service\MileValue\DataSource;

interface DataSourceInterface
{
    public function getSourceId(): string;

    /**
     * @param array $row - row from MileValue and Trip
     * @return array - state
     */
    public function check(array $row, array $state): array;
}
