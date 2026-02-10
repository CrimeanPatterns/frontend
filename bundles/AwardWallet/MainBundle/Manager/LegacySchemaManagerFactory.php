<?php

namespace AwardWallet\MainBundle\Manager;

class LegacySchemaManagerFactory
{
    public function make(): \TSchemaManager
    {
        return new \TSchemaManager();
    }
}
