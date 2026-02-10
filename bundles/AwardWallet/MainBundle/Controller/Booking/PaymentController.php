<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\Booking;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Event\BookingMessage;
use AwardWallet\MainBundle\Globals\Cart\CreditCardPaymentTypeSelector;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Manager\Exception\PaymentException;
use AwardWallet\MainBundle\Parameter\AwardWalletBookerParameter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/awardBooking/payment")
 */
class PaymentController extends AbstractController
{
    private BookingRequestManager $bookingRequestManager;
    private TranslatorInterface $translator;
    private AuthorizationCheckerInterface $authorizationChecker;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        BookingRequestManager $bookingRequestManager,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->bookingRequestManager = $bookingRequestManager;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/byCheck/{id}", name="aw_booking_payment_by_check", methods={"POST"}, requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('PAY', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function payByCheck(AbRequest $abRequest)
    {
        try {
            $invoice = $this->getInvoice($abRequest);
            $this->bookingRequestManager->markAsPaid($invoice, (float) $invoice->getTotal(), AbInvoice::PAYMENTTYPE_CHECK);
        } catch (PaymentException $e) {
            return new Response($e->getMessage());
        }

        return new Response('OK');
    }

    /**
     * @Route("/byCreditCard/{id}", name="aw_booking_payment_by_cc", methods={"POST"}, requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('PAY', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function payByCreditCard(AbRequest $abRequest, KernelInterface $kernel, AwardWalletBookerParameter $awardWalletBookerParameter, Manager $cartManager, RouterInterface $router, CreditCardPaymentTypeSelector $paymentTypeSelector)
    {
        try {
            $bookerInfo = $abRequest->getBooker()->getBookerInfo();
            $invoice = $this->getInvoice($abRequest);

            if (empty($bookerInfo->getPayPalPassword()) && !$kernel->isDebug() && $abRequest->getBooker()->getId() != $awardWalletBookerParameter->get()) {
                throw new PaymentException($this->translator->trans('booking.payment.cc-disabled', ["%booker%" => $bookerInfo->getServiceName()], 'booking'));
            }
        } catch (PaymentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        $cart = $cartManager->createNewCart();

        $discountAmount = 0;

        foreach ($invoice->getItems() as $item) {
            $cartItem = new Booking();
            $cartItem->setName($item->getDescription());
            $cartItem->setCnt($item->getQuantity());
            $cartItem->setPrice($item->getPrice());
            $cartItem->setDiscount(0);
            $cartItem->setRequestId($abRequest->getAbRequestID());
            $cartItem->setInvoiceId($invoice->getId());
            $cartItem->setOperation(1);
            $cart->addItem($cartItem);

            if (!empty($item->getDiscount())) {
                $discountAmount += $item->getDiscountAmount();
            }
        }

        $lang = $invoice->getMessage()->getUser()->getLanguage();
        $paymentType = $paymentTypeSelector->getCreditCardPaymentType($cart);

        if (
            $paymentType == Cart::PAYMENTTYPE_CREDITCARD
            && $this->authorizationChecker->isGranted('SITE_LOCAL_PROD_MODE')
        ) {
            $paymentType = Cart::PAYMENTTYPE_TEST_CREDITCARD;
        }

        $cart->setPaymenttype($paymentType);
        $cartManager->save($cart); // for order

        if ($discountAmount > 0) {
            $cartItem = new Discount();
            $cartItem->setPrice(round(-1 * $discountAmount, 2));
            $cartItem->setName($this->translator->trans('cart.item.discount-without-percent', [], 'messages', $lang));
            $cart->addItem($cartItem);
            $cartManager->save($cart);
        }

        $url = $router->generate($paymentType === Cart::PAYMENTTYPE_STRIPE_INTENT ? 'aw_cart_stripe_orderdetails' : 'aw_cart_common_orderdetails');

        $this->eventDispatcher->dispatch(new BookingMessage\EditEvent($invoice->getMessage()), 'aw.booking.message.edit');

        return new JsonResponse([
            'status' => 'ok',
            'url' => $url,
        ]);
    }

    /**
     * @Route("/markAsPaid/{id}", name="aw_booking_mark_invoice_as_paid", methods={"POST"}, requirements={"id" = "\d+"}, options={"expose"=true})
     * @ParamConverter("invoice", class="AwardWalletMainBundle:AbInvoice")
     * @Security("is_granted('CSRF')")
     */
    public function markAsPaid(Request $request, AbInvoice $invoice, EntityManagerInterface $entityManager)
    {
        $abMessage = $invoice->getMessage();
        $abRequest = $abMessage->getRequest();

        if (!$this->authorizationChecker->isGranted('BOOKER', $abRequest)) {
            throw new AccessDeniedException("Booker only");
        }

        if (!$abRequest->getBooker()->getBookerInfo()->getAcceptChecks()) {
            throw new AccessDeniedException("Enable \"Accept Checks\" in the booker profile");
        }

        if ($request->request->get('paid') == 'true') {
            $status = AbInvoice::STATUS_PAID;

            // change request status
            if (
                $abRequest->getStatus() != AbRequest::BOOKING_STATUS_PROCESSING
                && !$abMessage->isAutoreplyInvoice()
            ) {
                $abRequest->setStatus(AbRequest::BOOKING_STATUS_PROCESSING);
                $this->bookingRequestManager->changeStatus($abRequest, $abRequest->getStatus());
                $this->bookingRequestManager->flush();
            }
        } else {
            $status = AbInvoice::STATUS_UNPAID;
        }
        $abMessage->setLastUpdateDate(new \DateTime());
        $invoice->setStatus($status);
        $invoice->setPaidTo($abRequest->getBooker());
        $entityManager->persist($invoice);
        $entityManager->flush();

        $this->eventDispatcher->dispatch(new BookingMessage\EditEvent($abMessage, ['action' => 'statusChange']), 'aw.booking.message.edit');

        return new Response('OK');
    }

    protected function getInvoice(AbRequest $request)
    {
        $invoice = $request->getLastInvoice();

        if ($invoice->getStatus() == AbInvoice::STATUS_PAID) {
            throw new PaymentException($this->translator->trans(/** @Desc("You have already paid this invoice so this action is not required.") */ 'invoice_already_paid', [], 'booking'));
        }

        return $invoice;
    }
}
