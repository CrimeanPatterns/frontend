<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

class CreditCardShoppingCategoryGroup extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->Fields['Description']['HTML'] = true;
    }

    public function TuneList(&$list)
    {
        /* @var $list \TBaseList */
        parent::TuneList($list);

        $list->SQL = "select 
            ccscg.*, 
            cc.Name as CreditCardName, 
            scg.Name as GroupName,
            case when scg.Name is null then 0 else 1 end HaveGroup,
            case when ccscg.EndDate is null or ccscg.EndDate > now() then '2050-01-01' else ccscg.EndDate end as ActiveDate,  
            case when ccscg.EndDate is null or ccscg.EndDate > now() then 1 else 0 end as Active 
        from 
             CreditCardShoppingCategoryGroup ccscg
             join CreditCard cc on ccscg.CreditCardID = cc.CreditCardID
             left join ShoppingCategoryGroup scg on ccscg.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID";
        $list->DefaultSort = 'CreditCardID';
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        $result['CreditCardID']['Sort'] = 'cc.Name ASC, Active DESC, HaveGroup, ccscg.Multiplier DESC, ActiveDate DESC, scg.Name';
        $result['CreditCardID']['FilterField'] = 'ccscg.CreditCardID';
        $result['CreditCardID']['filterWidth'] = '300';
        $result['ShoppingCategoryGroupID']['Sort'] = 'Active DESC, HaveGroup, ccscg.Multiplier DESC, scg.Name ASC, ActiveDate DESC, cc.Name';
        $result['ShoppingCategoryGroupID']['FilterField'] = 'ccscg.ShoppingCategoryGroupID';
        $result['ShoppingCategoryGroupID']['filterWidth'] = '200';

        return $result;
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        $result['Description']['InputType'] = 'textarea';
        $result['Description']['InputAttributes'] = 'style="width: 700px;"';
        $result['Description']['Note'] = htmlspecialchars('<strong>2x Membership Rewards®</strong><br/><sub>on all purchases ($50,000 annual limit)</sub>');

        return $result;
    }
}
