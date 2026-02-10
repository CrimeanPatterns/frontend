<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Service\AccountHistory\HistoryQuery;

interface HistoryFormatterInterface
{
    public const DESKTOP = 'desktop';
    public const MOBILE = 'mobile';

    public function format(array $rows, HistoryQuery $historyQuery): ?array;

    public function formatRow(array $row, HistoryQuery $historyQuery): ?array;

    public function getId(): string;
}
