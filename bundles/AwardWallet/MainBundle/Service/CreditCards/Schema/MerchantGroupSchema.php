<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

class MerchantGroupSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->TableName = 'MerchantGroup';
        $this->ListClass = MerchantGroupList::class;
        $this->Fields = [
            'MerchantGroupID' => [
                'Caption' => 'ID',
                'Type' => 'integer',
                'FilterWidth' => 20,
                'FilterField' => 'mg.MerchantGroupID',
            ],
            'Name' => [
                'Caption' => 'Name',
                'Type' => 'string',
                'FilterWidth' => 40,
                'FilterField' => 'mg.Name',
            ],
            'ClickURL' => [
                'Caption' => 'Click URL',
                'Type' => 'string',
                'FilterWidth' => 20,
                'FilterField' => 'mg.ClickURL',
            ],
        ];
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        $result['Patterns'] = [
            'Type' => 'string',
            'Database' => false,
        ];
        $result['BonusEarns'] = [
            'Caption' => 'Bonus Earns (Active)',
            'Type' => 'string',
            'Database' => false,
        ];

        unset($result['ClickURL']);

        return $result;
    }
}
