<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\User;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\GooglePlayCartPaidListener as GooglePlay;
use AwardWallet\MainBundle\Service\Billing\PaypalCartPaidListener as PayPal;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as IntervalFormatter;
use AwardWallet\MainBundle\Service\User\SubscriptionInfoHelper;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group frontend-unit
 */
class SubscriptionInfoHelperTest extends BaseContainerTest
{
    private const STATUS_FREE = 1;
    private const STATUS_AWPLUS_TRIAL = 2;
    private const STATUS_AWPLUS = 3;
    private const STATUS_AWPLUS_EXPIRES_TODAY = 4;
    private const STATUS_AWPLUS_EXPIRED = 5;
    private const STATUS_AWPLUS_AT201 = 6;

    private const ACCOUNT_EMAIL = 'yamadatarou@email.com';
    private const SUBSCRIPTION_PRICE = 49.99;
    private const EXPIRATION_DATE_FORMAT = 'n/j/y';

    private ?SubscriptionInfoHelper $helper;
    private ?TranslatorInterface $translator;
    private ?LocalizeService $localizer;
    private ?IntervalFormatter $intervalFormatter;

    public function _before()
    {
        parent::_before();
        $this->translator = $this->container->get('translator');
        $this->localizer = $this->container->get(LocalizeService::class);
        $this->intervalFormatter = $this->container->get(IntervalFormatter::class);
    }

    public function _after()
    {
        $this->helper = null;
        $this->translator = null;
        $this->localizer = null;
        $this->intervalFormatter = null;
        parent::_after();
    }

    /**
     * @dataProvider userProvider
     */
    public function testGetUserSubscriptionInfo(array $userFields, ?\DateTimeImmutable $payDate, ?string $expirationDate, ?string $expirationDateAt201)
    {
        $payDate = ($payDate !== null) ? \DateTime::createFromImmutable($payDate) : null;
        $userId = $this->prepare($userFields, $payDate);
        $user = $this->em->getRepository(Usr::class)->find($userId);

        $this->createHelper($user);
        $this->assertEquals($this->getValidSubscriptionText($expirationDate), $this->helper->getExpirationDate());
        $this->assertEquals($this->getValidSubscriptionText($expirationDateAt201), $this->helper->getExpirationDateAt201());
    }

    /**
     * @dataProvider paymentTypeProvider
     */
    public function testGetUserPaymentType(array $userFields, ?\DateTimeImmutable $payDate, ?int $paymentType, ?array $cardOptions, string $paymentTypeText)
    {
        $this->mockService(PayPal::class, $this->makeEmpty(PayPal::class, [
            'onCartMarkPaid' => function ($event) {
                return;
            },
        ]));
        $this->mockService(GooglePlay::class, $this->makeEmpty(GooglePlay::class, [
            'onCartMarkPaid' => function ($event) {
                return;
            },
        ]));

        $payDate = ($payDate !== null) ? \DateTime::createFromImmutable($payDate) : null;
        $userId = $this->prepare($userFields, $payDate, $paymentType, $cardOptions);
        $user = $this->em->getRepository(Usr::class)->find($userId);

        $this->createHelper($user);
        $this->assertEquals($paymentTypeText, $this->helper->getPaymentType());
    }

    public function userProvider()
    {
        [$payDates, $expirationDates] = self::getExpirationDatesArray();

        return [
            'free' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_FREE,
                    'Subscription' => null,
                    'SubscriptionType' => null,
                    'PlusExpirationDate' => $expirationDates[self::STATUS_FREE],
                ],
                $payDates[self::STATUS_FREE],
                $expirationDates[self::STATUS_FREE],
                null,
            ],
            'awplus trial' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => null,
                    'SubscriptionType' => null,
                    'PlusExpirationDate' => $expirationDates[self::STATUS_AWPLUS_TRIAL],
                ],
                $payDates[self::STATUS_AWPLUS_TRIAL],
                $expirationDates[self::STATUS_AWPLUS_TRIAL],
                null,
            ],
            'awplus' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'PlusExpirationDate' => $expirationDates[self::STATUS_AWPLUS],
                ],
                $payDates[self::STATUS_AWPLUS],
                $expirationDates[self::STATUS_AWPLUS],
                null,
            ],
            'awplus expires today' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'PlusExpirationDate' => $expirationDates[self::STATUS_AWPLUS_EXPIRES_TODAY],
                ],
                $payDates[self::STATUS_AWPLUS_EXPIRES_TODAY],
                'Your subscription is expiring today.',
                null,
            ],
            'awplus expired' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'PlusExpirationDate' => $expirationDates[self::STATUS_AWPLUS_EXPIRED],
                ],
                $payDates[self::STATUS_AWPLUS_EXPIRED],
                'Expired yesterday',
                null,
            ],
            'awplus at201' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                    'PlusExpirationDate' => $expirationDates[self::STATUS_AWPLUS_AT201],
                    'AT201ExpirationDate' => $expirationDates[self::STATUS_AWPLUS_AT201],
                ],
                $payDates[self::STATUS_AWPLUS_AT201],
                $expirationDates[self::STATUS_AWPLUS_AT201],
                $expirationDates[self::STATUS_AWPLUS_AT201],
            ],
            'awplus at201 canceled' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => null,
                    'SubscriptionType' => null,
                    'PlusExpirationDate' => $expirationDates[self::STATUS_AWPLUS_AT201],
                    'AT201ExpirationDate' => $expirationDates[self::STATUS_AWPLUS_AT201],
                ],
                $payDates[self::STATUS_AWPLUS_AT201],
                $expirationDates[self::STATUS_AWPLUS_AT201],
                $expirationDates[self::STATUS_AWPLUS_AT201],
            ],
        ];
    }

    public function paymentTypeProvider()
    {
        $payDate = new \DateTimeImmutable();
        $expirationDate = $payDate->add(new \DateInterval('P1Y'));
        $expirationDateTrial = $payDate->add(new \DateInterval('P3M'));

        return [
            'credit card specific' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'PlusExpirationDate' => $expirationDate->format('Y-m-d H:i:s'),
                    'NextBillingDate' => $expirationDate->format('Y-m-d H:i:s'),
                ],
                $payDate,
                Cart::PAYMENTTYPE_CREDITCARD,
                [
                    'creditcardtype' => 'MasterCard',
                    'creditcardnumber' => 'XXXXXXXXXXXX1234',
                ],
                sprintf('Your card ending in *1234 will be charged $%0.2f on %s to renew AwardWallet Plus for one more year.', self::SUBSCRIPTION_PRICE, $expirationDate->format(self::EXPIRATION_DATE_FORMAT)),
            ],
            'credit card generic' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'PlusExpirationDate' => $expirationDate->format('Y-m-d H:i:s'),
                    'NextBillingDate' => $expirationDate->format('Y-m-d H:i:s'),
                ],
                $payDate,
                Cart::PAYMENTTYPE_CREDITCARD,
                null,
                sprintf('Your credit card will be charged $%0.2f on %s to renew AwardWallet Plus for one more year.', self::SUBSCRIPTION_PRICE, $expirationDate->format(self::EXPIRATION_DATE_FORMAT)),
            ],
            'paypal' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'PayPalRecurringProfileID' => 'pm_1QWm000000000000000000',
                    'PlusExpirationDate' => $expirationDate->format('Y-m-d H:i:s'),
                    'NextBillingDate' => $expirationDate->format('Y-m-d H:i:s'),
                ],
                $payDate,
                Cart::PAYMENTTYPE_PAYPAL,
                null,
                sprintf('Your PayPal account (%s) will be charged $%0.2f on %s to renew AwardWallet Plus for one more year.', self::ACCOUNT_EMAIL, self::SUBSCRIPTION_PRICE, $expirationDate->format(self::EXPIRATION_DATE_FORMAT)),
            ],
            'apple pay' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_MOBILE,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'PlusExpirationDate' => $expirationDate->format('Y-m-d H:i:s'),
                    'NextBillingDate' => $expirationDate->format('Y-m-d H:i:s'),
                ],
                $payDate,
                Cart::PAYMENTTYPE_APPSTORE,
                null,
                sprintf('Your Apple Pay account (%s) will be charged $%0.2f on %s via Apple to renew AwardWallet Plus for one more year.', self::ACCOUNT_EMAIL, self::SUBSCRIPTION_PRICE, $expirationDate->format(self::EXPIRATION_DATE_FORMAT)),
            ],
            'google play' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => Usr::SUBSCRIPTION_MOBILE,
                    'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                    'PlusExpirationDate' => $expirationDate->format('Y-m-d H:i:s'),
                    'NextBillingDate' => $expirationDate->format('Y-m-d H:i:s'),
                ],
                $payDate,
                Cart::PAYMENTTYPE_ANDROIDMARKET,
                null,
                sprintf('Your Google Play account (%s) will be charged $%0.2f on %s via Google to renew AwardWallet Plus for one more year.', self::ACCOUNT_EMAIL, self::SUBSCRIPTION_PRICE, $expirationDate->format(self::EXPIRATION_DATE_FORMAT)),
            ],
            'no subscription' => [
                [
                    'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                    'Subscription' => null,
                    'SubscriptionType' => null,
                    'PlusExpirationDate' => $expirationDateTrial->format('Y-m-d H:i:s'),
                ],
                $payDate,
                null,
                null,
                sprintf('Currently, you don\'t have an active subscription. On %s, your account will be downgraded to AwardWallet Free. <a target="_blank" href="/user/pay">Set up a subscription now to be charged on %s automatically.</a>', $expirationDateTrial->format(self::EXPIRATION_DATE_FORMAT), $expirationDateTrial->format(self::EXPIRATION_DATE_FORMAT)),
            ],
        ];
    }

    private static function getExpirationDatesArray(): array
    {
        $today = new \DateTimeImmutable();
        $payDates = [
            self::STATUS_FREE => null,
            self::STATUS_AWPLUS_TRIAL => $today->sub(new \DateInterval('P2M')),
            self::STATUS_AWPLUS => $today,
            self::STATUS_AWPLUS_EXPIRES_TODAY => $today->sub(new \DateInterval('P1Y'))->add(new \DateInterval('PT1H')),
            self::STATUS_AWPLUS_EXPIRED => $today->sub(new \DateInterval('P1Y1DT1H')),
            self::STATUS_AWPLUS_AT201 => $today,
        ];
        $expirationDates = [];

        foreach ($payDates as $status => $payDate) {
            /** @var \DateTimeImmutable|null $payDate */
            switch ($status) {
                case self::STATUS_FREE:
                    $expirationDate = null;

                    break;

                case self::STATUS_AWPLUS_TRIAL:
                    $expirationDate = $payDate->add(new \DateInterval('P3M'));

                    break;

                default:
                    $expirationDate = $payDate->add(new \DateInterval('P1Y'));

                    break;
            }

            $expirationDates[$status] = ($expirationDate !== null) ? $expirationDate->format('Y-m-d H:i:s') : null;
        }

        return [$payDates, $expirationDates];
    }

    /**
     * Creates an instance of the `SubscriptionInfoHelper` class.
     *
     * @param Usr $user the user for whom the subscription information is checked
     */
    private function createHelper(Usr $user): void
    {
        $this->helper = new SubscriptionInfoHelper(
            $this->makeEmpty(AwTokenStorageInterface::class, ['getUser' => $user]),
            $this->translator,
            $this->localizer,
            $this->intervalFormatter,
            $this->container->get('router')
        );
    }

    /**
     * Creates a new account.
     *
     * @param array $userFields an array of parameters to be filled in at creation
     * @param \DateTime|null $payDate date of payment
     * @param int|null $paymentType subscription payment method
     * @param array|null $cardOptions additional credit card options
     * @return int user ID
     */
    private function prepare(array $userFields, ?\DateTime $payDate, ?int $paymentType = null, ?array $cardOptions = null): int
    {
        $userId = $this->aw->createAwUser(null, null, $userFields);

        if ($userFields['AccountLevel'] === ACCOUNT_LEVEL_AWPLUS) {
            $payment = $this->createPayment($userId, $payDate, $userFields, $paymentType);

            if (in_array($paymentType, [Cart::PAYMENTTYPE_CREDITCARD, Cart::PAYMENTTYPE_STRIPE_INTENT])) {
                if ($cardOptions !== null) {
                    $payment->setCreditcardtype($cardOptions['creditcardtype']);
                    $payment->setCreditcardnumber($cardOptions['creditcardnumber']);
                } else {
                    $payment->setCreditcardtype(null);
                    $payment->setCreditcardnumber(null);
                }
            } elseif (in_array($paymentType, [Cart::PAYMENTTYPE_PAYPAL, Cart::PAYMENTTYPE_APPSTORE, Cart::PAYMENTTYPE_ANDROIDMARKET])) {
                $payment->setEmail(self::ACCOUNT_EMAIL);
            }

            $this->em->flush();
        }

        // Canceled subscription "AwardTravel 201"
        if ($userFields['AccountLevel'] === ACCOUNT_LEVEL_AWPLUS && $userFields['Subscription'] === null && isset($userFields['AT201ExpirationDate'])) {
            $this->db->executeQuery("UPDATE `Usr` SET `Subscription` = NULL WHERE `UserID` = {$userId}");
        }

        return $userId;
    }

    /**
     * Creates a new payment.
     *
     * @param int $userId user ID
     * @param \DateTime|null $payDate date of payment
     * @param array $userFields list of account parameters
     * @param int|null $paymentType subscription payment method
     */
    private function createPayment(int $userId, ?\DateTime $payDate, array $userFields, ?int $paymentType): Cart
    {
        if ($paymentType === null) {
            $paymentType = Cart::PAYMENTTYPE_STRIPE_INTENT;
        }

        switch ($userFields['SubscriptionType']) {
            case Usr::SUBSCRIPTION_TYPE_AWPLUS:
                $cartItem = new AwPlusSubscription();

                break;

            case Usr::SUBSCRIPTION_TYPE_AT201:
                $cartItem = new AT201Subscription1Year();

                break;

            default:
                $cartItem = !isset($userFields['AT201ExpirationDate']) ? new AwPlusTrial() : new AT201Subscription1Year();

                break;
        }

        return $this->aw->addUserPayment($userId, $paymentType, $cartItem, [], $payDate);
    }

    private function getValidSubscriptionText(?string $date): ?string
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date);

        if (!$dateTime) {
            return $date;
        }

        return $this->translator->trans('account_type.awplus.subscription_valid', [
            '%expiration_date%' => $this->localizer->formatDate($dateTime, 'short'),
            '%expiration_verbal%' => $this->intervalFormatter->formatDuration(new \DateTime(), $dateTime, true, false, true),
        ]);
    }
}
