<?php

class TCart extends TBaseCart
{
    public const AWPLUS_COST = 5;

    public $autoAddOneCards = true;

    // check that coupon is applicable. return null on success or error message
    public function CheckCoupon($arCoupon, $wantFree)
    {
        // check uses count
        if ($arCoupon['MaxUses'] > 0) {
            $q = new TQuery("select count(*) as Cnt from Cart where PayDate is not null and CouponID = {$arCoupon['CouponID']}");

            if ($q->Fields['Cnt'] >= $arCoupon['MaxUses']) {
                return "The number of times this coupon could be used has been exceeded by other AwardWallet members.";
            }
        }

        // check that there are items in cart, matched coupon targets
        $discount = $this->CalcDiscount($arCoupon["CouponID"]);

        if (($discount == 0) && ($arCoupon['Service'] != CART_ITEM_ONE_CARD)) {
            return "Coupon does not match selected items";
        }

        if ($wantFree) {
            $this->CalcTotals();

            if ($discount != $this->Total) {
                return "This coupon does not offer a 100% discount";
            }
        }

        if ($arCoupon['FirstTimeOnly'] == '1') {
            if ($arCoupon['Service'] == CART_ITEM_ONE_CARD) {
                $serviceName = "AwardWallet OneCard";
                $typeIds = [CART_ITEM_ONE_CARD];
            } else {
                $serviceName = "AwardWallet Plus";
                $typeIds = [CART_ITEM_AWPLUS, CART_ITEM_AWPLUS_1, CART_ITEM_AWPLUS_20];
            }

            if ($this->UserPaidFor($_SESSION['UserID'], $typeIds)) {
                return "This coupon code is only valid for the first time users of $serviceName service.
				Our records indicate that you have already used $serviceName in the past; therefore, this coupon can't be used with your account.";
            }
        }

        // check that user can't upgrade many times for free
        if (($discount > 0) && ($_SESSION['AccountLevel'] == ACCOUNT_LEVEL_AWPLUS)
            && ($this->TypeExists(CART_ITEM_AWPLUS)
                || $this->TypeExists(CART_ITEM_AWPLUS_1)
                || $this->TypeExists(CART_ITEM_AWPLUS_20))) {
            if (isset($arCoupon['FirstTimeOnly']) && $arCoupon['FirstTimeOnly'] == 1) {
                return "At the moment you already have AwardWallet Plus account. This coupon is intended for users who want to apply a coupon to upgrade from a regular AwardWallet account to AwardWallet Plus.";
            }
        }

        if ($this->UserUsedCoupon($_SESSION['UserID'], $arCoupon['Code'])) {
            return 'This coupon code can be used only once. Our records indicate that you have already used this
			coupon.';
        } /* checked */

        if ($this->TypeExists(CART_ITEM_TOP10)) {
            return "You can not apply coupons and pay at the same time";
        } /* checked */

        return null;
    }

    /**
     * Can I order onecard for $5 ? offer valid for users auto-charged within a week.
     *
     * @return bool
     */
    public function eligibleForOnecard()
    {
        if (empty($this->Fields['UserID'])) {
            return false;
        }
        $q = new TQuery("select 1 from Cart c
		join CartItem ci on c.CartID = ci.CartID
		where c.UserID = {$this->Fields['UserID']} and c.PayDate > adddate(now(), -14) and ci.TypeID = " . CART_ITEM_AWPLUS . " and ci.UserData = " . CART_FLAG_RECURRING . "
		and not exists(select 1 from Cart c2 join CartItem ci2 on c2.CartID = ci2.CartID
		where c2.UserID = c.UserID and c2.PayDate > c.PayDate and ci2.TypeID = " . CART_ITEM_ONE_CARD . " and ci2.UserData = " . CART_FLAG_RECURRING_ONECARD . ")
		limit 1");

        return !$q->EOF;
    }

    // calc totals
    public function CalcTotals()
    {
        global $Connection, $arCartItemName, $arCartItemPrice;
        parent::CalcTotals();

        if ($this->TypeExists(CART_ITEM_BOOKING) || !empty($this->Fields['PayDate']) || empty($this->ID)) {
            return;
        }

        // free onecards is no longer a thing :(
        $cardsCount = 0;
        $cardItemID = null;

        if (isset($this->CouponID)) {
            // add free one card if there is matching coupon
            $service = Lookup("Coupon", "CouponID", "Service", $this->CouponID, true);
            // if(in_array($service, array(CART_ITEM_ONE_CARD, CART_ITEM_AWPLUS_1_ONE_CARD, CART_ITEM_AWPLUS_ONE_CARD)))
            // $cardsCount++;

            // replace standard membership with extended if there is matching coupon
            if ($this->TypeExists(CART_ITEM_AWPLUS) && in_array($service, [CART_ITEM_AWPLUS_1, CART_ITEM_AWPLUS_1_ONE_CARD])) {
                $Connection->Execute("delete from CartItem where CartID = {$this->ID} AND TypeID = " . CART_ITEM_AWPLUS);
                $this->Add(CART_ITEM_AWPLUS_1, null, null, $arCartItemName[CART_ITEM_AWPLUS_1], 1, $arCartItemPrice[CART_ITEM_AWPLUS_1], null, null, null);
            }
        }

        // add cards basing on price
        $price = 0;
        $data = 0;
        //		if (SITE_MODE == SITE_MODE_BUSINESS){
        //			//$cardsCount += floor($this->Total/25)*3 + floor($this->Total % 25 / 10);
        //		}
        //		else{
        // //			if ($this->Total >= 10 || (!empty($this->CouponID) && Lookup('Coupon', 'CouponID', 'Code', $this->CouponID, true) == \AwardWallet\MainBundle\Entity\Coupon::COUPON_10YEARS && $this->Total > 7)){
        // //				if($this->Total < 25)
        // //					$cardsCount++;
        // //				else
        // //					$cardsCount += floor($this->Total/25)*3;
        // //			}
        // //			if($this->eligibleForOnecard() && $cardsCount == 0 && $this->autoAddOneCards){
        // //				$price = 5;
        // //				$data = CART_FLAG_RECURRING_ONECARD;
        // //				$cardsCount++;
        // //			}
        //		}
        $isAWBusinessPay = new TQuery("SELECT CartItemID FROM CartItem WHERE CartID = {$this->ID} AND (TypeID = " . CART_ITEM_AWB . " OR TypeID = " . CART_ITEM_AWB_PLUS . ")");

        if ($isAWBusinessPay->EOF) {
            $q = new TQuery("SELECT CartItemID FROM CartItem WHERE CartID = {$this->ID} AND TypeID = " . CART_ITEM_ONE_CARD);

            if (!$q->EOF) {
                $cardItemID = $q->Fields['CartItemID'];
            }

            if ($cardsCount > 0) {
                $name = $cardsCount . " OneCard Credit" . s($cardsCount);
                $name .= "<br/>(You will be able to order the cards and choose what info goes on the cards after you pay)";

                if (!isset($cardItemID)) {
                    $this->Add(CART_ITEM_ONE_CARD, null, null, $name, $cardsCount, $price, null, $data, null);
                } else {
                    $Connection->Execute("
											UPDATE 
												CartItem 
											SET 
												Price = $price,
												Cnt = $cardsCount,
												Name = '" . addslashes($name) . "',
												UserData = $data
											WHERE CartItemID = $cardItemID");
                }
            } else {
                $Connection->Execute("delete from CartItem where CartID = {$this->ID} AND TypeID = " . CART_ITEM_ONE_CARD);
            }
        } else {
            $Connection->Execute("delete from CartItem where CartID = {$this->ID} AND TypeID = " . CART_ITEM_ONE_CARD);
        }
    }

    public function NameForPayPal()
    {
        if ($this->TypeExists(CART_ITEM_BOOKING)) {
            $name = preg_replace("/<br[^>]*>/ims", ", ", $this->Names);
            $_names = explode(', ', $name);

            return $_names[0] . ", Order #" . $this->ID;
        } else {
            return parent::NameForPayPal();
        }
    }

    public function getMerchant()
    {
        if ($this->TypeExists(CART_ITEM_BOOKING)) {
            return 'BOOKYOURAWA';
        } else {
            return 'AWARDWALLET';
        }
    }

    /**
     * was.
     *
     * @return bool
     */
    public function isRecurringPayment()
    {
        $result = false;

        foreach ($this->ItemRows as $row) {
            if ($row['TypeID'] == CART_ITEM_AWPLUS && $row['UserData'] == CART_FLAG_RECURRING) {
                $result = true;

                break;
            }
        }

        return $result;
    }

    /**
     * was.
     *
     * @return bool
     */
    public function isAwPlusSubscription()
    {
        $result = false;

        foreach ($this->ItemRows as $row) {
            if ($row['TypeID'] == CART_ITEM_AWPLUS_1 && $row['UserData'] == CART_FLAG_RECURRING) {
                $result = true;

                break;
            }
        }

        return $result;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\AbRequest|null
     */
    public function getBookingRequest()
    {
        $result = null;
        $bookingsRequests = array_filter($this->ItemRows, function (array $row) {
            return $row['TypeID'] == CART_ITEM_BOOKING;
        });

        if (!empty($bookingsRequests)) {
            /** @var \AwardWallet\MainBundle\Entity\AbRequest $abRequest */
            $result = getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find(array_shift($bookingsRequests)['ID']);
        }

        return $result;
    }
}
