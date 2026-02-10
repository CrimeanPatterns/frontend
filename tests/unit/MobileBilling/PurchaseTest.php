<?php

namespace AwardWallet\Test\Unit\MobileBilling;

use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractPurchase;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\AwPlus as ConsumableAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit1;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit10;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit3;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit5;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusDiscounted;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusWeek;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group mobile
 * @group frontend-unit
 * @group mobile/billing
 * @group billing
 */
class PurchaseTest extends BaseUserTest
{
    private $billingProvider;

    public function _before()
    {
        parent::_before();
        $this->db->executeQuery("delete from GroupUserLink where UserID = {$this->user->getUserid()}");
        $this->em->refresh($this->user);
        $this->billingProvider = $this->container->get(Provider::class);
    }

    public function _after()
    {
        $this->billingProvider = null;
        parent::_after();
    }

    public function testGetAvailableSubscription()
    {
        $this->user->setDiscountedUpgradeBefore(null);
        $this->assertEquals(AwPlus::class, AbstractSubscription::getAvailableSubscription($this->user));
        $this->assertEquals(AwPlusDiscounted::class, AbstractSubscription::getAvailableSubscription($this->user, new \DateTime("2017-02-13")));
        $this->user->setDiscountedUpgradeBefore(new \DateTime("+1 month"));
        $this->em->flush();
        $this->assertEquals(AwPlus::class, AbstractSubscription::getAvailableSubscription($this->user));
        /** @var Sitegroup $staffGroup */
        $staffGroup = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Sitegroup::class)->findOneByGroupname("Staff");
        $this->assertInstanceOf(Sitegroup::class, $staffGroup);
        $this->db->haveInDatabase("GroupUserLink", [
            "SiteGroupID" => $staffGroup->getSitegroupid(),
            "UserID" => $this->user->getUserid(),
        ]);
        $this->em->refresh($this->user);
        $this->assertEquals(AwPlus::class, AbstractSubscription::getAvailableSubscription($this->user));
    }

    public function testGetAvailableProducts()
    {
        $creditProducts = [
            Credit1::class,
            Credit3::class,
            Credit5::class,
            Credit10::class,
        ];
        $this->user->setDiscountedUpgradeBefore(null);
        $this->assertEquals(array_merge([
            AwPlusWeek::class,
            AwPlusDiscounted::class,
            AwPlus::class,
            ConsumableAwPlus::class,
        ], $creditProducts), AbstractPurchase::getAvailableProducts($this->user, $this->billingProvider));
        $this->user->setDiscountedUpgradeBefore(new \DateTime("+1 month"));
        $this->assertEquals(array_merge([
            AwPlusWeek::class,
            AwPlusDiscounted::class,
            AwPlus::class,
            ConsumableAwPlus::class,
        ], $creditProducts), AbstractPurchase::getAvailableProducts($this->user, $this->billingProvider));
    }
}
