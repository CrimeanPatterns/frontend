<?php

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Type\ProfileCouponType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager as CartManager;
use AwardWallet\MainBundle\Service\CouponApplier;
use AwardWallet\MainBundle\Validator\CouponValidator;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UseCouponController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    private TranslatorInterface $translator;

    private CartManager $cartManager;

    private UserProfileWidget $userProfileWidget;

    private CouponValidator $validator;

    private CouponApplier $couponApplier;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        CartManager $cartManager,
        UserProfileWidget $userProfileWidget,
        CouponValidator $validator,
        CouponApplier $couponApplier
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->cartManager = $cartManager;
        $this->userProfileWidget = $userProfileWidget;
        $this->validator = $validator;
        $this->couponApplier = $couponApplier;
    }

    /**
     * @Route("/user/useCoupon", name="aw_users_usecoupon", options={"expose"=true})
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function useCouponAction(Request $request, AuthorizationCheckerInterface $authorizationChecker, Handler $profileCouponHandlerDesktop)
    {
        $this->userProfileWidget->setActiveItem('coupon');
        $back = $request->query->get('back');
        $codeFromQs = $request->query->get('Code');

        if (!$authorizationChecker->isGranted('ROLE_USER')) {
            // redirect to registration with field Coupon code
            if (!empty($codeFromQs) && is_string($codeFromQs)) {
                return $this->redirectToRoute('aw_register', ['code' => $codeFromQs]);
            }

            throw $this->createAccessDeniedException();
        }

        $cart = $this->cartManager->createNewCart();
        $cart->setPaymenttype(null);
        $session = $request->getSession();
        $form = $this->createForm(ProfileCouponType::class, $cart);

        if ($request->isMethod('GET')) {
            $codeFromRegister = $session->get('coupon');

            if (!empty($codeFromRegister) && is_string($codeFromRegister)) {
                $form->get('coupon')->setData($codeFromRegister);
                $session->remove('coupon');
            }

            if (!empty($codeFromQs) && is_string($codeFromQs)) {
                $form->get('coupon')->setData($codeFromQs);
            }
        }

        $success = false;

        if ($profileCouponHandlerDesktop->handleRequest($form, $request)) {
            $coupon = $cart->getCoupon();

            if ($coupon->getFirsttimeonly()) {
                return $this->redirectToRoute('aw_users_usecoupon_confirm', ['cartId' => $cart->getCartid()]);
            }

            if (count($cart->getItems()) > 0 && $cart->getTotalPrice() > 0) {
                return $this->redirectToRoute('aw_cart_common_paymenttype');
            }

            $success = true;
        }

        // custom error
        $errors = $form->getErrors(true);

        if ($errors->count() === 1 && isset($errors[0])) {
            $c = $errors[0]->getCause();

            if ($c && method_exists($c, 'getCause') && is_array($c->getCause()) && sizeof($c->getCause())) {
                $cause = $c->getCause();
            }
        }

        return $this->render('@AwardWalletMain/User/useCoupon.step1.html.twig', [
            'success' => $success,
            'cart' => $cart,
            'backLink' => isset($back) && is_string($back) ? $back : null,
            'form' => $form->createView(),
            'cause' => $cause ?? null,
        ]);
    }

    /**
     * @Route(
     *     "/user/useCoupon/confirm/{cartId}",
     *      name="aw_users_usecoupon_confirm",
     *      requirements={"cartId"="\d+"},
     *      options={"expose"=true}
     * )
     * @ParamConverter("cart", class="AwardWalletMainBundle:Cart", options={"id" = "cartId"})
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA') and is_granted('ROLE_USER')")
     */
    public function confirmCouponAction(Cart $cart, Request $request)
    {
        $this->userProfileWidget->setActiveItem('coupon');

        /** @var Usr $user */
        $user = $this->getUser();
        $coupon = $cart->getCoupon();

        if (
            $user->getId() != $cart->getUser()->getId()
            || $cart->isPaid()
            || is_null($coupon)
            || !$coupon->getFirsttimeonly()
            || !$this->validator->allowApplyFirstTimeOnlyCoupon($user)
        ) {
            return $this->redirectToRoute('aw_users_usecoupon');
        }

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, [
                /** @Desc("Set Up Subscription") */
                'label' => 'user.coupon.button.setup-subscription',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->couponApplier->applyCouponToCart($coupon, $cart);

            return $this->redirectToRoute('aw_cart_common_paymenttype');
        }

        return $this->render('@AwardWalletMain/User/useCoupon.step2.html.twig', [
            'cart' => $cart,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/user/get-available-coupons", name="aw_coupon_get_available", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function getAvailableCoupons(AwTokenStorageInterface $tokenStorage)
    {
        $coupons = $this->entityManager
            ->getRepository(Coupon::class)
            ->getCouponsByCode('Invite-' . $tokenStorage->getToken()->getUser()->getUserid() . '-%');

        if (!empty($coupons)) {
            $result = [
                'content' => $this->translator->trans('congratulation.message.text'),
                'coupons' => $coupons,
            ];
        } else {
            $result = [
                'content' => $this->translator->trans('upgrade.message.text1'),
            ];
        }

        return new JsonResponse($result);
    }
}
