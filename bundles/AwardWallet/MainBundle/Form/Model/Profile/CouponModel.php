<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;

/**
 * @AwAssert\AndX(constraints = {
 *     @AwAssert\AntiBruteforceLocker(
 *         service = "aw.security.antibruteforce.forgot",
 *         keyMethod = "getCouponLockerKey",
 *         field = "coupon"
 *     ),
 *     @AwAssert\Service(
 *         name = "aw.form.validator.profile_coupon",
 *         method = "validateCoupon",
 *         errorPath = "coupon"
 *     ),
 * })
 */
class CouponModel extends AbstractEntityAwareModel
{
    private ?string $coupon = null;

    private ?string $ip = null;

    public function getCoupon(): ?string
    {
        return $this->coupon;
    }

    public function setCoupon(?string $coupon): self
    {
        $this->coupon = $coupon;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getCouponLockerKey(): ?string
    {
        return $this->ip;
    }
}
