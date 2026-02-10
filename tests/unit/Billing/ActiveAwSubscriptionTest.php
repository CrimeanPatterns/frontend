<?php

namespace AwardWallet\Tests\Unit\Billing;

use AwardWallet\MainBundle\Entity\Billingaddress;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 * @group billing
 */
class ActiveAwSubscriptionTest extends BaseUserTest
{
    /**
     * @var Manager
     */
    protected $cartManager;

    /**
     * @var Billingaddress
     */
    protected $billingAddress;

    /**
     * @var CartRepository
     */
    protected $rep;

    public function _before()
    {
        parent::_before();
        $this->rep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $this->cartManager = $this->container->get('aw.manager.cart');
        $this->cartManager->setUser($this->user);

        $countryId = $this->db->haveInDatabase("Country", [
            'Name' => 'Russia',
            'HaveStates' => 1,
            'Code' => 'RU',
        ]);
        $stateId = $this->db->haveInDatabase("State", [
            'CountryID' => $countryId,
            'Code' => 'PERM',
            'Name' => 'Permskiy kray',
        ]);
        $billingAddressId = $this->db->haveInDatabase("BillingAddress", [
            'UserID' => $this->user->getUserid(),
            'AddressName' => 'my address',
            'Address1' => 'Popova, 10',
            'City' => 'Perm',
            'Zip' => '123456',
            'CountryID' => $countryId,
            'StateID' => $stateId,
            'FirstName' => $this->user->getFirstname(),
            'LastName' => $this->user->getLastname(),
        ]);
        $this->billingAddress = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Billingaddress::class)->find($billingAddressId);
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->em->flush();
    }

    public function _after()
    {
        $this->cartManager = $this->billingAddress = $this->rep = null;
        parent::_after();
    }

    public function testEmpty()
    {
        $this->assertNull($this->rep->getActiveAwSubscription($this->user));
    }

    public function testTrial()
    {
        $this->cartManager->giveAwPlusTrial();
        $this->assertNull($this->rep->getActiveAwSubscription($this->user));
    }

    public function testAwPlus()
    {
        $cart = $this->cartManager->createNewCart();
        $cart->addItem(new AwPlus());
        $this->cartManager->markAsPayed($cart);
        $this->assertNull($this->rep->getActiveAwSubscription($this->user));
    }

    public function testAwPlusAfterTrial()
    {
        $cartTrial = $this->cartManager->giveAwPlusTrial();
        $cartTrial->setPaydate(new \DateTime("-4 month"));
        $this->cartManager->save($cartTrial);

        $cart = $this->cartManager->createNewCart();
        $cart->addItem(new AwPlus());
        $this->cartManager->markAsPayed($cart);

        $this->assertNull($this->rep->getActiveAwSubscription($this->user));
    }

    public function testPendingPayPalSubscription()
    {
        $cartTrial = $this->cartManager->giveAwPlusTrial();
        $cartTrial->setPaydate(new \DateTime("-4 month"));
        $this->cartManager->save($cartTrial);

        $cart = $this->cartManager->createNewCart();
        $this->giveSubscription($cart, Cart::PAYMENTTYPE_CREDITCARD, true);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cart, $as);
    }

    public function testPayPalSubscriptionAfterPending()
    {
        $cartTrial = $this->cartManager->giveAwPlusTrial();
        $cartTrial->setPaydate(new \DateTime("-3 month, -1 hour"));
        $this->cartManager->save($cartTrial);

        $cartPending = $this->cartManager->createNewCart();
        $this->giveSubscription($cartPending, Cart::PAYMENTTYPE_CREDITCARD, true);
        $cartPending->setPaydate(new \DateTime("-2 month"));
        $this->cartManager->save($cartPending);

        $cartSubscription = $this->cartManager->createNewCart();
        $this->giveSubscription($cartSubscription, Cart::PAYMENTTYPE_CREDITCARD, false);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cartSubscription, $as);
    }

    public function testPayPalSubscriptionWithCoupon()
    {
        $cartSubscription = $this->cartManager->createNewCart();
        $this->giveSubscription($cartSubscription, Cart::PAYMENTTYPE_CREDITCARD, false);
        $cartSubscription->setPaydate(new \DateTime("-1 year, -2 day"));
        $this->cartManager->save($cartSubscription);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cartSubscription, $as);

        $cartCoupon = $this->cartManager->createNewCart();
        $cartCoupon->addItem(new AwPlus());
        $this->cartManager->markAsPayed($cartCoupon);
        $cartCoupon->setPaydate(new \DateTime("-6 month"));
        $this->cartManager->save($cartCoupon);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cartSubscription, $as);

        $this->user->setPaypalrecurringprofileid(null);
        $this->user->clearSubscription();
        $this->em->flush();

        $this->assertNull($this->rep->getActiveAwSubscription($this->user));
    }

    public function testIAPSubscriptionWithCoupon()
    {
        $cartSubscription = $this->cartManager->createNewCart();
        $this->giveSubscription($cartSubscription, Cart::PAYMENTTYPE_APPSTORE);
        $cartSubscription->setPaydate(new \DateTime("-6 month"));
        $this->cartManager->save($cartSubscription);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cartSubscription, $as);

        $cartSubscription->setPaydate(new \DateTime("-1 year, -2 day"));
        $this->cartManager->save($cartSubscription);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cartSubscription, $as);

        $cartSubscription->setPaydate(new \DateTime("-1 year, -10 day"));
        $this->cartManager->save($cartSubscription);

        $this->assertNull($this->rep->getActiveAwSubscription($this->user));

        $cartSubscription->setPaydate(new \DateTime("-1 year, -2 day"));
        $this->cartManager->save($cartSubscription);
        $cartCoupon = $this->cartManager->createNewCart();
        $cartCoupon->addItem(new AwPlus());
        $this->cartManager->markAsPayed($cartCoupon);
        $cartCoupon->setPaydate(new \DateTime("-6 month"));
        $this->cartManager->save($cartCoupon);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cartSubscription, $as);

        $this->user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $this->em->flush();

        $this->assertNull($this->rep->getActiveAwSubscription($this->user));

        $cartSubscription->setPaydate(new \DateTime("-10 month"));
        $this->cartManager->save($cartSubscription);

        $this->assertNotNull($this->rep->getActiveAwSubscription($this->user));
    }

    public function testCancelPayPalSubscription()
    {
        $cart = $this->cartManager->createNewCart();
        $this->giveSubscription($cart, Cart::PAYMENTTYPE_CREDITCARD, false);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cart, $as);
        $this->assertNotNull($this->user->getPaypalrecurringprofileid());

        $this->user->clearSubscription();
        $this->em->flush();

        $this->assertNull($this->rep->getActiveAwSubscription($this->user));
    }

    public function testPayPalExpiredAwPlusSubscription()
    {
        $cartSubscription = $this->cartManager->createNewCart();
        $this->giveSubscription($cartSubscription, Cart::PAYMENTTYPE_PAYPAL);
        $cartSubscription->setPaydate(new \DateTime("-1 year, -3 day"));
        $this->cartManager->save($cartSubscription);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cartSubscription, $as);

        $cartSubscription->setPaydate(new \DateTime("-1 year, -10 day"));
        $this->cartManager->save($cartSubscription);

        $as = $this->rep->getActiveAwSubscription($this->user);
        $this->assertNotNull($as);
        $this->assertEquals($cartSubscription, $as);

        $this->user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $this->em->flush();

        $this->assertNotNull($this->rep->getActiveAwSubscription($this->user));

        $this->user->clearSubscription();
        $this->em->flush();
        $this->assertNull($this->rep->getActiveAwSubscription($this->user));
    }

    public function testCreditCardThenBusiness()
    {
        $ccCart = $this->cartManager->createNewCart();
        $this->giveSubscription($ccCart, Cart::PAYMENTTYPE_CREDITCARD, true);

        $as = $this->rep->getActiveAwSubscription($this->user);
        codecept_debug("asCart id: {$as->getCartid()}");
        $this->assertEquals($ccCart->getCartid(), $as->getCartid());

        $businessCart = $this->cartManager->createNewCart();
        $this->giveSubscription($businessCart, Cart::PAYMENTTYPE_BUSINESS_BALANCE, true);
        codecept_debug("businessCart id: {$businessCart->getCartid()}");

        $as = $this->rep->getActiveAwSubscription($this->user);
        codecept_debug("asCart id: {$as->getCartid()}");
        $this->assertEquals($ccCart->getCartid(), $as->getCartid());
    }

    private function giveSubscription(Cart $cart, $paymentType, $pending = false)
    {
        $this->cartManager->addAwSubscriptionItem($cart, new \DateTime());
        $cart->setPaymenttype($paymentType);

        if (in_array($paymentType, [Cart::PAYMENTTYPE_CREDITCARD, Cart::PAYMENTTYPE_PAYPAL])) {
            $this->user->setPaypalrecurringprofileid("xxx");
            $this->user->setSubscription(Usr::SUBSCRIPTION_SAVED_CARD)
                       ->setSubscriptionType(Usr::SUBSCRIPTION_TYPE_AWPLUS);
        }

        if (in_array($paymentType, [Cart::PAYMENTTYPE_ANDROIDMARKET, Cart::PAYMENTTYPE_APPSTORE])) {
            $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE)
                       ->setSubscriptionType(Usr::SUBSCRIPTION_TYPE_AWPLUS);
        }

        if ($paymentType == Cart::PAYMENTTYPE_CREDITCARD) {
            $cart->setCreditcardtype('VISA');
            $cart->setCreditcardnumber(123456789);
        }

        if ($pending) {
            $subscription = $cart->getItemsByType([AwPlusSubscription::TYPE])->first();
            $subscription->setPrice(0);
            $cart->removeItemsByType([Discount::TYPE]);
        }

        $this->cartManager->markAsPayed($cart, $this->billingAddress);
        $this->em->flush();
    }
}
