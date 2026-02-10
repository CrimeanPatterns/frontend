<?php

namespace AwardWallet\Tests\Unit\Email;

use AwardWallet\Common\API\Email\V2\Coupon\Coupon;
use AwardWallet\Common\API\Email\V2\Meta\EmailInfo;
use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Email\CallbackProcessor;
use AwardWallet\MainBundle\Email\ProviderCouponProcessor;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\Tests\Unit\BaseUserTest;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class ProviderCouponTest extends BaseUserTest
{
    private ?ProviderCouponProcessor $pcp;
    private ?Owner $owner;

    public function _before()
    {
        parent::_before();
        $this->pcp = new ProviderCouponProcessor(
            $this->em->getRepository(Account::class),
            $this->em->getRepository(Providercoupon::class),
            $this->em->getRepository(Provider::class),
            new NullLogger(),
            $this->em
        );
        $this->owner = new Owner($this->user);
    }

    public function testSaveThree()
    {
        $this->assertEquals(CallbackProcessor::SAVE_MESSAGE_SUCCESS, $this->pcp->process($this->getResponse('delta', 'DL1111', null, null), $this->owner));
        $this->assertEquals(1, $this->db->grabNumRecords('ProviderCoupon', ['UserID' => $this->user->getId(), 'AccountID' => null]));
        $this->assertEquals(CallbackProcessor::SAVE_MESSAGE_SUCCESS, $this->pcp->process($this->getResponse('delta', 'DL2222', 'number', null), $this->owner));
        $this->assertEquals(2, $this->db->grabNumRecords('ProviderCoupon', ['UserID' => $this->user->getId(), 'AccountID' => null]));
        $this->assertEquals(CallbackProcessor::SAVE_MESSAGE_MISSED, $this->pcp->process($this->getResponse('delta', 'DL1111', null, null), $this->owner));
        $this->assertEquals(2, $this->db->grabNumRecords('ProviderCoupon', ['UserID' => $this->user->getId()]));
    }

    public function testMatch()
    {
        // one acc, no masks
        $dl = $this->aw->createAwAccount($this->user->getId(), 'delta', 'DL0001');
        $this->pcp->process($this->getResponse('delta', 'PC0001', null, null), $this->owner);
        $this->db->seeInDatabase('ProviderCoupon', ['UserID' => $this->user->getId(), 'CardNumber' => 'PC0001', 'AccountID' => $dl]);

        $this->pcp->process($this->getResponse('delta', 'PC0002', 'DL0001', null), $this->owner);
        $this->db->seeInDatabase('ProviderCoupon', ['UserID' => $this->user->getId(), 'CardNumber' => 'PC0002', 'AccountID' => $dl]);

        $this->pcp->process($this->getResponse('delta', 'PC0003', 'DL0002', null), $this->owner);
        $this->db->seeInDatabase('ProviderCoupon', ['UserID' => $this->user->getId(), 'CardNumber' => 'PC0003', 'AccountID' => null]);

        // two accs, with masks
        $ua1 = $this->aw->createAwAccount($this->user->getId(), 'mileageplus', 'UA0001');
        $ua2 = $this->aw->createAwAccount($this->user->getId(), 'mileageplus', 'UA0002');
        $this->pcp->process($this->getResponse('mileageplus', 'PC0004', null, null), $this->owner);
        $this->db->seeInDatabase('ProviderCoupon', ['UserID' => $this->user->getId(), 'CardNumber' => 'PC0004', 'AccountID' => null]);

        $this->pcp->process($this->getResponse('mileageplus', 'PC0005', 'UA00', 'right'), $this->owner);
        $this->db->seeInDatabase('ProviderCoupon', ['UserID' => $this->user->getId(), 'CardNumber' => 'PC0005', 'AccountID' => null]);

        $this->pcp->process($this->getResponse('mileageplus', 'PC0006', '002', 'left'), $this->owner);
        $this->db->seeInDatabase('ProviderCoupon', ['UserID' => $this->user->getId(), 'CardNumber' => 'PC0006', 'AccountID' => $ua2]);

        $this->pcp->process($this->getResponse('mileageplus', 'PC0007', 'UA**01', 'center'), $this->owner);
        $this->db->seeInDatabase('ProviderCoupon', ['UserID' => $this->user->getId(), 'CardNumber' => 'PC0007', 'AccountID' => $ua1]);
    }

    protected function getResponse($code, $number, $accNum, $accMask): ParseEmailResponse
    {
        $r = new ParseEmailResponse();
        $r->providerCode = $code;
        $r->metadata = new EmailInfo();
        $r->metadata->receivedDateTime = date('Y-m-d H:i:s');
        $c = new Coupon();
        $c->number = $number;
        $c->programCode = $code;
        $c->accountNumber = $accNum;
        $c->value = (string) rand(100, 10000);
        $c->type = 3;
        $c->accountMask = $accMask;
        $r->coupons = [$c];

        return $r;
    }
}
