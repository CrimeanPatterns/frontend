<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

interface MapperInterface
{
    /**
     * @param int $accountID current account id
     * @param array $accountFields current account fields
     * @param array $accountsIds all accounts ids (for mass query)
     * @return array account fields
     */
    public function map(MapperContext $mapperContext, $accountID, $accountFields, $accountsIds);

    public function alterTemplate(MapperContext $mapperContext);
}
