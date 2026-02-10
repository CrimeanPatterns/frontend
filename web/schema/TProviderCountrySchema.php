<?php

require_once __DIR__ . '/../lib/classes/TBaseSchema.php';
require_once 'ProviderPhone.php';

class TProviderCountrySchema extends TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();

        $this->TableName = 'ProviderCountry';
    }
}