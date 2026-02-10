<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\AwPlusGiftType;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Translator;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Security\LoginTrait;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group billing
 */
class GiftAWPlusCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    private const IS_ONLY_STAFF = false;

    /* @var RouterInterface */
    private $router;

    /* @var EntityManager */
    private $entityManager;

    /** @var Translator */
    private $translator;

    /** @var Usr */
    private $giverUser;

    /** @var Usr */
    private $recipientUser;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->entityManager = $I->grabService('doctrine.orm.entity_manager');
        $this->translator = $I->grabService('translator');

        $this->giverUser = $this->createUser($I, ['FirstName' => 'giverName'], self::IS_ONLY_STAFF);
        $this->recipientUser = $this->createUser($I, ['FirstName' => 'recipientName'], self::IS_ONLY_STAFF);

        $I->login($this->giverUser['login'], $this->giverUser['password']);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);
        unset($this->router, $this->entityManager);
    }

    public function invalidEmailTest(\TestSymfonyGuy $I): void
    {
        $I->amOnPage($this->router->generate('aw_user_giftawplus'));
        $I->sendPOST($this->router->generate('aw_user_giftawplus'), [
            'user_gift_awplus' => [
                '_token' => $I->grabValueFrom(['id' => 'user_gift_awplus__token']),
                'email' => 'invalid-email',
            ],
        ]);
        $I->see('This value is not a valid email address.');
    }

    public function userNotExistsTest(\TestSymfonyGuy $I): void
    {
        $I->amOnPage($this->router->generate('aw_user_giftawplus'));
        $I->sendPOST($this->router->generate('aw_user_giftawplus'), [
            'user_gift_awplus' => [
                '_token' => $I->grabValueFrom(['id' => 'user_gift_awplus__token']),
                'email' => 'user@not-exist.com',
            ],
        ]);
        $I->see($this->translator->trans('booking.request.add.form.contact.errors.not-exist', [], 'booking'));
    }

    public function bruteforceTest(\TestSymfonyGuy $I)
    {
        for ($i = 0; $i <= 11; $i++) {
            $I->amOnPage($this->router->generate('aw_user_giftawplus'));
            $I->sendPOST($this->router->generate('aw_user_giftawplus'), [
                'user_gift_awplus' => [
                    '_token' => $I->grabValueFrom(['id' => 'user_gift_awplus__token']),
                    'email' => $this->recipientUser['email'],
                    'payType' => 2,
                ],
            ]);
        }

        $I->see($this->translator->trans('user.email.locked', [], 'validators'));
    }

    public function friendAlreadyWithAwplus(\TestSymfonyGuy $I): void
    {
        $user3 = $this->createUser($I, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
        ]);

        $I->amOnPage($this->router->generate('aw_user_giftawplus'));
        $I->sendPOST($this->router->generate('aw_user_giftawplus'), [
            'user_gift_awplus' => [
                '_token' => $I->grabValueFrom(['id' => 'user_gift_awplus__token']),
                'email' => $user3['email'],
            ],
        ]);
        $I->see($this->translator->trans('user-already-awplus'));
    }

    public function payTest(\TestSymfonyGuy $I)
    {
        $giverUser = $this->entityManager->getRepository(Usr::class)->find($this->giverUser['userId']);
        $recipientUser = $this->entityManager->getRepository(Usr::class)->find($this->recipientUser['userId']);

        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($recipientUser->getId());
        $I->assertFalse($recipientUser->isAwPlus());
        $I->assertNull($recipientUser->getSubscription());

        $I->amOnPage($this->router->generate('aw_user_giftawplus'));
        $data = [
            'email' => $this->recipientUser['email'],
            'payType' => AwPlusGiftType::PAY_TYPE_YEARLY,
            'message' => 'user-msg',
        ];
        $I->submitForm('#giftForm', [
            'user_gift_awplus' => $data,
        ]);

        $I->prepareStripeIntentMocks($giverUser->getFullName());
        $I->see('Select Payment Type');
        $I->submitForm("//form", [
            'select_payment_type' => [
                '_token' => $I->grabValueFrom(['id' => 'select_payment_type__token']),
                'type' => Cart::PAYMENTTYPE_STRIPE_INTENT,
            ],
        ]);

        /** @var Session $session */
        $session = $I->grabService('session');
        $I->assertEquals($data, $session->get(AwPlusGiftType::SESSION_GIFT_AWPLUS_DATA));

        $cartId = $I->payWithStripeIntent($recipientUser->getId(), $recipientUser->getEmail(), false, SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR), SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR), null);

        if (self::IS_ONLY_STAFF) {
            $I->see('Order #' . $cartId . ' has been successfully submitted');
        } else {
            $I->see('You have successfully paid');
        }

        $this->entityManager->refresh($recipientUser);
        $this->entityManager->refresh($giverUser);
        $I->seeInDatabase('Cart', ['UserID' => $recipientUser->getId()]);
        $I->assertEquals('cu_123', $giverUser->getStripeCustomerId());
        $I->seeInDatabase('CartItem', ['CartId' => $cartId, 'TypeID' => AwPlusSubscription::TYPE]);
        $I->seeInDatabase('CartItem', ['CartId' => $cartId, 'ID' => $giverUser->getId(), 'TypeID' => AwPlusGift::TYPE]);

        $user = $this->entityManager->getConnection()->fetchAssoc('SELECT AccountLevel, Subscription, SubscriptionType, PayPalRecurringProfileID FROM Usr WHERE UserID = ' . $recipientUser->getId());
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $user['AccountLevel']);
        $I->assertEquals(Usr::SUBSCRIPTION_STRIPE, $user['Subscription']);
        $I->assertEquals(Usr::SUBSCRIPTION_TYPE_AWPLUS, $user['SubscriptionType']);
        $I->assertEquals("pm_123", $user['PayPalRecurringProfileID']);

        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($recipientUser->getId());
        $monthCount = round(($newExpiration['date'] - $oldExpiration['date']) / (SECONDS_PER_DAY * 30));
        $I->assertEquals(12, $monthCount);

        $I->seeEmailTo($giverUser->getEmail(), 'Order ID: ' . $cartId, null, 30);
        $I->seeEmailTo($recipientUser->getEmail(), 'You’ve just been given a year of AwardWallet Plus!', null, 30);
    }

    public function payWithPaypalTest(\TestSymfonyGuy $I)
    {
        $giverUser = $this->entityManager->getRepository(Usr::class)->find($this->giverUser['userId']);
        $recipientUser = $this->entityManager->getRepository(Usr::class)->find($this->recipientUser['userId']);

        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($recipientUser->getId());
        $I->assertFalse($recipientUser->isAwPlus());
        $I->assertNull($recipientUser->getSubscription());

        $I->amOnPage($this->router->generate('aw_user_giftawplus'));
        $data = [
            'email' => $this->recipientUser['email'],
            'payType' => AwPlusGiftType::PAY_TYPE_YEARLY,
            'message' => 'user-msg',
        ];
        $I->submitForm('#giftForm', [
            'user_gift_awplus' => $data,
        ]);

        $I->preparePayPalMocks(0, SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR));
        $I->see('Select Payment Type');
        $I->submitForm("//form", [
            'select_payment_type' => [
                '_token' => $I->grabValueFrom(['id' => 'select_payment_type__token']),
                'type' => Cart::PAYMENTTYPE_PAYPAL,
            ],
        ]);

        /** @var Session $session */
        $session = $I->grabService('session');
        $I->assertEquals($data, $session->get(AwPlusGiftType::SESSION_GIFT_AWPLUS_DATA));

        $cartId = $I->processPayPal();

        $this->entityManager->refresh($recipientUser);
        $this->entityManager->refresh($giverUser);
        $I->seeInDatabase('Cart', ['UserID' => $recipientUser->getId()]);
        $I->assertEquals(null, $giverUser->getStripeCustomerId());
        $I->seeInDatabase('CartItem', ['CartId' => $cartId, 'TypeID' => AwPlusSubscription::TYPE]);
        $I->seeInDatabase('CartItem', ['CartId' => $cartId, 'ID' => $giverUser->getId(), 'TypeID' => AwPlusGift::TYPE]);

        $user = $this->entityManager->getConnection()->fetchAssoc('SELECT AccountLevel, Subscription, SubscriptionType, PayPalRecurringProfileID FROM Usr WHERE UserID = ' . $recipientUser->getId());
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $user['AccountLevel']);
        $I->assertEquals(Usr::SUBSCRIPTION_PAYPAL, $user['Subscription']);
        $I->assertEquals(Usr::SUBSCRIPTION_TYPE_AWPLUS, $user['SubscriptionType']);
        $I->assertEquals("someProfileId", $user['PayPalRecurringProfileID']);

        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($recipientUser->getId());
        $monthCount = round(($newExpiration['date'] - $oldExpiration['date']) / (SECONDS_PER_DAY * 30));
        $I->assertEquals(12, $monthCount);

        $I->seeEmailTo($giverUser->getEmail(), 'Order ID: ' . $cartId, null, 30);
        $I->seeEmailTo($recipientUser->getEmail(), 'You’ve just been given a year of AwardWallet Plus!', null, 30);
    }

    public function checkOwnerWithSwitchBalanceWatchPay(\TestSymfonyGuy $I)
    {
        $giverUser = $this->entityManager->getRepository(Usr::class)->find($this->giverUser['userId']);

        $this->startGiftPay($I);

        // abort => switch to balancewatch pay
        $this->entityManager->flush($giverUser->setAccountlevel(ACCOUNT_LEVEL_AWPLUS));
        $I->amOnPage($this->router->generate('aw_users_pay_balancewatchcredit'));
        $I->see('Balance Watch Credits');
        $I->submitForm("//form", [
            'user_pay_balanceWatchCredit' => [
                '_token' => $I->grabValueFrom(['id' => 'user_pay_balanceWatchCredit__token']),
                'balanceWatchCredit' => 1,
            ],
        ]);
        $I->see('Select Payment Type');
        $I->submitForm("//form", [
            'select_payment_type' => [
                '_token' => $I->grabValueFrom(['id' => 'select_payment_type__token']),
                'type' => Cart::PAYMENTTYPE_STRIPE_INTENT,
            ],
        ]);
        $I->see('Order Review');

        $cartManager = $I->grabService('aw.manager.cart');
        $cart = $cartManager->getCart();
        $I->assertEquals($cart->getUser()->getId(), $this->giverUser['userId']);
    }

    public function checkOwnerWithSwitchSubscriptionPay(\TestSymfonyGuy $I)
    {
        $I->prepareStripeIntentMocks("giverName Petrovich");
        $this->startGiftPay($I);

        // abort => switch to subscription pay
        $I->amOnPage($this->router->generate('aw_users_pay'));
        $I->see('AwardWallet Plus subscription set up');
        $I->submitForm("//form", [
            'user_pay' => [
                '_token' => $I->grabValueFrom(['id' => 'user_pay__token']),
                'awPlus' => 'true',
                'onecard' => 0,
            ],
        ]);
        $I->see('Select Payment Type');
        $I->submitForm("//form", [
            'select_payment_type' => [
                '_token' => $I->grabValueFrom(['id' => 'select_payment_type__token']),
                'type' => Cart::PAYMENTTYPE_STRIPE_INTENT,
            ],
        ]);
        $I->see('Order Review');

        $cartManager = $I->grabService('aw.manager.cart');
        $cart = $cartManager->getCart();
        $I->assertEquals($cart->getUser()->getId(), $this->giverUser['userId']);
    }

    private function startGiftPay(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_user_giftawplus'));

        $data = [
            'email' => $this->recipientUser['email'],
            'payType' => AwPlusGiftType::PAY_TYPE_YEARLY,
            'message' => 'user-msg',
        ];
        $I->submitForm('#giftForm', [
            'user_gift_awplus' => $data,
        ]);

        $I->see('Select Payment Type');
        $I->submitForm("//form", [
            'select_payment_type' => [
                '_token' => $I->grabValueFrom(['id' => 'select_payment_type__token']),
                'type' => Cart::PAYMENTTYPE_STRIPE_INTENT,
            ],
        ]);

        $cartManager = $I->grabService('aw.manager.cart');
        $cart = $cartManager->getCart();

        $I->assertTrue($cart->hasItemsByType([AwPlusGift::TYPE]));
        $I->assertEquals($cart->getUser()->getId(), $this->recipientUser['userId']);
    }
}
