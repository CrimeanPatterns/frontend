<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Service\MileValue\Constants;

class AirlineFareClass extends \TBaseSchema
{
    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'AirlineID') {
            return SQLToArray("select AirlineID as ID, coalesce(concat(Code, ', ', Name), Name) as Name from Airline where Active = 1 order by coalesce(concat(Code, ', ', Name), Name)", "ID", "Name");
        }

        if ($field === 'ClassOfService') {
            return array_combine(Constants::CLASSES_OF_SERVICE, Constants::CLASSES_OF_SERVICE);
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }
}
