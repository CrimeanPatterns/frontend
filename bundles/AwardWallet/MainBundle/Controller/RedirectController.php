<?php

namespace AwardWallet\MainBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class RedirectController extends AbstractController
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @Route("/redirect/partner", name="aw_redirect_partner")
     * @param string|null $targetUrl
     * @param string|null $preloadUrl
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function partnerAction(Request $request, $targetUrl = null, $preloadUrl = null, LoggerInterface $logger)
    {
        if (!isset($targetUrl)) {
            $targetUrl = $request->get('Goto');
        }

        if (!isset($preloadUrl)) {
            $preloadUrl = $request->get('Preload');
        }

        if (empty($preloadUrl)) {
            $preloadUrl = null;
        }

        if (empty($targetUrl)) {
            $logger->critical('targetUrl is empty');

            return $this->redirect("/");
        }

        return $this->render('@AwardWalletMain/Redirect/partner.html.twig', [
            'targetUrl' => $targetUrl,
            'preloadUrl' => $preloadUrl,
        ]);
    }

    /**
     * @Route("/account/edit.php", name="aw_edit_redirect_partner")
     */
    public function accountEditAction(Request $request)
    {
        $providerId = (int) $request->get('ProviderID');

        if (empty($providerId)) {
            return new Response('Bad request', 400);
        }

        return $this->redirect($this->router->generate('aw_account_add', ['providerId' => $providerId]));
    }

    /**
     * @Route("/user/useCoupon.php", name="aw_redirect_use_coupon")
     */
    public function useCouponAction(Request $request)
    {
        return $this->redirect($this->router->generate('aw_users_usecoupon', $request->query->all()));
    }

    /**
     * @Route("/user/pay.php", name="aw_redirect_user_pay")
     * @Route("/user/10yearAnniversary.php", name="aw_redirect_user_pay_2")
     */
    public function userPayAction(Request $request)
    {
        return $this->redirect($this->router->generate('aw_users_pay'));
    }

    /**
     * @Route("/cards.php", name="aw_redirect_cards")
     */
    public function cardsAction(Request $request)
    {
        return $this->redirect('/blog/credit-cards/');
    }

    /**
     * @Route("/privacy.php", name="aw_redirect_privacy")
     */
    public function privacyAction(Request $request)
    {
        return $this->redirect($this->router->generate('aw_page_index', ["page" => "privacy"]));
    }

    /**
     * @Route("/terms.php", name="aw_redirect_terms")
     */
    public function termsAction(Request $request)
    {
        return $this->redirect($this->router->generate('aw_page_index', ["page" => "terms"]));
    }

    /**
     * @Route("/account/overview.php", name="aw_redirect_overview")
     */
    public function overviewAction()
    {
        return $this->redirect($this->router->generate('aw_account_list'));
    }

    /**
     * @Route("/forum/{any}", name="aw_blog_old_link_redirects", requirements={"any"=".+"})
     */
    public function forumAction(Request $request)
    {
        return $this->redirect('/');
    }
}
