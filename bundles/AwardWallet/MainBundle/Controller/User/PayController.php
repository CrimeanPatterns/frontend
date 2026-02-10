<?php

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\Cart\BalanceWatchCreditType;
use AwardWallet\MainBundle\Form\Type\Cart\UserPayType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager as CartManager;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class PayController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private CartManager $cartManager;
    private RouterInterface $router;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        CartManager $cartManager,
        RouterInterface $router
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->cartManager = $cartManager;
        $this->router = $router;
    }

    /**
     * @Route("/user/pay", name="aw_business_users_pay", host="%business_host%")
     * @Security("is_granted('ROLE_USER') and is_granted('SITE_BUSINESS_AREA')")
     * @return RedirectResponse
     */
    public function businessPayAction(Request $request)
    {
        return new RedirectResponse($this->generateUrl("aw_business_balance"));
    }

    /**
     * @Route("/user/pay", name="aw_users_pay", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @return RedirectResponse|Response
     */
    public function payAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        CartRepository $cartRepository
    ) {
        /** @var Usr $user */
        $user = $this->tokenStorage->getBusinessUser();
        $giveAWPlus = true;
        $isUpgrade = true;

        $cart = $this->cartManager->createNewCart();
        $subscriptionStartDate = $this->cartManager->getSubscriptionStartDate($user);

        if ($cart->isEthereumPaymentType() || $cart->isBitcoinPaymentType()) {
            $cart = $this->cartManager->createNewCart();
        }

        /** @var Cart $currentSubscription */
        $currentSubscription = $cartRepository->getActiveAwSubscription($user);

        if ($currentSubscription && $user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS) {
            $giveAWPlus = false;
        }

        if ($request->query->get('back') == 1) {
            $onecards = $cart->getOneCardsQuantity();
        } else {
            $onecards = $giveAWPlus ? 0 : 1;
        }

        $this->cartManager->fillCart($cart, $user, $onecards, $giveAWPlus, $subscriptionStartDate);

        $form = $this->createForm(UserPayType::class, null, ['with_upgrade_account' => $giveAWPlus]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->cartManager->fillCart($cart, $user, $data['onecard'], $data['awPlus'] == 'true', $subscriptionStartDate);

            return $this->redirect($this->router->generate('aw_cart_common_paymenttype', [
                'backTo' => $this->generateUrl('aw_users_pay'),
                'entry' => md5('aw_cart_common_paymenttype'),
            ]));
        }

        $options = [
            'giveAWPlus' => $giveAWPlus,
            'user_pay' => [
                'onecard' => $onecards,
            ],
            'price' => [
                'awplus' => AwPlusSubscription::PRICE,
                'onecard' => OneCard::PRICE,
            ],
            'discountName' => $cart->getDiscount() ? $cart->getDiscount()->getName() : null,
            'discountAmount' => $cart->getDiscount() ? abs($cart->getDiscount()->getPrice()) : null,
        ];

        $userProfileWidget->setActiveItem('upgrade');

        return $this->render('@AwardWalletMain/User/pay.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
                'giveAWPlus' => $giveAWPlus,
                'startDate' => $subscriptionStartDate,
                'paymentOptions' => $options,
                'isUpgrade' => $isUpgrade,
            ]
        );
    }

    /**
     * @Route("/user/pay/balancewatch-credit", name="aw_users_pay_balancewatchcredit", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @return RedirectResponse|Response
     */
    public function payBalanceWatchCreditAction(Request $request)
    {
        /** @var Usr $user */
        $user = $this->tokenStorage->getBusinessUser();

        if (!$user->isAwPlus()) {
            return $this->redirect($this->router->generate('aw_profile_overview', ['_fragment' => 'balancewatch-credit-upgrade']));
        }
        $queryParameters = [];
        $request->query->has('forceId') ? $queryParameters['forceId'] = (int) $request->query->get('forceId') : null;

        $form = $this->createForm(BalanceWatchCreditType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $cart = $this->cartManager->createNewCart();
            $this->cartManager->addBalanceWatchCredit($cart, $data['balanceWatchCredit']);
            $this->cartManager->save($cart);

            return $this->redirect($this->router->generate('aw_cart_common_paymenttype', $queryParameters));
        }

        $options = [
            'user_pay' => [
                'balanceWatchCreditAmount' => BalanceWatchCredit::COUNT_PRICE[1],
                'countPrice' => BalanceWatchCredit::COUNT_PRICE,
            ],
        ];

        return $this->render('@AwardWalletMain/Users/payBalanceWatchCredit.html.twig', [
            'form' => $form->createView(),
            'paymentOptions' => $options,
            'user' => $user,
            'cartItem' => 'balanceWatchCredit',
        ]);
    }
}
