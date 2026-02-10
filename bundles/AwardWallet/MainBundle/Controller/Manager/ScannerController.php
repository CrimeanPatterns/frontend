<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/manager/scanner")
 */
class ScannerController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_SCANNER')")
     * @Route("/", name="aw_manager_scanner")
     */
    public function indexAction(Request $request)
    {
        return $this->render("@AwardWalletMain/Manager/Scanner/index.html.twig");
    }

    /**
     * @Route("/proxy/{path}", name="aw_manager_scanner_proxy", requirements={"path"=".+"})
     * @Security("is_granted('ROLE_MANAGE_SCANNER')")
     */
    public function proxyAction(
        $path,
        Request $request,
        \HttpDriverInterface $httpDriver,
        string $emailApiUrl,
        string $emailApiHttpAuth
    ) {
        if (!preg_match('#^(/\w+)+$#ims', $path)) {
            throw new AccessDeniedHttpException("Invalid path");
        }

        $qs = $request->getQueryString();
        $url = $emailApiUrl . "/admin/scanner" . $path;

        if (!empty($qs)) {
            $url .= "?" . $qs;
        }

        $response = $httpDriver->request(new \HttpDriverRequest(
            $url,
            $request->getMethod(),
            $request->getContent(),
            ['Authorization' => 'Basic ' . base64_encode($emailApiHttpAuth)]
        ));

        return new Response($response->body, $response->httpCode, $response->headers);
    }
}
