<?php

require_once __DIR__ . "/../lib/schema/BaseAdminCoupon.php";

class TAdminCouponSchema extends TBaseAdminCouponSchema
{
    public function TAdminCouponSchema()
    {
        parent::TBaseAdminCouponSchema();
        $this->Fields['FirstTimeOnly'] = [
            "Type" => "boolean",
            "Value" => "1",
            "Caption" => "First time users only",
        ];
        $this->bIncludeList = false;
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();

        $fields['Discount']['Value'] = 100;
        $fields['Discount']['Min'] = 0;

        $objManager = new TTableLinksFieldManager();
        $objManager->TableName = "CouponItem";
        $objManager->Fields = [
            "CartItemType" => [
                "Type" => "integer",
                "Options" => $this->getCartItemTypes(),
                "Value" => \AwardWallet\MainBundle\Entity\CartItem\AwPlus2Months::TYPE,
                "Note" => "<script>
                        $(document).ready(function(){
                            $('#fldItemsAddOptionCartItemType option[value=\"" . CART_ITEM_AWPLUS_ONE_CARD . "\"], #fldItemsAddOptionCartItemType option[value=\"" . CART_ITEM_AWPLUS_1_ONE_CARD . "\"]')
                                .attr(\"style\",\"color:grey\");
//                                .attr(\"disabled\",\"disabled\");
                        });
                    </script>",
            ],
            "Cnt" => [
                "Caption" => "Count",
                "Type" => "integer",
                "Required" => true,
                "Value" => 1,
            ],
        ];
        $objManager->UniqueFields = ["CartItemType"];

        ArrayInsert($fields, 'Discount', true, ['Items' => ['Manager' => $objManager]]);

        return $fields;
    }

    public function GetListFields()
    {
        $arFields = parent::GetListFields();

        return $arFields;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->OnSave = null;
        $form->OnCheck = function () use ($form) {
            /** @var TTableLinksFieldManager $manager */
            $manager = $form->Fields["Items"]["Manager"];

            foreach ($manager->SelectedOptions as $option) {
                if (
                    $option["CartItemType"] == \AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription::TYPE
                    && $form->Fields["Discount"]["Value"] > 99
                ) {
                    return "You could not use 100% discount with AwPlus subscription";
                }
            }
        };
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->Limit = 3000;
        $list->Fields['CouponID']['Sort'] = 'c.CouponID DESC';
    }

    public static function getCartItemTypes(): array
    {
        return [
            \AwardWallet\MainBundle\Entity\CartItem\AwPlus1Month::TYPE => "AwardWallet Plus for 1 month",
            \AwardWallet\MainBundle\Entity\CartItem\AwPlus2Months::TYPE => "AwardWallet Plus for 2 months",
            \AwardWallet\MainBundle\Entity\CartItem\AwPlus3Months::TYPE => "AwardWallet Plus for 3 months",
            \AwardWallet\MainBundle\Entity\CartItem\AwPlus::TYPE => "AwardWallet Plus for 6 months",
            \AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year::TYPE => "AwardWallet Plus for 12 Months",
            \AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription::TYPE => "AwardWallet Plus Subscription",
            \AwardWallet\MainBundle\Entity\CartItem\OneCard::TYPE => "Free OneCard",
            \AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit::TYPE => 'BalanceWatch Credit',
            CART_ITEM_AWPLUS_ONE_CARD => "Free OneCard & AwardWallet Plus for 6 Months (deprecated, add as two items)",
            CART_ITEM_AWPLUS_1_ONE_CARD => "Free OneCard & AwardWallet Plus for 12 Months (deprecated, add as two items)",
        ];
    }
}
