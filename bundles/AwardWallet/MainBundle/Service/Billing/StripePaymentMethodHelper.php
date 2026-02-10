<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

class StripePaymentMethodHelper
{
    private StripeClient $stripeClient;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        StripeClient $stripeClient,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->stripeClient = $stripeClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Updates the name and last four digits of the credit card.
     */
    public function updateCreditCardDetails(string $paymentMethodId, Cart $cart)
    {
        try {
            $paymentMethod = $this->stripeClient->paymentMethods->retrieve($paymentMethodId);

            if ($paymentMethod->type === 'card') {
                // This hash contains a snapshot of the transaction specific details of the `card` payment method.
                $cart->setCreditcardtype($paymentMethod->card->brand);
                $cart->setCreditcardnumber($paymentMethod->card->last4);

                $this->entityManager->flush();
            } else {
                $this->logger->info('stripe payment method info: ' . json_encode([
                    'paymentIntentId' => $cart->getBillingtransactionid(),
                    'methodType' => $paymentMethod->type,
                    'methodDetails' => $paymentMethod->billing_details,
                ]));
            }
        } catch (InvalidRequestException $exception) {
            $format = 'error when receiving a payment method object, payment intent id: %s, message: %s';
            $this->logger->error(sprintf($format, $cart->getBillingtransactionid(), $exception->getMessage()));
        }
    }
}
