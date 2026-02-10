<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

interface HistoryMapInterface
{
    /**
     * return null to skip row.
     *
     * @return string|null
     */
    public static function map(array $row);
}
