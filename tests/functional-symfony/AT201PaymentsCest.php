<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Service\AppBot\AT201Notifier;
use Codeception\Example;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class AT201PaymentsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private const AT201_SITEGROUP_ID = 75;

    /** @var int */
    private $userId;

    public function _before(\CodeGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, ['Subscription' => Usr::SUBSCRIPTION_SAVED_CARD]);
        $I->mockService(AT201Notifier::class, $I->stubMake(AT201Notifier::class, [
            'subscribed' => Stub::atLeastOnce(
                function (Cart $cart) use ($I) {
                    $I->assertEquals($this->userId, $cart->getUser()->getUserid());
                }
            ),
        ]));
    }

    /**
     * @dataProvider availablePayments
     */
    public function testPaymentSuccess(\TestSymfonyGuy $I, Example $example): void
    {
        /** @var AT201SubscriptionInterface $cartItem */
        $cartItem = $example['item'];
        $now = new \DateTime();
        $expiration = (clone $now)->add(new \DateInterval(sprintf('P%sM', $cartItem->getMonths())));

        $I->addUserPayment($this->userId, Cart::PAYMENTTYPE_CREDITCARD, $cartItem, null, $now);

        $I->seeInDatabase('Usr',
            [
                'UserID' => $this->userId,
                'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                'PlusExpirationDate' => $expiration->format('Y-m-d H:i:s'),
                'AT201ExpirationDate' => $expiration->format('Y-m-d'),
            ]
        );

        $I->seeInDatabase('GroupUserLink', ['UserID' => $this->userId, 'SiteGroupID' => self::AT201_SITEGROUP_ID]);
    }

    /**
     * @dataProvider expirationsProvider
     */
    public function testAwPlusExpiration(\TestSymfonyGuy $I, Example $example): void
    {
        $em = $I->getContainer()->get('doctrine.orm.default_entity_manager');
        /** @var Usr $userEntity */
        $userEntity = $em->getRepository(Usr::class)->find($this->userId);
        $plusItem = new AwPlusSubscription();
        /** @var \DateTime $plusStart */
        $plusStart = $example['plusStart'];
        $plusExpiration = (clone $plusStart)->add(new \DateInterval(sprintf('P%sM', $plusItem->getMonths())));
        /** @var \DateTime $newPlusExpiration */
        $newPlusExpiration = $example['awPlusExpiration'];

        $I->addUserPayment($this->userId, Cart::PAYMENTTYPE_CREDITCARD, $plusItem, null, $plusStart);
        $em->refresh($userEntity);
        $I->assertEquals($userEntity->getSubscription(), Usr::SUBSCRIPTION_SAVED_CARD);
        $I->assertEquals($userEntity->getSubscriptionType(), Usr::SUBSCRIPTION_TYPE_AWPLUS);
        $I->assertEquals($userEntity->getPlusExpirationDate()->format('Y-m-d'), $plusExpiration->format('Y-m-d'));
        $I->assertNull($userEntity->getAt201ExpirationDate());
        $I->dontSeeInDatabase('GroupUserLink', ['UserID' => $this->userId, 'SiteGroupID' => self::AT201_SITEGROUP_ID]);

        $at201Item = new AT201Subscription6Months();
        $at201Start = new \DateTime();
        $at201Expiration = (clone $at201Start)->add(new \DateInterval(sprintf('P%sM', $at201Item->getMonths())));

        $I->addUserPayment($this->userId, Cart::PAYMENTTYPE_CREDITCARD, $at201Item, null, $at201Start);
        $em->refresh($userEntity);
        $I->assertEquals($userEntity->getSubscription(), Usr::SUBSCRIPTION_SAVED_CARD);
        $I->assertEquals($userEntity->getSubscriptionType(), Usr::SUBSCRIPTION_TYPE_AT201);
        $I->assertEquals($newPlusExpiration->format('Y-m-d'), $userEntity->getPlusExpirationDate()->format('Y-m-d'));
        $I->assertEquals($userEntity->getAt201ExpirationDate()->format('Y-m-d'), $at201Expiration->format('Y-m-d'));

        $I->seeInDatabase('GroupUserLink', ['UserID' => $this->userId, 'SiteGroupID' => self::AT201_SITEGROUP_ID]);
    }

    private function availablePayments(): array
    {
        return [
            ['item' => new AT201Subscription1Month()],
            ['item' => new AT201Subscription6Months()],
            ['item' => new AT201Subscription1Year()],
        ];
    }

    private function expirationsProvider(): array
    {
        return [
            [
                'plusStart' => ($start = date_create('-10 month')),
                'awPlusExpiration' => date_create('@' . $start->getTimestamp())->add(new \DateInterval('P18M')),
            ],
            [
                'plusStart' => ($start = date_create('-1 month')),
                'awPlusExpiration' => date_create('@' . $start->getTimestamp())->add(new \DateInterval('P18M')),
            ],
        ];
    }
}
