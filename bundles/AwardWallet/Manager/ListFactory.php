<?php

namespace AwardWallet\Manager;

use Symfony\Component\DependencyInjection\ServiceLocator;

class ListFactory
{
    private ServiceLocator $lists;

    private SchemaFactory $schemaFactory;

    public function __construct(ServiceLocator $lists, SchemaFactory $schemaFactory)
    {
        $this->lists = $lists;
        $this->schemaFactory = $schemaFactory;
    }

    public function get(string $schemaName): \TBaseList
    {
        $schema = $this->schemaFactory->getSchema($schemaName);

        if ($this->lists->has($schemaName)) {
            /** @var \TBaseList $list */
            $list = $this->lists->get($schemaName);
            $schema->TuneList($list);
        } else {
            $list = $schema->CreateList();
        }

        return $list;
    }
}
