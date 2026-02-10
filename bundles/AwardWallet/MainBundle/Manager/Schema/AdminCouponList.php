<?php

namespace AwardWallet\MainBundle\Manager\Schema;

class AdminCouponList extends \TBaseList
{
    public function __construct($table, $fields, $defaultSort)
    {
        $options = ["" => ""];

        foreach (\TAdminCouponSchema::getCartItemTypes() as $key => $value) {
            $options[(string) $key] = $value;
        }

        $fields["Items"] = [
            "Type" => "integer",
            "Options" => $options,
            "FilterField" => "ci.CartItemType",
            "filterWidth" => 180,
        ];
        $fields['CouponID']['Sort'] = 'c.CouponID ASC';
        $fields['CouponID']['FilterField'] = 'c.CouponID';
        parent::__construct($table, $fields, $defaultSort);
        $this->SQL = "select 
            c.CouponID,
            c.Code,
            c.Name,
            c.Discount,
            c.StartDate,
            c.EndDate,
            c.MaxUses,
            c.FirstTimeOnly,
            '' as Items,
            SUBSTRING( c.Code, LOCATE(  '-', c.Code ) +1, LOCATE(  '-', c.Code, LOCATE(  '-', c.Code ) +1 ) - LOCATE(  '-', c.Code ) - 1) as UserId,
            COUNT(pc.CartID) as Uses,
            MAX(pc.PayDate) as LastUseDate
        from
            Coupon c
            left join CouponItem ci on c.CouponID = ci.CouponID
            left join Cart pc on c.CouponID = pc.CouponID 
        where 
            1 = 1
            [Filters]
        group by 
            c.CouponID,
            c.Code,
            c.Name,
            c.Discount,
            c.EndDate,
            c.MaxUses,
            c.FirstTimeOnly
        ";
    }

    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);

        $q = new \TQuery("select CartItemType, Cnt from CouponItem where CouponID = {$this->Query->Fields['CouponID']}");
        $types = [];

        foreach ($q as $row) {
            $types[] = $this->Fields["Items"]["Options"][$row["CartItemType"]] . ": " . $q->Fields["Cnt"];
        }

        if (!empty($types)) {
            $this->Query->Fields["Items"] = implode("\n<br>", $types);
        } else {
            $this->Query->Fields["Items"] = "Default, AW Plus for 6 month";
        }
    }
}
