<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks;

abstract class Kind
{
    public const KIND_ROW = 'row';
    public const KIND_DATE = 'date';

    public const KIND_TITLE = 'title';
    public const KIND_BALANCE = 'balance';
    public const KIND_STRING = 'string';
    public const KIND_EARNING_POTENTIAL = 'earning_potential';
    public const KIND_TOTALS_TITLE = 'totals_title';
    public const KIND_CASH_EQUIVALENT = 'cash_equivalent';
}
