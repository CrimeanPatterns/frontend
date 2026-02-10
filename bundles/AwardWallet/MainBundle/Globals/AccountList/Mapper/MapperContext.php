<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\AccountList\Loader\LoaderContext;
use AwardWallet\MainBundle\Globals\AccountList\Options;

/**
 * @NoDI
 */
class MapperContext
{
    /**
     * @var array
     */
    public $rights;
    /**
     * @var array
     */
    public $shares;
    /**
     * @var ?string
     */
    public $rightsTag;
    /**
     * @var LoaderContext
     */
    public $loaderContext;
    /**
     * @var Options
     */
    public $options;
    /**
     * @var array
     */
    public $dataTemplate = [];
    /**
     * @var int
     */
    public $expirationDatesVisible = 0;
    /**
     * @var array
     */
    public $accountStat = [
        'AccountsByNumber' => [],
        'EliteStatusByProvider' => [],
    ];

    public array $hasBalanceInTotalSumProperty = [];

    public function __construct(LoaderContext $loaderContext, Options $options)
    {
        $this->loaderContext = $loaderContext;
        $this->options = $options;
    }

    public function isGranted($rightName, $id, $tableName): bool
    {
        return (isset($this->rights[$tableName][$rightName][$id])) ? $this->rights[$tableName][$rightName][$id] : false;
    }

    public function alterDataTemplateBy(array $alterations): void
    {
        $this->dataTemplate = \array_merge($this->dataTemplate, $alterations);
    }
}
