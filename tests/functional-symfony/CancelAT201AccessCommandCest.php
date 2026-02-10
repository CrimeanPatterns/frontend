<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Command\CancelAT201AccessCommand;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\AppBot\AT201Notifier;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\Tests\Modules\DbBuilder\Cart as DbCart;
use AwardWallet\Tests\Modules\DbBuilder\CartItem;
use Codeception\Util\Stub;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-functional
 */
class CancelAT201AccessCommandCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private const AT201_SITEGROUP_ID = 75;

    private const PAYPAL_PROFILE_ID = 'CARD-123';

    /** @var CommandTester */
    private $commandTester;

    /** @var CancelAT201AccessCommand */
    private $command;

    /** @var int */
    private $userId;

    public function testCancelSubscription(\CodeGuy $I)
    {
        $this->prepare($I, false, true);
        $at201Item = new AT201Subscription1Month();
        $at201Start = new \DateTime('-1 month -5 day');
        $at201Expiration = (clone $at201Start)->add(new \DateInterval(sprintf('P%sM', $at201Item->getMonths())));

        $I->makeCart(new DbCart(
            [
                CartItem::at201Subscription1Month(),
            ],
            [
                'UserID' => $this->userId,
                'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
                'PayDate' => $at201Start->format('Y-m-d H:i:s'),
            ]
        ));
        $I->updateInDatabase('Usr', [
            'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
            'PlusExpirationDate' => $at201Expiration->format('Y-m-d H:i:s'),
            'AT201ExpirationDate' => $at201Expiration->format('Y-m-d'),
        ], [
            'UserID' => $this->userId,
        ]);

        $paypal = $I->stubMake(PaypalRestApi::class, [
            'deleteSavedCard' => Stub::exactly(1,
                function ($cardId) use ($I) {
                    $I->assertEquals(self::PAYPAL_PROFILE_ID, $cardId);
                }
            ),
        ]);

        $I->mockService(PaypalRestApi::class, $paypal);
        $this->commandTester->execute(['--userId' => $this->userId, '--apply' => true]);

        $I->seeInDatabase('Usr',
            [
                'UserID' => $this->userId,
                'Subscription' => null,
                'SubscriptionType' => null,
                'AT201ExpirationDate' => null,
            ]
        );
        $I->dontSeeInDatabase('GroupUserLink', ['UserID' => $this->userId, 'SiteGroupID' => self::AT201_SITEGROUP_ID]);
    }

    public function testCancelAccessWithActivePlus(\CodeGuy $I)
    {
        $this->prepare($I, false, true);
        $at201Item = new AT201Subscription1Month();
        $at201Start = new \DateTime('-1 month -5 day');
        $at201Expiration = (clone $at201Start)->add(new \DateInterval(sprintf('P%sM', $at201Item->getMonths())));

        $I->makeCart(new DbCart(
            [
                CartItem::at201Subscription1Month(),
            ],
            [
                'UserID' => $this->userId,
                'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
                'PayDate' => $at201Start->format('Y-m-d H:i:s'),
            ]
        ));

        $plusItem = new AwPlusSubscription();
        $plusStart = new \DateTime();
        $plusExpiration = (clone $plusStart)->add(new \DateInterval(sprintf('P%sM', $plusItem->getMonths())));

        $I->makeCart(new DbCart(
            [
                CartItem::awPlusSubscription1Year(),
            ],
            [
                'UserID' => $this->userId,
                'PaymentType' => Cart::PAYMENTTYPE_CREDITCARD,
                'PayDate' => $plusStart->format('Y-m-d H:i:s'),
            ]
        ));

        $I->updateInDatabase('Usr', [
            'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
            'PlusExpirationDate' => $plusExpiration->format('Y-m-d H:i:s'),
            'AT201ExpirationDate' => $at201Expiration->format('Y-m-d'),
        ], [
            'UserID' => $this->userId,
        ]);

        $this->commandTester->execute(['--userId' => $this->userId, '--apply' => true]);

        $I->seeInDatabase('Usr',
            [
                'UserID' => $this->userId,
                'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                'PlusExpirationDate' => $plusExpiration->format('Y-m-d H:i:s'),
                'AT201ExpirationDate' => null,
            ]
        );
        $I->dontSeeInDatabase('GroupUserLink', ['UserID' => $this->userId, 'SiteGroupID' => self::AT201_SITEGROUP_ID]);
    }

    public function testRecalculateAT201Expiration(\CodeGuy $I)
    {
        $this->prepare($I, true, false);
        $at201Item = new AT201Subscription1Month();
        $at201Start = new \DateTime('-10 day');
        $at201Expiration = (clone $at201Start)->add(new \DateInterval(sprintf('P%sM', $at201Item->getMonths())));

        $I->addUserPayment($this->userId, Cart::PAYMENTTYPE_CREDITCARD, $at201Item, null, $at201Start);
        $I->seeInDatabase('Usr',
            [
                'UserID' => $this->userId,
                'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                'PlusExpirationDate' => $at201Expiration->format('Y-m-d H:i:s'),
                'AT201ExpirationDate' => $at201Expiration->format('Y-m-d'),
            ]
        );
        $I->updateInDatabase(
            'Usr',
            ['UserID' => $this->userId],
            ['AT201ExpirationDate' => (new \DateTime('-5 day'))->format('Y-m-d H:i:s')]
        );

        $this->commandTester->execute(['--userId' => $this->userId, '--apply' => true]);

        $I->seeInDatabase('Usr',
            [
                'UserID' => $this->userId,
                'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                'PlusExpirationDate' => $at201Expiration->format('Y-m-d H:i:s'),
                'AT201ExpirationDate' => $at201Expiration->format('Y-m-d'),
            ]
        );
        $I->seeInDatabase('GroupUserLink', ['UserID' => $this->userId, 'SiteGroupID' => self::AT201_SITEGROUP_ID]);
    }

    protected function prepare(\CodeGuy $I, bool $mockNotifierSubscribed, bool $mockNotifierUnsubscribed)
    {
        $this->userId = $I->createAwUser(null, null, ['Subscription' => Usr::SUBSCRIPTION_SAVED_CARD, 'PaypalRecurringProfileID' => self::PAYPAL_PROFILE_ID]);
        $methods = [];

        if ($mockNotifierUnsubscribed) {
            $methods['unsubscribed'] = Stub::atLeastOnce(
                function (Cart $cart) use ($I) {
                    $I->assertEquals($this->userId, $cart->getUser()->getUserid());
                }
            );
        }

        if ($mockNotifierSubscribed) {
            $methods['subscribed'] = Stub::atLeastOnce(
                function (Cart $cart) use ($I) {
                    $I->assertEquals($this->userId, $cart->getUser()->getUserid());
                }
            );
        }

        $I->mockService(AT201Notifier::class, $I->stubMake(AT201Notifier::class, $methods));

        $this->command = $I->getContainer()->get(CancelAT201AccessCommand::class);
        $this->commandTester = new CommandTester($this->command);
    }
}
