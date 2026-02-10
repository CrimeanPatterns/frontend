<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Email\Api;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EmailStatController extends AbstractController
{
    /**
     * @Route("/manager/email/stat/{type}", name="aw_manager_emailstat_index", methods={"GET"}, requirements={"type" = "thismonth|lastmonth|thisyear|lastyear|alltime|financial|daily|monthly"}, defaults={"type" = "thismonth"})
     * @Security("is_granted('ROLE_MANAGE_EMAILSTAT')")
     * @throws \AwardWallet\MainBundle\Email\ApiException
     */
    public function indexAction(Request $request, Api $emailApi, string $type, $emailApiConfig)
    {
        $regions = array_keys($emailApiConfig);
        $region = trim($request->query->get('region', ''));

        if (empty($region)) {
            $region = $regions[0];
        }

        if (!in_array($region, $regions)) {
            throw new BadRequestHttpException('Invalid region');
        }

        return $this->render('@AwardWalletMain/Manager/EmailStat/index.html.twig', array_merge($emailApi->call("admin/stat/request/{$type}", false, [], [], false, $region), ['regions' => $regions, 'region' => $region]));

        // todo:: remove after deploying europe
        if ($region === 'eu-central-1') {
            return $this->render('@AwardWalletMain/Manager/EmailStat/indexOld.html.twig', array_merge($emailApi->call("admin/stat/request", false, [], [], false, $region), ['regions' => $regions, 'region' => $region]));
        } else {
            return $this->render('@AwardWalletMain/Manager/EmailStat/index.html.twig', array_merge($emailApi->call("admin/stat/request/{$type}", false, [], [], false, $region), ['regions' => $regions, 'region' => $region]));
        }
    }
}
