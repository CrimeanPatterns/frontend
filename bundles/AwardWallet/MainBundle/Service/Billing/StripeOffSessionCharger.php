<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

class StripeOffSessionCharger
{
    private StripeClient $stripe;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private StripePaymentMethodHelper $paymentMethodHelper;

    public function __construct(
        StripeClient $stripe,
        LoggerInterface $paymentLogger,
        EntityManagerInterface $entityManager,
        StripePaymentMethodHelper $paymentMethodHelper
    ) {
        $this->stripe = $stripe;
        $this->logger = $paymentLogger;
        $this->entityManager = $entityManager;
        $this->paymentMethodHelper = $paymentMethodHelper;
    }

    /**
     * @return string payment intent id
     * @throws CardException
     */
    public function charge(string $customer, string $paymentMethod, float $amount, int $cartId, ?int $userId): string
    {
        $this->logger->info("making stripe payment with amount {$amount}");
        $intentOptions = [
            'customer' => $customer,
            'payment_method' => $paymentMethod,
            'amount' => (int) round($amount * 100),
            'currency' => 'usd',
            'description' => "Order #" . $cartId,
            'metadata' => [
                'cart_id' => $cartId,
            ],
            'statement_descriptor_suffix' => ' Order ' . $cartId,
            'off_session' => true,
            'confirm' => true,
            'payment_method_types' => ["card", "link"],
        ];

        if ($userId) {
            $intentOptions['metadata']['user_id'] = $userId;
        }

        try {
            $paymentIntent = $this->stripe->paymentIntents->create($intentOptions);
        } catch (InvalidRequestException $exception) {
            // there are users who are marked as Subscription=Stripe, but payment method was detached from customer on stripe side,
            // like customer cancelled subscription
            // looks like paypal -> stripe migration issue
            //
            // full exception text: The provided PaymentMethod was previously used with a PaymentIntent without Customer attachment, shared with a connected account without Customer attachment, or was detached from a Customer. It may not be used again. To use a PaymentMethod multiple times, you must attach it to a Customer first.
            if (stripos($exception->getMessage(), 'It may not be used again. To use a PaymentMethod multiple times, you must attach it to a Customer first')) {
                throw new CardException('Card was detached from the customer');
            }

            throw $exception;
        }

        if ($paymentIntent->payment_method !== null) {
            $cart = $this->entityManager->getRepository(Cart::class)->find($cartId);
            $this->paymentMethodHelper->updateCreditCardDetails($paymentIntent->payment_method, $cart);
        }

        $this->logger->info("stripe payment complete, payment intent id: " . $paymentIntent->id);

        return $paymentIntent->id;
    }
}
