<?php

namespace AwardWallet\MainBundle\Controller\Manager\ML;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class MlAdminProxyController
{
    private string $mlParsingAdminEndPoint;
    private \HttpDriverInterface $httpDriver;

    public function __construct(string $mlParsingAdminEndPoint, \HttpDriverInterface $httpDriver)
    {
        $this->mlParsingAdminEndPoint = $mlParsingAdminEndPoint;
        $this->httpDriver = $httpDriver;
    }

    /**
     * @Route("/manager/ml-parsing/{path}", name="aw_manager_mladmin_proxy", requirements={"path"=".+"})
     * @Security("is_granted('ROLE_MANAGE_MLPARSING')")
     */
    public function proxyAction($path, Request $request)
    {
        if (!preg_match('#^([\w-]+/)*[\w-]+$#ims', $path)) {
            throw new AccessDeniedHttpException("Invalid path");
        }

        $qs = $request->getQueryString();
        $servers = dns_get_record('web.ml-parsing', DNS_SRV);

        if ($servers === false) {
            return new Response("No live backend servers", 503);
        }

        $server = $servers[array_rand($servers)];
        $url = 'http://' . $server['target'] . "/manager/ml-parsing/" . $path;

        if (!empty($qs)) {
            $url .= "?" . $qs;
        }

        $response = $this->httpDriver->request(new \HttpDriverRequest(
            $url,
            $request->getMethod(),
            $request->getContent(),
        ));

        if ($response->httpCode < 200) {
            return new Response("network error: " . $response->errorCode . " " . $response->errorMessage . " while contacting $url");
        }

        return new Response($response->body, $response->httpCode, $response->headers);
    }
}
