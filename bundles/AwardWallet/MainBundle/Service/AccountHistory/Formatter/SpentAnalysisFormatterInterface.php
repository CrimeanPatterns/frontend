<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisQuery;

interface SpentAnalysisFormatterInterface
{
    public function format(array $rows, SpentAnalysisQuery $query, array $totals);
}
