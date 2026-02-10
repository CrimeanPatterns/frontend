<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\Entity\CartItem\AwBusinessCredit;
use AwardWallet\MainBundle\Form\Type\Cart\BusinessPayType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager as CartManager;
use AwardWallet\MainBundle\Service\BusinessTransaction\BusinessTransactionManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class PaymentController extends AbstractController
{
    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/balance/pay", name="aw_business_pay", options={"expose"=true})
     * @Template("@AwardWalletMain/Business/Payment/businessPay.html.twig")
     */
    public function businessPayAction(
        Request $request,
        BusinessTransactionManager $transactionManager,
        CartManager $cartManager,
        AwTokenStorageInterface $tokenStorage,
        RouterInterface $router
    ) {
        $cart = $cartManager->createNewCart();

        $monthlyEstimate = $transactionManager->getRecommendedPayment($tokenStorage->getBusinessUser());

        $form = $this->createForm(BusinessPayType::class, null, [
            'estimate' => $monthlyEstimate,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $item = new AwBusinessCredit();
            $item->setPrice($data['credit_amount']);
            $item->setMonthlyEstimate($monthlyEstimate);
            $cart->addItem($item);
            $cartManager->save($cart);

            return $this->redirect($router->generate('aw_cart_common_paymenttype'));
        }

        return [
            'form' => $form->createView(),
            'monthlyEstimate' => $monthlyEstimate,
        ];
    }
}
