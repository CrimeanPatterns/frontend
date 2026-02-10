<?php

namespace AwardWallet\MainBundle\Controller\WebPush;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/web-push/test")
 */
class TestController extends AbstractController
{
    /**
     * @Route("/anonymous", name="aw_webpush_test_anonymous")
     * @Template("@AwardWalletMain/WebPush/Test/testAnonymous.html.twig")
     */
    public function testAnonymousAction(string $vapidPublicKey, string $webpushIdParam)
    {
        return [
            'vapid_public_key' => $vapidPublicKey,
            'webpush_id' => $webpushIdParam,
        ];
    }
}
