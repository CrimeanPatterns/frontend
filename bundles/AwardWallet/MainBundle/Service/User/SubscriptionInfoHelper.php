<?php

namespace AwardWallet\MainBundle\Service\User;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubscriptionInfoHelper implements TranslationContainerInterface
{
    private AwTokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;
    private LocalizeService $localizer;
    private DateTimeIntervalFormatter $intervalFormatter;
    private RouterInterface $router;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        LocalizeService $localizer,
        DateTimeIntervalFormatter $intervalFormatter,
        RouterInterface $router
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->localizer = $localizer;
        $this->intervalFormatter = $intervalFormatter;
        $this->router = $router;
    }

    /**
     * Get the expiration date of the AwPlus subscription.
     *
     * @param Usr|null $user user entity class. If the user is not passed, the current user is used
     * @param bool $isMobile flag specifying that messages for the mobile app are needed
     */
    public function getExpirationDate(?Usr $user = null, bool $isMobile = false): ?string
    {
        $user = $user ?? $this->tokenStorage->getUser();
        $expirationDate = $user->getPlusExpirationDate();

        if ($user->getAccountlevel() === ACCOUNT_LEVEL_FREE || $expirationDate === null) {
            return null;
        }

        $expirationDate->setTime(0, 0);
        $today = (new \DateTime())->setTime(0, 0);
        $difference = $today->diff($expirationDate);
        $parameters = [
            '%expiration_verbal%' => $this->intervalFormatter->formatDuration(new \DateTime(), $expirationDate, true, false, true),
        ];

        if ($difference->invert === 0 && $difference->days >= 2) {
            if (!$isMobile) {
                $parameters['%expiration_date%'] = $this->localizer->formatDate($expirationDate, 'short', $user->getLocale());
            }

            $translation = !$isMobile ?
                $this->translator->trans(/** @Desc("Your subscription is valid for the next %expiration_verbal%, until %expiration_date%.") */ 'account_type.awplus.subscription_valid', $parameters) :
                $this->translator->trans(/** @Desc("Expires in %expiration_verbal%") */ 'account_type.awplus.expires.short', $parameters);
        } elseif ($difference->invert === 0 && $difference->days < 2) {
            $translation = !$isMobile ?
                $this->translator->trans(/** @Desc("Your subscription is expiring %expiration_verbal%.") */ 'account_type.awplus.expires_soon', $parameters) :
                $this->translator->trans(/** @Desc("Expires %expiration_verbal%") */ 'expires.relative_date.from_today', $parameters);
        } elseif ($difference->invert === 1 && $difference->days < 2) {
            $translation = $this->translator->trans(/** @Desc("Expired %expiration_verbal%") */ 'expired.relative_date.from_today', $parameters);
        } else {
            $translation = $this->translator->trans(/** @Desc("Expired %expiration_verbal% ago") */ 'expired.relative_date.days', $parameters);
        }

        return $translation;
    }

    /**
     * Get the expiration date of the AT201 subscription.
     */
    public function getExpirationDateAt201(): ?string
    {
        $user = $this->tokenStorage->getUser();

        if ($user->getAccountlevel() === ACCOUNT_LEVEL_AWPLUS && $user->hasAt201Access()) {
            $expirationDate = $user->getAt201ExpirationDate();

            return $this->translator->trans(/** @Desc("Your subscription is valid for the next %expiration_verbal%, until %expiration_date%.") */ 'account_type.awplus.subscription_valid', [
                '%expiration_date%' => $this->localizer->formatDate($expirationDate, 'short', $user->getLocale()),
                '%expiration_verbal%' => $this->intervalFormatter->formatDuration(new \DateTime(), $expirationDate, true, false, true),
            ]);
        }

        return null;
    }

    /**
     * Get the description for the current payment method.
     *
     * @throws \Exception if the subscription period is not specified in the period array
     */
    public function getPaymentType(): ?string
    {
        $user = $this->tokenStorage->getUser();

        if ($user->getAccountlevel() === ACCOUNT_LEVEL_FREE) {
            return null;
        }

        if ($user->hasAnyActiveSubscription()) {
            $lastPayment = $user->getActiveSubscriptionCart();

            if ($lastPayment === null || $lastPayment->getPaymenttype() === null) {
                return null;
            }

            // Checking for an At201 subscription
            if ($user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AT201 && $user->hasAt201Access()) {
                $expirationDate = $user->getAt201ExpirationDate();
                $subscriptionType = $this->translator->trans(/** @Desc("AwardTravel 201") */ 'subscription.awardtravel-201');
            } else {
                $expirationDate = $user->getPlusExpirationDate();
                $subscriptionType = $this->translator->trans(/** @Desc("AwardWallet Plus") */ 'account_type.awplus');
            }

            if ($user->getSubscription() === Usr::SUBSCRIPTION_PAYPAL) {
                $expirationDate = $user->getNextBillingDate();
            }

            if (!isset(self::getPeriodsArray()[$user->getSubscriptionPeriod()])) {
                throw new \Exception('Unknown subscription period');
            }

            $params = [
                '%price%' => $this->localizer->formatCurrency($user->getSubscriptionPrice(), 'USD', false, $user->getLocale()),
                '%expiration_date%' => $this->localizer->formatDate($expirationDate, 'short', $user->getLocale()),
                '%subscription_type%' => $subscriptionType,
                '%subscription_period%' => $this->translator->trans(self::getPeriodsArray()[$user->getSubscriptionPeriod()]),
            ];

            return $this->getPaymentTypeMessage($lastPayment, $params);
        } else {
            return $this->translator->trans('user.payment_type.no_subscription', [
                '%expiration_date%' => $this->localizer->formatDate($user->getPlusExpirationDate(), 'short', $user->getLocale()),
                '%link%' => $this->router->generate('aw_users_pay'),
            ]);
        }
    }

    /**
     * @return Message[]
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('user.payment_type.credit_card.specific'))->setDesc('Your card ending in %card_number% will be charged %price% on %expiration_date% to renew %subscription_type% %subscription_period%.'),
            (new Message('user.payment_type.credit_card.generic'))->setDesc('Your credit card will be charged %price% on %expiration_date% to renew %subscription_type% %subscription_period%.'),
            (new Message('user.payment_type.paypal'))->setDesc('Your PayPal account (%account_email%) will be charged %price% on %expiration_date% to renew %subscription_type% %subscription_period%.'),
            (new Message('user.payment_type.apple_pay'))->setDesc('Your Apple Pay account (%account_email%) will be charged %price% on %expiration_date% via Apple to renew %subscription_type% %subscription_period%.'),
            (new Message('user.payment_type.google_play'))->setDesc('Your Google Play account (%account_email%) will be charged %price% on %expiration_date% via Google to renew %subscription_type% %subscription_period%.'),
            (new Message('user.payment_type.no_subscription'))->setDesc('Currently, you don\'t have an active subscription. On %expiration_date%, your account will be downgraded to AwardWallet Free. <a target="_blank" href="%link%">Set up a subscription now to be charged on %expiration_date% automatically.</a>'),
            (new Message('user.payment_type.period_1_month'))->setDesc('for one more month'),
            (new Message('user.payment_type.period_2_months'))->setDesc('for two more months'),
            (new Message('user.payment_type.period_3_months'))->setDesc('for three more months'),
            (new Message('user.payment_type.period_6_months'))->setDesc('for six more months'),
            (new Message('user.payment_type.period_1_year'))->setDesc('for one more year'),
        ];
    }

    private static function getPeriodsArray(): array
    {
        return [
            SubscriptionPeriod::DAYS_1_MONTH => 'user.payment_type.period_1_month',
            SubscriptionPeriod::DAYS_2_MONTHS => 'user.payment_type.period_2_months',
            SubscriptionPeriod::DAYS_3_MONTHS => 'user.payment_type.period_3_months',
            SubscriptionPeriod::DAYS_6_MONTHS => 'user.payment_type.period_6_months',
            SubscriptionPeriod::DAYS_1_YEAR => 'user.payment_type.period_1_year',
        ];
    }

    /**
     * @throws \Exception if the payment type is not specified in `switch`
     */
    private function getPaymentTypeMessage(Cart $payment, array $params): string
    {
        switch ($payment->getPaymenttype()) {
            case Cart::PAYMENTTYPE_CREDITCARD:
            case Cart::PAYMENTTYPE_TEST_CREDITCARD:
            case Cart::PAYMENTTYPE_STRIPE_INTENT:
                if ($payment->getCreditcardtype() !== null) {
                    $message = $this->translator->trans('user.payment_type.credit_card.specific', [
                        '%card_number%' => '*' . substr($payment->getCreditcardnumber(), -4),
                    ] + $params);
                } else {
                    $message = $this->translator->trans('user.payment_type.credit_card.generic', $params);
                }

                break;

            case Cart::PAYMENTTYPE_PAYPAL:
            case Cart::PAYMENTTYPE_TEST_PAYPAL:
                $message = $this->translator->trans('user.payment_type.paypal', [
                    '%account_email%' => $payment->getEmail(),
                ] + $params);

                break;

            case Cart::PAYMENTTYPE_APPSTORE:
                $message = $this->translator->trans('user.payment_type.apple_pay', [
                    '%account_email%' => $payment->getEmail(),
                ] + $params);

                break;

            case Cart::PAYMENTTYPE_ANDROIDMARKET:
                $message = $this->translator->trans('user.payment_type.google_play', [
                    '%account_email%' => $payment->getEmail(),
                ] + $params);

                break;

            default:
                throw new \Exception('Unknown payment type');
        }

        return $message;
    }
}
