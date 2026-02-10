<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Loader;

use AwardWallet\MainBundle\Globals\Utils\LazyVal;

class LoaderContext
{
    /**
     * @var array[]
     */
    public $accounts = [];
    /**
     * @var int[]
     */
    public $accountsIds = [];
    /**
     * @var int[]
     */
    public $accountsIdsSupportPhone = [];
    /**
     * @var int[]
     */
    public $accountsIdsWithSubacc = [];
    /**
     * @var int[]
     */
    public $accountsIdsPending = [];
    /**
     * @var int[]
     */
    public $couponsIds = [];
    /**
     * @var array[]
     */
    public $totals = [];
    /**
     * @var int
     */
    public $accountsCount = 0;
    /**
     * @var int
     */
    public $lastUpdated;
    /**
     * @var array<int, int[]>
     */
    public array $providerToAccountIdsListMap = [];
    /**
     * @var ?LazyVal<array>
     */
    public ?LazyVal $mileValueDataCache = null;
}
