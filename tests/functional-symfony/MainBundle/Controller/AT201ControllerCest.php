<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Controller\AT201Controller;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
use AwardWallet\Tests\Modules\DbBuilder\Cart as DbCart;
use AwardWallet\Tests\Modules\DbBuilder\CartItem;

class AT201ControllerCest extends BaseTraitCest
{
    //    use AutoVerifyMocksTrait;
    //    use FreeUser;
    //    use LoggedIn;
    //
    //    public function testUnavailableLandingRoutes(\TestSymfonyGuy $I)
    //    {
    //        $I->amOnRoute('aw_at201_landing');
    //        $I->seeResponseCodeIs(404);
    //
    //        $I->amOnRoute('aw_at201_landing_locale', ['_locale' => 'ru']);
    //        $I->seeResponseCodeIs(404);
    //    }
    //
    //    public function testNewUserNotRedirected(\TestSymfonyGuy $I)
    //    {
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription1Year::TYPE]);
    //        $I->seeResponseCodeIs(404);
    //    }
    //
    //    public function testUserWithAwPlus(\TestSymfonyGuy $I)
    //    {
    //        $subscriptionItem = new AwPlusSubscription();
    //        $subscriptionStart = new \DateTime('-1 month');
    //        $subscriptionExpiration = (clone $subscriptionStart)->add(new \DateInterval(sprintf('P%sM', $subscriptionItem->getMonths())));
    //
    //        $I->makeCart(new DbCart(
    //            [
    //                CartItem::awPlusSubscription1Year(),
    //            ],
    //            [
    //                'UserID' => $this->user->getId(),
    //                'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
    //                'PayDate' => $subscriptionStart->format('Y-m-d H:i:s'),
    //            ]
    //        ));
    //        $I->updateInDatabase('Usr', [
    //            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
    //            'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
    //            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
    //            'PlusExpirationDate' => $subscriptionExpiration->format('Y-m-d H:i:s'),
    //        ], [
    //            'UserID' => $this->user->getId(),
    //        ]);
    //
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription1Year::TYPE]);
    //        $I->seeResponseCodeIs(404);
    //    }
    //
    //    public function testUserWith201Subscription(\TestSymfonyGuy $I)
    //    {
    //        $subscription201Start = new \DateTime('-1 year -1 month');
    //
    //        $awSubscriptionItem = new AwPlusSubscription();
    //        $awSubscriptionStart = new \DateTime('-7 day');
    //        $awSubscriptionExpiration = (clone $subscription201Start)->add(new \DateInterval(sprintf('P%sM', $awSubscriptionItem->getMonths())));
    //
    //        $I->makeCart(new DbCart(
    //            [
    //                CartItem::at201Subscription1Year(),
    //            ],
    //            [
    //                'UserID' => $this->user->getId(),
    //                'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
    //                'PayDate' => $subscription201Start->format('Y-m-d H:i:s'),
    //            ]
    //        ));
    //        $I->makeCart(new DbCart(
    //            [
    //                CartItem::awPlusSubscription1Year(),
    //            ],
    //            [
    //                'UserID' => $this->user->getId(),
    //                'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
    //                'PayDate' => $awSubscriptionStart->format('Y-m-d H:i:s'),
    //            ]
    //        ));
    //        $I->updateInDatabase('Usr', [
    //            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
    //            'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
    //            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
    //            'PlusExpirationDate' => $awSubscriptionExpiration->format('Y-m-d H:i:s'),
    //        ], [
    //            'UserID' => $this->user->getId(),
    //        ]);
    //
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription1Year::TYPE]);
    //        $I->seeResponseCodeIs(200);
    //    }
    //
    //    public function testWhiteList(\TestSymfonyGuy $I)
    //    {
    //        $I->amOnRoute('aw_at201_landing');
    //        $I->seeResponseCodeIs(404);
    //
    //        $I->amOnRoute('aw_at201_landing_locale', ['_locale' => 'ru']);
    //        $I->seeResponseCodeIs(404);
    //
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription1Year::TYPE]);
    //        $I->seeResponseCodeIs(404);
    //
    //        $whiteEmail = AT201Controller::WHITE_LIST[0];
    //        $I->executeQuery("DELETE FROM Usr WHERE Email = ' " . addslashes($whiteEmail) . "'");
    //        $I->executeQuery("UPDATE Usr SET Email = '" . addslashes($whiteEmail) . "' WHERE UserID = " . $this->user->getId());
    //        $this->logoutUser($I);
    //        $this->loginUser($I, $this->user);
    //
    //        $I->amOnRoute('aw_at201_landing');
    //        $I->seeResponseCodeIs(200);
    //
    //        $I->amOnRoute('aw_at201_landing_locale', ['_locale' => 'ru']);
    //        $I->seeResponseCodeIs(200);
    //
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription1Year::TYPE]);
    //        $I->seeResponseCodeIs(200);
    //    }
    //
    //    public function testMonthlySubscription(\TestSymfonyGuy $I)
    //    {
    //        $subscriptionItem = new AT201Subscription1Month();
    //        $subscriptionStart = new \DateTime('-30 day');
    //        $subscriptionExpiration = (clone $subscriptionStart)->add(new \DateInterval(sprintf('P%sM', $subscriptionItem->getMonths())));
    //
    //        $I->makeCart(new DbCart(
    //            [
    //                CartItem::at201Subscription1Month(),
    //            ],
    //            [
    //                'UserID' => $this->user->getId(),
    //                'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
    //                'PayDate' => $subscriptionStart->format('Y-m-d H:i:s'),
    //            ]
    //        ));
    //        $I->updateInDatabase('Usr', [
    //            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
    //            'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
    //            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
    //            'PlusExpirationDate' => $subscriptionExpiration->format('Y-m-d H:i:s'),
    //        ], [
    //            'UserID' => $this->user->getId(),
    //        ]);
    //
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription1Year::TYPE]);
    //        $I->followRedirects(false);
    //        $I->seeResponseCodeIs(200);
    //
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription6Months::TYPE]);
    //        $I->followRedirects(false);
    //        $I->seeResponseCodeIs(200);
    //
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription1Month::TYPE]);
    //        $I->followRedirects(false);
    //        $I->seeResponseCodeIs(200);
    //
    //        $I->executeQuery('DELETE FROM Cart WHERE UserID = ' . $this->user->getId());
    //        $I->updateInDatabase('Usr', [
    //            'Subscription' => null,
    //            'SubscriptionType' => null,
    //            'PlusExpirationDate' => null,
    //        ], [
    //            'UserID' => $this->user->getId(),
    //        ]);
    //
    //        $I->amOnRoute('aw_at201_payment', ['type' => AT201Subscription1Month::TYPE]);
    //        $I->followRedirects(false);
    //        $I->seeResponseCodeIs(404);
    //    }
}
