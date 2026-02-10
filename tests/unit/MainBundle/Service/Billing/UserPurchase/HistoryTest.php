<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Billing\UserPurchase;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Billing\UserPurchase\History;
use AwardWallet\Tests\Modules\DbBuilder\Cart;
use AwardWallet\Tests\Modules\DbBuilder\CartItem;
use AwardWallet\Tests\Modules\DbBuilder\Coupon;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class HistoryTest extends BaseUserTest
{
    private const AW_PLUS = 1;
    private const AT201 = 2;

    public function testCouponWithoutDiscountItem()
    {
        $couponCode = 'cc' . StringUtils::getRandomCode(20);
        $couponId = $this->dbBuilder->makeCoupon(
            new Coupon($couponCode, 'View From The Wing', 100, [
                'StartDate' => '2011-10-30',
                'EndDate' => date('Y-m-d', strtotime('+10 year')),
                'MaxUses' => 1000000,
                'FirstTimeOnly' => 1,
            ])
        );
        $payDate = new \DateTime('2018-07-11 21:55:05');
        $user = $this->getUser(
            (new User())
                ->setCarts([
                    Cart::paid($payDate, [
                        'CouponID' => $couponId,
                        'LastUsedDate' => $payDate->format('Y-m-d H:i:s'),
                        'FirstName' => 'Barry',
                        'LastName' => 'Inciong',
                        'Email' => 'smith@yahoo.com',
                        'CouponName' => 'View From The Wing',
                        'CouponCode' => $couponCode,
                    ])->setCartItems([
                        CartItem::awPlus6Months([
                            'Discount' => 100,
                        ]),
                    ]),
                ])
        );

        $history = new History($user);
        $awPlusInfo = $history->getAwPlusInfo();

        $this->assertNull($awPlusInfo->getCurrentPeriod());
        $this->assertNull($awPlusInfo->getCurrentExpirationDate());
        $this->assertNull($awPlusInfo->getSubscriptionPaymentCheckDate());
        $this->assertCount(0, $awPlusInfo->getCurrentAndNextPeriods());
        $this->assertNotNull($last = $awPlusInfo->getLastPeriod());
        $this->assertNotNull($lastCart = $last->getCart());
        $this->assertCount(1, $awPlusInfo->getCarts());
        $this->assertEquals(0, $lastCart->getTotalPrice());
    }

    public function testCouponWithDiscountItem()
    {
        $couponCode = 'cc' . StringUtils::getRandomCode(20);
        $couponId = $this->dbBuilder->makeCoupon(
            new Coupon($couponCode, 'View From The Wing', 100, [
                'StartDate' => '2011-10-30',
                'EndDate' => date('Y-m-d', strtotime('+10 year')),
                'MaxUses' => 1000000,
                'FirstTimeOnly' => 1,
            ])
        );
        $payDate = new \DateTime('2018-07-11 21:55:05');
        $user = $this->getUser(
            (new User())
                ->setCarts([
                    Cart::paid($payDate, [
                        'CouponID' => $couponId,
                        'LastUsedDate' => $payDate->format('Y-m-d H:i:s'),
                        'FirstName' => 'Barry',
                        'LastName' => 'Inciong',
                        'Email' => 'smith@yahoo.com',
                        'CouponName' => 'View From The Wing',
                        'CouponCode' => $couponCode,
                    ])->setCartItems([
                        CartItem::awPlus6Months([
                            'Discount' => 0,
                        ]),
                        CartItem::discount(-AwPlus::PRICE),
                    ]),
                ])
        );

        $history = new History($user);
        $awPlusInfo = $history->getAwPlusInfo();

        $this->assertNull($awPlusInfo->getCurrentPeriod());
        $this->assertNull($awPlusInfo->getCurrentExpirationDate());
        $this->assertNull($awPlusInfo->getSubscriptionPaymentCheckDate());
        $this->assertCount(0, $awPlusInfo->getCurrentAndNextPeriods());
        $this->assertNotNull($last = $awPlusInfo->getLastPeriod());
        $this->assertNotNull($lastCart = $last->getCart());
        $this->assertCount(1, $awPlusInfo->getCarts());
        $this->assertEquals(0, $lastCart->getTotalPrice());
    }

    public function testMobileSubscriptionWith201Subscription()
    {
        $mobileSubscriptionStartDate = new \DateTime('-4 month');
        $mobileSubscriptionEndDate = clone $mobileSubscriptionStartDate;
        $mobileSubscriptionEndDate = $mobileSubscriptionEndDate->modify('+1 year');

        $user = $this->getUser(
            (new User())
                ->setCarts([
                    $firstCart = Cart::paidAppStore($mobileSubscriptionStartDate)
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                ])
        );

        $history = new History($user);
        $awPlusInfo = $history->getAwPlusInfo();

        $this->assertNotNull($current = $awPlusInfo->getCurrentPeriod());
        $this->assertEquals($firstCart->getId(), $current->getCart()->getCartid());
        $this->assertEquals($current->getEndDate()->format('Y-m-d'), $mobileSubscriptionEndDate->format('Y-m-d'));
        $this->assertNotNull($expirationDate = $awPlusInfo->getCurrentExpirationDate());
        $this->assertEquals($mobileSubscriptionEndDate->format('Y-m-d'), $expirationDate->format('Y-m-d'));
        $this->assertNotNull($paymentCheckDate = $awPlusInfo->getSubscriptionPaymentCheckDate());
        $this->assertEquals($mobileSubscriptionEndDate->format('Y-m-d'), $paymentCheckDate->format('Y-m-d'));
        $this->assertCount(2, $awPlusInfo->getCurrentAndNextPeriods());

        // add AT201 subscription
        $at201SubscriptionStartDate = new \DateTime();
        $at201SubscriptionEndDate = (clone $at201SubscriptionStartDate)->modify('+1 year');
        // not "+2 year" because it calculates how many seconds are left from the first subscription
        // related to the leap year
        $totalExpirationDate = (clone $at201SubscriptionEndDate)->modify(sprintf('+%d seconds', $mobileSubscriptionEndDate->getTimestamp() - $at201SubscriptionStartDate->getTimestamp()));
        $this->dbBuilder->makeCart(
            $secondCart = Cart::paidStripe($at201SubscriptionStartDate, [
                'UserID' => $user->getId(),
            ])
                ->setCartItems([
                    CartItem::at201Subscription1Year(),
                ])
        );
        $this->em->refresh($user);

        $history = new History($user);
        $awPlusInfo = $history->getAwPlusInfo();

        $this->assertCount(2, $awPlusInfo->getCarts());
        $this->assertNotNull($current = $awPlusInfo->getCurrentPeriod());
        $this->assertEquals($secondCart->getId(), $current->getCart()->getCartid());
        $this->assertEquals($current->getEndDate()->format('Y-m-d'), $at201SubscriptionEndDate->format('Y-m-d'));
        $this->assertNotNull($expirationDate = $awPlusInfo->getCurrentExpirationDate());
        $this->assertEquals($totalExpirationDate->format('Y-m-d'), $expirationDate->format('Y-m-d'));
        $this->assertNotNull($paymentCheckDate = $awPlusInfo->getSubscriptionPaymentCheckDate());
        $this->assertEquals($at201SubscriptionEndDate->format('Y-m-d'), $paymentCheckDate->format('Y-m-d'));
        $this->assertCount(2, $awPlusInfo->getCurrentAndNextPeriods());
        $this->assertEquals($firstCart->getId(), $awPlusInfo->getLastPeriod()->getCart()->getCartid());
        $this->assertEquals($totalExpirationDate->format('Y-m-d'), $awPlusInfo->getLastPeriod()->getEndDate()->format('Y-m-d'));
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(
        array $carts,
        ?\DateTime $expirationDate,
        ?\DateTime $paymentCheckDate,
        array $currentPeriods,
        ?\DateTime $now = null,
        int $type = self::AW_PLUS
    ) {
        $now = $now ?? new \DateTime();
        $user = $this->getUser(
            (new User())
                ->setCarts($carts)
        );
        $history = new History($user);

        if ($type === static::AW_PLUS) {
            $info = $history->getAwPlusInfo();
        } else {
            $info = $history->getAT201Info();
        }

        $this->assertEquals($expirationDate, $info->getCurrentExpirationDate($now->getTimestamp()), 'expiration date is not equal');
        $this->assertEquals($paymentCheckDate, $info->getSubscriptionPaymentCheckDate($now->getTimestamp()), 'payment check date is not equal');
        $current = $info->getCurrentPeriod($now->getTimestamp());
        $periods = $info->getCurrentAndNextPeriods($now->getTimestamp());

        if (empty($currentPeriods)) {
            $this->assertTrue(is_null($current), 'current period is not null');
            $this->assertCount(0, $periods, 'count of periods is not equal');
        } else {
            $this->assertFalse(is_null($current), 'current period is null');
            $this->assertEquals(count($currentPeriods), count($periods), 'count of periods is not equal');

            foreach ($currentPeriods as $i => $currentPeriod) {
                /** @var Cart $cart */
                [$start, $end, $cart, $active] = $currentPeriod;
                $start = new \DateTime($start);
                $end = new \DateTime($end);
                $actual = $periods[$i];

                $this->assertEquals($start, $actual->getStartDate(), 'start date is not equal');
                $this->assertEquals($end, $actual->getEndDate(), 'end date is not equal');

                if (is_null($cart)) {
                    $this->assertTrue(is_null($actual->getCart()));
                } else {
                    $this->assertEquals($cart->getId(), $actual->getCart()->getCartid(), 'cart is not equal');
                }

                $this->assertEquals($active, $actual->isActive());
            }
        }
    }

    public function dataProvider(): array
    {
        return [
            'no carts' => self::case([], null, null, []),
            'aw plus 6 months' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlus6Months(),
                        ]),
                ],
                '2023-11-01',
                null,
                [
                    ['2023-05-01', '2023-11-01', $cart, true],
                ],
                '2023-07-01'
            ),
            'aw plus 6 months, 2' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlus6Months(),
                        ]),
                ],
                '2023-11-01',
                null,
                [
                    ['2023-05-01', '2023-11-01', $cart, true],
                ],
                '2023-10-31'
            ),
            'aw plus 6 months, 3' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlus6Months(),
                        ]),
                ],
                null,
                null,
                [],
                '2023-11-01'
            ),
            'aw plus 6 months, 4' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlus6Months(),
                        ]),
                ],
                null,
                null,
                [],
                '2024-02-01'
            ),
            'aw plus 1 year' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                ],
                '2024-05-01',
                '2024-05-01',
                [
                    ['2023-05-01', '2024-05-01', $cart, true],
                    ['2024-05-01', '2024-05-08', null, true],
                ],
                '2024-02-01'
            ),
            'aw plus 1 year, grace period' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                ],
                '2024-05-08',
                null,
                [
                    ['2024-05-01', '2024-05-08', null, true],
                ],
                '2024-05-03'
            ),
            'aw plus, purchase + subscription' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlus6Months(),
                        ]),
                    $cart2 = Cart::paidStripe(new \DateTime('2023-07-01'))
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                ],
                '2024-11-01',
                '2024-07-01',
                [
                    ['2023-07-01', '2024-07-01', $cart2, true],
                    ['2024-07-01', '2024-11-01', $cart, false],
                ],
                '2023-07-02'
            ),
            'aw plus, purchase + subscription, 2' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlus6Months(),
                        ]),
                    $cart2 = Cart::paidStripe(new \DateTime('2023-07-01'))
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                ],
                '2024-11-01',
                null,
                [
                    ['2024-07-01', '2024-11-01', $cart, false],
                ],
                '2024-10-15'
            ),
            'at201' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::at201Subscription1Year(),
                        ]),
                ],
                '2024-05-01',
                '2024-05-01',
                [
                    ['2023-05-01', '2024-05-01', $cart, true],
                    ['2024-05-01', '2024-05-08', null, true],
                ],
                '2024-02-01',
                self::AT201
            ),
            'at201, plus' => self::case(
                [
                    $cart = Cart::paidStripe(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::at201Subscription1Year(),
                        ]),
                ],
                '2024-05-01',
                '2024-05-01',
                [
                    ['2023-05-01', '2024-05-01', $cart, true],
                    ['2024-05-01', '2024-05-08', null, true],
                ],
                '2024-02-01'
            ),
            'aw plus, mobile subscription, purchase and at201' => self::case(
                [
                    $cart = Cart::paidAppStore(new \DateTime('2023-05-01'))
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                    $cart2 = Cart::paidStripe(new \DateTime('2023-07-01'))
                        ->setCartItems([
                            CartItem::at201Subscription1Year(),
                        ]),
                    $cart3 = Cart::paidStripe(new \DateTime('2023-09-01'))
                        ->setCartItems([
                            CartItem::awPlus6Months(),
                        ]),
                ],
                '2025-10-31',
                null,
                [
                    ['2023-09-01', '2024-03-01', $cart3, true],
                    ['2024-03-01', '2024-12-31', $cart, false],
                    ['2024-12-31', '2025-10-31', $cart2, false],
                ],
                '2023-09-01'
            ),
            'change start date mobile subscription' => self::case(
                [
                    $cart = Cart::paidAppStore(new \DateTime('2021-05-01'))
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                    $cart2 = Cart::paidAppStore(new \DateTime('2022-05-01'))
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                    $cart3 = Cart::paidAppStore(new \DateTime('2023-05-07'))
                        ->setCartItems([
                            CartItem::awPlusSubscription1Year(),
                        ]),
                ],
                '2024-05-07',
                '2024-05-07',
                [
                    ['2023-05-07', '2024-05-07', $cart3, true],
                    ['2024-05-07', '2024-05-14', null, true],
                ],
                '2024-01-01'
            ),
        ];
    }

    private function getUser(User $user): Usr
    {
        return $this->em->find(Usr::class, $this->dbBuilder->makeUser($user));
    }

    private static function case(
        array $carts,
        ?string $expirationDate,
        ?string $paymentCheckDate,
        array $currentPeriods,
        ?string $now = null,
        int $type = self::AW_PLUS
    ): array {
        return [
            $carts,
            is_null($expirationDate) ? null : new \DateTime($expirationDate),
            is_null($paymentCheckDate) ? null : new \DateTime($paymentCheckDate),
            $currentPeriods,
            is_null($now) ? null : new \DateTime($now),
            $type,
        ];
    }
}
