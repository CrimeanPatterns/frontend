<?php

namespace AwardWallet\MainBundle\Globals\Cart;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Parameter\AwardWalletBookerParameter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CreditCardPaymentTypeSelector
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private AwardWalletBookerParameter $awardWalletBookerParameter;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, AwardWalletBookerParameter $awardWalletBookerParameter)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->awardWalletBookerParameter = $awardWalletBookerParameter;
    }

    public function getCreditCardPaymentType(Cart $cart): int
    {
        $abRequestId = $cart->getBookingRequestId();

        if ($abRequestId === null) {
            $this->logger->info("no booking in cart, will pay with stripe");

            return Cart::PAYMENTTYPE_STRIPE_INTENT;
        }

        $abRequest = $this->entityManager->find(AbRequest::class, $abRequestId);

        return $this->getCreditCardPaymentTypeForBooker($abRequest->getBooker());
    }

    private function getCreditCardPaymentTypeForBooker(Usr $booker)
    {
        if ($booker->getId() == $this->awardWalletBookerParameter->get()) {
            $this->logger->info("aw booking in cart, will pay with stripe");

            return Cart::PAYMENTTYPE_STRIPE_INTENT;
        }

        $this->logger->info("non-aw booking in cart, will pay with credit card through paypal");

        return Cart::PAYMENTTYPE_CREDITCARD;
    }
}
