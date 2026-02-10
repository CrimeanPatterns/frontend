<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus3Months;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class HelpCovid19Controller
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $checker;
    /**
     * @var Manager
     */
    private $cartManager;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $checker,
        Manager $cartManager,
        EntityManagerInterface $em,
        Environment $twig,
        RouterInterface $router
    ) {
        $this->checker = $checker;
        $this->cartManager = $cartManager;
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->twig = $twig;
        $this->router = $router;
    }

    /**
     * @Route("/help/covid-19", name="aw_promotions_covid_19")
     * @Security("is_granted('ROLE_USER') && !is_granted('SITE_BUSINESS_AREA')")
     */
    public function indexAction(Request $request)
    {
        $user = $this->tokenStorage->getUser();
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['code' => Coupon::COUPON_HELP_COVID19_3MONTHS]);

        if (!$coupon) {
            throw new NotFoundHttpException();
        }
        $isUsedCoupon = $user->usedCoupon($coupon);

        $isAwPlus = $this->checker->isGranted('USER_AWPLUS', $user);

        if (!$isUsedCoupon && $request->getMethod() === 'POST' && $request->request->get('use-covid19-coupon')) {
            $cart = $this->cartManager->createNewCart();
            $cart->setPaymenttype(null);
            $cart->addItem(new AwPlus3Months());
            $cart->setCoupon($coupon);
            $this->cartManager->markAsPayed($cart);

            return new RedirectResponse($this->router->generate('aw_timeline'));
        }

        return new Response($this->twig->render(
            '@AwardWalletMain/HelpCovid19/index.html.twig',
            ['isAwPlus' => $isAwPlus, 'isUsedCoupon' => $isUsedCoupon]
        ));
    }
}
