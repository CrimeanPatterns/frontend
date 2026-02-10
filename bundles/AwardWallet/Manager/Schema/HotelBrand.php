<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Manager\Schema\HotelBrandList;
use AwardWallet\MainBundle\Service\HotelPointValue\PatternLoader;

class HotelBrand extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->ListClass = HotelBrandList::class;
        $this->Fields['Patterns']['HTML'] = true;
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        $result['Patterns']['InputType'] = 'textarea';
        $result['Patterns']['Note'] = PatternLoader::PATTERNS_SYNTAX_HELP;

        return $result;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);

        $form->OnCheck = function () use ($form) {
            return PatternLoader::validate($form->Fields['Patterns']['Value'] ?? '');
        };
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'ProviderID') {
            return SQLToArray("
                SELECT 
                    ProviderID AS ID, 
                    DisplayName AS Name 
                FROM 
                    Provider p
                WHERE 
                    Kind = " . PROVIDER_KIND_HOTEL . "
                    AND " . userProviderFilter() . "
                ORDER BY 
                    DisplayName", 'ID', 'Name'
            );
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }
}
