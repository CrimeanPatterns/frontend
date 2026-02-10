<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Email\Api;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

// later email2 need renamed to email
// принцип таков, что frontend.com/manager/email/... == email.com/email/...
// пока не отлажено, как тест frontend.com/manager/email2/... == email.com/email/...
// обработка 1в1 т.е. за currentURL не будет прятаться ничего. если редирект. то редирект по адресу согласно правила интерпретации
/**
 * @Route("/manager/email2")
 */
class EmailIntegrationController extends AbstractController
{
    /**
     * @Route("/{url}", name="aw_manager_email")
     * @throws \AwardWallet\MainBundle\Email\ApiException
     */
    public function indexAction(Request $request, Api $emailApi, $emailApiConfig)
    {
        $regions = array_keys($emailApiConfig);
        $region = trim($request->query->get('region', ''));

        if (empty($region)) {
            $region = $regions[0];
        }

        if (!in_array($region, $regions)) {
            throw new BadRequestHttpException('Invalid region');
        }

        return array_merge($emailApi->call("admin/stat/request", false, [], [], false, $region), ['regions' => $regions, 'region' => $region]);
    }
}
