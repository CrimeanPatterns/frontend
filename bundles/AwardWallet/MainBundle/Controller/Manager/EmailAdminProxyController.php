<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Email\Api;
use AwardWallet\MainBundle\Email\ApiException;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EmailAdminProxyController extends AbstractController
{
    /**
     * @Route("/manager/emailadmin/{url}", requirements={"url"=".+"})
     * @Security("is_granted('ROLE_MANAGE_MANUALPARSER')")
     */
    public function proxyAction(
        Request $request,
        $url,
        array $emailApiParams,
        AwTokenStorageInterface $tokenStorage,
        Api $emailApi
    ) {
        $regions = array_keys($emailApiParams);
        $region = trim($request->query->get('region', ''));

        if (empty($region)) {
            $region = $regions[0];
        }

        if (!in_array($region, $regions)) {
            throw new BadRequestHttpException('Invalid region');
        }
        $url = '/admin/manager/' . $url;

        if (!empty($request->getQueryString())) {
            $url .= '?' . $request->getQueryString();
        }
        $requestHeaders = [
            'Email-Admin-User-Id:' . $tokenStorage->getBusinessUser()->getId(),
            'Email-Admin-User-Login:' . $tokenStorage->getBusinessUser()->getLogin(),
            'Email-Admin-User-Name:' . $tokenStorage->getBusinessUser()->getFullName(),
            'Email-Admin-User-Time-Offset:' . $tokenStorage->getBusinessUser()->getDateTimeZone()->getOffset(new \DateTime()),
        ];

        try {
            $result = $emailApi->proxyCall($url, $request, $headers, $requestHeaders, $region);
            $params = ['headers' => $headers, 'content' => $result, 'title' => $headers['Email-Admin-Title']];
        } catch (ApiException $e) {
            return new Response($e->getMessage(), $e->getCode());
        }

        if (!empty($headers['Location'])) {
            return new RedirectResponse('/manager/emailadmin/' . ltrim($headers['Location'], '/'));
        }

        if (($headers['Content-Type'] ?? '') == 'application/json') {
            return new JsonResponse($result, 200, [], true);
        } elseif (!empty($headers['Email-Admin-No-Html'])) {
            $response = new Response($result);

            foreach (['Content-Type', 'Content-Disposition'] as $hName) {
                if (!empty($headers[$hName])) {
                    $response->headers->set($hName, $headers[$hName]);
                }
            }

            return $response;
        }

        return $this->render('@AwardWalletMain/Manager/EmailAdminProxy/index.html.twig', $params);
    }
}
