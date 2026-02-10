<?php

namespace AwardWallet\Tests\Unit\Billing;

use AwardWallet\MainBundle\Entity\Billingaddress;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\State;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Codeception\Stub\Expected;
use Doctrine\ORM\UnitOfWork;

/**
 * @group frontend-unit
 * @group billing
 */
class CartManagerTest extends BaseContainerTest
{
    /** @var \Doctrine\ORM\UnitOfWork */
    protected $uow;

    /** @var \AwardWallet\MainBundle\Globals\Cart\Manager */
    protected $cartManager;

    /** @var \AwardWallet\MainBundle\Entity\Usr */
    protected $user;

    protected $remove = [];

    public function _before()
    {
        parent::_before();
        $this->uow = $this->em->getUnitOfWork();
        $this->cartManager = $this->container->get('aw.manager.cart');

        // ## register user ###
        $userData = include __DIR__ . '/../../_data/fakeUser.php';
        $userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $user = $userRep->findOneBy(['login' => $userData['Login']]);

        if ($user) {
            $this->em->remove($user);
            $this->em->flush();
        }
        $user = new Usr();
        $user->setLogin($userData['Login']);
        $user->setPass($userData['Pass']);
        $user->setFirstname($userData['FirstName']);
        $user->setLastname($userData['LastName']);
        $user->setEmail($userData['Email']);
        $user->setAccountlevel($userData['AccountLevel']);
        $user->setDiscountedUpgradeBefore(new \DateTime());
        $user->setBalanceWatchCredits(0);
        $this->em->persist($user);
        $this->user = $user;
        $this->remove[] = $user;
        $this->cartManager->setUser($user);
    }

    public function _after()
    {
        foreach ($this->remove as $entity) {
            $this->em->remove($entity);
        }

        if ($this->em) {
            $this->em->flush();
        }
        $this->remove = [];

        $this->cartManager =
        $this->uow = null;

        parent::_after();
    }

    public function testSameCart()
    {
        $cart1 = $this->cartManager->createNewCart();
        $cart2 = $this->cartManager->getCart();
        $this->assertEquals(spl_object_hash($cart1), spl_object_hash($cart2));
        $this->remove[] = $cart1;
        $this->remove[] = $cart2;
    }

    public function testNotSameCart()
    {
        $cart1 = $this->cartManager->createNewCart();
        $cart2 = $this->cartManager->createNewCart();
        $this->assertNotEquals(spl_object_hash($cart1), spl_object_hash($cart2));
        $this->remove[] = $cart1;
        $this->remove[] = $cart2;
    }

    public function testOneCard()
    {
        // ## Create cart ###
        $cart = $this->cartManager->createNewCart();
        $this->remove[] = $cart;
        // new cart
        $this->assertNull($cart->getCartid());
        $this->assertTrue($this->uow->getEntityState($cart) == UnitOfWork::STATE_MANAGED);
        $this->cartManager->save($cart);
        // after save cart
        $this->assertNotNull($cart->getCartid());
        $this->assertEquals($cart, $this->cartManager->getCart());
        $this->assertEquals(0, $cart->getTotalPrice());
        $this->assertEquals(0, $cart->getTotalPrice());
        $this->assertEquals($this->user, $cart->getUser());
        $this->assertEquals(0, count($cart->getItems()));
        $this->assertEquals(0, $cart->getQuantityItems());
        $this->assertTrue(!$cart->isPaid());
        $this->assertMatchesRegularExpression("~Order #" . $cart->getCartid() . "~", (string) $cart);
        // ## Add item ###
        $item1 = new CartItem\AwPlus();
        $cart->addItem($item1);
        // asserts
        $this->assertEquals(1, $item1->getCnt());
        $this->assertMatchesRegularExpression("~" . $item1->getName() . "~", (string) $item1);
        $this->assertEquals(5, $cart->getTotalPrice());
        $this->assertEquals(5, $cart->getTotalPrice());
        $this->assertEquals(1, count($cart->getItems()));
        $this->assertEquals(1, $cart->getQuantityItems());
        $this->assertMatchesRegularExpression("~, Order #" . $cart->getCartid() . "~", (string) $cart);
        $item1->setCnt(3);
        $this->assertEquals(15, $cart->getTotalPrice());
        $this->assertEquals(15, $cart->getTotalPrice());
        $this->assertEquals(1, count($cart->getItems()));
        $this->assertEquals(3, $cart->getQuantityItems());
        $this->assertEquals(0, $cart->getDiscountAmount());
        $item1->setPrice(4.70);
        $this->assertEquals(14.10, $cart->getTotalPrice());
        $item1->setPrice(5);
        $this->assertEquals($cart, $item1->getCart());
        $this->assertEquals(0, $item1->getDiscountAmount());
        $this->assertEquals(15, $item1->getTotalPrice());
        $this->assertEquals(15, $item1->getTotalPrice());
        $this->assertEquals(5, $item1->getPrice());
        $this->assertEquals($this->user->getUserid(), $item1->getId());
        // ## Add coupon ###
        $coupon = $this->createCoupon(50, new \DateTime('-1 day'), new \DateTime('+30 day'), 0, true, 2);
        $this->remove[] = $coupon;
        // asserts
        $cart->setCoupon($coupon);
        $this->assertEquals(7.5, $cart->getTotalPrice());
        $this->assertEquals(15, $item1->getTotalPrice());

        // ## Add item2, item3 ###
        $item2 = new CartItem\Donation();
        $item2->setPrice(10);
        $cart->addItem($item2);
        $item3 = new CartItem\Donation();
        $item3->setPrice(20);
        $cart->addItem($item3);
        // asserts
        $this->assertEquals(22.5, $cart->getTotalPrice());
        $this->assertEquals(3, count($cart->getItems()));
        $this->assertEquals(5, $cart->getQuantityItems());
        $this->assertEquals(22.5, $cart->getDiscountAmount());
        $this->assertEquals(2, count($cart->getItemsByType([CartItem\Donation::TYPE])));
        $this->assertEquals(1, count($cart->getItemsByType([CartItem\AwPlus::TYPE])));
        $this->assertEquals(0, count($cart->getItemsByType([CartItem\AwPlusRecurring::TYPE])));
        $this->assertTrue($cart->hasItemsByType([CartItem\Donation::TYPE]));
        $this->assertTrue($cart->hasItemsByType([CartItem\AwPlus::TYPE]));
        $this->assertFalse($cart->hasItemsByType([CartItem\AwPlusRecurring::TYPE]));
        $this->assertFalse($cart->isAwPlusRecurringPayment());

        $this->assertEquals(10, $item2->getTotalPrice());
        $this->assertEquals(5, $item2->getDiscountAmount());
        $this->assertEquals(0, $item2->getDiscount()); // 50 - after mark as paid
        $this->assertEquals(20, $item3->getTotalPrice());
        $this->assertEquals(10, $item3->getDiscountAmount());
        // removing errors after removal of the item
        $cart->setError('Error text');
        $this->assertNotNull($cart->getError());
        $cart->removeItem($item3);
        $this->assertNull($cart->getError());
        $this->assertEquals(2, count($cart->getItems()));
        $cart->removeItemsByType([CartItem\AwPlus::TYPE]);
        $this->assertEquals(1, count($cart->getItems()));
        $this->assertFalse($cart->hasItemsByType([CartItem\AwPlus::TYPE]));
        $this->assertTrue($cart->hasItemsByType([
            CartItem\AwPlusRecurring::TYPE,
            CartItem\Donation::TYPE,
        ]));
        $this->assertEquals($item2, $cart->getItemsByType([CartItem\Donation::TYPE])->first());
        $this->assertEquals(5, $cart->getTotalPrice());
        // removing errors after add of the item
        $cart->setError('Error text');
        $this->assertNotNull($cart->getError());
        $cart->addItem($item1);
        $this->assertNull($cart->getError());
        $cart->addItem($item3);
        $this->assertEquals(3, count($cart->getItems()));
    }

    public function testMultiCart()
    {
        $cart = $this->cartManager->createNewCart();
        $item1 = new CartItem\AwPlus();
        $item2 = (new CartItem\Donation())->setPrice(10);
        $item3 = (new CartItem\Donation())->setPrice(20);
        $cart->addItem($item1)
            ->addItem($item2)
            ->addItem($item3);

        $coupon = $this->createCoupon(50, new \DateTime('-1 day'), new \DateTime('+30 day'), 0, true, 2);
        $this->remove[] = $coupon;
        $cart->setCoupon($coupon);

        $this->assertFalse($this->user->paidFor(CartItem\Donation::TYPE));
        $this->assertFalse($this->user->usedCoupon($coupon));
        $this->cartManager->save($cart);
        // test cascade saving
        $this->assertNotNull($item1->getCartitemid());
        $this->assertTrue($this->uow->getEntityState($item2) == \Doctrine\ORM\UnitOfWork::STATE_MANAGED);
        $this->assertEquals($cart, $this->cartManager->getCart());
        // mark as paid
        $this->cartManager->markAsPayed($cart);
        $this->assertTrue($cart->isPaid());
        // test move cart to archive
        $this->assertEquals($this->user->getFirstname(), $cart->getFirstname());
        $this->assertEquals($this->user->getLastname(), $cart->getLastname());
        $this->assertEquals($this->user->getEmail(), $cart->getEmail());
        $this->assertEquals($coupon->getCode(), $cart->getCouponcode());
        $this->assertEquals($coupon->getName(), $cart->getCouponname());
        $this->assertEquals(0, $item1->getDiscount());
        $this->assertTrue($this->user->paidFor(CartItem\AwPlus::TYPE));
        $this->assertTrue($this->user->usedCoupon($coupon));
        $this->assertEquals(1, count($this->user->getCarts()));
        // get new cart
        $cart2 = $this->cartManager->createNewCart();
        $this->remove[] = $cart2;
        $this->assertNotEquals($cart, $cart2);
        $this->assertNull($cart2->getCartid());
        $this->assertTrue($this->uow->getEntityState($cart2) == \Doctrine\ORM\UnitOfWork::STATE_MANAGED);
        // add item
        $item11 = new CartItem\OneCard();
        $item11->setPrice(25);
        $item11->setCnt(10);
        $cart2->addItem($item11);
        // mark as paid
        $this->cartManager->markAsPayed($cart2);
        $this->assertTrue($cart2->isPaid());
        $this->assertTrue($this->user->paidFor([
            CartItem\OneCard::TYPE,
            CartItem\Booking::TYPE,
        ]));
        $this->assertEquals(2, count($this->user->getCarts()));
        // get new cart
        $cart3 = $this->cartManager->createNewCart();
        $this->remove[] = $cart3;
        $this->assertNotEquals($cart2, $cart3);
        $this->assertNull($cart3->getCartid());
        $this->assertTrue($this->uow->getEntityState($cart3) == \Doctrine\ORM\UnitOfWork::STATE_MANAGED);
        // mark as paid
        $this->cartManager->markAsPayed($cart3);
        $this->assertTrue($cart3->isPaid());
        $this->assertEquals(3, count($this->user->getCarts()));
        // test coupon
        $this->assertEquals(1, count($coupon->getCarts()));
        $this->assertEquals(1, $coupon->getNumberOfUses());
        $cart4 = $this->cartManager->createNewCart();
        $this->remove[] = $cart4;
        $cart4->setCoupon($coupon);
        $this->assertEquals(2, count($coupon->getCarts()));
        $this->assertEquals(1, $coupon->getNumberOfUses());
    }

    public function testClearCart()
    {
        $cart = $this->cartManager->createNewCart();
        $item1 = new CartItem\AwPlus();
        $item2 = (new CartItem\Donation())->setPrice(10);
        $item3 = (new CartItem\Donation())->setPrice(20);
        $cart->addItem($item1)
            ->addItem($item2)
            ->addItem($item3);

        $coupon = $this->createCoupon(50, new \DateTime('-1 day'), new \DateTime('+30 day'), 0, true, 2);
        $this->remove[] = $coupon;
        $cart->setCoupon($coupon);

        $this->assertEquals(3, count($cart->getItems()));
        $this->assertTrue($this->uow->getEntityState($cart) == \Doctrine\ORM\UnitOfWork::STATE_MANAGED);
        $this->cartManager->save($cart);
        $cart->setError('Error text');
        $cart->clear();
        $this->assertEquals(0, count($cart->getItems()));
        $this->assertNull($cart->getError());
        $this->assertNull($cart->getCoupon());
        $this->cartManager->save($cart);
    }

    public function testMarkAsPaid()
    {
        $this->mockService(BackgroundCheckScheduler::class, $this->makeEmpty(BackgroundCheckScheduler::class, [
            'onUserPlusChanged' => Expected::once(),
        ]));
        $cart = $this->cartManager->createNewCart();
        $this->remove[] = $cart;

        $coupon = $this->createCoupon(50, new \DateTime('-1 day'), new \DateTime('+30 day'), CartItem\OneCard::TYPE, true, 1);
        $this->remove[] = $coupon;

        $cart->setCoupon($coupon);
        $this->cartManager->save($cart);

        $this->assertFalse($cart->hasItemsByType([CartItem\OneCard::TYPE]));
        $this->assertEquals(0, count($cart->getItems()));

        $item1 = (new CartItem\AwPlus())->setPrice(50);
        $cart->addItem($item1);
        $this->cartManager->save($cart);
        $this->assertFalse($cart->hasItemsByType([CartItem\OneCard::TYPE]));
        $this->assertEquals(1, count($cart->getItems()));
        // aw plus
        $this->cartManager->markAsPayed($cart);
        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
    }

    public function testBackgroundCheckSchedulerIsCalledOnRefund(): void
    {
        $this->mockService(BackgroundCheckScheduler::class, $this->makeEmpty(BackgroundCheckScheduler::class, [
            'onRefundEvent' => Expected::once(),
        ]));
        $cart = $this->cartManager->createNewCart();
        $this->remove[] = $cart;

        $item1 = (new CartItem\AwPlus())->setPrice(50);
        $cart->addItem($item1);
        $this->cartManager->addAwSubscriptionItem($cart, date_create());
        $this->cartManager->save($cart);
        $this->cartManager->markAsPayed($cart);
        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());

        // refund
        $this->cartManager->refund($cart);
    }

    public function testSendMailPaymentComplete()
    {
        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->atLeastOnce())
            ->method('send');

        $cartManager = $this->cartManager;
        $cartManager->setMailer($mailer);
        $cart = $cartManager->createNewCart();
        $this->remove[] = $cart;

        $state = (new State())
            ->setName('MyState')
            ->setCountryid(1)
            ->setCode('my-state');
        $this->em->persist($state);
        $this->remove[] = $state;
        $country = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Country::class)->find(179);

        $billingAddress = new Billingaddress();
        $billingAddress->setAddressname('Name');
        $billingAddress->setFirstname('John');
        $billingAddress->setLastname('Preston');
        $billingAddress->setAddress1('My street, 16');
        $billingAddress->setCity('Moskow');
        $billingAddress->setZip('123456');
        $billingAddress->setStateid($state);
        $billingAddress->setCountryid($country);
        $billingAddress->setUserid($this->user);
        $this->em->persist($billingAddress);
        $this->remove[] = $billingAddress;

        $item1 = (new CartItem\AwPlus())->setPrice(50);
        $cart->addItem($item1);
        $cart->setPaymenttype(\AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_CREDITCARD);
        $cartManager->markAsPayed($cart, $billingAddress);
    }

    public function testTranslateItems()
    {
        $cartManager = $this->cartManager;
        $cart = $cartManager->createNewCart();
        $this->remove[] = $cart;
        $item1 = new CartItem\AwPlus();
        $cart->addItem($item1);
        $cartManager->save($cart);
        $this->assertMatchesRegularExpression("/Account upgrade from regular to AwardWallet Plus/", $item1->getName());
        $this->assertMatchesRegularExpression("/6 months/", $item1->getDescription());
    }

    public function testBalanceWatchCreditsCart()
    {
        $this->assertEquals(0, $this->user->getBalanceWatchCredits());
        $cart = $this->cartManager->createNewCart();
        $this->assertEquals(0, $cart->getTotalPrice());
        $this->assertNull($cart->getCartid());

        $itemCredit = new CartItem\BalanceWatchCredit();
        $itemCredit->setCnt(1);
        $cart->addItem($itemCredit);
        $this->assertTrue($cart->hasItemsByType([CartItem\BalanceWatchCredit::TYPE]));

        $this->cartManager->save($cart);
        $this->cartManager->markAsPayed($cart);
        $this->assertTrue($cart->isPaid());

        $userRow = $this->em->getConnection()->executeQuery('SELECT BalanceWatchCredits FROM Usr WHERE UserID = ?', [$this->user->getUserid()])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, $userRow['BalanceWatchCredits']);

        $transactions = $this->em->getConnection()->executeQuery('
            SELECT COUNT(*)
            FROM BalanceWatchCreditsTransaction
            WHERE
                    UserID  = ?
                AND Amount  = ?
                AND Balance = ?
                AND TransactionType = ?',
            [$this->user->getUserid(), 5, 1, \AwardWallet\MainBundle\Entity\BalanceWatchCreditsTransaction::TYPE_PURCHASE],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetchColumn();
        $this->assertEquals(1, $transactions);
    }

    protected function createCoupon($discount, $start, $end, $service, $firsttimeonly = true, $maxuses = 1)
    {
        $coupon = new \AwardWallet\MainBundle\Entity\Coupon();
        $coupon->setName('TestCoupon');
        $coupon->setCode(uniqid('TestCoupon-'));
        $coupon->setDiscount($discount);
        $coupon->setStartdate($start);
        $coupon->setEnddate($end);
        $coupon->setFirsttimeonly($firsttimeonly);
        $coupon->setMaxuses($maxuses);
        $coupon->addItem($service);

        return $coupon;
    }
}
