<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Transformer\Desktop;
use AwardWallet\MainBundle\Service\Notification\TransformerInterface;
use AwardWallet\MainBundle\Service\Notification\Unsubscriber;
use AwardWallet\Tests\Unit\BaseUserTest;

class DesktopTransformerTest extends BaseUserTest
{
    /**
     * @var TransformerInterface
     */
    private $transformer;

    /**
     * @var MobileDevice
     */
    private $device;

    /**
     * @var Unsubscriber
     */
    private $unsubscriber;

    public function _before()
    {
        parent::_before();
        $this->transformer = $this->container->get(Desktop::class);
        $this->device = new MobileDevice();
        $this->device
            ->setAppVersion("web:personal")
            ->setCreationDate(new \DateTime())
            ->setDeviceType(MobileDevice::TYPE_SAFARI)
            ->setLang('en')
        ;
        $this->unsubscriber = $this->container->get(Unsubscriber::class);
    }

    public function _after()
    {
        parent::_after();
        unset($this->transformer, $this->device, $this->unsubscriber);
    }

    public function testSubAccount()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "notmatterwhat");
        $subAccId = $this->db->haveInDatabase("SubAccount", ["AccountID" => $accountId, 'DisplayName' => 'Test', 'Balance' => 12, 'Code' => 'test']);
        $subAcc = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class)->find($subAccId);
        $transformed = $this->transformer->transform($this->device, new Content("AwardWallet", "Some Message", Content::TYPE_REWARDS_ACTIVITY, $subAcc));
        $this->assertEquals("AwardWallet", $transformed->message);
        $this->assertEquals(["url" => $this->unsubscriber->addUnsubscribeCode($this->device, "/account/list/?account=" . $accountId), "body" => "Some Message"], $transformed->payload);
    }

    public function testAccount()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "notmatterwhat");
        $acc = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $transformed = $this->transformer->transform($this->device, new Content("AwardWallet", "Some Message", Content::TYPE_REWARDS_ACTIVITY, $acc));
        $this->assertEquals("AwardWallet", $transformed->message);
        $this->assertEquals(["url" => $this->unsubscriber->addUnsubscribeCode($this->device, "/account/list/?account=" . $accountId), "body" => "Some Message"], $transformed->payload);
    }

    public function testCoupon()
    {
        $couponId = $this->aw->createAwCoupon($this->user->getUserid(), 'Test', 'Coupon');
        $coupon = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)->find($couponId);
        $transformed = $this->transformer->transform($this->device, new Content("AwardWallet", "Some Message", Content::TYPE_ACCOUNT_EXPIRATION, $coupon));
        $this->assertEquals("AwardWallet", $transformed->message);
        $this->assertEquals(["url" => $this->unsubscriber->addUnsubscribeCode($this->device, "/account/list/?coupon=" . $couponId), "body" => "Some Message"], $transformed->payload);
    }
}
