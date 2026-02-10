<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Globals\Cart\UpgradeCodeGenerator;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-functional
 * @group billing
 */
class ChangePaymentMethodFromEmailCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private const CREDIT_CARD_BRAND = 'visa';
    private const CREDIT_CARD_LAST4 = '4242';

    private ?UsrRepository $userRepo;

    private ?CartRepository $cartRepository;

    private ?UpgradeCodeGenerator $codeGenerator;

    private ?Manager $cartManager;

    private ?EntityManagerInterface $em;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userRepo = $I->grabService(UsrRepository::class);
        $this->cartRepository = $I->grabService(CartRepository::class);
        $this->codeGenerator = $I->grabService(UpgradeCodeGenerator::class);
        $this->cartManager = $I->grabService(Manager::class);
        $this->em = $I->grabService(EntityManagerInterface::class);
    }

    public function testDontPreserveDiscount30(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        // create user with active subscription
        /** @var Usr $user */
        $user = $this->userRepo->find($userId);
        $this->cartManager->setUser($user);
        $cart = $this->cartManager->createNewCart();
        $cart->setPaymenttype(Cart::PAYMENTTYPE_CREDITCARD);
        $startDate = new \DateTime('-1 year');
        $startDate->modify('-3 day');
        $this->cartManager->addAwSubscriptionItem($cart, $startDate, false, false);
        $this->cartManager->addPercentDiscount($cart, 30, Discount::ID_DISCOUNT30, SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR));
        $this->cartManager->markAsPayed(null, null, $startDate, true);
        $user->setSubscription(Usr::SUBSCRIPTION_PAYPAL);
        $this->em->flush();

        $expectedPrice = round(SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR) * 0.7, 2);
        $I->assertEquals($expectedPrice, $cart->getTotalPrice());
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $user->getAccountLevel());
        $subscription = $this->cartRepository->getActiveAwSubscription($user);
        $I->assertEquals($subscription, $cart);
        $I->assertCount(1, $user->getCarts());

        $I->prepareStripeIntentMocks();

        $codeGenerator = $I->grabService(UpgradeCodeGenerator::class);
        $I->amOnRoute('aw_cart_change_payment_method_email', ['userId' => $user->getId(), 'hash' => $codeGenerator->generateCode($user)]);
        $I->payWithStripeIntent($user->getId(), $user->getEmail(), false, AwPlusSubscription::PRICE, AwPlusSubscription::PRICE);

        $this->em->flush();
        $this->em->clear();
        $user = $this->userRepo->find($userId);

        $I->assertCount(2, $user->getCarts());
        /** @var Cart $lastCart */
        $lastCart = $user->getCarts()->last();
        $I->assertEquals(AwPlusSubscription::PRICE, $lastCart->getTotalPrice());
        $I->assertEquals(self::CREDIT_CARD_BRAND, $lastCart->getCreditcardtype());
        $I->assertEquals(self::CREDIT_CARD_LAST4, $lastCart->getCreditcardnumber());
        $I->assertNull($lastCart->findItemByClass(Discount::class));
    }

    public function testRepeatLastSubscription(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        /** @var Usr $user */
        $user = $this->userRepo->find($userId);
        $this->cartManager->setUser($user);
        $cart = $this->cartManager->createNewCart();
        $cart->setPaymenttype(Cart::PAYMENTTYPE_CREDITCARD);
        $startDate = new \DateTime('-40 day');
        $this->cartManager->addAT201SubscriptionItem($cart, SubscriptionPeriod::DURATION_1_MONTH);
        $this->cartManager->markAsPayed(null, null, $startDate, true);
        $this->em->flush();

        $I->assertEquals(AT201Subscription1Month::PRICE, $cart->getTotalPrice());
        $I->assertCount(1, $user->getCarts());

        $I->prepareStripeIntentMocks();

        $codeGenerator = $I->grabService(UpgradeCodeGenerator::class);
        $I->amOnRoute('aw_cart_change_payment_method_email', ['userId' => $user->getId(), 'hash' => $codeGenerator->generateCode($user)]);
        $I->payWithStripeIntent($user->getId(), $user->getEmail(), false, AT201Subscription1Month::PRICE, AT201Subscription1Month::PRICE);
        $this->em->flush();

        $this->em->clear();
        $user = $this->userRepo->find($userId);
        $I->assertCount(2, $user->getCarts());
        /** @var Cart $lastCart */
        $lastCart = $user->getCarts()->last();
        $I->assertEquals(AT201Subscription1Month::PRICE, $lastCart->getTotalPrice());
        $I->assertEquals(self::CREDIT_CARD_BRAND, $lastCart->getCreditcardtype());
        $I->assertEquals(self::CREDIT_CARD_LAST4, $lastCart->getCreditcardnumber());
        $I->assertEquals(1, $lastCart->getItems()->count());
        $I->assertEquals(AT201Subscription1Month::class, get_class($lastCart->getSubscriptionItem()));
    }

    public function validEmailLinkAnonymous(\TestSymfonyGuy $I)
    {
        $userRepo = $I->grabService(UsrRepository::class);
        $codeGenerator = $I->grabService(UpgradeCodeGenerator::class);

        $user = $userRepo->find($I->createAwUser());

        $I->prepareStripeIntentMocks();
        $I->amOnRoute("aw_cart_change_payment_method_email", ["userId" => $user->getId(), "hash" => $codeGenerator->generateCode($user)]);
        $I->payWithStripeIntent($user->getId(), $user->getEmail(), false, AwPlusSubscription::PRICE, AwPlusSubscription::PRICE);

        $I->amOnRoute("aw_account_list");
        $I->seeCurrentUrlEquals("/login?BackTo=%2Faccount%2Flist%2F");

        $I->verifyMocks();
    }

    public function validEmailLinkLoggedIn(\TestSymfonyGuy $I)
    {
        $loggedIn = $this->userRepo->find($I->createAwUser());
        $linkOwner = $this->userRepo->find($I->createAwUser());

        $I->amOnRoute("aw_cart_change_payment_method_email", ["userId" => $linkOwner->getId(), "hash" => $this->codeGenerator->generateCode($linkOwner), "_switch_user" => $loggedIn->getLogin()]);
        $I->see($loggedIn->getFirstname());
        $I->seeCurrentRouteIs("aw_cart_common_paymenttype");

        $I->verifyMocks();
    }

    public function oldEmailLink(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/cart/paypal/change-payment");
        $I->seeCurrentUrlEquals("/login?BackTo=/cart/change-payment");
    }

    public function mobileOldEmailLink(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148');
        $I->amOnPage("/cart/paypal/change-payment");
        $I->seeCurrentUrlEquals("/m/login?BackTo=%2Fcart%2Fchange-payment");
    }

    public function invalidHash(\TestSymfonyGuy $I)
    {
        $user = $this->userRepo->find($I->createAwUser());
        $I->amOnRoute("aw_cart_change_payment_method_email", ["userId" => $user->getId(), "hash" => bin2hex(random_bytes(20))]);
        $I->seeCurrentUrlEquals("/login?BackTo=/cart/change-payment");
    }

    private function pay(\TestSymfonyGuy $I, Usr $user)
    {
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase('Usr', 'AccountLevel', ['UserID' => $user->getId()]));
        $I->see("Credit Card Info");
        $I->seeInField("//input[@name='card_info[full_name]']", $user->getFirstname() . " " . $user->getLastname());

        $cartId = CreditCardControllerCest::sendPaymentForm($I);
        $I->see("You have successfully paid");
        $I->seeEmailTo($user->getEmail(), "Order ID: {$cartId}", null, SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR));

        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR) . '*', $email->getBody());
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase('Usr', 'AccountLevel', ['UserID' => $user->getId()]));
    }

    private function mockPaypal(\TestSymfonyGuy $I)
    {
        $I->mockService(
            PaypalRestApi::class,
            $I->stubMake(PaypalRestApi::class, [
                'saveCreditCard' => Stub::exactly(1, function (array $cardData, $firstName, $lastName, $userId, $cartId) use ($I) {
                    $I->assertEquals("Billy", $firstName);
                    $I->assertEquals("Villy", $lastName);

                    return bin2hex(random_bytes(4));
                }),
                'payWithSavedCard' => Stub::exactly(1, function ($cart, $cardData, $directAmount) use ($I) {
                    $I->assertEquals(SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR), $directAmount);

                    return bin2hex(random_bytes(4));
                }),
            ])
        );
    }
}
