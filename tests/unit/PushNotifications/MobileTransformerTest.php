<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Transformer\Mobile;
use AwardWallet\MainBundle\Service\Notification\TransformerInterface;
use AwardWallet\Tests\Unit\BaseUserTest;

use function PHPUnit\Framework\assertMatchesRegularExpression;

/**
 * @group frontend-unit
 */
class MobileTransformerTest extends BaseUserTest
{
    /**
     * @var TransformerInterface
     */
    private $transformer;

    /**
     * @var MobileDevice
     */
    private $device;

    public function _before()
    {
        parent::_before();
        $this->transformer = $this->container->get(Mobile::class);
        $this->device = new MobileDevice();
        $this->device
            ->setAppVersion("3.11.23")
            ->setCreationDate(new \DateTime())
            ->setDeviceType(MobileDevice::TYPE_ANDROID)
            ->setLang('en')
            ->setDeviceKey(StringUtils::getRandomCode(50))
        ;
    }

    public function _after()
    {
        parent::_after();
        unset($this->transformer, $this->device);
    }

    public function testSubAccount()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "notmatterwhat");
        $subAccId = $this->db->haveInDatabase("SubAccount", ["AccountID" => $accountId, 'DisplayName' => 'Test', 'Balance' => 12, 'Code' => 'test']);
        $subAcc = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class)->find($subAccId);
        $transformed = $this->transformer->transform($this->device, new Content("AwardWallet", "Some Message", Content::TYPE_REWARDS_ACTIVITY, $subAcc));
        $this->assertEquals("Some Message", $transformed->message);
        $expected = [
            Mobile::DATA_TYPE_ACCOUNT => "a{$accountId}.{$subAccId}",
            "_ts" => $transformed->payload['_ts'],
            "channel" => "rewards_activity",
            "channelId" => "rewards_activity",
            "title" => "AwardWallet",
        ];
        $this->assertEquals($expected, $transformed->payload);
    }

    public function testAccount()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "notmatterwhat");
        $acc = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $transformed = $this->transformer->transform($this->device, new Content("AwardWallet", "Some Message", Content::TYPE_REWARDS_ACTIVITY, $acc));
        $this->assertEquals("Some Message", $transformed->message);
        $expected = [
            Mobile::DATA_TYPE_ACCOUNT => "a{$accountId}",
            "_ts" => $transformed->payload['_ts'],
            "channel" => "rewards_activity",
            "channelId" => "rewards_activity",
            "title" => "AwardWallet",
        ];
        $this->assertEquals($expected, $transformed->payload);
    }

    public function testCoupon()
    {
        $couponId = $this->aw->createAwCoupon($this->user->getUserid(), 'Test', 'Coupon');
        $coupon = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)->find($couponId);
        $transformed = $this->transformer->transform($this->device, new Content("AwardWallet", "Some Message", Content::TYPE_ACCOUNT_EXPIRATION, $coupon));
        $this->assertEquals("Some Message", $transformed->message);
        $expected = [
            Mobile::DATA_TYPE_ACCOUNT => "c{$couponId}",
            "_ts" => $transformed->payload['_ts'],
            "channel" => "balance_expiration",
            "channelId" => "balance_expiration",
            "title" => "AwardWallet",
        ];
        $this->assertEquals($expected, $transformed->payload);
    }

    public function testOfferUbsubscribe()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $em->persist($this->device);
        $em->flush($this->device);
        $transformed = $this->transformer->transform(
            $this->device,
            new Content('Some Title', 'Some Message', Content::TYPE_BLOG_POST, '/someUrl?param1=param1value')
        );
        $deviceId = $this->device->getMobileDeviceId();
        assertMatchesRegularExpression("#https://awardwallet\.com/someUrl\?param1=param1value&usc=d\-{$deviceId}\-[0-9a-f]{1,}#ims", $transformed->payload['ex']);
    }
}
