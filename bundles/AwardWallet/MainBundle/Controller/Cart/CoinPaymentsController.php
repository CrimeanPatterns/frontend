<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\CoinPayments\CoinPayments;
use Endroid\QrCode\QrCode;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/cart/coin-payments")
 */
class CoinPaymentsController extends AbstractController
{
    private Manager $cartManager;
    private RouterInterface $router;
    private string $cpMerchantId;
    private string $cpCallbackSecret;

    public function __construct(
        Manager $cartManager,
        RouterInterface $router,
        string $cpMerchantId,
        string $cpCallbackSecret,
        LoggerInterface $paymentLogger,
        LoggerInterface $logger
    ) {
        parent::__construct($paymentLogger, $logger);
        $this->cartManager = $cartManager;
        $this->router = $router;
        $this->cpMerchantId = $cpMerchantId;
        $this->cpCallbackSecret = $cpCallbackSecret;
    }

    /**
     * @Route("/prepare/{cartId}", name="aw_cart_coinpayments_prepare", requirements={"cartId" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function prepareAction(Request $request, $cartId, CoinPayments $coinPayments, AwTokenStorageInterface $tokenStorage)
    {
        $cartRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $cart = $cartRep->find($cartId);

        if ($cart->getUser() !== $tokenStorage->getBusinessUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$cart || !sizeof($cart->getItems())) {
            return $this->redirect($this->router->generate('aw_account_list'));
        }

        if (
            !$cart->isEthereumPaymentType() && !$cart->isBitcoinPaymentType()
        ) {
            return $this->redirectToRoute('aw_cart_common_paymenttype');
        }

        if ($cart->getPaydate()) {
            return $this->redirectToRoute('aw_cart_common_complete', ['id' => $cart->getCartid()]);
        }

        $currencyCode = $cart->isEthereumPaymentType() ? 'ETH' : 'BTC';

        if ($cart->getBillingtransactionid()) {
            $response = $coinPayments->transactionInfo($cart->getBillingtransactionid());

            if ($response['error'] == 'ok' && $response['result']['status'] > -1) {
                $response['result']['timeout'] = $response['result']['time_expires'] - time();
                $response['result']['address'] = $response['result']['payment_address'];
                $response['result']['amount'] = $response['result']['amountf'];
            } else {
                unset($response);
            }
        }

        if (!isset($response)) {
            $response = $coinPayments->createTransaction($cart, $currencyCode);

            if (!$response || $response['error'] != 'ok') {
                $this->logger->critical("CoinPayments API error", $response ? $response : []);
                $cart->setBillingtransactionid(null);
                $cart->setPaymenttype(null);
                $this->cartManager->save($cart);

                return $this->redirect($this->router->generate('aw_cart_common_paymenttype'));
            }
            $cart->setBillingtransactionid($response['result']['txn_id']);
            $this->cartManager->save($cart);
        }

        $qrCodeGenerator = new QrCode($response['result']['address']);
        $qrCodeGenerator->setSize(200);
        $response['result']['qrcode_url'] = $qrCodeGenerator->getDataUri();

        return $this->render('@AwardWalletMain/Cart/CoinPayments/prepare.html.twig', [
            'cart' => $cart,
            'result' => $response['result'],
            'currencyCode' => $currencyCode,
        ]);
    }

    /**
     * @Route("/cancel", name="aw_cart_coinpayments_cancel", requirements={"currencyCode" = "ETH|BTC"})
     * @Security("is_granted('ROLE_USER')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function cancelAction(Request $request)
    {
        $cart = $this->cartManager->getCart();

        if (
            !$cart->getPaydate()
            && ($cart->isEthereumPaymentType() || $cart->isBitcoinPaymentType())
        ) {
            $newCart = $this->cartManager->createNewCart();
            $this->cartManager->save($newCart);
        }

        return $this->redirect($this->router->generate('aw_account_list'));
    }

    /**
     * @Route("/callback", name="aw_cart_coinpayments_callback", methods={"POST"})
     */
    public function callbackAction(Request $request)
    {
        if ($request->getUser() !== $this->cpMerchantId || $request->getPassword() !== $this->cpCallbackSecret) {
            return new Response('Access denied', 403);
        }

        $post = $request->request;
        $cart_id = $post->get('custom');
        $cart = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)->find($cart_id);

        $merchantId = $post->get('merchant');
        $txn_id = $post->get('txn_id');
        $amount1 = floatval($post->get('amount1'));
        $currency1 = $post->get('currency1');
        $status = intval($post->get('status'));

        if ($merchantId != $this->cpMerchantId) {
            $error = 'Incorrect or missing merchant id';
        } elseif (!$cart) {
            $error = 'Cart not found';
        } elseif ($cart->getPaydate()) {
            $error = 'Cart already marked as paid';
        } elseif ($amount1 * 1.10 < $cart->getTotalPrice()) {
            $error = 'Amount is less than order total';
        } elseif ($currency1 != 'USD') {
            $error = 'Currency type mismatch';
        }

        if (isset($error)) {
            $this->logger->critical(sprintf("CoinPayments IPN error: %s", $error), $post->all());

            return new Response($error);
        }

        if ($status < 1) {
            $this->logger->info("CoinPayments IPN: waiting for payment or order timeout", $post->all());
        } else {
            if ($status >= 100 || $status == 2) {
                $this->logger->info(sprintf("CoinPayments IPN payment success, cart id: %s", $cart->getCartid()), $post->all());
            } else {
                $this->logger->info(sprintf("CoinPayments IPN payment pending, marking cart as paid anyway, cart id: %s", $cart->getCartid()), $post->all());
            }
            $cart->setBillingtransactionid($txn_id);
            $this->cartManager->markAsPayed($cart);
            /** @var Usr $user */
            $user = $cart->getUser();
            $this->getDoctrine()->getManager()->flush();
        }

        return new Response('OK');
    }
}
