<?php

namespace AwardWallet\Tests\Modules\Access;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\StringUtils;

class CouponDeleteScenario extends Scenario
{
    /**
     * @var int
     */
    public $couponId;

    /**
     * @var string
     */
    public $couponValue;

    private $linkedToAccount = false;

    public function create(\TestSymfonyGuy $I)
    {
        parent::create($I);
        $this->couponValue = StringUtils::getRandomCode(10);

        $accountId = null;

        if ($this->linkedToAccount) {
            $accountId = $I->createAwAccount($this->victimId, "testprovider", "balance.random");
        }

        $this->couponId = $I->createAwCoupon($this->victimId, 'Some Test Coupon', $this->couponValue, null, ["AccountID" => $accountId]);

        if ($this->shared) {
            if (empty($this->victimConnectionId)) {
                throw new \InvalidArgumentException("shared implies victimToAttacker connection");
            }
            $I->haveInDatabase('ProviderCouponShare', ['ProviderCouponID' => $this->couponId, 'UserAgentID' => $this->victimConnectionId]);
        }
    }

    public static function dataProvider()
    {
        // return arrays because codeception does not allow us to return objects from dataProvider
        return [
            ['scenario' => new static(Action::REDIRECT_TO_LOGIN, false)],
            ['scenario' => new static(Action::FORBIDDEN, true, null, null, false)],
            ['scenario' => new static(Action::FORBIDDEN, true, null, new Connection(true, Useragent::ACCESS_WRITE), false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_READ_NUMBER), new Connection(true, Useragent::ACCESS_WRITE), false)],
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_READ_NUMBER), new Connection(true, Useragent::ACCESS_WRITE), true)],
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_WRITE), null, true)],
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), false)],

            ['scenario' => (new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), true))->setLinkedToAccount(true)],
            ['scenario' => (new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), false))->setLinkedToAccount(true)],
        ];
    }

    public function setLinkedToAccount($linked)
    {
        $this->linkedToAccount = $linked;

        return $this;
    }
}
