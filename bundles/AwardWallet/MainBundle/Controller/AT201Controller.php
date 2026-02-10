<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\Cart\AT201SubscriptionType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\CartUserInfo;
use AwardWallet\MainBundle\Globals\Cart\CartUserSource;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * @Route("", name="")
 */
class AT201Controller
{
    public const WHITE_LIST = [
        'travelhacksrus@gmail.com',
        'heather.killingbeck@gmail.com',
        'carrieengfer7@gmail.com',
        'danisexton@gmail.com',
        'epeben@yahoo.com',
        'Michael@travelzork.com',
        'Trpat8401@gmail.com',
        'tmountcastle@gmail.com',
    ];

    /** @var AuthorizationCheckerInterface */
    private $checker;

    /** @var Environment */
    private $twig;

    /** @var RouterInterface */
    private $router;

    /** @var Manager */
    private $cartManager;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var CartUserSource */
    private $cartUserSource;

    /** @var SessionInterface */
    private $session;

    public function __construct(
        AuthorizationCheckerInterface $checker,
        Environment $twig,
        RouterInterface $router,
        Manager $cartManager,
        FormFactoryInterface $formFactory,
        CartUserSource $cartUserSource,
        SessionInterface $session
    ) {
        $this->checker = $checker;
        $this->twig = $twig;
        $this->router = $router;
        $this->cartManager = $cartManager;
        $this->formFactory = $formFactory;
        $this->cartUserSource = $cartUserSource;
        $this->session = $session;
    }

    /**
     * @Route("/at201", name="aw_at201_landing", defaults={"_canonical"="aw_at201_landing_locale", "_alternate"="aw_at201_landing_locale"})
     * @Route("/{_locale}/at201", name="aw_at201_landing_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_at201_landing_locale", "_alternate"="aw_at201_landing_locale"})
     */
    public function indexAction()
    {
        return new Response($this->twig->render('@AwardWalletMain/Page/at201.html.twig', [
            'biAnnualItem' => new AT201Subscription6Months(),
            'annualItem' => new AT201Subscription1Year(),
        ]));
    }

    /**
     * @Route("/user/pay/at201", name="aw_at201_payment")
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function paymentAction(Request $request, UsrRepository $usrRepository, AwTokenStorage $tokenStorage)
    {
        $type = (int) $request->get('type');
        $item = $this->getSubscriptionItemByType($type);
        $form = $this->formFactory->create(AT201SubscriptionType::class);
        $form->setData(['type' => $type]);
        $form->handleRequest($request);

        $user = $tokenStorage->getBusinessUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $cartUserInfo = null;

            if (isset($data['email'])) {
                $user = $usrRepository->findOneBy(['email' => $data['email']]);
                $cartUserInfo = new CartUserInfo($user->getId(), $user->getId(), true);
            }

            $item = $this->getSubscriptionItemByType((int) $data['type']);
            $cart = $this->cartManager->createNewCart($cartUserInfo);
            $this->cartManager->addAT201SubscriptionItem($cart, $item::DURATION);
            $cart->setCalcDate(new \DateTime());
            $cart->setPaymenttype(Cart::PAYMENTTYPE_STRIPE_INTENT);
            $this->cartManager->save($cart);

            return new RedirectResponse($this->router->generate('aw_cart_stripe_orderdetails'));
        }

        $params = [
            'form' => $form->createView(),
            'type' => $type,
            'plusSubscription' => false,
            'at201Subscription' => false,
            'item' => [
                'name' => $item::getTranslationMessages()[0] ?? '',
                'price' => $item->getPrice(),
                'months' => $item->getMonths(),
                'savings' => $item->getSavings(),
            ],
        ];

        if ($user !== null) {
            $params['plusSubscription'] = $user->getSubscription() > 0;
            $params['at201Subscription'] = $user->getSubscription() > 0 && $user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AT201;
        }

        return new Response($this->twig->render(
            '@AwardWalletMain/Users/payAT201Subscription.html.twig', $params
        ));
    }

    private function getSubscriptionItemByType(int $type): AT201SubscriptionInterface
    {
        switch ($type) {
            //            case AT201Subscription1Month::TYPE:
            //                return new AT201Subscription1Month();

            case AT201Subscription6Months::TYPE:
                return new AT201Subscription6Months();

            case AT201Subscription1Year::TYPE:
                return new AT201Subscription1Year();
        }

        throw new NotFoundHttpException();
    }
}
