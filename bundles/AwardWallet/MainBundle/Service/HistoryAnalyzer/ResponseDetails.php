<?php

namespace AwardWallet\MainBundle\Service\HistoryAnalyzer;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class ResponseDetails
{
    public $flights = 0;
    public $other = 0;
}
