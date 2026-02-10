<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Service\Billing\StripeCartServices;
use Psr\Log\LoggerInterface;
use Stripe\Charge;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeEventController extends AbstractController
{
    private StripeCartServices $stripeCartServices;
    private LoggerInterface $logger;
    private StripeClient $stripeClient;
    private string $stripeWebhookSecret;

    public function __construct(StripeCartServices $stripeCartServices, LoggerInterface $paymentLogger, StripeClient $stripeClient, string $stripeWebhookSecret)
    {
        $this->stripeCartServices = $stripeCartServices;
        $this->logger = $paymentLogger;
        $this->stripeClient = $stripeClient;
        $this->stripeWebhookSecret = $stripeWebhookSecret;
    }

    /**
     * @Route("/stripe-event", name="aw_stripe_event", methods={"POST"})
     */
    public function receiveEventAction(Request $request)
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $request->headers->get('Stripe-Signature'),
                $this->stripeWebhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            $this->logger->critical("stripe webhook error: " . $e->getMessage());

            return new Response(400);
        }

        $this->logger->info("received stripe event: " . $event->type);

        switch ($event->type) {
            case 'charge.refunded':
                /** @var Charge $charge */
                $charge = $event->data->object;
                $this->logger->info("received stripe refund: {$charge->id}");
                $cart = $this->stripeCartServices->findCart($charge->id, $charge->description);

                if ($cart) {
                    $this->stripeCartServices->deleteRefundedCart($charge, $cart, true);
                }

                break;
        }

        return new Response('ok');
    }
}
