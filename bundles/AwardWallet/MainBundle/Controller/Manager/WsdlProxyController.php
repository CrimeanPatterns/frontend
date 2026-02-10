<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/manager/wsdl")
 */
class WsdlProxyController extends AbstractController
{
    /**
     * @Template("@AwardWalletMain/Manager/WsdlProxy/proxy.html.twig")
     * @Route("/proxy/{path}", name="aw_manager_wsdl_proxy", requirements={"path"=".+"})
     * @Security("is_granted('ROLE_MANAGE_WSDL_PROXY')")
     */
    public function proxyAction($path, Request $request, string $wsdlProxyAuth)
    {
        if (!preg_match('#^admin/reports/\w+\.php#ims', $path)) {
            throw new AccessDeniedHttpException("Invalid path");
        }

        //		$host = $this->container->getParameter("wsdl_proxy_url");
        $host = 'https://wsdl-admin.awardwallet.com/';
        $qs = $request->getQueryString();
        $url = $host . $path;
        $url = str_replace('http:', 'https:', $url);

        if (!empty($qs)) {
            $url .= "?" . $qs;
        }

        $response = curlRequest(
            $url,
            180,
            [
                CURLOPT_USERPWD => $wsdlProxyAuth,
                CURLOPT_HTTPHEADER => ['Via-Aw-Proxy: true', 'X-Forwarded-Proto: https'],
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ]
        );

        return ['content' => $response];
    }
}
