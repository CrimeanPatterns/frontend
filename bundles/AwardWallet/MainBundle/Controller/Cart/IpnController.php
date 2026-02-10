<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\RecurringPaymentFailed;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\UpgradeCodeGenerator;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\PaypalIpnProcessor;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class IpnController extends AbstractController
{
    private LoggerInterface $logger;
    private UsrRepository $userRep;
    private CartRepository $cartRep;
    private PaypalRestApi $paypalApi;
    private EntityManagerInterface $em;
    private PlusManager $plusManager;
    private Mailer $mailer;
    private ExpirationCalculator $expirationCalculator;
    private RouterInterface $router;
    private UpgradeCodeGenerator $codeGenerator;
    private PaypalIpnProcessor $paypalIpnProcessor;
    private Manager $cartManager;

    public function __construct(
        LoggerInterface $paymentLogger,
        UsrRepository $userRep,
        CartRepository $cartRep,
        PaypalRestApi $paypalApi,
        EntityManagerInterface $em,
        PlusManager $plusManager,
        Mailer $mailer,
        ExpirationCalculator $expirationCalculator,
        RouterInterface $router,
        UpgradeCodeGenerator $codeGenerator,
        PaypalIpnProcessor $paypalIpnProcessor,
        Manager $cartManager
    ) {
        $this->logger = $paymentLogger;
        $this->userRep = $userRep;
        $this->cartRep = $cartRep;
        $this->paypalApi = $paypalApi;
        $this->em = $em;
        $this->plusManager = $plusManager;
        $this->mailer = $mailer;
        $this->expirationCalculator = $expirationCalculator;
        $this->router = $router;
        $this->codeGenerator = $codeGenerator;
        $this->paypalIpnProcessor = $paypalIpnProcessor;
        $this->cartManager = $cartManager;
    }

    /**
     * @Route("/paypal/IPNListener.php", name="aw_cart_paypal_ipn_listener")
     */
    public function listenerAction(Request $request)
    {
        $this->logger->info('ipn request received: ' . json_encode($request->request->all()));

        if ($request->request->count() < 2) {
            $this->logger->error("bad ipn request, expected at least 2 params, got " . $request->request->count());

            return new Response("Bad request", 400);
        }

        if ($request->request->get("payment_status") === "Refunded") {
            $this->refund($request->request);
            $this->logger->info("ipn success");

            return new Response();
        }

        switch ($request->request->get("txn_type")) {
            case 'recurring_payment':
                $this->recurringPaymentSuccess($request->request);

                break;

            case 'recurring_payment_failed':
                $this->recurringPaymentFailure($request->request, true);

                break;

            case 'recurring_payment_skipped':
                $this->recurringPaymentFailure($request->request, false);

                break;

            case 'recurring_payment_profile_cancel':
                $this->cancelProfile($request->request);

                break;

            default:
                $this->logger->info("ignoring unknown txn_type: " . $request->request->get("txn_type"));
        }

        $this->logger->info("ipn success");

        return new Response();
    }

    private function recurringPaymentSuccess(ParameterBag $request)
    {
        if ($request->get("payment_status") != 'Completed') {
            $this->logger->info("ignoring unknown payment status: " . $request->get("payment_status"));

            return;
        }
        $transactionId = $request->get('txn_id');

        if (empty($transactionId)) {
            $this->error("empty txn_id");
        }
        $profileId = $request->get('recurring_payment_id');

        if (empty($profileId)) {
            $this->error("empty recurring_payment_id");
        }

        $user = $this->getUserByRequest($request);

        if (empty($user)) {
            $this->logger->critical("can't find user with recurring profile", ["recurring_payment_id" => $profileId]);

            return;
        }

        $amount = $request->get('payment_gross');

        if (!is_numeric($amount)) {
            $this->error('payment_gross is not numeric');
        }

        $this->paypalIpnProcessor->processTransaction($user, $transactionId, $profileId, $request->get('payment_cycle'), $amount);
    }

    private function recurringPaymentFailure(ParameterBag $request, bool $downgrade)
    {
        $profileId = $request->get('recurring_payment_id');

        if (empty($profileId)) {
            $this->error("empty recurring_payment_id");
        }

        $user = $this->getUserByRequest($request);

        if (empty($user)) {
            $this->logger->warning("can't find user with recurring profile", ["recurring_payment_id" => $profileId]);

            return;
        }

        if ($user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS) {
            if ($user->getSubscription() == Usr::SUBSCRIPTION_PAYPAL) {
                $user->setFailedRecurringPayments($user->getFailedRecurringPayments() + 1);
                $this->em->flush();

                $cart = $this->cartRep->getActiveAwSubscription($user);
                $plusItem = $cart->getPlusItem();
                $template = new RecurringPaymentFailed($user);
                $template->semiAnnualSubscription = $plusItem instanceof AwPlusUpgradableInterface
                    && !is_subclass_of($plusItem, AwPlus::class);

                if ($cart->getPaymenttype() == Cart::PAYMENTTYPE_PAYPAL) {
                    $template->paymentSource = RecurringPaymentFailed::PAYMENT_SOURCE_PAYPAL;
                } else {
                    $template->paymentSource = RecurringPaymentFailed::PAYMENT_SOURCE_CC;
                    $template->ccNumber = substr($cart->getCreditcardnumber(), -4);
                }

                $template->amount = $request->get("amount");
                $template->throughDate = new \DateTime($cart->getPlusItem()->getDuration());
                $template->paymentLink = $this->router
                    ->generate('aw_cart_change_payment_method_email', [
                        'userId' => $user->getId(),
                        'hash' => $this->codeGenerator->generateCode($user),
                    ]);
                $message = $this->mailer->getMessageByTemplate($template);
                $this->mailer->send($message, [Mailer::OPTION_SKIP_DONOTSEND => true]);

                if ($downgrade) {
                    $this->plusManager->checkExpirationAndDowngrade($user);
                }
            } else {
                $this->logger->critical("failed ipn, but Subscription is not paypal", ["UserID" => $user->getUserid(), "recurring_payment_id" => $profileId]);
            }
            $this->em->flush();
        }
    }

    /**
     * @return Usr
     */
    private function getUserByRequest(ParameterBag $request)
    {
        $profileId = $request->get('recurring_payment_id');

        if (empty($profileId)) {
            $this->error('recurring_payment_id does not exists');
        }

        // TODO: validate payment with paypal

        return $this->userRep->findOneBy(['paypalrecurringprofileid' => $profileId]);
    }

    private function error($message)
    {
        $this->logger->error($message);

        throw new HttpException(500, $message);
    }

    private function cancelProfile(ParameterBag $request)
    {
        $profileId = $request->get('recurring_payment_id');

        if (empty($profileId)) {
            $this->error("empty recurring_payment_id");
        }

        if ($request->get("profile_status") !== 'Cancelled') {
            $this->error("profile was not cancelled");
        }

        $user = $this->getUserByRequest($request);

        if (empty($user)) {
            $this->logger->info("can't find user with recurring profile, already cancelled?", ["recurring_payment_id" => $profileId]);

            return;
        }

        if ($user->getSubscription() !== Usr::SUBSCRIPTION_PAYPAL) {
            $this->error("current subscription is not paypal");
        }

        $user->clearSubscription();
        $this->em->flush();

        $this->logger->info("subscription was removed from user {$user->getUserid()}");
    }

    private function refund(ParameterBag $request): void
    {
        $this->logger->info("processing refund request");
        $txId = $request->get("parent_txn_id");
        /** @var Cart $cart */
        $cart = $this->cartRep->findOneBy(['billingtransactionid' => $txId]);

        if ($cart === null) {
            $this->logger->warning("cart not found for refund", ["txId" => $txId]);

            return;
        }

        $this->logger->info("deleting refunded cart", ["cartId" => $cart->getCartid(), "txId" => $txId, "UserID" => $cart->getUser()->getId()]);
        $this->cartManager->refund($cart);
    }
}
