<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Command\SendSubscriptionRenewalReminderCommand;
use AwardWallet\MainBundle\Entity\Cart as CartEntity;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\Tests\Modules\DbBuilder\Cart as DbCart;
use AwardWallet\Tests\Modules\DbBuilder\CartItem as DbCartItem;
use AwardWallet\Tests\Modules\DbBuilder\User as DbUser;
use AwardWallet\Tests\Unit\CommandTester;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group frontend-unit
 * @group billing
 */
class SendSubscriptionRenewalReminderCommandTest extends CommandTester
{
    /**
     * @var SendSubscriptionRenewalReminderCommand
     */
    protected $command;

    public function _before()
    {
        parent::_before();
    }

    public function _after()
    {
        $this->cleanCommand();
        parent::_after();
    }

    /**
     * @dataProvider noUsersDataProvider
     */
    public function testNoUsers(DbUser $user)
    {
        $this->dbBuilder->makeUser($user);
        $this->runCommand($user->getId());
        $this->logContains('starting subscription renewal reminder command');
        $this->logContains('processed 0 users');
    }

    public function noUsersDataProvider(): array
    {
        return [
            'free user' => [new DbUser()],
            'awplus with 1 month to expire' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('+1 month')),
                ]),
            ],
            'awplus with 4 days to expire' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('+4 days')),
                ]),
            ],
            'awplus with 2 days to expire' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('+2 days')),
                ]),
            ],
            'awplus with 1 day to expire' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('+1 day')),
                ]),
            ],
            'awplus with expired subscription' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                ]),
            ],
            'at201 with 1 month to expire' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('+1 month')),
                ]),
            ],
            'at201 with 4 days to expire' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('+4 days')),
                ]),
            ],
            'at201 with 2 days to expire' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('+2 days')),
                ]),
            ],
            'at201 with 1 day to expire' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('+1 day')),
                ]),
            ],
            'at201 with expired subscription' => [
                new DbUser(null, false, [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'PlusExpirationDate' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                ]),
            ],
        ];
    }

    public function testEmailAwPlusUser()
    {
        $this->assertEmailSent($this->makeUser(true, false));
    }

    public function testEmailAwPlusUserWithPaypal()
    {
        $this->assertEmailSent($this->makeUser(true, true));
    }

    public function testEmailAt201User()
    {
        $this->assertEmailSent($this->makeUser(false, false, SubscriptionPeriod::DAYS_6_MONTHS));
    }

    public function testEmailAt201UserWithPaypal()
    {
        $this->assertEmailSent($this->makeUser(false, true, SubscriptionPeriod::DAYS_1_YEAR));
    }

    public function testEmailAwPlusViaApple()
    {
        $this->assertEmailSent($this->makeUser(true, false, SubscriptionPeriod::DAYS_1_YEAR, CartEntity::PAYMENTTYPE_APPSTORE));
    }

    protected function runCommand(int $userId): void
    {
        /** @var Mailer $mailer */
        $mailer = $this->container->get(Mailer::class);
        /** @var LoggerInterface $logger */
        $logger = $this->container->get($this->loggerService);

        $this->initCommand(
            new SendSubscriptionRenewalReminderCommand(
                $this->em->getConnection(),
                $this->em,
                $this->em->getRepository(Usr::class),
                $mailer,
                $logger,
                $this->container->get(TranslatorInterface::class),
                $this->container->get(LocalizeService::class),
            )
        );

        $params = ['--userId' => [$userId]];
        $this->clearLogs();
        $this->executeCommand($params);
    }

    private function assertEmailSent(int $userId): void
    {
        $this->runCommand($userId);
        $this->logContains('starting subscription renewal reminder command');
        $this->logContains('processed 1 users');
        $this->logContains('processing user ' . $userId);
    }

    private function makeUser(
        bool $isAwPlus = true,
        bool $isPaypal = false,
        int $subscriptionPeriod = SubscriptionPeriod::DAYS_1_YEAR,
        ?int $paymentType = null,
        string $expirationDate = '+3 days'
    ): int {
        $expirationDate = date('Y-m-d H:i:s', strtotime($expirationDate));

        if ($isAwPlus) {
            $fields = ['PlusExpirationDate' => $expirationDate];
        } else {
            $fields = [
                'PlusExpirationDate' => $expirationDate,
                'AT201ExpirationDate' => $expirationDate,
            ];
        }

        if ($isPaypal) {
            $fields['NextBillingDate'] = $expirationDate;
        }

        $user = new DbUser(null, false, array_merge([
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'SubscriptionType' => $isAwPlus ? Usr::SUBSCRIPTION_TYPE_AWPLUS : Usr::SUBSCRIPTION_TYPE_AT201,
            'Subscription' => $isPaypal ? Usr::SUBSCRIPTION_PAYPAL : Usr::SUBSCRIPTION_STRIPE,
            'SubscriptionPeriod' => $subscriptionPeriod,
            'SubscriptionPrice' => SubscriptionPrice::getPrice(
                $isAwPlus ? Usr::SUBSCRIPTION_TYPE_AWPLUS : Usr::SUBSCRIPTION_TYPE_AT201,
                SubscriptionPeriod::DAYS_TO_DURATION[$subscriptionPeriod]
            ),
        ], $fields));

        $payDate = date_create(str_replace('+', '-', SubscriptionPeriod::DAYS_TO_DURATION[$subscriptionPeriod]));
        $payDate->format('+3 day');

        switch ($subscriptionPeriod) {
            case SubscriptionPeriod::DAYS_1_YEAR:
                $lastCartItem = $isAwPlus ? DbCartItem::awPlusSubscription1Year() : DbCartItem::at201Subscription1Year();

                break;

            case SubscriptionPeriod::DAYS_6_MONTHS:
                $lastCartItem = $isAwPlus ? DbCartItem::awPlusSubscription6Months() : DbCartItem::at201Subscription6Months();

                break;
        }

        if (is_null($paymentType)) {
            $paymentType = $isPaypal ? CartEntity::PAYMENTTYPE_PAYPAL : CartEntity::PAYMENTTYPE_STRIPE_INTENT;
        }

        $cartFields = [];

        if ($paymentType === CartEntity::PAYMENTTYPE_STRIPE_INTENT) {
            $cartFields['CreditCardType'] = 'Visa';
            $cartFields['CreditCardNumber'] = 'XXXXXXXXXXXX1234';
        }

        $user->addCart(
            new DbCart(
                [$lastCartItem],
                array_merge([
                    'PayDate' => $payDate->format('Y-m-d H:i:s'),
                    'PaymentType' => $paymentType,
                ], $cartFields)
            )
        );
        $this->dbBuilder->makeUser($user);
        $this->db->updateInDatabase('Usr', [
            'LastSubscriptionCartItemID' => $lastCartItem->getId(),
        ]);

        return $user->getId();
    }
}
