<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

class CreditCardMerchantGroup extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->Fields['Description']['HTML'] = true;
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        $result['Description']['InputType'] = 'textarea';
        $result['Description']['InputAttributes'] = 'style="width: 700px;"';

        return $result;
    }

    public function TuneList(&$list)
    {
        /* @var $list \TBaseList */
        parent::TuneList($list);

        $list->SQL = "select
            ccmg.*,
            mg.Name as GroupName,
            case when ccmg.EndDate is null or ccmg.EndDate > now() then '2050-01-01' else ccmg.EndDate end as ActiveDate,  
            case when ccmg.EndDate is null or ccmg.EndDate > now() then 1 else 0 end as Active 
        from
            CreditCardMerchantGroup ccmg
            join CreditCard cc on ccmg.CreditCardID = cc.CreditCardID
            join MerchantGroup mg on ccmg.MerchantGroupID = mg.MerchantGroupID";
        $list->DefaultSort = 'CreditCardID';
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        $result['CreditCardID']['Sort'] = 'cc.Name ASC, Active DESC, ccmg.Multiplier DESC, ActiveDate DESC, mg.Name';
        $result['CreditCardID']['FilterField'] = 'ccmg.CreditCardID';
        $result['CreditCardID']['filterWidth'] = '300';
        $result['MerchantGroupID']['FilterField'] = 'ccmg.MerchantGroupID';
        $result['MerchantGroupID']['filterWidth'] = '200';

        return $result;
    }
}
