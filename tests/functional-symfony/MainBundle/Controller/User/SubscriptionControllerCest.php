<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\User;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
use AwardWallet\Tests\Modules\DbBuilder\Cart as DbCart;
use AwardWallet\Tests\Modules\DbBuilder\CartItem as DbCartItem;
use AwardWallet\Tests\Modules\DbBuilder\User as DbUser;
use AwardWallet\Tests\Modules\DbBuilder\UserAgent as DbUserAgent;
use Codeception\Example;
use Codeception\Stub\StubMarshaler;
use Codeception\Util\Stub;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class SubscriptionControllerCest
{
    use AutoVerifyMocksTrait;
    use DataAttributeAssertionsTrait;

    private ?RouterInterface $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService(RouterInterface::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
    }

    public function testBusiness(\TestSymfonyGuy $I)
    {
        $I->amOnBusiness();
        $I->amOnRoute('aw_user_subscription_get_cancel');
        $I->seeCurrentRouteIs('aw_login');

        $I->makeUser($business = new DbUser(null, true, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS]));
        $I->makeUser($admin = new DbUser());
        $I->makeUserAgent(new DbUserAgent($admin, $business, [
            'AccessLevel' => ACCESS_ADMIN,
        ]));
        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $admin->getFields()['Login']]);
        $I->seeResponseCodeIs(403);
    }

    public function testUnauthorized(\TestSymfonyGuy $I)
    {
        $I->amOnRoute('aw_user_subscription_get_cancel');
        $I->seeCurrentRouteIs('aw_login');
    }

    public function testAuthorized(\TestSymfonyGuy $I)
    {
        $I->makeUser($user = new DbUser());
        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
    }

    public function testUserWithoutAwPlus(\TestSymfonyGuy $I)
    {
        $I->makeUser($user = new DbUser());
        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', 'It looks like you don’t have an active AwardWallet Plus subscription at the moment.');
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->dontSeeDataAttribute($I, 'data-can-cancel');
        $this->dontSeeDataAttribute($I, 'data-manual');
        $this->dontSeeDataAttribute($I, 'data-is-at201');
        $I->saveCsrfToken();
        $I->sendDelete($this->router->generate('aw_user_subscription_cancel'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['error' => 'You do not have an active subscription']);
    }

    public function testUserWithCancelledAwPlusSubscription(\TestSymfonyGuy $I)
    {
        $I->makeUser($user = new DbUser(null, true, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]));
        $I->makeCart(
            new DbCart(
                [
                    DbCartItem::awPlusSubscription1Year(),
                ],
                [
                    'UserID' => $user->getId(),
                    'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
                    'PayDate' => date('Y-m-d H:i:s', strtotime('-1 month')),
                ]
            )
        );
        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', 'It looks like you don’t have an active AwardWallet Plus subscription at the moment.');
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->dontSeeDataAttribute($I, 'data-can-cancel');
        $this->dontSeeDataAttribute($I, 'data-manual');
        $this->dontSeeDataAttribute($I, 'data-is-at201');
        $I->saveCsrfToken();
        $I->sendDelete($this->router->generate('aw_user_subscription_cancel'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['error' => 'You do not have an active subscription']);
    }

    public function testUserWithActiveAwPlusSubscriptionViaPayPal(\TestSymfonyGuy $I)
    {
        $I->makeUser($user = new DbUser(null, true, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
        ]));
        $I->makeCart(
            new DbCart(
                [
                    $cartItem = DbCartItem::awPlusSubscription1Year(),
                ],
                [
                    'UserID' => $user->getId(),
                    'PaymentType' => Cart::PAYMENTTYPE_PAYPAL,
                    'PayDate' => date('Y-m-d H:i:s', strtotime('-1 month')),
                ]
            )
        );
        $I->updateInDatabase('Usr', ['LastSubscriptionCartItemID' => $cartItem->getId()], ['UserID' => $user->getId()]);
        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', [
            'Currently, your AwardWallet Plus subscription is set up via PayPal;',
            'charged a new price of $49.99',
        ]);
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->seeDataAttributeContains($I, 'data-can-cancel', 'true');
        $this->dontSeeDataAttribute($I, 'data-manual');
        $this->dontSeeDataAttribute($I, 'data-is-at201');
        $I->saveCsrfToken();
        $this->mockRecurringManager($I, Stub::once(function () {
            return true;
        }));
        $I->sendDelete($this->router->generate('aw_user_subscription_cancel'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function testUserWithActiveAwPlusSubscriptionViaPayPalCreditCard(\TestSymfonyGuy $I)
    {
        $I->makeUser($user = new DbUser(null, true, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
        ]));
        $I->makeCart(
            new DbCart(
                [
                    $cartItem = DbCartItem::awPlusSubscription1Year(),
                ],
                [
                    'UserID' => $user->getId(),
                    'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
                    'PayDate' => date('Y-m-d H:i:s', strtotime('-1 month')),
                    'CreditCardType' => 'Visa',
                    'CreditCardNumber' => 'XXXXXXXXXXXX1234',
                ]
            )
        );
        $I->updateInDatabase('Usr', ['LastSubscriptionCartItemID' => $cartItem->getId()], ['UserID' => $user->getId()]);

        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', [
            'Currently, your AwardWallet Plus subscription is set up via credit card ending in *1234;',
            'charged a new price of $49.99',
        ]);
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->seeDataAttributeContains($I, 'data-can-cancel', 'true');
        $this->dontSeeDataAttribute($I, 'data-manual');
        $this->dontSeeDataAttribute($I, 'data-is-at201');
        $I->saveCsrfToken();
        $this->mockRecurringManager($I, Stub::once(function () {
            return true;
        }));
        $I->sendDelete($this->router->generate('aw_user_subscription_cancel'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function testUserWithActiveAwPlusSubscriptionViaStripe(\TestSymfonyGuy $I)
    {
        $I->makeUser($user = new DbUser(null, true, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'Subscription' => Usr::SUBSCRIPTION_STRIPE,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
        ]));
        $I->makeCart(
            new DbCart(
                [
                    $cartItem = DbCartItem::awPlusSubscription1Year(),
                ],
                [
                    'UserID' => $user->getId(),
                    'PaymentType' => Cart::PAYMENTTYPE_STRIPE_INTENT,
                    'PayDate' => date('Y-m-d H:i:s', strtotime('-1 month')),
                ]
            )
        );
        $I->updateInDatabase('Usr', ['LastSubscriptionCartItemID' => $cartItem->getId()], ['UserID' => $user->getId()]);

        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', [
            'Currently, your AwardWallet Plus subscription is set up via Credit Card;',
            'charged a new price of $49.99',
        ]);
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->seeDataAttributeContains($I, 'data-can-cancel', 'true');
        $this->dontSeeDataAttribute($I, 'data-manual');
        $this->dontSeeDataAttribute($I, 'data-is-at201');
        $I->saveCsrfToken();
        $this->mockRecurringManager($I, Stub::once(function () {
            return true;
        }));
        $I->sendDelete($this->router->generate('aw_user_subscription_cancel'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function testUserWithActiveAwPlusSubscriptionViaApple(\TestSymfonyGuy $I)
    {
        $I->makeUser($user = new DbUser(null, true, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'Subscription' => Usr::SUBSCRIPTION_MOBILE,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
        ]));
        $I->makeCart(
            new DbCart(
                [
                    $cartItem = DbCartItem::awPlusSubscription1Year(),
                ],
                [
                    'UserID' => $user->getId(),
                    'PaymentType' => Cart::PAYMENTTYPE_APPSTORE,
                    'PayDate' => date('Y-m-d H:i:s', strtotime('-1 month')),
                ]
            )
        );
        $I->updateInDatabase('Usr', ['LastSubscriptionCartItemID' => $cartItem->getId()], ['UserID' => $user->getId()]);
        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', [
            'Currently, your AwardWallet Plus subscription is set up via Apple;',
            'you will be charged $49.99 per year',
            'press the button below for instructions on how to cancel with Apple',
        ]);
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->dontSeeDataAttribute($I, 'data-can-cancel');
        $this->seeDataAttributeContains($I, 'data-manual', 'true');
        $this->dontSeeDataAttribute($I, 'data-is-at201');
        $I->saveCsrfToken();
        $I->sendDelete($this->router->generate('aw_user_subscription_cancel'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['error' => 'Unable to cancel the subscription automatically']);
    }

    public function testUserWithActiveAwPlusSubscriptionViaGoogle(\TestSymfonyGuy $I)
    {
        $I->makeUser($user = new DbUser(null, true, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'Subscription' => Usr::SUBSCRIPTION_MOBILE,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
        ]));
        $I->makeCart(
            new DbCart(
                [
                    $cartItem = DbCartItem::awPlusSubscription1Year(),
                ],
                [
                    'UserID' => $user->getId(),
                    'PaymentType' => Cart::PAYMENTTYPE_ANDROIDMARKET,
                    'PayDate' => date('Y-m-d H:i:s', strtotime('-1 month')),
                ]
            )
        );
        $I->updateInDatabase('Usr', ['LastSubscriptionCartItemID' => $cartItem->getId()], ['UserID' => $user->getId()]);
        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', [
            'Currently, your AwardWallet Plus subscription is set up via Google Play;',
            'charged a new price of $49.99',
            'If you still wish to cancel your subscription press the button below:',
        ]);
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->seeDataAttributeContains($I, 'data-can-cancel', 'true');
        $this->dontSeeDataAttribute($I, 'data-manual');
        $this->dontSeeDataAttribute($I, 'data-is-at201');
        $I->saveCsrfToken();
        $this->mockRecurringManager($I, Stub::once(function () {
            return true;
        }));
        $I->sendDelete($this->router->generate('aw_user_subscription_cancel'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    /**
     * @dataProvider dataProviderUserWithActive201Subscription
     */
    public function testUserWithActive201SubscriptionViaCc(\TestSymfonyGuy $I, Example $example)
    {
        $I->makeUser($user = new DbUser(null, true, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'Subscription' => Usr::SUBSCRIPTION_STRIPE,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
        ]));
        $I->makeCart(
            new DbCart(
                [
                    $cartItem = $example['cartItem'],
                ],
                [
                    'UserID' => $user->getId(),
                    'PaymentType' => Cart::PAYMENTTYPE_STRIPE_INTENT,
                    'PayDate' => date('Y-m-d H:i:s', strtotime('-1 month')),
                ]
            )
        );
        $I->updateInDatabase('Usr', ['LastSubscriptionCartItemID' => $cartItem->getId()], ['UserID' => $user->getId()]);
        $I->amOnRoute('aw_user_subscription_get_cancel', ['_switch_user' => $user->getFields()['Login']]);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentRouteIs('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', [
            'Currently, your AwardTravel 201 subscription (which also includes an AwardWallet Plus subscription) is set up via Credit Card;',
            sprintf('will be charged %s', $example['price']),
        ]);
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->seeDataAttributeContains($I, 'data-can-cancel', 'true');
        $this->dontSeeDataAttribute($I, 'data-manual');
        $this->seeDataAttributeContains($I, 'data-is-at201', 'true');

        $I->updateInDatabase('Cart', [
            'CreditCardType' => 'Visa',
            'CreditCardNumber' => 'XXXXXXXXXXXX1234',
        ]);
        $I->amOnRoute('aw_user_subscription_get_cancel');
        $this->seeDataAttributeContains($I, 'data-user-info', [
            'Currently, your AwardTravel 201 subscription (which also includes an AwardWallet Plus subscription) is set up via credit card ending in *1234;',
            sprintf('will be charged %s', $example['price']),
        ]);
        $this->seeDataAttributeContains($I, 'data-new-subscription-price', '$49.99');
        $this->seeDataAttributeContains($I, 'data-can-cancel', 'true');
        $this->dontSeeDataAttribute($I, 'data-manual');
        $this->seeDataAttributeContains($I, 'data-is-at201', 'true');
        $I->saveCsrfToken();
        $this->mockRecurringManager($I, Stub::once(function () {
            return true;
        }));
        $I->sendDelete($this->router->generate('aw_user_subscription_cancel'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function dataProviderUserWithActive201Subscription(): array
    {
        return [
            '1 year' => [
                'cartItem' => DbCartItem::at201Subscription1Year(),
                'price' => '$119.99 per year',
            ],
            '6 months' => [
                'cartItem' => DbCartItem::at201Subscription6Months(),
                'price' => '$69.99 per 6 months',
            ],
            '1 month' => [
                'cartItem' => DbCartItem::at201Subscription1Month(),
                'price' => '$14.99 per month',
            ],
        ];
    }

    private function mockRecurringManager(\TestSymfonyGuy $I, StubMarshaler $stub)
    {
        $I->mockService(RecurringManager::class, $I->stubMake(RecurringManager::class, [
            'cancelRecurringPayment' => $stub,
        ]));
    }
}
